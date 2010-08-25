from Monitor import Monitor

from Logger import Logger
logger = Logger.logger

import commands, string

class MonitorCommand(Monitor):

    # connect to monitor
    def open(self):
        pass


    # get data from monitor
    def get_data(self, rule_name):
        query = self.queries[rule_name]
        logger.debug("Sending query to monitor: %s" % (query))

	# TODO,FIXME: protect against command injection
        for char in query:
            if not (char in string.letters or \
                    char in string.digits or \
                    char in '/:. -'):
                query = query.replace(char, '')

        data = commands.getoutput(query)
        logger.debug("Received data from monitor: %s" % (data))
        return data


    # close monitor connection
    def close(self):
        pass

