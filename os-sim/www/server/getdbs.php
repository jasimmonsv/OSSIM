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
require_once 'classes/Databases.inc';
require_once 'classes/Security.inc';
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "name";
if ($order == "ip") $order = "INET_ATON(ip)"; // Numeric ORDER for IP
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "";
$server_list = Databases::get_list($conn, "ORDER BY $order $limit");
if ($server_list[0]) {
    $total = $server_list[0]->get_foundrows();
    if ($total == 0) $total = count($server_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($server_list as $server) {
    $name = utf8_encode($server->get_name());
    $xml.= "<row id='".htmlspecialchars($name)."'>";
    $ip = $server->get_ip();

    $link_modify = "<a style='font-weight:bold;' href=\"./newdbsform.php?name=".urlencode($server->get_name())."\">".$name."</a>";

    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $ip . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $server->get_port() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $server->get_user() . "]]></cell>";
    $xml.= "<cell><![CDATA[" . preg_replace("/./","*",$server->get_pass()) . "]]></cell>";
    $icon = $server->get_icon();
    if ($icon == "") $icon = "&nbsp;";
    $xml.= "<cell><![CDATA[<img src='getdbsicon.php?name=" . urlencode($name) . "' border=0>]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


