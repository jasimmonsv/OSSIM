#!/usr/bin/python
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2010 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

#
# GLOBAL IMPORTS
#
import os, sys, time, re, datetime, stat, tarfile, threading, pwd, tempfile
from sets import Set
from optparse import OptionParser
from stat import *

#
# LOCAL IMPORTS
#
import Const
import Framework
from Logger import Logger
from OssimConf import OssimConf
from OssimDB import OssimDB
import Util
from Vulnerabilities import Vulnerabilities

#
# GLOBAL VARIABLES
#
logger = Logger.logger



class NessusManager:
    def __init__(self):
        logger.debug("Initialising Nessus Manager.")
        self.__nessus = DoNessus()


    def process(self, message):
        logger.debug("Nessus Manager: Processing: %s" % message)
        
        response = ""
        action = Util.get_var("action=\"([a-z]+)\"", message)
            
        if action == "report":
            result = re.findall("title=\"([a-zA-Z+]+)\" list=\"([0-9\.,]*)\"", message)

            if result != []:
                (title, list) = result[0]
                sensor_list = list.split(",")
                logger.info("Report host list: %s" % sensor_list)
                self.__nessus.generate_report(title, sensor_list)

        if action == "scan":
            sensor_list = []
            hosts_list = []
            hostgroups_list = []
            nets_list = []
            netgroups_list = []
                
            result = re.findall("target_type=\"([a-z]+)\"" , message)

            if result != []:
                target_type = result[0]

                # need to be modifified to support schedule for host, hostgropup, etc..
                if target_type == "schedule":
                    result = re.findall("id=\"([0-9]*)\"", message)

                    if result != []:
                        id = result[0]
                        logger.info("Got schedule request.")
                        self.__nessus.load_shedule(id)

                elif target_type == "sensors":
                    result = re.findall("list=\"([0-9\.,]*)\"", message)

                    if result != []:
                        list = result[0]

                        if not list == "":
                            sensor_list = list.split(",")

                    logger.info("Sensor_list: %s" % sensor_list)
                    self.__nessus.set_scan_type("sensor")
                    self.__nessus.load_sensors(sensor_list)

                elif target_type == "hosts":
                    result = re.findall("netgroups=\"([0-9a-zA-Z\._,]*)\" nets=\"([0-9a-zA-Z\._,]*)\" hostgroups=\"([0-9a-zA-Z\._,]*)\" hosts=\"([0-9\.,]*)\"", message)

                    if result != []:
                        (netgroups, nets, hostgroups, hosts) = result[0]
                        nets_list = self.__nessus.get_nets(netgroups,nets)

                        if not hostgroups == "":
                            hostgroups_list = hostgroups.split(",")

                        if not hosts == "":
                            hosts_list = hosts.split(",")

                    logger.info("Net_list: %s" % nets_list)
                    logger.info("Host_list: %s" % hosts_list)
                    logger.info("Hostgroup_list: %s" % hostgroups_list)
                    self.__nessus.set_scan_type("hosts")
                    self.__nessus.load_hosts(hostgroups_list, nets_list, hosts_list)

                if self.__nessus.status() == 0:
                    self.__nessus.run()
                    response = "ok"

                elif self.__nessus.status() > 0 :
                    logger.info("scan already started, status: %i" % self.__nessus.status())
                    response = "Scan already started, status: " + str(self.__nessus.status()) + "%"
            
        if action == "status":
            print __name__, ": status:", self.__nessus.status()
            response = str(self.__nessus.status())

        if action == "reset" and self.__nessus.status() == -1:
            print __name__, ": Resetting status"
            response = "Resetting status"
            self.__nessus.reset_status()

            if self.__nessus.status() == -1:
                print __name__, ": Previous scan aborted raising errors, please check your logfile. Error: " + \
                self.__nessus.get_error()
                response = "Previous scan aborted raising errors, please check your logfile. Error: " + str(self.__nessus.get_error())

        if action == "archive":
            result = re.findall("report=\"([a-z0-9.]+)\"", message)

            if result != []:
                report = result[0]
                logger.info("Got archive request for %s" % report)
                self.__nessus.archive(report)
                response = "nessus archive ack " + report

        if action == "delete":
            result = re.findall("report=\"([a-z0-9.]+)\"", message)

            if result != []:
                report = result[0]

            logger.info("Got delete request for %s" % report)

            if report.endswith(".report"):
                self.__nessus.delete(report, True)

            else:
                self.__nessus.delete(report, False)

            response = "nessus delete ack " + report

        if action == "restore":
            result = re.findall("report=\"([a-z0-9.]+)\"", message)

            if result != []:
                report = result[0]

            logger.info("Got restore request for: %s" % report)
            self.__nessus.restore(report)
            response = "nessus restore ack " + report


        # send back our response
        return reponse



class DoNessus (threading.Thread):

    def __init__ (self) :
        self.__conf = None      # ossim configuration values (ossim.conf)
        self.__conn = None      # cursor to ossim database
        self.__nessus_user = None
        self.__nessus_pass = None
        self.__nessus_host = None
        self.__nessus_port = None
        self.__nessusrc = None
        self.__nessus_bin = None
        self.__scanner_type = None
        self.__dirnames = {}
        self.__filenames = {}
        self.__linknames = {}
        self.__set_debug = True
        self.__active_sensors = Set()
        self.__status = 0
        self.__last_error = None
        self.__sensor_list = []
        self.__nets_list = []
        self.__hosts_list = []
        self.__hostgroups_list = []
        self.__scan_type = None
        self.__sensors_scan = []
        self.__sensors_targets = {}

        threading.Thread.__init__(self)

    def __startup (self) :

        # configuration values
        self.__conf = OssimConf (Const.CONFIG_FILE)

        # database connection
        self.__conn = OssimDB()
        self.__conn.connect ( self.__conf["ossim_host"],
                              self.__conf["ossim_base"],
                              self.__conf["ossim_user"],
                              self.__conf["ossim_pass"])

    def __cleanup (self) :
        self.__conn.close()

    def set_scan_type(self, type):
        self.__scan_type = type
    
    def load_sensors (self, sensor_list = []) :
        self.__debug("Loading sensors")
        self.__sensor_list = sensor_list

    def load_hosts (self, hostgroup_list, net_list, host_list):
        self.__debug("Loading hostgroups/nets/hosts")
        self.__hostgroups_list = hostgroup_list
        self.__nets_list = net_list
        self.__hosts_list = host_list
        
    def get_sensors_by_id (self, id) :
        tmp_conf = OssimConf (Const.CONFIG_FILE)
        tmp_conn = None
        self.__debug("Getting sensors from policy id %d" % int(id))
        sensors = []
        tmp_conn = OssimDB()
        tmp_conn.connect ( tmp_conf["ossim_host"],
                              tmp_conf["ossim_base"],
                              tmp_conf["ossim_user"],
                              tmp_conf["ossim_pass"])

        query = "SELECT * FROM plugin_scheduler_sensor_reference where plugin_scheduler_id = %d" % int(id)
        hash = tmp_conn.exec_query(query)

        for row in hash:
            sensors.append(row["sensor_name"])

        print sensors

        if len(sensors) == 0:
            return False

        tmp_conn.close()
        return sensors

    def get_sheduler_list_by_id (self, id, type) :
        tmp_conf = OssimConf (Const.CONFIG_FILE)
        tmp_conn = None
        self.__debug("Getting %s from policy id %d" % (type,int(id)))
        list = []
        tmp_conn = OssimDB()
        tmp_conn.connect ( tmp_conf["ossim_host"],
                              tmp_conf["ossim_base"],
                              tmp_conf["ossim_user"],
                              tmp_conf["ossim_pass"])

        query = "SELECT * FROM plugin_scheduler_%s_reference where plugin_scheduler_id = %d" % (type,int(id))
        hash = tmp_conn.exec_query(query)

        if type == "host":
            col = "ip"
        else:
            col = "%s_name" % type

        for row in hash:
            list.append(row[col])

        print list

        tmp_conn.close()
        return list


    def get_nets(self, netgroups, nets):
        netgroups_l = netgroups.split(",")
        nets_l = []
        if not nets == "":
            nets_l = nets.split(",")
        net_list = []

        tmp_conf = OssimConf (Const.CONFIG_FILE)
        tmp_conn = None
        tmp_conn = OssimDB()
        tmp_conn.connect ( tmp_conf["ossim_host"],
                              tmp_conf["ossim_base"],
                              tmp_conf["ossim_user"],
                              tmp_conf["ossim_pass"])
        for group in netgroups_l:
            self.__debug(group)
            query = "SELECT * FROM net_group_reference where net_group_name='%s'" % group
            hash = tmp_conn.exec_query(query)
            for row in hash:
                if not row["net_name"] in net_list:
                    net_list.append(row["net_name"])
        for net in nets_l:
            if not net in net_list:
                net_list.append(net)
        tmp_conn.close()
        return net_list

    def join_list(self, list1, list2):
        tmp_list = []
        
        for item in list1:
            if not item in tmp_list:
                tmp_list.append(item)
        for item in list2:
            if not item in tmp_list:
                tmp_list.append(item)
        return tmp_list

    def get_shedule_scan_type(self, id):
        tmp_conf = OssimConf (Const.CONFIG_FILE)
        tmp_conn = None
        tmp_conn = OssimDB()
        tmp_conn.connect ( tmp_conf["ossim_host"],
                              tmp_conf["ossim_base"],
                              tmp_conf["ossim_user"],
                              tmp_conf["ossim_pass"])
        query = "SELECT type_scan FROM plugin_scheduler WHERE id = '%s'" % id
        hash = tmp_conn.exec_query(query)
        if hash != []:
            scan_type = hash[0]["type_scan"]
        else: 
            scan_type = None
        tmp_conn.close()
        return scan_type

    def __rm_rf (self, what) :
        """ Recursively delete a directory """

        # Never delete /, /bin, /etc, etc...
        if len(what) < 5:
            return

        self.__debug("Deleting " + what)

        if os.path.isdir(what):
            os.chdir(what)
            for dirpath,dirnames,dirfiles in os.walk(what, topdown=False):
                for file in dirfiles:
                    if file == []: pass
                    os.remove(os.path.join(dirpath,file))
                for dir in dirnames:
                    if dir == []: pass
                    os.rmdir(os.path.join(dirpath,dir))
            os.chdir("..")
            os.rmdir(what)

    def __get_latest_scan_dates(self):
        scan_date_array = {}
        tmp_conf = OssimConf (Const.CONFIG_FILE)
        tmp_conn = None
        self.__debug("Getting latest scan dates")
        tmp_conn = OssimDB()
        tmp_conn.connect ( tmp_conf["ossim_host"],
                              tmp_conf["ossim_base"],
                              tmp_conf["ossim_user"],
                              tmp_conf["ossim_pass"])

        query = "SELECT hostvul.ip as ip, date_format(hostvul.scan_date,\"%Y%m%d%H%i%s\") as scan_date FROM (SELECT ip, max(scan_date) AS mymax FROM host_vulnerability group by ip) AS myvul, host_vulnerability AS hostvul WHERE hostvul.ip=myvul.ip AND hostvul.scan_date=myvul.mymax"
        hash = tmp_conn.exec_query(query)

        for row in hash:
            scan_date_array[row["ip"]] = row["scan_date"]
        return scan_date_array

    def generate_report(self, report_name, host_list = []) :
        self.__debug("Generating report")

        tmp_conf = OssimConf (Const.CONFIG_FILE)
        ip_scan_dates = {}
        nsr_filedescriptors = {}
        unique_scan_dates = Set() # .add()
        scanner_type = tmp_conf["scanner_type"]
        report_fds = {}
        combined_nessus_report = []
        ip_scan_dates = self.__get_latest_scan_dates()
        today_date = datetime.datetime.today().strftime("%Y%m%d%H%M00")

        nessus_rpt_path = os.path.normpath(tmp_conf["nessus_rpt_path"])
        report_dir = os.path.normpath(os.path.join(nessus_rpt_path, today_date + ".report"))
        if scanner_type == "openvas2":
            tmp_name = tempfile.mktemp(".ossim.report.nbe")
        else:
            tmp_name = tempfile.mktemp(".ossim.report.nsr")
        if os.path.isdir(report_dir):
            self.__debug("Report dir %s already exists, returning" % report_dir)
            return False

        for scan_key, scan_value in ip_scan_dates.iteritems():
            self.__debug("Scan ip %s, Scan date %s." % (scan_key, scan_value))
            unique_scan_dates.add(scan_value)

        for scan_date in unique_scan_dates:
            try:
                if scanner_type == "openvas2":
                    nsr_filedescriptors[scan_date] = open(os.path.join(nessus_rpt_path, scan_date, "report.nbe"), "r")
                else:
                    nsr_filedescriptors[scan_date] = open(os.path.join(nessus_rpt_path, scan_date, "report.nsr"), "r")
            except IOError:
                self.__debug("Failed to open scan directory for %s" % scan_date)
                pass

        outfile =  open(tmp_name, "w")
        for ip in host_list:
            if scanner_type == "openvas2":
                r = re.compile('^results\|[^\|]+\|([^\|]+)\|'+ip)
            else:
                r = re.compile('^'+ip)
            try:
                nsr_filedescriptors[ip_scan_dates[ip]].seek(0)
            except:
                continue
            for line in nsr_filedescriptors[ip_scan_dates[ip]]:
                if r.search(line): 
                    combined_nessus_report.append(line.rstrip("\r\n"))

        for nessus_fd in nsr_filedescriptors:
            nsr_filedescriptors[nessus_fd].close()

        try:
            for line in combined_nessus_report:
                outfile.write(line + "\n")
        finally:
            outfile.close()
        self.__debug("Report written into %s" % tmp_name)

        nessusrc = tmp_conf["nessusrc_path"]
        self.__nessus_bin = self.__nessus_bin or \
                            tmp_conf["nessus_path"] or \
                            "/usr/bin/nessus"


        if os.path.exists(tmp_name):
            self.__debug("Writing html report into %s" % report_dir)
            cmd = "%s -T html_graph -i %s -o %s" % (self.__nessus_bin, tmp_name, report_dir)
            os.system(cmd)

        if os.path.isdir(report_dir) and len(report_dir) > 4:
            os.chmod(report_dir,0755)
            os.chdir(report_dir)
            for dirpath,dirnames,dirfiles in os.walk(report_dir):
                for dir in dirnames:
                    if dir == []: pass
                    os.chmod(dir, 0755)
            outfile =  open(os.path.join(report_dir, "report_name.txt"), "w")
            try:
                outfile.write(report_name+ "\n")
            finally:
                outfile.close()


        os.unlink(tmp_name)

    def delete(self, delete_date, report = False) :
# Short sanity check.
        if len(delete_date) < 14:
            return False
        self.__startup()
        delete_dir = os.path.normpath(self.__conf["nessus_rpt_path"])
        deleted_dir = os.path.join(delete_dir, delete_date)
        self.__rm_rf(deleted_dir)
        if not report:
            self.__debug("Deleting database entries")
            query = "DELETE FROM host_vulnerability WHERE scan_date = '%s'" % delete_date
            self.__conn.exec_query(query)
            query = "DELETE FROM net_vulnerability WHERE scan_date = '%s'" % delete_date
            self.__conn.exec_query(query)
        return True

    def restore(self, restore_date) :
        self.__startup()
        restore_dir = os.path.normpath(self.__conf["nessus_rpt_path"])
        os.chdir(restore_dir)
        restore_file = os.path.join(restore_dir, "backup_" + restore_date + ".tar.gz")
        self.__debug("Restore file: " + restore_file)
        if os.path.exists(restore_file):
            self.__debug("Attempting to restore " + restore_file)
            tar = tarfile.open(restore_file, "r:gz")
            for tarinfo in tar:
                tar.extract(tarinfo)
            tar.close()
            os.remove(restore_file)
            return True
        return False
 
    def archive(self, archive_date, report = False) :
        """ Create a tar / gzip copy of a directory, delete it afterwards """
        self.__startup()
        archive_dir = os.path.normpath(self.__conf["nessus_rpt_path"])
        os.chdir(archive_dir)
        archived_dir = os.path.join(archive_dir, archive_date)
        archived_file = os.path.join(archive_dir, "backup_" + archive_date + ".tar.gz")
        # File exists already, don't overwrite
        if os.path.exists(archived_file):
            return False

        tar = tarfile.open(archived_file, "w:gz")

        try:
            #tar.add(archived_dir)
            tar.add(archive_date)
        except OSError:
            return False
        tar.close()
        self.__rm_rf(archived_dir)
        return True

    def __get_networks (self, ip) :
        query = "SELECT * FROM net"
        host_networks = []
        hash = self.__conn.exec_query(query)
        for row in hash:
            temp = []
            for n in row["ips"].split(','):
                temp.append(n)
            if Util.isIpInNet(ip, temp):
                host_networks.append(row["name"])
        return host_networks

    def __test_write_fatal (self, path) :
        if not os.access(path, os.W_OK):
            self.__last_error = "Nessus scan failed. Write permission needed on %s for user %d or group %d"  % (path, os.geteuid(), os.getegid())
            print "Nessus scan failed. Write permission needed on %s for user %d or group %d"  % (path, os.geteuid(), os.getegid())
            self.__cleanup()
            return

    def __test_write (self, path) :
        if not os.access(path, os.W_OK):
            return False
        return True

    def __debug (self, message) :
        if self.__set_debug:
            print message

    def __is_active (self, sensor) :
        cmd = "%s -c %s -x -s -q %s %s %s %s" % (self.__nessus_bin, self.__nessusrc, sensor, self.__nessus_port, self.__nessus_user, self.__nessus_pass)
        pattern = "Session ID(.*)Targets"
        output = os.popen(cmd)
        for line in output.readlines():
            result = re.findall(pattern, line)
            if result != []:
                output.close()
                return True
        output.close()
        return False

    def __append (self, source, dest) :
        try:
            tempfd1 = open(source,"r")
            tempfd2 = open(dest,"a")
            for line in tempfd1:
                tempfd2.write(line)
            tempfd1.close()
            tempfd2.close()
        except Exception, e:
            print "__append: %s" % e

    def __update_cross_correlation (self, nsr_file) :
        """ This function updates the host_plugin_sid cross-correlation table
        in order to do snort or whatever <-> nessus correlation. """

        try:
            tempfd = open(nsr_file)
        except Exception, e:
            print "Unable to open file %s: %s" % (nsr_file, e)
            return

        refs = {}
    
        if self.__scanner_type == "openvas2":
            pattern = re.compile("^results\|[\d+\.]+\|([\d+\.]+)\|.*\(\d+/tcp\)\|[\d+\.]+\.(\d+)\|.*")
        else:
            pattern = re.compile("^([^\|]*)\|[^\|]*\|([^\|]*)\|.*")
        for line in tempfd.readlines():
            result = pattern.search(line)
            if result is not None:
                try:
                    (first, second) = result.groups()
                    if not refs.has_key(first):
                        refs[first] = {"sids": Set() }
                    refs[first]["sids"].add(second)
                except Exception, e:
                    print "%s" % e

        tempfd.close()

        self.__debug("Updating...")
        for ip in refs.iterkeys():
            self.__debug("Deleting %s" % ip)
            query = "DELETE FROM host_plugin_sid WHERE host_ip = inet_aton('%s') and plugin_id = 3001" % ip
            self.__conn.exec_query(query)
            for plugin_sid in refs[ip]["sids"]:
                self.__debug("Inserting %d" % int(plugin_sid))
                query = "INSERT INTO host_plugin_sid(host_ip, plugin_id, plugin_sid) values(inet_aton('%s'), 3001, %d)" % (ip, int(plugin_sid))
                self.__conn.exec_query(query)

    def __update_vulnerability_tables(self, nsr_txt_file, today):
        """ This function updates the host_vulnerability & net_vulnerability
        tables used within the vulnmeter """

        risk_values = {
            "None": 0,
            "Verylow/none": 1,
            "Low": 2,
            "Low/Medium": 3,
            "Medium/Low": 4,
            "Medium": 5,
            "Medium/High": 6,
            "High/Medium": 7,
            "High": 8,
            "Veryhigh": 9,
            "Critical": 10
        }

        hosts = Set()
        hv = {}
        try:
            vulnsfd = open(nsr_txt_file, "r")
        except Exception, e:
            print "Unable to open file %s: %s" % (nsr_txt_file, e)
            return

        if self.__scanner_type == "openvas2":
            pattern1 = "^[^\|]+\|[^\|]+\|(\d+\.\d+\.\d+\.\d+)\|"
            pattern2 = "Risk [Ff]actor\s*[\\n]*:\s*[\\n]*(\w+)[;|\s|\\n]*"
        else:
            pattern1 = "^(\d+\.\d+\.\d+\.\d+)"
            pattern2 = "Risk [Ff]actor\s*:\W+(\w*)"

        for line in vulnsfd:
            result1 = re.findall(str(pattern1),line)
            result2 = re.findall(str(pattern2),line)
            try:
                (host) = result1[0]
                if not hv.has_key(host):
                    hv[host] = 0
            except IndexError:
                continue 
            try:
                (risk) = result2[0]
            except IndexError:
                # continue
                risk = "None"
            if risk == "":
                # continue
                risk = "None"


            
            hosts.add(host)
            risk = re.sub(" \/.*|if.*","", risk)
            risk = re.sub(" ","", risk)
            rv = 0
            """
            Need to modify this in order to catch weird nessus plugin things
            like:
            - Low to High
            - Low (if you are not using Kerberos) / High (if kerberos is enabled)
            - Low (remotely) / High (locally)
            - etc...
            """
            if risk_values.has_key(risk):
                rv = risk_values[risk]

            # Override using CVSS Score if present
            pattern3 = re.compile("CVSS Base Score\s*:\s*(\d+)\s*;")
            result3 = pattern3.search(line)
            if result3:
                (risk,) = result3.groups()
                rv = int(risk)

            hv[host] += rv


        vulnsfd.close()

        net_vuln_lvl = {}

        self.__debug("\nUpdating host vulnerability levels")

        if hosts is not None:
            for host in hosts:
                vulnerability = hv[host]

                query = "INSERT INTO host_vulnerability VALUES ('%s', '%s', '%s')" % (host, today , vulnerability)
                self.__conn.exec_query(query)


                vuln_networks = self.__get_networks(host)
                if vuln_networks is not None:
                    for net in vuln_networks:
                        try: net_vuln_lvl[net]
                        except: 
                            net_vuln_lvl[net] = 0
                        self.__debug("Increasing %s by %d due to %s" % (net, vulnerability, host))
                        net_vuln_lvl[net] += vulnerability

        self.__debug("\nUpdating net vulnerability levels")

        if net_vuln_lvl is not None:
            for net in net_vuln_lvl:
                query = "INSERT INTO net_vulnerability(net, scan_date, vulnerability) VALUES('%s', '%s', %d)" % (net, today, int(net_vuln_lvl[net]))
                self.__conn.exec_query(query)

    def __backup_vulnerability_tables(self, today):
        """ This function backups host_vulnerability and net_vulnerability into
        a text file """

        self.__debug("\nBacking up vulnerability levels (into files)")

        try:
            uid = pwd.getpwnam("mysql")[2]
        except Exception, e:
        # Mysql user might not be on the server host
            uid = 0

        os.chown(today, uid, -1)

        result_vuln_sql_host = os.path.join(today, "vuln_host.sql.txt")
        result_vuln_sql_net = os.path.join(today, "vuln_net.sql.txt")

        query = "SELECT * FROM host_vulnerability INTO OUTFILE '%s'" % result_vuln_sql_host
        try:
            self.__conn.exec_query(query)
        except Exception, e:
            print "Error executing sql backup query:", e

        query = "SELECT * FROM net_vulnerability INTO OUTFILE '%s'" % result_vuln_sql_net
        try:
            self.__conn.exec_query(query)
        except Exception, e:
            print "Error executing sql backup query:", e

        # No need for the webserver to access these files directly
        try:
            os.chmod(result_vuln_sql_host,0600)
            os.chmod(result_vuln_sql_net,0600)
        except OSError, e:
            print e

    def status (self) :
        return self.__status

    def reset_status (self) :
        self.__status = 0
        self.__last_error = None

    def get_error (self) :
        return self.__last_error


    def add_ip_to_sensor(self, ip, sensor):
        if not sensor in self.__sensors_scan:
            self.__sensors_scan.append(sensor)
            self.__sensors_targets[sensor] = ip + "\n"
        else:
            self.__sensors_targets[sensor] = self.__sensors_targets[sensor] + ip + "\n"

    def run (self) :
        self.__startup()
        self.__status = 1

        self.__scanner_type = self.__scanner_type or \
                            self.__conf["scanner_type"] or \
                            "openvas2"
        self.__debug("Scanner type: " + self.__scanner_type)

        # Test DK
        #self.__update_vulnerability_tables("/tmp/a.nbe", "0000-00-00 00:00:00") 
        #self.__status = 0
        #return 

        self.__nessus_bin = self.__nessus_bin or \
                            self.__conf["nessus_path"] or \
                            "/usr/bin/nessus"

        if self.__conf["nessus_distributed"] == "1":
            nessus_distributed = True
            self.__debug("nessus_distributed (True) -> " + self.__conf["nessus_distributed"])
        else:
            nessus_distributed = False
            self.__debug("nessus_distributed (False) -> " + self.__conf["nessus_distributed"])
        
        # TODO: Fix distributed nessus scanning, it's broken. 2009/07
        # That way Santiago can still try and set it to non-distributed and have it working in the meantime.
        nessus_distributed = True

        self.__nessus_user = self.__conf["nessus_user"]
        self.__nessus_pass = self.__conf["nessus_pass"]
        self.__nessus_host = self.__conf["nessus_host"]
        self.__nessus_port = self.__conf["nessus_port"]

        today_date = datetime.datetime.today().strftime("%Y%m%d%H%M00")

        self.__dirnames["nessus_rpt_path"] = os.path.normpath(self.__conf["nessus_rpt_path"]) + "/"
        self.__dirnames["nessus_tmp"] = os.path.join(self.__dirnames["nessus_rpt_path"], "tmp") + "/"
        self.__dirnames["sensors"] = os.path.join(self.__dirnames["nessus_tmp"], "sensors") + "/"
        self.__dirnames["today"] = os.path.join(self.__dirnames["nessus_rpt_path"], today_date) + "/"
        self.__filenames["targets"] = os.path.join(self.__dirnames["nessus_tmp"], today_date + "targets.txt")
        self.__filenames["result_nsr_txt"] = os.path.join(self.__dirnames["nessus_tmp"], today_date + "result.txt")
        if self.__scanner_type == "openvas2":
            self.__filenames["result_nsr"] = os.path.join(self.__dirnames["nessus_tmp"], today_date + "result.nbe")
        else:
            self.__filenames["result_nsr"] = os.path.join(self.__dirnames["nessus_tmp"], today_date + "result.nsr")
        self.__linknames["last"] = os.path.join(self.__dirnames["nessus_rpt_path"],"last")
        if self.__scanner_type == "openvas2":
            self.__filenames["today_nsr"] = os.path.join(self.__dirnames["nessus_tmp"],"temp_res." + today_date + ".nbe")
        else:
            self.__filenames["today_nsr"] = os.path.join(self.__dirnames["nessus_tmp"],"temp_res." + today_date + ".nsr")


        self.__test_write_fatal(self.__dirnames["nessus_rpt_path"])

        if not self.__test_write(self.__dirnames["today"]):
            self.__debug("Creating todays scan dir: %s" % self.__dirnames["today"])
            try :
                os.makedirs(self.__dirnames["today"], 0755)
            except OSError, e :
                print e


        if not self.__test_write(self.__dirnames["nessus_tmp"]):
            print "Creating temp dir: %s" % self.__dirnames["nessus_tmp"]
            try :
                os.makedirs(self.__dirnames["nessus_tmp"], 0755)
            except OSError, e :
                print e

        try :
            os.unlink(self.__filenames["result_nsr"])
        except OSError, e :
            pass

        # No need to generate fake nessusrc, nessus takes care of that

        self.__nessusrc = os.path.join(self.__dirnames["nessus_tmp"], ".nessusrc")
        if self.__conf["nessusrc_path"]:
            self.__nessusrc = self.__conf["nessusrc_path"]

        self.__status = 10

        scan_networks = []        
        scan_hosts = []
        
        if nessus_distributed:
            self.__debug("Entering distributed mode")
            if not self.__test_write(self.__dirnames["sensors"]):
                self.__debug("Creating sensor temp dir: %s" % self.__dirnames["sensors"])
                try :
                    os.makedirs(self.__dirnames["sensors"], 0755)
                except OSError, e :
                    print e
             
            sensors = []
            active_sensors = []

            self.__filenames["targetfile"] = {}
            self.__filenames["nsrfile"] = {}
            self.__status = 15

            if (self.__scan_type == "hosts"):
                self.__debug("Entering Netgroup/Hostgroup/Net/Host based scan")
                self.__sensors_scan = []
                self.__sensors_targets = {}
                for net in self.__nets_list:
                    query = "SELECT net.ips,sensor.ip FROM net, net_sensor_reference, sensor WHERE net_sensor_reference.sensor_name = sensor.name and net.name = net_sensor_reference.net_name and net.name = '%s'" % net
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        self.__debug("Adding net %s in sensor %s" % (row["ips"], row["ip"]))
                        self.add_ip_to_sensor(row["ips"], row["ip"])
                        scan_networks.append(row["ips"])
                for host in self.__hosts_list:
                    query = "SELECT sensor.ip FROM host_sensor_reference, sensor WHERE host_sensor_reference.sensor_name = sensor.name and host_ip = '%s'" % host
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        self.__debug("Adding host %s in sensor %s" % (host, row["ip"]))
                        self.add_ip_to_sensor(host, row["ip"])
                        scan_hosts.append(host)
                for hostgroup in self.__hostgroups_list:
                    query = "SELECT sensor.ip, host_ip FROM host_group_reference, host_group_sensor_reference, sensor WHERE host_group_sensor_reference.sensor_name = sensor.name and host_group_reference.host_group_name = host_group_sensor_reference.group_name and host_group_reference.host_group_name = '%s'" % hostgroup
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        self.__debug("Adding host %s in sensor %s from hostgroup %s" % (row["host_ip"], row["ip"],hostgroup))
                        self.add_ip_to_sensor(row["host_ip"], row["ip"])
                        scan_hosts.append(row["host_ip"])
                    
                for sensor in self.__sensors_scan:
                    self.__filenames["targetfile"][sensor] = os.path.join(self.__dirnames["sensors"], sensor + ".targets.txt")
                    sensorfd = open(self.__filenames["targetfile"][sensor],"w")
                    sensorfd.writelines(self.__sensors_targets[sensor])
                    sensorfd.close()
                sensors = self.__sensors_scan
            else:
                self.__debug("Entering sensor based scan")
                if len(self.__sensor_list) > 0:
                    sensors = self.__sensor_list
                else:
                    query = "SELECT * FROM sensor"
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        sensors.append(row["ip"])
                for sensor in sensors:
                    self.__filenames["targetfile"][sensor] = os.path.join(self.__dirnames["sensors"], sensor + ".targets.txt")
                    sensorfd = open(self.__filenames["targetfile"][sensor],"w")
    
                    # net_group_scan (3001,net_group_name) -> net_group_reference (net_group_name, net_name) -> net (name) -> net_sensor_reference (net_name,sensor_name) -> sensor (name,ip)
                    query = "SELECT net.name,net.ips,sensor.ip FROM net,net_group_scan,net_group_reference, net_sensor_reference,sensor WHERE net_group_scan.plugin_id = 3001 AND net_group_scan.net_group_name = net_group_reference.net_group_name AND net_group_reference.net_name = net.name AND net.name = net_sensor_reference.net_name AND net_sensor_reference.sensor_name = sensor.name AND sensor.ip = '%s'" % sensor
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        self.__debug("Adding net %s from net_group" % row["ips"])
                        sensorfd.writelines(row["ips"] + "\n")
                        scan_networks.append(row["ips"])
    
                    # net_scan(3001,net_name) -> net (name) -> net_sensor_reference (net_name,sensor_name) -> sensor (name,ip) => (net_name,net_ip,sensor_ip)
                    query = "SELECT net.name,net.ips,sensor.ip FROM net,net_scan,net_sensor_reference,sensor WHERE net_scan.plugin_id = 3001 AND net_scan.net_name = net.name AND net.name = net_sensor_reference.net_name AND net_sensor_reference.sensor_name = sensor.name AND sensor.ip = '%s'" % sensor
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        if row["ips"] in scan_networks:
                            self.__debug("DUP net, already defined within net_group: %s" % row["ips"])
                        else:
                            self.__debug("Adding net %s" % row["ips"])
                            sensorfd.writelines(row["ips"] + "\n")
                            scan_networks.append(row["ips"])
    
                    query = "SELECT sensor.ip, inet_ntoa(host_scan.host_ip) AS temporal FROM host_scan,host,sensor,host_sensor_reference WHERE plugin_id = 3001 AND host_sensor_reference.sensor_name = sensor.name AND host_sensor_reference.host_ip = inet_ntoa(host_scan.host_ip) AND host.ip = inet_ntoa(host_scan.host_ip) AND sensor.ip = '%s' " % sensor
                    hash = self.__conn.exec_query(query)
                    for row in hash:
                        if(Util.isIpInNet(row["temporal"], scan_networks)):
                            self.__debug("DUP host, already defined within network: %s" % row["temporal"])
                        else :
                            try:
                                sensorfd.writelines(row["temporal"] + "\n")
                            except KeyError, e:
                                pass
                            self.__debug("Adding host %s" % row["temporal"])
                            scan_hosts.append(row["temporal"])
                    sensorfd.close()

            pids = Set()
            self.__status = 20
            for sensor in sensors:
                pid = os.fork()
                if pid:
                    pids.add(pid)
                else:
                    if os.stat(self.__filenames["targetfile"][sensor])[stat.ST_SIZE] == 0:
                        try :
                            os.unlink(self.__filenames["targetfile"][sensor])
                        except OSError, e :
                            pass
                        self.__debug("Child %s exiting" % sensor)
                        os._exit(0)

                    if not self.__is_active(sensor):
                        try :
                            os.unlink(self.__filenames["targetfile"][sensor])
                        except OSError, e :
                            pass
                        self.__debug("Child %s exiting" % sensor)
                        os._exit(0)

                    self.__filenames["nsrfile"][sensor] = os.path.join(self.__dirnames["sensors"], sensor + ".temp_res.nsr")
                    targetfd = open(self.__filenames["targetfile"][sensor], "r")
                    num_hosts = 0
                    pattern = re.compile("(.*)\/(.*)")
                    for line in targetfd:
                        result = pattern.match(line)
                        if result is not None:
                            (network,mask) = result.groups()
                            num_hosts += int((2 << (32 - int(mask))-1) -2)
                        else:
                            num_hosts += 1
                    targetfd.close()
                    self.__debug("%s up and running, starting scan against %s hosts" % (sensor, num_hosts))

                    self.__debug("Starting scan against:\n----------------------")
                    targetfd = open(self.__filenames["targetfile"][sensor], "r")
                    for line in targetfd:
                        self.__debug(line)
                    targetfd.close()

                    if self.__scanner_type == "openvas2":
                        cmd = "%s -c %s -x -T nbe -q %s %s %s %s %s %s" % (self.__nessus_bin, self.__nessusrc, sensor, self.__nessus_port, self.__nessus_user, self.__nessus_pass, self.__filenames["targetfile"][sensor], self.__filenames["nsrfile"][sensor] )
                    else:
                        cmd = "%s -c %s -x -T nsr -q %s %s %s %s %s %s" % (self.__nessus_bin, self.__nessusrc, sensor, self.__nessus_port, self.__nessus_user, self.__nessus_pass, self.__filenames["targetfile"][sensor], self.__filenames["nsrfile"][sensor] )

                    # Discard output
                    os.system(cmd)


                    # Append results to main result file
                    self.__append(self.__filenames["nsrfile"][sensor], self.__filenames["result_nsr"])

                    self.__debug("Child %s exiting" % sensor)
                    os._exit(0)

            self.__debug("Waiting for scans to finish")
            for pid in pids:
                os.waitpid(pid, 0)
            self.__debug("Scan finished, cleaning up a bit")


            # Back to parent

            for sensor in active_sensors:
                try:
                    os.unlink (self.__filenames["nsrfile"][sensor])
                    os.unlink (self.__filenames["targetfile"][sensor])
                except OSError:
                    pass

        else: # non-distributed
            self.__debug("Entering non-distributed mode")

            sensorfd = open(self.__filenames["targets"], "w")
            self.__debug("Adding networks")
            query = "SELECT name,ips FROM net, net_scan WHERE net.name = net_scan.net_name AND net_scan.plugin_id = 3001"
            hash = self.__conn.exec_query(query)
            for row in hash:
                self.__debug("Adding network %s" % row["ips"])
                sensorfd.writelines(row["ips"] + "\n")
                scan_networks.append(row["ips"])

            self.__status = 15
                  
            self.__debug("Adding hosts")
            query = "SELECT inet_ntoa(host_ip) AS temporal FROM host_scan WHERE plugin_id = 3001"
            hash = self.__conn.exec_query(query)
            for row in hash:
                try:
                    if Util.isIpInNet(row["temporal"], scan_networks):
                        print "Dup: %s. Please check your config" % row["temporal"]
                    else:
                        self.__debug("Adding host %s" % row["temporal"])
                        scan_hosts.append(row["temporal"])
                        sensorfd.writelines(row["temporal"] + "\n")
                except KeyError:
                    pass
            if scan_networks != []:
                print scan_networks

            self.__status = 20

            sensorfd.close()
            self.__debug("Going to scan:")
            self.__debug("--------------")
            tempfd = open(self.__filenames["targets"], "r")
            for line in tempfd.readlines():
                self.__debug(line)
            tempfd.close()
            if self.__scanner_type == "openvas2":
                cmd = "%s -c %s -x -T nbe -q %s %s %s %s %s %s" % (self.__nessus_bin, self.__nessusrc, self.__nessus_host, self.__nessus_port, self.__nessus_user, self.__nessus_pass, self.__filenames["targets"], self.__filenames["result_nsr"])
            else:
                cmd = "%s -c %s -x -T nsr -q %s %s %s %s %s %s" % (self.__nessus_bin, self.__nessusrc, self.__nessus_host, self.__nessus_port, self.__nessus_user, self.__nessus_pass, self.__filenames["targets"], self.__filenames["result_nsr"])
            os.system(cmd)

        # Start Converting & calculating

        self.__status = 50

        # Convert to txt so we can match vulnerabilities
        if os.path.exists(self.__filenames["result_nsr"]):
            if self.__scanner_type == "openvas2":
                cmd = "/bin/cp %s %s" % (self.__filenames["result_nsr"], self.__filenames["result_nsr_txt"])
            else:
                cmd = "%s -c %s -T text -i %s -o %s" % (self.__nessus_bin, self.__nessusrc, self.__filenames["result_nsr"], self.__filenames["result_nsr_txt"])
            os.system(cmd)
        else:
            self.__status = -1
            self.__last_error = "Result file " + self.__filenames["result_nsr"] + " not present after scan"
            print "Scan failed check output and try enabling debug"
            return

        if os.path.exists(self.__filenames["result_nsr"]):
            self.__update_vulnerability_tables(self.__filenames["result_nsr"], today_date) 
            self.__debug("Calling Vulnerabilities from within DoNessus for nsr: %s" % self.__filenames["result_nsr"])
            vuln = Vulnerabilities()
            vuln.process(self.__filenames["result_nsr"], today_date, scan_networks, scan_hosts)

        try:
            if os.stat(self.__filenames["today_nsr"])[stat.ST_SIZE] > 0:
                self.__debug("\nAppending results")
                self.__append(self.__filenames["today_nsr"],self.__filenames["result_nsr"])
        except OSError:
            pass

        self.__rm_rf(self.__dirnames["today"])

        self.__status = 75

        self.__debug("Today is %s" % today_date)
        
        if os.path.exists(self.__filenames["result_nsr"]):
            cmd = "%s -c %s -T html_graph -i %s -o %s" % (self.__nessus_bin, self.__nessusrc, self.__filenames["result_nsr"], self.__dirnames["today"])
            os.system(cmd)
            if self.__scanner_type == "openvas2":
                cmd = "/bin/cp %s %s" % (self.__filenames["result_nsr"], os.path.join(self.__dirnames["today"], "report.nbe"))
            else:
                cmd = "/bin/cp %s %s" % (self.__filenames["result_nsr"], os.path.join(self.__dirnames["today"], "report.nsr"))
            os.system(cmd)


        self.__status = 90

        if os.path.isdir(self.__dirnames["today"]) and len(self.__dirnames["today"]) > 4:
            if self.__conf["ossim_type"] == "mysql":
                self.__backup_vulnerability_tables(self.__dirnames["today"]) 
            os.chmod(self.__dirnames["today"],0755)
            os.chdir(self.__dirnames["today"])
            for dirpath,dirnames,dirfiles in os.walk(self.__dirnames["today"]):
                for dir in dirnames:
                    if dir == []: pass
                    os.chmod(dir, 0755)
      
        if os.path.exists(self.__filenames["today_nsr"]):
            os.remove(self.__filenames["today_nsr"])

        if os.path.exists(self.__filenames["result_nsr"]):
            os.rename(self.__filenames["result_nsr"],self.__filenames["today_nsr"])

        try:
            os.remove(self.__linknames["last"])
        except Exception, e:
            pass
        if os.path.exists(self.__dirnames["today"]):
            os.symlink(self.__dirnames["today"], self.__linknames["last"])

        self.__status = 95

        if os.path.exists(self.__filenames["today_nsr"]):
            self.__update_cross_correlation(self.__filenames["today_nsr"])

        self.__debug("Parent exiting")

        self.__status = 0

        return


    def load_shedule(self, id):
        scan_type = self.get_shedule_scan_type(id)
        print "Schedule scan type: %s" % scan_type
        if scan_type == "sensor":
            sensors = []
            sensors = self.get_sensors_by_id(id)
            if sensors == []:
                # Wrong scheduler id
                print "Wrong scheduler id %d" % int(id)
                sys.exit()
            else:
                print __name__, ": Sensor_list:", sensors
                self.set_scan_type("sensor")
                self.load_sensors(sensors)
        else:
            netgroups = self.get_sheduler_list_by_id(id, "netgroup")
            nets = self.get_sheduler_list_by_id(id, "net")
            hostgroups_list = self.get_sheduler_list_by_id(id, "hostgroup")
            hosts_list = self.get_sheduler_list_by_id(id, "host")
            nets_list = self.join_list(netgroups,nets)
            print __name__, ": Net_list:", nets_list
            print __name__, ": Host_list:", hosts_list
            print __name__, ": Hostgroup_list:", hostgroups_list
            if nets_list == [] and hosts_list == [] and hostgroups_list == []:
                # Wrong scheduler id
                print "Wrong scheduler id %d" % int(id)
                sys.exit()
            self.set_scan_type("hosts")
            self.load_hosts(hostgroups_list, nets_list, hosts_list)


if __name__ == "__main__":

    sensors = []
    donessus = DoNessus()
    usage = "%prog [-i scheduler_id]"
    parser = OptionParser(usage = usage)
    parser.add_option("-i", "--scheduler-id", dest="scheduler_id", action="store", help = "Scheduler id to execute", metavar="sched_id")
    (options, args) = parser.parse_args()

    if options.scheduler_id is not None:
        donessus.load_shedule(options.scheduler_id)
    else:
        sensors.append("127.0.0.1")
        sensors.append("127.0.0.2")
        donessus.load_sensors(sensors)
    
    donessus.start()
# vim:ts=4 sts=4 tw=79 expandtab:
