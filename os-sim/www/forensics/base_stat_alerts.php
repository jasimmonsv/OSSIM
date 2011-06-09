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
require_once ('classes/Util.inc');

($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_stat_alerts.php");
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
$from = " FROM acid_event " . $criteria_clauses[0];
$where = ($criteria_clauses[1] != "") ? " WHERE " . $criteria_clauses[1] : " ";
// use accumulate tables only with timestamp criteria
$use_ac = (preg_match("/AND/", preg_replace("/AND \( timestamp/", "", $criteria_clauses[1]))) ? false : true;
if (preg_match("/ \d\d:\d\d:\d\d/",$criteria_clauses[1])) $use_ac = false;
//$use_ac = false;
if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
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
// Timezone
$tz = Util::get_timezone();

/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT acid_event.plugin_id, acid_event.plugin_sid) " . $from . $where;
/* Run the query to determine the number of rows (No LIMIT)*/
if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);
($debug_time_mode >= 1) ? $et->Mark("Counting Result size") : '';
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_alerts.php?caller=" . $caller);
$qro->AddTitle(" ");
$qro->AddTitle(gettext("Signature"), "sig_a", " ", " ORDER BY plugin_id ASC,plugin_sid", "sig_d", " ", " ORDER BY plugin_id DESC,plugin_sid");
//if ($db->baseGetDBversion() >= 103) $qro->AddTitle(gettext("Classification"), "class_a", ", MIN(sig_class_id) ", " ORDER BY sig_class_id ASC ", "class_d", ", MIN(sig_class_id) ", " ORDER BY sig_class_id DESC ");
$qro->AddTitle(gettext("Total") . "&nbsp;#", "occur_a", " ", " ORDER BY sig_cnt ASC", "occur_d", " ", " ORDER BY sig_cnt DESC");
$qro->AddTitle(gettext("Sensor") . "&nbsp;#");
$qro->AddTitle(_("Src. Addr.") , "saddr_a", ", count(DISTINCT ip_src) AS saddr_cnt ", " ORDER BY saddr_cnt ASC", "saddr_d", ", count(DISTINCT ip_src) AS saddr_cnt ", " ORDER BY saddr_cnt DESC");
$qro->AddTitle(_("Dst. Addr.") , "daddr_a", ", count(DISTINCT ip_dst) AS daddr_cnt ", " ORDER BY daddr_cnt ASC", "daddr_d", ", count(DISTINCT ip_dst) AS daddr_cnt ", " ORDER BY daddr_cnt DESC");
$qro->AddTitle(_("First")." ".Util::timezone($tz), "first_a", ", min(timestamp) AS first_timestamp ", " ORDER BY first_timestamp ASC", "first_d", ", min(timestamp) AS first_timestamp ", " ORDER BY first_timestamp DESC");
if ($show_previous_alert == 1) $qro->AddTitle("Previous");
$qro->AddTitle(_("Last")." ".Util::timezone($tz), "last_a", ", max(timestamp) AS last_timestamp ", " ORDER BY last_timestamp ASC", "last_d", ", max(timestamp) AS last_timestamp ", " ORDER BY last_timestamp DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* mstone 20050309 add sig_name to GROUP BY & query so it can be used in postgres ORDER BY */
/* mstone 20050405 add sid & ip counts */
//$sql = "SELECT DISTINCT signature, count(signature) as sig_cnt, " . "min(timestamp), max(timestamp), sig_name, count(DISTINCT(acid_event.sid)), count(DISTINCT(ip_src)), count(DISTINCT(ip_dst)), sig_class_id " . $sort_sql[0] . $from . $where . " GROUP BY signature, sig_name, sig_class_id " . $sort_sql[1];
$sql = "SELECT DISTINCT acid_event.plugin_id, acid_event.plugin_sid, count(acid_event.plugin_sid) as sig_cnt, " . "min(timestamp) as first_timestamp, max(timestamp) as last_timestamp, count(DISTINCT(acid_event.sid)) as sid_cnt, count(DISTINCT(ip_src)) as saddr_cnt, count(DISTINCT(ip_dst)) as daddr_cnt " . $sort_sql[0] . $from . $where . " GROUP BY plugin_id, plugin_sid " . $sort_sql[1];
//echo $sql."<br>";
// use accumulate tables only with timestamp criteria
if ($use_ac) {
    $where = $more = $sqla = $sqlb = $sqlc = "";
    if (preg_match("/timestamp/", $criteria_clauses[1])) {
        $where = "AND " . str_replace("timestamp", "day", $criteria_clauses[1]);
        $sqla = " and ac_alerts_signature.day=ac_alerts_sid.day";
        $sqlb = " and ac_alerts_signature.day=ac_alerts_ipsrc.day";
        $sqlc = " and ac_alerts_signature.day=ac_alerts_ipdst.day";
    }
    $orderby = str_replace("acid_event.", "", $sort_sql[1]);
    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT plugin_id, plugin_sid, 
       sum(sig_cnt) as sig_cnt,
       min(ac_alerts_signature.first_timestamp) as first_timestamp,
       max(ac_alerts_signature.last_timestamp) as last_timestamp,
       (select count(distinct(sid)) from ac_alerts_sid where ac_alerts_signature.plugin_id=ac_alerts_sid.plugin_id AND ac_alerts_signature.plugin_sid=ac_alerts_sid.plugin_sid $sqla) as sid_cnt,
       (select count(distinct(ip_src)) from ac_alerts_ipsrc where ac_alerts_signature.plugin_id=ac_alerts_ipsrc.plugin_id AND ac_alerts_signature.plugin_sid=ac_alerts_ipsrc.plugin_sid $sqlb) as saddr_cnt,
       (select count(distinct(ip_dst)) from ac_alerts_ipdst where ac_alerts_signature.plugin_id=ac_alerts_ipdst.plugin_id AND ac_alerts_signature.plugin_sid=ac_alerts_ipdst.plugin_sid $sqlc) as daddr_cnt
       FROM ac_alerts_signature FORCE INDEX(primary) 
       WHERE ac_alerts_signature.sig_cnt>0 $where GROUP BY plugin_id, plugin_sid $orderby";
    $event_cnt = EventCnt($db, "", "", "SELECT sum(sig_cnt) FROM ac_alerts_signature FORCE INDEX(primary) ".preg_replace("/^AND /","WHERE ",$where));
    /*
    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT signature, sum(sig_cnt) as sig_cnt,
      min(ac_alerts_signature.first_timestamp) as first_timestamp,  max(ac_alerts_signature.last_timestamp) as last_timestamp,
      sig_name,
      (select count(distinct(sid)) from ac_alerts_sid where ac_alerts_signature.signature=ac_alerts_sid.signature $sqla) as sig_cnt,
      (select count(distinct(ip_src)) from ac_alerts_ipsrc where ac_alerts_signature.signature=ac_alerts_ipsrc.signature $sqlb) as saddr_cnt,
      (select count(distinct(ip_dst)) from ac_alerts_ipdst where ac_alerts_signature.signature=ac_alerts_ipdst.signature $sqlc) as daddr_cnt,
      sig_class_id
      FROM ac_alerts_signature FORCE INDEX(primary) WHERE ac_alerts_signature.sig_cnt>0 $where GROUP BY signature, sig_name, sig_class_id $orderby";
    $event_cnt = EventCnt($db, "", "", "SELECT sum(sig_cnt) FROM ac_alerts_signature FORCE INDEX(primary) WHERE ac_alerts_signature.sig_cnt>0 $where");
	*/
} else {
    $event_cnt = EventCnt($db, "", "", "SELECT count(*) " . $from . $where);
    if ($event_cnt == 0) $event_cnt = 1;
}
//echo $sql; echo $cnt_sql;
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($use_ac) $qs->GetCalcFoundRows($cnt_sql, $db);
($debug_time_mode >= 1) ? $et->Mark("Retrieve Query Data") : '';
if ($debug_mode == 1) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique events %d-%d of <b>%s</b> matching your selection.");
if (Session::am_i_admin()) $displaying .= gettext(" <b>%s</b> total events in database.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_alerts.php">';
$qro->PrintHeader();
$i = 0;
$report_data = array(); // data to fill report_data 
// The below is due to changes in the queries...
// We need to verify that it works all the time -- Kevin
$and = (strpos($where, "WHERE") != 0) ? " AND " : " WHERE ";
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    if ($myrow["plugin_id"]=="" || $myrow["plugin_sid"]=="") continue;
    //
    $sig_id=$myrow["plugin_id"].";".$myrow["plugin_sid"];
    $signame = BuildSigByPlugin($myrow["plugin_id"], $myrow["plugin_sid"], $db);
    //
    /* get Total Occurrence */
    $total_occurances = $myrow["sig_cnt"];
    /* Get other data */
    $num_sensors = $myrow["sid_cnt"];
    $num_src_ip = $myrow["saddr_cnt"];
    $num_dst_ip = $myrow["daddr_cnt"];
    /* First and Last timestamp of this signature */
    $start_time = $myrow["first_timestamp"];
    $stop_time = $myrow["last_timestamp"];
    if ($tz!=0) {
    	$start_time = gmdate("Y-m-d H:i:s",get_utc_unixtime($db,$start_time)+(3600*$tz));
    	$stop_time = gmdate("Y-m-d H:i:s",get_utc_unixtime($db,$stop_time)+(3600*$tz));
	}
    /* Print out (Colored Version) -- Alejandro */
    //qroPrintEntryHeader((($colored_alerts == 1) ? GetSignaturePriority($sig_id, $db) : $i) , $colored_alerts);
    qroPrintEntryHeader( $i , $colored_alerts);
    $tmp_rowid = $myrow["plugin_id"]." ".$myrow["plugin_sid"];
    echo '  <TD nowrap>&nbsp;&nbsp;
                 <INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">
                 &nbsp;&nbsp;
             </TD>';
    echo '      <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    $sigstr = trim(preg_replace("/.*\/\s*(.*)/","\\1",preg_replace("/^[\.\,\"\!]|[\.\,\"\!]$/","",preg_replace("/.*##/","",html_entity_decode(strip_tags($signame))))));
    $siglink = "base_qry_main.php?new=1&submit=" . gettext("Query+DB") . "&num_result_rows=-1&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=" . urlencode($sig_id);
    $tmpsig = explode("##", $signame);
    if ($tmpsig[1]!="") {
        $antes = $tmpsig[0];
        $despues = $tmpsig[1];
    } else {
        $antes = "";
        $despues = $signame;
    }
    qroPrintEntry("$antes <a href='$siglink'>".trim($despues)."</a>" , "left");
    //if ($db->baseGetDBversion() >= 103) qroPrintEntry(GetSigClassName(GetSigClassID($sig_id, $db) , $db));
    $perc = (($avoid_counts != 1) ? ('&nbsp;(' . (round($total_occurances / $event_cnt * 100)) . '%)') : (''));
    //qroPrintEntry('<FONT>' . '<A HREF="base_qry_main.php?new=1amp;&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . (rawurlencode($sig_id)) . '&amp;sig_type=1' . '&amp;submit=' . gettext("Query+DB") . '&amp;num_result_rows=-1">' . $total_occurances . '</A>' .
    qroPrintEntry('<FONT>' . '<A HREF="' . $siglink . '">' . $total_occurances . '</A>' .
    /* mstone 20050309 lose this if we're not showing stats */
    $perc . '</FONT>', 'center', 'top', 'nowrap');
    qroPrintEntry('<A HREF="base_stat_sensor.php?sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($sig_id) . '&amp;sig_type=1">' . $num_sensors . '</A>');
    if ($db->baseGetDBversion() >= 100) $addr_link = '&amp;sig_type=1&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($sig_id);
    else $addr_link = '&amp;sig%5B0%5D=LIKE&amp;sig%5B1%5D=' . urlencode($sigstr);
    qroPrintEntry('<FONT>' . BuildUniqueAddressLink(1, $addr_link) . $num_src_ip . '</A></FONT>', 'center', 'top', 'nowrap');
    qroPrintEntry('<FONT>' . BuildUniqueAddressLink(2, $addr_link) . $num_dst_ip . '</A></FONT>', 'center', 'top', 'nowrap');
    qroPrintEntry('<FONT>' . $start_time . '</FONT>', 'center', 'top', 'nowrap');
    qroPrintEntry('<FONT>' . $stop_time . '</FONT>', 'center', 'top', 'style="padding:0 10px 0 10px" nowrap');
    qroPrintEntryFooter();
    $i++;
    $prev_time = null;
    
    // report_data
    $report_data[] = array (
        trim(html_entity_decode($despues)),
        html_entity_decode($total_occurances.$perc),
        $start_time, $stop_time,
        "", "", "", "", "", "", "",
        $num_sensors ,$num_src_ip, $num_dst_ip
    );
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_events_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
if ($debug_time_mode >= 1) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
echo "</body>\r\n</html>";
?>
