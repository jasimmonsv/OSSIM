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
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';

Session::logcheck("MenuIncidents", "ControlPanelAlarms");
ini_set("max_execution_time","300");
$unique_id = uniqid("alrm_");
$prev_unique_id = $_SESSION['alarms_unique_id'];
$_SESSION['alarms_unique_id'] = $unique_id;

require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Net.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Alarm.inc');
require_once ('classes/Plugin.inc');
require_once ('classes/Plugin_sid.inc');
require_once ('classes/Port.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Util.inc');
require_once ('classes/Tags.inc');
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
/* default number of events per page */
$ROWS = 50;
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
$delete = GET('delete');
$close = GET('close');
$open = GET('open');
$delete_day = GET('delete_day');
$order = GET('order');
$src_ip = GET('src_ip');
$dst_ip = GET('dst_ip');
$backup_inf = $inf = GET('inf');
$sup = GET('sup');
$hide_closed = GET('hide_closed');
$no_resolv = intval(GET('no_resolv'));
$refresh_time = GET('refresh_time');
$autorefresh = GET('autorefresh');
$query = (GET('query') != "") ? GET('query') : "";
$directive_id = GET('directive_id');
$sensor_query = GET('sensor_query');
$tag = GET('tag');
$num_events = GET('num_events');
$num_events_op = GET('num_events_op');
$params_string = "order=$order&src_ip=$src_ip&dst_ip=$dst_ip&inf=$inf&sup=$sup&hide_closed=$hide_closed&query=$query&directive_id=$directive_id&date_from=$date_from&date_to=$date_to&no_resolv=$no_resolv&sensor_query=$sensor_query&tag=$tag";

$sensors = $hosts = $ossim_servers = array();
list($sensors, $hosts) = Host::get_ips_and_hostname($conn,true);
/*$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array();
foreach ($_nets as $_net) $_nets_ips[] = $_net->get_ips();
$networks = implode(",",$_nets_ips);*/
$hosts_ips = array_keys($hosts);

$tags = Tags::get_list($conn);
$tags_html = Tags::get_list_html($conn);

$date_from = GET('date_from');
$date_to = GET('date_to');
$num_alarms_page = GET('num_alarms_page');
$param_unique_id = GET('unique_id');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, '.', 'illegal:' . _("order"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));
ossim_valid($close, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("close"));
ossim_valid($open, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("open"));
ossim_valid($delete_day, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("delete_day"));
ossim_valid($query, OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("query"));
ossim_valid($autorefresh, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("autorefresh"));
ossim_valid($refresh_time, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("refresh_time"));
ossim_valid($directive_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("directive_id"));
ossim_valid($src_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 'illegal:' . _("src_ip"));
ossim_valid($dst_ip, OSS_IP_ADDRCIDR, OSS_NULLABLE, 'illegal:' . _("dst_ip"));
ossim_valid($inf, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("inf"));
ossim_valid($sup, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($hide_closed, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("hide_closed"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
ossim_valid($param_unique_id, OSS_ALPHA, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("unique id"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("field number of alarms per page"));
ossim_valid($sensor_query, OSS_IP_ADDR, OSS_ALPHA, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("sensor_query"));
ossim_valid($tag, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("tag"));
ossim_valid($num_events, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("num_events"));
ossim_valid($num_events_op, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("num_events_op"));
if (ossim_error()) {
    die(ossim_error());
}
$_SESSION["_no_resolv"] = $no_resolv;

if ($autorefresh == 'on') {
    $autorefresh = 1;
} else {
    $autorefresh = 0;
}
if (empty($refresh_time) || ($refresh_time != 30000 && $refresh_time != 60000 && $refresh_time != 180000 && $refresh_time != 600000)) {
    $refresh_time = 60000;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?=_("Control Panel")?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
  <?php if ($autorefresh) { ?>
  <script type="text/javascript">
	setInterval("document.location.href='<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&no_resolv=<?=GET('no_resolv')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>&tag=<?php echo GET('tag') ?>&num_events=<?php echo GET('num_events') ?>&num_events_op=<?php echo GET('num_events_op') ?>&refresh_time=<?php echo GET('refresh_time') ?>&autorefresh=<?php echo GET('autorefresh') ?>&bypassexpirationupdate=1'",<?php echo $refresh_time ?>);
  </script>
  <?php } ?>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script src="../js/jquery.simpletip.js" type="text/javascript"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
<? include ("../host_report_menu.php") ?>
  <script language="javascript">
    function confirm_delete(url) {
        if (confirm('<?php echo _("Are you sure you want to delete this Alarm and all its events?") ?>')) {
            window.location=url;
        }
    }
  
  function show_alarm (id,tr_id) {
	tr = "tr"+tr_id;
	document.getElementById(tr).innerHTML = "<img src='../pixmaps/loading.gif' width='16'>";
	//alert (id);
	$.ajax({
		type: "GET",
		url: "events_ajax.php?backlog_id="+id,
		data: "",
		success: function(msg){
			//alert (msg);
			document.getElementById(tr).innerHTML = msg;
			plus = "plus"+tr_id;
			document.getElementById(plus).innerHTML = "<a href='' onclick=\"hide_alarm('"+id+"','"+tr_id+"');return false\"><img align='absmiddle' src='../pixmaps/minus-small.png' border='0'></a>"+tr_id;

			// GrayBox
			$(document).ready(function(){
				GB_TYPE = 'w';
				$("a.greybox").click(function(){
					var t = this.title || $(this).text() || this.href;
					GB_show(t,this.href,450,'90%');
					return false;
				});
			});
			load_contextmenu();
		}
		});
  }
  function hide_alarm (id,tr_id) {
	tr = "tr"+tr_id;
	document.getElementById(tr).innerHTML = "";
	plus = "plus"+tr_id;
	document.getElementById(plus).innerHTML = "<a href='' onclick=\"show_alarm('"+id+"','"+tr_id+"');return false\"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'></a>"+tr_id;
  }
  function checkall () {
	$("input[type=checkbox]").each(function() {
		if (this.id.match(/^check_\d+/)) {
			this.checked = (this.checked) ? false : true;
		}
	});
  }
  function checkall_date (d) {
	$("input[datecheck='"+d+"']").each(function() {
		this.checked = (this.checked) ? false : true;
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
		/*
		$y = strftime("%Y", time() - (24 * 60 * 60));
		$m = strftime("%m", time() - (24 * 60 * 60));
		$d = strftime("%d", time() - (24 * 60 * 60));
		*/
		$y = date("Y"); $m = date("m"); $d = date("d");
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
	var clicks = 0;
	$('#widgetCalendar').DatePicker({
		flat: true,
		format: 'Y-m-d',
		date: [new Date(datefrom), new Date(dateto)],
		calendars: 3,
		mode: 'range',
		starts: 1,
		onChange: function(formated) {
			if (formated[0]!="" && formated[1]!="" && clicks>0) {
				var f1 = formated[0].split(/-/);
				var f2 = formated[1].split(/-/);
				document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
				document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
				$('#date_str').css('text-decoration', 'underline');
				document.getElementById('queryform').submit();
			} clicks++;
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
		var params = "";
		$(".alarm_check").each(function()
		{
			if ($(this).attr('checked') == true) {
		    	params += "&"+$(this).attr('name')+"=1";
			}
		});
		$('#loading_div').html("<img src='../pixmaps/loading.gif'>");
		$.ajax({
			type: "POST",
			url: "alarms_check_delete.php",
			data: "background=1&unique_id=<?php echo $unique_id ?>"+params,
			success: function(msg){
				$('#loading_div').html("");
				document.location.href='<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&no_resolv=<?=GET('no_resolv')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>';
			}
		});
	}

	function set_hand_cursor() {
		document.body.style.cursor = 'pointer';
	  }
	  function set_pointer_cursor() {
		document.body.style.cursor = 'default';
	  }
	  	  
  </script>

</head>

<body>

<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php");

if (!empty($delete)) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
		if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::delete($conn, $delete);
		else die(ossim_error("Can't do this action for security reasons."));
	}
}
if (!empty($close)) {
	if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::close($conn, $close);
	else die(ossim_error("Can't do this action for security reasons."));
}
if (!empty($open)) {
    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::open($conn, $open);
	else die(ossim_error("Can't do this action for security reasons."));
}
if ($list = GET('delete_backlog')) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
	    if (check_uniqueid($prev_unique_id,$param_unique_id)) {
			if (!strcmp($list, "all")) {
				$backlog_id = $list;
				$id = null;
			} else {
				list($backlog_id, $id) = split("-", $list);
			}
			Alarm::delete_from_backlog($conn, $backlog_id, $id);
		}
		else die(ossim_error("Can't do this action for security reasons."));
	}
}
if (!empty($delete_day)) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
		if (check_uniqueid($prev_unique_id,$param_unique_id))
			Alarm::delete_day($conn, $delete_day);
		else
			die(ossim_error("Can't do this action for security reasons."));
	}
}
if (GET('purge')) {
	if (!Session::menu_perms("MenuIncidents", "ControlPanelAlarmsDelete"))
		die(ossim_error("You don't have required permissions to delete Alarms"));
	else {
	    if (check_uniqueid($prev_unique_id,$param_unique_id)) Alarm::purge($conn);
		else die(ossim_error("Can't do this action for security reasons."));
	}
}
if (empty($order)) $order = " a.timestamp DESC";
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
if ($num_alarms_page) {
    $ROWS = $num_alarms_page;
}
if (empty($inf)) $inf = 0;
if (!$sup) $sup = $ROWS;

//if ($sensor_query != "") $sensor_query_ip = (preg_match("/\d+\.\d+\.\d+\.\d+/",$sensor_query)) ? $sensor_query : Sensor::$sensor_query;
$sensors_str = "";
$hosts_str = "";
foreach ($sensors as $s_ip=>$s_name) {
	if ($s_name!=$s_ip) $sensors_str .= '{ txt:"'.$s_ip.' ['.$s_name.']", id: "'.$s_ip.'" },';
    else $sensors_str .= '{ txt:"'.$s_ip.'", id: "'.$s_ip.'" },';
}
foreach ($hosts as $h_ip=>$h_name) {
	if ($h_name!=$h_ip) $hosts_str .= '{ txt:"'.$h_ip.' ['.$h_name.']", id: "'.$h_ip.'" },';
    else $hosts_str .= '{ txt:"'.$h_ip.'", id: "'.$h_ip.'" },';
}

// Eficiencia mejorada (Granada, junio 2009)
list($alarm_list, $count) = Alarm::get_list3($conn, $src_ip, $dst_ip, $hide_closed, "ORDER BY $order", $inf, $sup, $date_from, $date_to, $query, $directive_id, $sensor_query, $tag, $num_events, $num_events_op);
if (!isset($_GET["hide_search"])) {
?>

<form method="GET" id="queryform" name="filters">
<input type="hidden" name="tag" value="<?php echo $tag ?>">
<input type="hidden" name="date_from" id="date_from"  value="<?php echo $date_from ?>">
<input type="hidden" name="date_to" id="date_to" value="<?php echo $date_to ?>">

<table width="90%" align="center" class="transparent"><tr><td class="nobborder left">
<a href="javascript:;" onclick="tooglebtn()"><img src="../pixmaps/sem/toggle.gif" border="0" id="timg" title="Toggle"> <small><font color="black"><?=_("Filters, Actions and Options")?></font></small></a>
</td></tr></table>
<table width="90%" align="center" id="searchtable" style="display:none">
<tr>
	<th><?php echo _("Filter") ?></th>
	<th><?=_("Actions")?></th>
	<th><?=_("Options")?></th>
</tr>
<tr>
	<td class="nobborder" style="text-align:center">
		<table class="noborder">
			<tr>
				<td width="20%" style="text-align: right; border-width: 0px">
			        <b><?=_("Sensor")?></b>:
			    </td>
			    <td style="text-align: left; border-width: 0px" nowrap>
					<input type="text" name="sensor_query" id="sensors" value="<?php echo $sensor_query ?>">
			    </td>
			</tr>
			<tr>
				<td width="20%" style="text-align: right; border-width: 0px">
			        <b><?=_("Alarm name")?></b>:
			    </td>
			    <td style="text-align: left; border-width: 0px" nowrap>
			        <input type="text" name="query" value="<?php echo $query ?>">
					&nbsp;<b><?=_("Directive ID")?></b>: <input type="text" name="directive_id" value="<?=$directive_id?>">
			    </td>
			</tr>
			<tr>
			    <td width="25%" style="text-align: right; border-width: 0px">
			        <b><?php echo _("IP Address") ?></b>:
			    </td>
			    <td style="text-align: left; border-width: 0px" nowrap>    
			        <?php echo _("source") ?>: <input type="text" size="15" name="src_ip" id="src_ip" value="<?php echo $src_ip ?>">&nbsp;&nbsp;
			        <?php echo _("destination") ?>: <input type="text" size="15" name="dst_ip" id="dst_ip" value="<?php echo $dst_ip ?>">
			    </td>
			</tr>
			<tr>
			    <td width="25%" style="text-align: right; border-width: 0px" nowrap>
			        <b><?php echo _("Num. alarms per page") ?></b>:
			    </td>
			    <td style="text-align: left; border-width: 0px">
			        <input type="text" size=3 name="num_alarms_page" value="<?php echo $ROWS ?>">
			        &nbsp;<b><?php echo _("Number of events in alarm") ?></b>:
			        &nbsp;<select name="num_events_op">
			        	<option value="less" <?php if ($num_events_op == "less") echo "selected"?>>&lt;=</option>
			        	<option value="more" <?php if ($num_events_op == "more") echo "selected"?>>&gt;=</option>
			        </select>
			        &nbsp;<input type="text" name="num_events" size=3 value="<?php echo $num_events ?>"></input>
			    </td>
			</tr>
			<tr>
			    <td width="20%" id="date_str" style="text-align: right; border-width: 0px<? if ($date_from != "" && $date_to != "") echo ";text-decoration:underline"?>">
			        <b><?php echo _('Date') ?></b>:
				</td>
				<td class="nobborder" style="font-size:10px">
					<div id="widget" style="display:inline">
						<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0"></a>
						<div id="widgetCalendar"></div>
					</div><?php if ($date_from != "" && $date_to != "") { ?><a href="<?=$_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&no_resolv=<?=GET('no_resolv')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=&date_to=&sensor_query=<?=GET('sensor_query')?>&tag=<?php echo GET('tag') ?>&num_events=<?php echo GET('num_events') ?>&num_events_op=<?php echo GET('num_events_op') ?>&refresh_time=<?php echo GET('refresh_time') ?>&autorefresh=<?php echo GET('autorefresh') ?>">[<?php echo $date_from ?> - <?php echo $date_to ?>]</a><?php } ?>
			    </td>
			</tr>
		</table>
	</td>
	<td class="nobborder" style="text-align:center">
		<table class="noborder">
			<tr><td class="nobborder">
				<a href="<?php
    echo $_SERVER["SCRIPT_NAME"] ?>?delete_backlog=all&unique_id=<?=$unique_id?>" onclick="if(!confirm('<?php echo _("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?") ?>')) return false;"><?php
    echo gettext("Delete ALL alarms"); ?></a> <br><br>
				<input type="button" value="<?=_("Delete selected")?>" onclick="if (confirm('<?=_("Alarms should never be deleted unless they represent a false positive. Do you want to Continue?")?>')) bg_delete();" class="lbutton">
				<br><br><input type="button" value="<?=_("Close selected")?>" onclick="document.fchecks.only_close.value='1';document.fchecks.submit();" class="lbutton">
				<br><br><a href="" onclick="$('#divadvanced').toggle();return false;"><img src="../pixmaps/plus-small.png" border="0" align="absmiddle"> <?=_("Advanced")?></a>
				<div id="divadvanced" style="display:none"><a href="<?php
    echo $_SERVER["SCRIPT_NAME"] ?>?purge=1&unique_id=<?=$unique_id?>"><?php
    echo gettext("Remove events without an associated alarm"); ?></a></div>
    			
			</td></tr>
			<tr>
				<td class="nobborder">
					<table class="transparent">
							<?php if (count($tags) < 1) { ?>
							<tr>
								<td class="nobborder"><?php echo _("No tags found.") ?> <a href="tags_edit.php"><?php echo _("Click here to create") ?></a></td>
							</tr>
							<?php } else { ?>
							<tr>
								<td class="nobborder"><a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#tags_filter').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _("Filter by label") ?></a></td>
								<td class="nobborder" nowrap>
								<?php if ($tag != "") { ?>
								<table class="transparent"><tr><td class="nobborder"><?php echo $tags_html[$tag] ?></td><td class="nobborder"><a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?order=$order&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip" . "&hide_closed=$not_hide_closed&num_alarms_page=$num_alarms_page&query=$query&directive_id=$directive_id&sensor_query=$sensor_query&no_resolv=$no_resolv&num_events=$num_events&num_events_op=$num_events_op" ?>&tag=">Remove filter</a></td></tr></table>
								<?php } ?>
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<div style="position:relative">
									<div id="tags_filter" style="display:none;border:0px;position:absolute">
									<table cellpadding='0' cellspacing='0' align="center" style="border-radius:0">
									<tr>
										<th style="padding-right:3px;border-radius:0px;border-top:0px;border-right:0px;border-left:0px">
											<div style='float:left; width:60%; text-align: right;padding:3px'><?php echo _("Labels")?></div>
											<div style='float:right; width:18%; padding: 3px; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#tags_filter').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
										</th>
									</tr>
									<? foreach ($tags as $tg) { ?>
									<tr>
										<td class="nobborder">
											<table class="transparent" cellpadding="4"><tr><td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.location='<?php echo $_SERVER["SCRIPT_NAME"] . "?order=$order&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip" . "&hide_closed=$not_hide_closed&num_alarms_page=$num_alarms_page&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op" ?>&tag=<?php echo $tg->get_id() ?>'" style="border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:<?php echo '#'.$tg->get_bgcolor()?>;color:<?php echo '#'.$tg->get_fgcolor()?>;font-weight:<?php echo ($tg->get_bold()) ? "bold" : "normal" ?>;font-style:<?php echo ($tg->get_italic()) ? "italic" : "none" ?>"><?php echo $tg->get_name()?></td></tr></table>
										</td>
										<td class="nobborder">
										<?php if ($tag == $tg->get_id()) { ?>
										<a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?order=$order&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip" . "&hide_closed=$not_hide_closed&num_alarms_page=$num_alarms_page&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op" ?>&tag="><img src="../pixmaps/cross-small.png" border="0" alt="<?php echo _("Remove filter") ?>" title="<?php echo _("Remove filter") ?>"></img></a>
										<?php } ?>
										</td>
									</tr>
									<?php } ?>
									</table>
									</div>
									</div>
								</td>
								<td class="nobborder"></td>
							</tr>
							<?php } ?>
						</table>
				</td>
			</tr>
		</table>
	</td>
	<td class="nobborder" style="text-align:center">
		<table class="noborder">
			<tr>
				<td style="text-align: left; border-width: 0px">
				<?php
				    $hide_closed == 1 ? 1 : 0;
				    $not_hide_closed = !$hide_closed;
				    $not_no_resolv = !$no_resolv;
				?>
			    <input style="border:none" name="no_resolv" type="checkbox" value="1" 
			        onClick="document.location='<?php
    echo $_SERVER["SCRIPT_NAME"] . "?order=$order&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip" . "&hide_closed=$hide_closed&num_alarms_page=$num_alarms_page&query=$query&directive_id=$directive_id&sensor_query=$sensor_query&num_events=$num_events&no_resolv=$not_no_resolv&num_events_op=$num_events_op" ?>'"
			        <?php if ($no_resolv) echo " checked " ?> 
			    /> <?php echo gettext("Do not resolv ip names"); ?>
				</td>
			</tr>		
			<tr>
				<td style="text-align: left; border-width: 0px">
			    <input style="border:none" name="hide_closed" type="checkbox" value="1" 
			        onClick="document.location='<?php
    echo $_SERVER["SCRIPT_NAME"] . "?order=$order&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip" . "&hide_closed=$not_hide_closed&num_alarms_page=$num_alarms_page&query=$query&directive_id=$directive_id&sensor_query=$sensor_query&num_events=$num_events&no_resolv=$no_resolv&num_events_op=$num_events_op" ?>'"
			        <?php if ($hide_closed) echo " checked " ?> 
			    /> <?php echo gettext("Hide closed alarms"); ?>
				</td>
			</tr>
			<tr>
				<td style="text-align: left; border-width: 0px">
				<?php
				$refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";
			    if ($refresh_time == 30000) $refresh_sel1 = 'selected="true"';
			    if ($refresh_time == 60000) $refresh_sel2 = 'selected="true"';
			    if ($refresh_time == 180000) $refresh_sel3 = 'selected="true"';
			    if ($refresh_time == 600000) $refresh_sel4 = 'selected="true"';
			    if ($autorefresh) {
			        $hide_autorefresh = 'checked="true"';
			        $disable_autorefresh = '';
			    } else {
			        $hide_autorefresh = '';
			        $disable_autorefresh = 'disabled="true"';
			    }
    			?>
				<input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" <?php echo $hide_autorefresh ?> /><?php echo gettext("Autorefresh") ?>&nbsp;<select name="refresh_time" <?php echo $disable_autorefresh ?> ><option value="30000" <?php echo $refresh_sel1 ?> ><?php echo _("30 sec") ?></options><option value="60000" <?php echo $refresh_sel2 ?>><?php echo _("1 min") ?></options><option value="180000" <?php echo $refresh_sel3 ?>><?php echo _("3 min") ?></options><option value="600000" <?php echo $refresh_sel4 ?>><?php echo _("10 min") ?></options></select>&nbsp;<a href="<? echo $_SERVER['SCRIPT_NAME']?>?query=<?=GET('query')?>&directive_id=<?=GET('directive_id')?>&inf=<?=GET('inf')?>&sup=<?=GET('sup')?>&hide_closed=<?=GET('hide_closed')?>&order=<?=GET('order')?>&src_ip=<?=GET('src_ip')?>&dst_ip=<?=GET('dst_ip')?>&num_alarms_page=<?=GET('num_alarms_page')?>&num_alarms_page=<?=GET('num_alarms_page')?>&date_from=<?=urlencode(GET('date_from'))?>&date_to=<?=urlencode(GET('date_to'))?>&sensor_query=<?=GET('sensor_query')?>&tag=<?php echo GET('tag') ?>&no_resolv=<?=GET('no_resolv')?>&num_events=<?php echo GET('num_events') ?>&num_events_op=<?php echo GET('num_events_op') ?>" >[<?php echo _("Refresh") ?>]</a>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr><th colspan="3" style="padding:5px"><input type="submit" class="button" value="<?php echo _("Go") ?>"></th></td>
</table>
</form>
<?php
} ?>
<br>
    <table width="100%" border=0 cellspacing=1 cellpadding=0>
      <tr><td colspan="11" id="loading_div" style="border:0px"></td></tr>
	  <tr>
        <td colspan="11" style="border-bottom:0px solid white" nowrap>
			<table class="transparent" width="100%">
				<tr>
					<td width="200" class="nobborder">
						<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#tags').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _("Apply label to selected alarms") ?></a> 
				   <div style="position:relative"> 
						<div id="tags" style="position:absolute;right:0;top:0;display:none">
						<table cellpadding='0' cellspacing='0' align="center" style="border-radius:0">
							<tr>
								<th style="padding-right:3px;border-radius:0px;border-top:0px;border-right:0px;border-left:0px">
									<div style='float:left; width:60%; text-align: right;padding:3px'><?php echo _("Labels")?></div>
									<div style='float:right; width:18%; padding: 3px; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#tags').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
								</th>
							</tr>
							<?php if (count($tags) < 1) { ?>
							<tr><td><?php echo _("No tags found.") ?></td></tr>
							<?php } else { ?>
							<? foreach ($tags as $tg) { ?>
							<tr>
								<td class="nobborder"><table class="transparent" cellpadding="4"><tr><td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.fchecks.move_tag.value='<?php echo $tg->get_id() ?>';document.fchecks.submit();" style="border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:<?php echo '#'.$tg->get_bgcolor()?>;color:<?php echo '#'.$tg->get_fgcolor()?>;font-weight:<?php echo ($tg->get_bold()) ? "bold" : "normal" ?>;font-style:<?php echo ($tg->get_italic()) ? "italic" : "none" ?>"><?php echo $tg->get_name()?></td></tr></table></td>
							</tr>
							<?php } ?>
							<tr>
								<td class="nobborder"><table class="transparent" cellpadding="2"><tr><td class="nobborder"><a href="" onclick="document.fchecks.move_tag.value='0';document.fchecks.submit();return false"><?php echo _("Remove selected") ?></a></td></tr></table></td>
							</tr>
							<?php } ?>
						</table>
						</div>
					</div>
					</td>
					<td class="nobborder center">
<?php
/*
* prev and next buttons
*/
$inf_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup - $ROWS) . "&inf=" . ($inf - $ROWS) . "&hide_closed=$hide_closed&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&no_resolv=$no_resolv&sensor_query=$sensor_query&tag=$tag&num_events=$num_events&num_events_op=$num_events_op";
$sup_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup + $ROWS) . "&inf=" . ($inf + $ROWS) . "&hide_closed=$hide_closed&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&no_resolv=$no_resolv&sensor_query=$sensor_query&tag=$tag&num_events=$num_events&num_events_op=$num_events_op";
$first_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($ROWS) . "&inf=0&hide_closed=$hide_closed&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&sensor_query=$sensor_query&no_resolv=$no_resolv&tag=$tag&num_events=$num_events&num_events_op=$num_events_op";
$last_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($count) . "&inf=" . ((floor($count/$ROWS))*$ROWS) . "&hide_closed=$hide_closed&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&no_resolv=$no_resolv&norefresh=$norefresh&sensor_query=$sensor_query&tag=$tag&num_events=$num_events&num_events_op=$num_events_op";
if ($src_ip) {
	$inf_link.= "&src_ip=$src_ip";
    $sup_link.= "&src_ip=$src_ip";
	$first_link.= "&src_ip=$src_ip";
	$last_link.= "&src_ip=$src_ip";
}
if ($dst_ip) {
    $inf_link.= "&dst_ip=$dst_ip";
    $sup_link.= "&dst_ip=$dst_ip";
	$first_link.= "&dst_ip=$dst_ip";
	$last_link.= "&dst_ip=$dst_ip";
}
if ($query != "") {
	$inf_link.= "&query=$query";
    $sup_link.= "&query=$query";
	$first_link.= "&query=$query";
	$last_link.= "&query=$query";
}
if ($directive_id != "") {
	$inf_link.= "&directive_id=$directive_id";
    $sup_link.= "&directive_id=$directive_id";
	$first_link.= "&directive_id=$directive_id";
	$last_link.= "&directive_id=$directive_id";
}
// XXX missing performance improve here
//$tot_alarms = Alarm::get_list($conn, $src_ip, $dst_ip, $hide_closed, "", null, null, $date_from, $date_to);
//$count = count($tot_alarms);
if ($inf >= $ROWS) {
    echo "<a href=\"$first_link\">&lt;&lt;-";
    printf(gettext("First") , $ROWS);
    echo "</a>";
	echo "&nbsp;<a href=\"$inf_link\">&lt;-";
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
	echo "&nbsp;<a href=\"$last_link\">";
    printf(gettext("Last") , $ROWS);
    echo " -&gt;&gt;</a>";
} else {
    echo "&nbsp;&nbsp;(";
    printf(gettext("%d-%d of %d") , $inf, $count, $count);
    echo ")&nbsp;&nbsp;";
}
?>
					</td>
					<td width="150" class="nobborder right">
						<table class="transparent">
							<tr>
								<td class="nobborder" nowrap><?=_("Ungrouped")?></td>
								<td class="nobborder"> | </td>
								<td class="nobborder"><a href="alarm_group_console.php"><b><?=_("Grouped")?></b></a></td>
							</tr>
						</table>
					</td>
				  </tr>
				</table>
			</td>
		</tr>
    <?php
    // Timezone correction
    $tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
    ?>
      <tr>
        <td class="nobborder" width="20" align="center"><input type="checkbox" name="allcheck" onclick="checkall()"></td>
		<td style="background-color:#9DD131;font-weight:bold">#</td>
        <td width="25%" style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("plugin_sid", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Alarm"); ?> </a></td>
        <td style="background-color:#9DD131;padding-left:3px;padding-right:3px;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("risk", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Risk"); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("sensor", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Sensor"); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"> <?php
echo gettext("Since")."<br>".Util::timezone($tz); ?> </td>
        <td style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("timestamp", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op" ?>"> 
            <?php
echo gettext("Last")."<br>".Util::timezone($tz); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("src_ip", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Source"); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("dst_ip", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Destination"); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("status", $order) . "&inf=$inf&sup=$sup&src_ip=$src_ip&dst_ip=$dst_ip&num_alarms_page=$num_alarms_page&date_from=$date_from&date_to=$date_to&hide_closed=$hide_closed&norefresh=$norefresh&query=$query&directive_id=$directive_id&no_resolv=$no_resolv&sensor_query=$sensor_query&num_events=$num_events&num_events_op=$num_events_op"
?>"> <?php
echo gettext("Status"); ?> </a></td>
        <td style="background-color:#9DD131;font-weight:bold"> <?php
echo gettext("Action"); ?> </td>
      </tr>
	  <form name="fchecks" action="alarms_check_delete.php" method="post">
	  <input type="hidden" name="hide_closed" value="<?=$hide_closed?>">
	  <input type="hidden" name="no_resolv" value="<?=$no_resolv?>">
	  <input type="hidden" name="only_close" value="">
	  <input type="hidden" name="move_tag" value="">
	  <input type="hidden" name="tag" value="<?php echo $tag ?>">
	  <input type="hidden" name="unique_id" value="<?=$unique_id?>">
	  <input type="hidden" name="date_from" value="<?php echo $date_from ?>">
	  <input type="hidden" name="date_to" value="<?php echo $date_to ?>">
	  <input type="hidden" name="order" value="<?=$order?>">
	  <input type="hidden" name="query" value="<?=$query?>">
	  <input type="hidden" name="norefresh" value="<?=$norefresh?>">
	  <input type="hidden" name="directive_id" value="<?=$directive_id?>">
	  <input type="hidden" name="src_ip" value="<?=$src_ip?>">
	  <input type="hidden" name="dst_ip" value="<?=$dst_ip?>">
	  <input type="hidden" name="inf" value="<?=$inf?>">
	  <input type="hidden" name="sup" value="<?=$sup?>">
	  <input type="hidden" name="num_alarms_page" value="<?=$num_alarms_page?>">
	  <input type="hidden" name="sensor_query" value="<?=$sensor_query?>">
	  <input type="hidden" name="num_events" value="<?=$num_events?>">
	  <input type="hidden" name="num_events_op" value="<?=$num_events_op?>">
	  

<?php
$time_start = time();
if ($count > 0) {
    $datemark = "";
    foreach($alarm_list as $alarm) {
        /* hide closed alarmas */
        if (($alarm->get_status() == "closed") and ($hide_closed == 1)) continue;
        $id = $alarm->get_plugin_id();
        $sid = $alarm->get_plugin_sid();
        $backlog_id = $alarm->get_backlog_id();
        $id_tag = $alarm->get_id_tag();
        /* get plugin_id and plugin_sid names */
        /*
        * never used ?
        *
        $plugin_id_list = Plugin::get_list($conn, "WHERE id = $id");
        $id_name = $plugin_id_list[0]->get_name();
        */
        /*
        $sid_name = "";
        if ($plugin_sid_list = Plugin_sid::get_list
        ($conn, "WHERE plugin_id = $id AND sid = $sid")) {
        $sid_name = $plugin_sid_list[0]->get_name();
        } else {
        $sid_name = "Unknown (id=$id sid=$sid)";
        }
        */
        $sid_name = $alarm->get_sid_name(); // Plugin_sid table just joined (Granada 27 mayo 2009)
        $date = Util::timestamp2date($alarm->get_timestamp());
        $date = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$date)+(3600*$tz));
        if ($backlog_id && $id==1505) {
            $since = Util::timestamp2date($alarm->get_since());
            $since = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$since)+(3600*$tz));            
        } else {
            $since = $date;
        }
        /* show alarms by days */
        $date_slices = split(" ", $date);
        list($year, $month, $day) = split("-", $date_slices[0]);
        $date_unformated = $year.$month.$day;
        $date_formatted = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
        if ($datemark != $date_slices[0]) {
            $link_delete = "
                    <a href=\"" . $_SERVER["SCRIPT_NAME"] . "?delete_day=" . $alarm->get_timestamp() . "&inf=" . ($sup - $ROWS) . "&sup=$sup&hide_closed=$hide_closed&unique_id=$unique_id\" style='font-weight:bold'> " . gettext("Delete") . " </a>
                ";
            echo "
                <tr>
                  
                  <td style='border:0px;background-color:#d6dfeb'><input type=\"checkbox\" onclick=\"checkall_date('".$date_unformated."')\"></td>
                  <td colspan=\"10\" style='padding:5px;border-bottom:0px solid white;background-color:#B5C7DF'>
                    <!--<hr border=\"0\"/>-->
                    <b>$date_formatted</b> [$link_delete]<br/>
                    <!--<hr border=\"0\"/>-->
                  </td>
                  
                </tr>
                ";
        }
        $datemark = $date_slices[0];
    	$event_count_label = "";
		if ($backlog_id) {
            $aid = $alarm->get_event_id();
            #$summary = Alarm::get_total_events($conn, $backlog_id);
            #$event_count_label = $summary["total_count"] . " "._("events");
            $event_count_label = Alarm::get_total_events($conn, $backlog_id)." "._("events");
        }
?>
      <tr>
        <td class="nobborder"><input style="border:none" type="checkbox" name="check_<?php echo $backlog_id ?>_<?php echo $alarm->get_event_id() ?>" id="check_<?php echo $backlog_id ?>" class="alarm_check" datecheck="<?php echo $date_unformated ?>" value="1"></td>
        <td class="nobborder" nowrap id="plus<?php echo $inf + 1 ?>">
           <? if ($backlog_id && $id==1505 && $event_count_label > 0) { ?>
           <a href="" onclick="show_alarm('<?php echo $backlog_id ?>','<?php echo $inf + 1 ?>');return false;"><img align='absmiddle' src='../pixmaps/plus-small.png' border='0'></a><?php echo ++$inf ?>
           <? } else { ?>
           <img align='absmiddle' src='../pixmaps/plus-small-gray.png' border='0'><font style="color:gray"><?php echo ++$inf ?></font>
           <? } ?>
        </td>
        <td class="nobborder" style="padding-left:20px">
        <?php
        $alarm_name = ereg_replace("directive_event: ", "", $sid_name);
        $alarm_name = Util::translate_alarm($conn, $alarm_name, $alarm);
        $alarm_name_orig = $alarm_name;
        if ($backlog_id && $id==1505) {
            $events_link = "events.php?backlog_id=$backlog_id";
            $alarm_name = "<a href=\"\"  onclick=\"show_alarm('" . $backlog_id . "','" . ($inf) . "');return false;\">$alarm_name</a>";
        } else {
            $events_link = $_SERVER["SCRIPT_NAME"];
            /*$alarm_link = Util::get_acid_pair_link($date, $alarm->get_src_ip() , $alarm->get_dst_ip());*/
            $alarm_link = Util::get_acid_single_event_link ($alarm->get_snort_sid(), $alarm->get_snort_cid());
            if ($show_all>=1)
            	$alarm_name = "<a href=\"" . $alarm_link . "\">$alarm_name</a>";
            else
            	$alarm_name = "<a href=\"" . $alarm_link . "&minimal_view=1&noback=1\" class=\"greybox2\">$alarm_name</a>";
        }
        ?>
        <table class="transparent"><tr>
        <?php if ($tags_html[$id_tag] != "") { ?><td class="nobborder"><?php echo $tags_html[$id_tag]; ?></td><?php } ?>
        <td class="nobborder"><b><?php echo $alarm_name; ?></b>
		<?php
        echo ($event_count_label > 0) ? "<br><font color=\"#AAAAAA\" style=\"font-size: 8px;\">(" . $event_count_label . ")</font>" : "";
?>
        </td>
        </tr></table>
        </td>
        
        <!-- risk -->
<?php
        $src_ip = $alarm->get_src_ip();
        $dst_ip = $alarm->get_dst_ip();
        $src_port = $alarm->get_src_port();
        $dst_port = $alarm->get_dst_port();
        //$src_port = Port::port2service($conn, $alarm->get_src_port());
        //$dst_port = Port::port2service($conn, $alarm->get_dst_port());
		$src_port = Port::port2service($conn, $src_port);
        $dst_port = Port::port2service($conn, $dst_port);
        $sensors = $alarm->get_sensors();
        $risk = $alarm->get_risk();
        if ($risk > 7) {
            echo "
            <td class='nobborder' style='text-align:center;background-color:red'>
              <b>
                <a href=\"$events_link\">
                  <font color=\"white\">$risk</font>
                </a>
              </b>
            </td>
            ";
        } elseif ($risk > 4) {
            echo "
            <td class='nobborder' style='text-align:center;background-color:orange'>
              <b>
                <a href=\"$events_link\">
                  <font color=\"black\">$risk</font>
                </a>
              </b>
            </td>
            ";
        } elseif ($risk > 2) {
            echo "
            <td class='nobborder' style='text-align:center;background-color:green'>
              <b>
                <a href=\"$events_link\">
                  <font color=\"white\">$risk</font>
                </a>
              </b>
            </td>
            ";
        } else {
            echo "
            <td class='nobborder' style='text-align:center'><a href=\"$events_link\">$risk</a></td>
            ";
        }
?>
        <!-- end risk -->


        <!-- sensor -->
        <td class="nobborder" style="text-align:center">
<?php
        foreach($sensors as $sensor) {
?>
          <a href="../sensor/sensor_plugins.php?hmenu=Sensors&smenu=Sensors&sensor=<?php
            echo $sensor ?>"
            ><?php
            echo ($no_resolv) ? $sensor : Host::ip2hostname($conn, $sensor) ?></a>  
<?php
        }
        if (!count($sensors)) {
            echo "&nbsp;";
        }
?>
        </td>
        <!-- end sensor -->


        <td style="padding-left:3px;padding-right:3px" class="center nobborder">
        <?php
        $acid_link = Util::get_acid_events_link($since, $date, "time_a");
        echo "
            <a href=\"$acid_link\">
              <font color=\"black\">$since</font>
            </a>
            ";
?>
        </td>
        <td style="padding-left:3px;padding-right:3px" class="center nobborder">
        <?php
        $acid_link = Util::get_acid_events_link($since, $date, "time_d");
        echo "
            <a href=\"$acid_link\">
              <font color=\"black\">$date</font></a>
            ";
?>
        </td>
        
<?php
        $src_link = "../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$src_ip&hmenu=Forensics&smenu=Forensics";
        $dst_link = "../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$dst_ip&hmenu=Forensics&smenu=Forensics";
        $src_name = ($no_resolv) ? $src_ip : Host::ip2hostname($conn, $src_ip);
        $dst_name = ($no_resolv) ? $dst_ip : Host::ip2hostname($conn, $dst_ip);
        $src_img = Host_os::get_os_pixmap($conn, $src_ip);
        $dst_img = Host_os::get_os_pixmap($conn, $dst_ip);
        $src_country = strtolower(geoip_country_code_by_addr($gi, $src_ip));
        $src_country_name = geoip_country_name_by_addr($gi, $src_ip);
        $src_country_img = "<img src=\"/ossim/pixmaps/flags/" . $src_country . ".png\" title=\"" . $src_country_name . "\">";
        $dst_country = strtolower(geoip_country_code_by_addr($gi, $dst_ip));
        $dst_country_name = geoip_country_name_by_addr($gi, $dst_ip);
        $dst_country_img = "<img src=\"/ossim/pixmaps/flags/" . $dst_country . ".png\" title=\"" . $dst_country_name . "\">";
?>
        <!-- src & dst hosts -->
		<td nowrap style="text-align:center;padding-left:3px;padding-right:3px" class="nobborder">
        <div id="<?php echo $src_ip; ?>;<?php echo $src_name; ?>" class="HostReportMenu">
		<?php
        $homelan = (Net::is_ip_in_cache_cidr($conn, $src_ip) || in_array($src_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
		if ($src_country) {
            echo "<a href=\"$src_link\">$src_name</a>:$src_port $src_img $src_country_img $homelan";
        } else {
            echo "<a href=\"$src_link\">$src_name</a>:$src_port $src_img $homelan";
        }
?></div></td>
		<td nowrap style="text-align:center;padding-left:3px;padding-right:3px" class="nobborder">
		<div id="<?php echo $dst_ip; ?>;<?php echo $dst_name; ?>" class="HostReportMenu">
		<?php
        $homelan = (Net::is_ip_in_cache_cidr($conn, $dst_ip) || in_array($dst_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
		if ($dst_country) {
            echo "<a href=\"$dst_link\">$dst_name</a>:$dst_port $dst_img $dst_country_img $homelan";
        } else {
            echo "<a href=\"$dst_link\">$dst_name</a>:$dst_port $dst_img $homelan";
        }
?></div></td>
        <!-- end src & dst hosts -->
        
        <td nowrap bgcolor="<?php echo ($alarm->get_status() == "open") ? "#ECE1DC" : "#DEEBDB" ?>" style="text-align:center;color:#4C7F41;border:1px solid <?php echo ($alarm->get_status() == "open") ? "#E6D8D2" : "#D6E6D2" ?>">
<?php
        $event_id = $alarm->get_event_id();
        if (($status = $alarm->get_status()) == "open") {
            echo "<a title='" . gettext("Click here to close alarm") . " #$event_id' " . "href=\"" . $_SERVER['SCRIPT_NAME'] . "?close=$event_id" . "&sup=" . "$sup" . "&inf=" . ($sup - $ROWS) . "&hide_closed=$hide_closed&query=".urlencode($query)."&unique_id=$unique_id\"" . " style='color:#923E3A'><b>" . gettext($status) . "</b></a>";
        } else {
            //echo gettext($status);
            echo "<a title='" . gettext("Click here to open alarm") . " #$event_id' " . "href=\"" . $_SERVER['SCRIPT_NAME'] . "?open=$event_id" . "&sup=" . "$sup" . "&inf=" . ($sup - $ROWS) . "&hide_closed=$hide_closed&query=".urlencode($query)."&unique_id=$unique_id\"" . " style='color:#4C7F41'><b>" . gettext($status) . "</b></a>";
        }
?>
        </td>

        <td nowrap class="nobborder" style='text-align:center'>
<?php
        if (($status = $alarm->get_status()) == "open") {
            echo "<a title='" . gettext("Click here to close alarm") . " #$event_id' " . "href=\"" . $_SERVER['SCRIPT_NAME'] . "?close=$event_id" . "&sup=" . "$sup" . "&inf=" . ($sup - $ROWS) . "&hide_closed=$hide_closed&query=".urlencode($query)."&unique_id=$unique_id\"" . " onclick=\"if (!confirm('"._("Are you sure to close this alarm")."?')) return false;\" style='color:#923E3A'><img src='../pixmaps/cross-circle-frame.png' border='0' alt='"._("Close alarm")."' title='"._("Close alarm")."'></a>";
        } else {
            //echo gettext($status);
            echo "<img src='../pixmaps/cross-circle-frame-gray.png' border='0' alt='"._("Alarm closed")."' title='"._("Alarm closed")."'>";
        }
?>
<?
// Calculate the right alarm_id
// $tmp_aid: Alarm ID
// $tmp_agid: Alarm Group ID

$tmp_bid = $alarm->get_backlog_id();
$tmp_eid = $alarm->get_event_id();
?>
        <a class="greybox" title="New ticket for Alert ID<?php echo $aid
?>" href="<?php
        echo "../incidents/newincident.php?" . "ref=Alarm&" . "title=" . urlencode($alarm_name_orig) . "&" . "priority=$risk&" . "src_ips=$src_ip&" . "event_start=$since&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port&" . "backlog_id=$tmp_bid&" . "event_id=$tmp_eid&" . "alarm_gid=$tmp_agid" ?>"><img src="../pixmaps/script--pencil.png" alt="<?=_("ticket")?>" title="<?=_("ticket")?>" border="0"/></a>
        </td>
      </tr>
	  
	  <tr><td colspan=11 id="tr<?php echo $inf ?>"></td></tr>
<?php
    } /* foreach alarm_list */
?>
</form>
      <tr>
      <td colspan="11" style="padding:10px;border-bottom:1px solid white">
<?php
    if ($backup_inf >= $ROWS) {
        echo "<a href=\"$inf_link\">&lt;-";
        printf(gettext("Prev %d") , $ROWS);
        echo "</a>";
    }
    if ($sup < $count) {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $backup_inf, $sup, $count);
        echo ")&nbsp;&nbsp;";
        echo "<a href=\"$sup_link\">";
        printf(gettext("Next %d") , $ROWS);
        echo " -&gt;</a>";
    } else {
        echo "&nbsp;&nbsp;(";
        printf(gettext("%d-%d of %d") , $backup_inf, $count, $count);
        echo ")&nbsp;&nbsp;";
    }
?>
      </td></tr>
<?php
} /* if alarm_list */
?>
    </table>


<?php
$time_load = time() - $time_start;
echo "[ " . gettext("Page loaded in") . " $time_load " . gettext("seconds") . " ]";
$db->close($conn);
geoip_close($gi);
?>
<script>
// GreyBox
$(document).ready(function(){
	GB_TYPE = 'w';
	$("a.greybox2").click(function(){
		var t = this.title || $(this).text() || this.href;
		GB_show(t,this.href,450,'90%');
		return false;
	});
	
	load_contextmenu();
	
	$(".scriptinfo").simpletip({
		position: 'right',
		onBeforeShow: function() { 
			var ip = this.getParent().attr('ip');
			this.load('alarm_netlookup.php?ip=' + ip);
		}
	});
	
	var sensors = [<?=preg_replace("/\,$/","",$sensors_str)?>];
	$("#sensors").autocomplete(sensors, {
		minChars: 0,
		width: 225,
		matchContains: "word",
		autoFill: true,
		formatItem: function(row, i, max) {
			return row.txt;
		}
	}).result(function(event, item) {
		$("#sensors").val(item.id);
	});
	var hosts = [<?=preg_replace("/\,$/","",$hosts_str)?>];
	$("#src_ip").autocomplete(hosts, {
		minChars: 0,
		width: 225,
		matchContains: "word",
		autoFill: true,
		formatItem: function(row, i, max) {
			return row.txt;
		}
	}).result(function(event, item) {
		$("#src_ip").val(item.id);
	});
	$("#dst_ip").autocomplete(hosts, {
		minChars: 0,
		width: 225,
		matchContains: "word",
		autoFill: true,
		formatItem: function(row, i, max) {
			return row.txt;
		}
	}).result(function(event, item) {
		$("#dst_ip").val(item.id);
	});
	<?php if ($date_from != "" || $query != "" || $sensor_query != "" || $directive_id != "" || $num_events > 0) { ?>
	tooglebtn();
	<?php } ?>
});
</script>
</body>
</html>


