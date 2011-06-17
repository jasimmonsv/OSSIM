<?php
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
require_once 'classes/Host.inc';
require_once 'classes/Host_os.inc';

$net_data = '<table align="center" class="noborder" style="background-color:white" width="100%">
<tr>
<th>'.gettext("THR_C").'</th>
<th>'.gettext("THR_A").'</th>
<th>'.gettext("Asset").'</th>
<th>'.gettext("Sensor").'</th>
<th>'.gettext("Nessus").'</th>
<th>'.gettext("Nagios").'</th>
</tr>
<tr>
<td>'.$net->get_threshold_c().'</td>
<td>'.$net->get_threshold_a().'</td>
<td>'.$net->get_asset().'</td>
<td>';

$sensors = "";
if ($sensor_list = $net->get_sensors($conn)) foreach($sensor_list as $sensor) {
	$sensors.= $sensor->get_sensor_name() . '<br/>';
}
$net_data .= $sensors.'</td>';

// Nessus
if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 3001")) {
	$scan_types = "<img src=\"../pixmaps/tables/tick.png\">";
} else {
	$scan_types = "<img src=\"../pixmaps/tables/cross.png\">";
}
$net_data .= '<td>'.$scan_types.'</td>';
// Nagios
if ($scan_list = Net_scan::get_list($conn, "WHERE net_name = '$name' AND plugin_id = 2007")) {
	$scan_types = "<img src=\"../pixmaps/tables/tick.png\">";
} else {
	$scan_types = "<img src=\"../pixmaps/tables/cross.png\">";
}
$net_data .= '<td>'.$scan_types.'</td>
</tr>
</table>';

?>
<table align="center" width="100%" height="100%" class="bordered">
	<tr>
		<td valign="top">
			<table>
				<tr>
					<td colspan="2" class="headerpr" height="18"><?=gettext("Inventory")?></td>
				</tr>
				<tr>
					<td class="nobborder" valign="top">
						<table align="center" class="noborder" width="100%">
						<tr>
							<th><?=gettext("Name")?></th>
							<th><?=gettext("IPs")?></th>
						</tr>
						<tr>
							<td><? echo $net->get_name(); ?> <a href="javascript:;" onclick="return false" class="scriptinfo_net" data='<?=$net_data?>'><img src="../pixmaps/information.png" align="top" border="0" title="<?=_("Show Info")?>" alt="<?=_("Show Info")?>"></a></td>
							<td><b><?php
							echo $net->get_ips() ?></b></td>
						</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table>
				<tr>
				<td width="70%" valign="top">
					<table>
						<?php
						$exp = CIDR::expand_CIDR($host,"SHORT","IP");
						$host_s_range = $exp[0];
						$host_e_range = end($exp);
						$host_list = Host::get_list($conn, "WHERE INET_ATON(ip) >= INET_ATON('$host_s_range') AND INET_ATON(ip) <= INET_ATON('$host_e_range')");
						if (count($host_list) > 0) {
						?>
						<tr><td class="headerpr" height="20"><?=gettext("Hosts")?></td></tr>
						<tr>
							<td>
							<div style="height:100px;overflow:auto">
							<table>
								<tr>
									<th> <?php
										echo gettext("Name"); ?>
									</th>
									<th> <?php
										echo gettext("IP"); ?>
									</th>
								</tr>
					<?
					$i = 1;
					foreach ($host_list as $h) { $bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
					?>
						  
								<tr>
									<td bgcolor="<?=$bgcolor?>"><a href="host_report.php?host=<?=$h->ip?>" class="HostReportMenu" id="<?php echo $h->ip; ?>;<?php echo $h->hostname; ?>"><?php
										echo $h->hostname." ".(Host_os::get_os_pixmap($conn, $h->ip)); ?></a>
									</td>
									<td bgcolor="<?=$bgcolor?>"><a href="host_report.php?host=<?=$h->ip?>" class="HostReportMenu" id="<?php echo $h->ip; ?>;<?php echo $h->hostname; ?>"><?php
										echo $h->ip ?></a>
									</td>
								</tr>
					<? $i++; } ?>
							</table>
							</div>
							</td>
						</tr>
					<? } ?>
					</table>
				</td>
				</tr>
			</table>
		</td>
	</tr>
</table>



