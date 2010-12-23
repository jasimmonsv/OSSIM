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
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_scan.inc');
require_once ('classes/Net_sensor_reference.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');


Session::logcheck("MenuPolicy", "PolicyNetworks");

$db = new ossim_db();
$conn = $db->connect();

$net_name  =  GET('name');
$clone     =  ( GET('clone') == 1 ) ? 1 : 0;

$array_assets  = array ("1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5");

$info_CIDR = "<div style='font-weight:normal; width: 170px;'>
				<div><span class='bold'>Format:</span> CIDR [,CIDR,...]</div>
				<div><span class='bold'>CIDR:</span> xxx.xxx.xxx.xxx/xx</div>
			</div>";
		
	
if ( isset($_SESSION['_net']) )
{
	$net_name    = $_SESSION['_net']['net_name'];
	$cidr        = $_SESSION['_net']['cidr'];  	
	$descr       = $_SESSION['_net']['descr'];  
	$asset       = $_SESSION['_net']['asset'];  	
	$sensors     = $_SESSION['_net']['sensors'];    
	$threshold_a = $_SESSION['_net']['threshold_a']; 
	$threshold_c = $_SESSION['_net']['threshold_c']; 
	$rrd_profile = $_SESSION['_net']['rrd_profile'];  
	$nagios      = $_SESSION['_net']['nagios'];
	
	unset($_SESSION['_net']);
	
}
else
{
	$conf = $GLOBALS["CONF"];
	$threshold_a = $threshold_c = $conf->get_conf("threshold");
	$descr    = "";
	$sensors  = array();
	
	if ($net_name != '')
	{
		ossim_valid($net_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, OSS_SQL, 'illegal:' . _(" Network Name"));
		
		if (ossim_error()) 
			die(ossim_error());			
			
		if ($net_list = Net::get_list($conn, "WHERE name = '$net_name'")) {
			$net   		 = $net_list[0];
			$descr 		 = $net->get_descr();
			$cidr 		 = $net->get_ips();
			$asset 		 = $net->get_asset();
			$threshold_a = $net->get_threshold_a();
			$threshold_c = $net->get_threshold_c();
			$nagios      =  ( Net_scan::in_net_scan($conn, $net_name, 2007)) ? "1" : ''; 
			
			$rrd_profile = $net->get_rrd_profile();
			if (!$rrd_profile) 
				$rrd_profile = "None";
				
			$tmp_sensors = $net->get_sensors($conn);
			
			foreach($tmp_sensors as $sensor) 
				$sensors[] = $sensor->get_sensor_name();
		}
	}
	
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
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
			
			$('textarea').elastic();
				
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "newnet.php");
			});
			
		});
	</script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 400px;}";
		    echo "#table_form th {width: 130px;}";
		}
		else
		{
			echo "#table_form {background: transparent; width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
	</style>
</head>

<body>
                                                                                
<?php

if (GET('name') != "" || GET('clone') == 1)
	$action = "modifynet.php";
else
	$action = "newnet.php";
	
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
	
?>																				
																				
<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="post" name='formnet' id='formnet' action="<?php echo $action?>">
	<table align="center" id='table_form'>
	<input type="hidden" name="insert" value="insert"/>
	<input type="hidden" name="clone" value="<?php echo $clone?>"/>
	<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
	<tr>
		<th><label for='netname'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<?php if (GET('name') == "" || GET('clone') == 1) {?>
				<input type='text' name='netname' id='netname' class='vfield req_field' value="<?php echo $net_name?>"/>
				<span style="padding-left: 3px;">*</span>
			<?php } 
				  else {
			?>	
				<input type='hidden' name='netname' id='netname' class='vfield req_field' value="<?php echo $net_name?>"/>
				<div class='bold'><?php echo $net_name?></div>
			<?php }  ?>
		</td>
    </tr>
	
    <tr>
		<th>
			<label for='cidr'><?php echo gettext("CIDRs"); ?></label>
			<a style="cursor:pointer; text-decoration: none;" class="cidr_info" txt="<?=$info_CIDR?>">
				<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
			</a>
		</th>
		<td class="left">
			<textarea name="cidr" id="cidr" class='vfield req_field'><?php echo $cidr?></textarea>
			<span style="padding-left: 3px; vertical-align: top;">*</span>
		</td>
	</tr>

	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
		<td class="left">
			<textarea name="descr" id='descr' class='vfield'><?php echo $descr?></textarea>
		</td>
	</tr>

	<tr>
		<th><label for='asset'><?php echo gettext("Asset"); ?></label></th>
		<td class="left">
			<select name="asset" id="asset" class='vfield req_field'>
			<?php 
				if ( !in_array($asset, $array_assets) )
					$asset = "2";
				
				foreach ($array_assets as $v)
				{
					$selected = ($asset == $v) ? "selected='selected'" : '';
					echo "<option value='$v' $selected>$v</option>";
				}
			?>
			</select>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>  
  
	<tr>
		<th> 
			<label for='sboxs1'><?php echo gettext("Sensors");?></label>
			<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'>Define which sensors has visibility of this host</div>">
				<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
			</a><br/>
			<span><a href="../sensor/newsensorform.php"><?php echo gettext("Insert new sensor"); ?> ?</a></span>
		</th>
		<td class="left">
			<?php
			/* ===== Sensors ==== */
			$i = 1;
			
			if ($sensor_list = Sensor::get_all($conn, "ORDER BY name"))
			{
				foreach($sensor_list as $sensor) {
					$sensor_name = $sensor->get_name();
					$sensor_ip = $sensor->get_ip();
													
					$class = ($i == 1) ? "class='req_field'" : "";
													
					$sname = "sboxs".$i;
					$checked = ( in_array($sensor_name, $sensors) )  ? "checked='checked'"  : '';
					
					echo "<input type='checkbox' name='sboxs[]' $class id='$sname' value='$sensor_name' $checked/>";
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
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo _("Advanced");?></a>
		</td>
	</tr>
	
	<tr class="advanced" style="display:none;">
		<th><label for='nagios'><?php echo gettext("Scan options"); ?></label></th>
		<td class="left">
            <?php $checked = ($nagios == '1') ? "checked='checked'" : ''; ?>		
			<input type="checkbox" class='vfield' name="nagios" id="nagios" value="1" <?php echo $checked;?>/> <?php echo gettext("Enable nagios"); ?>
		</td>
	</tr>

  
	<tr class="advanced" style="display:none;">
		<th> 
			<label for='rrd_profile'><?php echo gettext("RRD Profile"); ?></label><br/>
			<span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
		</th>
		<td class="left">
			<select name="rrd_profile" id='rrd_profile' class='vfield'>
				<option value="" selected='selected'><?php echo gettext("None"); ?></option>
				<?php
				foreach(RRD_Config::get_profile_list($conn) as $profile) {
					if (strcmp($profile, "global"))
					{
						$selected = ( $rrd_profile == $profile  ) ? " selected='selected'" : '';
						echo "<option value=\"$profile\" $selected>$profile</option>\n";
					}
				}
				?>
			</select>
		</td>
	</tr>

	<tr class="advanced" style="display:none;">
		<th><label for='threshold_c'><?php echo gettext("Threshold C"); ?></label></th>
		<td class="left">
			<input type="text" name="threshold_c" id='threshold_c' class='req_field vfield' value="<?php echo $threshold_c?>"/>
			<span style="padding-left: 3px;">*</span>	
		</td>
	</tr>

	<tr class="advanced" style="display:none;">
		<th><label for='threshold_a'><?php echo gettext("Threshold A"); ?></label></th>
		<td class="left">
			<input type="text" name="threshold_a" id='threshold_a' class='req_field vfield' value="<?php echo $threshold_a?>"/>
			<span style="padding-left: 3px;">*</span>	
		</td>
	</tr>

    <tr>
		<td colspan="2" align="center" style="padding: 10px;" class='noborder'>
			<input type="button" class="button" id='send' value="<?php echo _("Update");?>" onclick="submit_form();">
			<input type="reset"  class="button" value="<?=_("Clear form")?>"/>
		</td>
	</tr>
		
</table>

<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

</form>

<?php $db->close($conn); ?>
	</body>
</html>
