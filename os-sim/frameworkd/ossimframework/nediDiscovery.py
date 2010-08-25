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
Initial module for Auto Asset Discovery via Nedi

- Req: Nedi to extract needed information

'''

import csv
import urllib
from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
import time
import re
import socket
import struct
import os

logger = Logger.logger

nedi_script_file = "/var/nedi/nedi.pl"
nedi_seedlist_file = "/etc/nedi/seedlist"
nedi_config_file = "/etc/nedi/nedi.conf"

class nediDiscovery:
	_interval = 3600
	
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		self.vlans = []
		
	def connectDB(self):
		self.db = OssimDB()
		self.db.connect (self._tmp_conf["ossim_host"],
						 self._tmp_conf["ossim_base"],
		 				 self._tmp_conf["ossim_user"],
						 self._tmp_conf["ossim_pass"])
	
	def closeDB(self):
		self.db.close()


	def checkDevices(self):
		sql = "select name,ip,os,description from nedi.devices;"
		data = self.db.exec_query(sql)
		for d in data:
			ip = self.inet_ntoa(d['ip'])
			name = d['name']
			os = d['os']
			sensorIp = self.getOssimSensor()
			sensorName = 'opensourcesim'
			descr = d['description']
			if ip != "" and name != "" and  self.validateIp(ip) and not self.hostExist(ip):
				self.insertHost(ip, sensorName, name, descr)
				self.insertOS(ip, os, sensorIp)
	
	def chekNodes(self):
		sql = "select name, ip, mac, device, vlanid from nedi.nodes;"
		data = self.db.exec_query(sql)
		for node in data:
			ip = self.inet_ntoa(node['ip'])
			name = node['name']
			mac= node['mac']
			device = node['device']
			vlanId = node['vlanid']
			if not self.hostExist(ip):
				if ip != "0.0.0.0":
					if name == "-" or name == "":
						name = ip
					self.insertHost(ip, self.getOssimSensor(), name, "")
					#Check if the host is in some hostgroup based on vlan and device
					for vlan in self.vlans:
						if device  == vlan['device'] and node['vlanid'] == vlan['vlanid']:
							self.insertHostIntoHostGroup(ip, "%s_%s"%(vlan['vlanname'], vlan['device']))
				else:
					nmac = self.convertMac(mac)
					ip = self.getIpFromMac(nmac)
					if ip:
						print ip, nmac
						if name == "-" or name == "":
							name = ip
						self.insertHost(ip, self.getOssimSensor(), name, "")
						for vlan in self.vlans:
							if device  == vlan['device'] and node['vlanid'] == vlan['vlanid']:
								self.insertHostIntoHostGroup(ip, "%s_%s"%(vlan['vlanname'], vlan['device']))						

	def convertMac(self, mac):
		#080087808f4a
		#00:0C:29:AD:DA:78
		data = ""
		for i in range(0, len(mac)):
			if i % 2 == 1:
				data = data + mac[i].upper() + ":"
			else:
				data = data + mac[i].upper()
			i = i + 1
		return data[0:len(data)-1]
		
	
	def getIpFromMac(self, mac):
		sql = "SELECT inet_ntoa(ip) from host_mac where mac = '%s'" % mac
		data = self.db.exec_query(sql)
		if data == []:
			return False
		return data[0]['inet_ntoa(ip)']
		
		
	def insertHost(self, ip, sensorName, name, descr):
		print name
		name = name.replace("'","")
		sql = "INSERT INTO ossim.host(ip, hostname, asset, threshold_c, threshold_a, alert, persistence, descr) values ('%s', '%s', %d, 1000, 1000, 0, 0, '%s');" % (ip, name, 2, descr)
		self.db.exec_query(sql)
		logger.debug(sql)
		sql = "INSERT INTO ossim.host_sensor_reference(host_ip, sensor_name) values ('%s', '%s');" % (ip, sensorName)
		self.db.exec_query(sql)
		logger.info(sql)
		
	
	def insertHostIntoHostGroup(self, ip, hostGroupName):
		sql = "INSERT INTO host_group_reference(host_group_name, host_ip) values ('%s', '%s')" % (hostGroupName, ip)
		self.db.exec_query(sql)
		logger.debug(sql)		
		
	def insertOS(self, ip, osStr, sensor):
		os = None
		if osStr.find('IOS') != -1:
			os = 'Cisco'
		if os:
			sql = "insert into host_os(ip,os,date,sensor) values (inet_aton('%s'), '%s', now(), inet_aton('%s'))" % (ip, os, sensor) 
			self.db.exec_query(sql)
			logger.info(sql)
		
	def getOssimSensor(self):
		#FIXXXXX HOW TO GET THE LOCAL SENSOR????????
		sql = "select ip from sensor where name = 'opensourcesim';"
		data = self.db.exec_query(sql)
		return data[0]['ip']
		
		
	def vlanToHostGroup(self):
		#{'device': 'NA_SSEEARGUELLESL4', 'vlanname': 'PROTECCIONES', 'vlanid': 9}]
		for vlan in self.vlans:
			name = vlan['vlanname']
			if not self.hostGroupExist("%s_%s" % (name, vlan['device'])) and name.find('-default') == -1 and name != "default": 
				logger.info('HostGroup %s doesnt exists' % name)
				sql = "INSERT INTO ossim.host_group(name, threshold_c, threshold_a) values ('%s_%s', 30, 30)" % (vlan['vlanname'], vlan['device'])
				data = self.db.exec_query(sql)
				logger.debug(sql)
				#FIXME!!!!!!! INSERT host_group_sensor_reference

		
	def hostGroupExist(self, name):
		sql = "select name from ossim.host_group where name = '%s'" % name
		data = self.db.exec_query(sql)
		logger.debug(sql)
		if data == []:
			return False
		print "Existe"
		return True		
		
	def hostExist(self, host):
		sql = "select ip from ossim.host where ip = '%s';" % host
		data = self.db.exec_query(sql)
		logger.debug(sql)
		if data == []:
			return False
		print "Existe"
		return True
	
	def loop(self):
		while True:
			self.connectDB()
			self.launchNediDiscovery()
			self.getVlans()
			self.vlanToHostGroup()
			self.checkDevices()
			self.chekNodes()
			self.closeDB()
			time.sleep(self._interval)
	
		#TODO
	def launchNediDiscovery(self):
		#Feed nedi seedlist file with device information ip/community
		self.feedSeedList()
		#Launch nedi
		os.system('%s -u %s -U %s' % (nedi_script_file, nedi_seedlist_file, nedi_config_file))
		
	def feedSeedList(self):
		#sql = "select inet_ntoa(ip) as ip,community from ossim.network_device;"
		sql = "select ip,community from ossim.network_device;"
		data = self.db.exec_query(sql)
		logger.debug(sql)
		f = open('/etc/nedi/seedlist', 'w')
		for device in data:
			logger.info("Detected %s with community %s" % (device['ip'], device['community']))
			f.write("%s\t%s\n" % (device['ip'], device['community']))
		f.close()
		
	def inet_ntoa(self, ip):
		return socket.inet_ntoa(struct.pack('!L',int(ip)))
		
	def validateIp(self, ip_str):
		pattern = r"\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b"
		if re.match(pattern, ip_str):
			return True
		else:
			return False

	def getVlans(self):
		sql = "select * from nedi.vlans;"
		self.vlans = self.db.exec_query(sql)
		
if __name__ == '__main__':
	n = nediDiscovery()
	n.loop()
								