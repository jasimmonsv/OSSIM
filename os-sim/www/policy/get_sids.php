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
require_once 'classes/Security.inc';
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
//Session::logcheck("MenuIntelligence", "PolicyPluginGroups");
Session::logcheck("MenuConfiguration", "PluginGroups");
require_once 'ossim_db.inc';
require_once 'classes/Plugin_sid.inc';

$id = GET('id');

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
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page"));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("rp"));
ossim_valid($search, OSS_TEXT, OSS_NULLABLE, 'illegal:' . _("search"));
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "plugin_id";

$where = "";
if (!empty($search) && !empty($field)) $where.= " AND $field like '%" . $search . "%'";
$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";
$db = new ossim_db();
$conn = $db->connect();
$xml = "";
//if ($plugin_list = Plugin::get_list($conn, "$where ORDER BY $order $limit")) {
if ($plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id$where ORDER BY $order $limit")) {
    $total = $plugin_list[0]->get_foundrows();
    if ($total == 0) $total = count($plugin_list);
    $xml.= "<rows>\n";
    $xml.= "<page>$page</page>\n";
    $xml.= "<total>$total</total>\n";
    foreach($plugin_list as $plugin) {
        $id = $plugin->get_sid();
        $name = $plugin->get_name();
        $rel = $plugin->get_reliability();
        $prio = $plugin->get_priority();
		$xml.= "<row id='$id'>";
       // $lnk = "<a href='../conf/pluginsid.php?id=$id'>$id</a>";
       $lnk = $id;
        $xml.= "<cell><![CDATA[" . $lnk . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $name . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $rel . "]]></cell>";
        $xml.= "<cell><![CDATA[" . $prio . "]]></cell>";
        $xml.= "</row>\n";
    }
    $xml.= "</rows>\n";
}
echo $xml;
$db->close($conn);
exit;
?>





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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyPluginGroups");
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
</head>
<body>

<?php
require_once 'classes/Security.inc';
$id = GET('id');
$field = GET('field');
ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Plugin id"));
ossim_valid($field, OSS_DIGIT, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Text field"));
$sids = explode(",", $field);
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
require_once 'classes/Plugin_sid.inc';
$db = new ossim_db();
$conn = $db->connect();
?>
<script>
	function chk() {
		var sids = "";
		$("input[type=checkbox][checked]").each(function() {
			sids = sids + (sids=="" ? "" : ",") + $(this).val();
		});
		parent.changefield(sids);
		parent.GB_hide();
	}
	function onlyall() {
		$('input:checked').each(function() { $(this).attr('checked', false); });
		$('input[name=sid0]').attr('checked', true);
	}
	function uncheckall() {
		if ($('input[name=sid0]').is(':checked')) $('input[name=sid0]').attr('checked', false);
	}
	function selectmatches() {
		var re = new RegExp($('#qmatch').val(),"ig");
		var n=0;
		$('.matchall').each(function() {
			var name = $(this).html();
			if (name.match(re)) {
				chkid = $(this).attr('id');
				$('input[name='+chkid+']').attr('checked', true);
				n++;
			}
		});
		if (n>0) uncheckall();
		$('#mrp').html('['+n+' matched sids selected]');
	}
</script>
<form name="fo">
<table class="noborder" width="100%"><tr>
<td class="noborder left"><input type="checkbox" value="ANY" onclick="onlyall()" name="sid0" <?php echo (in_array("0", $sids) || in_array("ANY", $sids)) ? "checked" : "" ?>><b><?=_("ALL")?></b></td>
<td class="noborder center">
<?=_("Match all")?> <input type="text" id="qmatch" name="qmatch" size="15"> <input type="button" value="<?php echo _("Select Matches") ?>" onclick="selectmatches()" class="btn" style="font-size:11px"> <span id="mrp" class="small"></span>
</td>
<td class="noborder" style="text-align:right"><input type="button" value="<?php echo _("Accept") ?>" onclick="chk()" class="btn" style="font-size:12px"></td>
</tr></table>
<table align="center" width="100%">
    <tr>
        <th>&nbsp;</th>
        <th><?php echo _("SID") ?></th>
        <th><?php echo _("Name") ?></th>
        <th><?php echo _("R") ?></th>
        <th><?php echo _("P") ?></th>
    </tr>
<?php
$sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id");
$i = 0;
foreach($sid_list as $sid) {
    $bgcolor = ($i++ % 2 == 0) ? "bgcolor='#eeeeee'" : "";
?>
    <tr <?php echo $bgcolor ?>>
        <td class="noborder"><input type="checkbox" value="<?php echo $sid->get_sid() ?>" onclick="uncheckall()" name="sid<?php echo $i ?>" <?php echo (in_array($sid->get_sid() , $sids)) ? "checked" : "" ?>></td>
        <td class="noborder"><?php echo $sid->get_sid() ?></td>
        <td class="noborder left matchall" id="sid<?php echo $i ?>"><?php echo $sid->get_name() ?></td>
        <td class="noborder"><?php echo $sid->get_reliability() ?></td>
        <td class="noborder"><?php echo $sid->get_priority() ?></td>
    </tr>
<?php
}
$db->close($conn);
?>
</table>
<p style="text-align:right;margin:2px"><input type="button" value="<?php echo _("Accept") ?>" onclick="chk()" class="btn" style="font-size:12px"></p>
</form>
</body>
</html>
