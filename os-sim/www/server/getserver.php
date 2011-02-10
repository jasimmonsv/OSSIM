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
Session::logcheck("MenuConfiguration", "PolicyServers");
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Server.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Security.inc';
require_once 'server_get_servers.php';
require_once 'classes/WebIndicator.inc';
require_once 'classes/Util.inc';

$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "name";
if ($order == "ip") $order = "INET_ATON(ip)"; // Numeric ORDER for IP
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
//first, get the servers connected; all this servers are "actived"
list($server_list, $err) = server_get_servers($conn);
$server_list_aux = $server_list; //here are stored the connected servers
$server_stack = array(); //here will be stored the servers wich are in DDBB
$server_configured_stack = array();
if ($server_list) {
    foreach($server_list as $server_status) {
        if (in_array($server_status["servername"], $server_stack)) continue;
        array_push($server_stack, $server_status["servername"]);
    }
}
$active_servers = 0;
$total_servers = 0;
$xml = "";
$server_list = Server::get_list($conn, "ORDER BY $order $limit");
if ($server_list[0]) {
    $total = $server_list[0]->get_foundrows();
    if ($total == 0) $total = count($server_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($server_list as $server) {
    $total_servers++;
    $name = htmlspecialchars(utf8_encode($server->get_name()));
    $xml.= "<row id='".$name."'>";
    $ip = $server->get_ip();
    $link_modify = "<a style='font-weight:bold;' href=\"./newserverform.php?name=".urlencode($server->get_name())."\">" . Util::htmlentities($ip) . "</a>";
    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $server->get_port() . "]]></cell>";
    if (in_array($name, $server_stack)) {
        $xml.= "<cell><![CDATA[<img src='../pixmaps/tables/tick.png'>]]></cell>";
        $active_servers++;
        array_push($server_configured_stack, $name);
    } else {
        $xml.= "<cell><![CDATA[<img src='../pixmaps/tables/cross.png'>]]></cell>";
    }
    $role_list = Server::get_role($conn, "WHERE server_role.name = '".$server->get_name()."'");
    foreach($role_list as $role) {
        $xml.= "<cell><![CDATA[" . ($role->get_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_cross_correlate() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_store() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_qualify() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_resend_alarm() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_resend_event() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_sign() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_sem() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        $xml.= "<cell><![CDATA[" . ($role->get_sim() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
        break;
    }
    $desc = $server->get_descr();
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[".utf8_encode($desc)."]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


