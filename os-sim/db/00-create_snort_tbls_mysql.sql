# Copyright (C) 2000-2002 Carnegie Mellon University
#
# Maintainer: Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
#
# Original Author(s): Jed Pickel <jed@pickel.net>    (2000-2001)
#                     Roman Danyliw <rdd@cert.org>
#                     Todd Schrubb <tls@cert.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

DROP TABLE IF EXISTS `acid_ag`;
CREATE TABLE IF NOT EXISTS `acid_ag` (
  `ag_id` int(10) unsigned NOT NULL auto_increment,
  `ag_name` varchar(40) default NULL,
  `ag_desc` text,
  `ag_ctime` datetime default NULL,
  `ag_ltime` datetime default NULL,
  PRIMARY KEY  (`ag_id`),
  KEY `ag_id` (`ag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acid_ag_alert`
--

DROP TABLE IF EXISTS `acid_ag_alert`;
CREATE TABLE IF NOT EXISTS `acid_ag_alert` (
  `ag_id` int(10) unsigned NOT NULL,
  `ag_sid` int(10) unsigned NOT NULL,
  `ag_cid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ag_id`,`ag_sid`,`ag_cid`),
  KEY `ag_id` (`ag_id`),
  KEY `ag_sid` (`ag_sid`,`ag_cid`),
  KEY `ag_sid_2` (`ag_sid`,`ag_cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acid_event`
--

DROP TABLE IF EXISTS `acid_event`;
CREATE TABLE IF NOT EXISTS `acid_event` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `timezone` TINYINT(1) NOT NULL DEFAULT '0',
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
  `plugin_id` int(11) default NULL,
  `plugin_sid` int(11) default NULL,
  PRIMARY KEY  (`sid`,`cid`,`timestamp`),
  KEY `timestamp` (`timestamp`),
  KEY `layer4_sport` (`layer4_sport`),
  KEY `layer4_dport` (`layer4_dport`),
  KEY `ip_src` (`ip_src`,`timestamp`),
  KEY `ip_dst` (`ip_dst`,`timestamp`),
  KEY `acid_event_ossim_priority` (`ossim_priority`,`timestamp`),
  KEY `acid_event_ossim_risk_a` (`ossim_risk_a`,`timestamp`),
  KEY `acid_event_ossim_reliability` (`ossim_reliability`,`timestamp`),
  KEY `acid_event_ossim_risk_c` (`ossim_risk_c`,`timestamp`),
  KEY `sig_name` (`plugin_id`,`plugin_sid`,`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acid_event_input`
--

DROP TABLE IF EXISTS `acid_event_input`;
CREATE TABLE IF NOT EXISTS `acid_event_input` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
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
  `plugin_id` int(11) default NULL,
  `plugin_sid` int(11) default NULL,
  PRIMARY KEY  (`sid`,`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acid_ip_cache`
--

DROP TABLE IF EXISTS `acid_ip_cache`;
CREATE TABLE IF NOT EXISTS `acid_ip_cache` (
  `ipc_ip` int(10) unsigned NOT NULL,
  `ipc_fqdn` varchar(50) default NULL,
  `ipc_dns_timestamp` datetime default NULL,
  `ipc_whois` text,
  `ipc_whois_timestamp` datetime default NULL,
  PRIMARY KEY  (`ipc_ip`),
  KEY `ipc_ip` (`ipc_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_alerts_ipdst`
--

DROP TABLE IF EXISTS `ac_alerts_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_alerts_ipdst` (
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`plugin_id`,`plugin_sid`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_alerts_ipsrc`
--

DROP TABLE IF EXISTS `ac_alerts_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_alerts_ipsrc` (
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`plugin_id`,`plugin_sid`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_alerts_sid`
--

DROP TABLE IF EXISTS `ac_alerts_sid`;
CREATE TABLE IF NOT EXISTS `ac_alerts_sid` (
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`plugin_id`,`plugin_sid`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_alerts_signature`
--

DROP TABLE IF EXISTS `ac_alerts_signature`;
CREATE TABLE IF NOT EXISTS `ac_alerts_signature` (
  `day` date NOT NULL,
  `sig_cnt` int(11) NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`plugin_id`,`plugin_sid`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_dstaddr_ipdst`
--

DROP TABLE IF EXISTS `ac_dstaddr_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_dstaddr_ipdst` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_dstaddr_ipsrc`
--

DROP TABLE IF EXISTS `ac_dstaddr_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_dstaddr_ipsrc` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_dstaddr_sid`
--

DROP TABLE IF EXISTS `ac_dstaddr_sid`;
CREATE TABLE IF NOT EXISTS `ac_dstaddr_sid` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_dstaddr_signature`
--

DROP TABLE IF EXISTS `ac_dstaddr_signature`;
CREATE TABLE IF NOT EXISTS `ac_dstaddr_signature` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ip_dst`,`day`,`plugin_id`,`plugin_sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_dport`
--

DROP TABLE IF EXISTS `ac_layer4_dport`;
CREATE TABLE IF NOT EXISTS `ac_layer4_dport` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_dport_ipdst`
--

DROP TABLE IF EXISTS `ac_layer4_dport_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_layer4_dport_ipdst` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_dport_ipsrc`
--

DROP TABLE IF EXISTS `ac_layer4_dport_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_layer4_dport_ipsrc` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_dport_sid`
--

DROP TABLE IF EXISTS `ac_layer4_dport_sid`;
CREATE TABLE IF NOT EXISTS `ac_layer4_dport_sid` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_dport_signature`
--

DROP TABLE IF EXISTS `ac_layer4_dport_signature`;
CREATE TABLE IF NOT EXISTS `ac_layer4_dport_signature` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`plugin_id`,`plugin_sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_sport`
--

DROP TABLE IF EXISTS `ac_layer4_sport`;
CREATE TABLE IF NOT EXISTS `ac_layer4_sport` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_sport_ipdst`
--

DROP TABLE IF EXISTS `ac_layer4_sport_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_layer4_sport_ipdst` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_sport_ipsrc`
--

DROP TABLE IF EXISTS `ac_layer4_sport_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_layer4_sport_ipsrc` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_sport_sid`
--

DROP TABLE IF EXISTS `ac_layer4_sport_sid`;
CREATE TABLE IF NOT EXISTS `ac_layer4_sport_sid` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_layer4_sport_signature`
--

DROP TABLE IF EXISTS `ac_layer4_sport_signature`;
CREATE TABLE IF NOT EXISTS `ac_layer4_sport_signature` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`plugin_id`,`plugin_sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_sensor_ipdst`
--

DROP TABLE IF EXISTS `ac_sensor_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_sensor_ipdst` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_sensor_ipsrc`
--

DROP TABLE IF EXISTS `ac_sensor_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_sensor_ipsrc` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_sensor_sid`
--

DROP TABLE IF EXISTS `ac_sensor_sid`;
CREATE TABLE IF NOT EXISTS `ac_sensor_sid` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sid`,`day`),
  KEY `day` (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_sensor_signature`
--

DROP TABLE IF EXISTS `ac_sensor_signature`;
CREATE TABLE IF NOT EXISTS `ac_sensor_signature` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`sid`,`day`,`plugin_id`,`plugin_sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_srcaddr_ipdst`
--

DROP TABLE IF EXISTS `ac_srcaddr_ipdst`;
CREATE TABLE IF NOT EXISTS `ac_srcaddr_ipdst` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_srcaddr_ipsrc`
--

DROP TABLE IF EXISTS `ac_srcaddr_ipsrc`;
CREATE TABLE IF NOT EXISTS `ac_srcaddr_ipsrc` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_srcaddr_sid`
--

DROP TABLE IF EXISTS `ac_srcaddr_sid`;
CREATE TABLE IF NOT EXISTS `ac_srcaddr_sid` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ac_srcaddr_signature`
--

DROP TABLE IF EXISTS `ac_srcaddr_signature`;
CREATE TABLE IF NOT EXISTS `ac_srcaddr_signature` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `plugin_id` int(11) NOT NULL default '0',
  `plugin_sid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ip_src`,`day`,`plugin_id`,`plugin_sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `base_roles`
--

DROP TABLE IF EXISTS `base_roles`;
CREATE TABLE IF NOT EXISTS `base_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(20) NOT NULL,
  `role_desc` varchar(75) NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `base_roles` (`role_id`, `role_name`, `role_desc`) VALUES (1, 'Admin', 'Administrator'),
(10, 'User', 'Authenticated User'),
(10000, 'Anonymous', 'Anonymous User'),
(50, 'ag_editor', 'Alert Group Editor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `base_users`
--

DROP TABLE IF EXISTS `base_users`;
CREATE TABLE IF NOT EXISTS `base_users` (
  `usr_id` int(11) NOT NULL,
  `usr_login` varchar(25) NOT NULL,
  `usr_pwd` varchar(32) NOT NULL,
  `usr_name` varchar(75) NOT NULL,
  `role_id` int(11) NOT NULL,
  `usr_enabled` int(11) NOT NULL,
  PRIMARY KEY  (`usr_id`),
  KEY `usr_login` (`usr_login`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deletetmp`
--

DROP TABLE IF EXISTS `deletetmp`;
CREATE TABLE IF NOT EXISTS `deletetmp` (
  `id` int(11) NOT NULL,
  `perc` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detail`
--

DROP TABLE IF EXISTS `detail`;
CREATE TABLE IF NOT EXISTS `detail` (
  `detail_type` tinyint(3) unsigned NOT NULL,
  `detail_text` text NOT NULL,
  PRIMARY KEY  (`detail_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO detail (detail_type, detail_text) VALUES (0, 'fast');
INSERT INTO detail (detail_type, detail_text) VALUES (1, 'full');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encoding`
--

DROP TABLE IF EXISTS `encoding`;
CREATE TABLE IF NOT EXISTS `encoding` (
  `encoding_type` tinyint(3) unsigned NOT NULL,
  `encoding_text` text NOT NULL,
  PRIMARY KEY  (`encoding_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO encoding (encoding_type, encoding_text) VALUES (0, 'hex');
INSERT INTO encoding (encoding_type, encoding_text) VALUES (1, 'base64');
INSERT INTO encoding (encoding_type, encoding_text) VALUES (2, 'ascii');
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `event_stats`
--

DROP TABLE IF EXISTS `event_stats`;
CREATE TABLE IF NOT EXISTS `event_stats` (
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `extra_data`
--

DROP TABLE IF EXISTS `extra_data`;
CREATE TABLE IF NOT EXISTS `extra_data` (
  `sid` bigint(20) NOT NULL,
  `cid` bigint(20) NOT NULL,
  `filename` text,
  `username` text,
  `password` text,
  `userdata1` text,
  `userdata2` text,
  `userdata3` text,
  `userdata4` text,
  `userdata5` text,
  `userdata6` text,
  `userdata7` text,
  `userdata8` text,
  `userdata9` text,
  `data_payload` text,
  PRIMARY KEY  (`sid`,`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `icmphdr`
--

DROP TABLE IF EXISTS `icmphdr`;
CREATE TABLE IF NOT EXISTS `icmphdr` (
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iphdr`
--

DROP TABLE IF EXISTS `iphdr`;
CREATE TABLE IF NOT EXISTS `iphdr` (
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `last_update`
--

DROP TABLE IF EXISTS `last_update`;
CREATE TABLE IF NOT EXISTS `last_update` (
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO last_update VALUES ('1970-01-01 00:00:00');
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opt`
--

DROP TABLE IF EXISTS `opt`;
CREATE TABLE IF NOT EXISTS `opt` (
  `sid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `optid` int(10) unsigned NOT NULL,
  `opt_proto` tinyint(3) unsigned NOT NULL,
  `opt_code` tinyint(3) unsigned NOT NULL,
  `opt_len` smallint(6) default NULL,
  `opt_data` text,
  PRIMARY KEY  (`sid`,`cid`,`optid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reference`
--

DROP TABLE IF EXISTS `reference`;
CREATE TABLE IF NOT EXISTS `reference` (
  `ref_id` int(10) unsigned NOT NULL auto_increment,
  `ref_system_id` int(10) unsigned NOT NULL,
  `ref_tag` text NOT NULL,
  PRIMARY KEY  (`ref_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15609 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reference_system`
--

DROP TABLE IF EXISTS `reference_system`;
CREATE TABLE IF NOT EXISTS `reference_system` (
  `ref_system_id` int(10) unsigned NOT NULL auto_increment,
  `ref_system_name` varchar(20) default NULL,
  `icon` mediumblob NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY  (`ref_system_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12;

INSERT INTO `reference_system` (`ref_system_id`, `ref_system_name`, `icon`, `url`) VALUES
(1, ' url', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001f94944415478da6cc9b10d00210c04c1a573faa1337274f2fb644301bfc9043be6dacd4fae22b308fb0972a33091465f73de93cc15402c20c5e6aa9c485aff0335ff67f8fb1742ff01d2bf819a7f830d04b181f4df7f0cbf80f4ac7d2f1900028805a6edd3b73f605b7f011583f0cf5f7f197e00157dfff917682390fefd176cf38f5f7fc07c6d196e86ef5ffe300004101348f3bfffff19c22c0580b631806d4c731601daf29fa1d45792a12e4486a13d4a8e01e813b0cd0bb3d5c0f437a0c1ffbeff6600082026b01f8136810dfa07713608809c0c0215cb1e31e4cfbfcf30255911ec1570f8000dff0e0c837f5fff30000410d35760c0fc801a0092f8fb17c206861518fc0285c15fa8a13039a0253f815efafff52f034000b17cf90ef22fd4807f20c50c288afb62e5c174cce45b406f40c4fe025df20318a0ff7efe62000820968fdf7f0103869361f6ded70c19aea26005cd6b9fc26dcd987d171c684b72d518fcbbae81c576d7ea8269e1857719000288d1b9e5da7f5f434106561626a0b3fe014d0686383006beff84d2503e481c260f0a33717e56867dcbee33000410cba7cf7fc00a3e02a3f1d75f48e201f911e45c505a6004a60b5666460626208b8d9999818b0d28f29f1988215e05082046e5bcb3ff7f0053d47fb09ffe31fcff05620369603431009dfe1f6828980f8a0168cc2003800003004e8d5c22daf1b2240000000049454e44ae426082, 'http://%value%'),
(2, ' arachnids', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001b84944415478da6c88c90d00200cc3c260ecffed38bd03f48d25cb9297c8263e90394624aa0877c2aca1daf771fa3c028805a49893530d592b50e33f20fd1748ff65e0e0f80316fbf7ef27980f12fff7ef3790fd9b61fffea70c0001c402d3f6f7ef57a8a6df6085100dbfc03408836c87b90244cbc8f0825d0010404c305b35359703f12ab06610d6d5ddc9a0a7b79fc1c0e018c39f3f20173030d8db5f005ac400f602481ce4358000620299aea63697e1faf530866bd70219b4b53781fd0e02172e58319c3b67cc6066760e6c0808787a5e021af61fea6a0606800062fafdfb3734d0fe42fdc800f6062c20611a41b682c0faf53a0cc1c157a106fc6700082096efdfff81395a5a6b9162006290a1e129307df0a00158314cd3f2e55a0c9191d7c086020410d3972f10c557affa315cbeec8e62c0c9934660cd20bfc35c007211cc30905700028871f972cdffdada320c8c8cac2821fff7ef1f78bc8342fdd72f48fc83c440b490100bc3d1a39f18000288e5eb575094fd006afc02f6f35f60c8806c00d904f33f333303303d3031b0b0fc676063634449700001c4f2f9f35f86eddb9fc0fdf7f72f039c0d8a3a64e7fec7926601020c0006913514cb5b8c3b0000000049454e44ae426082, 'http://www.whitehats.com/info/ids%value%'),
(3, 'mcafee', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001ba4944415478da627c9c9efe9f010bf8fff72fc3ff9f3f19fefff80166fffbfe1dccfef7ed1b58fc0f90fdeddf3f068000620129e6343646e8040a823430800cf8fd9be1ff9f3f60f63f9061403e03d46010ffe9ae5d0c0001c402d7f7f123c256a0c27f50dbc136036dfd076343690e2d2d862f40cb00028809a459383595e13fc866a06d202cd1dcccc000b2fdd72f06d9d9b319e4962e85c801c594f7ed63503d7e9ce1dfd7af0c7fffff670008208687f1f1ff61e04563239cfd3833134c3f8c8afa7f3f28e8ff5d777730ff96b9f9ff9b060660f63279f9ff0001c404721e08bc6c696110afab63785e5101f113281c4081097509d8ff203e12fb0fd0050001c4f2f7cb178804d089cf2b2b110a916858e08180dab973f0f0fe093400208058408107b3f13f34e4611a1f84843028ac5f0fe6dfb6b404d3378181f713185e7a376e3080e21f2080186f686afee7f5f46460646383873028c4e16c50bcc3620068e85fa0174006f0f0f333ec7bfe9c012080583e036de50686e8ff4f9f205108f22334c461ae6164656560626404bb920928c68a94e0000288e53d50109420fe817c01f4132860fe4303082606a24162fffe63265a800003003780620a3cb35f280000000049454e44ae426082, 'http://vil.nai.com/vil/content/v_%value%.htm'),
(4, ' nessus', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001c54944415478da6cc6c90d00200c0341d33f15d104e5400e1bc29f9546da36fa3cf8240af412e5bd09e9446c4229f84aa4115700b180148bab09c035feffff1fa8f93fc3ff7fffc10a416c3006b2ff42f92043ff01f1e53df71800028805a6f1e7d7df0cff8192205b608a406c902d205b416c982b4062228a7c0c3ffe7c63000820b0017abe0a0ca796dd023bd52a5193e1f0acab0cf6993a70576dae3f05363cb8db0a21d6708ae1dfff7f0c0001c402320d04cca2d4188ecdbf0e66836c07819d5de7c0b6830c0e6cb76458997f181e067c125c4003fe32000410dc80e30b6f806d071bf00762807b9911985e5f719c6155e11186f089b660fefcb83d606ffcfdf797012080587e7dfb03093c60a0819c6e9ba60d762e086c693a0d5108341024b638753f989db8c805e29a7fbf18000288051478906883843698fd0f42fbd4998269b0ed936ce1fe9f1dbe93815b8403642d03400031ce8dd9f55fd15c8281858d89e137c83660dc836c8585363816a034c802b08b8061c425c0ce70fdd655068000027b0124f8ebdb6f48c281c53d88fe037109330b1303131323581cc446060001c4f2fdfb778673bbae81a3048221810a0a20607202f341e220e78212193a0008300051d0452435ed69ff0000000049454e44ae426082, 'http://www.nessus.org/plugins/index.php?view=single&id=%value%'),
(5, ' cve', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001c84944415478da62bc7d70c27f062ce0df9fdf0cfffefe02d23f18fefffbc3f0f7f70f20fe0ec6fffefc04d37f7e7d650008201690627e290384ceffff18feffff0ba4fe823582e87f7f218681f860f69f5f60fead93ab1800028805a6efcfcf4f100560c9df105b80b6fffd05b3f53bdc1520395e714d866f3ffe3200041013c456888d52ba810c3286e16083142c92a12ef8c3a0e95e0b96d70be863300c9dce601c390f68f05786df7ffe310004100bc806908d52ba010c4fceaf04db02e223c201ca063a1904ce2c4b00bb804b489ee1cfbfff0c0001c4f2f7f757b000c4fb7fa1feff03d704b2fdf2a652b84126510bc0f4a50d850c7ffffe67000820963f3fbf82fd0ad1f01b1e501003ffc06918fbe4c270703871f04930fc027a012080987efff80876c1a3334b18e4cde219142dd3c08a6fec6e05da5e07b4a90812b07f205e308f5fc96099bc1e6211d00b0001c4787c5ed07f710d7706266636a8ff7f82431e14ffb07887e01fd074f1134cb3f388329c3e7b99012080587efff80456f0fbfb27485cfffd85e1154626160666564e0626163686ffac1c28090e2080583e7ff9cef0ead00a60e03130fc053a091435a0a4090aa07f4041100de2839cfb1f4b9a05083000a6965f501be521170000000049454e44ae426082, 'http://cve.mitre.org/cgi-bin/cvename.cgi?name=%value%'),
(6, ' bugtraq', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d390000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001da49444154789c6cc8c10d00200cc340b33a43b00aab41db0424def875729ba31f3ed9c619488525ce73a2d8ef692f54c915402c20c5fc528a708dffffff67f8ffef2f84066904b181f4bfff7f811afe40c480f4bfff7f18ee9f3bca0010402c308d7f7e7c072b06d902b2f11fd0741006d9f2076a1bdc15409a4f5c96e1fbafdf0c00010436c0d23f06ee827d8b26010dfac7e09a5ccab0637a33d0c63f0c7e45ed0ceb3b0a81b6fe6308ad9b0e577b2dce990120805840a681c0c1a5d3c0ce748acb63d835a7131e0e206783d9403aa4660ac38a9a24701870098802e5ff33000410cb1fa801f6d159607af7dc1eb03fc19a8072ff81b682c3e61f8406792fba7d1198dd11edc80010404c7f817e0781fd4b2633ec99df07747a095c133826a02ef8cff0176ed0e2920830fbd7df7f0c0001c4f2fb27c400c7985c30bd73661b58d1e6fe2ab0df41604d533630e07e332cad8c6388ee5c8c883120060820c6452511ffc5d50c1898585819fefdfa05b41512ca7f40b1008af33f103e28fe4106fffdfd13e88d3f0cecdc7c0cb7eede63000820165014fdfb03d4f0eb0724e140e31d14fab0b06062666660e4e000f2ff81d9c8002080587efcf8c170e3d421600cfc07872a0883fd0f4a4840fa2f480cca06060e468a05083000c1653dc36e8579c10000000049454e44ae426082, 'http://www.securityfocus.com/bid/%value%'),
(7, 'can', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001cb4944415478da62bc7d70c27f062ce0df9fdf0cfffefe02d23f18fefffbc3f0f7f70f20fe0ec6fffefc04d37f7e7d650008201690627e290384ceffff18feffff0ba4fe823582e87f7f218681f860f69f5f60fead93ab1800028805a6efcfcf4f100560c9df105b80b6fffd05b3f53bdc1520395e714d866f3ffe3200041013c4d6bf0c92dabe0c52ba81709b152c9219946db2a1aef8cda0eddd0665ff61308e9c0734f82bc3ef3fff18000288096483a8aa13c3d38b6b199e9c5f09b65dde2c81e1cea1490cb7f6f530a8bb54803581807ed024b03c08805cf7e7df7f06800062fafbfb2bc411307f03694850fc05fb131ca0504d6757243398442d800632d08b7fff33000410d39f9f5f9134fc86dba6ea50c8a0e15a0595fb03a74f2e0c87b800181ebf805e000820a6df3f3e323c3ab39841d62812e8f478b86d3776b7325cdd5a0db5ed173c6a910dfb07f4024000311e9f17f45f5cc39d8189990d6ceabfbf3fc1210f8a7f58bc43f00f68baf809a6d97944194e9fbdcc0010402cbf7f7c022bf8fdfd1324aefffe827b05e61a4626160666564e0626163686ffac1c28090e2080583e7ff9cef0ead00a60e001fd057412286a4049131440ff8082201ac40739f73f96340b106000a0db5762f75048760000000049454e44ae426082, 'http://cve.mitre.org/cgi-bin/cvename.cgi?name=%value%'),
(11, 'kdb', 0x89504e470d0a1a0a0000000d49484452000000100000000b080600000076e20d39000000097048597300000b1300000b1301009a9c180000000467414d410000b18e7cfb5193000000206348524d00007a25000080830000f9ff000080e9000075300000ea6000003a980000176f925fc546000001d94944415478da6cc6c11100200803c1d8b87d5894f53004028ebebdcfde987b353ea90b54c02ba0d2d345583a783f0959e008201690626d0125b8c6ff4008d20cc27ffefd81b3419afe000d82190ce2efbf749401208058601a3ffffe0694fc0b94f803d608a24136826c06d90a6383e81f40acc227c3f0e7eb2f068000021b10a6e4c230e7e646b0e9395aa10cbd97973294e9c5c25d957fa20f2c37dba60a2e560014fbffef3f034000b1fc043a07e6e74ccd60b0e63f40978040cdd91960db275a1431241f6e018b85eead043b7fa36b0f83c5b13006800062f9fef72758225d239061d2d5950ca01005f91504407e851906135beddc0ea64106fefffb8f01208098befdf90116987c6d15439e7638c36fa0df4118ac09a8f90f940d722108f8ed2e61f0d891cf30d7b686e1dfefbf0c0001c4020a3cb002a00d5d97163354eac733d49e9d0916eb332f00d3f1071bc1ce06814d40a78340e4fe1a701800041023d0b4ff7612860c6c4c2c0c3fc1f1fe0b4cfff8fb134e8342fd27d43bbf80f1ffefd75f063e1e5e8607c7ae33000410cb9faf3fc18abefef90e8fdf7fffff83bd01f33f2bd070264626b037401631b021121c4000b1fcfaf49d61efc98360e78031306060342844fffdfd0ba641620cff31132d408001004d1842eca08c02e60000000049454e44ae426082, '../repository/index.php?hmenu=Repository&smenu=Repository&id_document=%value%');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schema`
--

DROP TABLE IF EXISTS `schema`;
CREATE TABLE IF NOT EXISTS `schema` (
  `vseq` int(10) unsigned NOT NULL,
  `ctime` datetime NOT NULL,
  PRIMARY KEY  (`vseq`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `schema`  (vseq, ctime) VALUES ('200', now());

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sensor`
--

DROP TABLE IF EXISTS `sensor`;
CREATE TABLE IF NOT EXISTS `sensor` (
  `sid` int(10) unsigned NOT NULL auto_increment,
  `hostname` text,
  `interface` text,
  `filter` text,
  `detail` tinyint(4) default NULL,
  `encoding` tinyint(4) default NULL,
  `last_cid` int(10) unsigned NOT NULL,
  `sensor` text NOT NULL,
  PRIMARY KEY  (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=206 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sig_class`
--

DROP TABLE IF EXISTS `sig_class`;
CREATE TABLE IF NOT EXISTS `sig_class` (
  `sig_class_id` int(10) unsigned NOT NULL auto_increment,
  `sig_class_name` varchar(60) NOT NULL,
  PRIMARY KEY  (`sig_class_id`),
  KEY `sig_class_id` (`sig_class_id`),
  KEY `sig_class_name` (`sig_class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sig_reference`
--

DROP TABLE IF EXISTS `sig_reference`;
CREATE TABLE IF NOT EXISTS `sig_reference` (
  `plugin_id` int(11) NOT NULL,
  `plugin_sid` int(11) NOT NULL,
  `ref_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`plugin_id`,`plugin_sid`,`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tcphdr`
--

DROP TABLE IF EXISTS `tcphdr`;
CREATE TABLE IF NOT EXISTS `tcphdr` (
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
  KEY `tcp_flags` (`tcp_flags`),
  KEY `tcp_sport_2` (`tcp_sport`),
  KEY `tcp_dport_2` (`tcp_dport`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `udphdr`
--

DROP TABLE IF EXISTS `udphdr`;
CREATE TABLE IF NOT EXISTS `udphdr` (
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
