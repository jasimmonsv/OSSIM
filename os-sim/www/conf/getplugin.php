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
Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 30;
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
$search = GET('query');
if (empty($search)) $search = POST('query');
$field = POST('qtype');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "id";
$where = "WHERE id<>1505";
if (!empty($search) && !empty($field)) $where.= " AND $field like '%" . $search . "%'";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "<rows>\n";
if ($plugin_list = Plugin::get_list($conn, "$where ORDER BY $order $limit")) {
    $total = $plugin_list[0]->get_foundrows();
    if ($total == 0) $total = count($plugin_list);
    $xml.= "<page>$page</page>\n";
    $xml.= "<total>$total</total>\n";
    foreach($plugin_list as $plugin) {
        $id = $plugin->get_id();
        $name = $plugin->get_name();
        $type = $plugin->get_type();
        $xml.= "<row id='$id'>";
        $lnk = "<a href='pluginsid.php?id=$id'>$id</a>";
        $xml.= "<cell><![CDATA[" . $lnk . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
        if ($type == '1') {
            $tipo = "Detector ($type)";
        } elseif ($type == '2') {
            $tipo = "Monitor ($type)";
        } else {
            $tipo = "Other ($type)";
        }
        $xml.= "<cell><![CDATA[" . $tipo . "]]></cell>";
		// Source Type
		$sourceType=$plugin->get_sourceType();
		$xml.= "<cell><![CDATA[" . $sourceType . "]]></cell>";
        $desc = $plugin->get_description();
        if ($desc == "") $desc = "&nbsp;";
        $xml.= "<cell><![CDATA[" . $desc . "]]></cell>";
        $xml.= "</row>\n";
    }
}
$xml.= "</rows>\n";
echo $xml;
$db->close($conn);
?>


