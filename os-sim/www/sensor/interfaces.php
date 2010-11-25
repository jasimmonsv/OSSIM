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
require_once ('classes/Sensor.inc');
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Sensor_interfaces.inc';
require_once ('classes/Sensor_interfaces.inc');
require_once ('classes/SecurityReport.inc');

include_once ('nfsen_functions.php');

Session::logcheck("MenuPolicy", "PolicySensors");

$sensor           = GET('sensor');
$name             = GET('name');

$nfsen_sensors    = get_nfsen_sensors();

$interface        = GET('interface');

$int_name         = GET('int_name');
$main             = intval(GET('main'));
$has_nagios       = intval(GET('has_nagios'));
$has_ntop         = intval(GET('has_ntop'));
$has_vuln_scanner = intval(GET('has_vuln_scanner'));
$has_kismet       = intval(GET('has_kismet'));
$vuln_user        = GET('vuln_user');
$vuln_pass        = GET('vuln_pass');
$vuln_port        = GET('vuln_port');

$base_port = ($nfsen_sensors[$name] != "") ? $nfsen_sensors[$name]['port'] : get_nfsen_baseport($nfsen_sensors);
$base_type = ($nfsen_sensors[$name] != "") ? $nfsen_sensors[$name]['type'] : "netflow";
$base_color = ($nfsen_sensors[$name] != "") ? $nfsen_sensors[$name]['color'] : "#0000ff";

$submit = GET('submit');
ossim_valid($sensor, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Sensor"));
ossim_valid($interface, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Interface"));
ossim_valid($int_name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Sensor Name"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Sensor Name"));
ossim_valid($submit, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Submit"));
ossim_valid($vuln_user, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Vuln User"));
ossim_valid($vuln_pass, OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Vuln Password"));
ossim_valid($vuln_port, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Vuln port"));

if (ossim_error()) {
    die(ossim_error());
}
$local_ip = `grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jasper.css">
	<link rel="stylesheet" type="text/css" href="../style/colorpicker.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery.colorpicker.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js"charset="utf-8"></script>
	<script type="text/javascript" src="../js/autoHeight.js"></script>
  
	<script type="text/javascript">
		$(document).ready(function(){
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,400,'90%');
				return false;
			});
			<? if ($nfsen_sensors[$name] != "") echo "is_running();"; else echo "color_picker();"; ?>

			$('textarea').elastic();
					
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "modifysensor.php");
			});
		});
  
		function toggle_vuln_scanner_options() {
		//if ('<?=trim($local_ip)?>' != '<?=$sensor?>')
			$("#vuln_scanner_option").toggle();
		}

		function color_picker() {
			$('#backgroundTitle1').ColorPicker({
				color: '<?=$base_color?>',
				onShow: function (colpkr) {
						$(colpkr).fadeIn(500);
						return false;
				},
				onHide: function (colpkr) {
						$(colpkr).fadeOut(500);
						return false;
				},
				onChange: function (hsb, hex, rgb) {
					$('#backgroundTitle1 div').css('backgroundColor', '#' + hex);
					$('#backgroundTitle1 div input').attr('value','#' + hex);
				}
			});
		}
  
		function nfsen_config() {
			var name = document.nfsenform.nfsen_name.value;
			var port = document.nfsenform.nfsen_port.value;
			var type = document.nfsenform.nfsen_type.value;
			var color = document.nfsenform.backgroundTitle.value;
			color = color.replace("#","");
			$('#netflow_hdr').html("<img src='../pixmaps/loading.gif' width='15'>");

			$.ajax({
				type: "GET",
				url: "nfsen_config.php?ip=<?=urlencode($sensor)?>&name="+name+"&port="+port+"&type="+type+"&color="+color,
				data: "",
				success: function(msg){
					if (msg != "You must fill all inputs") {
						$('#netflow_hdr').html("<?="<font style='color:green'><b>"._("is running")."</b></font>"?>");
						$('#netflow_button').val("<?=_("Stop and Remove")?>");
						$('#nfsen_port').attr('disabled', 'disabled');
						$('#nfsen_port').css('color', 'gray');
						$('#nfsen_type').attr('disabled', 'disabled');
						document.getElementById('netflow_button').onclick=function() { del_nfsen(); };
						alert(msg);
					}
				}
			});
		}
  
		function is_running() {
			$.ajax({
				type: "GET",
				url: "nfsen_config.php?ip=<?=urlencode($sensor)?>&name=<?=$name?>&status=1",
				data: "",
				success: function(msg){
					$('#nfsen_port').attr('disabled', 'disabled');
					$('#nfsen_port').css('color', 'gray');
					$('#nfsen_type').attr('disabled', 'disabled');
					if (msg.match(/is running/)) {
						$('#netflow_hdr').html("<?="<font style='color:green'><b>"._("is running")."</b></font>"?>");
					} else {
						alert(msg);
						$('#netflow_hdr').html("<?="<font style='color:red'><b>"._("is not running")."</b></font>"?>");
						$('#netflow_button').val("<?=_("Restart NetFlow")?>");
						document.getElementById('netflow_button').onclick=function() { nfsen_restart(); };
					}
				}
			});
		}
  
		function nfsen_restart() {
			var aux = $('#netflow_hdr').html();
			$('#netflow_hdr').html("<img src='../pixmaps/loading.gif' width='15'>");
			$.ajax({
				type: "GET",
				url: "nfsen_config.php?ip=<?=urlencode($sensor)?>&name=<?=$name?>&restart=1",
				data: "",
				success: function(msg){
					if (msg.match(/<?=$name?>: collector did not start/)) {
						$('#netflow_hdr').html(aux);
						alert(msg);
					} else {
						$('#netflow_hdr').html("<?="<font style='color:green'><b>"._("is running")."</b></font>"?>");
						$('#netflow_button').val("<?=_("Stop and Remove")?>");
						document.getElementById('netflow_button').onclick=function() { del_nfsen(); };
						alert(msg);
					}
				}
			});
		}
  
	function del_nfsen() {
		var aux = $('#netflow_hdr').html();
		$('#netflow_hdr').html("<img src='../pixmaps/loading.gif' width='15'>");
		$.ajax({
			type: "GET",
			url: "nfsen_config.php?ip=<?=urlencode($sensor)?>&name=<?=$name?>&delete=1",
			data: "",
			success: function(msg){
				if (msg != "") {
					$('#nfsen_port').attr('disabled', '');
					$('#nfsen_port').css('color', 'black');
					$('#nfsen_type').attr('disabled', '');
					$('#netflow_hdr').html("<?="<font style='color:red'><b>"._("is not configured")."</b></font>"?>");
					$('#netflow_button').val("<?=_("Configure and Run")?>");
					color_picker();
					document.getElementById('netflow_button').onclick=function() {nfsen_config();};
					alert(msg);
				} else {
					$('#netflow_hdr').html(aux);
				}
			}
		});
	}
</script>

<script type="text/javascript">
	var messages = new Array();
		messages[0] = '<?php echo _("The following fields are required:");?>';
		messages[1] = '<?php echo _("Invalid send method");?>';
		messages[2] = '<?php echo _("Validation error, please submit form again");?>';
		messages[3] = '<?php echo _("We found the following errors:");?>';
</script>

<style type='text/css'>
	input[type='text'], select, textarea {width: 90%; height: 18px;}
	textarea { height: 45px;}
	label {border: none; cursor: default;}
	.bold {font-weight: bold;}
	div.bold {line-height: 18px;}
</style>

</head>

<body>
<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php");


$db = new ossim_db();
$conn = $db->connect();

if (GET('submit'))
{
    $temp_msg = "inserted.";
    
    if ($submit == _("Insert"))
	{
    	if ($interface != "")
    		Sensor_interfaces::insert_interfaces($conn, $sensor, $interface, $int_name, $main);
    	else
    		$temp_msg = _("Error: Interface input can not be empty");
    	
    }
	elseif ($submit == _("Update"))
	{
        $temp_msg = gettext("updated") . " .";
        Sensor_interfaces::update_interfaces($conn, $sensor, $interface, $int_name, $main);
    }
	elseif ($submit == _("Delete"))
	{
		Sensor_interfaces::delete_interfaces($conn, $sensor, $interface);
        $temp_msg = gettext("deleted") . " .";
    }
    //    Sensor::update ($conn, $name, $ip, $priority, $port, $descr);
    //$db->close($conn);
}
elseif(GET('update')== _("Update"))
{
    Sensor::set_properties($conn,$sensor,$has_nagios,$has_ntop,$has_vuln_scanner,$has_kismet);
    //if (trim($local_ip)!=$sensor)
    Sensor::update_vuln_nessus_servers($conn, $sensor, $vuln_user, $vuln_pass, $vuln_port, $has_vuln_scanner);
}

$properties = array();
$properties = Sensor::get_properties($conn, $sensor);

//if (trim($local_ip)!=$sensor)
Sensor::insert_vuln_nessus_servers($conn, $sensor, $name, $properties["has_vuln_scanner"]);
$vuln_scanner_options = Sensor::get_vuln_scanner_options($conn, $sensor);



$conf = $GLOBALS["CONF"];

$updating = 0;
if ($sensor_interface_list = Sensor_interfaces::get_list($conn, $sensor)) 
    $updating = 1;

?>

<table align="center" width='100%' class="noborder" style="background-color:#ffffff">
	<tr>
		<td class="center nobborder">
			<iframe src="modifysensorform.php?name=<?=urlencode($name)?>&withoutmenu=1" scrolling="auto" class='autoHeight' width="100%" frameborder="0"></iframe>
		</td>
	</tr>
</table>

<table align="center" class="noborder" style="background-color:#ffffff">

	<?php if ($temp_msg != "") { ?> <tr><td><?php echo $temp_msg ?></td></tr> <?php } ?>

	<tr>
		<td class="noborder" valign="top">
			<table align="center" width="100%">
				<tr>
					<th><?php echo gettext("Interface"); ?> </th>
					<th><?php echo gettext("Name"); ?> </th>
					<th><?php echo gettext("Main"); ?> </th>
					<th><?php echo gettext("Action"); ?> </th>
				</tr>
	
				<? 	
				if ($updating)
				{ 
					foreach($sensor_interface_list as $s_int) { 
				?>
				<form method="GET" name='formsensor' id='formsensor' action="interfaces.php">
						<input type="hidden" name="sensor" value="<?php echo $sensor; ?>"/>
						<input type="hidden" name="name" value="<?php echo $name; ?>"/>
						<input type="hidden" name="interface" value="<?php echo $s_int->get_interface(); ?>"/>
				<tr>
					<td class="nobborder"><?php echo $s_int->get_interface(); ?></td>
					<td class="nobborder"><input type="text" name="int_name" value="<?php echo $s_int->get_name(); ?>"/></td>
					<td class="nobborder">
						<select name="main" id='main'>
						<?php
								if ($s_int->get_main())
								{
									echo "<option value='1' selected='selected'>".gettext("Yes")."</option>";
									echo "<option value='0'>".gettext("No")."</option>";
						
								} 
								else 
								{
									echo "<option value='1' selected='selected'>".gettext("No")."</option>";
									echo "<option value='0'>".gettext("Yes")."</option>";
								}
						?>
						</select>
					</td>
					<td class="nobborder" nowrap='nowrap'>
						<input type="submit" name="submit" value="<?=_("Update")?>" class="button" />
						<input type="submit" name="submit" value="<?=_("Delete")?>" class="button" />
					</td>
				</tr>
				</form>
				<? } ?>
			<? } ?>
			
				<form method="GET" action="interfaces.php">
					<input type="hidden" name="sensor" value="<?php echo $sensor; ?>"/>
					<input type="hidden" name="name" value="<?php echo $name; ?>"/>
					
					<tr>
						<td class="nobborder"><input type="text" name="interface"></td>
						<td class="nobborder"><input type="text" name="int_name"></td>
						<td class="nobborder">
							<select name="main" id='main'>
								<option value="1" selected> <?php echo gettext("Yes"); ?> </option>
								<option value="0"> <?php echo gettext("No"); ?> </option>
							</select>
						</td>
						<td class="center nobborder">
							<input type="submit" name="submit" value="<?=_("Insert")?>" class="lbutton"/>
						</td>
					</tr>
				</form>
		</table>
	</td>
</tr>
<tr>
	<td class="noborder" style="padding-top:20px">
	<table align="center" width="100%">
		<form method="GET" action="interfaces.php" name="finterfaces">
		<input type="hidden" name="name" value="<?php echo $name; ?>"/>
		<input type="hidden" name="sensor" value="<?php echo $sensor; ?>">
		<tr>
			<th> <?php echo gettext("Nagios"); ?></th>
			<th><?php echo gettext("Ntop"); ?></th>
			<th nowrap='nowrap'><?php echo gettext("Vuln Scanner"); ?> </th>
			<th><?php echo gettext("Kismet"); ?></th>
			<th><?php echo gettext("Action"); ?></th>
		</tr>
		<tr>
			<td class="center nobborder"><input type="checkbox" name="has_nagios"value="1"  <?=(($properties["has_nagios"]=="1") ? "checked='checked'" : "")?>></td>
			<td class="center nobborder"><input type="checkbox" name="has_ntop" value="1"  <?=(($properties["has_ntop"]=="1") ? "checked='checked'" : "")?>></td>
			<td class="center nobborder"><input type="checkbox" name="has_vuln_scanner" onclick="toggle_vuln_scanner_options()"  value="1"  <?=(($properties["has_vuln_scanner"]=="1") ? "checked" : "")?>></td>
			<td class="center nobborder"><input type="checkbox" name="has_kismet" value="1"  <?=(($properties["has_kismet"]=="1") ? "checked='checked'" : "")?>></td>
			<td class="center nobborder"><input type="submit" name="update" class="lbutton" value="Update"/></td>
		</tr>
		
        <tr id="vuln_scanner_option" style="display:<?=(($properties["has_vuln_scanner"]=="1") ? "visible":"none"); //  && trim($local_ip)!=$sensor?>">
            <td colspan="5" style="text-align:left;padding-left:152px;" class="nobborder">
                <table width="50%">
                    <tr>
                        <th><?=_("Vuln Scanner Options")?></th>
                    </tr>
					
                    <tr>
                        <td class="nobborder">
                            <table width="100%" class="noborder">
                                <tr>
                                    <td class="nobborder" style="text-align:right;paddin-right:5px;"><?=_("User");?>:</td>
									<td class="nobborder">
										<input type="text" name="vuln_user" value="<?=(($vuln_scanner_options["user"]!="")? $vuln_scanner_options["user"]:$GLOBALS["CONF"]->db_conf["nessus_user"])?>"/>
									</td>
                                </tr>
                                <tr>
                                    <td class="nobborder" style="text-align:right;paddin-right:5px;"><?=_("Pass");?>:</td>
									<td class="nobborder">
										<input type="password" name="vuln_pass" value="<?=(($vuln_scanner_options["PASSWORD"]!="")? $vuln_scanner_options["PASSWORD"]:$GLOBALS["CONF"]->db_conf["nessus_pass"])?>"/>
									</td>
                                </tr>
                                <tr>
                                    <td class="nobborder" style="text-align:right;paddin-right:5px;"><?=_("Port");?>:</td>
									<td class="nobborder">
										<input type="text" name="vuln_port" value="<?=(($vuln_scanner_options["port"]!="")? $vuln_scanner_options["port"]:$GLOBALS["CONF"]->db_conf["nessus_port"])?>"/>
									</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
		</form>
	</table>
	</td>
</tr>
<tr>
	<td class="nobborder" style="padding-top:20px">
		<table width="100%">
		<form name="nfsenform">
		<input type="hidden" name="nfsen_name" value="<?=$name?>"/>
		<tr id="nfsen_form">
			<td class="nobborder" colspan="6">
				<table class="transparent" align="center" width="100%">
					<tr>
						<th><?=_("Netflow Collection Configuration")?></th>
						<th><?=_("Action")?></th>
					</tr>
					<tr>
						<td class="nobborder">
							<table class="transparent">
								<tr>
									<td class="right nobborder" width="30"><?=_("Port")?>:</td>
									<td class="left nobborder">
										<input type="text" name="nfsen_port" id="nfsen_port" value="<?=$base_port?>" style="width:50px"/>
									</td>
									<td class="right nobborder" width="30"><?=_("Color")?>:</td>
									<td class="left nobborder">
										<div id="backgroundTitle1" class="colorSelector">
											<div style="background-color: <?=$base_color?>;">
												<input type="hidden" name="backgroundTitle" value="#0000ff">
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="right nobborder" width="30" style="padding-left:20px"><?=_("Type")?>:</td>
									<td class="left nobborder">
										<select name="nfsen_type" id="nfsen_type">
											<option value="netflow" <? if ($base_type == "netflow") echo "selected='selected'"?>><?=_("netflow")?>
											<option value="sflow" <? if ($base_type == "sflow") echo "selected='selected'"?>><?=_("sflow")?>
										</select>
									</td>
									
									<td class="nobborder" style="padding-left:10px"><?=_("Status")?>:</td>
									<td class="nobborder" id="netflow_hdr" nowrap='nowrap'><? if ($nfsen_sensors[$name] != "") echo "<font style='color:red'><b>"._("is configured")."</b></font>"; else echo "<font style='color:red'><b>"._("is not configured")."</b></font>" ?></td>
								</tr>
							</table>
						</td>
						<td class="center nobborder" style="padding-left:20px;padding-right:20px">
							<table class="transparent">
								<tr>
									<td class="center nobborder">
										<input type="button" id="netflow_button" value="<? if ($nfsen_sensors[$name] != "") echo _("Stop and Remove"); else echo _("Configure and Run") ?>" onclick="<? if ($nfsen_sensors[$name] != "") echo "del_nfsen()"; else echo "nfsen_config()"; ?>" class="lbutton"/></td></tr>
								<tr><td nowrap='nowrap' class="center nobborder" style="padding-top:10px"><img src="../pixmaps/help_icon.gif" align="absmiddle"> <a href="../nfsen/helpflows.php" class="greybox"><b><?=_("Configuration help")?></b></a></td></tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</form>
		</table>
	</td>
</tr>
</table>
    <p><input type="button" onclick="document.location.href='sensor.php'" class="button" value="<?=_("Back")?>"/></p>

</body>
</html>
<? $db->close($conn); ?>
