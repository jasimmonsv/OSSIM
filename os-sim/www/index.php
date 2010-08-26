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
require_once ('classes/Session.inc');
require_once 'classes/Security.inc';
Session::useractive("session/login.php");
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$ossim_link = $conf->get_conf("ossim_link");
$option = REQUEST("option");
$soption = REQUEST("soption");
$url = REQUEST("url");
ossim_valid($option, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("option"));
ossim_valid($soption, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("soption"));
ossim_valid($url, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("url"));
if (ossim_error()) {
    die(ossim_error());
}
// product version check
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title> <?php echo gettext("AlienVault - ".($opensource ? "Open Source SIM" : ($demo ? "Professional SIEM Demo" : "Professional SIEM"))); ?> </title>
<link rel="alternate" title="OSSIM Alarm Console"
	href="<?php echo "$ossim_link/feed/alarm_console.php" ?>"
	type="application/rss+xml">
<link rel="Shortcut Icon" type="image/x-icon" href="favicon.ico">
</head>
<frameset rows="66,*" border="0" frameborder="0" id="ossimframeset">
<frame src="header.php" name="header" scrolling="no" marginwidth='0' marginheight='0'>
<frameset cols="180,*" border="0" frameborder="0">
	<frame src="top.php?option=<?php echo $option ?>&soption=<?php echo $soption ?>&url=<?php echo urlencode($url) ?>" name="topmenu" scrolling="no" marginwidth='0' marginheight='0'>
	<frame src="#" name="main" id="maincontent" marginwidth='0' marginheight='0'>
</frameset>
</frameset>
</html>

