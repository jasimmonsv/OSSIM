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

#
# LOCAL IMPORTS
#
from Config import Conf, Plugin, Aliases, CommandLineOptions
from ParserLog import ParserLog
from Watchdog import Watchdog
from Logger import Logger
from Output import Output
from Stats import Stats
from Conn import ServerConn, FrameworkConn
from ConnPro import ServerConnPro
from Exceptions import AgentCritical
from ParserUnifiedSnort import ParserUnifiedSnort
from ParserDatabase import ParserDatabase
from ParserWMI import ParserWMI
from ParserSDEE import ParserSDEE
from ParserRemote import ParserRemote

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

        self.conf.read([conffile])

        # aliases
        aliases = Aliases()
        aliases.read([os.path.join(os.path.dirname(conffile), "aliases.cfg")])
        local_aliases_fn = os.path.join(os.path.dirname(conffile), "aliases.local")
        #if aliases.local exists, after we've loaded aliases default file, 
        #we load aliases.local
        if os.path.isfile(local_aliases_fn):
            logger.info("Reading local aliases file: %s" % local_aliases_fn)
            aliases.read(local_aliases_fn)
        # list of plugins and total number of rules within them
        self.plugins = []
        self.nrules = 0

        for name, path in self.conf.hitems("plugins").iteritems():
            if os.path.exists(path):
                plugin = Plugin()

                # Now read the config file
                plugin.read(path)
                plugin.set("config", "name", name)
                plugin.replace_aliases(aliases)
                plugin.replace_config(self.conf)
                self.plugins.append(plugin)
                self.nrules += len(plugin.sections()) \
                               - plugin.sections().count('translation') \
                               - 1 # [config]

            else:
                logger.error("Unable to read plugin configuration (%s) at (%s)" % (name, path))

        # server connection (only available if output-server is enabled)
        self.conn = None
        
        self.conn_framework = None

        # pro server connection (only available if output-server-pro is enabled)
        self.conn_plugins = {}
        self.conn_plugin_set = []

        self.detector_objs = []
        self.watchdog = None
        self.shutdown_running = False;

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

        Stats.startup()

        if self.conf.has_section("log"):
            if self.conf.has_option("log", "stats"):
                Stats.set_file(self.conf.get("log", "stats"))


    def init_output(self):

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


    def connect_framework(self):
        
        if self.conf.has_section("control-framework"):
            if self.conf.getboolean("control-framework", "enable"):
                # connect the control agent
                self.conn_framework = FrameworkConn(self.conf)

                if self.conn_framework.connect(attempts=3, waittime=30):
                    logger.debug("Control framework connection is now enabled!")
                    self.conn_framework.control_messages()
    
                else:
                    self.conn_framework = None
                    logger.error("Control framework connection is now disabled!")


    def connect_server(self):

        if self.conf.has_section("output-server"):
            if self.conf.getboolean("output-server", "enable"):
                self.conn = ServerConn(self.conf, self.plugins)
                if self.conn.connect(attempts=0, waittime=30):
                    self.conn.control_messages()

                    # init server output
                    if self.conf.has_section("output-server"):
                        if self.conf.getboolean("output-server", "enable"):
                            if self.conn is not None:
                                Output.add_server_output(self.conn)

                else:
                    self.conn = None
                    logger.error("Server connection is now disabled!")


    def connect_server_pro(self, id):

        if self.conf.has_section("output-server-pro"):
            if self.conf.getboolean("output-server-pro", "enable"):
                if not id in self.conn_plugin_set:
                    SCPro = ServerConnPro(self.conf, id)
                    self.conn_plugin_set.append(id)

                    if SCPro.connect(attempts=0, waittime=30):
                        self.conn_plugins[id] = SCPro.get_conn()

                        # init server output
                        if self.conf.has_section("output-server-pro"):
                            if self.conf.getboolean("output-server-pro", "enable"):
                                if self.conn_plugins[id] is not None:
                                    Output.add_server_output_pro(self.conn_plugins[id])

                    else:
                        self.conn_plugins[id] = None
                        logger.error("Server connection for plugin %s is now disabled!" % id)


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


    def init_plugin_conns(self):

        for plugin in self.plugins:
            id = plugin.get("DEFAULT", "plugin_id")

            if id > 0:
                thread.start_new_thread(self.connect_server_pro, (id,))


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
        if self.conf.getboolean("watchdog", "enable"):
            self.watchdog = Watchdog(self.conf, self.plugins)
            self.watchdog.start()


    def terminate(self, sig, params):
        if self.getShutDownRunning() == False:
           logger.info("WARNING: Shutdown received! - Processing it ...!")
           self.shutdown()
        else:
           logger.info("WARNING: Shutdown received! - We can't process it because another shutdonw process is running!")


    def shutdown(self):
        #Disable Ctrl+C signal.
        signal.signal(signal.SIGINT, signal.SIG_IGN)
        logger.warning("Kill signal received, exiting...")
        self.setShutDownRunning(True)

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

        # parsers
        for parser in self.detector_objs:
            if hasattr(parser, 'stop'):
                parser.stop()

        # Watchdog
        if self.watchdog:
            self.watchdog.shutdown()

        # output plugins
        Output.shutdown()

        # execution statistics
        Stats.shutdown()
        if Stats.dates['startup']:
            Stats.stats()

        # kill program
        pid = os.getpid()

        # TODO:
        # This can be avoided by implementing safe shutdown notification
        # for all threads. The reason for needing this is the abuse of the
        # "while 1" loops scattered througout
        self.setShutDownRunning(False)
        os.kill(pid, signal.SIGKILL)


    # Wait for a Control-C and kill all threads
    def waitforever(self):
        timer = 0

        while 1:
            time.sleep(1)
            timer += 1

            if timer > 30:
                Stats.log_stats()
                timer = 0


    def main(self):
        try:
            self.check_pid()
            self.createDaemon()
            self.init_stats()
            self.init_logger()

            thread.start_new_thread(self.connect_server, ())

            self.connect_framework()

            self.init_plugin_conns()
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
