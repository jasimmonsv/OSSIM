import os, time

from Logger import Logger
logger = Logger.logger

class Stats:

    dates = { 'startup': '', 'shutdown': '' }
    events = { 'total': 0, 'detector': 0, 'monitor': 0 }
    consolidation = {'total': 0, 'consolidated': 0}
    watchdog = { 'total': 0 }
    server_reconnects = 0
    file = ''

    def set_file(file):
        Stats.file = file
    set_file = staticmethod(set_file)

    def startup():
        Stats.dates['startup'] = time.ctime(time.time())
    startup = staticmethod(startup)

    def shutdown():
        Stats.dates['shutdown'] = time.ctime(time.time())
    shutdown = staticmethod(shutdown)

    def new_event(event):

        if not Stats.events.has_key(event['plugin_id']):
            Stats.events[event['plugin_id']] = 0

        # total events
        Stats.events['total'] += 1

        # detector|monitor events
        for _type in [ 'detector', 'monitor' ]:
            if event['type'] == _type:
                Stats.events[_type] += 1

        # events by plugin_id
        Stats.events[event['plugin_id']] += 1
    new_event = staticmethod(new_event)

    def watchdog_restart(plugin):
        Stats.watchdog['total'] += 1
        if not Stats.watchdog.has_key(plugin):
            Stats.watchdog[plugin] = 0
        Stats.watchdog[plugin] += 1
    watchdog_restart = staticmethod(watchdog_restart)


    def server_reconnect():
        Stats.server_reconnects += 1
    server_reconnect = staticmethod(server_reconnect)

    def log_stats():
        logger.debug("Agent was started at: %s" % (Stats.dates['startup']))
        logger.info("Total events captured: %d" % (Stats.events['total']))
        if Stats.watchdog['total'] > 0:
            logger.warning("Apps restarted by watchdog: %d" % \
                (Stats.watchdog['total']))
        if Stats.server_reconnects > 0:
            logger.warning("Server reconnections attempts: %d" % \
                (Stats.server_reconnects))
    log_stats = staticmethod(log_stats)

    def __summary():
        summary  = "\n-------------------------\n"
        summary += " Agent execution summary:\n"

        # startup and shutdown dates
        summary += "  + Startup date: %s\n" % (Stats.dates['startup'])
        if Stats.dates['shutdown']:
            summary += "  + Shutdown date: %s\n" % (Stats.dates['shutdown'])

        # events
        summary += "  + Total events: %d" % (Stats.events['total'])
        summary += " (Detector: %d, Monitor: %d)\n" % \
            (Stats.events['detector'], Stats.events['monitor'])
        for plugin_id, n_events in Stats.events.iteritems():
            if not plugin_id:
                if n_events:
                    summary += "    - plugin_id unkown: %d\n" % (int(n_events))
            elif plugin_id.isdigit():
                summary += "    - plugin_id %s: %d\n" % (plugin_id, n_events)

        # consolidation
        summary += "  + Events consolidated: %d (%d processed)\n" % \
            (Stats.consolidation['consolidated'], Stats.consolidation['total'])

        # wathdog restarts
        summary += "  + Apps restarted by watchdog: %d\n" % \
            (Stats.watchdog['total'])
        for process, n_restarts in Stats.watchdog.iteritems():
            if process != 'total':
                summary += "    - process %s: %d\n" % (process, n_restarts)

        # server reconnets
        summary += "  + Server reconnection attempts: %d\n" %\
             (Stats.server_reconnects)
        summary += "-------------------------"

        logger.info(summary)
        return summary
    __summary = staticmethod(__summary)

    def stats():
        summary = Stats.__summary()
        if not Stats.file:
            logger.error("There is no [log]->stats entry at configuration")
            return

        dir = Stats.file.rstrip(os.path.basename(Stats.file))
        if not os.path.isdir(dir):
            try:
                os.makedirs(dir, 0755)
            except OSError, e:
                logger.error(
                    "Can not create stats directory (%s): %s" % (dir, e))
                return

        try:
            fd = open(Stats.file, 'a+')
        except IOError, e:
            logger.warning("Error opening stats file: " + str(e))
        else:
            fd.write(summary)
            fd.write ("\n\n");
            fd.flush()
            fd.close()
            logger.info("Agent statistics written in %s" % (Stats.file))

    stats = staticmethod(stats)


