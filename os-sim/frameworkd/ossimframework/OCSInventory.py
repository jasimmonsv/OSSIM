from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
import time
import re
import threading
from Inventory import *

logger = Logger.logger

#class OCSInventory(threading.Thread):
class OCSInventory():
	_interval = 3600
	
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		self.inv = Inventory()
		#Implement cache with timeout?????
		self.cache = []
		#threading.Thread.__init__(self)

	def connectDB(self):
		self.db = OssimDB()
		self.db.connect (self._tmp_conf["ossim_host"],
						 self._tmp_conf["ossim_base"],
		 				 self._tmp_conf["ossim_user"],
						 self._tmp_conf["ossim_pass"])
	
	def closeDB(self):
		self.db.close()
		
	def run(self):
		while True:
			self.process()
			time.sleep(self._interval)
	
	def process(self):
		self.ossimHosts = self.inv.getListOfHosts()
		ocsHosts = self.getOCSHosts()
		for host in ocsHosts:
			ip = host['ipaddr']
			#Check if host is valid
			if self.inv.validateIp(ip):
				#Check if host exists
				if not host in self.ossimHosts:
					#Add host to ossim Database
					print "Adding %s" % ip
					self.inv.insertHost(ip, None, host['name'], host['description'])
					mac = self.getMACfromHost(host['id'], ip)
					self.inv.insertProp(ip, "macAddress", "OCS", mac, None)
					self.inv.insertProp(ip, "workgroup", "OCS", host['workgroup'], None)
					
	
	def getOCSHosts(self):
		self.connectDB()
		sql = "select id,name,osname,osversion,ipaddr,workgroup,description from ocsweb.hardware;"
		data = self.db.exec_query(sql)
		self.closeDB()
		return data
	
	def getMACfromHost(self, id, ip):
		self.connectDB()
		sql = "select MACADDR from ocsweb.networks where HARDWARE_ID = %d and IPADDRESS = '%s';" % (id, ip)
		data = self.db.exec_query(sql)
		if data:
			self.closeDB()
			return data[0]["macaddr"]
		
	
if __name__ == '__main__':
	ocs = OCSInventory()
	ocs.run()
