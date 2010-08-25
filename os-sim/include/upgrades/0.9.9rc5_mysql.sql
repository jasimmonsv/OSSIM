REPLACE INTO config (conf, value) VALUES ('login_enable_ldap', "no");
REPLACE INTO config (conf, value) VALUES ('login_enforce_existing_user', "yes");
REPLACE INTO config (conf, value) VALUES ('login_ldap_server', "127.0.0.1");
REPLACE INTO config (conf, value) VALUES ('login_ldap_o', "o=company");
REPLACE INTO config (conf, value) VALUES ('login_ldap_ou', "ou=people");

ALTER TABLE host ADD COLUMN lat varchar(255) DEFAULT 0;
ALTER TABLE host ADD COLUMN lon varchar(255) DEFAULT 0;

-- Moved lat/lon to new tables
ALTER TABLE host DROP COLUMN lat;
ALTER TABLE host DROP COLUMN lon;

CREATE TABLE map (
	id INT NOT NULL,
	name VARCHAR(255) NOT NULL,
	engine ENUM('openlayers_op', 'openlayers_ve', 'openlayers_yahoo', 'openlayers_image'),
	engine_data1 TEXT,
	engine_data2 TEXT,
	engine_data3 TEXT,
	engine_data4 TEXT,
	center_x VARCHAR(255),
	center_y VARCHAR(255),
	zoom INT,
	show_controls BOOL DEFAULT 1,
	PRIMARY KEY (id)
);
CREATE TABLE map_seq (
     id INTEGER UNSIGNED NOT NULL
);
INSERT INTO map_seq VALUES (0);

CREATE TABLE map_element (
	id INT NOT NULL,
	type ENUM('host', 'sensor', 'network', 'server'),
	ossim_element_key VARCHAR(255),
	map_id INT NOT NULL REFERENCES map(id),
	x VARCHAR(255),
	y VARCHAR(255),
	PRIMARY KEY (id)
);
CREATE TABLE map_element_seq (
     id INTEGER UNSIGNED NOT NULL
);
INSERT INTO map_element_seq VALUES (0);

--
-- New host group data
--

CREATE TABLE host_group (
  name              varchar(128) UNIQUE NOT NULL,
  threshold_c       int NOT NULL,
  threshold_a       int NOT NULL,
  rrd_profile       varchar(64),
  descr             varchar(255),
  PRIMARY KEY       (name)
);

CREATE TABLE host_group_scan (
  host_group_name               varchar(128) NOT NULL,
  plugin_id       INTEGER NOT NULL,
  plugin_sid      INTEGER NOT NULL,
  PRIMARY KEY (host_group_name, plugin_id, plugin_sid)
);

CREATE TABLE host_group_reference (
  host_group_name        varchar(128) NOT NULL,
  host_ip                varchar(15) NOT NULL,
  PRIMARY KEY     (host_group_name, host_ip)
);

CREATE TABLE host_group_sensor_reference (
    group_name      varchar(128) NOT NULL,
    sensor_name     varchar(64) NOT NULL,
    PRIMARY KEY     (group_name, sensor_name)
);

--
-- New scheduler group data
--

CREATE TABLE plugin_scheduler_netgroup_reference(
    plugin_scheduler_id   INT NOT NULL,
    netgroup_name         VARCHAR(255) NOT NULL,
    PRIMARY KEY           (plugin_scheduler_id, netgroup_name)
);

CREATE TABLE plugin_scheduler_hostgroup_reference(
    plugin_scheduler_id   INT NOT NULL,
    hostgroup_name        VARCHAR(255) NOT NULL,
    PRIMARY KEY           (plugin_scheduler_id, hostgroup_name)
);

CREATE TABLE plugin_scheduler_net_reference(
    plugin_scheduler_id   INT NOT NULL,
    net_name              VARCHAR(255) NOT NULL,
    PRIMARY KEY           (plugin_scheduler_id, net_name)
);

CREATE TABLE plugin_scheduler_host_reference(
    plugin_scheduler_id   INT NOT NULL,
    ip                    varchar(15) NOT NULL,
    PRIMARY KEY           (plugin_scheduler_id, ip)
);

--
--
--

ALTER TABLE user_config MODIFY COLUMN value MEDIUMTEXT;
ALTER TABLE map MODIFY COLUMN engine_data1 MEDIUMTEXT;

-- New log records
REPLACE INTO log_config (code, log, descr, priority) VALUES (046, 0, 'Policy - Policy: new policy added %1% %2%', 2);
REPLACE INTO log_config (code, log, descr, priority) VALUES (047, 1, 'Policy - Policy: policy %1% %2% deleted', 2);
REPLACE INTO log_config (code, log, descr, priority) VALUES (048, 0, 'Policy - Policy: policy %1% %2% modified', 2);

-- configure xajax path
INSERT INTO config (conf, value) VALUES ('xajax_php_path', 'xajax/');
INSERT INTO config (conf, value) VALUES ('xajax_js_path', '../js/');

-- default frameworkd components
INSERT INTO config (conf, value) VALUES ('frameworkd_controlpanelrrd', '1');
INSERT INTO config (conf, value) VALUES ('frameworkd_acidcache', '1');
INSERT INTO config (conf, value) VALUES ('frameworkd_listener', '1');
INSERT INTO config (conf, value) VALUES ('frameworkd_scheduler', '1');
INSERT INTO config (conf, value) VALUES ('frameworkd_soc', '0');
INSERT INTO config (conf, value) VALUES ('frameworkd_businessprocesses', '1');
INSERT INTO config (conf, value) VALUES ('frameworkd_backup', '1');

-- some sids formerly used to be together in the same plugin. now separated
--
--
-- SSH sids
--
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 1, NULL, NULL, 'SSHd: Failed password', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 2, NULL, NULL, 'SSHd: Failed publickey', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 3, NULL, NULL, 'SSHd: Invalid user', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 4, NULL, NULL, 'SSHd: Illegal user', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 5, NULL, NULL, 'SSHd: Root login refused', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 6, NULL, NULL, 'SSHd: User not allowed because listed in DenyUsers', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 7, NULL, NULL, 'SSHd: Login sucessful, Accepted password', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 8, NULL, NULL, 'SSHd: Login sucessful, Accepted publickey', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 9, NULL, NULL, 'SSHd: Bad protocol version identification', 3, 2);

--
-- PAM_unix Sids
--
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 1, NULL, NULL, 'PAM_unix: authentication successful', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 2, NULL, NULL, 'PAM_unix: authentication failure', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 3, NULL, NULL, 'PAM_unix: authentication failure 2 more times', 2, 2);

--
-- sudo sids
--
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 1, NULL, NULL, 'sudo: Failed su ' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 2, NULL, NULL, 'sudo: Successful su' ,1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 3, NULL, NULL, 'sudo: Command executed' ,2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4005, 4, NULL, NULL, 'sudo: User not in sudoers' ,3, 2);

-- new bp member types
REPLACE INTO bp_asset_member_type (type_name) VALUES ('net');
REPLACE INTO bp_asset_member_type (type_name) VALUES ('file');
REPLACE INTO bp_asset_member_type (type_name) VALUES ('host_group');
REPLACE INTO bp_asset_member_type (type_name) VALUES ('net_group');

--new snort preprocessor sids
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1119, 18, NULL, NULL, 'http_inspect: WEBROOT DIRECTORY TRAVERSAL');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1105, 2, NULL, NULL, ' spp_bo: Back Orifice Client Traffic Detected');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1105, 3, NULL, NULL, ' spp_bo: Back Orifice Server Traffic Detected');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1105, 4, NULL, NULL, ' spp_bo: Back Orifice Snort Buffer Attack');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1106, 5, NULL, NULL, ' spp_rpc_decode: Zero-length RPC Fragment');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1111, 21, NULL, NULL, ' spp_stream4: TCP Timestamp option has value of zero');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1111, 22, NULL, NULL, ' spp_stream4: Too many overlapping TCP packets');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1111, 23, NULL, NULL, ' spp_stream4: Packet in established TCP stream missing ACK');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1111, 24, NULL, NULL, ' spp_stream4: Evasive FIN Packet');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1111, 25, NULL, NULL, ' spp_stream4: SYN on established');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 59, NULL, NULL, ' snort_decoder: TCP Window Scale Option Scale Invalid (> 14)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 98, NULL, NULL, ' snort_decoder: Long UDP packet, length field < payload length');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 150, NULL, NULL, ' snort_decoder: Bad Traffic Loopback IP!');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 151, NULL, NULL, ' snort_decoder: Bad Traffic Same Src/Dst IP!');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 160, NULL, NULL, ' snort_decoder: WARNING: GRE header length > payload length');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 161, NULL, NULL, ' snort_decoder: WARNING: Multiple GRE encapsulations in packet');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 250, NULL, NULL, ' snort_decoder: WARNING: ICMP Original IP Header Truncated!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 251, NULL, NULL, ' snort_decoder: WARNING: ICMP Original IP Header Not IPv4!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 252, NULL, NULL, ' snort_decoder: WARNING: ICMP Original Datagram Length < Original IP Header Length!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 253, NULL, NULL, ' snort_decoder: WARNING: ICMP Original IP Payload < 64 bits!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 254, NULL, NULL, ' snort_decoder: WARNING: ICMP Origianl IP Payload > 576 bytes!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 255, NULL, NULL, ' snort_decoder: WARNING: ICMP Original IP Fragmented and Offset Not 0!"');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 270, NULL, NULL, ' snort_decoder: IPV6 packet exceeded TTL limit');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 271, NULL, NULL, ' snort_decoder: IPv6 header claims to not be IPv6');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 272, NULL, NULL, ' snort_decoder: IPV6 truncated extension header');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1116, 273, NULL, NULL, ' snort_decoder: IPV6 truncated header');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 1, NULL, NULL, ' frag3: IP Options on fragmented packet');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 2, NULL, NULL, ' frag3: Teardrop attack');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 3, NULL, NULL, ' frag3: Short fragment, possible DoS attempt');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 4, NULL, NULL, ' frag3: Fragment packet ends after defragmented packet');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 5, NULL, NULL, ' frag3: Zero-byte fragment');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 6, NULL, NULL, ' frag3: Bad fragment size, packet size is negative');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 7, NULL, NULL, ' frag3: Bad fragment size, packet size is greater than 65536');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 8, NULL, NULL, ' frag3: Fragmentation overlap');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 9, NULL, NULL, ' frag3: IPv6 BSD mbufs remote kernel buffer overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1123, 10, NULL, NULL, ' frag3: Bogus fragmentation packet. Possible BSD attack');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 1, NULL, NULL, ' smtp: Attempted command buffer overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 2, NULL, NULL, ' smtp: Attempted data header buffer overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 3, NULL, NULL, ' smtp: Attempted response buffer overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 4, NULL, NULL, ' smtp: Attempted specific command buffer overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 5, NULL, NULL, ' smtp: Unknown command');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1124, 6, NULL, NULL, ' smtp: Illegal command');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 1, NULL, NULL, ' ftp_pp: Telnet command on FTP command channel');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 2, NULL, NULL, ' ftp_pp: Invalid FTP command');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 3, NULL, NULL, ' ftp_pp: FTP parameter length overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 4, NULL, NULL, ' ftp_pp: FTP malformed parameter');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 5, NULL, NULL, ' ftp_pp: Possible string format attempt in FTP command/parameter');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 6, NULL, NULL, ' ftp_pp: FTP response length overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 7, NULL, NULL, ' ftp_pp: FTP command channel encrypted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 8, NULL, NULL, ' ftp_pp: FTP bounce attack');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1125, 9, NULL, NULL, ' ftp_pp: Evasive Telnet command on FTP command channel');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1126, 1, NULL, NULL, ' telnet_pp: Telnet consecutive AYT overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1126, 2, NULL, NULL, ' telnet_pp: Telnet data encrypted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1126, 3, NULL, NULL, ' telnet_pp: Subnegotiation Begin without matching Subnegotiation End');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 1, NULL, NULL, ' ssh: Gobbles exploit ');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 2, NULL, NULL, ' ssh: SSH1 CRC32 exploit ');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 3, NULL, NULL, ' ssh: Server version string overflow');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 4, NULL, NULL, ' ssh: Protocol mismatch');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 5, NULL, NULL, ' ssh: Bad message direction');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 6, NULL, NULL, ' ssh: Payload size incorrect for the given payload');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1128, 7, NULL, NULL, ' ssh: Failed to detect SSH version string');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 1, NULL, NULL, ' stream5: SYN on established session');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 2, NULL, NULL, ' stream5: Data on SYN packet');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 3, NULL, NULL, ' stream5: Data sent on stream not accepting data');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 4, NULL, NULL, ' stream5: TCP Timestamp is outside of PAWS window');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 5, NULL, NULL, ' stream5: Bad segment, overlap adjusted size less than/equal 0');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 6, NULL, NULL, ' stream5: Window size (after scaling) larger than policy allows');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 7, NULL, NULL, ' stream5: Limit on number of overlapping TCP packets reached');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1129, 8, NULL, NULL, ' stream5: Data sent on stream after TCP Reset');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1130, 1, NULL, NULL, ' dcerpc: Maximum memory usage reached');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1131, 1, NULL, NULL, ' dns: Obsolete DNS RData Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1131, 2, NULL, NULL, ' dns: Experimental DNS RData Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1131, 3, NULL, NULL, ' dns: Client RData TXT Overflow');


--new type_scan
ALTER TABLE plugin_scheduler ADD type_scan varchar(255) NOT NULL;

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '0.9.9rc5');
