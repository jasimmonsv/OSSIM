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
require_once 'classes/Host_mac.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Host_netbios.inc';
require_once 'classes/Frameworkd_socket.inc';
require_once 'classes/Net.inc';

if (GET('edit') == "Update") {
    for ($i = 0;; $i++) {
        $nagi = "nagios" . $i;
        $nagp = "port" . $i;
        $serv = GET($nagi);
        $nport = GET($nagp);
        if (!isset($_GET[$nagi])) break;

        if (isset($_GET[$nagp]) && is_numeric($nport)) {
            Host_services::set_nagios($conn, $host, $nport, 1);
        } else {
            Host_services::set_nagios($conn, $host, $serv, 0);
        }
    }
    $s = new Frameworkd_socket();
    if ($s->status) {
        if (!$s->write('nagios action="reload" "')) echo "Frameworkd couldn't recieve a nagios command.<br>";
        $s->close();
    } else echo "Couldn't connect to frameworkd...<br>";
}
/* services update */
if (GET('origin') == 'active' && GET('update') == 'services') {
    $conf = $GLOBALS["CONF"];
    $nmap = $conf->get_conf("nmap_path");
    $services = shell_exec("$nmap -sV -P0 $host");
    $lines = split("[\n\r]", $services);
    foreach($lines as $line) {
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        if (isset($regs[0])) {
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = getprotobyname($protocol);
            if ($protocol == - 1) {
                $protocol = 0;
            } else {
            }
            $service = $regs[2];
            $service_type = $regs[2];
            $version = $regs[4];
            $origin = 1;
            $date = strftime("%Y-%m-%d %H:%M:%S");
            Host_services::insert($conn, $host, $port, $date, $_SERVER["SERVER_ADDR"], $protocol, $service, $service_type, $version, $origin); // origin = 0 (pads), origin = 1 (nmap)
            
        }
    }
}
?>
<table align="center" width="100%" height="100%" class="bordered">
	<tr>
		<td valign="top">
			<table>
				<tr>
					<td colspan="2" class="headerpr" height="20"><?=gettext("Inventory")?></td>
				</tr>
					<tr>
						<td width="50%" class="nobborder" valign="top">
							<table align="center" class="noborder" width="100%">
							  <tr><th class="headergr"  colspan="2"> <?php
						echo gettext("Host Info"); ?> </th></tr>
						<?php
						$sensor_list = array();
						if ($host_list = Host::get_list($conn, "WHERE ip = '$host'")) {
							$host_aux = $host_list[0];
							$sensor_list = $host_aux->get_sensors($conn);
						?>
							  <tr>
								<th> <?php
							echo gettext("Name"); ?> </th>
								<td><?php
							echo $host_aux->hostname ?></td>
							  </tr>

						<?php
						}
						?>
							  <tr>
								<th>Ip</th>
								<td><b><?php
						echo $host ?></b></td>
							  </tr>
						<?php
						if ($os = Host_os::get_ip_data($conn, $host)) {
						?>
							  <tr>
								<th> <?php
							echo gettext("OS"); ?> </th>
								<td>
						<?php
							echo $os["os"];
							echo Host_os::get_os_pixmap($conn, $host);
						?>
								</td>
							  </tr>
						<?php
						}
						?>

						<?php
						if ($mac = Host_mac::get_ip_data($conn, $host)) {
						?>
							  <tr>
								<th>MAC</th>
								<td><?php
							echo $mac["mac"]; ?></td>
							  </tr>
						<?php
						}
						?>
							
							  
						<?php
						if ($netbios_list = Host_netbios::get_list($conn, "WHERE ip = '$host'")) {
							$netbios = $netbios_list[0];
						?>
							  <tr>
								<th> <?php
							echo gettext("Netbios Name"); ?> </th>
								<td><?php
							echo $netbios->name ?></td>
							  </tr>
							  <tr>
								<th> <?php
							echo gettext("Netbios Work Group"); ?> </th>
								<td><?php
							echo $netbios->wgroup ?></td>
							  </tr>
						<?php
						}
						?>
							</table>
						</td>
						<td valign="top" width="50%" class="nobborder">
							<table class="noborder" width="100%">
							<tr><th class="headergr"  colspan="2"><?php
					echo gettext("Host belongs to:"); ?></td></tr>

					<?php
					if ($net_list = Net::get_list($conn)) {
						foreach($net_list as $net) {
							if (Net::isIpInNet($host, $net->get_ips())) {
					?>
						  <tr>
							<th><?php
								echo gettext("Net"); ?></th>
							<td><?php
								echo $net->get_name() ?></td>
						  </tr>
					<?php
							}
						}
					}
					if ($sensor_list) {
						foreach($sensor_list as $sensor) {
							$sensor_name = $sensor->get_sensor_name();
					?>
						  <tr>
							<th><?=gettext("Sensor")?></th>
							<td><?php
							echo $sensor_name ?></td>
						  </tr>
					<?php
						}
					}
					?>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr><th colspan="2" style="padding:1px"><a href="javascript:;" onclick="return false;" class="scriptinfo" ip="<?=$host?>" class="scriptinfo"><?php echo gettext("Who is?"); ?></a></td></tr>
					</table>
					</td>
				</tr>
				<tr>
				  <td colspan="2" class="nobborder">
				  <form method="GET" action="<?php
			echo $_SERVER['SCRIPT_NAME'] ?>">
				  <table class="noborder" width="100%">
				  <tr>
					<th> <?php
			echo gettext("Service"); ?> </th>
					<th> <?php
			echo gettext("Version"); ?> </th>
					<th> <?php
			echo gettext("Origin"); ?> </th>
				  </tr>
			<?php
			$servs = 0;
			if ($services_list = Host_services::get_ip_data($conn, $host, "")) {
				$i = 1;
				foreach($services_list as $services) {
					$bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
			?>
			  <tr>
				<td bgcolor="<?=$bgcolor?>"><?php
					$servname = ($services['service'] != "unknown") ? $services['service'] : getservbyport($services['port'],getprotobynumber($services['protocol']));
					if ($servname == "") $servname = "unknown";
					echo ($servname) . " (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")" ?></td>
				<td bgcolor="<?=$bgcolor?>"><?php
					echo $services['version'] ?></td>
				<td bgcolor="<?=$bgcolor?>"><?=($services['origin']) ? "Active" : "Passive"?></td>
			  </tr>
			<?php
				$i++; }
			}
			?>
				  </table>
					</form>
				  </td>
				  </tr>
				
			</table>
		</td>
		<td valign="top">
			<table height="100%">
				<tr>
					<td colspan="2" class="headerpr" height="20">Network Usage</td>
				</tr>
				<!--
				<tr>
					<td><img src="../pixmaps/ntop_graph_thumb.gif"></td>
				</tr>
				<tr>
					<td><img src="../pixmaps/ntop_graph_thumb.gif"></td>
				</tr>
				-->
				
				<tr>
					<td id="graph1"><img src="../pixmaps/ntop_graph_thumb_gray.gif"></td>
				</tr>
				<tr>
					<td id="graph2"><img src="../pixmaps/ntop_graph_thumb_gray.gif"></td>
				</tr>
				
			</table>
		</td>
	</tr>
</table>
