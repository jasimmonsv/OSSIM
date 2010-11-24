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
require_once 'classes/Plugin_sid.inc';
require_once 'classes/Classification.inc';
require_once 'classes/Category.inc';
require_once 'classes/Subcategory.inc';
$page = POST('page');
if (empty($page)) $page = 1;
$rp = POST('rp');
if (empty($rp)) $rp = 25;
$order = GET('sortname');
if (empty($order)) $order = POST('sortname');
if (!empty($order)) $order.= (POST('sortorder') == "asc") ? "" : " desc";
$search = GET('query');
if (empty($search)) $search = POST('query');
$id = GET('id');
if (empty($id)) $search = POST('id');
$field = POST('qtype');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($field, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("field"));
ossim_valid($id, OSS_ALPHA, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "sid";
$where = "WHERE plugin_id = $id";
if (!empty($search) && !empty($field)) $where.= " AND $field like '%" . $search . "%'";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "";
if ($plugin_list = Plugin_sid::get_list($conn, "$where ORDER BY $order $limit")) {
    $total = $plugin_list[0]->get_foundrows();
    if ($total == 0) $total = count($plugin_list);
    $xml.= "<rows>\n";
    $xml.= "<page>$page</page>\n";
    $xml.= "<total>$total</total>\n";
    foreach($plugin_list as $plugin) {
        $id = $plugin->get_plugin_id();
        $sid = $plugin->get_sid();
        $name = $plugin->get_name();
        $xml.= "<row id='$sid'>";
        $xml.= "<cell><![CDATA[" . $id . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $sid . "]]></cell>";
        // translate category id
        if ($category_id = $plugin->get_category_id()) {
            if ($category_list = Category::get_list($conn, "WHERE id = '$category_id'")) {
                $category_name = $category_list[0]->get_name();
            }
        }else{
			$category_name='';
		}
        $category = (!empty($category_name)) ? $category_name . " (" . $category_id . ")" : "-";
        $xml.= "<cell><![CDATA[" . $category . "]]></cell>";
		// subcategory
		if($subcategory_id = $plugin->get_subcategory_id()){
			if ($subcategory_list = Subcategory::get_list($conn, "WHERE id = '$subcategory_id'")) {
                $subcategory_name = $subcategory_list[0]->get_name();
            }
		}else{
			$subcategory_name='';
		}
		$subcategory = (!empty($subcategory_name)) ? $subcategory_name . " (" . $subcategory_id . ")" : "-";
		$xml.= "<cell><![CDATA[" . $subcategory . "]]></cell>";
        // translate class id
        if ($class_id = $plugin->get_class_id()) {
            if ($class_list = Classification::get_list($conn, "WHERE id = '$class_id'")) {
                $class_name = $class_list[0]->get_name();
            }
        }
        $class = (!empty($class_name)) ? $class_name . " (" . $class_id . ")" : "-";
        $xml.= "<cell><![CDATA[" . $class . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
        $prio = $plugin->get_priority();
        $txt = "<select id='priority" . $sid . "' onchange='change_pri_rel(\"" . $sid . "\")'>";
        $txt.= "<option value='0'" . ($prio == 0 ? " SELECTED " : "") . ">0</option>";
        $txt.= "<option value='1'" . ($prio == 1 ? " SELECTED " : "") . ">1</option>";
        $txt.= "<option value='2'" . ($prio == 2 ? " SELECTED " : "") . ">2</option>";
        $txt.= "<option value='3'" . ($prio == 3 ? " SELECTED " : "") . ">3</option>";
        $txt.= "<option value='4'" . ($prio == 4 ? " SELECTED " : "") . ">4</option>";
        $txt.= "<option value='5'" . ($prio == 5 ? " SELECTED " : "") . ">5</option>";
        $txt.= "</select>";
        $xml.= "<cell><![CDATA[" . $txt . "]]></cell>";
        $rel = $plugin->get_reliability();
        $txt = "<select id='reliability" . $sid . "' onchange='change_pri_rel(\"" . $sid . "\")'>";
        $txt.= "<option value='0'" . ($rel == 0 ? " SELECTED " : "") . ">0</option>";
        $txt.= "<option value='1'" . ($rel == 1 ? " SELECTED " : "") . ">1</option>";
        $txt.= "<option value='2'" . ($rel == 2 ? " SELECTED " : "") . ">2</option>";
        $txt.= "<option value='3'" . ($rel == 3 ? " SELECTED " : "") . ">3</option>";
        $txt.= "<option value='4'" . ($rel == 4 ? " SELECTED " : "") . ">4</option>";
        $txt.= "<option value='5'" . ($rel == 5 ? " SELECTED " : "") . ">5</option>";
        $txt.= "<option value='6'" . ($rel == 6 ? " SELECTED " : "") . ">6</option>";
        $txt.= "<option value='7'" . ($rel == 7 ? " SELECTED " : "") . ">7</option>";
        $txt.= "<option value='8'" . ($rel == 8 ? " SELECTED " : "") . ">8</option>";
        $txt.= "<option value='9'" . ($rel == 9 ? " SELECTED " : "") . ">9</option>";
        $txt.= "<option value='10'" . ($rel == 10 ? " SELECTED " : "") . ">10</option>";
        $txt.= "</select>";
        $xml.= "<cell><![CDATA[" . $txt . "]]></cell>";
        $xml.= "</row>\n";
    }
    $xml.= "</rows>\n";
} else {
    $xml.= "<rows>\n<page>$page</page>\n<total>0</total>\n</rows>\n";
}
echo $xml;
$db->close($conn);
?>


