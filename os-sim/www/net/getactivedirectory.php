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
require_once 'classes/Session.inc';
require_once 'classes/Util.inc';
if (!Session::am_i_admin()) die(_("You don't have permissions for Asset Discovery"));
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
require_once 'ossim_db.inc';
require_once 'classes/ActiveDirectory.inc';
require_once 'classes/Security.inc';

$page = ( !empty($_POST['page']) ) ? POST('page') : 1;
$rp   = ( !empty($_POST['rp'])   ) ? POST('rp')   : 20;

$order  = GET('sortname');
$field  = ((POST('qtype')!="")? POST('qtype'):GET('qtype'));
$search = GET('query');
if (empty($search)) $search = POST('query');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
if (!empty($search) && !empty($field)) {
	$filter = "WHERE $field like '%$search%'";
	if ($field=="ip") $filter = "WHERE inet_ntoa($field) like '%$search%'";
}
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_DOT, OSS_DIGIT, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));

if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "ip";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db    = new ossim_db();
$conn  = $db->connect();
$xml   = "";

$ad_list = ActiveDirectory::get_list($conn, "$filter ORDER BY $order $limit");
if ($ad_list[0]) {
    $total = $ad_list[0]->get_foundrows();
    if ($total == 0) $total = count($ad_list);
} else $total = 0;
$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($ad_list as $ad) {
    $xml.= "<row id='".$ad->get_id()."'>";
    $xml.= "<cell><![CDATA[" . long2ip($ad->get_server()) . "]]></cell>";
    $xml.= "<cell><![CDATA[" . Util::htmlentities($ad->get_binddn()) . "]]></cell>";
	$pass = "";
	for ($p = 0; $p < strlen($ad->get_password()); $p++) {
        $pass .= "*";
    }
    $xml.= "<cell><![CDATA[" . $pass . "]]></cell>";
    $xml.= "<cell><![CDATA[" . Util::htmlentities($ad->get_scope()) . "]]></cell>";  
    $xml.= "</row>\n";
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


