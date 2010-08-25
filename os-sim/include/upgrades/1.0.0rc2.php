<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* - start_upgrade()
* Classes list:
* - upgrade_100rc2 extends upgrade_base
*/
require_once 'classes/Upgrade_base.inc';
require_once 'classes/NagiosConfigs.inc';
/*
*/
class upgrade_100rc2 extends upgrade_base {
    function start_upgrade() {
        $conn = & $this->conn;
        $snort = & $this->snort;
        /* Snort table changes */
        $sql = "
CREATE TABLE IF NOT EXISTS `ac_alerts_ipdst` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "
CREATE TABLE IF NOT EXISTS `ac_alerts_ipsrc` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alerts_sid` (
  `signature` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`signature`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alerts_signature` (
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
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alertsclas_classid` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alertsclas_ipdst` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alertsclas_ipsrc` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alertsclas_sid` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_alertsclas_signature` (
  `sig_class_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sig_class_id`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_dstaddr_ipdst` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_dstaddr_ipsrc` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_dstaddr_sid` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_dstaddr_signature` (
  `ip_dst` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_dst`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_dport` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_dport_ipdst` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_dport_ipsrc` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_dport_sid` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_dport_signature` (
  `layer4_dport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_dport`,`ip_proto`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_sport` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_sport_ipdst` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_sport_ipsrc` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_sport_sid` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_layer4_sport_signature` (
  `layer4_sport` int(10) unsigned NOT NULL,
  `ip_proto` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`layer4_sport`,`ip_proto`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_sensor_ipdst` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_sensor_ipsrc` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_src` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`ip_src`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_sensor_sid` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `first_timestamp` datetime NOT NULL,
  `last_timestamp` datetime NOT NULL,
  PRIMARY KEY  (`sid`,`day`),
  KEY `day` (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_sensor_signature` (
  `sid` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`sid`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_srcaddr_ipdst` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `ip_dst` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`ip_dst`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_srcaddr_ipsrc` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `cid` int(11) NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_srcaddr_sid` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
	";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "


CREATE TABLE IF NOT EXISTS `ac_srcaddr_signature` (
  `ip_src` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `signature` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ip_src`,`day`,`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "alter table extra_data change userdata6 userdata6 TEXT after userdata5;";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "alter table extra_data change userdata7 userdata7 TEXT after userdata6;";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "alter table extra_data change userdata8 userdata8 TEXT after userdata7;";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $sql = "alter table extra_data change userdata9 userdata9 TEXT after userdata8;";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        return true;
    }
}
?>
