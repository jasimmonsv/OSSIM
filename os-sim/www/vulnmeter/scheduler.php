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

//
// $Id: scheduler.php,v 1.23 2010/01/23 14:35:54 juanmals Exp $
//

/***********************************************************/
/*          Inprotect             */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect             */
/*                           */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                   */
/*                           */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA          */
/*                           */
/* Contact Information:                */
/* inprotect-devel@lists.sourceforge.net         */
/* http://inprotect.sourceforge.net/             */
/***********************************************************/
/* See the README.txt and/or help files for more      */
/* information on how to use & config.           */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.        */
/*                           */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items    */
/* discovered with this program's use.           */
/***********************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>


<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <script src="../js/prototype.js" type="text/javascript"></script>
</head>

<body onLoad="Element.hide('sched_form');">

<h1> <?php echo _("Scheduling information"); ?> </h1>
<?php
require_once ('classes/Security.inc');
require_once ('classes/Plugin_scheduler.inc');
require_once ('classes/Util.inc');
require_once ('classes/Host.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Plugin.inc');
require_once ('classes/Net_group_scan.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_scan.inc');
require_once ('classes/Host_group_scan.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_scan.inc');
$db = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];
$conf_threshold = $conf->get_conf('threshold');
$frameworkd_dir = $conf->get_conf('frameworkd_dir');
$donessus_path = $frameworkd_dir . "/DoNessus.py";
if (!is_executable($donessus_path)) {
    echo "<center><b>";
    echo _("DoNessus.py needs to be executable for the scheduler to work.");
    echo "<br/>";
    echo _("Please ignore this warning in case frameworkd is running on another host.");
    echo "</b></center>";
}
$action = REQUEST("action");
$plugin = REQUEST("plugin");
$id = REQUEST("id");
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Action"));
ossim_valid($plugin, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Plugin"));
ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ID"));
if (ossim_error()) {
    die(ossim_error());
}
?>
<p>
<?php echo _("Please adjust Ticket creation threshold, tickets will only be created for vulnerabilities whose risk level exceeds the threshold."); ?><br/>
<?php echo _("It is recommended to set a high level at the beginning in order to concentrate on more critical vulnerabilities first, lowering it after having solved/tagged them as false positivies."); ?><br/>
<?php echo _("Threshold configuration can be found at Configuration->Main, \"vulnerability_incident_threshold\"."); ?>&nbsp;
<?php echo _("Current risk threshold is:"); ?>
<b>
<?php
print $conf->get_conf("vulnerability_incident_threshold");
?>
</b>
</p>
<?php
if ($action == "insert") {
    //$plugin = REQUEST("plugin");
    $minute = REQUEST("minute");
    $hour = REQUEST("hour");
    $day_month = REQUEST("day_month");
    $month = REQUEST("month");
    $day_week = REQUEST("day_week");
    $nsensors = REQUEST('nsensors');
    $nnethostgroups = REQUEST('nnethostgroups');
    $groupType = REQUEST('groupType');
    ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Action"));
    ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("ID"));
    ossim_valid($minute, OSS_CRONTAB, 'illegal:' . _("Minute"));
    ossim_valid($hour, OSS_CRONTAB, 'illegal:' . _("Hour"));
    ossim_valid($day_month, OSS_CRONTAB, 'illegal:' . _("Day of month"));
    ossim_valid($month, OSS_CRONTAB, 'illegal:' . _("Month"));
    ossim_valid($day_week, OSS_CRONTAB, 'illegal:' . _("Day of week"));
    if (ossim_error()) {
        die(ossim_error());
    }
    if ($groupType == "sensor") {
        $sensors = array();
        for ($i = 0; $i < $nsensors; $i++) {
            if (REQUEST("sensor$i") != null) {
                array_push($sensors, REQUEST("sensor$i"));
            }
        }
        if (!count($sensors)) {
            die(ossim_error(_("At least one Sensor required")));
        }
    } else {
        $netgroup_array = POST("netgroupList");
        $hostgroup_array = POST("hostgroupList");
        $net_array = POST("netList");
        $host_array = POST("hostList");
        if (!count($netgroup_array) and !count($hostgroup_array) and !count($net_array) and !count($host_array)) {
            die(ossim_error(_("At least one Netgroup/Hostgroup/Net/Host required")));
        }
    }
    Plugin_scheduler::insert($conn, $plugin, $minute, $hour, $day_month, $month, $day_week, $sensors, $netgroup_array, $hostgroup_array, $net_array, $host_array, $groupType);
?>

<center><b><?php echo _("Successfully inserted"); ?></b></center>
<center><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>"><?php echo _("Back"); ?></a></center>
        
<?php
} else {
    if ($action == "delete") {
        Plugin_scheduler::delete($conn, $id);
    }
?>

<?php
    // Get schedule list
    $schedules = Plugin_scheduler::get_list($conn, "");
?>
<table width="100%" align="left">
<tr>
<th><?php echo _("Vuln ID"); ?></th>
<th><?php echo _("Minute"); ?></th>
<th><?php echo _("Hour"); ?></th>
<th><?php echo _("Day of Month"); ?></th>
<th><?php echo _("Month"); ?></th>
<th><?php echo _("Day of week"); ?></th>
<th><?php echo _("Type Of Scan"); ?></th>
<th><?php echo _("Action"); ?></th>
</tr>
<?php
    foreach($schedules as $schedule) {
        $id = $schedule->get_plugin();
        $sensors = Plugin_scheduler::get_sensors($conn, $schedule->get_id());
        $netgroups = Plugin_scheduler::get_netgroups($conn, $schedule->get_id());
        $hostgroups = Plugin_scheduler::get_hostgroups($conn, $schedule->get_id());
        $nets = Plugin_scheduler::get_nets($conn, $schedule->get_id());
        $hosts = Plugin_scheduler::get_hosts($conn, $schedule->get_id());
        if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) {
            $plugin_name = $plugin_list[0]->get_name();
        } else {
            $plugin_name = $id;
        }
        echo "<tr>\n";
        echo "<td>" . $plugin_name . "</td>\n";
        echo "<td>" . $schedule->get_minute() . "</td>\n";
        echo "<td>" . $schedule->get_hour() . "</td>\n";
        echo "<td>" . $schedule->get_day_month() . "</td>\n";
        echo "<td>" . $schedule->get_month() . "</td>\n";
        echo "<td>" . $schedule->get_day_week() . "</td>\n";
        echo "<td>";
        foreach($sensors as $sensor) {
            echo "Sensor: ";
            echo Host::ip2hostname($conn, $sensor->get_sensor_name()) . "<br>";
        }
        foreach($netgroups as $netgroup) {
            echo "NetGroups: ";
            echo $netgroup->get_netgroup_name() . "<br>";
        }
        foreach($hostgroups as $hostgroup) {
            echo "HostGroups: ";
            echo $hostgroup->get_hostgroup_name() . "<br>";
        }
        foreach($nets as $net) {
            echo "Nets: ";
            echo $net->get_net_name() . "<br>";
        }
        foreach($hosts as $host) {
            echo "Host: ";
            echo $host->get_name($conn) . "<br>";
        }
        echo "</td>\n";
        echo "<td>[ <a href=\"" . $_SERVER["SCRIPT_NAME"] . "?action=delete&id=" . $schedule->get_id() . "\">" . _("Delete") . "</a> | <a href=\"do_nessus.php?interactive=no&scheduler_id=" . $schedule->get_id() . "\">" . _("Scan now") . "</a> ]</td>";
        echo "</tr>\n";
    }
?>
</table>

&nbsp;<br/>
<?php echo _("Warning: scheduling two different scans for the same month, day, hour and minute will yield unexpected results."); ?> <?php echo _("Of course you can select multiiple sensors for a certain schedule."); ?><br/>
&nbsp;<br/>
<hr noshade>
<center>
<a href="#" onclick="Element.show('sched_form'); return false;"> <?php echo _("Add another schedule"); ?> </a>
</center>
<div id="sched_form">

        <h3><center> <?php echo _("Select sensors for this scan"); ?> </center></h3>
<ul>
<?php
    $tmp_sensors = Sensor::get_all($conn, "ORDER BY name ASC");
    $sensor_list = array();
    // Quick & dirty sensor index array for "sensor#" further below
    $sensor_index = array();
    $tmp_index = 0;
    $tmp_group_hosts = Host_group_scan::get_list($conn, "ORDER BY host_group_name ASC");
    $tmp_group_nets = Net_group_scan::get_list($conn, "ORDER BY net_group_name ASC");
    $tmp_host = Host_scan::get_list($conn, "ORDER BY host_ip ASC");
    $tmp_nets = Net_scan::get_list($conn, "ORDER BY net_name ASC");
    $global_i = 0;
    define("NESSUS", 3001);
    $net_group_index = array();
    $host_group_index = array();
    $hosts_index = array();
    $nets_index = array();
    $net_group_list = array();
    $host_group_list = array();
    $hosts_list = array();
    $nets_list = array();
    foreach($tmp_sensors as $sensor) {
        if (Sensor::check_plugin_rel($conn, $sensor->get_ip() , NESSUS)) {
            $sensor_index[$sensor->get_name() ] = $tmp_index;
            $tmp_index++;
            array_push($sensor_list, $sensor);
        }
    }
    $num = count($sensor_list);
    if ($num > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows = intval($num / $cols) + 1;
    $tmp_index = 0;
    foreach($tmp_group_nets as $gn) {
        $net_group_index[$gn->get_name($conn) ] = $tmp_index;
        $tmp_index++;
        array_push($net_group_list, $gn);
    }
    $tmp_index = 0;
    foreach($tmp_group_hosts as $gh) {
        $host_group_index[$gh->get_name($conn) ] = $tmp_index;
        $tmp_index++;
        array_push($host_group_list, $gh);
    }
    $tmp_index = 0;
    foreach($tmp_host as $hs) {
        $hosts_index[$hs->get_name($conn) ] = $tmp_index;
        $tmp_index++;
        array_push($hosts_list, $hs);
    }
    $tmp_index = 0;
    foreach($tmp_nets as $ns) {
        $nets_index[$ns->get_name($conn) ] = $tmp_index;
        $tmp_index++;
        array_push($nets_list, $ns);
    }
    $num_ng = count($net_group_list);
    if ($num_ng > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows_ng = intval($num_ng / $cols) + 1;
    $num_hg = count($host_group_list);
    if ($num_hg > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows_hg = intval($num_hg / $cols) + 1;
    $num_hs = count($hosts_list);
    if ($num_hs > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows_hs = intval($num_hs / $cols) + 1;
    $num_ns = count($nets_list);
    if ($num_ns > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows_ns = intval($num_ns / $cols) + 1;
    if ($num_ns + $num_hs + $num_hg + $num_ng > 20) {
        $cols_full = 5;
    } else {
        $cols_full = 3;
    }
    $group_scan_list = Net_group_scan::get_list($conn, "WHERE plugin_id = " . NESSUS);
    foreach($group_scan_list as $group_scan) {
        $net_group_sensors = Net_group::get_sensors($conn, $group_scan->get_net_group_name());
        echo "\n<script>\n";
        echo "var " . $group_scan->get_net_group_name() . " = true;\n";
        echo "</script>\n";
        $sensor_string = "";
        foreach($net_group_sensors as $ng_sensor => $name) {
            if ($sensor_string == "") {
                $sensor_string.= $sensor_index[$name];
            } else {
                $sensor_string.= "," . $sensor_index[$name];
            }
        }
        $nets_string = "";
        $nets = Net_group::get_networks($conn, $group_scan->get_net_group_name() , NESSUS);
        foreach($nets as $net) {
            $name = $net->get_net_name();
            if ($nets_string == "") {
                $nets_string.= $nets_index[$name];
            } else {
                $nets_string.= "," . $nets_index[$name];
            }
        }
        print "<li><a href=\"#\" onClick=\"return selectSomeNets('" . $group_scan->get_net_group_name() . "','" . $sensor_string . "','" . $nets_string . "');\">" . $group_scan->get_net_group_name() . "</a>";
    }
    $group_scan_list = Host_group_scan::get_list($conn, "WHERE plugin_id = " . NESSUS);
    foreach($group_scan_list as $group_scan) {
        $host_group_sensors = Host_group::get_sensors($conn, $group_scan->get_host_group_name());
        echo "\n<script>\n";
        echo "var " . $group_scan->get_host_group_name() . " = true;\n";
        echo "</script>\n";
        $sensor_string = "";
        foreach($host_group_sensors as $hg_sensor) {
            $name = $hg_sensor->get_sensor_name();
            if ($sensor_string == "") {
                $sensor_string.= $sensor_index[$name];
            } else {
                $sensor_string.= "," . $sensor_index[$name];
            }
        }
        $hosts_string = "";
        $hosts = Host_group::get_hosts($conn, $group_scan->get_host_group_name() , NESSUS);
        foreach($hosts as $host) {
            $name = $host->get_host_name($conn);
            if ($hosts_string == "") {
                $hosts_string.= $hosts_index[$name];
            } else {
                $hosts_string.= "," . $hosts_index[$name];
            }
        }
        print "<li><a href=\"#\" onClick=\"return selectSomeHosts('" . $group_scan->get_host_group_name() . "','" . $sensor_string . "','" . $hosts_string . "');\">" . $group_scan->get_host_group_name() . "</a>";
    }
?>  
</ul>
        <form action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" method="POST">
<center>
<input type="Submit" value="<?php echo _("Submit"); ?>" class="button">
</center>
        <h4><center> (<?php echo _("Empty means all"); ?>) </center></h4>
        <center><a href="#" onClick="return selectAll();"><?php echo _("Select / Unselect all"); ?></a></center>
<br/>

<table width="100%" border="0" align="left"><tr><td>
        <input type="radio" name="groupType" value="sensor" checked onClick="selectGroup('sensor');"> Sensor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="groupType" value="host" onClick="selectGroup('host');"> NetGroup / Nets / HostGroup / Hosts
</td></tr>
<tr><td>
        <div id="rowSensor">
        
	<table width="100%" align="left" border="0"><tr>
        <?php
    for ($i = 1; $i <= $rows; $i++) {
        for ($a = 0; $a < $cols && $global_i < $num; $a++) {
            $sensor = $sensor_list[$global_i];
            echo "<td width=\"" . intval(100 / $cols) . "%\">";
            $all['sensors'][] = "sensor" . $global_i;
?>
                <div align="left">
                <input align="left" type="checkbox" id="<?php echo "sensor" . $global_i ?>" name="<?php echo "sensor" . $global_i ?>"
                               value="<?php echo $sensor->get_ip() ?>" /><?php echo $sensor->get_name() ?></div></td>
                <?php
            $global_i++;
        }
        echo "</tr>\n";
?>
            <?php
    }
    echo "</table>\n";
?>
        </div>
        <div id="rowHost" style="display: none">
        <table width="100%" align="left" border="0">
	<tr>
        <th colspan="5">NetGroups</th></tr><tr>
	<?php
    $global_ng = 0;
    for ($i = 1; $i <= $rows_ng; $i++) {
        for ($a = 0; $a < $cols_full && $global_ng < $num_ng; $a++) {
            $netgroup = $net_group_list[$global_ng];
            echo "<td width=\"" . intval(100 / $cols_full) . "%\">";
            $all['netgroups'][] = "netgroup" . $global_ng;
?>
                <div align="left">
                <input align="left" type="checkbox" id="<?php echo "netgroup" . $global_ng ?>" name="netgroupList[]"
                               value="<?php echo $netgroup->get_name() ?>" /><?php echo $netgroup->get_name($conn) ?></div></td> 
		<?php
            $global_ng++;
        }
        echo "</tr>\n";
?>
        <?php
    }
?>
	
	<th colspan="5">HostGroups</th></tr><tr>
	
	<?php
    $global_hg = 0;
    for ($i = 1; $i <= $rows_hg; $i++) {
        for ($a = 0; $a < $cols_full && $global_hg < $num_hg; $a++) {
            $hostgroup = $host_group_list[$global_hg];
            echo "<td width=\"" . intval(100 / $cols_full) . "%\">";
            $all['hostgroups'][] = "hostgroup" . $global_hg;
?>
             <div align="left">
             <input align="left" type="checkbox" id="<?php echo "hostgroup" . $global_hg ?>" name="hostgroupList[]"
                       value="<?php echo $hostgroup->get_name() ?>" /><?php echo $hostgroup->get_name($conn) ?></div></td>
        <?php
            $global_hg++;
        }
        echo "</tr>\n";
?>
        <tr>
        <?php
    }
?>
	
	<th colspan="5">Nets</th></tr><tr>
	
	<?php
    $global_ns = 0;
    for ($i = 1; $i <= $rows_ns; $i++) {
        for ($a = 0; $a < $cols_full && $global_ns < $num_ns; $a++) {
            $nets = $nets_list[$global_ns];
            echo "<td width=\"" . intval(100 / $cols_full) . "%\">";
            $all['nets'][] = "net" . $global_ns;
?>
                <div align="left">
                <input align="left" type="checkbox" id="<?php echo "net" . $global_ns ?>" name="netList[]"
                               value="<?php echo $nets->get_name() ?>" /><?php echo $nets->get_name($conn) ?></div></td>
		 <?php
            $global_ns++;
        }
        echo "</tr>\n";
?>
           <tr>
        <?php
    }
?>
	
	<th colspan="5">Hosts</th></tr><tr>

	<?php
    $global_hs = 0;
    for ($i = 1; $i <= $rows_hs; $i++) {
        for ($a = 0; $a < $cols_full && $global_hs < $num_hs; $a++) {
            $hosts = $hosts_list[$global_hs];
            $all['hosts'][] = "host" . $global_hs;
            $arrayHost[$hosts->get_name($conn)]=array($hosts->get_host_ip(),$global_hs);
            $global_hs++;
        }
    }

	ksort($arrayHost);
	$i=1;
	foreach($arrayHost as $name => $data){
		echo "<td width=\"" . intval(100 / $cols_full) . "%\">";
	?>
	                <div align="left">
                <input align="left" type="checkbox" id="<?php echo "host" . $data[1] ?>" name="hostList[]"
                               value="<?=$data[0]?>" /><?=$name?></div></td>
	<?php
	
	if($i%$cols_full==0) echo "</tr>";
	$i++;
	}
    echo "</table>\n";
?>
        </div>
</td></tr>

<tr>
<td colspan="<?php echo $cols
?>">
<center>
<input type="hidden" name="nsensors" value="<?php
    echo $global_i ?>" />
<input type="hidden" name="plugin" value="<?php
    echo NESSUS ?>" />

<hr noshade>
<table width="70%" style="center" border="2">
<tr>
<td>
<center>
<p align="left">
<?php echo _("Sample would scan the 13th of each month, at 03:00"); ?>
</p>
<table width="400">
<tr><th><?php echo _("field</th><th>allowed values"); ?></th></tr>
<tr><td colspan="2"> <?php echo _("Specify your scheduling information using crontab-like syntax"); ?>:</td>
<tr><td colspan="2"><hr noshade></td></tr>
<tr><td><?php echo _("minute"); ?></td><td>0-59</td></tr>
<tr><td><?php echo _("hour"); ?></td><td>0-23</td></tr>
<tr><td><?php echo _("day of month"); ?></td><td>1-31</td></tr>
<tr><td><?php echo _("month"); ?></td><td>1-12</td></tr>
<tr><td><?php echo _("day of week"); ?></td><td>0-7</td></tr>
<tr><td colspan="2"><?php echo _("Use * as wildcard"); ?></td></tr>
</table>
</center>
</td>
<td>
<p align="left">
<form action="<?php
    echo $_SERVER["SCRIPT_NAME"]; ?>" method="POST">
<input type="hidden" name="action" value="insert">
<?php echo _('Minute') ?><br/><li><input type="text" size=10 name="minute" value="0"><br/>
<?php echo _('Hour') ?> <br/><li><input type="text" size=10 name="hour" value="3"><br/>
<?php echo _('Day of Month') ?> <br/><li><input type="text" size=10 name="day_month" value="13"><br/>
<?php echo _('Month') ?> <br/><li><input type="text" size=10 name="month" value="*"><br/>
<?php echo _('Day of Week') ?> <br/><li><input type="text" size=10 name="day_week" value="*"><br/>
</ul>
<br>
<input type="submit" value="<?php echo _("Submit"); ?>" class="button">
</p>
</form>
</td>
</tr>
</table>
</tr></td></table>
</div>

<?php
}
?>

<script>
var check_sensors = true;
var check_nethost = true;
var scanType = 'sensor';

function selectAll()

{

if (scanType  == 'sensor') {
    <?php
if (count($all['sensors']) != 0) {
    foreach($all['sensors'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_sensors;
    <?php
    }
} ?>
        check_sensors = check_sensors == false ? true : false;
    }
else {
    <?php
if (count($all['netgroups']) != 0) {
    foreach($all['netgroups'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_nethost;
    <?php
    }
} ?>
    <?php
if (count($all['hostgroups']) != 0) {
    foreach($all['hostgroups'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_nethost;
    <?php
    }
} ?>
    <?php
if (count($all['nets']) != 0) {
    foreach($all['nets'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_nethost;
    <?php
    }
} ?>
    <?php
if (count($all['hosts']) != 0) {
    foreach($all['hosts'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_nethost;
    <?php
    }
} ?>
        check_nethost = check_nethost == false ? true : false;
     }

return false;

}

function selectSomeNets(name, identifiersSensors, identifiersNets)
{
if (identifiersSensors.length != 0) {
	arrayOfStringsSensor = identifiersSensors.split(",");
	for (var i=0; i < arrayOfStringsSensor.length; i++) {
	document.getElementById("sensor" + arrayOfStringsSensor[i]).checked = window[name];
	}
}

if (identifiersNets.length != 0) {
	arrayOfStringsNets = identifiersNets.split(",");
	for (var i=0; i < arrayOfStringsNets.length; i++) {
	document.getElementById("net" + arrayOfStringsNets[i]).checked = window[name];
	}
}

window[name] = window[name] == false ? true : false;
return false;

}

function selectSomeHosts(name, identifiersSensors, identifiersHosts)
{

if (identifiersSensors.length != 0) {
	arrayOfStringsSensor = identifiersSensors.split(",");
	for (var i=0; i < arrayOfStringsSensor.length; i++) {
	document.getElementById("sensor" + arrayOfStringsSensor[i]).checked = window[name];
	}
}

if (identifiersHosts.length != 0) {	
	arrayOfStringsHosts = identifiersHosts.split(",");
	for (var i=0; i < arrayOfStringsHosts.length; i++) {
	document.getElementById("host" + arrayOfStringsHosts[i]).checked = window[name];
	}
}

window[name] = window[name] == false ? true : false;
return false;

}


function selectGroup(category)
{
    if (category == 'sensor') {
        document.getElementById("rowHost").style.display = 'none';
        document.getElementById("rowSensor").style.display = 'block';
    } else {
        document.getElementById("rowHost").style.display = 'block';
        document.getElementById("rowSensor").style.display = 'none';
    }

scanType = category;

}
            
</script>

</body>
</html>

