import Const, re, datetime, Util
from OssimDB import OssimDB
from OssimConf import OssimConf
from time import strftime

class Vulnerabilities :

    def __init__ (self) :
        self.__set_debug = True
        self.__conf = OssimConf(Const.CONFIG_FILE)
        self.__conn = OssimDB()
        self.__conn.connect( self.__conf['ossim_host'],
                             self.__conf['ossim_base'],
                             self.__conf['ossim_user'],
                             self.__conf['ossim_pass'])
        self.__id_pending = 65001 
        self.__id_false_positive = 65002
        self.__default_incident_type = 'Nessus Vulnerability'
        self.__nsr_fd = None
        self.__scanner_type = None
        self.__scan_time = None
        self.__ticket_default_user = "admin"
        self.__ticket_default_closed_description = "Automatic closed of the incident"
        self.__ticket_default_open_description = "Automatic open of the incident"

    def process(self, nsr_file, scan_date, scan_networks, scan_hosts) :
        self.__debug("Generating Incidents for found vulnerabilities")
        self.__scan_time = strftime('%Y-%m-%d %H:%M:%S')
        self.__scanner_type = self.__scanner_type or \
                            self.__conf["scanner_type"] or \
                            "openvas2"
        try:
            self.__nsr_fd = open(nsr_file)
        except Exception, e:
            self.__debug("Unable to open file %s: %s" % (nsr_file,e))
            return
        self.__parse_vulns_file()
        self.__debug("Automatic close of vulnerabilities")
        self.__traverse_vulns_incidents(scan_date, scan_networks, scan_hosts)
        self.__debug("Generating Incidents finished ok")

    def __parse_vulns_file(self):
        if self.__scanner_type == "openvas2":
            pattern = re.compile("results\|[\d+\.]+\|([\d+\.]+)\|.*\((\d+/tcp)\)\|[\d+\.]+\.(\d+)\|(Security Hole|Security Note|Security Warning)\|(.*)")
        else:
            pattern = re.compile("(.*)\|.*\((\d+/tcp)\)\|(\d+)\|(INFO|NOTE|REPORT)\|(.*)")
        for line in self.__nsr_fd.readlines():
            result = pattern.search(line)
            if result:
                # Reset
                (ip, port,nessusid,type,text) = result.groups()
                risk = self.__calc_risk(text, type)
                # If we've got a CVSS entry, use that one
                cvss = self.__calc_cvss(text, type)
                if cvss > risk:
                    risk = cvss
                tmp_risk_value = 0
                if risk == "NOTE":
                    tmp_risk_value = 0
                elif risk == "Security Note":
                    tmp_risk_value = 1
                elif risk == "INFO":
                    tmp_risk_value = 1
                elif risk == "LOW":
                    tmp_risk_value = 3
                elif risk == "Security Warning":
                    tmp_risk_value = 3
                elif risk == "MEDIUM":
                    tmp_risk_value = 5
                elif risk == "HIGH":
                    tmp_risk_value = 8
                elif risk == "Security Hole":
                    tmp_risk_value = 8
                elif risk == "REPORT":
                    tmp_risk_value = 10
                if int(tmp_risk_value) < int(self.__conf['vulnerability_incident_threshold']):
                    continue
                self.__debug("Vulnerability found: %s %s %s" % (ip,port,nessusid))
                #Check if exists a vulnerability already create
                query_incident = "SELECT incident_id FROM incident_vulns WHERE ip = '%s' AND port = '%s' AND nessus_id = '%s'" % (ip,port,nessusid)
                hash_incident = self.__conn.exec_query(query_incident)
                if hash_incident != []:
                    #Update field last_update with today value
                    query = "UPDATE incident SET last_update = '%s' WHERE id = '%s'" % (self.__scan_time,hash_incident[0]["incident_id"])
                    self.__conn.exec_query(query)
                    #Check if the incident is closed
                    query = "SELECT * FROM incident WHERE status='Closed' and id = '%s'" % hash_incident[0]["incident_id"]
                    hash_close_incident = self.__conn.exec_query(query)
                    if hash_close_incident != []:
                        #Check if has the tag  false positive
                        query = "SELECT * FROM incident_tag WHERE incident_tag.incident_id = '%s' AND incident_tag.tag_id = %s" % (hash_incident[0]["incident_id"],self.__id_false_positive)
                        hash_false_incident = self.__conn.exec_query(query)
                        if hash_false_incident == []:
                            #change the state of the incident to Open and add ticket
                            query = "UPDATE incident SET status = 'Open' WHERE id = '%s'" % hash_incident[0]["incident_id"]
                            self.__conn.exec_query(query)
                            ticket_id = self.__genID("incident_ticket_seq")
                            ticket_query = "INSERT INTO incident_ticket (id,incident_id, date, status, priority, users, description) values ('%s','%s','%s','%s','%s','%s','%s')" % (ticket_id, hash_incident[0]["incident_id"], self.__scan_time, 'Open', hash_close_incident[0]["priority"], self.__ticket_default_user, self.__ticket_default_open_description)
                            self.__conn.exec_query(ticket_query)
                else:
                   #Generate a new incident for the vulnerability
                   self.__debug("New Vulnerability")
                   #Get then name of the vulnerability.
                   query = "SELECT name,reliability,priority FROM plugin_sid where plugin_id = 3001 and sid = '%s'" % nessusid
                   hash_plugin = self.__conn.exec_query(query)
                   if hash_plugin != []:
                       vul_name = hash_plugin[0]["name"]
                   else:
                       vul_name = "Vulnerability - Unknown detail"
                   priority = self.__calc_priority(risk, ip, nessusid)
                   query = "INSERT INTO incident(title, date, ref, type_id, priority, status, last_update, in_charge, submitter, event_start, event_end) VALUES('%s', '%s', 'Vulnerability', '%s', '%s', 'Open', '%s', 'admin', 'nessus', '0000-00-00 00:00:00', '0000-00-00 00:00:00')" % (vul_name,self.__scan_time,self.__default_incident_type, priority, self.__scan_time)
                   self.__conn.exec_query(query)
                   # TODO: change this for a sequence
                   query = "SELECT MAX(id) id from incident"
                   hash5 = self.__conn.exec_query(query)
                   incident_id = hash5[0]['id'];
                   #sanity check
                   text = text.replace("\"","'")
                   incident_vulns_id = self.__genID("incident_vulns_seq")
                   query = "INSERT INTO incident_vulns(id, incident_id, ip, port, nessus_id, risk, description) VALUES('%s', '%s', '%s', '%s', '%s', '%s', \"%s\")" % (incident_vulns_id, incident_id, ip, port, nessusid, risk, text)
                   self.__conn.exec_query(query)
                   query = "INSERT INTO incident_tag(tag_id, incident_id) VALUES(%s, '%s')" % (self.__id_pending,incident_id)
                   self.__conn.exec_query(query)


    def __traverse_vulns_incidents(self, scan_date, scan_networks, scan_hosts):
        #returns the incidents of class Vulnerability that are 'Open'
        query = "SELECT i.id, i.last_update, i.priority, v.ip, v.nessus_id, v.risk FROM incident i, incident_vulns v WHERE i.id=v.incident_id and i.ref='Vulnerability' and i.status = 'Open'"
        hash = self.__conn.exec_query(query)
        for row in hash:
            if ( self.isIpInIpList(row["ip"],scan_hosts) or Util.isIpInNet(row["ip"],scan_networks) ):
                last_update = str(row["last_update"])
                if last_update != self.__scan_time:
                    #the vulnerability doesn't appear in the last scan
                    self.__debug("Vulnerability closed. Incident Id: %s  IP: %s" % (row["id"],row["ip"]) )
                    query = "UPDATE incident SET status = 'Closed' WHERE id = '%s'" % row["id"]
                    self.__conn.exec_query(query)
                    ticket_id = self.__genID("incident_ticket_seq")
                    ticket_query = "INSERT INTO incident_ticket (id,incident_id, date, status, priority, users, description) values ('%s','%s','%s','%s','%s','%s','%s')" % (ticket_id, row["id"], self.__scan_time, 'Closed', row["priority"], self.__ticket_default_user, self.__ticket_default_closed_description)
                    self.__conn.exec_query(ticket_query)
            else:
                #calc the priority
                priority = self.__calc_priority(row["risk"], row["ip"], row["nessus_id"])
                if priority < self.__conf['vulnerability_incident_threshold']:
                    continue
                query = "UPDATE incident SET priority = '%s' WHERE id = '%s'" % (priority, row["id"])
                self.__conn.exec_query(query)


    def isIpInIpList(self, host, host_list):
        for h in host_list:
            if (h == host):
                return True
        return False


    def __calc_risk(self, text, type):
        # This regexp isn't complete, but due to the randomnnes of the input and most of them having a CVSS score it should be enough
        pattern2 = re.compile("Risk [Ff]actor\s*[\\n]*:\s*[\\n]*(\w+)[;|\s|\\n]*")
        result2 = pattern2.search(text)
        if result2:
            (risk,) = result2.groups()
            risk = risk.upper()
        else:
            risk = type
        return risk

    def __calc_cvss(self, text, type):
        # Sometime we will be able to catch more detail here within
        pattern2 = re.compile("CVSS Base Score\s*:\s*(\d+)\s*;")
        result2 = pattern2.search(text)
        if result2:
            (risk,) = result2.groups()
            risk = int(risk)
            if risk < 1:
                risk = "NOTE"
            elif risk >= 1 and risk < 2:
                risk = "INFO"
            elif risk >= 2 and risk <= 3:
                risk = "LOW"
            elif risk > 3 and risk < 6:
                risk = "MEDIUM"
            elif risk >= 6 and risk <= 8:
                risk = "HIGH"
            elif risk > 8 and risk <= 10:
                risk = "REPORT"
            else:
                risk = "NOTE"
        else:
            risk = type
        return risk

    def __calc_priority(self, risk, hostip, nessusid) :
        # If it's not set, set it to 1
        risk_value = 1
        if risk == "NOTE":
            risk_value = 0
        elif risk == "INFO":
            risk_value = 1
        elif risk == "Security Note":
            risk_value = 1
        elif risk == "LOW":
            risk_value = 3
        elif risk == "Security Warning":
            risk_value = 3
        elif risk == "MEDIUM":
            risk_value = 5
        elif risk == "HIGH":
            risk_value = 8
        elif risk == "Security Hole":
            risk_value = 8
        elif risk == "REPORT":
            risk_value = 10

        query = "SELECT asset from host where ip='%s'" % hostip
        hash_asset = self.__conn.exec_query(query)
        if hash_asset != []:
            asset = hash_asset[0]["asset"]
        else:
            asset = 0
            
        query = "SELECT reliability from plugin_sid where sid='%s'" % nessusid
        hash_reliability = self.__conn.exec_query(query)
        if hash_reliability != []:
            reliability = hash_reliability[0]["reliability"]
        else:
            reliability = 0

        # FIXME: check this formula once the values are clear. This is most definetivley wrong.
        priority = int( (risk_value + asset + reliability) // 1.9 )
        return priority


    def __debug (self, message) :
        if self.__set_debug == True :
            print message


    #mover a ossimDB
    def __genID(self, sequence):
        query = "UPDATE %s SET id=LAST_INSERT_ID(id+1);" % sequence
        self.__conn.exec_query(query)
        last_id_query = "select LAST_INSERT_ID() as id;"
        hash_last_id = self.__conn.exec_query(last_id_query)
        return hash_last_id[0]["id"]


if __name__ == "__main__":
    vulnerabilities = Vulnerabilities()
    # This won't close non-present vulnerabilities, limited testing functionality.
    vulnerabilities.process("result.nsr", "0000-00-00 00:00:00", "192.168.1.0/24", "192.168.1.123")
