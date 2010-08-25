import sys, re
import Const

class OssimMiniConf :

    def __init__ (self, config_file=Const.CONFIG_FILE) :
        self._conf = {}
        # get config only from ossim.conf file
        # (for example, when you only need database access)
        self._get_conf(config_file)

    def __setitem__ (self, key, item) :
        self._conf[key] = item

    def __getitem__ (self, key) :
        return self._conf.get(key, None)

    def __repr__ (self):
        repr = ""
        for key, item in self._conf.iteritems():
            repr += "%s\t: %s\n" % (key, item)
        return repr


    def _get_conf (self, config_file) :

        # Read config from file
        #
        try:
            config = open(config_file)
        except IOError, e:
            print "Error opening OSSIM configuration file (%s)" % e
            sys.exit()
       
        pattern = re.compile("^(\S+)\s*=\s*(\S+)")

        for line in config:
            result = pattern.match(line)
            if result is not None:
                (key, item) = result.groups()
                self[key] = item
       
        config.close()


class OssimConf (OssimMiniConf) :

    def __init__(self, config_file=Const.CONFIG_FILE):
        OssimMiniConf.__init__(self, config_file)
        # complete config info from OssimDB
        self._get_db_conf()

    def _get_db_conf(self):

        # Now, complete config info from Ossim database
        #
        from OssimDB import OssimDB
        db = OssimDB()
        db.connect(self["ossim_host"], 
                   self["ossim_base"], 
                   self["ossim_user"],
                   self["ossim_pass"])
        hash = db.exec_query("SELECT * FROM config")
        for row in hash:
            # values declared at config file override the database ones
            if row["conf"] not in self._conf:
                self[row["conf"]] = row["value"]
        db.close()


if __name__ == "__main__":
    c = OssimConf(Const.CONFIG_FILE)
    print c


# vim:ts=4 sts=4 tw=79 expandtab:
