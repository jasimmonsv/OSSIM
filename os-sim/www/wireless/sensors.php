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
$location = base64_decode(GET('location'));
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal: location');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$snort = $db->snort_connect();
?>
<table align="center" width="100%" id="results">
<thead>
	<th height='20'><a href="javascript:;" onclick="load_data('sensors.php?location=<?=urlencode($location)?>&order=sensor')"><?=_("Sensor")?></a></th>
	<th nowrap><?=_("IP Addr")?></th>
	<th nowrap><?=_("MAC")?></th>
	<th nowrap><?=_("Model #")?></th>
	<th nowrap><?=_("Serial #")?></th>
	<th nowrap><?=_("Mounting Location")?></th>
	<th nowrap><?=_("In-Service")?></th>
	<th nowrap><?=_("Status")?></th>
	<th></th>
</thead>
<tbody>
<?
# sensor list with perms
require_once 'classes/Sensor.inc';
$ossim_sensors = Sensor::get_list($conn,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1");
$sensors_list = array();
foreach ($ossim_sensors as $sensor) $sensors_list[] = $sensor->get_ip();
#
$locations = Wireless::get_locations($conn,$location);
$i=0;
if (isset($locations[0])) {
    foreach ($locations[0]['sensors'] as $data) {
        $color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
        if (!in_array($data['ip'],$sensors_list)) $color = "bgcolor='#FFCA9F'";
        echo "<tr $color>
        <td><a href=\"javascript:;\" onclick=\"browsexml('".$data['ip']."','')\">".$data['sensor']."</a></td>
        <td>".$data['ip']."</td>
        <td>".$data['mac']."</td>
        <td>".$data['model']."</td>
        <td>".$data['serial']."</td>
        <td style='text-align:left;padding-left:10px'>".$data["mounting_location"]."</td>
        <td>".Wireless::get_firstevent_date($snort,$data['ip'])."</td>
        <td><img src='../pixmaps/tables/tick.png'></td>
        <td width='20'>
            <a href='sensor_edit.php?location=".urlencode(base64_encode($location))."&sensor=".urlencode($data["sensor"])."' class='greybox' title='Edit ".$data["sensor"]." details'><img src='../repository/images/edit.gif' border=0></a>
        </td>
        </tr>";
    }
}
?>
</tbody>
</table>
<div id="browsexml"></div>
<?
$db->close($conn);
?>
