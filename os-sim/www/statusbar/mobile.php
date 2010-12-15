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
* - html_service_level()
* - global_score()
* Classes list:
*/
set_time_limit(180);
require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Alarm.inc';
require_once 'classes/Util.inc';
require_once 'classes/SecurityReport.inc';
require_once("classes/Session.inc");
Session::logcheck("MainMenu", "Index");

function html_service_level($conn) {
    global $user;
    $range = "day";
    $level = 100;
    $class = "level4";
    //
    $sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
    $params = array(
        "global_$user",
        $range
    );
    if (!$rs = & $conn->Execute($sql, $params)) {
        echo "error";
        die($conn->ErrorMsg());
    }
    if ($rs->EOF) {
        return array(
            $level,
            "level11"
        );
    }
    $level = number_format(($rs->fields["c_sec_level"] + $rs->fields["a_sec_level"]) / 2, 0);
    $class = "level" . round($level / 9, 0);
    return array(
        $level,
        $class
    );
}
function get_siem_events($conn,$date) {
	$data = array();
    $sql = "SELECT COUNT(acid_event.cid) as num_events, hour(timestamp) as intervalo FROM snort.acid_event WHERE timestamp >= ? GROUP BY intervalo";
    $params = array( $date );
    if (!$rs = & $conn->Execute($sql, $params)) {
        echo "error";
        die($conn->ErrorMsg());
    }
    while (!$rs->EOF) {
    	$data[] = $rs->fields;
    	$rs->MoveNext();
    }
    //
    $events = 0;
    $sql = "SELECT COUNT(*) as num_events FROM snort.acid_event";
    if (!$rs = & $conn->Execute($sql)) {
        echo "error";
        die($conn->ErrorMsg());
    }
    if (!$rs->EOF) {
    	$events = $rs->fields[0];
    }
    return array($data,$events);
}
function global_score($conn) {
    global $conf_threshold;
    //
    $sql = "SELECT host_ip, compromise, attack FROM host_qualification";
    if (!$rs = & $conn->Execute($sql)) {
        die($conn->ErrorMsg());
    }
    $score_a = 0;
    $score_c = 0;
    while (!$rs->EOF) {
        if (Session::hostAllowed($conn,$rs->fields['host_ip'])) {
			$score_a+= $rs->fields['attack'];
			$score_c = $rs->fields['compromise'];
		}
        $rs->MoveNext();
    }
    $risk_a = round($score_a / $conf_threshold * 100);
    $risk_c = round($score_c / $conf_threshold * 100);
    $risk = ($risk_a > $risk_c) ? $risk_a : $risk_c;
    $img = 'green'; // 'off'
    $color = '';
    if ($risk > 500) {
        $img = 'red';
    } elseif ($risk > 300) {
        $img = 'yellow';
    } elseif ($risk > 100) {
        $img = 'green';
    }
    $alt = "$risk " . _("metric/threshold");
    return array(
        $img,
        $alt
    );
}
function get_sensor_sids($conn2) {
	$query = "SELECT * FROM snort.sensor";
	if (!$rs = & $conn2->Execute($query)) {
		print $conn2->ErrorMsg();
		exit();
	}
	while (!$rs->EOF) {
		$sname = ($rs->fields['sensor']!="") ? $rs->fields['sensor'] : preg_replace("/-.*/","",preg_replace("/.*\]\s*/","",$rs->fields['hostname']));
		$ret[$sname][] = $rs->fields['sid'];
		$rs->MoveNext();
	}
	return $ret;
}
function top_siem_events($conn,$limit) {
	$data = array();
	$sensor_where = "";
	$sensor_join = "";
	if (Session::allowedSensors() != "") {
		$user_sensors = explode(",",Session::allowedSensors());
		$snortsensors = get_sensor_sids($conn);
		$sids = array();
		foreach ($user_sensors as $user_sensor) {
			//echo "Sids de $user_sensor ".$snortsensors[$user_sensor][0]."<br>";
			if (count($snortsensors[$user_sensor]) > 0)
				foreach ($snortsensors[$user_sensor] as $sid) if ($sid != "")
					$sids[] = $sid;
		}
		if (count($sids) > 0) {
			$sensor_where = " AND acid_event.plugin_id=alarm.plugin_id AND acid_event.plugin_sid=alarm.plugin_sid AND acid_event.sid in (".implode(",",$sids).")";
			$sensor_where_ac = " WHERE acid_event.sid in (".implode(",",$sids).")";
		} else {
			$sensor_where = " AND acid_event.plugin_id=alarm.plugin_id AND acid_event.plugin_sid=alarm.plugin_sid AND acid_event.sid in (0)"; // Vacio
			$sensor_where_ac = " WHERE acid_event.sid in (0)"; // Vacio
		}
		$sensor_join = ($counter == 1) ? "snort.acid_event as acid_event," : "snort.acid_event,";
	}
    if ($sensor_where_ac!="")
        $query = "SELECT count(*) as num, plugin_sid.name FROM ".str_replace(",","",$sensor_join)." LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $sensor_where_ac GROUP BY name ORDER BY num DESC LIMIT $limit";
	else
        $query = "SELECT sum(ac.sig_cnt) as num, plugin_sid.name FROM snort.ac_alerts_signature AS ac LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=ac.plugin_id AND plugin_sid.sid=ac.plugin_sid GROUP BY name ORDER BY num DESC LIMIT $limit";
    if (!$rs = & $conn->Execute($query)) {
        echo "error";
        die($conn->ErrorMsg());
    }
    while (!$rs->EOF) {
    	$data[Util::signaturefilter($rs->fields["name"])] = $rs->fields["num"];
    	$rs->MoveNext();
    }
    return $data;
}

$screen = GET("screen");
$range = intval(GET("range"));
if ($range==0) $range=31;
ossim_valid($screen, OSS_LETTER, 'illegal:' . _("Screen"));
if (ossim_error()) {
    die(ossim_error());
}

// Database Object
$db = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];
$version = $conf->get_conf("ossim_server_version", FALSE);
$NUM_HOSTS = 5;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>AlienVault OSSIM Console: Current Status</title>
<link rel="stylesheet" type="TEXT/CSS" href="../style/mobile.css">
<meta name='viewport' content='user-scalable=yes, width=device-width;'>
<meta http-equiv="refresh" content="60">
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script><![endif]-->
<script type="text/javascript" src="../js/jquery.flot.pie.js" ></script>
<script type="text/javascript" src="../js/jquery.bgiframe.min.js"></script>
</head>
<body marginwidth=0 marginheight=0 topmargin=0 leftmargin=0 style="background-color:white">

<table border=0 cellpadding=0 cellspacing=0 width="100%">
<? include("mobile_header.php"); ?>
<tr><td>

<?
if ($screen == "status") {

	$conf_threshold = $conf->get_conf('threshold');
	// Get unresolved INCIDENTS
	if (!$order_by) {
	    $order_by = 'life_time';
	    $order_mode = 'ASC';
	}
	$incident_list = Incident::search($conn, array("status"=>"Open"), $order_by, $order_mode, 1, 10);
	$unresolved_incidents = Incident::search_count($conn);
	//$incident_list = Incident::get_list($conn, "ORDER BY date DESC");
	$incident_list = Incident::search($conn, array(), "date", "DESC", 1, 1);
	$incident_date1 = ($incident_list[0]) ? $incident_list[0]->get_date() : 0;
	$incident_ticket_list = Incident_ticket::get_list($conn, "ORDER BY date DESC LIMIT 1");
	$incident_date2 = ($incident_ticket_list[0]) ? $incident_ticket_list[0]->get_date() : 0;
	if ($incident_list[0] || $incident_ticket_list[0]) {
	    $incident_date = (strtotime($incident_date1) > strtotime($incident_date2)) ? $incident_date1 : $incident_date2;
	    if ($incident_date == 0) $incident_date = "__/__/__ --:--:--";
	}
	//$incident_list = Incident::get_list($conn, "ORDER BY priority DESC");
	$incident_list = Incident::search($conn, array(), "priority", "DESC", 1, 1);
	$incident_max_priority = ($incident_list[0]) ? $incident_list[0]->get_priority() : "-";
	$incident_max_priority_id = ($incident_list[0]) ? $incident_list[0]->get_id() : "0";
	// Get unresolved ALARMS
	$unresolved_alarms = Alarm::get_count($conn);
	list($alarm_date, $alarm_date_id) = Alarm::get_max_byfield($conn, "timestamp");
	list($alarm_max_risk, $alarm_max_risk_id) = Alarm::get_max_byfield($conn, "risk");
	if ($alarm_max_risk_id == "") { $alarm_max_risk_id = 0; $alarm_max_risk = "-"; }
	// Get service LEVEL
	//global $conn, $conf, $user, $range, $rrd_start;
	list($level, $levelgr) = html_service_level($conn);
	list($score, $alt) = global_score($conn);
	//
	list($siem,$events) = get_siem_events($conn,date("Y-m-d"));
	$i=0; foreach($siem as $p) $plot .= "[".($i++).",".$p["num_events"]."],";
	$i=0; foreach($siem as $p) $legend .= "[".($i++).",'".($i%2==0 ? $p["intervalo"]."h" : "")."'],";
	$data_pie = top_siem_events($conn,$NUM_HOSTS);
	
?>
<table cellpadding='0' cellspacing='0' border='0' align="center" width='99%'>
<tr>
<td class="canvas">
	<table cellpadding='0' cellspacing='0' border='0' width='100%'>
	<tr>
		<td height="3" colspan="11" bgcolor="#A1A1A1"></td>
	</tr>
	<tr>
		<td width="12" valign="top"><img src="../pixmaps/statusbar/btn_minimize.gif" border='0'></td>
		<td style="padding:5px 10px 3px 0px">
		
			<table cellpadding='0' cellspacing='0' border='0' width='100%'>
				<tr><td class="blackp" valign="bottom" style="padding:5px">
				    
				    <table cellpadding='0' cellspacing='0' border='0' align="center">
					<tr><td style="padding-right:40px">
						<table cellpadding='0' cellspacing='0' border='0' align="center">
						<tr>
								<td align="center"><a href="top.php?option=0&soption=1&url=<?=urlencode("control_panel/global_score.php?hmenu=Risk&smenu=Metrics")?>" target="topmenu"><img id="semaphore" src="../pixmaps/statusbar/sem_<?php echo $score ?>.gif" border="0" alt="<?=$alt?>" title="<?=$alt?>"></a></td>
								<td align="center" style="padding-left:6px" class="blackp2"<b><?=_("Global")?></b><br><?=_("score")?></td>
						</tr>
						</table>
					</td>
					<td>
						<table cellpadding='0' cellspacing='0' border='0' align="center">
						<tr>
						    <td>
								<table cellpadding='0' cellspacing='0' border='0'>
									<tr><td class="blackp2" nowrap align="center"><b><?=_("Service")?></b> <?=_("level")?></td></tr>
									<tr><td width='86px' height='30px' class="<?php echo $levelgr ?>" nowrap align="center" id="service_level_gr"><a href="top.php?option=0&soption=1&url=<?=urlencode("control_panel/show_image.php?range=day&ip=level_admin&what=attack&start=N-1D&end=N&type=level&zoom=1&hmenu=Risk&smenu=Metrics")?>" target="topmenu" id="service_level" class="black2" style="text-decoration:none"><?php echo $level ?> %</a></td></tr>
								</table>
							</td>
						  </tr>
						</table>
					</td>
					</tr>
					</table>
				
				
				</td></tr>
				
				<tr><td class="vsep"></td></tr>
				
				<tr>
					<td class="blackp" valign="bottom" style="padding:3px 5px 0px 5px" nowrap='nowrap'>
						<table cellpadding='0' cellspacing='0' border='0' align="center"><tr><td>
						<table cellpadding='0' cellspacing='0' border='0' align="right">
							<tr>
								<td class="bartitle" width="100"><a href="top.php?option=1&soption=1" target="topmenu" class="blackp"><?=_("Tickets")?> <b><?=_("Opened")?></b></a></td>
								<td class="capsule" width="60"><a href="top.php?option=1&soption=1" target="topmenu" class="whitepn" id="statusbar_unresolved_incidents"><?php echo Util::number_format_locale((int)$unresolved_incidents,0) ?></a></td>
								<td class="blackp" style="font-size:9px;color:#A1A1A1;" width="120" align="center"><?=_("Last updated")?></td>
							</tr>
						</table>
						</td></tr></table>
					</td>
				</tr>
			    <tr>
				  <td class="blackp" valign="bottom" style="padding:0px 5px 3px 5px" nowrap>
						<table cellpadding='0' cellspacing='0' border='0' align="center"><tr><td>
						<table cellpadding='0' cellspacing='0' border='0' align="right">
						<tr>
							<td class="bartitle" width="100"><a href="" target="topmenu" id="statusbar_incident_max_priority_txt" class="blackp"><?=_("Max")?> <b><?=_("priority")?></b></a></td>
							<td class="capsule" width="60"><a href="" target="topmenu" class="whitepn" id="statusbar_incident_max_priority"><?php echo $incident_max_priority ?></a></td>
							<td class="blackp" style="font-size:9px;color:#A1A1A1;" width="120" align="center"><?php echo $incident_date ?></td>
						</tr>
						</table>
						</td></tr></table>
				  </td>
			    </tr>
			  
				<tr><td class="vsep"></td></tr>
				
				<tr>
					<td class="blackp" valign="bottom" style="padding:3px 5px 0px 5px" nowrap='nowrap'>
							<table cellpadding='0' cellspacing='0' border='0' align="center"><tr><td>
							<table cellpadding='0' cellspacing='0' border='0' align="right">
							<tr>
								<td class="bartitle" width="100"><a href="top.php?option=1&soption=0" target="topmenu" class="blackp"><?=_("Unresolved")?> <b><?=_("Alarms")?></b></a></td>
								<td class="capsule" width="60"><a href="top.php?option=1&soption=0" target="topmenu" class="whitepn" id="statusbar_unresolved_alarms"><?php echo Util::number_format_locale((int)$unresolved_alarms,0) ?></a></td>
								<td class="blackp" style="font-size:9px;color:#A1A1A1;" width="120" align="center"><?=_("Last updated")?></td>
							</tr>
							</table>
							</td></tr></table>
					</td>
				</tr>
				<tr>
				  <td class="blackp" valign="bottom" style="padding:0px 5px 3px 5px" nowrap>
							<table cellpadding='0' cellspacing='0' border='0' align="center"><tr><td>
							<table cellpadding='0' cellspacing='0' border='0' align="right">
							<tr>
								<td class="bartitle" width="100"><a href="" target="topmenu" class="blackp" id="statusbar_alarm_max_risk_txt"><?=_("Max")?> <b><?=_("risk")?></b></a></td>
								<td class="capsule" width="60"><a href="" target="topmenu" class="whitepn" id="statusbar_alarm_max_risk"><?php echo $alarm_max_risk ?></a></td>
									<td class="blackp" style="font-size:9px;color:#A1A1A1;" width="120" align="center"><?php echo $alarm_date ?></td>
	
							</tr>
							</table>
							</td></tr></table>
				  </td>
			    </tr>
			    
			    <tr><td class="vsep"></td></tr>
			    
			    <tr><td class="blackp" align="center"style="padding-top:3px"><?=_("Last")." <b>"._("SIEM Events")."</b>"?>. <?=_("Total events")?>: <b><?=Util::number_format_locale($events,0)?></b> </td></tr>
				<tr>
				<td align="center">
					<div id="plotareaglobal" class="plot" style="text-align:center;margin:0px;display:none;width:95%"></div>
				</td>
				</tr>			  
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						function formatNmb(nNmb){
							var sRes = ""; 
							for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
								sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? ".": "") + sRes;
							return sRes;
						}
						function showTooltip(x, y, contents, link) {
							link = link.replace(".","");
							$('<div id="tooltip" class="tooltipLabel"><span style="font-size:10px;">' + contents + '</span></div>').css( {
								position: 'absolute',
								display: 'none',
								top: y - 28,
								left: x - 10,
								border: '1px solid #ADDF53',
								padding: '1px 2px 1px 2px',
								'background-color': '#CFEF95',
								opacity: 0.80
							}).appendTo("body").fadeIn(200);
						}		
						$( function () {
							var options = {
									lines: { show:true, labelHeight:0, lineWidth: 0.8},
									points: { show:false, radius: 2 },
									legend: { show: false },
									yaxis: { ticks:[] },
									xaxis: { tickDecimals:0, ticks: [<?=preg_replace("/,$/","",$legend)?>]},
									grid: { color: "#8E8E8E", labelMargin:0, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true}, shadowSize:1 };
							var data = [ {
								color: "rgb(135, 191, 35)",
								label: "Events",
								lines: { show: true, fill: true},
								data:[<?=preg_replace("/,$/","",$plot)?>]
							}];
							var plotarea = $("#plotareaglobal");
							plotarea.css("display", "");
							plotarea.css("height", 50);
							$.plot( plotarea , data, options );
							var previousPoint =null;
							$("#plotareaglobal").bind("plothover", function (event, pos, item) {
								if (item) {
									if (previousPoint != item.datapoint) {
										previousPoint = item.datapoint;
										$("#tooltip").remove();
										var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
										showTooltip(item.pageX, item.pageY, y + " " + item.series.label,y+"-"+x);
									}
								}
								else {
									$("#tooltip").remove();
									previousPoint = null;
								}
							});
							$("#plotareaglobal").bind("plotclick", function (event, pos, item) {
								if (item) {
									if (previousPoint != item.datapoint) {
										previousPoint = item.datapoint;
										$("#tooltip").remove();
										var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
										showTooltip(item.pageX, item.pageY, y + " " + item.series.label,y+"-"+x);
									}
								}
								else {
									$("#tooltip").remove();
									previousPoint = null;
								}
					        });
					        $.plot($("#graph"), [
								<? $i=0;foreach ($data_pie as $label => $data) { 
								    	if (strlen($label)>31) $label = substr($label, 0, 30)."..";	?>
									<?=($i++==0) ? "" : ","?>{ label: "<?=$label?>",  data: <?=$data?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			</table>
		</td>
	</tr>
	</table>
</td>
</tr>
</table>
<?

} elseif ($screen=="alarms" && Session::menu_perms("MenuIncidents", "ReportsAlarmReport")) {
	// Alarms report
	$report_type = "alarm";
	$security_report = new SecurityReport();
	$interval = 60 * 60 * 24 * $range; # 1 month
	$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time()-$interval);
	$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%Y-%m-%d", time());
	
	// Attacked host
	$list1 = $security_report->AttackHost($conn, "ip_dst", $NUM_HOSTS, $report_type, $date_from, $date_to);
	// Attacker host
	$list2 = $security_report->AttackHost($conn, "ip_src", $NUM_HOSTS, $report_type, $date_from, $date_to);
	// Ports
	$list3 = $security_report->Ports($NUM_HOSTS, $report_type, $date_from, $date_to);
	// Events
	$list4 = $security_report->Events($NUM_HOSTS, $report_type, $date_from, $date_to);
?>
<table cellpadding='0' cellspacing='0' border='0' align="center" width='99%'>
<tr>
<td class="canvas">
	<table cellpadding='0' cellspacing='0' border='0' width='100%'>
	<tr>
		<td height="3" colspan="11" bgcolor="#A1A1A1"></td>
	</tr>
	<tr>
		<td width="12" valign="top"><img src="../pixmaps/statusbar/btn_minimize.gif" border='0'></td>
		<td style="padding:5px 10px 3px 0px">
		
			<table cellpadding='0' cellspacing='0' border='0' width='100%'>
			    
			    <tr><td align="center"> 
			    	<table cellpadding='2' cellspacing='0' border='0'><tr>
			    	<td class="legendLabel<?=($range==1) ? " underline" : ""?>"> Last  <a href="?screen=alarms&range=1"><b><?=_("day")?></b></a></td><td>|</td>
			    	<td class="legendLabel<?=($range==7) ? " underline" : ""?>"><a href="?screen=alarms&range=7"><b><?=_("week")?></b></a></td><td>|</td>
			    	<td class="legendLabel<?=($range==31) ? " underline" : ""?>"><a href="?screen=alarms&range=31"><b><?=_("month")?></b></a></td><td>|</td>
			    	<td class="legendLabel<?=($range==365) ? " underline" : ""?>"><a href="?screen=alarms&range=365"><b><?=_("year")?></b></a></td>
			    	</tr></table>
			    </td></tr>
			    
			    <tr><td class="blackp" align="center"style="padding-top:5px"><?=_("Top")." <b>"._("Attacker hosts")."</b>"?></td></tr>
			    
				<tr>
				<td style="height:106px" align="center">
					<div id="graph1" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph1"), [
								<? $i=0;foreach ($list1 as $l) { 
								        $ip = $l[0];
        								$occurrences = Util::number_format_locale($l[1], 0);
        								$hostname = Host::ip2hostname($conn, $ip);
        								$os_pixmap = Host_os::get_os_pixmap($conn, $ip);
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $hostname $os_pixmap");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#E9967A","#F08080","#FF6347","#FF4500","#FF0000","#DC143C","#B22222"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			    <tr><td class="vsep" style="padding-top:5px"></td></tr>

			    <tr><td class="blackp" align="center" style="padding-top:3px"><?=_("Top")." <b>"._("Attacker hosts")."</b>"?></td></tr>
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph2" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph2"), [
								<? $i=0;foreach ($list2 as $l) { 
								        $ip = $l[0];
        								$occurrences = Util::number_format_locale($l[1], 0);
        								$hostname = Host::ip2hostname($conn, $ip);
        								$os_pixmap = Host_os::get_os_pixmap($conn, $ip);
        								$label = str_replace("'","\'","[<b>$occurrences</b>]  $hostname $os_pixmap");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			    <tr><td class="vsep" style="padding-top:5px"></td></tr>

			    <tr><td class="blackp" align="center" style="padding-top:3px"><?=_("Top")." <b>"._("Used Ports")."</b>"?></td></tr>
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph3" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph3"), [
								<? $i=0;foreach ($list3 as $l) { 
								        $port = $l[0];
								        $service = $l[1];
								        $occurrences = number_format($l[2], 0, ",", ".");
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $port $service");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[2]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			    <tr><td class="vsep" style="padding-top:5px"></td></tr>

			    <tr><td class="blackp" align="center" style="padding-top:3px"><?=_("Top")." <b>"._("Alarms")."</b>"?></td></tr>
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph4" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph4"), [
								<? $i=0;foreach ($list4 as $l) { 
								        $event = Util::signaturefilter($l[0]);
								        $short_event = SecurityReport::Truncate($event, 30);
								        $occurrences = number_format($l[1], 0, ",", ".");
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $short_event");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#90EE90","#00FF7F","#7CFC00","#32CD32","#3CB371","#228B22","#006400"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			</table>
		</td>
	</tr>
	</table>
</td>
</tr>
</table>
<?
	
} elseif ($screen=="tickets" && Session::menu_perms("MenuIncidents", "IncidentsReport")) {
	// Incidents report
	$list1 = Incident::incidents_by_status($conn); // Status
	$list2 = Incident::incidents_by_type($conn); // Type
	$list3 = Incident::incidents_by_user($conn); // User
?>
<table cellpadding='0' cellspacing='0' border='0' align="center" width='99%'>
<tr>
<td class="canvas">
	<table cellpadding='0' cellspacing='0' border='0' width='100%'>
	<tr>
		<td height="3" colspan="11" bgcolor="#A1A1A1"></td>
	</tr>
	<tr>
		<td width="12" valign="top"><img src="../pixmaps/statusbar/btn_minimize.gif" border='0'></td>
		<td style="padding:5px 10px 3px 0px">
		
			<table cellpadding='0' cellspacing='0' border='0' width='100%'>
			    
			    <tr><td class="blackp" align="center"style="padding-top:5px"><?=_("Tickets")." <b>"._("by status")."</b>"?></td></tr>
			    
				<tr>
				<td style="height:106px" align="center">
					<div id="graph1" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph1"), [
								<? $i=0;foreach ($list1 as $l) { 
							            $status = $l[0];
							            $occurrences = $l[1];
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $status");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#E9967A","#ADD8E6"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			    <tr><td class="vsep" style="padding-top:5px"></td></tr>

			    <tr><td class="blackp" align="center" style="padding-top:3px"><?=_("Tickets")." <b>"._("by user in charge")."</b>"?></td></tr>
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph2" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph2"), [
								<? $i=0;foreach ($list2 as $l) if ($i<$NUM_HOSTS) { 
										$user = $l[0];
										$occurrences = $l[1];
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $user");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#90EE90","#00FF7F","#7CFC00","#32CD32","#3CB371","#228B22","#006400"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			    <tr><td class="vsep" style="padding-top:5px"></td></tr>

			    <tr><td class="blackp" align="center" style="padding-top:3px"><?=_("Tickets")." <b>"._("by Type")."</b>"?></td></tr>
				
				<tr>
				<td style="height:106px" align="center">
					<div id="graph3" style="text-align:center;margin:0px;height:104px;width:98%"></div>
				</td>
				</tr>

					<script language="javascript" type="text/javascript">
						$( function () {
					        $.plot($("#graph3"), [
								<? $i=0;foreach ($list3 as $l) if ($i<$NUM_HOSTS) { 
							            $type = $l[0];
							            $occurrences = $l[1];
        								$label = str_replace("'","\'","[<b>$occurrences</b>] $type");
								    	//if (strlen($label)>31) $label = substr($label, 0, 30)."..";	
								?>
									<?=($i++==0) ? "" : ","?>{ label: '<?=$label?>',  data: <?=$l[1]?>}
								<? } ?>
								], 
								{
									pie: { 
										show: true, 
										pieStrokeLineWidth: 1, 
										pieStrokeColor: '#FFF', 
										pieChartRadius: 52, 			// by default it calculated by 
										centerOffsetTop: 0,
										centerOffsetLeft: 'auto', 			// if 'auto' and legend position is "nw" then centerOffsetLeft is equal a width of legend.
										showLabel: true,				//use ".pieLabel div" to format looks of labels
										labelOffsetFactor: 5/6, 		// part of radius (default 5/6)
										//labelOffset: 0        		// offset in pixels if > 0 then labelOffsetFactor is ignored
										labelBackgroundOpacity: 0.55, 	// default is 0.85
										labelFormatter: function(serie){// default formatter is "serie.label"
											//return serie.label;
											//return serie.data;
											//return serie.label+'<br/>'+Math.round(serie.percent)+'%';
											return Math.round(serie.percent)+'%';
										}
									},
						            colors: ["#EEE8AA","#F0E68C","#FFD700","#FF8C00","#DAA520","#D2691E","#B8860B"],
									legend: {
										show: true, 
										position: "ne", 
										backgroundOpacity: 0.1
									}
								});
						});
					</script>

			</table>
		</td>
	</tr>
	</table>
</td>
</tr>
</table>
<?

}
?>
</td></tr>
</table>
</body>
</html>
<?
$db->close($conn);
?>
