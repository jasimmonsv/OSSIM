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
require_once ('classes/RRD_config.inc');
require_once ('classes/Host_scan.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$db = new ossim_db();
$conn = $db->connect();

$array_assets = array ('0'=>'0', "1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5");

$array_os = array ( "Unknown" => "",
					"Microsoft Windows" => "Microsoft Windows",
					"Linux"   			=> "Linux",
					"FreeBSD" 			=> "FreeBSD",
					"NetBSD"  			=> "NetBSD",
					"OpenBSD" 			=> "OpenBSD",
					"Apple MacOSX"   	=> "Apple MacOSX",
					"SUN Solaris"		=> "SUN Solaris",
					"Cisco IOS"   		=> "Cisco IOS",
					"IBM AIX"     		=> "IBM AIX",
					"HP-UX"   			=> "HP-UX",
					"Compaq Tru64"   	=> "Compaq Tru64",
					"SGI IRIX"    		=> "SGI IRIX",
					"BSD\/OS"  			=> "BSD/OS",
					"SunOS"   			=> "SunOS",
					"Plan9"   			=> "Plan9",
					"IPhone"  			=> "IPhone");
					
$sensors     = array();
$conf        = $GLOBALS["CONF"];
$threshold_a = $threshold_c = $conf->get_conf("threshold");
$hostname    = $fqdns = $descr = $nat = $nagios = $os = $mac = $mac_vendor = $latitude = $longitude = "";
$asset       = 2;
$rrd_profile = "None";

$scan         = REQUEST('scan');
$ip           = REQUEST('ip');
$num_ips      = REQUEST('ips');
$type_action  = REQUEST('action');

ossim_valid($ip,   OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Ip"));
ossim_valid($ips,  OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Hosts"));
ossim_valid($scan, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Scan"));
ossim_valid($type_action, "duplicate", OSS_NULLABLE, 'illegal:' . _("Action"));

if (ossim_error()) {
    die(ossim_error());
}

if ( !empty ($scan) )
{
	$groupname = ( isset($_REQUEST['groupname']) ) ? REQUEST('groupname') : $_SESSION['_host']['groupname'];
	$ip        = REQUEST('target');
    	
	$action    = "../netscan/scan_db.php";
	$submit_function = "submit_form()";
	
	for ($i=0; $i<$num_ips; $i++) 
	{
		$item_ip = "ip_$i";
		$ips_address[] = ( isset($_SESSION['_host'][$item_ip]) ) ? $_SESSION['_host'][$item_ip] : POST($item_ip);
	}
}
else
{
	$action = "newhost.php";
	$submit_function  = "check_host()";
}

	
if ( isset($_SESSION['_host']) )
{
	$hostname    = $_SESSION['_host']['hostname'];
	$ip          = $_SESSION['_host']['ip'];  	
	$fqdns       = $_SESSION['_host']['fqdns']; 
	$descr	     = $_SESSION['_host']['descr']; 
	$asset       = $_SESSION['_host']['asset'];
	$nat         = $_SESSION['_host']['nat'];  	
	$sensors     = $_SESSION['_host']['sensors'];  
	$nagios      = $_SESSION['_host']['nagios'];	
	$rrd_profile = $_SESSION['_host']['rrd_profile'];  
	$threshold_a = $_SESSION['_host']['threshold_a']; 
	$threshold_c = $_SESSION['_host']['threshold_c']; 
	$os          = $_SESSION['_host']['os']; 
	$mac         = $_SESSION['_host']['mac']; 
	$mac_vendor  = $_SESSION['_host']['mac_vendor']; 
	$latitude    = $_SESSION['_host']['latitude']; 
	$longitude   = $_SESSION['_host']['longitude']; 
	
	unset($_SESSION['_host']);
}
else
{
	if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
		$host = $host_list[0];
		
	
	if ( !empty($host) )
	{
    	$hostname        = $old_hostname = $host->get_hostname();
		$fqdns           = $host->get_fqdns();
		$descr	         = $host->get_descr();
		$asset           = $host->get_asset();
		$nat             = $host->get_nat();
		
		$tmp_sensors     = $host->get_sensors($conn);
				
		foreach($tmp_sensors as $sensor) 
			$sensors[]   = $sensor->get_sensor_name();
		
		$nagios          =  ( Host_scan::in_host_scan($conn, $ip, 2007)) ? "1" : ''; 
		
		$rrd_profile     = $host->get_rrd_profile();
		
		if (!$rrd_profile) 
			$rrd_profile = "None";
		
		$threshold_a     = $host->get_threshold_a();
		$threshold_c     = $host->get_threshold_c();
		$os              = $host->get_os($conn);
		$mac             = $host->get_mac_address($conn);
		$mac_vendor      = $host->get_mac_vendor($conn);
		
		$coordinates     = $host->get_coordinates();

		$latitude        = $coordinates['lat'];
		$longitude       = $coordinates['lon'];
		
		$num_sensors     = count($sensors);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	
	<script type="text/javascript">
		
		function check_host () {
			
			var ip = $("#ip").val();
			$('#info_error').hide();
			$('#info_error').html("");
						
			$.ajax({
				type: "GET",
				url: "check_host_response.php?ip="+ip,
				data: "",
				success: function(msg){
					if (msg == "1")
					{
						var text = "<?php echo _("This ip already exists. Please, go to modify menu to edit this host") ?>";
						$('#info_error').html("<div style='text-align:center'>"+text+"</div>");
						$('#info_error').show();
					}
					else 
						submit_form();
				}
			});
		}
	
		function check_net (ip) {
			$('#loading').html('<img src="../pixmaps/loading.gif" width="13" alt="<?=_("Loading")?>">');
			$.ajax({
				type: "GET",
				url: "check_net_response.php?ip="+ip,
				data: "",
				success: function(msg){
					if (msg != "0") {
						var fields = msg.split(";");
						$("#asset").val(fields[0]);
						document.getElementById('threshold_c').value = fields[1];
						document.getElementById('threshold_a').value = fields[2];
					}
					$('#loading').html('');
				}
			});
		}



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
			
			$('textarea').elastic();
				
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "<?php echo $action ?>");
			});

		});

	</script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {width: 400px;}";
		    echo "#table_form th {width: 120px;}";
		}
		else
		{
			echo "#table_form {width: 500px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		a {cursor:pointer;}
	</style>
	
	
  		
</head>

<body>

<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php");

if ( !empty ($scan) )
	echo "<p>".gettext("Please, fill these global properties about the hosts you've scaned").":</p>";

?>
   
<div id='info_error' class='ossim_error' style='display: none;'></div>
   
<form method="POST" name="form_host" id="form_host" action="<?php echo $action ?>" enctype="multipart/form-data">

<input type="hidden" name="insert" value="insert"/>
<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>

<table align="center" id='table_form'>

<?php if (empty($scan)) { ?>
	
	<tr>
		<th><label for='hostname'><?php echo gettext("Hostname"); ?></label></th>
		<td class="left"><input type="text" class='req_field vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>
		<span style="padding-left: 3px;">*</span></td>
	</tr>	
	
	<tr>
		<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="ip" id="ip" value="<?php if($type_action!="duplicate") echo $ip?>" onchange="check_net(this.value)"/>
			<span style="padding-left: 3px;">*</span>
			<div id="loading" style="display:inline"></div>
		</td>
	</tr>
  
	<tr>
		<th>
			<label for='fqdns'><?php echo gettext("FQDN/Aliases"); ?></label>
			<a class="sensor_info"  txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Comma-separated FQDN or aliases")?></div>">
			<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
		</th>
		<td class="left">
			<textarea name="fqdns" id="fqdns" class='vfield'><?php echo $fqdns;?></textarea>
		</td>
	</tr>
  
<?php } else { ?>

	<input type="hidden" value="<?php echo $ip ?>" name="ip" id="ip"/>
	<tr>
		<th><label for='groupname'><?php echo gettext("Optional group name"); ?></label></th>
		<td class="left"><input type="text" name="groupname" id="groupname" class='vfield' value="<?php echo $groupname;?>"/>
	</tr>

<?php } ?>
  
	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
		<td class="left">
			<textarea name="descr" id="descr" class='vfield'><?php echo $descr;?></textarea>
		</td>
	</tr>

	<tr>
		<th><label for='asset'><?php echo gettext("Asset value"); ?></label></th>
		<td class="left">
			<select name="asset" id="asset" class='req_field vfield'>
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

	<tr style="display:none">
		<th><label for='nat'><?php echo gettext("NAT");?></label></th>
		<td class="left">
			<input type="text" class='vfield' name="nat" id="nat" value="<?php echo $nat;?>"/>
		</td>
	</tr>

	<tr>
		<th>
			<label for='sboxs1'><?php echo gettext("Sensors"); ?></label>
			<a class="sensor_info"  txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Define which sensors has visibility of this host")?></div>">
			<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
			<span><a href="../sensor/newsensorform.php"><?=gettext("Insert new sensor");?>?</a></span>
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
		<th><label for='icon'><?php echo gettext("Icon"); ?></label></th>
		<td class="left" style="color:gray">
			<input type="file" class='vfield' name="icon" id="icon"><br/>
			<?php echo "* "._("Allowed format").": 16x16 "._("png image") ?>
		</td>
	</tr>
					 
	<tr>
		<td style="text-align: left; border:none; padding-top:3px;">
			<a onclick="$('.advanced').toggle();">
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Advanced")?></a>
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
			<select name="rrd_profile" id="rrd_profile" class='vfield'>
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
 
  
	<?php if (empty($scan)) { ?>

	<tr>
		<td style="text-align: left; border:none; padding-top:3px;">
			<a onclick="$('.inventory').toggle();">
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Inventory")?></a>
		</td>
	</tr>

	<tr class="inventory" style="display:none;">
		<th><label for='os'><?php echo gettext("OS"); ?></label></th>
		<td class="left">
			<select name="os" id="os" class='vfield'>
				<?php
				foreach ($array_os as $k => $v)
				{
					$pattern = "/$k/i";
					$selected = ( preg_match($pattern, $os) ) ? "selected='selected'" : '';
					echo "<option value='$k' $selected>$v</option>";
				}
				?>
			</select>
		</td>
	</tr>

	<tr class="inventory" style="display:none;">
		<th><label for='mac'><?php echo gettext("Mac Address"); ?></label></th>
		<td class="left"><input type="text" class='vfield' name="mac" id="mac" value="<?php echo $mac;?>"/></td>
	</tr>

	<tr class="inventory" style="display:none;">
		<th><label for='mac_vendor'><?php echo gettext("Mac Vendor"); ?></label></th>
		<td class="left"><input type="text" class='vfield' name="mac_vendor" id="mac_vendor" value="<?php echo $mac_vendor;?>"/></td>
	</tr>

	<tr style="display:none">
		<td style="text-align: left; border:none; padding-top:3px;">
			<a onclick="$('.geolocation').toggle();">
			<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Geolocation Info")?></a>
		</td>
	</tr>
		
	<tr class="geolocation" style="display:none;">
	    <th><label for='latitude'><?php echo gettext("Latitude"); ?></label></th>
	    <td class="left"><input type="text" class='vfield' id="latitude" name="latitude" value="<?php echo $latitude;?>"/></td>
	</tr>
	
	<tr class="geolocation" style="display:none;">
	   <th><label for='longitude'><?php echo gettext("Longitude"); ?></label></th>
	    <td class="left"><input type="text" id="longitude" name="longitude" value="<?php echo $longitude;?>"/></td>
	</tr>

	<?php 
	} 
	else 
	{ 
		echo "<input type='hidden' class='vfield' name='ips' id='ips' value='$num_ips'/>";

		for ($i = 0; $i < $num_ips; $i++) 
			echo "<input type='hidden' class='vfield' name='ip_$i' id='ip_$i' value='".$ips_address[$i]."'/>";
	}
	?>


	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button" class="button" id='send' value="<?=_("Update")?>" onclick="<?php echo $submit_function;?>"/>
			<?php 
				if ( !empty($scan) )
					echo "<input type='button' class='button' value='".gettext("<< Back ")."' onclick=\"javascript:history.go(-1);\"/>";
				else
					echo "<input type='reset' class='button' value='".gettext("Clear form")."'/>";
			?>
			
		</td>
	</tr>
  
</table>
</form>

<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

</body>
</html>

<?php $db->close($conn); ?>

