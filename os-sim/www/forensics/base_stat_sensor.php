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
include ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_stat_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_ag_common.php");
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$hosts_ips = array_keys($hosts);

$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_stat_sensor.php");
$cs->ReadState();
$qs = new QueryState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = gettext("Sensor Listing");
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
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
$where = " WHERE " . $criteria_clauses[1];
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
// Timezone
$tz = Util::get_timezone();

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
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_SENSOR, $db);
$et->Mark("Alert Action");
/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT acid_event.sid) " . $from . $where;
/* Run the query to determine the number of rows (No LIMIT)*/
if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_sensor.php?caller=" . $caller);
$qro->AddTitle(" ");
$qro->AddTitle(gettext("Sensor"), "sid_a", " ", " ORDER BY acid_event.sid ASC", "sid_d", " ", " ORDER BY acid_event.sid DESC");
$qro->AddTitle(gettext("Name"), "", " ", " ", "", " ", " ");
$qro->AddTitle(gettext("Total Events"), "occur_a", " ", "  ORDER BY event_cnt ASC", "occur_d", " ", "  ORDER BY event_cnt DESC");
$qro->AddTitle(gettext("Unique Events"), "sig_a", "", " ORDER BY sig_cnt ASC", "sig_d", "", " ORDER BY sig_cnt DESC");
$qro->AddTitle(gettext("Src.&nbsp;Addr."), "saddr_a", "", " ORDER BY saddr_cnt ASC", "saddr_d", "", " ORDER BY saddr_cnt DESC");
$qro->AddTitle(gettext("Dest.&nbsp;Addr."), "daddr_a", "", " ORDER BY daddr_cnt ASC", "daddr_d", "", " ORDER BY daddr_cnt DESC");
$qro->AddTitle(_("First")." ".Util::timezone($tz), "first_a", "", " ORDER BY first_timestamp ASC", "first_d", "", " ORDER BY first_timestamp DESC");
$qro->AddTitle(_("Last")." ".Util::timezone($tz), "last_a", "", " ORDER BY last_timestamp ASC", "last_d", "", " ORDER BY last_timestamp DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , "");
$sql = "SELECT DISTINCT acid_event.sid, count(acid_event.cid) as event_cnt," . " count(distinct acid_event.plugin_id, acid_event.plugin_sid) as sig_cnt, " . " count(distinct(acid_event.ip_src)) as saddr_cnt, " . " count(distinct(acid_event.ip_dst)) as daddr_cnt, " . "min(timestamp) as first_timestamp, max(timestamp) as last_timestamp" . $sort_sql[0] . $from . $where . " GROUP BY acid_event.sid " . $sort_sql[1];
//echo $sql."<br>";
// use accumulate tables only with timestamp criteria
if ($use_ac) {
    $where = $more = $sqla = $sqlb = $sqlc = "";
    if (preg_match("/timestamp/", $criteria_clauses[1])) {
        $where = "WHERE " . str_replace("timestamp", "day", $criteria_clauses[1]);
        $sqla = " and ac_sensor_sid.day=ac_sensor_signature.day";
        $sqlb = " and ac_sensor_sid.day=ac_sensor_ipsrc.day";
        $sqlc = " and ac_sensor_sid.day=ac_sensor_ipdst.day";
    }
    $orderby = str_replace("acid_event.", "", $sort_sql[1]);
    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT ac_sensor_sid.sid,  sum(ac_sensor_sid.cid) as event_cnt,
     (select count(distinct ac_sensor_signature.plugin_id, ac_sensor_signature.plugin_sid) from ac_sensor_signature where ac_sensor_signature.sid=ac_sensor_sid.sid $sqla) as sig_cnt,
     (select count(distinct(ip_src)) from ac_sensor_ipsrc where ac_sensor_sid.sid=ac_sensor_ipsrc.sid $sqlb) as saddr_cnt,
     (select count(distinct(ip_dst)) from ac_sensor_ipdst where ac_sensor_sid.sid=ac_sensor_ipdst.sid $sqlc) as daddr_cnt,
      min(ac_sensor_sid.first_timestamp) as first_timestamp,  max(ac_sensor_sid.last_timestamp) as last_timestamp
      FROM ac_sensor_sid FORCE INDEX(primary) $where GROUP BY ac_sensor_sid.sid $orderby";
}
//echo $sql;
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($use_ac) $qs->GetCalcFoundRows($cnt_sql, $db);
$et->Mark("Retrieve Query Data");
if ($debug_mode == 1) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
/* Print the current view number and # of rows */
$displaying = gettext("Displaying sensors %d-%d of <b>%s</b> matching your selection.");
if (Session::am_i_admin()) $displaying .= gettext(" <b>%s</b> total events in database.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" NAME="PacketForm" id="PacketForm" ACTION="base_stat_sensor.php">';
$qro->PrintHeader();
$i = 0;
$report_data = array(); // data to fill report_data 

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $sensor_id = $myrow[0];
    $event_cnt = $myrow[1];
    $unique_event_cnt = $myrow[2];
    if ($unique_event_cnt == 0) $event_cnt=0;
    $num_src_ip = $myrow[3];
    $num_dst_ip = $myrow[4];
    $start_time = $myrow[5];
    $stop_time = $myrow[6];
    if ($tz!=0) {
    	$start_time = date("Y-m-d H:i:s",strtotime($start_time)+(3600*$tz));
    	$stop_time = date("Y-m-d H:i:s",strtotime($stop_time)+(3600*$tz));
	}    
    $sname = GetSensorName($sensor_id, $db);
	$sensor_ip = preg_replace("/\-.*/","",$sname);
	$homelan = (($match_cidr = Net::is_ip_in_cache_cidr($_conn, $sensor_ip)) || in_array($sensor_ip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$sensor_ip'><img src=\"".Host::get_homelan_icon($sensor_sip,$icons,$match_cidr,$_conn)."\" border=0></a>" : "";
	$country = strtolower(geoip_country_code_by_addr($gi, $sensor_ip));
	$country_name = geoip_country_name_by_addr($gi, $sensor_ip);
	if ($country) {
		$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
        $slnk = $current_url."/pixmaps/flags/".$country.".png";
	} else {
		$country_img = "";
        $slnk = ($homelan!="") ? $current_url."/forensics/images/homelan.png" : "";
	}
    /* Print out */
    qroPrintEntryHeader($i);
    $tmp_rowid = $sensor_id;
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    qroPrintEntry($sensor_id);
    qroPrintEntry((preg_match("/\-.+/",$sname) ? $sname : $sname."-snort").$country_img.$homelan);
    qroPrintEntry('<A HREF="base_qry_main.php?new=1&amp;sensor=' . $sensor_id . '&amp;num_result_rows=-1&amp;submit=' . gettext("Query+DB") . '">' . $event_cnt . '</A>');
    qroPrintEntry(BuildUniqueAlertLink("?sensor=" . $sensor_id) . $unique_event_cnt . '</A>');
    qroPrintEntry(BuildUniqueAddressLink(1, "&amp;sensor=" . $sensor_id) . $num_src_ip . '</A>');
    qroPrintEntry(BuildUniqueAddressLink(2, "&amp;sensor=" . $sensor_id) . $num_dst_ip . '</A>');
    qroPrintEntry($start_time);
    qroPrintEntry($stop_time);
    qroPrintEntryFooter();
    $i++;
    
    // report_data
    $report_data[] = array (
        trim(preg_match("/\-.+/",$sname) ? $sname : $sname."-snort"),
        $slnk,
        $num_src_ip, $num_dst_ip, $start_time, $stop_time,
        "", "", "", "", "", $sensor_id, 
        $event_cnt, $unique_event_cnt
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$sensors_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
echo "</body>\r\n</html>";
?>
