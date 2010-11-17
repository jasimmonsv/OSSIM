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
include ("classes/AlarmGroups.inc");
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "ControlPanelAlarms");

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
	$url = $_SERVER["SCRIPT_NAME"] . "?action=" . $action . $extra . $options . "&bypassexpirationupdate=1";
	return $url;
}


require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();

/* GET VARIABLES FROM URL */
//$ROWS = 100;
$ROWS = 10;
$db = new ossim_db();
$conn = $db->connect();
// Xajax . Register function getEvents
//$xajax = new xajax();
//$xajax->registerFunction("getEvents");
//$xajax->processRequests();
$delete = GET('delete');
$delete_group = GET('delete_group');
$close = GET('close');
$delete_day = GET('delete_day');
$order = GET('order');
$src_ip = GET('src_ip');
$dst_ip = GET('dst_ip');
$backup_inf = $inf = GET('inf');
$sup = GET('sup');
$hide_closed = GET('hide_closed');
$date_from = preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\1-\\2", GET('date_from'));
$date_to = preg_replace("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\1-\\2", GET('date_to'));
$num_alarms_page = GET('num_alarms_page');
$disp = GET('disp'); // Telefonica disponibilidad hack
$group = GET('group');
$new_descr = GET('descr');
$action = GET('action');
$show_options = GET('show_options');
$refresh_time = GET('refresh_time');
$autorefresh = GET('autorefresh');
$alarm = GET('alarm');
ossim_valid($disp, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("disp"));
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));
ossim_valid($delete_group, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));
ossim_valid($close, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("close"));
ossim_valid($open, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("open"));
ossim_valid($delete_day, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("delete_day"));
ossim_valid($src_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 'illegal:' . _("src_ip"));
ossim_valid($dst_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 'illegal:' . _("dst_ip"));
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($hide_closed, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($autorefresh, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("autorefresh"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("field number of alarms per page"));
ossim_valid($new_descr, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("descr"));
ossim_valid($show_options, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("show_options"));
ossim_valid($refresh_time, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("refresh_time"));
ossim_valid($alarm, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("alarm"));
ossim_valid($group, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("group"));
//action=change_descr
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("action"));
if (ossim_error()) {
    die(ossim_error());
}

if (empty($order)) $order = " timestamp DESC";
if ((!empty($src_ip)) && (!empty($dst_ip))) {
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip' 
                     OR inet_ntoa(dst_ip) = '$dst_ip'";
} elseif (!empty($src_ip)) {
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip'";
} elseif (!empty($dst_ip)) {
    $where = "WHERE inet_ntoa(dst_ip) = '$dst_ip'";
} else {
    $where = '';
}
if ($hide_closed == 'on') {
    $hide_closed = 1;
} else {
    $hide_closed = 0;
}
if ($autorefresh == 'on') {
    $autorefresh = 1;
} else {
    $autorefresh = 0;
}
if ($num_alarms_page) {
    $ROWS = $num_alarms_page;
}
if (empty($inf)) $inf = 0;
if (!$sup) $sup = $ROWS;

if (empty($show_options) || ($show_options < 1 || $show_options > 4)) {
    $show_options = 1;
}
if (empty($refresh_time) || ($refresh_time != 30 && $refresh_time != 60 && $refresh_time != 180 && $refresh_time != 600)) {
    $refresh_time = 60;
}

if (GET('take') != "") {
	if (!ossim_valid(GET('take'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("take"))) exit;
	AlarmGroups::take_group ($conn, GET('take'), $_SESSION["_user"]);
}
if (GET('release') != "") {
	if (!ossim_valid(GET('release'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("release"))) exit;
	AlarmGroups::release_group ($conn, GET('release'));
}
if ($new_descr != "" && $group != "") {
	if (!ossim_valid($new_descr, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("descr"))) exit;
	if (!ossim_valid($group, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("group"))) exit;
	AlarmGroups::change_descr ($conn, $new_descr, $group);
}
if (GET('close_group') != "") {
	if (!ossim_valid(GET('close_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("close_group"))) exit;
	$group_ids = split(',', GET('close_group'));
    foreach($group_ids as $group_id) AlarmGroups::change_status ($conn, $group_id, "closed");
}
if (GET('open_group') != "") {
	if (!ossim_valid(GET('open_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("open_group"))) exit;
	AlarmGroups::change_status ($conn, GET('open_group'), "open");
}
if (GET('action') == "open_alarm") {
    echo "<br><br><br>OPEN<br>";
	Alarm::open($conn, GET('alarm'));
}
if (GET('action') == "close_alarm") {
    Alarm::close($conn, GET('alarm'));
}
if (GET('action') == "delete_alarm") {
    Alarm::delete($conn, GET('alarm'));
}
$db_groups = AlarmGroups::get_dbgroups($conn);
list($alarm_group, $count) = AlarmGroups::get_unique_alarms($conn, $show_options, $hide_closed, $date_from, $date_to, $src_ip, $dst_ip, "LIMIT $inf,$sup");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?=_("Control Panel")?> </title>
  <?php
    if ($autorefresh) {
        print '<meta http-equiv="refresh" content="' . $refresh_time . ';url=' . build_url("", "") . '"/>';
    }
?>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<!--  <link rel="StyleSheet" href="dtree.css" type="text/css" />-->
  <link rel="stylesheet" href="../style/style.css"/>
  <link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>

  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  <script type="text/javascript">
  var open = false;
  
  function toggle_group (group_id,name,ip_src,ip_dst) {
	document.getElementById(group_id).innerHTML = "<img src='../pixmaps/loading.gif'>";
	//alert("alarm_unique_response.php?name="+name+"&ip_src="+ip_src+"&ip_dst="+ip_dst+"&hide_closed=<?=$hide_closed?>&from_date=<?=$date_from?>&to_date=<?=$date_to?>");
	$.ajax({
		type: "GET",
		url: "alarm_unique_response.php?name="+name+"&ip_src="+ip_src+"&ip_dst="+ip_dst+"&hide_closed=<?=$hide_closed?>&from_date=<?=$date_from?>&to_date=<?=$date_to?>",
		data: "",
		success: function(msg){
			//alert (msg);
			document.getElementById(group_id).innerHTML = msg;
			plus = "plus"+group_id;
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
		}
	});
  }
  function untoggle_group (group_id,name,ip_src,ip_dst) {
	plus = "plus"+group_id;
	document.getElementById(plus).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"');\"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
	document.getElementById(group_id).innerHTML = "";
  }
  
  function toggle_alarm (backlog_id,event_id) {
	var td_id = "eventbox"+backlog_id+"-"+event_id;
	var plus = "eventplus"+backlog_id+"-"+event_id;
	document.getElementById(td_id).innerHTML = "<img src='../pixmaps/loading.gif'>";
	$.ajax({
		type: "GET",
		url: "events_ajax.php?backlog_id="+backlog_id,
		data: "",
		success: function(msg){
			//alert (msg);
			document.getElementById(td_id).innerHTML = msg;
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/minus-small.png' border=0></a>";
			GB_TYPE = 'w';
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,450,'90%');
				return false;
			});
		}
	});
  }
  function untoggle_alarm (backlog_id,event_id) {
	var td_id = "eventbox"+backlog_id+"-"+event_id;
	var plus = "eventplus"+backlog_id+"-"+event_id;
	document.getElementById(td_id).innerHTML = "";
	document.getElementById(plus).innerHTML = "<a href='' onclick=\"toggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/plus-small.png' border=0></a>";
  }
  function tooglebtn() {
	$('#searchtable').toggle();
	if ($("#timg").attr('src').match(/toggle_up/)) 
		$("#timg").attr('src','../pixmaps/sem/toggle.gif');
	else
		$("#timg").attr('src','../pixmaps/sem/toggle_up.gif');
		
	if (!showing_calendar) calendar();
  }
  var showing_calendar = false;
  function calendar() {
	showing_calendar = true;
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
	var datefrom = new Date(<?php echo $y ?>,<?php echo $m ?>,<?php echo $d ?>);
	var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2 ?>,<?php echo $d2 ?>);
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
</head>
<body>
<?


//print_r($alarm_group);
//$count = count($alarm_group);
$tree_count = 0;

    if (GET('withoutmenu') != "1") include ("../hmenu.php");
    /* Filter & Action Console */
    print '<form name="filters" method="GET">';
    ?>
	<input type="hidden" name="date_from" id="date_from"  value="<?php echo $date_from ?>">
	<input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to ?>">
	<?
	print '<table width="90%" align="center" class="transparent"><tr><td class="nobborder left">';
    //print '<a href="javascript:;" onclick="tooglebtn()"><img src="../pixmaps/sem/toggle_up.gif" border="0" id="timg" title="Toggle"> <small><font color="black">'._("Filters, Actions and Options").'</font></small></a>';
    print '<a href="javascript:;" onclick="tooglebtn()"><img src="../pixmaps/sem/toggle.gif" border="0" id="timg" title="Toggle"> <small><font color="black">'._("Filters and Options").'</font></small></a>';
    print '</td></tr></table>';
    print '<table width="90%" align="center" id="searchtable" style="display:none"><tr><th colspan="2" width="60%">';
    //print _("Filter") . '</th><th>' . _("Actions") . '</th><th>' . _("Options") . '</th></tr>';
    print _("Filter") . '</th><th>' . _("Options") . '</th></tr>';
    // Date filter
    $underlined = ($date_from != "" && $date_to != "") ? ";text-decoration:underline" : "";
	print '<tr><td width="10%" id="date_str" style="text-align: right; border-width: 0px'.$underlined.'">';
    print '<b>' . _('Date') . '</b>:
    </td>';
    print '<td class="nobborder">
		<div id="widget">
			<a href="javascript:;"><img src="../pixmaps/calendar.png" id="imgcalendar" border="0"></a>
			<div id="widgetCalendar"></div>
		</div>
	</td>';
    //Actions
/*    print '<td rowspan="3" style="text-align: left;border-bottom:0px solid white" nowrap>
<a href=javascript:close_groups() >Close Groups</a><br/><br>
<a href=javascript:delete_groups()><b>Delete Groups</b></a>
</td>';*/
    //Options
    $selected1 = $selected2 = $selected3 = $selected4 = "";
    if ($show_options == 1) $selected1 = 'selected="true"';
    if ($show_options == 2) $selected2 = 'selected="true"';
    if ($show_options == 3) $selected3 = 'selected="true"';
    if ($show_options == 4) $selected4 = 'selected="true"';
    
    if ($hide_closed) {
        $hide_check = "checked";
    } else {
        $hide_check = "";
    }
    $refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";
    if ($refresh_time == 30) $refresh_sel1 = 'selected="true"';
    if ($refresh_time == 60) $refresh_sel2 = 'selected="true"';
    if ($refresh_time == 180) $refresh_sel3 = 'selected="true"';
    if ($refresh_time == 600) $refresh_sel4 = 'selected="true"';
    if ($autorefresh) {
        $hide_autorefresh = 'checked="true"';
        $disable_autorefresh = '';
    } else {
        $hide_autorefresh = '';
        $disable_autorefresh = 'disabled="true"';
    }
    //print '<td rowspan="3" style="text-align: left;border-bottom:0px solid white"><strong>Show:</strong>&nbsp;<select name="show_options">' . '<option value="1" ' . $selected1 . '>All Groups</option>' . '<option value="2" ' . $selected2 . '>My Groups</option>' . '<option value="3" ' . $selected3 . '>Groups Without Owner</option>' . '<option value="4" ' . $selected4 . '>My Groups & Without Owner</option>' . '</select> <br/>' . '<input type="checkbox" name="hide_closed" ' . $hide_check . ' />' . gettext("Hide closed alarms") . '<br/><input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" ' . $hide_autorefresh . ' />' . gettext("Autorefresh") . '&nbsp;<select name="refresh_time" ' . $disable_autorefresh . ' >' . '<option value="30" ' . $refresh_sel1 . ' >30 sec</options>' . '<option value="60" ' . $refresh_sel2 . ' >1 min</options>' . '<option value="180" ' . $refresh_sel3 . ' >3 min</options>' . '<option value="600" ' . $refresh_sel4 . ' >10 min</options>' . '</select>' . '&nbsp;<a href="' . build_url("", "") . '" >[Refresh]</a>' . '</td> </tr>';
    print '<td rowspan="3" style="text-align: left;border-bottom:0px solid white" valign="top"><input type="checkbox" name="hide_closed" '. $hide_check .'/>' . gettext("Hide closed alarms") . '<br/><input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" ' . $hide_autorefresh . ' />' . gettext("Autorefresh") . '&nbsp;<select name="refresh_time" ' . $disable_autorefresh . ' >' . '<option value="30" ' . $refresh_sel1 . ' >'._("30 sec").'</options>' . '<option value="60" ' . $refresh_sel2 . ' >'._("1 min").'</options>' . '<option value="180" ' . $refresh_sel3 . ' >'._("3 min").'</options>' . '<option value="600" ' . $refresh_sel4 . ' >'._("10 min").'</options>' . '</select>' . '&nbsp;<a href="' . build_url("", "") . '" >['._("Refresh").']</a>' . '</td> </tr>';
    // IP filter
    print '
<tr>
    <td width="10%" style="text-align: right; border-width: 0px">
        <b>' . _("IP Address") . ' </b>:
    </td>
    <td style="text-align: left; border-width: 0px" nowrap>' . _("source") . ': <input type="text" size="15" name="src_ip" value="' . $src_ip . '"> ' . _("destination") . ': <input type="text" size="15" name="dst_ip" value="' . $dst_ip . '">
    </td>
</tr>
';
    // Num alarm page filter
    print '
<tr>
    <td width="10%" style="text-align: right; border-width: 0px" nowrap>
        <b>' . _("Num. alarms per page") . '</b>:
    </td>
    <td style="text-align: left; border-width: 0px">
        <input type="text" size=3 name="num_alarms_page" value="' . $ROWS . '">
    </td>
</tr>
';
    print '<tr ><th colspan="4" style="padding:5px"><input type="submit" class="btn" value="' . _("Go") . '"></th></tr></table>';
    print '</form><br>';
?>
<table cellpadding=0 cellspacing=1 width='100%'>
	<tr>
		<td colspan="7" class="nobborder" style="text-align:center">
			<table class="noborder" align="center" width="100%">
				<tr>
					<td width="200" class="nobborder">
						&nbsp;
					</td>
			<?
				print "<td class='nobborder' style='text-align:center'>\n";
				/* No mola */
				// OPTIMIZADO con SQL_CALC_FOUND_ROWS (5 junio 2009 Granada)
				//$alarm_group = AlarmGroup::get_list($conn, $src_ip, $dst_ip, $hide_closed, "ORDER BY $order", null, null, $date_from, $date_to, $disp, $show_options);
				//$count = count($alarm_group);
				$first_link = build_url("change_page", "&inf=0" . "&sup=" . $ROWS);
				$last_link = build_url("change_page", "&inf=" . ($count - $ROWS) . "&sup=" . $count);
				$inf_link = build_url("change_page", "&inf=" . ($inf - $ROWS) . "&sup=" . ($sup - $ROWS));
				$sup_link = build_url("change_page", "&inf=" . ($inf + $ROWS) . "&sup=" . ($sup + $ROWS));
				if ($inf >= $ROWS) {
					echo "<a href=\"" . $first_link . "\" >&lt;"._("First")."&nbsp;</a>";
					echo "<a href=\"$inf_link\">&lt;-";
					printf(gettext("Prev %d") , $ROWS);
					echo "</a>";
				}
				if ($sup < $count) {
					echo "&nbsp;&nbsp;(";
					printf(gettext("%d-%d of %d") , $inf, $sup, $count);
					echo ")&nbsp;&nbsp;";
					echo "<a href=\"$sup_link\">";
					printf(gettext("Next %d") , $ROWS);
					echo " -&gt;</a>";
					echo "<a href=\"" . $last_link . "\" >&nbsp;"._("Last")."&gt;</a>";
				} else {
					echo "&nbsp;&nbsp;(";
					printf(gettext("%d-%d of %d") , $inf, $count, $count);
					echo ")&nbsp;&nbsp;";
				}
				?>
					</td>
					<td width="200" class="nobborder right">
						<table class="transparent">
							<tr>
								<td class="nobborder" nowrap><a href="alarm_console.php?hide_closed=1"><b><?=_("Ungrouped")?></b></a></td>
								<td class="nobborder"> | </td>
								<td class="nobborder"><a href="alarm_group_console.php"><b><?=_("Grouped")?></b></a></td>
								<td class="nobborder"> | </td>
								<td class="nobborder"><?=_("Unique")?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<? foreach($alarm_group as $group) { 
		$group_id = $group['group_id'];
		$ocurrences = $group['group_count'];
		/*
		if ($group['date'] != $lastday) {
			$lastday = $group['date'];
			list($year, $month, $day) = split("-", $group['date']);
			$date = strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year));
			$show_day = 1;
		} else $show_day = 0;
		*/
		$descr = $db_groups[$group_id]['descr'];
		$status = ($db_groups[$group_id]['status'] != "") ? $db_groups[$group_id]['status'] : "open";
		$incident_link = "<img border=0 src='../pixmaps/script--pencil-gray.png'/>";
		$background = '#DFDFDF;';
		$group_box = "";
        $owner_take = 0;
        $av_description = "readonly='true'";
		
		if ($ocurrences > 1) {
            $ocurrence_text = strtolower(gettext("Alarms"));
        } else {
            $ocurrence_text = strtolower(gettext("Alarm"));
        }
		$owner = ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) ? "<a href='alarm_group.php?release=$group_id'>"._("Release")."</a>" : "<a href='alarm_group.php?take=$group_id'>"._("Take")."</a>";
		
		/*
		if ($db_groups[$group_id]['owner'] != "")
			if ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) {
				$owner_take = 1;
				$background = '#B5C7DF;';
				if ($status == 'open') {
					$owner = "<a href='alarm_group.php?release=$group_id'>Release</a>";
				}
				$group_box = "<input type='checkbox' id='check_" . $group_id . "' name='group' value='" . $group_id . "' >";
				$incident_link = '<a class=greybox2 title=\'New ticket for Group ID' . $group_id . '\' href=\'../incidents/newincident.php?' . "ref=Alarm&" . "title=" . urlencode($alarm_name) . "&" . "priority=$s_risk&" . "src_ips=$src_ip&" . "event_start=$since&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . '\'>' . '<img border=0 src=\'../pixmaps/script--pencil.png\' alt=\'ticket\' border=\'0\'/>' . '</a>';
				$av_description = "";
			} else {
				$owner_take = 0;
				$background = '#FEE599;';
				$description = "<input type='text' name='input" . $group_id . "' title='" . $descr . "' " . $av_description . " style='text-decoration: none; border: 0px; background: #FEE599' size='20' value='" . $descr . "' />";
				$group_box = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
			}
		*/
		$delete_link = ($status == "open" && $owner_take) ? "<a title='" . gettext("Close") . "' href='alarm_group.php?close_group=$group_id'><img border=0 src='../pixmaps/cross-circle-frame.png'/>" . "</a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'/>";
        if ($status == 'open') {
            if ($owner_take) $close_link = "<a href='alarm_group.php?close_group=$group_id'><img src='../pixmaps/lock-unlock.png' alt='"._("Open, click to close group")."' title='"._("Open, click to close group")."' border=0></a>";
            else $close_link = "<img src='../pixmaps/lock-unlock.png' alt='"._("Open, take this group then click to close")."' title='"._("Open, take this group then click to close")."' border=0>";
        } else {
            if ($owner_take) $close_link = "<a href='alarm_group.php?open_group=$group_id'><img src='../pixmaps/lock.png' alt='"._("Closed, click to open group")."' title='"._("Closed, click to open group")."' border=0></a>";
            else $close_link = "<img src='../pixmaps/lock.png' alt='"._("Closed, take this group then click to open")."' title='"._("Closed, take this group then click to open")."' border=0>";
            $group_box = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
        }
		
		/*if ($show_day) { ?>
	<tr>
		<td colspan=7 class="nobborder" style="text-align:center;padding:5px"><b><?=$date?></b></td>
	</tr>
		<? }*/ ?>
	<tr>
		<td class="nobborder" id="plus<?=$group['group_id']?>"><a href="javascript:toggle_group('<?=$group['group_id']?>','<?=$group['name']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>');"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a></td>
		<th style='padding:5px;text-align: left; border-width: 0px; background: <?=$background?>'><?=$group['name']?>&nbsp;&nbsp;<span style='font-size:xx-small; text-color: #AAAAAA;'>(<?=$ocurrences?> <?=$ocurrence_text?>)</span></th>
	</tr>
	<tr>
		<td colspan="7" id="<?=$group['group_id']?>" class="nobborder" style="text-align:center;padding-left:55px;"></td>
	</tr>
<? } ?>
	<tr>
		<td colspan="7" class="nobborder" style="text-align:center">
			<table class="noborder" align="center">
			<?
				print "<tr><td class='nobborder' style='text-align:center'>\n";
				$first_link = build_url("change_page", "&inf=0" . "&sup=" . $ROWS);
				$last_link = build_url("change_page", "&inf=" . ($count - $ROWS) . "&sup=" . $count);
				$inf_link = build_url("change_page", "&inf=" . ($inf - $ROWS) . "&sup=" . ($sup - $ROWS));
				$sup_link = build_url("change_page", "&inf=" . ($inf + $ROWS) . "&sup=" . ($sup + $ROWS));
				if ($inf >= $ROWS) {
					echo "<a href=\"" . $first_link . "\" >&lt;"._("First")."&nbsp;</a>";
					echo "<a href=\"$inf_link\">&lt;-";
					printf(gettext("Prev %d") , $ROWS);
					echo "</a>";
				}
				if ($sup < $count) {
					echo "&nbsp;&nbsp;(";
					printf(gettext("%d-%d of %d") , $inf, $sup, $count);
					echo ")&nbsp;&nbsp;";
					echo "<a href=\"$sup_link\">";
					printf(gettext("Next %d") , $ROWS);
					echo " -&gt;</a>";
					echo "<a href=\"" . $last_link . "\" >&nbsp;"._("Last")."&gt;</a>";
				} else {
					echo "&nbsp;&nbsp;(";
					printf(gettext("%d-%d of %d") , $inf, $count, $count);
					echo ")&nbsp;&nbsp;";
				}
				print "</td></tr>";
				?>
			</table>
		</td>
	</tr>
</table>
<script type="text/javascript">
// DatePicker
$(document).ready(function(){
	GB_TYPE = 'w';
	$("a.greybox2").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,450,'90%');
		return false;
	});
	$("a.greybox").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,150,'40%');
		return false;
	});
	
	$('#date_from').datepicker();
	$('#date_to').datepicker();
});
</script>
</body>
</html>
