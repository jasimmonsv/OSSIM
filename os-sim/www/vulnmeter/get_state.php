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
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");
//

// LOCAL SERVER 
$scanner = ($_SESSION["scanner"]=="openvas") ? "openvas-client" : "nessus";
$running = shell_exec('ps -ef | grep "'.$scanner.'" | grep -v "/bin/sh" | egrep -v "serving|grep|nessus-service|nessusd" | wc -l'); 
$run = (intval($running) == 0) ? "0" : "1";

/*
#  nessusd: testing 192.168.1.5 (/var/lib/nessus/plugins/DDI_Directory_Scanner.nasl)
$lineas = explode("\n",`ps -ef | grep "$scanner" | grep -v "/bin/sh" | grep -v grep`); 
$i=0; foreach ($lineas as $linea) if (trim($linea)!="") {
	if (preg_match("/testing (.*?) \((.*)\/(.*?)\.(.*?)\)/",$linea,$found)) {
		if ($i++<25) print ";".$found[1]." ".$found[3];
        if ($i==26) print ";[...] ";
	}
}
*/

// Get .work running scans
$ips = array();
$details = array();

$lineas = explode("\n",`ps -ef | grep ".\out" | grep -v grep`); 
foreach ($lineas as $linea) if (preg_match("/ (.+\.out)/",$linea,$found)) {
	$file = preg_replace("/.*(\/usr)/","\\1",str_replace(".out",".work",trim($found[1])));
	$tail = file($file);
	foreach ($tail as $linea) {
		$linea = trim($linea);
		if (preg_match("/(.*?)\|(.*?)\|(.*?)\|(.*)/",$linea,$found))
			$ips[$found[1]][$found[2]]++;
	}
}
foreach ($ips as $type => $ip) {
	foreach ($ip as $host => $count) {
		$details[] = "$type: <b>$host</b> [$count]";
		$run++;
	}
}
// limit last
$detail = implode(";",array_slice(array_reverse($details),0,30));
echo $run.";".$detail;
?>
