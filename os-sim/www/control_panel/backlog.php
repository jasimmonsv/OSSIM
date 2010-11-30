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
Session::logcheck("MenuIntelligence", "CorrelationBacklog");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("Control Panel"); ?> </title>
  <meta http-equiv="refresh" content="150">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>

<body>

<?php
include ("../hmenu.php"); ?>

<center><small><?php echo _("The backlog contains all those directives matched who either haven't reached the last correlation level or haven't timed out yet") ?></small></center>
<br/>

<?php
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Backlog.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Util.inc');
require_once ('classes/Security.inc');
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();

$sensor_where = "";
if (Session::allowedSensors() != "") {
	$user_sensors = explode(",",Session::allowedSensors());
	foreach ($user_sensors as $user_sensor) if ($user_sensor != "")
		$sensor_str .= (($sensor_str != "") ? "," : "")."'".$user_sensor."'";
	if ($sensor_str == "") $sensor_str = "0";
	$sensor_where = " event.sensor in (" . $sensor_str . ")";
}

if ($sensor_where != "")
	$query = "select plugin_sid.name as Name, directive_id as Directive,  count(*) as Count from backlog, plugin_sid, backlog_event, event where backlog.directive_id = plugin_sid.sid and plugin_sid.plugin_id = 1505 AND backlog.id=backlog_event.backlog_id AND backlog_event.event_id = event.id AND $sensor_where group by directive_id order by Count desc;";
else
	$query = "select plugin_sid.name as Name, directive_id as Directive,  count(*) as Count from backlog, plugin_sid where backlog.directive_id = plugin_sid.sid and plugin_sid.plugin_id = 1505 group by directive_id order by Count desc;";

//echo $query;
	
if (!$rs = & $conn->Execute($query)) {
    print $conn->ErrorMsg();
} else {
?>
    <table width="100%">
<tr><th><?php echo _("Directive Name") ?></th><th><?php echo _("Directive Id"); ?></th><th><?php echo _("Count") ?></th><th><?php echo _("Edit"); ?></tr>
<?php
    while (!$rs->EOF) {
        list($waste, $directive_name) = split(":", $rs->fields["Name"], 2);
?>
      <tr>
        <td><?php echo $directive_name
?> </td>
        <td><?php echo $rs->fields["Directive"]; ?> </td>
        <td><?php echo $rs->fields["Count"]; ?> </td>
        <td><a href="../directive_editor/index.php?hmenu=Directives&smenu=Directives&level=1&directive=<?php echo $rs->fields["Directive"];?>"><?=_("View/Edit current directive definition")?></a></td>
      </tr>
<?php
        $rs->MoveNext();
    }
}
?>
    </table>
<?php
$db->close($conn);
?>

</body>
</html>
