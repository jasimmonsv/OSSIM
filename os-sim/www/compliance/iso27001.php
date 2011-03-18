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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "ComplianceMapping");
require_once 'classes/Compliance.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
$db = new ossim_db();
$conn = $db->connect();

$attr = GET('attr');
$table = GET('table');
$ref = GET('ref');
$toggle = GET('toggle');
ossim_valid($attr, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Attribute value"));
ossim_valid($table, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Table value"));
ossim_valid($ref, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Ref value"));
ossim_valid($toggle, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("toggle"));
if (ossim_error()) {
	die(ossim_error());
}
if ($attr != "" && $table != "" && $ref != "") {
	ISO27001::update_attr($conn,$table,$ref,$attr);
}

$groups = ISO27001::get_groups($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - Compliance </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
<script type="text/javascript">
	var toggled = "<?=$toggle?>"; // Subgroup toggled variable
	function toggle_group (id) {
		toggled = id;
		document.getElementById(id).style.display = "inline";
		var button = id+"_button";
		document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"untoggle_group('"+id+"');return false;\"><img src='../pixmaps/minus-small.png' border='0'></a>";
	}
	function untoggle_group (id) {
		toggled = "";
		document.getElementById(id).style.display = "none";
		var button = id+"_button";
		document.getElementById(button).innerHTML = "<a href='javascript:;' onclick=\"toggle_group('"+id+"');return false;\"><img src='../pixmaps/plus-small.png' border='0'></a>";
	}
	function get_plugins (ref) {
		var td = "SIDS_"+ref;
		document.getElementById(td).innerHTML = "<img src='../pixmaps/loading.gif' alt='Loading'>";
		$.ajax({
			type: "GET",
			url: "plugins_response.php?ref="+ref,
			data: "",
			success: function(msg){
				document.getElementById(td).innerHTML = msg;
				plus = "plus_"+ref;
				document.getElementById(plus).innerHTML = "<a href='' onclick=\"hide_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
			}
		});
	}
	function hide_plugins (ref) {
		var td = "SIDS_"+ref;
		document.getElementById(td).innerHTML = "";
		plus = "plus_"+ref;
		document.getElementById(plus).innerHTML = "<a href='' onclick=\"get_plugins('"+ref+"');return false\"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'></a>";
	}
	// GrayBox
	function GB_onclose () {
		document.location.href='iso27001.php?toggle='+toggled;
	}
	$(document).ready(function(){
		GB_TYPE = 'w';
		$("a.greybox").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,400,'80%');
			return false;
		});
		$("a.greybox_small").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,200,'50%');
			return false;
		});
	});
</script>
</head>
<body>
<? include("../hmenu.php"); ?>

<table class="noborder" style="background-color:white" width="100%">
	<? foreach ($groups as $title=>$data) { ?>
	<tr>
		<? if ($title != $toggle) { ?>
		<td width="10" class="nobborder" id="<?=$title?>_button"><a href="javascript:;" onclick="toggle_group('<?=$title?>');return false;"><img src="../pixmaps/plus-small.png" alt="toggle" border="0"></a></td>
		<? } else { ?>
		<td width="10" class="nobborder" id="<?=$title?>_button"><a href="javascript:;" onclick="untoggle_group('<?=$title?>');return false;"><img src="../pixmaps/minus-small.png" alt="toggle" border="0"></a></td>
		<? } ?>
		<th style="text-align:left;padding:5px"><?=$title." ".preg_replace("/\<\-+|\-+\>/","",$data['title'])?></th>
	</tr>
	<tr>
		<td class="nobborder"></td>
		<td class="nobborder">
		<div id="<?=$title?>" <? if ($toggle != $title) { ?>style="display:none"<? } ?>>
		<table width="100%">
			<tr>
				<td class="nobborder"></td>
				<th><?=_("Security Controls")?></th>
				<th><?=_("Applies")?></th>
				<th><?=_("Operational")?></th>
				<th><?=_("Justification")?></th>
				<th><?=_("Plugins")?></th>
			</tr>
		<? foreach ($data['subgroups'] as $s_title=>$subgroup) { 
				$implemented = ($subgroup['Selected']) ? "<a href='iso27001.php?attr=Implemented&table=".$subgroup['table']."&ref=".$subgroup['Ref']."&toggle=".$title."'>" : "";
		?>
			<tr>
				<td class="nobborder" id="plus_<?=$title?>_<?=$subgroup['Ref']?>"><? if ($subgroup['SIDSS_Ref'] != "") { ?><a href="javascript:;" onclick="get_plugins('<?=$title?>_<?=$subgroup['Ref']?>');return false;"><img src="../pixmaps/plus-small.png" border="0"></a><? } ?></td>
				<td class="nobborder"><b><?=$subgroup['Ref']?></b> <?=$subgroup['Security_controls']?></td>
				<td class="nobborder" style="text-align:center"><a href="iso27001.php?attr=Selected&table=<?=$subgroup['table']?>&ref=<?=$subgroup['Ref']?>&toggle=<?=$title?>"><b><?=($subgroup['Selected'])? "<font style='color:green' alt='"._("Click to set false")."' title='"._("Click to set false")."'>"._("Selected")."</font>" : "<font style='color:red' alt='"._("Click to set true")."' title='"._("Click to set true")."'>"._("Excluded")."</font>"?></b></a></td>
				<td class="nobborder" style="text-align:center"><?=$implemented?><?=($subgroup['Implemented'])? "<img src='../pixmaps/tick.png' border='0' alt='"._("Click to set false")."' title='"._("Click to set false")."'>" : "<img src='../pixmaps/cross.png' border='0' alt='"._("Click to set true")."' title='"._("Click to set true")."'>"?></a></td>
				<td class="nobborder" <? if ($subgroup['Justification'] == "") echo "style='text-align:center'"?>><?=($subgroup['Justification'] != "") ? $subgroup['Justification'] : ""?> <a href="field_edit.php?ref=<?=$subgroup['Ref']?>&table=<?=$subgroup['table']?>&field=Justification&pci=0" class="greybox_small" title="<?= _('Justification') ?>"><img align="absmiddle" src="../pixmaps/tables/table_<?=($subgroup['Justification'] != "") ? "edit" : "row_insert"?>.png" border="0" alt="<?=($subgroup['Justification'] != "") ? _("Edit") : _("Insert")?>" title="<?=($subgroup['Justification'] != "") ? _("Edit") : _("Insert")?>"></td>
				<td class="nobborder" style="text-align:center"><?=($subgroup['SIDSS_Ref'] != "") ? count(explode(",",$subgroup['SIDSS_Ref']))." Ref." : ""?> <a href="plugins_edit.php?ref=<?=$title?>_<?=$subgroup['Ref']?>" class="greybox" title="<?= _('Plugin Sids') ?>"><img align="absmiddle" src="../pixmaps/tables/table_<?=($subgroup['SIDSS_Ref'] != "") ? "edit" : "row_insert"?>.png" border="0" alt="<?=($subgroup['SIDSS_Ref'] != "") ? _("Edit") : _("Insert")?>" title="<?=($subgroup['SIDSS_Ref'] != "") ? _("Edit") : _("Insert")?>"></td>
			</tr>
			<? if ($subgroup['SIDSS_Ref'] != "") { ?>
			<tr><td colspan="7" class="nobborder" id="SIDS_<?=$title?>_<?=$subgroup['Ref']?>"></td></tr>
			<? } ?>
		<? } ?>
		</table>
		</div>
		</td>
	</tr>
	<? } ?>
</table>
</body>
</html>
