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
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "PolicySensors");
// load column layout
require_once ('../conf/layout.php');
$category = "policy";
$name_layout = "sensors_layout";
$layout = load_layout($name_layout, $category);
// data
require_once 'ossim_db.inc';
require_once 'get_sensors.php';
require_once 'classes/Sensor.inc';
$active_sensors = 0;
$total_sensors = 0;
$sensor_stack = array();
$sensor_stack_on = array();
$sensor_stack_off = array();
$sensor_configured_stack = array();
$db = new ossim_db();
$conn = $db->connect();
list($sensor_list, $err) = server_get_sensors($conn);
if ($err != "") echo $err;
foreach($sensor_list as $sensor_status) {
    if ($sensor_status["state"] = "on") {
        array_push($sensor_stack_on, $sensor_status["sensor"]);
        $sensor_stack[$sensor_status["sensor"]] = 1;
    } else {
        array_push($sensor_stack_off, $sensor_status["sensor"]);
    }
}
if ($sensor_list = Sensor::get_all($conn, "")) {
    $total_sensors = count($sensor_list);
    foreach($sensor_list as $sensor) {
        if ($sensor_stack[$sensor->get_ip() ] == 1) {
            $active_sensors++;
            array_push($sensor_configured_stack, $sensor->get_ip());
        }
    }
}

$active_sensors = ($active_sensors == 0) ? "<font color=red><b>$active_sensors</b></font>" : "<a href=\"sensor.php?onlyactive=1\"><font color=green><b>$active_sensors</b></font></a>";
$total_sensors = "<a href=\"sensor.php\"><b>$total_sensors</b></a>";
$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <meta http-equiv="X-UA-Compatible" content="IE=7" />
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
  <script type="text/javascript" src="../js/urlencode.js"></script>
</head>
<body>
                                                                                
	<?php
include ("../hmenu.php"); ?>
	<div  id="headerh1" style="width:100%;height:1px">&nbsp;</div>

<?php
//$sensor_stack_on[] = "192.168.1.2";
$diff_arr = array_diff($sensor_stack_on, $sensor_configured_stack);
if ($diff_arr) {
?>
	<table class="noborder"><tr>
	<td><font color="red"><b> <?php
    echo gettext("Warning"); ?> </b></font>:
		<?php
    echo gettext("the following sensor(s) are being reported as enabled by the server but aren't configured"); ?> .
	</td>
	</tr></table>

	<table class="noborder">
	<?php
    foreach($diff_arr as $ip_diff) { ?>
	<tr>
	<td nowrap><img src="../pixmaps/theme/host.png" border=0 align="absmiddle"><a href="sensor_plugins.php?sensor=<?php
        echo $ip_diff ?>"><b><?php
        echo $ip_diff ?></b></a>&nbsp;</td>
	<td nowrap style="background:#E8E8E8;border:1px solid #D7D7D7">&nbsp;<a href="newsensorform.php?ip=<?php
        echo $ip_diff ?>"><img src="../pixmaps/tables/table_row_insert.png" border=0 align="absmiddle"> <?php
        echo gettext("Insert"); ?> </a>&nbsp;</td>
	</tr>
	<tr><td colspan="2"></td></tr>
	<?php
    } ?>
	</table>
<?php
} ?>

  
	<table class="noborder">
	<tr><td valign="top">
		<table id="flextable" style="display:none"></table>
	</td><tr>
	</table>
	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
	  <li class="hostreport"><a href="#hostreport" class="greybox" style="padding:3px"><img src="../pixmaps/reports.png" align="absmiddle"/><?=_("Host Report")?></a></li>
	  <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
          <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
          <li class="hostreport"><a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Sensor")?></a></li>
        </ul>
	<style>
		table, th, tr, td {
			background:transparent;
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border:none;
			padding:0px; margin:0px;
		}
		input, select {
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border: 1px solid #8F8FC6;
			font-size:12px; font-family:arial; vertical-align:middle;
			padding:0px; margin:0px;
		}
	</style>
	<script>
	function get_width(id) {
		if (typeof(document.getElementById(id).offsetWidth)!='undefined') 
			return document.getElementById(id).offsetWidth-5;
		else
			return 700;
	}

        function get_height()
        {
           return parseInt($(document).height()) - 200;
        }
        
	function action(com,grid) {
		var items = $('.trSelected', grid);
		if (com=='<?php echo _("Delete selected")?>') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
                if(confirm('<?php echo _("Do you want to delete this sensor?") ?>')) {
                    var sdata = items[0].id.substr(3).split('###');
                    document.location.href = 'deletesensor.php?confirm=yes&name='+urlencode(sdata[0])
                }
			}
			else alert('<?=_("You must select a sensor")?>');
		}
		else if (com=='<?php echo _("Modify")?>') {
			if (typeof(items[0]) != 'undefined') {
				var sdata = items[0].id.substr(3).split('###');
				document.location.href = 'interfaces.php?sensor='+urlencode(sdata[1])+'&name='+urlencode(sdata[0]);
			}
			else alert('<?=_("You must select a sensor")?>');
		}
		else if (com=='<?php echo _("New")?>') {
			document.location.href = 'newsensorform.php'
		}
		else if (com=='<?php echo _("Apply")?>') {
			document.location.href = '../conf/reload.php?what=sensors&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>'
		}
	}
    function linked_to(rowid) {
		var aux = rowid.split(/###/);
		var ip = aux[1];
		var hostname = aux[0];    
        document.location.href = 'interfaces.php?sensor='+urlencode(ip)+'&name='+urlencode(hostname);
    }
	function menu_action(com,id,fg,fp) {
		var aux = id.split(/###/);
		var ip = aux[1];
		var hostname = aux[0];

                if (com=='hostreport') {
			var url = "../report/host_report.php?hostname="+hostname+"&host="+ip;
			if (hostname == ip) var title = "Host Report: "+ip;
			else var title = "Host Report: "+hostname+"("+ip+")";
			//GB_show(title,url,450,'90%');
			var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
			wnd.focus()
		}


                if (com=='delete') {
                    //Delete host by ajax
                    if (typeof(ip) != 'undefined') {
                        if(confirm('<?php echo _("Do you want to delete this sensor?") ?>')) {
                            document.location.href = 'deletesensor.php?confirm=yes&name='+urlencode(hostname)
                        }
                    }
                    else alert('<?=_("Sensor unselected")?>');
		}

                if (com=='modify') {
                    if (typeof(ip) != 'undefined') {
                       document.location.href = 'interfaces.php?sensor='+urlencode(ip)+'&name='+urlencode(hostname);
                    }
                    else alert('<?=_("Sensor unselected")?>');
		}

                if (com=='new') {
                    document.location.href = 'newsensorform.php'
		}



	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('<?=_("Saving column layout")?>...',false);
		$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
		});
	}
	$("#flextable").flexigrid({
		<? if ($_GET['onlyactive'] == 1) { ?>
		url: 'getsensor.php?sortname=active%20desc',
		<? } else { ?>
		url: 'getsensor.php?sortname=ip',
		<? } ?>
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "ip" => array(
        _('IP'),
        130,
        'true',
        'center',
        false
    ) ,
    "name" => array(
        _('Name'),
        180,
        'true',
        'center',
        false
    ) ,
    "priority" => array(
        _('Priority'),
        60,
        'true',
        'center',
        false
    ) ,
    "port" => array(
        _('Port'),
        40,
        'true',
        'center',
        true
    ) ,
    "version" => array(
        _('Version'),
        180,
        'true',
        'center',
        false
    ) ,
    "status" => array(
        _('Status'),
        50,
        'true',
        'center',
        false
    ) ,
    //"munin" => array('Munin',40,'false','center',false),
    "desc" => array(
        _('Description'),
        280,
        'false',
        'left',
        false
    )
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
echo "$colModel\n";
?>
			],
		buttons : [
			{name: '<?=_("New")?>', bclass: 'add', onpress : action},
			{separator: true},
			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
			{separator: true},
			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
			{separator: true},
			{name: '<?=_("Apply")?>', bclass: '<?php echo (WebIndicator::is_on("Reload_sensors")) ? "reload_red" : "reload" ?>', onpress : action},
			{separator: true},
			{name: '<a href=\"sensor.php?onlyactive=1\"><?=_("Active Sensors")?></a>: <?php echo $active_sensors ?>', bclass: 'info', iclass: 'ibutton'},
			{name: '<a href=\"sensor.php\"><?=_("Total Sensors")?></a>: <?php echo $total_sensors ?>', bclass: 'info', iclass: 'ibutton'}
			],
		sortname: "<?php echo $sortname ?>",
		sortorder: "<?php echo $sortorder ?>",
		usepager: true,
		title: '<?=_("Sensors")?>',
		pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("sensors")?>',
		nomsg: '<?=_("No sensors")?>',
		useRp: true,
		rp: 20,
		contextMenu: 'myMenu',
		onContextMenuClick: menu_action,
		showTableToggleBtn: true,
		singleSelect: true,
		width: get_width('headerh1'),
		height: get_height(),
		onColumnChange: save_layout,
		onDblClick: linked_to,
		onEndResize: save_layout
	});   
	
	</script>

</body>
</html>

