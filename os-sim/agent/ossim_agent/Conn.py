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
import os, re, socket, string, sys, thread, time
import threading
#
# LOCAL IMPORTS
#
from Config import Conf, Plugin
from Control import ControlManager
from Event import WatchRule
from Logger import *
from MonitorScheduler import MonitorScheduler
from Stats import Stats
import Utils
from Watchdog import Watchdog
from __init__ import __version__

#
# GLOBAL VARIABLES
#
logger = Logger.logger
MAX_TRIES = 3

class ServerData:
    '''
        Server data from ouptut-server-list section
    '''
    def __init__(self, hostname, \
                 ip, \
                 port, \
                 priority, \
                 sendEvents, \
                 allow_frmk_data):
        self.__ip = ip
        self.__hostname = hostname
        self.__port = port
        self.__priority = priority
        self.__sendEvents = sendEvents
        self.__allow_frmk_data = allow_frmk_data

    def get_ip(self):
        return self.__ip


    def get_hostname(self):
        return self.__hostname


    def get_port(self):
        return self.__port


    def get_priority(self):
        return self.__priority


    def get_send_events(self):
        return self.__sendEvents


    def get_allow_frmk_data(self):
        return self.__allow_frmk_data


    def set_ip(self, value):
        self.__ip = value


    def set_hostname(self, value):
        self.__hostname = value


    def set_port(self, value):
        self.__port = value


    def set_priority(self, value):
        self.__priority = value


    def set_send_events(self, value):
        self.__sendEvents = value


    def set_allow_frmk_data(self, value):
        self.__allow_frmk_data = value


    def del_ip(self):
        del self.__ip


    def del_hostname(self):
        del self.__hostname


    def del_port(self):
        del self.__port


    def del_priority(self):
        del self.__priority


    def del_send_events(self):
        del self.__sendEvents


    def del_allow_frmk_data(self):
        del self.__allow_frmk_data

    def __repr__(self):
        return "HN:%s |IP:%s |PORT: %s | PRIO: %s | SE:%s | AFD:%s " % (self.__hostname, self.__ip, self.__port, self.__priority, self.__sendEvents, self.__allow_frmk_data)


class ServerConn:

    __conn = None

    MSG_CONNECT = 'connect id="%s" ' + \
                          'type="sensor" ' + \
                          'version="' + __version__ + '"\n'
    MSG_APPEND_PLUGIN = 'session-append-plugin id="%s" ' + \
                          'plugin_id="%s" enabled="%s" state="%s"\n'
    MSG_GET_FRAMEWORK = 'server-get-framework\n'
    def __init__(self, server_ip, server_port, priority, allow_frmk_data, sendEvents, plugins):
        #self.conf = conf
        self.plugins = plugins
        self.sequence = 0
        self.server_ip = server_ip
        self.server_port = server_port
        self.allow_frmk_data = allow_frmk_data
        self.monitor_scheduler = MonitorScheduler()
        self.monitor_scheduler.start()
        self.__patternFrmkMessageResponse = re.compile("server_ip=\"(?P<srv_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}))\" server_name=\"(?P<srv_name>([^\"]+))\" server_port=\"(?P<srv_port>\d+)\" framework_ip=\"(?P<frmk_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}))\" framework_name=\"(?P<frmk_name>([^\"]+))\" framework_port=\"(?P<frmk_port>\d+)\"")
        self.frmk_hostname = ''
        self.frmk_ip = ''
        self.frmk_port = ''
        self.priority = priority
        self.__isAlive = False
        self.__stopped = False
        self.__sendEvents = sendEvents
        self.__keep_working = True
    # connect to server
    #  attempts == 0 means that agent try to connect forever
    #  waittime = seconds between attempts
    def connect(self, attempts=3, waittime=10.0):
        self.__keep_working = True
        self.sequence = 1
        count = 1

        if self.__conn is None:

            logger.info("Connecting to server (%s, %s).." \
                % (self.server_ip, self.server_port))

            while self.__keep_working:

                self.__connect_to_server()
                if self.__conn is not None:
                    self.__isAlive = True
                    self.__append_plugins()
                    if self.allow_frmk_data:
                        self.frmk_hostname, self.frmk_ip, self.frmk_port = self.__get_framework_connection_data()
                        logger.debug("Server (%s:%s) Framework Connnection Data FRMK_HN:%s, FRMK_IP:%s, FRMK_PORT:%s" % (self.server_ip, self.server_port, self.frmk_hostname, self.frmk_ip, self.frmk_port))
                    else:
                        logger.info("This server (%s:%s) doesn't support framework data connection" % (self.server_ip, self.server_port))
                    break

                else:
                    logger.info("Can't connect to server, " + \
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
        self.__isAlive = False
        self.__keep_working = False


    # Reset the current connection by closing and reopening it
    def reconnect(self, attempts=0, waittime=10.0):

        self.close()
        time.sleep(1)
        Stats.server_reconnect(self.server_ip)
        tmptries = 0
        while tmptries < MAX_TRIES:
            if self.connect(attempts, waittime) is not None:
                break
            tmptries += 1
        if tmptries == MAX_TRIES:
            self.__stopped = True
            self.__keep_working = False


    def send(self, msg):

        while self.__keep_working:
            try:
                if self.__isAlive:
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
            self.__isAlive = False
        else:
            if data == 'ok id="' + str(self.sequence) + '"\n':
                logger.info("Connected to server %s:%s!" % (self.server_ip, self.server_port))
                self.__stopped = False
                self.__keep_working = True
            else:
                logger.error("Bad response from server: %s" % (str(data)))
                self.__conn = None
                self.__isAlive = False

        return self.__conn


    def __append_plugins(self):

        logger.debug("Apending plugins..")
        msg = ''

        for plugin in self.plugins:
            self.sequence += 1
            if plugin.getboolean("config", "enable"):
                msg = self.MSG_APPEND_PLUGIN % \
                        (str(self.sequence),
                        plugin.get("config", "plugin_id"),
                        'true', 'start')
            else:
                msg = self.MSG_APPEND_PLUGIN % \
                        (str(self.sequence),
                        plugin.get("config", "plugin_id"),
                        'false', 'stop')
            self.send(msg)


    def recv_line(self):

        char = data = ''

        while self.__keep_working:
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


    def __recv_control_messages(self):

        ####### watch-rule test #######
        if (0):
            time.sleep(1)
            data = 'watch-rule plugin_id="2005" ' + \
               'plugin_sid="246" condition="gt" value="1" ' + \
               'from="127.0.0.1" to="127.0.0.1" ' + \
               'port_from="4566" port_to="22"'
            self.__control_monitors(data)
        ###############################

        while self.__keep_working:

            try:
                # receive message from server (line by line)
                data = self.recv_line()
                logger.info("Received message from server: " + data.rstrip())

                # 1) type of control messages: plugin management
                #    (start, stop, enable and disable plugins)
                #
                if data.startswith(Watchdog.PLUGIN_START_REQ) or \
                   data.startswith(Watchdog.PLUGIN_STOP_REQ) or \
                   data.startswith(Watchdog.PLUGIN_ENABLE_REQ) or \
                   data.startswith(Watchdog.PLUGIN_DISABLE_REQ):

                    self.__control_plugins(data)

                # 2) type of control messages: watch rules (monitors)
                #
                elif data.startswith('watch-rule'):

                    self.__control_monitors(data)

            except Exception, e:
                logger.error(
                    'Unexpected exception receiving from server: ' + str(e))


    def __control_plugins(self, data):

        # get plugin_id of process to start/stop/enable/disable
        pattern = re.compile('(\S+) plugin_id="([^"]*)"')
        result = pattern.search(data)
        if result is not None:
            (command, plugin_id) = result.groups()
        else:
            logger.warning("Bad message from server: %s" % (data))
            return

        # get plugin from plugin list searching by the plugin_id given
        for plugin in self.plugins:
            if int(plugin.get("config", "plugin_id")) == int(plugin_id):

                if command == Watchdog.PLUGIN_START_REQ:
                    Watchdog.start_process(plugin)

                elif command == Watchdog.PLUGIN_STOP_REQ:
                    Watchdog.stop_process(plugin)

                elif command == Watchdog.PLUGIN_ENABLE_REQ:
                    Watchdog.enable_process(plugin)

                elif command == Watchdog.PLUGIN_DISABLE_REQ:
                    Watchdog.disable_process(plugin)

                break


    def __control_monitors(self, data):

        # build a watch rule, the server request.
        watch_rule = WatchRule()
        for attr in watch_rule.EVENT_ATTRS:
            pattern = ' %s="([^"]*)"' % (attr)
            result = re.findall(pattern, data)
            if result != []:
                watch_rule[attr] = result[0]

        for plugin in self.plugins:

            # look for the monitor to be called
            if plugin.get("config", "plugin_id") == watch_rule['plugin_id'] and\
               plugin.get("config", "type").lower() == 'monitor':

                self.monitor_scheduler.\
                    new_monitor(type=plugin.get("config", "source"),
                                plugin=plugin,
                                watch_rule=watch_rule)
                break


    def control_messages(self):
        '''
        Launch new thread to manage control messages
        '''
        thread.start_new_thread(self.__recv_control_messages, ())


    def get_allow_frmk_data(self):
        return self.allow_frmk_data


    def get_is_alive(self):
        return self.__isAlive


    def get_server_ip(self):
        return self.server_ip


    def get_server_port(self):
        return self.server_port


    def get_send_events(self):
        return self.__sendEvents


    def __get_framework_connection_data(self):
        frmk_ip = ""
        frmk_port = ""
        frmk_hostname = ""
        if self.__conn is not None:
            try:
                logger.info("Waiting for framework connection data from %s:%s" % (self.server_ip, self.server_port))
                self.__conn.send(ServerConn.MSG_GET_FRAMEWORK)
                data = self.__conn.recv(1024)
                time.sleep(1)
            except socket.error, e:
                logger.error("Socket (%s:%s) is down. Error_str: %s" % (self.server_ip, self.server_port, str(e)))
                self.__conn = None
            if not data:
                logger.error("No reponse for 'server-get-framework' request")
            else:
                response_data = self.__patternFrmkMessageResponse.match(data)
                if response_data is not None:
                    frmk_ip = response_data.group('frmk_ip')
                    frmk_hostname = response_data.group('frmk_name')
                    frmk_port = response_data.group('frmk_port')
                else:
                    logger.error("Bad reponse for 'server-get-framework' request")
        else:
            self.__logFunctionPtr("I'm not connected!", LogMsg.ERROR)
        return frmk_hostname, frmk_ip, frmk_port


    def get_framework_data(self):
        return self.frmk_hostname, self.frmk_ip, self.frmk_port


    def get_priority(self):
        return self.priority


    def get_is_stopped(self):
        return self.__stopped


class FrameworkConn():

    __conn = None
    __controlmanager = None


    MSG_CONNECT = 'control id="%s" action="connect" version="' + __version__ + '"\n'


    def __init__(self, conf, frmk_id, frmk_ip, frmk_port):
        self._framework_id = frmk_id #conf.get("control-framework", "id")
        self._framework_ip = frmk_ip#conf.get("control-framework", "ip")
        self._framework_port = frmk_port #conf.get("control-framework", "port")
        self._framework_ping = True
        self.__keep_processing = True
        # instatiate the control manager
        self.__controlmanager = ControlManager(conf)


    # connect to framework daemon
    #  attempts == 0 means that agent try to connect forever
    #  waittime = seconds between attempts
    def connect(self, attempts=0, waittime=10.0):

        # connection attempt counter
        count = 0

        if self.__conn is None:

            logger.info("Connecting to control framework (%s:%s) ..." \
                % (self._framework_ip, self._framework_port))

            while attempts == 0 or count < attempts:
                self.__connect_to_server()

                if self.__conn is not None:
                    break

                else:
                    logger.info("Can't connect to control framework, " + \
                                "retrying in %d seconds" % (waittime))
                    if self.__keep_processing:
                        time.sleep(waittime)
                    else:
                        count = attempts

                count += 1

        else:
            logger.info("Reusing control framework connection (%s:%s) ..." \
                % (self.server_ip, self.server_port))

        return self.__conn


    def close(self):
        logger.info("Closing control framework connection ...")

        if self.__conn is not None:
            self.__conn.close()
            self.__conn = None
        self.__keep_processing = False
        self.__controlmanager.stopProcess()



    # Reset the current connection by closing and reopening it
    def reconnect(self, attempts=0, waittime=10.0):

        self.close()
        time.sleep(2)

        while self.__keep_processing:
            if self.connect(attempts, waittime) is not None:
                break


    def send(self, msg):
        while self.__keep_processing:
            try:
                self.__conn.send(msg)

            except socket.error, e:
                logger.error(e)
                self.reconnect()

            except AttributeError: # self.__conn == None
                self.reconnect()

            else:
                logger.debug(msg.rstrip())
                return


    def __connect_to_server(self):
        self.__conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        # establish a 15 minute timeout on the socket
        self.__conn.settimeout(15)

        data = ""

        try:
            self.__conn.connect((self._framework_ip, int(self._framework_port)))
            self.__conn.send(self.MSG_CONNECT % self._framework_id)

            logger.debug("Waiting for control framework ...")

            data = self.__conn.recv(1024)

        except socket.timeout, e:
            logger.error("Timed out (%us) waiting for the control framework!" % self.__conn.gettimeout())
            self.__conn = None

        except socket.error, e:
            logger.error("Unable to connect to the control framework!")
            self.__conn = None

        else:
            if data == 'ok id="' + str(self._framework_id) + '"\n':
                logger.info("Connected to the control framework (%s:%s) !" % (self._framework_ip, self._framework_port))

            else:
                logger.error("Bad response from the control framework: %s" % (str(data)))
                self.__conn = None


    def __recv_line(self):

        char = data = ''

        while self.__keep_processing:
            try:
                char = self.__conn.recv(1)

            except socket.timeout, e:
                logger.debug("Timed out waiting!")

            except socket.error, e:
                logger.error('Error receiving data from the control framework: %s' % str(e))
                self.reconnect()

            except AttributeError:
                logger.error('Error receiving data from the control framework!')
                self.reconnect()

            else:
                data += char

                if char == '\n':
                    break

                elif char == '':
                    logger.warning('Connection to the control framework appears to be down.')
                    self.reconnect()

        return data


    # receive control messages from the framework daemon
    def __recv_control_messages(self):

        while self.__keep_processing:
            try:
                # receive message from server (line by line)
                data = self.__recv_line().rstrip('\n')

                try:
                    response = self.__controlmanager.process(self.__conn, data)
                    # send out all items in the response queue
                    while len(response) > 0:
                        self.send(response.pop(0))

                except Exception, e:
                    logger.warning('Unexpected exception: %s' % str(e))

            except Exception, e:
                logger.error(
                    'Unexpected exception receiving from the control framework: %s' % str(e))


    def __ping(self):
        while self.__keep_processing:
            self.send("ping\n")
            time.sleep(60)


    # launch new thread to manage control messages
    def control_messages(self):
        thread.start_new_thread(self.__recv_control_messages, ())

        # enable keep-alive pinging if appropriate
        if self._framework_ping:
            thread.start_new_thread(self.__ping, ())


# vim:ts=4 sts=4 tw=79 expandtab:

