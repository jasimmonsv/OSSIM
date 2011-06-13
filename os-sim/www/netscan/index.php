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
// menu authentication
require_once ("classes/Session.inc");
require_once ("ossim_db.inc");
require_once ("classes/Net.inc");
require_once ("classes/Scan.inc");
require_once ("classes/Sensor.inc");

Session::logcheck("MenuPolicy", "ToolsScan");

$db   = new ossim_db();
$conn = $db->connect();

$net_group_list = Net_group::get_list($conn);

$net_list = Net::get_list($conn);
$assets   = array();

foreach ($net_list as $_net) {
	$assets_aux[] = '{ txt:"NET:'.$_net->get_name().' ['.$_net->get_ips().']", id: "'.$_net->get_ips().'" }';
}

$host_list = Host::get_list($conn);
foreach ($host_list as $_host) {
	$assets_aux[] = '{ txt:"HOST:'.$_host->get_ip().' ['.$_host->get_hostname().']", id: "'.$_host->get_ip().'/32" }';
}

$host_group_list = 	Host_group::get_list($conn);
foreach ($host_group_list as $_host_group)
{
	$hosts  = $_host_group->get_hosts($conn, $_host_group->get_name());
	$ids    = null;
	foreach ($hosts as $k => $v)
		$ids .= $v->get_host_ip()."/32 "; 
	
	$assets_aux[] = '{ txt:"HOSTGROUP:'.$_host_group->get_name().'", id: "'.rtrim($ids).'" }';
}


$sensor_list = Sensor::get_list($conn, "ORDER BY name");
foreach ($sensor_list as $_sensor) {
	$assets_aux[] = '{ txt:"SENSOR:'.$_sensor->get_name().' ['.$_sensor->get_ip().']", id: "'.$_sensor->get_ip().'/32" }';
}


$assets = implode(",\n", $assets_aux);

$db->close($conn);

require_once ("ossim_conf.inc");
$conf      = $GLOBALS["CONF"];
$nmap_path = $conf->get_conf("nmap_path");

$nmap_exists  = ( file_exists($nmap_path) ) ? 1 : 0;

$nmap_running = Scan::scanning_now();


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type='text/javascript'>
				
		function start_scan()
		{
			var assets = $('#assets').val();
			
			if( assets.trim() == '' ) 
			{
				alert('<?php echo _("You must choose at least one asset") ?>');
				return false;
			}
			else
			{
				$("#process").contents().find("#res_container").remove();
				$('#process').css('height', '200px');
				
				var data = $('#assets_form').serialize();
				
				$.ajax({
					type: "GET",
					url: 'do_scan.php',
					data: data + "&validate_all=true",
					success: function(html){

						var status = parseInt(html);
											
						if ( status == 1 )
						{
							$("#error_messages").html('');
							$("#error_messages").css('display', 'none');
							
							$('#process_div').show()
							$('#scan_button').removeClass();
							$('#scan_button').attr('disabled', 'disabled');
							$('#scan_button').addClass('buttonoff');
							$('#assets_form').submit();
						}
						else
						{
							$("#error_messages").html(html);
							$("#error_messages").css('display', 'block');
						}
																
					}
				});
						
			}
		}
		
		function remote_scan()
		{
			//$('#process_div').show();
			document.location.href='remote_scans.php';
		}
		
		
		var layer = null;
		var nodetree = null;
		var suf = "c";
		var i=1;
		
		function load_tree(filter) {
			if (nodetree!=null) {
				nodetree.removeChildren();
				$(layer).remove();
			}
			layer = '#srctree'+i;
			$('#tree').append('<div id="srctree'+i+'" class="tree_container"></div>');
			$(layer).dynatree({
				initAjax: { url: "draw_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					var asset = dtnode.data.key.split(":");
					
					if ( asset[0].match(/^(HOST|NET|SENSOR|HOSTGROUP)/) ) 
					{
						var assets = $('#assets').val();
						if ( assets != '' )
						{
							var value = $('#assets').val() + " " + dtnode.data.asset_data;
							$('#assets').val(value);
						}
						else
						
						$('#assets').val(dtnode.data.asset_data);
					}
						
				},
				
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "draw_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1
		}
		
		
						
		$(document).ready(function(){
			
			$(".cidr_info").simpletip({
				position: 'top',
				offset: [120, 30],
				content: '',
				baseClass: 'stooltip',
				onBeforeShow: function() {
					var txt = this.getParent().attr('txt');
					this.update(txt);
				}
			});
			
			var assets = [ <?php echo $assets; ?> ];
			
			$("#assets").autocomplete(assets, {
				minChars: 0,
				width: 400,
				matchContains: "word",
				multiple: true,
				multipleSeparator: " ",
				autoFill: false,
				formatItem: function(row, i, max) {
					return row.txt;
				},
				formatResult: function(data, value) {
					return data.id;
				}
			});
			
			load_tree("");
			
							
			$('#clear_all').bind('click', function()  { $('#assets').val('') });	
								
		});
	</script>
  
	<style type='text/css'>
		th { padding: 3px 0px;}
		
		.container {
			margin:auto; 
			padding: 20px 30px;
		}
		
		#process_div {
			width: 550px;
			background: transparent;
			margin: 20px auto;
		}
		
		#process { 
			height: 100%;
			width: 100%;
			background: transparent;
		}
		
		#assets { 
			width: 400px; 
			height: 40px;
			text-align: left;
		}
		
		small { color: grey; }
		
		.div_small { padding: 5px 0px 0px 1px;}
		
		.cidr_info {
			cursor:pointer; 
			text-decoration: none;
			outline: none;
		}
		
		.cidr_info div {
			text-decoration: none;
			outline: none;
		}
		
		#tree {margin: 15px auto 5px auto; text-align: left;}
		
		#error_messages {
			display: none;
			width: 700px;
		}
		
		.ossim_error { width: auto;}
		
		.error_item { padding-left: 50px;}
		
	</style>
  
</head>

<body>

<?php
$typeMenu='horizontal';
include ("../hmenu.php");

if (!$nmap_exists) 
{
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("NMAP_PATH");
}
?>
<!-- Asset form -->

<div id='error_messages' class='ossim_error'></div>

<form name="assets_form" id="assets_form" method="GET" action="do_scan.php" target="process">
	<table align="center" style='width: 550px;'>
		<tr>
			<th colspan="2"><?php echo gettext("Please, select the assets you want to scan:") ?></th>
		</tr>
		<tr>
			<td colspan="2" class='container'>
				<table width='100%' class='transparent'>
					<tr>
						<td class='noborder'>
							<?php
							$info_cidr = "<div style='font-weight:normal; width: 170px;'>
									<div><span class='bold'>Format:</span> CIDR[,CIDR,...] CIDR</div>
									<div><span class='bold'>CIDR:</span> xxx.xxx.xxx.xxx/xx</div>
								</div>";
							?>
							<textarea name="assets" id="assets"></textarea>
							<div style='width: 20px; float:right;'>
								<a class="cidr_info" txt="<?php echo $info_cidr?>">
									<img src="../pixmaps/help.png" width="16" border="0" align='absmiddle'/>
								</a>
							</div>
						</td>
						
						<td class='noborder left' valign='bottom' style='text-align: right;'>
							<input type='button' id='clear_all' class='lbutton' value='[X]'/>
						</td>
					</tr>
					<tr><td class='noborder' colspan='2'><div id='tree'></div></td></tr>
				</table>
			</td>
		</tr>
		
		<tr>
			<th colspan="2"><?php echo _("Assets discover options")?></th>
		</tr>

		<!-- full scan -->
		<tr>
			<td colspan="2" class='container'>
				<?php echo _("Scan type")?>:&nbsp;
				<select name="full_scan">
					<option value=""><?php echo _("Normal")?></option>
					<option value="fast"><?php echo _("Fast Scan")?></option>
					<option value="full"><?php echo _("Full Scan")?></option>
				</select>
				<div class='div_small'>
					<small>
						<strong><?php echo _("Full mode")?></strong> <?php echo _("will be much slower but will include OS, services, service versions and MAC address into the inventory")?><br/>
						<strong><?php echo _("Fast mode")?></strong> <?php echo _("will scan fewer ports than the default scan")?>
					</small>
				</div>
			</td>
		</tr>
		<!-- end full scan -->

		<!-- timing template (T0-5) -->
		<tr>
			<td colspan="2" class='container'>
				<?php echo _("Timing template")?>:&nbsp;
				<select name="timing_template">
					<option value="-T0">(T0) <?php echo _("paranoid")?></option>
					<option value="-T1">(T1) <?php echo _("sneaky")?></option>
					<option value="-T2">(T2) <?php echo _("polite")?></option>
					<option selected='selected' value="-T3">(T3) <?php echo _("normal")?></option>
					<option value="-T4">(T4) <?php echo _("aggressive")?></option>
					<option value="-T5">(T5) <?php echo _("insane")?></option>
				</select>
				
				<div class='div_small'>
					<small>
						<strong><?php echo _("Paranoid")?></strong> <?php echo _("and")?> <strong><?php echo _("Sneaky")?></strong> <?php echo _("modes are for IDS evasion")?><br/>
						<strong><?php echo _("Polite")?></strong> <?php echo _("mode slows down the scan to use less bandwidth and target machine resources")?><br/>
						<strong><?php echo _("Aggressive")?></strong> <?php echo _("and")?> <strong><?php echo _("Insane")?></strong> <?php echo _("modes speed up the scan (fast and reliable networks)")?><br/>
					</small>
				</div>
			</td>
		</tr>
		<!-- end timing template -->

		<!-- do scan -->
		<tr>
			<td colspan="2" class="nobborder center" style='padding: 10px;'>
				<?php
					if ( !$nmap_exists || $nmap_running )
					{
						$disabled = " disabled='disabled'";
						$input_class = "buttonoff";
					}
					else
					{
						$disabled    = "";
						$input_class = "button";
					}
				?>
			
				<input type="button" id="scan_button" class="<?php echo $input_class?>" onclick="start_scan();" value="<?php echo _("Start Scan") ?>"<?php echo $disabled?>/>
			
				<?php 
				if (Session::am_i_admin()) 
				{ 
					?>&nbsp;&nbsp;
					<input type="button" class="button" value="<?php echo _("Manage Remote Scans") ?>" onclick="remote_scan()"/>
					<?php 
				} 
				?>
			
			</td>
		</tr>
		
	</table>
	
	<!-- end do scan -->
	
	<div id='process_div' style="display:<?php echo ($nmap_running) ? "block" : "none"?>">  
		<table width='100%'>
			<tr>
				<td class='nobborder'>
					<iframe name="process" id="process" src="<?php if ($nmap_running) echo "do_scan.php?only_status=1" ?>" frameborder="0" scrolling="no"></iframe>
				</td>
			</tr>
		</table>
	</div>
</form>
<!-- end of Asset form -->


<?php
require_once ('classes/Scan.inc');

$scan = new Scan("");

if ( GET('clearscan') ) 
    Scan::del_scan($scan->nmap_completed_scan);

$lastscan = $scan->get_scan();

if (is_array($lastscan) && count($lastscan)>0) 
{
    require_once ('scan_util.php');
	$_SESSION["_scan"] = $lastscan;
    scan2html($lastscan);
} 
else 
{
    echo "<!-- <p align=\"center\">";
    echo _("NOTE: This tool is a nmap frontend. In order to use all nmap functionality, you need root privileges.");
    echo "<br/>";
    echo _("For this purpose you can use suphp, or change group to the web-user and set suid to nmap binary (<strong>chgrp www-data /usr/bin/nmap ; chmod 4750 /usr/bin/nmap</strong>).");
    echo "</p> -->";
}
?>


	</body>
</html>
