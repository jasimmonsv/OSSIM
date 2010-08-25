from Event import Event
from Config import Conf, CommandLineOptions
from Exceptions import AgentCritical
from Logger import Logger
logger = Logger.logger

import string, os, sys, re

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
    def event(self, e): pass
    def shutdown(self): pass
    def plugin_state(self, msg): pass


class OutputPlain(OutputPlugins):

    def __init__(self, conf):
        self.conf = conf
        logger.info("Added Plain output")
        logger.debug("OutputPlain options: %s" %\
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

    def __init__(self, conn):
        logger.info("Added Server output")
        self.conn = conn
        self.activated = True
        self.send_events = False
        self.conf = Conf()
        self.options = CommandLineOptions().get_options()
        if self.options.config_file:
            conffile = self.options.config_file
        else:
            conffile = self.conf.DEFAULT_CONFIG_FILE
        self.conf.read([conffile])
        if self.conf.has_section("output-server"):
            if self.conf.getboolean("output-server", "send_events"):
                self.send_events = True


    def event(self, e):
        if self.activated and self.send_events:
            try:
                self.conn.send(str(e))
            except:
                return


    def plugin_state(self, msg):
        if self.activated:
            try:
                self.conn.send(msg)
            except:
                return


    def shutdown(self):
        self.conn.close()
        self.activated = False

class OutputServerPro(OutputPlugins):

    def __init__(self, conn):
        logger.info("Added Pro Server output")
        self.conn = conn
        self.activated = True

    def match_event(self, e):
        return True
        regexp = ".*plugin_id=\"" + self.conn.get_id() + "\".*"
        if re.match(regexp, str(e)) is not None:
                return True
        else:
                return False

    def event(self, e):
        if self.match_event(e) and self.activated:
            try:
                self.conn.send(str(e))
            except:
                return

    def shutdown(self):
        self.activated = False


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

    def event(self, e):

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

    def event(self, e):

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


# different ways to log ossim events (Event objects)
class Output:

    _outputs = []
    plain_output = server_output = server_output_pro = csv_output = db_output = False

    def add_plain_output(conf):
        if Output.plain_output is False:
            Output._outputs.append(OutputPlain(conf))
            Output.plain_output = True
    add_plain_output = staticmethod(add_plain_output)

    def add_server_output(conn):
        if Output.server_output is False:
            Output._outputs.append(OutputServer(conn))
            Output.server_output = True
    add_server_output = staticmethod(add_server_output)

    def add_server_output_pro(conn):
        Output._outputs.append(OutputServerPro(conn))
        Output.server_output_pro = True
    add_server_output_pro = staticmethod(add_server_output_pro)

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
        logger.info(str(e).rstrip())
        for output in Output._outputs:
            output.event(e)
    event = staticmethod(event)

    def plugin_state(msg):
        logger.info(str(msg).rstrip())
        for output in Output._outputs:
            output.plugin_state(msg)
    plugin_state = staticmethod(plugin_state)

    def shutdown():
        for output in Output._outputs:
            output.shutdown()
    shutdown =  staticmethod(shutdown)


if __name__ == "__main__":

    event = Event()
    Output.add_server_output()
    Output.event(event)
    Output.add_csv_output()
    Output.event(event)


# vim:ts=4 sts=4 tw=79 expandtab:
