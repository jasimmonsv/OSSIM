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
* - ip_max_occurrences()
* - event_max_occurrences()
* - event_max_risk()
* - port_max_occurrences()
* - less_stable_services()
* Classes list:
*/
set_time_limit(900);
require_once ('classes/Session.inc');
//Session::logcheck("MenuReports", "ReportsSecurityReport");
Session::logcheck("MenuIncidents", "ReportsAlarmReport");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  
  <? include ("../host_report_menu.php") ?>
  <!-- ui-1.8 format (dd/mm/YYYY) -->
</head>
<body>
<?php
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';
include ("../hmenu.php");
if (GET('type') == 'alarm') {
    $report_type = "alarm";
} else {
    $report_type = "event";
}

require_once ('ossim_conf.inc');
$path_conf = $GLOBALS["CONF"];
$jpgraph_path = $path_conf->get_conf("jpgraph_path");
if (!is_readable($jpgraph_path)) {
    $error = new OssimError();
    $error->display("JPGRAPH_PATH");
}
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('jgraphs/jgraphs.php');
require_once ('classes/SecurityReport.inc');
$security_report = new SecurityReport();
$server = $_SERVER["SERVER_ADDR"];
$file = $_SERVER["REQUEST_URI"];
/* database connect */
$db = new ossim_db();
$conn = $db->connect();
/* Number of hosts to show */
$NUM_HOSTS = 10;
//#############################
// Top attacked hosts
//#############################
$month = 60 * 60 * 24 * 31; # 1 month
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time()-$month);
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%Y-%m-%d", time());
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
if (ossim_error()) {
    die(ossim_error());
}
?>
<script type="text/javascript">
	/*
	$(document).ready(function(){
		calendar();
	});*/
	function postload() {
		calendar();
	}
	
	function calendar() {
	// CALENDAR
	<?
	if ($date_from != "") {
		$aux = split("-",$date_from);
		$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
	} else {
		$y = strftime("%Y", time() - (24 * 60 * 60));
		$m = strftime("%m", time() - (24 * 60 * 60));
		$d = strftime("%d", time() - (24 * 60 * 60));
	}
	if ($date_to != "") {
		$aux = split("-",$date_to);
		$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
	} else {
		$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
	}
	?>
	var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
	var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);
	
	$('#widgetCalendar').DatePicker({
		flat: true,
		format: 'Y-m-d',
		date: [new Date(datefrom), new Date(dateto)],
		calendars: 3,
		mode: 'range',
		starts: 1,
		onChange: function(formated) {
			if (formated[0]!=formated[1]) {
				var f1 = formated[0].split(/-/);
				var f2 = formated[1].split(/-/);
				document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
				document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
				$('#date_str').css('text-decoration', 'underline');
				document.getElementById('dateform').submit();
			}
		}
	});
	var state = false;
	$('#widget>a').bind('click', function(){
		$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
		$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
		state = !state;
		return false;
	});
	$('#widgetCalendar div.datepicker').css('position', 'absolute');
	}
</script>
<form method=get id="dateform">
	<input type="hidden" name="section" value="<?=GET('section')?>">
	<input type="hidden" name="type" value="<?=GET('type')?>">
	<table align="center">
	<tr>
		<td colspan="2" class="nobborder">
			<table class="noborder" width="150">
				<tr>
					<td style="text-align: center; border-width: 0px">
						<b><?php echo _('Time') ?></b>:
					</td>
					<td style="text-align: center; border-width: 0px">
						<div id="widget">
						<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0"></a>
						<div id="widgetCalendar"></div>
					</div>
					</td>
					<td class="nobborder" nowrap style="color:gray;padding-left:20px"><i>
					from: <input type="text" name="date_from" id="date_from"  value="<?php echo $date_from ?>" style="border:0px;width:80px;color:gray">
					to: <input type="text" name="date_to" id="date_to" value="<?php echo $date_to ?>" style="border:0px;width:80px;color:gray">
					</i></td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
</form>
<?
if (GET('section') == 'attacked') {
    ip_max_occurrences("ip_dst",$date_from,$date_to);
}
//#############################
// Top attacker hosts
//#############################
elseif (GET('section') == 'attacker') {
    ip_max_occurrences("ip_src",$date_from,$date_to);
}
//#############################
// Top events received
//#############################
elseif (GET('section') == 'events_recv') {
    event_max_occurrences($date_from,$date_to);
}
//#############################
// Top events risk
//#############################
elseif (GET('section') == 'events_risk') {
    event_max_risk($date_from,$date_to);
}
//#############################
// Top used destination ports
//#############################
elseif (GET('section') == 'dest_ports') {
    port_max_occurrences($date_from,$date_to);
}
/* Top data traffic */
elseif (GET('section') == 'traffic') {
    echo _("Working on")."...";
}
/* Top throughput */
elseif (GET('section') == 'throughput') {
    echo _("Working on")."...";
}
/* Top used services */
elseif (GET('section') == 'services') {
    echo _("Working on")."...";
}
//##############################
// Top less stable services
//##############################
elseif (GET('section') == 'availability') {
    less_stable_services();
} elseif (GET('section') == 'all') {
    echo "<br/><br/>";
    ip_max_occurrences("ip_dst",$date_from,$date_to);
    echo "<br/><br/>";
    ip_max_occurrences("ip_src",$date_from,$date_to);
    echo "<br/><br/>";
    port_max_occurrences($date_from,$date_to);
    echo "<br/><br/>";
    echo "<center>";
    echo "<table style=\"border:0px;\" width=\"750\" cellspacing=\"0\" cellpadding=\"0\">";
    echo "<tr><td valign=\"top\" class=\"nobborder\">";
    event_max_occurrences($date_from,$date_to);
    echo "</td><td width=\"20\" class=\"nobborder\">&nbsp;</td><td valign=\"top\" class=\"nobborder\">";
    event_max_risk($date_from,$date_to);
    echo "</td></tr></table>";
    echo "</center>";
    // echo "<br/>";
    // less_stable_services();
    
}
$db->close($conn);
?>
   
</body>
</html>



<?php
/*
* return the list of host with max occurrences
* as dest or source
* pre: type is "ip_src" or "ip_dst"
*/
function ip_max_occurrences($target,$date_from,$date_to) {
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
	
	/* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
    if (!strcmp($target, "ip_src")) {
        if ($report_type == "alarm") {
            $target = "src_ip";
        }
        $title = _("Attacker hosts");
    } elseif (!strcmp($target, "ip_dst")) {
        if ($report_type == "alarm") {
            $target = "dst_ip";
        }
        $title = _("Attacked hosts");
    }
	$list = $security_report->AttackHost($security_report->ossim_conn, $target, $NUM_HOSTS, $report_type, $date_from, $date_to);
    if (count($list)==0) {
        echo "<table align='center' class='nobborder'><tr><td class='nobborder'>"._("No data available")."</td></tr></table></body></html>";
        exit(0);
    }
?>
        <table align="center" width="750" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr"><?=_("Top")?><?php echo "$NUM_HOSTS $title" ?></td></tr>
        </table>
        <table align="center" width="750">
		<tr><td style="padding-top:15px;" valign="top" class="nobborder">
        <table align="center">
		  <tr>
            <th> <?php echo gettext("Host"); ?> </th>
            <th> <?php echo gettext("Occurrences"); ?> </th>
          </tr>
<?php
	foreach($list as $l) {
        $ip = $l[0];
        $occurrences = number_format($l[1], 0, ",", ".");
        $hostname = Host::ip2hostname($security_report->ossim_conn, $ip);
        $os_pixmap = Host_os::get_os_pixmap($security_report->ossim_conn, $ip);
        if ($report_type == "alarm") {
            if ($target == "src_ip") {
                $link = "$ossim_link/control_panel/alarm_console.php?src_ip=" . $ip;
            } elseif ($target == "dst_ip") {
                $link = "$ossim_link/control_panel/alarm_console.php?dst_ip=" . $ip;
            } else {
                $link = "$ossim_link/control_panel/alarm_console.php?src_ip=" . $ip . "&dst_ip=" . $ip;
            }
        } else {
            $link = "$acid_link/" . $acid_prefix . "_stat_alerts.php?&" . "num_result_rows=-1&" . "submit=Query+DB&" . "current_view=-1&" . "ip_addr[0][1]=$target&" . "ip_addr[0][2]==&" . "ip_addr[0][3]=$ip&" . "ip_addr_cnt=1&" . "sort_order=time_d";
        }
?>
          <tr>
            <td><div id="<?php echo $ip;?>;<?php echo $hostname; ?>" class="HostReportMenu" style="display:inline">
              <a title="<?php
        echo $ip ?>" 
                 href="<?php
        echo $link ?>"><?php
        echo $hostname ?></a></div>
              <?php
        echo $os_pixmap ?>
            </td>
            <td><?php
        echo $occurrences ?></td>
          </tr>
<?php
    }
?>
        </table>
        </td>
        <td valign="top" class="nobborder">
<?php
    if ($report_graph_type == "applets") {
        jgraph_attack_graph($target, $NUM_HOSTS);
    } else {
?>
        <img src="graphs/attack_graph.php?target=<?php
        echo $target
?>&hosts=<?php
        echo $NUM_HOSTS ?>&type=<?php
        echo $report_type ?>&date_from=<?=urlencode($date_from)?>&date_to=<?=urlencode($date_to)?>" 
                 alt="attack_graph"/>
<?php
    }
?>
        </td>                 
        </tr>
        </table>
<?php
}
/*
* return the event with max occurrences
*/
function event_max_occurrences($date_from,$date_to) {
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    /* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
?>
        <table align="center" width="100%" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr">
        <?php
    if ($report_type == "alarm") { ?>
        <?=_("Top")?> <?php
        echo "$NUM_HOSTS "._("Alarms") ?>
        <?php
    } else { ?>
        <?=_("Top")?> <?php
        echo "$NUM_HOSTS "._("Events") ?>
        <?php
    } ?>
            </td></tr>
        </table>
        <table align="center" width="100%">
          <tr>
            <?php
    if ($report_type == "alarm") { ?>
            <th> <?php
        echo gettext("Alarm"); ?> </th>
            <?php
    } else { ?>
            <th> <?php
        echo gettext("Event"); ?> </th>
            <?php
    } ?>
            <th> <?php
    echo gettext("Occurrences"); ?> </th>
          </tr>
<?php
    $list = $security_report->Events($NUM_HOSTS, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $event = $l[0];
        $short_event = SecurityReport::Truncate($event, 60);
        $occurrences = number_format($l[1], 0, ",", ".");
?>
          <tr>
             <?php
        if ($report_type == "alarm") {
            $link = "$ossim_link/control_panel/alarm_console.php";
        } else {
            $link = "$acid_link/" . $acid_prefix . "_qry_main.php?new=1&" . "sig[0]==&" . "sig[1]=" . urlencode($event) . "&" . "sig[2]==&" . "submit=Query+DB&" . "num_result_rows=-1&" . "sort_order=time_d";
        }
?>
            <td style="text-align:left;"><a href="<?php
        echo $link ?>"><?php
        echo Util::signaturefilter($short_event); ?></a></td>
            <td><?php
        echo $occurrences ?></td>
          </tr>
<?php
    }
?>
        <tr>
          <td colspan="2" class="nobborder" height="348" valign="top"><center>
            <br/>
<?php
    if ($report_graph_type == "applets") {
        jgraph_nbevents_graph();
    } else {

                ?><iframe src="graphs/events_received_graph.php?hosts=<?php
        echo $NUM_HOSTS
?>&type=<?php
        echo $report_type ?>&date_from=<?=urlencode($date_from)?>&date_to=<?=urlencode($date_to)?>" alt="<?=_("events graph")?>"
        frameborder="0" style="margin:0px;padding:0px;width:430px;height:430px;border: 0px solid rgb(170, 170, 170);text-align:center"> </iframe><?
    
    /*
?>
            <img src="graphs/events_received_graph.php?hosts=<?php
        echo $NUM_HOSTS
?>&type=<?php
        echo $report_type ?>&date_from=<?=urlencode($date_from)?>&date_to=<?=urlencode($date_to)?>" alt="events graph"/>
<?php*/
    }
?>
          </center></td>
        <tr/>
        </table>
<?php
}
/*
* return a list of events ordered by risk
*/
function event_max_risk($date_from,$date_to) {
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    require_once ('sec_util.php');
?>
        <table align="center" width="100%" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr">
        <?php
    if ($report_type == "alarm") { ?>
        <?=_("Top")?> <?php
        echo "$NUM_HOSTS "._("Alarms by Risk") ?>
        <?php
    } else { ?>
        <?=_("Top")?> <?php
        echo "$NUM_HOSTS "._("Events by Risk") ?>
        <?php
    } ?>
        </td></tr></table>
        <table align="center" width="100%">
          <tr>
            <?php
    if ($report_type == "alarm") { ?>
            <th> <?php
        echo gettext("Alarm"); ?> </th>
            <?php
    } else { ?>
            <th> <?php
        echo gettext("Event"); ?> </th>
            <?php
    } ?>
            <th> <?php
    echo gettext("Risk"); ?> </th>
          </tr>
<?php
    $list = $security_report->EventsByRisk($NUM_HOSTS, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $event = $l[0];
        $risk = $l[1];
?>
          <tr>
            <td style="text-align:left;"><?php
        echo Util::signaturefilter($event); ?></a></td>
            <td style="text-align:left;"><?php
        echo_risk($risk); ?></td>
          </tr>
<?php
    }
?>
        </table>
        <br/>
<?php
}
/*
* return the list of ports with max occurrences
*/
function port_max_occurrences($date_from,$date_to) {
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    /* ossim framework conf */
    $conf = $GLOBALS["CONF"];
    $acid_link = $conf->get_conf("acid_link");
    $ossim_link = $conf->get_conf("ossim_link");
    $acid_prefix = $conf->get_conf("event_viewer");
    $report_graph_type = $conf->get_conf("report_graph_type");
?>
        <table align="center" width="750" cellpadding="0" cellspacing="0" class="noborder">
            <tr><td class="headerpr"><?=_("Top")?> <?php echo "$NUM_HOSTS" ?> <?=_("Used Ports")?></td></tr>
        </table>
        <table align="center" width="750">
          <tr>
            <td style="padding-top:15px;" valign="top" class="nobborder">
        <table align="center">
          <tr>
            <th><?=_("Port")?></th>
            <th><?=_("Service")?></th>
            <th><?=_("Occurrences")?></th>
          </tr>
<?php
    $list = $security_report->Ports($NUM_HOSTS, $report_type, $date_from, $date_to);
    foreach($list as $l) {
        $port = $l[0];
        $service = $l[1];
        $occurrences = number_format($l[2], 0, ",", ".");
?>
          <tr>
            <td>
              <?php
        $link = "$acid_link/" . $acid_prefix . "_stat_uaddr.php?" . "tcp_port[0][0]=+&" . "tcp_port[0][1]=layer4_dport&" . "tcp_port[0][2]==&" . "tcp_port[0][3]=$port&" . "tcp_port[0][4]=+&" . "tcp_port[0][5]=+&" . "tcp_port_cnt=1&" . "layer4=TCP&" . "num_result_rows=-1&" . "current_view=-1&" . "addr_type=1&" . "sort_order=occur_d";
        echo "<a href=\"$link\">$port</a>";
?>
            </td>
            <td><?php
        echo $service ?></td>
            <td><?php
        echo $occurrences ?></td>
          </tr>
<?php
    }
    echo "</table>\n";
?>
            </td>
            <td valign="top" class="nobborder">
<?php
    if ($report_graph_type == "applets") {
        jgraph_ports_graph();
    } else {
?>
              <img src="graphs/ports_graph.php?ports=<?php
        echo $NUM_HOSTS
?>&type=<?php
        echo $report_type ?>&date_from=<?=urlencode($date_from)?>&date_to=<?=urlencode($date_to)?>"/>
<?php
    }
?>
            </td>
          </tr>
        </table>
            
<?php
}
/*
* return the list of less stabe services
*/
function less_stable_services() {
    global $NUM_HOSTS;
    /* opennms db connect */
    $opennms_db = new ossim_db();
    $opennms_conn = $opennms_db->opennms_connect();
    $query = OssimQuery("SELECT servicename, count(servicename) 
            FROM ifservices ifs, service s 
            WHERE ifs.serviceid = s.serviceid AND ifs.status = 'D' 
            GROUP BY servicename ORDER BY count(servicename) DESC 
            LIMIT $NUM_HOSTS");
    $rs = & $opennms_conn->Execute($query);
    if (!$rs) {
        print $opennms_conn->ErrorMsg();
    } else {
?>
        <h2><?=_("Top")?> <?php
        echo "$NUM_HOSTS" ?> <?=_("less stabe services")?></h2>
        <table align="center">
          <tr>
            <th> <?php
        echo gettext("Service"); ?> </th>
            <th> <?php
        echo gettext("Ocurrences"); ?> </th>
          </tr>
<?php
        while (!$rs->EOF) {
            $service = $rs->fields["servicename"];
            $occurrences = number_format($rs->fields["count"], 0, ",", ".");
?>
          <tr>
            <td><?php
            echo $service ?></td>
            <td><?php
            echo $occurrences ?></td>
          </tr>
<?php
            $rs->MoveNext();
        }
    }
    $opennms_db->close($opennms_conn);
    echo "</table><br/>\n";
}
?>
