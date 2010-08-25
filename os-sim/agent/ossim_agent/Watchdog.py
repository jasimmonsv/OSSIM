from Config import Conf, Plugin
from Output import Output
from Logger import *
from Task import Task
from Stats import Stats
logger = Logger.logger
import time
import datetime
import threading, string, time, os, commands, datetime

class Watchdog(threading.Thread):

    # plugin states
    PLUGIN_START_MSG            = 'plugin-process-started'
    PLUGIN_STOP_MSG             = 'plugin-process-stopped'
    PLUGIN_UNKNOWN_MSG          = 'plugin-process-unknown'
    PLUGIN_ENABLE_MSG           = 'plugin-enabled'
    PLUGIN_DISABLE_MSG          = 'plugin-disabled'

    PLUGIN_START_STATE_MSG      = PLUGIN_START_MSG   + " plugin_id=\"%s\"\n"
    PLUGIN_STOP_STATE_MSG       = PLUGIN_STOP_MSG    + " plugin_id=\"%s\"\n"
    PLUGIN_UNKNOWN_STATE_MSG    = PLUGIN_UNKNOWN_MSG + " plugin_id=\"%s\"\n"
    PLUGIN_ENABLE_STATE_MSG     = PLUGIN_ENABLE_MSG  + " plugin_id=\"%s\"\n"
    PLUGIN_DISABLE_STATE_MSG    = PLUGIN_DISABLE_MSG + " plugin_id=\"%s\"\n"

    # plugin server requests
    PLUGIN_START_REQ            = 'sensor-plugin-start'
    PLUGIN_STOP_REQ             = 'sensor-plugin-stop'
    PLUGIN_ENABLE_REQ           = 'sensor-plugin-enable'
    PLUGIN_DISABLE_REQ          = 'sensor-plugin-disable'

    # sensor info
    AGENT_DATE                  = "agent-date agent_date=\"%s\" tzone=\"%s\"\n"

    def __init__(self, conf, plugins):

        self.conf = conf
        self.plugins = plugins
        self.interval = self.conf.getfloat("watchdog", "interval") or 3600.0
        threading.Thread.__init__(self)


    # find the process ID of a running program
    def pidof(program):

        pid = string.split(commands.getoutput('pidof %s' % program), ' ')
        if pid[0] == '':
            return None
        else:
            return pid[0]
    pidof = staticmethod(pidof)

    def start_process(plugin, notify=True):

        id      = plugin.get("config", "plugin_id")
        process = plugin.get("config", "process")
        name    = plugin.get("config", "name")
        command = plugin.get("config", "startup")

        # start service
        if command:
            logger.info("Starting service %s (%s): %s.." % (id, name, command))
            task = Task(command)
            task.Run(1,0)
            timeout = 300
            start = datetime.datetime.now()
            plugin.start_time = float(time.time())
            while not task.Done():
                time.sleep(0.1)
                now = datetime.datetime.now()
                if (now - start).seconds> timeout:
                    task.Kill()
                    logger.warning("Could not start %s, returning after %s second(s) wait time." % (command, timeout))

        # notify result to server
        if notify:
            if not process:
                logger.debug("plugin (%s) has an unknown state" % (name))
                Output.plugin_state(Watchdog.PLUGIN_UNKNOWN_STATE_MSG % (id))
            elif Watchdog.pidof(process) is not None:
                logger.info(WATCHDOG_PROCESS_STARTED % (process, id))
                Output.plugin_state(Watchdog.PLUGIN_START_STATE_MSG % (id))
            else:
                logger.warning(WATCHDOG_ERROR_STARTING_PROCESS % (process, id))
    start_process = staticmethod(start_process)

    def stop_process(plugin, notify=True):

        id      = plugin.get("config", "plugin_id")
        process = plugin.get("config", "process")
        name    = plugin.get("config", "name")
        command = plugin.get("config", "shutdown")

        # stop service
        if command:
            logger.info("Stopping service %s (%s): %s.." % (id, name, command))
            logger.debug(commands.getstatusoutput(command))

        # notify result to server
        if notify:
            time.sleep(1)
            if not process:
                logger.debug("plugin (%s) has an unknown state" % (name))
                Output.plugin_state(Watchdog.PLUGIN_UNKNOWN_STATE_MSG % (id))
            elif Watchdog.pidof(process) is None:
                logger.info(WATCHDOG_PROCESS_STOPPED % (process, id))
                Output.plugin_state(Watchdog.PLUGIN_STOP_STATE_MSG % (id))
            else:
                logger.warning(WATCHDOG_ERROR_STOPPING_PROCESS % (process, id))
    stop_process = staticmethod(stop_process)


    def enable_process(plugin, notify=True):

        id      = plugin.get("config", "plugin_id")
        name    = plugin.get("config", "name")

        # enable plugin
        plugin.set("config", "enable", "yes")

        # notify to server
        if notify:
            logger.info("plugin (%s) is now enabled" % (name))
            Output.plugin_state(Watchdog.PLUGIN_ENABLE_STATE_MSG % (id))
    enable_process = staticmethod(enable_process)

    def disable_process(plugin, notify=True):

        id      = plugin.get("config", "plugin_id")
        name    = plugin.get("config", "name")

        # disable plugin
        plugin.set("config", "enable", "no")

        # notify to server
        if notify:
            logger.info("plugin (%s) is now disabled" % (name))
            Output.plugin_state(Watchdog.PLUGIN_DISABLE_STATE_MSG % (id))
    disable_process = staticmethod(disable_process)


    # restart services every interval
    def _restart_services(self, plugin):

        name = plugin.get("config", "name")

        if not plugin.has_option("config", "restart") or \
           not plugin.has_option("config", "enable"):
            return
            
        if plugin.getboolean("config", "restart") and \
           plugin.getboolean("config", "enable"):

            current_time = time.time()
            if plugin.has_option("config", "restart_interval"):
                restart_interval = plugin.getint("config", "restart_interval")
            else:
                restart_interval = 3600

            if not hasattr(plugin, 'start_time'):
                # The plugin was started before agent startup
                plugin.start_time = float(time.time())
            else:
                if plugin.start_time + restart_interval < current_time:
                    logger.debug("Plugin %s must be restarted" % (name))
                    self.stop_process(plugin)
                    self.start_process(plugin)


    def run(self):

        first_run = True

        while 1:
	   
	    t = datetime.datetime.now()
	    tzone = str(self.conf.get("plugin-defaults", "tzone"))
	    Output.plugin_state(self.AGENT_DATE % (str(time.mktime(t.timetuple())),tzone))
	    #logger.info(self.AGENT_DATE % (str(time.mktime(t.timetuple())),tzone))
            for plugin in self.plugins:

                id      = plugin.get("config", "plugin_id")
                process = plugin.get("config", "process")
                name    = plugin.get("config", "name")

                logger.debug("Checking process %s for plugin %s." \
                    % (process, name))

                # 1) unknown process to monitoring
                if not process:
                    logger.debug("plugin (%s) has an unknown state" % (name))
                    Output.plugin_state(self.PLUGIN_UNKNOWN_STATE_MSG % (id))

                # 2) process is running
                elif self.pidof(process) is not None:
                    logger.debug("plugin (%s) is running" % (name))
                    Output.plugin_state(self.PLUGIN_START_STATE_MSG % (id))

                    # check for for plugin restart
                    self._restart_services(plugin)


                # 3) process is not running
                else:
                    logger.debug("plugin (%s) is not running" % (name))
                    Output.plugin_state(self.PLUGIN_STOP_STATE_MSG % (id))

                    # restart services (if start=yes in plugin 
                    # configuration and plugin is enabled)
                    if plugin.getboolean("config", "start") and \
                       plugin.getboolean("config", "enable"):
                        self.start_process(plugin)
                        if self.pidof(process) is not None and not first_run:
                            Stats.watchdog_restart(process)

                # send plugin enable/disable state
                if plugin.getboolean("config", "enable"):
                    logger.debug("plugin (%s) is enabled" % (name))
                    Output.plugin_state(self.PLUGIN_ENABLE_STATE_MSG % (id))
                else:
                    logger.debug("plugin (%s) is disabled" % (name))
                    Output.plugin_state(self.PLUGIN_DISABLE_STATE_MSG % (id))


            time.sleep(float(self.interval))
            first_run = False


    def shutdown(self):

        for plugin in self.plugins:

            # stop service (if stop=yes in plugin configuration)
            if plugin.getboolean("config", "stop"):
                self.stop_process(plugin=plugin, notify=False)


# vim:ts=4 sts=4 tw=79 expandtab:

