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
require_once ("classes/Util.inc");
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/User_config.inc');
include("classes/Server.inc");
Session::logcheck("MenuEvents", "ControlPanelSEM");
require_once ('../graphs/charts.php');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
// Open Source
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if (!preg_match("/pro|demo/i",$version)) {
	echo "<html><body><a href='http://www.alienvault.com/information.php?interest=ProfessionalSIEM' target='_blank' title='Profesional SIEM'><img src='../pixmaps/sem_pro.png' border=0></a></body></tml>";
	exit;
}
$db_aux = new ossim_db();
$conn_aux = $db_aux->connect();

//
$param_query = GET("query") ? GET("query") : "";
$param_start = GET("start") ? GET("start") : strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 30));
$param_end = GET("end") ? GET("end") : strftime("%Y-%m-%d %H:%M:%S", time());

$_SESSION['graph_type'] = "last_month";
$_SESSION['cat'] = "Oct%2C+2010";

$framework_ip = $conf->get_conf("frameworkd_address", FALSE);
$database_servers = Server::get_list($conn_aux,",server_role WHERE server.name=server_role.name AND server_role.sem=1");
$ip_to_name = array();
foreach ($database_servers as $db) {
	$name = $db->get_name();
	$ip = $db->get_ip();
	$ip_to_name[$ip] = $name;
}
if (count($ip_to_name)==0) $ip_to_name[$framework_ip] = "Local";
$fcolors = array("2c3816","dee5f2","f8f4f0","e0ecff","fadcb3","dfe2ff","f3e7b3","e0d5f9","ffffd4","fde9f4","f9ffef","ffe3e3","f1f5ec");
$bcolors = array("addf53","5a6986","ec7000","206cff","b36d00","0000cc","ab8b00","5229a3","636330","854f61","64992c","cc0000","006633");
$logger_servers = array();
$logger_error = array();
$ip_list = "";
if (count($database_servers) > 1) {
	$num_servers = count($database_servers);
	foreach ($database_servers as $db) {
		$name = $db->get_name();
		$ip = $db->get_ip();
		if ($ip == $_SERVER['SERVER_ADDR']) $ip = "127.0.0.1";
		ossim_valid(GET($name), OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, 'illegal: server '.$name);
		if ((GET($name) != "" && GET("num_servers") != "") || GET("num_servers") == "") {
			$cmd = 'sudo ./test_remote_ssh.pl '.$ip;
			$res = explode("\n",`$cmd`);
			if ($res[0] == "OK") {
				$logger_servers[$name] = $ip;
				if ($ip_list != "") $ip_list .= ",";
				$ip_list .= $ip;
			} else {
				$logger_error[$name] = $ip;
			}
		}
	}
	if (count($logger_servers) == 1 && reset($logger_servers) == "127.0.0.1") $ip_list = "";
}
if (count($logger_servers) == 0) {
	$num_servers = 1;
	$framework_ip = $conf->get_conf("frameworkd_address", FALSE);
	$logger_servers[$ip_to_name[$framework_ip]] = "127.0.0.1";
}
$from_remote = ($ip_list != "") ? 1 : 0;
$_SESSION['logger_servers'] = $logger_servers;

ossim_valid($param_query, OSS_TEXT, OSS_NULLABLE, '[', ']', 'illegal:' . _("query"));
ossim_valid($param_start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("start date"));
ossim_valid($param_end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("end date"));
ossim_valid($num_servers, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("num_servers"));
if (ossim_error()) {
    die(ossim_error());
}
$config = parse_ini_file("everything.ini");

if($config["debug"]==1){
	if($config["debug_log"]==""){
		$config["debug_log"] = "/var/log/ossim/sem.log";
	}
	//$handle = fopen($config["debug_log"], "a+");
	//fputs($handle,"============================== INDEX.php ".date("Y-m-d H:i:s")." ==============================\n");
	//fclose($handle);
}

$uniqueid = uniqid(rand() , true);

// Filters
$uconfig = new User_config($conn_aux);

$_SESSION['logger_filters'] = $uconfig->get(Session::get_session_user(), 'logger_filters', 'php', "logger");
if ($_SESSION['logger_filters']['default'] == "") {
	$_SESSION['logger_filters']['default']['start_aaa'] = $param_start;
	$_SESSION['logger_filters']['default']['end_aaa'] = $param_end;
	$_SESSION['logger_filters']['default']['query'] = "";
	$uconfig->set(Session::get_session_user(), 'logger_filters', $_SESSION['logger_filters'], 'php', 'logger');
}
?>
<?php
$help_entries["help_tooltip"] = _("Click on this icon to active <i>contextual help</i> mode. Then move your mouse over items and you\'ll see how to use them.<br/>Click here again to disable that mode.");
$help_entries["search_box"] = _("This is the main searchbox. You can type in stuff and it will be searched inside the \'data\' field. Special keywords can be used to restrict search on specific fields:<br/><ul><li>sensor</li><li>src_ip</li><li>dst_ip</li><li>plugin_id</li><li>src_port</li><li>dst_port</li></ul><br/>Examples:<ul><li>plugin_id=4004 and root</li><li>plugin_id!=4004 and not root</li></ul>");
$help_entries["saved_searches"] = _("You can save queries using the Save button near the search box. Here you can recover them and/or delete them.");
$help_entries["close_all"] = _("This will close the graphs below as well as the cache status. Used for a quick <i>tidy up</i>.");
$help_entries["cache_status"] = _("Depending on the amount of time you query on and your log volume, cache can be grow rapidly. Use this to check the status and clean/delete as needed.");
$help_entries["graphs"] = _("Graphs will be recalculated based on the searchbox data, but take some time. Collapse this part for faster searching. You can add query criteria by clicking on various graph regions. Charst aren\'t drawn if the query results in more than 500000 events.");
$help_entries["result_box"] = _("This is the main result box. Each line is a log entry, and can be reordered based on date. You can click anywhere on the log lines to add the highlighted text to the search criteria.");
//$help_entries["clear"] = _("Use this to clear the search criteria.");
$help_entries["play"] = _("Submit your query for processing.");
$help_entries["date_ack"] = _("Acknowledge your date setting in order to recalculate the query.");
$help_entries["save_button"] = _("Use this button to save your current search for later re-use. Saved searches can be viewed by clicking on the saved searches drop-down in the upper left corner.");
$help_entries["date_frame"] = _("Choose between various pre-defined dates to query on. They will be recalculated each time the page is loaded.");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<link rel="stylesheet" href="../forensics/styles/ossim_style.css">
<link href="../style/jquery.contextMenu.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>

<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script src="../js/jquery.contextMenu.js" type="text/javascript"></script>
<script type="text/javascript" src="../js/greybox_post.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script><![endif]-->
<script src="../js/jquery-ui-1.8.core-and-interactions.min.js" type="text/javascript" charset="utf-8"></script>
<script src="../js/jquery-ui-1.8.autocomplete.min.js" type="text/javascript" charset="utf-8"></script>
<script src="../js/tag-it.js" type="text/javascript" charset="utf-8"></script>


<script type="text/javascript" src="../js/jquery.flot.pie.js"></script>
<script src="../js/datepicker.js" type="text/javascript"></script>
<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
<script src="../js/urlencode.js" type="text/javascript"></script>

<? include ("../host_report_menu.php") ?>

<style type="text/css">

#searches table {
    background:none repeat scroll 0 0 #FAFAFA;
    border:1px solid #BBBBBB;
    color:black;
    text-align:center;
   -moz-border-radius:8px 8px 8px 8px;
   padding: 2px;
}

#searches table tr td{
    padding: 0;
}
#searches table tr td input, #searches table{
    font-size: 0.9em;
    line-height: 0.5em;
}
#searches table tr td ul{
    padding: 0px;
}
#searches table tr td ul li{
    padding: 0px 0px 0px 12px;
    list-style-type: none;
    text-align: left;
    margin: 0px;
    clear:left;
    position: relative;
    height: 23px;
    line-height: 1em;
}
#searches table tr td ul li.par{
    background: #f2f2f2;
}
#searches table tr td ul li.impar{
    background: #fff;
}
#searches table tr th{
    background:url("../pixmaps/theme/ui-bg_highlight-soft_75_cccccc_1x300.png") repeat-x scroll 50% 50% #CCCCCC;
    border:1px solid #AAAAAA;
    color:#222222;
    font-size:11px;
    font-weight:bold;
    padding:0 10px;
    text-align:center;
    white-space:nowrap;
    -moz-border-radius:5px 5px 5px 5px;
}


#searchbox{
	font-size: 1.5em;
	margin: 0.5em;
}

#dhtmltooltip{
position: absolute;
width: 150px;
border: 2px solid black;
padding: 2px;
background-color: lightyellow;
visibility: hidden;
z-index: 100;
}

img{
	vertical-align:middle;
}
small {
	font:12px arial;
}

#maintable{
background-color: white;
}
#searchtable{
background-color: white;
}
.negrita { font-weight:bold; font-size:14px; }
.thickbox { color:gray; font-size:10px; }
.header{
line-height:28px; height: 28px; background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 0% 0%; color: rgb(51, 51, 51); font-size: 12px; font-weight: bold; text-align:center;
}

ol, ul, li {
	list-style: none;
	margin: 0;
	padding: 0;
	border: 0;
	outline: 0;
}
.ui-autocomplete {
	background-color: #F8F8F8;
	border:1px solid #CCCCCC;
	position: absolute;
	cursor: default;
}
.ui-autocomplete .ui-menu-item {
}
.ui-autocomplete  .ui-menu-item a {
	display:block;
	padding:4px 6px;
	text-decoration:none;
	line-height:12px;
}
.ui-autocomplete .ui-menu-item a.ui-state-hover,
.ui-autocomplete .ui-menu-item a.ui-state-active {
	background-color:#28BC04;
	color:#fff;
	margin:0;
}
.ui-autocomplete-loading {
	background: white url(../pixmaps/loading3.gif) right center no-repeat;
}

ul.tagit {
	padding:1px 5px;
	border-style:solid;
	border-width:1px;
	border-color:#C6C6C6;
	overflow:auto;
}
ul.tagit li {
	-moz-border-radius:5px 5px 5px 5px;
	display: block;
	float: left;
	margin:2px 5px 2px 5px;
}
ul.tagit li.tagit-choice {
	background-color:#eeeeee;
	border:1px solid #dddddd;
	padding:2px 4px 3px;
	font-size:13px;
}
ul.tagit li.tagit-choice:hover {
	background-color:#C0E380;
	border-color:#AEDF52;
}
ul.tagit li.tagit-new {
	padding:2px 4px 3px;
	padding:2px 4px 1px;
	padding:2px 4px 1px 3px;
}

ul.tagit li.tagit-choice input {
	display:block;
	float:left;
	margin:2px 5px 2px 0;
}
ul.tagit li.tagit-choice a.close {
	color:#777777;
	cursor:pointer;
	font-size:12px;
	font-weight:bold;
	outline:medium none;
	padding:2px 0 2px 3px;
	text-decoration:none;
}
ul.tagit input[type="text"] {
	-moz-box-sizing:border-box;
	border:none;
	margin:0;
	padding:0;
	width:150px;
	height:24px;
	border-color:#C6C6C6;
	background-color:#FFFFFF;
	color:#333333;
	font-size:13px;
}
ul.tagit input.tagit-hidden {
	width:5px;
}

.ytooltip {
        text-align:left;
        position: absolute;
        padding: 5px;
        z-index: 10; 

        color: #303030;
        background-color: #f5f5b5;
        border: 1px solid #DCEFB3;

        font-family: arial;
        font-size: 11px;   
}
</style>

<script>

var first_load = 1;
var byDateStart="";
var byDateEnd="";

function bold_dates(which_one){
	$('#date1td,#date2td,#date3td,#date4td,#date5td').css('background-color','white');
	$('#date1a,#date2a,#date3a,#date4a,#date5a').css('color','black');
	if (which_one) $('#'+which_one+"td").css('background-color','#28BC04');
	if (which_one) $('#'+which_one+"a").css('color','white');
}

function display_info ( var1, var2, var3, var4, var5, var6 ){
// Handle clicks on graphs
	hideGraphs();
	var combined = var6 + "=" + var4;
	SetSearch(combined);
	//hideLayer("by_date");
}

function is_operator (value) {
	return (value == "and" || value == "AND" || value == "or" || value == "OR") ? 1 : 0;
}

function SetFromIframe(content,str,start,end,sort) {
	HandleResponse(content);
	$("#processcontent").show();
	document.getElementById('processframe').style.height = "30px";
    if(document.getElementById('txtexport').value!='noExport') {
        $("#href_download").show();
        $("#img_download").show();
        $("#href_download").attr("href", "download.php?query=" + str + "&start=" + start + "&end=" + end + "&sort=" + sort);
    }
    document.getElementById('txtexport').value = 'noExport';
    load_contextmenu();
    $(".scriptinfo").simpletip({
            position: 'right',
            onBeforeShow: function() {
                    var ip = this.getParent().attr('ip');
                    this.load('../control_panel/alarm_netlookup.php?ip=' + ip);
            }
    });
    $(".scriptinfotxt").simpletip({
            position: 'right',
            baseClass: 'ytooltip',
            onBeforeShow: function() {
                    this.update(this.getParent().attr('txt'));
            }
    });
}

function GetSearchString() {
	var str = "";
	var prev_atom = "";
    $('.search_atom').each(function(){
		var cur_atom = this.value;
		cur_atom = cur_atom.replace(" = ","=");
		cur_atom = cur_atom.replace(" != ","!=");
		cur_atom = cur_atom.replace(/ /g,"SPACESCAPE");
		if (!is_operator(cur_atom) && !cur_atom.match(/\=/)) {
			cur_atom = "data="+cur_atom;
		}
		/* first atom */
		if (prev_atom == "") {
			str = cur_atom;
		}
		/* default operator AND if not specified */
		else if (prev_atom != "" && !is_operator(prev_atom) && !is_operator(cur_atom)) {
			str += " AND "+cur_atom;
		}
		/* other case */
		else {
			str += " "+cur_atom;
		}
    	prev_atom = cur_atom;
    });
    str = escape(str);
    return str;
}

function MakeRequest()
{
    if(document.getElementById('txtexport').value=='noExport') {
        $("#href_download").hide();
        $("#img_download").hide();
    }
	// Used for main query
	//document.getElementById('loading').style.display = "block";
        //
    //document.getElementById('ResponseDiv').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif"> <?php echo _('Loading events...'); ?>';

    var str = GetSearchString();
    
/*
    $('#mytags').tag_input.parents("ul").children(".tagit-choice").each(function(i){
		n = $(this).children("input").val();
		alert(n);
	})*/
    //var str = escape(document.getElementById('searchbox').value);

    //alert(str);
	<? if (GET('query') != "")  { ?>
	var str = "<?php echo GET('query')?>";
	<? } ?>
	var offset = parseInt(document.getElementById('offset').value);
	var start = escape(document.getElementById('start').value);
	var end = escape(document.getElementById('end').value);
	var sort = escape(document.getElementById('sort').value);
	var top = escape(document.getElementById('top').value);

    var txtexport = document.getElementById('txtexport').value;
    document.getElementById('ResponseDiv').innerHTML = "";
	document.getElementById('processframe').src = "process.php?query=" + str + "&offset=" + offset + "&top=" + top + "&start=" + start + "&end=" + end + "&sort=" + sort + "&uniqueid=<?php echo $uniqueid ?><?=(($config["debug"]==1) ? "&debug_log=".urlencode($config["debug_log"]) : "")?>&txtexport="+txtexport;
	
	return false;
}

function resize_iframe() {
	document.getElementById('processframe').style.height = "400px";
}

function RequestLines()
{
	// Used for main query
	document.getElementById('loading').style.display = "block";
	var start = escape(document.getElementById('start').value);
	var end = escape(document.getElementById('end').value);
	var url = "wcl.php?ips=<?php echo $ip_list ?>&start=" + start + "&end=" + end + "&uniqueid=<?php echo $uniqueid?><?=(($config["debug"]==1) ? "&debug_log=".urlencode($config["debug_log"]) : "")?>";
	$.ajax({
		type: "GET",
		url: url,
		data: "",
		success: function(msg) {
			document.getElementById('loading').style.display = "none";
			document.getElementById('numlines').innerHTML = msg;
		}
	});
}

function KillProcess()
{
	$.ajax({
		type: "GET",
		url: "killprocess.php?uniqueid=<?php echo $uniqueid ?>&ips=<?php echo $ip_list ?>",
		data: "",
		success: function(msg) {
			alert("Processes stoped!");
		}
	});
}

function HandleQuery(response){
// Print query listing
	document.getElementById('saved_searches').innerHTML = response;
}

function MakeRequest2(query, action)
{
// Used for query saving
	$.ajax({
		type: "GET",
		url: "manage_querys.php?query=" + urlencode(query) + "&action=" + action,
		data: "",
		success: function(msg) {
			HandleQuery(msg);
		}
	});
}

function DeleteQuery(query){
// delete saved query from list
	MakeRequest2(query,"delete");
}

function AddQuery(){
// Add saved query to list
	var query = escape(document.getElementById('searchbox').value);
	MakeRequest2(query,"add");
}

var graphs_toggled = false;
function getGraphs() {
	document.getElementById('test').style.display = "inline";
	//document.getElementById('test').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif"> <?php echo _('Loading Stats, please a wait a few seconds...') ?>';
	/*$.ajax({
		type: "GET",
		url: "pies.php?uniqueid="+Math.floor(Math.random()*101),
		data: "",
		success: function(msg) {
			document.getElementById('test').innerHTML = msg;
			document.getElementById('graphs_link').href = "javascript:hideGraphs();";
			graphs_toggled = true;
			load_pies();
		}
	});*/
	document.getElementById('graphs_link').href = "javascript:hideGraphs();";
	graphs_toggled = true;
	//document.getElementById('test').innerHTML = '<div id="testLoading"><img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif"> <?php echo _('Loading Stats, please a wait a few seconds...') ?></div>'+"<iframe src='pies.php?ips=<?php echo $ip_list ?>&uniqueid=<?php echo $uniqueid ?>' style='width:100%;height:460px;border: 1px solid rgb(170, 170, 170);overflow:hidden' frameborder='0'></iframe>";
	document.getElementById('test').innerHTML = "<iframe src='pies.php?ips=<?php echo $ip_list ?>&uniqueid=<?php echo $uniqueid ?>' style='width:100%;height:460px;border: 1px solid rgb(170, 170, 170);overflow:hidden' frameborder='0'></iframe>";
}
function hideGraphs() {
	document.getElementById('graphs_link').href = "javascript:getGraphs();";
	document.getElementById('test').style.display = "none";
	graphs_toggled = false;
}

function toggleLayer( whichLayer )
{
  var elem, vis;
  if( document.getElementById ) // this is the way the standards work
    elem = document.getElementById( whichLayer );
  else if( document.all ) // this is the way old msie versions work
      elem = document.all[whichLayer];
  else if( document.layers ) // this is the way nn4 works
    elem = document.layers[whichLayer];
  vis = elem.style;
  // if the style.display value is blank we try to figure it out here
  if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
    vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
  vis.display = (vis.display==''||vis.display=='block')?'none':'block';
}

function hideLayer( whichLayer )
{
  var elem, vis;
  if( document.getElementById ) // this is the way the standards work
    elem = document.getElementById( whichLayer );
  else if( document.all ) // this is the way old msie versions work
      elem = document.all[whichLayer];
  else if( document.layers ) // this is the way nn4 works
    elem = document.layers[whichLayer];
  vis = elem.style;
  // if the style.display value is blank we try to figure it out here
  vis.display = 'none';
}


function closeLayer( whichLayer )
{
  var elem, vis;
  if( document.getElementById ) // this is the way the standards work
    elem = document.getElementById( whichLayer );
  else if( document.all ) // this is the way old msie versions work
      elem = document.all[whichLayer];
  else if( document.layers ) // this is the way nn4 works
    elem = document.layers[whichLayer];
  vis = elem.style;
  // if the style.display value is blank we try to figure it out here
  vis.display = 'none';
}

function SubmitClick() {
	$('#offset').val('0');
	if (typeof($('.tagit-input')).val() != 'undefined' && $('.tagit-input').val() != ''){
		AddAtom($('.tagit-input').val());$('.tagit-input').val('');
	}
	doQuery('noExport');
}

function IsNew (value){
	value = value.replace(/\<b\>/g,"");
	value = value.replace(/\<\/b\>/g,"");
	if (!value.match(/\=/) && !is_operator(value)) value = "data="+value;
	var is_new = true;
	$('#mytags').children(".tagit-choice").each(function(i){
		n = $(this).children("input").val();
		if (value == n) {
			is_new = false;
		}
	})
	return is_new;
}

function AddAtom(value) {
	if (IsNew(value)) {
	if (!value.match(/\=/) && !is_operator(value)) value = "<b>data</b>="+value;
	if (value.match(/\=/) && !value.match(/\<b\>/)) {
		value = "<b>"+value;
		if (value.match("!=")) {
			value = value.replace("!=","</b>!=");
		} else {
			value = value.replace("=","</b>=");
		}
	}
	var el = "";
	el  = "<li class=\"tagit-choice\">\n";
	el += value + "\n";
	el += "<a class=\"close\">x</a>\n";
	value = value.replace(/\<b\>/g,"");
	value = value.replace(/\<\/b\>/g,"");
	el += "<input type=\"hidden\" style=\"display:none;\" class=\"search_atom\" value=\""+value+"\" name=\"item[tags][]\">\n";
	el += "</li>\n";
	$(el).insertBefore ($('#mytags').children(".tagit-new"));
	}
}

function SetSearch(content)
{
	var atoms = new Array;
	if (content.match(/ /)) {
		atoms = content.split(" "); 
	} else {
		atoms[0] = content;
	}
	for (i = 0; i < atoms.length; i++) {
		var value = atoms[i];
		value = value.replace(/^\s+/g,'').replace(/\s+$/g,'').replace(/\n/g,'');
		if (value != "" && value != "data=") {
			AddAtom(value);
		}
	}
	window.scrollTo(0,0);
	//$('#tip_msg').show();
	//setTimeout("$('#tip_msg').fadeOut('slow');",2000);
	MakeRequest();
}

function ReplaceSearch(content)
{
// Replace search bar, perform search
  document.getElementById('searchbox').value = content;
  MakeRequest();
}

function ClearSearch()
{
// Clear search bar, perform search
  document.getElementById('searchbox').value = "";
  document.getElementById('offset').value = "0";
  document.getElementById('sort').value = "none";
  MakeRequest();
}

function IncreaseOffset(amount)
{
// Pagination
  var offset = parseInt(document.getElementById('offset').value);
  document.getElementById('offset').value = offset + amount;
  MakeRequest();
}

function DateAsc()
{
// Sorting
  document.getElementById('sort').value = "date";
  MakeRequest();
}

function DateDesc()
{
// Sorting
  document.getElementById('sort').value = "date_desc";
  MakeRequest();
}


function DecreaseOffset(amount)
{
// Pagination
	var offset = parseInt(document.getElementById('offset').value);
	document.getElementById('offset').value = offset - amount;
	MakeRequest();
}

function setFixed(start, end, gtype, datef)
{
// Gets fixed time ranges from day, month, etc... buttons
	document.getElementById('start').value = start;
	document.getElementById('start_aaa').value = start;
	document.getElementById('end').value = end;
	document.getElementById('end_aaa').value = end;
	if (gtype != '' && datef != '') {
		UpdateByDate("forensic.php?graph_type="+gtype+"&cat="+datef);
	}
	
	$('#widgetCalendar').DatePickerClear();
	var date_arr = new Array; date_arr[0] = start; date_arr[1] = end;
	$('#widgetCalendar').DatePickerSetDate(date_arr, 0);
	RequestLines();
	MakeRequest();
}

function setFixed2()
{
// Gets fixed time ranges from calendar popups
// If not entered manually hour information will be missing so..
	var start_pad = "";
	var end_pad = "";
	if(document.getElementById('start_aaa').value.length == 10){
		var start_pad = " 00:00:00";
	}
	if(document.getElementById('end_aaa').value.length == 10){
		var end_pad = " 00:00:00";
	}

	document.getElementById('start').value = document.getElementById('start_aaa').value + start_pad;
	document.getElementById('end').value = document.getElementById('end_aaa').value + end_pad;
	RequestLines();
	MakeRequest();
}


function HandleResponse(response)
{
// Main response handler for event lines
	document.getElementById('ResponseDiv').innerHTML = response;
	if(first_load == 1){
		first_load = 0;
	} else {
		if (graphs_toggled) getGraphs();
	}
}

function HandleCacheResponse(response)
{
// Handle Gauge and cache information
  var responses = response.split(":");
  if(responses[0] == "pct"){
    gauge.needle.setValue(responses[1]);
  } else {
  document.getElementById('gauge_text').innerHTML = response;
  }
}

function showTip(text, color, width){
	if(document.body.style.cursor == 'help'){
		ddrivetip(text,color,width);
	}
}

function hideTip(){
	if(document.body.style.cursor == 'help'){
		hideddrivetip();
	}
}

function toggleCursor(){
	if(document.body.style.cursor == 'help'){
		document.body.style.cursor = document.getElementById('cursor').value;} else {
			document.body.style.cursor = "help";
		}
}

function HandleStatsByDate(response)
{
	//document.getElementById('by_date').innerHTML=response.replace(/so.write\([^\)]+\)/,'so.write("by_date")');
	var cont=document.getElementById('by_date').innerHTML;
	document.getElementById('by_date').innerHTML="";
	document.getElementById('by_date').innerHTML=cont;
  	if(first_load != 1)
	{
		hideLayer("test");
	}
}

function UpdateByDate(urlres)
{
	$.ajax({
		type: "GET",
		url: urlres,
		data: "",
		async: false,
		success: function(msg) {
			HandleStatsByDate(msg);
		}
	});
}


function graph_by_date( col ,row ,value, category, series, t_year, t_month)
{
    var urlres = "forensic.php";
    var month;
    var year;
    var day;
    var hour;
	//alert(col+','+row+','+value+','+category+','+series+','+t_year+','+t_month);
    document.getElementById('searchbox').value = "";
    document.getElementById('offset').value = "0";
    document.getElementById('sort').value = "none";
  switch(row)
  {
    case 1:
      urlres = urlres+ "?graph_type=year&cat=" + category;

      year=category.replace(/^ *| *$/g,"");
      byDateStart=year+"-01-01";
      byDateEnd=year+"-12-31";
      document.getElementById('start').value = byDateStart+" 00:00:00";
      document.getElementById('start_aaa').value = byDateStart+" 00:00:00";
      document.getElementById('end').value = byDateEnd+ " 23:59:59";
      document.getElementById('end_aaa').value = byDateEnd+" 23:59:59";
      RequestLines(); MakeRequest();
      bold_dates();
    break;
    case 2:
      urlres = urlres + "?graph_type=month&cat=" + category;

      month=monthToNumber(category.replace(/,.*$/,""));
      year=category.replace(/^.*, /,"");
      byDateStart=year+"-"+month+"-01";
      lastmonthday = new Date((new Date(year, month, 1))-1).getDate();
      byDateEnd=year+"-"+month+"-"+lastmonthday;
      document.getElementById('start').value = byDateStart+" 00:00:00";
      document.getElementById('start_aaa').value = byDateStart+" 00:00:00";
      document.getElementById('end').value = byDateEnd+ " 23:59:59";
      document.getElementById('end_aaa').value = byDateEnd+" 23:59:59";
      RequestLines(); MakeRequest();
      bold_dates();
    break;
    case 3:
      urlres = urlres + "?graph_type=day&cat=" + category;

      month=monthToNumber(category.replace(/ .*$/,""));
      year=category.replace(/^.*, /,"");
      day=category.replace(/^[^ ]+ /,"");
      day=day.replace(/,.*$/,"");
      if(day.length==1)
      	day="0"+day;
      byDateStart=year+"-"+month+"-"+day;
      byDateEnd=year+"-"+month+"-"+day;
      document.getElementById('start').value = byDateStart+" 00:00:00";
      document.getElementById('start_aaa').value = byDateStart+" 00:00:00";
      document.getElementById('end').value = byDateEnd+ " 23:59:59";
      document.getElementById('end_aaa').value = byDateEnd+" 23:59:59";
      RequestLines(); MakeRequest();
      bold_dates();
      //alert("day: "+ day +" month: "+month+ " year: "+year);
    break;
    default:
      //Dont create another graph... refresh the search and stop here
      hour=category.replace(/[^\d]+/,"");
      hour=hour.replace(/[^\d]+/,"");
      document.getElementById('start_aaa').value = document.getElementById('start').value = byDateStart+" "+hour+":00:00";
      document.getElementById('end_aaa').value = document.getElementById('end').value = byDateEnd+ " "+hour+":59:59";
      RequestLines(); 
      MakeRequest();
      bold_dates();
      return;
    break;
  }
  //alert (urlres);
  UpdateByDate(urlres);
}
function monthToNumber(m)
{
	m=m.toLowerCase();
	switch(m)
	{
		case "jan":
			return "01";
			break;
		case "feb":
			return "02";
			break;
		case "mar":
			return "03";
			break;
		case "apr":
			return "04";
			break;
		case "may":
			return "05";
			break;
		case "jun":
			return "06";
			break;
		case "jul":
			return "07";
			break;
		case "aug":
			return "08";
			break;
		case "sep":
			return "09";
			break;
		case "oct":
			return "10";
			break;
		case "nov":
			return "11";
			break;
		case "dec":
			return "12";
			break;
		default:
			return 0;
			break;
	}
}

function SubmitForm() { /*document.forms[0].submit();*/ MakeRequest(); }

function EnterSubmitForm(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  if (evt.keyCode == 13) SubmitForm();
} 

function doQuery(tipoExport) {
  //hideLayer("by_date");
  document.getElementById('txtexport').value=tipoExport;
  SubmitForm();
}

function CalendarOnChange() {
	bold_dates('');
	setFixed2();
}
Array.prototype.in_array = function(p_val) {
    for(var i = 0, l = this.length; i < l; i++) {
        if(this[i] == p_val) {
            return true;
        }
    }
    return false;
}

$(document).ready(function(){
	//UpdateByDate('forensic.php?graph_type=all&cat=');
	$('#date4').addClass('negrita');
	bold_dates('date4');
	//UpdateByDate('forensic.php?graph_type=last_year&cat=<?php echo urlencode(date("M, Y")) ?>');
	RequestLines();
	MakeRequest();
	//setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 31)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','last_month','<?php echo urlencode(date("Y")) ?>');
	$("#start_aaa,#end_aaa").change(function(objEvent){
		CalendarOnChange();
	});
	<? if (trim($_GET['query'])!="") { ?>
	bold_dates('date5');
	setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 365)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','year','<?php echo urlencode(date("Y")) ?>');
	<? } ?>
	
	// CALENDAR
	<?
	$y = strftime("%Y", time() - ((24 * 60 * 60) * 30));
	$m = strftime("%m", time() - ((24 * 60 * 60) * 30));
	$d = strftime("%d", time() - ((24 * 60 * 60) * 30));
	?>
	var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
	var dateto = new Date(<?php echo date("Y") ?>,<?php echo date("m")-1 ?>,<?php echo date("d") ?>);
    var dayswithevents = [ ];
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
				document.getElementById('start_aaa').value = f1[0]+'-'+f1[1]+'-'+f1[2];
				document.getElementById('end_aaa').value = f2[0]+'-'+f2[1]+'-'+f2[2];
                $("#widget>a").trigger('click');
				setFixed2();
			}
		}/*,
		
		onRender: function(date) {
			return {
					//disabled: (date.getTime() < now.getTime()),
					//className: dayswithevents.in_array(date.getTime()) ? 'datepickerSpecial' : false
			}
		}*/
	});
	var state = false;
	$('#widget>a').bind('click', function(){
		$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
		$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
		state = !state;
		return false;
	});
	$('#widgetCalendar div.datepicker').css('position', 'absolute');

	/* AUTOCOMPLETE SEARCH */
	<?php 
	list($sensors, $hosts) = Host::get_ips_and_hostname($conn_aux);
	?>
	$("#mytags").tagit({
		autoFormat: true,
		changeFunction: function() { MakeRequest(); },
		availableTags: ["data"<?php foreach ($sensors as $ip=>$name) { ?>, "sensor:<?php echo $name?>"<?php } ?><?php $top = 0; foreach ($hosts as $ip=>$name) if ($top < 20) { ?>, "source:<?php echo $name?>", "destination:<?php echo $name ?>"<?php $top++; } ?>]
	});
});
function change_calendar() {
	var n = 0;
	var date_arr = new Array; date_arr[0] = document.getElementById('start_aaa').value; date_arr[1] = document.getElementById('end_aaa').value;
	
	$('#widgetCalendar').DatePickerSetDate(date_arr, 0);
}

function save_filter(filter_name) {
	//var filter_name = document.getElementById('filter').value;
	var start = document.getElementById('start').value;
	var end = document.getElementById('end').value;
	var query = GetSearchString();
	
	document.getElementById('filter_msg').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif">';
	$.ajax({
		type: "GET",
		url: "ajax_filters.php?mode=new&filter_name="+filter_name+"&start="+start+"&end="+end+"&query="+query,
		data: "",
		success: function(msg) {
			document.getElementById('filter_msg').innerHTML = "";
		}
	});
}
function new_filter() {
	var filter_name = document.getElementById('filter_name').value;
	var start = document.getElementById('start').value;
	var end = document.getElementById('end').value;
	var query = GetSearchString();
	
	if (filter_name == "") alert("<?=_("You must type a name for the new filter.")?>");
	else {
		document.getElementById('filter_msg').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif">';
		$.ajax({
			type: "GET",
			url: "ajax_filters.php?mode=new&filter_name="+filter_name+"&start="+start+"&end="+end+"&query="+query,
			data: "",
			success: function(msg) {
				document.getElementById('filter_box').innerHTML = msg;
				document.getElementById('filter_msg').innerHTML = "";
			}
		});
	}
}
function change_filter(filter_name) {
	document.getElementById('filter_msg').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif">';
	$.ajax({
		type: "GET",
		url: "ajax_filters.php?mode=load&filter_name="+filter_name,
		data: "",
		success: function(msg) {
			var filter_data = msg.split(/\#\#/);
			if (filter_data[0] != "" && filter_data[1] != "") {
				document.getElementById('start_aaa').value = filter_data[1];
				document.getElementById('end_aaa').value = filter_data[2];
				$('#mytags').children(".tagit-choice").remove();
				if (filter_data[3] != "") SetSearch(filter_data[3]);
								
                document.getElementById('filter_box').innerHTML = msg;
				setFixed2();
			}
			else alert("Error: "+msg);
			document.getElementById('filter_msg').innerHTML = "";
		}
	});
}
function delete_filter(filter_name) {
	//var filter_name = document.getElementById('filter').value;

	if (filter_name == "" || filter_name == "default") alert("<?=_("You can not delete this filter.")?>");
	else {
            if(confirm('<?php echo _("Are you secure?")?>')){
                    document.getElementById('filter_msg').innerHTML = '<img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif">';
                    $.ajax({
                            type: "GET",
                            url: "ajax_filters.php?mode=delete&filter_name="+filter_name,
                            data: "",
                            success: function(msg) {
                                    document.getElementById('filter_box').innerHTML = msg;
                                    document.getElementById('filter_msg').innerHTML = "";
                            }
                    });
                }else{
                    return false;
                }
	}
}
</script>
</head>
<body>
<ul id="myMenu" class="contextMenu">
<li class="report"><a href="#edit"><?=_("Host Report")?></a></li>
</ul>
<?php
include ("../hmenu.php"); ?>

<table border=0 cellpadding=0 cellspacing=0 align="right">
<?
if (count($database_servers)>0 && Session::menu_perms("MenuPolicy", "PolicyServers")) { 
	// session server
	?>
	<form name="serverform">
	<tr>
		<td align='left' style="padding-right:10px">
		<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#rservers').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("Remote Servers")?></a>
			<div style="position:relative; z-index:1">
			<div id="rservers" style="position:absolute;right:0;top:0;display:none;border:1px solid gray;background-color:#EEEEEE">
				<table border=0 cellpadding=1 cellspacing=2 width="100%">
				<?php
				$i = 0;
				foreach ($database_servers as $db) {
					if ($i >= count($bcolors)) $i = 0;
					$name = $db->get_name();
					$_SESSION['logger_colors'][$name]['bcolor'] = $bcolors[$i];
					$_SESSION['logger_colors'][$name]['fcolor'] = $fcolors[$i];
					?>
					<tr bgcolor='#EEEEEE'>
						<td><input type="checkbox" id="check_<?php echo $name ?>" onclick="document.serverform.submit()" name="<?php echo $name ?>" value="<?php echo $name ?>" <?php if ($logger_servers[$name]) { echo "checked"; } if ($logger_error[$name]) { echo " disabled"; } ?>></input></td>
						<td></td>
						<td><table><tr><td style="padding-left:5px;padding-right:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;background-color:<?php echo '#'.$bcolors[$i]?>;color:<?php echo '#'.$fcolors[$i]?>"><?php echo $name ?></td></tr></table></td>
					</tr>
				<?php $i++; } ?>
				</table>
			</div>
			</div>
		</td>
	</tr>
	<input type="hidden" name="num_servers" value="<?php echo $i ?>"></input>
	</form>
<?php
}
?>
</table>

<a href="javascript:toggleLayer('by_date');"><img src="<?php echo $config["toggle_graph"]; ?>" border="0" title="<?=_("Toggle Graph by date")?>"> <small><font color="black"><?=_("Graphs by dates")?></font></small></a>
<center style="margin:0px">
<div id="by_date">
    <div id="testLoading2"><img align="middle" style="vertical-align: middle;" src="../pixmaps/sem/loading.gif"> <?php echo _('Loading Graphs, please a wait a few seconds...') ?></div>
    <a href="javascript:UpdateByDate('forensic.php?graph_type=all&cat=&uniqueID=<?php echo $uniqueid ?>');"><small><font color="black"><?=_("Click to show the main chart")?></font></small></a>
    <IFRAME src="forensic_source.php?ips=<?php echo $ip_list ?>" frameborder="0" style="margin-top:0px;width:100%;height:180px;overflow:hidden"></IFRAME>
</div>
</center>
<!--
<div id="help" style="position:absolute; right:30px; top:5px";>
<img src="<?php echo $config["help_graph"] ?>" border="0" onMouseover="ddrivetip('<?php echo $help_entries["help_tooltip"]; ?>','lighblue', 300)" onMouseout="hideddrivetip()" onClick="toggleCursor()">
</div>-->
<!-- Misc internal vars -->
<form id="search" action="javascript:MakeRequest();">
<input type="hidden" id="searchbox"></input>
<input type="hidden" name="cursor" id="cursor" value="">
<script>
document.getElementById('cursor').value = document.body.style.cursor;
</script>
<input type="hidden" id="offset" value="0">
<?php // Possible sort values: none, date, date_desc
 ?>
<input type="hidden" id="sort" value="none">
<input type="hidden" id="start" value="<?php echo ($param_start != "" && date_parse($param_start)) ? $param_start : strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 31)) ?>">
<?php // Temporary fix until the server logs right
 ?>
<input type="hidden" id="end" value="<?php echo ($param_end != "" && date_parse($param_end)) ? $param_end : strftime("%Y-%m-%d %H:%M:%S", time()) ?>">
<!--
<div id="compress">
<center><a href="javascript:closeLayer('entiregauge');closeLayer('test');closeLayer('compress');" onMouseOver="showTip('<?php echo $help_entries["close_all"] ?>','lightblue','300')" onMouseOut="hideTip()"><font color="black"><?php echo _("Click here in order to compress everything") ?></a></center>
</div>
-->
<div id="saved_searches" style="display:none; position:absolute; background-color:#FFFFFF">
<?php
require_once ("manage_querys.php");
?>
</div>
<table cellspacing="0" width="100%" border="0" id="maintable">
<tr>
<td nowrap class="nobborder">
	<table cellspacing="0" width="100%" id="searchtable" style="border: 1px solid rgb(170, 170, 170);border-radius: 0px; -moz-border-radius: 0px; -webkit-border-radius: 0px;background:url(../pixmaps/fondo_hdr2.png) repeat-x">
		<tr><td class="nobborder" align="center" style="text-align:center;padding-top:20px">
			<table class="transparent" align="center">
				<tr>
					<td class="nobborder" style="font-size:18px;font-weight:bold;color:#222222"><?=_("Search")?>:</td>
					<!--
					<a href="javascript:toggleLayer('saved_searches');" onMouseOver="showTip('<?php echo $help_entries["saved_searches"] ?>','lightblue','300')" onMouseOut="hideTip()"><img src="<?php echo $config["toggle_graph"]; ?>" border="0" title="Toggle Graph"> <small><font color="#AAAAAA"><?php echo _("Saved Searches") ?></font></small></a>

						
						<a href="javascript:AddQuery()" onMouseOver="showTip('<?php echo $help_entries["saved_searches"] ?>','lightblue','300')" onMouseOut="hideTip()"><img src="<?php echo $config["save_graph"] ?>" border="0" style="vertical-align:middle; padding-left:5px; padding-right:5px;"></a>
						-->
						<!--<input type="text" id="searchbox" size="60" value="<?=$_GET['query']?>" style="vertical-align:middle;" onKeyUp="return EnterSubmitForm(event)" onMouseOver="showTip('<?php echo $help_entries["search_box"] ?>','lightblue','300')" onMouseOut="hideTip()"><a onMouseOver="showTip('<?php echo $help_entries["play"] ?>','lightblue','300')" onMouseOut="hideTip()" href="javascript:doQuery()" title="<?php echo _("Submit Query") ?>"><img src="<?php echo $config["play_graph"]; ?>" border="0" align="middle" style="padding-left:5px; padding-right:5px;"></a>-->
					<td>
					<div style="clear:both">
					<ul id="mytags" style="list-style: none"></ul>
					</div>
					</td>
					<!-- <td class="nobborder"><input type="text" id="searchbox" size="60" value="<?=$_GET['query']?>" style="vertical-align:middle;" onKeyUp="return EnterSubmitForm(event)" onMouseOver="showTip('<?php echo $help_entries["search_box"] ?>','lightblue','300')" onMouseOut="hideTip()"></td> -->
					<td class="nobborder"><input type="button" class="button" onclick="SubmitClick()" value="<?php echo _("Submit Query") ?>" style="font-weight:bold;height:30px"></td>
                                        <?php /*
					<!--<a href="javascript:ClearSearch()" onMouseOver="showTip('<?php echo $help_entries["clear"] ?>','lightblue','300')" onMouseOut="hideTip()"><font color="#999999"><small><?php echo _("Clear Query"); ?></small></font></a>-->
					<td class="nobborder"><input type="button" onclick="ClearSearch()" value="<?php echo _("Clear Query"); ?>" class="button" style="height:20px"></td>
                                         */?>
					<td class="nobborder" style="padding:10px">
                                            <input type="hidden" name="txtexport" id="txtexport" value="noExport" />
                                            <a onclick="doQuery('exportScreen')" href="#" alt="<?php echo _("Export screen")?>"><img src="../pixmaps/exportScreen.png" border="0" title="<?php echo _("Export screen")?>" alt="<?php echo _("Export screen")?>" /></a>
                                            <a onclick="doQuery('exportEntireQuery')" href="#" alt="<?php echo _("Export entire query")?>"><img src="../pixmaps/exportQuery.png" border="0" title="<?php echo _("Export entire query")?>" alt="<?php echo _("Export entire query")?>" /></a>
					</td>
					<td class="nobborder">
                    	<a href="#" id="href_download" style="display:none;"><img align="absmiddle" title="<?=_("Download")?>" alt="<?=_("Download")?>" style="display:none;" id="img_download" src="../pixmaps/download.png" border="0" width="15"></a>
                    </td>
                    <td class="nobborder">
                    	<a href="javascript:;" onclick="$('#searches').toggle()"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"> <b><?php echo _("Predefined Searches")?></b></a>
                        <div style="position:relative">
                        <div id="searches" style="position:absolute;right:0;top:0;display:none">
                        <table cellpadding=0 cellspacing=0 align="center">
	                        <tr>
	                        	<th style="padding-right:3px"><?php echo _("Select a predefined to search") ?> <a style="margin: 0 0 0 5px" href="javascript:;" onclick="$('#searches').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" /></a></th>
	                        </tr>
	                        <tr class="noborder">
		                        <td id="filter_box">
		                        	<input type="hidden" name="filter" id="filter" value="default" />
			                        <ul>
			                        <? $i=0;
			                        foreach ($_SESSION['logger_filters'] as $name=>$attr) {
			                        $i++;    ?>
			                        <li class="<?php if($i%2==0){ echo 'impar'; }else{ echo 'par'; } ?>">
				                        <div style="float:left">
				                        <a onclick="change_filter('<?php echo $name ?>')" href="#" id="filter_<?php echo $name ?>">
				                        <?php echo $name ?>
				                        </a>
				                        </div>
				                        <div style="position: absolute;right:2px;float:left;width: 40px;opacity:0.4;filter:alpha(opacity=40)">
				                        <img src="../pixmaps/disk-gray.png" alt="<?php echo _("Update"); ?>" title="<?php echo _("Update"); ?>" border="0" />
				                        <img src="../vulnmeter/images/delete.gif" alt="<?php echo _("Delete"); ?>" title="<?php echo _("Delete"); ?>" border="0" />
				                        </div>
			                        </li>
			                        <? } ?>
			                        </ul>
		                        </td>
	                        </tr>
	                        <tr>
	                       		<td id="filter_msg" class="noborder"></td>
	                        </tr>
	                        <tr>
	                        	<td class="noborder" style="text-align: left;padding-left: 7px">
		                        <input type="text" name="filter_name" id="filter_name" value="" style="width:140px"  />
		                        <input type="button" value="<?php echo _("add")?>" onclick="new_filter()" class="button" style="height:18px;width:30px" />
	                       		</td>
	                        </tr>
                        </table>
                        </div>
                        </div>
                    </td>
				</tr>
				<tr>
					<td style="font-size:9x">&nbsp;</td>
					<td>
					<div id="tip_msg" style="font-family:arial;font-size:9px;color:gray;display:none">
					<?php echo _("Add new criteria search or click Submit button to get events.") ?>
					</div>
					</td>
				</tr>
			</table>
		</tr>
		
		<tr><td style="padding-left:10px;padding-right:10px" colspan="5" class="nobborder"><table class="noborder" width="100%" cellpadding=0 cellspacing=0 border=0><tr><td class="nobborder" style="background:url('../pixmaps/points.gif') repeat-x"><img src="../pixmaps/points.gif"></td></tr></table></td></tr>
		
		<tr>
			<td class="nobborder" style="padding:10px" valign="top">
			<table class="transparent" width="100%">
				<tr>
				<td class="nobborder">
					<table class="transparent">
                    <tr>
                        <td class="nobborder" nowrap><?=_("Time frame selection")?>:</td>
                        <td class="nobborder">
                            <div id="widget">
                                <a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0"></a>
                                <div id="widgetCalendar"></div>
                            </div>
                        </td>
                        <td class="nobborder" nowrap>
                        <?php
                        if ($param_start != "" && $param_end != "" && date_parse($param_start) && date_parse($param_end)) {
                        ?>
                            <input type="text" size="18" id="start_aaa" name="start_aaa" value="<?php echo $param_start; ?>">
                            <input type="text" size="18" id="end_aaa" name="end_aaa" value="<?php echo $param_end; ?>" >

                        <?php
                        } else {
                        ?>
                            <input type="text" size="18" id="start_aaa" name="start_aaa" value="<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 31)) ?>">
                            <input type="text" size="18" id="end_aaa" name="end_aaa" value="<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>">
                        <?php
                        }
                        ?>
                        <input type="button" value="<?=_("OK")?>" onclick="change_calendar();setFixed2();" class="button" style="font-size:10px;height:22px;width:28px" />
                        </td>
					</tr>
                    <tr>
                        <td colspan="3" nowrap><img src="../pixmaps/arrow_green.gif" alt="" align="absmiddle"></img> Fetch&nbsp;
                            <select name="top" id="top" onchange="document.getElementById('offset').value='0';doQuery('noExport')">
                                <option value="10">10</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>&nbsp;<?php echo ($from_remote) ? _("events <b>per server</b>") : _("events per page"); ?>
                        </td>
                    </tr>
					</table>
				</td>
				<td nowrap class="nobborder" valign="top">
					<table class="transparent">
					<tr>
					<td class="nobborder" nowrap id="date2td" style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "day") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "day") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="javascript:setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - (24 * 60 * 60)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','day','<?php echo urlencode(date("M d, Y")) ?>');" onClick="javascript:bold_dates('date2');" id="date2a"><?=_("Last 24 Hours")?></a>
					</td>
					<td class="nobborder"><font style="color:green;font-weight:bold">|</font></td>
					<td class="nobborder" id="date3td" nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "week") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "week") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="javascript:setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 7)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','last_week','<?php echo urlencode(date("M, Y")) ?>');" onClick="javascript:bold_dates('date3');" id="date3a"><?=_("Last Week")?></a>
					</td>
					<td class="nobborder"><font style="color:green;font-weight:bold">|</font></td>
					<td class="nobborder" id="date4td" nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "month") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "month") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="javascript:setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 31)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','last_month','<?php echo urlencode(date("M, Y")) ?>');" onClick="javascript:bold_dates('date4');" id="date4a"><?=_("Last Month")?></a>
					</td>
					<td class="nobborder"><font style="color:green;font-weight:bold">|</font></td>
					<td class="nobborder" id="date5td" nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "all") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "all") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="javascript:setFixed('<?php echo strftime("%Y-%m-%d %H:%M:%S", time() - ((24 * 60 * 60) * 365)) ?>','<?php echo strftime("%Y-%m-%d %H:%M:%S", time()); ?>','last_year','<?php echo urlencode(date("Y")) ?>');" onClick="javascript:bold_dates('date5');" id="date5a"><?=_("Last Year")?></a>
					</td>
					</tr>
					</table>
				</td>
				<td class="nobborder" nowrap align="middle" valign="center" style="padding-left:20px">
					<div id="numlines" style="vertical-align:middle; padding-right:10px">&nbsp;</div>
				</td>
				<td class="nobborder" nowrap width="20" style="padding-left:15px" valign="top">
					<div id="loading" style="display:none; vertical-align:middle; padding-right:10px; padding-top:10px;"><img src="<?php echo $config["loading_graph"]; ?>" align="middle" style="vertical-align:middle;"></div>
				</td>
				</tr>
			</table>
			</td>
		</tr>
	</table>
	</td>
	</tr>
	</table>
</form>
<table width="100%">
<tr>
<td width="50" valign="top"><a href="javascript:getGraphs();" id="graphs_link"><img src="<?php echo $config["toggle_graph"]; ?>" border="0" title="<?=_("Toggle Graph")?>"> <small><font color="black"><?php echo _("Stats") ?></font></small></a></td>
<td><div id="loadingProcess">
<iframe id="processframe" src="" width="100%" style="height:30px" frameborder="0" scrolling="no"></iframe>
</div></td>
</tr>
</table>
<div id="test" onMouseOver="showTip('<?php echo $help_entries["graphs"] ?>','lightblue','300')" onMouseOut="hideTip()" style="z-index:50;display:none">
</div>
<?
//echo "storage_graphs2.php?label=" . _("Sources") . "&what=src_ip&uniqueid=$uniqueid";
?>
<div id="ResponseDiv" style="height:22px;margin-top:5px" onMouseOver="showTip('<?php echo $help_entries["result_box"] ?>','lightblue','300')" onMouseOut="hideTip()">
</div>
<script>
<?php
if ($param_start != "" && $param_end != "" && date_parse($param_start) && date_parse($param_end)) {
    //print "setFixed('$param_start', '$param_end', '', '');\n";
} else {
    //print "RequestLines();MakeRequest();\n";
}
?>
</script>

<div id="dhtmltooltip"></div>

<script type="text/javascript">

/***********************************************
* Cool DHTML tooltip script- © Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

var offsetxpoint=-60 //Customize x offset of tooltip
var offsetypoint=20 //Customize y offset of tooltip
var ie=document.all
var ns6=document.getElementById && !document.all
var enabletip=false
if (ie||ns6)
	var tipobj=document.all? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : ""

	function ietruebody(){
		return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
	}

	function ddrivetip(thetext, thecolor, thewidth){
		if (ns6||ie){
			if (typeof thewidth!="undefined") tipobj.style.width=thewidth+"px"
				if (typeof thecolor!="undefined" && thecolor!="") tipobj.style.backgroundColor=thecolor
					tipobj.innerHTML=thetext
					enabletip=true
					return false
		}
	}

	function positiontip(e){
		if (enabletip){
			var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
			var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
			//Find out how close the mouse is to the corner of the window
			var rightedge=ie&&!window.opera? ietruebody().clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
			var bottomedge=ie&&!window.opera? ietruebody().clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20

			var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000

			//if the horizontal distance isn't enough to accomodate the width of the context menu
			if (rightedge<tipobj.offsetWidth)
				//move the horizontal position of the menu to the left by it's width
				tipobj.style.left=ie? ietruebody().scrollLeft+event.clientX-tipobj.offsetWidth+"px" : window.pageXOffset+e.clientX-tipobj.offsetWidth+"px"
				else if (curX<leftedge)
					tipobj.style.left="5px"
					else
						//position the horizontal position of the menu where the mouse is positioned
						tipobj.style.left=curX+offsetxpoint+"px"

						//same concept with the vertical position
						if (bottomedge<tipobj.offsetHeight)
							tipobj.style.top=ie? ietruebody().scrollTop+event.clientY-tipobj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-tipobj.offsetHeight-offsetypoint+"px"
							else
								tipobj.style.top=curY+offsetypoint+"px"
								tipobj.style.visibility="visible"
		}
	}

	function hideddrivetip(){
		if (ns6||ie){
			enabletip=false
			tipobj.style.visibility="hidden"
			tipobj.style.left="-1000px"
			tipobj.style.backgroundColor=''
			tipobj.style.width=''
		}
	}

	document.onmousemove=positiontip
</script>
<form action="validate.php" method="post" id="validate_form">
<input type="hidden" name="log">
<input type="hidden" name="start">
<input type="hidden" name="end">
<input type="hidden" name="logfile">
<input type="hidden" name="signature">
</form>
<script>
    function validate_signature(log,start,end,logfile,signature) {
        $('#validate_form input[name=log]').val(log);
        $('#validate_form input[name=start]').val(start);
        $('#validate_form input[name=end]').val(end);
        $('#validate_form input[name=logfile]').val(logfile);
        $('#validate_form input[name=signature]').val(signature);
        GB_show('<?=_("Validate signature")?>','#validate_form',300,600);
    }
</script>
</body>
</html>
