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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body scroll=no style="overflow:hidden">
<?php
//include ("../hmenu.php"); 
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
ossim_valid($host, OSS_IP_ADDRCIDR, OSS_NULLABLE, 'illegal:' . _("Host"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
$conf = $GLOBALS["CONF"];
$ip_slashed = str_replace(".", "/", $host);
#$acid_link = $conf->get_conf("acid_link");
#$acid_prefix = $conf->get_conf("event_viewer");
#$acid_main_link = str_replace("//", "/", $conf->get_conf("acid_link") . "/" . $acid_prefix . "_stat_ipaddr.php?ip=$host&netmask=32&clear_allcriteria=1&withoutmenu=1");
#$interface = $conf->get_conf("ossim_interface");
?>

<table align="center" class="noborder"><tr>
<td class="nobborder" style="padding:0px 10px 0px 0px"><b><?=$host?></b></td>
<td class="nobborder">[</td>
<td class="nobborder" nowrap> <a href="host_report.php?host=<?= $host ?>" target="report"> <?= gettext("Report"); ?> </a> </td>

<? if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) { ?>
<td class="nobborder">|</td>
<td class="nobborder" nowrap> <a href="metrics.php?host=<?= $host ?>" target="report"> <?= gettext("Metrics"); ?> </a> </td>
<? } ?>

<td class="nobborder">|</td>
<td class="nobborder" nowrap> <a href="<?=Sensor::get_sensor_link($conn, $host) . "/$host.html"?>" target="report"> <?= gettext("Usage"); ?> </a> </td>

<? if ((Host::in_host($conn, $host)) || (Net::isIpInAnyNet($conn, $host))) { 
	$interface = Sensor::get_sensor_interface($conn, $host);
?>
<td class="nobborder">|</td>
<td class="nobborder" nowrap> <a href="<?=Sensor::get_sensor_link($conn, $host) . "/plugins/rrdPlugin?action=list&key=interfaces/$interface/hosts/$ip_slashed&title=host%20$host" ?>" target="report"> <?= gettext("Anomalies"); ?> </a> </td>

<?
 }
$db->close($conn);
?>

<td class="nobborder">]</td>
</table>

</body>
</html>
