import Const
import os
from OssimConf import OssimConf


class nagios_host:
    _name=""
    _alias=""
    _address=""
    _use="generic-host"
    _parents=""
    __conf=None

    def __init__(self, ip, hostname, sensor, conf=None):
        self._name=ip
        self._alias=hostname
        self._address=ip
        if sensor != "":
            self._parents=sensor
        self.__conf=conf

    def debug(self, msg):
        print __name__," : ",msg

    def file_host(self):
        if self.__conf is None:
            self.__conf = OssimConf (Const.CONFIG_FILE)
            self.debug ("Getting new ossim config for %s " % self._name)
        return os.path.join(self.__conf['nagios_cfgs'], "hosts", self._address + ".cfg")

    def write(self):
        cfg_text = "define host{\n"
        cfg_text += "\thost_name " + self._alias + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\taddress " + self._address + "\n"
        cfg_text += "\tuse " + self._use + "\n"
#        if self._parents != "":
#            cfg_text += "\tparents " + self._parents + "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host(), "w")
            f.write(cfg_text)
            self.debug("host configuration checked for %s " % self._name)
        except Exception, e:
            self.debug(e)
            return False

    def host_in_nagios(self):
        return os.path.exists(self.file_host())

    def delete_host(self):
        if self.host_in_nagios():
            os.remove(self.file_host())
        else:
            self.debug ("Error: File %s does NOT exist!" % self.file_host())


class nagios_host_service:
    _host_names=""
    _descr=""
    _check_cmd=""
    _use="generic-service"
    _notif=""
    _port=""

    def __init__(self, hostnames, port, descr,cmd, notif_interval="",conf=None):
        self._host_names=hostnames
        self._descr=descr
        self._port=port
        self._check_cmd=cmd
        self.__conf=conf
        if notif_interval != "":
            self._notif=notif_interval

    def debug(self, msg):
        print __name__," : ", msg

    def file_host_service(self):
        if self.__conf is None:
            self.__conf = OssimConf (Const.CONFIG_FILE)
            self.debug ("Checking nagios services for %s " % self._host_names)
        return os.path.join(self.__conf['nagios_cfgs'],
                            "host-services",
                    self._descr + ".cfg")

    def write(self):
        cfg_text = "define service{\n"
        cfg_text += "\thost_name " + self._host_names + "\n"
        cfg_text += "\tservice_description " + self._descr + "\n"
        cfg_text += "\tcheck_command " + self._check_cmd + "\n"
        cfg_text += "\tuse " + self._use + "\n"
        if self._notif != "":
            cfg_text += "\tnotification_interval " + self._notif + "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_service(), "w")
            f.write(cfg_text)
            self.debug("service configuration checked for %s " % self._host_names)
            f.close()
        except Exception, e:
            print e
            return False

    def file_host_service_in_nagios(self):
        return os.path.exists(self.file_host_service())

    def delete(self):
        if self.file_host_service_in_nagios():
            os.remove(self.file_host_service())
        else:
            self.debug ("Error: File %s does NOT exist!" % self.file_host_service())

    def select_command(self):
        port=self._port
        if port == 21:
            self._check_cmd="check_ftp"
            self._descr="FTP"
        elif port == 22:
            self._check_cmd="check_ssh"
            self._descr="SSH"
        elif port == 23:
            self._check_cmd="check_tcp!23"
            self._descr="TCP"
        elif port == 25:
            self._check_cmd="check_smtp"
            self._descr="SMTP"
        elif port == 80:
            self._check_cmd="check_http"
            self._descr="HTTP"
        elif port == 161:
            self._check_cmd="check_snmp"
            self._descr="SNMP"
        elif port == 389:
            self._check_cmd="check_ldap"
            self._descr="LDAP"
        elif port == 3306:
            self._check_cmd="check_mysql"
            self._descr="MYSQL"
        elif port == 3389:
            self._check_cmd="check_tcp!3389"
            self._descr="TERMINAL_SERVER"
        elif port == 5432:
            self._check_cmd="check_pgsql"
            self._descr="PGSQL"
        elif (port == 6667 or port == 6668 or port == 6669):
            self._check_cmd="check_ircd"
            self._descr="IRCD"
        else:
            self._check_cmd="check_tcp!%d" % self._port
            self._descr="GENERIC_TCP_%d" % self._port
            # To search in /etc/services !!!

    def add_host(self,host):
        if self._host_names != "":
            self._host_names+=","
            self._host_names+=host

#k=nagios_host_service("192.168.1.1,10.0.0.20","22","ssh","check_ssh","0",None)
#k.select_command()
#k.add_host("192.168.1.3")
#k.write()



class nagios_host_group:
    _name=""
    _alias=""
    _members=""
    __conf=None

    def __init__(self, name, alias, members, conf=None):
        self._name=name
        self._alias=alias
        self._members=members
        self.__conf=conf

    def debug(self, msg):
        print __name__," : ",msg

    def file_host_group(self):
        if self.__conf is None:
            self.__conf = OssimConf (Const.CONFIG_FILE)
            self.debug ("Checking nagios config for %s " % self._name)
        return os.path.join(self.__conf['nagios_cfgs'], "hostgroups", self._name + ".cfg")

    def write(self):
        cfg_text = "define hostgroup{\n"
        cfg_text += "\thostgroup_name " + self._name + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\tmembers " + self._members+ "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_group(), "w")
            f.write(cfg_text)
            self.debug("hostgroup configuration checked for %s " % self._name)
        except Exception, e:
            self.debug(e)
            return False

    def host_group_in_nagios(self):
        return os.path.exists(self.file_host_group())

    def delete_host_group(self):
        if self.host_group_in_nagios():
            os.remove(self.file_host_group())
        else:
            self.debug ("Error: File %s does NOT exist!" % self.file_host_group())


class nagios_host_group_service:
    _name=""
    _alias=""
    _members=""
    __conf=None

    def __init__(self, name, alias, members, conf=None):
        self._name=name
        self._alias=alias
        self._members=members
        self.__conf=conf

    def debug(self, msg):
        print __name__," : ",msg

    def file_host_group(self):
        if self.__conf is None:
            self.__conf = OssimConf (Const.CONFIG_FILE)
            self.debug ("Checking nagios config for %s (%s)" % (self._name,self._alias))
        return (self.__conf['nagios_cfgs'] + "hostgroup-services/" + self._name+ ".cfg")

    def write(self):
        cfg_text = "define hostgroup{\n"
        cfg_text += "\thostgroup_name " + self._name + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\tmembers " + self._members+ "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_group(), "w")
            f.write(cfg_text)
            self.debug("hostgroup configuration checked for %s (%s)" % (self._name,self._alias))
        except Exception, e:
            debug(e)
            return False

    def host_group_in_nagios(self):
        return os.path.exists(self.file_host_group())

    def delete_host_group(self):
        if self.host_group_in_nagios():
            os.remove(self.file_host_group())
        else:
            self.debug ("Error: File %s does NOT exist!" % self.file_host_group())


