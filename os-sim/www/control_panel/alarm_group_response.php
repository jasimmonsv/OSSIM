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
function build_url($action, $extra) {
	global $date_from, $date_to, $show_options, $src_ip, $dst_ip, $num_alarms_page, $hide_closed, $autorefresh, $refresh_time, $inf, $sup;
	if (empty($action)) {
		$action = "none";
	}
	$options = "";
	if (!empty($date_from)) {
		$options = $options . "&date_from=" . $date_from;
	}
	if (!empty($date_to)) $options = $options . "&date_to=" . $date_to;
	if (!empty($show_options)) $options = $options . "&show_options=" . $show_options;
	if (!empty($autorefresh)) $options = $options . "&autorefresh=on";
	if (!empty($refresh_time)) $options = $options . "&refresh_time=" . $refresh_time;
	if (!empty($src_ip)) $options = $options . "&src_ip=" . $src_ip;
	if (!empty($dst_ip)) $options = $options . "&dsp_ip=" . $dsp_ip;
	if (!empty($num_alarms_page)) $options = $options . "&num_alarms_page=" . $num_alarms_page;
	if (!empty($hide_closed)) $options = $options . "&hide_closed=on";
	if ($action != "change_page") {
		if (!empty($inf)) $options = $options . "&inf=" . $inf;
		if (!empty($sup)) $options = $options . "&sup=" . $sup;
	}
	$url = "alarm_group_console.php?action=" . $action . $extra . $options;
	return $url;
}

require_once ('classes/Util.inc');
require_once ("classes/Alarm.inc");
require_once ("classes/AlarmGroups.inc");
require_once ('classes/Host.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Session.inc');

include ("geoip.inc");
Session::logcheck("MenuIncidents", "ControlPanelAlarms");
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$src_ip = GET('ip_src');
$dst_ip = GET('ip_dst');
$timestamp = GET('timestamp');
$from_date = GET('date_from');
$to_date = GET('date_to');
$timestamp = GET('timestamp');
$name = $_SESSION[GET('name')];
$group_id = GET('group_id');
$hide_closed = GET('hide_closed');
$only_delete = GET('only_delete'); // Number of groups to delete
$only_close = GET('only_close'); // Number of groups to close
$only_open = GET('only_open'); // Number of groups to open
$unique_id = GET('unique_id');
$no_resolv = intval(GET('no_resolv'));
$top = (GET('top') != "") ? GET('top') : 100;
$from = (GET('from') != "") ? GET('from') : 0;
$top += $from;

$timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","",$timestamp);

ossim_valid($src_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("src_ip"));
ossim_valid($dst_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("dst_ip"));
ossim_valid($timestamp, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("timestamp"));
ossim_valid($from_date, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from_date"));
ossim_valid($to_date, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to_date"));
ossim_valid($name, OSS_DIGIT, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, '\>\<', 'illegal:' . _("name"));
ossim_valid($hide_closed, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($only_delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("only_delete"));
ossim_valid($only_close, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("only_close"));
ossim_valid($only_open, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("only_open"));
ossim_valid($unique_id, OSS_ALPHA, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("unique id"));
ossim_valid($group_id, OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, OSS_SCORE, 'illegal:' . _("group_id"));
ossim_valid($no_resolv, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("no_resolv"));
if (ossim_error()) {
    die(ossim_error());
}

if ($timestamp != "") {
	$from_date = ($timestamp!="") ? $timestamp." 00:00:00" : null;
	$to_date = ($timestamp!="") ? $timestamp : null;
}

if ($only_delete) {
	for ($i = 1; $i <= $only_delete; $i++) {
		$data = explode("_",GET('group'.$i));
		$name = $_SESSION[$data[0]];
		$src_ip = $data[1];
		$dst_ip = $data[2];
		$timestamp = $data[3];
		$timestamp_date = preg_replace("/ \d\d\:\d\d\:\d\d/","",$timestamp);
		AlarmGroups::delete_group ($conn, $data[0], $_SESSION["_user"]);
		list ($list,$num_rows) = AlarmGroups::get_alarms ($conn,$src_ip,$dst_ip,0,"",null,null,$timestamp,$timestamp_date,$name);
		foreach ($list as $s_alarm) {
			$s_backlog_id = $s_alarm->get_backlog_id();
			$s_event_id = $s_alarm->get_event_id();
			Alarm::delete_from_backlog($conn, $s_backlog_id, $s_event_id);
		}
	}
	exit;
}
if ($only_close) {
	for ($i = 1; $i <= $only_close; $i++) {
		$data = explode("_",GET('group'.$i));
		$name = $_SESSION[$data[0]];
		$src_ip = $data[1];
		$dst_ip = $data[2];
		$timestamp = $data[3];
		AlarmGroups::change_status ($conn, $data[0], "closed");
		list ($list,$num_rows) = AlarmGroups::get_alarms ($conn,$src_ip,$dst_ip,0,"",null,null,$from_date,$to_date,$name);
		foreach ($list as $s_alarm) {
			$s_backlog_id = $s_alarm->get_backlog_id();
			$s_event_id = $s_alarm->get_event_id();
			Alarm::close($conn, $s_event_id);
		}
	}
	exit;
}
if ($only_open) {
	for ($i = 1; $i <= $only_open; $i++) {
		$data = explode("_",GET('group'.$i));
		$name = $_SESSION[$data[0]];
		$src_ip = $data[1];
		$dst_ip = $data[2];
		$timestamp = $data[3];
		AlarmGroups::change_status ($conn, $data[0], "open");
		list ($list,$num_rows) = AlarmGroups::get_alarms ($conn,$src_ip,$dst_ip,0,"",null,null,$from_date,$to_date,$name);
		foreach ($list as $s_alarm) {
			$s_backlog_id = $s_alarm->get_backlog_id();
			$s_event_id = $s_alarm->get_event_id();
			Alarm::open($conn, $s_event_id);
		}
	}
	exit;
}

$host_list = Host::get_list($conn);
$assets = array();
foreach($host_list as $host) {
	$assets[$host->get_ip() ] = $host->get_asset();
}
list ($list,$num_rows) = AlarmGroups::get_alarms ($conn,$src_ip,$dst_ip,$hide_closed,"",$from,$top,$from_date,$to_date,$name);

?>
<table class="transparent" width="100%">
	<?php if ($from < 1) { ?>
    <tr>
		<td class="nobborder"></td>
		<td class="nobborder"></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Alarm Name")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Risk")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Since Date")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Date")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Source")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Destination")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Status")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Action")?></td>
	</tr>
    <?php } else { // hidden header ?>
    <tr>
		<td class="nobborder"></td>
		<td class="nobborder"></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Alarm Name")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Risk")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Since Date")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Date")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Source")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Destination")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Status")?></td>
		<td style='text-align: center; background-color:transparent;font-weight:bold;color:transparent;border-bottom:0px'><?=gettext("Action")?></td>
	</tr>
    <?php } ?>
<? foreach ($list as $s_alarm) {
	$bgcolor = (++$i%2 == 0) ? "#FAFAFA" : "#F2F2F2";
	$s_id = $s_alarm->get_plugin_id();
	$s_sid = $s_alarm->get_plugin_sid();
	$s_backlog_id = $s_alarm->get_backlog_id();
	$s_event_id = $s_alarm->get_event_id();
	$s_src_ip = $s_alarm->get_src_ip();
	$s_src_port = $s_alarm->get_src_port();
	$s_dst_port = $s_alarm->get_dst_port();
	$s_dst_ip = $s_alarm->get_dst_ip();
	$s_status = $s_alarm->get_status();
	$s_asset_src = array_key_exists($s_src_ip, $assets) ? $assets[$s_src_ip] : $default_asset;
	$s_asset_dst = array_key_exists($s_dst_ip, $assets) ? $assets[$s_dst_ip] : $default_asset;
	/*
	$s_src_port = Port::port2service($conn, $s_src_port);
	$s_dst_port = Port::port2service($conn, $s_dst_port);
	
	*/
	$s_src_link = "../report/index.php?host=$s_src_ip&section=events";
	$src_title = gettext("Src Asset:")." <b>$s_asset_src</b><br>IP: <b>$s_src_ip</b>";
	$s_dst_link = "../report/index.php?host=$s_dst_ip&section=events";
	$dst_title = gettext("Dst Asset:")." <b>$s_asset_dst</b><br>IP: <b>$s_dst_ip</b>";
	$s_src_name = ($no_resolv) ? $s_src_ip : Host::ip2hostname($conn, $s_src_ip);
	$s_dst_name = ($no_resolv) ? $s_dst_ip : Host::ip2hostname($conn, $s_dst_ip);
	// $s_src_name = $s_src_ip;
	// $s_dst_name = $s_dst_ip;
	$s_src_img = str_replace("\"", "'", Host_os::get_os_pixmap($conn, $s_src_ip));
	$s_dst_img = str_replace("\"", "'", Host_os::get_os_pixmap($conn, $s_dst_ip));
	$src_country = strtolower(geoip_country_code_by_addr($gi, $s_src_ip));
	$src_country_img = ($src_country) ? "<img src='/ossim/pixmaps/flags/" . $src_country . ".png'>" : "";
	$dst_country = strtolower(geoip_country_code_by_addr($gi, $s_dst_ip));
	$dst_country_img = ($dst_country) ? "<img src='/ossim/pixmaps/flags/" . $dst_country . ".png'>" : "";
	$source_link = "<a href='" . $s_src_link . "' title='" . $s_src_ip . "' >" . $s_src_name . "</a>:" . $s_src_port . " $s_src_img $src_country_img";
	$source_balloon = "<div class='balloon'>" . $source_link . "<span class='tooltip'><span class='top'></span><span class='middle ne11'>$src_title</span><span class='bottom'></span></span></div>";
	$dest_link = "<a href='" . $s_dst_link . "' title='" . $s_dst_ip . "' >" . $s_dst_name . "</a>:" . $s_dst_port . " $s_dst_img $dst_country_img";
	$dest_balloon = "<div class='balloon'>" . $dest_link . "<span class='tooltip'><span class='top'></span><span class='middle ne11'>$dst_title</span><span class='bottom'></span></span></div>";
	//		    $selection_array[$group_id][$child_number] = $s_backlog_id . "-" . $s_event_id;
	$s_sid_name = "";
	if ($s_plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $s_id AND sid = $s_sid")) {
		$s_sid_name = $s_plugin_sid_list[0]->get_name();
		$s_sid_priority = $s_plugin_sid_list[0]->get_priority();
	} else {
		$s_sid_name = "Unknown (id=$s_id sid=$s_sid)";
		$s_sid_priority = "N/A";
	}
	$s_date = Util::timestamp2date($s_alarm->get_timestamp());
	if ($s_backlog_id != 0) {
		$s_since = Util::timestamp2date($s_alarm->get_since());
	} else {
		$s_since = $s_date;
	}
	$s_risk = $s_alarm->get_risk();
	//$s_alarm_link = Util::get_acid_pair_link($s_date, $s_alarm->get_src_ip(), $s_alarm->get_dst_ip());
	//		    $s_alarm_link = "events.php?backlog_id=$s_backlog_id";
	//$s_alarm_link = "javascript:xajax_getEvents(" . $s_backlog_id . "," . $s_event_id .");";
	$s_alarm_link = "javascript:toggle_event(" . $s_backlog_id . "," . $s_event_id . ");";
	/* Alarm name */
	$s_alarm_name = ereg_replace("directive_event: ", "", $s_sid_name);
	$s_alarm_name = Util::translate_alarm($conn, $s_alarm_name, $s_alarm);
	$summary = Alarm::get_alarm_stats($conn, $s_backlog_id, $s_event_id);
	$event_ocurrences = $summary["total_count"];
	if ($event_ocurrences != 1) {
		$ocurrences_text = strtolower(gettext("Events"));
	} else {
		$ocurrences_text = strtolower(gettext("Event"));
	}
	$events_count = ($event_ocurrences > 0) ? "<br><font style='font-size: 9px; color: #AAAAAA;'>($event_ocurrences $ocurrences_text)</font>" : "";
	$balloon_name = "<div class='balloon'>" . $s_alarm_name . $events_count . "<span class='tooltip'><span class='top'></span><span class='middle ne11'>".gettext("Src Asset:")." <b>" . $s_asset_src . "</b><br>".gettext("Dst Asset:")." <b>" . $s_asset_dst . "</b><br>Priority: <b>" . $s_sid_priority . "</b></span><span class='bottom'></span></span></div>";
	/* Risk field */
	if ($s_risk > 7) {
		$color = "red; color:white";
	} elseif ($s_risk > 4) {
		$color = "orange; color:black";
	} elseif ($s_risk > 2) {
		$color = "green; color:white";
	}
	if ($color) {
		$risk_field = "<td class='nobborder' style='text-align: center; background-color: " . $color . ";'>" . $s_risk . "</td>";
	} else {
		$risk_field = "<td class='nobborder' style='text-align: center' >" . $s_risk . "</td>";
	}
	/* Delete link */
	/*
	if ($s_backlog_id == 0) {
	$s_delete_link = '<a title=\'' . gettext("Delete") . '\' href=\'javascript:confirm_delete(\"' . $_SERVER["SCRIPT_NAME"] .
	"?delete=$s_event_id" .
	"&sup=" . "$sup" .
	"&inf=" . ($sup-$ROWS) .
	"&hide_closed=$hide_closed" . "\\\");'>" .
	"<img border=0 src='../pixmaps/cross-circle-frame.png' style='visibility: visible;'/>" . "</a>";
	} else {
	$s_delete_link = '<a title=\'' . gettext("Delete") . '\' href=\'javascript:confirm_delete(\"' . $_SERVER["SCRIPT_NAME"] .
	"?delete_backlog=" . "$s_backlog_id-$s_event_id" .
	"&sup=" . "$sup" .
	"&inf=" . ($sup-$ROWS) .
	"&hide_closed=$hide_closed" . "\\\");'>" .
	"<img border=0 src='../pixmaps/cross-circle-frame.png' style='visibility: visible;' />" . "</a>";
	}
	}*/
	$s_delete_link = ($s_status == 'open') ? "<a href='' onclick=\"document.getElementById('action').value='close_alarm';document.getElementById('alarm').value='$s_event_id';form_submit();return false\" title='" . gettext("Click here to close alarm") . "'><img border=0 src='../pixmaps/cross-circle-frame.png' style='visibility: visible;'></a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'>";
	/* Checkbox */
	if ($owner == $_SESSION["_user"] || $owner == "") {
		$checkbox = "<input type='checkbox' name='check_".$s_backlog_id."_".$s_event_id."' class='alarm_check' value='1'>";
	} else {
		$checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='true' value='" . $s_backlog_id . "-" . $s_event_id . "'>";
	}
	if ($s_status == 'open') {
		$status_link = "<a href='' onclick=\"document.getElementById('action').value='close_alarm';document.getElementById('alarm').value='$s_event_id';form_submit();return false\" style='color:" . (($s_status == "open") ? "#923E3A" : "#4C7F41") . "'>" . gettext("Open") . "</a>";
		//$status_link = "<a href='" . build_url("close_alarm", "&alarm=" . $s_backlog_id . "-" . $s_event_id) . "' title='" . gettext("Click here to close alarm") ."'>" . gettext("Open") . "</a>";
		
	} else {
		$status_link = "<a href='' onclick=\"document.getElementById('action').value='open_alarm';document.getElementById('alarm').value='$s_event_id';form_submit();return false\" style='color:" . (($s_status == "open") ? "#923E3A" : "#4C7F41") . "'>" . gettext("Closed") . "</a>";
		//$status_link = "<a href='alarm_group_console.php?action=open_alarm&alarm=" . $s_event_id . "&unique_id=$unique_id' style='color:" . (($s_status == "open") ? "#923E3A" : "#4C7F41") . "'>" . gettext("Closed") . "</a>";
		$checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='true' value='" . $s_backlog_id . "-" . $s_event_id . "'>";
	}
	/* Expand button */
	if ($event_ocurrences > 0) $expand_button = "<a href='' onclick=\"toggle_alarm('$s_backlog_id','$s_event_id');return false;\"><img src='../pixmaps/plus-small.png' border='0' alt='plus'></img></a>";
	else $expand_button = "<img src='../pixmaps/plus-small-gray.png' border='0' alt='plus'>";
?>
	<tr>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: center;padding-left:30px' width='3%' id="eventplus<?=$s_backlog_id . "-" . $s_event_id?>"><?php echo $expand_button ?></td>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: center'><?=$checkbox?></td>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: left; padding-left:10px' width='30%'><strong><?=$balloon_name?></strong></td>
		<?=$risk_field?>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: center' width='12%'><?=$s_since?></td>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: center' width='12%'><?=$s_date?></td>
		<td class="nobborder" nowrap style='background-color:<?php echo $bgcolor ?>;text-align: center;'><?=$source_balloon?></td>
		<td class="nobborder" nowrap style='background-color:<?php echo $bgcolor ?>;text-align: center;'><?=$dest_balloon?></td>
		<td class="nobborder" bgcolor='<?=(($s_status == "open") ? "#ECE1DC" : "#DEEBDB")?>' style='text-align: center; border-width: 0px;'><b><?=$status_link?></b></td>
		<td class="nobborder" style='background-color:<?php echo $bgcolor ?>;text-align: center'><?=$s_delete_link?></td>
	</tr>
	<tr>
		<td class="nobborder"></td>
		<td class="nobborder" colspan='9' name='eventbox<?=$s_backlog_id . "-" . $s_event_id?>"' id='eventbox<?=$s_backlog_id . "-" . $s_event_id?>'></td></tr>
<? } ?>
	<?php if ($top < $num_rows) { ?>
	<div id="link_row" style="display:inline">
	<tr>
		<td class="center nobborder" colspan="10"><a href="" onclick="toggle_group('<?=$group_id ?>','<?=$name ?>','<?php echo $src_ip ?>','<?php echo $dst_ip ?>','',<?php echo $from + 100 ?>);this.style.color='transparent';return false">> <?php echo _("Show the next 100 alarms") ?></a></td>
	</tr>
	</div>
	<tr>
		<td class="center nobborder" colspan="10" id="<?php echo $group_id.($from + 100)?>"></td>
	</tr>
 <?php } ?>
</table>