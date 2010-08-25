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
Session::logcheck("MenuEvents", "ReportsWireless");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <link rel="stylesheet" type="text/css" href="../style/tree.css" />
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/jquery.tablePagination.js"></script>
  <script type="text/javascript" src="../js/jquery.cookie.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script> 
  
  <? include ("../host_report_menu.php") ?>
  <style type="text/css">
	a.tlink {
		color:black; padding:0px;
	}
	a.tlink:hover {
		color:white; padding:2px;
		background-color:darkblue;
		text-decoration:none;
	}
  </style>
  <script type="text/javascript">
	function postload () {
		var lnk = $('#loadme').attr('lnk');
		if (lnk) load_data(lnk);
	}
	function showhide(layer,img){
		$(layer).toggle();
		if ($(img).attr('src').match(/plus/))
			$(img).attr('src','../pixmaps/minus-small.png')
		else
			$(img).attr('src','../pixmaps/plus-small.png')
	}
	var last_url = "";
	function load_data(url) {
		last_url = url;
		if (url.match(/_pdf/)) {
			window.open(url, '', '');
		} else {
			$('#data').hide();
			$('#loading').show();
			$.ajax({
				type: "GET",
				url: url,
				success: function(msg) {
					$('#data').html(msg);
					$('#loading').hide();
					$('#data').show();
					activate_table();
				}
			});
		}
	}
	function activate_table() {
		$("a.greybox").click(function(){
			//if ($(this).attr('aps') != 'undefined') {
			//	save_cookie($(this).attr('aps'));
			//}
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,350,"85%");
			return false;
		});
		//$('tbody tr:odd', $('#results')).hide();
        
		$("#results").tablePagination({
			currPage : 1, 
			rowsPerPage : 15,
			optionsForRows: [5,15,25,50],
			//ignoreRows : $('tbody tr:odd', $('#results')),
			firstArrow : (new Image()).src="../pixmaps/first.gif",
			prevArrow : (new Image()).src="../pixmaps/prev.gif",
			lastArrow : (new Image()).src="../pixmaps/last.gif",
			nextArrow : (new Image()).src="../pixmaps/next.gif"
		});
		// simpletip
		$(".scriptinfo").simpletip({
			position: 'left',
			content: 'Loading info...',
			onBeforeShow: function() { 
				var txt = this.getParent().attr('txt');
				this.update(txt);
			}
		});
		load_contextmenu();
	}
    function changeview (si,param) {
        load_data('networks.php?index='+si+'&order=ssid&'+param)
    }
    function changeviewc (si,param) {
        load_data('clients.php?index='+si+'&'+param)
    }
	function GB_onclose(url) {
		if (url.match(/_edit/)) {
			// launch default active node
			if (last_url) load_data(last_url);
		}
	}
    var loading = '<img src="../pixmaps/loading.gif" width="16" border=0 align="absmiddle"> Loading xmls...';
    function browsexml(sensor,date) {
        $('#browsexml').html(loading);
        $.ajax({
            type: "GET",
            data: { sensor: sensor, date: date },
            url: "browse_sensor.php",
            success: function(msg) {
                $('#browsexml').html(msg);
                layer = null;
                nodetree = null;
            }
        });
    }
    var layer = null;
    var nodetree = null;
    var i=1;
    function viewxml(file, sensor) {
        if (nodetree!=null) {
            nodetree.removeChildren();
            $(layer).remove();
        }
        layer = '#srctree'+i;
        $('#wcontainer').append('<div id="srctree'+i+'" style="width:100%"></div>');
        $(layer).dynatree({
            initAjax: { url: "view_xml.php", data: { sensor: sensor, file: file } },
            clickFolderMode: 2,
            onActivate: function(dtnode) {},
            onDeactivate: function(dtnode) {}
        });
        nodetree = $(layer).dynatree("getRoot");
        i=i+1
    }
  </script>
</head>
<body>
<?
include("../hmenu.php"); 

# sensor list with perms
require_once 'Wireless.inc';
require_once 'classes/Sensor.inc';
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$locations = Wireless::get_locations($conn); 
$ossim_sensors = Sensor::get_list($conn,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1");
?>

<table class="noborder" width="100%">
<tr>
<td valign="top" class="noborder" width="275" nowrap>

	<table width="99%">
	<th style='font-size:14px'>Locations</th>
	<tr><td class="noborder" style="padding:5px;text-align:left">
	<div style="width:98%;border:1px dotted black;padding:2px">
<?
	$sensors_list = array();
	$max = count($locations);
    if ($max==0){
        echo _("No locations defined available.")."<br>"._("Please click <a href='setup.php'>Setup</a> to define locations.");
    }
	foreach ($ossim_sensors as $sensor) $sensors_list[] = $sensor->get_ip();
	#
	$i=0; $first=0;
	#$img1 = "<img src='../pixmaps/ne_plus.png' align='absmiddle' border=0 id='imgXY'>";
	#$img2 = "<img src='../pixmaps/ne_minus.png' align='absmiddle' border=0 id='imgXY'>";
	$img3 = "<img src='../pixmaps/theme/ltL_nes.gif' align='absmiddle' border=0>";
	$img4 = "<img src='../pixmaps/theme/ltL_ne.gif' align='absmiddle' border=0>";
	$plus = "<img src='../pixmaps/plus-small.png' align='absmiddle' border=0 id='imgX'>";
	$gray = "<img src='../pixmaps/plus-small-gray.png' align='absmiddle' border=0>";
	$minus = "<img src='../pixmaps/minus-small.png' align='absmiddle' border=0 id='imgX'>";
	$si = 0; unset($_SESSION["sensors"]);
	foreach ($locations as $data) {
		$i++;
		$expand = ($i==1) ? "id='loadme'" : "";
		# filter only allowed sensors
		$valid_sensors = array();
		foreach ($data["sensors"] as $s) if (in_array($s["ip"],$sensors_list)) $valid_sensors[] = $s["ip"];
        #
		if (count($valid_sensors)>0) {
			$_SESSION["sensors"][] = implode(",",$valid_sensors);
			$first++;
			$active = ($first==1) ? "block" : "none";
			$img = ($first==1) ? str_replace("X",$i,$minus) : str_replace("X",$i,$plus);
			echo "<div style='padding-left:5px;font-size:12px;font-weight:bold;line-height:16px'><a href='javascript:;' onclick=\"showhide('#cell$i','#img$i')\">$img</a><img src='../pixmaps/theme/net_group.png' align='absmiddle'>&nbsp;".$data["location"]."</div>\n";
			echo "<div id='cell$i' style='display:$active'><div style='padding-left:22px'>$img3<img src='../pixmaps/theme/wifi.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('networks.php?index=$si&order=ssid')\" lnk='networks.php?order=ssid' class='tlink' $expand>"._("Networks")."</a></div>\n";
			echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/net.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('clients.php?index=$si')\" class='tlink'>"._("Clients")."</a></div>\n";
			echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/host.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('sensors.php?index=$si&location=".urlencode(base64_encode($data['location']))."')\" class='tlink'>"._("Sensors")."</a></div>\n";
			echo "<div style='padding-left:22px'>$img3<img src='../pixmaps/theme/report.png' align='absmiddle'>&nbsp;<a href=\"javascript:load_data('events.php?index=$si')\" class='tlink'>"._("Events")."</a></div>\n";
			echo "<div style='padding-left:22px'>$img4<img src='../pixmaps/monitor.png' align='absmiddle'>&nbsp;<span class='tlink'>"._("Reports")."</span></div>\n";
			echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"networks_pdf.php?index=$si&order=ssid&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Networks")."</a></div>\n";
			echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=1&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Cloaked Networks having uncloaked APs")."</a></div>\n";
			echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=2&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Encrypted Networks having unencrypted APs")."</a></div>\n";
			echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"aps_pdf.php?index=$si&type=3&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Networks using weak encryption")."</a></div>\n";
			echo "<div style='padding-left:40px'>$img4<img src='../pixmaps/pdf.png' align='absmiddle'>&nbsp;<a href=\"clients_pdf.php?index=$si&type=3&location=".urlencode(base64_encode($data['location']))."\" target='_blank' class='tlink'>"._("Suspicious clients")."</a></div>\n";
			echo "</div>"; $si++;
		} else {
			echo "<div style='padding-left:5px;font-size:12px;font-weight:bold;line-height:16px'>$gray<img src='../pixmaps/theme/net_group.png' align='absmiddle'>&nbsp;".$data["location"]."</div>\n";
		}
	}
?>
	</div>
	</td></tr>
	</table>

</td>
<td valign="top" class="noborder" style="padding-left:10px">

	<span id="loading" style="display:none;text-align:center">
	<img src="../pixmaps/loading.gif" width="16" border=0 align="absmiddle"> Loading data...
	</span>

	<div width="100%" id="data" style="display:none">
	</div>

</td>
</tr>
</table>

</body>
</html>

