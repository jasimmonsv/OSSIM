CREATE DATABASE IF NOT EXISTS tmp;
use tmp;

DROP TABLE IF EXISTS `ac_alerts_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alerts_ipdst` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alerts_ipsrc`
--

DROP TABLE IF EXISTS `ac_alerts_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alerts_ipsrc` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alerts_sid`
--

DROP TABLE IF EXISTS `ac_alerts_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alerts_sid` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alerts_signature`
--

DROP TABLE IF EXISTS `ac_alerts_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alerts_signature` (
  `signature` int(10) unsigned NOT NULL,
  `sig_name` varchar(255) NOT NULL default '',
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sig_cnt` int(11) NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`signature`,`sig_name`,`sig_class_id`,`day`),
  KEY `day` (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alertsclas_classid`
--

DROP TABLE IF EXISTS `ac_alertsclas_classid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alertsclas_classid` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alertsclas_ipdst`
--

DROP TABLE IF EXISTS `ac_alertsclas_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alertsclas_ipdst` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alertsclas_ipsrc`
--

DROP TABLE IF EXISTS `ac_alertsclas_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alertsclas_ipsrc` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alertsclas_sid`
--

DROP TABLE IF EXISTS `ac_alertsclas_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alertsclas_sid` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_alertsclas_signature`
--

DROP TABLE IF EXISTS `ac_alertsclas_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_alertsclas_signature` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_dstaddr_ipdst`
--

DROP TABLE IF EXISTS `ac_dstaddr_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_dstaddr_ipdst` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_dstaddr_ipsrc`
--

DROP TABLE IF EXISTS `ac_dstaddr_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_dstaddr_ipsrc` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_dstaddr_sid`
--

DROP TABLE IF EXISTS `ac_dstaddr_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_dstaddr_sid` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_dstaddr_signature`
--

DROP TABLE IF EXISTS `ac_dstaddr_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_dstaddr_signature` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_dport`
--

DROP TABLE IF EXISTS `ac_layer4_dport`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_dport` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_dport_ipdst`
--

DROP TABLE IF EXISTS `ac_layer4_dport_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_dport_ipdst` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_dport_ipsrc`
--

DROP TABLE IF EXISTS `ac_layer4_dport_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_dport_ipsrc` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_dport_sid`
--

DROP TABLE IF EXISTS `ac_layer4_dport_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_dport_sid` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_dport_signature`
--

DROP TABLE IF EXISTS `ac_layer4_dport_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_dport_signature` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_sport`
--

DROP TABLE IF EXISTS `ac_layer4_sport`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_sport` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_sport_ipdst`
--

DROP TABLE IF EXISTS `ac_layer4_sport_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_sport_ipdst` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_sport_ipsrc`
--

DROP TABLE IF EXISTS `ac_layer4_sport_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_sport_ipsrc` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_sport_sid`
--

DROP TABLE IF EXISTS `ac_layer4_sport_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_sport_sid` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_layer4_sport_signature`
--

DROP TABLE IF EXISTS `ac_layer4_sport_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_layer4_sport_signature` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_sensor_ipdst`
--

DROP TABLE IF EXISTS `ac_sensor_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_sensor_ipdst` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_sensor_ipsrc`
--

DROP TABLE IF EXISTS `ac_sensor_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_sensor_ipsrc` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_sensor_sid`
--

DROP TABLE IF EXISTS `ac_sensor_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_sensor_sid` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sid`,`day`),
  KEY `day` (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_sensor_signature`
--

DROP TABLE IF EXISTS `ac_sensor_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_sensor_signature` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_srcaddr_ipdst`
--

DROP TABLE IF EXISTS `ac_srcaddr_ipdst`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_srcaddr_ipdst` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_srcaddr_ipsrc`
--

DROP TABLE IF EXISTS `ac_srcaddr_ipsrc`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_srcaddr_ipsrc` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_srcaddr_sid`
--

DROP TABLE IF EXISTS `ac_srcaddr_sid`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_srcaddr_sid` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ac_srcaddr_signature`
--

DROP TABLE IF EXISTS `ac_srcaddr_signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ac_srcaddr_signature` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `acid_ag`
--

DROP TABLE IF EXISTS `acid_ag`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `acid_ag` (
  `ag_id` int(10) unsigned NOT NULL auto_increment,
  `ag_name` varchar(40) default NULL,
  `ag_desc` text,
  `ag_ctime` datetime default NULL,
  `ag_ltime` datetime default NULL,
  PRIMARY KEY  (`ag_id`),
  KEY `ag_id` (`ag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `acid_ag_alert`
--

DROP TABLE IF EXISTS `acid_ag_alert`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `acid_ag_alert` (
  `ag_id` int(10) unsigned NOT NULL,
  `ag_sid` int(10) unsigned NOT NULL,
  `ag_cid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ag_id`,`ag_sid`,`ag_cid`),
  KEY `ag_id` (`ag_id`),
  KEY `ag_sid` (`ag_sid`,`ag_cid`),
  KEY `ag_sid_2` (`ag_sid`,`ag_cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `acid_event`
--

DROP TABLE IF EXISTS `acid_event`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `acid_event` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  `sig_name` varchar(255) default NULL,
  `sig_class_id` int(10) unsigned default NULL,
  `sig_priority` int(10) unsigned default NULL,
  `timestamp` datetime NOT NULL,
  `ip_src` int(10) unsigned default NULL,
  `ip_dst` int(10) unsigned default NULL,
  `ip_proto` int(11) default NULL,
  `layer4_sport` int(10) unsigned default NULL,
  `layer4_dport` int(10) unsigned default NULL,
  `ossim_type` int(11) default '1',
  `ossim_priority` int(11) default '1',
  `ossim_reliability` int(11) default '1',
  `ossim_asset_src` int(11) default '1',
  `ossim_asset_dst` int(11) default '1',
  `ossim_risk_c` int(11) default '1',
  `ossim_risk_a` int(11) default '1',
  PRIMARY KEY  (`sid`,`cid`,`timestamp`),
  KEY `signature` (`signature`),
  KEY `sig_class_id` (`sig_class_id`),
  KEY `sig_priority` (`sig_priority`),
  KEY `timestamp` (`timestamp`),
  KEY `layer4_sport` (`layer4_sport`),
  KEY `layer4_dport` (`layer4_dport`),
  KEY `acid_event_ossim_type` (`ossim_type`),
  KEY `acid_event_ossim_asset_src` (`ossim_asset_src`),
  KEY `acid_event_ossim_risk_c` (`ossim_risk_c`),
  KEY `cid` (`cid`),
  KEY `ip_src` (`ip_src`,`timestamp`),
  KEY `sig_name` (`sig_name`,`timestamp`),
  KEY `ip_dst` (`ip_dst`,`timestamp`),
  KEY `acid_event_ossim_asset_dst` (`ossim_asset_dst`,`timestamp`),
  KEY `acid_event_ossim_priority` (`ossim_priority`,`timestamp`),
  KEY `acid_event_ossim_reliability` (`ossim_reliability`,`timestamp`),
  KEY `acid_event_ossim_risk_a` (`ossim_risk_a`,`timestamp`),
  KEY `ip_proto` (`ip_proto`,`timestamp`),
  KEY `sid` (`sid`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `acid_ip_cache`
--

DROP TABLE IF EXISTS `acid_ip_cache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `acid_ip_cache` (
  `ipc_ip` int(10) unsigned NOT NULL,
  `ipc_fqdn` varchar(50) default NULL,
  `ipc_dns_timestamp` datetime default NULL,
  `ipc_whois` text,
  `ipc_whois_timestamp` datetime default NULL,
  PRIMARY KEY  (`ipc_ip`),
  KEY `ipc_ip` (`ipc_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `base_roles`
--

DROP TABLE IF EXISTS `base_roles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `base_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(20) NOT NULL,
  `role_desc` varchar(75) NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `base_users`
--

DROP TABLE IF EXISTS `base_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `base_users` (
  `usr_id` int(11) NOT NULL,
  `usr_login` varchar(25) NOT NULL,
  `usr_pwd` varchar(32) NOT NULL,
  `usr_name` varchar(75) NOT NULL,
  `role_id` int(11) NOT NULL,
  `usr_enabled` int(11) NOT NULL,
  PRIMARY KEY  (`usr_id`),
  KEY `usr_login` (`usr_login`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `data`
--

DROP TABLE IF EXISTS `data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `data` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `data_payload` text,
  PRIMARY KEY  (`sid`,`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `deletetmp`
--

DROP TABLE IF EXISTS `deletetmp`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `deletetmp` (
  `id` int(11) NOT NULL,
  `perc` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `detail`
--

DROP TABLE IF EXISTS `detail`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `detail` (
  `detail_type` tinyint(3) unsigned NOT NULL,
  `detail_text` text NOT NULL,
  PRIMARY KEY  (`detail_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `encoding`
--

DROP TABLE IF EXISTS `encoding`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `encoding` (
  `encoding_type` tinyint(3) unsigned NOT NULL,
  `encoding_text` text NOT NULL,
  PRIMARY KEY  (`encoding_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `event` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `sig` (`signature`),
  KEY `time` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `event_stats`
--

DROP TABLE IF EXISTS `event_stats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `event_stats` (
  `timestamp` datetime NOT NULL,
  `sensors` int(10) unsigned NOT NULL,
  `sensors_total` int(10) unsigned NOT NULL,
  `uniq_events` int(10) unsigned NOT NULL,
  `categories` int(10) unsigned NOT NULL,
  `total_events` int(10) unsigned NOT NULL,
  `src_ips` int(10) unsigned NOT NULL,
  `dst_ips` int(10) unsigned NOT NULL,
  `uniq_ip_links` int(10) unsigned NOT NULL,
  `source_ports` int(10) unsigned NOT NULL,
  `dest_ports` int(10) unsigned NOT NULL,
  `source_ports_udp` int(10) unsigned NOT NULL,
  `source_ports_tcp` int(10) unsigned NOT NULL,
  `dest_ports_udp` int(10) unsigned NOT NULL,
  `dest_ports_tcp` int(10) unsigned NOT NULL,
  `tcp_events` int(10) unsigned NOT NULL,
  `udp_events` int(10) unsigned NOT NULL,
  `icmp_events` int(10) unsigned NOT NULL,
  `portscan_events` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`timestamp`),
  KEY `sensors_idx` (`sensors`),
  KEY `sensors_total_idx` (`sensors_total`),
  KEY `uniq_events_idx` (`uniq_events`),
  KEY `categories_idx` (`categories`),
  KEY `total_events_idx` (`total_events`),
  KEY `src_ips_idx` (`src_ips`),
  KEY `dst_ips_idx` (`dst_ips`),
  KEY `uniq_ip_links_idx` (`uniq_ip_links`),
  KEY `source_ports_idx` (`source_ports`),
  KEY `dest_ports_idx` (`dest_ports`),
  KEY `source_ports_udp_idx` (`source_ports_udp`),
  KEY `source_ports_tcp_idx` (`source_ports_tcp`),
  KEY `dest_ports_udp_idx` (`dest_ports_udp`),
  KEY `dest_ports_tcp_idx` (`dest_ports_tcp`),
  KEY `tcp_events_idx` (`tcp_events`),
  KEY `udp_events_idx` (`udp_events`),
  KEY `icmp_events_idx` (`icmp_events`),
  KEY `portscan_events_idx` (`portscan_events`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `extra_data`
--

DROP TABLE IF EXISTS `extra_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `extra_data` (
  `sid` bigint(20) NOT NULL,
  `cid` bigint(20) NOT NULL,
  `filename` varchar(255) default NULL,
  `username` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `userdata1` varchar(255) default NULL,
  `userdata2` varchar(255) default NULL,
  `userdata3` varchar(255) default NULL,
  `userdata4` varchar(255) default NULL,
  `userdata5` varchar(255) default NULL,
  `userdata6` text,
  `userdata7` text,
  `userdata8` text,
  `userdata9` text,
  PRIMARY KEY  (`sid`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `icmphdr`
--

DROP TABLE IF EXISTS `icmphdr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `icmphdr` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `icmp_type` tinyint(3) unsigned NOT NULL,
  `icmp_code` tinyint(3) unsigned NOT NULL,
  `icmp_csum` smallint(5) unsigned default NULL,
  `icmp_id` smallint(5) unsigned default NULL,
  `icmp_seq` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `icmp_type` (`icmp_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `iphdr`
--

DROP TABLE IF EXISTS `iphdr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `iphdr` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  `ip_ver` tinyint(3) unsigned default NULL,
  `ip_hlen` tinyint(3) unsigned default NULL,
  `ip_tos` tinyint(3) unsigned default NULL,
  `ip_len` smallint(5) unsigned default NULL,
  `ip_id` smallint(5) unsigned default NULL,
  `ip_flags` tinyint(3) unsigned default NULL,
  `ip_off` smallint(5) unsigned default NULL,
  `ip_ttl` tinyint(3) unsigned default NULL,
  `ip_proto` tinyint(3) unsigned NOT NULL,
  `ip_csum` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `ip_src` (`ip_src`),
  KEY `ip_dst` (`ip_dst`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `opt`
--

DROP TABLE IF EXISTS `opt`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `opt` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `optid` int(10) unsigned NOT NULL,
  `opt_proto` tinyint(3) unsigned NOT NULL,
  `opt_code` tinyint(3) unsigned NOT NULL,
  `opt_len` smallint(6) default NULL,
  `opt_data` text,
  PRIMARY KEY  (`sid`,`cid`,`optid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ossim_event`
--

DROP TABLE IF EXISTS `ossim_event`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ossim_event` (
  `sid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `priority` int(11) default '1',
  `reliability` int(11) default '1',
  `asset_src` int(11) default '1',
  `asset_dst` int(11) default '1',
  `risk_c` int(11) default '1',
  `risk_a` int(11) default '1',
  `plugin_id` int(11) NOT NULL,
  `plugin_sid` int(11) NOT NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `type` (`type`),
  KEY `priority` (`priority`),
  KEY `reliability` (`reliability`),
  KEY `asset_src` (`asset_src`),
  KEY `asset_dst` (`asset_dst`),
  KEY `risk_c` (`risk_c`),
  KEY `risk_a` (`risk_a`),
  KEY `plugin_id` (`plugin_id`),
  KEY `plugin_sid` (`plugin_sid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `reference`
--

DROP TABLE IF EXISTS `reference`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `reference` (
  `ref_id` int(10) unsigned NOT NULL auto_increment,
  `ref_system_id` int(10) unsigned NOT NULL,
  `ref_tag` text NOT NULL,
  PRIMARY KEY  (`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `reference_system`
--

DROP TABLE IF EXISTS `reference_system`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `reference_system` (
  `ref_system_id` int(10) unsigned NOT NULL auto_increment,
  `ref_system_name` varchar(20) default NULL,
  PRIMARY KEY  (`ref_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `schema`
--

DROP TABLE IF EXISTS `schema`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `schema` (
  `vseq` int(10) unsigned NOT NULL,
  `ctime` datetime NOT NULL,
  PRIMARY KEY  (`vseq`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `sensor`
--

DROP TABLE IF EXISTS `sensor`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `sensor` (
  `sid` int(10) unsigned NOT NULL auto_increment,
  `hostname` text,
  `interface` text,
  `filter` text,
  `detail` tinyint(4) default NULL,
  `encoding` tinyint(4) default NULL,
  `last_cid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `sig_class`
--

DROP TABLE IF EXISTS `sig_class`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `sig_class` (
  `sig_class_id` int(10) unsigned NOT NULL auto_increment,
  `sig_class_name` varchar(60) NOT NULL,
  PRIMARY KEY  (`sig_class_id`),
  KEY `sig_class_id` (`sig_class_id`),
  KEY `sig_class_name` (`sig_class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `sig_reference`
--

DROP TABLE IF EXISTS `sig_reference`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `sig_reference` (
  `sig_id` int(10) unsigned NOT NULL,
  `ref_seq` int(10) unsigned NOT NULL,
  `ref_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_id`,`ref_seq`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `signature`
--

DROP TABLE IF EXISTS `signature`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `signature` (
  `sig_id` int(10) unsigned NOT NULL auto_increment,
  `sig_name` varchar(255) NOT NULL,
  `sig_class_id` int(10) unsigned NOT NULL,
  `sig_priority` int(10) unsigned default NULL,
  `sig_rev` int(10) unsigned default NULL,
  `sig_sid` int(10) unsigned default NULL,
  PRIMARY KEY  (`sig_id`),
  KEY `sign_idx` (`sig_name`(20)),
  KEY `sig_class_id_idx` (`sig_class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=335 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `tcphdr`
--

DROP TABLE IF EXISTS `tcphdr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tcphdr` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `tcp_sport` smallint(5) unsigned NOT NULL,
  `tcp_dport` smallint(5) unsigned NOT NULL,
  `tcp_seq` int(10) unsigned default NULL,
  `tcp_ack` int(10) unsigned default NULL,
  `tcp_off` tinyint(3) unsigned default NULL,
  `tcp_res` tinyint(3) unsigned default NULL,
  `tcp_flags` tinyint(3) unsigned NOT NULL,
  `tcp_win` smallint(5) unsigned default NULL,
  `tcp_csum` smallint(5) unsigned default NULL,
  `tcp_urp` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `tcp_sport` (`tcp_sport`),
  KEY `tcp_dport` (`tcp_dport`),
  KEY `tcp_flags` (`tcp_flags`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `udphdr`
--

DROP TABLE IF EXISTS `udphdr`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `udphdr` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `udp_sport` smallint(5) unsigned NOT NULL,
  `udp_dport` smallint(5) unsigned NOT NULL,
  `udp_len` smallint(5) unsigned default NULL,
  `udp_csum` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`sid`,`cid`),
  KEY `udp_sport` (`udp_sport`),
  KEY `udp_dport` (`udp_dport`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;