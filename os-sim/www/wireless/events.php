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
//
$order = GET('order');
$si = intval(GET('index'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, 'illegal: sensors');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->snort_connect();
?>
<table align="center" width="100%" id="results">
<thead>
	<th height='20'><?=_("Signature")?></th>
	<th nowrap><?=_("Total #")?></th>
	<th nowrap><?=_("Sensor #")?></th>
	<th nowrap><?=_("Src. Addr.")?></th>
	<th nowrap><?=_("Dst. Addr.")?></th>
	<th nowrap><?=_("First")?></th>
	<th nowrap><?=_("Last")?></th>
</thead>
<tbody>
<?
$sids = "";
if ($sensors!="") {
	$sensor_list = explode(",",$sensors);
	$sids = Wireless::get_sids($conn,$sensor_list);
}
$events = Wireless::get_events($conn,$sids);
$i=0;
foreach ($events as $data) {
	$color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
	echo "<tr $color>
	<td style='text-align:left;padding-left:5px'>".$data['signature']."</td>
	<td>".$data['total']."</td>
	<td>".$data['sensor']."</td>
	<td>".$data['src']."</td>
	<td>".$data['dst']."</td>
	<td>".$data['first']."</td>
	<td>".$data['last']."</td>
	</tr>";
}
?>
</tbody>
</table>
<br>
<form action="../forensics/base_qry_main.php" method="get">
<input type="hidden" name="hmenu" value="Forensics">
<input type="hidden" name="smenu" value="Forensics">
<input type="hidden" name="search" value="1">
<input type="hidden" name="sensor" value="<?=$sids?>">
<input type="hidden" name="plugin" value="<?=$sids?>">
<input type="hidden" name="timerange" value="all">
<input type="hidden" name="clear_criteria" value="time">
<input type="hidden" name="bsf" value="Query DB">
<input type="hidden" name="search" value="1">
<input type="submit" value="View All" class="btn">
</form>
<?
$db->close($conn);
?>
