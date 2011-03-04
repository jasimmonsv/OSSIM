<?
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
//ini_set('memory_limit', '128M');
require_once 'classes/Security.inc';
require_once 'classes/Plugin_reference.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';
require_once 'ossim_db.inc';

$db          = new ossim_db();
$conn        = $db->connect();
$plugin_list = Plugin::get_list($conn, "ORDER BY name", 0);

include ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");

/* Connect to the Alert database */
$db_snort = NewBASEDBConnection($DBlib_path, $DBtype);
$db_snort->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs       = new QueryState();

$newref = GET('newref');
$delete = GET('deleteref');

ossim_valid($newref, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("newref"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));

if (ossim_error()) {
    die(ossim_error());
}

if ($newref != "")
{
	$sql = "INSERT INTO reference_system (ref_system_name) VALUES (\"$newref\")";
	$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
}

if (preg_match("/^\d+$/",$delete))
{
	$sql = "SELECT sig_reference.ref_id FROM sig_reference,reference WHERE reference.ref_system_id=$delete AND reference.ref_id=sig_reference.ref_id";
	$result = $qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
	$ids = "";
	while ($myrow = $result->baseFetchRow())
	{
		if ($ids != "") $ids .= ",";
		$ids .= $myrow[0];
	}
	
	if ($ids != "")
	{
		$sql = "DELETE FROM sig_reference WHERE ref_id in ($ids)";
		$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
	}
	
	$sql = "DELETE FROM reference_system WHERE ref_system_id=$delete";
	$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
	$sql = "DELETE FROM reference WHERE ref_system_id=$delete";
	$qs->ExecuteOutputQueryNoCanned($sql, $db_snort);
}

$sql       = "SELECT * FROM reference_system";
$result    = $qs->ExecuteOutputQuery($sql, $db_snort);
$ref_types = array();
while ($myrow = $result->baseFetchRow()) {
	$ref_types[] = $myrow;
}
?>

<!-- <?php echo gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION; ?> -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo gettext("iso-8859-1"); ?>">
	<meta http-equiv="pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>

	<script type="text/javascript">

	function GB_onclose() {
		document.location.href= 'manage_references.php';
	}

	function ref_delete (id)
	{
		$.ajax({
			type: "GET",
			url: "manage_references_checkdel.php?id="+id,
			data: "",
			success: function(msg){
				if (msg != "0" && msg != "") {
					if (confirm("<?=_("This reference type is linked in some events (at least ")?>"+msg+"<?=_("). Are you sure to delete?")?>")) {
						document.fdelete.deleteref.value = id;
						document.fdelete.submit();
					}
				} else if (msg == "0") {
					document.fdelete.deleteref.value = id;
					document.fdelete.submit();
				}
			}
		});
	}

	function ref_delete_plugin (plugin_id,plugin_sid,ref_id)
	{
		if (confirm("<?=_("Are you sure to delete reference for plugin_sid ")?>"+plugin_sid+"?")) {
			$.ajax({
				type: "GET",
				url: "manage_references_getrefs.php",
				data: { plugin_id:plugin_id, plugin_sid:plugin_sid, delete_ref_id:ref_id },
				success: function(msg) {
					//alert(msg);
					$("#references_found").html(msg);
				}
			});
		}
	}

	function load_sid (id)
	{
		$("#sid1").html("<img src='../pixmaps/loading.gif' width='20' alt='Loading'>Loading");
		$.ajax({
			type: "GET",
			url: "../conf/pluginrefrules_ajax.php",
			data: { plugin_id:id, num:1, manage:1 },
			success: function(msg) {
				//alert(msg);
				$("#sid1").html(msg);
			}
		});
	}

	function load_refs () {
		plugin_id = document.getElementById('plugin_id1').value;
		plugin_sid = document.getElementById('sidajax1').value;
		$.ajax({
			type: "GET",
			url: "manage_references_getrefs.php",
			data: { plugin_id:plugin_id, plugin_sid:plugin_sid },
			success: function(msg) {
				//alert(msg);
				$("#references_found").html(msg);
			}
		});
	}

	function formsubmit () {
		
		var plugin_id    = document.frules.plugin_id1.value;
		var plugin_sid   = document.frules.plugin_sid1.value;
		var newref_type  = document.frules.newref_type.value;
		var newref_value = document.frules.newref_value.value;
		
		if (plugin_id!= '' 	&& plugin_sid != '' & newref_type != '' &&  newref_value) 
		{
			$.ajax({
				type: "GET",
				url: "manage_references_getrefs.php",
				data: { plugin_id:plugin_id, plugin_sid:plugin_sid, newref_type:newref_type, newref_value:newref_value },
				success: function(msg) {
					//alert(msg);
					$("#references_found").html(msg);
				}
			});
		}
		else alert ("<?php echo _("Must select Data Source/Event Type pair and type a value")?>");
	}


	// GreyBox
	$(document).ready(function(){
		
		GB_TYPE = 'w';
		$("a.greybox").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,210,'40%');
			return false;
		});
	});


</script>
</head>
<body>
<?
if (GET('withoutmenu') != "1") include ("../hmenu.php");
?>
<table class="transparent" width="95%" align="center">
	<tr>
		<td class="nobborder">
			<table class="transparent" align="center" width="100%" height="100%">
			<tr>
				<td valign="top" class="nobborder" width="55%">
					<table class="transparent" height="100%">
						<tr>
							<td class="nobborder">
								<table height="100%">
									<tr><th colspan="5" height="20"><?=_("Reference Types")?></th></tr>
									<form method="get" name="fdelete">
									<input type="hidden" name="deleteref" value=""/>
									<?php $i=1; foreach ($ref_types as $myrow) { $color=($i%2==0)?"#FFFFFF":"#F2F2F2";?>
									<tr bgcolor="<?=$color?>">
										<td class="center nobborder"><img src='manage_references_icon.php?id=<?=$myrow[0]?>' border=0></td>
										<td class="nobborder"><?=$myrow[1]?></td>
										<td class="nobborder"><?=str_replace("%value%","<b>%value%</b>",$myrow[3])?></td>
										<td class="nobborder"><a href="manage_references_modifysys.php?id=<?=$myrow[0]?>" title="Edit Reference" class="greybox"><img src="../pixmaps/tables/table_edit.png" alt="<?=_("Edit")?>" title="<?=_("Edit")?>" border="0"></a></td>
										<td class="nobborder"><a style='cursor:pointer;' onclick="ref_delete(<?=$myrow[0]?>);return false;"><img src="../pixmaps/tables/table_row_delete.png" alt="<?=_("Delete")?>" title="<?=_("Delete")?>" border="0"/></a></td>
									</tr>
									<?php $i++; } ?>
									</form>
								</table>
							</td>
						</tr>
						
						<form method="get" name="fadd">
						<tr>
							<td height="70" class="center nobborder" valign="top"><b><?=_("New reference type")?></b>: <input type="text" value="" name="newref"><br><br><input type="button" class="button" onclick="document.fadd.submit();return false;" alt="<?=_("Add new")?>" title="<?=_("Add new")?>" value="<?=_("Insert")?>"></a></td>
						</tr>
						</form>
					</table>
				</td>
				
				<td valign="top" class="nobborder" style="padding-top:2px" width="45%">
					<table width="100%" height="100%" class="transparent">
						<form name="frules" method="get">
						<tr>
							<td class="nobborder" valign="top">
								<table height="100%" width="100%">
								<tr><th height="20"><?php echo _("New Reference")?></th></tr>
								<input type="hidden" name="plugin_sid1" value="">
									<?php if ($message != "") { ?>
									<tr><td class="" id="message" class="nobborder" style="text-align:center">
									<?php echo $message?>
									</td></tr>
									<?php } ?>
									<tr>
										<td class="left nobborder"><?=_("Reference Type")?>:
											<select name="newref_type" id="newref_type">
												<?php foreach ($ref_types as $myrow) { ?>
												<option value="<?=$myrow[0]?>"><?=$myrow[1]?>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="left nobborder"><?=_("Data Source ID")?>: 
										<select name="plugin_id1" id="plugin_id1" onchange="load_sid(document.frules.plugin_id1.value);" style="width:202px">
										<option value=""><?=_("Select Data Source ID")?>
											<?php
											foreach($plugin_list as $plugin) {
												$id = $plugin->get_id();
												$plugin_name = $plugin->get_name();
											?>
												<option value="<?=$id?>"><?=$plugin_name?>
											<?php } ?>
												</select>
										</td>
									</tr>
							  
									<tr>
										<td id="sid1" class="left nobborder"><?=_("Event Type ID")?>:
											<select name="" disabled style="width:200px">
												<option value=""><?=_("Select Event Type ID")?>
											</select>
										</td>
									</tr>
									
									<tr><td id="references_found" class="nobborder"></td></tr>
								</table>
							</td>
						</tr>
				
						<tr>
							<td height="70" class="center nobborder" valign="top">
								<strong><?=_("Ref Value")?></strong>: <input style='width: 250px;' type="text" name="newref_value" value=""/><br/><br/>
								<input type="button" class="button" value="<?=_("Create Reference")?>" id="create_button" onclick="formsubmit()">
							</td>
						</tr>
						</form>	
					</table>
				</td>
			</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>