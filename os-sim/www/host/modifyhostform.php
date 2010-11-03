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
* - match_os()
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_scan.inc');
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');
require_once ('classes/Frameworkd_socket.inc');
require_once ('classes/Port.inc');
require_once ('classes/Util.inc');



/* print SELECTED for html-select when os is matched */
function match_os($pattern, $os) {
    $pattern = "/$pattern/i";
    if (preg_match($pattern, $os)) echo " selected='selected' ";
}

Session::logcheck("MenuPolicy", "PolicyHosts");


$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("ip"));
if (ossim_error()) 
    die(ossim_error());

$db = new ossim_db();
$conn = $db->connect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){

			$(".sensor_info").simpletip({
							position: 'top',
							offset: [-60, -10],
							content: '',
							baseClass: 'ytooltip',
							onBeforeShow: function() {
									var txt = this.getParent().attr('txt');
									this.update(txt);
							}
			});

			// Autocomplete ports
			var ports = [
				<?php
				$ports = Port::get_list($conn);
				
				$nports=count($ports);
				foreach ($ports as $key => $port){
				?>
				{ txt:"<?php echo $port->get_port_number()."-".$port->get_protocol_name(); ?>", id: "<?php echo $port->get_port_number()."-".$port->get_protocol_name(); ?>" }<?php if($nports>1&&$key<($nports-1)){ echo ','; }}?>
			];
		
			$("#port").autocomplete(ports, {
				minChars: 0,
				width: 300,
				max: 100,
				matchContains: true,
				autoFill: true,
				formatItem: function(row, i, max) {
					return row.txt;
				}
			}).result(function(event, item) {
				//$(".hosts").val('');
				$('#newport').val(item.id);
			});
		});
	</script>
	
	<style type='text/css'>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
	</style>
	
</head>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
    $host = $host_list[0];


if (empty($host)) {
	Util::print_error(_("You don't have permission to modify this host"));
    exit;
}

if ( GET('edit') == _("Modify") ) 
{
	for ($i = 0;; $i++)
	{
        $nagi = "nagios" . $i;
        $nagp = "port" . $i;
        $serv = GET($nagi);
        $nport = GET($nagp);
        if (!isset($_GET[$nagi])) break;
        if (isset($_GET[$nagp]) && is_numeric($nport)) 
            Host_services::set_nagios($conn, $ip, $nport, 1);
        else
            Host_services::set_nagios($conn, $ip, $serv, 0);
        
    }
    $s = new Frameworkd_socket();
    if ($s->status) {
        if (!$s->write('nagios action="reload" "')) 
			Util::print_error(_("Frameworkd couldn't recieve a nagios command").".<br>");
        $s->close();
    } 
	else 
		Util::print_error(_("Couldn't connect to frameworkd")."...<br>");
}

if(GET('deleteService')!=null){
    $explode=explode('-',GET('deleteService'));

    Host_services::deleteUnit($conn, $ip, $explode[0], $explode[1], $explode[2]);
}
/* services update */
if (GET('update') == 'services')
{
    $conf = $GLOBALS["CONF"];
    $nmap = $conf->get_conf("nmap_path");
    $services = shell_exec("$nmap -sV -P0 $ip");
    $lines = split("[\n\r]", $services);
    foreach($lines as $line)
	{
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        if (isset($regs[0]))
		{
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = getprotobyname($protocol);
            if ($protocol == - 1) {
                $protocol = 0;
            } 
			
            $service = $regs[2];
            $service_type = $regs[2];
            $version = $regs[4];
            $origin = 1;
            $date = strftime("%Y-%m-%d %H:%M:%S");
            Host_services::insert($conn, $ip, $port, $date, $_SERVER["SERVER_ADDR"], $protocol, $service, $service_type, $version, $origin); // origin = 0 (pads), origin = 1 (nmap)
        }
    }
}

if (GET('newport') != ""||GET('port')!="")
{
	if( GET('newport') == "" )
		$newPort=GET('port');
	else
		$newPort=GET('newport');
	
	$aux = explode("-",$newPort);
	$port_number    = $aux[0];
	$protocol_name  = $aux[1];
	$newport_nagios = (GET('newportnagios') != "") ? 1 : 0;
	ossim_valid($port_number, OSS_DIGIT, 'illegal:' . _("port number"));
	ossim_valid($protocol_name, OSS_ALPHA, 'illegal:' . _("protocol name"));
	
	if (ossim_error()) 
		die(ossim_error());
	
	$date = strftime("%Y-%m-%d %H:%M:%S");

    $serviceName=getservbyport($port_number,$protocol_name);
    if($serviceName=='')
        $serviceName='unknown';
        
	Host_services::insert($conn, $ip, $port_number, $date, $_SERVER["SERVER_ADDR"], $protocol_name, $serviceName, "unknown", "unknown", 1, $newport_nagios); // origin = 0 (pads), origin = 1 (nmap)
	
}

?>
	<table align="center" class="noborder" style='background-color: transparent;'>
		<tr>
			<td class="nobborder" valign="top">
				<table>
					<form method="post" action="modifyhost.php">
					<input type="hidden" name="insert" value="insert">
					<input type="hidden" name="hostname_old" value="<?php echo $host->get_hostname() ?>">
					<tr>
						<th> <?php echo gettext("Hostname"); ?></th>
						<td class="left"><input type="text" name="hostname" size="25" value="<?php
							echo $host->get_hostname(); ?>"/><span style="padding-left: 3px;">*</span></td>
					</tr>
					
					<tr>
						<th> <?php echo gettext("IP"); ?></th>
						<input type="hidden" name="ip" value="<?php echo $host->get_ip(); ?>">
						<td class="left"><b><?php echo $host->get_ip(); ?></b></td>
					</tr>
	  
	  
					<tr>
						<th> 
							<?php echo gettext("FQDN/Aliases"); ?> 
							<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Comma-separated FQDN or aliases")?></div>">
							<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
						</th>
						<td class="left">
							<textarea name="fqdns" rows="2" cols="40"><?php $fqdns = $host->get_fqdns(); echo ($fqdns != "NULL") ? $fqdns : ""; ?></textarea>
						</td>
					</tr>

					<tr>
						<th> <?php echo gettext("Description"); ?> </th>
						<td class="left">
							<textarea name="descr" rows="3" cols="40"><?php $dscr = $host->get_descr(); echo ($dscr != "NULL") ? $dscr : ""; ?></textarea>
						</td>
					</tr>

					<tr>
						<th> <?php echo gettext("Asset"); ?> </th>
						<td class="left">
							<select name="asset">
								<option <?php if ($host->get_asset() == 0) echo " selected='selected' "; ?> value="0"><?php echo gettext("0"); ?> </option>
								<option <?php if ($host->get_asset() == 1) echo " selected='selected' "; ?> value="1"><?php echo gettext("1"); ?> </option>
								<option <?php if ($host->get_asset() == 2) echo " selected='selected' "; ?> value="2"><?php echo gettext("2"); ?> </option>
								<option <?php if ($host->get_asset() == 3) echo " selected='selected' "; ?> value="3"><?php echo gettext("3"); ?> </option>
								<option <?php if ($host->get_asset() == 4) echo " selected='selected' "; ?> value="4"><?php echo gettext("4"); ?> </option>
								<option <?php if ($host->get_asset() == 5) echo " selected='selected' "; ?> value="5"><?php echo gettext("5"); ?> </option>
							</select><span style="padding-left: 3px;">*</span>
						</td>
					</tr>
  
					<tr>
						<th> <?php echo gettext("NAT"); ?> </th>
						<td class="left"><input type="text" name="nat" size="25" value="<?php echo $host->get_nat(); ?>"></td>
					</tr>
					
					<tr>
						<th> <?php echo gettext("Sensors"); ?>
							<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Define which sensors has visibility of this host")?></div>">
							<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
							</a><br/>
							<span><a href="../sensor/newsensorform.php"><?php echo gettext("Insert new sensor"); ?> ?</a></span>
						</th>
						
						<td class="left">
							<?php
							/* ===== sensors ==== */
							$i = 1;
							if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
								foreach($sensor_list as $sensor) 
								{
									$sensor_name = $sensor->get_name();
									$sensor_ip = $sensor->get_ip();
									if ($i == 1) {
										echo "<input type='hidden' name='nsens' value='".count($sensor_list)."'/>";
									}
									$name = "mboxs" . $i;
									
									$checked = ( Host_sensor_reference::in_host_sensor_reference($conn, $host->get_ip() , $sensor_name) ) ? "checked='checked'"  : '';
									echo "<input type='checkbox' name='$name' value='$sensor_name' $checked/>";
									echo $sensor_ip . " (" . $sensor_name . ")<br>"; 
									
									$i++;
								}
							}
							?>
						</td>
					</tr>

					<tr>
						<td style="text-align: left; border:none; padding-top:3px;">
							<a onclick="$('.advanced').toggle()" style="cursor:pointer;">
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Advanced")?></a>
						</td>
					</tr>
          
					<tr class="advanced" style="display:none;">
						<th> <?php echo gettext("Scan options"); ?> </th>
						<td class="left">
						<!-- <input type="checkbox"
						<?php if (Host_scan::in_host_scan($conn, $host->get_ip() , 3001)) {
								echo " checked='checked' ";
						}		
						?>
						name="nessus" value="1"><?php echo gettext("Enable nessus scan"); ?> </input><br>-->
						<?php
							$checked = ( Host_scan::in_host_scan($conn, $host->get_ip() , 2007) ) ? "checked='checked'" : '';
							echo "<input type='checkbox' name='nagios' value='1'/>".gettext("Enable nagios");
						?>

						</td>
					</tr>

	  
					<tr class="advanced" style="display:none;">
						<th> 
							<?php echo gettext("RRD Profile"); ?><br/>
							<span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
						</th>
						<td class="left">
							<select name="rrd_profile">
								<?php
								foreach(RRD_Config::get_profile_list($conn) as $profile)
								{
										$host_profile = $host->get_rrd_profile();
										if (strcmp($profile, "global")) {
												$option = "<option value=\"$profile\"";
												if (0 == strcmp($host_profile, $profile)) $option.= " selected='selected' ";
												$option.= ">$profile</option>\n";
												echo $option;
										}
								}
								?>
								<option value="" <?php if (!$host_profile) echo " selected='selected' " ?>><?php echo gettext("None"); ?> </option>
							</select>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>

					<tr class="advanced" style="display:none;">
						<th><?php echo gettext("Threshold C"); ?></th>
						<td class="left">
							<input type="text" name="threshold_c" size="11" value="<?php echo $host->get_threshold_c(); ?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>

					<tr class="advanced" style="display:none;">
						<th> <?php echo gettext("Threshold A"); ?></th>
						<td class="left">
							<input type="text" name="threshold_a" size="11" value="<?php echo $host->get_threshold_a(); ?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>
					
					<!--
					  <tr>
						<th>Alert</th>
						<td class="left">
						  <select name="alert">
							<option <?php // if ($host->get_alert() == 1) echo " selected='selected' ";
					 ?>
								value="1">Yes</option>
							<option <?php // if ($host->get_alert() == 0) echo " selected='selected' ";
					 ?>
								value="0">No</option>
						  </select>
						</td>
					  </tr>
					  <tr>
						<th>Persistence</th>
						<td class="left">
						  <input type="text" name="persistence" size="3"
								 value="<?php //echo $host->get_persistence();
					 ?>">min.
						</td>
					  </tr>
					-->
	  
					<tr><td style="text-align: left; border:none; padding-top:3px;"><a onclick="$('.inventory').toggle()" style="cursor:pointer;"><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"><?=gettext("Inventory")?></a></td></tr>
					<tr class="inventory" style="display:none;">
						<th> <?php echo gettext("OS"); ?> </th>
						<td class="left">
							<select name="os" style="width:170px">
								<option value="Unknown"> </option>
								<option value="Windows" <?php match_os("Win", $host->get_os($conn))    ?>><?php echo _("Microsoft Windows"); ?> </option>
								<option value="Linux"   <?php match_os("Linux", $host->get_os($conn))  ?>><?php echo _("Linux"); ?> </option>
								<option value="FreeBSD" <?php match_os("FreeBSD", $host->get_os($conn))?>><?php echo _("FreeBSD"); ?> </option>
								<option value="NetBSD"  <?php match_os("NetBSD", $host->get_os($conn)) ?>><?php echo _("NetBSD"); ?> </option>
								<option value="OpenBSD" <?php match_os("OpenBSD", $host->get_os($conn))?>><?php echo _("OpenBSD"); ?> </option>
								<option value="MacOS"   <?php match_os("MacOS", $host->get_os($conn))  ?>><?php echo _("Apple MacOS"); ?> </option>
								<option value="Solaris" <?php match_os("Solaris", $host->get_os($conn))?>><?php echo _("SUN Solaris"); ?> </option>
								<option value="Cisco"   <?php match_os("Cisco", $host->get_os($conn))  ?>><?php echo _("Cisco IOS"); ?> </option>
								<option value="AIX"     <?php match_os("AIX", $host->get_os($conn))    ?>><?php echo _("IBM AIX"); ?> </option>
								<option value="HP-UX"   <?php match_os("HP-UX", $host->get_os($conn))  ?>><?php echo _("HP-UX"); ?> </option>
								<option value="Tru64"   <?php match_os("Tru64", $host->get_os($conn))  ?>><?php echo _("Compaq Tru64"); ?> </option>
								<option value="IRIX"    <?php match_os("IRIX", $host->get_os($conn))   ?>><?php echo _("SGI IRIX"); ?> </option>
								<option value="BSD/OS"  <?php match_os("BSD\/OS", $host->get_os($conn))?>><?php echo _("BSD/OS"); ?> </option>
								<option value="SunOS"   <?php match_os("SunOS", $host->get_os($conn))  ?>><?php echo _("SunOS"); ?> </option>
								<option value="Plan9"   <?php match_os("Plan9", $host->get_os($conn))  ?>><?php echo _("Plan9"); ?> </option> 
								<option value="IPhone"  <?php match_os("IPhone", $host->get_os($conn)) ?>><?php echo _("IPhone"); ?> </option>
							</select>
						</td>
					</tr>
				  
					<tr class="inventory" style="display:none;">
						<th> <?php echo gettext("Mac Address"); ?> </th>
						<td class="left">
							<input type="text" name="mac" size="25" value="<?php echo $host->get_mac_address($conn); ?>" />
						</td>
					</tr>

					<tr class="inventory" style="display:none;">
						<th> <?php echo gettext("Mac Vendor"); ?> </th>
						<td class="left">
							<input type="text" name="mac_vendor" size="25" value="<?php echo $host->get_mac_vendor($conn); ?>" />
						</td>
					</tr>

					<tr>
						<td style="text-align: left; border:none; padding-top:3px;">
							<a onclick="$('.geolocation').toggle()" style="cursor:pointer;">
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Geolocation Info")?></a>
						</td>
					</tr>
					
					<tr class="geolocation" style="display:none;">
						<th> <?php echo gettext("Latitude"); ?></th>
						<td class="left">
							<input type="text" name="latitude" size="25" value="<?php $coordinates = $host->get_coordinates(); echo $coordinates['lat']; ?>"/>
						</td>
					</tr>
					
					<tr class="geolocation" style="display:none;">
						<th> <?php  echo gettext("Longitude"); ?></th>
						<td class="left">
							<input type="text" name="longitude" size="25" value="<?php $coordinates = $host->get_coordinates(); echo $coordinates['lon']; ?>"/>
						</td>
					</tr>
		
					<tr>
						<td colspan="2" align="center" style="border-bottom: 0px; padding: 10px;">
						<input type="submit" value="<?=_("OK")?>" class="button"/>
						<input type="reset" value="<?=_("Reset")?>" class="button"/>
						</td>
					</tr>
				</table>
			</form>
		</td>
		
		<td valign="top" class="nobborder" style="min-width: 400px;">
			<table class="noborder" width="100%">
				<tr>
					<th colspan="2" style="padding:5px"> <?php echo gettext("Port / Service information"); ?>
					[ <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?ip=<?php echo $ip ?>&update=services">
						<?php echo gettext("Scan"); ?> </a> ]
					</th>
				</tr>
				
				<?php
				$servs = 0; 
				if ($services_list = Host_services::get_ip_data($conn, $ip, '1'))
				{ 
				?>
				<tr>
					<td colspan="2" class="nobborder">
						<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
						<input type="hidden" name="ip" value="<?=GET('ip')?>"/>
						<table width="450px">
							<tr>
								<th width="70px"> <?php	echo gettext("Service"); ?> </th>
								<th> <?php echo gettext("Version"); ?> </th>
								<th> <?php echo gettext("Date"); ?> </th>
								<th> <?php echo gettext("Nagios"); ?> </th>
								<th> <?php echo gettext("Actions"); ?></th>
							</tr>
							
							<?php foreach($services_list as $services) { ?>
							<tr>
								<td class='left'><?php echo "<span style='font-weight: bold; font-size:8pt;'>".$services['service']."</span><span style='font-size: 7pt; color: #333333;'> (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")</span>" ?></td>
								<td><?php echo ($services['version'] != "") ? $services['version'] : _("Unknown") ?></td>
								<td style="font-size:7pt;"><?php echo $services['date'] ?></td>
								<td>
									<input type="checkbox" name="port<?php echo $servs; ?>" value="<?php echo $services['port'] ?>"<?php if ($services['nagios']) echo "checked='checked'"; ?>/>
									<input type="hidden" name="nagios<?php echo $servs++; ?>" value="<?php echo $services['port'] ?>"/>
								</td>
								<td valign='middle'>
									<a href="modifyhostform.php?ip=<?php echo $ip; ?>&deleteService=<?php echo $services['port'].'-'.$services['protocol'].'-'.$services['service']; ?>">
									<img src="../vulnmeter/images/delete.gif" width="16" height="16" border="0" /></a>
								</td>
							</tr>
							<?php }
							if ($servs > 0) { ?>
							<tr>
								<td class="nobborder" colspan="4"></td>
								<td class="nobborder" style='padding: 5px 0px;'>
									<input type="submit" name="edit" value="<?=_("Modify")?>" class="lbutton"/>
									<input type="hidden" name="host" value="<?php echo $ip ?>"/>
									<input type="hidden" name="origin" value="<?php echo GET('origin') ?>"/>
								</td>
							</tr>
							<? } ?>
						</table>
						</form>
					</td>
				</tr>
			<? } ?>
				<tr>
					<td class="nobborder">
						<table width="100%">
                            <tr>
								<td class="nobborder">
									<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
									<input type="hidden" name="ip" value="<?=GET('ip')?>"/>
									<table class="transparent" width="100%">
										<tr><th colspan="3"><?=_("Add new service")?></th></tr>
										<tr>
											<td class="nobborder left" width="48%">
												<? /*$ports2 = Port::get_list($conn); ?>
												<select name="newport">
												<? foreach ($ports2 as $port3)?>
													<option value="<?=$port3->get_port_number()."-".$port3->get_protocol_name()?>"><?=$port3->get_port_number()."-".$port3->get_protocol_name()?></option>
												</select>
												 *
												 */?>
												<input type="hidden" id="newport" name="newport" value="<?php //echo $assetst?>"/>
												<input type="text" name="port" style="width: 180px; height:20px; color: black;" id="port" />
											</td>
											<td class="nobborder left" width="30%">
												<input type="checkbox" name="newportnagios" value="1"/><span style="padding-left: 5px;">Nagios</span>
											</td>
											<td class="nobborder" style="text-align: right;">
												<input type="submit" value="<?=_("OK")?>" class="lbutton"/>
											</td>
										</tr>
									</table>
									</form>
								</td>
							</tr>
                        </table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="noborder"><p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p></td>
		<td class='noborder'></td>
	</tr>
</table>


</body>
</html>
<?php $db->close($conn); ?>
