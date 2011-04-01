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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "ToolsScan");
ini_set("max_execution_time","1200");
ob_implicit_flush();
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
	function stop_nmap(asset) {
		$.ajax({
			type: "POST",
			url: "do_scan.php?only_stop=1&net="+asset,
			success: function(msg){
				$('#loading').html("");
			}
		});
	}
  </script>
</head>
<body style="background-color:#FAFAFA">

<?php
require_once 'classes/Security.inc';
$net = GET('net');
$full_scan = GET('full_scan');
$timing_template = GET('timing_template');
$only_stop = GET('only_stop');
$only_status = GET('only_status');
ossim_valid($net, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Net"));
ossim_valid($full_scan, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("full scan"));
ossim_valid($timing_template, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("timing_template"));
ossim_valid($only_stop, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("only stop"));
ossim_valid($only_status, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("only status"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('classes/Scan.inc');

// Only Stop
if ($only_stop) {
	$scan = new Scan($net);
	$scan->stop_nmap();
	exit;
}

// Launch Scan
session_write_close();
if (!$only_status && !$only_stop) {
	$rscan = new RemoteScan($net,($full_scan=="full") ? "root" : "ping");
	if (($available = $rscan->available_scan()) != "") {
		$remote_sensor = $available;
	} else {
		$remote_sensor = "null";
	}
	$cmd = "/usr/bin/php /usr/share/ossim/scripts/vulnmeter/remote_nmap.php $net $remote_sensor '$timing_template' '$full_scan' > /tmp/nmap_scanning.log 2>&1 &";
	if (file_exists("/tmp/nmap_completed_scan.log")) unlink("/tmp/nmap_completed_scan.log");
	system($cmd);
}

// Scan Status
$scanning_nets = Scan::scanning_what();
if (count($scanning_nets) > 0) {
	// print html
	foreach($scanning_nets as $net) {
		$rscan = new RemoteScan($net,($full_scan=="full") ? "root" : "ping");
		if ($rscan->available_scan()) { // $full_scan!="full" &&
			echo _("Scanning network") . " ($net), " . _(" with a remote sensor, please wait") . "...<div id='loading'><img src='../pixmaps/loading.gif' align='absmiddle' width='16'></div> <div id='stop_div'></div><br>\n";
		} else {
			echo _("Scanning network") . " ($net), " . _(" locally, please wait") . "...<div id='loading'><img src='../pixmaps/loading.gif' align='absmiddle' width='16'></div> <div id='stop_div'><input type='button' class='button' onclick='stop_nmap(\"$net\")' value='"._("Stop Scan")."'></div><br>\n";
		}
	}
	?><script type="text/javascript">parent.doIframe();</script><?php
	// change status
	while(Scan::scanning_now()) {
		foreach($scanning_nets as $net) {
			$tmp_file = ("/tmp/nmap_root.log") ? "/tmp/nmap_root.log" : "/tmp/nmap_ping.log";
       		if (file_exists($tmp_file)) {
				$lines = file($tmp_file);
				$perc = 0;
				$ip = "";
				foreach ($lines as $line) {
					if (preg_match("/^Scanning\s+(\d+\.\d+\.\d+\.\d+)/",$line,$found)) {
						$ip = $found[1];
					}
					if (preg_match("/About\s+(\d+\.\d+)\%/",$line,$found)) {
						$perc = $found[1];
					}
				}
				if ($perc > 0) {
					?><script type="text/javascript">document.getElementById('loading').innerHTML = "Scan<?php if ($ip != "") echo " [$ip]" ?>: <?php echo $found[1] ?>%";</script><?php
				}
			}
        }
        sleep(3);
	}
	$has_results = false;
	if (file_exists("/tmp/nmap_scanning.log")) {
		$has_results = true;
		$output = file("/tmp/nmap_scanning.log");
		foreach ($output as $line) {
			if (!preg_match("/appears to be up/",$line)) {
				echo $line;
			}
		}
		unlink("/tmp/nmap_scanning.log");
	}
	echo "<br>";
	echo ($has_results) ? _("Scan completed") : _("Scan aborted");
	echo "<br><br>";
	if ($has_results) { ?><input type="button" class="button" onclick="parent.document.location.href='index.php#results'" value="<?php echo gettext("View results") ?>"><?php } ?>
	<script type="text/javascript">$('#loading').html("");$('#stop_div').html("");parent.document.getElementById('scan_button').disabled = false</script><?php
} else {
	echo "No nmap process found.";
}

/*
$rscan = new RemoteScan($net,($full_scan=="full") ? "root" : "ping");
if ($rscan->available_scan()) { // $full_scan!="full" && 
	
	// try remote nmap
	echo _("Scanning network") . " ($net), " . _(" with a remote sensor, please wait") . "...<br/>\n";
	$rscan->do_scan(FALSE);
	if ($rscan->err()!="") 
		echo _("Failed remote network scan: ") . "<font color=red>".$rscan->err() ."</font><br/>\n";
	else
		$rscan->save_scan();
	
} else {
?><script type="text/javascript">parent.document.getElementById('scan_button').disabled = true</script><?php
	echo _("Unable to launch remote network scan: ") . "<font color=red>".$rscan->err() ."</font><br/>\n"; // if ($full_scan!="full") 
	echo _("Scanning network") . " ($net), " . _(" locally, please wait") . "...<br><div id='loading'><img src='../pixmaps/loading.gif' align='absmiddle' width='16'> <input type='button' class='button' onclick='stop_nmap(\"$net\")' value='"._("Stop Scan")."'></div><br>\n";
	?><script type="text/javascript">parent.doIframe();</script><?php
	// try local nmap
	$scan = new Scan($net);
	$scan->append_option($timing_template);
	// $full_scan can be: null, "fast" or "full"
	if ($full_scan) {
	    if ($full_scan == "fast") {
	        $scan->append_option("-F");
	        $scan->do_scan(TRUE);
	    } else {
	        $scan->do_scan(TRUE);
	    }
	} else {
	    $scan->do_scan(FALSE);
	}
	//$scan->save_scan();
	
}
*/
?>
</body>
</html>


