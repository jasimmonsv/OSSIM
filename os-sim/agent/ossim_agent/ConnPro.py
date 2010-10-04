#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import os
import re
import sys
import socket
import string
import thread
import time

#
# LOCAL IMPORTS
#
from Config import Conf, Plugin
from Logger import Logger
from MonitorScheduler import MonitorScheduler
from Stats import Stats
from Watchdog import Watchdog
from __init__ import __version__

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class ServerConnPro:

    __conn = None

    MSG_CONNECT = 'connect id="%s" ' + \
                  'type="sensor" ' + \
                  'version="' + __version__ + '"\n'

    def __init__(self, conf, id):
        self.conf = conf
        self.id = id
        self.server_ip = self.conf.get("output-server-pro", "ip")
        self.server_port = self.conf.get("output-server-pro", "port")
        self.sequence = 0


    def connect(self, attempts=3, waittime=10.0):
        """ Establish connection with the server.

        Keyword Arguments:
        attempts - number of reconnection attempts before failing. a value of 0
                   infers unlimited attempts. (default 0)
        waittime - time in seconds between connection attempts. (default 10.0)
        """

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


    def reconnect(self, attempts=0, waittime=10.0):
        """Reset the current connection by closing and reopening it. """

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
