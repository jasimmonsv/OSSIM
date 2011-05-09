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
include("global_score_functions.php");

Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
$db = new ossim_db();
$conn = $db->connect();
if (Session::menu_perms("MenuControlPanel", "ControlPanelEvents")) {
    $event_perms = true;
} else {
    $event_perms = false;
}
$event_perms = true; // ControlPanelEvents temporarily disabled
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
$from = (GET('from') != "") ? GET('from') : 0;
$max = 100;

ossim_valid($range, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("range"));
ossim_valid($from, OSS_DIGIT, 'illegal:' . _("from"));
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


// Cache some queries
$host_qualification_cache = get_host_qualification($conn);
$net_qualification_cache = get_net_qualification($conn);
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
$net_limit = " LIMIT $from,$max";
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
    $net_current_a = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'attack');
    $net_current_c = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'compromise');
    
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
    // Changed: get networks by AJAX
    
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
$sql = "SELECT net_name FROM net_group_reference";
if (!$rs = & $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}
$nets_grouped = array();
while (!$rs->EOF) {
	$nets_grouped[$rs->fields['net_name']]++;
	$rs->MoveNext();
}

$sql = "SELECT
            net.name as net_name,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address
        FROM
            net
        WHERE
            net.name$net_where$net_limit";
if (!$rs = & $conn->Execute($sql)) {
    die($conn->ErrorMsg());
}

$networks = array();
$count = 1;
while (!$rs->EOF) {
	// check perms over the network
    //$has_net_perms = check_net_perms($rs->fields['net_address']);
	// Fixed: netAllowed check net/sensor granularity perms
	//$has_perms = Session::netAllowed($conn, $rs->fields['net_address']);
	$has_perms = true;
    // if no perms over the network, try perms over the related sensor
    //$has_perms = $has_net_perms ? true : check_sensor_perms($rs->fields['net_address'], 'net');
	$net = $rs->fields['net_name'];
	if ($nets_grouped[$net] == "" || $count > $max) { $rs->MoveNext(); continue; }
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
        'current_a' => get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'attack') ,
        'current_c' => get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net', 'compromise') ,
        'has_perms' => $has_perms
    );
    $rs->MoveNext();
    $count++;
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
    // Modified 14/03/2011. This function returns array.
    // Host can be linked into may nets and groups
    $groups_belong = host_get_network_data($ip, $groups, $networks);
    $net_belong = "";
    // No perms over the host's network
    $threshold_a = $conf_threshold;
    $threshold_c = $conf_threshold;
    if (count($groups_belong['nets']) < 1) {
        $rs->MoveNext();
        continue;
        // Host doesn't belong to any network
    /*    
    } elseif ($net === null) {
        $threshold_a = $conf_threshold;
        $threshold_c = $conf_threshold;
        // User got perms
    */  
    } else {
        // threshold inheritance (for multiple nets get the closest)
        $closest_net = Net::GetClosestNet($conn, $ip);
    	foreach ($groups_belong['nets'] as $net_name_aux=>$net) {
    		if ($net_name_aux == $closest_net) {
    			$net_threshold_a = $net['threshold_a'];
    			$net_threshold_c = $net['threshold_c'];
    			$net_belong = $net_name_aux;
    			$group_belong = $net['group'];
    		}
        }
        if ($net_belong == "") {
        	$net_belong = $net_name_aux;
        	$group_belong = $net['group'];
        }
        $threshold_a = $rs->fields['threshold_a'] ? $rs->fields['threshold_a'] : $net_threshold_a;
        $threshold_c = $rs->fields['threshold_c'] ? $rs->fields['threshold_c'] : $net_threshold_c;
    }
	
	// No perms over the host (by sensor filter)
	/* Patch: already filtered
    if (!Session::hostAllowed($conn,$ip)) {
		$rs->MoveNext();
		continue;
	}
	*/
	
    // get host & global metrics
    $current_a = get_current_metric($host_qualification_cache,$net_qualification_cache,$ip, 'host', 'attack');
    $current_c = get_current_metric($host_qualification_cache,$net_qualification_cache,$ip, 'host', 'compromise');
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
    if ($net_belong == "") {
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
            'network' => $net_belong,
            'group' => $group_belong
        );
        $hosts[$ip] = $data;
        if ($group_belong) {
            $groups[$group_belong]['nets'][$net_belong]['hosts'][$ip] = $data;
        } else {
            $networks[$net_belong]['hosts'][$ip] = $data;
        }
        //printr($data);
        
    }
    $rs->MoveNext();
}
////////////////////////////////////////////////////////////////
// Global score
////////////////////////////////////////////////////////////////
$global = get_score("global_$user", 'global');
$global['current_a'] = get_current_metric($host_qualification_cache,$net_qualification_cache,'global', 'global', 'attack');
$global['current_c'] = get_current_metric($host_qualification_cache,$net_qualification_cache,'global', 'global', 'compromise');
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
    //uasort($groups[$group]['nets'], 'order_by_risk');
    foreach($group_data['nets'] as $net => $net_data) {
        $net_perms = $net_data['has_perms'];
        /*
        if (isset($groups[$group]['nets'][$net]['hosts'])) {
            uasort($groups[$group]['nets'][$net]['hosts'], 'order_by_risk');
        }
        */
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
	<title> <?php echo gettext("Control Panel"); ?> </title>
	<!-- <meta http-equiv="refresh" content="150"> -->
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/style.css"/>
	<?php include ("../host_report_menu.php") ?>
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
			setTimeout('refresh()',90000);
		}
    
	function GB_onclose() {
			document.location.reload();
		}
		
		function toggle(type, start_id, end_id, link_id)
		{
			if ($("#"+link_id+'_c').html() == '<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">') {
				for (i=0; i < end_id; i++) {
					id = start_id + i;
					tr_id = type + '_' + id;
					$("#"+tr_id+'_c').show();
					$("#"+tr_id+'_a').show();
				}
				$("#"+link_id+'_c').html('<img src="../pixmaps/minus-small.png" align="absmiddle" border="0">');
				$("#"+link_id+'_a').html('<img src="../pixmaps/minus-small.png" align="absmiddle" border="0">');
			} else {
				for (i=0; i < end_id; i++) {
					id = start_id + i;
					tr_id = type + '_' + id;
					$("#"+tr_id+'_c').hide();
					$("#"+tr_id+'_a').hide();
				}
				$("#"+link_id+'_c').html('<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">');
				$("#"+link_id+'_a').html('<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">');
			}
		}
		
		function toggle_group(group_name,link_id,ac) {
			if ($("#"+link_id+'_'+ac).html() == '<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">') {
				$("#group_"+link_id+'_'+ac).html("<img src='../pixmaps/loading.gif' width='20'>");
				$.ajax({
					type: "GET",
					url: "global_score_ajax.php?group_name="+group_name+"&ac="+ac+"&range=<?php echo $range ?>",
					data: "",
					success: function(msg){
						$("#"+link_id+'_'+ac).html('<img src="../pixmaps/minus-small.png" align="absmiddle" border="0">');
						$("#group_"+link_id+'_'+ac).html(msg);
					}
				});
			} else {
				$("#group_"+link_id+'_'+ac).html("");
				$("#"+link_id+'_'+ac).html('<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">');
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

	<?php include ("../hmenu.php"); ?>
	<table width="100%" align="center" class='transparent'>
		<tr>
			<td class="noborder" colspan="2">
				<!--
				Page Header (links, riskmeter, rrd)
				-->
				
				<table width="100%" align="center" class='transparent'>
					<tr>
						<td colspan="2" class="noborder" style="padding-bottom:5px">
						<?php
						foreach(array('day' => _("Last day"), 'week' => _("Last week"), 'month' => _("Last month"), 'year' => _("Last year") ) as $r => $text) 
						{
							if ($r == $range) echo '<b>';
							?>
								<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?range=<?php echo $r ?>"><?php echo $text ?></a> 
							<?php
							if ($r == $range) echo '</b>';
							if ($r!="year") echo " | ";
						} 
						?>
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
							
							$zoom    = floatval(GRAPH_ZOOM);
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
							<img usemap="#riskmap" border=0 src="../report/graphs/draw_rrd.php?ip=global_<?php echo $user?>&what=compromise&start=<?php echo $rrd_start?>&end=N&type=global&zoom=<?php echo GRAPH_ZOOM?>"/>
						</td>
						
						<td class="noborder">
							<table>
								<tr>
								  <?php if (Session::menu_perms("MenuControlPanel", "MonitorsRiskmeter")) { ?>
								  <th><?php echo _("Riskmeter") ?></th>
								  <? } ?>
								  <th><?php echo _("Service Level") ?>&nbsp;</th>
								</tr>
								
								<tr>
									<?php if (Session::menu_perms("MenuControlPanel", "MonitorsRiskmeter")) { ?>
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
		foreach(array('compromise', 'attack') as $metric_type) 
		{
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
					<tr><td colspan="6"><center><strong><?php echo $title?></strong></center></td></tr>
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
						html_set_values_session("global_$user", 'global', $global["max_$ac"], $global["max_{$ac}_date"], $global["current_$ac"], $global["threshold_$ac"], $ac);
						?>
						<td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
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
				if (count($groups)) 
				{ 
				?>
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
					foreach($groups as $group_name => $group_data) 
					{
						$num_nets = count($group_data['nets']);
						?>
						<tr>
							<td class="noborder">
								<?php if (round($group_data["max_$ac"] / $group_data["threshold_$ac"] * 100) > 100) { ?>
								<a id="a_<?php echo ++$a ?>_<?php echo $ac ?>" href="javascript: toggle_group('<?php echo $group_name ?>','a_<?php echo $a ?>','<?php echo $ac ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></img></a>
								<?php } ?>
							</td>
							
							<td style="text-align: left"><b><?php echo $group_name ?></b></td>
							<?php html_set_values('group_' . $group_name, 'net', $group_data["max_$ac"], $group_data["max_{$ac}_date"], $group_data["current_$ac"], $group_data["threshold_$ac"], $ac); ?>
							
							<td nowrap='nowrap'>
								<a href="<?php echo Util::graph_image_link('group_' . $group_name, 'net', $metric_type, $rrd_start, "N", 1, $range) ?>"><img src="../pixmaps/graph.gif" border="0"/></a>&nbsp;
								<a href="../incidents/newincident.php?ref=Metric&title=<?php echo urlencode(_("Metric Threshold: ".strtoupper($ac)." level exceeded")." (Net: group_$group_name)") ?>&priority=<?php echo $group_data["max_$ac"]/$group_data["threshold_$ac"] ?>&target=<?php echo urlencode("Net: group_$group_name") ?>&metric_type=<?php echo $metric_type ?>&metric_value=<?php echo $metric_type ?>&event_start=<?php echo $group_data["max_{$ac}_date"] ?>&event_end=<?php echo $group_data["max_{$ac}_date"] ?>"><img src="../pixmaps/incident.png" width="12" alt="i" border="0"/></a>
							</td>
							
							<td nowrap='nowrap'><?php echo ($group_data["max_{$ac}_date"] == 0 || strtotime($group_data["max_{$ac}_date"]) == 0) ? _('n/a') : $group_data["max_{$ac}_date"] ?></td>
							<?php
							// Group MAX
							$link_aux = ($group_data["max_{$ac}_date"] == 0) ? "#" : Util::get_acid_date_link($group_data["max_{$ac}_date"]);
							echo _html_metric($group_data["max_$ac"], $group_data["threshold_$ac"], $link_aux);
							
							// Group CURRENT
							echo _html_metric($group_data["current_$ac"], $group_data["threshold_$ac"], $link);
							?>
						</tr>
						
						<tr>
							<td colspan="6" class="nobborder"><div id="group_a_<?php echo $a ?>_<?php echo $ac ?>"></div></td>
						</tr>
					<?php
					} 
				} 	
				?>
					
				<!--
				Network outside groups
				-->

				<?php
				if (count($networks)) 
				{ 
					?>
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
					foreach($networks as $net_name => $net_data) 
					{
						$num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
						?>
						<tr>
							<td colspan="2" style="text-align: left">
							<?php
							if ($num_hosts) 
							{ 
								?>
								<a id="a_<?php echo ++$a?>_<?php echo $ac?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, 'a_<?php echo $a ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></a>&nbsp;
								<?php
							} ?>
							<a href="../report/host_report.php?host=<?=$net_data['address']?>" id="<?=$net_data['address']?>;<?=$net_name?>" class="NetReportMenu"><b><?php echo $net_name?></b></a>
						</td>
						
						<?php html_set_values($net_name, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac); ?>
						
						<td nowrap='nowrap'><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
						</tr>
						
						<?php
						if ($num_hosts) 
						{
							uasort($net_data['hosts'], 'order_by_risk');
							foreach($net_data['hosts'] as $host_ip => $host_data) 
							{
								$host++;
								?>
								<tr id="host_<?php echo $host?>_<?php echo $ac?>" style="display: none">
									<td width="3%" style="border: 0px;">&nbsp;</td>
									<td style="text-align: left">&nbsp;&nbsp;
										<?php echo html_host_report($host_ip, $host_data['name']) ?>
									</td>
									<?php html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);?>
									<td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
									<td><?php echo html_date() ?></td>
									<?php echo html_max() ?>
									<?php echo html_current() ?>
								</tr>   
								<?php
							} 
						} 
					} 
				} 
			?>
			
			<!--
			Hosts
			-->
			
			<?php
			if (count($hosts)) 
			{ 
			?>
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
				foreach($hosts as $ip => $host_data) 
				{
					$group = $host_data['group'] ? " - " . $host_data['group'] : '';
					?>
					<tr>
						<td nowrap='nowrap' colspan="2" style="text-align: left">
						  <?php echo html_host_report($ip, $host_data['name'], $host_data['network'] . $group) ?>
						</td>
						
						<?php html_set_values($ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);?>
						
						<td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
					</tr>
					<?php
				} 
			} 
		
			?>
			
			<!--
			Hosts outside networks
			-->
			
			<?php
			if (count($ext_hosts)) 
			{ 
				?>
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
				foreach($ext_hosts as $ip => $host_data) 
				{
					?>
					<tr>
						<td colspan="2" style="text-align: left">
							<?php echo html_host_report($ip, $ip) ?>
						</td>
						<?php html_set_values($ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac); ?>
						<td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
					</tr>
				<?php
				} 
			} 
			?>
			</table>
		</td>
			<?php
		} 
		?>

		</td>
	</tr>
</table>

<div style='padding: 10px 0px 5px 5px; font-weight: bold;'><?php echo _("Legend") ?>:</div>
<table width="30%" align="left" style='margin-left: 10px;'>
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
		<td class='nobborder center'><?php echo _("Metric over 500% threshold") ?></td>
	</tr>
</table>
<br/>

</body></html>
