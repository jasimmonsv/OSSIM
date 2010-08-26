<?php
/*****************************************************************************
*
*    License:
*
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
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
Session::logcheck("MenuPolicy", "5DSearch");
$i = GET('i');
$type = GET('type');
$subtype = GET('subtype');
$match = GET('match');
$op = GET('op');
$n = GET('n');

ossim_valid($i, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("i"));
ossim_valid($type, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("type"));
ossim_valid($subtype, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("subtype"));
ossim_valid($match, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("match"));
ossim_valid($i, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("i"));
ossim_valid($op, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("op"));
ossim_valid($n, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("n"));

if (ossim_error()) {
    exit;
}

/* DEBUG*/
if ($match == "" && $n <= 0) {
	$f = fopen("/tmp/setvars.log","w");
	fputs($f,"n: $n\ni: $i\ntype: $type\nsubtype: $subtype\nmatch: $match\n");
	fclose($f);
}

// 5D Basic Search
if (GET('basic')) {
	$basic_search = array();
	$date_from = (GET('date_from') != "Any date" && GET('date_from') != "") ? preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\1-\\2",GET('date_from')) : "1700-01-01";
	$date_to = (GET('date_to') != "Any date" && GET('date_to') != "") ? preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/","\\3-\\1-\\2",GET('date_to')) : "3000-01-01";
	$_SESSION['inventory_search']['date_from'] = $date_from;
	$_SESSION['inventory_search']['date_to'] = $date_to;
	
	// All Empty
	$basic_search[0] = array(
		"type"=>"Generic",
		"subtype"=>"None",
		"match"=>"LIKE",
		"query"=>"SELECT DISTINCT INET_NTOA(ip_dst) AS ip FROM snort.ac_dstaddr_ipsrc WHERE INET_NTOA(ip_src) %op% ? UNION SELECT DISTINCT INET_NTOA(dst_ip) as ip FROM alarm WHERE INET_NTOA(src_ip) %op% ? UNION SELECT DISTINCT INET_NTOA(ip_src) AS ip FROM snort.ac_srcaddr_ipdst WHERE INET_NTOA(ip_dst) %op% ? UNION SELECT DISTINCT INET_NTOA(src_ip) as ip FROM alarm WHERE INET_NTOA(dst_ip) %op% ?",
		"query_match"=>"boolean");
		
	// Network
	$basic_search[1] = array(
		"type"=>"Network",
		"subtype"=>"Network is like",
		"match"=>"LIKE",
		"query"=>"SELECT ip FROM host WHERE INET_ATON(ip) BETWEEN ?",
		"query_match"=>"network");
	
	// Inventory
	$basic_search[2] = array(
		"type"=>"Inventory",
		"subtype"=>"Has Serv/OS",
		"match"=>"LIKE",
		"query"=>"function:query_inventory",
		"query_match"=>"text");
	
	// Vulnerabilities
	$basic_search[3] = array(
		"type"=>"Vulnerabilities",
		"subtype"=>"Vuln contains",
		"match"=>"LIKE",
		"query"=>"SELECT DISTINCT INET_NTOA(hp.host_ip) as ip FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? 
			UNION 
			SELECT DISTINCT INET_NTOA(s.host_ip) as ip FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.cve_id %op% ?",
		"query_match"=>"text");
	
	// Tickets
	$basic_search[4] = array(
		"type"=>"Incidents",
		"subtype"=>"Incident contains",
		"match"=>"LIKE",
		"query"=>"SELECT DISTINCT a.src_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.date >= '$date_from' AND i.date <= '$date_to' AND a.src_ips != '' AND i.title %op% ? 
			UNION 
			SELECT DISTINCT a.dst_ips as ip FROM incident i,incident_alarm a WHERE i.id=a.incident_id AND i.date >= '$date_from' AND i.date <= '$date_to' AND a.dst_ips != '' AND i.title %op% ? 
			UNION 
			SELECT DISTINCT inet_ntoa(a.src_ip) as ip FROM alarm a,plugin_sid p WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND a.timestamp >= '$date_from' AND a.timestamp <= '$date_to' AND a.src_ip != '' AND p.name %op% ? 
			UNION 
			SELECT DISTINCT inet_ntoa(a.dst_ip) as ip FROM alarm a,plugin_sid p WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND a.timestamp >= '$date_from' AND a.timestamp <= '$date_to' AND a.dst_ip != '' AND p.name %op% ? 
			UNION
			SELECT r.keyname as ip FROM repository d,repository_relationships r WHERE d.id=r.id_document AND r.type='host' AND keyname!='' AND d.text %op% ?",
		"query_match"=>"text");
	
	// Security Events
	$basic_search[5] = array(
		"type"=>"Security Events",
		"subtype"=>"Event contains",
		"match"=>"LIKE",
		"query"=>"SELECT DISTINCT INET_NTOA(ac.ip_src) as ip FROM snort.ac_srcaddr_signature ac,ossim.plugin_sid s WHERE s.plugin_id=ac.plugin_id AND s.sid=ac.plugin_sid AND ac.day >= '$date_from' AND ac.day <= '$date_to' AND s.name %op% ?
			UNION
			SELECT DISTINCT INET_NTOA(ac.ip_dst) as ip FROM snort.ac_dstaddr_signature ac,ossim.plugin_sid s WHERE s.plugin_id=ac.plugin_id AND s.sid=ac.plugin_sid AND ac.day >= '$date_from' AND ac.day <= '$date_to' AND s.name %op% ?",
		"query_match"=>"text");
		
	// Set Values
	$_SESSION['inventory_search']['num'] = $n;
	$k = 0;
	for ($i = 1; $i <= 5; $i++) {
		$val = 'value'.$i;
		if (GET($val) != "") {
			$k++;
			$_SESSION['inventory_search'][$k] = $basic_search[$i];
			$_SESSION['inventory_search'][$k]['value'] = GET($val);
		}
		else {
			unset ($_SESSION['inventory_search'][$i]);
			continue;
		}
	}
	// Empty search
	if ($n == 0) {
		$_SESSION['inventory_search']['num'] = 1;
		$_SESSION['inventory_search'][1] = $basic_search[0];
		$_SESSION['inventory_search'][1]['value'] = "";
	}
	if (GET('date_from')) {
	
	}

// ADVANCED
} else {
	// Set Values
	if ($n > 0) {
		$_SESSION['inventory_search']['num'] = $n;
		for ($i = 1; $i <= $n; $i++) {
			$val = 'value'.$i;
			$_SESSION['inventory_search'][$i]['value'] = GET($val);
		}
	}
	// Set filters selections
	else {
		$_SESSION['inventory_search'][$i]['type'] = $type;
		$_SESSION['inventory_search'][$i]['subtype'] = $subtype;
		$_SESSION['inventory_search'][$i]['match'] = $match;
	}
}
//print_r($_SESSION);
?>
