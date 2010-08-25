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
Session::logcheck("MenuControlPanel", "ControlPanelAlarms");
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');
require_once ('classes/AlarmGroup.inc');
$db = new ossim_db();
$conn = $db->connect();
$alarms = GET('alarm');
ossim_valid($alarms, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("alarm"));
list($alarm_group, $count) = AlarmGroup::get_list($conn, "", "", "", "ORDER BY timestamp DESC");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> Control Panel </title>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" href="../style/style.css"/>
</head>

<body>
<table cellpadding=0 cellspacing=0 border=0 class="noborder" width="100%">
<form name="fgroup" action="alarm_group_console.php" method="get" target="_parent">
	<input name="alarm" type="hidden" value="<?php echo $alarms
?>">
	<input name="action" type="hidden" value="group_alarm">
	<tr><td class="nobborder" style="text-align:center">Group alarms into an <b>existing group</b>:</td></tr>
	<tr><td class="nobborder" style="padding-top:15px;text-align:center">
		<select name="group">
			<?php
foreach($alarm_group as $group) { ?>
			<option value="<?php echo $group->get_group_id() ?>">G<?php echo $group->get_group_id() ?>
			<?php
} ?>
		</select>
		</td>
	</tr>
	<tr><td class="nobborder" style="padding-top:5px;text-align:center"><input type="submit" value="OK" class="btn"></td></tr>
</form>
	<!--
	<tr><td class="nobborder" style="padding-top:10px"><hr></td></tr>
	<tr><td class="nobborder" style="padding-top:15px;text-align:center">Group alarms into a <b>new group</b>:</td></tr>
	<tr><td class="nobborder" style="text-align:center;padding-top:5px"><input type="button" value="Create and Group alarms" class="btn"></td></tr>
	-->
</table>
</body>
