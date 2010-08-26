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
# Some maintenance tasks for the Nagios config related to HOST SERVICES
#

#
# GLOBAL IMPORTS
#
import os, re, subprocess, threading, time

#
# LOCAL IMPORTS
#
import Const
from Logger import Logger
from NagiosMisc import nagios_host, nagios_host_service, nagios_host_group_service, nagios_host_group
from OssimDB import OssimDB
from OssimConf import OssimConf
import Util

#
# GLOBAL VARIABLES
#
logger = Logger.logger
looped = 0



class NagiosManager:
    def __init__(self, conf):
        logger.debug("Initialising Nagios Manager.")
        self.__nagios = None
        self.__conf = conf

    def process(self, message):
        logger.debug("Nagios Manager: Processing: %s" % message)

        response = ""
        action = Util.get_var("action=\"([a-z]+)\"", message)

        if action == "add":
            type = Util.get_var("type=\"([a-zA-Z]+)\"", message)

            logger.debug("TYPE: %s" % type)
            if type == "host":
                liststring = Util.get_var("list=\"[^\"]+\"", message)
                logger.debug("STRING: %s" % liststring)
                list = liststring.split("|")
                logger.debug("LIST: %s" % list)
                for host in list:
                    host_data = re.match(r"^\s*(list=\")*(?P<ip>([0-9]{1,3}\.){3}[0-9]{1,3})\;(?P<hostname>[\w_\-\s.]+)\s*\"*$",host)

                    if host_data.group('ip') != [] and host_data.group('hostname') != []:
                        hostname = host_data.group('hostname')
                        ip = host_data.group('ip')

                        logger.debug("Adding hostname \"%s\" with ip \"%s\" to nagios" % (hostname, ip))

                        nh = nagios_host(ip, hostname, "", self.__conf)
                        nh.write()

            if type == "hostgroup":
                name = Util.get_var("name=\"([\w_\-\s.]+)\"", message)
                liststring = Util.get_var("list=\"([^\"]+)\"", message)
                list = liststring.split(",")
                logger.debug("LISTSTRING: %s" % liststring)
                hosts = ""
                for host in list:
                    #host_data=re.match(r"^\s*(list=\")*(?P<ip>([0-9]{1,3}\.){3}[0-9]{1,3})\s*\"*$",host)
                    #To support nagios name
                            
                    host_data = re.match("(?P<ip>[^;]+);(?P<name>[^$]+)$",host)
                    if host_data and host_data.group('ip') != []:
                        ip = host_data.group('ip')
                        hName = host_data.group('name')

                        if hosts == "":
                            hosts = ip
                            hgName= hName
                        else:
                            hosts = ip + "," + hosts
                            hgName = hName + "," + hgName

                        logger.debug("Adding host \"%s\" with ip \"%s\" needed by group_name %s to nagios" % (hName, ip, name))

                        nh = nagios_host(ip, hName, "", self.__conf)
                        nh.write()

                    else:
                        logger.warning("Nagios format error in message: %s" % message)
                        return

                if hosts != "":
                    logger.debug("Adding %s to nagios" % (name))
                    logger.debug("LIST: %s" % (hgName))
                    nhg = nagios_host_group(name, name, hgName, self.__conf)
                    nhg.write()

                else:
                    logger.debug("Invalid hosts list... not adding %s to nagios" % (name))

            action = "reload"

        if action == "del":
            type = Util.get_var("type=\"([a-zA-Z]+)\"", message)

            if type == "host":
                ip = Util.get_var("list=\"\s*(([0-9]{1,3}\.){3}[0-9]{1,3})\s*\"",message)
                ip = ip[0]

                if ip != "":
                    logger.debug("Deleting hostname \"%s\" from nagios" % (ip))
                    nh = nagios_host(ip, ip, "", self.__conf)
                    nh.delete_host()

            if type == "hostgroup":
                name = Util.get_var("name=\"([\w_\-.]+)\"", message)

                logger.debug("Deleting hostgroup_name \"%s\" from nagios" % (name))

                nhg = nagios_host_group(name, name, "", self.__conf)
                nhg.delete_host_group()

            action="reload"

        else: 
            print


        if action=="restart" or action=="reload":
            if self.__nagios == None:
                self.__nagios = DoNagios()

            self.__nagios.make_nagios_changes()
            self.__nagios.reload_nagios()

        # send back our response
        return response



class DoNagios(threading.Thread):
    _interval = 600                 # intervals

    def test_create_dir(self,path):
        if not os.path.exists(path):
            os.makedirs(path)


    def __init__(self):
        self._tmp_conf = OssimConf (Const.CONFIG_FILE)
        threading.Thread.__init__(self)
        self.test_create_dir(self._tmp_conf['nagios_cfgs'])
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'],"hosts"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'],"host-services"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'],"hostgroups"))
        self.test_create_dir(os.path.join(self._tmp_conf['nagios_cfgs'],"hostgroup-services"))


    def run(self):
        global looped

        if looped == 0:
            self.loop()
            looped = 1

        else:
            logger.debug("Ignoring additional instance.")


    def loop(self):
        while True:
            logger.debug("Looking for new services to add")
            self.make_nagios_changes()

            # sleep until the next round
            logger.debug("Sleeping until the next round in %ss" % self._interval)
            time.sleep(self._interval)


    def make_nagios_changes(self):
        port = None
        db = OssimDB()
        db.connect (self._tmp_conf["ossim_host"],
                self._tmp_conf["ossim_base"],
                self._tmp_conf["ossim_user"],
                self._tmp_conf["ossim_pass"])
        query="select port from host_services where (protocol=6 or protocol=0) and nagios=1 group by port"
        services=db.exec_query(query)

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "host-services")

        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        path = os.path.join(self._tmp_conf['nagios_cfgs'], "hostgroup-services")

        for fi in os.listdir(path):
            os.remove(os.path.join(path, fi))

        i = 0

        for port in services:
            i+=1
            query = "select h.hostname, hs.service_type from host_services hs, host_scan h_sc, host h where (hs.protocol=6 or hs.protocol=0) and hs.port=%s and hs.ip=h_sc.host_ip and h_sc.plugin_id=2007 and hs.nagios=1 and h.ip=inet_ntoa(hs.ip) group by h.ip order by h.ip" % port['port']
            hosts = db.exec_query(query)
            list = ""
            for host in hosts:
                if list != "":
                    list += ","

                list += host['hostname']

            if list != "":
                k = nagios_host_service(list, port['port'], host['service_type'], "check_tcp!%d" % port['port'], "0", self._tmp_conf)
                k.select_command()
                k.write()

                hg = nagios_host_group_service(self.serv_port(port['port']),self.serv_name(port['port']),list,self._tmp_conf)
                hg.write()

        if port is not None and port in services:
            logger.debug("Changes where applied! Reloading Nagios config.")
            self.reload_nagios()

        db.close()


    def reload_nagios(self):

        # catch the process output for logging purposes
        process = subprocess.Popen(self._tmp_conf['nagios_reload_cmd'], stdout=subprocess.PIPE, stderr=subprocess.PIPE, shell=True)
        (pid, exit_status) = os.waitpid(process.pid, 0)
        output = process.stdout.read().strip() + process.stderr.read().strip()

        # show command output if return code indicates error
        if exit_status != 0:
            logger.error(output)


    def port_to_service(self, number):
        f = open("/etc/services")
        #Actually we only look for tcp protocols here
        regexp_line = r'^(?P<serv_name>[^\s]+)\s+%d/tcp.*' % number
        try:
            service = re.compile(regexp_line)
            for line in f:
                serv = service.match(line)

                if serv != None:
                    return serv.groups()[0]

        finally:
            f.close()


    def serv_name(self, port):
        return "%s_Servers" % (self.port_to_service(port)) 


    def serv_port(self, port):
        return "port_%d_Servers" % port





