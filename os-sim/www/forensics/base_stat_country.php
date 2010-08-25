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
ini_set('memory_limit', '256M');
set_time_limit(180);
include ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_qry_common.php");

if (GET('sensor') != "") ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));;

// Geoip
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
//$addr_type = ImportHTTPVar("addr_type", VAR_DIGIT);
$addr_type = 1;
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    _SELECTED,
    _ALLONSCREEN,
    _ENTIREQUERY
));
$dst_ip = NULL;
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$et = new EventTiming($debug_time_mode);
// The below three lines were moved from line 87 because of the odd errors some users were having
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$cs = new CriteriaState("base_stat_country.php", "&amp;addr_type=1");
$cs->ReadState();
/* Dump some debugging information on the shared state */
if ($debug_mode > 0) {
    PrintCriteriaState();
}
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, _MOSTFREQADDRS, "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */
if ($addr_type == SOURCE_IP) {
    $page_title = _UNISADD;
    $results_title = _SUASRCIP;
    $addr_type_name = "ip_src";
} else {
    if ($addr_type != DEST_IP) ErrorMessage(_SUAERRCRITADDUNK);
    $page_title = _UNIDADD;
    $results_title = _SUADSTIP;
    $addr_type_name = "ip_dst";
}
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
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
        PrintFramedBoxHeader(_QSCSUMM, "#669999", "#FFFFFF");
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
$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
// use accumulate tables only with timestamp criteria
$use_ac = (preg_match("/AND/", preg_replace("/AND \( timestamp/", "", $criteria_clauses[1]))) ? false : true;
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
//$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
//$qs->AddValidActionOp(_SELECTED);
//$qs->AddValidActionOp(_ALLONSCREEN);
$qs->SetActionSQL($from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_UADDR, $db);
$et->Mark("Alert Action");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uaddr.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);
$qro->AddTitle(" ");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$sql = "(SELECT DISTINCT ip_src, 'S', COUNT(acid_event.cid) as num_events ". $sort_sql[0] . $from . $where . " GROUP BY ip_src HAVING num_events>0 " . $sort_sql[1]. ") UNION (SELECT DISTINCT ip_dst, 'D', COUNT(acid_event.cid) as num_events ". $sort_sql[0] . $from . $where . " GROUP BY ip_src HAVING num_events>0 " . $sort_sql[1]. ")";
// use accumulate tables only with timestamp criteria
if ($use_ac) {
    $where = $more = $sqla = $sqlb = $sqlc = "";
    
    if (preg_match("/timestamp/", $criteria_clauses[1])) {
        $where = "WHERE " . str_replace("timestamp", "day", $criteria_clauses[1]);
    }
    $orderby = str_replace("acid_event.", "", $sort_sql[1]); // $orderby not included
    $sql = "(SELECT DISTINCT ip_src, 'S', sum(cid) as num_events
		FROM ac_srcaddr_ipsrc $where GROUP BY ip_src HAVING num_events>0) UNION 
		(SELECT DISTINCT ip_dst, 'D', sum(cid) as num_events
		FROM ac_dstaddr_ipdst $where GROUP BY ip_dst HAVING num_events>0)";
    
}
//echo $sql;
//print_r($_SESSION);
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
//if ($use_ac) $qs->GetCalcFoundRows($cnt_sql, $db);
$et->Mark("Retrieve Query Data");
if ($debug_mode == 1) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
/* Print the current view number and # of rows */
//$qs->PrintResultCnt();

$country_acc = array();
$countries = array(); // Ordered
$hosts_ips = array_keys($hosts);
while (($myrow = $result->baseFetchRow())) {
	if ($myrow[0] == NULL) continue;
    $currentIP = baseLong2IP($myrow[0]);
    $ip_type = $myrow[1];
    $num_events = $myrow[2];
    $field = ($ip_type=='S') ? 'srcnum' : 'dstnum';
    if (geoip_country_name_by_addr($gi, $currentIP)=="" && (Net::isIpInNet($currentIP, $networks) || in_array($currentIP, $hosts_ips))) {
		$country_name = _("Local");
		$country = 'local';
	} else {
		$country = strtolower(geoip_country_code_by_addr($gi, $currentIP));
        $country_name = geoip_country_name_by_addr($gi, $currentIP);
    }
	if ($country_name == "") $country_name = _("Unknown Country");
	//echo "IP $currentIP $country_name <br>";
	if ($country!="local") {
		$countries[$country_name] += $num_events;
		$country_acc[$country_name][$field]++;
		$country_acc[$country_name]['events'] += $num_events;
		$country_acc[$country_name]['flag'] = ($country_name != _("Unknown Country")) ? (($country=="local") ? "<img src=\"images/homelan.png\" border=0 title=\"$country_name\">" : " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" title=\"$country_name\">") : "";
		$country_acc[$country_name]['flagr'] = ($country_name != _("Unknown Country")) ? (($country=="local") ? $current_url."/forensics/images/homelan.png" : $current_url."/pixmaps/flags/".$country.".png") : "";
		$country_acc[$country_name]['code'] = $country;
	}
	// 
}

arsort($countries);

echo '<TABLE BORDER=0 WIDTH="100%">
           <TR><TD CLASS="header" width="25%">Country</TD>
               <TD CLASS="header" width="15%"># ' . _QSCOFALERTS . '</TD>
               <TD CLASS="header" width="10%"># of Src IPs</TD>
               <TD CLASS="header" width="10%"># of Dst IPs</TD>
			   <TD CLASS="header">Event</TD></TR>';

$report_data = array(); // data to fill report_data 
$max_cnt = 1;
$i = 0;
if (count($countries)==0) {
	echo "<tr><td colspan='5'>"._("There aren't any country event, only local IP's.")."</td></tr>\n";
}
foreach ($countries as $country=>$num) { 
	$cc = ($i % 2 == 0) ? "#eeeeee" : "#ffffff";
	if ($max_cnt == 1 && $num > 0) $max_cnt = $num;
	$data = $country_acc[$country];
	if ($data['srcnum']+$data['dstnum'] == 0) $entry_width = 0;
    else $entry_width = round($data['events'] / $max_cnt * 100);
	if ($entry_width > 0) $entry_color = "#84C973";
	else $entry_color = $cc;
	if ($data['code']=="") $data['code']="unknown";
	?>
	<tr bgcolor="<?=$cc?>">
		<td style="padding:3px"><?=$data['flag']." ".$country?></td>
		<td align="center"><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=alerts&query=<?=urlencode(base64_encode($sql))?>"><?=Util::number_format_locale($data['events'],0)?></a></td>
		<td align="center">
			<? if ($data['srcnum']>0) { ?><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=srcaddress&query=<?=urlencode(base64_encode($sql))?>"><?=Util::number_format_locale($data['srcnum'],0)?></a></td>
			<? } else echo "0" ?>
		<td align="center">
			<? if ($data['dstnum']>0) { ?><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=dstaddress&query=<?=urlencode(base64_encode($sql))?>"><?=Util::number_format_locale($data['dstnum'],0)?></a>
			<? } else echo "0" ?>
			</td>
		<TD><TABLE WIDTH="100%">
		  <TR>
		   <TD BGCOLOR="<?=$entry_color?>" WIDTH="<?=$entry_width?>%">&nbsp;</TD>
		   <TD></TD>
		  </TR>
		 </TABLE>
		</TD>
	</tr>
	<?
	$i++;
    
    // report_data
    $report_data[] = array (
        $country, $data['flagr'],
        "", "", "", "", "", "", "", "", "",
        $data['events'], $data['srcnum']+$data['dstnum'], $entry_width
    );
}

echo '</TABLE>';

$result->baseFreeRows();
$qro->PrintFooter();
//$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_country_events_report_type);
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
echo "</body>\r\n</html>";
geoip_close($gi);
?>
