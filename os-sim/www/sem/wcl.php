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
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');
Session::logcheck("MenuEvents", "ControlPanelSEM");
$start = GET("start");
$end = GET("end");
$ips = GET("ips");
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($ips, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("ips"));
if (ossim_error()) {
    die(ossim_error());
}

$user = $_SESSION["_user"];
$result = array();

if ($ips != "") {
	$cmd = "sudo ./fetchremote_wcl.pl '$user' '$start' '$end' $ips";
	$ips_arr = explode(",",$ips);
	$ip_to_name = array();
	foreach ($_SESSION['logger_servers'] as $name=>$ip) {
		$ip_to_name[$ip] = $name;
	}
} else {
	$cmd = "perl wcl.pl '$user' '$start' '$end'";
}
$debuglog = GET("debug_log");
if($debuglog != ""){
	$handle = fopen($debuglog, "a+");
	fputs($handle,"============================== WCL.php ".date("Y-m-d H:i:s")." ==============================\n");
	$cmd.= " '$debuglog'";
	fputs($handle,"WCL.php: $cmd\n");
	fclose($handle);
}

$fp = popen("$cmd 2>/dev/null", "r");
while (!feof($fp)) {
    $line = trim(fgets($fp));
    if ($line != "") $result[] = $line;
}
fclose($fp);
$ok = 0;
$i = 0;
foreach($result as $line) if (trim($line) != "") {
    if ($ips != "") {
    	$current_server = $ip_to_name[$ips_arr[$i]];
    	echo "<table align='center'><tr><td style='padding-left:5px;padding-right:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:#".$_SESSION['logger_colors'][$current_server]['bcolor'].";color:#".$_SESSION['logger_colors'][$current_server]['fcolor']."'>$current_server</td><td style='padding-left:5px'>"."<b>" . Util::number_format_locale($line, 0) . "</b> "._("logs")."</td></tr></table>";
    	$ok = 1;
    	$i++;
    } else {
		echo _("About")." <b>" . Util::number_format_locale($line, 0) . "</b> "._("logs")."\n";
    	$ok = 1;
    	break;
    }
}
if (!$ok) echo _("About")." <b>0</b> "._("logs")."\n";

?>
