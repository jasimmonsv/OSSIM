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
Session::logcheck("MenuPolicy", "PolicyPorts");
require_once 'ossim_db.inc';
require_once 'classes/Port_group.inc';
require_once 'classes/Security.inc';


$page = ( !empty($_POST['page']) ) ? POST('page') : 1;
$rp   = ( !empty($_POST['rp'])   ) ? POST('rp')   : 20;

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

$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db   = new ossim_db();
$conn = $db->connect();
$xml  = "";
$port_list = Port_group::get_list($conn, "ORDER BY $order $limit");

if ($port_list[0]) 
{
    $total = $port_list[0]->get_foundrows();
    if ($total == 0) $total = count($port_list);
} 
else 
	$total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($port_list as $port_group) {
    $name = $port_group->get_name();
    $xml.= "<row id='".htmlspecialchars(utf8_encode($name))."'>";
    $link_modify = "<a style='font-weight:bold;' href=\"./newportform.php?portname=".urlencode($name)."\">" .Util::htmlentities($name) . "</a>";
    $xml.= "<cell><![CDATA[" . $link_modify . "]]></cell>";
    $ports = "";
    foreach($port_group->get_reference_ports($conn, $port_group->get_name()) as $port) {
        $ports.= (($ports == "") ? "" : ", ") . $port->get_port_number() . "-" . $port->get_protocol_name();
    }
    $xml.= "<cell><![CDATA[" . $ports . "]]></cell>";
    $desc = $port_group->get_descr();
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[" . utf8_encode($desc) . "]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


