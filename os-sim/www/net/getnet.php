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
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";
Session::logcheck("MenuPolicy", "PolicyNetworks");
require_once 'ossim_db.inc';
require_once 'classes/Net.inc';
require_once 'classes/Net_scan.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Security.inc';
require_once 'classes/WebIndicator.inc';
require_once ("classes/Repository.inc");
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
$search = GET('query');
if (empty($search)) $search = POST('query');
$field = POST('qtype');
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$nagios_action = GET('nagios_action');
$nessus_action = GET('nessus_action');
$net_name = GET('net_name');
ossim_valid($nessus_action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Nessus action"));
ossim_valid($nagios_action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Nagios action"));
ossim_valid($net_name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Net name"));
ossim_valid($order, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if ((!empty($nessus_action)) AND (!empty($net_name))) {
    if ($nessus_action == "toggle") {
        $nessus_action = ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$net_name' AND plugin_id = 3001")) ? "disable" : "enable";
    }
    if ($nessus_action == "enable") {
        Net::enable_plugin($conn, $net_name, 3001);
    } elseif ($nessus_action = "disable") {
        Net::disable_plugin($conn, $net_name, 3001);
    }
}
if ((!empty($nagios_action)) AND (!empty($net_name))) {
    if ($nagios_action == "toggle") {
        $nagios_action = ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$net_name' AND plugin_id = 2007")) ? "disable" : "enable";
    }
    if ($nagios_action == "enable") {
        Net::enable_plugin($conn, $net_name, 2007);
    } elseif ($nagios_action = "disable") {
        Net::disable_plugin($conn, $net_name, 2007);
    }
}
if (empty($order)) $order = "name";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$where = "";
if (!empty($search) && !empty($field)) $where = "name LIKE '%$search%'";
$xml = "";
$net_list = Net::get_list($conn, $where, "ORDER BY $order $limit");
if ($net_list[0]) {
    $total = $net_list[0]->get_foundrows();
    if ($total == 0) $total = count($net_list);
} else $total = 0;

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($net_list as $net) {
    $name = $net->get_name();
    $xml.= "<row id='".htmlspecialchars($name)."'>";
    $link_modify = "<a style='font-weight:bold;' href=\"./newnetform.php?name=".urlencode($name)."\">" . htmlentities($name) . "</a>";
    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $net->get_ips() . "]]></cell>";

    $desc = $net->get_descr();
    if ($desc == "") $desc = "&nbsp;";
      $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";

    $xml.= "<cell><![CDATA[" . $net->get_asset() . "]]></cell>";
    
    $sensors = "";
    if ($sensor_list = $net->get_sensors($conn)) foreach($sensor_list as $sensor) {
      $sensors.= ($sensors == "" ? '':', ') . $sensor->get_sensor_name();
    }
    $xml.= "<cell><![CDATA[" . $sensors . "]]></cell>";

    $xml.= "<cell><![CDATA[" . $net->get_threshold_c() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $net->get_threshold_a() . "]]></cell>";

    $rep = "";
    if ($linkedocs = Repository::have_linked_documents($conn, $name, 'net')) $rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/repository_list.php?keyname=" . urlencode($name) . "&type=net')\" class=\"blue\">[" . $linkedocs . "]</a>&nbsp;";
    $rep.= "<a href=\"../repository/index.php?hmenu=Repository&smenu=Repository\" target='main'><img src=\"../pixmaps/tables/table_edit.png\" title=\"Edit KDB\" alt=\"Edit KDB\" border=0 align=\"absmiddle\"></a>";
    $xml.= "<cell><![CDATA[" . $rep . "]]></cell>";

   

    /* Nessus
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 3001")) {
        $scan_types = "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $scan_types = "<img src='../pixmaps/tables/cross.png'>";
    }
    $xml.= "<cell><![CDATA[" . $scan_types . "]]></cell>"; */
    // Nagios
   
    if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 2007")) {
        $scan_types = "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $scan_types = "<img src='../pixmaps/tables/cross.png'>";
    }
    $xml.= "<cell><![CDATA[" . $scan_types . "]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


