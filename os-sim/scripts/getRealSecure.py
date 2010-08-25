#!/usr/bin/env python
# encoding: utf-8
'''
Python script to extract information from SiteProtector system

Jaime Blasco jaime.blasco@alienvault.com

'''
import sys
try:
	import pymssql
except:
	print "You need pymssql to run this script"
	sys.exit(0)

import socket
import struct
import time
import os
import commands

#Extract extra data from each of the events
def getEventData(sID):
    #sql1 = "select * from SensorDataAVP1 where SensorDataID = %s" % sID
    sql1 = "select CONVERT(nvarchar(200), AttributeName), CONVERT(nvarchar(200), AttributeValue) from SensorDataAVP1 where SensorDataID = %s" % sID
    print sql1
    cursor.execute(sql1)
    ret = cursor.fetchall()
    data = ""
    for i in ret:
        #data = data + "%s:%s, " % (i[1], i[4])
    	data = data + "%s:%s, " % (i[0], i[1])
    return data


#Check if there us another instance running
def checkIfRunning():
	pid = os.getpid()
	print pid
	data = commands.getstatusoutput('ps auxwwwwwwwwww|grep getRealSecure.py|grep -v grep')
	data2 = data[1].split('\n')
	if data[1].find("python /usr/share/ossim/scripts/getRealSecure.py") != -1 and len(data2) > 1:
        	print "Exist"
        	sys.exit()
	else:
        	print "getRealSecure.py process didn't existed before this one"

#Connect to Database
def connectToDB():
	db = pymssql.connect(host="", user="", password="", database="")	
	cursor=db.cursor()
	return cursor

#Initial query
def getFirstID():
	cursor = connectToDB()
	sql = "select TOP 1 data.SensorDataRowID from SensorData1 as data order by data.SensorDataRowID desc"
	cursor.execute(sql)
	row= cursor.fetchone()
	cVal = int(row[0])
	cursor.close()
	return cVal


def extractData(sID):
    #sql1 = "select * from SensorDataAVP1 where SensorDataID = %s" % sID
    sql1 = "select CONVERT(nvarchar(200), AttributeName), CONVERT(nvarchar(200), AttributeValue) from SensorDataAVP1 where SensorDataID = %s" % sID
    #print sql1
    cursor.execute(sql1)
    ret = cursor.fetchall()
    return ret

def countEvents(sID):
   ret = extractData(sID)
   for i in ret:
        #print i[0]
        if i[0] == ":repeat-count":
                return int(i[1])
   return 1



checkIfRunning()

cVal = getFirstID()
while 1:
	conn = False
	while not conn:
		try:
			cursor = connectToDB()
			conn = True
		except:
			print "Error connecting to MSSQL Server......"
			time.sleep(10)
		
	print "SELECT made"
        #sql = "select data.SensorDataRowID, data.AlertName, data.AlertDateTime, data.SensorAddressInt, data.SrcAddressInt, data.DestAddressInt, data.SourcePort FROM SensorData1 as data where data.SensorDataRowID > %s ORDER BY data.SensorDataRowID;" % cVal
        sql = "SELECT  SensorData.SensorDataRowID, SensorData.AlertName, SensorData.AlertDateTime, SensorData.SensorAddressInt, SensorData.SrcAddressInt, SensorData.DestAddressInt, SensorData.SourcePort, Observances.SecChkID, SecurityChecks.ChkBriefDesc, SensorData.AlertPriority, SensorData.DestPortName, Protocols.ProtocolName, Products.ProdName, SensorData.VirtualSensorName, ObservanceType.ObservanceTypeDesc, SensorData.AlertCount, SensorData.Cleared, SensorData.ObjectName, SensorData.ObjectType, SensorData.VulnStatus, SensorData.UserName, attrs.event_type, attrs.adapter, attrs.attacker_ip, attrs.attacker_port,  attrs.victim_ip, attrs.victim_port, attrs.url, attrs.server,  attrs.protocol, attrs.field, attrs.value, attrs.httpsvr, SensorData.SensorDataID FROM  SensorData WITH (NOLOCK) LEFT OUTER JOIN  (SELECT SensorDataID, max(case when AttributeName = ':event-type'  then AttributeValue end)  as event_type, max(case when AttributeName = ':adapter' then AttributeValue end)  as adapter, max(case when AttributeName = ':intruder-ip-addr' then AttributeValue end)  as attacker_ip, max(case when AttributeName = ':intruder-port' then AttributeValue end) as attacker_port, max(case when AttributeName = ':victim-ip-addr' then AttributeValue end) as victim_ip, max(case when AttributeName = ':victim-port' then AttributeValue end)  as victim_port, max(case when AttributeName = ':URL' then AttributeValue end)  as url, max(case when AttributeName = ':server' then AttributeValue end) as server,  max(case when AttributeName = ':protocol' then AttributeValue end)  as protocol, max(case when AttributeName = ':field' then AttributeValue end)  as field, max(case when AttributeName = ':value' then AttributeValue end)  as value, max(case when AttributeName = ':httpsvr'          then AttributeValue end)  as httpsvr            from SensorDataAVP            where AttributeName in ( ':event-type', ':adapter', ':intruder-ip-addr', ':intruder-port', ':victim-ip-addr', ':victim-port' )            group by SensorDataID       ) attrs on SensorData.SensorDataID = attrs.SensorDataID LEFT OUTER JOIN       Observances WITH (NOLOCK) ON SensorData.ObservanceID = Observances.ObservanceID LEFT OUTER JOIN       SecurityChecks WITH (NOLOCK) ON Observances.SecChkID = SecurityChecks.SecChkID LEFT OUTER JOIN       Protocols WITH (NOLOCK) ON SensorData.ProtocolID = Protocols.ProtocolID LEFT OUTER JOIN       Products WITH (NOLOCK) ON SensorData.ProductID = Products.ProductID LEFT OUTER JOIN       ObservanceType WITH (NOLOCK) ON Observances.ObservanceType = ObservanceType.ObservanceType WHERE SensorData.SensorDataRowID > %s ORDER BY SensorData.SensorDataRowID ASC" % cVal
        
         
        cursor.execute(sql)
        ret = cursor.fetchall()
        if ret and len(ret) > 0:
                cVal = int(ret[len(ret) - 1][0])
        for e in ret:
		f=open('/var/log/siteprotector.log', 'a')
                evId = str(e[0])
                evName = str(e[1])
                evDate = e[2]
                try:
                        evSensor = socket.inet_ntoa(struct.pack('!L',int(e[3])))
                except:
                        evSensor = "127.0.0.1"

                try:
                        evSrc = socket.inet_ntoa(struct.pack('!L',int(e[4])))
                except:
                        evSrc = "0.0.0.0"
                try:
                        evDst = socket.inet_ntoa(struct.pack('!L',int(e[5])))
                except:
                        evDst = "0.0.0.0"
                try:
                        evSrcP = e[6]
                        if not evSrcP:
                                evSrcP = "0"
                except:
                        evSrcP = "0"

                try:
                        evDstP = e[17]
                        if not evDstP:
                                evDstP = "0"
                        else:
                            evDstP = str(e[17])
                except:
                        evDstP = "0"

                data = getEventData(int(e[33])).replace("\n", "")
                
		num = countEvents(int(e[33]))
		for i in range(0, num):
			f.write("%s,%s,%s,%s,%s,%s,%s,%s,%s\n" % (evId, evName, evDate, evSensor, evSrc, evDst, evSrcP, evDstP, data))
                	print ("%s,%s,%s,%s,%s,%s,%s,%s,%s\n" % (evId, evName, evDate, evSensor, evSrc, evDst, evSrcP, evDstP, data))
			print "***%s" % data
		f.close()
                #f.write("SensorDataRowID: %s, AlertName: %s, AlertDateTime: %s, SensorAddress: %s, SrcAddress: %s, DestAddress: %s, SourcePort: %s, DstPort: %s, SecChkID: %s, ChkBriefDesc: %s, AlertPriority: %s, DestPortName: %s, ProtocolName: %s, ProdName: %s, VirtualSensorName: %s, ObservanceTypeDesc: %s, AlertCount: %s, Cleared: %s, ObjectName: %s, ObjectType: %s, VulnStatus: %s, UserName: %s, event_type: %s, adapter: %s, attacker_ip: %s, attacker_port: %s, victim_ip: %s, victim_port: %s, url: %s, server: %s, protocol: %s, field: %s, value: %s, httpsvr: %s\n" % (evId, evName, evDate, evSensor, evSrc, evDst, evSrcP, evDstP, str(e[8]), str(e[8]), str(e[9]), str(e[10]), str(e[11]), str(e[12]), str(e[13]), str(e[14]), str(e[15]), str(e[16]), str(e[17]), str(e[18]), str(e[19]), str(e[20]), str(e[21]), str(e[22]), str(e[23]), str(e[24]), str(e[25]), str(e[26]), str(e[27]), str(e[28]), str(e[29]), str(e[30]), str(e[31]), str(e[32])))
                #print "%s,%s,%s,%s,%s,%s,%s,%s,\n" % (evId, evName, evDate, evSensor, evSrc, evDst, evSrcP, evDstP)
        cursor.close()
	time.sleep(20)


