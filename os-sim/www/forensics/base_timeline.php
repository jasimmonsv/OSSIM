<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/
include_once ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
Session::logcheck("MenuEvents", "EventsForensics");
include ("geoip.inc");
require_once ('classes/Util.inc');
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$hosts_ips = array_keys($hosts);

($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_timeline.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$max = ImportHTTPVar("max", VAR_DIGIT);
if (!$max) $max=50;
$resolution = ImportHTTPVar("resolution", VAR_ALPHA);
if ($resolution=="") $resolution="m";
//
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_alerts, gettext("Most Frequent Events"), "occur_d");
$qs->AddCannedQuery("last_alerts", $last_num_ualerts, gettext("Last Events"), "last_d");
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = gettext("Event Listing");
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 0);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 0);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();
$qro = new QueryResultsOutput("base_qry_main.php" . $qs->SaveStateGET());
$qro->AddTitle(qroReturnSelectALLCheck());

// Timezone
$tz = Util::get_timezone();

/* Apply sort criteria */
if ($qs->isCannedQuery()) $sort_sql = " ORDER BY timestamp DESC ";
else {
	$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
    //  3/23/05 BDB   mods to make sort by work for Searches
    $sort_sql = "";
    if (!isset($sort_order)) {
        $sort_order = NULL;
    }
    if ($sort_order == "sip_a") {
        $sort_sql = " ORDER BY ip_src ASC";
        $criteria_sql = str_replace("1  AND ( timestamp", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sip_d") {
        $sort_sql = " ORDER BY ip_src DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_a") {
        $sort_sql = " ORDER BY ip_dst ASC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_d") {
        $sort_sql = " ORDER BY ip_dst DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sig_a") {
        $sort_sql = " ORDER BY plugin_id ASC,plugin_sid";
    } elseif ($sort_order == "sig_d") {
        $sort_sql = " ORDER BY plugin_id DESC,plugin_sid";
    } elseif ($sort_order == "time_a") {
        $sort_sql = " ORDER BY timestamp ASC";
    } elseif ($sort_order == "time_d") {
        $sort_sql = " ORDER BY timestamp DESC";
    } elseif ($sort_order == "oasset_d_a") {
        $sort_sql = " ORDER BY ossim_asset_dst ASC";
    } elseif ($sort_order == "oasset_d_d") {
        $sort_sql = " ORDER BY ossim_asset_dst DESC";
    } elseif ($sort_order == "oprio_a") {
        $sort_sql = " ORDER BY ossim_priority ASC";
    } elseif ($sort_order == "oprio_d") {
        $sort_sql = " ORDER BY ossim_priority DESC";
    } elseif ($sort_order == "oriska_a") {
        $sort_sql = " ORDER BY ossim_risk_c ASC";
    } elseif ($sort_order == "oriska_d") {
        $sort_sql = " ORDER BY ossim_risk_c DESC";
    } elseif ($sort_order == "oriskd_a") {
        $sort_sql = " ORDER BY ossim_risk_a ASC";
    } elseif ($sort_order == "oriskd_d") {
        $sort_sql = " ORDER BY ossim_risk_a DESC";
    } elseif ($sort_order == "oreli_a") {
        $sort_sql = " ORDER BY ossim_reliability ASC";
    } elseif ($sort_order == "oreli_d") {
        $sort_sql = " ORDER BY ossim_reliability DESC";
    } elseif ($sort_order == "proto_a") {
        $sort_sql = " ORDER BY ip_proto ASC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "proto_d") {
        $sort_sql = " ORDER BY ip_proto DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    }
}
// Choose the correct INDEX for select
if (preg_match("/^time/", $sort_order)) $sql.= " FORCE INDEX (timestamp)";
if (!$printing_ag) {
	$sql = $sql . $join_sql . $where_sql . $criteria_sql . $sort_sql. " LIMIT $max";
    /* ***** Generate and print the criteria in human readable form */
    echo '<TABLE WIDTH="100%">
           <TR>
             <TD WIDTH="60%" VALIGN=TOP>';
    if (!array_key_exists("minimal_view", $_GET)) {
        PrintCriteria($caller);
    }
    echo '</TD></tr><tr>
           <TD VALIGN=TOP>';
    if (!array_key_exists("minimal_view", $_GET)) {
        PrintFramedBoxHeader(gettext("Summary Statistics"), "#669999", "#FFFFFF");
        PrintGeneralStats($db, 1, $show_summary_stats, "$join_sql ", "$where_sql $criteria_sql");
    }
    PrintFramedBoxFooter();
    echo ' </TD>
           </TR>
          </TABLE>
		  <!-- END HEADER TABLE -->
		  
		  </div>  </TD>
           </TR>
          </TABLE>';
}
$i=0;
$qs->num_result_rows = $max;
$qs->current_view = 0;
$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
$hosts_ips = array_keys($hosts);
$report_data = array(); // data to fill report_data 

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while ($myrow = $result->baseFetchRow()) {
    //
    if ($tz!=0) $myrow["timestamp"] = gmdate("Y-m-d H:i:s",get_utc_unixtime($db,$myrow["timestamp"])+(3600*$tz));
    $current_sip32 = $myrow["ip_src"];
    $current_sip = baseLong2IP($current_sip32);
    $current_dip32 = $myrow["ip_dst"];
    $current_dip = baseLong2IP($current_dip32);
    $current_proto = $myrow["ip_proto"];
    $current_sport = $current_dport = "";
    if ($myrow["layer4_sport"] != 0) $current_sport = ":" . $myrow["layer4_sport"];
    if ($myrow["layer4_dport"] != 0) $current_dport = ":" . $myrow["layer4_dport"];
    $current_sig = BuildSigByPlugin($myrow["plugin_id"], $myrow["plugin_sid"], $db);
    $current_sig_txt = trim(html_entity_decode(strip_tags($current_sig)));
    $current_otype = $myrow["ossim_type"];
    $current_oprio = $myrow["ossim_priority"];
    $current_oreli = $myrow["ossim_reliability"];
    $current_oasset_s = $myrow["ossim_asset_src"];
    $current_oasset_d = $myrow["ossim_asset_dst"];
    $current_oriskc = $myrow["ossim_risk_c"];
    $current_oriska = $myrow["ossim_risk_a"];
    //
    if ($portscan_payload_in_signature == 1) {
        /* fetch from payload portscan open port number */
        if (stristr($current_sig_txt, "(portscan) Open Port")) {
            $sql2 = "SELECT data_payload FROM data WHERE sid='" . $myrow["sid"] . "' AND cid='" . $myrow["cid"] . "'";
            $result2 = $db->baseExecute($sql2);
            $myrow_payload = $result2->baseFetchRow();
            $result2->baseFreeRows();
            $myrow_payload = PrintCleanHexPacketPayload($myrow_payload[0], 2);
            $current_sig = $current_sig . str_replace("Open Port", "", $myrow_payload);
        }
        /* fetch from payload portscan port range */
        else if (stristr($current_sig_txt, "(portscan) TCP Portscan") || stristr($current_sig_txt, "(portscan) UDP Portscan")) {
            $sql2 = "SELECT data_payload FROM data WHERE sid='" . $myrow["sid"] . "' AND cid='" . $myrow["cid"] . "'";
            $result2 = $db->baseExecute($sql2);
            $myrow_payload = $result2->baseFetchRow();
            $result2->baseFreeRows();
            $myrow_payload = PrintCleanHexPacketPayload($myrow_payload[0], 2);
            $current_sig = $current_sig . stristr(stristr($myrow_payload, "Port/Proto Range") , ": ");
        }
    }
    $rowid = ($qs->GetCurrentView() * $show_rows) + $i;
    $tmpsig = explode("##", $current_sig);
	if ($tmpsig[1]!="") {
		$antes = $tmpsig[0];
		$despues = $tmpsig[1];
	} else {
		$antes = "";
		$despues = $current_sig;
	}
    // 5- Source IP Address
    if ($current_sip32 != "") {
        $country = strtolower(geoip_country_code_by_addr($gi, $current_sip));
        $country_name = geoip_country_name_by_addr($gi, $current_sip);
        if ($country) {
            $country_img = " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            $slnk = "<img src='../pixmaps/flags/".$country.".png' style='width:3mm'>";
            $slnkrd = $current_url."/pixmaps/flags/".$country.".png";
        } else {
            $country_img = "";
            $slnk = $slnkrd = "";
        }
        $sip_aux = ($sensors[$current_sip] != "") ? $sensors[$current_sip] : (($hosts[$current_sip] != "") ? $hosts[$current_sip] : $current_sip);
        $div = '<div id="'.$current_sip.';'.$ip_aux.'" class="HostReportMenu">';
		$bdiv = '</div>';
		$homelan = (($match_cidr = Net::is_ip_in_cache_cidr($_conn, $current_sip)) || in_array($current_sip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_sip'><img src=\"".Host::get_homelan_icon($current_sip,$icons,$match_cidr,$_conn)."\" border=0></a>" : "";
        if ($homelan!="") {
        	$slnk = "<img src='images/homelan.png' align='absmiddle' border=0 style='width:3mm'>"; 
        	$slnkrd = $current_url."/forensics/images/homelan.png";
        }
    }   
    // 6- Destination IP Address
    if ($current_dip32 != "") {
        $country = strtolower(geoip_country_code_by_addr($gi, $current_dip));
        $country_name = geoip_country_name_by_addr($gi, $current_dip);
        if ($country) {
            $country_img = " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
            $dlnk = "<img src='../pixmaps/flags/".$country.".png' style='width:3mm'>";
            $dlnkrd = $current_url."/pixmaps/flags/".$country.".png";
        } else {
            $country_img = "";
            $dlnk = $dlnkrd = "";
        }
        $dip_aux = ($sensors[$current_dip] != "") ? $sensors[$current_dip] : (($hosts[$current_dip] != "") ? $hosts[$current_dip] : $current_dip);
        $div = '<div id="'.$current_dip.';'.$ip_aux.'" class="HostReportMenu">';
		$bdiv = '</div>';
		$homelan = (($match_cidr = Net::is_ip_in_cache_cidr($_conn, $current_dip)) || in_array($current_dip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_dip'><img src=\"".Host::get_homelan_icon($current_dip,$icons,$match_cidr,$_conn)."\" border=0></a>" : "";
        if ($homelan!="") {
        	$dlnk = "<img src='images/homelan.png' align='absmiddle' border=0 style='width:3mm'>"; 
        	$dlnkrd = $current_url."/forensics/images/homelan.png";
        }
	}
    //
    $i++;
	$report_data[] = array (
        trim(html_entity_decode($despues)),
        $myrow["timestamp"],
        $sip_aux.$current_sport, $slnkrd,
        $dip_aux.$current_dport, $dlnkrd,
        $current_url."/forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5",
        $current_url."/forensics/bar2.php?value=" . $current_oprio . "&max=5",
        $current_url."/forensics/bar2.php?value=" . $current_oreli . "&max=9",
        $current_url."/forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1",
        IPProto2str($current_proto),$rowid,$myrow["sid"],$myrow["cid"]
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$events_report_type);
$qs->SaveState();
?>
<form action="base_timeline.php" id="ftl">
<table cellpadding=0 cellspacing=0 width="100%">
<tr>
<td align="left" style="padding-top:3px">
	<img src="../pixmaps/arrow_green.gif" border=0 align="absmiddle"> <?=_("Timeline resolution")?>:&nbsp;
	<input type="radio" name="resolution" onclick="$('#ftl').submit()" value="s"<?=($resolution=="s") ? " checked" : ""?>> <?=_("Seconds")?>&nbsp;
	<input type="radio" name="resolution" onclick="$('#ftl').submit()" value="m"<?=($resolution=="m") ? " checked" : ""?>> <?=_("Minutes")?>&nbsp;
	<input type="radio" name="resolution" onclick="$('#ftl').submit()" value="h"<?=($resolution=="h") ? " checked" : ""?>> <?=_("Hours")?>&nbsp;
	<input type="radio" name="resolution" onclick="$('#ftl').submit()" value="d"<?=($resolution=="d") ? " checked" : ""?>> <?=_("Days")?>&nbsp;
</td>
<td align="right" style="padding-top:3px">
	<img src="../pixmaps/arrow_green.gif" border=0 align="absmiddle"> <?=_("Events to draw")?>: 
	<select name="max" onchange="$('#ftl').submit()">
		<option<?=($max=="50") ? " selected" : ""?>>50</option>
		<option<?=($max=="100") ? " selected" : ""?>>100</option>
		<option<?=($max=="250") ? " selected" : ""?>>250</option>
		<option<?=($max=="500") ? " selected" : ""?>>500</option>
		<option<?=($max=="1000") ? " selected" : ""?>>1000</option>
		<option<?=($max=="5000") ? " selected" : ""?>>5000</option>
	</select>
</td>
</tr>
</table>
</form>

<IFRAME style="width:100%; height: 450px; margin:5px 0px 0px 0px;padding:0px;border:1px solid #CCCCCC;" frameborder="0" scrolling="no" name="forum" src="base_timeline_ifr.php?resolution=<?=$resolution?>"></IFRAME>

<?
PrintBASESubFooter();
echo "</body>\r\n</html>";
?>
