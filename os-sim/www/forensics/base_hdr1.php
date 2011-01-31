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
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if (GET('withoutmenu') != "1") include ("../hmenu.php");
$uri = $_SERVER['REQUEST_URI'];
$actual_url = str_replace("?clear_allcriteria=1&","?",str_replace("&clear_allcriteria=1","",$uri)).(preg_match("/\?.*/",$uri) ? "&" : "?");
?>
<table width="100%"><tr>
	<td valign="top">
		<table border=0 cellpadding=0 cellspacing=0>
		<?
		if (count($database_servers)>0 && Session::menu_perms("MenuConfiguration", "PolicyServers") && preg_match("/pro|demo/i",$version)) { 
			// session server
			$ss = (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="") ? $_SESSION["server"][0] : "local";
			echo "<tr><td align='left'><img src='../server/getdbsicon.php?name=".urlencode($ss)."' border=0 width='32' height='32'><a href='javascript:;' onclick='$(\"#dbs\").toggle();$(\"#imgplus\").attr(\"src\",(($(\"#imgplus\").attr(\"src\").match(/plus/)) ? \"images/minus-small.png\" : \"images/plus-small.png\"))'><img src='images/plus-small.png' border=0 id='imgplus'></a></td></tr>";
			echo "<tr style='display:none' id='dbs'><td colspan=2 style='border:1px solid #CCCCCC'><table border=0 cellpadding=1 cellspacing=0>";
			foreach ($database_servers as $db) {
				$svar = base64_encode($db->get_ip().":".$db->get_port().":".$db->get_user().":".$db->get_pass());
				echo "<tr bgcolor='#EEEEEE'><td><img src='../server/getdbsicon.php?name=".urlencode($db->get_ip())."' border=0 width='16' height='16'></td>";
				$name = ($ss==$db->get_ip()) ? "<b>".$db->get_name()."</b>" : $db->get_name(); 
				echo "<td><a href='".$actual_url."server=$svar'>".$name."</a></td></tr>";
			}
			echo "<tr bgcolor='#EEEEEE'><td><img src='../server/getdbsicon.php?name=local' border=0 width='16' height='16'></td>";
			echo "<td><a href='".$actual_url."server=local'>".($ss=="local" ? "<b>"._("Local")."</b>" : _("local"))."</a></td></tr>";
			echo "</table></td></tr>";
		}
		?>
		</table>
	</td>
	<td align="center">
		<table><tr>
		<td>
			<div id="plotareaglobal" class="plot" style="text-align:center;margin:0px 15px 0px 0px;display:none;"></div>
		</td>
		<td style="padding:0px 0px 10px 20px">
			<form style="margin:0px" action="../control_panel/event_panel.php" method="get">
			<input type="submit" class="button" value="<?php echo gettext("Real Time")?>" name="submit" style="font-weight:bold">
			</form>
		</td>
		</tr>
		</table>
	</td>
</tr>
</table>
