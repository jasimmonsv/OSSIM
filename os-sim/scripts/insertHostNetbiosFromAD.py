'''
Script to populate host_netbios and netbios ossim tables
It should be executed on a windows machine (with AD access)
You need the following python modules:
http://timgolden.me.uk/python/downloads/active_directory-0.6.7.zip

'''

import active_directory
import socket
import sys

try:
	domain = sys.argv[1]
except:
	print "Usage:	populateAdHosts.py	domainName"
	sys.exit()
	
def generateSql(host, ip):
	print "REPLACE INTO host_netbios VALUES ('%s', '%s', '%s');" % (ip, host, domain)

def resolv(host):
    try:
        addr = socket.gethostbyname(host)
    except socket.gaierror:
        return host

    return addr
    
for c in active_directory.search (objectCategory='Computer'):
    if c.displayName != None:
	host= c.displayName.replace("$", "")
	ip = resolv(host)
        generateSql(host,ip)   
    
    
