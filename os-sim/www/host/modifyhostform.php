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

$db    = new ossim_db();
$conn  = $db->connect();

$ip    = GET('ip');
$style = "style='display: none;'";

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));

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

$conf     = $GLOBALS["CONF"];					
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
            $protocol = $protocol_ids[strtolower(trim($protocol))];
            			
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
		$error_message = "<div style='padding-left: 10px'>".ossim_get_error_clean()."</div>";
	else
	{
		$date = strftime("%Y-%m-%d %H:%M:%S");
		
        
        if( $nservice !='')
            $serviceName = $nservice;
        else if ($ports[$port_number." - ".$protocol_name]!="") 
            $serviceName = $ports[$port_number." - ".$protocol_name];
        else
            $serviceName = 'unknown';
        
        // Insert new port
        $chport = array();
        $chport = Port::get_list($conn, "where port_number = $port_number and protocol_name = '$protocol_name'");
        if(count($chport)==0) {
            Port::insert($conn, $port_number, $protocol_name, $serviceName, "");
        }
		
		$protocol = $protocol_ids[$protocol_name];
		Host_services::insert($conn, $ip, $port_number, $date, $_SERVER["SERVER_ADDR"], $protocol, $serviceName, "unknown", "unknown", 1, $newport_nagios); // origin = 0 (pads), origin = 1 (nmap)
		
	}	

}

if ( $error_message != null )
{	
	$style 		   = "style='display: block;'";
	$error_message = "<div style='padding-left: 15px;'>$error_message</div>";
	ossim_clean_error();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<!-- Dynatree libraries: -->
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<link type="text/css" rel="stylesheet" href="../style/tree.css" />

	<script type="text/javascript">
	
		messages[6]  = '<div class="reload"><img src="../pixmaps/theme/ltWait.gif" border="0" align="absmiddle"/> <?php echo _("Re-loading data...") ?></div>';
		
		function saveService(){
			if($('#port').val()<0 || $('#port').val()>65535)
			{
				alert('<?php echo _("Error: Invalid port.  Insert a value between 0 and 65535")?>');
				return false;
			}
			
			if( $('#service').val() == "" ) 
				$('#service').val("Unknown");
			
			var newService = $('#port').val()+' - '+$('#protocol').val();
			
			$('#newport').val(newService);
			$('#serviceform').submit();
		}
    
		function fillService(){
			$("#service").attr('disabled','');
			var ports = new Array(); 
			
			<?php
			foreach($ports as $k => $v) {
				echo "ports['$k'] = '$v';\n";
			}
			?>
			
			if(typeof ports[$('#port').val()+' - '+$('#protocol').val()] !== 'undefined')
			{
				$('#service').val(ports[$('#port').val()+' - '+$('#protocol').val()]);
				$("#service").attr('disabled','disabled');
			}
			else
				$('#service').val("");
			
		}
			
				
		
		var layer_1     = null;
		var layer_2     = null;
		var nodetree_1  = null;
		var nodetree_2  = null;
		var i           =  1;
		var j           =  1;
	
		function load_tree_1(container, ip){
			
			if (nodetree_1!=null) {
				nodetree_1.removeChildren();
				$(layer_1).remove();
			}
			
			layer_1 = '#srctree1_'+i;
			$('#'+container).append('<div id="srctree1_'+i+'" style="width:100%"></div>');
			
			$(layer_1).html(messages[6]);
						
			$(layer_1).dynatree({
				initAjax: {url: "draw_properties_tree.php", data: {ip: ip, tree: container} },
				minExpandLevel: 2,
				clickFolderMode: 1,
				checkbox: true,
				cookieId: "dynatree_1",
				onClick: function(node, event) 
				{
					
					if ( node.getEventTargetType(event) == 'checkbox' )
						return;
						
					if( node.data.key.match("property_") != null )
					{
						var key      = node.data.key.split("_");
						var prop_ref = $('#inv_prop_ref').val(key[1]);
						active_form_properties(key[1]);
						reset_inputs("add_prop");
					}
					else if( node.data.key.match("nagios_") != null )
					{
						var key = node.data.key.split("###");					
						
						if ( key[2] == "nagios_ok" )
							key = key[0]+"###"+key[1]+"###nagios_ko";
						else
							key = key[0]+"###"+key[1]+"###nagios_ok";
						
						update_services(key);
					}
					else if	( node.data.key.match("item_prop_") != null &&  node.data.key.match("item_prop_4_" ) == null )
					{
						
						var key         = node.data.key.split("###");						
						var source_node = $(layer_1).dynatree("getTree").getNodeByKey("source_"+key[1]);
						var source_id   = source_node.data.source_id;
												
						if (source_id == 1 || ( source_id != 1 && node.data.anom == 0) )
						{
							var prop_ref = key[2];
													
							$('#inv_prop_value').val(node.data.value);
							
							var extra_node = $(layer_1).dynatree("getTree").getNodeByKey("extra_"+key[1]);
							var version = ( extra_node.data.extra != 'None' ) ? extra_node.data.extra : "";
							
							$('#inv_prop_version').val(version);
							
							if ( source_id != 1 && node.data.anom == 0 )
								var source_id = 1;
							
							$('#inv_prop_source_id').val(source_id);
							$('#inv_prop_anom').val(node.data.anom);
							$('#inv_action').val("update_prop");
							$('#inv_prop_ref').val(prop_ref);
							$('#inv_prop_id').val(key[1]);
							
							active_form_properties(prop_ref);
						}
						else
						{
							if (source_id != 1 && node.data.anom == 1)
							{
								reset_forms('');
								$('#id_change').val(key[1]);
								$('#cont_changes').show();
							}
						}
					}
					
				},
								
				onSelect: function(select, node) {
					
					// Get a list of all selected nodes, and convert to a key array:
					var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
						return node.data.key;
					});
					
					if ( selKeys.length > 0 )
						$("#cont_delete_selected").show();
					else
						$("#cont_delete_selected").hide();
				}	
			});
			
			
			nodetree_1 = $(layer_1).dynatree("getRoot");
						
			i=i+1;
		}
		
		function load_tree_2(container, ip){
			
			if (nodetree_2!=null) {
				nodetree_2.removeChildren();
				$(layer_2).remove();
			}
			
			layer_2 = '#srctree2_'+i;
			$('#'+container).append('<div id="srctree2_'+i+'" style="width:100%"></div>');
			$(layer_2).html(messages[6]);
									
			$(layer_2).dynatree({
				initAjax: {url: "draw_properties_tree.php", data: {ip: ip, tree: container} },
				minExpandLevel: 2,
				clickFolderMode: 1,
				checkbox: true,
				onSelect: function(select, node) {
					
					var key = node.data.key.split("###");					
					if ( key[2] == "nagios_ok" )
						node.data.key = key[0]+"###"+key[1]+"###nagios_ko";
					else
						node.data.key = key[0]+"###"+key[1]+"###nagios_ok";
				},	
				cookieId: "dynatree_2"
			});
						
			nodetree_2 = $(layer_2).dynatree("getRoot");
						
			j=j+1;
		}
		
		function delete_properties(items)
		{
			if (confirm("<?php echo _("Are you sure to delete this properties")?>?"))
			{
				
				$('#info_error').html('');
				var ip = $('#ip').val();
				
				$.ajax({
					type: "POST",
					url: "properties_actions.php",
					data: "action=delete&ip="+ip+"&data="+items,
					success: function(msg){
						
						load_tree_1('tree_container_1', ip);
						
						if ( items.match("item_prop_4") != null )
							load_tree_2('tree_container_2', ip);
						
						reset_forms('');					
						
					}
				});
			}
		}
		
		function update_services(items)
		{
			var ip = $('#ip').val();
			$('#info_error').html('');
			
			$.ajax({
				type: "POST",
				url: "properties_actions.php",
				data: "action=nagios&ip="+ip+"&data="+items,
				success: function(msg){
					load_tree_2('tree_container_2', ip);
					load_tree_1('tree_container_1', ip);
					reset_forms('');			
					
					if ( msg != '' )
					{
						$('#info_error').html("<div style='padding-left: 10px'>"+msg+"</div>");
						$('#info_error').fadeIn(2000);
					}
				}
			});
			
		}
		
		function update_property()
		{
			var ip = $('#ip').val();
			var action = ($('#inv_action').val() == 'add_prop') ? "add" : "update";
			
			$('#info_error').html('');
			
			$.ajax({
				type: "POST",
				url: "properties_actions.php",
				data: "action="+action+"&"+$('#inventoryform').serialize(),
				success: function(msg){
					load_tree_1('tree_container_1', ip);
					reset_forms('');
										
					if ( msg != '' )
					{
						$('#info_error').html("<div style='padding-left: 10px'>"+msg+"</div>");
						$('#info_error').fadeIn(2000);
					}
				}
			});
			
		}
		
		function make_changes(action)
		{
			var ip = $('#ip').val();
						
			$('#info_error').html('');
			
			$.ajax({
				type: "POST",
				url: "properties_actions.php",
				data: "action="+action+"&ip="+ip+"&"+$('#changeform').serialize(),
				success: function(msg){
					load_tree_1('tree_container_1', ip);
					reset_forms('');
										
					if ( msg != '' )
					{
						$('#info_error').html("<div style='padding-left: 10px'>"+msg+"</div>");
						$('#info_error').fadeIn(2000);
					}
				}
			});
		}
		
				
		function active_form_properties(prop_ref)
		{
			if ( prop_ref != 0 )
			{
				//Services
				if ( prop_ref == 4 )
				{	
					$('#properties_form_1').hide();
					$('#properties_form_2').show();
				}
				else 
				{
					$('#properties_form_1').show();
					$('#properties_form_2').hide();
					
					//No extra data
					if ( prop_ref >= 6 && prop_ref <= 8 )
						$('#cont_prop_version').hide();
					else
						$('#cont_prop_version').show();
				}
			}
			else
				reset_forms('');
		}
		
		
		function reset_forms(action)
		{
			$("#cont_delete_selected").hide();
			$('#inv_prop_ref').val('0');
			$('#properties_form_1').hide();
			$('#properties_form_2').hide();
			$("#cont_changes").hide();
			reset_inputs(action);
		}
		
		function reset_inputs(action)
		{
			$('#inv_action').val(action);
			$('#inv_prop_value').val('');
			$('#inv_prop_version').val('');
			$('#inv_prop_id').val('');
			$('#inv_prop_source_id').val('');
			$('#inv_prop_anom').val('');
		}

		
		$(document).ready(function(){

			load_tree_1('tree_container_1', '<?php echo $ip?>');
			load_tree_2('tree_container_2', '<?php echo $ip?>');
			
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
			
			$('#availability').bind('click', function() {
			    if ( $('#availability').hasClass("hide") )
				{
					$('#availability').removeClass();
					$('#availability').addClass("show");
					$('#cont_sam').show();
				}
				else
				{
					$('#cont_sam').hide();
					$('#availability').removeClass();
					$('#availability').addClass("hide");
				}
			});
			
			$("#delete_selected").bind('click', function() {
				var node = $(layer_1).dynatree("getRoot");
								
				var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
					return node.data.key;
				});
					
				selKeys = selKeys.join(",");
				delete_properties(selKeys);
			});
			
			$("#update_selected").bind('click', function() {
				var node = $(layer_2).dynatree("getRoot");
				
				var nagios_keys = new Array();
				var i = 0;
				
				$(layer_2).dynatree("getRoot").visit(function(node){
					key = node.data.key;
					if ( key.match("nagios_") != null )
					{
						nagios_keys[i] = key;
						i++;
					}
				});
																	
				nagios_keys = nagios_keys.join(",");
				update_services(nagios_keys);		
				
			});

			$('#inv_prop_ref').bind('change', function() {
				var prop_ref = $('#inv_prop_ref').val();
								
				active_form_properties(prop_ref);
				reset_inputs('add_prop');
						
			});
			
			$('.scan').bind('click', function(event) {
				event.preventDefault();
				var img = "<img style='padding-left: 3px' align='absmiddle' src='../pixmaps/loading3.gif'/>";
				$(".scan").parent().append(img);
				document.location.href = $(".scan").attr("href");
			});
			
			$('#update_button').bind('click', function() {
				 update_property();
			});
			
			$('#accept_change').bind('click', function() {
				make_changes('accept_change');
				$('#cont_changes').hide();
				
			});
			
			$('#discard_change').bind('click', function() {
				make_changes('discard_change');
				$('#cont_changes').hide();
			});
			
		});
	
	
	</script>
	
	<style type='text/css'>
		#host_container { padding-bottom: 20px;}
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		a {cursor:pointer;}
		.bold {font-weight: bold;}
		table { background: transparent;}
		
		#form_right { min-width: 400px;}
		div.bold {line-height: 18px;}
		.red {color: #E54D4D; font-weight: bold; text-align: center;}
		#properties_form_1, #properties_form_2 {display:none;}
		#cont_new_property {padding: 10px 0px;}
		.mr5 {margin-right: 5px;}
		.error_message {clear: both; padding: 10px;}
		#table_properties {width: 100%;}
		#table_properties th {width: 80px;}
		#inv_prop_value {width: 98%;}
		#cont_prop_version {display: none;}
		#inv_prop_version{width: 98%; height: 30px;}
		#inv_prop_ref { width: 200px;}
		.cont_inv_action {padding: 3px 0px;}
		#delete_properties, #delete_services { display : none;}
		#cont_delete_selected {padding: 8px 0px 5px 0px; text-align:center; display: none;}
		#cont_update_selected {padding: 8px 0px 5px 0px; text-align:center;}
		.sep15 {height: 15px;}
		.sep10 {padding-bottom: 10px;}
		
		#cont_services th {padding: 2px 0px;}
		.legend { font-style: italic; border-bottom: none; padding: 5px 0px;}
		
		#table_inventory, #table_services { border: 1px dotted gray; 
						   border-radius: 0px;
						   -moz-border-radius: 0px;
						   -webkit-border-radius: 0px;
						   padding: 0px 10px;
		}
		
		#cont_sam {display: none;}
		
		div.ui-dynatree-container { border: none !important;}
		
		#cont_changes { text-align: center; padding: 10px 0px; display:none;}
					
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

?>
	<div id='info_error' class='ossim_error' <?php echo $style ?>><?php echo $error_message;?></div>
	
	<div id='host_container'>
	
	<table align="center" class='noborder'>
		<tr>
			<td class="nobborder" valign="top">
				<table id='table_container'>
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
							<div class='bold'><?php echo $ip; ?></div>
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
								foreach($sensor_list as $sensor)
								{
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
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo gettext("Advanced")?></a>
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
							<input type="button" class="button" id='send' value="<?=_("Update")?>" onclick="submit_form();"/>
							<input type="reset"  class="button" value="<?php echo gettext("Clear form"); ?>"/>
						</td>
					</tr>
					
					<tr>
						<td colspan="2" align="center" class='legend'>
							<p align="center"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>
						</td>
					</tr>
					
				</table>
				
				
				
			</form>
		</td>
		
		<td valign="top" class="nobborder" id='form_right'>
						
			<!-- INVENTORY -->
			<table class='noborder' width="100%" cellspacing="0" cellpadding="0">
			
                <tr><th style="padding:5px"><?php echo _("Inventory")." [ <a class='scan' href='".$_SERVER["SCRIPT_NAME"]."?ip=$ip&update=services'>"._("Scan now")."</a> ]"; ?></th></tr>
				
				<tr>
					<td class='noborder'>
						<table id='table_inventory' class='noborder' width='100%'>
				
							<tr><td class="nobborder"><div id='tree_container_1'></div></td></tr>
							
							<tr><td class="nobborder sep15">&nbsp;</td></tr>
												
							<tr>
								<td class="nobborder center" id='cont_changes'>
									<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" id="changeform">
										<input type='button' class='lbutton' id='accept_change'  value='<?php echo _("Accept changes")?>'/>
										<input type='button' class='lbuttond' id='discard_change' value='<?php echo _("Discard changes")?>'/>
										<input type="hidden" id='id_change' name='id_change'/>
									</form>
								</td>
							</tr>
							
							<tr>
								<td class="nobborder" id='cont_delete_selected'>
									<input type='button' class='lbutton' id='delete_selected' value='<?php echo _("Delete Selected")?>'/>
								</td>
							</tr>
							
							<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" id="inventoryform">
							<input type="hidden" name="ip" value="<?php echo $ip;?>"/>
							
							<tr>
								<td id='cont_new_property' class='nobborder left'>
									<span class='mr5'><?php echo _("Add new property")?>:</span>
									<select name="inv_prop_ref" id="inv_prop_ref">
										<option value='0'>-- <?php echo _("Select a property type")?> --</option>
										<?php
											$properties_types = Host::get_properties_types($conn);
											
											foreach ($properties_types as $k => $v)
												echo "<option value='".$v["id"]."'>".gettext($v["description"])."</option>";
										?>
									</select>
								</td>
							</tr>
								
							<tr id='properties_form_1'>
								<td class='nobborder sep10'>
									<table id='table_properties' class='transparent'>
										<tr>
											<th><span><?php echo _("Value");?></span></th>
											<td class='noborder left'><input type='text' id='inv_prop_value' name='inv_prop_value'/></td>
										</tr>
										<tr id='cont_prop_version'>
											<th><span><?php echo _("Version");?></span></th>
											<td class='noborder left'><textarea id='inv_prop_version' name='inv_prop_version'></textarea></td>
										</tr>
										<tr>
											<td class='noborder right cont_inv_action' colspan='2'>
												<input type="button" id='update_button' class="lbutton" value="<?php echo _("Update")?>"/>
												<input type="hidden" id='inv_action' name='inv_action'/>
												<input type="hidden" id='inv_prop_id' name='inv_prop_id'/>
												<input type="hidden" id='inv_prop_source_id' name='inv_prop_source_id'/>
												<input type="hidden" id='inv_prop_anom' name='inv_prop_anom'/>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							
							</form>
							
							<tr id='properties_form_2'>
								<td class='noborder sep10'>
									<form method="GET" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" id="serviceform">
										<input type="hidden" name="ip" value="<?=GET('ip')?>"/>
										<table class="transparent" width="100%" cellspacing="0" cellpadding="0">
											<tr>
												<td class="nobborder" width="100%">
													<? /*$ports2 = Port::get_list($conn); ?>
													<select name="newport">
													<? foreach ($ports2 as $port3)?>
														<option value="<?=$port3->get_port_number()."-".$port3->get_protocol_name()?>"><?=$port3->get_port_number()."-".$port3->get_protocol_name()?></option>
													</select>
													 *
													 */?>
													<table width="100%" class='noborder' id='cont_services'>
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
																<input type="button" value="<?=_("Add")?>" onclick="saveService();" class="lbutton"/>
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
				
				<tr>
					<td class="nobborder left" style='height: 40px;'>
						<a id='availability' class='hide'><img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo _("Availability")?></a>
					</td>
				</tr>
				
				<tr>
					<td class='noborder'>
						<table width='100%' class='noborder' id='cont_sam'>
						
							<tr>
								<th style="padding:5px">
									<?php echo _("Services Availability Monitoring")." [ <a class='scan' href='".$_SERVER["SCRIPT_NAME"]."?ip=$ip&update=services'>"._("Scan now")."</a> ]"; ?>
								</th>
							</tr>
						
							<tr>
								<td class='noborder'>
									<table id='table_services' class='noborder' width='100%'>
										<tr><td class="nobborder"><div id='tree_container_2'></div></td></tr>
							
										<tr>
											<td class="nobborder" id='cont_update_selected'>
												<input type='button' class='lbutton' id='update_selected' value='<?php echo _("Update Services")?>'/>
											</td>
										</tr>
									</table>
								</td>
							</tr>		
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</div>

</body>
</html>
<?php $db->close($conn); ?>
