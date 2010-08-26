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
require_once 'classes/Event_viewer.inc';
require_once 'classes/User_config.inc';
require_once 'classes/Plugingroup.inc';
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
Session::logcheck("MenuEvents", "EventsViewer");
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$login = Session::get_session_user();
$groups_config = $config->get($login, 'event_viewer', 'php');
$date_to = GET('date_to') ? GET('date_to') : date('Y-m-d');
$date_from = GET('date_from') ? GET('date_from') : date('Y-m-d');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title><?php echo _("OSSIM Framework") ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script src="../js/datepicker.js" type="text/javascript"></script>
  <script src="../js/jquery.simpletip.js" type="text/javascript"></script>
<script type="text/javascript">
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
  

$(document).ready(function(){
	calendar();
	$(".scriptinfo").simpletip({
		position: 'right',
		onBeforeShow: function() { 
			var ip = this.getParent().attr('ip');
			this.load('../control_panel/alarm_netlookup.php?ip=' + ip);
		}
	});
});
</script>
</head>
<body>
<?php
include ("../hmenu.php"); ?>
<div style="text-align: left;border:1px solid red;background-color:#f7edec;padding:5px"><?php echo _("<b>Warning</b>: This tab will be removed in the next release. Now you should
customize the SIEM tab in order to have custom columns in the next release.") ?></div>
<div style="text-align: right"><b>
<a href="<?php echo $conf->get_conf("acid_link") . "/" . $conf->get_conf("event_viewer") . "_qry_main.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d" ?>"><?php echo _("Go to SIEM Events") ?></a>
</b></div>
<table width="100%" align="center" style="border-width: 0px">
<tr>
<td style="border-width: 0px">
  <table width="100%" align="center"><tr>

<?php
settype($groups_config, 'array');
$configured_groups = array_keys($groups_config);
$groups = Plugingroup::get_list($conn);
$selected_group = GET('group_id') ? GET('group_id') : 0;
$host = GET('host');
$page_from = GET('page_from') ? GET('page_from') : 0;
$total_rows = GET('total_rows') ? GET('total_rows') : 150;
$display_by = GET('display_by') ? GET('display_by') : 'day';
ossim_valid($host, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("IP"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("start date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("end date"));
ossim_valid($page_from, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("page from"));
ossim_valid($total_rows, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("num results"));
ossim_valid($display_by, OSS_LETTER, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("display by"));
ossim_valid($total_rows, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("group id"));
ossim_valid($selected_group, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("group id"));
if (ossim_error()) {
    die(ossim_error());
}
// Add a hardcoded "All group"
$group_all = new Plugingroup(0, _("All") , _("All plugins") , false);
array_unshift($groups, $group_all);
array_unshift($configured_groups, 0);
$first_run = true;
foreach($groups as $group) {
    $id = $group->get_id();
    if (in_array($id, $configured_groups)) {
        $name = $group->get_name();
        $descr = $group->get_description();
        if ((!$selected_group && $first_run) || ($id == $selected_group)) {
            $selected_group = $id;
?>
        <td width="10%" style="border-width: 0px; background-color: grey;">
            <a href="./index.php?group_id=<?php echo $id
?>&host=<?php echo $host
?>&date_from=<?php echo $date_from
?>&date_to=<?php echo $date_to
?>&display_by=<?php echo $display_by
?>" style="color: white" title="<?php echo $descr
?>"><b>&gt; <?php echo $name ?> &lt;</b></a>
        </td>
<?php
        } else { ?>
        <td width="10%" style="border-width: 0px;" title="<?php echo $descr
?>"><a href="./index.php?group_id=<?php echo $id
?>&host=<?php echo $host
?>&date_from=<?php echo $date_from
?>&date_to=<?php echo $date_to
?>&display_by=<?php echo $display_by
?>"><?php echo $name
?></a></td>
<?php
        }
        $first_run = false;
    }
}
//    <td style="border-width: 0px;"><a href="#">Hids</a></td>

?>

  </tr></table>
</td>
<td style="border-width: 0px; text-align: right"><a href="./configure_event_viewer.php"><?php echo _("Configure Event Tabs") ?></a>
</td>
</tr>
</table>
<br>
<?php
/*
The available fields are:
SID: snort sensor id
CID: snort event id
DATE: received event date
PLUGIN_ID: ossim plugin id
PLUGIN_NAME: ossim plugin name
PLUGIN_DESC: ossim plugin description
PLUGIN_SID: ossim plugin sid
SID_NAME: signature name
FILENAME: field from snort.extra_data table
USERNAME: ''
PASSWORD: ''
USERDATA1: ''
USERDATA2: ''
USERDATA3: ''
USERDATA4: ''
USERDATA5: ''
USERDATA6: ''
USERDATA7: ''
USERDATA8: ''
USERDATA9: ''
IP_SRC: the source ip of the event
IP_DST: the destination ip of the event
IP_PROTO: the ip protocol
PORT_SRC: the source port
PORT_DST: the destination port
IP_PORTSRC: the source ip and port in the format ip:port
IP_PORTDST: the destination ip and port in the format ip:port

*/
// if no viewer configured show default settings
if ($selected_group == 0) {
    $table_conf = array(
        1 => array(
            'label' => _("Type") ,
            'align' => 'left',
            'width' => '60',
            'contents' => '[PLUGIN_NAME] <b>SID_NAME</b>'
        ) ,
        2 => array(
            'label' => _("Date") ,
            'wrap' => false,
            'contents' => 'DATE'
        ) ,
        3 => array(
            'label' => _("Source IP") ,
            'contents' => 'IP_PORTSRC'
        ) ,
        4 => array(
            'label' => _("Destination IP") ,
            'contents' => 'IP_PORTDST'
        )
    );
    $plugin_group = 0;
} else {
    $table_conf = $groups_config[$selected_group];
    $plugin_group = $selected_group;
}
$page_conf = array(
    'results_per_page' => $total_rows, /* How many results per page */
    'plugin_group' => $plugin_group
    /* The plugin group to use or false for all plugins */
);
$viewer = new Event_viewer($page_conf, $table_conf);
$viewer->init_plugins_conf();
$viewer->draw();
?>
</body></html>
