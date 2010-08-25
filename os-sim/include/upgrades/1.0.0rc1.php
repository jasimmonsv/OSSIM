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
* - nagios_check()
* - start_upgrade()
* Classes list:
* - upgrade_100rc1 extends upgrade_base
*/
require_once 'classes/Upgrade_base.inc';
require_once 'classes/NagiosConfigs.inc';
/*
*/
class upgrade_100rc1 extends upgrade_base {
    function nagios_check() {
        $conn = & $this->conn;
        $sql = "desc host_services;";
        $res = $conn->Execute($sql);
        $sql = "show columns from host_services where Field like 'nagios';";
        $res = $conn->Execute($sql);
        if ($res->EOF) {
            echo "Creating nagios flag for host_services<br>";
            $sql = "ALTER TABLE `host_services` ADD COLUMN `nagios` boolean  NOT NULL DEFAULT 1 AFTER `anom`;";
            $res = $conn->Execute($sql);
            if (!$res) {
                echo "Nagios flag was NOT created!";
                return true;
            }
        } else echo "Nagios flag already created.. ok<br>";
        // Create configurations for the old hosts that have nagios enabled
        $sql = "select h.ip, h.hostname from host h, host_scan hs where hs.plugin_id=2007 and hs.host_ip=inet_aton(h.ip);";
        $res = $conn->Execute($sql);
        if (!$res->EOF) {
            echo "Creating nagios host definitions<br>";
            while (!$res->EOF) {
                $host_ip = $res->fields["ip"];
                $hostname = $res->fields["hostname"];
                echo "Checking nagios config for $hostname ($host_ip)<br>";
                $sensors = "";
                $q = new NagiosAdm();
                $q->addHost(new NagiosHost($host_ip, $hostname, $sensors));
                $q->close();
                $res->MoveNext();
            }
            echo "Done!<br>";
        } else echo "The creation of nagios host definition is not needed right now<br>";
        return true;
    }
    function start_upgrade() {
        $conn = & $this->conn;
        $snort = & $this->snort;
        /* Snort table changes */
        $sql = "CREATE TABLE `event_stats` (
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
  PRIMARY KEY (`timestamp`),
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
";
        if (!$snort->Execute($sql)) {
            print ("Error was:<b>" . $snort->ErrorMsg() . "</b><br>");
        }
        $this->nagios_check();
        return true;
    }
}
?>
