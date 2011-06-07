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
import os
import time
import commands
#
# LOCAL IMPORTS
#
import Const
from OssimDB import OssimDB
from OssimConf import OssimConf

from Logger import Logger
logger = Logger.logger

class ApacheNtopProxyManager:
    def __init__(self,conf):
        self.__newConfigFileTemplateName = "/etc/apache2/conf.d/ntop-%s.conf" 
        self.__sensorVar = "$(SENSOR_IP)"
        self.__myDB = OssimDB()
        self.__myDB_connected = False
        self.__myconf = conf
        self.__sensors = []


    def __reloadApache(self):
        logger.info("Reloading apache...")
        status, output = commands.getstatusoutput('/etc/init.d/apache2 reload')
        if status == 0:
            logger.info ("Reloading apache  .... OK")
        else:
            logger.error("Reloading apache  .... FAIL ..status code:%s" % status )

    def __getSensorList(self):
        if not self.__myDB_connected:
            self.__myDB.connect (self.__myconf["ossim_host"],
            self.__myconf["ossim_base"],
            self.__myconf["ossim_user"],
            self.__myconf["ossim_pass"])
            self.__myDB_connected = True
        #read sensor list.
        query = 'select ip from sensor;' 
        tmp = self.__myDB.exec_query(query)
        for sensor in tmp:
            self.__sensors.append(sensor['ip'])
        self.__myDB.close()
        self.__myDB_connected = False
        
            
    def __buildNtopConfigurationForSensor(self,sensor_ip):
        #Create a the new file:
        newfile_name = self.__newConfigFileTemplateName % sensor_ip
        logger.info("Creating ntop proxy configuration %s" % newfile_name)        
        new_config_file  = open(newfile_name,'w')
        template = open(Const.NTOP_APACHE_PROXY_TEMPLATE)
        for line in template:
            new_config_file.write(line.replace(self.__sensorVar, sensor_ip))
        new_config_file.close()
        template.close()
        

    def refreshConfiguration(self):
        #remove old configuration
        status, output = commands.getstatusoutput(' rm /etc/apache2/conf.d/ntop-*.conf')
        if not os.path.isfile(Const.NTOP_APACHE_PROXY_TEMPLATE):
            logger.error("I can't create Ntop proxy configurations. Template file: %s not exist!" % Const.NTOP_APACHE_PROXY_TEMPLATE)
        else:
            self.__getSensorList()
            time.sleep(1)
            logger.info("Sensors are loaded")
            for sensor in self.__sensors:
                self.__buildNtopConfigurationForSensor(sensor)
            self.__reloadApache()
