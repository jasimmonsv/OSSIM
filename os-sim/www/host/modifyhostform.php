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
require_once ('classes/Protocol.inc');
require_once ('classes/Util.inc');


Session::logcheck("MenuPolicy", "PolicyHosts");

$db = new ossim_db();
$conn = $db->connect();

$ip = GET('ip');

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip"));

if (ossim_error()) 
    die(ossim_error());
	
$ports           = array();
$port_list       = array();
$arr_ports_input = array();
$ports_input     = "";

if ($port_list = Port::get_list($conn))
{
    foreach($port_list as $port) 
        $ports[$port->get_port_number()." - ".$port->get_protocol_name()] = $port->get_service();
}

// check service file

$services = shell_exec("egrep 'tcp|udp' /etc/services | awk '{print $1 $2 }'");
$lines    = split("[\n\r]", $services);

foreach($lines as $line)
{
    preg_match('/(\D+)(\d+)\/(.+)/', $line, $regs);
    if($ports[$regs[2]." - ".$regs[3]] == "") {
        $ports[$regs[2]." - ".$regs[3]] = $regs[1];
    }
}


$array_assets = array ( '0'=>'0', "1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5");

$array_os = array ( "Unknown" => "",
					"Win"     => "Microsoft Windows",
					"Linux"   => "Linux",
					"FreeBSD" => "FreeBSD",
					"NetBSD"  => "NetBSD",
					"OpenBSD" => "OpenBSD",
					"MacOS"   => "Apple MacOS",
					"Solaris" => "SUN Solaris",
					"Cisco"   => "Cisco IOS",
					"AIX"     => "IBM AIX",
					"HP-UX"   => "HP-UX",
					"Tru64"   => "Compaq Tru64",
					"IRIX"    => "SGI IRIX",
					"BSD\/OS"  => "BSD/OS",
					"SunOS"   => "SunOS",
					"Plan9"   => "Plan9",
					"IPhone"  => "IPhone");

$conf = $GLOBALS["CONF"];					
$sensors  = array();

$threshold_a = $threshold_c = $conf->get_conf("threshold");
$hostname = $fqdns = $descr = $nat = $nagios = $os = $mac = $mac_vendor = $latitude = $longitude = "";
$rrd_profile = "None";

// load protocol ids
$protocol_ids = array();
if($protocol_list = Protocol::get_list($conn)) {
    foreach($protocol_list as $protocol_data) {
        $protocol_ids[$protocol_data->get_name()] = $protocol_data->get_id(); 
    }
}

if ( isset($_SESSION['_host']) )
{
	$hostname      = $_SESSION['_host']['hostname'];
	$old_hostname  = $_SESSION['_host']['old_hostname'];
	$ip            = $_SESSION['_host']['ip'];  	
	$fqdns         = $_SESSION['_host']['fqdns']; 
	$descr	       = $_SESSION['_host']['descr']; 
	$asset         = $_SESSION['_host']['asset'];
	$nat           = $_SESSION['_host']['nat'];  	
	$sensors       = $_SESSION['_host']['sensors'];  
	$nagios        = $_SESSION['_host']['nagios'];	
	$rrd_profile   = $_SESSION['_host']['rrd_profile'];  
	$threshold_a   = $_SESSION['_host']['threshold_a']; 
	$threshold_c   = $_SESSION['_host']['threshold_c']; 
	$os            = $_SESSION['_host']['os']; 
	$mac           = $_SESSION['_host']['mac']; 
	$mac_vendor    = $_SESSION['_host']['mac_vendor']; 
	$latitude      = $_SESSION['_host']['latitude']; 
	$longitude     = $_SESSION['_host']['longitude']; 
	
	unset($_SESSION['_host']);
}
else
{
	if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
		$host = $host_list[0];
		
	
	if ( !empty($host) )
	{
    	$hostname        = $old_hostname = $host->get_hostname();
		$fqdns           = $host->get_fqdns();
		$descr	         = $host->get_descr();
		$asset           = $host->get_asset();
		$nat             = $host->get_nat();
		
		$tmp_sensors     = $host->get_sensors($conn);
				
		foreach($tmp_sensors as $sensor) 
			$sensors[]   = $sensor->get_sensor_name();
		
		$nagios          =  ( Host_scan::in_host_scan($conn, $ip, 2007)) ? "1" : ''; 
		
		$rrd_profile     = $host->get_rrd_profile();
		
		if (!$rrd_profile) 
			$rrd_profile = "None";
		
		$threshold_a     = $host->get_threshold_a();
		$threshold_c     = $host->get_threshold_c();
		$os              = $host->get_os($conn);
		$mac             = $host->get_mac_address($conn);
		$mac_vendor      = $host->get_mac_vendor($conn);
		
		$coordinates     = $host->get_coordinates();

		$latitude        = $coordinates['lat'];
		$longitude       = $coordinates['lon'];
		
		$num_sensors     = count($sensors);
	}
}


$style = "style='display: none;'";

if ( GET('edit') == _("Update Services") ) 
{
	for ($i = 0;; $i++)
	{
        $nagi   = "nagios" . $i;
        $nagp   = "port" . $i;
        $serv   = GET($nagi);
        $nport  = GET($nagp);
		
        if (!isset($_GET[$nagi])) 
			break;
        
		if ( isset($_GET[$nagp]) && is_numeric($nport) ) 
            Host_services::set_nagios($conn, $ip, $nport, 1);
        else
            Host_services::set_nagios($conn, $ip, $serv, 0);
        
    }
    
	$s = new Frameworkd_socket();
    if ($s->status) {
        if ( !$s->write('nagios action="reload" "') ) 
			$error_nagios[] = _("Frameworkd couldn't recieve a nagios command");
			
        $s->close();
    } 
	else 
		$error_nagios[] = _("Couldn't connect to frameworkd");
		
}

if( GET('deleteService')!=null )
{
    $explode=explode('-',GET('deleteService'),3);

    Host_services::deleteUnit($conn, $ip, $explode[0], $explode[1], $explode[2]);
}

/* services update */
if ( GET('update') == 'services' )
{
    $conf     = $GLOBALS["CONF"];
    $nmap     = $conf->get_conf("nmap_path");
    $services = shell_exec("$nmap -sV -P0 $ip");
    $lines    = split("[\n\r]", $services);
    foreach($lines as $line)
	{
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        
		if (isset($regs[0]))
		{
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = getprotobyname($protocol);
            if ($protocol == - 1)
                $protocol = 0;
            			
            $service = $regs[2];
            $service_type = $regs[2];
            $version = $regs[4];
            $origin = 1;
            $date = strftime("%Y-%m-%d %H:%M:%S");
            Host_services::insert($conn, $ip, $port, $date, $_SERVER["SERVER_ADDR"], $protocol, $service, $service_type, $version, $origin); // origin = 0 (pads), origin = 1 (nmap)
        }
    }
}

if ( GET('newport') != "" || GET('port')!="" )
{
	if( GET('newport') == "" )
		$newPort=GET('port');
	else
		$newPort=GET('newport');
	
	$aux            =  explode("-",$newPort);
	$port_number    =  trim($aux[0]);
	$protocol_name  =  trim($aux[1]);
	$nservice       =  GET('service');
    $newport_nagios =  (GET('newportnagios') != "") ? 1 : 0;
	
	ossim_valid($port_number, OSS_PORT, 'illegal:' . _("Port number"));
	ossim_valid($protocol_name, OSS_PROTOCOL, 'illegal:' . _("Protocol name"));
    ossim_valid($nservice, OSS_NULLABLE,OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Service"));
	
		
	if ( ossim_error() ) 
	{
		$service_error = "<div style='padding-left: 10px'>".ossim_get_error_clean()."</div>";
		ossim_clean_error();
		$style = "style='display: block;'";
	}
	else
	{
		$date = strftime("%Y-%m-%d %H:%M:%S");
		
        
        if( $nservice !='') {
            $serviceName = $nservice;
        }
        else if ($ports[$port_number." - ".$protocol_name]!="") {
            $serviceName = $ports[$port_number." - ".$protocol_name];
        }
        else{
            $serviceName = 'unknown';
        }

        // insert new port?
        $chport = array();
        $chport = Port::get_list($conn, "where port_number = $port_number and protocol_name = '$protocol_name'");
        if(count($chport)==0) {
            Port::insert($conn, $port_number, $protocol_name, $serviceName, "");
        }
		Host_services::insert($conn, $ip, $port_number, $date, $_SERVER["SERVER_ADDR"], $protocol_ids[$protocol_name], $serviceName, "unknown", "unknown", 1, $newport_nagios); // origin = 0 (pads), origin = 1 (nmap)
		
	}	
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>

	<script type="text/javascript">
		
		function check_host () {
			
			var ip = $("#ip").val();
			
			$.ajax({
				type: "GET",
				url: "check_host_response.php?ip="+ip,
				data: "",
				success: function(msg){
					if (msg == "1")
					{
						if (confirm("Do you want to update host '"+ip+"'?"))
							submit_form();
					}
					else 
						submit_form();
				}
			});
		}
		
		$(document).ready(function(){

			$(".sensor_info").simpletip({
							position: 'top',
							offset: [-60, -10],
							content: '',
							baseClass: 'yltooltip',
							onBeforeShow: function() {
									var txt = this.getParent().attr('txt');
									this.update(txt);
							}
			});

			$(".extra").simpletip({
							position: 'bottom',
							offset: [0, 0],
							content: '',
							baseClass: 'ytooltip',
							onBeforeShow: function() {
									var txt = this.getParent().attr('txt');
									this.update(txt);
							}
			});
			
			// Autocomplete ports
			/*var ports = [ <?= $ports_input ?> ];
		
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
			});*/
			
			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "modifyhost.php");
			});
			
		});
    function saveService() {
        if($('#port').val()<0 || $('#port').val()>65535){
            alert('Error: Malformed port is between 0 and 65535');
            return false;
        }
        if($('#service').val()=="") {
            $('#service').val("Unknown");
        }
        var newService = $('#port').val()+' - '+$('#protocol').val();
        $('#newport').val(newService);

        $('#serviceform').submit();
    }
    function fillService() {
        $("#service").attr('disabled','');
        var ports = new Array(); 
        <?php
        foreach($ports as $k => $v) {
            echo "ports['$k'] = '$v';\n";
        }
        
        ?>
        if(typeof ports[$('#port').val()+' - '+$('#protocol').val()] !== 'undefined') {
            $('#service').val(ports[$('#port').val()+' - '+$('#protocol').val()]);
            $("#service").attr('disabled','disabled');
        }
        else {
            $('#service').val("");
        }
    }
	</script>
	
	<style type='text/css'>
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		a {cursor:pointer;}
	</style>
	
</head>
<body>



<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 

	
if ( empty( $ip ) ) {
	Util::print_error(_("You don't have permission to modify this host"));
    exit;
}


if (count($error_nagios) > 0)
{
	$message_error = implode("<br/>", $error_nagios);
	Util::print_error($message_error);
}


?>
	<div id='info_error' class='ossim_error' <?php echo $style ?>><?php echo $service_error;?></div>
	
	<table align="center" class="noborder" style='background-color: transparent;'>
		<tr>
			<td class="nobborder" valign="top">
				<table>
					<form method="post" id='formhost' name='formhost' action="modifyhost.php">
					<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
					<input type="hidden" name="insert" value="insert"/>
					<input type="hidden" name="old_hostname" id="old_hostname" value="<?php echo $old_hostname; ?>"/>
					
					<tr>
						<th><label for='hostname'><?php echo gettext("Hostname"); ?></label></th>
						<td class="left">
							<input type="text" class='req_field vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>
					
					<tr>
						<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
						<td class="left">
							<input type="hidden" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
							<div class='bold'><?php echo $ip; ?></div></td>
						</td>
					</tr>
	  	  
					<tr>
						<th>
							<label for='fqdns'><?php echo gettext("FQDN/Aliases"); ?></label>
							<a class="sensor_info"  txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Comma-separated FQDN or aliases")?></div>">
							<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
						</th>
						<td class="left">
							<textarea name="fqdns" id="fqdns" class='vfield'><?php echo $fqdns;?></textarea>
						</td>
					</tr>
					

					<tr>
						<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
						<td class="left">
							<textarea name="descr" id="descr" class='vfield'><?php echo $descr;?></textarea>
						</td>
					</tr>

					<tr>
						<th><label for='asset'><?php echo gettext("Asset value"); ?></label></th>
						<td class="left">
							<select name="asset" id="asset" class='req_field vfield'>
							<?php 
								if ( !in_array($asset, $array_assets) )
									$asset = "2";
								
								foreach ($array_assets as $v)
								{
									$selected = ($asset == $v) ? "selected='selected'" : '';
									echo "<option value='$v' $selected>$v</option>";
								}
							?>
							</select>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>
  
					<tr style="display:none">
						<th><label for='nat'><?php echo gettext("NAT");?></label></th>
						<td class="left">
							<input type="text" class='vfield' name="nat" id="nat" value="<?php echo $nat;?>"/>
						</td>
					</tr>

					<tr>
						<th>
							<label for='sboxs1'><?php echo gettext("Sensors"); ?></label>
							<a class="sensor_info"  txt="<div style='width: 150px; white-space: normal; font-weight: normal;'><?=gettext("Define which sensors has visibility of this host")?></div>">
							<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/></a><br/>
							<span><a href="../sensor/newsensorform.php"><?=gettext("Insert new sensor");?>?</a></span>
						</th>
						<td class="left">
							<?php
							/* ===== Sensors ==== */
							$i = 1;
							
							if ($sensor_list = Sensor::get_all($conn, "ORDER BY name"))
							{
								foreach($sensor_list as $sensor) {
									$sensor_name = $sensor->get_name();
									$sensor_ip = $sensor->get_ip();
																	
									$class = ($i == 1) ? "class='req_field'" : "";
																	
									$sname = "sboxs".$i;
									$checked = ( in_array($sensor_name, $sensors) )  ? "checked='checked'"  : '';
									
									echo "<input type='checkbox' name='sboxs[]' $class id='$sname' value='$sensor_name' $checked/>";
									echo $sensor_ip . " (" . $sensor_name . ")<br/>"; 
								  
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
						<th><label for='nagios'><?php echo gettext("Scan options"); ?></label></th>
						<td class="left">
							<?php $checked = ($nagios == '1') ? "checked='checked'" : ''; ?>		
							<input type="checkbox" class='vfield' name="nagios" id="nagios" value="1" <?php echo $checked;?>/> <?php echo gettext("Enable nagios"); ?>
						</td>
					</tr>

					<tr class="advanced" style="display:none;">
						<th>
							<label for='rrd_profile'><?php echo gettext("RRD Profile"); ?></label><br/>
							<span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
						</th>
						<td class="left">
							<select name="rrd_profile" id="rrd_profile" class='vfield'>
								<option value="" selected='selected'><?php echo gettext("None"); ?></option>
								<?php
								foreach(RRD_Config::get_profile_list($conn) as $profile) {
									if (strcmp($profile, "global"))
									{
										$selected = ( $rrd_profile == $profile  ) ? " selected='selected'" : '';
										echo "<option value=\"$profile\" $selected>$profile</option>\n";
									}
								}
								?>
							</select>
						</td>
					</tr>

	  
					<tr class="advanced" style="display:none;">
						<th><label for='threshold_c'><?php echo gettext("Threshold C"); ?></label></th>
						<td class="left">
							<input type="text" name="threshold_c" id='threshold_c' class='req_field vfield' value="<?php echo $threshold_c?>"/>
							<span style="padding-left: 3px;">*</span>	
						</td>
					</tr>

					<tr class="advanced" style="display:none;">
						<th><label for='threshold_a'><?php echo gettext("Threshold A"); ?></label></th>
						<td class="left">
							<input type="text" name="threshold_a" id='threshold_a' class='req_field vfield' value="<?php echo $threshold_a?>"/>
							<span style="padding-left: 3px;">*</span>	
						</td>
					</tr>
									
					<tr style="display:none">
						<td style="text-align: left; border:none; padding-top:3px;">
							<a onclick="$('.inventory').toggle();">
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Inventory")?></a>
						</td>
					</tr>

					<tr class="inventory" style="display:none;">
						<th><label for='os'><?php echo gettext("OS"); ?></label></th>
						<td class="left">
							<select name="os" id="os" class='vfield'>
								<?php
								foreach ($array_os as $k => $v)
								{
									$pattern = "/$k/i";
									$selected = ( preg_match($pattern, $os) ) ? "selected='selected'" : '';
									echo "<option value='$k' $selected>$v</option>";
								}
								?>
							</select>
						</td>
					</tr>
					
					<tr class="inventory" style="display:none;">
						<th><label for='mac'><?php echo gettext("Mac Address"); ?></label></th>
						<td class="left"><input type="text" class='vfield' name="mac" id="mac" value="<?php echo $mac;?>"/></td>
					</tr>

					<tr class="inventory" style="display:none;">
						<th><label for='mac_vendor'><?php echo gettext("Mac Vendor"); ?></label></th>
						<td class="left"><input type="text" class='vfield' name="mac_vendor" id="mac_vendor" value="<?php echo $mac_vendor;?>"/></td>
					</tr>

					<tr style="display:none">
						<td style="text-align: left; border:none; padding-top:3px;">
							<a onclick="$('.geolocation').toggle();">
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Geolocation Info")?></a>
						</td>
					</tr>
						
					<tr class="geolocation" style="display:none;">
						<th><label for='latitude'><?php echo gettext("Latitude"); ?></label></th>
						<td class="left"><input type="text" class='vfield' id="latitude" name="latitude" value="<?php echo $latitude;?>"/></td>
					</tr>
					
					<tr class="geolocation" style="display:none;">
						<th><label for='longitude'><?php echo gettext("Longitude"); ?></label></th>
						<td class="left"><input type="text" id="longitude" name="longitude" value="<?php echo $longitude;?>"/></td>
					</tr>
					
					<tr>
						<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
							<input type="button" class="button" id='send' value="<?=_("Update")?>" onclick="check_host();"/>
							<input type="reset"  class="button" value="<?php echo gettext("Clear form"); ?>"/>
						</td>
					</tr>
				</table>
				
				<table class="noborder" width="100%" cellspacing="0" cellpadding="0" align="center" style='background-color: transparent;'>
				<tr><td class="noborder"><p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p></td>
				</tr></table>
				
			</form>
		</td>
		
		<td valign="top" class="nobborder" style="min-width: 400px;">
						
			<!-- INVENTORY -->
			<table class="noborder" width="100%" cellspacing="0" cellpadding="0">
			
				<?php
                $properties = Host::get_host_properties($conn, $ip);
                ?>
                <tr>
                    <th colspan="2" style="padding:5px">
                    <?php echo gettext("Inventory"); ?>
                    </th>
                </tr>
                <?php
                if(count($properties)==0) {
                ?>
                    <tr>
                        <td colspan="2" class="nobborder" style="padding:5px 0px 5px 0px;text-align:center">
                            <?php echo _("No Properties found");?>
                        </td>
                    </tr>
                <?php
                }
				else
				{ 
				?>
				<tr>
					<td colspan="2" class="nobborder">
						<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
							<input type="hidden" name="ip" value="<?php echo $ip;?>"/>
							<table width="450px">
								<tr>
									<th><?php echo gettext("Property"); ?></th>
									<th> <?php echo gettext("Value"); ?> </th>
									<th> <?php echo gettext("Source"); ?> </th>
									<th> <?php echo gettext("Date"); ?> </th>
									<th> <?php echo gettext("Actions"); ?></th>
								</tr>
								
								<?php foreach($properties as $prop) { ?>
								<tr>
									<td nowrap><?php echo ucwords($prop['property']) ?></td>
									<? if ($prop['extra']!="" AND $prop['extra']!="None") { ?>
									<td style="font-size:8pt;" class="extra" txt="<?=str_replace("/\"/","'",$prop['extra'])?>"><?php echo $prop['value'] ?></td>
									<? } else { ?>
									<td style="font-size:8pt;"><?php echo $prop['value'] ?></td>
									<? } ?>
									<td style="font-size:8pt;"><?php echo $prop['source'] ?></td>
									<td style="font-size:7pt;"><?php echo $prop['date'] ?></td>
									<td valign='middle'>
										<a href=""><img src="../vulnmeter/images/delete.gif" width="16" height="16" border="0" title="<?=_("Delete property")?>"/></a>
									</td>
								</tr>
								<? } ?>
							</table>
						</form>
					</td>
				</tr>
				<? } ?>
                <tr>
                    <td colspan="2" class="nobborder">&nbsp;</td>
                </tr>
			</table>
			<br/>
			
			<a onclick="$('#availability').toggle();"><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?=gettext("Availability")?></a>
			
			<!-- AVALILABILITY -->
			<table class="noborder" width="100%" cellspacing="0" cellpadding="0" id="availability" <? if (GET('edit') != _("Update Services")) echo "style='display:none'" ?>>
			
				<?php
                $services_list = Host_services::get_ip_data($conn, $ip, '1');
                ?>
                <tr>
                    <th colspan="2" style="padding:5px">
                    <?php echo gettext("Port / Service information"); ?>
                    [ <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?ip=<?php echo $ip ?>&update=services">
                        <?php echo gettext("Scan"); ?> </a> ]
                    </th>
                </tr>
                <?php
                if(count($services_list)==0) {
                ?>
                    <tr>
                        <td colspan="2" class="nobborder" style="padding:5px 0px 5px 0px;text-align:center">
                            <?php echo _("No Services found");?>
                        </td>
                    </tr>
                <?php
                }
				$servs = 0; 
				if ($services_list)
				{ 
				?>
				<tr>
					<td colspan="2" class="nobborder">
						<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>">
							<input type="hidden" name="ip" value="<?php echo $ip;?>"/>
							<table width="450px">
								<tr>
									<th width="70px"><?php echo gettext("Service"); ?></th>
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
										<img src="../vulnmeter/images/delete.gif" width="16" height="16" border="0"  title="<?=_("Delete service")?>"/></a>
									</td>
								</tr>
								<?php }
								if ($servs > 0) { ?>
								<tr>
									<td class="nobborder center" colspan="5" style='padding: 5px 0px;'>
										<input type="submit" class="lbutton" name="edit" value="<?=_("Update Services")?>"/>
										<input type="hidden" name="host" value="<?php echo $ip ?>"/>
										<input type="hidden" name="origin" value="<?php echo GET('origin')?>"/>
									</td>
								</tr>
								<? } ?>
							</table>
						</form>
					</td>
				</tr>
                <tr>
                    <td colspan="2" class="nobborder">&nbsp;</td>
                </tr>
			<? } ?>
				<tr>
					<td class="nobborder" width="100%">
						<table width="100%" cellspacing="0" cellpadding="0" class="transparent">
                            <tr>
								<td class="nobborder" width="100%">
									<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" id="serviceform">
										<input type="hidden" name="ip" value="<?=GET('ip')?>"/>
										<table class="transparent" width="100%" cellspacing="0" cellpadding="0">
											<tr><th colspan="3" style="padding:5px;"><?=_("Add new service")?></th></tr>
											<tr>
												<td class="nobborder" width="100%">
													<? /*$ports2 = Port::get_list($conn); ?>
													<select name="newport">
													<? foreach ($ports2 as $port3)?>
														<option value="<?=$port3->get_port_number()."-".$port3->get_protocol_name()?>"><?=$port3->get_port_number()."-".$port3->get_protocol_name()?></option>
													</select>
													 *
													 */?>
                                                    <table width="100%">
                                                        <tr>
                                                            <th><?php echo _("Port number");?></th>
                                                            <th><?php echo _("Protocol");?></th>
                                                            <th><?php echo _("Service");?></th>
                                                            <th><?php echo _("Nagios");?></th>
                                                            <td class="nobborder">&nbsp;</td>
                                                        </tr>
                                                        <tr>
                                                        <td class="nobborder" style="text-align:center;">
                                                            <input type="hidden" id="newport" name="newport" value="<?php //echo $assetst?>"/>
                                                            <input type="text" name="port" style="width: 80px; height:20px; color: black;" id="port" onKeyUp="fillService();"/>
                                                        </td>
                                                        <td class="nobborder" style="text-align:center;">
                                                            <select id="protocol" style="width: 80px;" onchange="fillService();">
                                                                <option value="tcp">TCP</option>
                                                                <option value="udp">UDP</option>
                                                            </select>
                                                        </td>
                                                        <td class="nobborder" style="text-align:center;">
                                                            <input type="text" name="service" style="width: 80px; height:20px; color: black;" id="service" />
                                                        </td>
                                                        <td class="nobborder left"  style="text-align:center;">
                                                            <input type="checkbox" name="newportnagios" value="1"/>
                                                        </td>
                                                        <td class="nobborder" style="text-align: right;">
                                                            <input type="button" value="<?=_("OK")?>" onclick="saveService();" class="lbutton"/>
                                                        </td>
                                                        </tr>
                                                    </table>
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
</table>


</body>
</html>
<?php $db->close($conn); ?>
