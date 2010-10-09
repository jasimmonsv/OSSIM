import base64
import getpass
import os
import socket
import sys
import traceback
import paramiko

#gather-package-list.nasl

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
		self.connect()
		self.ch = self.t.open_channel(kind = "session")
		self.ch.exec_command(comm)
		if (self.ch.recv_ready):
			return self.ch.recv(10000000)
		self.logout()
	
	def logout(self):
		self.t.close()
	
	def getOSInfo(self):
		#Debian
		data = self.execute("cat /etc/debian_version")
		if data.find("2.2") != -1:
			osType = "Linux"
			osDist = "Debian"
			osStr = "Debian 2.2 (Potato)"
			cpe = "cpe:/o:debian:debian_linux:2.2"
			return {"osType" : osType, "osDist" : osDist, "osStr" : osStr, "cpe": cpe}
		if data.find("3.0") != -1:
			osType = "Linux"
			osDist = "Debian"
			osStr = "Debian 3.0 (Woody)"
			cpe = "cpe:/o:debian:debian_linux:3.0"
			return {"osType" : osType, "osDist" : osDist, "osStr" : osStr, "cpe": cpe}			
		if data.find("3.1") != -1:
			osType = "Linux"
			osDist = "Debian"
			osStr = "Debian 3.1 (Sarge)"
			cpe = "cpe:/o:debian:debian_linux:3.1"
			return {"osType" : osType, "osDist" : osDist, "osStr" : osStr, "cpe": cpe}			
		if data.find("4.0") != -1:
			osType = "Linux"
			osDist = "Debian"
			osStr = "Debian 4.0 (Etch)"
			cpe = "cpe:/o:debian:debian_linux:4.0"
			return {"osType" : osType, "osDist" : osDist, "osStr" : osStr, "cpe": cpe}				
		if data.find("5.0") != -1:
			osType = "Linux"
			osDist = "Debian"
			osStr = "Debian 5.0 (Lenny)"
			cpe = "cpe:/o:debian:debian_linux:5.0"
			return {"osType" : osType, "osDist" : osDist, "osStr" : osStr, "cpe": cpe}
			
		return None
	
	def getSoftware(self, os):
		if os == "Debian":
			data = self.execute("dpkg -l")
			data = data.split("\n")
			print data
		return None
					
if __name__ == '__main__':
	conn = SSHConnection("192.168.1.134", 22, "root", "temporal")
	#conn.connect()
	print conn.execute('who')
	os = conn.getOSInfo()
	print os
	conn.getSoftware(os["osDist"])
	#print conn.execute('dpkg -l')
	#conn.logout()

'''



'''
