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
set_time_limit(3600);
ob_implicit_flush();
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Net.inc');
require_once ('classes/Host_scan.inc');
require_once ('classes/Plugin.inc');
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
include ("functions.php");

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$sensors = $hosts = $ossim_servers = array();
list($sensors, $hosts) = Host::get_ips_and_hostname($conn);
$networks = "";
$_nets = Net::get_all($conn);
$_nets_ips = $_host_ips = $_host = array();
foreach ($_nets as $_net) $_nets_ips[] = $_net->get_ips();
$networks = implode(",",$_nets_ips);
$hosts_ips = array_keys($hosts);

$operator = GET('operator');
ossim_valid($operator, "and", "or", OSS_NULLABLE, 'illegal:' . _("operador"));
if (ossim_error()) {
    die(ossim_error());
}

// Save Search
for ($i = 1; $i <= $_SESSION['inventory_search']['num']; $i++) {
	$_SESSION['inventory_last_search'][$i] = $_SESSION['inventory_search'][$i];
}
$_SESSION['inventory_last_search_op'] = $operator;
$_SESSION['inventory_last_search']['num'] = $_SESSION['inventory_search']['num'];

// Read config file with filters rules
$rules = get_rulesconfig ();

$max_rows = 8;
//echo "<br><br><br><br>";
//print_r($_SESSION['inventory_search']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="../js/jquery.progressbar.min.js"></script>
<script type="text/javascript" src="../js/greybox.js"></script>
<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
<? include ("../host_report_menu.php") ?>
<script type="text/javascript">
var pag = 1;

	$(document).ready(function(){
		$('#loading').toggle();
		$('#search_result').toggle();
		$(".scriptinfo").simpletip({
			position: 'right',
			onBeforeShow: function() { 
				var ip = this.getParent().attr('ip');
				this.load('../control_panel/alarm_netlookup.php?ip=' + ip);
			}
		});
		$(".greybox_caption").simpletip({
			position: 'right',
			onBeforeShow: function() {
				var data = this.getParent().attr('data');
				if (data != "") this.update(data);
			}
		});
		$("a.greybox_caption").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,450,'90%');
			return false;
		});
		$("a.greybox").click(function(){
			var t = this.title || $(this).text() || this.href;
			GB_show(t,this.href,400,400);
			return false;
		});
	});
	
	function profile_save(filter_name) {
		if (filter_name == "") alert("<?=_("Please, type a name for this predefined search")?>");
		else {
			$('#save_button').attr('disabled','disabled');
			$('#last_profile').val("Wait...");
			$('#last_profile').css("color","gray");
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'export_last' },
				success: function(msg) {
					alert("<?=_("Current search successfully saved as ")?>'"+filter_name+"'");
					$('#last_profile').val("");
				}
			});
		}
	}
	function put_msg (str) {
		document.getElementById('msg').innerHTML = str;
		setInterval("document.getElementById('msg').innerHTML=''",2000);
	}
</script>
</head>
<?

?>
<body style="margin:0px">
<? include ("../hmenu.php") ?>
<div id="loading" style="width:33%;position:absolute;top:40%;left:33%;">
	<table width="100%" class="transparent">
		<tr>
			<td width="100%" class="nobborder" style="text-align:center">
				<span class="progressBar" id="pbar"></span>
			</td>
		</tr>
		<tr>
			<td width="100%" class="nobborder" id="progressText" style="text-align:center">&nbsp;</td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	$("#progressText").html('<?=_("Filtering. Please, wait a few seconds...")?>');
    $("#pbar").progressBar();
	$("#pbar").progressBar(0);
	$("#progressText").html('<?=_("Loading <b>Report</b>. <br>Please, wait a few seconds...")?>');
</script>
<?
//usleep(500000);
?>
<div id="search_result" style="display:none;width:100%">
<?php
$results = array();
$errors = 0;
$errorlog = array();
$criterias = array();
$has_criterias = array();
for ($i = 1; $i <= $_SESSION['inventory_search']['num']; $i++) {
	$results[$i] = array();
	$perc = $i/$_SESSION['inventory_search']['num']*100;
	$filter = $_SESSION['inventory_search'][$i];
	$criterias[$filter['type']][$filter['subtype']] = ($filter['value'] != "") ? $filter['value'] : "(is true)";
	
	// Advanced: get query from rules. UserFriendly: get query from session
	$q = (GET('userfriendly')) ? $filter['query'] : $rules[$filter['type']][$filter['subtype']]['query'];
	$m = (GET('userfriendly')) ? $filter['query_match'] : $rules[$filter['type']][$filter['subtype']]['match'];
	
	if($m=='fixedText'){
		// For FixedText
		if(!empty($filter['value2'])){
			$value2 = $filter['value2'];
		}else{
			$value2=null;
		}
		
		check_security($filter['value'],$m,$value2,GET('userfriendly'));
	}else{
		check_security($filter['value'],$m,NULL,GET('userfriendly'));
	}
	if ($rules[$filter['type']][$filter['subtype']]['match'] == "concat"){
		list($query,$params) = build_concat_query ($q,$filter['value'],$filter['match'],"concat");
	}elseif($m=='fixedText'){
		list($query,$params) = build_query_two_values ($q,$filter['value'],$filter['value2'],$filter['match'],$m);
	}else{
		list($query,$params) = build_query ($q,$filter['value'],$filter['match'],$m);
	}
	//echo "Filter $i: ".$filter['type']." ".$filter['subtype']." ".$filter['value']." ".$filter['match']."<br>";
	//print_r($params);
	//echo "SQL: ".$query."<br><br>";
	?><script type="text/javascript">$("#pbar").progressBar(<?=$perc?>);$("#progressText").html('<b><?=gettext("Filtering criteria $i")?></b>...');</script><?
	//usleep(500000);
	// FUNCTION MODE (special query)
	if (preg_match("/^function\:(.+)/",$query,$found)) {
		if (function_exists($found[1])) {
			list($err_code,$ips_add) = $found[1]($filter['value']);
			if ($err_code) {
				$errors = 1;
				$errorlog[$i] = "CRITERIA $i: <font color='red'><b>ERROR</b></font>. in function <b>'".$found[1]."'</b>: '$ips_add'";
			} else {
				foreach ($ips_add as $ip) {
					if (Session::hostAllowed($conn,$ip)) {
						$results[$i][] = $ip;
						$has_criterias[$filter['type'].$filter['subtype']][$ip] = true;
					}
				}
				$errorlog[$i] = "CRITERIA $i: ".$filter['type']."->".$filter['subtype']." <b>".$filter['value']."</b> <font color='green'><b>OK</b></font>. ".(count($results[$i]))." IPs found";
			}
		} else {
			$errors = 1;
			$errorlog[$i] = "CRITERIA $i: <font color='red'><b>ERROR</b></font>. Function not found <i>'".$found[1]."'</i>";
		}
	// QUERY MODE (directly from DB)
	} else {
		//print_r($query); print_r($params);
		if (!$rs = & $conn->Execute($query, $params)) {
			$errors = 1;
			$errorlog[$i] = "CRITERIA $i: <font color='red'><b>ERROR</b></font>. Check query <i>'".$rules[$filter['type']][$filter['subtype']]['query']."'</i> in <b>rules</b>. Error msg:<i>".$conn->ErrorMsg()."</i>";
			//print $conn->ErrorMsg();
		} else {
			while (!$rs->EOF) {
				if (Session::hostAllowed($conn,$rs->fields["ip"])) {
					$results[$i][] = $rs->fields["ip"];
					$has_criterias[$filter['type'].$filter['subtype']][$rs->fields["ip"]] = true;
				}
				$rs->MoveNext();
			}
			$errorlog[$i] = "CRITERIA $i: ".$filter['type']."->".$filter['subtype']." <b>".$filter['value']."</b> <font color='green'><b>OK</b></font>. ".(count($results[$i]))." IPs found";
		}
	}
}
$_SESSION['inventory_search']['result']['criterias'] = $criterias;
$_SESSION['inventory_search']['result']['has_criterias'] = $has_criterias;
//$host_list = array_intersect ($results);

//print_r($results);
$host_list = $results[1];
$host_list_aux = array();
/*
for ($i = 2; $i <= $_SESSION['inventory_search']['num']; $i++) {
	if ($operator == "or") {
		foreach ($results[$i-1] as $ip) {
			$host_list_aux[$ip]++;
		}
	} else {
		$host_list = array_intersect ($results[$i-1],$results[$i]);
	}
}*/
for ($i = 2; $i <= $_SESSION['inventory_search']['num']; $i++) {
	if ($operator == "or") {
		foreach ($results[$i] as $ip) {
			$host_list_aux[$ip]++;
		}
	} else {
		$host_list = array_intersect ($host_list,$results[$i]);
	}
}

if ($operator == "or") {
	foreach ($host_list_aux as $h=>$val) {
		if (!in_array($h,$host_list)) $host_list[] = $h;
	}
}


?><script type="text/javascript">$("#pbar").progressBar(100);$("#progressText").html('<b><?=gettext("Loading results, please wait")?></b>...');</script><?
	//usleep(500000);
//if (!GET('userfriendly')) {
?>
<table class="noborder" align="center" width="100%" style="background-color:white">
	<tr><th style="text-align:center">Criterias</th></tr>
	<? foreach ($errorlog as $e) { ?>
	<tr><td class="nobborder" style="text-align:center"><?=$e?></td></tr>
	<? } ?>
</table>
<?
//}
if (count($host_list) < 1 && !$errors) {
?>
<table class="noborder" align="center" width="100%">
	<tr><td class="nobborder" style="text-align:center">All host filtered. No results found.</td></tr>
	<tr><td class="nobborder" style="padding-top:10px;text-align:center"><input type="button" value="Back" onclick="document.location.href='<?=(GET('userfriendly')) ? "userfriendly.php" : "inventory_search.php"?>'" class="button"></td></tr>
</table>
<? } elseif(!$errors) {
	$hosts = Host::get_list($conn);
	$_SESSION['inventory_search']['result']['list'] = array();
	$host_objects = array();
	foreach ($hosts as $host_obj) {
		$host_objects[$host_obj->get_ip()] = $host_obj;
		//if (in_array($host_obj->get_ip(),$host_list))
			//$_SESSION['inventory_search']['result']['list'][] = $host_obj;
	}
	foreach ($host_list as $ip) {
		if ($host_objects[$ip] != "") $_SESSION['inventory_search']['result']['list'][] = $host_objects[$ip];
		else {
			$obj = new Host($ip, $ip, 0, 0, 0, "", 0, 0, null, "", 0,0,0);
			$_SESSION['inventory_search']['result']['list'][] = $obj;
		}
	}
	$total = count($_SESSION['inventory_search']['result']['list']);
	$last_page = floor(($total-1)/$max_rows)+1;
?>
<table class="noborder" width="100%" style="background-color:white">
	<tr>
		<td class="nobborder">
			<table id="results" width="100%" class="noborder" style="background-color:white<? if (GET('userfriendly')) echo ";border:1px solid #CCCCCC"?>" align="center">
			<? if (GET('userfriendly')) { basic_header(); }?>
	<? $i = 0; foreach ($_SESSION['inventory_search']['result']['list'] as $host) {?>
		<? if ($i < $max_rows) { ?>
		<?
		if (GET('userfriendly')) host_row_basic($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips,$i);
		else host_row($host,$conn,$criterias,$has_criterias,$networks,$hosts_ips);
		?>
	<? } ?>
	<? $i++; } ?>
			</table>
		</td>
	</tr>
	
	<tr>
		<td class="nobborder" style="padding:5px;text-align:center">
			<table align="center" width="100%">
				<tr>
					<td class="left nobborder" style="text-align:left;padding:5px" width="33%" nowrap>
						<? if (GET('userfriendly')) { ?>
						<input type="button" value="<?=_("New Search")?>" onclick="document.location.href = 'userfriendly.php?hmenu=Asset+Search&smenu=Asset+Search'" class="button" style="font-size:12px">&nbsp;
						<? } else { ?>
						<input type="button" value="<?=_("Edit Search")?>" onclick="document.location.href = 'inventory_search.php?hmenu=Asset+Search&smenu=Advanced'" class="button" style="font-size:12px">&nbsp;
						<input type="button" value="<?=_("New Search")?>" onclick="document.location.href = 'inventory_search.php?new=1&hmenu=Asset+Search&smenu=Advanced'" class="button" style="font-size:12px">&nbsp;&nbsp;
						<input type="text" value="" name="last_profile" id="last_profile" style="width:60px;font-size:10px">
						<input type="button" value="<?=_("Save Search")?>" id="save_button" onclick="profile_save(document.getElementById('last_profile').value)" class="button" style="font-size:10px">&nbsp;
						<? } ?>
						<!--<input type="button" value="<?=_("Reload")?>" onclick="document.location.reload()" class="button" style="font-size:12px">-->
					</td>
					<? if ($last_page > 1) { ?>
					<td class="center nobborder" width="34%">
						<table align="center" class="transparent">
							<tr>
								<td class="nobborder" style="padding-left:10px;padding-top:10px;padding-bottom:10px"><a href="javascript;" onclick="page('first');return false;"><img src="../pixmaps/first.gif" border="0"></a></td>
								<td class="nobborder" id="prev_link"><a href="javascript:;" onclick="page('prev');return false;"><img src="../pixmaps/prev.gif" border="0"></a></td>
								<td class="nobborder"><?=_("Page")?></td>
								<td class="nobborder"><input id="pag_input" type="text" value="1" onkeypress="enter(this.value, event);" style="width:30px"></td>
								<td class="nobborder"><?=_("of")?> <?=$last_page?></td>
								<td class="nobborder" id="last_link"><a href="javascript:;" onclick="page('next');return false;"><img src="../pixmaps/next.gif" border="0"></a></td>
								<td class="nobborder" style="padding-bottom:10px;padding-top:10px;padding-right:10px"><a href="javascript;" onclick="page('last');return false;"><img src="../pixmaps/last.gif" border="0"></a></td>
							</tr>
						</table>
					</td>
					<? } ?>
					<td class="right nobborder" width="33%">
						<table align="right" class="transparent">
							<tr>
								<td class="nobborder" width="30"><?=_("Results")?>:</td>
								<td class="nobborder" width="5" id="from" style="font-weight:bold">1</td>
								<td class="nobborder" width="5"> - </td>
								<td class="nobborder" width="5" id="to" style="font-weight:bold"><?=($total < $max_rows) ? $total : $max_rows?></td>
								<td class="nobborder" width="40" nowrap style="padding-right:20px"><?=_("of")?> <b><?=$total?></b></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td class="nobborder" style="text-align:center;color:green;font-weight:bold" id="msg"></td></tr>
</table>
	
<script type="text/javascript">
// Parse ajax response
function parseJSON (data) {
	try {
		return eval ("(" + data + ")");
	} catch (e) {
		alert ("ERROR JSON "+e.message+" : "+data);
		return null;
	}
}
function page (direction) {
	if (direction == "next") var p = pag+1;
	else if (direction == "prev") var p = pag-1;
	else if (direction == "last") var p = <?=$last_page?>;
	else if (direction == "first") var p = 1;
	else if (direction.match(/^\d+$/)) var p = direction; // from input
	if (p > 0 && p <= <?=$last_page?>) {
		document.getElementById('results').innerHTML = "<img src='../pixmaps/loading.gif'> <?=_("Loading...")?>";
		$.ajax({
			type: "GET",
			url: "session_result.php",
			data: { userfriendly: <?=(GET('userfriendly')) ? "1" : "0"?>, page: p, rp: <?=$max_rows?> },
			success: function(msg) {
				var ret = parseJSON(msg);
				document.getElementById('results').innerHTML = ret.results;
				pag = ret.page;
				document.getElementById('from').innerHTML = ret.from;
				document.getElementById('to').innerHTML = ret.to;
				document.getElementById('pag_input').value = pag;
				load_contextmenu();
				$(".scriptinfo").simpletip({
					position: 'right',
					onBeforeShow: function() { 
						var ip = this.getParent().attr('ip');
						this.load('../control_panel/alarm_netlookup.php?ip=' + ip);
					}
				});
				$(".greybox_caption").simpletip({
					position: 'right',
					onBeforeShow: function() { 
						var data = this.getParent().attr('data');
						if (data != "") this.update(data);
					}
				});
				$("a.greybox_caption").click(function(){
					var t = this.title || $(this).text() || this.href;
					GB_show(t,this.href,450,'90%');
					return false;
				});
				$("a.greybox").click(function(){
					var t = this.title || $(this).text() || this.href;
					GB_show(t,this.href,400,400);
					return false;
				});
			}
		});
	}
}
function order(ord) {
	document.getElementById('results').innerHTML = "<img src='../pixmaps/loading.gif'> <?=_("Loading...")?>";
		$.ajax({
			type: "GET",
			url: "session_result.php",
			data: { userfriendly: <?=(GET('userfriendly')) ? "1" : "0"?>, page: 1, rp: <?=$max_rows?>, order: ord },
			success: function(msg) {
				var ret = parseJSON(msg);
				document.getElementById('results').innerHTML = ret.results;
				pag = ret.page;
				document.getElementById('from').innerHTML = ret.from;
				document.getElementById('to').innerHTML = ret.to;
				document.getElementById('pag_input').value = pag;
				load_contextmenu();
				$(".scriptinfo").simpletip({
					position: 'right',
					onBeforeShow: function() { 
						var ip = this.getParent().attr('ip');
						this.load('../control_panel/alarm_netlookup.php?ip=' + ip);
					}
				});
				$(".greybox_caption").simpletip({
					position: 'right',
					onBeforeShow: function() { 
						var data = this.getParent().attr('data');
						if (data != "") this.update(data);
					}
				});
				$("a.greybox_caption").click(function(){
					var t = this.title || $(this).text() || this.href;
					GB_show(t,this.href,450,'90%');
					return false;
				});
				$("a.greybox").click(function(){
					var t = this.title || $(this).text() || this.href;
					GB_show(t,this.href,450,'90%');
					return false;
				});
			}
		});
}
function enter (val,e) {
	var key;

	if (window.event){
		key = window.event.keyCode;
	}else if (e){
		key = e.which;
	}else{
		return;
	}
	if (key == 13) {
		page (val);
	}
}
</script>
<? } ?>
</div>
</body>
</html>
