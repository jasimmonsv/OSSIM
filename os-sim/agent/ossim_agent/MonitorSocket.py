from Monitor import Monitor

from Logger import Logger
logger = Logger.logger

import socket

class MonitorSocket(Monitor):

    # connect to monitor
    def open(self):

        self.conn = None

        location = self.plugin.get("config", "location")
        (host, port) = location.split(':')

        try:
            self.conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.conn.connect((host, int(port)))
        except socket.error, e:
            logger.warning(e)
            logger.error("Can't connect to Monitor (%s).." % (location))
            self.conn = None

        return None


    # get data from monitor
    def get_data(self, rule_name):

        self.close()
        self.open()

        if self.conn is None:
            return None

        data = ''
        query = self.queries[rule_name]

        try:
            logger.debug("Sending query to monitor: %s" % (query))
            self.conn.send(query + "\n")
            data = self.conn.recv(1024)
            logger.debug("Received data from monitor: %s" % (data))
        except socket.error, e:
            logger.warning(e)
            logger.error("Error in monitor connection..")
            return None

        return data


    # close monitor connection
    def close(self):

        try:
            self.conn.shutdown(2)
            self.conn.close()
        except socket.error, e:
            logger.warning(e)
            logger.error("Can not close monitor connection..")


