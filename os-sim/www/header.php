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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
Session::useractive("session/login.php");
?>
<html>
<head>
<title>OSSIM (Open Source Security Information Management)</title>
<link rel="stylesheet" type="TEXT/CSS" href="style/top.css">
<style> html,body { height:100%} </style>
</head>

<body marginwidth=0 marginheight=0 topmargin=0 leftmargin=0>

<?php
require_once 'classes/Config.inc';
$config = new Config();
$version = $config->get_conf("ossim_server_version", FALSE);
//
?>
<table border=0 cellpadding=0 cellspacing=0 width="100%">
<tr><td id="ossimlogo" style="background:url('pixmaps/top/bg_header.gif') repeat-x bottom left;height:65">
  <table border=0 cellpadding=0 cellspacing=0 height="65">
  <tr><td style="padding-left:10px"><img src="pixmaps/top/logo<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" border=0></td></tr>
  </table>
</td></tr>
<tr><td style="background:url('pixmaps/top/bg_darkgray.gif') repeat-x top left;height:2">&nbsp;</td></tr>
</table>

<?
// Check Updates
$check_updates = $config->get_conf("update_checks_enable");
$last_update = str_replace("-", "", $config->get_conf("last_update"));
$updates_file = "/etc/ossim/updates/update_log.txt";
$updatesf = array();
if (file_exists($updates_file)) $updatesf = array_reverse(file($updates_file));
foreach($updatesf as $line) if (preg_match("/^(\d+)\s(.*)/", trim($line) , $found) != "") {
    $new_updates = ($found[1] > $last_update) ? true : false;
    if ($new_updates) break;
}
if ($check_updates == "" || $new_updates) { ?>
<div id="updates" style="position:absolute;top:16px;left:240px;z-index:1000;background:url(pixmaps/ballooninfo.png) no-repeat;width:190px;height:57px;padding:5px 0px 0px 6px;line-height:14px">
<?php
    $link = "updates/index.php?hmenu=Upgrade&smenu=Updates";
    if ($check_updates == "") echo "<a href='$link' class='blue' target='main'>" . _("Enable auto update checks") . "?</a><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$link&checking=1' class='blue' target='main'>" . _("Yes") . "</a> &nbsp;&nbsp;&nbsp;&nbsp; <a href='$link&checking=2' class='blue' target='main'>" . _("No") . "</a></center>";
    elseif ($new_updates) echo "<img src='pixmaps/top/lamp.png' align='absmiddle' border=0>&nbsp;<a href='$link' class='blue' target='main'> "._("New updates available")."</a>";
?>
</div>
<?php
}
?>
<br>
<?php
Session::logcheck("MainMenu", "Index");
include ("statusbar/status_bar.php"); ?>
</body>
</html>

