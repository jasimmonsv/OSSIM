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
Session::logcheck("MenuPolicy", "PolicyHosts");
require_once 'ossim_db.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Host_group_scan.inc';
require_once 'classes/Host_group_reference.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Security.inc';
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
$host_group_name = GET('host_group_name');
ossim_valid($nessus_action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Nessus action"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
ossim_valid($nagios_action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Nagios action"));
ossim_valid($host_group_name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Host group name"));
ossim_valid($order, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Order"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$hosts_list = Host_group_reference::get_list($conn, $host_group_name);
$iter = 0;
foreach($hosts_list as $host) $hosts[$iter++] = $host->host_ip;
if ((!empty($nessus_action)) AND (!empty($host_group_name))) {
    if ($nessus_action == "toggle") {
        $nessus_action = ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$host_group_name' AND plugin_id = 3001")) ? "disable" : "enable";
    }
    if ($nessus_action == "enable") {
        Host_group::enable_nessus($conn, $host_group_name);
    } elseif ($nessus_action = "disable") {
        Host_group::disable_nessus($conn, $host_group_name);
    }
    //$db->close($conn);
    
}
if ((!empty($nagios_action)) AND (!empty($host_group_name))) {
    if ($nagios_action == "toggle") {
        $nagios_action = ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$host_group_name' AND plugin_id = 2007")) ? "disable" : "enable";
    }
    if ($nagios_action == "disable") {
        if (Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) {
            foreach($hosts as $h) {
                if (Host_group_scan::can_delete_host_from_nagios($conn, $h, $host_group_name)) {
                    require_once 'classes/NagiosConfigs.inc';
                    $q = new NagiosAdm();
                    $q->delHost(new NagiosHost($h, $h, ""));
                    $q->close();
                }
            }
            Host_group_scan::delete($conn, $host_group_name, 2007);
        }
    }
    if ($nagios_action == "enable") {
        if (Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) Host_group_scan::delete($conn, $host_group_name, 2007);
        Host_group_scan::insert($conn, $host_group_name, 2007);
        require_once 'classes/NagiosConfigs.inc';
        $q = new NagiosAdm();
        $q->addNagiosHostGroup(new NagiosHostGroup($host_group_name, $hosts, $sensors),$conn);
        $q->close();
    }
}
if (empty($order)) $order = "name";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$xml = "";
$where = "";
if($field=="name")
    $where = "WHERE name like '%$search%'";
else if ($field=="ip")
    $where = ", host_group_reference WHERE host_group.name=host_group_reference.host_group_name AND host_group_reference.host_ip like '%$search%'";

list($host_group_list,$total) = Host_group::get_list_pag($conn, "$where ORDER BY $order $limit");

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($host_group_list as $host_group) {
    $name = $host_group->get_name();
    $xml.= "<row id='".$name."'>";
    $link_modify = "<a style='font-weight:bold;' href=\"./newhostgroupform.php?name=".urlencode($name)."\">" .$name. "</a>";
    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $list = "";
    if ($host_list = $host_group->get_hosts($conn)) foreach($host_list as $host) $list.= ($list == "" ? "" : ", ") . $host->get_host_name($conn);
    $xml.= "<cell><![CDATA[" . $list . "]]></cell>";
    $desc = $host_group->get_descr();
    if ($desc == "") $desc = "&nbsp;";
      $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";

    $sensors = "";
    if ($sensor_list = $host_group->get_sensors($conn)) foreach($sensor_list as $sensor) {
       $sensors.= ($sensors == "" ? '':', ') . $sensor->get_sensor_name();
    }
    $xml.= "<cell><![CDATA[" . $sensors . "]]></cell>";

    $xml.= "<cell><![CDATA[" . $host_group->get_threshold_c() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host_group->get_threshold_a() . "]]></cell>";
    /* Nessus
    if ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$name' AND plugin_id = 3001")) {
        $scan_types = "<img src='../pixmaps/tables/tick.png'>";
    } else {
        $scan_types = "<img src='../pixmaps/tables/cross.png'>";
    }
    $xml.= "<cell><![CDATA[" . $scan_types . "]]></cell>"; */
    // Nagios
       
    $rep = "";
    if ($linkedocs = Repository::have_linked_documents($conn, $host_group->get_name() , 'host_group')) $rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/repository_list.php?keyname=" . urlencode($name) . "&type=host_group')\" class=\"blue\">[" . $linkedocs . "]</a>&nbsp;";
    $rep.= "<a href=\"../repository/index.php?hmenu=Repository&smenu=Repository\" target='main'><img src=\"../pixmaps/tables/table_edit.png\" title=\"Edit KDB\" alt=\"Edit KDB\" border=0 align=\"absmiddle\"></a>";
    $xml.= "<cell><![CDATA[" . $rep . "]]></cell>";
    if ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$name' AND plugin_id = 2007")) {
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


