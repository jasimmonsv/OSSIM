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

if (!isset($_SESSION["_user"])) {
    require_once("ossim_conf.inc");
    $conf = $GLOBALS["CONF"];
	$ossim_link = $conf->get_conf("ossim_link", FALSE);
    $login_location = $ossim_link . '/session/login.php';
	header("Location: $login_location");
	exit;
}
// Timezone correction
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
$timetz = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction

// Custom Views
require_once('classes/User_config.inc');
$login = Session::get_session_user();
$db_aux = new ossim_db();
$conn_aux = $db_aux->connect();
$config = new User_config($conn_aux);
$_SESSION['views'] = $config->get($login, 'custom_views', 'php', "siem");

// First save of default view (important!)
if ($_SESSION['views']['default'] == "") {
	$_SESSION['views']['default']['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','IP_PORTDST','ASSET','PRIORITY','RELIABILITY','RISK','IP_PROTO');
	$session_data = $_SESSION;
	foreach ($_SESSION as $k => $v) {
	if (preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|deletetask|mdspw).*/",$k))
		unset($session_data[$k]);		
	}
	$_SESSION['views']['default']['data'] = $session_data;
	$config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
}
$db_aux->close($conn_aux);

if ($_SESSION['view_name_changed']) { $_GET['custom_view'] = $_SESSION['view_name_changed']; $_SESSION['view_name_changed'] = ""; $_SESSION['norefresh'] = 1; }
else $_SESSION['norefresh'] = "";
$custom_view = $_GET['custom_view'];
if ($custom_view != "") {
	$_SESSION['current_cview'] = $custom_view;
	if (is_array($_SESSION['views'][$custom_view]['data']))
		foreach ($_SESSION['views'][$custom_view]['data'] as $skey=>$sval) {
			if (!preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|deletetask|mdspw).*/",$skey))
			    $_SESSION[$skey] = $sval;
			else
                unset($_SESSION[$skey]);
		}
}
if ($_SESSION['current_cview'] == "") $_SESSION['current_cview'] = 'default';
// Columns data (for matching on print functions)
$_SESSION['views_data'] = array(
	"SID_NAME" => array("title"=>"sid name","width"=>"40","celldata" => ""),
	"IP_PROTO" => array("title"=>"L4-proto","width"=>"40","celldata" => "")
);
// TIME RANGE
if ($_GET['time_range'] != "") {
    // defined => save into session
    if (isset($_GET['time'])) $_SESSION['time'] = $_GET['time'];
    if (isset($_GET['time_cnt'])) $_SESSION['time_cnt'] = $_GET['time_cnt'];
    if (isset($_GET['time_range'])) $_SESSION['time_range'] = $_GET['time_range'];
} elseif ($_SESSION['time_range'] != "" && $_GET['date_range'] == "") {
    // not defined => load from session or unset
    if ($_GET["clear_criteria"] == "time") {
        unset($_SESSION['time']);
        unset($_SESSION['time_cnt']);
        $_GET['time_range'] = "all";
        $_SESSION['time_range'] = $_GET['time_range'];
    } else {
        if (isset($_SESSION['time'])) $_GET['time'] = $_SESSION['time'];
        if (isset($_SESSION['time_cnt'])) $_GET['time_cnt'] = $_SESSION['time_cnt'];
        if (isset($_SESSION['time_range'])) $_GET['time_range'] = $_SESSION['time_range'];
    }
} elseif ($_GET['date_range'] == "week") {
	$start_week = explode("-",date("Y-m-d", $timetz - (24 * 60 * 60 * 7)));
	$_GET['time'][0] = array(
        null,
        ">=",
        $start_week[1] ,
        $start_week[2] ,
        $start_week[0] ,
        null,
        null,
        null,
        null,
        null
    );
    $_GET['time_cnt'] = "1";
    $_GET['time_range'] = "week";
    $_SESSION['time'] = $_GET['time'];
    $_SESSION['time_cnt'] = $_GET['time_cnt'];
    $_SESSION['time_range'] = $_GET['time_range'];
} else {
    // default => load today values
    $_GET['time'][0] = array(
        null,
        ">=",
        date("m",$timetz) ,
        date("d",$timetz) ,
        date("Y",$timetz) ,
        null,
        null,
        null,
        null,
        null
    );
    $_GET['time_cnt'] = "1";
    $_GET['time_range'] = "today";
    $_SESSION['time'] = $_GET['time'];
    $_SESSION['time_cnt'] = $_GET['time_cnt'];
    $_SESSION['time_range'] = $_GET['time_range'];
}
// NUMEVENTS
$numevents = intval($_GET["numevents"]);
if ($numevents>0) {
	GLOBAL $show_rows;
	$show_rows = $numevents;
}
// PAYLOAD
// IP
// LAYER 4 PROTO
//print_r($_GET);
//print_r($_SESSION['time']);

// IP search by url (host report link)
if (preg_match("/^\d+\.\d+\.\d+\.\d+$/",$_GET["ip"])) {
	$ip_aux = explode (".",$_GET['ip']);
	$_SESSION["ip_addr"] = array (
		array ("","ip_both","=",$ip_aux[0],$ip_aux[1],$ip_aux[2],$ip_aux[3],$_GET['ip'])
	);
	$_SESSION["ip_addr_cnt"] = 1;
	$_SESSION["ip_field"] = array (
		array ("","","=")
	);
	$_SESSION["ip_field_cnt"] = 1;
}
//
// DATABASES
//
if ($_GET["server"]!="") {
	if ($_GET["server"]=="local") unset($_SESSION["server"]);
	else $_SESSION["server"] = explode(":",base64_decode($_GET["server"]));
}
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="") {
	// change connect variables
	$alert_host = $_SESSION["server"][0];
	$alert_port = $_SESSION["server"][1];
	$alert_user = $_SESSION["server"][2];
	$alert_password = $_SESSION["server"][3];
	$alert_dbname = $_SESSION["server"][4];
	require_once ("$BASE_path/includes/base_db.inc.php");
	$dbtest = NewBASEDBConnection($DBlib_path, $DBtype);
	$dbtest->DB = NewADOConnection();
	error_reporting(E_ERROR | E_PARSE);
	if (!$dbtest->DB->PConnect((($alert_port == "") ? $alert_host : ($alert_host . ":" . $alert_port)), $alert_user, $alert_password, $alert_dbname)) {
		unset($_SESSION['server']);
		echo "<br>&nbsp;<font style='font-family:arial;font-size:11px'><b>ERROR</b>: "._("Unable to connect")." ".$alert_dbname." ($alert_host). Connection restored to local.";
		echo "<br>&nbsp;<a href='base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d' style='font-family:arial;font-size:11px'><u>Click here to continue</u></a>";
		exit;
	}
	error_reporting(E_ALL ^ E_NOTICE);
}

$current_url = Util::get_ossim_url();
$events_report_type = 33;
$graph_report_type = 34;
$criteria_report_type = 35;
$unique_events_report_type = 36;
$unique_iplinks_report_type = 37;
$sensors_report_type = 38;
$unique_addr_report_type = 40;
$src_port_report_type = 42;
$dst_port_report_type = 44;
$unique_plugins_report_type = 46;
$unique_country_events_report_type = 48;
//
$current_cols_titles = array(
    "SIGNATURE" => _("Signature"),
    "DATE" => _("Date")." ".Util::timezone($tz),
    "IP_PORTSRC" => _("Source"),
    "IP_PORTDST" => _("Dest."),
    "IP_SRC" => _("Src IP"),
    "IP_DST" => _("Dst IP"),   
    "IP_SRC_FQDN" => _("Src IP FQDN"),
    "IP_DST_FQDN" => _("Dst IP FQDN"),     
    "PORT_SRC" => _("Src Port"),
    "PORT_DST" => _("Dst Port"),
    "ASSET" => _("Asset &nbsp;<br>S<img src='images/arrow-000-small.gif' border=0 align=absmiddle>D"),
    "PRIORITY" => _("Prio"),
    "RELIABILITY" => _("Rel"),
    "RISK" => _("Risk"),
    "IP_PROTO" => _("L4-proto"),
    "USERDATA1" => _("Userdata1"),
    "USERDATA2" => _("Userdata2"),
    "USERDATA3" => _("Userdata3"),
    "USERDATA4" => _("Userdata4"),
    "USERDATA5" => _("Userdata5"),
    "USERDATA6" => _("Userdata6"),
    "USERDATA7" => _("Userdata7"),
    "USERDATA8" => _("Userdata8"),
    "USERDATA9" => _("Userdata9"),
    "USERNAME" => _("Username"),
    "FILENAME" => _("Filename"),
    "PASSWORD" => _("Password"),
    "PAYLOAD" => _("Payload"),
    "SID" => _("SID"),
    "CID" => _("CID"),
    "PLUGIN_ID" => _("Data Source ID"),
    "PLUGIN_SID" => _("Event Type ID"),
    "PLUGIN_DESC" => _("Data Source Description"),
    "PLUGIN_NAME" => _("Data Source Name"),
    "PLUGIN_SOURCE_TYPE" => _("Source Type"),
    "PLUGIN_SID_CATEGORY" => _("Category"),
    "PLUGIN_SID_SUBCATEGORY" => _("SubCategory"),
    'CONTEXT' => _("Context")
);
$current_cols_widths = array(
    "SIGNATURE" => "45mm",
    "IP_PORTSRC" => "25mm",
    "IP_PORTDST" => "25mm",
    "ASSET" => "12mm",
    "PRIORITY" => "12mm",
    "RELIABILITY" => "12mm",
    "RISK" => "12mm",
    "IP_PROTO" => "10mm",
);
$siem_events_title = _("SIEM Events events");
?>