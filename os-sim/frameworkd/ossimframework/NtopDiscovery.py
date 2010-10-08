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

'''
Initial module for Auto Asset Discovery via NTOP

- Req: Ntop dev version with .py scripts to extract needed information
- Req: Firewall open 3000 from the framework

'''

import csv
import urllib
from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
import time
import re
import threading
from Inventory import *

logger = Logger.logger

class NtopDiscovery(threading.Thread):
	_interval = 100
	
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		#Implement cache with timeout?????
		self.inv = Inventory()
		self.cache = []
		threading.Thread.__init__(self)

	def connectDB(self):
		self.db = OssimDB()
		self.db.connect (self._tmp_conf["ossim_host"],
						 self._tmp_conf["ossim_base"],
		 				 self._tmp_conf["ossim_user"],
						 self._tmp_conf["ossim_pass"])
	
	def closeDB(self):
		self.db.close()
		
	def getDataFromSensor(self, ip, port):
		logger.debug("Retrieving NTOP data from %s" % ip)
		try:
			f = urllib.urlopen("http://%s:%s/python/get.py" % (ip, port))
			return f.read()
		except IOError, msg:
			print msg
			logger.error("Error retrieving NTOP information from %s - msg:%s" % (ip, msg))
			return None
			
	def process(self, ip, sensorName, data, nets):
		logger.debug("Processing NTOP data from %s" % ip)
		data = data.split("\n")
		for row in csv.reader(data, delimiter=',', quoting=csv.QUOTE_NONE):
			if row != []:
				##ip,mac,name,fingerprint,isFTPhost,isWorkstation,isMasterBrowser,isPrinter,isSMTPhost,isPOPhost,isIMAPhost,isDirectoryHost,isHTTPhost,isVoIPClient,isVoIPGateway,isDHCPServer,isDHCPClient,
				ip = row[0]
				print ip
				mac = row[1]
				name = row[2]
				if not self.inv.hostExist(ip):
					if ip != "" and name != "" and  self.inv.validateIp(ip) and not self.blacklisted(ip) and self.inv.hostInNetworks(ip, nets):
						self.inv.insertHost(ip, sensorName, name)
						self.inv.insertSensorReference(ip, sensorName)
				else:
					if not self.inv.hostHasName(ip):
						self.inv.insertHostName(ip, name)
					if not self.inv.hostHasSensor(ip, sensorName):
						self.insertSensorReference(ip, sensorName)
					
		
	def run(self):
		while True:
			sensors = self.inv.getSensors()
			for s in sensors:
				ip = s['ip']
				port = "3000"
				name = s['name']
				nets = self.inv.getSensorNetworks(ip)
				data = self.getDataFromSensor(ip,port)
				if data != None:
					self.process(ip, name, data, nets)
			time.sleep(self._interval)
			
	def blacklisted(self, ip):
		if ip[0:3] == "224":
			return True
		if ip[0:3] == "239":
			return True
		if ip[0:3] == "255":
			return True
		if ip == "0.0.0.0":
			return True
	

if __name__ == '__main__':
	n = NtopDiscovery()
	n.start()


