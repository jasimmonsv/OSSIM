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
Session::logcheck("MenuReports", "ReportsHostReport");
?>

<html>
<head>
<title> OSSIM </title>
</head>

<?php
require_once 'classes/Security.inc';
$host = GET('host');
if (preg_match("/\/\d+/",$host))
	ossim_valid($host, OSS_IP_CIDR, 'illegal:' . _("Host"));
else
	ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("Host"));
if (ossim_error()) {
    die(ossim_error());
}
$height = (GET('section')=='network') ? 1 : 1;
$report = (GET('section')=='network') ? "Network+Report" : "Host+Report";
?>

<frameset rows="<?=$height?>,*" border="0" frameborder="0">
<frame src="top.php?hmenu=Host+Report&smenu=<?=$report?>&host=<?php echo $host; ?>"> -->

<?php
/* inventory */
if (!strcmp(GET('section') , 'inventory')) {
    echo "<frame src=\"inventory.php?host=" . $host . "&origin=passive\" name=\"report\">";
}
/* metrics */
elseif (!strcmp(GET('section') , 'metrics')) {
    echo "<frame src=\"metrics.php?host=" . $host . "\" name=\"report\">";
}
/* events */
elseif (!strcmp(GET('section') , 'events')) {
    require_once ('ossim_conf.inc');
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $acid_main_link = $conf->get_conf("acid_link") . "/$acid_prefix" . "_stat_ipaddr.php?ip=$host&netmask=32";
    echo "<frame src=\"" . $acid_main_link . "\" name=\"report\">";
}
/* ntop */
elseif (!strcmp(GET('section') , 'usage')) {
    require_once ('ossim_db.inc');
    require_once ('classes/Sensor.inc');
    $db = new ossim_db();
    $conn = $db->connect();
    $ntop_link = Sensor::get_sensor_link($conn, $host);
    $db->close($conn);
    echo "<frame src=\"$ntop_link/" . $host . ".html\" name=\"report\">";
}
/* default */
else {
    echo "<frame src=\"host_report.php?host=" . $host . "\" name=\"report\">";
}
?>

</frameset>
<body>
</body>
</html>

