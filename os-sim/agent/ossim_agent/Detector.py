#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import threading
import time

#
# LOCAL IMPORTS
#
import Config
from ConnPro import ServerConnPro
import Event
from Logger import Logger
from Output import Output
from Stats import Stats
from Threshold import EventConsolidation
import re
#from datetime import datetime, timedelta
from pytz import timezone, all_timezones
#import pytz
from time import mktime, gmtime, strftime
import Utils

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Detector(threading.Thread):

    def __init__(self, conf, plugin, conn):

        self._conf = conf
        self._plugin = plugin
        self.os_hash = {}
        self.conn = conn
        self.consolidation = EventConsolidation(self._conf)
        if "tzone" in self._plugin.hitems("DEFAULT"):
            self._timezone = self._plugin.get("DEFAULT", "tzone")
            logger.debug("Plugin %s (%s) with specific tzone = %s" % \
                         (self._plugin.get("config", "name"),
                          self._plugin.get("DEFAULT", "plugin_id"),
                          self._timezone))
        else:
            self._timezone = self._conf.get("plugin-defaults", "tzone")

        logger.info("Starting detector %s (%s).." % \
                    (self._plugin.get("config", "name"),
                     self._plugin.get("DEFAULT", "plugin_id")))
        threading.Thread.__init__(self)
        self._agenttimezone = self._conf.get("plugin-defaults", "tzone")
        self._EventTimeZone = None
        #2011-02-01 17:00:16
#        self.patternISO_date = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)')
#        self.patternUTClocalized = re.compile('(?P<year>\d+)[\s-](?P<month>\d+)[\s-](?P<day>\d+)\s+(?P<hour>\d+):(?P<minute>\d+):(?P<second>\d+)(?P<tzone_symbol>[-|+])(?P<tzone_hour>\d{2}):(?P<tzone_min>\d{2})')
        self.checkTimeZone()

    def checkTimeZone(self):
        if self._timezone in all_timezones:
            used_tzone = self._timezone
            logger.debug("Using custom plugin tzone data: %s" % used_tzone)
        elif self._agenttimezone in all_timezones:
            used_tzone = self._agenttimezone
            logger.info("Warning: Invalid plugin tzone information. Using agent tzone: %s" % used_tzone)
        else:
            try:
                #read local timezone information. 
                f = open('/etc/timezone', 'r')
                used_tzone = f.readline().rstrip()
                f.close()
                if used_tzone not in all_timezones:
                    logger.info("Warning, we can't read valid timezone data.Using GMT")
                    used_tzone = 'GMT'
            except e:
                used_tzone = 'GMT'
                logger.info("Warning, we can't read valid timezone data.Using GMT")
            logger.info("Warning: Invalid plugin tzone and invalid agent tzone, using system tzone: %s" % used_tzone)
        self._EventTimeZone = used_tzone

    def _event_os_cached(self, event):

        if isinstance(event, Event.EventOS):
            import string
            current_os = string.join(string.split(event["os"]), ' ')
            previous_os = self.os_hash.get(event["host"], '')

            if current_os == previous_os:
                return True

            else:
                # Fallthrough and add to cache
                self.os_hash[event["host"]] = \
                    string.join(string.split(event["os"]), ' ')

        return False


    def _exclude_event(self, event):

        if self._plugin.has_option("config", "exclude_sids"):
            exclude_sids = self._plugin.get("config", "exclude_sids")
            if event["plugin_sid"] in Config.split_sids(exclude_sids):
                logger.debug("Excluding event with " + \
                    "plugin_id=%s and plugin_sid=%s" % \
                    (event["plugin_id"], event["plugin_sid"]))
                return True

        return False

    def _thresholding(self):
        """
        This section should contain:
          - Absolute thresholding by plugin, src, etc...
          - Time based thresholding
          - Consolidation
        """

        self.consolidation.process()


    def _plugin_defaults(self, event):
        ipv4_reg = "\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}"
        # get default values from config
        #
        if self._conf.has_section("plugin-defaults"):

        # 1) date
            default_date_format = self._conf.get("plugin-defaults",
                                                 "date_format")
            if event["date"] is None and default_date_format and \
               'date' in event.EVENT_ATTRS:
                event["date"] = time.strftime(default_date_format,
                                              time.localtime(time.time()))

        # 2) sensor
            default_sensor = self._conf.get("plugin-defaults", "sensor")
            if event["sensor"] is None  and default_sensor and \
               'sensor' in event.EVENT_ATTRS:
                event["sensor"] = default_sensor

        # 3) interface
            default_iface = self._conf.get("plugin-defaults", "interface")
            if event["interface"] is None and default_iface and \
               'interface' in event.EVENT_ATTRS:
                event["interface"] = default_iface

        # 4) source ip
            if event["src_ip"] is None and 'src_ip' in event.EVENT_ATTRS:
                event["src_ip"] = event["sensor"]

        # 5) Time zone 
            #default_tzone = self._conf.get("plugin-defaults", "tzone")
            if 'tzone' in event.EVENT_ATTRS:
                Utils.normalizeToUTCDate(event, self._EventTimeZone)
        # 6) sensor,source ip and dest != localhost
            if event["sensor"] in ('127.0.0.1', '127.0.1.1'):
                event["sensor"] = default_sensor

            if event["dst_ip"] in ('127.0.0.1', '127.0.1.1'):
                event["dst_ip"] = default_sensor

            if event["src_ip"] in ('127.0.0.1', '127.0.1.1'):
                event["src_ip"] = default_sensor

            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['src_ip'] is not None:
                if not re.match(ipv4_reg, event['src_ip']):
                    data = event['src_ip']
                    event['src_ip'] = '0.0.0.0'
                    logger.warning("Event's field src_ip (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata8" % data)
                    event['userdata8'] = data
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['dst_ip'] is not None:
                if not re.match(ipv4_reg, event['dst_ip']):
                    data = event['dst_ip']
                    logger.warning("Event's field dst_ip (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata9" % data)
                    event['dst_ip'] = '0.0.0.0'
                    event['userdata9'] = data
            #Check if valid ip, if not we put 0.0.0.0 in sensor field
            if event['sensor'] is not None:
                if not re.match(ipv4_reg, event['sensor']):
                    data = event['sensor']
                    logger.warning("Event's field sensor (%s) is not a valid IP.v4 address, set it to default ip 0.0.0.0 and real data on userdata7" % data)
                    event['sensor'] = '0.0.0.0'
                    event['userdata7'] = data

        # the type of this event should always be 'detector'
        if event["type"] is None and 'type' in event.EVENT_ATTRS:
            event["type"] = 'detector'



        return event


    def send_message(self, event):

        if self._event_os_cached(event):
            return

        if self._exclude_event(event):
            return

        # use default values for some empty attributes
        event = self._plugin_defaults(event)

        Output.event(event)
        Stats.new_event(event)
        return
        # check for consolidation
        if self.conn is not None:
            try:
                self.conn.send(str(event))
            except:
                id = self._plugin.get("config", "plugin_id")
                c = ServerConnPro(self._conf, id)
                self.conn = c.connect(0, 10)
                try:
                    self.conn.send(str(event))
                except:
                    return

            logger.info(str(event).rstrip())

        elif not self.consolidation.insert(event):
            Output.event(event)

        Stats.new_event(event)


    def stop(self):
        #self.consolidation.clear()
        pass

    def process(self):
        """Process method placeholder.

        NOTE: Must be overriden in child classes.
        """
        pass


    def run(self):
        self.process()



class ParserSocket(Detector):

    def process(self):
        self.process()



class ParserDatabase(Detector):

    def process(self):
        self.process()



class ParserWMI(Detector):

    def process(self):
        self.process()



# vim:ts=4 sts=4 tw=79 expandtab:
