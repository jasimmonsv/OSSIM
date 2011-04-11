#!/usr/bin/python
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
import os, re, socket, SocketServer, sys, threading

#
# LOCAL IMPORTS
#
import Action
from AlarmGroup import AlarmGroup
import Const
from DoControl import ControlManager
from DoNessus import NessusManager
from DoNagios import NagiosManager
from Logger import Logger
from OssimConf import OssimConf
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger
controlmanager = None

class FrameworkBaseRequestHandler(SocketServer.StreamRequestHandler):

    __nessusmanager = None
    __nagiosmanager = None
    __conf = None



    def handle(self):
        global controlmanager

        self.__id = None

        logger.debug("Request from: %s:%i" % (self.client_address))
        while 1:
            try:
                line = self.rfile.readline().rstrip('\n')

                if len(line) > 0:
                    command = line.split()[0]
                    # set sane default response
                    response = ""

                    # check if we are a "control" request message
                    if command == "control":
                        # spawn our control timer
                        if controlmanager == None:
                            controlmanager = ControlManager()

                        response = controlmanager.process(self, command, line)

                    # otherwise we are some form of standard control message
                    elif command == "nessus":
                        if self.__nessusmanager == None:
                            self.__nessusmanager = NessusManager

                        response = self.__nessusmanager.process(line)

                    elif command == "nagios":
                        if self.__nagiosmanager == None:
                            self.__nagiosmanager = NagiosManager(OssimConf(Const.CONFIG_FILE))

                        response = self.__nagiosmanager.process(line)

                    elif command == "ping":
                        response = "pong\n"

                    elif command == "add_asset":
                        #To all agents:
                        #add_asset hostname=crg ip=192.168.2.15 id=all
                        linebk = line
                        pattern = "add_asset\s+hostname=(?P<hostname>\w+)\s+ip=(?P<ip>\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})"
                        reg_comp = re.compile(pattern)
                        res = reg_comp.match(line)
                        if res is not None:
                            hostname = res.group('hostname')
                            ip = res.group('ip')
                            if hostname is not None and ip is not None:
                                newcommand = 'action="%s" hostname="%s" ip="%s" id=all' % (command, hostname, ip)
                                if controlmanager == None:
                                    controlmanager = ControlManager()
                                response = controlmanager.process(self, command, newcommand)
                            else:
                                logger.debug("Invalid add_asset command:%s", linebk)
                    elif command == "remove_asset":
                        linebk = line
                        pattern = "remove_asset\s+hostname=(?P<hostname>\w+)\s+ip=(?P<ip>\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})"
                        reg_comp = re.compile(pattern)
                        res = reg_comp.match(line)
                        if res is not None:
                            hostname = res.group('hostname')
                            ip = res.group('ip')
                            if hostname is not None and ip is not None:
                                newcommand = 'action="%s" hostname="%s" ip="%s" id=all' % (command, hostname, ip)
                                if controlmanager == None:
                                    controlmanager = ControlManager()
                                response = controlmanager.process(self, command, line)
                    elif command == "refresh_asset_list":
                        line = line + ' id=all'
                        line = 'action="%s"' % command + line
                        if controlmanager == None:
                            controlmanager = ControlManager()
                        response = controlmanager.process(self, command, line)
                    else:
                        a = Action.Action(line)
                        a.start()

                        # Group Alarms
                        #ag = AlarmGroup.AlarmGroup()
                        #ag.start()

                    # return the response as appropriate
                    if len(response) > 0:
                        logger.info("CRG --- Response: %s" % response)
                        self.wfile.write(response)

                    line = ""

                else:
                    return

            except IndexError:
                logger.error("IndexError")

            except Exception, e:
                logger.error("Unexpected exception in control: %s" % str(e))
                return


    def finish(self):
        global controlmanager
        if controlmanager != None:
            controlmanager.finish(self)

        return SocketServer.StreamRequestHandler.finish(self)


    def set_id(self, id):
        self.__id = id


    def get_id(self):
        return self.__id


class FrameworkBaseServer(SocketServer.ThreadingTCPServer):
    allow_reuse_address = True

    def __init__(self, server_address, handler_class=FrameworkBaseRequestHandler):
        SocketServer.ThreadingTCPServer.__init__(self, server_address, handler_class)
        return


    def serve_forever(self):
        while True:
            self.handle_request()

        return



class Listener(threading.Thread):

    def __init__(self):

        self.__server = None
        threading.Thread.__init__(self)


    def run(self):


        try:
            serverAddress = (str(Const.LISTENER_ADDRESS), int(Const.LISTENER_PORT))
            self.__server = FrameworkBaseServer(serverAddress, FrameworkBaseRequestHandler)

        except socket.error, e:
            logger.critical(e)
            sys.exit()


        self.__server.serve_forever()


if __name__ == "__main__":

    listener = Listener()
    listener.start()

# vim:ts=4 sts=4 tw=79 expandtab:
