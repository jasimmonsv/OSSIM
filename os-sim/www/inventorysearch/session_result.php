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
require_once 'classes/Session.inc';
Session::logcheck("MenuPolicy", "5DSearch");
require_once 'classes/Security.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Host_scan.inc';
require_once 'classes/Plugin.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
include ("functions.php");

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$sensors = $hosts = $ossim_servers = array();
list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array();
foreach ($_nets as $_net) $_nets_ips[] = $_net->get_ips();
$networks = implode(",",$_nets_ips);
$hosts_ips = array_keys($hosts);

$page = intval(GET('page'));
if (empty($page) || $page==0) $page = 1;
$rp = intval(GET('rp'));
if (empty($rp) || $rp==0) $rp = 10;
$start = (($page - 1) * $rp);

// Reorder by host
if (GET('order') == 1) {
	$host_list_old = $_SESSION['inventory_search']['result']['list'];
	$_SESSION['inventory_search']['result']['list'] = "";
	foreach ($host_list_old as $host) {
		$host_order[$host->get_ip()] = $host;
	}
	ksort($host_order);
	foreach ($host_order as $ip=>$object) {
		$_SESSION['inventory_search']['result']['list'][] = $object;
	}
}

$userfriendly = intval(GET('userfriendly'));

$host_list = $_SESSION['inventory_search']['result']['list'];
$criterias = $_SESSION['inventory_search']['result']['criterias'];
$has_criterias = $_SESSION['inventory_search']['result']['has_criterias'];

$to = (count($host_list) < $start+$rp) ? count($host_list) : $start+$rp;
$from = $start+1;

echo "{\n\"page\":$page,\n\"total\":".count($host_list).",\n\"to\":$to,\n\"from\":$from,\n\"results\": '";
if ($userfriendly) basic_header();
for ($i = $start; $i < $to; $i++) {
	$host = $host_list[$i];
	if ($userfriendly) host_row_basic($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips,$i);
	else host_row($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips);
}
echo "'}\n";

?>
