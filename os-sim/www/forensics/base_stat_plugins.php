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
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$hosts_ips = array_keys($hosts);

($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_stat_plugins.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
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
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();
if (!$printing_ag) {
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
$from = " FROM acid_event, sensor " . $criteria_clauses[0];
$fromcnt = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE ". $criteria_clauses[1];
// use accumulate tables only with timestamp criteria
if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
// Timezone
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;

//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
//$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
//$qs->AddValidActionOp(gettext("Delete Selected"));
//$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from . $where);
($debug_time_mode >= 1) ? $et->Mark("Initialization") : '';
$qs->RunAction($submit, PAGE_STAT_ALERTS, $db);
($debug_time_mode >= 1) ? $et->Mark("Alert Action") : '';
/* Get total number of events */
/* mstone 20050309 this is expensive -- don't do it if we're avoiding count() */
/*if ($avoid_counts != 1 && !$use_ac) {
$event_cnt = EventCnt($db);
if($event_cnt == 0){
$event_cnt = 1;
}
}*/
/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT acid_event.plugin_id) " . $fromcnt . $where;
/* Run the query to determine the number of rows (No LIMIT)*/
$qs->GetNumResultRows($cnt_sql, $db);
($debug_time_mode >= 1) ? $et->Mark("Counting Result size") : '';
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_plugins.php?caller=" . $caller);
//$qro->AddTitle(" ");
$qro->AddTitle(_("Data Source"));
$qro->AddTitle(_("Events") , "occur_a", " ", " ORDER BY events ASC, sensors DESC", "occur_d", ", ", " ORDER BY events DESC, sensors DESC");
$qro->AddTitle(gettext("Sensor") . "&nbsp;#", "sid_a", " ", " ORDER BY sensors ASC, events DESC", "sid_d", " ", " ORDER BY sensors DESC, events DESC");
$qro->AddTitle(gettext("Last Event"));
$qro->AddTitle(gettext("Source Address"));
$qro->AddTitle(gettext("Dest. Address"));
$qro->AddTitle(gettext("Date")." ".Util::timezone($tz));
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* mstone 20050309 add sig_name to GROUP BY & query so it can be used in postgres ORDER BY */
/* mstone 20050405 add sid & ip counts */
$sql = "select SQL_CALC_FOUND_ROWS max(acid_event.cid),acid_event.plugin_id,count(distinct acid_event.plugin_sid) as events,acid_event.timestamp,count(distinct acid_event.sid) as sensors,plugin.name  " . $fromcnt  . ",ossim.plugin " . $where . " AND plugin.id=acid_event.plugin_id GROUP BY acid_event.plugin_id " . $sort_sql[1];
//echo $sql;
$event_cnt = EventCnt($db, "", "", $sql);
if ($event_cnt == 0) $event_cnt = 1;

/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
$qs->GetCalcFoundRows($cnt_sql, $db);
($debug_time_mode >= 1) ? $et->Mark("Retrieve Query Data") : '';
if ($debug_mode == 1) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
/* Print the current view number and # of rows */
$qs->PrintResultCnt("",array(),gettext("Displaying unique data sources %d-%d of <b>%s</b> matching your selection. <b>%s</b> total events in database."));
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_plugins.php">';
$qro->PrintHeader();
$i = 0;
// The below is due to changes in the queries...
// We need to verify that it works all the time -- Kevin
$and = (strpos($where, "WHERE") != 0) ? " AND " : " WHERE ";
$i = 0;
$report_data = array(); // data to fill report_data 
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $bgcolor = ($i%2 == 0) ? "bgcolor='#FFFFFF'" : "bgcolor='#F2F2F2'";
	$max_cid = $myrow[0];
	$plugin_id = $myrow["plugin_id"];
    $timestamp = $myrow["timestamp"];
    if ($tz!=0) $timestamp = gmdate("Y-m-d H:i:s",strtotime($timestamp)+(3600*$tz));
    $plugin_name = $myrow["name"];
	$total_occurances = $myrow["events"];
	$total_sensors = $myrow["sensors"];
	
	$temp = "SELECT acid_event.sid,acid_event.ip_src,acid_event.ip_dst,plugin_sid.name as sig_name FROM acid_event LEFT JOIN ossim.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE acid_event.plugin_id=$plugin_id AND cid=$max_cid LIMIT 1";
	$result2 = $db->baseExecute($temp);
	$last = $result2->baseFetchRow();
	$last_signature = $last['sig_name'];
    $sig_id = $last['sid'];
	$submit = "#" . (($qs->GetCurrentView() * $show_rows) + $i) . "-(" . $sig_id . "-" . $max_cid . ")";
	$current_sip = long2ip($last['ip_src']);
	$current_dip = long2ip($last['ip_dst']);
	$homelan_sip = (Net::is_ip_in_cache_cidr($_conn, $current_sip) || in_array($current_sip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_sip'><img src=\"images/homelan.png\" border=0></a>" : "";
	$homelan_dip = (Net::is_ip_in_cache_cidr($_conn, $current_dip) || in_array($current_dip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_dip'><img src=\"images/homelan.png\" border=0></a>" : "";
	
    /* Print out (Colored Version) -- Alejandro */
    //qroPrintEntryHeader((($colored_alerts == 1) ? GetSignaturePriority($sig_id, $db) : $i) , $colored_alerts);
    $tmp_rowid = rawurlencode($sig_id);
    /*echo '  <TD nowrap '.$bgcolor.'>&nbsp;&nbsp;
                 <INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">
                 &nbsp;&nbsp;
             </TD>';
    echo '      <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';*/
    $urlp = "base_qry_main.php?search=1&sensor=&bsf=Query+DB&search_str=&sip=&ossim_risk_a=+&plugin=$plugin_id";
    qroPrintEntry('&nbsp;<a href="'.$urlp.'">' . $plugin_name . '</a>','left',"","nowrap",$bgcolor);
	qroPrintEntry('&nbsp;<a href="'.$urlp.'">' . $total_occurances . '</a>',"center","","",$bgcolor);
    qroPrintEntry('' . $total_sensors . '',"center","","",$bgcolor);
	//qroPrintEntry('<FONT>' . '' . $sid_id . '' . (($avoid_counts != 1) ? ('(' . (round($total_occurances / $event_cnt * 100)) . '%)') : ('')) . '</FONT>', 'center', 'top', 'nowrap', $bgcolor);
	//qroPrintEntry("<A HREF='base_qry_alert.php?submit=" . rawurlencode($submit) . "&amp;sort_order='>".$last_signature."</a>","left","","",$bgcolor);
	qroPrintEntry("<A HREF='$urlp'>".$last_signature."</a>","left","","",$bgcolor);
	
	// Source IP
	$country = strtolower(geoip_country_code_by_addr($gi, $current_sip));
	$country_name = geoip_country_name_by_addr($gi, $current_sip);
	if ($country) {
		$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
        $slnk = $current_url."/pixmaps/flags/".$country.".png";
	} else {
		$country_img = "";
        $slnk = ($homelan_sip!="") ? $current_url."/forensics/images/homelan.png" : "";
	}
	$ip_aux = ($sensors[$current_sip] != "") ? $sensors[$current_sip] : (($hosts[$current_sip] != "") ? $hosts[$current_sip] : $current_sip);
    $srcip = $ip_aux.$current_sport;
	$div = '<div id="'.$current_sip.';'.$ip_aux.'" class="HostReportMenu">';
	$bdiv = '</div>';
	qroPrintEntry($div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_sport . '</FONT>' . $country_img . $homelan_sip . $bdiv, 'center', 'top', 'nowrap', $bgcolor);
	// Dest IP
	$country = strtolower(geoip_country_code_by_addr($gi, $current_dip));
	$country_name = geoip_country_name_by_addr($gi, $current_dip);
	if ($country) {
		$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
        $dlnk = $current_url."/pixmaps/flags/".$country.".png";
	} else {
		$country_img = "";
        $dlnk = ($homelan_dip!="") ? $current_url."/forensics/images/homelan.png" : "";
	}
	$ip_aux = ($sensors[$current_dip] != "") ? $sensors[$current_dip] : (($hosts[$current_dip] != "") ? $hosts[$current_dip] : $current_dip);
    $dstip = $ip_aux.$current_sport;
	$div = '<div id="'.$current_dip.';'.$ip_aux.'" class="HostReportMenu">';
	$bdiv = '</div>';
	qroPrintEntry($div.'<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask32">' . $ip_aux . '</A><FONT SIZE="-1">' . $current_dport . '</FONT>' . $country_img . $homelan_dip . $bdiv, 'center', 'top', 'nowrap', $bgcolor);
	
    qroPrintEntry($timestamp,"","","nowrap",$bgcolor);
    qroPrintEntryFooter();
    $i++;
    $prev_time = null;
    
    // report_data
    $report_data[] = array (
        $plugin_name, $last_signature, 
        $srcip, $slnk, $dstip, $dlnk, $timestamp,
        "", "", "", "",
        $total_occurances, $total_sensors , 0
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_plugins_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
if ($debug_time_mode >= 1) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
echo "</body>\r\n</html>";
?>
