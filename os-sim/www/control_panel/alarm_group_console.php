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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';

Session::logcheck("MenuIncidents", "ControlPanelAlarms");
$unique_id = uniqid("alrm_");
$prev_unique_id = $_SESSION['alarms_unique_id'];
$_SESSION['alarms_unique_id'] = $unique_id;

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
	$url = $_SERVER["SCRIPT_NAME"] . "?action=" . $action . $extra . $options . "&bypassexpirationupdate=1&group_type=".GET('group_type');
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
$date_from = GET('date_from');
$date_to = GET('date_to');
$num_alarms_page = GET('num_alarms_page');
$disp = GET('disp'); // Telefonica disponibilidad hack
$group = GET('group'); // Alarm group for change descr
$new_descr = GET('descr');
$action = GET('action');
$show_options = GET('show_options');
$refresh_time = GET('refresh_time');
$autorefresh = GET('autorefresh');
$alarm = GET('alarm');
$param_unique_id = GET('unique_id');
$group_type = GET('group_type') ? GET('group_type') : "all";
ossim_valid($param_unique_id, OSS_ALPHA, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("unique id"));
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
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::take_group ($conn, GET('take'), $_SESSION["_user"]);
	else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('release') != "") {
	if (!ossim_valid(GET('release'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("release"))) exit;
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::release_group ($conn, GET('release'));
	else die(ossim_error("Can't do this action for security reasons."));
}
if ($new_descr != "" && $group != "") {
	if (!ossim_valid($new_descr, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("descr"))) exit;
	if (!ossim_valid($group, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("group"))) exit;
	AlarmGroups::change_descr ($conn, $new_descr, $group);
}
if (GET('close_group') != "") {
	if (!ossim_valid(GET('close_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("close_group"))) exit;
	$group_ids = split(',', GET('close_group'));
    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
	foreach($group_ids as $group_id) AlarmGroups::change_status ($conn, $group_id, "closed");
    } else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('open_group') != "") {
	if (!ossim_valid(GET('open_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("open_group"))) exit;
	if (check_uniqueid($prev_unique_id,$param_unique_id)) AlarmGroups::change_status ($conn, GET('open_group'), "open");
	else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('delete_group') != "") {
	if (!ossim_valid(GET('delete_group'), OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("delete_group"))) exit;
	$group_ids = split(',', GET('delete_group'));
    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
	foreach($group_ids as $group_id) AlarmGroups::delete_group ($conn, $group_id, $_SESSION["_user"]);
    } else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('action') == "open_alarm") {
	if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::open($conn, GET('alarm'));
	else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('action') == "close_alarm") {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::close($conn, GET('alarm'));
    else die(ossim_error("Can't do this action for security reasons."));
}
if (GET('action') == "delete_alarm") {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::delete($conn, GET('alarm'));
    else die(ossim_error("Can't do this action for security reasons."));
}
$db_groups = AlarmGroups::get_dbgroups($conn);
list($alarm_group, $count) = AlarmGroups::get_grouped_alarms($conn, $group_type, $show_options, $hide_closed, $date_from, $date_to, $src_ip, $dst_ip, "LIMIT $inf,$sup");
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

  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  <script type="text/javascript">
  var open = false;
  
  function toggle_group (group_id,name,ip_src,ip_dst,time,from) {
	document.getElementById(group_id+from).innerHTML = "<img src='../pixmaps/loading.gif'>";
	$.ajax({
		type: "GET",
		url: "alarm_group_response.php?from="+from+"&group_id="+group_id+"&unique_id=<?php echo $unique_id ?>&name="+group_id+"&ip_src="+ip_src+"&ip_dst="+ip_dst+"&timestamp="+time+"&hide_closed=<?=$hide_closed?>",
		data: "",
		success: function(msg){
			//alert (msg);
			document.getElementById(group_id+from).innerHTML = msg;
			plus = "plus"+group_id;
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>";
		}
	});
  }
  function untoggle_group (group_id,name,ip_src,ip_dst,time) {
	plus = "plus"+group_id;
	document.getElementById(plus).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"','');\"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
	document.getElementById(group_id).innerHTML = "";
  }
  function opencloseAll () {
	if (!open) {
	<? foreach ($alarm_group as $group) { ?>
	toggle_group('<?=$group['group_id']?>','<?php echo $group['name'] ?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','');
	<? } ?>
	open = true;
	document.getElementById('expandcollapse').src='../pixmaps/minus.png';
	} else {
	<? foreach ($alarm_group as $group) { ?>
	untoggle_group('<?=$group['group_id']?>','<?php echo $group['name'] ?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>');
	<? } ?>
	open = false;
	document.getElementById('expandcollapse').src='../pixmaps/plus.png';
	}
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
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"untoggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/minus-small.png' border='0' alt='plus'></img></a>";
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
	document.getElementById(plus).innerHTML = "<a href='' onclick=\"toggle_alarm('"+backlog_id+"','"+event_id+"');return false\"><img src='../pixmaps/plus-small.png' border='0' alt='plus'></img></a>";
  }
  
  function change_descr(objname)
	{
		var descr;
		descr = document.getElementsByName(objname); 
		descr = descr[0];	
		location.href= "alarm_group_console.php?group_type=<?php echo $group_type ?>&group=" + objname.replace("input","") + "&descr=" + descr.value;
	}

	function send_descr(obj ,e) 
	{
		var key;

		if (window.event)
		{
			key = window.event.keyCode;
		}
		else if (e)
		{
			key = e.which;
		}
		else
		{
			return;
		}
		if (key == 13) 
		{
			location.href="<?php print $_SERVER["SCRIPT_NAME"] ?>"+"?action=change_descr&group=" + obj.name + "&descr=" + obj.value;
			change_descr(obj.name);
		}
	}

	function open_group(group_id,name,ip_src,ip_dst,time) {
		// GROUPS
		$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'>");
		document.getElementById("plus"+group_id).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"','');\"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
		document.getElementById(group_id).innerHTML = "";
		$.ajax({
			type: "GET",
			url: "alarm_group_response.php?only_open=1&group1="+group_id,
			data: "",
			success: function(msg){
				document.getElementById('lock_'+group_id).innerHTML = "<a href='' onclick=\"close_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"');return false\"><img src='../pixmaps/lock-unlock.png' alt='<?php echo _("Open, click to close group") ?>' title='<?php echo _("Open, click to close group") ?>' border=0></a>";
			}
		});
	}
	
	function close_group(group_id,name,ip_src,ip_dst,time) {
		// GROUPS
		$('#lock_'+group_id).html("<img src='../pixmaps/loading.gif' width='16'>");
		document.getElementById("plus"+group_id).innerHTML = "<a href=\"javascript:toggle_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"','');\"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a>";
		document.getElementById(group_id).innerHTML = "";
		$.ajax({
			type: "GET",
			url: "alarm_group_response.php?only_close=1&group1="+group_id,
			data: "",
			success: function(msg){
				document.getElementById('lock_'+group_id).innerHTML = "<a href='' onclick=\"open_group('"+group_id+"','"+name+"','"+ip_src+"','"+ip_dst+"','"+time+"');return false\"><img src='../pixmaps/lock.png' alt='<?php echo _("Closed, click to open group") ?>' title='<?php echo _("Closed, click to open group") ?>' border=0></a>";
			}
		});
	}
	
	function close_groups() {
		// ALARMS
		var params = "";
		$(".alarm_check").each(function()
		{
			if ($(this).attr('checked') == true) {
		    	params += "&"+$(this).attr('name')+"=1";
			}
		});
		// GROUPS
		var selected_group = "";
		var group = document.getElementsByName("group");	
		var index = 0;

		for(var i = 0; i < group.length; i++)
		{
			if( group[i].checked )
			{
				selected_group += "&group"+(index+1)+"="+group[i].value;
				index++;
			}
		}

		if (selected_group.length == 0 && params == "")
		{
			alert("Please, select the groups or any alarm to close");
			return;
		}
		$('#loading_div').html("<img src='../pixmaps/loading.gif' width='16'>");
		if (params != "") {
			$.ajax({
				type: "POST",
				url: "alarms_check_delete.php",
				data: "background=1&only_close=1&unique_id=<?php echo $unique_id ?>"+params,
				success: function(msg){
					//$('#loading_div').html("");
					//$('#loading_div').html("");
					if (selected_group != "") {
						$.ajax({
							type: "GET",
							url: "alarm_group_response.php?only_close="+index+selected_group,
							data: "",
							success: function(msg){
								//alert (msg);
								location.href="<?php print build_url("", "") ?>";
							}
						});
					}
					location.href="<?php print build_url("", "") ?>";
				}
			});
		} else {
			$.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_close="+index+selected_group,
				data: "",
				success: function(msg){
					location.href="<?php print build_url("", "") ?>";
				}
			});
		}
	}
	
	function checkall () {
		$("input[type=checkbox]").each(function() {
			if (this.id.match(/^check_/) && this.disabled == false) {
				this.checked = (this.checked) ? false : true;
			}
		});
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

  function bg_delete() {
		// ALARMS
		var params = "";
		$(".alarm_check").each(function()
		{
			if ($(this).attr('checked') == true) {
		    	params += "&"+$(this).attr('name')+"=1";
			}
		});
		// GROUPS
		var selected_group = "";
		var group = document.getElementsByName("group");	
		var index = 0;

		for(var i = 0; i < group.length; i++)
		{
			if( group[i].checked )
			{
				selected_group += "&group"+(index+1)+"="+group[i].value;
				index++;
			}
		}
		
		if (selected_group == "" && params == "")
		{
			alert("Please, select the groups or any alarm to delete");
			return;
		}
		if (params != "") {
			$.ajax({
				type: "POST",
				url: "alarms_check_delete.php",
				data: "background=1&unique_id=<?php echo $unique_id ?>"+params,
				success: function(msg){
					//$('#loading_div').html("");
					if (selected_group != "") {
						$.ajax({
							type: "GET",
							url: "alarm_group_response.php?only_delete="+index+selected_group,
							data: "",
							success: function(msg){
								//alert (msg);
								document.location.href='<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>&group_type=<?php echo $group_type ?>';
							}
						});
					}
					document.location.href='<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>&group_type=<?php echo $group_type ?>';
				}
			});
		} else {
			$.ajax({
				type: "GET",
				url: "alarm_group_response.php?only_delete="+index+selected_group,
				data: "",
				success: function(msg){
					//alert (msg);
					document.location.href='<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>';
				}
			});
		}
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
    print '<a href="javascript:;" onclick="tooglebtn()"><img src="../pixmaps/sem/toggle.gif" border="0" id="timg" title="Toggle"> <small><font color="black">'._("Filters, Actions and Options").'</font></small></a>';
    print '</td></tr></table>';
    print '<table width="90%" align="center" id="searchtable" style="display:none"><tr><th colspan="2" width="60%">';
    print _("Filter") . '</th><th>' . _("Actions") . '</th><th>' . _("Options") . '</th></tr>';
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
    ?>
    <td rowspan="3" style="text-align: left;border-bottom:0px solid white" nowrap>
		<input type="button" onclick="close_groups()" value="<?php echo _("Close Selected") ?>" class="lbutton">
		<br><br><input type="button" value="<?=_("Delete selected")?>" onclick="if (confirm('<?=_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?")?>')) bg_delete();" class="lbutton">
	</td>
<?php
    //Options
    $selected1 = $selected2 = $selected3 = $selected4 = "";
    if ($show_options == 1) $selected1 = 'selected="true"';
    if ($show_options == 2) $selected2 = 'selected="true"';
    if ($show_options == 3) $selected3 = 'selected="true"';
    if ($show_options == 4) $selected4 = 'selected="true"';
    if ($hide_closed) {
        $hide_check = 'checked="true"';
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
    print '<td rowspan="3" style="text-align: left;border-bottom:0px solid white"><strong>'._("Show").':</strong>&nbsp;<select name="show_options">' . '<option value="1" ' . $selected1 . '>'._("All Groups").'</option>' . '<option value="2" ' . $selected2 . '>'._("My Groups").'</option>' . '<option value="3" ' . $selected3 . '>'._("Groups Without Owner").'</option>' . '<option value="4" ' . $selected4 . '>'._("My Groups & Without Owner").'</option>' . '</select> <br/>' . '<input type="checkbox" name="hide_closed" ' . $hide_check . ' />' . gettext("Hide closed alarms") . '<br/><input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" ' . $hide_autorefresh . ' />' . gettext("Autorefresh") . '&nbsp;<select name="refresh_time" ' . $disable_autorefresh . ' >' . '<option value="30" ' . $refresh_sel1 . ' >'._("30 sec").'</options>' . '<option value="60" ' . $refresh_sel2 . ' >'._("1 min").'</options>' . '<option value="180" ' . $refresh_sel3 . ' >'._("3 min").'</options>' . '<option value="600" ' . $refresh_sel4 . ' >'._("10 min").'</options>' . '</select>' . '&nbsp;<a href="' . build_url("", "") . '" >['._("Refresh").']</a>' . '</td> </tr>';
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
    print '<tr ><th colspan="4" style="padding:5px"><input type="submit" class="button" value="' . _("Go") . '"> <div id="loading_div" style="display:inline"></div></th></tr></table>';
    print '<br>';
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
					<td width="250" class="nobborder right">
						<table class="transparent">
							<tr>
								<td class="nobborder" nowrap><a href="alarm_console.php?hide_closed=1"><b><?=_("Ungrouped")?></b></a></td>
								<td class="nobborder"> | </td>
								<td class="nobborder" nowrap><?=_("Grouped by")?>: </td>
								<td class="nobborder">
									<select name="group_type" onchange="document.filters.submit()">
										<option value="all" <?php if ($group_type == "all") echo "selected" ?>>Alarm name, Src/Dst, Date</option>
										<option value="namedate" <?php if ($group_type == "namedate") echo "selected" ?>>Alarm name, Date</option>
										<option value="name" <?php if ($group_type == "name") echo "selected" ?>>Alarm name</option>
									</select>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</form>
	<tr>
		<td width='3%' class='nobborder' style='text-align:center'><input type='checkbox' name='allcheck' onclick='checkall()'></td>
		<td class='nobborder' style='text-align: center; padding:0px' width='3%'><a href='javascript: opencloseAll();'><img src='../pixmaps/plus.png' id='expandcollapse' border=0 alt='<?=_("Expand/Collapse ALL")?>' title='<?=_("Expand/Collapse ALL")?>'></a></td>
		<td style='text-align: left;padding-left:10px; background-color:#9DD131;font-weight:bold'><?=gettext("Group")?></td>
		<td width='10%' style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Owner")?></td>
		<td width='20%' style='text-align: center; background-color:#9DD131;font-weight:bold'><?=gettext("Description")?></td>
		<td style='text-align: center; background-color:#9DD131;font-weight:bold' width='7%'><?=gettext("Status")?></td>
		<td width='7%' style='text-decoration: none; background-color:#9DD131;font-weight:bold'><?=gettext("Action")?></td>
	</tr>
<?
    // Timezone correction
    $tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
    foreach($alarm_group as $group) { 
        $group['date'] = date("Y-m-d H:i:s",strtotime($group['date'])+(3600*$tz));
		$group_id = $group['group_id'];
		$_SESSION[$group_id] = $group['name'];
		$ocurrences = $group['group_count'];
		if ($group['date'] != $lastday) {
			$lastday = $group['date'];
			list($year, $month, $day) = split("-", $group['date']);
			$date = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
			$show_day = 1;
		} else $show_day = 0;
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
		$owner = ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) ? "<a href='alarm_group_console.php?group_type=$group_type&release=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Release")."</a>" : "<a href='alarm_group_console.php?group_type=$group_type&take=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Take")."</a>";
		
		if ($db_groups[$group_id]['owner'] != "")
			if ($db_groups[$group_id]['owner'] == $_SESSION["_user"]) {
				$owner_take = 1;
				$background = '#A7D7DF;';
				if ($status == 'open') {
					$owner = "<a href='alarm_group_console.php?group_type=$group_type&release=$group_id&inf=$inf&sup=$sup&unique_id=$unique_id'>"._("Release")."</a>";
				}
				$group_box = "<input type='checkbox' id='check_" . $group_id . "' name='group' value='" . $group_id . "' >";
				$incident_link = '<a class=greybox2 title=\''._("New ticket for Group ID") . $group_id . '\' href=\'../incidents/newincident.php?nohmenu=1&' . "ref=Alarm&" . "title=" . urlencode($alarm_name) . "&" . "priority=$s_risk&" . "src_ips=$src_ip&" . "event_start=$since&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . '\'>' . '<img border=0 src=\'../pixmaps/script--pencil.png\' alt=\''._("ticket").'\' border=\'0\'/>' . '</a>';
				$av_description = "";
			} else {
				$owner_take = 0;
				$background = '#FEE599;';
				$description = "<input type='text' name='input" . $group_id . "' title='" . $descr . "' " . $av_description . " style='text-decoration: none; border: 0px; background: #FEE599' size='20' value='" . $descr . "' />";
				$group_box = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
			}
		
		$delete_link = ($status == "open" && $owner_take) ? "<a title='" . gettext("Close") . "' href=''><img border=0 src='../pixmaps/cross-circle-frame.png'/>" . "</a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'/>";
        if ($status == 'open') {
            if ($owner_take) $close_link = "<a href='' onclick=\"close_group('".$group_id."','".$group['name']."','".$group['ip_src']."','".$group['ip_dst']."','".$group['date']."');return false\"><img src='../pixmaps/lock-unlock.png' alt='"._("Open, click to close group")."' title='"._("Open, click to close group")."' border=0></a>";
            else $close_link = "<img src='../pixmaps/lock-unlock.png' alt='"._("Open, take this group then click to close")."' title='"._("Open, take this group then click to close")."' border=0>";
        } else {
            if ($owner_take) $close_link = "<a href='' onclick=\"open_group('".$group_id."','".$group['name']."','".$group['ip_src']."','".$group['ip_dst']."','".$group['date']."');return false\"><img src='../pixmaps/lock.png' alt='"._("Closed, click to open group")."' title='"._("Closed, click to open group")."' border=0></a>";
            else $close_link = "<img src='../pixmaps/lock.png' alt='"._("Closed, take this group then click to open")."' title='"._("Closed, take this group then click to open")."' border=0>";
            $group_box = "<input type='checkbox' disabled = 'true' name='group' value='" . $group_id . "' >";
        }
		
		if ($show_day) { ?>
	<tr>
		<td colspan=7 class="nobborder" style="text-align:center;padding:5px;background-color:#B5C7DF"><b><?=$date?></b></td>
	</tr>
		<? } ?>
	<tr>
		<td class="nobborder" width="50"><input type='checkbox' id='check_<?=$group_id?>' name='group' value='<?=$group_id?>_<?=$group['ip_src']?>_<?=$group['ip_dst']?>_<?=$group['date']?>' <?if (!$owner_take) echo "disabled"?>></td>
		<td class="nobborder" id="plus<?=$group['group_id']?>"><a href="javascript:toggle_group('<?=$group['group_id']?>','<?php echo $group['name']?>','<?=$group['ip_src']?>','<?=$group['ip_dst']?>','<?=$group['date']?>','');"><strong><img src='../pixmaps/plus-small.png' border=0></strong></a></td>
		<th style='text-align: left; border-width: 0px; background: <?=$background?>'><?=$group['name']?>&nbsp;&nbsp;<span style='font-size:xx-small; text-color: #AAAAAA;'>(<?=$ocurrences?> <?=$ocurrence_text?>)</span></th>
		<th width='10%' style='text-align: center; border-width: 0px; background: <?=$background?>'><?=$owner?></th>
		<th width='20%' style='text-align: center; border-width: 0px; background: <?=$background?>;padding:3px'>
			<table class='noborder' style='background:$background'>
				<tr>
					<td class='nobborder'><input type='text' name='input<?=$group_id?>' title='<?=$descr?>' <?=$av_description?> style='text-decoration: none; border: 0px; background: #FFFFFF' size='20' value='<?=$descr?>' onkeypress='send_descr(this, event);' /></td>
					<td class='nobborder'><a href=javascript:change_descr('input<?=$group_id?>')><img valign='middle' border=0 src='../pixmaps/disk-black.png' /></a></td>
				</tr>
			</table>
		</th>
		<th style='text-align: center; border-width: 0px; background: <?=$background?>' id='lock_<?php echo $group_id ?>' width='7%'><?=$close_link?></th>
		<td width='7%' style='text-decoration: none;'><?=$delete_link?> <?=$incident_link?></td>
	</tr>
	<tr>
		<td colspan="7" id="<?=$group['group_id']?>" class="nobborder" style="text-align:center"></td>
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
});
</script>
</body>
</html>
