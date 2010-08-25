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
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once 'ossim_conf.inc';
require_once 'ossim_db.inc';
require_once 'classes/Policy_group.inc';
require_once 'classes/Security.inc';
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, 'illegal:' . _("rp"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "group_id";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "";
$policygroup_list = Policy_group::get_list($conn, "WHERE group_id>0 ORDER BY $order $limit");
$total = 0;
if ($policygroup_list[0]) {
    $total = $policygroup_list[0]->get_foundrows();
    if ($total == 0) $total = count($policygroup_list);
}
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";
foreach($policygroup_list as $policygrp) {
    $id = $policygrp->get_group_id();
    $name = $policygrp->get_name();
    $xml.= "<row id='$id'>";
    $xml.= "<cell><![CDATA[" . $id . "]]></cell>";
    $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
    $desc = $policygrp->get_descr();
    if ($desc == "") $desc = "&nbsp;";
    $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


