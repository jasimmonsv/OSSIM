<?php
/**
* Class and Function List:
* Function list:
* - PrintCleanURL()
* - PrintBinDownload()
* - PrintPcapDownload()
* - PrintPacketLookupBrowseButtons2()
* - showShellcodeAnalysisLink()
* - PrintPacketLookupBrowseButtons()
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
$_SESSION['norefresh'] = 1;
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$payload = FALSE;
$offset = 0;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
// set cookie for packet display
if (isset($_GET['asciiclean'])) {
    1 == $_GET['asciiclean'] ? setcookie('asciiclean', 'clean') : setcookie('asciiclean', 'normal');
}
function PrintCleanURL() {
    // This function creates the url to display the cleaned up payload -- Kevin
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    $sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
    if ((isset($_GET['asciiclean']) && $_GET['asciiclean'] == 1) || (isset($_COOKIE['asciiclean']) && ($_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
        //create link to non-cleaned payload display
        $url = '<center><a href="base_qry_alert.php?' . $query;
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=0">' . gettext("Normal Display") . '</a></center>';
        return $url;
    } else {
        //create link to cleaned payload display
        $url = '<center><a href="base_qry_alert.php?' . $query;
        $url.= '&amp;sort_order=' . urlencode($sort_order) . '&amp;asciiclean=1">' . gettext("Plain Display") . '</a></center>';
        return $url;
    }
}
function PrintBinDownload($db, $cid, $sid) {
    // Offering a URL to a download possibility:
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if (isset($_GET['asciiclean']) && ($_GET['asciiclean'] == 1) || ((isset($_COOKIE['asciiclean']) && $_COOKIE['asciiclean'] == "clean") && (!isset($_GET['asciiclean'])))) {
        $url = '<center><a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;cid=' . urlencode($cid) . '&amp;sid=' . urlencode($sid) . '&amp;asciiclean=1">Download of Payload</a></center>';
    } else {
        $url = '<center><a href="base_payload.php?' . $query;
        $url.= '&amp;download=1&amp;cid=' . urlencode($cid) . '&amp;sid=' . urlencode($sid) . '&amp;asciiclean=0">Download of Payload</a></center>';
    }
    return $url;
}
function PrintPcapDownload($db, $cid, $sid) {
    if (is_array($db->DB->MetaColumnNames('data')) && (!in_array("pcap_header", $db->DB->MetaColumnNames('data')) || !in_array("data_header", $db->DB->MetaColumnNames('data')))) {
        $type = 3;
    } else {
        $type = 2;
    }
    $query = CleanVariable($_SERVER["QUERY_STRING"], VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    if ((isset($_GET['asciiclean']) && $_GET['asciiclean'] == 1) || (isset($_COOKIE['asciiclean']) && ($_COOKIE["asciiclean"] == "clean") && (!isset($_GET['asciiclean'])))) {
        $url = '<center><a href="base_payload.php?' . $query;
        $url.= '&amp;download=' . urlencode($type) . '&amp;cid=' . urlencode($cid) . '&amp;sid=' . urlencode($sid) . '&amp;asciiclean=1">Download in pcap format</a></center>';
    } else {
        $url = '<center><a href="base_payload.php?' . $query;
        $url.= '&amp;download=' . urlencode($type) . '&amp;cid=' . urlencode($cid) . '&amp;sid=' . urlencode($sid) . '&amp;asciiclean=0">Download in pcap format</a></center>';
    }
    return $url;
}
function PrintPacketLookupBrowseButtons2($seq, $order_by_tmp, $where_tmp, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    //if ($where_tmp != "") $_SESSION["where"] = $where_tmp;
    //if ($order_by_tmp != "") $_SESSION["order_by"] = $order_by_tmp;
    //$order_by = $_SESSION["order_by"];
    //$where = $_SESSION["where"];
    $order_by = $order_by_tmp;
    $where = $where_tmp;
    if ($seq < 1) {
        $sql = "SELECT acid_event.sid, acid_event.cid $where $order_by limit $seq,2";
        //echo $sql;
        $result2 = $db->baseExecute($sql);
        $previous_button = '[ ' . gettext("First") . ' ]' . "\n";
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button = '[ ' . gettext("Last") . ' ]' . "\n";
        else {
            $next_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&gt;&gt; ' . gettext("Next") . ' #';
            $next_button.= ($seq + 1) . '-(' . $myrow2["sid"] . '-' . $myrow2["cid"] . ')">' . "\n";
        }
    } else {
        $sql = "SELECT acid_event.sid, acid_event.cid $where $order_by limit " . intval($seq - 1) . ",3";
        //echo $sql;
        $result2 = $db->baseExecute($sql);
        $myrow2 = $result2->baseFetchRow();
        $previous_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&lt;&lt; ' . gettext("Previous") . ' #';
        $previous_button.= ($seq - 1) . '-(' . $myrow2["sid"] . '-' . $myrow2["cid"] . ')">' . "\n";
        $myrow2 = $result2->baseFetchRow();
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button = '[ ' . gettext("Last") . ' ]' . "\n";
        else {
            $next_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&gt;&gt; ' . gettext("Next") . ' #';
            $next_button.= ($seq + 1) . '-(' . $myrow2["sid"] . '-' . $myrow2["cid"] . ')">' . "\n";
        }
    }
    $result2->baseFreeRows();
}
function showShellcodeAnalysisLink($cid, $sid, $signature) {
    $url = (!preg_match("/shellcode/i",$signature)) ? '' : '<center><a href="shellcode.php?cid=' . $cid . '&amp;sid=' . $sid . '">Shellcode Analysis</a></center>';
    return $url;
}
function PrintPacketLookupBrowseButtons($seq, $save_sql, $db, &$previous_button, &$next_button) {
    echo "\n\n<!-- Single Alert Browsing Buttons -->\n";
    $result2 = $db->baseExecute($save_sql);
    if ($seq == 0) $previous_button = '[ ' . gettext("First") . ' ]' . "\n";
    $i = 0;
    while ($i <= $seq + 1) {
        $myrow2 = $result2->baseFetchRow();
        if ($myrow2 == "") $next_button = '[ ' . gettext("Last") . ' ]' . "\n";
        else if ($i == $seq - 1) {
            $previous_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&lt;&lt; ' . gettext("Previous") . ' #';
            $previous_button.= ($seq - 1) . '-(' . $myrow2[0] . '-' . $myrow2[1] . ')">' . "\n";
        } else if ($i == $seq + 1) {
            $next_button = '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="&gt;&gt; ' . gettext("Next") . ' #';
            $next_button.= ($seq + 1) . '-(' . $myrow2[0] . '-' . $myrow2[1] . ')">' . "\n";
        }
        $i++;
    }
    $result2->baseFreeRows();
}

/*
*  Need to import $submit and set the $QUERY_STRING early to support
*  the back button.  Otherwise, the value of $submit will not be passed
*  to the history.
*/
/* This call can include "#xx-(xx-xx)" values and "submit" values. */
$submit = ImportHTTPVar("submit", VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
//if(preg_match("/^#0(-\(\d+-\d+\))$/", $submit, $matches)){
//$submit = "#1" . $matches[1];
//}
$sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
$_SERVER["QUERY_STRING"] = "submit=" . rawurlencode($submit);
//unset($_GET["sort_order"]);
$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . gettext("Query+DB"));
$cs->ReadState();
$qs = new QueryState();
$page_title = gettext("Event");
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
if (!array_key_exists("minimal_view", $_GET)) PrintCriteria("");
$criteria_clauses = ProcessCriteria();
$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
// Payload special case
//if (preg_match("/data_payload/", $criteria_clauses[1])) {
//    $where = ",extra_data WHERE acid_event.sid = extra_data.sid AND acid_event.cid=extra_data.cid AND " . $criteria_clauses[1];
//}
//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->SetActionSQL($sort_sql[0] . $from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_ALERT_DISPLAY, $db);
$et->Mark("Alert Action");
/* If get a valid (sid,cid) store it in $caller.
* But if $submit is returning from an alert action
* get the (sid,cid) back from $caller
*/
if ($submit == gettext("Delete Selected")) $submit = ImportHTTPVar("caller", VAR_DIGIT | VAR_PUNC);
else $caller = $submit;
/* Setup the Query Results Table -- However, this data structure is not
* really used for output.  Rather, it duplicates the sort SQL set in
*  base_qry_sqlcalls.php
*/
$qro = new QueryResultsOutput("");
$qro->AddTitle(gettext("Signature"), "sig_a", " ", " ORDER BY sig_name ASC", "sig_d", " ", " ORDER BY sig_name DESC");
$qro->AddTitle("Timestamp", "time_a", " ", " ORDER BY timestamp ASC ", "time_d", " ", " ORDER BY timestamp DESC ");
$qro->AddTitle("Source<BR>Address", "sip_a", " ", " ORDER BY ip_src ASC", "sip_d", " ", " ORDER BY ip_src DESC");
$qro->AddTitle("Dest.<BR>Address", "dip_a", " ", " ORDER BY ip_dst ASC", "dip_d", " ", " ORDER BY ip_dst DESC");
$qro->AddTitle("Layer 4<BR>Proto", "proto_a", " ", " ORDER BY layer4_proto ASC", "proto_d", " ", " ORDER BY layer4_proto DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* Apply sort criteria */
if ($sort_sql[1]=="" && !isset($sort_order)) $sort_order = "time_d";
if ($sort_order == "sip_a") {
    $sort_sql[1] = " ORDER BY ip_src ASC,timestamp DESC";
    $where = str_replace("1  AND ( timestamp", "ip_src >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "sip_d") {
    $sort_sql[1] = " ORDER BY ip_src DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_src >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "dip_a") {
    $sort_sql[1] = " ORDER BY ip_dst ASC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "dip_d") {
    $sort_sql[1] = " ORDER BY ip_dst DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $where);
} elseif ($sort_order == "sig_a") {
    $sort_sql[1] = " ORDER BY plugin_id ASC,plugin_sid,timestamp DESC";
} elseif ($sort_order == "sig_d") {
    $sort_sql[1] = " ORDER BY plugin_id DESC,plugin_sid,timestamp DESC";
} elseif ($sort_order == "time_a") {
    $sort_sql[1] = " ORDER BY timestamp ASC";
} elseif ($sort_order == "time_d") {
    $sort_sql[1] = " ORDER BY timestamp DESC";
} elseif ($sort_order == "oasset_d_a") {
    $sort_sql[1] = " ORDER BY ossim_asset_dst ASC,timestamp DESC";
} elseif ($sort_order == "oasset_d_d") {
    $sort_sql[1] = " ORDER BY ossim_asset_dst DESC,timestamp DESC";
} elseif ($sort_order == "oprio_a") {
    $sort_sql[1] = " ORDER BY ossim_priority ASC,timestamp DESC";
} elseif ($sort_order == "oprio_d") {
    $sort_sql[1] = " ORDER BY ossim_priority DESC,timestamp DESC";
} elseif ($sort_order == "oriska_a") {
    $sort_sql[1] = " ORDER BY ossim_risk_c ASC,timestamp DESC";
} elseif ($sort_order == "oriska_d") {
    $sort_sql[1] = " ORDER BY ossim_risk_c DESC,timestamp DESC";
} elseif ($sort_order == "oriskd_a") {
    $sort_sql[1] = " ORDER BY ossim_risk_a ASC,timestamp DESC";
} elseif ($sort_order == "oriskd_d") {
    $sort_sql[1] = " ORDER BY ossim_risk_a DESC,timestamp DESC";
} elseif ($sort_order == "oreli_a") {
    $sort_sql[1] = " ORDER BY ossim_reliability ASC,timestamp DESC";
} elseif ($sort_order == "oreli_d") {
    $sort_sql[1] = " ORDER BY ossim_reliability DESC,timestamp DESC";
} elseif ($sort_order == "proto_a") {
    $sort_sql[1] = " ORDER BY ip_proto ASC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $where);
} elseif ($sort_order == "proto_d") {
    $sort_sql[1] = " ORDER BY ip_proto DESC,timestamp DESC";
    $where = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $where);
}

$save_sql = "SELECT acid_event.sid, acid_event.cid" . $sort_sql[0] . $from . $where . $sort_sql[1];
//print_r($save_sql);
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
GetQueryResultID($submit, $seq, $sid, $cid);
if ($debug_mode > 0) echo "\n====== Alert Lookup =======<BR>
           submit = $submit<br>
		   sid = $sid<BR>
           cid = $cid<BR>
           seq = $seq<BR>\n" . "===========================<BR>\n";
/* Verify that have extracted (sid, cid) correctly */
if (!($sid > 0 && $cid > 0)) {
    ErrorMessage(gettext("Invalid (sid,cid) pair") . " (" . $sid . "," . $cid . ")");
    exit();
}
$tmp_sql = $sort_sql[1];
echo "<!-- END HEADER TABLE -->
		  </div> </TD>
           </TR>
          </TABLE>";
echo "<FORM METHOD=\"GET\" ACTION=\"base_qry_alert.php\">\n";
// Normal view
if (!array_key_exists("minimal_view", $_GET)) {
	PrintPacketLookupBrowseButtons2($seq, $tmp_sql, $sort_sql[0] . $from . $where, $db, $previous, $next);
	echo "<CENTER>\n<B>" . gettext("Event") . " #" . ($seq) . "</B><BR>\n$previous &nbsp&nbsp&nbsp\n$next\n</CENTER>\n";
	echo "<HR style='border:none;background:rgb(202, 202, 202);height:1px;margin-top:15px;margin-bottom:15px'>\n";
// In graybox external minimal view (no pagging)
} elseif (!array_key_exists("noback", $_GET)) {
	echo "<div align='center'><input type='button' class='button' value='Back' onclick='javascript:history.back()'></div>";
}

/* Make Selected */
echo "\n<INPUT TYPE=\"hidden\" NAME=\"action_chk_lst[0]\" VALUE=\"$submit\">\n";
/* Event */
//$sql2 = "SELECT signature, timestamp FROM acid_event WHERE sid='" .  filterSql($sid,$db) . "' AND cid='" .  filterSql($cid,$db) . "'";
$sql2 = "SELECT plugin_id, plugin_sid, timestamp, tzone, ip_src, ip_dst, ip_proto, layer4_sport, layer4_dport, ossim_priority, ossim_reliability, ossim_asset_src, ossim_asset_dst, ossim_risk_c, ossim_risk_a FROM acid_event WHERE sid='" . filterSql($sid,$db) . "' AND cid='" . filterSql($cid,$db) . "'";
//echo $sql2;
$result2 = $db->baseExecute($sql2);
$myrow2 = $result2->baseFetchRow();
$plugin_id = $myrow2[0];
$plugin_sid = $myrow2[1];
$timestamp = $myrow2[2];
$tzone = $myrow2[3];
$ip_src = $myrow2[4]; $current_sip = baseLong2IP($ip_src);
$ip_dst = $myrow2[5]; $current_dip = baseLong2IP($ip_dst);
$ip_proto = $myrow2[6];
$layer4_sport = $myrow2[7];
$layer4_dport = $myrow2[8];
$ossim_priority= $myrow2[9];
$ossim_reliability = $myrow2[10];
$ossim_asset_src = $myrow2[11];
$ossim_asset_dst = $myrow2[12];
$ossim_risk_c = $myrow2[13];
$ossim_risk_a = $myrow2[14];
if ($plugin_id == "" || $plugin_sid == "") {
    echo '<CENTER><B>';
    ErrorMessage(gettext("Event DELETED"));
    echo '</CENTER></B>';
    echo "</body>\r\n</html>";
    exit(0);
}
/* Get sensor parameters: */
$sql4 = "SELECT * FROM sensor  WHERE sid='" . filterSql($sid,$db) . "'";
$result4 = $db->baseExecute($sql4);
$myrow4 = $result4->baseFetchRow();
$result4->baseFreeRows();
$encoding = $myrow4["encoding"];
$detail = $myrow4["detail"];
$payload = "";
/* Get plugin id & sid */
//$sql5 = "select ossim_event.plugin_id, ossim_event.plugin_sid, ossim.plugin.name, ossim.plugin_sid.name, extra_data.filename, extra_data.username, extra_data.password, extra_data.userdata1, extra_data.userdata2, extra_data.userdata3, extra_data.userdata4, extra_data.userdata5, extra_data.userdata6, extra_data.userdata7, extra_data.userdata8, extra_data.userdata9 from ossim_event, ossim.plugin, ossim.plugin_sid join extra_data on extra_data.sid = '" . intval($sid) . "' and extra_data.cid = '" . intval($cid) . "' where ossim.plugin_sid.plugin_id = ossim.plugin.id and ossim.plugin_sid.sid = ossim_event.plugin_sid and ossim_event.plugin_id = ossim.plugin.id and ossim_event.sid = '" . intval($sid) . "' and ossim_event.cid = '" . intval($cid) . "'";
$sql5 = "select ossim.plugin.name, ossim.plugin_sid.name from ossim.plugin, ossim.plugin_sid where ossim.plugin_sid.plugin_id = ossim.plugin.id and ossim.plugin_sid.sid = $plugin_sid and ossim.plugin.id = $plugin_id";
//echo $sql5;
$result5 = $db->baseExecute($sql5);
if ($myrow5 = $result5->baseFetchRow()) {
    $plugin_name = $myrow5[0];
    $plugin_sid_name = $myrow5[1];
    $result5->baseFreeRows();
}
// extra_data
$filename = $username = $password = $userdata1 = $userdata2 = $userdata3 = $userdata4 = $userdata5 = $userdata6 = $userdata7 = $userdata8 = $userdata9 = "(null)";
$context = 0;
$sql6 = "select filename,username,password,userdata1,userdata2,userdata3,userdata4,userdata5,userdata6,userdata7,userdata8,userdata9,data_payload,context from extra_data where sid = '" . intval($sid) . "' and cid = '" . intval($cid) . "';";
//echo $sql6;
$result6 = $db->baseExecute($sql6);
if ($myrow6 = $result6->baseFetchRow()) {
    $filename = Util::htmlentities($myrow6["filename"]);
    $username = $myrow6["username"];
    $password = $myrow6["password"];
    $userdata1 = Util::htmlentities($myrow6["userdata1"]);
    $userdata2 = Util::htmlentities($myrow6["userdata2"]);
    $userdata3 = Util::htmlentities($myrow6["userdata3"]);
    $userdata4 = Util::htmlentities($myrow6["userdata4"]);
    $userdata5 = Util::htmlentities($myrow6["userdata5"]);
    $userdata6 = Util::htmlentities($myrow6["userdata6"]);
    $userdata7 = Util::htmlentities($myrow6["userdata7"]);
    $userdata8 = Util::htmlentities($myrow6["userdata8"]);
    $userdata9 = Util::htmlentities($myrow6["userdats9"]);
    $payload = $myrow6["data_payload"];
    $context = $myrow6["context"];
    $result6->baseFreeRows();
}

// Timezone
$tz=(isset($_SESSION["_timezone"])) ? intval($_SESSION["_timezone"]) : intval(date("O"))/100;
$event_date = $timestamp;
$tzdate = $event_date;
$event_date_uut = get_utc_unixtime($db,$event_date);
// Event date timezone
if ($tzone!=0) $event_date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tzone));    
// Apply user timezone
if ($tz!=0) $tzdate = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));
	
$tzcell = ($event_date==$timestamp || $event_date==$tzdate) ? 0 : 1;
_("Event date").": ".htmlspecialchars("<b>".$event_date."</b><br>"._("Timezone").": <b>".Util::timezone($tzone)."</b>");
	
// This is one array that contains all the ids that are been used by snort, this way we will show more info for those events.
$snort_ids = range(1000, 1500);
// ojo antes GetTagTriger(BuildSigByID($myrow2[0], $db, 1, $plugin_id) , $db, $sid, $cid))
echo '
       <BLOCKQUOTE>
       <TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
          <TR><TD CLASS="header3" WIDTH=50 ALIGN=CENTER ROWSPAN=4>Meta</TD>
              <TD>
                  <TABLE BORDER=0 CELLPADDING=4>
                    <TR><TD CLASS="header" >' . _("ID") . ' #</TD>
                        <TD CLASS="header" nowrap>' . _("Date")." ".Util::timezone($tz) . '</TD>
                        '.($tzcell ? '<TD CLASS="header" nowrap>'._("Event date").'</TD>' : '').'
                        <TD CLASS="header">' . _("Triggered Signature") . '</TD>
                        <TD CLASS="header" nowrap>' . _("Data Source Name") . '</TD>
                        <TD CLASS="header" nowrap>' . _("Data Source ID") . '</TD>
                        <TD CLASS="header" nowrap>' . _("Event Type ID") . '</TD>
						<TD></td></TR>
                    <TR><TD CLASS="plfield" nowrap>' . ($sid . " - " . $cid) . '</TD>
                        <TD CLASS="plfield" nowrap>' . htmlspecialchars($tzdate) . '</TD>
                        '.($tzcell ? '<TD CLASS="plfield" nowrap>'.$event_date.'<br>'.Util::timezone($tzone).'</TD>' : '').'
                        <TD CLASS="plfield">';
	$htmlTriggeredSignature=html_entity_decode(htmlspecialchars(str_replace("##", "", BuildSigByPlugin($plugin_id, $plugin_sid, $db))));
	echo $htmlTriggeredSignature.'</TD>
                        <TD CLASS="plfield">' . $plugin_name . '</TD>
                        <TD CLASS="plfield">' . $plugin_id . '</TD>
                        <TD CLASS="plfield">' . $plugin_sid . '</TD>
						'.(($_GET['minimal_view'] == "") ? '<TD CLASS="plfield"><a href="javascript:;" onclick="GB_show(\''._("Modify Rel/Prio").'\',\'modify_relprio.php?id='.$plugin_id.'&sid='.$plugin_sid.'\',200,400)" class="greybox"><img src="../pixmaps/pencil.png" border="0" alt="'._("Modify Rel/Prio").'" title="'._("Modify Rel/Prio").'"></a></td>' : '');
'<a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=2009-0033" target="_blank"><img src="manage_references_icon.php?id=5" alt="cve" title="cve" border="0"></a> <a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=2007-5976" target="_blank"><img src="manage_references_icon.php?id=5" alt="cve" title="cve" border="0"></a> pads: New service detectedArray
';
	
	//<--
	$return;
	foreach(explode('http://cve.mitre.org/cgi-bin/cvename.cgi?name=',$htmlTriggeredSignature) as $key => $value ){
		if($key!=0){
			$posIni=strpos($value,"'");
			if($posIni!==false){
				$return[]='CVE-'.substr($value,0,$posIni);
			}
		}
	}
	if(!empty($return)){
		$arrayData='data='.implode('__',$return).'&plugin_id='.$plugin_id.'&plugin_sid='.$plugin_sid;
	?>
		<TD CLASS="plfield">
			<a href="javascript:;" title="<?php echo _("Info from OSVDB");?>" class="greybox" onclick="GB_show('Info from OSVDB','osvdb_info.php?<?php echo $arrayData; ?>',450,'90%')"><img src="../vulnmeter/images/osvdb.png" border="0"></a>
		</TD>
		<?php
	}
	//-->
	
echo '</TR>
                  </TABLE>
              </TD>
           </TR>';
// COMMON DATA
//
include ("geoip.inc");
$gi = geoip_open("/usr/share/geoip/GeoIP.dat", GEOIP_STANDARD);
$_conn = $dbo->connect();
$hosts_ips = array_keys($hosts);
// Source
$country = strtolower(geoip_country_code_by_addr($gi, $current_sip));
$country_name = geoip_country_name_by_addr($gi, $current_sip);
$country_img = ($country) ? " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">" : "";
$sip_aux = ($sensors[$current_sip] != "") ? $sensors[$current_sip] : (($hosts[$current_sip] != "") ? $hosts[$current_sip] : $current_sip);
if ($sip_aux!=$current_sip)
	$sip_aux = "[$sip_aux] $current_sip";
$homelan = (Net::is_ip_in_cache_cidr($_conn, $current_sip) || in_array($current_sip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_sip'><img src=\"images/homelan.png\" border=0></a>" : "";
$ip_src_data = '<A HREF="base_stat_ipaddr.php?ip=' . $current_sip . '&amp;netmask=32">' . $sip_aux . $country_img . $homelan;
// Destionation
$country = strtolower(geoip_country_code_by_addr($gi, $current_dip));
$country_name = geoip_country_name_by_addr($gi, $current_dip);
$country_img = ($country) ? " <img src=\"../pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">" : "";
$dip_aux = ($sensors[$current_dip] != "") ? $sensors[$current_dip] : (($hosts[$current_dip] != "") ? $hosts[$current_dip] : $current_dip);
if ($dip_aux!=$current_dip)
	$dip_aux = "[$dip_aux] $current_dip";
$homelan = (Net::is_ip_in_cache_cidr($_conn, $current_dip) || in_array($current_dip, $hosts_ips)) ? " <a href='javascript:;' class='scriptinfo' style='text-decoration:none' ip='$current_dip'><img src=\"images/homelan.png\" border=0></a>" : "";
$ip_dst_data = '<A HREF="base_stat_ipaddr.php?ip=' . $current_dip . '&amp;netmask=32">' . $dip_aux . $country_img . $homelan;
geoip_close($gi);
$dbo->close($_conn);
echo '  <TR>
             <TD>
                <TABLE BORDER=0 CELLPADDING=4>
                  <TR>
                       <TD class="header">' . gettext("Source Address") . '</TD>
                       <TD class="header">' . gettext("Source Port") . '</TD>
                       <TD class="header">' . gettext("Destination Address") . '</TD>
                       <TD class="header">' . gettext("Destination Port") . '</TD>
                       <TD class="header">' . gettext("Protocol") . '</TD>
                       <TD class="header">' . gettext("Asset").' S<img border="0" align="absmiddle" src="images/arrow-000-small.gif">D</TD>
                       <TD class="header">' . gettext("Priority") . '</TD>
                       <TD class="header">' . gettext("Reliability") . '</TD>
                       <TD class="header">' . gettext("Risk") . '</TD>
                  </TR>
                  <TR><TD class="plfield" nowrap>' . $ip_src_data . '</TD>
                      <TD class="plfield" nowrap>' . $layer4_sport . '</TD>
                      <TD class="plfield" nowrap>' . $ip_dst_data . '</TD>
                      <TD class="plfield" nowrap>' . $layer4_dport . '</TD>
                      <TD class="plfield" nowrap>' . IPProto2str($ip_proto) . '</TD>
                      <TD class="plfield" nowrap><img src="bar2.php?value=' . $ossim_asset_src . '&value2=' . $ossim_asset_dst . '&max=5" border="0" align="absmiddle" title="'.$ossim_asset_src -> $ossim_asset_dst.'"></TD>
                      <TD class="plfield" nowrap><img src="bar2.php?value=' . $ossim_priority . '&max=5" border="0" align="absmiddle" title="'.$ossim_priority.'"></TD>
                      <TD class="plfield" nowrap><img src="bar2.php?value=' . $ossim_reliability . '&max=9" border="0" align="absmiddle" title="'.$ossim_reliability.'"></TD>
                      <TD class="plfield" nowrap><img src="bar2.php?value=' . $ossim_risk_c . '&value2=' . $ossim_risk_a . '&max=9&range=1" border="0" align="absmiddle" title="'.$ossim_risk_c -> $ossim_risk_a.'"></TD>
                  </TR>
                 </TABLE>     
             </TD>
          </TR>';            
// END COMMON DATA
           
echo '  <TR>
             <TD>
                <TABLE BORDER=0 CELLPADDING=4>
                  <TR><TD CLASS="header2" ALIGN=CENTER ROWSPAN=2>' . gettext("Sensor")  . '</TD>
                       <TD class="header">', gettext("Sensor") . ' ' . gettext("Address"), '</TD>
                       <TD class="header">' . gettext("Interface") . '</TD>
                  </TR>
                  <TR><TD class="plfield">' . htmlspecialchars(($myrow4["sensor"]!="") ? $myrow4["sensor"] : $myrow4["hostname"]) . '</TD>
                      <TD class="plfield">' . (($myrow4["interface"] == "") ? "&nbsp;<I>" . gettext("none") . "</I>&nbsp;" : $myrow4["interface"]) . '</TD>
                  </TR>
                 </TABLE>     
             </TD>
          </TR>';          
if ($resolve_IP == 1) {
    echo '  <TR>
              <TD>
                <TABLE BORDER=0 CELLPADDING=4>
                  <TR><TD CLASS="iptitle" ALIGN=CENTER ROWSPAN=2>FQDN</TD>
                       <TD class="header">' . gettext("Sensor") . ' ' . gettext("Name") . '</TD>
                  </TR>
                  <TR><TD class="plfield">' . (baseGetHostByAddr(($myrow4["sensor"]!="") ? $myrow4["sensor"] : $myrow4["hostname"], $db, $dns_cache_lifetime)) . '</TD>
                  </TR>
                 </TABLE>     
            </TR>';
}
$result4->baseFreeRows();
/*
$sql4 = "SELECT acid_ag_alert.ag_id, ag_name, ag_desc " . "FROM acid_ag_alert LEFT JOIN acid_ag ON acid_ag_alert.ag_id = acid_ag.ag_id " . "WHERE ag_sid='" . $sid . "' AND ag_cid='" . $cid . "'";
$result4 = $db->baseExecute($sql4);
$num = $result4->baseRecordCount();
echo ' <TR>
<TD>
<TABLE BORDER=0 CELLPADDING=4>
<TR><TD CLASS="metatitle" ALIGN=CENTER ROWSPAN='.($num+1).'>'.gettext("Event Group").'</TD>';

if ( $num > 0 )
echo '        <TD class="header">'.gettext("ID").'</TD>
<TD class="header">'.gettext("Name").'</TD>
<TD class="header">'.gettext("Description").'</TD></TR>';
else
echo '        <TD class="plfield">&nbsp;&nbsp;<I>'.gettext("none").'</I>&nbsp;</TD></TR>';

for ($i = 0; $i < $num; $i++)
{
$myrow4 = $result4->baseFetchRow();

echo '    <TR><TD class="plfield">'.$myrow4[0].'</TD>
<TD class="plfield">'.$myrow4[1].'</TD>
<TD class="plfield">'.$myrow4[2].'</TD>
</TR>';
}
echo '      </TABLE>';
$result4->baseFreeRows();

echo '   </TR>';
*/

// Context
switch(intval($context)) {
	case 3:
		$context_txt = '<img src="images/marker_red.png" border="0"> '._("Event prioritized, as target is vulnerable to the attack");
		break;
	
	case 2:
		$context_txt = '<img src="images/marker_green.png" border="0"> '._("Event deprioritized, as target inventory didn't match the list of affected systems");
		break;
	
	case 1:
		$context_txt .= '<img src="images/marker_yellow.png" border="0"> '._("Event prioritized, as target inventory matched the list of affected systems");
		break;
	
	case 0:
		$context_txt = '<img src="images/marker_grey.png" border="0"> '._("No action related to the context analysis");
		break;
}
echo '  <TR>
             <TD>
                <TABLE BORDER=0 CELLPADDING=4>
                  <TR>
                  	<TD CLASS="header2" ALIGN=CENTER>' . _("Context Analysis"). '</TD>
                  	<TD class="plfield">' . $context_txt . '</TD>
                  </TR>
                 </TABLE>    
             </TD>     
          </TR>';
          
echo '    </TABLE>';
$result2->baseFreeRows();
/* IP */
$sql2 = "SELECT ip_src, ip_dst, " . "ip_ver, ip_hlen, ip_tos, ip_len, ip_id, ip_flags, ip_off, ip_ttl, ip_csum, ip_proto" . " FROM iphdr  WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
$result2 = $db->baseExecute($sql2);
$layer4_proto = - 1;
if ($myrow2 = $result2->baseFetchRow()) {
    if ($myrow2[0] != "") {
        $sql3 = "SELECT * FROM opt  WHERE sid='" . $sid . "' AND cid='" . $cid . "' AND opt_proto='0'";
        $result3 = $db->baseExecute($sql3);
        $num_opt = $result3->baseRecordCount();
        echo '<br>
           <TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
              <TR><TD CLASS="header3" WIDTH=50 ROWSPAN=3 ALIGN=CENTER>IP';
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=2>';
        echo '            <TR><TD class="header">' . gettext("Source Address") . '</TD>
                            <TD class="header">' . gettext("Dest. Address") . '</TD>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '
                            <TD class="header">Ver</TD>
                            <TD class="header">Hdr Len</TD>
                            <TD class="header">TOS</TD>
                            <TD class="header">' . gettext("length") . '</TD>
                            <TD class="header">' . gettext("ID") . '</TD>
                            <TD class="header">fragment</TD>
                            <TD class="header">offset</TD>
                            <TD class="header">TTL</TD>
                            <TD class="header">chksum</TD></TR>';
        }
        echo '             <TR><TD class="plfield">
                           <A HREF="base_stat_ipaddr.php?ip=' . baseLong2IP($myrow2[0]) . '&amp;netmask=32">' . baseLong2IP($myrow2[0]) . '</A></TD>';
        echo '                 <TD class="plfield">
                             <A HREF="base_stat_ipaddr.php?ip=' . baseLong2IP($myrow2[1]) . '&amp;netmask=32">' . baseLong2IP($myrow2[1]) . '</A></TD>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[2]) . '</TD>';
            echo '                 <TD class="plfield">' . ($myrow2[3] << 2) . '</TD>'; /* ihl is in 32 bit words, must be multiplied by 4 to show in bytes */
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[4]) . '</TD>';
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[5]) . '</TD>';
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[6]) . '</TD>';
            echo '                 <TD class="plfield">';
            if ($myrow2[7] == 1) echo 'yes';
            else echo 'no';
            echo '</TD>';
            list(, $my_offset,) = unpack("n", pack("S", $myrow2[8]));
            echo '                 <TD class="plfield">' . ($my_offset * 8) . '</TD>';
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[9]) . '</TD>';
            echo '                 <TD class="plfield">' . htmlspecialchars($myrow2[10]) . '<BR>= 0x' . dechex($myrow2[10]) . '</TD></TR>';
        }
        echo '         </TABLE>';
        if ($resolve_IP == 1) {
            echo '  <TR>
                  <TD>
                    <TABLE BORDER=0 CELLPADDING=4>
                      <TR><TD CLASS="iptitle" ALIGN=CENTER ROWSPAN=2>FQDN</TD>
                           <TD class="header">' . gettext("Source Name") . '</TD>
                           <TD class="header">' . gettext("Dest. Name") . '</TD>
                      </TR>
                      <TR><TD class="plfield">' . (baseGetHostByAddr(baseLong2IP($myrow2[0]) , $db, $dns_cache_lifetime)) . '</TD>
                          <TD class="plfield">' . (baseGetHostByAddr(baseLong2IP($myrow2[1]) , $db, $dns_cache_lifetime)) . '</TD>
                      </TR>
                     </TABLE>     
                </TR>';
        }
        echo '  <TR>';
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=4>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '           <TR><TD CLASS="header2" ALIGN=CENTER ROWSPAN=' . (($num_opt != 0) ? ($num_opt + 1) : 1) . '>' . gettext("Options") . '</TD>';
        }
        $layer4_proto = $myrow2[11];
        if ($num_opt > 0) {
            echo '            <TD></TD>
                           <TD class="header">' . gettext("code") . '</TD>
                           <TD class="header">' . gettext("length") . '</TD>
                           <TD class="header" ALIGN=CENTER>' . gettext("data") . '</TD>';
            for ($i = 0; $i < $num_opt; $i++) {
                $myrow3 = $result3->baseFetchRow();
                echo '    <TR><TD>#' . ($i + 1) . '</TD>';
                echo '        <TD class="plfield">' . IPOption2str($myrow3[4]) . '</TD>';
                echo '        <TD class="plfield">' . htmlspecialchars($myrow3[5]) . '</TD>';
                echo '        <TD class="plfield">';
                if ($myrow3[6] != "") echo $myrow3[6];
                else echo '&nbsp;';
                echo '</TD></TR>';
            }
        } else {
            if (in_array($plugin_id, $snort_ids)) {
                echo '             <TD class="plfield"> &nbsp&nbsp&nbsp <I>' . gettext("none") . ' </I></TD></TR>';
            }
        }
        echo '         </TABLE></TD></TR>';
        echo '</TABLE>';
        $result3->baseFreeRows();
    }
    $result2->baseFreeRows();
}
/* TCP */
if ($layer4_proto == "6") {
    $sql2 = "SELECT tcp_sport, tcp_dport, tcp_seq, tcp_ack, tcp_off, tcp_res, tcp_flags, tcp_win, " . "       tcp_csum, tcp_urp FROM tcphdr  WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
    $result2 = $db->baseExecute($sql2);
    if ($myrow2 = $result2->baseFetchRow()) {
        $sql3 = "SELECT * FROM opt  WHERE sid='" . $sid . "' AND cid='" . $cid . "' AND opt_proto='6'";
        $result3 = $db->baseExecute($sql3);
        $num_opt = $result3->baseRecordCount();
        echo '<br>
               <TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
                  <TR><TD CLASS="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER>TCP';
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=2>';
        echo '            <TR><TD class="header">' . gettext("Source") . ' ' . gettext("Port") . '</TD>
                                <TD class="header"> ' . gettext("Dest") . ' ' . gettext("Port") . ' &nbsp</TD>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '                <TD class="header">R 1</TD>
            	                    <TD class="header">R 0</TD>
                    	            <TD class="header">U R G</TD>
                            	    <TD class="header">A C K</TD>
                               	    <TD class="header">P S H</TD>
                                	    <TD class="header">R S T</TD>
                            	    <TD class="header">S Y N</TD>
                           	            <TD class="header">F I N</TD>
                                        <TD class="header">seq #</TD>
                                        <TD class="header">ack</TD>
                                        <TD class="header">offset</TD>
                                        <TD class="header">res</TD>
                                        <TD class="header">window</TD>
                                        <TD class="header">urp</TD>
                       		    <TD class="header">chksum</TD>';
        }
        echo '</TR>';
        $src_port = $myrow2[0] . '<BR>';
        foreach($external_port_link as $name => $baseurl) {
            $src_port = $src_port . '[<A HREF="' . $baseurl . $myrow2[0] . '" TARGET="_ACID_PORT_">' . $name . '</A>] ';
        }
        $dst_port = $myrow2[1] . '<BR>';
        foreach($external_port_link as $name => $baseurl) {
            $dst_port = $dst_port . '[<A HREF="' . $baseurl . $myrow2[1] . '" TARGET="_ACID_PORT_">' . $name . '</A>] ';
        }
        echo '            <TR><TD class="plfield">' . $src_port . '</TD>';
        echo '                <TD class="plfield">' . $dst_port . '</TD>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '                <TD class="plfield">';
            if (($myrow2[6] & 128) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 64) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 32) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 16) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 8) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 4) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 2) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD><TD class="plfield">';
            if (($myrow2[6] & 1) != 0) echo 'X';
            else echo '&nbsp;';
            echo '                    </TD>';
            echo '                <TD class="plfield">' . $myrow2[2] . '</TD>';
            echo '                <TD class="plfield">' . $myrow2[3] . '</TD>';
            /* data offset is in 32 bit words, cf. RFC 793, 3.1 (= p. 16),
            * PrintTCPHeader() in snort-2.6.0/src/log.c
            * DecodeTCP() in snort-2.6.0/src/decode.c
            * #define TCP_OFFSET(tcph) in snort-2.6.0/src/decode.h
            * Database() in snort-2.6.0/src/output-plugins/spo_database.c */
            echo '                <TD class="plfield">' . ($myrow2[4] << 2) . '</TD>';
            echo '                <TD class="plfield">' . $myrow2[5] . '</TD>';
            echo '                <TD class="plfield">' . $myrow2[7] . '</TD>';
            echo '                <TD class="plfield">' . $myrow2[9] . '</TD>';
            echo '                <TD class="plfield">' . $myrow2[8] . '<BR>=<BR>0x' . dechex($myrow2[8]) . '</TD>';
        } // End if == snort_id
        echo '</TR>';
        echo '         </TABLE></TR>';
        echo '  <TR>';
        echo '      <TD>';
        if (in_array($plugin_id, $snort_ids)) {
            echo '         <TABLE BORDER=0 CELLPADDING=4>';
            echo '           <TR><TD CLASS="header3" ALIGN=CENTER ROWSPAN=' . (($num_opt != 0) ? ($num_opt + 1) : 1) . '>' . gettext("Options") . '</TD>';
            if ($num_opt != 0) {
                echo '            <TD></TD>
                               <TD class="header">' . gettext("code") . '</TD>
                               <TD class="header">' . gettext("length") . '</TD>
                               <TD class="header">' . gettext("data") . '</TD>';
                for ($i = 0; $i < $num_opt; $i++) {
                    $myrow3 = $result3->baseFetchRow();
                    echo '    <TR><TD class="plfield">#' . ($i + 1) . '</TD>';
                    echo '        <TD class="plfield">' . TCPOption2str($myrow3[4]) . '</TD>';
                    echo '        <TD class="plfield">' . $myrow3[5] . '</TD>';
                    echo '        <TD class="plfield">';
                    if ($myrow3[6] != "") echo $myrow3[6];
                    else echo '&nbsp;';
                    echo '</TD></TR>';
                }
            } else {
                echo '             <TD class="plfield"> &nbsp;&nbsp;&nbsp; <I>' . gettext("none") . ' </I></TD></TR>';
            }
            echo '         </TABLE>';
        }
        echo '</TD></TR>';
        echo '</TABLE>';
        $result2->baseFreeRows();
        $result3->baseFreeRows();
    }
}
/* UDP */
if ($layer4_proto == "17") {
    $sql2 = "SELECT * FROM udphdr  WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
    $result2 = $db->baseExecute($sql2);
    if ($myrow2 = $result2->baseFetchRow()) {
        echo '<br>
               <TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
                  <TR><TD CLASS="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER>UDP</TD>';
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=2>';
        echo '            <TR><TD class="header">' . gettext("source port") . '</TD>
                                <TD class="header">' . gettext("dest port") . '</TD>
                                <TD class="header">' . gettext("length") . '</TD></TR>';
        $src_port = $myrow2[2] . '<BR>';
        foreach($external_port_link as $name => $baseurl) {
            $src_port = $src_port . '[<A HREF="' . $baseurl . $myrow2[2] . '" TARGET="_ACID_PORT_">' . $name . '</A>] ';
        }
        $dst_port = $myrow2[3] . '<BR>';
        foreach($external_port_link as $name => $baseurl) {
            $dst_port = $dst_port . '[<A HREF="' . $baseurl . $myrow2[3] . '" TARGET="_ACID_PORT_">' . $name . '</A>] ';
        }
        echo '            <TR><TD class="plfield">' . $src_port . '</TD>';
        echo '                <TD class="plfield">' . $dst_port . '</TD>';
        echo '                <TD class="plfield">' . $myrow2[4] . '</TD></TR>';
        echo '         </TABLE></TD></TR>';
        echo '</TABLE>';
        $result2->baseFreeRows();
    }
}
/* ICMP */
if ($layer4_proto == "1") {
    $sql2 = "SELECT icmp_type, icmp_code, icmp_csum, icmp_id, icmp_seq FROM icmphdr " . "WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
    $result2 = $db->baseExecute($sql2);
    if ($myrow2 = $result2->baseFetchRow()) {
        echo '<br>
               <TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
                  <TR><TD CLASS="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER>ICMP';
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=2>';
        echo '            <TR><TD class="header">' . gettext("type") . '</TD>
                               <TD class="header">' . gettext("code") . '</TD>
                               <TD class="header">checksum</TD>
                               <TD class="header">' . gettext("ID") . '</TD>
                               <TD class="header">seq #</TR>';
        echo '            <TR><TD class="plfield">(' . $myrow2[0] . ') ' . ICMPType2str($myrow2[0]) . '</TD>';
        echo '                <TD class="plfield">(' . $myrow2[1] . ') ' . ICMPCode2str($myrow2[0], $myrow2[1]) . '</TD>';
        echo '                <TD class="plfield">' . $myrow2[2] . '<BR>=<BR>0x' . dechex($myrow2[2]) . '</TD>';
        echo '                <TD class="plfield">' . $myrow2[3] . '</TD>';
        echo '                <TD class="plfield">' . $myrow2[4] . '</TD></TR>';
        echo '         </TABLE>';
        echo '</TABLE>';
        $ICMPitype = $myrow2[0];
        $ICMPicode = $myrow2[1];
        $result2->baseFreeRows();
    }
}
/* Connect with KDB if plugin_id=1505 */
if ($plugin_id==1505 && $plugin_sid!="") {
    $sql2 = "SELECT k.text FROM ossim.repository k, ossim.repository_relationships r WHERE k.id=r.id_document and r.type='directive' and r.keyname='".$plugin_sid ."'";
    $result2 = $db->baseExecute($sql2);
    $kdb = "";
    if ($myrow2 = $result2->baseFetchRow()) {
        $result2->baseFreeRows();
        $kdb = $myrow2[0];
    }
    if ($kdb!="") {
        echo '<br><TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
           <TR><TD class="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER>KDB</TD><TD class="header4" valign="top" style="padding-left:5px">' . $kdb . 
           '</TD></TR></TABLE>';
    }
}

if (in_array($plugin_id, $snort_ids)) {
    echo '<br><TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
           		<TR><TD class="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER valign="top">Payload';
    echo ("<br><br>" . PrintCleanURL());
    echo ("<br>" . PrintBinDownload($db, $cid, $sid));
    echo ("<br>" . PrintPcapDownload($db, $cid, $sid));
    echo ("<br>" . showShellcodeAnalysisLink($cid, $sid, $plugin_sid_name));
} else {
    echo '<br><TABLE BORDER=0 cellpadding=2 cellspacing=0 class="bborder" WIDTH="100%">
           <TR><TD class="header3" WIDTH=50 ROWSPAN=2 ALIGN=CENTER>Log';
}
echo '       <TD>';
if ($payload) {
    if (!in_array($plugin_id, $snort_ids)) {
        echo '      <TD>';
        echo '         <TABLE BORDER=0 CELLPADDING=2>';
        echo '            <TR><TD class="header">&nbsp;filename&nbsp;</TD>
                           <TD class="header">&nbsp;username&nbsp;</TD>
                           <TD class="header">&nbsp;password&nbsp;</TD>
                           <TD class="header">&nbsp;userdata1&nbsp;</TD>
                           <TD class="header">&nbsp;userdata2&nbsp;</TD>
                           <TD class="header">&nbsp;userdata3&nbsp;</TD>
                           <TD class="header">&nbsp;userdata4&nbsp;</TD>
                           <TD class="header">&nbsp;userdata5&nbsp;</TD>
                           <TD class="header">&nbsp;userdata6&nbsp;</TD>
                           <TD class="header">&nbsp;userdata7&nbsp;</TD>
                           <TD class="header">&nbsp;userdata8&nbsp;</TD>
                           <TD class="header">&nbsp;userdata9&nbsp;</TD>';
        echo '            <TR><TD class="plfield">' . $filename . '</TD>';
        echo '                <TD class="plfield">' . $username . '</TD>';
        echo '                <TD class="plfield">' . $password . '</TD>';
        echo '                <TD class="plfield">' . $userdata1 . '</TD>';
        echo '                <TD class="plfield">' . $userdata2 . '</TD>';
        echo '                <TD class="plfield">' . $userdata3 . '</TD>';
        echo '                <TD class="plfield">' . $userdata4 . '</TD>';
        echo '                <TD class="plfield">' . $userdata5 . '</TD>';
        echo '                <TD class="plfield">' . $userdata6 . '</TD>';
        echo '                <TD class="plfield">' . $userdata7 . '</TD>';
        echo '                <TD class="plfield">' . $userdata8 . '</TD>';
        echo '                <TD class="plfield">' . $userdata9 . '</TD></TR>';
        echo '         </TABLE>';
    }
    /* print the packet based on encoding type */
    PrintPacketPayload($payload, $encoding, 1);
    if ($layer4_proto == "1") {
        if ( /* IF ICMP source quench */
        ($ICMPitype == "4" && $ICMPicode == "0") ||
        /* IF ICMP redirect */
        ($ICMPitype == "5") ||
        /* IF ICMP parameter problem */
        ($ICMPitype == "12" && $ICMPicode == "0") ||
        /* IF ( network, host, port unreachable OR
        frag needed OR network admin prohibited OR filtered) */
        ($ICMPitype == "3" || $ICMPitype == "11") && $ICMPicode == "0" || $ICMPicode == "1" || $ICMPicode == "3" || $ICMPicode == "4" || $ICMPicode == "9" || $ICMPicode == "13") {
            /* 0 == hex, 1 == base64, 2 == ascii; cf. snort-2.4.4/src/plugbase.h */
            if ($encoding == 1) {
                /* encoding is base64 */
                $work = bin2hex(base64_decode(str_replace("\n", "", $payload)));
            } else {
                /* assuming that encoding is hex */
                $work = str_replace("\n", "", $payload);
            }
            /*
            *  - depending on how the packet logged, 32-bits of NULL padding after
            *    the checksum may still be present.
            */
            if (substr($work, 0, 8) == "00000000") $offset = 8;
            /* for dest. unreachable, frag needed and DF bit set indent the padding
            * of MTU of next hop
            */
            else if (($ICMPitype == "3") && ($ICMPicode == "4")) $offset+= 8;
            if ($ICMPitype == "5") {
                $gateway = hexdec($work[0 + $offset] . $work[1 + $offset]) . "." . hexdec($work[2 + $offset] . $work[3 + $offset]) . "." . hexdec($work[4 + $offset] . $work[5 + $offset]) . "." . hexdec($work[6 + $offset] . $work[7 + $offset]);
                $offset+=8;
            }
            $icmp_src = hexdec($work[24 + $offset] . $work[25 + $offset]) . "." . hexdec($work[26 + $offset] . $work[27 + $offset]) . "." . hexdec($work[28 + $offset] . $work[29 + $offset]) . "." . hexdec($work[30 + $offset] . $work[31 + $offset]);
            $icmp_dst = hexdec($work[32 + $offset] . $work[33 + $offset]) . "." . hexdec($work[34 + $offset] . $work[35 + $offset]) . "." . hexdec($work[36 + $offset] . $work[37 + $offset]) . "." . hexdec($work[38 + $offset] . $work[39 + $offset]);
            $icmp_proto = hexdec($work[18 + $offset] . $work[19 + $offset]);
            $hdr_offset = ($work[$offset + 1]) * 8 + $offset;
            $icmp_src_port = hexdec($work[$hdr_offset] . $work[$hdr_offset + 1] . $work[$hdr_offset + 2] . $work[$hdr_offset + 3]);
            $icmp_dst_port = hexdec($work[$hdr_offset + 4] . $work[$hdr_offset + 5] . $work[$hdr_offset + 6] . $work[$hdr_offset + 7]);
            echo '<TABLE BORDER=0>';
            echo '<TR>';
            if ($ICMPitype == "5") {
                echo '<TD class="header">Gateway IP</TD>';
                echo '<TD class="header">Gateway Name</TD>';
            }
            echo '<TD class="header">Protocol</TD>';
            echo '<TD class="header">Org.Source IP</TD>';
            echo '<TD class="header">Org.Source Name</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="header">Org.Source Port</TD>';
            echo '<TD class="header">Org.Destination IP</TD>';
            echo '<TD class="header">Org.Destination Name</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="header">Org.Destination Port</TD>';
            echo '</TR>';
            echo '<TR>';
            if ($ICMPitype == "5") {
                echo '<TD class="plfield">';
                echo '<A HREF="base_stat_ipaddr.php?ip=' . $gateway . '&amp;netmask=32" TARGET="_PL_SIP">' . $gateway . '</A></TD>';
                echo '<TD class="plfield">' . baseGetHostByAddr($gateway, $db, $dns_cache_lifetime) . '</TD>';
            }
            echo '<TD class="plfield">' . IPProto2Str($icmp_proto) . '</TD>';
            echo '<TD class="plfield">';
            echo '<A HREF="base_stat_ipaddr.php?ip=' . $icmp_src . '&amp;netmask=32" TARGET="_PL_SIP">' . $icmp_src . '</A></TD>';
            echo '<TD class="plfield">' . baseGetHostByAddr($icmp_src, $db, $dns_cache_lifetime) . '</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="plfield">' . $icmp_src_port . '</TD>';
            echo '<TD class="plfield">';
            echo '<A HREF="base_stat_ipaddr.php?ip=' . $icmp_dst . '&amp;netmask=32" TARGET="_PL_DIP">' . $icmp_dst . '</A></TD>';
            echo '<TD class="plfield">' . baseGetHostByAddr($icmp_dst, $db, $dns_cache_lifetime) . '</TD>';
            if ($icmp_proto == "6" || $icmp_proto == "17") echo '<TD class="plfield">' . $icmp_dst_port . '</TD>';
            echo '</TR>';
            echo '</TABLE>';
        }  
    }
} else {
    /* Don't have payload so lets print out why by checking the detail level */
    /* if have fast detail level */
    if ($detail == "0") echo '<BR> &nbsp <I>' . gettext("Fast logging used -i so payload was discarded") . '</I><BR>';
    else echo '<BR> &nbsp <I>' . gettext("none") . ' </I><BR>';
}
?>
  <tr>
	<td>
<?php
if (in_array($plugin_id, $snort_ids)) {
	//
	// snort rule detection
    //
    echo '<TABLE BORDER=0><TR><TD class="header"> <img src="../pixmaps/snort.png" border="0" align="absmiddle"> &nbsp; '._("Snort rule Detection").'</TD></TR>';
	$result = exec("grep -n 'sid:$plugin_sid;' /etc/snort/rules/*.rules");
	// format: /etc/snort/rules/ddos.rules:53:alert tcp $EXTERNAL_NET any -> $HOME_NET 15104 (msg:"DDOS mstream client to handler"; flow:stateless; flags:S,12; reference:arachnids,111; reference:cve,2000-0138; classtype:attempted-dos; sid:249; rev:8;)
	preg_match("/(.*?):\d+:(.*?) \((.*?)\)/",$result,$found);
	if (trim($result)=="" || count($found)<=1) {
		echo "<tr><td><center>"._("No rules found for sid")." <b>$plugin_sid</b></center></td></tr>\n";
	} else {
		$file = basename($found[1]);
		echo "<TR><TD class='plfield' style='text-align:left'><b>File:</b> $file</TD></TR>\n";
		$rule = $found[2];
		echo "<TR><TD class='plfield' style='text-align:left'><b>Rule:</b> $rule</TD></TR>\n";
		$more = explode(";",$found[3]);
		foreach ($more as $dat) {
			$val = explode(":",$dat);
			if ($val[0]!="") echo "<TR><TD class='plfield' style='text-align:left;padding-left:25px'><b>".trim($val[0]).":</b> ".$val[1]."</TD></TR>\n";
		}
	}		
    echo '</TABLE>';
	//
	// pcap
	//
    include ("base_payload_pcap.php");
}
?>
	</td>
  </tr>
  <?php
echo '</TABLE></BLOCKQUOTE><P>';
if (!array_key_exists("minimal_view", $_GET)) {
	echo "<CENTER>$previous &nbsp&nbsp&nbsp $next</CENTER>";
	$qs->PrintAlertActionButtons();
}
$qs->SaveState();
ExportHTTPVar("caller", $caller);
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
if (!array_key_exists("minimal_view", $_GET)) $et->PrintTiming();
echo "</body>\r\n</html>";
?>
