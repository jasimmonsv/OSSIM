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
Session::logcheck("MainMenu", "Index", "session/login.php");
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$ossim_link = $conf->get_conf("ossim_link");
// product version check
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;
?>
<html>
<head>
<title> <?php echo gettext("AlienVault - ".($opensource ? "Open Source SIEM" : ($demo ? "Unified SIEM Demo" : "Unified SIEM"))); ?> </title>
<link rel="Shortcut Icon" type="image/x-icon" href="favicon.ico">
<link rel="stylesheet" type="text/css" href="/ossim/style/style.css"/>
<script>
var newwindow;
function new_wind(url,name)
{
	newwindow=window.open(url,name,'height=768,width=1024,scrollbars=yes');
	if (window.focus) {newwindow.focus()}
}
</script>
</head>
<body>

<table align="center" style="padding:2px;background-color:#f2f2f2;border-color:#aaaaaa" class="nobborder">
<tr><td class="nobborder">
	<table cellpadding=0 cellspacing=0 border=0 align="center" class="nobborder">
		<tr>
			<td style="padding-top:20px;padding-left:10px;text-align:center" class="nobborder">
				<a href="/ossim"><img src="/ossim/pixmaps/ossim<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" alt="open source SIM logo" border="0" /></a>
			</td>
		</tr>
		<tr>
			<td style="padding-top:20px;padding-right:10px;font-color:darkgray" align="center">

				<font style='font-size:200%'><?=_("We apologize")?></font>:<br>
				<font style='font-size:120%'><b><?=_("The page you are trying to view does not exists")?>.</b></font><br>
				<hr style="height:1px;border:none;background-color:#D5D5D5;color:#D5D5D5"><br>
				<p align="justify" style="font-color:darkgray">
				<?=_("You may try to open <b>AlienVault</b> home page, our categories index or our search engine to find the page you are looking for.")?><br><br>
				<?=_("If you have any doubts about how to navigate through <b>AlienVault</b> visit our")?> <a href="javascript:new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:control_panel','Dashboard Help');" class=na11>Help</a>
				</p>
			</td>		
		</tr>
	</table>
</td></tr>
</table>

</body>
</html>

