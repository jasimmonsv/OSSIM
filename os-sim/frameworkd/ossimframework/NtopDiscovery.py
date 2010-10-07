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

logger = Logger.logger

class ntopDiscovery:
	_interval = 100
	
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		#Implement cache with timeout?????
		self.cache = []

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

	def getSensors(self):
		sql = "SELECT name, ip from sensor;"
		data = self.db.exec_query(sql)
		logger.debug(sql)
		return data
	
	def getSensorNetworks(self, ip):
		sql = "select n.ips from ossim.net as n, ossim.sensor as s, net_sensor_reference as nsr where s.ip = '%s' and nsr.sensor_name = s.name and n.name = nsr.net_name;" % ip
		data = self.db.exec_query(sql)
		logger.debug(sql)
		nets = []
		for n in data:
			nets.append(n['ips'])
		return nets
		
	def insertHost(self, ip, sensorName, name):
		name = name.replace("'","")
		sql = "INSERT INTO ossim.host(ip, hostname, asset, threshold_c, threshold_a, alert, persistence) values ('%s', '%s', %d, 1000, 1000, 0, 0);" % (ip, name, 2)
		self.db.exec_query(sql)
		logger.debug(sql)
		#sql = "INSERT INTO ossim.host_sensor_reference(host_ip, sensor_name) values ('%s', '%s');" % (ip, sensorName)
		#self.db.exec_query(sql)
		#logger.debug(sql)
	
	def insertHostName(self, ip, name):
		name = name.replace("'","")
		sql = "update host set hostname = '%s' where ip = '%s';" % (name, ip)
		logger.debug(sql)
		self.db.exec_query(sql)
			
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
				if not self.hostExist(ip):
					if ip != "" and name != "" and  self.validateIp(ip) and not self.blacklisted(ip) and self.hostInNetworks(ip, nets):
						self.insertHost(ip, sensorName, name)
						self.insertSensorReference(ip, sensorName)
				else:
					if not self.hostHasName(ip):
						self.insertHostName(ip, name)
					if not self.hostHasSensor(ip, sensorName):
						self.insertSensorReference(ip, sensorName)
						
	def hostExist(self, host):
		sql = "select ip from ossim.host where ip = '%s';" % host
		data = self.db.exec_query(sql)
		logger.debug(sql)
		if data == []:
			return False
		logger.debug("Existe")
		return True
	
	def hostHasName(self, host):
		sql = "select hostname from ossim.host where ip = '%s';" % host
		logger.debug(sql)
		data = self.db.exec_query(sql)
		if len(data) > 0 and data[0]["hostname"] != host:
			return True
		else:
			return False
		return False
	
	def hostHasSensor(self, host, sname):
		sql = "select host_ip from host_sensor_reference where host_ip = '%s' and sensor_name = '%s';" % (host, sname)
		logger.debug(sql)
		data = self.db.exec_query(sql)
		if data == []:
			return False
		return True
	
	def insertSensorReference(self, host, sname):
		sql = "INSERT INTO host_sensor_reference (host_ip, sensor_name) values ('%s', '%s');" % (host, sname)
		self.db.exec_query(sql)
		logger.debug(sql)
		
	def loop(self):
		while True:
			self.connectDB()
			sensors = self.getSensors()
			for s in sensors:
				ip = s['ip']
				port = "3000"
				name = s['name']
				nets = self.getSensorNetworks(ip)
				data = self.getDataFromSensor(ip,port)
				if data != None:
					self.process(ip, name, data, nets)
			self.getSensors()
			self.closeDB()
			time.sleep(self._interval)
	
	def validateIp(self, ip_str):
		pattern = r"\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b"
		if re.match(pattern, ip_str):
			return True
		else:
			return False
			
	def blacklisted(self, ip):
		if ip[0:3] == "224":
			return True
		if ip[0:3] == "239":
			return True
		if ip[0:3] == "255":
			return True
		if ip == "0.0.0.0":
			return True
	
	def hostInNetworks(self, ip, nets):
		#IMPLEMENTAR
		return True

if __name__ == '__main__':
	n = ntopDiscovery()
	n.loop()

