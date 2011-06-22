import sys
import OssimConf
import Const
import time
import os
from Logger import Logger
try:
    from adodb import adodb
except ImportError:
    try:
        import adodb
    except ImportError:
        print "You need python adodb module installed"
        sys.exit()


import _mysql_exceptions
#from _mysql_exceptions import OperationalError

logger = Logger.logger


class OssimDB:

    def __init__ (self) :
        self.conn = None
        self.conf = OssimConf.OssimMiniConf()

    def connect (self, host, db, user, passwd = ""):
        self.conn = adodb.NewADOConnection(self.conf["ossim_type"])
        if passwd is None:
            passwd = ""
        try:
            self.conn.Connect(host, user, passwd, db)
        except Exception, e:
            print __name__, ": Can't connect to database (%s@%s)" % (user, host)
            print e
            sys.exit()
        self._host = host
        self._db = db
        self._user = user
        self._password = passwd

    # execute query and return the result in a hash
    def exec_query (self, query) :

        arr = []
        retries = 0
        reconnect  = 0
        while 1: 
            try: 
             if reconnect == 1:
                #retries = retries  + 1 	
                logger.warning ("Reconnecting to %s,database %s in 10 seconds" % (self._host, self._db))
                time.sleep(10)
                self.conn.Connect (self._host,self._user,self._password,self._db)
                reconnect = 0
             logger.debug ("Query " + query)
             cursor = self.conn.Execute(query)
             break
            except _mysql_exceptions.OperationalError, e:
              print __name__, \
                ': Error executing query (%s) """%s"""' % (query, e)
              reconnect = 1 

            except Exception,e:
              print __name__, \
                ': Error executing query (%s) """%s"""' % (query, e)
              return[]
       # if retries == 10:
       #  print __name__, \
       #        ": Can't reconected to database after %d retries. Exiting framework" % retries
       #  sys.exit (-1)
			
        while not cursor.EOF:
            arr.append(cursor.GetRowAssoc(0))
            cursor.MoveNext()
        self.conn.CommitTrans()
        cursor.Close()
        return arr

    def close (self):
        self.conn.Close()


if __name__ == "__main__" :
    db = OssimDB()
    db.connect(host="localhost", db="ossim", user="root", passwd="temporal")
    hash = db.exec_query("SELECT * FROM config")
    for row in hash: print row
    db.close()

# vim:ts=4 sts=4 tw=79 expandtab:
