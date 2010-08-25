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
header('Pragma: public_no_cache');
session_cache_limiter('public_no_cache');
require_once ('classes/Session.inc');
require_once 'classes/Security.inc';
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('../graphs/charts.php');
$gr = $_GET["gr"] ? $_GET["gr"] : "";
ossim_valid($gr, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, 'illegal:'._("gr"));
if (ossim_error()) {
	die(ossim_error());
}
$w = (is_numeric($_GET["w"])) ? $_GET["w"] : 250;
$h = (is_numeric($_GET["h"])) ? $_GET["h"] : 250;
$gr.= "&uid=" . uniqid(rand() , true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head><title><?=_("Chart")?></title></head>
<body topmargin=0 leftmargin=0 marginwidth=0 marginheight=0 scroll=no>
<div style="position:absolute;left:0px;top:0px">
<?php
$start = $_SESSION["forensic_start"];
$end = $_SESSION["forensic_end"];
$dif = strtotime($end) - strtotime($start);
if ($dif <= 86400 || $w >= 250) echo InsertChart("charts.swf?timeout=600", "../graphs/charts_library", $gr, $w, $h, "#FFF", true);
?>
</div>
</body>
</html>
