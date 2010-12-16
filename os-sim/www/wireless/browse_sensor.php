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
//ini_set('memory_limit', '256M');
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "ReportsWireless");
require_once 'classes/Security.inc';
require_once 'Wireless.inc';
//
$sensor = GET('sensor');
$date = GET('date');
ossim_valid($sensor, OSS_IP_ADDR, 'illegal: sensor');
ossim_valid($date, OSS_DIGIT, OSS_NULLABLE, 'illegal: sensor');
if (ossim_error()) {
    die(ossim_error());
}
# sensor list with perms
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
if (!validate_sensor_perms($conn,$sensor,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1")) {
    echo $_SESSION["_user"]." have not privileges for $sensor";
    $db->close($conn);
    exit;
}
$db->close($conn);
#
?>
<br>
<?
$files = $browse = array();
// dir files
$path = "/var/ossim/kismet/parsed/$sensor/";
$cmd = "find $path -name '*xml' -printf '%TY%Tm%Td;%f\n' | sort -r | grep '$date'";
$files = explode("\n",`$cmd`);
foreach ($files as $file) if (trim($file)!="") {
    $value = explode(";",trim($file));
    if ($date=="") $date = $value[0];
    if ($value[0]!=$date) break;
    $browse[] = $value[1];
}
?>
<table class="noborder"><tr><td style="align:left" class="noborder" valign="top">
<form style="margin-bottom:10px">
<?=_("Browse available dates")?>: <select name="date" id="combodates" onchange="browsexml('<?=$sensor?>',$('#combodates').val())">
<?
$cmd = "find $path -name '*xml' -printf '%TY%Tm%Td\n' | sort -r | uniq";
$dates = explode("\n",`$cmd`);
foreach ($dates as $now) if (trim($now)!="") {
    $fnow = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$now);
    echo "<option value='$now'".($now==$date ? " selected": "").">$fnow";
}
?>
</select>
</form>
<table style="text-align:left" cellpadding=3>
<th height='20'><?=_("File")?></th>
<th nowrap><?=_("Last Modified Date")?></th>
<?
$fdate = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$date);
foreach ($browse as $file) {
    echo "<tr><td><a href=\"javascript:;\" onclick=\"viewxml('$file','$sensor')\">$file</a>";
    echo " <a href=\"viewxml.php?file=".urlencode($file)."&sensor=".urlencode($sensor)."\" target='viewxml'><img src='../pixmaps/tables/table_edit.png' border=0 align=absmiddle></a>";
    echo "</td><td>".date("Y-m-d H:i:s",filemtime($path.$file))."</td></tr>";
}

?>
</table>
</td>
<td id="wcontainer" class="noborder left" valign="top">

</td>
</tr></table>
