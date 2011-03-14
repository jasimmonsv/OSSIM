<?php 
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

function get_host_qualification($conn) {
	$arr = array();
	$sql = "SELECT host_ip, compromise, attack FROM host_qualification";
	if (!$rs = & $conn->Execute($sql)) {
		die($conn->ErrorMsg());
	}
	while (!$rs->EOF) {
		$arr[$rs->fields['host_ip']]['attack'] = $rs->fields['attack'];
		$arr[$rs->fields['host_ip']]['compromise'] = $rs->fields['compromise'];
		$rs->MoveNext();
	}
	return $arr;
}
function get_net_qualification($conn) {
	$arr = array();
	$sql = "SELECT net_name, compromise, attack FROM net_qualification";
	if (!$rs = & $conn->Execute($sql)) {
		die($conn->ErrorMsg());
	}
	while (!$rs->EOF) {
		$arr[$rs->fields['net_name']]['attack'] = $rs->fields['attack'];
		$arr[$rs->fields['net_name']]['compromise'] = $rs->fields['compromise'];
		$rs->MoveNext();
	}
	return $arr;
}

function get_current_metric($host_qualification_cache, $net_qualification_cache, $name, $type = 'host', $ac = 'attack') {
    static $qualification;
    global $conn;
    if (!$qualification) {
        $qualification['global']['global']['attack'] = 0;
        $qualification['global']['global']['compromise'] = 0;
        foreach ($host_qualification_cache as $host=>$fields) {
            $host = $fields['host_ip'];
            $qualification['host'][$host]['attack'] = $fields['attack'];
            $qualification['global']['global']['attack']+= $fields['attack'];
            $qualification['host'][$host]['compromise'] = $fields['compromise'];
            $qualification['global']['global']['compromise']+= $fields['compromise'];
        }
        
        foreach ($net_qualification_cache as $net=>$fields) {
            $host = $net;
            $qualification['net'][$host]['attack'] = $fields['attack'];
            $qualification['net'][$host]['compromise'] = $fields['compromise'];
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
function host_get_network_data($ip, $groups, $networks) {
    // search in groups
    $groups_belong['groups'] = array();
    $groups_belong['nets'] = array();
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
                    continue;
                }
                $groups_belong['groups'][$group_name]++;
                $groups_belong['nets'][$net_name] = $n_data;
            }
        }
    }
    // search in nets
    foreach($networks as $net_name => $n_data) {
        $address = $n_data['address'];
        if ($address != "" && Net::isIpInNet($ip, $address)) {
            if (!$n_data['has_perms'] && !check_sensor_perms($ip, 'host')) {
                continue;
            }
            $groups_belong['nets'][$net_name] = $n_data;
        }
    }
    return $groups_belong;
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
function html_set_values_session($subject, $subject_type, $max, $max_date, $current, $threshold, $ac) {
    $_SESSION['global_score']['_subject'] = $subject;
    $_SESSION['global_score']['_subject_type'] = $subject_type;
    $_SESSION['global_score']['_max'] = $max;
    $_SESSION['global_score']['_max_date'] = $max_date;
    $_SESSION['global_score']['_current'] = $current;
    $_SESSION['global_score']['_threshold'] = $threshold;
    $_SESSION['global_score']['_ac'] = $ac;
}
function ajax_set_values() {
	$GLOBALS['_subject'] = $_SESSION['global_score']['_subject'];
    $GLOBALS['_subject_type'] = $_SESSION['global_score']['_subject_type'];
    $GLOBALS['_max'] = $_SESSION['global_score']['_max'];
    $GLOBALS['_max_date'] = $_SESSION['global_score']['_max_date'];
    $GLOBALS['_current'] = $_SESSION['global_score']['_current'];
    $GLOBALS['_threshold'] = $_SESSION['global_score']['_threshold'];
    $GLOBALS['_ac'] = $_SESSION['global_score']['_ac'];
}
function _html_metric($metric, $threshold, $link) {
    global $event_perms;
    $risk = round($metric / $threshold * 100);
    $font_color = 'color="white"';
    $color = '';
    if ($risk > 500) {
        $color = 'bgcolor="#FF0000"';
        $risk = gettext("high");
    } elseif ($risk > 300) {
        $color = 'bgcolor="orange"';
        $risk = gettext("med");
    } elseif ($risk > 100) {
        $color = 'bgcolor="green"';
        $risk = gettext("low");
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
?>