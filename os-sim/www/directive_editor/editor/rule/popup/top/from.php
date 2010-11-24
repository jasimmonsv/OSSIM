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
require_once ('classes/Security.inc');
require_once ("../../../../include/utils.php");
dbConnect();
$getports = (GET('port') == "1") ? 1 : 0;
/*
$from = GET('from');
$from_list = GET('from_list');
ossim_valid($from, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("from"));
ossim_valid($from_list, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("from_list"));
if (ossim_error()) {
    die(ossim_error());
}

if (substr($from_list, 0, 1) == '!') {
    $default_checked = ' checked="checked"';
    $from_list = substr($from_list, 1);
} else $default_checked = '';
*/
if ($getports) {
	$port_list = getPortList();
	$already = array();
	$i = 0;
	foreach ($port_list as $port) {
		if ($i > 100) continue;
		$port_number = $port->get_port_number();
		if (!$already[$port_number]) {
			echo "$port_number=$port_number\n";
			$i++;
		}
		$already[$port_number]++;
	}
} else {
	$host_list = getHostList();
	$net_list = getNetList();
	if (count($host_list) + count($net_list) > 100) echo "Total=".(count($host_list) + count($net_list))."\n";
	
	$i = 0;
	foreach ($host_list as $host) {
		if ($i > 100) continue;
	    $hostname = $host->get_hostname();
	    $ip = $host->get_ip();
	    if ($from == 'ANY') {
	        $checked = ' checked="checked"';
	    } elseif (in_array($ip, split(',', $from_list))) {
	        $checked = ($default_checked == '') ? ' checked="checked"' : '';
	    } else {
	        $checked = $default_checked;
	    }
	    echo "$ip=$hostname\n";
	    $i++;
	}
	
	foreach ($net_list as $net) {
	    if ($i > 100) continue;
		$netname = $net->get_name();
	    $ips = $net->get_ips();
	    if ($from == 'ANY') {
	        $checked = ' checked="checked"';
	    } elseif (in_array($netname, split(',', $from_list))) {
	        $checked = ($default_checked == '') ? ' checked="checked"' : '';
	    } else {
	        $checked = $default_checked;
	    }
	    echo "$ips=$netname\n";
	    $i++;
	}
}
dbClose();
?>
