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
    logger.info("You need python mysqldb module installed")
try:
    import pymssql

except ImportError:
    logger.info("You need python pymssql module installed")
try:
    import cx_Oracle
except ImportError:
    logger.info("You need python cx_Oracle module installed. This is not an error if you aren't using an Oracle plugin")
try:
    import ibm_db
except ImportError:
    logger.info("You need python ibm_db module installed. This is not an error if you aren't using an IBM plugin")

"""
Parser Database
TODO:
TRANSLATIONS support
"""



class ParserDatabase(Detector):
	def __init__(self, conf, plugin, conn):
		self._conf = conf
		self._plugin = plugin
		self.rules = []	  # list of RuleMatch objects
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
		elif self._plugin.get("config", "source_type") == "oracle":
			try:
				cursor = self.connectOracle()
				logger.info("Connection OK")
				test = 1
			except:
				logger.info("Can't connect to Oracle database")
		elif self._plugin.get("config", "source_type") == "db2":
			try:
				cursor = self.connectDB2()
				if cursor:
					logger.info("Connection OK")
					test = 1
				else:
					logger.info("Can't connect to DB2 database")
			except:
				logger.info("Can't connect to DB2 database")
		else:
			logger.info("Not supported database")
		if test and self._plugin.get("config", "source_type") != "db2":
			sql = rules['start_query']['query']
			logger.info(sql)
			cursor.execute(sql)
			rows= cursor.fetchone()
			if not rows:
			    logger.warning("Initial query empty, please double-check")
			    return
			cVal = str((rows[0]))
			logger.info(cVal)
			tSleep = self._plugin.get("config", "sleep")
			sql = rules['query']['query']
			#logger.info(sql)
			ref = int(rules['query']['ref'])
			logger.info(ref)
			cursor.close()
		elif test and self._plugin.get("config", "source_type") == "db2":
			sql = rules['start_query']['query']
			logger.info(sql)
			result = ibm_db.exec_immediate(cursor, sql)
			dictionary = ibm_db.fetch_both(result)
			if not dictionary:
				logger.warning("Initial query empty, please double-check")
				return
			cVal = str((dictionary[0]))
			logger.info(cVal)
			tSleep = self._plugin.get("config", "sleep")
			ref = int(rules['query']['ref'])
			logger.info(ref)
			logger.info("Connection closed")
			#ibm_db.close(cursor)	

		while 1:
			if self._plugin.get("config", "source_type") == "mysql":
				cursor = self.connectMysql()
			if self._plugin.get("config", "source_type") == "mssql":
				cursor = self.connectMssql()
			if self._plugin.get("config", "source_type") == "oracle":
				cursor = self.connectOracle()
			if self._plugin.get("config", "source_type") == "db2":
				cursor = self.connectDB2()
				print cursor

			if self._plugin.get("config", "source_type") != "db2":
				logger.info("Querying Database")
				sql = rules['query']['query']
				sql = sql.replace("$1", str(cVal))
				logger.info(sql)
				cursor.execute(sql)
				ret = cursor.fetchall()
				if len(ret) > 0:
					#We have to think about event order when processing
					cVal = ret[len(ret) - 1][ref]
					for e in ret:
					       	self.generate(e)
				cursor.close()
				time.sleep(int(tSleep))
			else:
				logger.info("Querying DB2 Database")
				sql = rules['query']['query']
				sql = sql.replace("$1", str(cVal))
				logger.info(sql)
				result = ibm_db.exec_immediate(cursor, sql)
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
		print "******************"
		return conn

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
