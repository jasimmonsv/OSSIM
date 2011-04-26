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
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
// Geoip
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);

$hosts_ips = array_keys($hosts);

$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$fqdn = ImportHTTPVar("fqdn", VAR_ALPHA | VAR_SPACE);
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_stat_iplink.php");
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_alerts, gettext("Most Frequent Events"), "occur_d");
$qs->AddCannedQuery("last_alerts", $last_num_ualerts, gettext("Last Events"), "last_d");
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = gettext("IP Links");
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
$where = " WHERE " . $criteria_clauses[1];
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
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_IPLINK, $db);
$et->Mark("Alert Action");
/* Run the query to determine the number of rows (No LIMIT)*/
$qs->current_view = 0;
$qs->num_result_rows = UniqueLinkCnt($db, $criteria_clauses[0], $criteria_clauses[1]);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_iplink.php?fqdn=$fqdn&caller=$caller");
$qro->AddTitle(" ");
if ($fqdn=="yes") $qro->AddTitle(gettext("Source FQDN"));
$qro->AddTitle(gettext("Source IP"), "sip_a", "", " ORDER BY ip_src ASC", "sip_d", "", " ORDER BY ip_src DESC");
$qro->AddTitle(gettext("Direction"));
$qro->AddTitle(gettext("Destination IP"), "dip_a", "", " ORDER BY ip_dst ASC", "dip_d", "", " ORDER BY ip_dst DESC");
if ($fqdn=="yes") $qro->AddTitle(gettext("Destination FQDN"));
$qro->AddTitle(gettext("Protocol"), "proto_a", "", " ORDER BY ip_proto ASC", "proto_d", "", " ORDER BY ip_proto DESC");
$qro->AddTitle(gettext("Unique Dst Ports"), "dport_a", "", " ORDER BY clayer4 ASC", "dport_d", "", " ORDER BY clayer4 DESC");
$qro->AddTitle(gettext("Unique Events"), "sig_a", "", " ORDER BY csig ASC", "sig_d", "", " ORDER BY csig DESC");
$qro->AddTitle(gettext("Total Events"), "events_a", "", " ORDER BY ccid ASC", "events_d", "", " ORDER BY ccid DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$sql = "SELECT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto, COUNT(DISTINCT acid_event.layer4_dport) as clayer4, COUNT(acid_event.cid) as ccid, COUNT(DISTINCT acid_event.plugin_id, acid_event.plugin_sid) csig " . $sort_sql[0] . $from . $where . " GROUP by ip_src, ip_dst, ip_proto " . $sort_sql[1] ;
#$sql = "SELECT DISTINCT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto " . $sort_sql[0] . $from . $where . $sort_sql[1];
/* Run the Query again for the actual data (with the LIMIT) */
$qs->current_view = $submit;
//echo "$sql<br>\n";
$result = $qs->ExecuteOutputQuery($sql, $db);
$et->Mark("Retrieve Query Data");
if ($debug_mode == 1) {
    $qs->PrintCannedQueryList();
    $qs->DumpState();
    echo "$sql<BR>";
}
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique ip links %d-%d of <b>%s</b> matching your selection.");
if (Session::am_i_admin()) $displaying .= gettext(" <b>%s</b> total events in database.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_iplink.php">';
$qro->PrintHeader();
$i = 0;
$report_data = array(); // data to fill report_data 

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $sip = $myrow[0]; $ip_sip = baseLong2IP($sip);
    $dip = $myrow[1]; $ip_dip = baseLong2IP($dip);
    $proto = $myrow[2];
    if ($fqdn=="yes") {
		$sip_fqdn = baseGetHostByAddr($ip_sip , $db, $dns_cache_lifetime);
		$dip_fqdn = baseGetHostByAddr($ip_dip , $db, $dns_cache_lifetime);
	}
    /* Get stats on the link */
    if ($sip && $dip) {
        #$temp = "SELECT COUNT(DISTINCT layer4_dport), " . "COUNT(acid_event.cid), COUNT(DISTINCT acid_event.signature)  " . $from . $where . " AND acid_event.ip_src='" . $sip . "' AND acid_event.ip_dst='" . $dip . "' AND acid_event.ip_proto='" . $proto . "'";
        #$result2 = $db->baseExecute($temp);
        #$row = $result2->baseFetchRow();
        #$num_occurances = $row[1];
        #$num_unique_dport = $row[0];
        #$num_unique = $row[2];
        #$result2->baseFreeRows();
        $num_occurances = $myrow[4];
        $num_unique_dport = $myrow[3];
        $num_unique = $myrow[5];
        /* Print out */
        qroPrintEntryHeader($i);
        $tmp_ip_criteria = '&amp;ip_addr%5B0%5D%5B0%5D=+&amp;ip_addr%5B0%5D%5B1%5D=ip_src&amp;ip_addr%5B0%5D%5B2%5D=%3D' . '&amp;ip_addr%5B0%5D%5B3%5D=' . baseLong2IP($sip) . '&amp;ip_addr%5B0%5D%5B8%5D=+&amp;ip_addr%5B0%5D%5B9%5D=AND' . '&amp;ip_addr%5B1%5D%5B0%5D=+&amp;ip_addr%5B1%5D%5B1%5D=ip_dst&amp;ip_addr%5B1%5D%5B2%5D=%3D' . '&amp;ip_addr%5B1%5D%5B3%5D=' . baseLong2IP($dip) . '&amp;ip_addr%5B1%5D%5B8%5D=+&amp;ip_addr%5B1%5D%5B9%5D=+' . '&amp;ip_addr_cnt=2'; //&amp;layer4=' . IPProto2str($proto);
        $tmp_rowid = $sip . "_" . $dip . "_" . $proto;
        //echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
        //echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
        echo "<td></td>";
        $s_country = strtolower(geoip_country_code_by_addr($gi, $ip_sip));
        $s_country_name = geoip_country_name_by_addr($gi, $ip_sip);
        $homelan_sip = (($match_cidr = Net::is_ip_in_cache_cidr($_conn, $ip_sip)) || in_array($ip_sip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$ip_sip'><img src=\"".Host::get_homelan_icon($ip_sip,$icons,$match_cidr,$_conn)."\" border=0></a>" : "";
        if ($s_country) {
            $s_country_img = " <img src=\"/ossim/pixmaps/flags/" . $s_country . ".png\" title=\"" . $s_country_name . "\">";
            $slnk = $current_url."/pixmaps/flags/".$s_country.".png";
        } else {
            $s_country_img = "";
            $slnk = ($homelan_sip!="") ? $current_url."/forensics/images/homelan.png" : "";
        }
        $d_country = strtolower(geoip_country_code_by_addr($gi, $ip_dip));
        $d_country_name = geoip_country_name_by_addr($gi, $ip_dip);
        $homelan_dip = (($match_cidr = Net::is_ip_in_cache_cidr($_conn, $ip_dip)) || in_array($ip_dip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$ip_dip'><img src=\"".Host::get_homelan_icon($ip_dip,$icons,$match_cidr,$_conn)."\" border=0></a>" : "";
        if ($d_country) {
            $d_country_img = " <img src=\"/ossim/pixmaps/flags/" . $d_country . ".png\" title=\"" . $d_country_name . "\">";
            $dlnk = $current_url."/pixmaps/flags/".$d_country.".png";
        } else {
            $d_country_img = "";
            $dlnk = ($homelan_dip!="") ? $current_url."/forensics/images/homelan.png" : "";
        }
        if ($fqdn=="yes") qroPrintEntry('<FONT>' . $sip_fqdn . '</FONT>');
        qroPrintEntry(BuildAddressLink(baseLong2IP($sip) , 32) . $ip_sip . '</A>' . $s_country_img . $homelan_sip, "", "", "nowrap");
        qroPrintEntry('<img src="images/dash.png" border="0">');
        qroPrintEntry(BuildAddressLink(baseLong2IP($dip) , 32) . $ip_dip . '</A>' . $d_country_img . $homelan_dip, "", "", "nowrap");
        if ($fqdn=="yes") qroPrintEntry('<FONT>' . $dip_fqdn . '</FONT>');
        qroPrintEntry('<FONT>' . IPProto2str($proto) . '</FONT>');
        $tmp = '<A HREF="base_stat_ports.php?port_type=2&amp;proto=' . $proto . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . $num_unique_dport . '</A>');
        $tmp = '<A HREF="base_stat_alerts.php?foo=1' . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . $num_unique . '</A>');
        $tmp = '<A HREF="base_qry_main.php?new=1' . '&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query+DB") . '&amp;current_view=-1' . $tmp_ip_criteria . '">';
        qroPrintEntry($tmp . $num_occurances . '</A>');
        qroPrintEntryFooter();
    }
    $i++;
    
    // report_data
    $report_data[] = array (
        $ip_sip, $slnk, $ip_dip, $dlnk, IPProto2str($proto),
        "", "", "", "", "", "",
        $num_unique_dport, $num_unique, $num_occurances
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_iplinks_report_type);
$qs->SaveState();
echo "<input type='hidden' name='fqdn' value='$fqdn'>\n";
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
echo "</body>\r\n</html>";
geoip_close($gi);
?>
