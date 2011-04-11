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
import pdb

#
# GLOBAL VARIABLES
#
logger = Logger.logger

#
# CRITICAL IMPORTS
#
#try:
#    import MySQLdb
#
#except ImportError:
#    logger.info("You need python mysqldb module installed")
#try:
#    import pymssql
#
#except ImportError:
#    logger.info("You need python pymssql module installed")
#    
#try:
#    import cx_Oracle
#except ImportError:
#    logger.info("You need python cx_Oracle module installed. This is not an error if you aren't using an Oracle plugin")
#try:
#    import ibm_db
#except ImportError:
#    logger.info("You need python ibm_db module installed. This is not an error if you aren't using an IBM plugin")

"""
Parser Database
"""

try:
    import ibm_db
    db2notloaded = False
except ImportError:
    db2notloaded = True

try:
    import MySQLdb
    mysqlnotloaded = False
except ImportError:
    mysqlnotloaded = True
try:
    import pymssql
    mssqlnotloaded = False
except ImportError:
    mssqlnotloaded = True
try:
    import cx_Oracle
    oraclenotloaded = False
except ImportError:
    oraclenotloaded = True
MAX_TRIES_DB_CONNECT = 10

class ParserDatabase(Detector):
    def __init__(self, conf, plugin, conn):
        self._conf = conf
        self._plugin = plugin
        self.rules = []      # list of RuleMatch objects
        self.conn = conn
        Detector.__init__(self, conf, plugin, conn)
        self.__myDataBaseCursor = None
        self.__tries = 0
        self.stop_processing = False
        self._databasetype = self._plugin.get("config", "source_type")
        self._canrun = True
        if self._databasetype == "db2" and db2notloaded:
            logger.info("You need python ibm_db module installed. This is not an error if you aren't using an IBM plugin")
            self._canrun = False
        elif self._databasetype == "mysql" and mysqlnotloaded:
            logger.info("You need python mysqldb module installed")
            self._canrun = False
            self.stop()
        elif self._databasetype == "oracle" and oraclenotloaded:
            logger.info("You need python cx_Oracle module installed. This is not an error if you aren't using an Oracle plugin")
            self._canrun = False
        elif self._databasetype == "mssql" and mssqlnotloaded:
            logger.info("You need python pymssql module installed")
            self._canrun = False


    def runStartQuery(self, plugin_source_type, rules):
        cVal = 0
        if self.__myDataBaseCursor is None:
            return cVal

        if plugin_source_type != "db2":
            sql = rules['start_query']['query']
            logger.debug("Running Start query: %s" % sql)
            self.__myDataBaseCursor.execute(sql)
            rows = self.__myDataBaseCursor.fetchone()
            if not rows:
                logger.warning("Initial query empty, please double-check")
                return
            cVal = str((rows[0]))

            try:
                cVal = int(cVal)
            except ValueError, e:
                #logger.info("cVal None, no data. <%s>" % cVal)
                cVal = 0
            #logger.info("cVal = %s" % cVal)
            tSleep = self._plugin.get("config", "sleep")
            sql = rules['query']['query']
            #logger.info(sql)

            #logger.info(ref)
            #cursor.close()
        elif plugin_source_type == "db2":
            sql = rules['start_query']['query']
            logger.debug("Start query: %:s" % sql)
            result = ibm_db.exec_immediate(self.__myDataBaseCursor, sql)
            dictionary = ibm_db.fetch_both(result)
            if not dictionary:
                logger.warning("Initial query empty, please double-check")
                return
            cVal = str((dictionary[0]))
            try:
                cVal = int(cVal)
            except ValueError, e:
                logger.debug("cVal None, no data. <%s>" % cVal)

                cVal = 0
            #logger.info(cVal)

            #ref = int(rules['query']['ref'])
            #logger.info(ref)
            logger.info("Connection closed")
        return cVal


    def openDataBaseCursor(self, database_type):
        opennedCursor = False
        if database_type == "mysql":
            #Test Connection
            try:
                self.__myDataBaseCursor = self.connectMysql()
                #logger.info("Connection OK")
                opennedCursor = True
            except:
                logger.info("Can't connect to MySQL database")
        elif database_type == "mssql":
            try:
                self.__myDataBaseCursor = self.connectMssql()
#                logger.info("Connection OK")
                opennedCursor = True
            except:
                logger.info("Can't connect to MS-SQL database")
        elif database_type == "oracle":
            try:
                self.__myDataBaseCursor = self.connectOracle()
#                logger.info("Connection OK")
                opennedCursor = True
            except:
                logger.info("Can't connect to Oracle database")
        elif database_type == "db2":
            try:
                self.__myDataBaseCursor = self.connectDB2()
                if self.__myDataBaseCursor:
#                    logger.info("Connection OK")
                    opennedCursor = True
                else:
                    logger.info("Can't connect to DB2 database")
            except:
                logger.info("Can't connect to DB2 database")
        else:
            logger.info("Not supported database")
        return opennedCursor


    def closeDBCursor(self):
        if self.__myDataBaseCursor is not None:
            self.__myDataBaseCursor.close()


    def stop(self):
        logger.info("Stopping database parser...")
        self.stop_processing = True
        try:
            self.closeDBCursor()
            self.join()
        except RuntimeError:
            logger.warning("Stopping thread that likely hasn't started.")


    def tryConnectDB(self):
        connected = False
        while not self.openDataBaseCursor(self._plugin.get("config", "source_type")) and self.__tries < MAX_TRIES_DB_CONNECT:
            logger.info("We cant connect to data base, retrying in 10 seconds....try:%d", self.__tries)
            self.__tries += 1
            time.sleep(10)
        else:
            connected = True
            self.__tries = 0
        return connected


    def process(self):
        tSleep = self._plugin.get("config", "sleep")
        if not self._canrun:
            logger.info("We can't start the process,needed modules")
            return
        logger.info("Starting Database plugin")
        rules = self._plugin.rules()
        run_process = False
        #logger.info(rules['start_query']['query'])
        #if rules['start_query']['query']
        if not self.tryConnectDB():
            self.stop()
            return

        cVal = 0
        plugin_source_type = self._plugin.get("config", "source_type")
        while cVal == 0 and not self.stop_processing:
            cVal = self.runStartQuery(plugin_source_type, rules)
            if cVal <= 0:
                logger.info("Waiting for next pooling...no data")
                time.sleep(10)
            else:
                run_process = True
        ref = int(rules['query']['ref'])
        while run_process and not self.stop_processing:
            if self._plugin.get("config", "source_type") != "db2":
                sql = rules['query']['query']
                sql = sql.replace("$1", str(cVal))
                logger.debug(sql)
                self.__myDataBaseCursor.execute(sql)
                ret = self.__myDataBaseCursor.fetchall()

                if len(ret) > 0:
                    #We have to think about event order when processing
                    cVal = ret[len(ret) - 1][ref]
                    for e in ret:
                        #pdb.set_trace()
                        self.generate(e)
                #cursor.close()
                time.sleep(int(tSleep))
            else:
                sql = rules['query']['query']
                sql = sql.replace("$1", str(cVal))
                logger.debug(sql)
                result = ibm_db.exec_immediate(self.__myDataBaseCursor, sql)
                dictionary = ibm_db.fetch_both(result)
                #print dictionary
                ret = []
                while dictionary != False:
                    ret1 = []
                    for i in dictionary.keys():
                        ret1.append(dictionary[i])
                    ret.append(ret1)
                    dictionary = ibm_db.fetch_both(result)
                cVal = ret[len(ret) - 1][ref]
                for e in ret:
                    logger.info("-.-->", e)
                    self.generate(e)
                time.sleep(int(tSleep))


    def connectMysql(self):
        #logger.info("here")
        host = self._plugin.get("config", "source_ip")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        try:
            db = MySQLdb.connect(host=host, user=user, passwd=passwd, db=db)
        except Exception, e:
            logger.error("We can't connecto to database: %s" % e)
            return None
        cursor = db.cursor()
        return cursor


    def connectMssql(self):
        host = self._plugin.get("config", "source_ip")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        db = self._plugin.get("config", "db")
        db = pymssql.connect(host=host, user=user, password=passwd, database=db)
        cursor = db.cursor()
        return cursor


    def connectOracle(self):
        dsn = self._plugin.get("config", "dsn")
        user = self._plugin.get("config", "user")
        passwd = self._plugin.get("config", "password")
        conn = cx_Oracle.connect(user, passwd, dsn)
        cursor = conn.cursor()
        return cursor


    def connectDB2(self):
        dsn = self._plugin.get("config", "dsn")
        conn = ibm_db.connect(dsn, "", "")
        return conn


    def generate(self, groups):
        event = Event()
        rules = self._plugin.rules()
        for key, value in rules['query'].iteritems():
            if key != "query" and key != "regexp" and key != "ref":
                #logger.info("Request")
                data = self._plugin.get_replace_array_value(value, groups)
                if data is not None:
                    event[key] = data
                #event[key] = self.get_replace_value(value, groups)
                #self.plugin.get_replace_value
        if event is not None:
            self.send_message(event)
