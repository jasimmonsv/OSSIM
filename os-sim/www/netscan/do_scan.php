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
ob_implicit_flush();

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
Session::logcheck("MenuPolicy", "ToolsScan");
ini_set("max_execution_time","1200");

require_once 'classes/Security.inc';


$assets          = GET('assets');
$full_scan       = GET('full_scan');
$timing_template = GET('timing_template');
$only_stop       = GET('only_stop');
$only_status     = GET('only_status');

ossim_valid($full_scan,       OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Full scan"));
ossim_valid($timing_template, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Timing_template"));
ossim_valid($only_stop,       OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Only stop"));
ossim_valid($only_status,     OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Only status"));

if (ossim_error()) 
{
    $info_error[] = ossim_get_error();
	$error        = true;
}


ossim_clean_error();

$assets_string = null;
$info_error    = null;
$error         = false;

$assets_aux    = ( !empty($assets) ) ? explode(" ", trim($assets)) : array();
$assets        = null;

$db   = new ossim_db();
$conn = $db->connect();

foreach ($assets_aux as $k => $v)
{
	$aux = null;
	
	if ( !preg_match('/,/', $v) )
		$aux = array($v);
	else
		$aux = explode(",", $v);
	
	foreach ($aux as $i => $j)
	{
		$j = trim($j);
		
		ossim_valid($j, OSS_IP_ADDRCIDR, 'illegal:' . _("Assets"));
		
		if ( ossim_error() )
		{
			$ip_cidr = Host::hostname2ip($conn, $j);
			$ip_cidr = ( empty($ip_cidr) ) ? Net::get_ips_by_name($conn,$j) : $ip_cidr."/32";
			
			if ( empty($ip_cidr) )
			{
				$info_error[] = ossim_get_error();
				$error        = true;
				break;	
			}
			else
				$aux[$i] = $ip_cidr;
		}
		else
		{
			if ( !preg_match('/\//', $j) )
				$aux[$i] = $j."/32";
		}
		
		ossim_clean_error();
	}

	$assets_string .= implode(",", $aux)." ";	
}

$db->close($conn);


if ( GET('validate_all') == true )
{
	if ( empty($info_error) )
		echo "1";
	else
		echo "<div style='text-align: left; padding: 0px 0px 10px 40px'>"._("We found the following errors").":</div><div class='error_item'>".utf8_encode(implode("</div><div class='error_item'>", $info_error))."</div>";
		
	exit();
}
else
{
	if ($error == true)
	{
		?>
		<script type="text/javascript">
			parent.$('#scan_button').attr('disabled', '');
			parent.$('#scan_button').removeClass();
			parent.$('#scan_button').addClass('button');
		</script>
		<?php
		exit();
	}
}

$assets        = rtrim($assets_string);
$scan_path_log = "/tmp/nmap_scanning_".md5(Session::get_secure_id()).".log";

require_once ('classes/Scan.inc');

// Only Stop
if ($only_stop) 
{
	$scan = new Scan($assets);
	$scan->stop_nmap();
	exit;
}

session_write_close();

if (!$only_status && !$only_stop) 
{
	$rscan = new RemoteScan($assets,($full_scan=="full") ? "root" : "ping");
	
	if (($available = $rscan->available_scan()) != "") 
		$remote_sensor = $available;
	else 
		$remote_sensor = "null";
	
	$cmd = "/usr/bin/php /usr/share/ossim/scripts/vulnmeter/remote_nmap.php '$assets' '$remote_sensor' '$timing_template' '$full_scan' '".$rscan->nmap_completed_scan."' > $scan_path_log 2>&1 &";
	
	if ( file_exists($rscan->nmap_completed_scan) ) 
		@unlink($rscan->nmap_completed_scan);
	
	system($cmd);
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
	<script type="text/javascript">
		function stop_nmap(asset) {
			
			parent.$('#stop_scan').attr('value', '<?php echo _("Stopping scan...")?>');
			
			$.ajax({
				type: "POST",
				url: "do_scan.php?only_stop=1&assets="+asset,
				success: function(msg){
					$('#res_container').remove();
					parent.$('#scan_button').attr('disabled', '');
					parent.$('#scan_button').removeClass();
					parent.$('#scan_button').addClass('button');
					parent.$('#process_div').hide();
				}
			});
		}
		
		function setIframeHeight(id)
		{
			var elem = parent.document.getElementById(id);
					
			if(elem.contentDocument)
				var height = elem.contentDocument.body.offsetHeight + 35;
			else 
				var height = elem.contentWindow.document.body.scrollHeight + 35;
				
			if (height > 200)
				parent.$('#'+id).css('height', height+'px');
		}
						
	</script>
	
	<style type='text/css'>
		body { background: transparent; }
		a {cursor: pointer;}
		
		.loading_nmap {
			width: 99%; 
			height: 99%; 
			background: transparent;
			padding-bottom: 10px;
			text-align: left;
			
		}
		
		.loading_nmap span{
			margin-right: 5px;
		}
		
		.loading_nmap img { margin-right: 5px;}
		
		.ossim_error { width: auto;}
		
		.error_item { padding-left: 30px}
				
	</style>
	
</head>

<body>

<div id='res_container'>

<?php

// Scan Status
$scanning_assets = Scan::scanning_what();
$is_remote       = false;

//print_r($scanning_assets);

if (count($scanning_assets) > 0) 
{
	foreach($scanning_assets as $sc_asset) 
	{
		$rscan = new RemoteScan($sc_asset,($full_scan=="full") ? "root" : "ping");
		
		//Full Scan
		if (($available = $rscan->available_scan()) != "") 
		{ 
			$id        = md5($sc_asset);
			$is_remote = true;		
						
			echo "<div class='loading_nmap remote' id='assets_".$id."'>
					<img class='img_loading' id='img_".$id."' src='../pixmaps/loading3.gif' align='absmiddle' alt='"._("Loading")."'/>
					<span id='text_".$id."'>"._("Scanning network"). " ($sc_asset) ". _("with a remote sensor")." [$available], "._("please wait")."...</span>
				  </div>\n";
		} 
		else 
		{
					
			echo "<div class='loading_nmap local' id='assets_".$id."'>
					<img class='img_loading' id='img_".$id."' src='../pixmaps/loading3.gif' align='absmiddle' alt='"._("Loading")."'/>
					<span id='text_".$id."'>"._("Scanning local network"). " ($sc_asset) "._("please wait")."...</span>
					<input type='button' class='lbuttond stop_scan' onclick='stop_nmap(\"".$sc_asset."\")' value='"._("Stop Scan")."'/>
				</div>\n";
		}

				
		?>
		<script type="text/javascript">setIframeHeight('process');</script>
		<?php
		
	}
	
	/*
	while( Scan::scanning_now() ) 
	{
		foreach($scanning_assets as $sc_asset) 
		{
			$tmp_file = ("/tmp/nmap_root.log") ? "/tmp/nmap_root.log" : "/tmp/nmap_ping.log";
			
			if ($remote) 
				$tmp_file = $scan_path_log;
       		
			if (file_exists($tmp_file)) 
			{
				$lines = file($tmp_file);
				$perc  = 0;
				$ip    = "";
				
				foreach ($lines as $line) 
				{
					if (preg_match("/^Scanning\s+(\d+\.\d+\.\d+\.\d+)/",$line,$found)) {
						$ip = $found[1];
					}
					
					if (preg_match("/About\s+(\d+\.\d+)\%/",$line,$found)) {
						$perc = $found[1];
					}
				}

				if ($perc > 0) 
				{
					$id = md5($sc_asset);
					?>
					<script type="text/javascript">
						setIframeHeight('process');
						$('#asset_<?php echo $id?>').html("Scan<?php if ($ip != "") echo " [$ip]\n" ?>: <?php echo $found[1] ?>%\n");
					</script>
					<?php
				}
			}
			
		}
        sleep(3);
	} */

	while( Scan::scanning_now() ) 
	{
  		sleep(3);
	}
	
	$has_results = false;
	
	if ( file_exists($scan_path_log) ) 
	{
		$has_results = true;
		$output = file($scan_path_log);
		
		foreach ($output as $line) 
		{
			if (!preg_match("/appears to be up/",$line)) {
				echo $line."\n";
			}
		}
		
		@unlink($scan_path_log);
	}
	
	echo "<br/>";
	
	if ( $has_results ) 
	{
		echo "<div style='margin:auto; text-align: center; padding: 5px 0px'>
				<span style='font-weight: bold;'>"._("Scan completed")."</span><a onclick=\"parent.document.location.href='index.php'\"> [ "._("Click here to view the results")."]</a>
			  </div>\n";
	}
	else
	{
		"<div style='color:red; margin:auto; text-align: center;'>"._("Scan aborted")."</div>\n";
	}
	
	echo "<br/><br/>";

	?>
	
	<script type="text/javascript">
		setIframeHeight('process');
		$('.loading_nmap').remove();
	</script>
	
	<?php
} 
else 
{
	echo "<div class='ossim_info' style='width: auto'>";
		echo "<div>"._("Format not allowed")."</div>"; 
		echo "<div style='padding-left: 10px;'>"._("Correct format").": CIDR[,CIDR,....] CIDR <br/>CIDR: xxx.xxx.xxx.xxx/xx</div>";
	echo "</div>";
}
?>

	<script type="text/javascript">
		parent.$('#scan_button').attr('disabled', '');
		parent.$('#scan_button').removeClass();
		parent.$('#scan_button').addClass('button');
	</script>

</div>

</body>
</html>


