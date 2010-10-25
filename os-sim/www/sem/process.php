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
ob_start();
set_time_limit(300);
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ("classes/Host.inc");
require_once ("classes/Net.inc");
require_once ("process.inc");
require_once ('ossim_db.inc');
function dateDiff($startDate, $endDate)
{
    // Parse dates for conversion
    $startArry = date_parse($startDate);
    $endArry = date_parse($endDate);

    // Convert dates to Julian Days
    $start_date = gregoriantojd($startArry["month"], $startArry["day"], $startArry["year"]);
    $end_date = gregoriantojd($endArry["month"], $endArry["day"], $endArry["year"]);

    // Return difference
    return round(($end_date - $start_date), 0);
}
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$config = parse_ini_file("everything.ini");
$a = GET("query");
//$export = (GET('txtexport') == "true") ? 1 : 0;
$export = GET('txtexport');
if($export=='exportEntireQuery'){
    $numResult=999999999;
}else{
    $numResult=50;
}
$offset = GET("offset");
if (intval($offset) < 1) {
    $offset = 0;
}
$start = GET("start");
$end = GET("end");
$sort_order = GET("sort");
$uniqueid = GET("uniqueid");

$debug_log = GET("debug_log");
ossim_valid($debug_log, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_SLASH, 'illegal:' . _("debug_log"));
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($offset, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("offset"));
ossim_valid($a, OSS_TEXT, OSS_NULLABLE, '[', ']', 'illegal:' . _("a"));
ossim_valid($sort_order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("sort order"));
ossim_valid($uniqueid, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("uniqueid"));
if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();

$sensors = $hosts = $ossim_servers = array(); $hostnames = array();
list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array(); $netnames = array();
foreach ($_nets as $_net) { $_nets_ips[] = $_net->get_ips(); $netnames[$_net->get_name()] = $_net->get_ips(); }
foreach ($hosts as $ip=>$name) { $hostnames[$name] = $ip; }
$networks = implode(",",$_nets_ips);
$hosts_ips = array_keys($hosts);

if ($a != "" && !preg_match("/\=/",$a)) { // Search in data field
	$a = "data='".$a."'";
}

$atoms = explode("|",preg_replace("/ (and|or) /i","|",$a));
foreach ($atoms as $atom) {
	if (preg_match("/source type(\!?\=)(.+)/", $atom, $matches)) {
	    $source_type = $matches[2];
	    $a = str_replace("source type".$matches[1].$matches[2],"taxonomy".$matches[1]."'".$source_type."-0-0'",$a);
	}
	if (preg_match("/plugin(\!?\=)(.+)/", $atom, $matches)) {
	    $plugin_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
	    $query = "select id from plugin where name like '" . $plugin_name . "%' order by id";
	    if (!$rs = & $conn->Execute($query)) {
	        print $conn->ErrorMsg();
	        exit();
	    }
	    if ($plugin_id = $rs->fields["id"] != "") {
	        $plugin_id = $rs->fields["id"];
	    } else {
	        $plugin_id = $matches[2];
	    }
	    $a = str_replace("plugin".$matches[1].$matches[2],"plugin_id".$matches[1]."'".$plugin_id."'",$a);
	}
	if (preg_match("/sensor(\!?\=)(\S+)/", $atom, $matches)) {
	    $plugin_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
	    $plugin_name = str_replace("'","",$plugin_name);
	    $query = "select ip from sensor where name like '" . $plugin_name . "%'";
	    if (!$rs = & $conn->Execute($query)) {
	        print $conn->ErrorMsg();
	        exit();
	    }
	    if ($rs->fields["ip"] != "") {
	        $sensor_ip = $rs->fields["ip"];
	    } else {
	        $sensor_ip = $matches[2];
	    }
	    $a = str_replace("sensor".$matches[1].$matches[2],"sensor".$matches[1].$sensor_ip,$a);
	}
	if (preg_match("/(source|destination)(\!?\=)(\S+)/", $atom, $matches)) {
	    $field = $matches[1];
	    $field = str_replace("source","src",$field);
	    $field = str_replace("destination","dst",$field);
		$op = $matches[2];
	    $name = $matches[3];
	    if ($netnames[$name] != "") {
	    	$resolv = $netnames[$name];
	    	$field .= "_net";
	    } else {
	    	$resolv = ($hostnames[$name] != "") ? $hostnames[$name] : $name;
	    	$field .= "_ip";
	    }
		$a = str_replace($matches[1].$matches[2].$matches[3],$field.$op.$resolv,$a);
	}
}

$_SESSION["forensic_query"] = $a;
$_SESSION["forensic_start"] = $start;
$_SESSION["forensic_end"] = $end;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">

<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../js/jquery.progressbar.min.js"></script>
<style type="text/css">
.level11  {  background:url(../pixmaps/statusbar/level11.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level10  {  background:url(../pixmaps/statusbar/level10.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level9  {  background:url(../pixmaps/statusbar/level9.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level8  {  background:url(../pixmaps/statusbar/level8.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level7  {  background:url(../pixmaps/statusbar/level7.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level6  {  background:url(../pixmaps/statusbar/level6.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level5  {  background:url(../pixmaps/statusbar/level5.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level4  {  background:url(../pixmaps/statusbar/level4.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level3  {  background:url(../pixmaps/statusbar/level3.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level2  {  background:url(../pixmaps/statusbar/level2.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level1  {  background:url(../pixmaps/statusbar/level1.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level0  {  background:url(../pixmaps/statusbar/level0.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.tag_cloud { padding: 3px; text-decoration: none; }
.tag_cloud:link  { color: #17457C; }
.tag_cloud:visited { color: #17457C; }
.tag_cloud:hover { color: #ffffff; background: #17457C; }
.tag_cloud:active { color: #ffffff; background: #ACFC65; }
a {
	font-size:10px;
}
</style>
</head>
<body>
<?php
$time1 = microtime(true);
$cmd = process($a, $start, $end, $offset, $sort_order, "logs", $uniqueid, $numResult, 1);
$user = $_SESSION["_user"];
?>
<div id="loading" style="position:absolute;top:0;left:30%">
	<table class="noborder" style="background-color:white">
		<tr>
			<td class="nobborder" style="text-align:center">
				<span class="progressBar" id="pbar"></span>
			</td>
			<td class="nobborder" id="progressText" style="text-align:center;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
			<td><input type="button" onclick="parent.KillProcess()" class="button" value="<?php echo _("Stop") ?>"></input></td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	$("#pbar").progressBar();
	$("#pbar").progressBar(1);
</script>
<?php
//$status = exec($cmd, $result);
$result = array();
//echo "$cmd $user";exit;

if($debug_log!=""){
	$handle = fopen($debug_log, "a+");
	fputs($handle,"============================== PROCESS.php ".date("Y-m-d H:i:s")." ==============================\n");
	fputs($handle,"PROCESS.php: $cmd '$user' '".$debug_log."'\n");
	fclose($handle);
}

// LOCAL OR REMOTE fetch
if (is_array($_SESSION['logger_servers']) && (count($_SESSION['logger_servers']) > 1 || (count($_SESSION['logger_servers']) == 1 && !$_SESSION['logger_servers']['local']))) {
	$cmd = str_replace("fetchall.pl","fetchremote.pl",$cmd);
	$servers_string = "";
	foreach ($_SESSION['logger_servers'] as $key=>$val) {
		$servers_string .= ($servers_string != "") ? ",".$val : $val;
	}
	echo "$cmd '$user' $servers_string 2>>/dev/null";exit;
	$fp = popen("$cmd '$user' $servers_string 2>>/dev/null", "r");
} else {
	$fp = popen("$cmd '$user' '".$_GET['debug_log']."' 2>>/dev/null", "r");
}

$perc = 1;
$ndays = dateDiff($start,$end);
if ($ndays < 1) $ndays = 1;
$inc = 100/$ndays;
$num_lines = 0;
while (!feof($fp)) {
    $line = trim(fgets($fp));
    if ($line != "") $result[] = $line;
	if (preg_match("/Searching in (\d\d\d\d\d\d\d\d)/",$line,$found)) {
    	ob_flush();
		flush();
		$sdate = date("d F Y",strtotime($found[1]));
    	?><script type="text/javascript">$("#pbar").progressBar(<?php echo floor($perc) ?>);$("#progressText").html('Searching <b>events</b> in <?php echo $sdate?>...');</script><?php
    	$perc += $inc;
    	if ($perc > 100) $perc = 100;
    } elseif ($line != "") { $num_lines++; }
}
?><script type="text/javascript">$("#loading").hide();</script><?php
fclose($fp);
$time2 = microtime(true);
$totaltime = round($time2 - $time1, 2);
//print "</td><td class=\"nobborder\" width=\"10\">&nbsp;</td><td class=\"nobborder\" style=\"text-align:right;\" nowrap>"._("Parsing time").": <b>$totaltime</b> "._("seconds").".</td></tr></table>";
//$num_lines = get_lines($a, $start, $end, $offset, $sort_order, "logs", $uniqueid);

// Avoid graphs being drawn with more than 100000 events
if ($num_lines > 500000) {
?>
	<script>
	document.getElementById('too_many_events').style.display = 'block';
	document.getElementById('test').style.display = 'none';
	</script>
<?php
}
?>
<div id="processcontent" style="display:none">
<?php
print "<table width=\"100%\" class=\"noborder\" style=\"background-color:transparent;\"><tr><td class=\"nobborder\" nowrap>";
echo '<img src="../pixmaps/arrow_green.gif">';
print _("Time Range").": <b>$start <-> $end</b>";
print "</td><td class=\"nobborder\" width=\"10\">&nbsp;</td><td class=\"nobborder\" style=\"text-align:right;\" nowrap>"._("Parsing time").": <b>$totaltime</b> "._("seconds").".</td></tr></table>";
$alt = 0;
print "<center>\n";
if ($offset != 0 && $num_lines > 0) {
?>
<a href="javascript:DecreaseOffset(50);"><?php echo "<< "._("Previous 50") ?></a>
<?php
}
if ($num_lines > 50) { //if($num_lines > $offset + 50){
    echo ($offset != 0) ?  "&nbsp;|&nbsp;" : "";
?>
<a href="javascript:IncreaseOffset(50);"><?php echo _("Next 50")." >>" ?></a>
<?php
}
print "</center>\n";
print "<table class='transparent' style='border: 1px solid rgb(170, 170, 170);border-radius: 0px; -moz-border-radius: 0px; -webkit-border-radius: 0px;' width='100%' cellpadding='2' cellspacing='0'>";
print "<tr height=\"35\"><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("ID") . "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>";
print "<a href=\"javascript:DateAsc()\"><img src=\"../forensics/images/order_sign_a.gif\" border=\"0\"></a>";
print " " . _("Date") . " ";
print "<a href=\"javascript:DateDesc()\"><img src=\"../forensics/images/order_sign_d.gif\" border=\"0\"></a>";
print "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Type");
print "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Sensor") . "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Source") . "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Dest") . "</td><td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Data") . "</td><td class='plfieldhdr' style='border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'>" . _("Signature") . "</td></tr>";
$color_words = array(
    'warning',
    'error',
    'failure',
    'break',
    'critical',
    'alert'
);
$inc_counter = 1 + $offset;
$cont = 0;

// Output file TXT
if (isset($export)) {
	if (is_dir("/var/ossim/logs/searches")) {
		// dir
		$outdir = "/var/ossim/logs/searches/$user"."_"."$start"."_"."$end"."_"."$sort_order"."_".str_replace("/","_slash_",$a);
		if (!is_dir($outdir)) mkdir($outdir);
		$outfilename = $outdir."/results.txt";
		// file
		if ($offset > 0 && file_exists($outfilename)) {
			$outfile = fopen($outfilename,"a");
			$loglist = fopen($outdir."/loglist.txt","a");
		}
		else {
			$outfile = fopen($outfilename,"w");
			$loglist = fopen($outdir."/loglist.txt","w");
		}
		$logarr = array();
	}
}
$colort = 0;

$htmlResult=true;
foreach($result as $res) if ($cont++ < $numResult) {
    if ($cont > 50){
        $htmlResult=false;
    }
    $res = str_replace("<", "", $res);
    $res = str_replace(">", "", $res);
    //entry id='2' fdate='2008-09-19 09:29:17' date='1221816557' plugin_id='4004' sensor='192.168.1.99' src_ip='192.168.1.119' dst_ip='192.168.1.119' src_port='0' dst_port='0' data='Sep 19 02:29:17 ossim sshd[2638]: (pam_unix) session opened for user root by root(uid=0)'
    // para coger
    if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='[^']+'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $res, $matches)) {
    // fin para coger
        $lf = explode(";", $res);
        // para coger
        $logfile = urlencode(end($lf));
        // fin paga coger
        if($htmlResult){
            $data = $matches[10];
            $signature = $matches[12];
            $query = "select name from plugin where id = " . intval($matches[4]);
            if (!$rs = & $conn->Execute($query)) {
                print $conn->ErrorMsg();
                exit();
            }
        }
        // para coger
        $plugin = htmlspecialchars($rs->fields["name"]);
        if ($plugin == "") {
            $plugin = intval($matches[4]);
        }
        // fin para coger
        if($htmlResult){
            $red = 0;
            $color = "black";
        }
        // para coger
        $date = $matches[2];
        // fin para coger
        if($htmlResult){
            $sensor = $matches[5];
            $src_ip = $matches[6];
            $country = strtolower(geoip_country_code_by_addr($gi, $src_ip));
            $country_name = geoip_country_name_by_addr($gi, $src_ip);
            if ($country) {
                $country_img_src = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            } else {
                $country_img_src = "";
            }
                    $dst_ip = $matches[7];
                    $country = strtolower(geoip_country_code_by_addr($gi, $dst_ip));
            $country_name = geoip_country_name_by_addr($gi, $dst_ip);
            if ($country) {
                $country_img_dst = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            } else {
                $country_img_dst = "";
            }

                    $homelan_src = (Net::isIpInNet($src_ip, $networks) || in_array($src_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
                    $homelan_dst = (Net::isIpInNet($dst_ip, $networks) || in_array($dst_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";

            $src_port = $matches[8];
            $dst_port = $matches[9];
                    // resolv hostname
                    $sensor_name = ($sensors[$sensor]!="") ? $sensors[$sensor] : $sensor;
                    $src_ip_name = ($sensors[$src_ip]!="") ? $sensors[$src_ip] : (($hosts[$src_ip]!="") ? $hosts[$src_ip] : $src_ip);
                    $dst_ip_name = ($sensors[$dst_ip]!="") ? $sensors[$dst_ip] : (($hosts[$dst_ip]!="") ? $hosts[$dst_ip] : $dst_ip);

                    $src_div = "<div id=\"$src_ip;$src_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";
                    $dst_div = "<div id=\"$dst_ip;$dst_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";

            $line = "<tr".(($colort%2==0) ? " style=\"background-color: #F2F2F2\"" : "").">
            <td style='border-right:1px solid #FFFFFF;text-align:center;' nowrap>" . "<a href=\"../incidents/newincident.php?" . "ref=Alarm&" . "title=" . urlencode($plugin . " Event") . "&" . "priority=1&" . "src_ips=$src_ip&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . "\">" . "<img src=\"../pixmaps/incident.png\" width=\"12\" alt=\"i\" border=\"0\"/></a> " . $inc_counter . "</td>
            <td style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>" . htmlspecialchars($matches[2]) . "</td>";
            //$line.= "<td><font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color'; this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('plugin=' + this.innerHTML)\"\">$plugin</span></td>";
        $line.= "<td style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'><a href=\"#\" onclick=\"javascript:SetSearch('<b>plugin</b>=' + this.innerHTML)\"\">$plugin</a></td>";
            $line.="<td style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'>";
            //$line.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('src_ip=' + this.innerHTML)\"\">" . htmlspecialchars($sensor_name) . "</span></td><td nowrap>$src_div";
            //$line.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('src_ip=' + this.innerHTML)\"\">" . htmlspecialchars($src_ip_name) . "</span></div>:";
            //$line.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('src_port=' + this.innerHTML)\"\">" . htmlspecialchars($matches[8]) . "</span>$country_img_src</td><td nowrap>$dst_div";
            //$line.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('dst_ip=' + this.innerHTML)\"\">" . htmlspecialchars($dst_ip_name) . "</span></div>:";
            //$line.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green'; this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.forms[0].cursor.value;\" onclick=\"javascript:SetSearch('dst_port=' + this.innerHTML)\"\">" . htmlspecialchars($matches[9]) . "</span>$country_img_dst</td>";

            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>sensor</b>=$sensor_name')\"\">" . htmlspecialchars($sensor_name) . "</a></td><td style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$src_div";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>source</b>=$src_ip')\"\">" . htmlspecialchars($src_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\">" . htmlspecialchars($matches[8]) . "</a>$country_img_src $homelan_src</td><td style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$dst_div";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>destination</b>=$dst_ip')\"\">" . htmlspecialchars($dst_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\">" . htmlspecialchars($matches[9]) . "</a>$country_img_dst $homelan_dst</td>";
            if ($alt) {
                $color = "grey";
                $alt = 0;
            } else {
                $color = "blue";
                $alt = 1;
            }
            $verified = - 1;
            $data = $matches[10];
            if ($signature != '') {
                $sig_dec = base64_decode($signature);
                $pub_key = openssl_get_publickey($config["pubkey"]); // openssl_pkey_get_public
                $verified = openssl_verify($data, $sig_dec, $pub_key);
                //error_log("$data\n$signature\n", 3, "/tmp/validate");
            }
            $encoded_data = base64_encode($data);
            $data = "<td style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;'>";
        }
        // para coger
		$data_out = "";
        // fin para coger
        // change ,\s* or #\s* adding blank space to force html break line
        // para coger
        $matches[10] = preg_replace("/(\,|\#)\s*/", "\\1 ", $matches[10]);
        // fin para coger
        if($htmlResult){
		foreach(split("[\| \t;:]", $matches[10]) as $piece) {
                    $clean_piece = str_replace("(", " ", $piece);
                    $clean_piece = str_replace(")", " ", $clean_piece);
                    $clean_piece = str_replace("[", " ", $clean_piece);
                    $clean_piece = str_replace("]", " ", $clean_piece);
                                $clean_piece = htmlspecialchars($clean_piece);
                    $red = 0;
                    foreach($color_words as $word) {
                        if (stripos($clean_piece, $word)) {
                            $red = 1;
                            break;
                        }
                    }
                    if ($red) {
                        $data.= "<font color=\"red\"><span onmouseover=\"this.style.color = 'green';this.style.cursor='pointer';\" onmouseout=\"this.style.color = 'red';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:SetSearch('data=" . $clean_piece . "')\"\">" . $clean_piece . " </span>";
                    } else {
                        $data.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green';this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:SetSearch('data=" . $clean_piece . "')\"\">" . $clean_piece . " </span>";
                    }
                }
                if ($verified >= 0) {
                    if ($verified == 1) {
                        $data.= '<img src="' . $config["verified_graph"] . '" height=15 width=15 title="Valid" />';
                    } else if ($verified == 0) {
                        $data.= '<img src="' . $config["failed_graph"] . '" height=15 width=15 title="Wrong" />';
                    } else {
                        $data.= '<img src="' . $config["error_graph"] . '" height=15 width=15 title="Error" />';
                        $data.= openssl_error_string();
                    }
                }
        }
        // para coger
		$data_out = $matches[10];
        // fin para coger
        if($htmlResult){
            $data.= '</td><td style="text-align:center;padding-left:5px;padding-right:5px;" nowrap><a href="validate.php?log=' . urlencode($encoded_data) . '&start=' . $start . '&end=' . $end . '&logfile=' . $logfile . '&signature=' . urlencode($signature) . '"  class="thickbox" rel="AjaxGroup" onclick="GB_show(\''._("Validate signature").'\',this.href,300,600);return false"><img src="../pixmaps/lock-small.png" border=0><i>'._("Validate").'</i></a>';
            $data.= "</td>";
            $line.= $data;
        }
        // para coger
        $inc_counter++;
        // fin para coger

		if (is_dir("/var/ossim/logs/searches") && isset($export)) {
			fputs($outfile,"$inc_counter,$date,$plugin,".htmlspecialchars($matches[5]).",".htmlspecialchars($matches[6]).":".htmlspecialchars($matches[8]).",".htmlspecialchars($matches[7]).":".htmlspecialchars($matches[9]).",$data_out\n");
			$logarr[urldecode($logfile)]++;
		}
    }
    if($htmlResult){
        print $line;
        $colort++;
    }
}
print "</table>";

if (is_dir("/var/ossim/logs/searches") && isset($export)) {
	fclose ($outfile);
	$logs = "";
	foreach ($logarr as $key=>$val) {
		$logs .= $key."\n";
	}
	fputs($loglist,$logs);
	fclose ($loglist);
}

if ($num_lines == 0) {
    print "<center><font style='color:red;font-size:14px'><br>"._("No Data Found Matching Your Criteria")."</center>";
}
print "<center>\n";
if ($offset != 0 && $num_lines > 0) {
?>
<a href="javascript:DecreaseOffset(50);"><?php echo "<< "._("Previous 50") ?></a>
<?php
}
if ($num_lines > 50) { //if($num_lines > $offset + 50){
    echo ($offset != 0) ?  "&nbsp;|&nbsp;" : "";
?>
<a href="javascript:IncreaseOffset(50);"><?php echo _("Next 50")." >>" ?></a>
<?php
}

?>
</center>
<br>
</div>
</body>
<script type="text/javascript">$("#pbar").progressBar(100);parent.SetFromIframe($("#processcontent").html())</script>
<?php 
ob_end_flush();
?>