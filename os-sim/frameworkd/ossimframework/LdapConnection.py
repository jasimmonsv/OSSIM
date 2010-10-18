import os
import sys,ldap,ldap.async
import re

class LDAPConnection:
	def __init__(self, host, user, password, dom, binddn, base_dn):
		self.host = host
		self.user = user
		self.password = password
		self.dom = dom
		self.binddn = binddn
		self.base_dn = base_dn
		
	
	def connect(self):
		self.con = ldap.initialize('ldap://%s' % self.host)
		self.con.simple_bind_s(self.binddn, self.password )
		self.s = ldap.async.List(con,)
	
	def getComputers(self):
		self.s.startSearch(base_dn, ldap.SCOPE_SUBTREE, '(objectClass=Computer)',)
		try:
			partial = s.processResults()
		except ldap.SIZELIMIT_EXCEEDED:
  			sys.stderr.write('Warning: Server-side size limit exceeded.\n')
		else:
  			if partial:
    			sys.stderr.write('Warning: Only partial results received.\n')
		return s.allResults()
