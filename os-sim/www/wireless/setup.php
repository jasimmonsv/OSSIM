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
Session::logcheck("MenuEvents", "ReportsWireless");
require_once 'classes/Security.inc';
require_once 'classes/Sensor.inc';
require_once 'Wireless.inc';
//
$location = GET('location');
$desc = GET('desc');
$action = GET('action');
$sensor = GET('sensor');
$model = GET('model');
$serial = GET('serial');
$mounting = GET('mounting');
$layer = GET('layer');
ossim_valid($location, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal: location');
ossim_valid($desc, OSS_TEXT, OSS_NULLABLE, 'illegal: desc');
ossim_valid($sensor, OSS_TEXT, OSS_NULLABLE, OSS_SCORE, 'illegal: sensor');
ossim_valid($model, OSS_TEXT, OSS_NULLABLE, OSS_SPACE, '#', 'illegal: model');
ossim_valid($serial, OSS_TEXT, OSS_NULLABLE, OSS_SPACE, '#', 'illegal: serial');
ossim_valid($mounting, OSS_TEXT, OSS_NULLABLE, OSS_SPACE, 'illegal: mounting');
ossim_valid($action, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal: action');
ossim_valid($layer, OSS_DIGIT, OSS_NULLABLE, 'illegal: layer');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();

if ($action=="add" && $location!="") {
	Wireless::add_location($conn,$location,$desc);
} 
if ($action=="del" && $location!="") {
	Wireless::del_location($conn,$location);
} 
if ($action=="add_sensor" && $location!=""  && $sensor!="") {
	Wireless::add_locations_sensor($conn,$location,$sensor,$model,$serial,$mounting);
} 
if ($action=="del_sensor" && $location!=""  && $sensor!="") {
	Wireless::del_locations_sensor($conn,$location,$sensor);
} 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.watermarkinput.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script type="text/javascript">
	var max = 0;
	$(document).ready(function () {
		<? if ($layer!="") { ?>
		showhide('#cell<?=$layer?>','#img<?=$layer?>')
		<? } ?>
		$("#location").Watermark('<?=_("Location")?>');
		$("#desc").Watermark('<?=_("Description")?>');
		for (var i=1;i<=max;i++) {
			$("#model"+i).Watermark('<?=_("Model")?>');
			$("#serial"+i).Watermark('<?=_("Serial")?>');
			$("#mounting"+i).Watermark('<?=_("Mounting Location")?>');
		}
	});

	function showhide(layer,img){
		$(layer).toggle();
		if ($(img).attr('src').match(/plus/))
			$(img).attr('src','../pixmaps/minus-small.png')
		else
			$(img).attr('src','../pixmaps/plus-small.png')
	}
  </script>
</head>
<body>
<? include("../hmenu.php"); ?>

<table id="data" align="center" width="80%">
<tr><td colspan=3 style='text-align:left'>

	<form><input type="hidden" name="action" value="add">
	<table class="noborder">
	<tr>
		<td class="noborder"><input type="text" size="30" id="location" name="location"></td>
		<td class="noborder"><input type="text" size="60" id="desc" name="desc"></td>
		<td class="noborder"><input type="submit" value="Add New Location" class="lbutton"></td>
	</tr>
	</table>
	</form>
	
</td></tr>
<th colspan="2" height='20' width="25%"><?=_("Location")?></th>
<th><?=_("Description")?></th>
<?  if ($_SESSION["_user"]=="admin") { ?><th><?=_("User")?></th><? } ?>
<th></th>
<?
$locations = Wireless::get_locations($conn); 
$ossim_sensors = Sensor::get_list($conn,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1");
$sensors_list = "";
foreach ($ossim_sensors as $sensor) {
	$sensors_list .= "<option value='".$sensor->get_name()."'>".$sensor->get_name()." [".$sensor->get_ip()."]";
}
$c=0;
foreach ($locations as $data) {
	$c++;
	echo "<tr bgcolor='#f2f2f2'>
	<td width='20'><a href=\"javascript:;\" onclick=\"showhide('#cell$c','#img$c')\"><img src='../pixmaps/plus-small.png' id='img$c' border=0></a></td>
	<td>".$data["location"]."</td>
	<td style='text-align:left;padding-left:10px'>".$data['description']."</td>";
	
	if ($_SESSION["_user"]=="admin") echo "<td>".$data["user"]."</td>";
	
	echo "<td width='20'>
		<a href='?action=del&location=".urlencode($data["location"])."'><img src='../repository/images/del.gif' border=0></a>
	</td></tr>
	<tr><td colspan=3 style='padding:10px 0px 10px 40px;display:none' id='cell$c'>
		<table width='100%'>
		<tr><td colspan=7>

			<form>
			<input type='hidden' name='action' value='add_sensor'>
			<input type='hidden' name='layer' value='$c'>
			<input type='hidden' name='location' value='".$data["location"]."'>
			<table class='noborder'>
			<tr>
				<td class='noborder'><select name='sensor'>".$sensors_list."</select></td>
				<td class='noborder'><input type='text' size='15' name='model' id='model$c'></td>
				<td class='noborder'><input type='text' size='15' name='serial' id='serial$c'></td>
				<td class='noborder'><input type='text' size='25' name='mounting' id='mounting$c'></td>
				<td class='noborder'><input type='submit' value='Add Sensor' class='lbutton'></td>
			</tr>
			</table>
			</form>
	
		</td></tr>
		<th nowrap>"._("Sensor")."</th>
		<th nowrap>"._("IP Addr")."</th>
		<th nowrap>"._("Mac")."</th>
		<th nowrap>"._("Model #")."</th>
		<th nowrap>"._("Serial #")."</th>
		<th nowrap>"._("Mounting Location")."</th>
		<th></th>";
	$i=0;	
	foreach ($data["sensors"] as $sensors) {
		$color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
		echo "<tr $color>
		<td>".$sensors["sensor"]."</td>
		<td>".$sensors["ip"]."</td>
		<td>".$sensors["mac"]."</td>
		<td>".$sensors["model"]."</td>
		<td>".$sensors["serial"]."</td>
		<td style='text-align:left;padding-left:10px'>".$sensors["mounting_location"]."</td>
		<td width='20'>
			<a href='?action=del_sensor&location=".urlencode($data["location"])."&sensor=".urlencode($sensors["sensor"])."&layer=$c'><img src='../repository/images/del.gif' border=0></a>
		</td>
		</tr>";
	}
	echo "
		</table>
	</td></tr>";
}
?>
</table>
<script>max=<?=$c?>;</script>
<?
$db->close($conn);
?>
</body>
</html>

