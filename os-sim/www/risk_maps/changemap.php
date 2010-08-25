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
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/User_config.inc';

$db = new ossim_db();
$conn = $db->connect();
$map = (POST("map") != "") ? POST("map") : ((GET("map") != "") ? GET("map") : (($_SESSION["riskmap"]!="") ? $_SESSION["riskmap"] : 1));
$name = POST('name');
$erase_element = GET('delete');
$setdefault = GET('default');

ossim_valid($erase_element, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("erase_element"));
ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%", 'illegal:'._("name"));
ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));
ossim_valid($setdefault, OSS_DIGIT, OSS_NULLABLE, 'illegal:'._("default"));
if (ossim_error()) {
	die(ossim_error());
}

$config = new User_config($conn);
$login = Session::get_session_user();

if ($setdefault != "") {
	$config->set($login, "riskmap", $setdefault, 'simple', "main");
}

$default_map = $config->get($login, "riskmap", 'simple', 'main');
//
if (is_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'])) {
	$filename = "maps/" . $name . ".jpg";
	if(getimagesize($HTTP_POST_FILES['ficheromap']['tmp_name'])){
		move_uploaded_file($HTTP_POST_FILES['ficheromap']['tmp_name'], $filename);
	}
}
if ($erase_element != "") {
	if (getimagesize("maps/".$erase_element)) {
		unlink("maps/" . $erase_element);
		$_SESSION["riskmap"] = $map = 1;
	}
}
?>
<html>
<head>
<title><?= _("Alarms") ?> - <?= _("View")?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="./custom_style.css">
<style type="text/css">
	.itcanbemoved { position:absolute; }
</style>
</head>
<body leftmargin=5 topmargin=5 class="ne1">
 <?
	$maps = explode("\n",`ls -1 'maps' | grep -v CVS`);
	$i=0; $n=0; $txtmaps = ""; $mn=-1;
	foreach ($maps as $ico) if (trim($ico)!="") {
		if(!getimagesize("maps/" . $ico)) { continue;}
		$n = str_replace("map","",str_replace(".jpg","",$ico));
		$defaultborder = ($n == $default_map) ? " style='text-decoration:italic'" : " style='font-weight:bold;font-size:12px'";
		$deftxt = ($n == $default_map) ? _("DEFAULT MAP") : _("Set as Default");
		if (intval($n)>$mn) $mn=intval($n);
		$txtmaps .= "<td><a href='view.php?map=$n'><img src='maps/$ico' border=".(($map==$n) ? "2" : "0")." width=150 height=150></a>";
		$txtmaps .= "<a href='changemap.php?map=$map&delete=".urlencode("$ico")."' title='"._("Delete map")."'><img src='images/delete.png' border=0></a><br><a href='changemap.php?map=$map&default=$n' class='ne'$defaultborder>$deftxt</a></td>";
		$i++; if ($i % 5 == 0) {
			$txtmaps .= "</tr><tr>";
		}
	}
 ?> 
 <table align="center">
 <tr><td class="ne1" align="center" colspan="5">
 <form action="changemap.php" method=post name=f1 enctype="multipart/form-data">
 <?= _("Upload map file") ?>: <input type=hidden value="<? echo $map ?>" name=map>
 <input type=hidden name=name value="map<? echo ($mn+1) ?>"><input type=file class=ne1 size=15 name=ficheromap>
 <input type=submit value="<?= _("Upload") ?>" class="btn" style="font-size:12px">
 </form>
 </td></tr>
 <tr><? echo $txtmaps ?></tr>
 </table>
</body>
</html>
