import base64
import getpass
import os
import socket
import sys
import traceback
import paramiko
import re

#gather-package-list.nasl

class SSHConnection:
	def __init__(self, host, port, user, password):
		self.host = host
		self.user = user
		self.password = password
		self.port = port
		
	def connect(self):
		self.client = paramiko.SSHClient()
		self.client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
		try:
			self.client.connect(hostname=self.host, port=self.port, username=self.user, password=self.password, look_for_keys=False)
			return True
		except:
			print "Error conecting to %s" % self.host
			return False
			self.client.close()
			
			
