import thread, time, socket, string, sys, re, os

from Config import Conf, Plugin
from Logger import *
logger = Logger.logger
from Stats import Stats
from Watchdog import Watchdog
from MonitorScheduler import MonitorScheduler
from __init__ import __version__

class ServerConnPro:

    __conn = None

    MSG_CONNECT         = 'connect id="%s" ' +\
                          'type="sensor" '   +\
                          'version="'+__version__+'"\n'

    def __init__(self, conf, id):
        self.conf = conf
        self.id = id
        self.server_ip = self.conf.get("output-server-pro", "ip")
        self.server_port = self.conf.get("output-server-pro", "port")
        self.sequence = 0


    # connect to server
    #  attempts == 0 means that agent try to connect forever
    #  waittime = seconds between attempts
    def connect(self, attempts = 3, waittime = 10.0):

        self.sequence = 1
        count = 1

        if self.__conn is None:

            logger.info("Connecting to server (%s, %s).." \
                % (self.server_ip, self.server_port))

            while 1:

                self.__connect_to_server()
                if self.__conn is not None:
                    logger.info("Connected to server")
                    break

                else:
                    logger.info("Can't connect to server, " +\
                                "retrying in %d seconds" % (waittime))
                    time.sleep(waittime)

                # check #attempts
                if attempts != 0 and count == attempts:
                    break
                count += 1

        else:
            logger.info("Reusing server connection (%s, %s).." \
                % (self.server_ip, self.server_port))

        return self.__conn


    def close(self):
        logger.info("Closing server connection..")
        if self.__conn is not None:
            self.__conn.close()
            self.__conn = None

    def get_id(self):
        return self.id

    def get_conn(self):
        return self.__conn

    # Reset the current connection by closing and reopening it
    def reconnect(self, attempts = 0, waittime = 10.0):

        self.close()
        time.sleep(1)
        Stats.server_reconnect()
        while 1:
            if self.connect(attempts, waittime) is not None:
                break


    def send(self, msg):

        while 1:
            try:
                self.__conn.send(msg)
            except socket.error, e:
                logger.error(e)
                self.reconnect()
            except AttributeError: # self.__conn == None
                self.reconnect()
            else:
                logger.debug(msg.rstrip())
                break


    def __connect_to_server(self):

        self.__conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        data = ""
        try:
            self.__conn.connect((self.server_ip, int(self.server_port)))
            self.__conn.send(self.MSG_CONNECT % (self.sequence))
            logger.debug("Waiting for server..")
            data = self.__conn.recv(1024)
        except socket.error, e:
            logger.error(ERROR_CONNECTING_TO_SERVER \
                % (self.server_ip, str(self.server_port)) + ": " + str(e))
            self.__conn = None
        else:
            if data == 'ok id="' + str(self.sequence) + '"\n':
                logger.info("Connected to server!")
            else:
                logger.error("Bad response from server: %s" % (str(data)))
                self.__conn = None

        return self.__conn



    def recv_line(self):

        char = data = ''

        while 1:
            try:
                char = self.__conn.recv(1)
                data += char
                if char == '\n':
                    break
            except socket.error, e:
                logger.error('Error receiving data from server: ' + str(e))
                time.sleep(10)
                self.reconnect()
            except AttributeError:
                logger.error('Error receiving data from server')
                time.sleep(10)
                self.reconnect()

        return data



# vim:ts=4 sts=4 tw=79 expandtab:

