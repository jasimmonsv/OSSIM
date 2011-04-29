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

function orderArray($x, $y){
	if ( $x['date'] == $y['date'] )
		return 0;
	else if ( $x['date'] > $y['date'] )
		return -1;
	else
		return 1;
}

require_once 'classes/Host.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Host_mac.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Host_netbios.inc';
require_once 'classes/Frameworkd_socket.inc';
require_once 'classes/Net.inc';

if (GET('edit') == "Update") 
{
    for ($i = 0;; $i++) 
	{
        $nagi = "nagios" . $i;
        $nagp = "port" . $i;
        $serv = GET($nagi);
        $nport = GET($nagp);
        
		if (!isset($_GET[$nagi])) break;

        if (isset($_GET[$nagp]) && is_numeric($nport)) 
            Host_services::set_nagios($conn, $host, $nport, 1);
        else 
            Host_services::set_nagios($conn, $host, $serv, 0);
        
    }
	
    $s = new Frameworkd_socket();
    
	if ($s->status) 
	{
        if ( !$s->write('nagios action="reload" "') )
			echo _("Frameworkd couldn't recieve a nagios command").".<br>";
        $s->close();
    } 
	else 
		echo _("Couldn't connect to frameworkd")."...<br>";
}

/* services update */
if (GET('origin') == 'active' && GET('update') == 'services') 
{
    $conf     = $GLOBALS["CONF"];
    $nmap     = $conf->get_conf("nmap_path");
    $services = shell_exec("$nmap -sV -P0 $host");
    $lines    = split("[\n\r]", $services);
    
	foreach($lines as $line) 
	{
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        if (isset($regs[0])) 
		{
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = getprotobyname($protocol);
            if ($protocol == - 1) 
			{
                $protocol = 0;
            } 
			
            $service      = $regs[2];
            $service_type = $regs[2];
            $version      = $regs[4];
            $origin       = 1;
            $date         = strftime("%Y-%m-%d %H:%M:%S");
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
					<td colspan="2" class="headerpr" height="20"><?php echo _("Inventory")?></td>
				</tr>
				
				<tr>
					<td width="50%" class="nobborder" valign="top">
						<table align="center" class="noborder" width="100%">
							<tr><th class="headergr"  colspan="2"> <?php echo _("Host Info"); ?> </th></tr>
						<?php
						$sensor_list = array();
                        $coordinates = array(0,0,2);
						
						if ($host_list = Host::get_list($conn, "WHERE ip = '$host'")) 
						{
							$host_aux = $host_list[0];
                            $coordinates = $host_aux->get_coordinates();
							$sensor_list = $host_aux->get_sensors($conn);
							?>
							<tr>
								<th> <?php echo _("Name"); ?> </th>
								<td><?php  echo $host_aux->hostname ?></td>
							</tr>
							<?php
						}
							?>
							<tr>
								<th>Ip</th>
								<td><strong><?php echo $host ?></strong></td>
							</tr>
							<?php
						
						if ($os = Host_os::get_ip_data($conn, $host)) 
						{
							?>
							<tr>
								<th><?php echo _("OS"); ?> </th>
								<td><?php echo $os["os"].Host_os::get_os_pixmap($conn, $host);?></td>
							</tr>
							<?php
						}
						
					
						if ($mac = Host_mac::get_ip_data($conn, $host)) 
						{
							?>
							<tr>
								<th>MAC</th>
								<td><?php echo $mac["mac"]; ?></td>
							</tr>
							<?php
						}
						
						if ($netbios_list = Host_netbios::get_list($conn, "WHERE ip = '$host'"))
						{
							$netbios = $netbios_list[0];
							?>
							<tr>
								<th> <?php echo _("Netbios Name"); ?> </th>
								<td><?php echo $netbios->name ?></td>
							</tr>
							
							<tr>
								<th> <?php echo _("Netbios Work Group"); ?> </th>
								<td><?php  echo $netbios->wgroup ?></td>
							</tr>
							<?php
						}
						?>
						</table>
					</td>
					
					<td valign="top" width="50%" class="nobborder">
						<table class="noborder" width="100%">
							<tr><th class="headergr"  colspan="2"><?php	echo _("Host belongs to:"); ?></th></tr>

							<?php
							if ($net_list = Net::get_list($conn)) 
							{
								foreach($net_list as $net) 
								{
									if (Net::is_ip_in_cache_cidr($conn, $host, $net->get_ips())) 
									{
									?>
									<tr>
										<th><?php echo _("Net"); ?></th>
										<td><?php echo $net->get_name() ?></td>
									</tr>
									<?php
									}
								}
							}
							
							if ($sensor_list) 
							{
								foreach($sensor_list as $sensor) 
								{
									$sensor_name = $sensor->get_sensor_name();
									?>
									<tr>
										<th><?php echo _("Sensor")?></th>
										<td><?php echo $sensor_name ?></td>
									</tr>
									<?php
								}
							}
							?>
					
							<tr><td colspan="2">&nbsp;</td></tr>
							<tr><th colspan="2" style="padding:1px"><a href="javascript:;" onclick="return false;" class="scriptinfo" ip="<?php echo $host?>" class="scriptinfo"><?php echo _("Who is?"); ?></a></td></tr>
						</table>
					</td>
				</tr>
                <?php
                if (intval($coordinates['lat'])!=0 && intval($coordinates['lon'])!=0) 
				{
					?>
				<tr>
					<td colspan="2" class="nobborder">
						<div id='map' style='height:200px; width:425px'></div>
						<script>
							var latitude  = '<?php echo $coordinates['lat']?>';
							var longitude = '<?php echo $coordinates['lon']?>';
							var zoom      = <?php echo $coordinates['zoom']?>;
							$(document).ready(function(){ 
								initialize();
							});
						</script>
					</td>
				</tr>
					<?php
                }
                ?>
				<tr>
					<td colspan="2" class="nobborder">
					<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
					<style type="text/css">
						#tableServicesTit{
							display:block;
						}
						#tableServices{
							display:block;
							height: 200px;
							overflow:auto;
							padding: 0;
							margin: 0;
						}
						#tableServices .tableServices_t1{
							width: 140px;
						}
						#tableServices .tableServices_t2{
							width: 220px;
						}
						#tableServices .tableServices_t3{
							width: 120px;
						}
						#tableServicesTit .tableServices_t1{
							width: 120px; font-size:11px;
						}
						#tableServicesTit .tableServices_t2{
							width: 200px; font-size:11px;
						}
						#tableServicesTit .tableServices_t3{
							width: 100px; font-size:11px;
					}
					</style>
					
					<table id="tableServicesTit" class="noborder">
						<tr>
							<th class="tableServices_t1"> <?php echo _("Property"); ?> </th>
							<th class="tableServices_t2"> <?php echo _("Version"); ?> </th>
							<th class="tableServices_t3"> <?php echo _("Date"); ?> </th>
						</tr>
					</table>
				  
					<table id="tableServices" class="noborder">
						<?php
						$services_list = Host_services::get_ip_data($conn, $host, "");
						$property_list = Host::get_host_properties($conn, $host);
						$temp_array=array_merge($services_list, $property_list);
						usort($temp_array, 'orderArray');
						//
						if (!empty($temp_array)) 
						{
							$i = 1;
							foreach($temp_array as $services) 
							{
								$bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
								?>
								<tr>
								<?php
									if( empty($services['id']) )
									{
										// Services
										?>
										<td class="tableServices_t1" bgcolor="<?php echo $bgcolor?>">
											<?php
											$servname = ($services['service'] != "unknown") ? $services['service'] : getservbyport($services['port'],getprotobynumber($services['protocol']));
											if ($servname == "") $servname = "unknown";
											echo ($servname) . " (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")" ?>
										</td>
										
										<td class="tableServices_t2" bgcolor="<?php echo $bgcolor?>">
											<?php if(!empty($services['version'])){ echo $services['version'] ?><br /><?php } ?>
											Nagios: <?php echo ($services['origin']) ? _("Active") : _("Passive")?>
										</td>
										
										<td class="tableServices_t3" bgcolor="<?php echo $bgcolor?>">
											<?php echo $services['date']; ?>
										</td>
							
									<?php
									}
									else
									{
										// Properties
										?>
										<td class="tableServices_t1" bgcolor="<?php echo $bgcolor?>">
											<?php if($services['anom']==1){ ?><img src="../pixmaps/warning.png" title="<?php echo _('Anomaly detection');?>" /><?php } ?>
											<?php
												$propertyName=Host::get_properties_types($conn, $services['property_ref']);
												echo $propertyName [0]['description'];
											?>
										</td>
										
										<td class="tableServices_t2" bgcolor="<?php echo $bgcolor?>">
											<?php if(!empty($services['sensor'])){ ?><?php echo _('Sensor').': '.$services['sensor']; ?><br /><?php } ?>
											<?php echo $services['value']; ?><br />
											<?php if(!empty($services['extra'])){ ?>
											<span style="font-size: 10px;color: grey"><?php echo $services['extra'];?></span><br />
											<?php } ?>
											<?php if(!empty($services['source'])){ ?><?php echo _('Source').': '.$services['source']; ?><br /><?php } ?>
										</td>
										
										<td class="tableServices_t3" bgcolor="<?php echo $bgcolor?>"><?php echo $services['date']; ?></td>
										<?php 
									}
									?>
								</tr>
								<?php
								$i++; 
							}
						}
						/*
						$servs = 0;
						if ($services_list = Host_services::get_ip_data($conn, $host, "")) {
							$i = 1;
							foreach($services_list as $services) {
								$bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
						?>
						  <tr>
							<td bgcolor="<?php echo $bgcolor?>"><?php
								$servname = ($services['service'] != "unknown") ? $services['service'] : getservbyport($services['port'],getprotobynumber($services['protocol']));
								if ($servname == "") $servname = "unknown";
								echo ($servname) . " (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")" ?></td>
							<td bgcolor="<?php echo $bgcolor?>"><?php echo $services['version'] ?></td>
							<?php // echo ($services['origin']) ? "Active" : "Passive"?>
							<td bgcolor="<?php echo $bgcolor?>"><?php echo $services['date']; ?></td>
						  </tr>
						<?php
							$i++; }
						}*/
						?>
					</table>
					</form>
				</td>
			</tr>
				  <?php /*
				<tr>
				  <td colspan="2" class="nobborder">
					<table class="noborder" width="100%">
					  <tr>
						<th> <?php echo _("Property"); ?> </th>
						<th> <?php echo _("Value"); ?> </th>
					  </tr>
				<?php
				if ($property_list = Host::get_host_properties($conn, $host)) {				
					$i = 1;
					foreach($property_list as $properties) {
						$bgcolor = ($i%2==0) ? "#E1EFE0" : "#FFFFFF";
				?>
				  <tr>
					<td bgcolor="<?php echo $bgcolor?>">
						<?php
							$propertyName=Host::get_properties_types($conn, $properties['property_ref']);
							echo $propertyName [0]['name'];
						?>
					</td>
					<td bgcolor="<?php echo $bgcolor?>">
						<?php
						echo $properties['value'];
						if(!empty($properties['extra'])){
							echo '<br /><span style="font-size: 10px;color: grey">'.$properties['extra'].'</span>';
						}
						?>
					</td>
				  </tr>
				<?php
					$i++; }
				}
				?>
					  </table>
				  </td>
				</tr>
				*/?>
		</table>
	</td>
	
	<td valign="top">
			<table height="100%">
				<tr>
					<td colspan="2" class="headerpr" height="20"><?php echo _("Network Usage"); ?></td>
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
