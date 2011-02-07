<?php
/*****************************************************************************
*
*    License:
*
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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Security.inc');
require_once ('classes/InventorySearch.inc');
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$del_type = GET('del_type');
$del_subtype = GET('del_subtype');
ossim_valid($del_type, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("del_type"));
ossim_valid($del_subtype, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("del_subtype"));
if (ossim_error()) {
    die(ossim_error());
}
if ($del_type != "" && $del_subtype != "") {
	InventorySearch::delete($conn,$del_type,$del_subtype);
}

$db_rules = InventorySearch::get_all($conn);
$matches = InventorySearch::get_matches($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript">
function show_content (i) {
	document.getElementById('content'+i).style.display = "inline";
	document.getElementById('link'+i).innerHTML = "<a href='' onclick='hide_content(\""+i+"\");return false;'><img src='../pixmaps/minus-small.png' border='0' align='absmiddle'></a>";
}
function hide_content (i) {
	document.getElementById('content'+i).style.display = "none";
	document.getElementById('link'+i).innerHTML = "<a href='' onclick='show_content(\""+i+"\");return false;'><img src='../pixmaps/plus-small.png' border='0' align='absmiddle'></a>";
}
</script>
</head>
<body onUnload="opener.recarga()">
<table width="100%">
	<tr>
		<th colspan="2"><a href="editrule.php"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle" border="0"> <?=_("NEW FILTER RULE")?></a></th>
	</tr>
	<tr><td class="nobborder">&nbsp;</td></tr>
	<? $i=1; foreach ($db_rules as $rule) { ?>
	<tr>
		<td class="nobborder" width="10" id="link<?=$i?>"><a href="" onclick="show_content(<?=$i?>);return false;"><img src="../pixmaps/plus-small.png" border="0" align="absmiddle"></a></td>
		<th nowrap style="text-align:left"><b><?=$rule->get_type()?></b> -> <b><?=$rule->get_subtype()?></b> (<i><?=$rule->get_match()?></i>)</th>
	</tr>
	<tr>
		<td class="nobborder"></td>
		<td class="nobborder">
			<div id="content<?=$i?>" style="display:none">
			<table class="noborder" width="100%">
			<tr>
				<td><b><?php echo gettext("QUERY"); ?></b>:<br><?=$rule->get_query()?></td>
			</tr>
			<? if ($rule->get_prelist() != "") { ?>
			<tr>	
				<td><i><b><?php echo gettext("Predefined List"); ?></b></i>:<br><?=$rule->get_prelist()?></td>
			</tr>
			<? } ?>
			<tr>
				<td class="nobborder" style="text-align:center">
					<a href="editrule.php?type=<?=$rule->get_type()?>&subtype=<?=$rule->get_subtype()?>"><img src="../pixmaps/tables/table_edit.png" border="0"></a>
					<a href="editrules.php?del_type=<?=$rule->get_type()?>&del_subtype=<?=$rule->get_subtype()?>" onclick="if(!confirm('<?=_("Are you sure")?>?')) return false;"><img src="../pixmaps/tables/table_row_delete.png" border="0"></a>
				</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
	<? $i++; } ?>
</table>
</body>
</html>
