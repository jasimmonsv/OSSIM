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
import random, threading, time
from threading import Timer
#
# LOCAL IMPORTS
#
from Logger import Logger
import Util
from OssimDB import OssimDB
from OssimConf import OssimConf

# GLOBAL VARIABLES
#
logger = Logger.logger

class ControlManager:
    def __init__(self, conf):
        logger.debug("Initialising ControlManager...")

        self.control_agents = {}
        self.transaction_map = {}
        self.__myDB = OssimDB()
        self.__myDB_connected = False
        self.__myconf = conf
        self.__transaction_timeout = 60
        self.__control = DoControl(self)
        self.__control.start()


    def refreshAgentCache(self, requestor, agent_id):
        if not self.__myDB_connected:
            self.__myDB.connect (self.__myconf["ossim_host"],
            self.__myconf["ossim_base"],
            self.__myconf["ossim_user"],
            self.__myconf["ossim_pass"])
            self.__myDB_connected = True
        #read host list
        query = 'select hostname,ip from host'
        tmp = self.__myDB.exec_query(query)
        new_command = 'action="refresh_asset_list" list={'
        for host in tmp:
            new_command += '%s=%s,' % (host['hostname'], host['ip'])
        new_command = new_command[0:len(new_command) - 1]
        new_command += '}'
        # add this connection to the transaction map
        transaction = self.__transaction_id_get()
        self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}
        # append the transaction to the message for tracking
        self.control_agents[agent_id].wfile.write(new_command + ' transaction="%s"\n' % transaction)
        logger.info("Updating asset list to agent: %s" % agent_id)

    def process(self, requestor, command, line):
        logger.debug("Processing: %s" % line)
        response = ""
        action = Util.get_var("action=\"([^\"]+)\"", line)

        if action == "connect":
            id = Util.get_var("id=\"([^\"]+)\"", line)

            if id != "":
                requestor.set_id(id)
            else:
                requestor.set_id("%s_%i" % (requestor.client_address))

            logger.debug("Adding control agent %s to the list." % id);

            # add this connection to our control agent collection
            self.control_agents[id] = requestor


            # indicate we're good to go
            response = 'ok id="%s"\n' % id
            timer = Timer(5.0, self.refreshAgentCache, (requestor, id,))
            timer.start()


        elif action == "getconnectedagents":

            # set up response
            response = "control getconnectedagents"

            # indicate the number of agents connected
            keys = self.control_agents.keys()
            response += ' count="%d"' % len(keys)

            # build the connected list
            if keys != None:
                # sort list before sending
                keys.sort()

                names = "|".join(keys)
            else:
                names = ""

            response += ' names="%s" errno="0" error="Success." ackend\n' % names

        else:
            # check if we are a transaction
            transaction = Util.get_var("transaction=\"([^\"]+)\"", line)

            if transaction != "":
                if transaction not in self.transaction_map:
                    logger.error("Transaction has no apparent originator!")

                else:
                    # respond to the original requester
                    self.transaction_map[transaction]["socket"].wfile.write(line + "\n")

                    # remove from map if end of transaction
                    if Util.get_var("(ackend)", line) != "":
                        logger.debug("Closing transaction: %s" % transaction)
                        del self.transaction_map[transaction]

            # assume we are a command request to an agent
            else:
                id = Util.get_var("id=\"([^\"]+)\"", line)

                if id == "" or id == "all":
                    logger.debug("Broadcasting to all ...");
                    if len(self.control_agents) == 0:
                        response = line + ' errno="-1" error="No agents available." ackend\n'

                    else:
                        # send line to each control agent
                        for key in self.control_agents:

                            # add this connection to the transaction map
                            transaction = self.__transaction_id_get()
                            self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}

                            # append the transaction to the message for tracking

                            self.control_agents[key].wfile.write(line + ' transaction="%s"\n' % transaction)

                elif id in self.control_agents:
                    logger.debug("Broadcasting to %s ..." % id);

                    # add this connection to the transaction map
                    transaction = self.__transaction_id_get()
                    self.transaction_map[transaction] = {'socket':requestor, 'time':time.time()}

                    # append the transaction to the message for tracking
                    self.control_agents[id].wfile.write(line + ' transaction="%s"\n' % transaction)

                else:
                    response = line + ' errno="-1" error="Agent not available." ackend\n'
                    logger.warning('Agent "%s" is not connected! %s' % (id, message));

        # send back our response
        return response


    def finish(self, requestor):
        id = requestor.get_id()

        # check if we were a control agent and cleanup
        if id is not None and id in self.control_agents:
            logger.debug('Removing control agent "%s" from the list.' % id)
            del self.control_agents[id]

        # clean up outstanding transactions
        for t in self.transaction_map.keys():
            if self.transaction_map[t]["socket"] == requestor:
                logger.debug('Removing outstanding transaction: %s' % t)
                del self.transaction_map[t]


    def __transaction_id_get(self):
        # generate a transaction id to ensure returns are sent to the
        # original requester
        transaction = str(random.randint(0, 65535))
        while transaction in self.transaction_map:
           transaction = str(random.randint(0, 65535))

           logger.debug("Choosing transaction ID: %s" % transaction)

        return transaction


    def check_transaction_timeouts(self):
        if len(self.transaction_map) > 0:
            now = time.time()

            for t in self.transaction_map.keys():
                delta = int(now - self.transaction_map[t]["time"])
                # return a timeout response and close the transaction as required
                if delta > self.__transaction_timeout:
                    response = 'control transaction="%s" errno="-1" error="Transaction timed out due to inactivity for at least %d seconds." ackend\n' % (t, delta)
                    self.transaction_map[t]["socket"].wfile.write(response)
                    del self.transaction_map[t]



class DoControl(threading.Thread):

    def __init__(self, manager):
        self.__manager = manager
        threading.Thread.__init__(self)


    def run(self):
        while 1:
            time.sleep(1)
            self.__manager.check_transaction_timeouts()



