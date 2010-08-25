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
require_once 'Wireless.inc';
$location = base64_decode(GET('location'));
$sensor = GET('sensor');
$model = GET('model');
$serial = GET('serial');
$mounting = GET('mounting');
ossim_valid($model, OSS_TEXT, OSS_NULLABLE, '#', 'illegal: model');
ossim_valid($serial, OSS_TEXT, OSS_NULLABLE, '#', 'illegal: serial');
ossim_valid($mounting, OSS_TEXT, OSS_NULLABLE, 'illegal: mounting location');
ossim_valid($location, OSS_ALPHA, OSS_PUNC, 'illegal: location');
ossim_valid($sensor, OSS_ALPHA, OSS_PUNC, 'illegal: sensor');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$msg = "";
if ($location!=""  && $sensor!="" && GET('action')=="update") {
	Wireless::add_locations_sensor($conn,$location,$sensor,$model,$serial,$mounting);
	$msg = "<font color=green>"._("Update successfully!")."</font><script>parent.GB_hide();</script>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<form>
<input type="hidden" name="location" value="<?=base64_encode($location)?>">
<input type="hidden" name="sensor" value="<?=$sensor?>">
<input type="hidden" name="action" value="update">
<? if($msg!="") echo "<center>$msg</center>"; ?>
<table width="100%">
<th><?=_("Model #")?></th>
<th><?=_("Serial #")?></th>
<th><?=_("Mounting Location")?></th>
<?
$locations = Wireless::get_locations($conn,$location);
$i=0;
foreach ($locations[0]['sensors'] as $data) if ($data['sensor']==$sensor) break;
?>
<tr>
	<td valign=top><input type='text' size='30' name='model' value="<?=$data["model"]?>"></td>
	<td valign=top><input type='text' size='30' name='serial' value="<?=$data["serial"]?>"></td>
	<td valign=top><textarea cols=50 rows=3 name="mounting"><?=$data["mounting_location"]?></textarea></td>
</tr>
<tr><td colspan="3" class="noborder">
	<input type="submit" value="Update" class="btn">
</td></tr>
</table><br>
</form>
<?
$db->close($conn);
?>
</body>
</html>

