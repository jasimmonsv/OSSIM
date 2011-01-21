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
* - get_score()
* - get_current_metric()
* - host_get_network_data()
* - check_sensor_perms()
* - check_net_perms()
* - order_by_risk()
* - html_service_level()
* - html_set_values()
* - _html_metric()
* - _html_rrd_link()
* - html_max()
* - html_current()
* - html_rrd()
* - html_incident()
* - html_host_report()
* - html_date()
* Classes list:
*/
/*
TODO
- missing sensors stuff (see Session::hostAllowed() & Host::get_realted_sensors())
- now everybody can see hosts outside defined networks, maybe add a new user perm
- max metric date could be shown in days/hours/mins (see Util::date_diff())
- add help
*/
require_once 'classes/Session.inc';
require_once 'classes/Util.inc';
require_once 'classes/Net.inc';
Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
$db = new ossim_db();
$conn = $db->connect();
if (Session::menu_perms("MenuControlPanel", "ControlPanelEvents")) {
    $event_perms = true;
} else {
    $event_perms = false;
}
if (Session::menu_perms("MenuReports", "ReportsHostReport")) {
    $host_report_perms = true;
} else {
    $host_report_perms = false;
}
////////////////////////////////////////////////////////////////
// Param validation
////////////////////////////////////////////////////////////////
$valid_range = array(
    'day',
    'week',
    'month',
    'year'
);
$range = GET('range');
ossim_valid($range, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("range"));
if (ossim_error()) {
    die(ossim_error());
}
if (!$range) {
    $range = 'day';
} elseif (!in_array($range, $valid_range)) {
    die(ossim_error('Invalid range'));
}
if ($range == 'day') {
    $rrd_start = "N-1D";
} elseif ($range == 'week') {
    $rrd_start = "N-7D";
} elseif ($range == 'month') {
    $rrd_start = "N-1M";
} elseif ($range == 'year') {
    $rrd_start = "N-1Y";
}
$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];
$conf_threshold = $conf->get_conf('threshold');
////////////////////////////////////////////////////////////////
// Script private functions
////////////////////////////////////////////////////////////////
/*
* @param $name, string with the id of the object (ex: a network name or a host
* ip)
* @param $type, enum ('day', 'month', ...)
*/
function get_score($name, $type) {
    global $conn, $range;
    static $scores = null;
    // first time build the scores cache
    if (!$scores) {
        $sql = "SELECT id, rrd_type, max_c, max_a, max_c_date, max_a_date
                FROM control_panel WHERE time_range = ?";
        $params = array(
            $range
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            // $id = 'netfoo.com#net'
            $id = $rs->fields['id'] . '#' . $rs->fields['rrd_type'];
            $scores[$id] = array(
                'max_c' => $rs->fields['max_c'],
                'max_a' => $rs->fields['max_a'],
                'max_c_date' => $rs->fields['max_c_date'],
                'max_a_date' => $rs->fields['max_a_date']
            );
            $rs->MoveNext();
        }
    }
    $id = $name . '#' . $type;
    if (isset($scores[$id])) {
        return $scores[$id];
    }
    return array(
        'max_c' => 0,
        'max_a' => 0,
        'max_c_date' => 0,
        'max_a_date' => 0
    );
}
function get_current_metric($name, $type = 'host', $ac = 'attack') {
    static $qualification;
    global $conn;
    if (!$qualification) {
        $sql = "SELECT host_ip, compromise, attack FROM host_qualification";
        if (!$rs = & $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        $qualification['global']['global']['attack'] = 0;
        $qualification['global']['global']['compromise'] = 0;
        while (!$rs->EOF) {
            $host = $rs->fields['host_ip'];
            $qualification['host'][$host]['attack'] = $rs->fields['attack'];
            $qualification['global']['global']['attack']+= $rs->fields['attack'];
            $qualification['host'][$host]['compromise'] = $rs->fields['compromise'];
            $qualification['global']['global']['compromise']+= $rs->fields['compromise'];
            $rs->MoveNext();
        }
        $sql = "SELECT net_name, compromise, attack FROM net_qualification";
        if (!$rs = & $conn->Execute($sql)) {
            die($conn->ErrorMsg());
        }
        while (!$rs->EOF) {
            $host = $rs->fields['net_name'];
            $qualification['net'][$host]['attack'] = $rs->fields['attack'];
            $qualification['net'][$host]['compromise'] = $rs->fields['compromise'];
            $rs->MoveNext();
        }
    }
    if (isset($qualification[$type][$name][$ac])) {
        return $qualification[$type][$name][$ac];
    }
    // no current metric for this network object
    return 0;
}
/*
*
* @param string $ip,  the host ip
* @return mixed     - array: with full network data
*                   - false: user have no perms over the network
*                   - null: host is not in any defined network
*/
function host_get_network_data($ip) {
    global $groups, $networks;
    // search in groups
    foreach($groups as $group_name => $g_data) {
        foreach($g_data['nets'] as $net_name => $n_data) {
            $address = $n_data['address'];
            if (!strpos($address, "/")) {
                // tvvcox: i've detected some wrong network addresses, catch them with that
                //echo "<font color='red'>"._("Invalid network address for")." $net_name: $address</font><br>";
                continue;
            }
            if (Net::isIpInNet($ip, $address)) {
                if (!$n_data['has_perms'] && !check_sensor_perms($ip, 'host')) {
                    return false;
                }
                $n_data['group'] = $group_name;
                $n_data['name'] = $net_name;
                return $n_data;
            }
        }
    }
    // search in nets
    foreach($networks as $net_name => $n_data) {
        $address = $n_data['address'];
        if (Net::isIpInNet($ip, $address)) {
            if (!$n_data['has_perms'] && !check_sensor_perms($ip, 'host')) {
                return false;
            }
            $n_data['group'] = false;
            $n_data['name'] = $net_name;
            return $n_data;
        }
    }
    // This means the host didn't belong to any net
    //echo "$ip not in any network<br>";
    return null;
}
/*
* A user has perms over a:
*
* a) host: If an allowed sensor has the same ip as $subject or if the user has
* an allowed sensor related to this host (host_sensor_reference)
*
* b) net: if the user has an allowed sensor related to this net
* (net_sensor_reference)
*/
function check_sensor_perms($subject, $type = 'host') {
    global $conn, $allowed_sensors, $groups, $networks;
    static $host_sensors = false, $sensors_ip = array() , $net_sensors = false;
    // if $allowed_sensors is empty, that means permit all
    if (!$allowed_sensors) {
        return true;
    }
    if ($type == 'host') {
        // First time build the static arrays
        if (!$host_sensors) {
            // Get the IP of each allowed sensor
            $sql = "SELECT sensor.ip FROM sensor WHERE ";
            $sqls = array();
            foreach($allowed_sensors as $s) {
                $sqls[] = "sensor.name = '$s'";
            }
            $sql.= implode(' OR ', $sqls);
            if (!$rs = $conn->Execute($sql)) {
                die($conn->ErrorMsg());
            }
            while (!$rs->EOF) {
                $sensors_ip[] = $rs->fields['ip'];
                $rs->MoveNext();
            }
            // Get the sensors related to the IP
            $sql = "SELECT host_ip, sensor_name FROM host_sensor_reference";
            if (!$rs = $conn->Execute($sql)) {
                die($conn->ErrorMsg());
            }
            while (!$rs->EOF) {
                $sensor_name = $rs->fields['sensor_name'];
                if (in_array($sensor_name, $allowed_sensors)) {
                    $host_sensors[$rs->fields['host_ip']][] = $sensor_name;
                }
                $rs->MoveNext();
            }
        }
        // if the ip has related sensors and one of each related sensor
        // is listed as allowed then permit
        if (isset($host_sensors[$subject])) {
            return count(array_intersect($host_sensors[$subject], $allowed_sensors));
        }
        // if the ip matches the ip of one allowed sensor: permit
        return in_array($subject, $sensors_ip);
    }
    if ($type == 'net') {
        // First time build the static array
        if (!$net_sensors) {
            // Get the sensors related to the net
            $sql = "SELECT net_name, sensor_name FROM net_sensor_reference";
            if (!$rs = $conn->Execute($sql)) {
                die($conn->ErrorMsg());
            }
            while (!$rs->EOF) {
                $sensor_name = $rs->fields['sensor_name'];
                if (in_array($sensor_name, $allowed_sensors)) {
                    $net_sensors[$rs->fields['net_name']][] = $sensor_name;
                }
                $rs->MoveNext();
            }
        }
        // if the net has related sensors and one of each related sensor
        // is listed as allowed then permit
        if (isset($net_sensors[$subject])) {
            return count(array_intersect($net_sensors[$subject], $allowed_sensors));
        }
    }
    return false;
}
function check_net_perms($net_name) {
    global $allowed_nets;
    if (is_array($allowed_nets) && !in_array($net_name, $allowed_nets)) {
        return false;
    }
    return true;
}
function order_by_risk($a, $b) {
    global $order_by_risk_type;
    $max = $order_by_risk_type == 'attack' ? 'max_a' : 'max_c';
    $threshold = $order_by_risk_type == 'attack' ? 'threshold_a' : 'threshold_c';
    $val_a = round($a[$max] / $a[$threshold]);
    $val_b = round($b[$max] / $b[$threshold]);
    if ($val_a == $val_b) {
        // same risk, so order alphabetically
        return strnatcmp($a['name'], $b['name']);
        // same risk order by max (like previous version)
        /*
        if ($a[$max] != $b[$max]) {
        return $a[$max] > $b[$max] ? -1 : 1;
        }
        return 0;
        */
    }
    return ($val_a > $val_b) ? -1 : 1;
}
function html_service_level() {
    global $conn, $conf, $user, $range, $rrd_start;
    $sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
    $params = array(
        "global_$user",
        $range
    );
    if (!$rs = & $conn->Execute($sql, $params)) {
        die($conn->ErrorMsg());
    }
    if ($rs->EOF) {
        return "<td>" . _("n/a") . "<td>";
    }
    $level = ($rs->fields["c_sec_level"] + $rs->fields["a_sec_level"]) / 2;
    $level = sprintf("%.2f", $level);
    $link = Util::graph_image_link("level_$user", "level", "attack", $rrd_start, "N", 1, $range);
    $use_svg = $conf->get_conf("use_svg_graphics");
    if ($use_svg) {
        return "
            <td><a class='greybox' href='$link'>
                 <embed src='svg_level.php?sl=$level&scale=0.8'  
                        pluginspage='http://www.adobe.com/svg/viewer/install/'
                        type='image/svg+xml' height='85' width='100' /> 
            </a></td>";
    } else {
        if ($level >= 95) {
            $bgcolor = "green";
            $fontcolor = "white";
        } elseif ($level >= 90) {
            $bgcolor = "#CCFF00";
            $fontcolor = "black";
        } elseif ($level >= 85) {
            $bgcolor = "#FFFF00";
            $fontcolor = "black";
        } elseif ($level >= 80) {
            $bgcolor = "orange";
            $fontcolor = "black";
        } elseif ($level >= 75) {
            $bgcolor = "#FF3300";
            $fontcolor = "white";
        } else {
            $bgcolor = "red";
            $fontcolor = "white";
        }
        return "
          <td bgcolor='$bgcolor'><b>
            <a href='$link'>
              <font size='+1' color='$fontcolor'>$level%</font>
            </a>
          </b></td>";
    }
}
function html_set_values($subject, $subject_type, $max, $max_date, $current, $threshold, $ac) {
    $GLOBALS['_subject'] = $subject;
    $GLOBALS['_subject_type'] = $subject_type;
    $GLOBALS['_max'] = $max;
    $GLOBALS['_max_date'] = $max_date;
    $GLOBALS['_current'] = $current;
    $GLOBALS['_threshold'] = $threshold;
    $GLOBALS['_ac'] = $ac;
}
function _html_metric($metric, $threshold, $link) {
    global $event_perms;
    $risk = round($metric / $threshold * 100);
    $font_color = 'color="white"';
    $color = '';
    if ($risk > 500) {
        $color = 'bgcolor="#FF0000"';
        $risk = 'high';
    } elseif ($risk > 300) {
        $color = 'bgcolor="orange"';
        $risk = 'med';
    } elseif ($risk > 100) {
        $color = 'bgcolor="green"';
        $risk = 'low';
    } else {
        $font_color = 'color="black"';
        $risk = '-';
    }
    $html = "<td $color><span title='$metric / $threshold (" . _("metric/threshold") . ")'>";
    if ($event_perms) {
        $html.= "<a href='$link'><font $font_color>$risk</font></a>";
    } else {
        $html.= "<font $font_color>$risk</font>";
    }
    $html.= "</span></td>";
    return $html;
}
function _html_rrd_link() {
    global $user, $range, $rrd_start;
    $type = $GLOBALS['_ac'] == 'c' ? 'compromise' : 'attack';
    $link = Util::graph_image_link($GLOBALS['_subject'], $GLOBALS['_subject_type'], $type, $rrd_start, "N", 1, $range);
    return $link;
}
function html_max() {
    if ($GLOBALS['_max_date'] == 0) {
        $link = '#';
    } else {
        $link = Util::get_acid_date_link($GLOBALS['_max_date']);
    }
    return _html_metric($GLOBALS['_max'], $GLOBALS['_threshold'], $link);
}
function html_current() {
    $link = _html_rrd_link();
    return _html_metric($GLOBALS['_current'], $GLOBALS['_threshold'], $link);
}
function html_rrd() {
    return '<a href="' . _html_rrd_link() . '"><img 
            src="../pixmaps/graph.gif" border="0"/></a>';
}
function html_incident() {
    $subject = $GLOBALS['_subject'];
    $subject_type = $GLOBALS['_subject_type'];
    $metric = $GLOBALS['_max'];
    $threshold = $GLOBALS['_threshold'];
    $ac = $GLOBALS['_ac'];
    $max_date = $GLOBALS['_max_date'];
    global $range;
    $range_translations = array(
        "day" => "today",
        "week" => "this week",
        "month" => "this month",
        "year" => "this year"
    );
    if ($max_date == 0) {
        $max_date = $range_translations[$range];
    }
    $title = sprintf(_("Metric Threshold: %s level exceeded") , strtoupper($ac));
    $target = "$subject_type: $subject";
    $type = $ac == 'c' ? 'Compromise' : 'Attack';
    $priority = round($metric / $threshold);
    if ($priority > 10) {
        $priority = 10;
    }
    $html = "<a href='../incidents/newincident.php?" . "ref=Metric&" . "title=" . urlencode("$title ($target)") . "&" . "priority=$priority&" . "target=" . urlencode($target) . "&" . "metric_type=$type&" . "metric_value=$metric&" . "event_start=$max_date&" . "event_end=$max_date'>" . '<img src="../pixmaps/incident.png" width="12" alt="i" border="0"/>' . '</a>';
    return $html;
}
function html_host_report($ip, $name, $title = '') {
    global $host_report_perms;
    if ($title) {
        $title = "title='$title'";
    }
    if ($host_report_perms) {
        return "<a href='../report/host_report.php?host=$ip' $title  id='$ip;$name' class='HostReportMenu'>
                $name</a>";
    } else {
        return "<span $title>$name</span>";
    }
}
function html_date() {
    // max_date == 0, when there was no metric
    if ($GLOBALS['_max_date'] == 0 || strtotime($GLOBALS['_max_date']) == 0) {
        return _('n/a');
    }
    return $GLOBALS['_max_date'];
}
////////////////////////////////////////////////////////////////
// Network Groups
////////////////////////////////////////////////////////////////
// If allowed_nets === null, then permit all
$allowed_nets = Session::allowedNets($user);
if ($allowed_nets) {
    $allowed_nets = explode(',', $allowed_nets);
}
$allowed_sensors = Session::allowedSensors($user);
if ($allowed_sensors) {
    $allowed_sensors = explode(',', $allowed_sensors);
}
$net_where = "";
if ($allowed_sensors != "" || $allowed_nets != "") {
	$nets_aux = Net::get_list($conn);
	$networks = "";
	foreach ($nets_aux as $net) {
		$networks .= ($networks != "") ? ",'".$net->get_name()."'" : "'".$net->get_name()."'"; 
	}
	if ($networks != "") {
		$net_where = " AND net.name in ($networks)";
	}
}
// We can't join the control_panel table, because new ossim installations
// holds no data there
$sql = "SELECT
            net_group.name as group_name,
            net_group.threshold_c as group_threshold_c,
            net_group.threshold_a as group_threshold_a,
            net.name as net_name,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address
        FROM
            net_group,
            net,
            net_group_reference
        WHERE
            net_group_reference.net_name = net.name AND
            net_group_reference.net_group_name = net_group.name$net_where";
if (!$rs = & $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}
$groups = array();
$group_max_c = $group_max_a = 0;
while (!$rs->EOF) {
    $group = $rs->fields['group_name'];
    $groups[$group]['name'] = $group;
    // check perms over the network
	// Fixed: netAllowed check net/sensor granularity perms
    //$has_perms = Session::netAllowed($conn, $rs->fields['net_address']);
	//$has_net_perms = check_net_perms($rs->fields['net_address']);
    // if no perms over the network, try perms over the related sensor
    //$has_perms = $has_net_perms ? true : check_sensor_perms($rs->fields['net_address'], 'net');
    // the user only have perms over this group if he has perms over
    // all the networks of this group
    //if (!isset($groups[$group]['has_perms'])) {
      //  $groups[$group]['has_perms'] = $has_perms;
    //} elseif (!$has_perms) {
      //  $groups[$group]['has_perms'] = false;
    //}
    $groups[$group]['has_perms'] = true;
    // If there is no threshold specified for a group, pick the configured default threshold
    $group_threshold_a = $rs->fields['group_threshold_a'] ? $rs->fields['group_threshold_a'] : $conf_threshold;
    $group_threshold_c = $rs->fields['group_threshold_c'] ? $rs->fields['group_threshold_c'] : $conf_threshold;
    $groups[$group]['threshold_a'] = $group_threshold_a;
    $groups[$group]['threshold_c'] = $group_threshold_c;
    $net = $rs->fields['net_name'];
    // current metrics
    $net_current_a = get_current_metric($net, 'net', 'attack');
    $net_current_c = get_current_metric($net, 'net', 'compromise');
    @$groups[$group]['current_a']+= $net_current_a;
    @$groups[$group]['current_c']+= $net_current_c;
    // scores
    $score = get_score($net, 'net');
    @$groups[$group]['max_c']+= $score['max_c'];
    @$groups[$group]['max_a']+= $score['max_a'];
    $net_max_c_time = strtotime($score['max_c_date']);
    $net_max_a_time = strtotime($score['max_a_date']);
    if (!isset($groups[$group]['max_c_date'])) {
        $groups[$group]['max_c_date'] = $score['max_c_date'];
    } else {
        $group_max_c_time = strtotime($groups[$group]['max_c_date']);
        if ($net_max_c_time > $group_max_c_time) {
            $groups[$group]['max_c_date'] = $score['max_c_date'];
        }
    }
    if (!isset($groups[$group]['max_a_date'])) {
        $groups[$group]['max_a_date'] = $score['max_a_date'];
    } else {
        $group_max_a_time = strtotime($groups[$group]['max_a_date']);
        if ($net_max_c_time > $group_max_c_time) {
            $groups[$group]['max_a_date'] = $score['max_a_date'];
        }
    }
    // If there is no threshold specified for a network, pick the group threshold
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $group_threshold_a;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $group_threshold_c;
    $groups[$group]['nets'][$net] = array(
        'name' => $net,
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a' => $score['max_a'],
        'max_c' => $score['max_c'],
        'max_a_date' => $score['max_a_date'],
        'max_c_date' => $score['max_c_date'],
        'address' => $rs->fields['net_address'],
        'current_a' => $net_current_a,
        'current_c' => $net_current_c,
        'has_perms' => $has_perms
    );
    $rs->MoveNext();
}

////////////////////////////////////////////////////////////////
// Networks outside groups
////////////////////////////////////////////////////////////////
$sql = "SELECT
            net.name as net_name,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address
        FROM
            net
        WHERE
            net.name NOT IN (SELECT net_name FROM net_group_reference)$net_where";
if (!$rs = & $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}

$networks = array();
while (!$rs->EOF) {
    // check perms over the network
    //$has_net_perms = check_net_perms($rs->fields['net_address']);
	// Fixed: netAllowed check net/sensor granularity perms
	//$has_perms = Session::netAllowed($conn, $rs->fields['net_address']);
	$has_perms = true;
    // if no perms over the network, try perms over the related sensor
    //$has_perms = $has_net_perms ? true : check_sensor_perms($rs->fields['net_address'], 'net');
	$net = $rs->fields['net_name'];
    $score = get_score($net, 'net');
    // If there is no threshold specified for the network, pick the global configured threshold
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $conf_threshold;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $conf_threshold;
    $networks[$net] = array(
        'name' => $net,
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a' => $score['max_a'],
        'max_c' => $score['max_c'],
        'max_a_date' => $score['max_a_date'],
        'max_c_date' => $score['max_c_date'],
        'address' => $rs->fields['net_address'],
        'current_a' => get_current_metric($net, 'net', 'attack') ,
        'current_c' => get_current_metric($net, 'net', 'compromise') ,
        'has_perms' => $has_perms
    );
    $rs->MoveNext();
}
////////////////////////////////////////////////////////////////
// Hosts
////////////////////////////////////////////////////////////////
$host_where = "";
if ($allowed_sensors != "" || $allowed_nets != "") {
	$hosts_aux = Host::get_list($conn);
	$hosts = "";
	foreach ($hosts_aux as $host) {
		$hosts .= ($hosts != "") ? ",'".$host->get_ip()."'" : "'".$host->get_ip()."'";
	}
	if ($hosts != "") {
		$host_where = " AND control_panel.id in ($hosts)";
	}
}
$sql = "SELECT
            control_panel.id,
            control_panel.max_c,
            control_panel.max_a,
            control_panel.max_c_date,
            control_panel.max_a_date,
            host.threshold_a,
            host.threshold_c,
            host.hostname
        FROM
            control_panel
        LEFT JOIN host ON control_panel.id = host.ip
        WHERE
            control_panel.time_range = ? AND
            control_panel.rrd_type = 'host'$host_where";
$params = array(
    $range
);
if (!$rs = & $conn->Execute($sql, $params)) {
    die($conn->ErrorMsg());
}
$hosts = $ext_hosts = array();
$global_a = $global_c = 0;
while (!$rs->EOF) {
    $ip = $rs->fields['id'];
    $net = host_get_network_data($ip);
    // No perms over the host's network
    if ($net === false) {
        $rs->MoveNext();
        continue;
        // Host doesn't belong to any network
        
    } elseif ($net === null) {
        $threshold_a = $conf_threshold;
        $threshold_c = $conf_threshold;
        // User got perms
        
    } else {
        // threshold inheritance
        $threshold_a = $rs->fields['threshold_a'] ? $rs->fields['threshold_a'] : $net['threshold_a'];
        $threshold_c = $rs->fields['threshold_c'] ? $rs->fields['threshold_c'] : $net['threshold_c'];
    }
	
	// No perms over the host (by sensor filter)
	/* Patch: already filtered
    if (!Session::hostAllowed($conn,$ip)) {
		$rs->MoveNext();
		continue;
	}
	*/
	
    // get host & global metrics
    $current_a = get_current_metric($ip, 'host', 'attack');
    $current_c = get_current_metric($ip, 'host', 'compromise');
    $global_a+= $current_a;
    $global_c+= $current_c;
    // only show hosts over their threshold
    $max_a_level = round($rs->fields['max_a'] / $threshold_a);
    $current_a_level = round($current_a / $threshold_a);
    $max_c_level = round($rs->fields['max_c'] / $threshold_c);
    $current_c_level = round($current_c / $threshold_c);
    //* comment out this if you want to see all hosts
    if ($max_a_level <= 1 && $current_a_level <= 1 && $max_c_level <= 1 && $current_c_level <= 1) {
        $rs->MoveNext();
        continue;
    }
    //*/
    $name = Host::ip2hostname($conn, $ip);
    // $name = $rs->fields['hostname'] ? $rs->fields['hostname'] : $ip;
    if ($net === null) {
        $ext_hosts[$ip] = array(
            'name' => $name,
            'threshold_a' => $threshold_a,
            'threshold_c' => $threshold_c,
            'max_c' => $rs->fields['max_c'],
            'max_a' => $rs->fields['max_a'],
            'max_c_date' => $rs->fields['max_c_date'],
            'max_a_date' => $rs->fields['max_a_date'],
            'current_a' => $current_a,
            'current_c' => $current_c,
        );
    } else {
        $data = array(
            'name' => $name,
            'threshold_a' => $threshold_a,
            'threshold_c' => $threshold_c,
            'max_c' => $rs->fields['max_c'],
            'max_a' => $rs->fields['max_a'],
            'max_c_date' => $rs->fields['max_c_date'],
            'max_a_date' => $rs->fields['max_a_date'],
            'current_a' => $current_a,
            'current_c' => $current_c,
            'network' => $net['name'],
            'group' => $net['group']
        );
        $hosts[$ip] = $data;
        $group = $net['group'];
        $net_name = $net['name'];
        if ($group) {
            $groups[$group]['nets'][$net_name]['hosts'][$ip] = $data;
        } else {
            $networks[$net_name]['hosts'][$ip] = $data;
        }
        //printr($data);
        
    }
    $rs->MoveNext();
}
////////////////////////////////////////////////////////////////
// Global score
////////////////////////////////////////////////////////////////
$global = get_score("global_$user", 'global');
$global['current_a'] = get_current_metric('global', 'global', 'attack');
$global['current_c'] = get_current_metric('global', 'global', 'compromise');
$global['threshold_a'] = $conf_threshold;
$global['threshold_c'] = $conf_threshold;
////////////////////////////////////////////////////////////////
// Permissions & Ordering
////////////////////////////////////////////////////////////////
foreach($networks as $net => $net_data) {
    $net_perms = $net_data['has_perms'];
    if (!$net_perms) {
        unset($networks[$net]);
    }
}
// Groups
$order_by_risk_type = 'compromise';
uasort($groups, 'order_by_risk');
foreach($groups as $group => $group_data) {
    $group_perms = $group_data['has_perms'];
    uasort($groups[$group]['nets'], 'order_by_risk');
    foreach($group_data['nets'] as $net => $net_data) {
        $net_perms = $net_data['has_perms'];
        if (isset($groups[$group]['nets'][$net]['hosts'])) {
            uasort($groups[$group]['nets'][$net]['hosts'], 'order_by_risk');
        }
        // the user doesn't have perms over the group but only over
        // some networks of it. List that networks as networks outside
        // groups.
        if (!$group_perms && $net_perms) {
			$networks[$net] = $net_data;
        }
    }
    if (!$group_perms) {
        unset($groups[$group]);
    }
}
// Networks outside groups
uasort($networks, 'order_by_risk');
// Hosts in networks
uasort($hosts, 'order_by_risk');
// Host outside networks
uasort($ext_hosts, 'order_by_risk');
////////////////////////////////////////////////////////////////
// HTML Code
////////////////////////////////////////////////////////////////

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("Control Panel"); ?> </title>
  <!-- <meta http-equiv="refresh" content="150"> -->
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <? include ("../host_report_menu.php") ?>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script language="javascript">
	var reload = true;
	function refresh() {
		if (reload == true) document.location.reload();
	}
	function postload() {
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
        	reload = false;
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,'80%','75%');
            return false;
        });
        $("area.greybox").click(function(){
			reload = false;
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,400,'650');
            return false;
        });
        setTimeout('refresh()',30000);
    }
    function GB_onclose() {
        document.location.reload();
    }
    function toggle(type, start_id, end_id, link_id)
    {
        if ($("#"+link_id+'_c').html() == '+') {
			for (i=0; i < end_id; i++) {
				id = start_id + i;
                tr_id = type + '_' + id;
                $("#"+tr_id+'_c').show();
                $("#"+tr_id+'_a').show();
            }
            $("#"+link_id+'_c').html('-');
            $("#"+link_id+'_a').html('-');
        } else {
            for (i=0; i < end_id; i++) {
                id = start_id + i;
                tr_id = type + '_' + id;
                $("#"+tr_id+'_c').hide();
                $("#"+tr_id+'_a').hide();
            }
            $("#"+link_id+'_c').html('+');
            $("#"+link_id+'_a').html('+');
        }
    }
  </script>
  <style type="text/css">

  body.score {
      margin-right: 5px;
      margin-left: 5px;
  }
  </style>
  
  
  
</head>
<body class="score">
<?php
include ("../hmenu.php"); ?>
<table width="100%" align="center" style="border: 0px;">
<tr>
<td class="noborder" colspan="2">
<!--

Page Header (links, riskmeter, rrd)

-->
    <table width="100%" align="center" style="border: 0px;">
    <tr>
    <td colspan="2" class="noborder" style="padding-bottom:5px">
    <?php
foreach(array(
    'day' => _("Last day") ,
    'week' => _("Last week") ,
    'month' => _("Last month") ,
    'year' => _("Last year")
) as $r => $text) {
    if ($r == $range) echo '<b>';
?>
       <a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?range=<?php echo $r ?>"><?php echo $text ?></a> 
    <?php
    if ($r == $range) echo '</b>';
    if ($r!="year") echo " | ";
} ?>
    </td>
    </tr>
    <tr>
    <td class="noborder">
    <map id="riskmap" name="riskmap">
    <?php
define("GRAPH_HEIGHT", 100);
define("GRAPH_WIDTH", 400);
define("GRAPH_BORDER_HEIGHT", 42);
define("GRAPH_BORDER_WIDTH", 50);
define("GRAPH_ZOOM", "0.85");
$time_range = time();
switch ($range) {
    case 'day':
        $nmapitems = 4 * 6;
        $basetime = 60 * 60;
        $deltax0_percent = ($basetime - (intval(date("i")) * 60 + intval(date("s")))) / $basetime;
        break;

    case 'week':
        $nmapitems = 7 * 4;
        $basetime = 6 * 60 * 60;
        $deltax0_percent = ($basetime - ((intval(date("G")) % 6) * 3600 + intval(date("i")) * 60 + intval(date("s")))) / $basetime;
        break;

    case 'month':
        $nmapitems = 4 * 7;
        $basetime = 24 * 60 * 60;
        $deltax0_percent = ($basetime - (intval(date("G")) * 3600 + intval(date("i")) * 60 + intval(date("s")))) / $basetime;
        break;

    case 'year':
        $nmapitems = 12;
        $basetime = intval(date("t")) * 24 * 60 * 60;
        $deltax0_percent = ($basetime - (intval(date("j")) * 24 * 3600 + intval(date("G")) * 3600 + intval(date("i")) * 60 + intval(date("s")))) / $basetime;
        break;

    default:
        die(ossim_error('Invalid range'));
}
$zoom = floatval(GRAPH_ZOOM);
$xcanvas = 0;
$ycanvas = 0;
$wcanvas = GRAPH_WIDTH * $zoom;
$hcanvas = GRAPH_HEIGHT * $zoom;
$deltax = $wcanvas / $nmapitems;
$deltay = $hcanvas;
$deltax0 = $deltax0_percent * $deltax;
$cx = $xcanvas + (GRAPH_BORDER_WIDTH * $zoom);
$cy = $ycanvas + (GRAPH_BORDER_HEIGHT * $zoom);
$i = 0;
if ($deltax0 > 0) {
    $start_epoch = $time_range - ($nmapitems * $basetime);
    $start_acid = date("Y-m-d H:i:s", $start_epoch);
    $end_epoch = $time_range - ($nmapitems * $basetime) + $deltax0_percent * $basetime;
    $end_acid = date("Y-m-d H:i:s", $end_epoch);
    //        echo "<area shape=\"rect\" target=\"_blank\" href=\"".Util::get_acid_events_link($start_acid,$end_acid)."\" title=\"$start_acid -> $end_acid\" coords=\"".round($cx).",".round($cy).",".round($cx+$deltax0).",".round($cy+$deltay)."\">\n";
    echo "<area class=\"greybox\" shape=\"rect\" target=\"_blank\" href=\"find_peaks.php?start=" . $start_epoch . "&end=" . $end_epoch . "&type=" . "host" . "&range=" . $range . "\" title=\"$start_acid -> $end_acid\" coords=\"" . round($cx) . "," . round($cy) . "," . round($cx + $deltax0) . "," . round($cy + $deltay) . "\">\n";
    $cx = $cx + $deltax0;
    $i++;
    $nmapitems++;
}

for (; $i < $nmapitems; $i++) {
    $start_epoch = $time_range + $deltax0_percent * $basetime - (($nmapitems - $i) * $basetime);
    $start_acid = date("Y-m-d H:i:s", $start_epoch);
    $end_epoch = $time_range + $deltax0_percent * $basetime - (($nmapitems - $i) * $basetime) + $basetime;
    $end_acid = date("Y-m-d H:i:s", $end_epoch);
    //        echo "<area shape=\"rect\" target=\"_blank\" href=\"".Util::get_acid_events_link($start_acid,$end_acid)."\" title=\"$start_acid -> $end_acid\" coords=\"".round($cx).",".round($cy).",".round($cx+$deltax).",".round($cy+$deltay)."\">\n";
    echo "<area class=\"greybox\" shape=\"rect\" target=\"_blank\" href=\"find_peaks.php?start=" . $start_epoch . "&end=" . $end_epoch . "&type=" . "host" . "&range=" . $range . "\" title=\"$start_acid -> $end_acid\" coords=\"" . round($cx) . "," . round($cy) . "," . round($cx + $deltax) . "," . round($cy + $deltay) . "\">\n";
    $cx = $cx + $deltax;
}


?>
    </map>
    <img usemap="#riskmap" border=0 src="../report/graphs/draw_rrd.php?ip=global_<?php echo $user
?>&what=compromise&start=<?php echo $rrd_start
?>&end=N&type=global&zoom=<?php echo GRAPH_ZOOM
?>">
    </td>
    <td class="noborder">
    <table>
		<tr>
          <? if (Session::menu_perms("MenuControlPanel", "MonitorsRiskmeter")) { ?>
		  <th><?php echo _("Riskmeter") ?></th>
		  <? } ?>
          <th><?php echo _("Service Level") ?>&nbsp;</th>
        </tr>
		
		<tr>
			<? if (Session::menu_perms("MenuControlPanel", "MonitorsRiskmeter")) { ?>
			<td class="noborder">
				<a class="greybox" href="../riskmeter/index.php" title='<?=_("Riskmeter")?>'><img border="0" src="../pixmaps/riskmeter.png"/></a>
			</td>
			<? } ?>
			<?php echo html_service_level() ?>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</td>
</tr>
<tr>
<?php
foreach(array(
    'compromise',
    'attack'
) as $metric_type) {
    $a = 1;
    $net = $host = 0;
    if ($metric_type == 'compromise') {
        $title = _("C O M P R O M I S E");
        $ac = 'c';
    } else {
        $title = _("A T T A C K");
        $ac = 'a';
    }
?>
<td width="50%" class="noborder" valign="top">
    <table width="100%" align="center">
    <tr><td colspan="6"><center><b><?php echo $title
?></b></center></td></tr>
    <tr>
        <th colspan="6" class="noborder"><?php echo _("Global") ?></th>
    </tr>
<!--

Global

-->
    <tr>
        <th colspan="3"><?php echo _("Global") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
    <tr>
        <td colspan="2"><b><?php echo _("GLOBAL SCORE") ?><b></td>
        <?php
    html_set_values("global_$user", 'global', $global["max_$ac"], $global["max_{$ac}_date"], $global["current_$ac"], $global["threshold_$ac"], $ac);
?>
        <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
        <td nowrap><?php echo html_date() ?></td>
        <?php echo html_max() ?>
        <?php echo html_current() ?>
    </tr>
    <tr>
        <td colspan="6" class="noborder">&nbsp;</td>
    </tr>
<!--

Network Groups

-->
    <?php
    if (count($groups)) { ?>
    <tr>
        <th colspan="6" class="noborder"><?php echo _("Network Groups") ?></th>
    </tr>
    <tr>
        <th colspan="3"><?php echo _("Group") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
        <?php
        foreach($groups as $group_name => $group_data) {
            $num_nets = count($group_data['nets']);
?>
            <tr>
            <td class="noborder">
                <a id="a_<?php echo ++$a
?>_<?php echo $ac
?>" href="javascript: toggle('net', <?php echo $net + 1 ?>, <?php echo $num_nets ?>, 'a_<?php echo $a ?>');">+</a>
            </td>
            <td style="text-align: left"><b><?php echo $group_name ?></b></td>
            <?php
            html_set_values('group_' . $group_name, 'net', $group_data["max_$ac"], $group_data["max_{$ac}_date"], $group_data["current_$ac"], $group_data["threshold_$ac"], $ac);
?>
            <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
            <td nowrap><?php echo html_date() ?></td>
            <?php echo html_max() ?>
            <?php echo html_current() ?>
            </tr>
            <?php
            foreach($group_data['nets'] as $net_name => $net_data) {
                $net++;
                $num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
?>
                <tr id="net_<?php echo $net
?>_<?php echo $ac
?>" style="display: none">
                    
                    <td width="3%" class="noborder">&nbsp;</td>
                    <td style="text-align: left">
                        <?php
                if ($num_hosts) { ?>
                        <a id="a_<?php echo ++$a
?>_<?php echo $ac
?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, 'a_<?php echo $a ?>');">+</a>&nbsp;
                        <?php
                } ?>
                        <?php echo $net_name ?>
                    </td>
                    <?php
                html_set_values($net_name, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac);
?>
                    <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                    <td nowrap><?php echo html_date() ?></td>
                    <?php echo html_max() ?>
                    <?php echo html_current() ?>
                </tr>
                <?php
                if (isset($net_data['hosts'])) {
                    foreach($net_data['hosts'] as $host_ip => $host_data) {
                        $host++;
?>
                        <tr id="host_<?php echo $host
?>_<?php echo $ac
?>" style="display: none">
                            <td width="6%" style="border: 0px;">&nbsp;</td>
                            <td style="text-align: left">&nbsp;&nbsp;
                                <?php echo html_host_report($host_ip, $host_data['name']) ?>
                            </td>
                            <?php
                        html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);
?>
                            <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                            <td nowrap><?php echo html_date() ?></td>
                            <?php echo html_max() ?>
                            <?php echo html_current() ?>
                        </tr>   
                   <?php
                    } ?>
               <?php
                } ?>
            <?php
            } ?>
        <?php
        } ?>
    <?php
    } ?>
<!--

Network outside groups

-->
    <?php
    if (count($networks)) { ?>
    <tr>
        <th colspan="6" class="noborder"><?php echo _("Networks outside groups") ?></th>
    </tr>
    <tr>
        <th colspan="3"><?php echo _("Network") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
        <?php
        $i = 0;
        foreach($networks as $net_name => $net_data) {
			$num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
?>
        <tr>
        <td colspan="2" style="text-align: left">
            <?php
            if ($num_hosts) { ?>
            <a id="a_<?php echo ++$a
?>_<?php echo $ac
?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, 'a_<?php echo $a ?>');">+</a>&nbsp;
            <?php
            } ?>
            <a href="../report/host_report.php?host=<?=$net_data['address']?>" id="<?=$net_data['address']?>;<?=$net_name?>" class="NetReportMenu"><b><?php echo $net_name
			?></b></a>
        </td>
        <?php
            html_set_values($net_name, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac);
?>
        <td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
        <td nowrap><?php echo html_date() ?></td>
        <?php echo html_max() ?>
        <?php echo html_current() ?>
        </tr>
            <?php
            if ($num_hosts) {
                uasort($net_data['hosts'], 'order_by_risk');
                foreach($net_data['hosts'] as $host_ip => $host_data) {
                    $host++;
?>
                    <tr id="host_<?php echo $host
?>_<?php echo $ac
?>" style="display: none">
                        <td width="3%" style="border: 0px;">&nbsp;</td>
                        <td style="text-align: left">&nbsp;&nbsp;
                            <?php echo html_host_report($host_ip, $host_data['name']) ?>
                        </td>
                        <?php
                    html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);
?>
                        <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                        <td nowrap><?php echo html_date() ?></td>
                        <?php echo html_max() ?>
                        <?php echo html_current() ?>
                    </tr>   
               <?php
                } ?>
           <?php
            } ?>
        <?php
        } ?>
    <?php
    } ?>
<!--

Hosts

-->
    <?php
    if (count($hosts)) { ?>
    <tr>
        <th colspan="6" class="noborder"><?php echo _("Hosts") ?></th>
    </tr>
    <tr>
        <th colspan="3"><?php echo _("Host Address") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
        <?php
        $i = 0;
        foreach($hosts as $ip => $host_data) {
            $group = $host_data['group'] ? " - " . $host_data['group'] : '';
?>
        <tr>
        <td nowrap colspan="2" style="text-align: left">
          <?php echo html_host_report($ip, $host_data['name'], $host_data['network'] . $group) ?>
        </td>
        <?php
            html_set_values($ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);
?>
        <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
        <td nowrap><?php echo html_date() ?></td>
        <?php echo html_max() ?>
        <?php echo html_current() ?>
        </tr>
        <?php
        } ?>
    <?php
    } ?>
<!--

Hosts outside networks

-->
    <?php
    if (count($ext_hosts)) { ?>
    <tr>
        <th colspan="6" class="noborder"><?php echo _("Hosts outside defined networks") ?></th>
    </tr>
    <tr>
        <th colspan="3"><?php echo _("Host Address") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
        <?php
        $i = 0;
        foreach($ext_hosts as $ip => $host_data) {
?>
        <tr>
        <td colspan="2" style="text-align: left">
            <?php echo html_host_report($ip, $ip) ?>
        </td>
        <?php
            html_set_values($ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);
?>
        <td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
        <td nowrap><?php echo html_date() ?></td>
        <?php echo html_max() ?>
        <?php echo html_current() ?>
        </tr>
        <?php
        } ?>
    <?php
    } ?>
    </table>
</td>
<?php
} ?>

</td>
</tr>
</table>
<br>
<b><?php echo _("Legend") ?>:</b><br>
<table width="30%" align="left">
<tr>
    <?php echo _html_metric(0, 100, '#') ?>
    <td><?php echo _("No appreciable risk") ?></td>
</tr>
<tr>
    <?php echo _html_metric(101, 100, '#') ?>
    <td><?php echo _("Metric over 100% threshold") ?></td>
</tr>
<tr>
    <?php echo _html_metric(301, 100, '#') ?>
    <td><?php echo _("Metric over 300% threshold") ?></td>
</tr>
<tr>
    <?php echo _html_metric(501, 100, '#') ?>
    <td><?php echo _("Metric over 500% threshold") ?></td>
</tr>
</table>
<br>

</body></html>
