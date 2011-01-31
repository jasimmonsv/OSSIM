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
</head>
<body>

<?php
require_once 'classes/Security.inc';
include ("../hmenu.php");
$net = GET('net');
$full_scan = GET('full_scan');
$timing_template = GET('timing_template');
$net_input = GET('net_input');
ossim_valid($net, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Net"));
ossim_valid($net_input, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Net"));
ossim_valid($full_scan, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("full scan"));
ossim_valid($timing_template, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("timing_template"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($net)) $net = $net_input;
require_once ('classes/Scan.inc');

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

	echo _("Unable to launch remote network scan: ") . "<font color=red>".$rscan->err() ."</font><br/>\n"; // if ($full_scan!="full") 
	echo _("Scanning network") . " ($net), " . _(" locally, please wait") . "...<br/>\n";
	
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
	$scan->save_scan();
	
}
echo gettext("Scan completed") . ".<br/><br/>";
echo "<a href=\"index.php#results\">" . gettext("Click here to show the results") . "</a>";
?>

</body>
</html>


