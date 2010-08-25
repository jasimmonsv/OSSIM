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
Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) {
print _("You don't have permissions to edit risk indicators");
exit();
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="../style/style.css">
</head>
<body style="text-align:center">
<?php
/*
$docroot = "/var/www/";
$resolution = "128x128";
$icon_cats = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/'`);

print "<ul>";
foreach($icon_cats as $ico_cat){
if(!$ico_cat)continue;
$icons = explode("\n",`ls -1 '$docroot/ossim_icons/Regular/$ico_cat/$resolution/'`);
print "<li><a href=\"/ossim_icons/Regular/$ico_cat/$resolution/" . $icons[0] . "\" rel=\"lyteshow[$ico_cat]\" target=\"_top\">$ico_cat</a>";
print "<div style=\"display:none\">";
foreach($icons as $ico){
print "<a href=\"/ossim_icons/Regular/$ico_cat/$resolution/$ico\" rel=\"lyteshow[$ico_cat]\" target=\"_top\">$ico_cat</a>";
}
print "</div>";
}
print "</ul>\n";
*/
require_once 'classes/Security.inc';
$dir = $_GET['dir'];
ossim_valid($dir, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("dir"));

if (ossim_error()) {
	die(ossim_error());
}
$standard_dir = "pixmaps/standard/";
if ($dir=="custom") $standard_dir = "pixmaps/uploaded/";
if ($dir=="flags") $standard_dir = "pixmaps/flags/";
$icons = explode("\n",`ls -1 '$standard_dir'`);
$i = 0;
foreach($icons as $ico){
  if(!$ico)continue;
  if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico)){ continue;}
  $ico2 = preg_replace("/\..*/","",$ico);
  print "<a href=\"javascript:parent.choose_icon('$standard_dir/$ico')\" title=\"Click to choose $ico2\"><img src=\"$standard_dir/$ico\" style='margin:10px' border=0></a>";
}
?>
</body>
</html>
