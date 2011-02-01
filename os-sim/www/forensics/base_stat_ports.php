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
include_once ("$BASE_path/base_qry_common.php");
$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$et = new EventTiming($debug_time_mode);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
/* FIXME: OSSIM */
/* This used to break the port filters, have to look deeply on this
maybe changing db_connect_method in base_conf.php */
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$cs = new CriteriaState("base_stat_ports.php");
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$port_proto = "TCP";
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uports, _MOSTFREQPORTS, "occur_d");
$qs->AddCannedQuery("last_ports", $last_num_uports, _LASTPORTS, "last_d");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    _SELECTED,
    _ALLONSCREEN,
    _ENTIREQUERY
));
$port_type = ImportHTTPVar("port_type", VAR_DIGIT);
$proto = ImportHTTPVar("proto", VAR_DIGIT | VAR_PUNC);
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = "";
switch ($proto) {
    case TCP:
        $page_title = _UNIQ . " TCP ";
        $displaytitle = ($port_type==SOURCE_PORT) ? _DISPLAYINGTOTALPTCPSRC : _DISPLAYINGTOTALPTCPDST; 
        break;

    case UDP:
        $page_title = _UNIQ . " UDP ";
        $displaytitle = ($port_type==SOURCE_PORT) ? _DISPLAYINGTOTALPUDPSRC : _DISPLAYINGTOTALPUDPDST;
        break;

    case -1:
        $page_title = _UNIQ . " ";
        $displaytitle = ($port_type==SOURCE_PORT) ? _DISPLAYINGTOTALPSRC : _DISPLAYINGTOTALPDST;
        break;
}
switch ($port_type) {
    case SOURCE_PORT:
        $page_title = $page_title . _SRCPS;
        break;

    case DEST_PORT:
        $page_title = $page_title . _DSTPS;
        break;
}
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();
/* special case - erase ip_proto filter */
$criteria_clauses = preg_replace("/ AND acid_event.ip_proto= '\d+'/", "", $criteria_clauses);
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
$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
// use accumulate tables only with timestamp criteria
$use_ac = (preg_match("/AND/", preg_replace("/AND \( timestamp|AND acid_event\.ip_proto/", "", $criteria_clauses[1]))) ? false : true;
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
$qs->AddValidActionOp(_SELECTED);
$qs->AddValidActionOp(_ALLONSCREEN);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_PORTS, $db);
$et->Mark("Alert Action");
switch ($proto) {
    case TCP:
        $proto_sql = " ip_proto = " . TCP;
        break;

    case UDP:
        $proto_sql = " ip_proto = " . UDP;
        break;

    default:
        $proto_sql = " ip_proto IN (" . TCP . ", " . UDP . ")";
        break;
}
if ($criteria_clauses[1] != "") $criteria_clauses[1] = $proto_sql . " AND " . $criteria_clauses[1];
else $criteria_clauses[1] = $proto_sql;
switch ($port_type) {
    case SOURCE_PORT:
        $port_type_sql = "layer4_sport";
        break;

    case DEST_PORT:
    default:
        $port_type_sql = "layer4_dport";
        break;
}
// Timezone
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;

/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT $port_type_sql) " . " FROM acid_event " . $criteria_clauses[0] . " WHERE " . $criteria_clauses[1];
/* Run the query to determine the number of rows (No LIMIT)*/
if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_ports.php?caller=$caller" . "&amp;port_type=$port_type&amp;proto=$proto");
$qro->AddTitle(" ");
$qro->AddTitle(_PORT, "port_a", " ", " ORDER BY $port_type_sql ASC", "port_d", " ", " ORDER BY $port_type_sql DESC");
$qro->AddTitle(_SENSOR, "sensor_a", " ", " ORDER BY num_sensors ASC", "sensor_d", " ", " ORDER BY num_sensors DESC");
$qro->AddTitle(_OCCURRENCES, "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(gettext("Unique Events"), "alerts_a", " ", " ORDER BY num_sig ASC", "alerts_d", " ", " ORDER BY num_sig DESC");
$qro->AddTitle(_SUASRCADD, "sip_a", " ", " ORDER BY num_sip ASC", "sip_d", " ", " ORDER BY num_sip DESC");
$qro->AddTitle(_SUADSTADD, "dip_a", " ", " ORDER BY num_dip ASC", "dip_d", " ", " ORDER BY num_dip DESC");
$qro->AddTitle(_("First")." ".Util::timezone($tz), "first_a", " ", " ORDER BY first_timestamp ASC", "first_d", " ", " ORDER BY first_timestamp DESC");
$qro->AddTitle(_("Last")." ".Util::timezone($tz), "last_a", " ", " ORDER BY last_timestamp ASC", "last_d", " ", " ORDER BY last_timestamp DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$where = " WHERE " . $criteria_clauses[1];
$sql = "SELECT DISTINCT $port_type_sql, MIN(ip_proto), " . " COUNT(acid_event.cid) as num_events," . " COUNT( DISTINCT acid_event.sid) as num_sensors, " . " COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig, " . " COUNT( DISTINCT ip_src ) as num_sip, " . " COUNT( DISTINCT ip_dst ) as num_dip, " . " MIN(timestamp) as first_timestamp, " . " MAX(timestamp) as last_timestamp " . $sort_sql[0] . " FROM acid_event " . $criteria_clauses[0] . $where . " GROUP BY " . $port_type_sql . " HAVING num_events>0 " . $sort_sql[1];
//echo "$sql<br>";
// use accumulate tables only with timestamp criteria
if ($use_ac) {
    $more = $sqla = $sqlb = $sqlc = $sqld = "";
    if ($port_type_sql == "layer4_sport") {
		if (preg_match("/timestamp/", $criteria_clauses[1])) {
            $where = "WHERE " . str_replace("acid_event.", "", str_replace("timestamp", "day", $criteria_clauses[1]));
            $sqla = " and ac_layer4_sport.day=ac_layer4_sport_sid.day";
            $sqlb = " and ac_layer4_sport.day=ac_layer4_sport_signature.day";
            $sqlc = " and ac_layer4_sport.day=ac_layer4_sport_ipsrc.day";
            $sqld = " and ac_layer4_sport.day=ac_layer4_sport_ipdst.day";
        }
        $orderby = str_replace("acid_event.", "", $sort_sql[1]);
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT layer4_sport, MIN(ip_proto) as min_ip_proto,
			sum(cid) as num_events,
			(select count(distinct(sid)) from ac_layer4_sport_sid where ac_layer4_sport.layer4_sport=ac_layer4_sport_sid.layer4_sport $sqla) as num_sensors,
			(select count(distinct ac_layer4_sport_signature.plugin_id, ac_layer4_sport_signature.plugin_sid) from ac_layer4_sport_signature where ac_layer4_sport.layer4_sport=ac_layer4_sport_signature.layer4_sport $sqlb) as num_sig,
			(select count(distinct(ip_src)) from ac_layer4_sport_ipsrc where ac_layer4_sport.layer4_sport=ac_layer4_sport_ipsrc.layer4_sport $sqlc) as num_sip,
			(select count(distinct(ip_dst)) from ac_layer4_sport_ipdst where ac_layer4_sport.layer4_sport=ac_layer4_sport_ipdst.layer4_sport $sqld) as num_dip,
			min(first_timestamp) as first_timestamp,  max(last_timestamp) as last_timestamp
			FROM ac_layer4_sport $where GROUP BY layer4_sport HAVING num_events>0 $orderby";
    } else {
        if (preg_match("/timestamp/", $criteria_clauses[1])) {
            $where = "WHERE " . str_replace("acid_event.", "", str_replace("timestamp", "day", $criteria_clauses[1]));
            $sqla = " and ac_layer4_dport.day=ac_layer4_dport_sid.day";
            $sqlb = " and ac_layer4_dport.day=ac_layer4_dport_signature.day";
            $sqlc = " and ac_layer4_dport.day=ac_layer4_dport_ipsrc.day";
            $sqld = " and ac_layer4_dport.day=ac_layer4_dport_ipdst.day";
        }
        $orderby = str_replace("acid_event.", "", $sort_sql[1]);
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT layer4_dport, MIN(ip_proto) as min_ip_proto,
			sum(cid) as num_events,
			(select count(distinct(sid)) from ac_layer4_dport_sid where ac_layer4_dport.layer4_dport=ac_layer4_dport_sid.layer4_dport $sqla) as num_sensors,
			(select count(distinct ac_layer4_dport_signature.plugin_id, ac_layer4_dport_signature.plugin_sid) from ac_layer4_dport_signature where ac_layer4_dport.layer4_dport=ac_layer4_dport_signature.layer4_dport $sqlb) as num_sig,
			(select count(distinct(ip_src)) from ac_layer4_dport_ipsrc where ac_layer4_dport.layer4_dport=ac_layer4_dport_ipsrc.layer4_dport $sqlc) as num_sip,
			(select count(distinct(ip_dst)) from ac_layer4_dport_ipdst where ac_layer4_dport.layer4_dport=ac_layer4_dport_ipdst.layer4_dport $sqld) as num_dip,
			min(first_timestamp) as first_timestamp,  max(last_timestamp) as last_timestamp
			FROM ac_layer4_dport $where GROUP BY layer4_dport HAVING num_events>0 $orderby";
    }
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
    echo '<HR><TABLE BORDER=1>
             <TR><TD>port_type</TD>
                 <TD>proto</TD></TR>
             <TR><TD>' . $port_type . '</TD>
                 <TD>' . $proto . '</TD></TR>
           </TABLE>';
}
/* Print the current view number and # of rows */
$qs->PrintResultCnt("",array(),$displaytitle);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_ports.php">' . "\n";
$qro->PrintHeader();
echo "<input type='hidden' name='port_type' value='$port_type'>\n";
$i = 0;
$report_data = array(); // data to fill report_data 
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentPort = $url_port = $myrow[0] . ' ';
    if ($port_proto == TCP) {
        $currentPort = $currentPort . '/ tcp ';
    }
    if ($port_proto == UDP) {
        $currentPort = $currentPort . '/ udp ';
    }
    $crPort = $currentPort;
    // Go here to change the format of the Port lookup stuff! -- Kevin Johnson
    $extcolors = array ("#478F23","#456C9F","#AF4200"); $jc=0;
    foreach($external_port_link as $name => $baseurl) {
        $currentPort = $currentPort . '<A HREF="' . $baseurl . $myrow[0] . '" TARGET="_ACID_PORT_"><font color="'.$extcolors[$jc++].'">[' . $name . ']</font></A> ';
    }
    $port_proto = $myrow[1];
    $num_events = $myrow[2];
    $num_sensors = $myrow[3];
    $num_sig = $myrow[4];
    $num_sip = $myrow[5];
    $num_dip = $myrow[6];
    $first_time = $myrow[7];
    $last_time = $myrow[8];
    if ($tz!=0) {
    	$first_time = date("Y-m-d H:i:s",strtotime($first_time)+(3600*$tz));
    	$last_time = date("Y-m-d H:i:s",strtotime($last_time)+(3600*$tz));
	} 
    if ($port_proto == TCP) {
        $url_port_type = "tcp";
        $url_layer4 = "TCP";
    }
    if ($port_proto == UDP) {
        $url_port_type = "udp";
        $url_layer4 = "UDP";
    }
    $url_param = $url_port_type . "_port%5B0%5D%5B0%5D=+" . "&amp;" . $url_port_type . "_port%5B0%5D%5B1%5D=" . $port_type_sql . "&amp;" . $url_port_type . "_port%5B0%5D%5B2%5D=%3D" . "&amp;" . $url_port_type . "_port%5B0%5D%5B3%5D=" . $url_port . "&amp;tcp_flags%5B0%5D=&amp;" . $url_port_type . "_port%5B0%5D%5B4%5D=+" . "&amp;" . $url_port_type . "_port%5B0%5D%5B5%5D=+" . "&amp;" . $url_port_type . "_port_cnt=1" . "&amp;layer4=" . $url_layer4 . "&amp;num_result_rows=-1&amp;current_view=-1";
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns */
    if ($proto == TCP) $tmp_rowid = TCP . "_";
    else if ($proto == UDP) $tmp_rowid = UDP . "_";
    else $tmp_rowid = - 1 . "_";
    ($port_type == SOURCE_PORT) ? ($tmp_rowid.= SOURCE_PORT) : ($tmp_rowid.= DEST_PORT);
    $tmp_rowid.= "_" . $myrow[0];
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    qroPrintEntry($currentPort);
    qroPrintEntry('<A HREF="base_stat_sensor.php?' . $url_param . '">' . $num_sensors . '</A>');
    qroPrintEntry('<A HREF="base_qry_main.php?' . $url_param . '&amp;new=1&amp;submit=' . _QUERYDBP . '&amp;sort_order=sig_a">' . $num_events . '</A>');
    qroPrintEntry('<A HREF="base_stat_alerts.php?' . $url_param . '&amp;&sort_order=occur_d">' . $num_sig . '</A>');
    qroPrintEntry('<A HREF="base_stat_uaddr.php?' . $url_param . '&amp;addr_type=1' . '&amp;sort_order=addr_a">' . $num_sip);
    qroPrintEntry('<A HREF="base_stat_uaddr.php?' . $url_param . '&amp;addr_type=2' . '&amp;sort_order=addr_a">' . $num_dip);
    qroPrintEntry($first_time);
    qroPrintEntry($last_time);
    qroPrintEntryFooter();
    ++$i;
    
    // report_data
    $report_data[] = array (
        trim($crPort), $num_sig,
        $num_sip, $num_dip, $first_time, $last_time,
        "", "", "", "", "",
        ($proto<0 ? 0 : ($proto==TCP ? 1 : 2)), $num_sensors, $num_events
    );
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,($port_type == SOURCE_PORT) ? $src_port_report_type : $dst_port_report_type);
$qs->SaveState();
ExportHTTPVar("port_type", $port_type);
ExportHTTPVar("proto", $proto);
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
echo "</body>\r\n</html>";
?>
