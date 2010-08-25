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
//error_reporting(E_NOTICE);
require_once ('classes/Session.inc');
require_once ('classes/CIDR.inc');
Session::logcheck("MenuReports", "ReportsHostReport");
// load column layout
require_once ('../conf/layout.php');
$category = "report";
$name_layout = "host_layout";
$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
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
	
	<table class="noborder">
	<tr><td valign="top">
		<table id="flextable" style="display:none"></table>
	</td><tr>
	<tr><td valign="top" class="noborder" style="padding-top:10px">
		<IFRAME src="" frameborder="0" name="addcontent" id="addcontent" width="500"></IFRAME>
	</td></tr>
	</table>

	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
		<li class="hostreport"><a href="#hostreport" class="greybox"><img src="../pixmaps/reports.png"> Host Report</a></li>
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
			return document.getElementById(id).offsetWidth-20;
		else
			return 700;
	}
	function action(com,grid) {
		var items = $('.trSelected', grid);
		if (com=='Delete selected') {
			//Delete host by ajax
			if (typeof(items[0]) != 'undefined') {
				$("#flextable").changeStatus('Deleting host...',false);
				$.ajax({
						type: "GET",
						url: "deletehost.php?confirm=yes&ip="+urlencode(items[0].id.substr(3)),
						data: "",
						success: function(msg) {
							$("#flextable").flexReload();
						}
				});
			}
			else alert('You must select a host');
		}
		else if (com=='Modify') {
			if (typeof(items[0]) != 'undefined') document.location.href = 'modifyhostform.php?ip='+urlencode(items[0].id.substr(3))
			else alert('You must select a host');
		}
		else if (com=='Insert new host') {
			document.location.href = 'newhostform.php'
		}
		else if (com=='Reload') {
			document.location.href = '../conf/reload.php?what=hosts&back=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>'
		}
	}
	function save_layout(clayout) {
		$("#flextable").changeStatus('Saving column layout...',false);
		$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
		});
	}
	function menu_action(com,id,fg,fp) {
		if (com=='hostreport') {
			var ip = id;
			var hostname = id;
			var url = "../report/host_report.php?host="+ip+"&hostname="+hostname;
			if (hostname == ip) var title = "Host Report: "+ip;
			else var title = "Host Report: "+hostname+"("+ip+")";
			//GB_show(title,url,450,'90%');
			var wnd = window.open(url,'hostreport_'+ip,'fullscreen=yes,scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
			wnd.focus()
		}
	}

	$("#flextable").flexigrid({
		url: 'gethost.php',
		dataType: 'xml',
		colModel : [
		<?php
$default = array(
    "hostname" => array(
        'Hostname',
        100,
        'true',
        'left',
        false
    ) ,
    "ip" => array(
        'IP',
        100,
        'true',
        'center',
        false
    ) ,
    "asset" => array(
        'Asset',
        40,
        'true',
        'center',
        false
    ) ,
	"threshold_c" => array(
        'Thr_C',
        40,
        'true',
        'center',
        false
    ) ,
    "threshold_a" => array(
        'Thr_A',
        40,
        'true',
        'center',
        false
    ) ,
    "sensors" => array(
        'Sensors',
        100,
        'false',
        'center',
        false
    ) ,
    "scantype" => array(
        'Scantype',
        80,
        'false',
        'center',
        false
    ) ,
    "os" => array(
        'OS',
        85,
        'false',
        'center',
        false
    )
);
list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "hostname", "asc", 300);
echo "$colModel\n";
?>
			],
		searchitems : [
			{display: 'Hostname', name : 'hostname'},
			{display: 'IP', name : 'ip', isdefault: true}
			],
		sortname: "<?php echo $sortname ?>",
		sortorder: "<?php echo $sortorder ?>",
		usepager: true,
		title: 'HOSTS',
		pagestat: 'Displaying {from} to {to} of {total} hosts',
		nomsg: 'No hosts',
		useRp: true,
		rp: 25,
		contextMenu: 'myMenu',
		onContextMenuClick: menu_action,
		showTableToggleBtn: true,
		singleSelect: true,
		width: get_width('headerh1'),
		height: 400,
		onColumnChange: save_layout,
		onEndResize: save_layout
	});   
	
	</script>

</body>
</html>

