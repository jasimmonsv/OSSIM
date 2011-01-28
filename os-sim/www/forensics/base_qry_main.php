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
/*
* $caller: an auxiliary variable used to determine the how the search parameters were entered (i.e.
*          whether through a form or through another mechanism
*  - "stat_alerts" : display results based on the the Alert Listings
*  - "top_tcp" :
*  - "top_udp" :
*  - "top_icmp" :
*  - "last_tcp" :
*  - "last_udp" :
*  - "last_icmp" :
*
* $submit: used to determine the next action which should be taken when the form is submitted.
*  - _QUERYDB         : triggers a query into the database
*  - _ADDTIME         : adds another date/time row
*  - _ADDADDR         : adds another IP address row
*  - _ADDIPFIELD      : adds another IP field row
*  - _ADDTCPPORT      : adds another TCP port row
*  - _ADDTCPFIELD     : adds another TCP field row
*  - _ADDUDPPORT      : adds another UDP port row
*  - _ADDUDPFIELD     : adds another UDP field row
*  - _ADDICMPFIELD    : adds another ICMP field row
*  - "#X-(X-X)"       : sid-cid keys for a packet lookup
*  - _SELECTED
*  - _ALLONSCREEN
*  - _ENTIREQUERY
*
* $layer4: stores the layer 4 protocol used in query
*
* $save_sql: the current sql string generating the query
*
* $save_criteria: HTML-human readable criteria of the $save_sql string
*
* $num_result_rows: rows in the entire record set retried under the current
*                   query
*
* $current_view: current view of the result set
*
* $sort_order: how to sort the output
*
* ----- Search Result Variables ----
* $action_chk_lst[]: array of check boxes to determine if an alert
*                    was selected for action
* $action_lst[]: array of (sid,cid) of all alerts on screen
*/
include ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/includes/base_action.inc.php");



include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_ag_common.php");
include_once ("$BASE_path/base_qry_common.php");

$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . _QUERYDBP);
//echo "<br><br><br>";

// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$new = ImportHTTPVar("new", VAR_DIGIT);
//require_once ("ossim_error.inc");
/*
printr($_GET);
print "<HR>";

*/
//print_r($_SESSION);
/* This call can include many values. */
$submit = ImportHTTPVar("submit", VAR_DIGIT | VAR_PUNC | VAR_LETTER, array(
    _SELECTED,
    _ALLONSCREEN,
    _ENTIREQUERY,
    _QUERYDB,
    _ADDTIME,
    _ADDADDRESS,
    _ADDIPFIELD,
    _ADDTCPPORT,
    _ADDTCPFIELD,
    _ADDUDPPORT,
    _ADDUDPFIELD,
    _ADDICMPFIELD
));

/* Search Box. DK */
/* For your own mental health, skip over until 20 or 30 lines below :P */
//require_once("/usr/share/ossim/include/ossim_error.inc");

if ($submit == gettext("Signature")) {

    $search_str = ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    //print_r ($_GET);
    $_GET['sig'][0] = "LIKE";
    $_GET['sig'][1] = $search_str;
    $_GET['sig_type'] = 0;
    $_GET['submit'] = $submit = _QUERYDB;

} elseif ($submit == "Payload") {
    $search_str = ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_LETTER);
    $_GET["search"] = 1;
    $_GET["data_cnt"] = 1;
    $_GET["data"][0] = array("","LIKE",$search_str,"","");
    $_GET['submit'] = $submit = _QUERYDB;  
//	echo "<br><br>";
//	echo "dentro de payload<br>";

} elseif ($submit == "IP") {
    $ip_search = ImportHTTPVar("search_str", VAR_DIGIT | VAR_PUNC | VAR_FSLASH | VAR_LETTER);
    $ip_search = preg_replace("/\s*AND.*/","",$ip_search);
    $ipsf = preg_split("/\s*OR\s*/",$ip_search);
    $ipfilter = array();
    for ($i=0;$i<count($ipsf);$i++) {
        $ip_search = $ipsf[$i];
        $mask = explode ("/",$ip_search);
        $ip_aux = explode (".",$mask[0]);
        $or = (($i+1)==count($ipsf)) ? "" : "OR";
        $ipfilter[] = array ("","ip_both","=",$ip_aux[0],$ip_aux[1],$ip_aux[2],$ip_aux[3],$ip_search,"",$or,$mask[1]);
    }
    $_SESSION["ip_addr"] = $ipfilter;
    $_SESSION["ip_addr_cnt"] = count($ipfilter);
    $_SESSION["ip_field"] = array (
        array ("","","=")
    );
    $_SESSION["ip_field_cnt"] = 1;
}

//print_r($_POST);
//print_r($_SESSION["ip_addr"]);
//echo get_include_path();
//set_include_path(".:/usr/share/php:/usr/share/ossim/include/");
//echo phpinfo();
//print_r($_SESSION["ip_addr"]);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

if ($_GET['sensor'] != "" || $_GET["ossim_risk_a"] != "") {
	unset($_GET['search']);
	$_GET['submit'] = $submit = _QUERYDB;
}
/* Code to correct 'interesting' (read: unexplained) browser behavior */
/* Something with Netscape 4.75 such that the $submit variable is no recognized
* under certain circumstances.  This one is a result of using HTTPS and
* clicking on TCP traffic profile from base_main.php
*/
if ($cs->criteria['layer4']->Get() != "" && $submit == "") $submit = _QUERYDB;

// Set the sort order to the new sort order if one has been selected
$sort_order = ImportHTTPVar("sort_order", VAR_LETTER | VAR_USCORE);
if ($sort_order == "" || !isset($sort_order)) {
    // If one wasn't picked, try the prev_sort_order
    $sort_order = ImportHTTPVar("prev_sort_order", VAR_LETTER | VAR_USCORE);
    // If there was no previous sort order, default it to none.
    if (($sort_order == "" || !isset($sort_order)) && $submit == _QUERYDB) {
        //$sort_order = "none"; //default to none.
        $_GET["sort_order"] = $sort_order = "time_d"; //if ($_GET['sensor'] != "") $sort_order = "time_d";
    }
}
/* End 'interesting' browser code fixes */
// ADD TIME Criteria
/*
if ($submit == _ADDTIME) {
if ($_SESSION['time_cnt'] == 1) {
$taux = array("","<=",$_SESSION['time'][0][2],$_SESSION['time'][0][3],$_SESSION['time'][0][4],"","","","");
$_SESSION['time'][1] = $taux;
$_SESSION['time_cnt'] = 2;
}
if ($_SESSION['time_cnt'] == 0) {
$taux = array("","<=",$_SESSION['time'][0][2],$_SESSION['time'][0][3],$_SESSION['time'][0][4],"","","","");
$_SESSION['time'][0] = $taux;
$_SESSION['time_cnt'] = 1;
}
echo "COUNT A ".$_SESSION['time_cnt'];
// Force show criteria form
//$submit = "";
$new = 1;
print_r ($_SESSION['time']);
}
*/

/* Totally new Search */
if (($new == 1) && ($submit == "")) {
    $cs->InitState();
}
/* is this a new query, invoked from the SEARCH screen ? */
/* if the query string if very long (> 700) then this must be from the Search screen  */
$back = ImportHTTPVar("back", VAR_DIGIT);
if (($GLOBALS['maintain_history'] == 1) && ($back != 1) && ($submit == _QUERYDB) && (isset($_GET['search']) && $_GET['search'] == 1)) {
    !empty($_SESSION['back_list_cnt']) ? $_SESSION['back_list_cnt']-- : $_SESSION['back_list_cnt'] = 0; /* save on top of initial blank query screen   */
    $submit = ""; /*  save entered search criteria as if one hit Enter */
    $_POST['submit'] = $submit;
    $cs->ReadState(); /* save the search criteria       */
    // Solve error when payload is searched cnt = 1
//    if ($_GET{"data"} {
//        0
//    } {
//        2
//    } != "") $cs->criteria['data']->criteria_cnt = 1;

   if ($_GET["data"][0][2] != "") $cs->criteria['data']->criteria_cnt = 1;
    $submit = _QUERYDB; /* restore the real submit value  */
    $_POST['submit'] = $submit;
}
$cs->ReadState();

$qs = new QueryState();
$qs->AddCannedQuery("last_tcp", $last_num_alerts, _LASTTCP, "time_d");
$qs->AddCannedQuery("last_udp", $last_num_alerts, _LASTUDP, "time_d");
$qs->AddCannedQuery("last_icmp", $last_num_alerts, _LASTICMP, "time_d");
$qs->AddCannedQuery("last_any", $last_num_alerts, _LASTALERTS, "time_d");

$page_title = _QUERYRESULTS;
if ($qs->isCannedQuery()) if (!array_key_exists("minimal_view", $_GET)) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , "", 1);
else if (!array_key_exists("minimal_view", $_GET)) PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, "", 1);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$printing_ag = false;
?>
<FORM METHOD="POST" name="PacketForm" id="PacketForm" ACTION="base_qry_main.php" style="margin:0 auto">
<input type='hidden' name="search" value="1" />
<input type="hidden" name="sort_order" value="<?php echo ($_GET['sort_order'] != "") ? $_GET['sort_order'] : $_POST['sort_order'] ?>">
<?php
/* Dump some debugging information on the shared state */
if ($debug_mode > 0) {
    PrintCriteriaState();
}
/* a browsing button was clicked -> increment view */
if (is_numeric($submit)) {
    if ($debug_mode > 0) ErrorMessage("Browsing Clicked ($submit)");
    $qs->MoveView($submit);
    $submit = _QUERYDB;
}
//echo $submit." ".$qs->isCannedQuery()." ".$qs->GetCurrentSort()." ".$_SERVER["QUERY_STRING"];
/* Run the SQL Query and get results */
if ($submit == _QUERYDB || $submit == _QUERYDBP || $submit == _SELECTED || $submit == _ALLONSCREEN || $submit == _ENTIREQUERY || $qs->isCannedQuery() || ($qs->GetCurrentSort() != "" && $qs->GetCurrentSort() != "none" && $_SERVER["QUERY_STRING"]!="new=1")) {

	/* Init and run the action */
	$criteria_clauses = ProcessCriteria();
	//print_r($criteria_clauses);
    $from = "FROM acid_event " . $criteria_clauses[0];
    $where = "";
    if ($criteria_clauses[1] != "") $where = "WHERE " . $criteria_clauses[1];
    $where = str_replace("::%", ":%:%", $where);
    if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
        if ($matches[2] != $matches[3]) {
            //print "A";
            $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
        } else {
            //print "B";
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
    $qs->AddValidActionOp(_ENTIREQUERY);
    $qs->SetActionSQL("SELECT acid_event.sid, acid_event.cid $from $where");
    $et->Mark("Initialization");

    $qs->RunAction($submit, PAGE_QRY_ALERTS, $db);
    $et->Mark("Alert Action");

    if ($debug_mode > 0) ErrorMessage("Initial/Canned Query or Sort Clicked");

    include ("$BASE_path/base_qry_sqlcalls.php");
}
/* Return the input form to get more criteria from user */
else {
	include ("$BASE_path/base_qry_form.php");
}

$qs->SaveState();


echo "\n</FORM>\n";
if (!array_key_exists("minimal_view", $_GET)) {
    PrintBASESubFooter();
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
echo "</body>\r\n</html>";
?>
