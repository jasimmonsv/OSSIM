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
// PDF REPORT
/*
require_once ('classes/pdfReport.inc');
$pdfReport = new PdfReport($siem_events_title);
$htmlPdfReport = new Html($siem_events_title,$siem_events_title,'','font-size:10px');
*/
// GEOIP
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
global $colored_alerts, $debug_mode;
/* **************** Run the Query ************************************************** */
/* base_ag_main.php will include this file
*  - imported variables: $sql, $cnt_sql
*/
if ($printing_ag) {
	ProcessCriteria();
    $page = "base_ag_main.php";
    $tmp_page_get = "&amp;ag_action=view&amp;ag_id=$ag_id&amp;submit=x";
    $sql = $save_sql;
} else {
	$page = "base_qry_main.php";
    $cnt_sql = "SELECT COUNT(acid_event.cid) FROM acid_event " . $join_sql . $where_sql . $criteria_sql;
    $tmp_page_get = "";
}
// Timezone
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;

/* Run the query to determine the number of rows (No LIMIT)*/
//$qs->GetNumResultRows($cnt_sql, $db);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("$page" . $qs->SaveStateGET() . $tmp_page_get);
$qro->AddTitle(qroReturnSelectALLCheck());
//$qro->AddTitle("ID");
$qro->AddTitle("SIGNATURE", "sig_a", " ", " ORDER BY plugin_id ASC,plugin_sid", "sig_d", " ", " ORDER BY plugin_id DESC,plugin_sid");
$qro->AddTitle("DATE", "time_a", " ", " ORDER BY timestamp ASC ", "time_d", " ", " ORDER BY timestamp DESC ");
$qro->AddTitle("IP_PORTSRC", "sip_a", " ", " ORDER BY ip_src ASC", "sip_d", " ", " ORDER BY ip_src DESC");
$qro->AddTitle("IP_PORTDST", "dip_a", " ", " ORDER BY ip_dst ASC", "dip_d", " ", " ORDER BY ip_dsat DESC");
//$qro->AddTitle("Asset", "oasset_d_a", " ", " ORDER BY ossim_asset_dst ASC", "oasset_d_d", " ", " ORDER BY ossim_asset_dst DESC");
//$qro->AddTitle("Asset", "oasset_s_a", " ", " ORDER BY ossim_asset_src ASC", "oasset_s_d", " ", " ORDER BY ossim_asset_src DESC", "oasset_d_a", " ", " ORDER BY ossim_asset_dst ASC", "oasset_d_d", " ", " ORDER BY ossim_asset_dst DESC");
$qro->AddTitle("ASSET");
$qro->AddTitle("PRIORITY", "oprio_a", " ", " ORDER BY ossim_priority ASC", "oprio_d", " ", " ORDER BY ossim_priority DESC");
$qro->AddTitle("RELIABILITY", "oreli_a", " ", " ORDER BY ossim_reliability ASC", "oreli_d", " ", " ORDER BY ossim_reliability DESC");
//$qro->AddTitle("Risk", "oriska_a", " ", " ORDER BY ossim_risk_a ASC", "oriska_d", " ", " ORDER BY ossim_risk_a DESC");
$qro->AddTitle("RISK", "oriska_a", " ", " ORDER BY ossim_risk_c ASC", "oriska_d", " ", " ORDER BY ossim_risk_c DESC", "oriskd_a", " ", " ORDER BY ossim_risk_a ASC", "oriskd_d", " ", " ORDER BY ossim_risk_a DESC");
//$qro->AddTitle("L4-proto", "proto_a", " ", " ORDER BY ip_proto ASC", "proto_d", " ", " ORDER BY ip_proto DESC");
$qro->AddTitle("IP_PROTO");
$qro->AddTitle("IP_SRC");
$qro->AddTitle("IP_SRC_FQDN");
$qro->AddTitle("IP_DST");
$qro->AddTitle("IP_DST_FQDN");
$qro->AddTitle("PORT_SRC");
$qro->AddTitle("PORT_DST");
$qro->AddTitle("USERDATA1");
$qro->AddTitle("USERDATA2");
$qro->AddTitle("USERDATA3");
$qro->AddTitle("USERDATA4");
$qro->AddTitle("USERDATA5");
$qro->AddTitle("USERDATA6");
$qro->AddTitle("USERDATA7");
$qro->AddTitle("USERDATA8");
$qro->AddTitle("USERDATA9");
$qro->AddTitle("USERNAME");
$qro->AddTitle("FILENAME");
$qro->AddTitle("PASSWORD");
$qro->AddTitle("PAYLOAD");
$qro->AddTitle("SID");
$qro->AddTitle("CID");
$qro->AddTitle("PLUGIN_ID");
$qro->AddTitle("PLUGIN_SID");
$qro->AddTitle("PLUGIN_DESC");
$qro->AddTitle("PLUGIN_NAME");
$qro->AddTitle("PLUGIN_SOURCE_TYPE");
$qro->AddTitle("PLUGIN_SID_CATEGORY");
$qro->AddTitle("PLUGIN_SID_SUBCATEGORY");
$qro->AddTitle("CONTEXT");
/* Apply sort criteria */
if ($qs->isCannedQuery()) $sort_sql = " ORDER BY timestamp DESC ";
else {
	//$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
    //  3/23/05 BDB   mods to make sort by work for Searches
    $sort_sql = "";
    if (!isset($sort_order)) {
        $sort_order = NULL;
    }
    if ($sort_order == "sip_a") {
        $sort_sql = " ORDER BY ip_src ASC,timestamp DESC";
        $criteria_sql = str_replace("1  AND ( timestamp", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sip_d") {
        $sort_sql = " ORDER BY ip_src DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_a") {
        $sort_sql = " ORDER BY ip_dst ASC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_d") {
        $sort_sql = " ORDER BY ip_dst DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sig_a") {
        $sort_sql = " ORDER BY plugin_id ASC,plugin_sid,timestamp DESC";
    } elseif ($sort_order == "sig_d") {
        $sort_sql = " ORDER BY plugin_id DESC,plugin_sid,timestamp DESC";
    } elseif ($sort_order == "time_a") {
        $sort_sql = " ORDER BY timestamp ASC";
    } elseif ($sort_order == "time_d") {
        $sort_sql = " ORDER BY timestamp DESC";
    } elseif ($sort_order == "oasset_d_a") {
        $sort_sql = " ORDER BY ossim_asset_dst ASC,timestamp DESC";
    } elseif ($sort_order == "oasset_d_d") {
        $sort_sql = " ORDER BY ossim_asset_dst DESC,timestamp DESC";
    } elseif ($sort_order == "oprio_a") {
        $sort_sql = " ORDER BY ossim_priority ASC,timestamp DESC";
    } elseif ($sort_order == "oprio_d") {
        $sort_sql = " ORDER BY ossim_priority DESC,timestamp DESC";
    } elseif ($sort_order == "oriska_a") {
        $sort_sql = " ORDER BY ossim_risk_c ASC,timestamp DESC";
    } elseif ($sort_order == "oriska_d") {
        $sort_sql = " ORDER BY ossim_risk_c DESC,timestamp DESC";
    } elseif ($sort_order == "oriskd_a") {
        $sort_sql = " ORDER BY ossim_risk_a ASC,timestamp DESC";
    } elseif ($sort_order == "oriskd_d") {
        $sort_sql = " ORDER BY ossim_risk_a DESC,timestamp DESC";
    } elseif ($sort_order == "oreli_a") {
        $sort_sql = " ORDER BY ossim_reliability ASC,timestamp DESC";
    } elseif ($sort_order == "oreli_d") {
        $sort_sql = " ORDER BY ossim_reliability DESC,timestamp DESC";
    } elseif ($sort_order == "proto_a") {
        $sort_sql = " ORDER BY ip_proto ASC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "proto_d") {
        $sort_sql = " ORDER BY ip_proto DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    }
    ExportHTTPVar("prev_sort_order", $sort_order);
}
// Choose the correct INDEX for select
if (preg_match("/^time/", $sort_order)) $sql.= " FORCE INDEX (timestamp)";
//elseif (preg_match("/^sip/", $sort_order)) $sql.= " FORCE INDEX (ip_src)";
//elseif (preg_match("/^dip/", $sort_order)) $sql.= " FORCE INDEX (ip_dst)";
//elseif (preg_match("/^sig/", $sort_order)) $sql.= " FORCE INDEX (sig_name)";
//elseif (preg_match("/^oasset/", $sort_order)) $sql.= " FORCE INDEX (ossim_asset_dst)";
//elseif (preg_match("/^oprio/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_priority)";
//elseif (preg_match("/^oriska/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_risk_a)";
//elseif (preg_match("/^oriskd/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_risk_c)";
//elseif (preg_match("/^oreli/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_reliability)";
//elseif (preg_match("/^proto/", $sort_order)) $sql.= " FORCE INDEX (ip_proto)";
// Make SQL string with criterias
if (!$printing_ag) $sql = $sql . $join_sql . $where_sql . $criteria_sql . $sort_sql;
if ($debug_mode > 0) {
    echo "<P>SUBMIT: $submit";
    echo "<P>sort_order: $sort_order";
    echo "<P>SQL (save_sql): $sql";
    echo "<P>SQL (sort_sql): $sort_sql";
}
/* Run the Query again for the actual data (with the LIMIT) */
//$result = ""; // $qs->ExecuteOutputQuery($sql, $db);
//echo $sql."<br>".$timetz;

$_SESSION['siem_current_query'] = $sql;

$result = $qs->ExecuteOutputQuery($sql, $db);
$et->Mark("Retrieve Query Data");
// Optimization UPDATE using SQL_CALC_FOUND_ROWS (2/02/2009 - Granada)
$qs->GetCalcFoundRows($cnt_sql, $db);
if ($debug_mode > 0) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
if (!$printing_ag) {
    /* ***** Generate and print the criteria in human readable form */
    echo '<TABLE WIDTH="100%">
           <TR>
             <TD VALIGN=TOP>';
    if (!array_key_exists("minimal_view", $_GET)) {
    	PrintCriteria($caller);
    }
    echo '</TD></tr><tr>
           <TD VALIGN=TOP>';
    if (!array_key_exists("minimal_view", $_GET)) {
        PrintFramedBoxHeader(gettext("Summary Statistics"), "#669999", "#FFFFFF");
        PrintGeneralStats($db, 1, $show_summary_stats, "$join_sql ", "$where_sql $criteria_sql");
		PrintFramedBoxFooter();
    }
	echo '</TD></tr>';
	
	echo '<tr><td style="padding-top:10px; padding-right:25px; text-align:right;">';
	
    //PrintCustomViews();
	PrintPredefinedViews();
	
    echo ' </td>
           </tr>';
    echo '
          </TABLE>
		  <!-- END HEADER TABLE -->
		  
		  </div> </TD>
           </TR>
          </TABLE>';
}
/* Clear the old checked positions */
for ($i = 0; $i < $show_rows; $i++) {
    $action_lst[$i] = "";
    $action_chk_lst[$i] = "";
}
// time selection for graph x
$tr = ($_SESSION["time_range"] != "") ? $_SESSION["time_range"] : "all";
$trdata = array(0,0,$tr);
if ($tr=="range") {
    $desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3]);
    $hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3]);
    $diff = $hasta - $desde; 
    if ($diff > 2592000) $tr = "all";
    elseif ($diff > 1296000) $tr = "month";
    elseif ($diff > 604800) $tr = "weeks";
    elseif ($diff > 172800) $tr = "week";
    else $tr = "day";
    $trdata = array ($desde,$hasta,"range");
}
$tzc = ($tz>0) ? "+$tz:00" : "$tz:00";
switch ($tr) {
    case "today":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, 'h' as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "day":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "week":
    case "weeks":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    case "month":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    default:
        $interval = "monthname(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, year(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
}
//$sqlgraph = "SELECT COUNT(acid_event.cid) as num_events, $interval FROM acid_event " . $join_sql . $where_sql . $criteria_sql . $grpby;
$sqlgraph = "SELECT COUNT(acid_event.cid) as num_events, $interval FROM acid_event " . $join_sql . $where_sql . $criteria_sql . $grpby;
//echo $sqlgraph."<br>";
/* Print the current view number and # of rows */

$_SESSION['siem_current_query_graph'] = $sqlgraph;

// do we need load extradata?
$need_extradata = 0;
foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $field) {
	if (preg_match("/^(USERDATA|USERNAME|FILENAME|PASSWORD|PAYLOAD|CONTEXT)/i",$field))
		$need_extradata=1;
}

$qs->PrintResultCnt($sqlgraph, $trdata); //base_state_query.inc.php
// COLUMNS of Events Table (with ORDER links)
//$htmlPdfReport->set('<table cellpadding=2 cellspacing=0 class="w100">');
$qro->PrintHeader('',1);
$i = 0;
$hosts_ips = array_keys($hosts);
$report_data = array(); // data to fill report_data 

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
    
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    unset($cell_data);
    unset($cell_more);
    unset($cell_pdfdata);
    unset($cell_align);
    unset($cell_tooltip);
    $current_sig = BuildSigByPlugin($myrow["plugin_id"], $myrow["plugin_sid"], $db);
    if (preg_match("/FILENAME|USERNAME|PASSWORD|PAYLOAD|USERDATA\d+/",$current_sig)) $need_extradata = 1;
    //
    // Load extra data if neccesary
    
    if ($need_extradata && !array_key_exists("username",$myrow)) {
		$rs_ed = $qs->ExecuteOutputQueryNoCanned("SELECT * FROM extra_data WHERE sid=".$myrow["sid"]." AND cid=".$myrow["cid"], $db);
	    while ($row_ed = $rs_ed->baseFetchRow()) {
	    	foreach ($row_ed as $k => $v) $myrow[$k] = $v;
	    }
	    $rs_ed->baseFreeRows();
	}
    //
    // SID, CID, PLUGIN_*
    $cell_data['SID'] = $myrow["sid"];
    $cell_align['SID'] = "center";
    $cell_data['CID'] = $myrow["cid"];
    $cell_align['CID'] = "center";
    $cell_data['PLUGIN_ID'] = $myrow["plugin_id"];
    $cell_align['PLUGIN_ID'] = "center";
    $cell_data['PLUGIN_SID'] = $myrow["plugin_sid"];
    $cell_align['PLUGIN_SID'] = "center";
    if (in_array("PLUGIN_NAME",$_SESSION['views'][$_SESSION['current_cview']]['cols']) || in_array("PLUGIN_DESC",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        list($cell_data['PLUGIN_NAME'],$cell_data['PLUGIN_DESC']) = GetPluginNameDesc($myrow["plugin_id"], $db);
        $cell_align['PLUGIN_NAME'] = $cell_align['PLUGIN_DESC'] = "left";
    }
    if (in_array("PLUGIN_SOURCE_TYPE",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        $cell_data['PLUGIN_SOURCE_TYPE'] = ($opensource) ? _("Only in Profesional version") : GetSourceType($myrow["plugin_id"],$db);
        $cell_align['PLUGIN_SOURCE_TYPE'] = "center";
    }
    if (in_array("PLUGIN_SID_CATEGORY",$_SESSION['views'][$_SESSION['current_cview']]['cols']) || in_array("PLUGIN_SID_SUBCATEGORY",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        list($cell_data['PLUGIN_SID_CATEGORY'],$cell_data['PLUGIN_SID_SUBCATEGORY']) = ($opensource) ? array(_("Only in Profesional version"),_("Only in Profesional version")) : GetCategorySubCategory($myrow["plugin_id"],$myrow["plugin_sid"],$db);
        $cell_align['PLUGIN_SID_CATEGORY'] = $cell_align['PLUGIN_SID_SUBCATEGORY'] = "center";
    }
    //
    $current_sip32 = $myrow["ip_src"];
    $current_sip = baseLong2IP($current_sip32);
    $current_dip32 = $myrow["ip_dst"];
    $current_dip = baseLong2IP($current_dip32);
    $current_proto = $myrow["ip_proto"];
    $current_sport = $current_dport = "";
    if ($myrow["layer4_sport"] != 0) $current_sport = ":" . $myrow["layer4_sport"];
    if ($myrow["layer4_dport"] != 0) $current_dport = ":" . $myrow["layer4_dport"];
    if ($debug_mode > 1) {
        SQLTraceLog("\n\n");
        SQLTraceLog(__FILE__ . ":" . __LINE__ . ":\n############## <calls to BuildSigByID> ##################");
    }
    // SIGNATURE
    $current_sig = TranslateSignature($current_sig,$myrow);
    $current_sig_txt = trim(html_entity_decode(strip_tags($current_sig)));
    //$current_sig_txt = BuildSigByID($myrow[2], $myrow["sid"], $myrow["cid"], $db, 2);
    if ($debug_mode > 1) {
        SQLTraceLog(__FILE__ . ":" . __LINE__ . ":\n################ </calls to BuildSigByID> ###############");
        SQLTraceLog("\n\n");
    }
    $current_otype = $myrow["ossim_type"];
    $current_oprio = $myrow["ossim_priority"];
    $current_oreli = $myrow["ossim_reliability"];
    $current_oasset_s = $myrow["ossim_asset_src"];
    $current_oasset_d = $myrow["ossim_asset_dst"];
    $current_oriskc = $myrow["ossim_risk_c"];
    $current_oriska = $myrow["ossim_risk_a"];
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
    //$current_sig = GetTagTriger($current_sig, $db, $myrow[0], $myrow[1]);
    // ********************** EVENTS TABLE **********************
    // <TR>
    //qroPrintEntryHeader((($colored_alerts == 1) ? GetSignaturePriority($myrow[2], $db) : $i) , $colored_alerts);
    qroPrintEntryHeader($i , $colored_alerts);
    $rowid = ($qs->GetCurrentView() * $show_rows) + $i;
    $tmp_rowid = "#" . $rowid . "-(" . $myrow["sid"] . "-" . $myrow["cid"] . ")";
    // <TD>
    // 1- Checkbox
    qroPrintEntry('<INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . htmlspecialchars($tmp_rowid) . '">',"","","","style='border-left:1px solid white;border-top:1px solid white'");
    echo '    <INPUT TYPE="hidden" NAME="action_lst['.$i.']" VALUE="'.htmlspecialchars($tmp_rowid).'">';
    // 2- ID
    
    /** Fix for bug #1116034 -- Input by Tim Rupp, original solution and code by Alejandro Flores **/
    //$temp = "<A HREF='base_qry_alert.php?submit=".rawurlencode($tmp_rowid)."&amp;sort_order=";
    //$temp .= ($qs->isCannedQuery()) ? $qs->getCurrentCannedQuerySort() : $qs->getCurrentSort();
    //$temp .= "'>".$tmp_rowid."</a>";
    //qroPrintEntry($temp);
    //$temp = "";
    // 3- Signature
    $tmpsig = explode("##", $current_sig);
	if ($tmpsig[1]!="") {
		$antes = $tmpsig[0];
		$despues = $tmpsig[1];
	} else {
		$antes = "";
		$despues = $current_sig;
	}
    //$temp = $tmpsig[0]."] <A HREF='base_qry_alert.php?submit=".rawurlencode($tmp_rowid)."&amp;sort_order=";
    $temp = "$antes <A HREF='base_qry_alert.php?submit=" . rawurlencode($tmp_rowid) . "&amp;sort_order=";
    $temp.= ($qs->isCannedQuery()) ? $qs->getCurrentCannedQuerySort() : $qs->getCurrentSort();
    $temp.= "'>" . $despues . "</a>"; // $tmpsig[1]
    $cell_data['SIGNATURE'] = $temp;
    $cell_pdfdata['SIGNATURE'] = html_entity_decode($despues);
	$cell_align['SIGNATURE'] = "left";
    if ($_SESSION['current_cview']=="default") $cell_more['SIGNATURE'] = "width='25%'"; // only in default view
    $temp = "";
    // 4- Timestamp
    //qroPrintEntry($myrow["timestamp"], "center");

    $tzone = $myrow['tzone'];
    $event_date = $myrow['timestamp'];
    $tzdate = $event_date;
    $event_date_uut = get_utc_unixtime($db,$event_date);
    // Event date timezone
	if ($tzone!=0) $event_date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tzone));    
    // Apply user timezone
	if ($tz!=0) $tzdate = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));
		    
	$cell_data['DATE'] = $tzdate;
	$cell_tooltip['DATE'] = ($event_date==$myrow['timestamp'] || $event_date==$tzdate) ? "" : _("Event date").": ".htmlspecialchars("<b>".$event_date."</b><br>"._("Timezone").": <b>".Util::timezone($tzone)."</b>");
	$cell_pdfdata['DATE'] = str_replace(" ","<br>",$tzdate);
	$cell_align['DATE'] = "center";
	$cell_more['DATE'] = "nowrap";
    //$tmp_iplookup = 'base_qry_main.php?sig%5B0%5D=%3D' . '&amp;num_result_rows=-1' . '&amp;time%5B0%5D%5B0%5D=+&amp;time%5B0%5D%5B1%5D=+' . '&amp;submit=' . gettext("Query+DB") . '&amp;current_view=-1&amp;ip_addr_cnt=2';
    /* TCP or UDP show the associated port #
    if ( ($current_proto == TCP) || ($current_proto == UDP) )
    $result4 = $db->baseExecute("SELECT layer4_sport, layer4_dport FROM acid_event ".
    "WHERE sid='".$myrow[0]."' AND cid='".$myrow[1]."'");
    
    if ( ($current_proto == TCP) || ($current_proto == UDP) )
    {
    $myrow4 = $result4->baseFetchRow();
    
    if ( $myrow4[0] != "" )  $current_sport = ":".$myrow4[0];
    if ( $myrow4[1] != "" )  $current_dport = ":".$myrow4[1];
    }
    */
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
		$homelan = (Net::is_ip_in_cache_cidr($_conn, $current_sip) || in_array($current_sip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_sip'><img src=\"images/homelan.png\" border=0></a>" : "";
        if ($homelan!="") {
        	$slnk = "<img src='images/homelan.png' align='absmiddle' border=0 style='width:3mm'>"; 
        	$slnkrd = $current_url."/forensics/images/homelan.png";
        }
		$cell_data['IP_PORTSRC'] = $div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $sip_aux . '</A><FONT SIZE="-1">' . $current_sport . '</FONT>' . $country_img . $homelan . $bdiv;
        $cell_pdfdata['IP_PORTSRC'] = $sip_aux.$current_sport.$slnk;
		$cell_data['IP_SRC'] = $current_sip . $country_img . $homelan;
		$cell_data['PORT_SRC'] = str_replace(":","",$current_sport);
		//qroPrintEntry($div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $sip_aux . '</A><FONT SIZE="-1">' . $current_sport . '</FONT>' . $country_img . $homelan . $bdiv, 'center', 'top', 'nowrap');
    } else {
        /* if no IP address was found check if this is a spp_portscan message
        * and try to extract a source IP
        * - contrib: Michael Bell <michael.bell@web.de>
        */
        if (stristr($current_sig_txt, "portscan")) {
            $line = split(" ", $current_sig_txt);
            foreach($line as $ps_element) {
                if (ereg("[0-9]*\.[0-9]*\.[0-9]*\.[0-9]", $ps_element)) {
                    $ps_element = ereg_replace(":", "", $ps_element);
                    $div = '<div id="'.$ps_element.';'.$ps_element.'" class="HostReportMenu">';
					$bdiv = "</div>";
					$cell_data['IP_PORTSRC'] = "$div<A HREF=\"base_stat_ipaddr.php?ip=" . $ps_element . "&amp;netmask=32\">" . $ps_element . "</A>$bdiv";
					//qroPrintEntry("$div<A HREF=\"base_stat_ipaddr.php?ip=" . $ps_element . "&amp;netmask=32\">" . $ps_element . "</A>$bdiv");
                }
            }
        } else {
			//qroPrintEntry('<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . gettext("unknown") . '</A>');
			$cell_data['IP_PORTSRC'] = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . gettext("unknown") . '</A>';
		}
		$cell_data['IP_SRC'] = gettext("unknown");
		$cell_data['PORT_SRC'] = gettext("unknown");
    }
    $cell_align['IP_PORTSRC'] = "center";
    $cell_align['IP_SRC'] = "center";
    $cell_align['PORT_SRC'] = "center";
	if (in_array("IP_SRC_FQDN",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
		$cell_data['IP_SRC_FQDN'] = baseGetHostByAddr($current_sip, $db, $dns_cache_lifetime);
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
		$homelan = (Net::is_ip_in_cache_cidr($conn, $current_dip) || in_array($current_dip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_dip'><img src=\"images/homelan.png\" border=0></a>" : "";
        if ($homelan!="") {
        	$dlnk = "<img src='images/homelan.png' align='absmiddle' border=0 style='width:3mm'>"; 
        	$dlnkrd = $current_url."/forensics/images/homelan.png";
        }
		$cell_data['IP_PORTDST'] = $div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask32">' . $dip_aux . '</A><FONT SIZE="-1">' . $current_dport . '</FONT>' . $country_img . $homelan . $bdiv;
        $cell_pdfdata['IP_PORTDST'] = $dip_aux.$current_dport.$dlnk;
		$cell_data['IP_DST'] = $current_dip . $country_img . $homelan;
		$cell_data['PORT_DST'] = str_replace(":","",$current_dport);
		//qroPrintEntry($div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask32">' . $dip_aux . '</A><FONT SIZE="-1">' . $current_dport . '</FONT>' . $country_img . $homelan . $bdiv, 'center', 'top', 'nowrap');
    } else {
		//qroPrintEntry('<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . gettext("unknown") . '</A>');
		$cell_data['IP_PORTDST'] = '<A HREF="' . $BASE_urlpath . '/help/base_app_faq.php#1">' . gettext("unknown") . '</A>';
		$cell_data['IP_DST'] = gettext("unknown");
		$cell_data['PORT_DST'] = gettext("unknown");
	}
    $cell_align['IP_PORTDST'] = "center";
    $cell_align['IP_DST'] = "center";
    $cell_align['PORT_DST'] = "center";
	if (in_array("IP_DST_FQDN",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
		$cell_data['IP_DST_FQDN'] = baseGetHostByAddr($current_dip, $db, $dns_cache_lifetime);
	}
    
    // 7- Asset
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle' title='$current_oasset_s -> $current_oasset_d'>&nbsp;");
	$cell_data['ASSET'] = "<img src=\"bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle' title='$current_oasset_s -> $current_oasset_d'>&nbsp;";
	$cell_pdfdata['ASSET'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['ASSET'] = "center";

    $current_orisk = ($current_dip != "255.255.255.255") ? $current_oriska : $current_oriskc;
    
   /*if ($current_dip != "255.255.255.255") {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle'>&nbsp;");
        $current_orisk = $current_oriska;
    } else {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_oasset_s . "&max=5\" border='0' align='absmiddle'>&nbsp;");
        $current_orisk = $current_oriskc;
    }*/

    // 8- Priority
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle' title='$current_oprio'>&nbsp;");
	$cell_data['PRIORITY'] = "<img src=\"bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle' title='$current_oprio'>&nbsp;";
	$cell_pdfdata['PRIORITY'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oprio . "&max=5' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['PRIORITY'] = "center";
    //if ($current_oprio != "")
    //	qroPrintEntry($current_oprio);
    //else
    //	qroPrintEntry("--");

    // 10- Rel
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle' title='$current_oreli'>&nbsp;");
	$cell_data['RELIABILITY'] = "<img src=\"bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle' title='$current_oreli'>&nbsp;";
	$cell_pdfdata['RELIABILITY'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oreli . "&max=9' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['RELIABILITY'] = "center";
    //if ($current_oreli != "")
    //	qroPrintEntry($current_oreli);
    //else
    //	qroPrintEntry("--");

    // 9- Risk
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle' title='$current_oriskc -> $current_oriska'>&nbsp;");
	$cell_data['RISK'] = "<img src=\"bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle' title='$current_oriskc -> $current_oriska'>&nbsp;";
	$cell_pdfdata['RISK'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['RISK'] = "center";
    /*if ($current_otype == 2) {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_orisk . "&max=9&range=1\" border='0' align='absmiddle'>&nbsp;");
    } else {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_orisk . "&max=9&range=1\" border='0' align='absmiddle'>&nbsp;");
    }*/
    
    // 10 - Context
    switch(intval($myrow["context"])) {
    	case 3:
    		$context = '<a href="javascript:;" title="'._("Event prioritized, as target is vulnerable to the attack").'"><img src="images/marker_red.png" border="0"></a>';
    		break;
    	
    	case 2:
    		$context = '<a href="javascript:;" title="'._("Event deprioritized, as target inventory didn't match the list of affected systems").'"><img src="images/marker_green.png" border="0"></a>';
    		break;
    	
    	case 1:
    		$context = '<a href="javascript:;" title="'._("Event prioritized, as target inventory matched the list of affected systems").'"><img src="images/marker_yellow.png" border="0"></a>';
    		break;
    	
    	case 0:
    		$context = '<a href="javascript:;" title="'._("No action related to the context analysis").'"><img src="images/marker_grey.png" border="0"></a>';
    		break;
    }
	$cell_data['CONTEXT'] = $context;
	$cell_align['CONTEXT'] = "center";
    $cell_more['CONTEXT'] = "nowrap";
    
    // 11- Protocol
    //qroPrintEntry('<FONT>' . IPProto2str($current_proto) . '</FONT>');
	$cell_data['IP_PROTO'] = '<FONT>' . IPProto2str($current_proto) . '</FONT>';
	$cell_align['IP_PROTO'] = "center";
	
	// X- ExtraData
	$cell_data['USERNAME'] = wordwrap($myrow['username'],25," ",true);
	$cell_data['PASSWORD'] = wordwrap($myrow['password'],25," ",true);
	$cell_data['FILENAME'] = wordwrap($myrow['filename'],25," ",true);
	$cell_data['PAYLOAD'] = wordwrap($myrow['data_payload'],25," ",true);
	for ($u = 1; $u < 10; $u++)
		$cell_data['USERDATA'.$u] = wordwrap($myrow['userdata'.$u],25," ",true);

    $cc = ($i % 2 == 0) ? "class='par'" : "";
    //$htmlPdfReport->set("<tr $cc>\n");
    foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $colname) {
        if ($cell_data[$colname] == "") $cell_data[$colname] = "<font style='color:gray'><i>Empty</i></font>";
        if ($cell_tooltip[$colname]!="") 
        	qroPrintEntryTooltip($cell_data[$colname], $cell_align[$colname],"",$cell_more[$colname],$cell_tooltip[$colname]);
        else
        	qroPrintEntry($cell_data[$colname], $cell_align[$colname],"",$cell_more[$colname]);        	
        //$w = ($current_cols_widths[$colname]!="") ? "style='width:".$current_cols_widths[$colname]."'" : "";
        //$htmlPdfReport->set("<td class='siem' $w align='".$cell_align[$colname]."'>".($cell_pdfdata[$colname]!="" ? $cell_pdfdata[$colname] : $cell_data[$colname])."</td>\n");
    }
    //$htmlPdfReport->set("</tr>\n");

    qroPrintEntryFooter();
    $i++;
    /*if ( ($current_proto == 6) || ($current_proto == 17) )
    {
    $result4->baseFreeRows();
    $myrow4[0] = $myrow4[1] = "";
    }*/
    
    /* report_data */
    $report_data[] = array (
        trim(html_entity_decode($despues)),
        $tzdate,
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
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$events_report_type);
$et->PrintForensicsTiming();
geoip_close($gi);
//$htmlPdfReport->set('</table>');
//$pdfReport->setHtml($htmlPdfReport->get());
?>








