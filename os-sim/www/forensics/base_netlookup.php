<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
//
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("ip"));
if (ossim_error()) {
    die(ossim_error());
}
//
require_once ('classes/Net.inc');
require_once 'ossim_db.inc';
$db = new ossim_db();
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$conn = $db->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$conn = $db->connect();
$netname = Net::GetClosestNet($conn, $ip);
if ($netname != false) {
	list($ips,$icon) = Net::get_ips_by_name($conn,$netname,true);
	if ($icon!="") echo "<img src='data:image/png;base64,".base64_encode($icon)."' border='0'> ";
	echo "<b>$netname</b> ($ips)";
}
else echo "<b>$ip</b> not found in home networks";
$db->close($conn);
?>
