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
* - incidents_by_status_table()
* - incidents_by_type_table()
* - incidents_by_user_table()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "IncidentsReport");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <style type="text/css">body,html {  height:"100%" }</style>
</head>
<body>
<?php
include ("../hmenu.php");
require_once ('ossim_db.inc');
require_once ('classes/Incident.inc');
$db = new ossim_db();
$conn = $db->connect();
echo "<br>";
echo "<center>";
echo "<table class=\"nobborder\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color:#FFFFFF;\">";
echo "<tr><td valign=\"top\" class=\"nobborder\">";
incidents_by_status_table($conn);
echo "</td><td width=\"15\" class=\"nobborder\">&nbsp;</td><td valign=\"top\" class=\"nobborder\">";
incidents_by_user_table($conn);
echo "</td><td width=\"15\" class=\"nobborder\">&nbsp;</td>";
echo "<td valign=\"top\" class=\"nobborder\">";
incidents_by_type_table($conn);
echo "</td></tr>";
echo "</table>";
echo "</center>";
function incidents_by_status_table($conn) {
    $list = Incident::incidents_by_status($conn);
    if (count($list)>0) {
?>

    <table align="center" width="270" cellpadding="0" cellspacing="0" class="noborder">
        <tr><td class="headerpr"><?php echo gettext("Tickets by status");?></td></tr>
    </table>
    <table align="center" width="270" height="515">
      <tr>
        <th><?php
    echo gettext("Ticket Status") ?></th>
        <th><?php
    echo gettext("Ocurrences") ?></th>
      </tr>
<?php
        foreach($list as $l) {
            $status = $l[0];
            $occurrences = $l[1];
?>
      <tr>
        <td><?php
            Incident::colorize_status($status) ?></td>
        <td><?php
            echo $occurrences ?></td>
      </tr>
<?php
        }
    } else {
        echo _("No Data Available")."</td></tr></table></center></body></html>";
        exit(0);
    }
?>
      <tr height="100%">
        <td colspan="2" class="nobborder" height="100%" valign="top">
        <iframe src="graphs/incidents_pie_graph.php?by=status" frameborder="0" style="width:230px;height:400px;"></iframe>
          <!--<img src="graphs/incidents_pie_graph.php?by=status"
               alt="<?//=_("Ticket by status graph")?>"/>-->
        </td>
      </tr>
    </table>
    <br/>
<?php
}
function incidents_by_type_table($conn) {
?>
    <table align="center" width="270" cellpadding="0" cellspacing="0" class="noborder">
        <tr><td class="headerpr"><?php echo gettext("Tickets by type"); ?></td></tr>
    </table>
    
    <table align="center" width="270" height="515">
      <tr>
        <th><?php
    echo gettext("Ticket type") ?></th>
        <th><?php
    echo gettext("Ocurrences") ?></th>
      </tr>
<?php
    if ($list = Incident::incidents_by_type($conn)) {
        foreach($list as $l) {
            $type = $l[0];
            $occurrences = $l[1];
?>
      <tr>
        <td style="text-align:left;"><?php
            echo $type ?></td>
        <td><?php
            echo $occurrences ?></td>
      </tr>
<?php
        }
    }
?>
      <tr height="100%">
        <td colspan="2" class="nobborder" height="100%" valign="top">
        <iframe src="graphs/incidents_pie_graph.php?by=type" frameborder="0" style="width:230px;height:400px;"></iframe>
        <!--<img src="graphs/incidents_pie_graph.php?by=type"
               alt="<?//=_("Tickets by type graph")?>"/>-->
        </td>
      </tr>
    </table>
    <br/>
<?php
}
function incidents_by_user_table($conn) {
?>
    <table align="center" width="270" cellpadding="0" cellspacing="0" class="noborder">
        <tr><td class="headerpr"><?php echo gettext("Tickets by user in charge"); ?></td></tr>
    </table>
    
    <table align="center" width="270" height="515">
      <tr>
        <th><?php
    echo gettext("User in charge") ?></th>
        <th><?php
    echo gettext("Ocurrences") ?></th>
      </tr>
<?php
    if ($list = Incident::incidents_by_user($conn)) {
        foreach($list as $l) {
            $user = $l[0];
            $occurrences = $l[1];
?>
      <tr>
        <td><?php
            echo $user ?></td>
        <td><?php
            echo $occurrences ?></td>
      </tr>
<?php
        }
    }
?>
      <tr height="100%">
        <td colspan="2" class="nobborder" height="100%" valign="top">
        <iframe src="graphs/incidents_pie_graph.php?by=user" frameborder="0" style="width:230px;height:400px;"></iframe>
          <!--<img src="graphs/incidents_pie_graph_new.php?by=user"
               alt="<?//=_("Tickets by user graph")?>"/>-->
        </td>
      </tr>
    </table>
    <br/>
<?php
}
?>
<br/>
<center>
<table class="noborder" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;">
<tr>
    <td class="nobborder">
        <center>
        <table align="center" width="840" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr"><?php echo _("Closed Tickets By Month") ?></td></tr>
        </table>
        <table width="840" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;">
            <tr><td class="noborder"><img src="graphs/incidents_bar_graph.php?by=monthly_by_status"
           alt="<?=_("Num Tickets closed by month")?>"/></td></tr>
        </table>
        </center>
    </td>
</tr>
<tr>
    <td height="20" class="nobborder"></td>
</tr>
<tr>
    <td class="nobborder">
        <center>
        <table align="center" width="840" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr"><?php echo _("Ticket Resolution Time"); ?></td></tr>
        </table>
        <table width="840" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;">
            <tr><td class="noborder"><img src="graphs/incidents_bar_graph.php?by=resolution_time"
           alt="<?=_("Tickets by resolution time")?>"/></td></tr>
        </table>
        </center>
    </td>
</tr>
</table>
</center>
<br><br>
</body>
</html>
