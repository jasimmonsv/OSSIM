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
import os
import re
import socket
import sys
import time

#
# LOCAL IMPORTS
#
from Config import Plugin
from Detector import Detector
from Event import Event, EventOS, EventMac, EventService, EventHids
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger

#
# CRITICAL IMPORTS
#
try:
    import MySQLdb

except ImportError:
    logger.critical("You need python mysqldb module installed")

try:
    import pymssql

except ImportError:
    logger.critical("You need python pymssql module installed")

"""
Parser Database
TODO:
TRANSLATIONS support
"""



class ParserDatabase(Detector):

    def __init__(self, conf, plugin, conn):
        self._conf = conf
        self._plugin = plugin
        self.rules = []          # list of RuleMatch objects
        self.conn = conn
        Detector.__init__(self, conf, plugin, conn)


    def process(self):
        logger.info("Started")
        rules = self._plugin.rules()
        #logger.info(rules['start_query']['query'])
        #if rules['start_query']['query']

        test = None

        if self._plugin.get("config", "source_type") == "mysql":

            #Test Connection
            try:
                cursor = self.connectMysql()
                logger.info("Connection OK")
                test = 1

            except:
                logger.info("Can't connect to MySQL database")

        elif self._plugin.get("config", "source_type") == "mssql":

            try:
                cursor = self.connectMssql()
                logger.info("Connection OK")
                test = 1

            except:
                logger.info("Can't connect to MS-SQL database")

        else:
            logger.info("Not supported database")

            if test:
                sql = rules['start_query']['query']
                logger.info(sql)
                cursor.execute(sql)
                rows= cursor.fetchone()

                if not rows:
                    logger.warning("Initial query empty, please double-check")
                    return

                cVal = str(int(rows[0]))
                logger.info(cVal)
                #logger.info(cVal)
                tSleep = self._plugin.get("config", "sleep")
                sql = rules['query']['query']
                #logger.info(sql)
                ref = int(rules['query']['ref'])

                logger.info(ref)

                while 1:
                    logger.info("Querying Database")
                    sql = rules['query']['query']
                    sql = sql.replace("$1", str(cVal))
                    logger.info(sql)
                    cursor.execute(sql)
                    ret = cursor.fetchall()

                    if len(ret) > 0:
                        cVal = ret[len(ret) - 1][0]

                        for e in ret:
                            self.generate(e)

                    time.sleep(int(tSleep))


    def connectMysql(self):
        logger.info("here")
        host = self._plugin.get("config", "source_ip")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        db=MySQLdb.connect(host=host,user=user, passwd=passwd,db=db)
        cursor=db.cursor()

        return cursor


    def connectMssql(self):
        host = self._plugin.get("config", "source_ip")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        db = pymssql.connect(host=host, user=user, password=passwd, database=db)
        cursor=db.cursor()

        return cursor


    def connectOracle():

        pass


    def generate(self, groups):

        event = Event()
        rules = self._plugin.rules()

        for key, value in rules['query'].iteritems():
            if key != "query" and key != "regexp" and key != "ref":
                #logger.info("Request")
                event[key] = self._plugin.get_replace_array_value(value, groups)
                #event[key] = self.get_replace_value(value, groups)
                #self.plugin.get_replace_value

        if event is not None:
            self.send_message(event)

