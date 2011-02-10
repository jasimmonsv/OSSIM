<?php
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
  include("vars_session.php");
  include ("$BASE_path/includes/base_constants.inc.php");
  include ("$BASE_path/includes/base_include.inc.php");
  include_once ("$BASE_path/base_db_common.php");
  include_once ("$BASE_path/base_common.php");
  include_once ("$BASE_path/base_stat_common.php");
  include_once ("$BASE_path/base_qry_common.php");
  include_once ("$BASE_path/base_ag_common.php");

  $et = new EventTiming($debug_time_mode);
  $cs = new CriteriaState("base_stat_sensor.php");
  $cs->ReadState();

  $qs = new QueryState();

   // Check role out and redirect if needed -- Kevin
  $roleneeded = 10000;
  $BUser = new BaseUser();
  if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1))
    base_header("Location: ". $BASE_urlpath . "/index.php");

  $submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(gettext("Delete Selected"), gettext("Delete ALL on Screen"), gettext("Delete Entire Query")));
  $qs->MoveView($submit);             /* increment the view if necessary */

  $page_title = gettext("Sensor Listing");
  PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink(), 1);
  
  /* Connect to the Alert database */
  $db = NewBASEDBConnection($DBlib_path, $DBtype);
  $db->baseDBConnect($db_connect_method,
                     $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

  if ( $event_cache_auto_update == 1 )  UpdateAlertCache($db);

  $criteria_clauses = ProcessCriteria();  

  if ( !$printing_ag )
  {
     /* ***** Generate and print the criteria in human readable form */
     echo '<TABLE WIDTH="100%">
           <TR>
             <TD WIDTH="60%" VALIGN=TOP>';

	if(!array_key_exists("minimal_view",$_GET)) {
		PrintCriteria($caller);
	}

     echo '</TD></tr><tr>
           <TD VALIGN=TOP>';
      
	if(!array_key_exists("minimal_view",$_GET))
	{
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
  
  $from = " FROM acid_event FORCE INDEX (unique_sensors) ".$criteria_clauses[0];
  $where = " WHERE ".$criteria_clauses[1];

  // use accumulate tables only with timestamp criteria
  $use_ac = false;
  
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

  $qs->SetActionSQL($from.$where);
  $et->Mark("Initialization");

  $qs->RunAction($submit, PAGE_STAT_SENSOR, $db);
  $et->Mark("Alert Action");

  /* create SQL to get Unique Alerts */
  $cnt_sql = "SELECT count(DISTINCT acid_event.sid) ".$from.$where;

  /* Run the query to determine the number of rows (No LIMIT)*/
  if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);
  $et->Mark("Counting Result size");

  /* Setup the Query Results Table */
  $qro = new QueryResultsOutput("base_stat_servers.php?x=x");

  $qro->AddTitle(" ");
  $qro->AddTitle("Hostname", 
                "", " ",
                         "",
                "", " ",
                         "");  
  $qro->AddTitle("Ip Addr", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Port", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Description", 
                "", " ",
                         " ",
                "", " ",
                         " ");

  $qro->AddTitle("Correlate", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Cross Correlate", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Store", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Qualify", 
                "", " ",
                         " ",
                "", " ",
                         " ");
  $qro->AddTitle("Resend Alarms", 
                "", " ",
                         " ",
                "", " ",
                         " ");

  $sort_sql = $qro->GetSortSQL($qs->GetCurrentSort(), "");

  $sql = "SELECT DISTINCT acid_event.sid, count(acid_event.cid) as event_cnt,".
         " count(distinct(acid_event.signature)) as sig_cnt, ".
         " count(distinct(acid_event.ip_src)) as saddr_cnt, ".
         " count(distinct(acid_event.ip_dst)) as daddr_cnt, ".
         "min(timestamp) as first_timestamp, max(timestamp) as last_timestamp".
         $sort_sql[0].$from.$where." GROUP BY acid_event.sid ".$sort_sql[1];
	
  //echo $sql."<br>";	 
  
  // use accumulate tables only with timestamp criteria
  if ($use_ac) {
	$where = $more = $sqla = $sqlb = $sqlc = "";
	if (preg_match("/timestamp/",$criteria_clauses[1])) {
		$where = "WHERE ".str_replace("timestamp","day",$criteria_clauses[1]);
		$sqla = " and ac_sensor_sid.day=ac_sensor_signature.day";
		$sqlb = " and ac_sensor_sid.day=ac_sensor_ipsrc.day";
		$sqlc = " and ac_sensor_sid.day=ac_sensor_ipdst.day";
	}
	$orderby = str_replace("acid_event.","",$sort_sql[1]);
	$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT ac_sensor_sid.sid,  sum(ac_sensor_sid.cid) as event_cnt,
     (select count(distinct(signature)) from ac_sensor_signature where ac_sensor_signature.sid=ac_sensor_sid.sid $sqla) as sig_cnt,
     (select count(distinct(ip_src)) from ac_sensor_ipsrc where ac_sensor_sid.sid=ac_sensor_ipsrc.sid $sqlb) as saddr_cnt,
     (select count(distinct(ip_dst)) from ac_sensor_ipdst where ac_sensor_sid.sid=ac_sensor_ipdst.sid $sqlc) as daddr_cnt,
      min(ac_sensor_sid.first_timestamp) as first_timestamp,  max(ac_sensor_sid.last_timestamp) as last_timestamp
      FROM ac_sensor_sid FORCE INDEX(primary) $where GROUP BY ac_sensor_sid.sid $orderby";
  }
  //echo $sql;
  
  $sql = "select * from server";
  
  /* Run the Query again for the actual data (with the LIMIT) */
  $result = $qs->ExecuteOutputQuery($sql, $db);
  if ($use_ac) $qs->GetCalcFoundRows($cnt_sql, $db);
  
  $et->Mark("Retrieve Query Data");

  if ( $debug_mode == 1 )
  {
     $qs->PrintCannedQueryList();
     $qs->DumpState();
     echo "$sql<BR>";
  }

  /* Print the current view number and # of rows */
  //$qs->PrintResultCnt();
  echo "<br>";
  echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_servers.php">';
  $qro->PrintHeader();

  $i = 0;
  while ( ($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt()) )
  {

    /* Print out */ 
    qroPrintEntryHeader($i);    

    $tmp_rowid = $sensor_id;
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst['.$i.']" VALUE="'.$tmp_rowid.'">';
    echo '        <INPUT TYPE="hidden" NAME="action_lst['.$i.']" VALUE="'.$tmp_rowid.'"></TD>';

    qroPrintEntry($myrow[0]);
    qroPrintEntry($myrow[1]);
    qroPrintEntry($myrow[2]);
    qroPrintEntry($myrow[3]);
    qroPrintEntry("Yes");
    qroPrintEntry("Yes");
    qroPrintEntry("Yes");
    qroPrintEntry("Yes");
    qroPrintEntry("Yes");
    qroPrintEntryFooter();

    $i++;
  }

  $result->baseFreeRows();

  $qro->PrintFooter();

  $qs->PrintBrowseButtons();
  $qs->PrintAlertActionButtons();
  $qs->SaveState();
  echo "\n</FORM>\n";
  
  PrintBASESubFooter();

  $et->Mark("Get Query Elements");
  $et->PrintTiming();
  echo "</body>\r\n</html>";
?>
