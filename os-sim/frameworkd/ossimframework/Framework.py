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
import os, sys, time, signal
from optparse import OptionParser

#
# LOCAL IMPORTS
#
import Const

from Logger import Logger
logger = Logger.logger



class Framework:

    def __init__ (self):
        self.__classes = [
                "ControlPanelRRD",
                "AcidCache",
#                "OptimizeDB",
                "Listener",
                "Scheduler",
                "SOC",
                "BusinessProcesses",
                "EventStats",
                "Backup",
                "DoNagios",
#                "AlarmGroup",
#                "AlarmIncidentGeneration",
            ]


    def __parse_options(self):
        
        usage = "%prog [-d] [-v] [-s delay] [-c config_file]"
        parser = OptionParser(usage = usage)
        parser.add_option("-v", "--verbose", dest="verbose", action="count",
                          help="make lots of noise")
        parser.add_option("-L", "--log", dest="log", action="store",
                          help="user FILE for logging purposes", metavar="FILE")
        parser.add_option("-d", "--daemon", dest="daemon", action="store_true",
                          help="Run script in daemon mode")
        parser.add_option("-s", "--sleep", dest="sleep", action="store",
                          help = "delay between iterations (seconds)", metavar="DELAY")
        parser.add_option("-c", "--config", dest="config_file", action="store",
                          help="read config from FILE", metavar="FILE")
        parser.add_option("-p", "--port", dest="listener_port", action="store",
                          help="use PORT as listener port", metavar="PORT")
        parser.add_option("-l", "--listen-address", dest="listener_address", action="store",
                          help="use ADDRESS as listener IP address", metavar="ADDRESS")

        (options, args) = parser.parse_args()

        if options.verbose and options.daemon:
            parser.error("incompatible options -v -d")
        
        return options


    def __daemonize(self):

        try:
            Logger.remove_console_handler()

            print "OSSIM Framework: Forking into background..."
            pid = os.fork()
            if pid > 0: sys.exit(0)

        except OSError, e:
            print >>sys.stderr, "fork failed: %d (%s)" % (e.errno, e.strerror)
            sys.exit(1)


    def waitforever(self):
        """Wait for a Control-C and kill all threads"""

        while 1:
            try:
                time.sleep(1)
            except KeyboardInterrupt:
                pid = os.getpid()
                os.kill(pid, signal.SIGTERM)


    def startup (self) :
        options = self.__parse_options()

        # configuration file
        if options.config_file is not None:
            Const.CONFIG_FILE = options.config_file

        if options.sleep is not None:
            Const.SLEEP = float(options.sleep)
        
        if options.listener_port is not None:
            Const.LISTENER_PORT = options.listener_port

        if options.listener_address is not None:
            Const.LISTENER_ADDRESS = options.listener_address

        # log directory
        if not os.path.isdir(Const.LOG_DIR):
            os.makedirs(Const.LOG_DIR, 0755)

        # daemonize
        if options.daemon is not None:
            self.__daemonize()

            # Redirect error file descriptor
#            sys.stderr = open(os.path.join
#                (Const.LOG_DIR, 'frameworkd_error.log'), 'w')

            # Redirect standard file descriptors (daemon mode)
#            sys.stdin  = open('/dev/null', 'r')
#            sys.stdout = open(os.path.join(Const.LOG_DIR, 'frameworkd.log'), 'w')

        # logging (info logging only by default)
        verbose = "info"

        Logger.add_file_handler('%s/frameworkd.log' % Const.LOG_DIR)
        Logger.add_error_file_handler('%s/frameworkd_error.log' % Const.LOG_DIR)

        if options.verbose is not None:
            # -v or -vv command line argument
            #  -v -> self.options.verbose = 1
            # -vv -> self.options.verbose = 2
            for i in range(options.verbose):
                verbose = Logger.next_verbose_level(verbose)
     
            Logger.set_verbose(verbose)


    def main(self):

        logger.info("Frameworkd is starting up...")

        from OssimConf import OssimConf
        conf = OssimConf (Const.CONFIG_FILE)

        for c in self.__classes :
            conf_entry = "frameworkd_" + c.lower()

            if str(conf[conf_entry]).lower() in ('1', 'yes', 'true'):
                logger.info(c.upper() + " is enabled")

                exec "from %s import %s" % (c, c)
                exec "t = %s()" % (c)
                t.start()

            else:
                logger.info(c.upper() + " is disabled")



if __name__ == "__main__" :
   
    f = Framework()
    f.startup()
    f.main()
    f.waitforever()

# vim:ts=4 sts=4 tw=79 expandtab:
