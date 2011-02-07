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
* - check_writable_relative()
* Classes list:
*/
require_once 'ossim_db.inc';
require_once 'classes/Session.inc';
require_once 'classes/User_config.inc';
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

Session::logcheck("MenuControlPanel", "BusinessProcesses");

function mapAllowed($perms_arr,$version) {
	if (Session::am_i_admin()) return true;
	$ret = false;
	foreach ($perms_arr as $perm=>$val) {
		// ENTITY
		if (preg_match("/^\d+$/",$perm)) {
			if (preg_match("/pro|demo/i",$version) && $_SESSION['_user_vision']['entity'][$perm]) {
				$ret = true;
			}
		// USER
		} elseif (Session::get_session_user() == $perm) {
			$ret = true;
		}
	}
	return $ret;
}

function is_in_assets($conn,$name,$type) {
	if ($type == "host") {
		$sql = "SELECT * FROM host WHERE hostname=\"$name\"";
	} elseif ($type == "sensor") {
		$sql = "SELECT * FROM sensor WHERE name=\"$name\"";
	} elseif ($type == "net") {
		$sql = "SELECT * FROM net WHERE name=\"$name\"";
	} elseif ($type == "host_group") {
		$sql = "SELECT * FROM host_group WHERE name=\"$name\"";
	}
	$result = $conn->Execute($sql);
	return (!$result->EOF) ? 1 : 0;
}

$can_edit = false;

if (Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
$can_edit = true;
}


function check_writable_relative($dir){
$uid = posix_getuid();
$gid = posix_getgid();
$user_info = posix_getpwuid($uid);
$user = $user_info['name'];
$group_info = posix_getgrgid($gid);
$group = $group_info['name'];
$fix_cmd = '. '._("To fix that, execute following commands as root").':<br><br>'.
                   "cd " . getcwd() . "<br>".
                   "mkdir -p $dir<br>".
                   "chown $user:$group $dir<br>".
                   "chmod 0700 $dir";
if (!is_dir($dir)) {
     die(_("Required directory " . getcwd() . "$dir does not exist").$fix_cmd);
}
$fix_cmd .= $fix_extra;

if (!$stat = stat($dir)) {
        die(_("Could not stat configs dir").$fix_cmd);
}
        // 2 -> file perms (must be 0700)
        // 4 -> uid (must be the apache uid)
        // 5 -> gid (must be the apache gid)
if ($stat[2] != 16832 || $stat[4] !== $uid || $stat[5] !== $gid)
        {
            die(_("Invalid perms for configs dir").$fix_cmd);
        }
}
check_writable_relative("./maps");
check_writable_relative("./pixmaps/uploaded");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= _("Alarms") ?> - <?= _("View")?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="./custom_style.css">
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<style type="text/css">
	.itcanbemoved { position:absolute; }
</style>
</head>
<? 
require_once 'classes/Security.inc';

$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$login = Session::get_session_user();
$default_map = $config->get($login, "riskmap", 'simple', 'main');

if ($default_map == "") $default_map = 1;

$map = ($_GET["map"]!="") ? $_GET["map"] : $default_map;
$_SESSION["riskmap"] = $map;

if ($_GET['default'] != "" && $map != "")
	$config->set($login, "riskmap", $map, 'simple', "main");
//print_r($opts);

//$hide_others = ($_GET["hide_others"]!="") ? $_GET["hide_others"] : 0;
$hide_others=1;

ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));
if (ossim_error()) {
	die(ossim_error());
}

$perms = array();
$query = "SELECT map,perm FROM risk_maps";
if ($result = $conn->Execute($query)) {
	while (!$result->EOF) {
		$perms[$result->fields['map']][$result->fields['perm']]++;
		$result->MoveNext();
	}
}
if (is_array($perms[$map]) && !mapAllowed($perms[$map],$version)) {
	echo "<br><br><center>"._("You don't have permission to see this Map $map.")."</center>";
	exit;
}
?>
<script>
	template_begin = '<table border=0 cellspacing=0 cellpadding=1 style="background-color:BGCOLOR"><tr><td colspan=2 class=ne1 align=center><i>NAME</i></td></tr><tr><td><a href="URL"><img src="ICON" width="SIZE" border=0></a></td><td>'
	template_end = '</td></tr></table>'
	txtbbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtbbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtbba = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtbbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtbrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtbrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtbra = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtbrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtbab = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtbar = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtbaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtbav = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtbvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtbvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtbva = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtbvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

	txtrbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtrbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtrba = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtrbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtrrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtrrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtrra = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtrrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtrab = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtrar = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtraa = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtrav = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtrvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtrvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtrva = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtrvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

	txtabb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtabr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtaba = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtabv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtarb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtarr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtara = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtarv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtaab = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtaar = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtaaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtaav = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtavb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtavr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtava = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtavv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

	txtvbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtvbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtvba = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtvbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtvrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtvrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtvra = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtvrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtvab = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtvar = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtvaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtvav = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
	txtvvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
	txtvvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
	txtvva = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
	txtvvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

	function responderAjax(url) {
		/*var ajaxObject = document.createElement('script');
		ajaxObject.src = url;
		ajaxObject.type = "text/javascript";
		ajaxObject.charset = "utf-8";
		document.getElementsByTagName('head').item(0).appendChild(ajaxObject);*/
	
		$.ajax({
		   type: "GET",
		   url: url,
		   success: function(msg){
			 eval(msg);
		   }
		});		
		
	}
	function urlencode(str) { return escape(str).replace('+','%2B').replace('%20','+').replace('*','%2A').replace('/','%2F').replace('@','%40'); }

	function changeDiv (id,name,url,icon,valor,r_url,v_url,a_url,ip,size) {
		if (size == 0) size = '100%';
		if (icon.match(/\#/)) {
			var aux = icon.split(/\#/);
			var iconbg = aux[1];
			icon = aux[0];
		} else {
			var iconbg = "transparent";
		}
		//icon = icon.replace("//","/");
		valor = valor.replace('<td>R</td>','<td><a class="ne11" target="main" href="'+r_url+'">R</a></td>').replace('<td>V</td>','<td><a class="ne11" target="main" href="'+v_url+'">V</a></td>').replace('<td>A</td>','<td><a class="ne11" target="main" href="'+a_url+'">A</a></td>');
		if (url=="REPORT" && ip!="") url = "../report/index.php?host="+ip;
        var content = template_begin.replace('NAME',name).replace('URL',url).replace('ICON',icon).replace('SIZE',size).replace('SIZE',size).replace('BGCOLOR',iconbg) + valor + template_end
		document.getElementById('alarma'+id).innerHTML = content;
	}

	function initDiv () {
		var x = 0;
		var y = 0;
		var el = document.getElementById('map_img');
		var obj = el;
		do {
			x += obj.offsetLeft;
			y += obj.offsetTop;
			obj = obj.offsetParent;
		} while (obj);	
		var objs = document.getElementsByTagName("div");
		var txt = ''
		for (var i=0; i < objs.length; i++) {
			if (objs[i].className == "itcanbemoved") {
				xx = parseInt(objs[i].style.left.replace('px',''));
				objs[i].style.left = xx + x
				yy = parseInt(objs[i].style.top.replace('px',''));
				objs[i].style.top = yy + y;
				objs[i].style.visibility = "visible"
			}
		}
		refresh_indicators()
	}
	
	function refresh_indicators() {
		responderAjax("refresh.php?map=<? echo $map ?>")
	}
		refresh_indicators();
		setInterval(refresh_indicators,5000);
	
</script>
<body leftmargin=5 topmargin=5 class=ne1 onload="initDiv()">
<table border=0 cellpadding=0 cellspacing=0><tr>
<td valign=top id="map">
	<img id="map_img" src="maps/map<? echo $map ?>.jpg" border="0">
</td>
<td valign=top class=ne1 style="padding-left:5px">
<?php

if(!$hide_others){
?>

 <h2><?= _("Maps") ?></h2>
 <?php
 if($can_edit){
   print "&nbsp;(<a href='riskmaps.php?hmenu=Risk+Maps&smenu=Edit+Risk+Maps' target='_parent'><b>" . _("Edit") . "</b></a>)";
   //print "&nbsp;(<a href='index.php?map=$map'><b>" . _("Edit") . "</b></a>)";
 }
 ?>
 <br>
 <?
	$maps = explode("\n",`ls -1 'maps' | grep -v CVS`);
	$i=0; $n=0; $txtmaps = ""; $linkmaps = "";
	foreach ($maps as $ico) if (trim($ico)!="") {
	    if(!getimagesize("maps/" . $ico)){ continue;}
		$n = str_replace("map","",str_replace(".jpg","",$ico));
		if (is_array($perms[$n]) && !mapAllowed($perms[$n],$version)) continue;
		$txtmaps .= "<td><a href='$SCRIPT_NAME?map=$n'><img src='maps/$ico' border=".(($map==$n) ? "2" : "0")." width=100 height=100></a></td>";
		$i++; if ($i % 4 == 0) {
			$txtmaps .= "</tr><tr>";
		}
	}
 ?> 
 <table><tr><? echo $txtmaps ?></tr></table>	
 <br>
<?
} // if(!$hide_others)
	// Get Host, Sensor, Net lists to check user perms
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn,true);
	$nets = Net::get_list($conn);
	$query = "select * from risk_indicators where name <> 'rect' AND map= ?";
	//echo $map."<br>";
	$params = array($map);
	if (!$rs = &$conn->Execute($query, $params)) {
		print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF){
			$size = ($rs->fields["size"] > 0) ? $rs->fields["size"] : '100%';
			if (preg_match("/\#/",$rs->fields["icon"])) {
				$aux = explode("#",$rs->fields["icon"]);
				$icon = $aux[0]; $bgcolor = $aux[1];
			} else $bgcolor = "transparent";
			$has_perm = 0;
			$in_assets = is_in_assets($conn,$rs->fields['type_name'],$rs->fields['type']);
			if ($rs->fields['type'] == "host") {
				foreach ($hosts as $hip=>$hname) if ($hname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "sensor" || $rs->fields['type'] == "server") {
				foreach ($sensors as $sip=>$sname) if ($sname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "net") {
				foreach ($nets as $net) if ($net->get_name() == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "host_group") {
				if (Session::groupHostAllowed($conn,$rs->fields['type_name'])) $has_perm = 1;
			} else $has_perm = 1;
			if (Session::am_i_admin()) $has_perm = 1;
			require_once('classes/Util.inc');
			if (!$in_assets) {
				echo "<div id=\"alarma".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
				echo "<table border=0 cellspacing=0 cellpadding=1 style=\"background-color:$bgcolor\"><tr><td colspan=2 class=ne align=center><i>".Util::htmlentities($rs->fields["name"], ENT_COMPAT, "UTF-8")."</i></td></tr><tr><td><a href=\"\" onclick=\"alert('Warning: this asset is not in inventory.');return false\"><img src=\"../pixmaps/marker--exclamation.png\" width=\"".$size."\" height=\"".$size."\" border=0></a></td><td>";
				echo "<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td></tr></table>";
				echo "</td></tr></table></div>\n";
				$rs->MoveNext(); continue;
			}
			if (!$has_perm) { $rs->MoveNext(); continue; }
			echo "<div id=\"alarma".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
			if ($rs->fields["url"]=="") $rs->fields["url"]="javascript:;";
			echo "<table border=0 cellspacing=0 cellpadding=1 style=\"background-color:$bgcolor\"><tr><td colspan=2 class=ne align=center><i>".$rs->fields["name"]."</i></td></tr><tr><td><a href=\"".$rs->fields["url"]."\"><img src=\"".$rs->fields["icon"]."\" width=\"".$size."\" height=\"".$size."\" border=0></a></td><td>";
			echo "<table border=0 cellspacing=0 cellpadding=1><tr><td>R</td><td>V</td><td>A</td></tr><tr><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td></tr></table>";
			echo "</td></tr></table></div>\n";
			$rs->MoveNext();
		}
	}

	$query = "select * from risk_indicators where name='rect' AND map= ?";
	$params = array($map);
    
	if (!$rs = &$conn->Execute($query, $params)) {
        print $conn->ErrorMsg();        
	} else {
        while (!$rs->EOF){
        	$has_perm = 0;
			$in_assets = is_in_assets($conn,$rs->fields['type_name'],$rs->fields['type']);
			if ($rs->fields['type'] == "host") {
				foreach ($hosts as $hip=>$hname) if ($hname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "sensor" || $rs->fields['type'] == "server") {
				foreach ($sensors as $sip=>$sname) if ($sname == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "net") {
				foreach ($nets as $net) if ($net->get_name() == $rs->fields['type_name']) $has_perm = 1;
			} elseif ($rs->fields['type'] == "host_group") {
				if (Session::groupHostAllowed($conn,$rs->fields['type_name'])) $has_perm = 1;
			} else $has_perm = 1;
			if (Session::am_i_admin()) $has_perm = 1;
			if (!$in_assets) {
				echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
				echo "<a href=\"\" onclick=\"alert('Warning: this asset is not in inventory.');return false;\" target=\"_blank\" style=\"text-decoration:none\"><table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table></a>";
				echo "</div>\n";
				$rs->MoveNext(); continue;
			}
			if (!$has_perm) { $rs->MoveNext(); continue; }
			echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
			if ($rs->fields["url"]=="") $rs->fields["url"]="javascript:;";
			echo "<a href=\"".$rs->fields["url"]."\" target=\"_blank\" style=\"text-decoration:none\"><table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table></a>";
			echo "</div>\n";
            $rs->MoveNext();
	    }
    }

//} // if(!$hide_others)
	
	$conn->close();
?>
</td>
</tr>
<form name="fdefault" method="get" action="view.php">
<input type="hidden" name="map" value="<?=$_GET['map']?>">
<input type="hidden" name="default" value="1">
<tr>
	<td><input type="submit" value="<?=_("Set as Default")?>" class="button"></td>
</tr>
</form>
</table>
</body>
</html>
