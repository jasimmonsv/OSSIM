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
Session::logcheck("MenuPolicy", "PolicyNetworks");
?>

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  
	<script type="text/javascript">
		$(document).ready(function(){

			$(".sensor_info").simpletip({
					position: 'top',
					offset: [-60, -10],
					content: '',
					baseClass: 'ytooltip',
					onBeforeShow: function() {
						var txt = this.getParent().attr('txt');
						this.update(txt);
				}
			});

			$(".cidr_info").simpletip({
					position: 'top',
					offset: [-60, -10],
					content: '',
					baseClass: 'ytooltip',
					onBeforeShow: function() {
						var txt = this.getParent().attr('txt');
						this.update(txt);
				}
			});

		});
	</script>
  
	<style type='text/css'>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		.table_form {width: 450px;}
	</style>
  
	
  
</head>
<body>
                                                                                
<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<?php
require_once 'classes/Net.inc';
require_once 'classes/Net_scan.inc';
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Net_sensor_reference.inc';
require_once 'classes/RRD_config.inc';
require_once 'classes/Security.inc';
$name = GET('name');
$clone = (GET('clone') == "1") ? 1 : 0;
ossim_valid($name, OSS_NET_NAME, 'illegal:' . _("Net name"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if ($net_list = Net::get_list($conn, "WHERE name = '$name'")) {
    $net = $net_list[0];
}
?>

<form method="post" action="modifynet.php">
<table align="center" class='table_form'>
	<input type="hidden" name="clone" value="<?=$clone?>">
	<input type="hidden" name="insert" value="insert">
	<tr>
    <? if ($clone) { ?>
		<th> <?php echo gettext("Netname"); ?></th>
		<td class="left">
			<input type="text" name="name" value="<?php echo $net->get_name(); ?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
    <? } else { ?>
			<th> <?php echo gettext("Netname"); ?></th>
			<input type="hidden" name="name" value="<?php echo $net->get_name(); ?>"/>
			<td class="left"><b><?php echo $net->get_name(); ?></b></td>
    <? } ?>
	</tr>

	<tr>
		<th> <?php echo gettext("CIDRs"); 
			$info_CIDR= "<div style='font-weight:normal; width: 170px;'>
							<div><span style='font-weight: bold'>Format:</span> CIDR [,CIDR,...]</div>
							<div><span style='font-weight: bold'>CIDR:</span> xxx.xxx.xxx.xxx/xx</div>
						</div>";
		
		?>
			<a style="cursor:pointer; text-decoration: none;" class="cidr_info" txt="<?=$info_CIDR?>">
				<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
			</a>
		</th>
		<td class="left">
			<textarea name="ips"><?php echo $net->get_ips(); ?></textarea>
			<span style="padding-left: 3px; vertical-align: top;">*</span>
		</td>
	</tr>

	<tr>
		<th> <?php echo gettext("Description"); ?> </th>
		<td class="left">
			<textarea name="descr"><?php echo $net->get_descr(); ?></textarea>
		</td>
	</tr>

	<tr>
		<th> <?php echo gettext("Asset"); ?></th>
		<td class="left">
			<select name="asset">
				<option <?php if ($net->get_asset() == 0) echo " selected='selected' "; ?> value="0">0</option>
				<option <?php if ($net->get_asset() == 1) echo " selected='selected' "; ?> value="1">1</option>
				<option <?php if ($net->get_asset() == 2) echo " selected='selected' "; ?> value="2">2</option>
				<option <?php if ($net->get_asset() == 3) echo " selected='selected' "; ?> value="3">3</option>
				<option <?php if ($net->get_asset() == 4) echo " selected='selected' "; ?> value="4">4</option>
				<option <?php if ($net->get_asset() == 5) echo " selected='selected' "; ?> value="5">5</option>
			</select><span style="padding-left: 3px;">*</span>
		</td>
	</tr>

	<tr>
		<th> <?php echo gettext("Sensors"); ?>
			<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'>Define which sensors has visibility of this host</div>">
				<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
			</a><br/>
			<span><a href="../sensor/newsensorform.php"><?php echo gettext("Insert new sensor"); ?> ?</a></span>
		</th>
		<td class="left">
			<?php
			/* ===== sensors ==== */
			$i = 1;
			if ($sensor_list = Sensor::get_list($conn, "ORDER BY name"))
			{
				foreach($sensor_list as $sensor) 
				{
					$sensor_name = $sensor->get_name();
					$sensor_ip = $sensor->get_ip();
					if ($i == 1)
						echo "<input type='hidden' name='nsens' value='".count($sensor_list)."'/>";
					
					$name = "mboxs" . $i;
					$checked = ( Net_sensor_reference::in_net_sensor_reference($conn, $net->get_name() , $sensor_name) ) ? "checked='checked'" : '';
					echo "<input type='checkbox' name='$name' value='$sensor_name' $checked/>";
					echo $sensor_ip . " (" . $sensor_name . ")<br/>";
					
					$i++;
				}
			}
			?>
		</td>
	</tr>

	<tr>
		<td style="text-align: left; border:none; padding-top:3px;">
			<a onclick="$('.advanced').toggle()" style="cursor:pointer;">
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/>Advanced</a>
		</td>
	</tr>

	<tr class="advanced" style="display:none;">
		<th> <?php echo gettext("Scan options"); ?> </th>
		<td class="left">
			<!--<input type="checkbox"
			<?php
			if ( Net_scan::in_net_scan($conn, $net->get_name() , 3001)) 
				echo " checked='checked' ";
			
			?>
			name="nessus" value="1"> <?=_("Enable nessus scan")?> </input><br>-->
			
			<?php $checked = ( Net_scan::in_net_scan($conn, $net->get_name() , 2007)) ? "checked='checked'" : ''; ?>
			
			<input type="checkbox" name="nagios" value="1" <?php echo $checked;?>/><?=_("Enable nagios")?>

		</td>
	</tr>

	<tr class="advanced" style="display:none;">
		<th> <?php echo gettext("RRD Profile"); ?><br/>
			<span><a href="../rrd_conf/new_rrd_conf_form.php"> <?php echo gettext("Insert new profile"); ?> ?</a></span>
		</th>
		<td class="left">
			<select name="rrd_profile">
				<?php
				foreach(RRD_Config::get_profile_list($conn) as $profile)
				{
					$net_profile = $net->get_rrd_profile();
					if (strcmp($profile, "global")) {
						$option = "<option value=\"$profile\"";
						if (0 == strcmp($net_profile, $profile)) $option.= " selected='selected' ";
						$option.= ">$profile</option>\n";
						echo $option;
					}
				}
				?>
				<option value="" <?php if (!$net_profile) echo " selected='selected' " ?>><?php echo gettext("None"); ?> </option>

			</select>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>

	<tr class="advanced" style="display:none;">
		<th> <?php echo gettext("Threshold C"); ?></th>
		<td class="left">
			<input type="text" name="threshold_c" size="11" value="<?php echo $net->get_threshold_c(); ?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
  
	<tr class="advanced" style="display:none;">
		<th> <?php echo gettext("Threshold A"); ?></th>
		<td class="left">
			<input type="text" name="threshold_a" size="11" value="<?php echo $net->get_threshold_a(); ?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
  
	<!--
		<tr>
		<th>Alert</th>
		<td class="left">
		  <select name="alert">
			<option <?php //if ($net->get_alert() == 1) echo " SELECTED ";
	 ?>
				value="1">Yes</option>
			<option <?php //if ($net->get_alert() == 0) echo " SELECTED ";
	 ?>
				value="0">No</option>
		  </select>
		</td>
	  </tr>
	  <tr>
		<th>Persistence</th>
		<td class="left">
		  <input type="text" name="persistence" size="3"
				 value="<?php //echo $net->get_persistence();
	 ?>">min.
		</td>
	  </tr>
	-->
  
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="submit" value="<?=_("OK")?>" class="button"/>
			<input type="reset" value="<?=_("Reset")?>" class="button"/>
		</td>
	</tr>
</table>
</form>

<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

<?php $db->close($conn); ?>

	</body>
</html>

