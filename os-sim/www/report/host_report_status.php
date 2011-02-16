<?php
/*****************************************************************************
*
*    License:
*
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
require_once 'classes/Incident.inc';
require_once 'classes/Incident_ticket.inc';
require_once 'classes/Alarm.inc';
require_once 'classes/Status.inc';
require_once 'classes/Util.inc';

function html_service_level($conn,$host="",$date_range=null) {
    global $user;
	// for custom
	if($date_range!=null){
		$date_from_div=explode('-',$date_range['date_from']);
		$date_to_div=explode('-',$date_range['date_to']);
		// calculate number of days
		$date_from_op=gregoriantojd ($date_from_div[1],$date_from_div[2],$date_from_div[0]);
		$date_to_op=gregoriantojd ($date_to_div[1],$date_to_div[2],$date_to_div[0]);
		$n_days=$date_to_op-$date_from_op+1;
		//
		if($n_days==1){
			$range = "day";
		}elseif($n_days>1&&$n_days<=7){
			$range = "week";
		}elseif($n_days>7&&$n_days<=31){
			$range = "month";
		}elseif($n_days>31){
			$range = "year";
		}
	}else{
		$range = "day";
	}
	//
    $level = 100;
    $class = "level4";
    //
	if($host!='any'){
		$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
		$params = array(
			//"global_$user",
			$host,
			$range
		);
	}else{
		$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ? AND rrd_type='global'";
		$params = array(
			'global_'.$user,
			$range
		);
	}
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
function global_score($conn, $host) {
    global $conf_threshold;
    //
	if($host!='any'){
		$sql = "SELECT host_ip, compromise, attack FROM host_qualification WHERE host_ip='$host'";
	}else{
		$sql = "SELECT host_ip, compromise, attack FROM host_qualification";
	}
    if (!$rs = & $conn->Execute($sql)) {
        die($conn->ErrorMsg());
    }
    $score_a = 0;
    $score_c = 0;
    while (!$rs->EOF) {
        $score_a+= $rs->fields['attack'];
        $score_c = $rs->fields['compromise'];
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
$conf_threshold = $conf->get_conf('threshold');

// Get service LEVEL
//global $conn, $conf, $user, $range, $rrd_start;
list($level, $levelgr) = html_service_level($conn,$host,$date_range);
list($score, $alt) = global_score($conn,$host);

?>
<script type="text/javascript">$("#pbar").progressBar(30);$("#progressText").html('<?=gettext("Loading")?> <b><?=gettext("SIEM Events")?></b>...');</script><?
ob_flush();
flush();
usleep(500000);
// Get SIM Events

// for custom
if($date_range!=null){
	$date_from = $date_range['date_from'];
	$date_to = $date_range['date_to'];
}else{
	$date_from = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60));
	$date_to = strftime("%Y-%m-%d %H:%M:%S", time());
}
//

$date_from_week = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60 * 7));
$limit = 6;
if($host!='any'){
	list($sim_foundrows,$sim_highrisk,$sim_risknum,$sim_date) = Status::get_SIM_Resume($host,$host,$date_from,$date_to);
}else{
	list($sim_foundrows,$sim_highrisk,$sim_risknum,$sim_date) = Status::get_SIM_Resume('','',$date_from,$date_to);
}
//list($sim_events,$sim_foundrows,$sim_highrisk,$sim_risknum,$sim_date,$unique_events,$event_cnt,$plots,$sim_ports,$sim_ipsrc,$sim_ipdst,$sim_gplot,$sim_numevents) = Status::get_SIM($host,$host);
if($host!='any'){
	list($sim_ports,$sim_ipsrc,$sim_ipdst) = Status::get_SIM_Clouds($host,$host,$date_range);
}else{
	list($sim_ports,$sim_ipsrc,$sim_ipdst) = Status::get_SIM_Clouds('','',$date_range);
}
/*
echo '-------------';
echo $date_from_week;
echo $date_to;
echo '-------------';
*/
if($host!='any'){
	$sim_gplot = Status::get_SIM_Plot($host,$host,$date_from_week,$date_to);
}else{
	$sim_gplot = Status::get_SIM_Plot('','',$date_from_week,$date_to);
}
//print_r($sim_gplot);
if($host!='any'){
	list($unique_events,$plots,$sim_numevents) = Status::get_SIM_Unique($host,$host,$date_from_week,$date_to,$limit);
}else{
	list($unique_events,$plots,$sim_numevents) = Status::get_SIM_Unique('','',$date_from_week,$date_to,$limit);
}
if ($event_cnt < 1) $event_cnt = 1;

?><script type="text/javascript">$("#pbar").progressBar(40);$("#progressText").html('<?=gettext("Loading")?> <b><?=gettext("Logger Events")?></b>...');</script><?
ob_flush();
flush();
usleep(500000);
// Get SEM Events
$start = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60));
$start_week = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60 * 7));
$start_year = strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60 * 365));
$end = strftime("%Y-%m-%d %H:%M:%S", time());
if($host!='any'){
	list($sem_events,$sem_foundrows) = Status::get_SEM("",$start,$end,"date",1234,$host);
	list($sem_events_week,$sem_foundrows_week,$sem_date,$sem_wplot_y,$sem_wplot_x) = Status::get_SEM("",$start_week,$end,"none",1234,$host);
}else{
	list($sem_events,$sem_foundrows) = Status::get_SEM("",$start,$end,"date",1234);
	list($sem_events_week,$sem_foundrows_week,$sem_date,$sem_wplot_y,$sem_wplot_x) = Status::get_SEM("",$start_week,$end,"none",1234);
}
//list($sem_events_year,$sem_foundrows_year,$sem_date_year,$sem_yplot_y,$sem_yplot_x) = Status::get_SEM("",$start_year,$end,"none",1234,$host);

?><script type="text/javascript">$("#pbar").progressBar(50);$("#progressText").html('<?=gettext("Loading")?> <b><?=gettext("Anomalies")?></b>...');</script><?
ob_flush();
flush();
usleep(500000);
// Anomalies
if($host!='any'){
	list($anm_events,$anm_foundrows,$anm_foundrows_week,$anm_date) = Status::get_anomalies($conn,$host);
}else{
	list($anm_events,$anm_foundrows,$anm_foundrows_week,$anm_date) = Status::get_anomalies($conn);
}
?><script type="text/javascript">$("#pbar").progressBar(60);$("#progressText").html('<?=gettext("Loading")?> <b><?=gettext("Vulnerabilities")?></b>...');</script><?
ob_flush();
flush();
usleep(500000);
// Vulnerabilities
list($vul_events,$vul_foundrows,$vul_highrisk,$vul_risknum,$vul_lastdate) = Status::get_vul_events($conn,$host);

// Availability (nagios)
list($ava_date,$ava_foundrows,$ava_highprio,$ava_prionum) = Status::get_availability_events($conn_snort,$host);

?><script type="text/javascript">$("#pbar").progressBar(70);</script><?
ob_flush();
flush();
usleep(500000);
?>
<table cellpadding=0 cellspacing=2 border=0 width="100%" height="100%">
	<tr>
		<td class="headerpr" height="20"><?=gettext("General Status")?></td>
	</tr>
	<tr>
		<td style="text-align:center">
			<table cellpadding=0 cellspacing=0 border=0 align="center">
			<tr>
				<td class="blackp" valign="middle" nowrap align="right" style="border:0px solid white;text-align:right"><?php echo gettext("<b>Service</b> level:");?> </td>
				<td class="<?php echo $levelgr ?>" width="90" height="30" nowrap align="left" id="service_level_gr" style="border:0px solid white"><a href="../top.php?option=0&soption=1&url=control_panel%2Fshow_image.php%3Frange%3Dday%26ip%3Dlevel_admin%26what%3Dattack%26start%3DN-1D%26end%3DN%26type%3Dlevel%26zoom%3D1" target="topmenu" id="service_level" class="black" style="text-decoration:none"><?php echo $level ?> %</a></td>
				<td></td>
				<td class="nobborder">
					<table class="noborder" cellpadding=0 cellspacing=0 border=0 align=""><tr>
					<td style="padding-left:4px;text-align:right"><a href="../top.php?option=0&soption=1&url=control_panel%2Fglobal_score.php" target="topmenu" class="blackp" style="text-decoration:none"><?php echo gettext("<b>Global</b> score:"); ?></a></td>
					<td class="nobborder" style="text-align:left"><a href="../top.php?option=0&soption=1&url=control_panel%2Fglobal_score.php" target="topmenu"><img id="semaphore" src="../pixmaps/statusbar/sem_<?php echo $score ?>_h.gif" border="0" alt="<?php echo $alt ?>" title="<?php echo $alt ?>"></a></td>
					</tr>
					</table>
				</td>
				<!--
				<td>
					Time range: 
					<select name="timerange">
						<option value="day">Last Day
						<option value="week">Last Week
						<option value="2weeks">Last 2 Weeks
						<option value="month">Last Month
					</select>
				</td>
				-->
			</tr>
			</table>
		</td>
	</tr>
	<tr><td class="vsep" style="border:0px solid white"></td></tr>
	<tr>
		<td>
			<table cellspacing="2" cellpadding="1">
				<tr bgcolor="#E1EFE0">
					<td class="bartitle" width="125"><a href="../top.php?option=1&soption=1&url=<?php echo "incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets" ?>" target="topmenu" class="blackp"><?=gettext("Tickets")?> <b><?=gettext("Opened")?></b></a></td>
					<td class="capsule" width="50" id="tickets_num"><a href="../top.php?option=1&soption=1&url=<?php echo urlencode("incidents/index.php?status=Open&hmenu=Tickets&smenu=Tickets") ?>" target="topmenu" class="whitepn">-</a></td>
					<td class="blackp" style="font-size:8px;border:0px solid white" align="center" id="tickets_date" nowrap>-</td>
					<td class="blackp" nowrap style="text-align:right"><a href="javascript:;" target="topmenu" id="statusbar_incident_max_priority_txt" class="blackp"><?=gettext("Max")?> <b><?=gettext("priority")?></b>:</a></td>
					<td><table style="width:auto" cellpadding=0 cellspacing=0><tr><td style="text-align:left"><a href="javascript:;" target="topmenu" class="blackp" id="statusbar_incident_max_priority">-</a></td></tr></table></td>
				</tr>
				<tr>
					<td class="bartitle" width="125"><a href="../top.php?option=1&soption=0&url=control_panel%2Falarm_console.php<?php if($host!='any'){ $url_temp="?hide_closed=1&src_ip=$host&dst_ip=$host&hmenu=Alarms&smenu=Alarms"; }else{ $url_temp="?hide_closed=1&hmenu=Alarms&smenu=Alarms"; } echo urlencode($url_temp)?>" target="topmenu" class="blackp"><?=gettext("Unresolved")?> <b><?=gettext("Alarms")?></b></a></td>
					<td class="capsule" width="50"><a href="../top.php?option=1&soption=0&url=control_panel%2Falarm_console.php<?php if($host!='any'){ $url_temp="?hide_closed=1&src_ip=$host&dst_ip=$host&hmenu=Alarms&smenu=Alarms"; }else{ $url_temp="?hide_closed=1&hmenu=Alarms&smenu=Alarms"; } echo urlencode($url_temp)?>" target="topmenu" class="whitepn" id="statusbar_unresolved_alarms">0</a></td>
					<td class="blackp" style="font-size:8px" align="center" id="alarms_date" nowrap>-</td>
					<td class="blackp" nowrap style="text-align:right"><a href="javascript:;" target="topmenu" class="blackp" id="statusbar_alarm_max_risk_txt"><?=gettext("Highest")?> <b><?=gettext("risk")?></b>:</a></td>
					<td><table style="width:auto" cellpadding=0 cellspacing=0><tr><td style="text-align:left"><a href="javascript:;" target="topmenu" class="blackp" id="statusbar_alarm_max_risk">-</a></td></tr></table></td>
				</tr>
				
				<tr bgcolor="#E1EFE0">
					<td class="bartitle"><?=gettext("Vulnerabilities")?></td>
					<td class="capsule" width="50"><a href="../top.php?option=2&soption=2&url=<?=urlencode("vulnmeter/index.php?value=$host&type=hn&hmenu=Vulnerabilities&smenu=Vulnerabilities")?>" target="topmenu" class="whitepn"><?=Util::number_format_locale((int)$vul_foundrows,0)?></a></td>
					<td class="blackp" style="font-size:8px" nowrap><?=$vul_lastdate?></td>
					<td class="blackp" nowrap style="text-align:right"><?=gettext("Highest Risk")?>:</td>
					<td class="blackp" style="text-align:left"><table style="width:auto;background-color:transparent" cellpadding=0 cellspacing=0><tr><td class="blackp"><a href="../top.php?option=2&soption=2&url=<?=urlencode("vulnmeter/index.php?value=$host&type=hn&hmenu=Vulnerabilities&smenu=Vulnerabilities")?>" target="topmenu" class="blackp" style="background-color:transparent"><?=Incident::get_priority_in_html($vul_highrisk)?></a></td><td class="blackp" style="background-color:transparent"> (<b><?=$vul_risknum?></b> <i><?=gettext("events")?></i>)</td></tr></table></td>
				</tr>
				<tr>
					<td style="border:0px solid white" class="bartitle"><b><?=_("SIEM")?></b> <?=gettext("Events")?></td>
					<td style="border:0px solid white" class="capsule" width="50"><a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target="topmenu" class="whitepn"><?=Util::number_format_locale((int)$sim_foundrows,0)?></a></td>
					<td class="blackp" style="font-size:8px;border:0px" align="center" nowrap><?=$sim_date?></td>
					<td class="blackp" nowrap style="text-align:right"><?=gettext("Highest Risk")?>:</td>
					<td class="blackp" style="text-align:left" nowrap><table style="width:auto" cellpadding=0 cellspacing=0><tr><td class="blackp"><a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target="topmenu" class="blackp"><?=Incident::get_priority_in_html($sim_highrisk)?></a></td><td class="blackp"> (<b><?=$sim_risknum?></b> <i><?=gettext("events")?></i>)</td></tr></table></td>
				</tr>

				<tr bgcolor="#E1EFE0">
					<td style="border:0px" class="bartitle"><b><?=_("Logger")?></b> <?=gettext("Events")?></td>
					<td style="border:0px" class="capsule" width="50"><a href="../top.php?option=2&soption=1&url=<?=urlencode("sem/index.php?hmenu=SEM&smenu=SEM&query=".urlencode("ip=$host"))?>" target="topmenu" class="whitepn"><?=(($sem_foundrows == 50000) ? ">" : "").Util::number_format_locale((int)$sem_foundrows,0)?></a></td>
					<td class="blackp" style="font-size:8px;border:0px" align="center" nowrap><?=$sem_date?></td>
					<td class="blackp" nowrap style="text-align:right"><?=gettext("Last Week")?>:</td>
					<td class="blackp" style="text-align:left"><a href="../top.php?option=2&soption=1&url=<?=urlencode("sem/index.php?hmenu=SEM&smenu=SEM&query=".urlencode("ip=$host"))?>" target="topmenu" class="blackp"><b><?=Util::number_format_locale((int)$sem_foundrows_week,0)?></b> <i><?=gettext("events")?></i></a></td>
				</tr>
				<tr>
					<td class="bartitle"><?=gettext("Anomalies")?></td>
					<td class="capsule" width="50"><a href="../top.php?option=2&soption=3&url=<?=urlencode("control_panel/anomalies.php?hmenu=Anomalies&smenu=Anomalies")?>" target="topmenu" class="whitepn"><?=Util::number_format_locale((int)$anm_foundrows,0)?></a></td>
					<td class="blackp" style="font-size:8px" align="center" nowrap><?=$anm_date?></td>
					<td class="blackp" nowrap style="text-align:right"><?=gettext("Last Week")?>:</td>
					<td class="blackp" style="text-align:left"><a href="../top.php?option=2&soption=3&url=<?=urlencode("control_panel/anomalies.php?hmenu=Anomalies&smenu=Anomalies")?>" target="topmenu" class="blackp"><b><?=Util::number_format_locale((int)$anm_foundrows_week,0)?></b> <i><?=gettext("events")?></i></a></td>
				</tr>

				<tr bgcolor="#E1EFE0">
					<td class="bartitle"><?=gettext("Availability Events")?></td>
					<td class="capsule" width="50"><a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target="topmenu" class="whitepn"><?=Util::number_format_locale((int)$ava_foundrows,0)?></a></td>
					<td class="blackp" style="font-size:8px;border:0px" align="center" nowrap><?=$ava_date?></td>
					<td class="blackp" nowrap style="text-align:right"><?=gettext("High Prio")?>:</td>
					<td class="blackp" style="text-align:left"><a href="../top.php?option=2&soption=0&url=<?=urlencode("forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d&ip=$host&date_range=week&hmenu=Forensics&smenu=Forensics")?>" target="topmenu" class="blackp"><b><?=$ava_highprio?></b></a> (<b><?=Util::number_format_locale((int)$ava_prionum,0)?></b> <i><?=gettext("events")?></i>)</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
