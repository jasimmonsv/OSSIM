from Monitor import Monitor

from Logger import Logger
logger = Logger.logger

from Database import DatabaseConn

class MonitorDatabase(Monitor):

    def open(self):

        location = self.plugin.get("config", "location")
        (db_type, host, db_name, user, password) = location.split(':')

        self.conn = DatabaseConn()
        self.conn.connect(db_type, host, db_name, user, password)

    # get data from monitor
    def get_data(self, rule_name):
        
        if self.conn is None:
            return None

        data = ''
        query = self.queries[rule_name]

        logger.debug("Sending query to monitor: %s" % (query))
        result = self.conn.exec_query(query)
        logger.debug("Received data from monitor: %s" % (str(result)))
        return result

    # close monitor connection
    def close(self):
        self.conn.close()

