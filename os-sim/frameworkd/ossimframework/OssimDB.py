import sys
import OssimConf
import Const

try:
    from adodb import adodb
except ImportError:
    try:
        import adodb
    except ImportError:
        print "You need python adodb module installed"
        sys.exit()

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

    # execute query and return the result in a hash
    def exec_query (self, query) :

        arr = []
        try:
            cursor = self.conn.Execute(query)
        except Exception, e:
            print __name__, \
                ': Error executing query (%s) """%s"""' % (query, e)
            return []
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
