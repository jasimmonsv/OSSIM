#!/usr/bin/env python
# encoding: utf-8
'''
Parser for Cisco SDEE

Cisco� Network Prevention Systems (IPS)

Cisco� Network Detection Systems (IPS)

Cisco Switch IDS

Cisco IOS� routers with Inline Intrusion Prevention System (IPS) functions

Cisco IDS modules for routers

Cisco PIX� Firewalls

Cisco Catalyst� 6500 Series firewall services modules (FWSMs)

Cisco Management Center for Cisco security agents

CiscoWorks Monitoring Center for Security servers

'''

import os, sys, time, re, socket, commands

from Detector import Detector
from Event import Event, EventOS, EventMac, EventService, EventHids
from Logger import Logger
logger = Logger.logger
from time import sleep
from Config import Plugin
from pySDEE import SDEE 
import xml.dom.minidom

class ParserSDEE(Detector):
	def __init__(self, conf, plugin, conn):
		self._conf = conf
		self._plugin = plugin
		self.rules = []
		self.conn = conn
		self.sIdFile = '/etc/ossim/agent/sdee_sid.data'
		Detector.__init__(self, conf, plugin, conn)

	def parse(self, data):
		doc = xml.dom.minidom.parseString(data)
		alertlist = doc.getElementsByTagName('sd:evIdsAlert')
		
		alert_obj_list = []
		for alert in alertlist:
			sig = alert.getElementsByTagName('sd:signature')[0]
			logger.debug("1597 SDEE Parsing Alert")	
			#Plugin sid
			sid = sig.attributes['id'].nodeValue
		
			desc = sig.attributes['description'].nodeValue
				
			participants = alert.getElementsByTagName('sd:participants')[0]
			
			if not participants.hasChildNodes():
				logger.debug("Ignoring SDEE alert. Possible TCP/UDP/ARP DoS")
				continue
			
			attacker = participants.getElementsByTagName('sd:attacker')[0]
			#Src addr
			attAddr = attacker.getElementsByTagName('sd:addr')[0].firstChild.data
			
			#Src port
			try:
				attPort = attacker.getElementsByTagName('sd:port')[0].firstChild.data
			except:
				attPort = 0
			
			for dst in alert.getElementsByTagName('sd:target'):
				data1 = self.sanitize(alert.toxml())
				logger.debug("SDEE: %s" % data1)
				#Dst Address
				dstAddr = dst.getElementsByTagName('sd:addr')[0].firstChild.data
				
				#Dst Port
				try:
					dstPort = dst.getElementsByTagName('sd:port')[0].firstChild.data
				except:
					dstPort = 0
				
				logger.debug("%s:%s,  %s:%s, %s:%s" % (sid, desc, attAddr, attPort, dstAddr, dstPort))
				self.generate(sid, attAddr, attPort, dstAddr, dstPort, data1)

	def sanitize(self, data):
		data = data.replace("\n","").replace("<"," ").replace(">"," ").replace("/","").replace('"', "")
		return data
	 				
	def generate(self, sid, attAddr, attPort, dstAddr, dstPort, data):
		event = Event()
		event["plugin_id"] = self.plugin_id
		event["plugin_sid"] = sid
		event["log"] = data
		event["sensor"] = self.host
		event["src_ip"] = attAddr
		event["src_port"] = attPort
		event["dst_ip"] = dstAddr
		event["dst_port"] = dstPort
		if event is not None:
			self.send_message(event)
		
		#FIXME: Process timestamp and escaped log data
		
	def process(self):
		logger.info("Started SDEE Collector")
		self.host = self._plugin.get("config", "source_ip")
		self.username = self._plugin.get("config", "user")
		self.password = self._plugin.get("config", "password")
		self.sleepField = self._plugin.get("config", "sleep")
		self.plugin_id = self._plugin.get("DEFAULT", "plugin_id")
		
		sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
		try:
			sdee.open()
			logger.info("SDEE subscriberId %s" % sdee._subscriptionid)
			f = open(self.sIdFile, 'w')
			f.write("%s\n" % sdee._subscriptionid)
			f.close()
		except:
			logger.error("Error opening SDEE connection with device %s" % self.host)
			logger.info("SDEE: Trying to close last session")
			f = open(self.sIdFile, 'r')
			subs = f.readline()
			
			try:
				sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
				sdee._subscriptionid = subs
				sdee.close()
			except:
				logger.error("SDEE: losing last session Failed")
				return
			
			try:	
				sdee = SDEE(user=self.username,password=self.password,host=self.host,method='https', force='yes')
				sdee.open()
				logger.info("SDEE subscriberId %s" % sdee._subscriptionid)
				f = open(self.sIdFile, 'w')
				f.write("%s\n" % sdee._subscriptionid)
				f.close()			
			
			except:
				logger.error("SDEE Failed")
				return
				
		while 1:
			sdee.get()
			logger.info("Requesting SDEE Data...")
			data = sdee.data()
			logger.debug(data)
			self.parse(data)
			sleep(int(self.sleepField))
		
		
		
		
		
