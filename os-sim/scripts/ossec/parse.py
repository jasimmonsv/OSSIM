import xml.dom.minidom
import sys

plugins = {"syslog" : "7001","firewall" : "7002","ids" : "7003","web-log" : "7004","squid" : "7005","windows" : "7006","ossec" : "7007",
		   "pam" : "7008","authentication_success" : "7009","authentication_failed" : "7010","invalid_login" : "7011","authentication_failures" : "7012",
		   "sshd" : "7013","recon" : "7014","exploit_attempt" : "7015","telnetd" : "7016","errors" : "7017","low_diskspace" : "7018",
		   "nfs" : "7019","xinetd" : "7020","access_control" : "7021","access_denied" : "7022","mail" : "7023","smartd" : "7024","linuxkernel" : "7025",
		   "promisc" : "7026","service_availability" : "7027","system_shutdown" : "7028","cron" : "7029","su" : "7030","tripwire" : "7031","adduser" : "7032",
		   "sudo" : "7033","pptp" : "7034","fts" : "7035","arpwatch" : "7036","new_host" : "7037","ip_spoof" : "7038","symantec" : "7039","virus" : "7040",
		   "pix" : "7041","config_changed" : "7042","account_changed" : "7043","system_error" : "7044","named" : "7045","invalid_access" : "7046",
		   "client_misconfig" : "7047","smbd" : "7048","vsftpd" : "7049","connection_attempt" : "7050","pure-ftpd" : "7051","proftpd" : "7052",
		   "msftp" : "7053","hordeimp" : "7054","vpopmail" : "7055","courier" : "7056","web" : "7057","accesslog" : "7058","attack" : "7059",
		   "sql_injection" : "7060","web_scan" : "7061","apache" : "7062","automatic_attack" : "7063","unknown_resource" : "7064","invalid_request" : "7065",
		   "mysql_log" : "7066","postgresql_log" : "7067","firewall_drop" : "7068","multiple_drops" : "7069","cisco_ios" : "7070","netscreenfw" : "7071",
		   "sonicwall" : "7072","postfix" : "7073","spam" : "7074","multiple_spam" : "7075","sendmail" : "7076","smf-sav" : "7077","imapd" : "7078",
		   "mailscanner" : "7079","ms" : "7080","exchange" : "7081","racoon" : "7082","cisco_vpn" : "7083","spamd" : "7084","win_authentication_failed" : "7085",
		   "policy_changed" : "7086","logs_cleared" : "7087","login_denied" : "7088","time_changed" : "7089","attacks" : "7090","elevation_of_privilege" : "7091",
		   "zeus" : "7092","rootcheck" : "7093","syscheck" : "7094","hostinfo" : "7095","local" : "7096",
		   "group_created" : "7100", "group_changed" : "7101", "group_deleted" : "7102",
		   "dovecot" : "7103", "mcafee" : "7104", "service_start" : "7105", "process_monitor" : "7106", 
		   "login_time" : "7107", "trend_micro" : "7108", "vmware" : "7109", "dhcp_lease_action" : "7110", "login_day" : "7111", "dhcp_maintenance" : "7112",
		   "dhcp_dns_maintenance" :"7113", "dhcp_rogue_server" : "7114" , "dhcp_ipv6" : "7115"}

name = sys.argv[1]
#print name
try:
	doc = xml.dom.minidom.parse(name)
except:
	print "Error on %s" % name
	sys.exit(0)

defs = doc.getElementsByTagName('group')[0]
group = defs.getAttribute('name')
for d in defs.getElementsByTagName('rule'):
	id = d.getAttribute('id')
	name = d.getElementsByTagName('description')[0].lastChild.nodeValue.replace("\n", "").replace("'", "").replace('"', "")
	try:
		dgroup = d.getElementsByTagName('group')[0].lastChild.nodeValue
	except:
		dgroup = group
	dgroup = dgroup.split(",")[0]
	try:
		pid = plugins[dgroup]
		#print 'INSERT INTO plugin_sid(plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES(%s, %s, NULL, NULL, 1,1, "ossec: %s");' % (pid, id, name)
		print "%s=%s" % (id, pid)
	except:
		print "%s doesnt exists" % dgroup
	#print "%s : %s : %s, %s" % (id,name, dgroup, pid)
	#print d.localName


