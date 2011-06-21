#!/usr/bin/env python
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
import sys
import time
import signal
import string
import thread
import re
import threading
import socket

#
# LOCAL IMPORTS
#
from Config import Conf, Plugin, Aliases, CommandLineOptions
from ParserLog import ParserLog
from Watchdog import Watchdog
from Logger import Logger
from Output import Output
from Stats import Stats
from Conn import ServerConn, FrameworkConn, ServerData
#from ConnPro import ServerConnPro
from Exceptions import AgentCritical
from ParserUnifiedSnort import ParserUnifiedSnort
from ParserDatabase import ParserDatabase
from ParserWMI import ParserWMI
from ParserSDEE import ParserSDEE
from ParserRemote import ParserRemote
from ParserUtil import HostResolv
import codecs
import pdb
#
# GLOBAL VARIABLES
#
logger = Logger.logger



class Agent:

    def __init__(self):

        # parse command line options
        self.options = CommandLineOptions().get_options()
        # read configuration
        self.conf = Conf()
        if self.options.config_file:
            conffile = self.options.config_file

        else:
            conffile = self.conf.DEFAULT_CONFIG_FILE

        self.conf.read([conffile], False)

        # aliases
        aliases = Aliases()
        aliases.read([os.path.join(os.path.dirname(conffile), "aliases.cfg")], False)
        local_aliases_fn = os.path.join(os.path.dirname(conffile), "aliases.local")
        #if aliases.local exists, after we've loaded aliases default file, 
        #we load aliases.local
        if os.path.isfile(local_aliases_fn):
            logger.info("Reading local aliases file: %s" % local_aliases_fn)
            aliases.read(local_aliases_fn, False)
        # list of plugins and total number of rules within them
        self.plugins = []
        self.nrules = 0

        for name, path in self.conf.hitems("plugins").iteritems():
            if os.path.exists(path):
                plugin = Plugin()

                #Check if unicode support is needed.
                ff = open (path, 'r')
                bom = ff.read(4)
                withunicode = False

                if bom.startswith(codecs.BOM_UTF8):
                    logger.info("Plugin configuration file: %s is encoded as utf-8, all regular expressions will be compiled as unicode" % path)
                    withunicode = True
                ff.close()

                # Now read the config file
                plugin.read(path, withunicode)
                
                #check if custom plugin configuration exist
                custompath = "%s.local" % path
                if os.path.exists(custompath):
                    logger.warning("Loading custom configuration for plugin: %s" % custompath)
                    custom_plug = Plugin()
                    custom_plug.read(custompath,withunicode,False)
                                     
                    for section in custom_plug.sections():
                        for item in custom_plug.hitems(section):
                            new_value = custom_plug.get(section,item)
                            old_value = plugin.get(section,item)
                            
                            if new_value != old_value:
                                plugin.set(section,item,new_value)
                                logger.warning("Loading custon value for %s--->%s. New value: %s - Old value: %s" % (section,item,new_value,old_value))
                self.nrules += len(plugin.sections()) \
                               - plugin.sections().count('translation') \
                               - 1 # [config]

                plugin.set("config", "name", name)
                plugin.set("config", "unicode_support", str(withunicode))
                plugin.replace_aliases(aliases)
                plugin.replace_config(self.conf)
                self.plugins.append(plugin)
                self.nrules += len(plugin.sections()) \
                               - plugin.sections().count('translation') \
                               - 1 # [config]

            else:
                logger.error("Unable to read plugin configuration (%s) at (%s)" % (name, path))

        HostResolv.loadHostCache()
        # server connection (only available if output-server is enabled)
        self.conn = None

        self.conn_framework = None

        # pro server connection (only available if output-server-pro is enabled)
        self.conn_plugins = {}
        self.conn_plugin_set = []

        self.detector_objs = []
        self.watchdog = None
        self.shutdown_running = False
        #output server list.
        self.__outputServerList = []
        self.__outputServerConnecitonList = []
        self.__frameworkConnecitonList = []
        self.__connect_to_server_end = False
        self.__keep_working = True
        self.__currentPriority = 0
        self.__checkThread = None
        self.__stop_server_counter_array = {}
        self.__output_dic = {}


    def setShutDownRunning(self, value):
        self.shutdown_running = value


    def getShutDownRunning(self):
        return self.shutdown_running


    def init_logger(self):
        """Initiate the logger. """

        # open file handlers (main and error logs)
        if self.conf.has_option("log", "file"):
            Logger.add_file_handler(self.conf.get("log", "file"))

        if self.conf.has_option("log", "error"):
            Logger.add_error_file_handler(self.conf.get("log", "error"))

        if self.conf.has_option("log", "syslog"):
            if (self.conf.get("log", "syslog")):
                Logger.add_syslog_handler((self.conf.get("log", "syslog"), 514))

        # adjust verbose level
        verbose = self.conf.get("log", "verbose")
        if self.options.verbose is not None:
            # -v or -vv command line argument
            #  -v -> self.options.verbose = 1
            # -vv -> self.options.verbose = 2
            for i in range(self.options.verbose):
                verbose = Logger.next_verbose_level(verbose)

        Logger.set_verbose(verbose)


    def init_stats(self):
        '''
            Initialize Stats 
        '''
        Stats.startup()

        if self.conf.has_section("log"):
            if self.conf.has_option("log", "stats"):
                Stats.set_file(self.conf.get("log", "stats"))


    def init_output(self):
        '''
            Initialize Outputs
        '''

        printEvents = True

        if self.conf.has_section("output-properties"):
            printEvents = self.conf.getboolean("output-properties", "printEvents")
        Output.print_ouput_events(printEvents)


        if self.conf.has_section("output-plain"):
            if self.conf.getboolean("output-plain", "enable"):
                Output.add_plain_output(self.conf)

        # output-server is enabled in connect_server()
        # if the connection becomes availble

        if self.conf.has_section("output-csv"):
            if self.conf.getboolean("output-csv", "enable"):
                Output.add_csv_output(self.conf)

        if self.conf.has_section("output-db"):
            if self.conf.getboolean("output-db", "enable"):
                Output.add_db_output(self.conf)
        Output.set_priority(0)
        self.__currentPriority = 0

    def is_frmk_in_list(self,frmk_ip):
        for fmk in self.__frameworkConnecitonList:
            if fmk.get_frmkip() == frmk_ip:
                return True
        return False
    def get_frmk(self,frmk_ip):
        for fmk in self.__frameworkConnecitonList:
            if frmk.get_frmkip() == frmk_ip:
                return fmk
        return None
    def get_is_srv_in_list(self,srv_ip):
        for srv in self.__outputServerConnecitonList:
            if srv.get_server_ip() == srv_ip:
                return True
        return False
    
    def connect_framework(self):
        '''
            Request each server connection for its framework data and try to connect it.
        '''
        conn_counter = 0
        logger.info("----------------------------------- FRAMEWORK CONNECTIONS ----------------------------")
        for server_conn in self.__outputServerConnecitonList:
            if server_conn.get_priority() <= self.__currentPriority \
            and server_conn.get_is_alive() and server_conn.get_has_valid_frmkdata():
            
                frmk_tmp_id, frmk_tmp_ip, frmk_tmp_port = server_conn.get_framework_data()
                tmpFrameworkConn = None
                tryConnect = False
                if not self.is_frmk_in_list(frmk_tmp_ip):
                    tmpFrameworkConn = FrameworkConn(self.conf, frmk_tmp_id, frmk_tmp_ip, frmk_tmp_port)
                    tryConnect = True
                else:
                    tmpFrameworkConn=self.get_frmk(frmk_tmp_ip)
                    if tmpFrameworkConn is not None and not tmpFrameworkConn.frmk_alive():
                        tryConnect = True
                if tryConnect:
                    if tmpFrameworkConn.connect(attempts=3, waittime=30):
                        logger.debug("Control Framework (%s:%s) is now enabled!" % (frmk_tmp_ip, frmk_tmp_port))
                        conn_counter += 1
                        tmpFrameworkConn.frmk_control_messages()
                        self.__frameworkConnecitonList.append(tmpFrameworkConn)
        if conn_counter == 0:
            logger.warning("No Framework connections available")
        logger.info("----------------------------------- FRAMEWORK CONNECTIONS ENDS------------------------")


    def connect_server(self):
        '''
            Try to connect to configured servers.
        '''
        logger.debug("----------------------------------- SERVER CONNECTIONS -------------------------------")
        #If our output server list is not empty we have to connect to the server into the list.
        tmpPrioConnectedServer = -1
        if len(self.__outputServerList) > 0:
            for serverdata in self.__outputServerList:
                if serverdata.get_priority() >= tmpPrioConnectedServer and  serverdata.get_send_events():
                    tmpConnection = ServerConn(serverdata.get_ip(), serverdata.get_port(), serverdata.get_priority(), \
                                               serverdata.get_allow_frmk_data(), serverdata.get_send_events(), self.plugins)
                    if serverdata.get_configured_framework():
                        tmpConnection.set_framework_data(serverdata.get_frmk_hostname(), \
                                                         serverdata.get_frmk_ip(), \
                                                         serverdata.get_frmk_port())
                    if tmpConnection.connect(attempts=3, waittime=30):
                        tmpConnection.control_messages()
                        tmpPrioConnectedServer = serverdata.get_priority()
                        Output.add_server_output(tmpConnection, serverdata.get_priority(), serverdata.get_send_events())
                        self.__output_dic[serverdata.get_ip()] = 1
                        self.__connect_to_server_end = True
                    
                    self.__outputServerConnecitonList.append(tmpConnection)

        logger.debug("----------------------------------- SERVER CONNECTIONS ENDS---------------------------")


    def check_pid(self):
        """Check if a running instance of the agent already exists. """

        pidfile = self.conf.get("daemon", "pid")

        # check for other ossim-agent instances when not using --force argument
        if self.options.force is None and os.path.isfile(pidfile):
            raise AgentCritical("There is already a running instance")

        # remove ossim-agent.pid file when using --force argument
        elif os.path.isfile(pidfile):
            try:
                os.remove(pidfile)

            except OSError, e:
                logger.warning(e)


    def createDaemon(self):
        """Detach a process from the controlling terminal and run it in the
        background as a daemon.

        Note (DK): Full credit for this daemonize function goes to Chad J. Schroeder.
        Found it at ASPN http://aspn.activestate.com/ASPN/Cookbook/Python/Recipe/278731
        Please check that url for useful comments on the function.
        """

        # Install a handler for the terminate signals
        signal.signal(signal.SIGTERM, self.terminate)

        # -d command-line argument
        if self.options.daemon:
            self.conf.set("daemon", "daemon", "True")

        if self.conf.getboolean("daemon", "daemon") and \
            self.options.verbose is None:
            logger.info("Forking into background..")

            UMASK = 0
            WORKDIR = "/"
            MAXFD = 1024
            REDIRECT_TO = "/dev/null"

            if (hasattr(os, "devnull")):
                REDIRECT_TO = os.devnull

            try:
                pid = os.fork()

            except OSError, e:
                raise Exception, "%s [%d]" % (e.strerror, e.errno)
                sys.exit(1)

            # check if we are the first child
            if (pid == 0):
                os.setsid()

                # attempt to fork a second child
                try:
                    pid = os.fork()   # Fork a second child.

                except OSError, e:
                    raise Exception, "%s [%d]" % (e.strerror, e.errno)
                    sys.exit(1)

                # check if we are the second child
                if (pid == 0):
                    os.chdir(WORKDIR)
                    os.umask(UMASK)

                # otherwise exit the parent (the first child of the second child)
                else:
                    open(self.conf.get("daemon", "pid"), 'w').write("%d" % pid)
                    os._exit(0)

            # otherwise exit the parent of the first child
            else:
                os._exit(0)

            import resource         # Resource usage information.
            maxfd = resource.getrlimit(resource.RLIMIT_NOFILE)[1]
            if (maxfd == resource.RLIM_INFINITY):
                maxfd = MAXFD

            for fd in range(0, maxfd):
                try:
                    os.close(fd)

                except OSError:      # ERROR, fd wasn't open to begin with (ignored)
                    pass

            os.open(REDIRECT_TO, os.O_RDWR) # standard input (0)
            os.dup2(0, 1)                   # standard output (1)
            os.dup2(0, 2)                   # standard error (2)
            return(0)


    def init_plugins(self):

        for plugin in self.plugins:
            if plugin.get("config", "type") == "detector":
                plugin_id = plugin.get("DEFAULT", "plugin_id")

                if plugin.get("config", "source") == "log":
                    if plugin_id in self.conn_plugins:
                        parser = ParserLog(self.conf, plugin, self.conn_plugins[plugin_id])

                    else:
                        parser = ParserLog(self.conf, plugin, None)

                    parser.start()
                    self.detector_objs.append(parser)

                elif plugin.get("config", "source") == "snortlog":
                    if plugin_id in self.conn_plugins:
                        parser = ParserUnifiedSnort(self.conf, plugin, self.conn_plugins[plugin_id])

                    else:
                        parser = ParserUnifiedSnort(self.conf, plugin, None)

                    parser.start()
                    self.detector_objs.append(parser)

                elif plugin.get("config", "source") == "database":
                    if plugin_id in self.conn_plugins:
                        parser = ParserDatabase(self.conf, plugin, self.conn_plugins[plugin_id])

                    else:
                        parser = ParserDatabase(self.conf, plugin, None)

                    parser.start()
                    self.detector_objs.append(parser)

                elif plugin.get("config", "source") == "wmi":
                    line_cnt = 0
                    try:
                        credentials = open(plugin.get("config", "credentials_file"), "rb")
                    except:
                        logger.warning("Unable to load wmi credentials file %s, disabling wmi collection." % (plugin.get("config", "credentials_file")))
                        plugin.set("config", "enable", "no")
                        continue
                    for row in credentials:
                        creds = row.split(",")
                        # TODO: Check for shell escape chars in host, user and pass that could break this
                        if plugin_id in self.conn_plugins:
                            parser = ParserWMI(self.conf, plugin, self.conn_plugins[plugin_id], creds[0], creds[1], creds[2])

                        else:
                            parser = ParserWMI(self.conf, plugin, None, creds[0], creds[1], creds[2])

                        parser.start()

                elif plugin.get("config", "source") == "sdee":
                    if plugin_id in self.conn_plugins:
                        parser = ParserSDEE(self.conf, plugin, self.conn_plugins[plugin_id])

                    else:
                        parser = ParserSDEE(self.conf, plugin, None)
                    parser.start()
                    self.detector_objs.append(parser)
                elif plugin.get("config", "source") == "remote-log":
                    if plugin_id in self.conn_plugins:
                        parser = ParserRemote(self.conf, plugin, self.conn_plugins[plugin_id])
                    else:
                        parser = ParserRemote(self.conf, plugin, None)
                        logger.info("Remote Log parser.........................................")

                    parser.start()
                    self.detector_objs.append(parser)

        logger.info("%d detector rules loaded" % (self.nrules))


    def init_watchdog(self):
        '''
            Starts Watchdog thread
        '''
        if self.conf.getboolean("watchdog", "enable"):
            self.watchdog = Watchdog(self.conf, self.plugins)
            self.watchdog.start()


    def terminate(self, sig, params):
        '''
            Handle terminate signal
        '''
        if self.getShutDownRunning() == False:
            logger.info("WARNING: Shutdown received! - Processing it ...!")
            self.shutdown()
        else:
            logger.info("WARNING: Shutdown received! - We can't process it because another shutdonw process is running!")


    def shutdown(self):
        '''
            Handles shutdown signal. Stop all threads, plugist, closes connections...
        '''
        #Disable Ctrl+C signal.
        signal.signal(signal.SIGINT, signal.SIG_IGN)
        logger.warning("Kill signal received, exiting...")
        self.setShutDownRunning(True)
        Watchdog.setShutdownRunning(True)
        self.__keep_working = False
        logger.info("Waiting for check thread..")
        if self.__checkThread is not None:
            self.__checkThread.join(2)

        # Remove the pid file
        pidfile = self.conf.get("daemon", "pid")
        if os.path.exists(pidfile):
            f = open(pidfile)
            pid_from_file = f.readline()
            f.close()

            try:
                # don't remove the ossim-agent.pid file if it 
                # belongs to other ossim-agent process
                if pid_from_file == str(os.getpid()):
                    os.remove(pidfile)

            except OSError, e:
                logger.warning(e)


        # output plugins
        Output.shutdown()

        # parsers
        for parser in self.detector_objs:
            if hasattr(parser, 'stop'):
                parser.stop()


        # close framework connections
        for frmk_conn in self.__frameworkConnecitonList:
            frmk_conn.close()
        # execution statistics        
        Stats.shutdown()
        if Stats.dates['startup']:
            Stats.stats()
        # Watchdog
        if self.watchdog:
            self.watchdog.shutdown()
        # kill program
        pid = os.getpid()

        # TODO:
        # This can be avoided by implementing safe shutdown notification
        # for all threads. The reason for needing this is the abuse of the
        # "while 1" loops scattered througout
        self.setShutDownRunning(False)
        os.kill(pid, signal.SIGKILL)


    def waitforever(self):
        '''
            Wait forever agent loop
        '''
        timer = 0

        while 1:
            time.sleep(1)
            timer += 1

            if timer > 30:
                Stats.log_stats()
                timer = 0


    def __getServerConn_byIP(self, ip):
        '''
            Read the interal serverconnection list and returns the server with the ip,
            passed as an argument
        '''
        for server in self.__outputServerConnecitonList:
            if server.get_server_ip() == ip:
                return server


    def __readOuptutServers(self):
        ''' Read the ouptput server list, if exists'''

        if self.conf.has_section("output-server"):
            if self.conf.getboolean("output-server", "enable"):
                primarySever = ServerData("primary", self.conf.get("output-server", "ip"), \
                                          self.conf.get("output-server", "port"), priority=0, \
                                          sendEvents=True, allow_frmk_data=False)
                if self.conf.has_section("control-framework"):
                    primarySever.set_frmk_data(socket.gethostname(), \
                                               self.conf.get("control-framework", "ip"), \
                                               self.conf.get("control-framework", "port"))
                self.__outputServerList.append(primarySever)
                Stats.add_server(primarySever.get_ip())
        #Regular expression to parse the readed line
        #data_reg_expr = "(?P<server_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<server_port>[0-9]{1,5});(?P<send_events>True|False|Yes|No);(?P<allow_frmk_data>True|False|Yes|No);(?P<server_priority>[0-5]);(?P<frmk_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<fmrk_port>[0-9]{1,5});(?P<frmk_id>((([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)([a-zA-Z])+)))"
        #data_reg_expr ="(?P<server_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<server_port>[0-9]{1,5});(?P<send_events>True|False|Yes|No);(?P<allow_frmk_data>True|False|Yes|No);(?P<server_priority>[0-5]);(?P<frmk_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<fmrk_port>[0-9]{1,5});(?P<frmk_id>(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|\b-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|\b-){0,61}[0-9A-Za-z])?)*\.?)"
        data_reg_expr ="(?P<server_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<server_port>[0-9]{1,5});(?P<send_events>True|False|Yes|No);(?P<allow_frmk_data>True|False|Yes|No);(?P<server_priority>[0-5]);(?P<frmk_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3}));(?P<frmk_port>[0-9]{1,5})"
        pattern = re.compile(data_reg_expr)
        if self.conf.has_section("output-server-list"):
            logger.debug("ouptut-server-list section founded! Reading it!")
            for hostname, data in self.conf.hitems("output-server-list").iteritems():
                value_groups = pattern.match(data)
                if value_groups is not None:
                    server_ip = value_groups.group('server_ip')
                    server_port = int(value_groups.group('server_port'))
                    server_send_events = value_groups.group('send_events')
                    allow_frmk_data = value_groups.group('allow_frmk_data')
                    server_priority = int(value_groups.group('server_priority'))
                    fmk_ip = value_groups.group('frmk_ip')
                    fmk_port = value_groups.group('frmk_port')
                    fmk_id =socket.gethostname() #value_groups.group('frmk_id')
                    logger.debug("Server -> IP: %s , PORT: %s , SEND_EVENTS: %s , ALLOW_FRMK_DATA: %s, PRIORITY:%s" % (server_ip, server_port, server_send_events, allow_frmk_data, server_priority))
                    tmpServer = ServerData(hostname, server_ip, server_port, server_priority, server_send_events, allow_frmk_data)
                    tmpServer.set_frmk_data(fmk_id,fmk_ip,fmk_port)
                    self.__outputServerList.append(tmpServer)
                    
                    Stats.add_server(server_ip)
                else:
                    logger.warning("Invalid server output (%s = %s),please check your configuration file" % (hostname, data))
        self.__outputServerList.sort(cmp=lambda x, y: cmp(x.get_priority(), y.get_priority()))
        for server in self.__outputServerList:
            logger.info("-------> %s" % server)
            #set stop counter for every server to 0
            self.__stop_server_counter_array[server.get_ip()] = 0
            

    def __changePriority(self):
        '''
            Change current server output priority
        '''
        Output.set_priority(self.__currentPriority)
        for frmk_conn in self.__frameworkConnecitonList:
            frmk_conn.close()
        self.connect_framework()


    def __check_servers_by_priority(self, prio):
        '''
            Check servers by priority
        '''

        aliveServers = 0
        for server_conn in self.__outputServerConnecitonList:
            if server_conn.get_priority () == prio:
                if server_conn.get_is_alive():
                    aliveServers = aliveServers + 1
                    self.__stop_server_counter_array[ server_conn.get_server_ip()] = 0
                    if not self.__output_dic.has_key(server_conn.get_server_ip()):
                        self.__output_dic[server_conn.get_server_ip()] = 1
                        logger.info("Adding new output: %s:%s" % (server_conn.get_server_ip(), server_conn.get_server_port()))
                        Output.add_server_output(server_conn, server_conn.get_priority(), server_conn.get_send_events())
                else:
                    #increases stop counter
                    self.__stop_server_counter_array[ server_conn.get_server_ip()] += 1
        return aliveServers


    def __check_server_status(self):
        '''
            Check if there is any server, with the max priority (temporal priority),  alive.
            If yes and the temporal priority  is greater than current priority, we've to change the priority, if no, we do nothing
        '''
        reconnect_try = False
        priority_changed = False
        #Default values
        timeBeetweenChecks = 60.0
        maxStopCounter = 5.0
        poolInterval = 30.0
        if self.conf.has_section("output-properties"):
            timeBeetweenChecks = float(self.conf.get("output-properties", "timeBeetweenChecks"))
            maxStopCounter = float(self.conf.get("output-properties", "maxStopCounter"))
            poolInterval = float(self.conf.get("output-properties", "poolInterval"))
        logger.info("Check status configuration: Time between checks: %s - max stop counter: %s pool interval: %s" % (timeBeetweenChecks, maxStopCounter, poolInterval))
        while self.__keep_working:
            reconnect_try = False
            priority_changed = False
            tmpPrio = 0
            aliveServers = 0
            while aliveServers == 0 and self.__keep_working:
                logger.info("Checking server with priority %d" % tmpPrio)
                aliveServers = self.__check_servers_by_priority(tmpPrio)
                if aliveServers == 0:
                    logger.info("No server with priority %d alive" % tmpPrio)
                if aliveServers > 0:
                    logger.info("There are %d servers with priority %d alive" % (aliveServers, tmpPrio))
                    self.connect_framework()
                if aliveServers == 0 and tmpPrio == 5:
                    tmpPrio = 0
                    #sleep 30 seconds before new pool
                    logger.warning("No available servers .... next pool in 30 seconds")
                    time.sleep(poolInterval)
                else:
                    tmpPrio = tmpPrio + 1
                for server_ip, stop_counter in self.__stop_server_counter_array.items():
                    logger.info("Server:%s - stopCounter:%s" % (server_ip, stop_counter))
                    if stop_counter == maxStopCounter:
                        serverconn = self.__getServerConn_byIP(server_ip)
                        logger.info("Server %s:%s has reached five stops, trying to reconnect!" % (serverconn.get_server_ip(), serverconn.get_server_port()))
                        serverconn.connect(attempts=3, waittime=10)
                        Stats.server_reconnect(serverconn.get_server_ip())
                        self.__stop_server_counter_array[server_ip] = 0
                        reconnect_try = True
                        time.sleep(2)
            self.__connect_to_server_end = True
            #Some server is alive...
            if (tmpPrio - 1) != self.__currentPriority:
                logger.warning("Current priority server has changed, current priority = %d", tmpPrio - 1)
                self.__currentPriority = tmpPrio - 1
                self.__changePriority()
                priority_changed = True
            #check stop counter, if a server reaches five stops, we retry to connect

            if self.__keep_working and not reconnect_try and not priority_changed:
                time.sleep(timeBeetweenChecks)


    def main(self):
        try:
            self.__readOuptutServers()
            self.check_pid()
            self.createDaemon()
            self.init_stats()
            self.init_logger()
            thread.start_new_thread(self.connect_server, ())
            self.__checkThread = threading.Thread(target=self.__check_server_status, args=())
            self.__checkThread.start()
            while not self.__connect_to_server_end:
                logger.info("Waiting to server connections available...")
                time.sleep(5)
            self.connect_framework()
            self.init_output()
            time.sleep(1)
            self.init_plugins()
            self.init_watchdog()
            self.waitforever()

        except KeyboardInterrupt:
            if self.getShutDownRunning() == False:
                logger.info("WARNING! Ctrl+C received! shutdowning")
                self.shutdown()
            else:
                logger.info("WARNING! Ctrl+C received! Shutdown signal ignored -- Another shutdown process running.")

        except AgentCritical, e:
            logger.critical(e)
            if self.getShutDownRunning() == False:
                self.shutdown()
                logger.info("WARNING! Exception captured, shutdowning!")
            else:
                logger.info("WARNING! Exception captured! Shutdown signal ignored -- Another shutdown process running")

        except Exception, e:
            logger.error("Unexpected exception: " + str(e))

            # print trace exception
            import traceback
            traceback.print_exc()

            # print to error.log too
            if self.conf.has_option("log", "error"):
                fd = open(self.conf.get("log", "error"), 'a+')
                traceback.print_exc(file=fd)
                fd.close()


if __name__ == "__main__":
    a = Agent()
    a.main()


# vim:ts=4 sts=4 tw=79 expandtab:
