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
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Sensor_interfaces.inc');
require_once ('classes/Host_vulnerability.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Util.inc');
require_once 'classes/Security.inc';
$host = GET('host');
ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("Host"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$conf = $GLOBALS["CONF"];
$ip_slashed = str_replace(".", "/", $host);
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$acid_main_link = str_replace("//", "/", $conf->get_conf("acid_link") . "/" . $acid_prefix . "_stat_ipaddr.php?ip=$host&netmask=32&clear_allcriteria=1&withoutmenu=1");
$interface = $conf->get_conf("ossim_interface");
?>

<br/>
&nbsp;<font style="font-size: 12pt; font-weight: bold;">
<?php
echo gettext("Host Report"); ?>
</font><br/><br/>

&nbsp;&nbsp;<a href="inventory.php?host=<?php
echo $host
?>&origin=passive" target="report">
    <?php
echo gettext("Inventory"); ?> </a><br/><br/>
    
<?php
if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) { ?>

&nbsp;&nbsp;<a href="metrics.php?host=<?php
    echo $host
?>" target="report">
    <?php
    echo gettext("Metrics"); ?> </a><br/><br/>

<?php
} // Sesion::menu_perms("MenuControlPanel", "ControlPanelMetrics")
 ?>
    
<?php
if (Session::menu_perms("MenuControlPanel", "ControlPanelAlarms")) { ?>

&nbsp;&nbsp;<b> <?php
    echo gettext("Alarms"); ?> </b><br/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../control_panel/alarm_console.php?withoutmenu=1&src_ip=<?php
    echo $host
?>&dst_ip=<?php
    echo $host ?>" 
    target="report">
    <?php
    echo gettext("Source or Dest"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../control_panel/alarm_console.php?withoutmenu=1&src_ip=<?php
    echo $host
?>" target="report">
    <?php
    echo gettext("Source"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../control_panel/alarm_console.php?withoutmenu=1&dst_ip=<?php
    echo $host
?>" target="report">
    <?php
    echo gettext("Destination"); ?> </a><br/><br/>
    
<?php
} // Sesion::menu_perms("MenuControlPanel", "ControlPanelAlarms")
 ?>

<?php
if (Session::menu_perms("MenuControlPanel", "ControlPanelEvents")) { ?>

&nbsp;&nbsp;<b> <?php
    echo gettext("Events"); ?> </b><br/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php
    echo $acid_main_link ?>"
    target="report">
    <?php
    echo gettext("Main"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php
    echo str_replace("//", "/", "$acid_link/" . $acid_prefix . "_stat_alerts.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr[0][1]=ip_src&ip_addr[0][2]==&ip_addr[0][3]=" . $host . "&ip_addr_cnt=1&sort_order=time_d&clear_allcriteria=1&withoutmenu=1"); ?>"
    target="report">
    <?php
    echo gettext("Src Unique events"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php
    echo str_replace("//", "/", "$acid_link/" . $acid_prefix . "_stat_alerts.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr[0][1]=ip_dst&ip_addr[0][2]==&ip_addr[0][3]=" . $host . "&ip_addr_cnt=1&sort_order=time_d&clear_allcriteria=1&withoutmenu=1"); ?>"
    target="report">
    <?php
    echo gettext("Dst Unique events"); ?> </a><br/><br/>

<?php
} // Sesion::menu_perms("MenuControlPanel", "ControlPanelEvents")
 ?>

<?php
if (Host_vulnerability::in_host_vulnerability($conn, $host)) {
?>
&nbsp;&nbsp;<b>Vulnerabilites</b><br/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../vulnmeter/index.php?noimages=1&host=<?php
    echo $host ?>" target="report">
<?php
    $ip_stats = Host_vulnerability::get_list($conn, "WHERE ip = \"$host\"", "ORDER BY scan_date DESC", $ggregated = true, 1);
    foreach($ip_stats as $host_vuln) {
        $scan_date = $host_vuln->get_scan_date();
    }
?>
    <?php
    echo gettext("Vulnmeter"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../vulnmeter/<?php
    echo date("YmdHis", strtotime($scan_date)); ?>/<?php
    echo ereg_replace("\.", "_", $host); ?>/index.html"
    target="report">
    <?php
    echo gettext("Security Problems"); ?> </a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="../incidents/index.php?ref=Vulnerability&status=Open&filter=OK&order_by=life_time&with_text=<?php
    echo $host; ?>" target="report">
    <?php
    echo gettext("Incidents"); ?> </a><br/><br/>
<?php
}
?>
&nbsp;&nbsp;<a href="<?php
echo Sensor::get_sensor_link($conn, $host) . "/$host.html" ?>" target="report">
    <?php
echo gettext("Usage"); ?> </a><br/><br/>
    <?php
if ((Host::in_host($conn, $host)) || (Net::isIpInAnyNet($conn, $host))) {
?>
&nbsp;&nbsp;
<a href="<?php
    $interface = Sensor::get_sensor_interface($conn, $host);
    echo Sensor::get_sensor_link($conn, $host) . "/plugins/rrdPlugin?action=list&key=interfaces/$interface/hosts/$ip_slashed&title=host%20$host" ?>" target="report">
    <?php
    echo gettext("Anomalies"); ?> </a><br/><br/>

<?php
}
$db->close($conn);
?>

</body>
</html>

