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
* - html_service_level()
* - global_score()
* Classes list:
*/
?>
<tr>
	<td id="ossimlogo" style="background:url('../pixmaps/top/bg_header.gif') repeat-x bottom left;height:60">
		<table border=0 cellpadding=0 cellspacing=0 height="60">
		<tr>
			<td style="padding-left:10px"><img src="../pixmaps/top/logo<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" border='0'></td>
			<td align="right" style="padding-left:10px">
				<table border=0 cellpadding=2 cellspacing=0 align="center" width="100%">
				<tr>
				
					<td <?=($screen=="status") ? "class='underline'" : ""?>><a href="?screen=status"><img src="chart_area.png" border="0"></a></td>

					<? if (Session::menu_perms("MenuIncidents", "ReportsAlarmReport")) { ?>
					<td <?=($screen=="alarms") ? "class='underline'" : ""?>><a href="?screen=alarms&range=365"><img src="alarm_check.png" border="0"></a></td>
					<? } ?>
					
					<? if (Session::menu_perms("MenuIncidents", "IncidentsReport")) { ?>
					<td <?=($screen=="tickets") ? "class='underline'" : ""?>><a href="?screen=tickets"><img src="tag.png" border="0"></a></td>
					<? } ?>
					
				</tr>
				</table>
			</td>
		</tr>
	  </table>
	</td>
</tr>
