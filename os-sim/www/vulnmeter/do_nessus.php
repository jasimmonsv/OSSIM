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
* - show_form()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>

<?php
// Testing some padding here for different browsers, see php flush() man page.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
include ("../hmenu.php"); ?>                                                                   

<?php
require_once 'classes/Security.inc';
$status = REQUEST('status');
$interactive = REQUEST('interactive');
$nsensors = REQUEST('nsensors');
$sensors = REQUEST('sensors');
$scheduler_id = REQUEST('scheduler_id');
ossim_valid($nsensors, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nsensors"));
ossim_valid($status, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Status"));
ossim_valid($scheduler_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Status"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_acl.inc');
require_once ('ossim_db.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Net_group_scan.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_scan.inc');
require_once ('classes/Host_group_scan.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_scan.inc');
$db = new ossim_db();
$conn = $db->connect();
define("NESSUS", 3001);
$sensor_list = array();
// Quick & dirty sensor index array for "sensor#" further below
$sensor_index = array();
$tmp_index = 0;
//$tmp_sensors = Sensor::get_all($conn, "ORDER BY name ASC");
$tmp_sensors = Sensor::get_list($conn, "ORDER BY name ASC"); // For filtering user perms
$tmp_group_hosts = Host_group_scan::get_list($conn, "WHERE plugin_id = 3001 ORDER BY host_group_name ASC");
$tmp_group_nets = Net_group_scan::get_list($conn, "WHERE plugin_id = 3001 ORDER BY net_group_name ASC");
$tmp_host = Host_scan::get_list($conn, "WHERE plugin_id = 3001 ORDER BY host_ip ASC");
$tmp_nets = Net_scan::get_list($conn, "WHERE plugin_id = 3001 ORDER BY net_name ASC");
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
function show_form() {
    global $sensor_list;
    global $net_group_list;
    global $host_group_list;
    global $hosts_list;
    global $nets_list;
    global $conn;
    global $sensor_index;
    global $net_group_index;
    global $host_group_index;
    global $hosts_index;
    global $nets_index;
    $global_i = 0;
    $num = count($sensor_list);
    if ($num > 20) {
        $cols = 5;
    } else {
        $cols = 3;
    }
    $rows = intval($num / $cols) + 1;
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
?>
	<h3><center> <?php echo _("Select sensors for this scan"); ?> </center></h3>
<ul>
<?php
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
<p>
<?php echo _("Please adjust incident creation threshold, incidents will only be created for vulnerabilities whose risk level exceeds the threshold."); ?><br/>
<?php echo _("It is recommended to set a high level at the beginning in order to concentrate on more critical vulnerabilities first, lowering it after having solved/tagged them as false positivies."); ?><br/>
<?php echo _("Threshold configuration can be found at Configuration->Main, \"vulnerability_incident_threshold\"."); ?>&nbsp;
<?php echo _("Current risk threshold is:"); ?>
<b>
<?php
    require_once ('ossim_conf.inc');
    $conf = $GLOBALS["CONF"];
    print $conf->get_conf("vulnerability_incident_threshold");
?>
</b>
</p>
	<h4><center> (<?php echo _("Empty means all"); ?>) </center></h4>
	<center><a href="#" onClick="return selectAll();"><?php echo _("Select / Unselect all"); ?></a></center>
<br/>

<table width="100%" border="0" align="center"><tr><td>
	<input type="radio" name="groupType" value="sensor" checked onClick="selectGroup('sensor');"> Sensor &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="radio" name="groupType" value="host" onClick="selectGroup('host');"> NetGroup / Nets / HostGroup / Hosts
</td></tr>
<tr><td>
        <div id="rowSensor">
        <table width="100%" align="left" border="0"><tr>
	<?php
    for ($i = 1; $i <= $rows; $i++) {
?>
	<?php
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
	<th colspan="3">NetGroups</th></tr><tr>
	<?php
    $global_ng = 0;
    for ($i = 1; $i <= $rows_ng; $i++) {
?>
        <?php
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
            <tr>
    <?php
    }
?>

	<th colspan="3">HostGroups</th></tr><tr>

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

	<th colspan="3">Nets</th></tr><tr>
        
        <?php
    $global_ns = 0;
    for ($i = 1; $i <= $rows_ns; $i++) {
?>
        <?php
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

	<th colspan="3">Hosts</th></tr><tr>

        <?php
    $global_hs = 0;
    for ($i = 1; $i <= $rows_hs; $i++) {
?>
        <?php
        for ($a = 0; $a < $cols_full && $global_hs < $num_hs; $a++) {
            $hosts = $hosts_list[$global_hs];
            echo "<td width=\"" . intval(100 / $cols_full) . "%\">";
            $all['hosts'][] = "host" . $global_hs;
?>
                <div align="left">
                <input align="left" type="checkbox" id="<?php echo "host" . $global_hs ?>" name="hostList[]"
                               value="<?php echo $hosts->get_host_ip() ?>" /><?php echo $hosts->get_name($conn) ?></div></td>
                 <?php
            $global_hs++;
        }
        echo "</tr>\n";
?>
            <?php
    }
    echo "</table>\n";
?>
        </div>	

</td></tr></table>
<br>
<center>
<input type="hidden" name="nsensors" value="<?php
    echo $global_i ?>" />
<input type="Submit" class="btn" value="<?php echo _("Submit"); ?>">
</center>
</form>
<center><a href="index.php"> <?php
    echo gettext("Back"); ?> </a></center>
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
<?php
}
if ($interactive == "yes") {
    show_form();
    exit();
}
$sensors = "";
for ($i = 0; $i < $nsensors; $i++) {
    if (ossim_error()) {
        die(ossim_error());
    }
    if ($sensors == "") $sensors = POST("sensor$i");
    else if (POST("sensor$i") != "") $sensors.= "," . POST("sensor$i");
}
$netgroup_l = "";
$netgroup_array = POST("netgroupList");
for ($i = 0; $i < count($netgroup_array); $i++) {
    if ($netgroup_l == "") $netgroup_l = $netgroup_array[$i];
    else $netgroup_l.= "," . $netgroup_array[$i];
}
$hostgroup_l = "";
$hostgroup_array = POST("hostgroupList");
for ($i = 0; $i < count($hostgroup_array); $i++) {
    if ($hostgroup_l == "") $hostgroup_l = $hostgroup_array[$i];
    else $hostgroup_l.= "," . $hostgroup_array[$i];
}
$net_l = "";
$net_array = POST("netList");
for ($i = 0; $i < count($net_array); $i++) {
    if ($net_l == "") $net_l = $net_array[$i];
    else $net_l.= "," . $net_array[$i];
}
$host_l = "";
$host_array = POST("hostList");
for ($i = 0; $i < count($host_array); $i++) {
    if ($host_l == "") $host_l = $host_array[$i];
    else $host_l.= "," . $host_array[$i];
}
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
/* Frameworkd's address & port */
$address = $conf->get_conf("frameworkd_address");
$port = $conf->get_conf("frameworkd_port");
/* create socket */
$socket = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket < 0) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("CRE_SOCKET", array(
        socket_strerror($socket)
    ));
}
/* connect */
$result = @socket_connect($socket, $address, $port);
if (!$result) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("FRAMW_NOTRUN", array(
        $address . ":" . $port
    ));
}
if ($status == "reset") {
    $in = 'nessus action="reset"' . "\n";
    socket_write($socket, $in, strlen($in));
?>
	   <center><a href="index.php"> <?php
    echo gettext("Back"); ?> </a></center>
	<?php
    exit();
}
if (strlen($sensors) == 0) {
    foreach($sensor_list as $sensor) {
        if ($sensors == "") $sensors = $sensor->get_ip();
        else $sensors.= "," . $sensor->get_ip();
    }
}
if ($scheduler_id > 0) {
    $in = 'nessus action="scan" target_type="schedule" id="' . $scheduler_id . '"' . "\n";
} else {
    if (POST("groupType") == "sensor") {
        $in = 'nessus action="scan" target_type="sensors" list="' . $sensors . '"' . "\n";
    } else {
        $in = 'nessus action="scan" target_type="hosts" netgroups="' . $netgroup_l . '" nets="' . $net_l . '" hostgroups="' . $hostgroup_l . '" hosts="' . $host_l . '"' . "\n";
    }
}
$out = '';
socket_write($socket, $in, strlen($in));
echo str_pad('', 1024); // minimum start for Safari

?>
<center> 
<?php
echo gettext("Nessus scan started, depending on number of hosts to be scanned this may take a while"); ?>.
</center>
<center>
<?php echo _("Scan status:") . " " ?>
<div id="percentage">
<?php echo "0% " . _("completed.") ?>
</div>
</center>
<?php
flush(); ?>
<?php
$in = 'nessus action="status"' . "\n";
while (socket_write($socket, $in, strlen($in)) && ($out = socket_read($socket, 255, PHP_BINARY_READ))) {
    if ($out > 0 && $out < 100) {
?>
<script language="javascript">
percentage_div = document.getElementById("percentage");
percentage_div.innerHTML = '"' . <?php
        echo rtrim($out); ?> + "<?php echo "% " . _("completed.") ?>";
</script>
<?php
        flush();
    } elseif ($out < 0) {
?>
<script language="javascript">
percentage_div = document.getElementById("percentage");
percentage_div.innerHTML =  "<?php echo '"' . _("Error! return was:") . " " ?>" + <?php
        echo rtrim($out); ?> + "<?php echo " " . _("Please check your frameworkd logs.") . '"' ?>" ;
</script>
<?php
        flush();
        break;
    } elseif ($out == 100) {
?>
<script language="javascript">
percentage_div = document.getElementById("percentage");
percentage_div.innerHTML =  "<?php echo '"' . _("Scan succesfully completed.") . '"' ?>" ; 
</script>
<?php
        flush();
        break;
    } else {
        if (preg_match("/Error/", $out)) {
?>
<script language="javascript">
percentage_div = document.getElementById("percentage");
percentage_div.innerHTML =   <?php echo '"<BR>' . _("An error ocurred, please check your frameworkd & web server logs:") . '<BR><BR><b>' . rtrim($out) . '</b><BR>"' ?>; 
percentage_div.innerHTML += "<BR><a href=\"<?php echo $_SERVER["SCRIPT_NAME"] ?>?status=reset\" > <?php echo _("Reset") . "<BR>&nbsp;<BR>"; ?>"; 
</script>
<?php
            flush();
            break;
        } else {
?>
<script language="javascript">
percentage_div = document.getElementById("percentage");
percentage_div.innerHTML =   <?php echo '"<BR>' . _("Frameworkd said:") . '<BR><BR><b>' . rtrim($out) . '</b><BR>&nbsp;<BR>"' ?>; 
</script>

<?php
            flush();
            break;
        }
    }
    sleep(5);
}
socket_close($socket);
?>
<center><a href="index.php"> <?php
echo gettext("Back"); ?> </a></center>
 
</body>
</html>

