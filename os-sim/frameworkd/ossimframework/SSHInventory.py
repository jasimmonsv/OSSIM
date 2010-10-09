from OssimDB import OssimDB
from OssimConf import OssimConf
from Logger import Logger
import Const
import time
import re
import threading
from Inventory import *
from SSHConnection import *
logger = Logger.logger

#class SSHInventory(threading.Thread):
class SSHInventory():
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
		#Check host with local credentials
		hosts = self.inv.getHostWithCredentials("SSH")
		
		
			
