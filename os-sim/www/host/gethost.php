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
Session::logcheck("MenuPolicy", "PolicyHosts");
require_once 'ossim_db.inc';
require_once 'classes/Host.inc';
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
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
ossim_valid($order, "()", OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "hostname";
if (!empty($ip_range)) $search = 'WHERE inet_aton(ip) >= inet_aton("' . $ip_range[0] . '") and inet_aton(ip) <= inet_aton("' . $ip_range[1] . '")';
elseif (!empty($by_ip)) $search = "WHERE ip like '%$search%'";
elseif (!empty($search) && !empty($field)) $search = "WHERE $field like '%$search%'";
elseif (!empty($search)) $search = "WHERE ip like '%$search%' OR hostname like '%$search%'";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "";

$host_list = Host::get_list_pag($conn, "$search", "ORDER BY $order $limit");

if ($host_list[0]) {
    $total = $host_list[0]->get_foundrows();
    if ($total == 0) $total = count($host_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($host_list as $host) {
    $ip = $host->get_ip();
    $xml.= "<row id='$ip'>";
	$name = "<a style='font-weight:bold;' href=\"./modifyhostform.php?ip=".urlencode($ip)."\">" . htmlentities($host->get_hostname()) . "</a>" . Host_os::get_os_pixmap($conn, $ip);
    $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $ip . "]]></cell>";
    $desc = $host->get_descr();
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[" . utf8_encode($desc) . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host->get_asset() . "]]></cell>";
    $sensors = "";
    if ($sensor_list = $host->get_sensors($conn))
       foreach($sensor_list as $sensor) {
          $sensors.= ($sensors == "" ? '':', ') . $sensor->get_sensor_name();
       }
    $xml.= "<cell><![CDATA[" . utf8_encode($sensors) . "]]></cell>";
    $scantype = "<img src='../pixmaps/tables/cross.png'>";
    if ($scan_list = Host_scan::get_list($conn, "WHERE host_ip = inet_aton('$ip')")) {
          foreach($scan_list as $scan) {
            $id = $scan->get_plugin_id();
            $plugin_name = "";
            if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id"))
            {
                $plugin_name.= $plugin_list[0]->get_name();
                if ( $plugin_name == "nagios" )
                  $scantype = "<img src='../pixmaps/tables/tick.png'>";
                else
                  $scantype = "<img src='../pixmaps/tables/cross.png'>";

            }
            else
              $scantype = "<img src='../pixmaps/tables/cross.png'>";
            
        }
    }
    $xml.= "<cell><![CDATA[" . $host->get_threshold_c() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host->get_threshold_a() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host->get_alert() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host->get_persistence() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $host->get_rrd_profile() . "]]></cell>";
   
    $apps = Host::get_apps($conn,$ip);
    if (count($apps)>0) {
    	$xml.= "<cell><![CDATA[<a href='javascript:;' onclick=\"view_apps('$ip')\" class='blue' target=\"main\">[".(count($apps))."]&nbsp;<img src='../pixmaps/tools.png' title='".(count($apps))._(" apps found")."' border='0' align='absmiddle'></a>]]></cell>";
    } else {
    	$xml.= "<cell><![CDATA[<img src='../pixmaps/tools_gray.png' title='"._("No apps found")."' border='0'>]]></cell>";
    }
    $rep = "";
    if ($linkedocs = Repository::have_linked_documents($conn, $ip, 'host'))
    	//$rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/index.php?search_bylink=$ip&hmenu=Repository&smenu=Repository')\" class=\"blue\" target=\"main\">[" . $linkedocs . "]</a>&nbsp;";
    	$rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('../repository/repository_list.php?keyname=" . urlencode($ip) . "&type=host')\" class=\"blue\" target=\"main\">[" . $linkedocs . "]</a>&nbsp;";
    	
    $rep.= "<a href=\"javascript:;\" onclick=\"GB_edit('addrepository.php?id_host=" . $ip . "&name_host=" . urlencode($host->get_hostname()) . "')\"><img src=\"../pixmaps/tables/table_row_insert.png\" border=0 title=\"Add KDB\" alt=\"Add KDB\" align=\"absmiddle\"></a>";
    $rep.= "&nbsp;<a href=\"../repository/index.php?hmenu=Repository&smenu=Repository\" target=\"main\"><img src=\"../pixmaps/tables/table_edit.png\" title=\"Edit KDB\" alt=\"Edit KDB\" border=0 align=\"absmiddle\"></a>";
    $xml.= "<cell><![CDATA[" . utf8_encode($rep) . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $scantype . "]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


