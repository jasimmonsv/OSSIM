from OssimDB import OssimDB
from OssimConf import OssimConf
import Const
import time
import re
import socket
import struct
import os
import threading

class Inventory:
	def __init__(self):
		self._tmp_conf = OssimConf (Const.CONFIG_FILE)
		self.vlans = []
		self.properties = {}
		self.sources = {}
		
	def connectDB(self):
		self.db = OssimDB()
		self.db.connect (self._tmp_conf["ossim_host"],
						 self._tmp_conf["ossim_base"],
		 				 self._tmp_conf["ossim_user"],
						 self._tmp_conf["ossim_pass"])
	
	def closeDB(self):
		self.db.close()
	
	def loadProperties(self):
		sql = "select id, name from host_property_reference;"
		data = self.db.exec_query(sql)
		
	def loadReferences(self):
		sql = "select name, priority from host_source_reference;"
		data = self.db.exec_query(sql)
		for d in data:
			print d
		
	'''
	def insertProp(self, host, prop, source, value, extra):
		self.connectDB()
		sql = "" % name
		data = self.db.exec_query(sql)
	'''
	
if __name__ == '__main__':
	inv = Inventory()
	inv.loadReferences()
	
		
