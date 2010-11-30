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
Session::logcheck("MenuEvents", "EventsRT");
require_once 'classes/Host.inc';
require_once 'classes/Protocol.inc';
require_once 'classes/Plugin.inc';
require_once 'ossim_db.inc';
header('Cache-Control: no-cache');
$db = new ossim_db();
$conn = $db->connect();
$snort_conn = $db->snort_connect();
//CONFIG
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$acid_table = ($conf->get_conf("copy_siem_events")=="no") ? "acid_event" : "acid_event_input";
$key_index = ($conf->get_conf("copy_siem_events")=="no") ? "force index(IND)" : "";
$from_snort = true;
$max_rows = 15;
$delay = (preg_match("/MSIE /", $_SERVER['HTTP_USER_AGENT'])) ? 150 : 800; // do not modify
if (!isset($_SESSION['id'])) $_SESSION['id'] = "0";
if (!isset($row_num)) {
    global $row_num;
    $row_num = 0;
}
if (!isset($_SESSION['plugins_to_show'])) $_SESSION['plugins_to_show'] = array();
// responder js
if (GET('modo') == "responder") {
    $plugins = base64_decode(GET('plugins'));
    $risk = GET('risk');
    if ($from_snort) {
        // read from acid_event
        $where = ($plugins != "") ? "AND $acid_table.sid in ($plugins) AND timestamp>".strtotime("-1 days") : "";
        // Limit in second select when sensor is specified (OJO)
		$firstlimit = (Session::allowedSensors() != "") ? " limit 99999" : " limit $max_rows";
        $key_index = ($plugins != "") ? "" : str_replace("IND","timestamp",$key_index);
        //$sql = 'select "0" as plugin_id,"0" as plugin_sid, unix_timestamp(timestamp) as id, sid, signature.sig_name as plugin_sid_name, inet_ntoa(ip_src) as aux_src_ip, inet_ntoa(ip_dst) as aux_dst_ip, timestamp, ossim_risk_a as risk_a, ossim_risk_c as risk_c, (select substring_index(substring_index(hostname,":",1),"-",1) from sensor where sensor.sid = acid_event.sid) as sensor, layer4_sport as src_port, layer4_dport as dst_port, ossim_priority as priority, ossim_reliability as reliability, ossim_asset_src as asset_src, ossim_asset_dst as asset_dst, ip_proto as protocol, (select interface from sensor where sensor.sid = acid_event.sid) as interface from acid_event force index(' . $index . '), signature WHERE signature.sig_id=acid_event.signature ' . $where . ' order by timestamp desc'.$firstlimit;
        $sql = "select $acid_table.plugin_id, $acid_table.plugin_sid, unix_timestamp(timestamp) as id, $acid_table.sid, plugin_sid.name as plugin_sid_name, inet_ntoa(ip_src) as aux_src_ip, inet_ntoa(ip_dst) as aux_dst_ip, timestamp, ossim_risk_a as risk_a, ossim_risk_c as risk_c, (select substring_index(substring_index(hostname,':',1),'-',1) from sensor where sensor.sid = $acid_table.sid) as sensor, layer4_sport as src_port, layer4_dport as dst_port, ossim_priority as priority, ossim_reliability as reliability, ossim_asset_src as asset_src, ossim_asset_dst as asset_dst, ip_proto as protocol, (select interface from sensor where sensor.sid = $acid_table.sid) as interface from $acid_table $key_index LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=$acid_table.plugin_id AND plugin_sid.sid=$acid_table.plugin_sid WHERE 1=1 " . $where . " order by timestamp desc".$firstlimit;
		
		// Reselect when SENSOR is specified (better than join tables)
		if (Session::allowedSensors() != "") {
			$sensorlist = explode (",",Session::allowedSensors());
			foreach ($sensorlist as $s)
				$wheresensor .= ($wheresensor != "") ? " OR sensor='$s'" : " WHERE sensor='$s'";
			$sql = "SELECT * FROM ($sql) as preselect$wheresensor LIMIT $max_rows";
		}
		// QUERY DEBUG:
		//$f = fopen ("/tmp/sensordebug","w");
		//fputs ($f,$sql."\n");
		//fclose ($f);
		if (!$rs = & $snort_conn->Execute($sql)) {
            echo "// Query error: $sql\n// " . $snort_conn->ErrorMsg() . "\n";
            return;
        }
    } else {
        // read from event_tmp
        $sql = "SELECT *, inet_ntoa(src_ip) as aux_src_ip, inet_ntoa(dst_ip) as aux_dst_ip, '' as sid FROM event_tmp";
        if ($plugins != "" || $risk != "") $sql.= " WHERE 1";
        if ($risk != "") $sql.= " AND risk_a>$risk AND risk_c>$risk";
        if ($plugins != "") $sql.= " AND plugin_id in ($plugins)";
        $sql.= " ORDER BY timestamp DESC limit $max_rows";
        if (!$rs = & $conn->Execute($sql)) {
            echo "// Query error: $sql\n";
            return;
        }
    }
    $i = 0;
    echo "// $sql\n";
    while (!$rs->EOF) {
        $risk = ($rs->fields["risk_a"] > $rs->fields["risk_c"]) ? $rs->fields["risk_a"] : $rs->fields["risk_c"];
        echo "edata[$i][0] = '" . $rs->fields["id"] . "';\n";
        echo "edata[$i][1] = '" . $rs->fields["timestamp"] . "';\n";
        echo "edata[$i][2] = '" . str_replace("'", "\'", $rs->fields["plugin_sid_name"]) . "';\n";
        if ($risk > 7) { $rst="style=\"padding:2px 5px 2px 5px;background-color:red;color:white\""; }
        elseif ($risk > 4) { $rst="style=\"padding:2px 5px 2px 5px;background-color:orange;color:black\""; }
        elseif ($risk > 2) { $rst="style=\"padding:2px 5px 2px 5px;background-color:green;color:white\""; }
        else { $rst="style=\"padding:2px 5px 2px 5px;color:black\""; }
        echo "edata[$i][3] = '<span $rst>" . $risk . "</span>';\n";
        echo "var pid = '" . $rs->fields["plugin_id"] . "'; if (pid == '0') pid = sids['id_" . $rs->fields["sid"] . "'];\n";
        echo "edata[$i][4] = pid;\n";
        echo "edata[$i][5] = '" . $rs->fields["plugin_sid"] . "';\n";
        echo "edata[$i][6] = '" . $rs->fields["sensor"] . "';\n";
        echo "edata[$i][7] = '" . $rs->fields["aux_src_ip"] . "';\n";
        echo "edata[$i][8] = '" . $rs->fields["src_port"] . "';\n";
        echo "edata[$i][9] = '" . $rs->fields["aux_dst_ip"] . "';\n";
        echo "edata[$i][10] = '" . $rs->fields["dst_port"] . "';\n";
        // more detail
        echo "edata[$i][11] = '" . $rs->fields["priority"] . "';\n";
        echo "edata[$i][12] = '" . $rs->fields["reliability"] . "';\n";
        echo "edata[$i][13] = '" . $rs->fields["interface"] . "';\n";
        echo "edata[$i][14] = '" . $rs->fields["protocol"] . "';\n";
        echo "edata[$i][15] = '" . $rs->fields["asset_src"] . "';\n";
        echo "edata[$i][16] = '" . $rs->fields["asset_dst"] . "';\n";
        echo "edata[$i][17] = '" . $rs->fields["alarm"] . "';\n";
        $rs->MoveNext();
        $i++;
    }
    while ($i < $max_rows) { // fill rest
        for ($k = 0; $k <= 17; $k++) echo "edata[$i][$k] = '';\n";
        $i++;
    }
    echo "draw_edata();\n";
} else {
?>
<html>
<head>
<title>Event Tail Viewer</title>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<style type="text/css">
.opaque { opacity:1; MozOpacity:1; KhtmlOpacity:1; filter:alpha(opacity=100); width:900; background-color:#72879A }
.semiopaque { opacity:0.9; MozOpacity:0.9; KhtmlOpacity:0.9; filter:alpha(opacity=90); background-color:#B5C3CF }
.little { font-size:8px }
body { font-family:arial; font-size:11px; }
</style>
<script>
// capa flotante activa con mouseover

var IE = document.all?true:false
if (!IE) document.captureEvents(Event.MOUSEMOVE)
document.onmousemove = getMouseXY;
var tempX = 0
var tempY = 0

var difX = 15
var difY = 0

function getMouseXY(e) {
	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft + difX
		tempY = event.clientY + document.body.scrollTop + difY
	} else {  // grab the x-y pos.s if browser is MOZ
		tempX = e.pageX + difX
		tempY = e.pageY + difY
	}  
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}
	var dh = document.body.clientHeight;
	if (document.getElementById("numeroDiv").offsetHeight+tempY > dh)
		tempY = tempY - (document.getElementById("numeroDiv").offsetHeight + tempY - dh)
	document.getElementById("numeroDiv").style.left = tempX
	document.getElementById("numeroDiv").style.top = tempY
	return true
}

function ticketon(i) { 
	if (document.getElementById) {
		pause = true;
		if (getcontent('footer') != 'Stoped.') changecontent('footer','<?php echo _("Paused") ?>.')
		// generating detail info
		var txt1 = '<table border=0 cellpadding=8 cellspacing=0 class="semiopaque"><tr><td class=nobborder style="line-height:18px" nowrap>'
		txt1 = txt1 + 'Date: <b>' + edata[i][1] + '</b><br>'
		txt1 = txt1 + 'Event: <b>' + edata[i][2] + '</b><br>'
		txt1 = txt1 + 'Risk: <b>' + edata[i][3] + '</b><br>'
		var plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
		txt1 = txt1 + 'Plugin: <b>' + plugin + '</b>' + ',&nbsp; Plugin_sid: <b>' + edata[i][5] + '</b><br>'
		var sensor = (sensors[edata[i][6]] != undefined) ? sensors[edata[i][6]] : ((hosts[edata[i][6]] != undefined) ? hosts[edata[i][6]] : edata[i][6]);
		txt1 = txt1 + 'Sensor: <b>' + sensor + '</b> <i>[' + edata[i][6] + ']</i><br>'
		var host = (edata[i][7]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][7]] != undefined) ? hosts[edata[i][7]] : edata[i][7]);
		var ip = edata[i][7];
		if (host!='N/A' && edata[i][8]!="0") ip = ip + ":" + edata[i][8];
		txt1 = txt1 + 'Source IP: <b>' + host + '</b> <i>[' + ip + ']</i><br>'
		host = (edata[i][9]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][9]] != undefined) ? hosts[edata[i][9]] : edata[i][9]);
		ip = edata[i][9];
		if (edata[i][10]!="0") ip = ip + ":" + edata[i][10];
		txt1 = txt1 + 'Dest IP: <b>' + host + '</b> <i>[' + ip + ']</i><br>'
		txt1 = txt1 + 'Priority: <b>' + edata[i][11] + '</b>' + ',&nbsp; Reliability: <b>' + edata[i][12] + '</b><br>'
		proto = (protocols['proto_'+edata[i][14]] != undefined) ? protocols['proto_'+edata[i][14]] : edata[i][14]
		txt1 = txt1 + 'Interface: <b>' + edata[i][13] + '</b>' + ',&nbsp; Protocol: <b>' + proto + '</b><br>'
		txt1 = txt1 + 'Asset Src: <b>'+ edata[i][15] + '</b>' + ',&nbsp; Asset Dst: <b>' + edata[i][16] + '</b><br>'
		if (edata[i][17]!="") txt1 = txt1 + 'Alarm: <b>' + edata[i][17] + '</b><br>'
		document.getElementById("numeroDiv").innerHTML = txt1
		document.getElementById("numeroDiv").style.display = ''
		document.getElementById("numeroDiv").style.visibility = 'visible'
	}
}

function ticketoff() { 
	if (document.getElementById) {
		document.getElementById("numeroDiv").style.visibility = 'hidden'
		document.getElementById("numeroDiv").style.display = 'none'
		document.getElementById("numeroDiv").innerHTML = ''
		if (getcontent('footer') != '<?php echo _("Stoped") ?>.') changecontent('footer','<?php echo _("Continue...waiting next refresh") ?>')
		pause = false;
	}
}

// fade effect
function opacity(id, opacStart, opacEnd, millisec) {
	//speed for each frame
	var speed = Math.round(millisec / 100);
	var timer = 0;

	//determine the direction for the blending, if start and end are the same nothing happens
	if(opacStart > opacEnd) {
		for(i = opacStart; i >= opacEnd; i--) {
			setTimeout("changeOpac(" + i + ",'" + id + "')",(timer * speed));
			timer++;
		}
	} else if(opacStart < opacEnd) {
		for(i = opacStart; i <= opacEnd; i++)
			{
			setTimeout("changeOpac(" + i + ",'" + id + "')",(timer * speed));
			timer++;
		}
	}
}
//change the opacity for different browsers
function changeOpac(opacity, id) {
	var object = document.getElementById(id).style; 
	object.opacity = (opacity / 100);
	object.MozOpacity = (opacity / 100);
	object.KhtmlOpacity = (opacity / 100);
	object.filter = "alpha(opacity=" + opacity + ")";
}
function currentOpac(id, opacEnd, millisec) {
	//standard opacity is 100
	var currentOpac = 100;
	
	//if the element has an opacity set, get it
	if(document.getElementById(id).style.opacity < 100) {
		currentOpac = document.getElementById(id).style.opacity * 100;
	}
	//call for the function that changes the opacity
	opacity(id, currentOpac, opacEnd, millisec)
}
function disolveOpac() {
	for (var i=0;i<<?php echo $max_rows
?>;i++) if (efade[i]==1) currentOpac('row'+i,0,<?php echo $delay
?>)
}
// end fade
//
// combo filter functions
function newcheckbox (elName,val) {
	var el = document.createElement('input');
	el.type = 'checkbox';
	el.name = elName;
	el.id = elName;
	el.value = val;
	el.className = 'little'
	el.addEventListener("click", reload, true); 
	return el;
}
function addtocombofilter (text,value) {
	var fo=document.getElementById('filter')
	if (notfound(fo,value)) {
		fo.appendChild(newcheckbox(text,value))
		fo.appendChild(document.createTextNode(text))
		fo.appendChild(document.createElement('br'))
	}
}
function notfound (fo,value) {
	var inputs = fo.getElementsByTagName("input");
	for (var i=0; i<inputs.length; i++) 
		if (inputs[i].getAttribute('type')=='checkbox') {
			if (inputs[i]["value"]==value) {
				return false
			}
		}
	return true
}
function getdatafromcombo(h) {
	var value = '';
	var myselect=document.getElementById(h)
	for (var i=0; i<myselect.options.length; i++) {
			if (myselect.options[i].selected==true) {
					value = value + ((value=='') ? '' : ',') + myselect.options[i].value
			}
	}
	return value;
}
function getdatafromcheckbox() {
	var value = '';
	var inputs = document.getElementById('filter').getElementsByTagName("input");
	for (var i=0; i<inputs.length; i++) 
		if (inputs[i].getAttribute('type')=='checkbox') {
			if (inputs[i]["checked"]==true) {
				value = value + ((value=='') ? '' : ',') + inputs[i]["value"]
			}
		}
	return value;
}
function rst() {
	var myselect=document.getElementById(comborisk);
	var inputs = document.getElementById('filter').getElementsByTagName("input");
	for (var i=0; i<inputs.length; i++) 
		if (inputs[i].getAttribute('type')=='checkbox')
			inputs[i].setAttribute('checked',false);
	reload()
}
//
// Hosts and Sensors to direct-name-resolv
<?php
    $sensors = $hosts = array();
    list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
?>
var sensors = new Array(<?php echo count($sensors) ?>)
var hosts = new Array(<?php echo count($hosts) ?>)
<?php
    foreach($sensors as $ip => $sensor) echo "sensors['$ip'] = '$sensor'\n";
    foreach($hosts as $ip => $host) echo "hosts['$ip'] = '$host'\n";
?>
// Protocol list
<?php
    if ($protocol_list = Protocol::get_list($conn)) {
        echo "var protocols = new Array(" . count($protocol_list) . ")\n";
        foreach($protocol_list as $proto) {
            //$_SESSION[$id] = $plugin->get_name();
            echo "protocols['proto_" . $proto->get_id() . "'] = '" . $proto->get_name() . "'\n";
        }
    }
?>
// plugin list
<?php
    if ($plugin_list = Plugin::get_list($conn, "")) {
        echo "var plugins = new Array(" . count($plugin_list) . ")\n";
        foreach($plugin_list as $plugin) {
            //$_SESSION[$id] = $plugin->get_name();
            echo "plugins['id_" . $plugin->get_id() . "'] = '" . $plugin->get_name() . "'\n";
            echo "plugins['id_" . $plugin->get_name() . "'] = '" . $plugin->get_name() . "'\n";
        }
    }
?>
// sids
<?php
	$sids = array();
    //if ($rs = & $snort_conn->Execute("select sid,hostname from sensor")) {
    if ($rs = & $snort_conn->Execute("select distinct sensor.sid,sensor.hostname from sensor,acid_event where acid_event.sid=sensor.sid and acid_event.timestamp>".strtotime("-1 days"))) {
        while (!$rs->EOF) {
            $plugid = explode("-", $rs->fields["hostname"]);
            if ($plugid[1] == "") $plugid[1] = "snort";
				$sids[$plugid[1]][] = $rs->fields["sid"]; // extract sid=>plugins
			
            $rs->MoveNext();
        }
        echo "var sids = new Array(" . count($sids) . ")\n";
        foreach($sids as $key => $value) {
            foreach($value as $ss) echo "sids['id_" . $ss . "'] = '$key'\n";
        }
    }
?>
// content functions
var ajaxObj = null;
var pause = false;
var url = '<?php echo $SCRIPT_NAME
?>?modo=responder';
var comborisk = 'rsk';
function changecontent(id,content) { document.getElementById(id).innerHTML = content }
function getcontent(id) { return document.getElementById(id).innerHTML }
function create_script(url) {
	// load extra parameters from select filter
	var idf = getdatafromcheckbox();
	if (idf!='') url = url + '&plugins=' + idf
	<?php
    if ($from_snort == false) { ?>
	var rsk = getdatafromcombo(comborisk);
	if (rsk!='' && rsk!='0') url = url + '&risk=' + rsk
	<?php
    } ?>
	// make script element
	//changecontent('footer','<?php echo _("Refreshing") ?> '+url+'...')
	var ajaxObject = document.createElement('script');
	ajaxObject.src = url;
	ajaxObject.type = "text/javascript";
	ajaxObject.charset = "utf-8";
	try {
		return ajaxObject;
	} finally {
		ajaxObject = null;
	}
}
function refresh() {
	// ajax responder
	if (pause==false) {
		changecontent('footer','<?php echo _("Refreshing") ?>...')
		var h = document.getElementsByTagName('head')
		if (ajaxObj) ajaxObj.parentNode.removeChild(ajaxObj)
		ajaxObj = create_script(url)
		h.item(0).appendChild(ajaxObj);
	}
}
var edata = new Array(<?php echo $max_rows ?>)
var eprev = new Array(<?php echo $max_rows ?>)
var efade = new Array(<?php echo $max_rows ?>)
<?php
    for ($i = 0; $i < $max_rows; $i++) { ?>
edata[<?php echo $i
?>] = new Array(18);
eprev[<?php echo $i
?>] = 0;
efade[<?php echo $i
?>] = 0;
<?php
    } ?>
var fadescount = 0;
function draw_edata() {
	if (pause == false) {
		fadescount = 0;
		for (var i=0;i<<?php echo $max_rows
?>;i++) {
			// calculate different rows
			efade[i] = (eprev[i]==edata[i][0]) ? 0 : 1;
			if (efade[i]==1) fadescount++;
			eprev[i] = edata[i][0];
			// change content
			changecontent('date'+i,edata[i][1]);
			urle = "<a href=\"javascript:;\" onmouseover=\"ticketon(" + i + ")\" onmouseout=\"ticketoff()\" style='text-decoration:underline'>" + edata[i][2] + "</a>"
			changecontent('event'+i,urle);
			changecontent('trevent'+i,edata[i][2]);
			changecontent('risk'+i,edata[i][3]);
			plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
			changecontent('plugin_id'+i,plugin);
			//changecontent('plugin_sid'+i,edata[i][5]); changecontent('trplugin_sid'+i,edata[i][5]);
			<?php
    if (!$from_snort) echo "addtocombofilter (plugin,edata[i][4]);\n"; ?>
			sensor = (sensors[edata[i][6]] != undefined) ? sensors[edata[i][6]] : ((hosts[edata[i][6]] != undefined) ? hosts[edata[i][6]] : edata[i][6]);
			changecontent('sensor'+i,sensor);
			host = (edata[i][7]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][7]] != undefined) ? hosts[edata[i][7]] : edata[i][7]);
			if (host!='N/A' && edata[i][8]!="0" && edata[i][8]!="") host = host + ":" + edata[i][8];
			changecontent('srcip'+i,host);
			host = (edata[i][9]=='0.0.0.0') ? 'N/A' : ((hosts[edata[i][9]] != undefined) ? hosts[edata[i][9]] : edata[i][9]);
			if (edata[i][10]!="0" && edata[i][10]!="") host = host + ":" + edata[i][10];
			changecontent('dstip'+i,host);
		}
		// launch opacity effect only for different rows
		steps = Math.floor(100/(fadescount+1));
		for (var i=0;i<<?php echo $max_rows
?>;i++) if (efade[i]==1) changeOpac(100-(steps*i),'row'+i)
		setTimeout("disolveOpac()",150);
		changecontent('footer','<?php echo _("Done") ?>. [<b>' + fadescount + '</b> <?php echo _("new rows") ?>]')
	}
}
var idr = null
function play() { 
	refresh();
	if (idr == null) idr = setInterval("refresh()",document.controls.speed.options[document.controls.speed.selectedIndex].value);
}
function stop() { clearInterval(idr); idr = null; changecontent('footer','<?php echo _("Stoped") ?>.') }
function reload() { stop(); play() }
function pausecontinue() { if (idr==null) play(); else stop(); }
function go() {
	for (var i=0;i<<?php echo $max_rows ?>;i++) changeOpac(0,'row'+i)
	play()
}
</script>
</head>
<body onload="go()">
<?php
    include ("../hmenu.php"); ?>

<table border=0 cellpadding=0 cellspacing=0 class="nobborder"><tr><td class="nobborder">
	<form name="controls" onsubmit="return false" style="margin:0 auto">
	<input type=button onclick="pausecontinue()" value="<?php echo _("pause"); ?>" class="btn" style="font-size:12px">
	<!--
	<input type=button  Onclick="play()" value="<?php echo _("start"); ?>">
	<input type=button  Onclick="stop()" value="<?php echo _("stop"); ?>">
	-->
	<SELECT NAME="speed" Onchange="reload()" disabled style="display:none;">  
		<OPTION VALUE="5000"> <?php echo _("Slow"); ?>
		<OPTION VALUE="3000"> <?php echo _("Medium"); ?>
		<OPTION VALUE="1500" selected> <?php echo _("Fast"); ?>
	</SELECT>
	</form>
</td><td style="padding-left:10px" id="footer" class="nobborder"></td>
</tr></table>

<br>
<div id="cab" style="position:absolute;left:20px;top:90px">
<table border=0 cellpadding=0 cellspacing=0 class="nobborder" width="920" align="center">
<tr height="22">
  <td width="140" style="text-align:left"><b><?php echo _("Date"); ?></b></td>
  <td width="275" style="text-align:left;color:gray"><b><?php echo _("Event Name"); ?></b></td>
  <td width="40"><b><?php echo _("Risk"); ?></b></td>
  <td width="80"><b><?php echo _("Generator"); ?></b></td>
  <td width="100"><b><?php echo _("Sensor"); ?></b></td>
  <td width="140"><b><?php echo _("Source IP"); ?></b></td>
  <td width="140"><b><?php echo _("Dest IP"); ?></b></td>
</tr>
</table>
</div>

<div id="tr" style="position:absolute;left:20px;top:120px">
<?php
    for ($i = 0; $i < $max_rows; $i++) { ?>
<div id="row<?php echo $i
?>" class="opaque">
	<table border=0 cellpadding=0 cellspacing=0 class="nobborder" width="920" align="center">
	<tr height="22">
	  <td width="140" style="color:white;text-align:left" id="trdate<?php echo $i
?>">&nbsp;</td>
	  <td width="275" style="color:white;text-align:left;padding-left:5px" id="trevent<?php echo $i
?>">&nbsp;</td>
	  <td width="40" id="trrisk<?php echo $i
?>">&nbsp;</td>
	  <td width="80" id="trplugin_id<?php echo $i
?>">&nbsp;</td>
	  <td width="100" id="trsensor<?php echo $i
?>">&nbsp;</td>
	  <td width="140" id="trsrcip<?php echo $i
?>">&nbsp;</td>
	  <td width="140" id="trdstip<?php echo $i
?>">&nbsp;</td>
	</tr>
	<tr height=1></tr>
	</table>
</div>
<?php
    } ?>
</div>

<div id="str" style="position:absolute;left:20px;top:120px">
<table border=0 cellpadding=0 cellspacing=0 class="nobborder" width="920" align="center">
<?php
    for ($i = 0; $i < $max_rows; $i++) { ?>
<tr height="22">
  <td width="140" style="text-align:left" id="date<?php echo $i
?>"></td>
  <td width="275" style="color:blue;text-align:left;padding-left:5px" id="event<?php echo $i
?>"></td>
  <td width="40" id="risk<?php echo $i
?>"> </td>
  <td width="80" id="plugin_id<?php echo $i
?>"> </td>
  <td width="100" id="sensor<?php echo $i
?>"> </td>
  <td width="140" id="srcip<?php echo $i
?>"> </td>
  <td width="140" id="dstip<?php echo $i
?>"> </td>
</tr>
<tr height=1></tr>
<?php
    } ?>
</table>
</div>

<div id="filters" style="position:absolute;left:950px;top:90px">
<table border=0 cellpadding=0 cellspacing=0 width="120" class="nobborder">
<tr><td class="nobborder" nowrap>
<form id="filter" name="filter" style="margin:0 auto">
	<input type="button" value="reset" onclick="rst()" class="btn" style="font-size:12px"><br><br>
	<?php
    if ($from_snort == false) { ?>
	<b>Risk filter:</b><br>
	<select name="rsk" id="rsk" onchange="reload()" style="width:100;overflow-x:hidden"><option value="0">0<option value="1">1<option value="2">2<option value="3">3<option value="4">4<option value="5">5<option value="6">6<option value="7">7<option value="8">8<option value="9">9<option value="10">10</select>
	<br><br>
	<?php
    } ?>
	<b>Plugin filter:</b><br>
<?php
    if ($from_snort) {
        // read from sensor table
        ksort($sids);
        foreach($sids as $pl => $arr) {
            $val = implode(",", $arr);
            echo "<input type='checkbox' class='little' value='".base64_encode($val)."'>$pl<br>\n";
        }
    }
?>
</form>
</td></tr>
</table>
</div>

<div id="numeroDiv" style="position:absolute; z-index:999; left:0px; top:0px; visibility:hidden; display:none"></div>

</body>
</html>
<?php
} ?>
