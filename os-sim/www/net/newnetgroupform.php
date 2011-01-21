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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_sensor_reference.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_group_scan.inc');
require_once ('classes/RRD_config.inc');


Session::logcheck("MenuPolicy", "PolicyNetworks");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
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
			$('textarea').elastic();
				
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "newnetgroup.php");
			});
		});
	</script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 400px;}";
		    echo "#table_form th {width: 120px;}";
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

$db = new ossim_db();
$conn = $db->connect();

$ngname = GET('name');

if ( isset($_SESSION['_netgroup']) )
{
	$ngname       = $_SESSION['_netgroup']['ngname'];    
	$networks     = $_SESSION['_netgroup']['networks'];
	$descr        = $_SESSION['_netgroup']['descr'];       
	$threshold_a  = $_SESSION['_netgroup']['threshold_a']; 
	$threshold_c  = $_SESSION['_netgroup']['threshold_c']; 
	$rrd_profile  = $_SESSION['_netgroup']['rrd_profile'];  
	
	
	unset($_SESSION['_netgroup']);
}
else
{
	$conf = $GLOBALS["CONF"];
	$threshold_a = $threshold_c = $conf->get_conf("threshold");
	$descr  = "";
	$networks  = array();
	
	if ($ngname != '')
	{
		ossim_valid($ngname, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, OSS_SQL, 'illegal:' . _(" Network Group Name"));
		
		if (ossim_error()) 
			die(ossim_error());			
			
		if ($net_group_list = Net_group::get_list($conn, "name = '$ngname'")) {
			$net_group = $net_group_list[0];

			$descr        = $net_group->get_descr();
			$threshold_c  = $net_group->get_threshold_c();
			$threshold_a  = $net_group->get_threshold_a();
			$obj_networks = $net_group->get_networks($conn);
			
			foreach($obj_networks as $net)
				$networks[] = $net->get_net_name();
																				
			$rrd_profile = $net_group->get_rrd_profile();
			if (!$rrd_profile) 
				$rrd_profile = "None";
																		
		}
	}
	
}

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

if (GET('name') != "" || GET('clone') == 1)
	$action = "modifynetgroup.php";
else
	$action = "newnetgroup.php";

?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form name='form_ng' id='form_ng' method="POST" action="<?php echo $action;?>">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" id='table_form'>
	
	<tr>
		<th><label for='ngname'><?php echo gettext("Name"); ?></label></th>
			
		<td class="left">
			<?php if (GET('name') == "" ) {?>
				<input type='text' name='ngname' id='ngname' class='vfield req_field' value="<?php echo $ngname?>"/>
				<span style="padding-left: 3px;">*</span>
			<?php } else { ?>	
				<input type='hidden' name='ngname' id='ngname' class='vfield req_field' value="<?php echo $ngname?>"/>
				<div class='bold'><?php echo $ngname?></div>
			<?php }  ?>
		</td>
		
	</tr>

	<tr>
		<th> 
			<label for='mboxs1'><?php echo gettext("Networks");?></label><br/>
			<span><a href="newnetform.php"> <?php echo gettext("Insert new network"); ?> ?</a></span>
		</th> 
				
		<td class="left">
						
			<?php
			/* ===== Networks ==== */
			$i = 1;
			if ($network_list = Net::get_list($conn)) 
			{
				foreach($network_list as $network) 
				{
					$net_name = $network->get_name();
					$net_ips  = $network->get_ips();
					
					$class = ($i == 1) ? "class='req_field'" : "";
					
					$name = "mboxs".$i;
					$checked = ( in_array($net_name, $networks) )  ? "checked='checked'"  : '';
										
					echo "<input type='checkbox' name='mboxs[]' id='$name' $class value='$net_name' $checked/>";
					echo $net_name . " (" . $net_ips . ")<br/>"; 
      
					$i++;
				}
			}
			?>
						
			
		</td>
	</tr>

	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label><br/>
		<td class="left"><textarea name="descr" id='descr' class='vfield'><?php echo $descr; ?></textarea></td>
	</tr>
	
	<tr>
		<td style="text-align: left; border:none; padding-top:3px;">
			<a onclick="$('.advanced').toggle()" style="cursor:pointer;">
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo _("Advanced");?></a>
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
			<input type="button" class="button" id='send' value="<?php echo _("Update")?>" onclick="submit_form()">
			<input type="reset" class="button" value="<?=_("Clear form")?>"/>
		</td>
	</tr>
		
</table>

<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

</form>

<?php $db->close($conn); ?>
	</body>
</html>

