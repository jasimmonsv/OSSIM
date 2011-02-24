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
ob_implicit_flush();
set_time_limit(300);
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ("classes/Host.inc");
require_once ("classes/Net.inc");
require_once ("classes/Util.inc");
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
function has_results($num_lines) {
	foreach ($num_lines as $server=>$num) {
		if ($num > 0) return 1;
	}
	return 0;
}
function background_task($path_dir) {
	// Prepare background task
	$server_ip=trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
	$https=trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
	$server='http'.(($https=="yes") ? "s" : "").'://'.$server_ip.'/ossim';
	$rnd = date('YmdHis').rand();
	$cookieFile= "$path_dir/cookie";
	$tmpFile= "$path_dir/bgt";
	file_put_contents($cookieFile,"#\n$server_ip\tFALSE\t/\tFALSE\t0\tPHPSESSID\t".session_id()."\n");
	$url = $server.'/sem/process.php?'.str_replace("exportEntireQuery","exportEntireQueryNow",$_SERVER["QUERY_STRING"]);
	$wget = "wget -q --no-check-certificate --cookies=on --keep-session-cookies --load-cookies='$cookieFile' '$url' -O -";
	exec("$wget > '$tmpFile' 2>&1 & echo $!");
}

include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$config = parse_ini_file("everything.ini");
$a = GET("query");

//$export = (GET('txtexport') == "true") ? 1 : 0;
$export = GET('txtexport');
$top = GET('top');

if ($export=='stop') {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">
</head>
<body topmargin="0">
<p style="text-align:center;margin:0px;font-weight:bold"><?php echo _("Process Stopped!") ?></p>
</body>
</html>
<?
exit;
}

$offset = GET("offset");
if (intval($offset) < 1) {
    $offset = 0;
}
$start = GET("start");
$end = GET("end");
$sort_order = GET("sort");
$uniqueid = GET("uniqueid");
$tzone = intval(GET("tzone"));

$debug_log = GET("debug_log");
ossim_valid($debug_log, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_SLASH, 'illegal:' . _("debug_log"));
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($offset, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("offset"));
ossim_valid($top, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("top"));
ossim_valid($a, OSS_TEXT, OSS_NULLABLE, '[', ']', 'illegal:' . _("a"));
ossim_valid($sort_order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("sort order"));
ossim_valid($uniqueid, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("uniqueid"));
if (ossim_error()) {
    die(ossim_error());
}

$start_query = $start;
$end_query = $end;

if ($tzone!=0) {
	$start = date("Y-m-d H:i:s",strtotime($start)+(-3600*$tzone));
	$end = date("Y-m-d H:i:s",strtotime($end)+(-3600*$tzone));
}	

$db = new ossim_db();
$conn = $db->connect();

$sensors = $hosts = $logger_servers = array(); $hostnames = array(); $sensornames = array();
list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
//$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array(); $netnames = array();
foreach ($_nets as $_net) { $_nets_ips[] = $_net->get_ips(); $netnames[$_net->get_name()] = $_net->get_ips(); }
foreach ($hosts as $ip=>$name) { $hostnames[$name] = $ip; }
foreach ($sensors as $ip=>$name) { $sensornames[$name] = $ip; }
//$networks = implode(",",$_nets_ips);
$hosts_ips = array_keys($hosts);

if ($a != "" && !preg_match("/\=/",$a)) { // Search in data field
	$a = "data='".$a."'";
}

if (preg_match("/(.*?)=(.*)/",$a,$fnd)) {
    $a = preg_replace("/(\|)/","\\1".$fnd[1]."=",$a);
}
// Patch "sensor=A OR sensor=B"
$a = preg_replace("/SPACESCAPEORSPACESCAPE([a-zA-Z\_]+)\=/"," or \\1=",$a);

$atoms = explode("|",preg_replace("/ (and|or) /i","|",$a));

foreach ($atoms as $atom) {
    $atom = trim($atom);
	$atom = str_replace("src_ip=","src=",$atom);
	$atom = str_replace("dst_ip=","dst=",$atom);
	if (preg_match("/sourcetype(\!?\=)(.+)/", $atom, $matches)) {
	    $source_type = $matches[2];
	    $a = str_replace("sourcetype".$matches[1].$matches[2],"taxonomy".$matches[1]."'".$source_type."-0-0'",$a);
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
	    $sensor_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
	    $sensor_name = str_replace("'","",$sensor_name);
	    $query = "select ip from sensor where name like '" . $sensor_name . "'";
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
	if (preg_match("/(src|dst)(\!?\=)(\S+)/", $atom, $matches)) {
	    $field = $matches[1];
		$op = $matches[2];
	    $name = $matches[3];
	    if ($netnames[$name] != "") {
	    	$resolv = $netnames[$name];
	    	$field .= "_net";
	    } else {
	    	$resolv = ($sensornames[$name]!="") ? $sensornames[$name] : (($hostnames[$name]!="") ? $hostnames[$name] : $name);
	    	$field .= "_ip";
	    }
		$a = str_replace($matches[1].$matches[2].$matches[3],$field.$op.$resolv,$a);
	}
}

$_SESSION["forensic_query"] = $a;
$_SESSION["forensic_start"] = $start;
$_SESSION["forensic_end"] = $end;

$user = $_SESSION["_user"];

if($export=='exportEntireQuery') {
	$outdir = $config["searches_dir"].$user."_"."$start"."_"."$end"."_"."$sort_order"."_".base64_encode($a);
	if(strlen($outdir) > 255) {
		$outdir = substr($outdir,0,255);
	}
	if (!is_dir($outdir)) mkdir($outdir);
	background_task($outdir);
	unset($export); // continues normal execution
}

if($export=='exportEntireQueryNow') {
    set_time_limit(3600);
    $save = $_SESSION;
    session_write_close();
    $_SESSION = $save;
    $top = (intval($config["max_export_events"])>0) ? $config["max_export_events"] : 250000;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">

<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
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
$cmd = process($a, $start, $end, $offset, $sort_order, "logs", $uniqueid, $top, 1);

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
if (is_array($_SESSION['logger_servers']) && (count($_SESSION['logger_servers']) > 1 || (count($_SESSION['logger_servers']) == 1 && reset($_SESSION['logger_servers']) != "127.0.0.1"))) {
	$from_remote = 1;
	$cmd = str_replace("perl fetchall.pl","sudo ./fetchremote.pl",$cmd);
	$servers_string = "";
	$num_servers = 0;
	?><div id="loading" style="position:absolute;top:0;left:30%"><table class="noborder" style="background-color:white"><?php
	foreach ($_SESSION['logger_servers'] as $key=>$val) {
		$servers_string .= ($servers_string != "") ? ",".$val : $val;
		$logger_servers[$val] = $key;
		$num_servers++;
		?>
		<tr>
			<td><span class="progressBar" id="pbar_<?php echo $key ?>"></span></td>
			<td valign="top" class="nobborder" id="progressText_<?php echo $key ?>" style="text-align:left;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
			<script type="text/javascript">
				$("#pbar_<?php echo $key ?>").progressBar();
				$("#pbar_<?php echo $key ?>").progressBar(1);
			</script>
		</tr>
		<?php
	}
	?>	<tr>
			<td colspan="2" style="text-align:center;padding-top:5px"><input type="button" onclick="parent.KillProcess()" class="button" value="<?php echo _("Stop") ?>"></input></td>
		</tr>
		</table>
	</div><script type="text/javascript">parent.resize_iframe();</script><?php
	//echo "$cmd '$user' $servers_string 2>>/dev/null";exit;
	$fp = popen("$cmd '$user' $servers_string 2>>/dev/null", "r");
} else {
	?>
	<div id="loading" style="position:absolute;top:0;left:30%">
		<table class="noborder" style="background-color:white">
			<tr>
				<td class="nobborder" style="text-align:center">
					<span class="progressBar" id="pbar_local"></span>
				</td>
				<td class="nobborder" id="progressText_local" style="text-align:center;padding-left:5px"><?=gettext("Loading data. Please, wait a few seconds...")?></td>
				<td><input type="button" onclick="parent.KillProcess()" class="button" value="<?php echo _("Stop") ?>"></input></td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
		$("#pbar_local").progressBar();
		$("#pbar_local").progressBar(1);
	</script>
	<?php
	foreach ($_SESSION['logger_servers'] as $key=>$val) {
		$logger_servers[$val] = $key;
	}
	$from_remote = 0;
	$num_servers = 1;
	$fp = popen("$cmd '$user' '".$_GET['debug_log']."' 2>>/dev/null", "r");
}
$perc = array();
$ndays = dateDiff($start,$end);
if ($ndays < 1) $ndays = 1;
$inc = 100/$ndays;
$num_lines = array(); // Number of lines for each logger server
$current_server = ($from_remote) ? "" : "local";
$server_bcolor = $server_fcolor = array();
$cont = 0;
$has_next_page = 0;
while (!feof($fp)) {
    $line = trim(fgets($fp));
	// Remote connect message
    /*
    if (preg_match("/^Connecting (.+)/",$line,$found)) {
    	$current_server = ($logger_servers[$found[1]] != "") ? $logger_servers[$found[1]] : $found[1];
    	$server_bcolor[$current_server] = $_SESSION['logger_colors'][$current_server]['bcolor'];
    	$server_fcolor[$current_server] = $_SESSION['logger_colors'][$current_server]['fcolor'];
    	$cont++;
    }
    */
	// Searching message
    if (preg_match("/^Searching in (\d\d\d\d\d\d\d\d) from (\d+\.\d+\.\d+\.\d+)/",$line,$found)) {
    	ob_flush();
		flush();
		$sdate = date("d F Y",strtotime($found[1]));
		$current_server = ($logger_servers[$found[2]] != "") ? $logger_servers[$found[2]] : $found[2];
		if (!$from_remote) $current_server = "local";
		if ($perc[$current_server] == "") { $perc[$current_server] = 1; }
		$from_str = ($from_remote) ? " from <b>".$current_server."</b>" : ""; 
    	?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(<?php echo floor($perc[$current_server]) ?>);$("#progressText_<?php echo $current_server ?>").html('Searching <b>events</b> in <?php echo $sdate?><?php echo $from_str ?>...');</script><?php
    	$perc[$current_server] += $inc;
    	if ($perc[$current_server] >= 100 || $num_lines[$current_server] >= $top) {
    		?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(100);$("#progressText_<?php echo $current_server ?>").html('All done <?php echo $from_str ?>...');</script><?php
    		$perc[$current_server] = 100;
    	}
    // Event line
    } elseif (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'/",$line,$found)) {
    	$fields = explode(";",$line);
    	$current_server = ($logger_servers[trim($fields[count($fields)-1])] != "") ? $logger_servers[trim($fields[count($fields)-1])] : trim($fields[count($fields)-1]);
    	$event_date = preg_replace("/\s|\-/","",$found[2]);
    	$num_lines[$current_server]++;
    	if ($num_lines[$current_server] <= $top) {
    		$result[$line] = $event_date;
    	} else {
    		$has_next_page = 1;
    	}
    }
	if ($num_lines[$current_server] >= $top) {
    	?><script type="text/javascript">$("#pbar_<?php echo $current_server ?>").progressBar(100);$("#progressText_<?php echo $current_server ?>").html('All done <?php echo $from_str ?>...');</script><?php
    	$perc[$current_server] = 100;
    }
}

// Order only if remote fetch
if ($from_remote) {
	arsort($result);
}

?><script type="text/javascript">$("#loading").hide();</script><?php
fclose($fp);
$time2 = microtime(true);
$totaltime = round($time2 - $time1, 2);
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
$txtzone = Util::timezone($tz);
?>
<div id="processcontent" style="display:none">
<?php if (has_results($num_lines)) { ?>
<table width="100%" class="noborder" style="background-color:transparent;">
	<tr>
		<td width="20%" class="nobborder" nowrap><img src="../pixmaps/arrow_green.gif" align="absmiddle"><?php print _("Time Range").": <b>$start_query <-> $end_query</b>" ?></td>
		<td class="center nobborder">
			<?php if ($from_remote) { ?>
			<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+$top)."</b>"._(" <b>first</b> events")._(" for <b>each server</b>")." (<b>".(($offset*$num_servers)+1)."</b> - <b>".(($offset*$num_servers)+count($result))."</b> total)" ?>.&nbsp;
			<?php } else { ?>
			<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+count($result))."</b>"._(" events") ?>.&nbsp;
			<?php } ?>
			<?php if ($offset > 0) { ?>
			<a href="javascript:DecreaseOffset(<?php echo GET('top')?>);"><?php echo ($from_remote) ? "<< "._("Fetch the previous ") : "<< "._("Previous ")?><?php echo "<b>".GET('top')."</b>" ?></a>
			<?php } ?>
			<?php if ($has_next_page) { //if($num_lines > $offset + 50){
			    echo ($offset != 0) ?  "&nbsp;<b>|</b>&nbsp;" : "";
			?>
			<a href="javascript:IncreaseOffset(<?php echo GET('top')?>);"><?php echo ($from_remote) ? _("Fetch the next ") : _("Next ")?><?php echo "<b>".GET('top')."</b> >>" ?></a>
			<?php } ?>
		</td>
		<td width="20%" class="nobborder" style="text-align:right;" nowrap><?php echo _("Parsing time").": <b>$totaltime</b> "._("seconds") ?></td>
	</tr>
</table>

<table class='transparent' style='border: 1px solid rgb(170, 170, 170);border-radius: 0px; -moz-border-radius: 0px; -webkit-border-radius: 0px;' width='100%' cellpadding='5' cellspacing='0'>
	<tr height="35">
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("ID") ?></td>
		<?php if ($from_remote) { ?>
		<td class='plfieldhdr' style='padding-left:3px;padding-right:3px;border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Server") ?></td>
		<?php } ?>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;' nowrap>
			<a href="javascript:DateAsc()"><img src="../forensics/images/order_sign_a.gif" border="0"></a><?php print " " . _("Date") . " $txtzone " ?>
			<a href="javascript:DateDesc()"><img src="../forensics/images/order_sign_d.gif" border="0"></a>
		</td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Type") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Sensor") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Source") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Dest") ?></td>
		<td class='plfieldhdr' style='border-right: 1px solid rgb(170, 170, 170);border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Data") ?></td>
		<td class='plfieldhdr' style='border-bottom: 1px solid rgb(170, 170, 170); background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 50% 50%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: rgb(34, 34, 34); font-size: 12px; font-weight: bold;'><?php echo _("Signature") ?></td>
	</tr>
<?php

// Output file TXT
if (isset($export) && $export != "noExport") {
	if (is_dir($config["searches_dir"])) {
		// dir
		$outdir = $config["searches_dir"].$user."_"."$start"."_"."$end"."_"."$sort_order"."_".base64_encode($a);
		if (!is_dir($outdir)) mkdir($outdir);
		$outfilename = $outdir."/results.txt";
		// file
		if ($offset > 0 && file_exists($outfilename)) {
			$outfile = fopen($outfilename,"a");
			$loglist = fopen($outdir."/loglist.txt","a");
		}
		else {
			$outfile = fopen($outfilename,"w"); fclose($outfile); $outfile = fopen($outfilename,"w");
			$loglist = fopen($outdir."/loglist.txt","w");
		}
		$logarr = array();
	}
}

// RESULTS Main Loop
$color_words = array(
    'warning',
    'error',
    'failure',
    'break',
    'critical',
    'alert'
);
$inc_counter = 1 + $offset;
$total_counter = 1 + $offset*$num_servers;
$cont = array(); // Counter for each logger server
$colort = 0;
$alt = 0;
$htmlResult=true;
foreach($result as $res=>$event_date) {
    //entry id='2' fdate='2008-09-19 09:29:17' date='1221816557' plugin_id='4004' sensor='192.168.1.99' src_ip='192.168.1.119' dst_ip='192.168.1.119' src_port='0' dst_port='0' data='Sep 19 02:29:17 ossim sshd[2638]: (pam_unix) session opened for user root by root(uid=0)'
	if (preg_match("/entry id='([^']+)'\s+fdate='([^']+)'\s+date='([^']+)'\s+plugin_id='([^']+)'\s+sensor='([^']+)'\s+src_ip='([^']+)'\s+dst_ip='([^']+)'\s+src_port='([^']+)'\s+dst_port='([^']+)'\s+tzone='([^']+)'+\s+data='([^']+)'(\s+sig='([^']*)')?/", $res, $matches)) {
		$lf = explode(";", $res);
        $logfile = $lf[count($lf)-3];
        $current_server = urlencode($lf[count($lf)-1]);
        $current_server_ip = $current_server;
        $current_server = $logger_servers[$current_server];
		if ($cont[$current_server] == "") $cont[$current_server] = 1;
		if ($cont[$current_server] > $num_lines[$current_server] || $cont[$current_server] > $top*$num_servers){
	        $htmlResult = false;
	    } else {
	    	$htmlResult = ($export=='exportEntireQueryNow') ? false : true;
	    }
	    
	    $res = str_replace("<", "", $res);
	    $res = str_replace(">", "", $res);
    
        if($htmlResult){
            $data = $matches[11];
            $signature = $matches[13];
            $query = "select name from plugin where id = " . intval($matches[4]);
            if (!$rs = & $conn->Execute($query)) {
                print $conn->ErrorMsg();
                exit();
            }
        }
        // para coger
        $plugin = Util::htmlentities($rs->fields["name"]);
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
        $event_date = $matches[2];
        $tzone = intval($matches[10]);
        $txtzone = Util::timezone($tzone);

        // Special case: old events
        $eventhour = date("H",strtotime($date));
        $ctime = explode("/",$logfile); $storehour = $ctime[count($ctime)-3]; // hours
        $warning = ($storehour-$eventhour != 0) ? "<a href='javascript:;' txt='"._("Date may not be normalized")."' class='scriptinfotxt'><img src='../pixmaps/warning.png' border=0 style='margin-left:3px;margin-right:3px'></a>" : "";
        
        // Event date timezone
		if ($tzone!=0) $event_date = date("Y-m-d H:i:s",strtotime($event_date)+(3600*$tzone));
        
        // Apply user timezone
		if ($tz!=0) $date = date("Y-m-d H:i:s",strtotime($date)+(3600*$tz));
	
		//echo "$date - $event_date - $tzone - $tz<br>";
		
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

                    $homelan_src = (Net::is_ip_in_cache_cidr($conn, $src_ip) || in_array($src_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$src_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";
                    $homelan_dst = (Net::is_ip_in_cache_cidr($conn, $dst_ip) || in_array($dst_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$dst_ip'><img src=\"../forensics/images/homelan.png\" border=0></a>" : "";

            $src_port = $matches[8];
            $dst_port = $matches[9];
                    // resolv hostname
                    $sensor_name = ($sensors[$sensor]!="") ? $sensors[$sensor] : $sensor;
                    $src_ip_name = ($sensors[$src_ip]!="") ? $sensors[$src_ip] : (($hosts[$src_ip]!="") ? $hosts[$src_ip] : $src_ip);
                    $dst_ip_name = ($sensors[$dst_ip]!="") ? $sensors[$dst_ip] : (($hosts[$dst_ip]!="") ? $hosts[$dst_ip] : $dst_ip);

                    $src_div = "<div id=\"$src_ip;$src_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";
                    $dst_div = "<div id=\"$dst_ip;$dst_ip_name\" class=\"HostReportMenu\" style=\"display:inline\">";

            $line = "<tr".(($colort%2==0) ? " style=\"background-color: #F2F2F2\"" : "").">
            <td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;' nowrap>" . $warning . "<a href=\"../incidents/newincident.php?" . "ref=Alarm&" . "title=" . urlencode($plugin . " Event") . "&" . "priority=1&" . "src_ips=$src_ip&" . "event_end=$date&" . "src_ports=$src_port&" . "dst_ips=$dst_ip&" . "dst_ports=$dst_port" . "\">" . "<img src=\"../pixmaps/incident.png\" width=\"12\" alt=\"i\" border=\"0\"/></a> " . $total_counter . "</td>";
            if ($from_remote) {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;' nowrap><table class='transparent' align='center'><tr><td class='nobborder' style='padding:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:#".$_SESSION['logger_colors'][$current_server]['bcolor'].";color:#".$_SESSION['logger_colors'][$current_server]['fcolor']."'>$current_server</td></tr></table></td>";
            }
            
            // compare real date with timezone corrected date
			if ($event_date==$matches[2] || $event_date==$date) {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>" . Util::htmlentities($date) . "</td>";
			} else {
            	$line .= "<td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap> <a href='javascript:;' txt='" ._("Event date").": ". Util::htmlentities("<b>".$event_date."</b><br>"._("Timezone").": <b>$txtzone</b>") . "' class='scriptinfotxt' style='text-decoration:none'>" . Util::htmlentities($date) . "</a></td>";
			}
			
       		$line.= "<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'><a href=\"#\" onclick=\"javascript:SetSearch('<b>plugin</b>=' + this.innerHTML)\"\">$plugin</a></td>";
            $line.="<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;text-align:center;'>";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>sensor</b>=$sensor_name');return false\"\">" . Util::htmlentities($sensor_name) . "</a></td><td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$src_div";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>src</b>=$src_ip_name');return false\"\">" . Util::htmlentities($src_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>src_port</b>=$matches[8]');return false\">" . Util::htmlentities($matches[8]) . "</a>$country_img_src $homelan_src</td><td class='nobborder' style='border-right:1px solid #FFFFFF;text-align:center;padding-left:5px;padding-right:5px;' nowrap>$dst_div";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>dst</b>=$dst_ip_name');return false\"\">" . Util::htmlentities($dst_ip_name) . "</a></div>:";
            $line.= "<a href=\"#\" onclick=\"javascript:SetSearch('<b>dst_port</b>=$matches[9]');return false\">" . Util::htmlentities($matches[9]) . "</a>$country_img_dst $homelan_dst</td>";
            if ($alt) {
                $color = "grey";
                $alt = 0;
            } else {
                $color = "blue";
                $alt = 1;
            }
            $verified = - 1;
            $data = $matches[11];
            if ($signature != '') {
                $sig_dec = base64_decode($signature);
                $pub_key = openssl_get_publickey($config["pubkey"]); // openssl_pkey_get_public
                $verified = openssl_verify($data, $sig_dec, $pub_key);
                //error_log("$data\n$signature\n", 3, "/tmp/validate");
            }
            $encoded_data = base64_encode($data);
            $data = "<td class='nobborder' style='border-right:1px solid #FFFFFF;padding-left:5px;padding-right:5px;'>";
        }
        // para coger
		$data_out = "";
        // fin para coger
        // change ,\s* or #\s* adding blank space to force html break line
        // para coger
        $matches[11] = preg_replace("/(\,|\#)[^\d+]\s*/", "\\1 ", $matches[11]);
        // fin para coger
        if($htmlResult){
                $matches[11] = wordwrap($matches[11], 60, " ", true);
                $matches[11] = preg_replace("/(;) (&#\d+;)/",";\\1\\2",$matches[11]);
                $matches[11] = preg_replace("/(&) (#\d+;)/","\\1\\2",$matches[11]);
                $matches[11] = preg_replace("/(&#) (\d+;)/","\\1\\2",$matches[11]);
                $matches[11] = preg_replace("/(&#\d+) (\d+;)/","\\1\\2",$matches[11]);
                $matches[11] = preg_replace("/(&#\d+) (;)/","\\1\\2",$matches[11]);
                $matches[11] = preg_replace("/(&#\d+;) (&)/","\\1\\2",$matches[11]);
                foreach(split("[\| \t:]", $matches[11]) as $piece) {
                    $clean_piece = str_replace("(", " ", $piece);
                    $clean_piece = str_replace(")", " ", $clean_piece);
                    $clean_piece = str_replace("[", " ", $clean_piece);
                    $clean_piece = str_replace("]", " ", $clean_piece);
                    $clean_piece = Util::htmlentities($clean_piece);
                    $red = 0;
                    foreach($color_words as $word) {
                        if (stripos($clean_piece, $word)) {
                            $red = 1;
                            break;
                        }
                    }
                    if ($red) {
                        $data.= "<font color=\"red\"><span onmouseover=\"this.style.color = 'green';this.style.cursor='pointer';\" onmouseout=\"this.style.color = 'red';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:SetSearch('<b>data</b>=" . $clean_piece . "')\">" . $clean_piece . " </span>";
                    } else {
                        $data.= "<font color=\"$color\"><span onmouseover=\"this.style.color = 'green';this.style.cursor='pointer';\" onmouseout=\"this.style.color = '$color';this.style.cursor = document.getElementById('cursor').value;\" onclick=\"javascript:SetSearch('<b>data</b>=" . $clean_piece . "')\"\">" . $clean_piece . " </span>";
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
		$data_out = $matches[11];
        // fin para coger
        if($htmlResult){
            $data.= '</td><td class="nobborder" style="text-align:center;padding-left:5px;padding-right:5px;" nowrap><a href="javascript:;" class="thickbox" rel="AjaxGroup" onclick="validate_signature(\''.$encoded_data.'\',\''.$start.'\',\''.$end.'\',\''.$logfile.'\',\''.$signature.'\',\''.$current_server_ip.'\');return false" style="font-family:arial;color:gray"><img src="../pixmaps/lock-small.png" align="absmiddle" border=0><i>'._("Validate").'</i></a>';
            $data.= "</td>";
            $line.= $data;
        }
        // para coger
        $inc_counter++;
        // fin para coger

		if (is_dir($config["searches_dir"]) && isset($export) && $export != "noExport") {
			fputs($outfile,"$inc_counter,$date,$plugin,".Util::htmlentities($matches[5]).",".Util::htmlentities($matches[6]).":".Util::htmlentities($matches[8]).",".Util::htmlentities($matches[7]).":".Util::htmlentities($matches[9]).",$data_out\n");
			$logarr[urldecode($logfile)]++;
		}
		
		$cont[$current_server]++;
	    if($htmlResult){
	        print $line;
	        $colort++;
	        $total_counter++;
	    }
    } else {
    	echo "<tr><td class='nobborder' colspan='9'>WARNING: NOT MATCHING EVENT</td></tr>";
    }
}
print "</table>";

if (is_dir($config["searches_dir"]) && isset($export) && $export != "noExport") {
	fclose ($outfile);
	$logs = "";
	foreach ($logarr as $key=>$val) {
		$logs .= $key."\n";
	}
	fputs($loglist,$logs);
	fclose ($loglist);
}

} // FROM: if (has_results()) {

if (!has_results($num_lines)) {
    print "<center><font style='color:red;font-size:14px'><br>"._("No Data Found Matching Your Criteria")."</font></center>";
} else {
?>
<center>
<?php if ($from_remote) { ?>
<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+$top)."</b>"._(" <b>first</b> events")._(" for <b>each server</b>")." (<b>".(($offset*$num_servers)+1)."</b> - <b>".(($offset*$num_servers)+count($result))."</b> total)" ?>.&nbsp;
<?php } else { ?>
<?php echo _("Showing ")."<b>".($offset+1)."</b> - <b>".($offset+count($result))."</b>"._(" events") ?>.&nbsp;
<?php } ?>
<?php if ($offset > 0) { ?>
<a href="javascript:DecreaseOffset(<?php echo GET('top')?>);" style="color:black"><?php echo ($from_remote) ? "<< "._("Fetch the previous ") : "<< "._("Previous ")?><?php echo "<b>".GET('top')."</b>" ?></a>
<?php } ?>
<?php if ($has_next_page) { //if($num_lines > $offset + 50){
    echo ($offset != 0) ?  "&nbsp;<b>|</b>&nbsp;" : "";
?>
<a href="javascript:IncreaseOffset(<?php echo GET('top')?>);" style="color:black"><?php echo ($from_remote) ? _("Fetch the next ") : _("Next ")?><?php echo "<b>".GET('top')."</b> >>" ?></a>
<?php } ?>
</center>
<br>
<?php } ?>
</div>
</body>
<script type="text/javascript">$("#pbar").progressBar(100);parent.SetFromIframe($("#processcontent").html(),"<?php echo $a ?>","<?php echo $start ?>","<?php echo $end ?>","<?php echo $sort_order ?>")</script>
