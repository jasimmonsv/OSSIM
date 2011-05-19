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
import string
import sys
import threading
import Queue
import pickle
import time
#
# LOCAL IMPORTS
#
from Config import Conf, CommandLineOptions
from Event import Event
from Exceptions import AgentCritical
from Logger import Logger

#
# GLOBAL VARIABLES
#
logger = Logger.logger


SIZE_TO_WARNING = 5


PATH_TO_STORE_EVENTS = "/var/ossim/agent_events/"
class StoredEvent:
    '''
        Represents a stored Event. 
    '''
    def __init__(self, e, priority):
        self.__event = e
        self.__priority = priority
        self.__isSend = False

    def get_event(self):
        return self.__event


    def get_is_send(self):
        return self.__isSend


    def set_event(self, value):
        self.__event = value


    def set_is_send(self, value):
        self.__isSend = value


    def del_event(self):
        del self.__event


    def del_is_send(self):
        del self.__isSend

    def get_priority(self):
        return self.__priority


    event = property(get_event, set_event, del_event, "event's docstring")
    isSend = property(get_is_send, set_is_send, del_is_send, "isSend's docstring")


class storedEventSenderThread(threading.Thread):
    '''
        Thread to send stored events to the server.
    '''
    def __init__(self, queue, sendFunctionPtr, sendEvents_event, filename):
        '''
        Constructor:
        queue (Queue.Queue): Queue to receive event to be stored.
        sendFuntionPtr: Pointer to function used to send events.
        sendEvents_event (threading.Event()): Indicates that this thread can start to send events.
        filename: Filename of the file where we store events. 
        '''
        threading.Thread.__init__(self)
        self.__keep_working = True
        self.__storeQueue = queue
        self.__eventList = []
        self.__canSendEvents = sendEvents_event
        self.__filename = filename
        self.__sendFunction = sendFunctionPtr


    def saveToFile(self):
        '''
            Save events to list
        '''
        logger.info("Saving events to file.. %s " % self.__filename)
        pickle.dump(self.__eventList, open(self.__filename, "wb"))


    def loadEventsFile(self):
        if os.path.isfile(self.__filename):
            logger.info("Loading stored events from '%s'" % Ev)
            self.__eventList = pickle.load(open(self.__filename))
            HostResolv.printCache()


    def synchrofile(self):
        '''
            Synchronized events in store_event_list of those stored on HHDD.
        '''
        #Delete send events ..
        logger.info("Synchronizing evnets to file.... ")
        tmplist = []
        if not os.path.exists(PATH_TO_STORE_EVENTS):
            os.mkdir(PATH_TO_STORE_EVENTS)
        for ev in self.__eventList:
            if not ev.get_is_send():
                tmplist.append(ev)
        del self.__eventList[:]
        self.__eventList = tmplist
        self.saveToFile()


    def run(self):
        '''
            Main thread function
        '''
        logger.info("Running stored thread....")
        storedEvents = 0
        while self.__keep_working:
            while not self.__storeQueue.empty():
                #recieved event to store
                ev = self.__storeQueue.get_nowait()
                self.__eventList.append(ev)
                logger.info("Getting event from queue... storedEvents: %s" % storedEvents)
                storedEvents = len(self.__eventList)
                ismult = storedEvents % 10
                if ismult == 0:
                    self.synchrofile()
                if len(self.__eventList) > SIZE_TO_WARNING:
                    logger.warning("Event to stored and waiting to be send %d" % len(self.__eventList))

            if self.__canSendEvents.isSet():
                if len(self.__eventList) > 0:
                    logger.info("Evento to send..%s " % len(self.__eventList))
                    for ev in self.__eventList:
                        if not ev.get_is_send():
                            self.__sendFunction(ev.get_event(), ev.get_priority())
                            ev.set_is_send(True)
                            logger.info("Sending stored event... ")
                        time.sleep(0.2)#5eps
                    self.synchrofile()



    def shutdown(self):
        self.__keep_working = False


class OutputPlugins:

    def _open_file(self, file):
        dir = file.rstrip(os.path.basename(file))

        if not os.path.isdir(dir):
            try:
                os.makedirs(dir, 0755)

            except OSError, e:
                raise AgentCritical("Error creating directory (%s): %s" % \
                    (dir, e))

        try:
            fd = open(file, 'a')

        except IOError, e:
            raise AgentCritical("Error opening file (%s): %s" % (file, e))

        return fd

    #
    # the following methods must be overriden in child classes
    #
    def event(self, e):
        pass


    def shutdown(self):
        pass


    def plugin_state(self, msg):
        pass


class OutputPlain(OutputPlugins):

    def __init__(self, conf):
        self.conf = conf
        logger.info("Added Plain output")
        logger.debug("OutputPlain options: %s" % \
            (self.conf.hitems("output-plain")))
        self.plain = self._open_file(self.conf.get("output-plain", "file"))
        self.activated = True


    def event(self, e):
        if self.activated:
            self.plain.write(str(e))
            self.plain.flush()


    def plugin_state(self, msg):
        if self.activated:
            self.plain.write(msg)
            self.plain.flush()


    def shutdown(self):
        logger.info("Closing Plain file..")
        self.plain.flush()
        self.plain.close()
        self.activated = False


class OutputServer(OutputPlugins):

    def __init__(self, conn, priority, sendEvents):
        logger.info("Added Server output (%s:%s)" % (conn.get_server_ip(), conn.get_server_port()))
        self.conn = conn
        self.activated = True
        self.send_events = sendEvents
        self.__mypriority = priority
        self.options = CommandLineOptions().get_options()
        self.__sendEvents_Event = threading.Event()
        self.__storeThread = None
        self.__storeQueue = Queue.Queue()
        self.__filename = PATH_TO_STORE_EVENTS + "%s.%s" % (conn.get_server_ip(), conn.get_server_port())
        logger.info("Path to store events: %s" % self.__filename)
        self.__storeThread = storedEventSenderThread(self.__storeQueue, self.event, self.__sendEvents_Event , self.__filename)
        self.__storeThread.start()


    def event(self, e, priority):
        if self.activated and self.send_events and self.__mypriority <= priority:
            try:
                if not self.conn.get_is_alive():
#                    if re.search("(event\s+.*)", e):
                    logger.info("Event stored...:%s" % e)
                    self.__storeEvent(e, priority)
                    self.__sendEvents_Event.clear()
                else:
                    self.conn.send(str(e))
                    self.__sendEvents_Event.set()
            except:
#                if re.search("(event\s+.*)", e):
                logger.info("Event stored...:%s" % e)
                self.__storeEvent(e, priority)
                self.__sendEvents_Event.clear()
                return


    def __storeEvent(self, event, priority):
        stEvent = StoredEvent(event, priority)
        logger.info("Storing event...:%s" % event)
        self.__storeQueue.put_nowait(stEvent)


    def plugin_state(self, msg):
        if self.activated:
            try:
                self.conn.send(msg)
            except:
                return


    def shutdown(self):
        self.conn.close()
        self.activated = False
        if self.__storeThread:
            self.__storeThread.shutdown()
            self.__storeThread.join()


class OutputCSV(OutputPlugins):

    def __init__(self, conf):

        self.conf = conf
        logger.info("Added CSV output")
        logger.debug("OutputCSV options: %s" % (self.conf.hitems("output-csv")))

        file = self.conf.get("output-csv", "file")
        first_creation = not os.path.isfile(file)
        self.csv = self._open_file(file)
        if first_creation:
            self.__write_csv_header()
        self.activated = True


    def __write_csv_header(self):

        header = ''

        for attr in Event.EVENT_ATTRS:
            header += "%s," % (attr)
        self.csv.write(header.rstrip(",") + "\n")
        self.csv.flush()


    def __write_csv_event(self, e):

        event = ''

        for attr in e.EVENT_ATTRS:
            if e[attr] is not None:
                event += "%s," % (string.replace(e[attr], ',', ' '))

            else:
                event += ","

        self.csv.write(event.rstrip(',') + "\n")
        self.csv.flush()


    def event(self, e, priority=0):

        if self.activated:
            if e["event_type"] == "event":
                self.__write_csv_event(e)


    def shutdown(self):
        logger.info("Closing CSV file..")
        self.csv.flush()
        self.csv.close()
        self.activated = False


class OutputDB(OutputPlugins):

    from Database import DatabaseConn

    def __init__(self, conf):
        logger.info("Added Database output")
        logger.debug("OutputDB options: %s" % (conf.hitems("output-db")))

        self.conf = conf

        type = self.conf.get('output-db', 'type')
        host = self.conf.get('output-db', 'host')
        base = self.conf.get('output-db', 'base')
        user = self.conf.get('output-db', 'user')
        password = self.conf.get('output-db', 'pass')

        self.conn = OutputDB.DatabaseConn()
        self.conn.connect(type, host, base, user, password)
        self.activated = True


    def event(self, e, priority=0):

        if self.conn is not None and e["event_type"] == "event" \
           and self.activated:

            # build query
            query = 'INSERT INTO event ('

            for attr in e.EVENT_ATTRS:
                query += "%s," % (attr)

            query = query.rstrip(',')
            query += ") VALUES ("

            for attr in e.EVENT_ATTRS:
                value = ''

                if e[attr] is not None:
                    value = e[attr]

                query += "'%s'," % (value)

            query = query.rstrip(',')
            query += ");"

            logger.debug(query)

            try:
                self.conn.exec_query(query)

            except Exception, e:
                logger.error(": Error executing query (%s)" % (e))


    def shutdown(self):
        logger.info("Closing database connection..")
        self.conn.close()
        self.activated = False


class Output:
    """Different ways to log ossim events (Event objects)."""

    _outputs = []
    plain_output = server_output = server_output_pro = csv_output = db_output = False
    _priority = 0
    _printEvents = True
    _shutdown = False
    def print_ouput_events(value):
        logger.debug("Setting printEvents to %s" % value)
        Output._printEvents = value
    print_ouput_events = staticmethod(print_ouput_events)
    def set_priority(priority):
        Output._priority = priority
    set_priority = staticmethod(set_priority)

    def get_current_priority():
        return Output._priority
    get_current_priority = staticmethod(get_current_priority)


    def add_plain_output(conf):
        if Output.plain_output is False:
            Output._outputs.append(OutputPlain(conf))
            Output.plain_output = True

    add_plain_output = staticmethod(add_plain_output)


    def add_server_output(conn, priority, sendEvents):
        Output._outputs.append(OutputServer(conn, priority, sendEvents))

    add_server_output = staticmethod(add_server_output)


    def add_csv_output(conf):
        if Output.csv_output is False:
            Output._outputs.append(OutputCSV(conf))
            Output.csv_output = True

    add_csv_output = staticmethod(add_csv_output)


    def add_db_output(conf):
        if Output.db_output is False:
            Output._outputs.append(OutputDB(conf))
            Output.db_output = True

    add_db_output = staticmethod(add_db_output)


    def event(e):
        if Output._shutdown:
            return
        if Output._printEvents:
            logger.info(str(e).rstrip())
        for output in Output._outputs:
            output.event(e, Output.get_current_priority())

    event = staticmethod(event)


    def plugin_state(msg):
        logger.info(str(msg).rstrip())

        for output in Output._outputs:
            output.plugin_state(msg)

    plugin_state = staticmethod(plugin_state)


    def shutdown():
        Output._shutdown = True
        for output in Output._outputs:
            output.shutdown()

    shutdown = staticmethod(shutdown)


if __name__ == "__main__":

    event = Event()
    Output.add_server_output()
    Output.event(event)
    Output.add_csv_output()
    Output.event(event)

# vim:ts=4 sts=4 tw=79 expandtab:
