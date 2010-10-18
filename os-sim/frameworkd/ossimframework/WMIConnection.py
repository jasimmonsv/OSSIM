import os
import sys
import commands
import re

#Win32_OperatingSystem
#smb_reg_service_pack.nasl

class WMIConnection:
	def __init__(self, host, user, password):
		self.host = host
		self.user = user
		self.password = password
	
	def execute(self, sql):
		#TODO RETURN ERROR if FAILS
		data = commands.getstatusoutput('wmic -U %s%%%s //%s "%s"' % (self.user, self.password, self.host, sql))
		return data[1]
	
	def getLoggedUsers(self):
		data = self.execute("SELECT * FROM Win32_LoggedOnUSer")
		users = []
		for l in data.split("\n"):
			p = re.compile('.*Win32_Account.Domain=\"(?P<domain>[^\"]+)\",Name=\"(?P<user>[^\"]+)\".*Win32_LogonSession.LogonId=\"(?P<id>[^\"]+)\"')
			m = p.match(l)
			if (m):
				users.append({"domain" : m.group(1), "username" : m.group(2), "logonId" : m.group(3)})
		print users
		return users
	
	def getOSInfo(self):
		data = self.execute("SELECT Caption,ServicePackMajorVersion,ServicePackMinorVersion,Version FROM Win32_OperatingSystem")
		
		
if __name__ == '__main__':
	conn = WMIConnection("192.168.1.135", "Administrador", "temporal")
	print conn.getLoggedUsers()

