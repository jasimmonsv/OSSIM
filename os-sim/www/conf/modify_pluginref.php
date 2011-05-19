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
Session::logcheck("MenuIntelligence", "CorrelationCrossCorrelation");
//ini_set('memory_limit', '256M');

require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
$plugin_id1  = GET('plugin_id1');
$plugin_id2  = GET('plugin_id2');
$plugin_sid1 = GET('plugin_sid1');
$plugin_sid2 = GET('plugin_sid2');

$id1  = GET('id');
$id2  = GET('ref_id');
$sid1 = GET('sid');
$sid2 = GET('ref_sid');

ossim_valid($plugin_id1, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Plugin_id1"));
ossim_valid($plugin_id2, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Plugin_id2"));
ossim_valid($plugin_sid1, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Plugin_sid1"));
ossim_valid($plugin_sid2, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Plugin_sid2"));
ossim_valid($id1, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Id"));
ossim_valid($sid1, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Sid"));
ossim_valid($id2, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Ref_id"));
ossim_valid($sid2, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Ref_sid"));

ossim_valid($order, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, 'illegal:' . _("Order"));
ossim_valid($sup, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Sup"));
ossim_valid($inf, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Inf"));

if (ossim_error()) {
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();

require_once 'classes/Plugin_reference.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';

if ( $plugin_id1!="" && $plugin_id2!="" && $plugin_sid1!="" && $plugin_sid2!="" && $id1!="" && $id2!="" && $sid1!="" && $sid2!="" ) 
{
	$error = Plugin_reference::change_rule ($conn, $plugin_id1,$plugin_id2,$plugin_sid1,$plugin_sid2, $id1,$id2,$sid1,$sid2);
	$message = ( $error ) ? "<div class='ossim_error'>"._("Error changing reference")."</div>" : "<div class='ossim_success'>"._("Reference successfully updated")."</div>";
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Plugin reference"); ?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
  
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	
	<script type="text/javascript">
		function load_sid (num,id) {
			
			if (num == 1) $("#sid1").html("<img src='../pixmaps/loading.gif' width='20' alt='<?php echo _("Loading")?>' align='absmiddle'/><span style='margin-left:5px'><?php echo _("Loading")?>...</span>");
			if (num == 2) $("#sid2").html("<img src='../pixmaps/loading.gif' width='20' alt='<?php echo _("Loading")?>' align='absmiddle'/><span style='margin-left:5px'><?php echo _("Loading")?>...</span>");
				
			
			$.ajax({
				type: "GET",
				url: "pluginrefrules_ajax.php",
				data: { plugin_id:id, num:num },
				success: function(msg) {
					//alert(msg);
					if (num == 1) $("#sid1").html(msg);
					if (num == 2) $("#sid2").html(msg);
				}
			});
		}
		
		function formsubmit () {
			if (document.frules.plugin_id1.value != ''
			&& document.frules.plugin_id2.value != ''
			&& document.frules.plugin_sid1.value != ''
			&& document.frules.plugin_sid2.value != '') {
				document.frules.submit();
			}
			else alert ("<?php echo _('Must select Plugin ID/Plugin SID pair')?>");
		}
		
		$(document).ready(function(){
			<?php if ($message != "") { ?>
			setTimeout("document.location = 'pluginref.php'",4000);
			<?php } ?>
		});
		
</script>

<style type='text/css'>
	#message {
		width: 60%;
		margin: 10px auto;
	}
	
	#ccr_title {padding: 5px 0px;}
	
	.ossim_error, .ossim_success { width: auto; text-align: center;}
</style>

</head>
<body>

<?php
include ("../hmenu.php");

$plugin_list = Plugin::get_list($conn, "ORDER BY name", 0);

?>

<form name="frules" method="get">
	<input type="hidden" name="id" value="<?php echo $id1?>"/>
	<input type="hidden" name="ref_id" value="<?php echo $id2?>"/>
	<input type="hidden" name="sid" value="<?php echo $sid1?>"/>
	<input type="hidden" name="ref_sid" value="<?php echo $sid2?>"/>

	<input type="hidden" name="plugin_sid1" value="<?php echo $sid1?>"/>
	<input type="hidden" name="plugin_sid2" value="<?php echo $sid2?>"/>
	
	<?php if ($message != "") { ?>
	<div id="message"><?php echo $message ?></div>
	<?php } ?>

	<table align="center">


	<?php

	if ($message=="")
	{ 
	?>
	<tr><th id='ccr_title' colspan="2" ><?php echo _("Change Cross-Correlation rule") ?></th></tr>
	
	<tr>
        <td class="nobborder" style="text-align:center;padding:20px"><?php echo _("Plugin ID:")?> 
			<select name="plugin_id1" onchange="load_sid(1,document.frules.plugin_id1.value);">
				<option value=""><?php echo _('Select Plugin ID')?>
				<?php
				foreach($plugin_list as $plugin) 
				{
					$id = $plugin->get_id();
					$plugin_name = $plugin->get_name();
					
					?>
						<option value="<?php echo $id?>" <? if ($id == $id1) echo "selected='selected'"?>><?php echo $plugin_name?>
					<?php 
				} 
				?>
			</select>
		</td>
		
		<td class="nobborder" style="text-align:center;padding:20px"><?php echo _('Reference ID')?>:
			<select name="plugin_id2" onchange="load_sid(2,document.frules.plugin_id2.value);">
				<option value=""><?php echo _('Select Reference ID')?>
				<?php
				foreach($plugin_list as $plugin) 
				{
					$id = $plugin->get_id();
					$plugin_name = $plugin->get_name();
					?>
					<option value="<?php echo $id?>"<? if ($id == $id2) echo "selected='selected'"?>><?php echo $plugin_name?>
					<?php
					} 
				?>
			</select>
		</td>
    </tr>
	  
	<tr>
		<td id="sid1" class="nobborder" style="text-align:center;padding:20px">
			<?php $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id1 ORDER BY name", 0);?>
			<?php echo _('Plugin SID')?>:
			<select id="sidajax1" onchange="document.frules.plugin_sid1.value=this.value">
				<option value=""><?php echo _('Select Plugin SID')?>
				<?php
				foreach($plugin_list as $plugin) 
				{
					?>
					<option value="<?php echo $plugin->get_sid()?>" <? if ($plugin->get_sid() == $sid1) echo " selected='selected'"?>><?=preg_replace("/(.............................).*/","\\1[...]",$plugin->get_name())?>
					<?php
				}
				?>
			</select>
		</td>
		
		<td id="sid2" class="nobborder" style="text-align:center;padding:20px">
			<?php
			$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id2 ORDER BY name", 0); ?>
			<?php echo _('Reference SID')?>:
			<select id="sidajax2" onchange="document.frules.plugin_sid2.value=this.value">
				<option value=""><?php echo _('Select Reference SID')?>
				<?php
				foreach($plugin_list as $plugin) 
				{
					?>
					<option value="<?=$plugin->get_sid()?>" <? if ($plugin->get_sid() == $sid2) echo " selected"?>><?=preg_replace("/(.............................).*/","\\1[...]",$plugin->get_name())?>
					<?php
				}
				?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" class="nobborder" style="text-align:center; padding: 10px;">
			<input type="button" class="button" value="<?php echo _('Change rule')?>" onclick="formsubmit()"/>&nbsp;
			<input type="button" class="button" value="<?php echo _('BACK')?>" onclick="document.location='pluginref.php'"/>
		</td>
	</tr>
	<?php } ?>
	</table>

</form>	
</body>

<?php $db->close($conn); ?>

</html>
