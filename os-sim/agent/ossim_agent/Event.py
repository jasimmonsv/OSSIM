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
from time import mktime, strptime, time
from base64 import b64encode
from datetime import datetime

#
# LOCAL IMPORTS
#
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Event:
    EVENT_BASE64 = [
        'username',
        'password',
        'filename',
        'userdata1',
        'userdata2',
        'userdata3',
        'userdata4',
        'userdata5',
        'userdata6',
        'userdata7',
        'userdata8',
        'userdata9', 'log']
    EVENT_TYPE = 'event'
    EVENT_ATTRS = [
        "type",
        "date",
        "sensor",
        "interface",
        "plugin_id",
        "plugin_sid",
        "priority",
        "protocol",
        "src_ip",
        "src_port",
        "dst_ip",
        "dst_port",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "data",
        "snort_sid", # snort specific
        "snort_cid", # snort specific
        "fdate",
        "tzone"
    ]


    def __init__(self):
        self.event = {}
        self.event["event_type"] = self.EVENT_TYPE
        self.normalized = False
    def __setitem__(self, key, value):

        if key in self.EVENT_ATTRS:
            if key in self.EVENT_BASE64:
                self.event[key] = b64encode (value)
            else:
                self.event[key] = value#self.sanitize_value(value)
            if key == "date" and not self.normalized:
                # Fill with a default date.
                date_epoch = int(time())
                # Try first for string dates.
                try:
                    date_epoch = int(mktime(strptime(value, "%Y-%m-%d %H:%M:%S")))
                    self.event["fdate"] = value
                    self.event["date"] = date_epoch
                    self.normalized = True
                except (ValueError):
                    logger.warning("There was an error parsing a string date (%s)" % (value))

                # Do not allow dates in the future.
#                if date > int(time()):
#                    logger.warning("Detected date in the future (%s), please check your device date" % (self.event[key]))
#                
                # fdate as date is coming.
                #fdate_utc = datetime.utcfromtimestamp(date).isoformat(" ")
                # Later in Detector._plugin_defualt, we normalized the datetime



        elif key != 'event_type':
            logger.warning("Bad event attribute: %s" % (key))

    def __getitem__(self, key):
        return self.event.get(key, None)


    def __repr__(self):
        """Event representation."""
        event = self.EVENT_TYPE

        for attr in self.EVENT_ATTRS:
            if self[attr]:
                event += ' %s="%s"' % (attr, self[attr])

        return event + "\n"


    def dict(self):
        # return the internal hash
        return self.event


    def sanitize_value(self, string):
        return str(string).strip().replace("\"", "\\\"").replace("'", "")



class EventOS(Event):

    EVENT_TYPE = 'host-os-event'
    EVENT_ATTRS = [
        "host",
        "os",
        "sensor",
        "interface",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
        "tzone",
        "src_ip",
        "dst_ip",
    ]



class EventMac(Event):

    EVENT_TYPE = 'host-mac-event'
    EVENT_ATTRS = [
        "host",
        "mac",
        "vendor",
        "sensor",
        "interface",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
        "tzone",
        "src_ip",
        "dst_ip",
    ]



class EventService(Event):

    EVENT_TYPE = 'host-service-event'
    EVENT_ATTRS = [
        "host",
        "sensor",
        "interface",
        "port",
        "protocol",
        "service",
        "application",
        "date",
        "plugin_id",
        "plugin_sid",
        "occurrences",
        "log",
        "fdate",
        "tzone",
        "src_ip",
        "dst_ip",
    ]



class EventHids(Event):

    EVENT_TYPE = 'host-ids-event'
    EVENT_ATTRS = [
        "host",
        "hostname",
        "hids_event_type",
        "target",
        "what",
        "extra_data",
        "sensor",
        "date",
        "plugin_id",
        "plugin_sid",
        "username",
        "password",
        "filename",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "occurrences",
        "log",
        "fdate",
        "tzone",
        "src_ip",
        "dst_ip",
    ]



class WatchRule(Event):

    EVENT_TYPE = 'event'
    EVENT_ATTRS = [
        "type",
	"date",
	"fdate",
	"sensor",
	"interface",
	"src_ip",
	"dst_ip",
	"protocol",
        "plugin_id",
        "plugin_sid",
        "condition",
        "value",
        "port_from",
        "src_port",
        "port_to",
        "dst_port",
        "interval",
        "from",
        "to",
        "absolute",
	"log",
        "userdata1",
        "userdata2",
        "userdata3",
        "userdata4",
        "userdata5",
        "userdata6",
        "userdata7",
        "userdata8",
        "userdata9",
        "filename",
        "username",
    ]



class Snort(Event):

    EVENT_TYPE = 'snort-event'
    EVENT_ATTRS = [
        "sensor",
        "interface",
        "gzipdata",
        "unziplen",
        "event_type",
        "plugin_id",
        "type",
        "occurrences",
        "date",
        "src_ip",
        "dst_ip",
        "fdate",
        "tzone",
    ]

# vim:ts=4 sts=4 tw=79 expandtab:
