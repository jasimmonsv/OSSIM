from Monitor import Monitor

from Logger import Logger
from SessionParser import SessionParser
logger = Logger.logger

import urllib, socket, sgmllib, re

class MonitorSession(Monitor):

    # connect to monitor
    def open(self):
        return

    # get data from monitor
    def get_data(self, rule_name):

        data = ''
        location = self.plugin.get("config", "location")
        query = self.queries[rule_name]

        logger.debug("Sending query to monitor: %s" % (query))

        try:
            f = urllib.urlopen(location + query)
            s = f.read()
        except IOError, e:
            logger.warning("Error connecting to monitor: %s" % (e))
            return data
        sessionparser = SessionParser()
        sessionparser.parse(s)

        data = sessionparser.get_sessions()

        return data


    # close monitor connection
    def close(self):
        return
