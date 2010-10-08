import base64
import getpass
import os
import socket
import sys
import traceback
import paramiko

class SSHConnection:
	def __init__(self, host, port, user, password):
		self.host = host
		self.user = user
		self.password = password
		self.port = port
	
	def connect(self):
		self.t = paramiko.Transport((self.host,self.port))
		try:
			self.t.connect(username=self.user, password=self.password, hostkey=None)
		except:
			self.t.close()
	
	def execute(self, comm):
		self.ch = self.t.open_channel(kind = "session")
		self.ch.exec_command(comm)
		if (self.ch.recv_ready):
			return self.ch.recv(1000)
	
	def logout(self):
		self.t.close()
		
if __name__ == '__main__':
	conn = SSHConnection("192.168.1.140", 22, "root", "test")
	conn.connect()
	print conn.execute('who')
	conn.logout()

