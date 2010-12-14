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
	$url = $_SERVER["SCRIPT_NAME"] . "?action=" . $action . $extra . $options;
	return $url;
}

//ini_set('memory_limit', '128M');

require_once ('classes/Util.inc');
require_once ("classes/Alarm.inc");
require_once ("classes/AlarmGroups.inc");
require_once ('classes/Host.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Session.inc');
require_once ('classes/Port.inc');
require_once ('classes/Protocol.inc');

include ("geoip.inc");
Session::logcheck("MenuIncidents", "ControlPanelAlarms");
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$name = GET('name');
$hide_closed = GET('hide_closed');
$from_date = (GET('from_date') != "") ? GET('from_date') : "1970-01-01";
$to_date = (GET('to_date') != "") ? GET('to_date') : "3000-01-01";
$top = (GET('top') != "") ? GET('top') : 100;
$from = (GET('from') != "") ? GET('from') : 0;
$top += $from;
$group_id = GET('group_id');

ossim_valid($from_date, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from_date"));
ossim_valid($to_date, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to_date"));
ossim_valid($name, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, OSS_PUNC_EXT, '\<\>', 'illegal:' . _("name"));
ossim_valid($hide_closed, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($top, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("top"));
ossim_valid($from, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("from"));
ossim_valid($group_id, OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("group_id"));
if (ossim_error()) {
    die(ossim_error());
}

$host_list = Host::get_list($conn);
$assets = array();
foreach($host_list as $host) {
	$assets[$host->get_ip() ] = $host->get_asset();
}

list ($list,$num_rows) = AlarmGroups::get_alarms ($conn,"","",$hide_closed,"ORDER BY a.timestamp DESC",$from,$top,$from_date,$to_date,$name);
$ports = Protocol::get_list($conn);

?>
<table width="100%" <?php if ($from > 0) echo "class='transparent'" ?>>
    <?php if ($from < 1) { ?>
    <tr>
        <th>&nbsp;</th>
        <!--<th>&nbsp;</th>-->
        <th><?=_("Alarm")?></th>
        <th><?=_("Risk")?></th>
        <th><?=_("Since")?></th>
        <th><?=_("Last")?></th>
        <th><?=_("Source")?></th>
        <th><?=_("Destination")?></th>
        <th><?=_("Protocol")?></th>
    </tr>
    <?php } else { // hidden header ?>
    <tr>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent">&nbsp;</th>
        <!--<th>&nbsp;</th>-->
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Alarm")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Risk")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Since")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Last")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Source")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Destination")?></th>
        <th style="border:1px solid transparent;background-image:none;background-color:transparent;color:transparent"><?=_("Protocol")?></th>
    </tr>
    <?php } ?>
<? foreach ($list as $s_alarm) {
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
	$src_title = "Src Asset: <b>$s_asset_src</b><br>IP: <b>$s_src_ip</b>";
	$s_dst_link = "../report/index.php?host=$s_dst_ip&section=events";
	$dst_title = "Dst Asset: <b>$s_asset_dst</b><br>IP: <b>$s_dst_ip</b>";
	$s_src_name = Host::ip2hostname($conn, $s_src_ip);
	$s_dst_name = Host::ip2hostname($conn, $s_dst_ip);
	// $s_src_name = $s_src_ip;
	// $s_dst_name = $s_dst_ip;
	$s_src_img = str_replace("\"", "'", Host_os::get_os_pixmap($conn, $s_src_ip));
	$s_dst_img = str_replace("\"", "'", Host_os::get_os_pixmap($conn, $s_dst_ip));
	$src_country = strtolower(geoip_country_code_by_addr($gi, $s_src_ip));
	$src_country_img = ($src_country) ? "<img src='/ossim/pixmaps/flags/" . $src_country . ".png'>" : "";
	$dst_country = strtolower(geoip_country_code_by_addr($gi, $s_dst_ip));
	$dst_country_img = ($dst_country) ? "<img src='/ossim/pixmaps/flags/" . $dst_country . ".png'>" : "";
	$source_link = "<a href='" . $s_src_link . "' title='" . $s_src_ip . "' >" . $s_src_name . "</a>:" . Port::port2service($conn, $s_src_port) . " $s_src_img $src_country_img";
	$source_balloon = "<div class='balloon'>" . $source_link . "<span class='tooltip'><span class='top'></span><span class='middle ne11'>$src_title</span><span class='bottom'></span></span></div>";
	$dest_link = "<a href='" . $s_dst_link . "' title='" . $s_dst_ip . "' >" . $s_dst_name . "</a>:" . Port::port2service($conn, $s_dst_port) . " $s_dst_img $dst_country_img";
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
	/* Risk field */
	if ($s_risk > 7) {
		$color = "red; color:white";
	} elseif ($s_risk > 4) {
		$color = "orange; color:black";
	} elseif ($s_risk > 2) {
		$color = "green; color:white";
	}
	if ($color) {
		$risk_field = "<td class='nobborder' style='text-align: center; border-width: 1px; background-color: " . $color . ";'>" . $s_risk . "</td>";
	} else {
		$risk_field = "<td class='nobborder' style='text-align: center; border-width: 1px;' >" . $s_risk . "</td>";
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
	$s_delete_link = ($s_status == 'open') ? "<a href='" . build_url("close_alarm", "&alarm=" . $s_backlog_id . "-" . $s_event_id) . "' title='" . gettext("Click here to close alarm") . "'><img border=0 src='../pixmaps/cross-circle-frame.png' style='visibility: visible;'></a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'>";
	/* Checkbox */
	if ($owner == $_SESSION["_user"] || $owner == "") {
		$checkbox = "<input type='checkbox' name='alarm_checkbox' value='" . $s_event_id . "' disabled>";
	} else {
		$checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='true' value='" . $s_backlog_id . "-" . $s_event_id . "' disabled>";
	}
	if ($s_status == 'open') {
		$status_link = "<a href='alarm_group_console.php?action=close_alarm&alarm=" . $s_event_id . "' style='color:" . (($s_status == "open") ? "#923E3A" : "#4C7F41") . "'>" . gettext("Open") . "</a>";
		//$status_link = "<a href='" . build_url("close_alarm", "&alarm=" . $s_backlog_id . "-" . $s_event_id) . "' title='" . gettext("Click here to close alarm") ."'>" . gettext("Open") . "</a>";
		
	} else {
		$status_link = "<a href='alarm_group_console.php?action=open_alarm&alarm=" . $s_event_id . "' style='color:" . (($s_status == "open") ? "#923E3A" : "#4C7F41") . "'>" . gettext("Closed") . "</a>";
		$checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='true' value='" . $s_backlog_id . "-" . $s_event_id . "'>";
	}
	$summary = Alarm::get_alarm_stats($conn, $s_backlog_id, $s_event_id);
	$event_ocurrences = $summary["total_count"];
	if ($event_ocurrences != 1) {
		$ocurrences_text = strtolower(gettext("Events"));
	} else {
		$ocurrences_text = strtolower(gettext("Event"));
	}
	$balloon_name = "<div class='balloon'>" . $s_alarm_name . " <font style='font-size: x-small; font-weight:normal; text-color: #AAAAAA;'>($event_ocurrences $ocurrences_text)</font><span class='tooltip'><span class='top'></span><span class='middle ne11'>Src Asset: <b>" . $s_asset_src . "</b><br>Dst Asset: <b>" . $s_asset_dst . "</b><br>Priority: <b>" . $s_sid_priority . "</b></span><span class='bottom'></span></span></div>";
	/* Expand button */
	if ($event_ocurrences > 0) $expand_button = "<a href='" . $s_alarm_link . "' ><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
	else $expand_button = "<strong>[-]</strong>";
?>
	<tr>
		<td style='text-align: center' id="eventplus<?=$s_backlog_id . "-" . $s_event_id?>"><a href="" onclick="toggle_alarm('<?=$s_backlog_id?>','<?=$s_event_id?>');return false;"><img src='../pixmaps/plus-small.png' border=0></a></td>
		<!--<td style='text-align: center'><?//=$checkbox?></td>-->
		<td style='text-align: left; padding-left:10px' nowrap>
            <strong><?=$balloon_name?></strong>
        </td>
		<?=$risk_field?>
		<td style='text-align: center' nowrap><?=$s_since?></td>
		<td style='text-align: center' nowrap><?=$s_date?></td>
		<td nowrap style='text-align: center; background-color: #eeeeee;'><?=$source_balloon?></td>
		<td nowrap style='text-align: center; background-color: #eeeeee;'><?=$dest_balloon?></td>
<?      if($ports[$s_alarm->get_protocol()]!=""){
            $cport = $ports[$s_alarm->get_protocol()]->get_alias();
        }
        else {
            $cport = $s_alarm->get_protocol();
        }
        ?>
        <td nowrap style='text-align: center; background-color: #eeeeee;'><?=$cport?></td>
		<!--<td bgcolor='<?//=(($s_status == "open") ? "#ECE1DC" : "#DEEBDB")?>' style='text-align: center; border-width: 1px;border:1px solid <?//=(($s_status == "open") ? "#E6D8D2" : "#D6E6D2")?>'><b><?//=$status_link?></b></td>
		<td style='text-align: center'><?//=$s_delete_link?></td>-->
	</tr>
	<tr>
		<td class="nobborder"></td>
		<td class="nobborder" colspan='6' name='eventbox<?=$s_backlog_id . "-" . $s_event_id?>"' id='eventbox<?=$s_backlog_id . "-" . $s_event_id?>'></td></tr>
 <? } ?>
 <?php if ($num_rows > count($list)) { ?>
	<div id="link_row" style="display:inline">
	<tr>
		<td class="center nobborder" colspan="8"><a href="" onclick="toggle_group('<?=$group_id ?>','<?=$name ?>',<?php echo $from + $top ?>);this.style.color='transparent';return false">> <?php echo _("Show the next 100 alarms") ?></a></td>
	</tr>
	</div>
	<tr>
		<td class="center nobborder" colspan="8" id="<?php echo $group_id.($from + $top)?>"></td>
	</tr>
 <?php } ?>
</table>