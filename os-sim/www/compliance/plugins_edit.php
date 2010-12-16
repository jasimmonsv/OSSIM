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
require_once ('classes/Security.inc');
require_once ('classes/Compliance.inc');
require_once ('classes/Plugin_sid.inc');

function get_sids ($sids) {
	$sids_aux = explode(",",$sids);
	$sids_keys = array();
	foreach ($sids_aux as $sid) {
		$sids_keys[$sid] = true;
	}
	return $sids_keys;
}

require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
$db = new ossim_db();
$conn = $db->connect();

$ref = (GET('ref') != "") ? explode ("_",GET('ref')) : explode ("_",POST('ref'));
$pci = (GET('pci') != "") ? 1 : (POST('pci') ? 1 : 0);

ossim_valid($ref[0], OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',', 'illegal:' . _("ref"));
ossim_valid($ref[1], OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',', 'illegal:' . _("ref"));
if (ossim_error()) {
	die(ossim_error());
}

$groups = ($pci) ? PCI::get_groups($conn) : ISO27001::get_groups($conn);

if (POST('remove') != "") {
	$removesid = POST('remove');
	$sids = POST('sids');
	ossim_valid($removesid, OSS_DIGIT, 'illegal:' . _("remove"));
	ossim_valid($sids, OSS_DIGIT, ',', 'illegal:' . _("sids"));
	if (ossim_error()) {
		die(ossim_error());
	}
	
	$sids_keys = get_sids($sids);
	unset($sids_keys[$removesid]);
	$sids_str = preg_replace("/^\,|\,$/","",implode(",",array_keys($sids_keys)));
	$table = $groups[$ref[0]]['subgroups'][$ref[1]]['table'];
	if ($pci) PCI::update_sids($conn,$table,$ref[1],$sids_str);
	else ISO27001::update_sids($conn,$table,$ref[1],$sids_str);
	$groups = ($pci) ? PCI::get_groups($conn) : ISO27001::get_groups($conn);
} elseif (POST('newsid') != "") {
	$newsids = POST('newsid');
	$sids = POST('sids');
	ossim_valid($sids, OSS_DIGIT, OSS_NULLABLE, ',', 'illegal:' . _("sids"));
	if (ossim_error()) {
		die(ossim_error());
	}
	$sids_keys = get_sids($sids);
	foreach ($newsids as $newsid) {
		ossim_valid($newsid, OSS_DIGIT, 'illegal:' . _("newsid"));
		if (ossim_error()) {
			die(ossim_error());
		}
		if (!$sids_keys[$newsid]) {
			$sids_keys[$newsid] = true;
			$sids_str = preg_replace("/^\,|\,$/","",implode(",",array_keys($sids_keys)));
			$table = $groups[$ref[0]]['subgroups'][$ref[1]]['table'];
			if ($pci) PCI::update_sids($conn,$table,$ref[1],$sids_str);
			else ISO27001::update_sids($conn,$table,$ref[1],$sids_str);
		}
	}
	$groups = ($pci) ? PCI::get_groups($conn) : ISO27001::get_groups($conn);
}

$sids = $groups[$ref[0]]['subgroups'][$ref[1]]['SIDSS_Ref'];
$title = $groups[$ref[0]]['subgroups'][$ref[1]]['Security_controls'];
$sids_keys = get_sids ($sids);
$directives = Plugin_sid::get_list($conn,"WHERE plugin_id=1505");
?>
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - <?=_("Compliance")?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script src="../js/jquery-1.3.2.min.js" language="javascript" type="text/javascript"></script>
</head>
<body>
<table class="noborder" align="center" style="background-color:white">
	<form name="fsids" method="post">
	<input type="hidden" value="<?=$pci?>" name="pci">
	<input type="hidden" value="" name="remove">
	<input type="hidden" name="sids" value="<?=$sids?>">
	<tr><th><?=_("SIDs for")?> <?=$title?></th></tr>
	<tr>
		<td class="" style="padding-top:2px;padding-bottom:10px"><?=_("Associated Values")?>
			<div style="height:100px;overflow:auto">
			<? if ($sids != "") { ?>
			<table class="noborder" width="100%" style="background-color:white">
				<tr>
					<th><?=_("SID")?></th>
					<th><?=_("Name")?></th>
					<th>&nbsp;</th>
				</tr>
				<? $i=1; foreach ($directives as $d) if ($sids_keys[$d->get_sid()]) { $color = ($i%2==0) ? "#F2F2F2" : "F8F8F8";?>
				<tr bgcolor="<?=$color?>">
					<td class="nobborder" style="text-align:center" width="30"><?=$d->get_sid()?></td>
					<td class="nobborder"><?=str_replace("directive_event: ","",$d->get_name())?></td>
					<td class="nobborder" style="text-align:center"><a href="javascript:;" onclick="document.fsids.remove.value='<?=$d->get_sid()?>';document.fsids.submit();return false;"><img src="../pixmaps/tables/table_row_delete.png" border="0" alt="<?=_("Remove")?>" title="<?=_("Remove")?>"></a></td>
				</tr>
				<? $i++; } ?>
				</tr>
			</table>
			<? } ?>
			</div>
		</td>
	</tr>
	<tr>
		<td class="nobborder" style="padding-top:10px;padding-bottom:10px">
			<table width="100%" style="background-color:#EEEEEE">
				<tr>
					<td class="nobborder" style="text-align:center;padding:5px 0px 5px 5px"><?=_("Associate new value")?></td>
				</tr>
				<tr>
					<td class="nobborder" style="padding:0px 5px 5px 5px">
					<select name="newsid[]" multiple="multiple" size="8">
					<? foreach ($directives as $d) if (!$sids_keys[$d->get_sid()]) { ?>
						<option value="<?=$d->get_sid()?>"><?=str_replace("directive_event: ","",$d->get_name())?>
					<? } ?>
					</select>
					</td>
				</tr>
				<tr><td class="nobborder" style="text-align:center;padding:10px"><input type="submit" value="<?=_("ADD")?>" class="button"> <input type="button" onclick="window.parent.GB_onclose()" value="<?=_("Close")?>" class="button"></td></tr>
			</table>
		</td>
	</tr>
	</form>
</table>
</body>
</html>
