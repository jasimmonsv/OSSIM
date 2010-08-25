#!/usr/bin/python
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
import threading, os, gzip, time, glob, time

#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
from OssimDB import OssimDB
from OssimConf import OssimConf

#
# GLOBAL VARIABLES
#
logger = Logger.logger

class AlarmGroup (threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)

    def __startup (self):
        self._CONF  = OssimConf(Const.CONFIG_FILE)
        self._DB    = OssimDB()
        self._DB.connect(self._CONF['ossim_host'],
                     self._CONF['ossim_base'],
                     self._CONF['ossim_user'],
                     self._CONF['ossim_pass'])

    
    def __cleanup (self):
        self._DB.close()

    def run (self):
        self.__startup()
    
        while True:
            notgrouped = self.get_not_grouped()
            if (len(notgrouped) > 0):
                logger.info("Grouping %s alarms" % str(len(notgrouped)))

                for alarm in notgrouped:
                   self.group_alarm(alarm['original_bl'], alarm['original_ei'])

                # Close group if all alarms in group are closed
                for alarm in notgrouped:
                     self.close_group(alarm['original_bl'], alarm['original_ei'])

            else:
                logger.info("No alarms to group")

            time.sleep(300)
            

    def get_not_grouped(self):

        #query = "SELECT * from alarm"
        #query = "select alarm.backlog_id as original, alarm_group_members.backlog_id as grouped, alarm.timestamp from alarm left join alarm_group_members on alarm.backlog_id=alarm_group_members.backlog_id where alarm_group_members.backlog_id is null order by timestamp"
        query = "select alarm.backlog_id as original_bl, alarm.event_id as original_ei, alarm_group_members.backlog_id as grouped_id, alarm_group_members.event_id as grouped_ei, alarm.timestamp from alarm left join alarm_group_members on alarm.backlog_id=alarm_group_members.backlog_id and alarm.event_id=alarm_group_members.event_id where alarm_group_members.backlog_id is null order by timestamp"

        result = self._DB.exec_query(query)

        return result

    def close_group(self, backlog_id, event_id):
        # Look for group_id of alarm
        query = 'select group_id from alarm_group_members where event_id=\'' + str(event_id) + '\' and backlog_id=\'' + str(backlog_id) + '\''
        group_id = self._DB.exec_query(query)

        if group_id:
            group_id = group_id[0]
            query = 'select alarm.status from alarm_group_members, alarm where alarm_group_members.backlog_id=alarm.backlog_id and alarm_group_members.event_id=alarm.event_id and alarm_group_members.group_id=\'' + str(group_id['group_id']) + '\' and alarm.status=\'open\''
            open_alarms = self._DB.exec_query(query)
            if not open_alarms:
                logger.info("Closing group %s" % str(group_id['group_id']))
                query = 'UPDATE alarm_group SET status=\'closed\' WHERE id=\'' + str(group_id['group_id']) + '\''
                self._DB.exec_query(query)
                
    def group_alarm(self, backlog_id, event_id):
        # Check type && (source || dest)
        logger.info("Processing alarm: %s %s " % (str(backlog_id), str(event_id)))
        
        # Src, dst of alarm
        alarm_data=self.get_alarm_data(backlog_id, event_id)[0] 

        # Query constructor
        start = '\''+str(alarm_data['timestamp'].year)+'-'+str(alarm_data['timestamp'].month)+'-'+str(alarm_data['timestamp'].day)+' 00:00:00\''
        end = '\''+str(alarm_data['timestamp'].year)+'-'+str(alarm_data['timestamp'].month)+'-'+str(alarm_data['timestamp'].day)+' 23:59:59\''
        query = 'select * from alarm where timestamp > ' +start + ' and timestamp < ' + end

        # Get alarms on same day
        day_alarms = self._DB.exec_query(query)
        
        for i in day_alarms:
            # Check if we have same plugin_id and plugin_sid
            if (alarm_data['plugin_id'] == i['plugin_id'] and alarm_data['plugin_sid'] == i['plugin_sid']):
                # Check if source or destination are the same
                if ((alarm_data['src_ip'] == i['src_ip'] and i['src_ip'] != 0 ) or (alarm_data['dst_ip'] == i['dst_ip'] and i['dst_ip'] != 0)):
                    # Look for alarm_group
                    group=self.get_group_id(i['backlog_id'], i['event_id'])
                    if group:
#                       print "Insertando en grupo antiguo: ", group
                        self.add_alarm2group(group,backlog_id, event_id)
                        return 0

        # Create new group
        group=self.create_new_group(alarm_data['timestamp'])
        self.add_alarm2group(group,backlog_id, event_id)

        # Create new group if necessary
        # Insert alarm into group
        return 1

    def create_new_group(self, timestamp):
        query = 'insert into alarm_group (timestamp, descr) values (\'' + str(timestamp) + '\',"")'
        self._DB.exec_query(query)
        query = "select id from alarm_group order by id desc limit 1"
        result = self._DB.exec_query(query)

        return result[0]['id']

    def add_alarm2group(self, group_id, backlog_id, event_id):
        query = 'insert into alarm_group_members (group_id,backlog_id,event_id) values (' + str(group_id) + ',' + str(backlog_id) + ',' + str(event_id) + ')'

        result = self._DB.exec_query(query)

        return result

    def get_alarm_data(self, backlog_id, event_id):
        query = "select plugin_id, plugin_sid, src_ip, dst_ip, timestamp from alarm where backlog_id="+str(backlog_id) + " and event_id=" + str(event_id)

        result = self._DB.exec_query(query)

        return result

    def get_group_id(self, backlog_id, event_id):
        query = "select group_id from alarm_group_members where backlog_id=" + str(backlog_id) + " and event_id=" + str(event_id)

        result = self._DB.exec_query(query)
        if result:
            return result[0]['group_id']
        else:
            return []
        
if __name__ == '__main__':
    ag=AlarmGroup()
    ag.start()
