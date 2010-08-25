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
Session::logcheck("MenuControlPanel", "ControlPanelHids");
?>

<html>
<head>
  <title><?php echo _("OSSIM Framework") ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
                                                                                
  <h1><?php echo _("Host IDS") ?></h1>

<?php
require_once ('ossim_db.inc');
require_once ('ossim_sql.inc');
require_once ('ossim_error.inc');
require_once ('classes/Host_ids.inc');
$db = new ossim_db();
$conn = $db->connect();
?>
<div align="center">
<img src="hids_graph.php?limit=10">
</div>
<br>
<hr noshade>
<br>
<table align="center" width="80%">
<tr>
<th> <?php echo _("Host") ?> </th><th> <?php echo _("Event date") ?> </th><th> <?php echo _("Events"); ?> </th>
</tr>
<?php
if ($host_ids_list = Host_ids::get_list_reduced($conn, "", "group by ip order by 'count(sid)' desc ")) {
    foreach($host_ids_list as $host) {
        $ip = $host->get_ip();
        $date = $host->get_date();
        $count = $host->get_count();
        printf("<TR><TH>
        <A HREF=\"host_detail.php?ip=$ip&date=$date\">$ip</A></TH>
        <TD>$date</TD><TD>$count</TD></TR>");
    }
}
?>
</table>
</body>
</html>
<?php
$db->close($conn);
exit();
?>
