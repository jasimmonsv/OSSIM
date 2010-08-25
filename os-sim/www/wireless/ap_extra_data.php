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
$ssid = base64_decode(GET('ssid'));
$sensors = GET('sensors');
ossim_valid($ssid, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\>', 'illegal: ssid');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal: sensors');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->snort_connect();
$ossim = $db->connect();
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
<table width="100%">
<th><?=_("MAC")?></th>
<th><?=_("Manufacturer")?></th>
<th><?=_("# Clients")?></th>
<th><?=_("Frequency")?></th>
<th><?=_("Channel")?></th>
<th><?=_("Type")?></th>
<th><?=_("1st Connect")?></th>
<th><?=_("Last Connect")?></th>
<th><?=_("Sensor")?></th>
<th></th>
<?
$sids = "";
if ($sensors!="") {
	$sensor_list = explode(",",$sensors);
	$sids = Wireless::get_sids($conn,$sensor_list);
}
$aps = Wireless::get_aps($conn,$ssid,$sids);
$i=0;
foreach ($aps as $data) {
	$color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
	echo "<tr $color>
	<td>".$data['mac']."</td>
	<td style='padding:0px 10px 0px 10px;text-align:left'>".$data['vendor']."</td>
	<td>".$data['clients']."</td>
	<td>".$data['freq']."</td>
	<td>".$data['channel']."</td>
	<td>".$data['type']."</td>
	<td>".$data['first']."</td>
	<td>".$data['last']."</td>
	<td>-</td>
	<td width='20'>
		<a href='ap_edit.php?ssid=".urlencode(base64_encode($ssid))."&mac=".urlencode($data['mac'])."'><img src='../repository/images/edit.gif' border=0></a>
	</td>
	</tr>";
	$details = Wireless::get_ap_data($ossim,$data['mac']);
	if ($details['notes']!="") {
		echo "<tr $color><td colspan=10 style='text-align:left;padding:0px 10px 10px 0px'><img src='../pixmaps/theme/arrow-315-small.png' border=0 align=absmiddle><b>Notes:</b> ".utf8_encode(nl2br($details['notes']))."</td></tr>";
	}
}
?>
</table><br>
</body>
</html>
<?
$db->close($conn);
?>
