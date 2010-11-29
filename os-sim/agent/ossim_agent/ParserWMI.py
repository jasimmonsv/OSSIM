'''
WMI EventLog Parser, adapted from Database Parser
TODO:
 TRANSLATIONS support
 Testing
 Make sure it works with more wmi stuff, not just windows log files
 Test with different languages / windows versions
'''

import os, sys, time, re, socket, commands

from Detector import Detector
from Event import Event, EventOS, EventMac, EventService, EventHids
from Logger import Logger
logger = Logger.logger
#from TailFollow import TailFollow
from time import sleep
from Config import Plugin

class ParserWMI(Detector):
        def __init__(self, conf, plugin, conn, hostname, username, password):
                self._conf = conf
                self._plugin = plugin
                self.rules = []          # list of RuleMatch objects
                self.conn = conn
                self.hostname = hostname
                self.username = username
                self.password = password
                Detector.__init__(self, conf, plugin, conn)

        def process(self):
                rules = self._plugin.rules()
                #logger.info(rules['start_query']['query'])
                #logger.info(rules['start_cmd']['cmd'])
                #if rules['start_query']['query']
                test = None
                host = self.hostname
                username = self.username
                password = self.password.strip()
                logger.info("WMI Collection started. Trying to connect to %s." % host)
                data = commands.getstatusoutput('wmic -U %s%%%s //%s "SELECT LogFileName FROM Win32_NTEventLogFile"' % (username, password, host))
                data = data[1].split("\n")
                for l in data:
                    try:
                        l = l.split('|')
                        if l[0] == "Security":
                            test = 1
                    except:
                        pass
                if test:
                        cmd= rules['start_cmd']['cmd']
                        cmd = cmd.replace("OSS_WMI_USER",username)
                        cmd = cmd.replace("OSS_WMI_PASS",password)
                        cmd = cmd.replace("OSS_WMI_HOST",host)
                        data = commands.getstatusoutput(cmd)
                        if not data[1]:
                            logger.warning("Initial WMI query empty, please double-check: %s" % cmd)
                            return
                        cVal = str(int(data[1]))
                        logger.info(cVal)
                        #logger.info(cVal)
                        tSleep = self._plugin.get("config", "sleep")
                        regexp = rules['cmd']['regexp']
                        start_regexp = rules['cmd']['start_regexp']
                        splitter = re.compile('(?<!\r)\n') # Split on \n unless it's preceded by \r
                        cregexp = re.compile(regexp)
                        while 1:
                                logger.info("Fetching using WMI")
                                cmd = rules['cmd']['cmd']
                                cmd = cmd.replace("OSS_WMI_USER",username)
                                cmd = cmd.replace("OSS_WMI_PASS",password)
                                cmd = cmd.replace("OSS_WMI_HOST",host)
                                cmd = cmd.replace("OSS_COUNTER",str(cVal))
                                #logger.info(cmd)
                                ret = commands.getstatusoutput(cmd)
                                data = splitter.split(ret[1])
                                cval_helper = 1
                                for piece in data:
                                    piece = piece.replace("\r\n"," ")
                                    result = cregexp.search(piece)
                                    if result is None:
                                        continue
                                    else:
                                        if cval_helper == 1:
                                        # Only calculate cVal for first row since logs come out reversed
                                            cVal = str(int(result.groups()[4]))
                                            cval_helper = 0
                                        self.generate(result.groups())
                                time.sleep(int(tSleep))
                else:
                    logger.warning("Can't connect to WMI at %s" % host)

        def generate(self, groups):
                event = Event()
                rules = self._plugin.rules()
                for key, value in rules['cmd'].iteritems():
                        if key != "cmd" and key != "regexp" and key != "ref" and key != "start_regexp":
                                #logger.info("Request")
                                event[key] = self._plugin.get_replace_array_value(value, groups)
                                #event[key] = self.get_replace_value(value, groups)
                                #self.plugin.get_replace_value
                if event is not None:
                        self.send_message(event)

