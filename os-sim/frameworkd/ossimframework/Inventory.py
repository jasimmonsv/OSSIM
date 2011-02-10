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

from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
import time
import re
import socket
import struct
import os
import threading
import SubnetTree

logger = Logger.logger

class Inventory:
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		self.properties = {}
		self.sources = {}
		self.credentialTypes = {}
		self.loadProperties()
		self.loadSources()
		self.loadCredentialTypes()
		
	def connectDB(self):
		self.db = OssimDB()
		self.db.connect (self._tmp_conf["ossim_host"],
						 self._tmp_conf["ossim_base"],
		 				 self._tmp_conf["ossim_user"],
						 self._tmp_conf["ossim_pass"])
	
	def closeDB(self):
		self.db.close()
	
	def loadProperties(self):
		self.connectDB()
		sql = "select id, name from host_property_reference;"
		data = self.db.exec_query(sql)
		for d in data:
			self.properties[d["name"]] = d["id"]
		self.closeDB()
			
	def loadSources(self):
		self.connectDB()
		sql = "select id, name, relevance from host_source_reference;"
		data = self.db.exec_query(sql)
		for d in data:
			self.sources[d["name"]] = (d["id"], d["relevance"])
		self.closeDB()
	
	def loadCredentialTypes(self):
		self.connectDB()
		sql = "select id, name from credential_type;"
		data = self.db.exec_query(sql)
		for d in data:
			self.credentialTypes[d["name"]] = d["id"]
		self.closeDB()
		
	def insertProp(self, host, prop, source, value, extra):
		self.connectDB()
		sql = "INSERT INTO host_properties (ip, date, property_ref, source_id, value, extra) VALUES ('%s', now(), %d, %d, '%s', '%s');" % (host, self.properties[prop], self.sources[source][0], value, extra)
		self.db.exec_query(sql)
		self.closeDB()
		
	def getProps(self, ip):
		self.connectDB()
		sql = "select distinct(property_ref) from host_properties where ip = '%s';" % ip
		data = self.db.exec_query(sql)
		props = []
		for d in data:
			props.append(d["property_ref"])
		self.closeDB()
		return props

	def updateProp(self, ip, prop, source, value, extra):
		self.connectDB()
		sql = "UPDATE host_properties SET source_id = %d, date = now(), value = '%s', extra = '%s' where ip = '%s' and property_ref = %d" % (self.sources[source][0], value, extra, ip, self.properties[prop])
		print sql
		logger.debug(sql)
		self.db.exec_query(sql)
		self.closeDB()
	
	def getPropByHost(self, host, prop):
		self.connectDB()
		sql = "SELECT date, source_id, value, extra from host_properties where ip = '%s' and prop = %d" % (host, self.properties[prop])
		data = self.db.exec_query(sql)
		self.closeDB()
		return data
	
	def getListOfHosts(self):
		self.connectDB()
		sql = "SELECT ip from ossim.host;"
		logger.debug(sql)
		data = self.db.exec_query(sql)
		hosts = []
		for d in data:
			hosts.append(d["ip"])
		self.closeDB()
		return hosts
	
	def getSensors(self):
		self.connectDB()
		sql = "SELECT name, ip from ossim.sensor;"
		data = self.db.exec_query(sql)
		logger.debug(sql)
		self.closeDB()
		return data

	def getSensorNetworks(self, ip):
		self.connectDB()
		sql = "select n.ips from ossim.net as n, ossim.sensor as s, ossim.net_sensor_reference as nsr where s.ip = '%s' and nsr.sensor_name = s.name and n.name = nsr.net_name;" % ip
		data = self.db.exec_query(sql)
		print sql
		logger.debug(sql)
		nets = []
		for n in data:
			nets.append(n['ips'])
		self.closeDB()
		return nets

	def insertHost(self, ip, sensorName, name, descr):
		self.connectDB()
		name = name.replace("'","")
		sql = "INSERT INTO ossim.host(ip, hostname, asset, threshold_c, threshold_a, alert, persistence, descr) values ('%s', '%s', %d, 1000, 1000, 0, 0, '%s');" % (ip, name, 2, descr)
		self.db.exec_query(sql)
		logger.debug(sql)
		self.closeDB()

	def insertHostName(self, ip, name):
		self.connectDB()
		name = name.replace("'","")
		sql = "update host set hostname = '%s' where ip = '%s';" % (name, ip)
		logger.debug(sql)
		self.db.exec_query(sql)
		self.closeDB()

	def hostExist(self, host):
		self.connectDB()
		sql = "select ip from ossim.host where ip = '%s';" % host
		data = self.db.exec_query(sql)
		logger.debug(sql)
		if data == []:
			self.closeDB()
			return False
		logger.debug("Existe")
		self.closeDB()
		return True

	def hostHasName(self, host):
		self.connectDB()
		sql = "select hostname from ossim.host where ip = '%s';" % host
		logger.debug(sql)
		data = self.db.exec_query(sql)
		if len(data) > 0 and data[0]["hostname"] != host:
			self.closeDB()
			return True
		else:
			self.closeDB()
			return False
		self.closeDB()
		return False

	def hostHasSensor(self, host, sname):
		self.connectDB()
		sql = "select host_ip from host_sensor_reference where host_ip = '%s' and sensor_name = '%s';" % (host, sname)
		logger.debug(sql)
		data = self.db.exec_query(sql)
		if data == []:
			self.closeDB()
			return False
		self.closeDB()
		return True

	def insertSensorReference(self, host, sname):
		self.connectDB()
		sql = "INSERT INTO host_sensor_reference (host_ip, sensor_name) values ('%s', '%s');" % (host, sname)
		self.db.exec_query(sql)
		logger.debug(sql)
		self.closeDB()

	def validateIp(self, ip_str):
		#pattern = r"\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b"
		#pattern = "^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}"
		try:
			pattern = "^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
			if re.match(pattern, ip_str):
				return True
			else:
				return False
		except:
			return False
	
	def getIpFromMac(self, mac):
		self.connectDB()
		sql = "SELECT inet_ntoa(ip) from host_mac where mac = '%s'" % mac
		data = self.db.exec_query(sql)
		if data == []:
			self.closeDB()
			return False
		self.closeDB()
		return data[0]['inet_ntoa(ip)']

	def hostInNetworks(self, ip, nets):
		t = SubnetTree.SubnetTree()
		for n in nets:
			print "XX : %s" % n
			t[n] = n
		if ip in t:
			return True
		return False
		
	
	def insertHostMac(self, ip, mac):
		pass
	
	def generateCPE(self, osname, osversion, extra):
		#TODO
		#Windows
		version = ""
		verTable = {"4\.0" : "cpe:/o:microsoft:windows_nt",
					"5\.0" : "cpe:/o:microsoft:windows_2000", 
					"5\.1" : "cpe:/o:microsoft:windows_xp",
					"5\.2" : "cpe:/o:microsoft:windows_server_2003",
					"6\.0" : "cpe:/o:microsoft:windows_vista",
					"7\.0" : "cpe:/o:microsoft:windows_7",
					}
		if osname.find("Windows") != -1:
			#Check verTable
			for v in verTable.keys():
				p = re.compile(v)
				if p.match(osversion):
					version = verTable[v]
					
					#Check Service Pack
					rp = re.compile("Service Pack (\d+)")
					if extra.find("Service Pack") != -1:
						m = rp.match(extra)
						if m:
							sp = m.group(1)
							version = version + "::sp%s" % sp
					break
					
		if version != "":
			return version
		else:
			return None
			
	
	def getHostWithCredentials(self, ctype):
		self.connectDB()
		sql = "select ip, username, password, extra from credentials where type = %d and value = ;" % self.credentialTypes[ctype]
		data = self.db.exec_query(sql)
		self.closeDB()
		return data
		
	
if __name__ == '__main__':
	inv = Inventory()
	print inv.properties
	print inv.sources
	inv.getListOfHosts()
		
