<?
/*****************************************************************************
*
*    License:
*
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
require_once ("ossim_conf.inc");
require_once ("ossim_db.inc");
require_once ('classes/Sensor.inc');

$db = new ossim_db();
$conn = $db->connect();

$net = GET('net');

ossim_valid($net, OSS_IP_ADDRCIDR, 'illegal:' . _("host"));

if (ossim_error()) {
    die(ossim_error());
}

error_reporting(0);

$ntop_link = Sensor::get_net_sensor_link($conn, $net);

$source1 = $ntop_link."/ipProtoDistribution.png";

$source2 = $ntop_link."/plugins/rrdPlugin?action=graphSummary&graphId=4&key=interfaces/eth0/&start=now-12h&end=now";

$salida1 = get_headers($source1);
$salida2 = get_headers($source2);
?>
<table>
	<? if (!preg_match("/Not Found/",$salida1[0]) && $salida1 != null) { ?>
	<tr>
		<td>
		<iframe frameborder="0" src="<?=$source1?>" width="400" height="250"></iframe>
		</td>
	</tr>
		<? }
   if (!preg_match("/Not Found/",$salida2[0]) && $salida2 != null) { ?>
	<tr>
		<td>
		<img src="<?=$source2?>">
		</td>
	</tr>
    <?php
    }
    ?>
</table>
<?php
    $db->close($conn);
?>
