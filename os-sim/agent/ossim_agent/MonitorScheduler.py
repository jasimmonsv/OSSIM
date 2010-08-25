import threading, time

from Logger import Logger
logger = Logger.logger

from EventList import EventList

class MonitorScheduler(threading.Thread):

    def __init__(self):
        self.monitor_list = EventList()
        threading.Thread.__init__(self)

    def new_monitor(self, type, plugin, watch_rule):
        if type in ('socket', 'unix_socket'):
            from MonitorSocket import MonitorSocket
            monitor = MonitorSocket(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
        elif type == 'database':
            from MonitorDatabase import MonitorDatabase
            monitor = MonitorDatabase(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
        elif type == ('command'):
            from MonitorCommand import MonitorCommand
            monitor = MonitorCommand(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
        elif type == ('http'):
            from MonitorHTTP import MonitorHTTP
            monitor = MonitorHTTP(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
        elif type == ('session'):
            from MonitorSession import MonitorSession
            monitor = MonitorSession(plugin, watch_rule)
            self.monitor_list.appendRule(monitor)
#
#        TODO: still not implemented
#
#        elif type in ('log', 'file'):
#            from MonitorFile import MonitorFile
#            monitor = MonitorFile(plugin, watch_rule)
#            self.monitor_list.appendRule(monitor)
#
         
    def run(self):
        logger.debug("Monitor Scheduler started")

        while 1:
            remove_monitors = []

            for monitor in self.monitor_list:
                # get monitor from monitor list
                # process watch-rule and remove from list

                # TODO: check if monitor is a Monitor instance
                # if isinstance(monitor, Monitor)

                if monitor.process():
                    remove_monitors.append(monitor)

            for m in remove_monitors:
                self.monitor_list.removeRule(m)

            # don't overload agent
            time.sleep(2)


# vim:ts=4 sts=4 tw=79 expandtab:


