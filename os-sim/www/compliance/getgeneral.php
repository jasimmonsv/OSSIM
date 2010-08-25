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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Compliance.inc';
require_once 'classes/Security.inc';

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
if (empty($order)) $order = "sid";
//if ($order == "ip") $order = "INET_ATON(ip)"; // Numeric ORDER for IP
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();

$xml = "";
list ($category_list, $total) = Compliance::get_category($conn,$limit);

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
//var_dump($category_list);
foreach($category_list as $cat) {
    $sid = $cat->get_sid();
    $xml.= "<row id='$sid'>";
	$xml.= "<cell><![CDATA[" . $sid . "]]></cell>";
	$xml.= "<cell><![CDATA[" . trim(str_replace("directive_event:","",$cat->get_plugin_name())) . "]]></cell>";
    $xml.= "<cell><![CDATA[" . ($cat->get_targeted() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_untargeted() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_approach() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_exploration() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_penetration() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_generalmalware() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_imp_qos() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_imp_infleak() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_imp_lawful() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_imp_image() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_imp_financial() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_D() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_I() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_C() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
	$xml.= "<cell><![CDATA[" . ($cat->get_net_anomaly() ? "<img src='../pixmaps/tables/tick-small-circle.png'>" : "<img src='../pixmaps/tables/cross-small-circle.png'>") . "]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


