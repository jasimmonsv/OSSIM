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
require_once 'classes/Session.inc';
require_once 'ossim_conf.inc';
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to edit risk indicators");
exit();
}

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

/*

Requirements: 
- web server readable/writable ./maps
- web server readable/writable ./pixmaps/uploaded
- standard icons at pixmaps/standard
- Special icons at docroot/ossim_icons/


TODO: Rewrite code, beutify, use ossim classes for item selection, convert operations into ossim classes

*/

require_once 'classes/Security.inc';
require_once 'ossim_db.inc';


$erase_element = GET('delete');
$erase_type    = GET('delete_type');
$map           = (POST("map") != "") ? POST("map") : ((GET("map") != "") ? GET("map") : (($_SESSION["riskmap"]!="") ? $_SESSION["riskmap"] : 1));
$type          = (GET("type")!="") ? GET("type") : "host";
$name          = POST('name');

ossim_valid($erase_element, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("erase_element"));
ossim_valid($erase_type , "map", "icon", OSS_NULLABLE, 'illegal:'._("erase_type"));
ossim_valid($type, OSS_ALPHA, OSS_DIGIT, 'illegal:'._("type"));
ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%", 'illegal:'._("name"));
ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));

if (ossim_error()) {
	die(ossim_error());
}

// Cleanup a bit

$name = str_replace("..","",$name);
$erase_element = str_replace("..","",$erase_element);

$uploaded_icon = false;

if (is_uploaded_file($HTTP_POST_FILES['fichero']['tmp_name'])) {
 if (exif_imagetype ($HTTP_POST_FILES['fichero']['tmp_name']) == IMAGETYPE_JPEG || exif_imagetype ($HTTP_POST_FILES['fichero']['tmp_name']) == IMAGETYPE_GIF ) {
    $size = getimagesize($HTTP_POST_FILES['fichero']['tmp_name']);
        if ($size[0] < 400 && $size[1] < 400) {
                $uploaded_icon = true;
                $filename = "pixmaps/uploaded/" . $name . ".jpg";
                move_uploaded_file($HTTP_POST_FILES['fichero']['tmp_name'], $filename);
        } else {
            echo _("<span style='color:#FF0000;'>The file uploaded is too big (Max image size 400x400 px).</span>");
        }
    } else {
        echo _("<span style='color:#FF0000;'>The image format should be .jpg or .gif.</span>");
    }
}
if (is_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'])) {
	$filename = "maps/" . $name . ".jpg";
	if(getimagesize($HTTP_POST_FILES['ficheromap']['tmp_name'])){
		move_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'], $filename);
	}
}
if ($erase_element != "") {
	switch($erase_type){
		case "map":
			if(getimagesize("maps/" . $erase_element)){
			unlink("maps/" . $erase_element);
			}
		break;
		case "icon":
			if(getimagesize("pixmaps/uploaded/" . $erase_element)){
			unlink("pixmaps/uploaded/" . $erase_element);
			}
			break;
		default:
		break;
	}
}


$db   = new ossim_db();
$conn = $db->connect();

$perms = array();

$query = "SELECT map,perm FROM risk_maps";
if ($result = $conn->Execute($query)) {
  while (!$result->EOF) {
    $perms[$result->fields['map']][$result->fields['perm']]++;
    $result->MoveNext();
  }
}
if (is_array($perms[$map]) && !mapAllowed($perms[$map],$version)) {
	echo "<br><br><center>"._("You don't have permission to see this Map.")."</center>";
	exit;
}


// perm check
$sensor_where = "";
$sensor_hg = "";
if (Session::allowedSensors() != "") {
    $user_sensors = explode(",",Session::allowedSensors());
    if (count($user_sensors)>0) $sensor_hg = " AND s.ip in ('".str_replace(",","','",Session::allowedSensors())."')";
    foreach ($user_sensors as $user_sensor)
        $sensor_where .= ($sensor_where != "") ? " OR ip='".$user_sensor."'" : " WHERE ip='".$user_sensor."'";
    //if ($sensor_where == "") $sensor_where = "";
}
$nets_where = "";
if (Session::allowedNets() != "") {
    $user_nets = explode(",",Session::allowedNets());
    foreach ($user_nets as $user_net)
        $nets_where .= ($nets_where != "") ? " OR ips='".$user_net."'" : " WHERE ips='".$user_net."'";
    //if ($nets_where == "") $nets_where = "";
}

//$types = array("host","net","host_group","net_group","sensor","server");
if (Session::am_i_admin()) $types = array("host","host_group","net","sensor","server");
else $types = array("host","host_group","net","sensor");

$data_types = array();
foreach ($types as $htype) {
	$data_arr = array();
	if($htype == "host"){
		require_once 'classes/Host.inc';
		//$query = "select hostname as name,ip from $htype h order by hostname";
		$data_arr = Host::get_list($conn);
	}
	elseif ($htype == "sensor") {
		require_once 'classes/Sensor.inc';
		//$query = "select name from sensor$sensor_where";
		$data_arr = Sensor::get_list($conn);
	}
	elseif ($htype == "net") {
		require_once 'classes/Net.inc';
		//$query = "select name,ips from net$nets_where";
		$nets_arr = Net::get_list($conn);
	}
	elseif ($htype == "host_group") {
		require_once 'classes/Host_group.inc';
		//$query = "select distinct t.name from $htype t,host_group_sensor_reference r,sensor s where t.name=r.group_name and r.sensor_name=s.name $sensor_hg order by t.name";
		$data_arr = Host_group::get_list($conn);
	}
	foreach ($data_arr as $element) {
		$data_types[$htype][] = ($htype == "host") ? $element->get_hostname() : $element->get_name();
	}
	/*
	if (!$rs = &$conn->Execute($query)) {
		print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF){
	
			//if ($htype == "host" && !Session::hostAllowed($conn,$rs->fields["ip"])) { $rs->MoveNext(); continue; }
			//if ($htype == "net" && !Session::netAllowed($conn,$rs->fields["ips"])) { $rs->MoveNext(); continue; }
			$data_types[$htype][] = $rs->fields["name"];
			$rs->MoveNext();
		}
	}
	*/
}


if (preg_match("/MSIE/",$_SERVER['HTTP_USER_AGENT'])) { ?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<? } ?>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?= _("Risk Maps") ?>  - <?= _("Edit") ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="custom_style.css">
	<link rel="stylesheet" href="lytebox.css" type="text/css" media="screen" />
	<link rel="stylesheet" type="text/css" href="../style/greybox.css" />
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<script type="text/javascript" src="lytebox.js"></script>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>


	<script type='text/javascript'>

		function GB_onclose() {}

		function loadLytebox(){
			var cat = document.getElementById('category').value;
			var id = cat + "-0";
			myLytebox.start(document.getElementById(id));
		}

		function choose_icon(icon){
		var cat   = document.getElementById('category').value;
		var timg = document.getElementById('chosen_icon');
		//var res = "48x48";
		//
		//for( i = 0; i < document.f.resolution2.length; i++ )
		//{
		//	if( document.f.resolution2[i].checked == true ){
		//		res = document.f.resolution2[i].value;
		//		break;
		//	}
		//}
		//icon2 = icon.replace("RESOLUTION",res);
		//timg.src= icon2;
		timg.src = icon
		changed = 1;
		}

		function toggleLayer( whichLayer )
		{
		  var elem, vis;
		  if( document.getElementById ) // this is the way the standards work
			elem = document.getElementById( whichLayer );
		  else if( document.all ) // this is the way old msie versions work
			  elem = document.all[whichLayer];
		  else if( document.layers ) // this is the way nn4 works
			elem = document.layers[whichLayer];
		  vis = elem.style;
		  // if the style.display value is blank we try to figure it out here
		  if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
			vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
		  vis.display = (vis.display==''||vis.display=='block')?'none':'block';
		}


		template_begin = '<table border=0 cellspacing=0 cellpadding=1 style="background-color:BGCOLOR"><tr><td colspan=2 class=ne1 align=center><i>NAME</i></td></tr><tr><td><img src="ICON" width="SIZE" border=0></td><td>'
		template_end = '</td></tr></table>'
		template_rect = '<table border=0 cellspacing=0 cellpadding=0 width="100%" height="100%"><tr><td style="border:1px dotted black">&nbsp;</td></tr></table>'
		txtbbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtbbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtbba = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtbbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtbrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtbrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtbra = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtbrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtbab = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtbar = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtbaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtbav = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtbvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtbvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtbva = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtbvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

		txtrbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtrbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtrba = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtrbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtrrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtrrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtrra = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtrrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtrab = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtrar = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtraa = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtrav = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtrvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtrvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtrva = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtrvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

		txtabb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtabr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtaba = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtabv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtarb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtarr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtara = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtarv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtaab = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtaar = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtaaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtaav = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtavb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtavr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtava = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtavv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

		txtvbb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtvbr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtvba = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtvbv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtvrb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtvrr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtvra = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtvrv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtvab = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtvar = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtvaa = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtvav = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'
		txtvvb = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/b.gif" border=0></td></tr></table>'
		txtvvr = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/r.gif" border=0></td></tr></table>'
		txtvva = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/a.gif" border=0></td></tr></table>'
		txtvvv = '<table border=0 cellspacing=0 cellpadding=1><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td><td><img src="images/v.gif" border=0></td></tr></table>'

		function findPos(obj) {
			var curleft = curtop = 0;
			if (obj.offsetParent) {
			do {
				curleft += obj.offsetLeft;
				curtop += obj.offsetTop;
			} while (obj = obj.offsetParent);
			return [curleft,curtop];
		}
		}

		var moz = document.getElementById && !document.all;
		var moving = false;
		var resizing = false;
		var dobj;	
		var changed = 0;
    
		function dragging(e){
			if (moving) {
				x = moz ? e.clientX : event.clientX;
				y = moz ? e.clientY : event.clientY;
				sx = (typeof(window.scrollX) != 'undefined') ? window.scrollX : ((typeof(document.body.scrollLeft) != 'undefined') ? document.body.scrollLeft : 0);
				sy = (typeof(window.scrollY) != 'undefined') ? window.scrollY : ((typeof(document.body.scrollTop) != 'undefined') ? document.body.scrollTop : 0);
				document.f.state.value = "<?= _("moving...") ?>";
				document.f.posx.value = x + sx
				document.f.posy.value = y + sy
				dobj.style.left = x + sx - parseInt(dobj.style.width.replace('px',''))/2;
				dobj.style.top = y + sy - parseInt(dobj.style.height.replace('px',''))/2;
				// Check if it's under the wastebin icon
				var waste = document.getElementById("wastebin")
				var waste_pos = [];
				waste_pos  = findPos(waste);
				if ( x>= waste_pos[0] && x<= waste_pos[0] + 48 && y>=waste_pos[1] && y<= waste_pos[1] + 53 ) {
					dobj.style.visibility = 'hidden'
				}
				changed = 1;
				return false;
			}
			
			if (resizing) {
				sx = (typeof(window.scrollX) != 'undefined') ? window.scrollX : ((typeof(document.body.scrollLeft) != 'undefined') ? document.body.scrollLeft : 0);
				sy = (typeof(window.scrollY) != 'undefined') ? window.scrollY : ((typeof(document.body.scrollTop) != 'undefined') ? document.body.scrollTop : 0);
				x = moz ? e.clientX+10+ sx : event.clientX+10+ sx;
				y = moz ? e.clientY+10+ sy : event.clientY+10+ sy;
				document.f.state.value = "<?= _("resizing...") ?>";
				document.f.posx.value = x + sx;
				document.f.posy.value = y + sy;
				xx = parseInt(dobj.style.left.replace('px','')) + 5;
				yy = parseInt(dobj.style.top.replace('px','')) + 5;
				w = (x > xx) ? x-xx : xx
				h = (y > yy) ? y-yy : yy
				dobj.style.width = w
				dobj.style.height = h 
				changed = 1;
				return false;
			}
    }
	
		function releasing(e) {
			moving = false;
			resizing = false;
			document.f.state.value = ""
			if (dobj != undefined) dobj.style.cursor = 'pointer'
		}
		
		function pushing(e) {
			var fobj = moz ? e.target : event.srcElement;
			var button = moz ? e.which : event.button;
			if (typeof fobj.tagName == 'undefined') return false;
			while (fobj.tagName.toLowerCase() != "html" && fobj.className != "itcanbemoved") {
				fobj = moz ? fobj.parentNode : fobj.parentElement;
			}
			if (fobj.className == "itcanbemoved") {
				var ida = fobj.id.replace("alarma","").replace("rect","");
				if (document.getElementById('dataname'+ida)) {
					if (document.getElementById('dataurl'+ida).value=="REPORT") {
						document.getElementById('linktomapurl').style.display = 'none';
						document.getElementById('linktomapmaps').style.display = 'none';
						document.getElementById('check_report').checked=1;
					}
					else {
						document.getElementById('linktomapurl').style.display = '';
						document.getElementById('linktomapmaps').style.display = '';
						document.getElementById('check_report').checked=0;
					}
					document.f.url.value = document.getElementById('dataurl'+ida).value
					document.f.alarm_id.value = ida
					if (!fobj.id.match(/rect/)) {
						document.f.alarm_name.value = document.getElementById('dataname'+ida).value
						document.f.type.value = document.getElementById('datatype'+ida).value
						var id_type = 'elem_'+document.getElementById('datatype'+ida).value
						document.getElementById('elem').value = document.getElementById('type_name'+ida).value
						change_select()
						if(document.getElementById('dataicon' + ida) != null) {
							document.getElementById('chosen_icon').src = document.getElementById('dataicon'+ida).value
						}
						if(document.getElementById('dataiconsize' + ida) != null) {
							document.getElementById('iconsize').value = document.getElementById('dataiconsize'+ida).value
						}
						if(document.getElementById('dataiconbg' + ida) != null) {
							document.getElementById('iconbg').value = document.getElementById('dataiconbg'+ida).value
						}
					}
				}
				if (button>1) {
					resizing = true;
					fobj.style.cursor = 'nw-resize'
				} else {
					moving = true;
					fobj.style.cursor = 'move'
				}
				dobj = fobj
				return false;
			}
		}
		
		document.onmousedown = pushing;
		document.onmouseup = releasing;
		document.onmousemove = dragging;

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

		function drawDiv (id, name, valor, icon, url, x, y, w, h, type, type_name, size) {
			if (size == 0) size = '100%';
			if (icon.match(/\#/)) {
				var aux = icon.split(/\#/);
				var iconbg = aux[1];
				icon = aux[0];
			} else {
				var iconbg = "transparent";
			}
			var el = document.createElement('div');
			var the_map= document.getElementById("map_img")
			var map_pos = [];
			map_pos = findPos(the_map);
			el.id='alarma'+id
			el.className='itcanbemoved'
			el.style.position = 'absolute';
			el.style.left = x + map_pos[0];
			el.style.top = y
			el.style.width = w
			el.style.height = h
			var content = template_begin.replace('NAME',name).replace('ICON',icon).replace('SIZE',size).replace('SIZE',size).replace('BGCOLOR',iconbg) + valor + template_end
			el.innerHTML = content;
			el.style.visibility = 'visible'
			document.body.appendChild(el);
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataname' + id + '" id="dataname' + id + '" value="' + name + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="datatype' + id + '" id="datatype' + id + '" value="' + type + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="type_name' + id + '" id="type_name' + id + '" value="' + type_name + '">\n';
			document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataurl' + id + '" id="dataurl' + id + '" value="' + url + '">\n';
			if (document.getElementById('dataicon' + id) != null) {
				document.getElementById('tdnuevo').innerHTML += '<input type="hidden" name="dataicon' + id + '" id="dataicon' + id + '" value="' + icon + '">\n';
			}
			document.f.state.value = "<?= _("New") ?>"
		}

		function changeDiv (id,name,url,icon,valor, x, y, w, h, size) {
			//
			if (size == 0) size = '100%';
			if (icon.match(/\#/)) {
				var aux = icon.split(/\#/);
				var iconbg = aux[1];
				icon = aux[0];
			} else {
				var iconbg = "transparent";
			}
			var content = template_begin.replace('NAME',name).replace('ICON',icon).replace('SIZE',size).replace('SIZE',size).replace('BGCOLOR',iconbg) + valor + template_end
			if (typeof(document.getElementById('alarma'+id)) != null) document.getElementById('alarma'+id).innerHTML = content;
			document.f.state.value = ""
			//changed = 1;
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
					objs[i].style.left = xx + x;
					yy = parseInt(objs[i].style.top.replace('px',''));
					objs[i].style.top = yy + y; 
					objs[i].style.visibility = "visible"
				}
			}
			refresh_indicators()
			// greybox
			$("a.greybox").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   var url = this.href + "?dir=" + document.getElementById('category').value;
			   GB_show(t,url,420,"50%");
			   return false;
			});
			// Tree
			load_tree("");
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
			$('#tree').append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layer).dynatree({
				initAjax: { url: "type_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					var keys = dtnode.data.key.split(/\;/);
					document.getElementById('type').value = keys[0];
					document.getElementById('elem').value = keys[1];
					if (keys[0] == "host" || keys[0] == "net") document.getElementById('check_report').checked = true;
					else document.getElementById('check_report').checked = false;
					toggle_rm();
					change_select()
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "type_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1
		}
		
		function get_echars(data){
			var echars='';
			//alert(data);
			//alert(data.match(/&#(\d{4,5});/));
			alert(data.match(/^[a-zA-Z]*$/));
			//if(){
			
			//var echars = ( preg_match_all('/&#(\d{4,5});/', $data, $match) != false ) ? $match[1] : array();
			
			return echars;
		}
		
		function addnew(map,type) {
			document.f.alarm_id.value = ''
			if (type == 'alarm') {
				if (document.f.alarm_name.value != '') {
					var txt = '';
					var robj = document.getElementById("chosen_icon");
					txt = txt + urlencode(robj.src) + ';';
					type = document.f.type.value;
					//var id_type = 'elem_'+type
					elem = document.getElementById('elem').value;
					txt = txt + urlencode(type) + ';' + urlencode(elem) + ';';
					var temp_value=document.f.alarm_name.value;
					//alert(temp_value);
					if(temp_value.match(/^[a-zA-Z0-9ó]$/)==null){
						txt = txt + document.f.alarm_name.value + ';';
					}else{
						//alert('codificandooooo');
						txt = txt + urlencode(document.f.alarm_name.value) + ';';
					}
					txt = txt + urlencode(document.f.url.value) + ';';
					txt = txt.replace(/\//g,"url_slash");
					txt = txt.replace(/\%3F/g,"url_quest");
					txt = txt.replace(/\%3D/g,"url_equal");
					responderAjax('responder.php?map=' + map + '&data=' + txt + '&iconbg=' + document.f.iconbg.value + '&iconsize=' + document.f.iconsize.value);
					document.f.state.value = '<?= _("New Indicator created.") ?>!';
				} else {
					alert("<?= _("Indicator name can't be void") ?>")
				}	
			} else {
				responderAjax('responder.php?map=' + map + '&type=rect&url=' + urlencode(document.f.url.value))
				document.f.state.value = '<?= _("New rectangle created") ?>'
			}
			changed = 1;
		}

		function drawRect (id,x,y,w,h) {
			var el = document.createElement('div');
			var the_map= document.getElementById("map_img")
			var map_pos = [];
			map_pos = findPos(the_map)
			el.id='rect'+id
			el.className='itcanbemoved'
			el.style.position = 'absolute';
			el.style.left = x + map_pos[0];
			el.style.top = y
			el.style.width = w
			el.style.height = h
			var content = template_rect
			el.innerHTML = "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>" + content;
			el.style.visibility = 'visible'
			document.body.appendChild(el);
			document.f.state.value = "<?= _("New") ?>"
			changed = 1;
		}

		function save(map) {
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
				if (objs[i].className == "itcanbemoved" && objs[i].style.visibility != "hidden") {
					xx = objs[i].style.left.replace('px','');
					yy = objs[i].style.top.replace('px','');
					txt = txt + objs[i].id + ',' + (xx-x) + ',' + (yy-y) + ',' + objs[i].style.width + ',' + objs[i].style.height + ';';
				}
			}
			var id_type = 'elem_'+document.f.type.value;
			var url_aux = urlencode(document.f.url.value);
			var icon_aux = urlencode(document.getElementById("chosen_icon").src);
			url_aux = url_aux.replace(/\//g,"url_slash");
			url_aux = url_aux.replace(/\%3F/g,"url_quest");
			url_aux = url_aux.replace(/\%3D/g,"url_equal");
			icon_aux = icon_aux.replace(/\//g,"url_slash");
			icon_aux = icon_aux.replace(/\%3F/g,"url_quest");
			icon_aux = icon_aux.replace(/\%3D/g,"url_equal");
			urlsave = 'save.php?type=' +urlencode(document.f.type.value)+'&type_name='+ urlencode(document.getElementById('elem').value) +'&map=' + map + '&id=' + document.f.alarm_id.value + '&name=' + urlencode(document.f.alarm_name.value) + '&url=' + url_aux + '&icon=' + icon_aux + '&data=' + txt + '&iconbg=' + document.f.iconbg.value + '&iconsize=' + document.f.iconsize.value;
			//alert(urlsave)
			responderAjax(urlsave);
			document.f.state.value = "<?= _("Indicators saved.") ?>";
			changed = 0;
		}

		function refresh_indicators() {
			responderAjax("refresh.php?map=<? echo $map ?>&bypassexpirationupdate=1")
		}
		refresh_indicators();
		setInterval(refresh_indicators,5000);


		function chk(fo) {
			if  (fo.name.value=='') {
				alert("<?php echo "Icon requires a name!" ?>");
				return false;
			}
			return true;
		}
		
		function view() { document.location.href = '<? echo $SCRIPT_NAME ?>?map=<? echo $map ?>&type=' + document.f.type.value }	
		
		function change_select()
		{
			document.getElementById('selected_msg').innerHTML = "<b><?=_("Selected type")?></b>:"+document.f.type.value+" - "+document.f.elem.value;
			if (document.f.type.value == "host_group") {
				document.getElementById('linktoreport').style.display = 'none';
			}
			else {
				document.getElementById('linktoreport').style.display = '';
			}
		}
		
		function toggle_rm() {
			if (document.getElementById('check_report').checked==true) {
				document.getElementById('linktomapurl').style.display = 'none';
				document.getElementById('linktomapmaps').style.display = 'none';
				document.f.url.value = "REPORT";
			}
			else {
				document.getElementById('linktomapurl').style.display = '';
				document.getElementById('linktomapmaps').style.display = '';
				document.f.url.value = "";
			}
		}
		
		function checkSaved()
		{
			// Disable, this seems to break something
			if(changed)
			{
				//(if(0){
				var x=window.confirm("<?= _("Unsaved changes, want to save them before exiting?"); ?>");
				if(x)
				{
					save('<? echo $map ?>');
					return true;
				} 
				else 
					return true;
			}		
		}
				
		
	</script>
</head>

<body class='ne1' oncontextmenu="return true;" onload='initDiv();' onunload='checkSaved();'>

<table class='noborder' border='0' cellpadding='0' cellspacing='0'>
	
	<?php
		$maps = explode("\n",`ls -1 'maps'`);
		$i=0; $n=0; $linkmaps = "";
		foreach ($maps as $ico) if (trim($ico)!="") {
				if(is_dir("maps/" . $ico) || !getimagesize("maps/" . $ico)){ continue;}
				$n = str_replace("map","",str_replace(".jpg","",$ico));
				$linkmaps .= "<td><a href='javascript:;' onclick='document.f.url.value=\"view.php?map=$n\"'><img src='maps/$ico' border=0 width=50 height=50 style='border:1px solid #cccccc' alt='$ico' title='$ico'></a></td>";
				$i++; if ($i % 3 == 0) $linkmaps .= "</tr><tr>";
		}
	?>
	
	<tr>
		<td valign='top' class='ne1' nowrap='nowrap' style="padding:5px;">
		
		<table width="100%">
			<tr><th colspan='2' class='rm_tit_section'><?php echo _("Upload New Indicator")?></th></tr>
			<tr>
				<td colspan='2' class='ne1' id="uploadform" style="display:none;">
					<form action="index.php" method='post' name='f2' enctype="multipart/form-data" onsubmit="return chk(document.f2)">
						<table id='rm_up_icon' width='100%'>
							<tr>
								<th><?php echo _("Name Icon")?>:</th>
								<td><input type='text' class='ne1' name='name'/></td>
							</tr>
							<tr>
								<th><?php echo _("Upload icon file")?>:</th>
								<td>
									<input type='file' class='ne1' size='15' name='fichero'/>
									<input type='hidden' value="<? echo $map ?>" name='map'>
								</td>
							</tr>
							<tr><td class='cont_submit' colspan='2'><input type='submit' value="<?= _("Upload") ?>" class="lbutton"/></td></tr>
						</table>
					</form>
				</td>
			</tr>
			
			<tr>
				<td>
				<?php
					$docroot = "/var/www/";
					$resolution = "128x128";
					$icon_cats = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/'`);
					
					echo "<select id='category' name='categories'>
							<option value=\"standard\">Default Icons</option>
							<option value=\"flags\">Country Flags</option>
							<option value=\"custom\">Own Uploaded</option>";
						
							foreach($icon_cats as $ico_cat)
							{
								if(!$ico_cat)continue;
								
								echo "<option value=\"$ico_cat\">$ico_cat</option>";
							}
					echo "</select>";

					/*
					$resolutions = array("16x16", "24x24", "32x32", "48x48", "72x72", "128x128", "256x256");
					print "<br/>";
					$i = 0;
					foreach($resolutions as $ress){
					print "<input type='radio' name='resolution2' value='$ress' ".($ress==$resolution ? "checked" : "")."><small>$ress</small>";
					$i++; if ($i % 3 == 0) echo "</br>";
					}*/
				?>
				</td>
			
				<td rowspan="2" align="center" valign="middle" width="40%">
					<img src="<?=(($uploaded_icon) ? $filename : "pixmaps/standard/default.png")?>" name="chosen_icon" id="chosen_icon"/>
				</td>
			</tr>
		
			<tr>
				<td align="left">
					<a href="chooser.php" title="Icon browser" class="greybox" style="font-size:12px"><?=_("Browse all")?></a>
					<span> / </span>
					<a href="javascript:loadLytebox()" id="lytebox_misc" title="Icon chooser" style="font-size:12px" rev="width: 400px; height: 300px;scrolling: no;"><?=_("Choose from list")?></a>
					<span> / </span>
					<a onclick="$('#uploadform').show();return false" style="font-size:12px"><?=_("Upload your own icon")?></a><br/>
				</td>
			</tr>
		</table>
		
		<div style="display:none">
			<form name="f" action="modify.php"><input type='hidden' name="alarm_id" value=""/> x <input type='text' size='1' name='posx'/> y <input type='text' size='1' name='posy'/> <input type='text' size='30' name='state' style="border:1px solid white;"/>
		</div>
				
		<table class='cont_icons' border="0" width="100%">		
			<tr><th class='rm_tit_section'><?php echo _("Icons")?></th></tr>
							
			<tr>
				<td class='bold'><?=_("Background")?>: 
					<select name="iconbg" id="iconbg">
						<option value=""><?=_("Transparent")?></option>
						<option value="white"><?=_("White")?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td class='bold'><?=_("Size")?>: 
					<select name="iconsize" id="iconsize">
						<option value="0"><?=_("Default")?></option>
						<option value="30"><?=_("Small")?></option>
						<option value="40"><?=_("Medium")?></option>
						<option value="50"><?=_("Big")?></option>
					</select>
				</td>
			</tr>
		</table>

	<?php
	if(0){
	?>
	 <table>
	 <!-- iconos -->
	 <tr><td class='ne1' colspan='2'>
		<table><tr>
		<?
			$ico_std = explode("\n",`ls -1 'pixmaps/standard'`);
			$i=0;
			foreach ($ico_std as $ico) if (trim($ico)!="") {
					if(is_dir("pixmaps/standard/" . $ico) || !getimagesize("pixmaps/standard/" . $ico)){ continue;}
				echo "<td><img src='pixmaps/standard/$ico' border=0></td><td align=center><input type=radio name=icon value='pixmaps/standard/$ico'".(($i==0) ? " checked" : "")."></td>";
				$i++; if ($i % 6 == 0) echo "</tr><tr>";
			}
			$ico_std = explode("\n",`ls -1 'pixmaps/uploaded'`);
			foreach ($ico_std as $ico) if (trim($ico)!="") {
					if(is_dir("pixmaps/uploaded/" . $ico) || !getimagesize("pixmaps/uploaded/" . $ico)){ continue;}
				echo "<td><img src='pixmaps/uploaded/$ico' border=0></td><td align=center><input type=radio name=icon value='pixmaps/uploaded/$ico'><br><a href='$SCRIPT_NAME?map=$map&delete_type=icon&delete=".urlencode("$ico")."'><img src='images/delete.png' border=0></a><a href=\"pixmaps/uploaded/$ico\" rel=\"lytebox[test]\" title=\"&lt;a href='javascript:alert(&quot;placeholder&quot;);'&gt;Click HERE!&lt;/a&gt;\">AAAAA</a></td>";
				$i++; if ($i % 6 == 0) echo "</tr><tr>";
			}		
		?>
		</tr></table>
	 </td></tr>
	 <?
	 } // end if(0)
	 ?>
 
 
 <!-- types -->
 <br/>
 <input type="hidden" name="type" id="type" value=""/>
 <input type="hidden" name="elem" id="elem" value=""/>
 
 <table width="100%">
	<tr>
		<td class='ne1'>
			<table width="100%" border="0" class="noborder">
				<tr><th colspan="2" class='rm_tit_section' valign="top"><?php echo _("Asset") ?></th></tr>
				
				<tr><td colspan="2" nowrap='nowrap'><div id="tree"></div></td></tr>

				<tr><td colspan="2" id="selected_msg"></td></tr>

				<tr>
					<td class='ne11' nowrap> <?= _("Indicator Name"); ?> </td>
					<td><input type='text' size='30' name="alarm_name" class='ne1'/></td>
				</tr>
				
				<tr id="linktoreport">
					<td colspan="2" class='ne11' nowrap='nowrap'>
						<?= _("Click to link to host/network report"); ?>&nbsp;&nbsp;
						<input type="checkbox" id="check_report" onclick="toggle_rm();"/></td>
				</tr>
				
				<tr id="linktomapurl">
					<td class='ne11'> <?= _("URL"); ?> </td>
					<td><input type='text' size='30' name="url" class='ne1'/></td>
				</tr>
				
				<tr id="linktomapmaps">
					<td class='ne1 bold'><i> <?= _("Choose map to link") ?> </i></td>
					<td><table><tr><? echo $linkmaps ?></tr></table></td>
				</tr>

				<tr>
					<td colspan="2" nowrap='nowrap'>
						<input type='button' value="<?= _("New Indicator") ?>" onclick="addnew('<? echo $map ?>','alarm')" class="lbutton" /> 
						<input type='button' value="<?= _("New Rect") ?>" onclick="addnew('<? echo $map ?>','rect')" class="lbutton"/> 
						<input type='button' value="<?= _("Save Changes") ?>" onclick="save('<? echo $map ?>')" class="lbutton"/>
					</td>
				</tr>	
			</table>
		</td>
	</tr>
	
	<tr><td id="tdnuevo"></td></tr>
</table>
<?
	// Get Host, Sensor, Net lists to check user perms
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn,true);
	$nets = Net::get_list($conn);
	$query = "select * from risk_indicators where name <> 'rect' AND map= ?";
	$params = array($map);
	if (!$rs = &$conn->Execute($query, $params)) {
		print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF) {
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
			if (!$in_assets) {
				echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datatype".$rs->fields["id"]."\" id=\"datatype".$rs->fields["id"]."\" value=\"".$rs->fields["type"]."\">\n";
				echo "<input type=\"hidden\" name=\"type_name".$rs->fields["id"]."\" id=\"type_name".$rs->fields["id"]."\" value=\"".$rs->fields["type_name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
				echo "<input type=\"hidden\" name=\"dataicon".$rs->fields["id"]."\" id=\"dataicon".$rs->fields["id"]."\" value=\"".preg_replace("/\#.*/","",$rs->fields["icon"])."\">\n";
				echo "<input type=\"hidden\" name=\"dataiconsize".$rs->fields["id"]."\" id=\"dataiconsize".$rs->fields["id"]."\" value=\"".$rs->fields["size"]."\">\n";
				echo "<input type=\"hidden\" name=\"dataiconbg".$rs->fields["id"]."\" id=\"dataiconbg".$rs->fields["id"]."\" value=\"".((preg_match("/\#(.+)/",$rs->fields["icon"],$found)) ? $found[1] : "")."\">\n";
				echo "<div id=\"alarma".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"background:url(../pixmaps/1x1.png);visibility:hidden;position:absolute;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
				echo "<table border=0 cellspacing=0 cellpadding=1 style=\"background-color:$bgcolor\"><tr><td colspan=2 class=ne align=center><i>".$rs->fields["name"]."</i></td></tr><tr><td><img src=\"".preg_replace("/\#.+/","",str_replace("//","/",$rs->fields["icon"]))."\" width=\"".$size."\" height=\"".$size."\" border=0></td><td>";
				echo "<table border=0 cellspacing=0 cellpadding=1style=\"background:url(../pixmaps/1x1.png);\"><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td></tr></table>";
				echo "</td></tr></table></div>\n";
			}
			if (!$has_perm) { $rs->MoveNext(); continue; }
			echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
			echo "<input type=\"hidden\" name=\"datatype".$rs->fields["id"]."\" id=\"datatype".$rs->fields["id"]."\" value=\"".$rs->fields["type"]."\">\n";
			echo "<input type=\"hidden\" name=\"type_name".$rs->fields["id"]."\" id=\"type_name".$rs->fields["id"]."\" value=\"".$rs->fields["type_name"]."\">\n";
			echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
			echo "<input type=\"hidden\" name=\"dataicon".$rs->fields["id"]."\" id=\"dataicon".$rs->fields["id"]."\" value=\"".preg_replace("/\#.*/","",$rs->fields["icon"])."\">\n";
			echo "<input type=\"hidden\" name=\"dataiconsize".$rs->fields["id"]."\" id=\"dataiconsize".$rs->fields["id"]."\" value=\"".$rs->fields["size"]."\">\n";
			echo "<input type=\"hidden\" name=\"dataiconbg".$rs->fields["id"]."\" id=\"dataiconbg".$rs->fields["id"]."\" value=\"".((preg_match("/\#(.+)/",$rs->fields["icon"],$found)) ? $found[1] : "")."\">\n";
			echo "<div id=\"alarma".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"background:url(../pixmaps/1x1.png);visibility:hidden;position:absolute;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
			echo "<table border=0 cellspacing=0 cellpadding=1 style=\"background-color:$bgcolor\"><tr><td colspan=2 class=ne align=center><i>".$rs->fields["name"]."</i></td></tr><tr><td><img src=\"".preg_replace("/\#.+/","",str_replace("//","/",$rs->fields["icon"]))."\" width=\"".$size."\" height=\"".$size."\" border=0></td><td>";
			echo "<table border=0 cellspacing=0 cellpadding=1 style=\"background:url(../pixmaps/1x1.png);\"><tr><td class=ne11>R</td><td class=ne11>V</td><td class=ne11>A</td></tr><tr><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td><td><img src='images/b.gif' border=0></td></tr></table>";
			echo "</td></tr></table></div>\n";
			$rs->MoveNext();
		}
	}
	$query = "select * from risk_indicators where name='rect' AND map = ?";
	$params = array($map);

	if (!$rs = &$conn->Execute($query, $params)) {            
	print $conn->ErrorMsg();
	} else {
		while (!$rs->EOF) {
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
				echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
				echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
				echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"position:absolute;background:url(../pixmaps/1x1.png);visibility:visible;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
                echo "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>";
				echo "<table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table>";
				echo "</div>\n";
				$rs->MoveNext(); continue;
			}
			if (!$has_perm) { $rs->MoveNext(); continue; }
			echo "<input type=\"hidden\" name=\"dataname".$rs->fields["id"]."\" id=\"dataname".$rs->fields["id"]."\" value=\"".$rs->fields["name"]."\">\n";
			echo "<input type=\"hidden\" name=\"datanurl".$rs->fields["id"]."\" id=\"dataurl".$rs->fields["id"]."\" value=\"".$rs->fields["url"]."\">\n";
			echo "<div id=\"rect".$rs->fields["id"]."\" class=\"itcanbemoved\" style=\"position:absolute;background:url(../pixmaps/1x1.png);visibility:visible;left:".$rs->fields["x"]."px;top:".$rs->fields["y"]."px;height:".$rs->fields["h"]."px;width:".$rs->fields["w"]."px\">";
            echo "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border=0></div>";
			echo "<table border=0 cellspacing=0 cellpadding=0 width=\"100%\" height=\"100%\"><tr><td style=\"border:1px dotted black\">&nbsp;</td></tr></table>";
			echo "</div>\n";
			$rs->MoveNext();
		}
	}
	
	$conn->close();
?>
<?php

$uploaded_dir = "pixmaps/uploaded/";
$uploaded_link = "pixmaps/uploaded/";

$icons = explode("\n",`ls -1 '$uploaded_dir'`);
print "<div style=\"display:none;\">";
$i = 0;
foreach($icons as $ico){
if(!$ico)continue;
if(is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)){ continue;}
print "<a href=\"$uploaded_link/$ico\" id=\"custom-$i\" rel=\"lytebox[custom]\" title=\"&lt;a href='javascript:choose_icon(&quot;$uploaded_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">custom</a>";
$i++;
}

$uploaded_dir = "pixmaps/flags/";
$uploaded_link = "pixmaps/flags/";

$icons = explode("\n",`ls -1 '$uploaded_dir'`);
print "<div style=\"display:none;\">";
$i = 0;
foreach($icons as $ico){
if(!$ico)continue;
if(is_dir($uploaded_dir . "/" .  $ico) || !getimagesize($uploaded_dir . "/" . $ico)){ continue;}
print "<a href=\"$uploaded_link/$ico\" id=\"flags-$i\" rel=\"lytebox[flags]\" title=\"&lt;a href='javascript:choose_icon(&quot;$uploaded_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">flags</a>";
$i++;
}


$standard_dir = "pixmaps/standard/";
$standard_link = "pixmaps/standard/";

$icons = explode("\n",`ls -1 '$standard_dir'`);
print "<div style=\"display:none;\">";
$i = 0;
foreach($icons as $ico){
if(!$ico)continue;
if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico)){ continue;}
print "<a href=\"$standard_link/$ico\" id=\"standard-$i\" rel=\"lytebox[standard]\" title=\"&lt;a href='javascript:choose_icon(&quot;$standard_link/$ico&quot;);'&gt;" . htmlspecialchars(_("Choose this one")) . ".&lt;/a&gt;\">standard</a>";
$i++;
}

print "</div>\n";

/*
$docroot = "/var/www/";
$resolution = "128x128";
$icon_cats = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/'`);
foreach($icon_cats as $ico_cat){
if(!$ico_cat)continue;
$icons = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/$ico_cat/$resolution/'`);
print "<div style=\"display:none;\">";
$i = 0;
foreach($icons as $ico){
if(is_dir("$docroot/ossim_icons/Regular/$ico_cat/$resolution/$ico") || !getimagesize("$docroot/ossim_icons/Regular/$ico_cat/$resolution/$ico")){ continue;}
if(!$ico)continue;
print "<a href=\"/ossim_icons/Regular/$ico_cat/$resolution/$ico\" id=\"$ico_cat-$i\" rel=\"lytebox[$ico_cat]\" title=\"&lt;a href='javascript:choose_icon(&quot;/ossim_icons/Regular/$ico_cat/RESOLUTION/$ico&quot;);'&gt;Choose this one.&lt;/a&gt;\">$ico_cat</a>";
$i++;
}
print "</div>\n";
}
*/
?>

		</form>
	</td>

	<td width="48" valign='top'>
		<img src='images/wastebin.gif' id="wastebin" border='0'/>
	</td>
	<td valign='top' id="map">
		<img src="maps/map<? echo $map ?>.jpg" id="map_img" border='0'/>
	</td>
</tr>
</table>


</body>
</html>
