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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
require_once ('classes/Session.inc');
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
require_once ('classes/CIDR.inc');
//Session::logcheck("MenuPolicy", "PolicyHosts");
Session::logcheck("MenuReports", "ReportsHostReport");
require_once 'ossim_db.inc';
require_once 'classes/Host.inc';
require_once 'classes/Net_scan.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Host_scan.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/CIDR.inc';
require_once 'classes/Security.inc';
require_once 'classes/WebIndicator.inc';
require_once ("classes/Repository.inc");
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if ($order == "ip") $order = "INET_ATON(ip)"; // Numeric ORDER for IP
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
$search = GET('query');
if (empty($search)) $search = POST('query');
$field = POST('qtype');
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$lsearch = $search;
ossim_valid($search, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_ALPHA, 'illegal:' . _("search"));
if (!empty($search))
// The CIDR validation is not working...
if (preg_match("/^\s*([0-9]{1,3}\.){3}[0-9]{1,3}\/(3[0-2]|[1-2][0-9]|[0-9])\s*$/", $search)) {
    $ip_range = CIDR::expand_CIDR($search, "SHORT", "IP");
    ossim_valid($ip_range[0], OSS_IP_ADDR, 'illegal:' . _("search cidr"));
    ossim_valid($ip_range[1], OSS_IP_ADDR, 'illegal:' . _("search cidr"));
} else {
    if (preg_match("/^\s*([0-9]{1,3}\.){3}[0-9]{1,3}\s*$/", $search)) $by_ip = true;
    else ossim_valid($search, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_DOT, OSS_DIGIT, 'illegal:' . _("search"));
}
ossim_valid($order, "()", OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, 'illegal:' . _("rp"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "name";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$where = "";
if (!empty($search) && !empty($field)) $where = "name LIKE '%$search%'";
$db = new ossim_db();
$conn = $db->connect();

$xml = "";
$net_list = Net::get_list($conn, "$where", "ORDER BY $order");
if ($net_list[0]) {
    $total = $net_list[0]->get_foundrows();
    if ($total == 0) $total = count($net_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($net_list as $net) {
    $ips = $net->get_ips();
	$name = $net->get_name();
    $xml.= "<row id='$name'>";
	$aname = "<a href='../report/index.php?section=network&host=$ips'>$name</a>";
    $xml.= "<cell><![CDATA[" . $aname . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $ips . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $net->get_asset() . "]]></cell>";
	
	$xml.= "<cell><![CDATA[";
	// Nessus
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 3001")) {
        $xml .= "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $xml .= "<img src='../pixmaps/tables/cross.png'>";
    }
	$xml .= "]]></cell>";
	
    // Nagios
	$xml.= "<cell><![CDATA[";
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 2007")) {
        $xml .= "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $xml .= "<img src='../pixmaps/tables/cross.png'>";
    }
	$xml .= "]]></cell>";
	
	$sensors = "";
    if ($sensor_list = $net->get_sensors($conn)) foreach($sensor_list as $sensor) {
        $sensors.= $sensor->get_sensor_name() . '<br/>';
    }
    $xml.= "<cell><![CDATA[" . $sensors . "]]></cell>";
    $desc = $net->get_descr();
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";
	
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


