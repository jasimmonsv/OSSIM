<?php
/**
* Class and Function List:
* Function list:
* - StoreAlertNum()
* - StoreAlertNum2()
* - GetTimeProfile2()
* - PrintTimeProfile()
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
function StoreAlertNum($sql, $label, $time_sep, $i_year, $i_month, $i_day, $i_hour) {
    GLOBAL $db, $cnt, $label_lst, $value_lst, $value_POST_lst, $debug_mode;
    $label_lst[$cnt] = $label;
    if (sizeof($time_sep) == 0) {
        $time_sep = array(
            0 => '',
            1 => ''
        );
    }
    if ($debug_mode > 0) echo $sql . "<BR>";
    $result = $db->baseExecute($sql);
    if ($myrow = $result->baseFetchRow()) {
        $value_lst[$cnt] = $myrow[0];
        $result->baseFreeRows();
        $value_POST_lst[$cnt] = "base_qry_main.php?new=1&amp;submit=" . _QUERYDBP . "&amp;num_result_rows=-1&amp;time_cnt=1" . "&amp;time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3D";
        if ($time_sep[0] == "hour") $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B3%5D=' . $i_day . '&amp;time%5B0%5D%5B4%5D=' . $i_year . '&amp;time%5B0%5D%5B5%5D=' . $i_hour;
        else if ($time_sep[0] == "day") $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B3%5D=' . $i_day . '&amp;time%5B0%5D%5B4%5D=' . $i_year;
        else if ($time_sep[0] == "month") $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B4%5D=' . $i_year;
        /* add no parentheses and no operator */
        $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time%5B0%5D%5B8%5D=+&amp;time%5B0%5D%5B9%5D=+';
        $cnt++;
    } else $value_lst[$cnt++] = 0;
}
function StoreAlertNum2($count, $label, $time_sep, $i_year, $i_month, $i_day, $i_hour) {
    GLOBAL $db, $cnt, $label_lst, $value_lst, $value_POST_lst, $debug_mode;
    $label_lst[$cnt] = $label;
    if (sizeof($time_sep) == 0) {
        $time_sep = array(
            0 => '',
            1 => ''
        );
    }
    $value_lst[$cnt] = $count;
    $value_POST_lst[$cnt] = "base_qry_main.php?new=1&amp;submit=" . _QUERYDBP . "&amp;num_result_rows=-1" . "&amp;time%5B0%5D%5B0%5D=+";
    if ($time_sep[0] == "hour") $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time_range=today&amp;time%5B0%5D%5B1%5D=%3E%3D&amp;time_cnt=2&amp;time%5B1%5D%5B1%5D=%3C%3D' . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B3%5D=' . $i_day . '&amp;time%5B0%5D%5B4%5D=' . $i_year . '&amp;time%5B0%5D%5B5%5D=' . $i_hour . '&amp;time%5B0%5D%5B6%5D=00&amp;time%5B0%5D%5B7%5D=00' . '&amp;time%5B1%5D%5B2%5D=' . $i_month . '&amp;time%5B1%5D%5B3%5D=' . $i_day . '&amp;time%5B1%5D%5B4%5D=' . $i_year . '&amp;time%5B1%5D%5B5%5D=' . $i_hour . '&amp;time%5B1%5D%5B6%5D=59&amp;time%5B1%5D%5B7%5D=59';
    else if ($time_sep[0] == "day") $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time_range=day&amp;time%5B0%5D%5B1%5D=%3E%3D&amp;time_cnt=2&amp;time%5B1%5D%5B1%5D=%3C%3D' . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B3%5D=' . $i_day . '&amp;time%5B0%5D%5B4%5D=' . $i_year . '&amp;time%5B0%5D%5B5%5D=00&amp;time%5B0%5D%5B6%5D=00&amp;time%5B0%5D%5B7%5D=00' . '&amp;time%5B1%5D%5B2%5D=' . $i_month . '&amp;time%5B1%5D%5B3%5D=' . $i_day . '&amp;time%5B1%5D%5B4%5D=' . $i_year . '&amp;time%5B1%5D%5B5%5D=23&amp;time%5B1%5D%5B6%5D=59&amp;time%5B1%5D%5B7%5D=59';
    else if ($time_sep[0] == "month") {
        $i_month2 = ($i_month == 12) ? 1 : $i_month + 1;
        $i_year2 = ($i_month2 == 1) ? $i_year + 1 : $i_year;
        $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time_range=month&amp;time%5B0%5D%5B1%5D=%3E%3D&amp;time_cnt=2&amp;time%5B1%5D%5B1%5D=%3C' . '&amp;time%5B0%5D%5B2%5D=' . $i_month . '&amp;time%5B0%5D%5B4%5D=' . $i_year . '&amp;time%5B1%5D%5B2%5D=' . $i_month2 . '&amp;time%5B1%5D%5B4%5D=' . $i_year2;
    }
    /* add no parentheses and no operator */
    $value_POST_lst[$cnt] = $value_POST_lst[$cnt] . '&amp;time%5B0%5D%5B8%5D=+&amp;time%5B0%5D%5B9%5D=+';
    $cnt++;
}
// DK 2008-03-19
function GetTimeProfile2($start_date, $end_date, $time_sep, $join, $where) {
    GLOBAL $db, $cnt, $label_lst, $value_lst, $value_POST_lst, $debug_mode;
    $precision = $time_sep[0];
    // group by date_format(timestamp, "%Y%m%d %H")
    switch ($precision) {
        case "hour":
            $format = "%Y%m%d %H";
            break;

        case "day":
            $format = "%Y%m%d";
            break;

        case "month":
        default:
            $format = "%Y%m";
            break;
    }
    if ($where != "") $sql = "select date_format(timestamp, \"$format\") as date, count(timestamp) as count from acid_event $join $where group by date_format(timestamp, \"$format\");";
    else $sql = "select date_format(timestamp, \"$format\") as date, count(timestamp) as count from acid_event where timestamp between \"$start_date\" and \"$end_date\" + interval 1 day group by date_format(timestamp, \"$format\");";
    if ($debug_mode > 0) echo $sql;
    $result = $db->baseExecute($sql);
    while ($myrow = $result->baseFetchRow()) {
        $date_str = $myrow["date"];
        $count = $myrow["count"];
        $i_year = substr($date_str, 0, 4);
        $i_month = "";
        $i_day = "";
        $i_hour = "";
        switch ($precision) {
            case "hour":
                $i_month = substr($date_str, 4, 2);
                $i_day = substr($date_str, 6, 2);
                $i_hour = substr($date_str, 9, 2);
                StoreAlertNum2($count, $i_month . "/" . $i_day . "/" . $i_year . " " . $i_hour . ":00:00 - " . $i_hour . ":59:59", $time_sep, $i_year, $i_month, $i_day, $i_hour);
                break;

            case "day":
                $i_month = substr($date_str, 4, 2);
                $i_day = substr($date_str, 6, 2);
                StoreAlertNum2($count, $i_month . "/" . $i_day . "/" . $i_year, $time_sep, $i_year, $i_month, $i_day, $i_hour);
                break;

            case "month":
            default:
                $i_month = substr($date_str, 4, 2);
                StoreAlertNum2($count, $i_month . "/" . $i_year, $time_sep, $i_year, $i_month, $i_day, $i_hour);
                $format = "%Y%m";
                break;
        }
    }
    $result->baseFreeRows();
}
function PrintTimeProfile($time) {
    GLOBAL $cnt, $label_lst, $value_lst, $value_POST_lst;
    $time_str = "&time_range=day";
    /* find max value */
    $max_cnt = $value_lst[0];
    for ($i = 0; $i < $cnt; $i++) if ($value_lst[$i] > $max_cnt) $max_cnt = $value_lst[$i];
    echo '<TABLE BORDER=0 WIDTH="100%">
           <TR><TD CLASS="header" width="25%">' . _CHRTTIME . '</TD>
               <TD CLASS="header" width="15%"># ' . _QSCOFALERTS . '</TD>
               <TD CLASS="header">' . _ALERT . '</TD></TR>';
    $total = 0;
    for ($i = 0; $i < $cnt; $i++) {
        if ($value_lst[$i] == 0) $entry_width = 0;
        else $entry_width = round($value_lst[$i] / $max_cnt * 100);
        $total+= $value_lst[$i];
        $cc = ($i % 2 == 0) ? "#eeeeee" : "#ffffff";
        //if ($entry_width > 0) $entry_color = "#BF8385";
        if ($entry_width > 0) $entry_color = "#84C973";
		else $entry_color = $cc;
        echo '<TR bgcolor="' . $cc . '">
                 <TD ALIGN=CENTER>';
        if ($value_lst[$i] == 0) echo $label_lst[$i];
        else {
            // Hourly
            if (preg_match("/(\d\d)\/(\d\d)\/(\d\d\d\d) (\d\d):(\d\d):(\d\d) - (\d\d):(\d\d):(\d\d)/", $label_lst[$i], $found)) {
                //$time_str = "&time[0][1]=%3E%3D&time[0][2]=".$found[2]."&time[0][3]=".$found[1]."&time[0][4]=".$found[3]."&time[0][5]=".$found[4]."&time[0][6]=".$found[5]."&time[0][7]=".$found[6]."&time[1][0]=&time[1][1]=%3E%3D&time[1][2]=".$found[2]."&time[1][3]=".$found[1]."&time[1][4]=".$found[3]."&time[1][5]=".$found[7]."&time[1][6]=".$found[8]."&time[1][7]=".$found[9];
                
            }
            // Monthly
            elseif (preg_match("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", $label_lst[$i], $found)) {
                //$time_str = "&time[0][1]=%3E%3D&time[0][2]=".$found[2]."&time[0][3]=".$found[1]."&time[0][4]=".$found[3]."&time[1][0]=&time[1][1]=%3E%3D&time[1][2]=".$found[2]."&time[1][3]=".$found[1]."&time[1][4]=".$found[3];
                
            }
            // Yearly
            elseif (preg_match("/(\d\d)\/(\d\d\d\d)/", $label_lst[$i], $found)) {
                //$time_str = "&time[0][1]=%3E%3D&time[0][2]=".$found[1]."&time[0][3]=&time[0][4]=".$found[2]."&time[1][0]=&time[1][1]=%3E%3D&time[1][2]=".$found[1]."&time[1][3]=&time[1][4]=".$found[2];
                
            }
            echo '<A HREF="' . $value_POST_lst[$i] . $time_str . '">' . $label_lst[$i] . '</A>';
        }
        echo '</TD>
                 <TD ALIGN=CENTER>' . Util::number_format_locale((int)$value_lst[$i],0) . '</TD>
                 <TD><TABLE WIDTH="100%">
                      <TR>
                       <TD BGCOLOR="' . $entry_color . '" WIDTH="' . $entry_width . '%">&nbsp;</TD>
                       <TD></TD>
                      </TR>
                     </TABLE>
                 </TD>
             </TR>';
    }
    echo '<TR><TD CLASS="total">&nbsp;</TD>
              <TD CLASS="total">' . Util::number_format_locale((int)$total,0) . '</TD>
              <TD CLASS="total">&nbsp;</TD></TR>
          </TABLE>';
}
// TIME PROFILE
include ("base_conf.php");
include ("vars_session.php");
include_once ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
require_once ("classes/Util.inc");
include_once ("$BASE_path/base_stat_common.php");
include_once ("$BASE_path/base_qry_common.php");
$time_sep = ImportHTTPVar("time_sep", VAR_ALPHA);
$time = ImportHTTPVar("time", VAR_DIGIT);
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE);
$cs = new CriteriaState("base_stat_alerts.php");
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$page_title = _BSTTITLE;
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
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
        //echo "CRITERIA: $join_sql ; $where_sql ; $criteria_sql<br>\n";
        
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
if ($event_cache_auto_update == 1) UpdateAlertCache($db);
if ($submit == "") {
    InitArray($time, $MAX_ROWS, TIME_CFCNT, "");
}
echo '<FORM ACTION="base_stat_time.php" METHOD="get" style="margin-top:3px">
        <input type="hidden" name="time_range" value="today">
		<TABLE WIDTH="100%" BORDER=0 cellpadding=0 cellspacing=0>
         <TR>
          <TD WIDTH="100%" class="header">' . _BSTTIMECRIT . '</TD>
          <TD></TD></TR>
        </TABLE>';
$today_d = date("d");
$today_m = date("m");
$today_y = date("Y");
$yesterday_d = date("d", mktime(0, 0, 0, $today_m, $today_d - 1, $today_y));
$yesterday_m = date("m", mktime(0, 0, 0, $today_m, $today_d - 1, $today_y));
$yesterday_y = date("Y", mktime(0, 0, 0, $today_m, $today_d - 1, $today_y));
$week_d = date("d", mktime(0, 0, 0, $today_m, $today_d - (date("w") - 1) , $today_y));
$week_m = date("m", mktime(0, 0, 0, $today_m, $today_d - (date("w") - 1) , $today_y));
$week_y = date("Y", mktime(0, 0, 0, $today_m, $today_d - (date("w") - 1) , $today_y));
$two_week_d = date("d", mktime(0, 0, 0, $today_m, $today_d - 7 - (date("w") - 1) , $today_y));
$two_week_m = date("m", mktime(0, 0, 0, $today_m, $today_d - 7 - (date("w") - 1) , $today_y));
$two_week_y = date("Y", mktime(0, 0, 0, $today_m, $today_d - 7 - (date("w") - 1) , $today_y));
$month_d = date("d", mktime(0, 0, 0, $today_m, 1, $today_y));
$month_m = date("m", mktime(0, 0, 0, $today_m, 1, $today_y));
$month_y = date("Y", mktime(0, 0, 0, $today_m, 1, $today_y));
$two_month_d = date("d", mktime(0, 0, 0, $today_m - 1, 1, $today_y));
$two_month_m = date("m", mktime(0, 0, 0, $today_m - 1, 1, $today_y));
$two_month_y = date("Y", mktime(0, 0, 0, $today_m - 1, 1, $today_y));
$year_d = date("d", mktime(0, 0, 0, 1, 1, $today_y));
$year_m = date("m", mktime(0, 0, 0, 1, 1, $today_y));
$year_y = date("Y", mktime(0, 0, 0, 1, 1, $today_y));
$lyear_d = date("d", mktime(0, 0, 0, $today_m, $today_d, $today_y - 1));
$lyear_m = date("m", mktime(0, 0, 0, $today_m, $today_d, $today_y - 1));
$lyear_y = date("Y", mktime(0, 0, 0, $today_m, $today_d, $today_y - 1));
$two_year_d = date("d", mktime(0, 0, 0, $today_m, $today_d, $today_y - 2));
$two_year_m = date("m", mktime(0, 0, 0, $today_m, $today_d, $today_y - 2));
$two_year_y = date("Y", mktime(0, 0, 0, $today_m, $today_d, $today_y - 2));
?>
<table cellpadding=0 cellspacing=2 border=0 width="100%" style="border:1px solid #ABB7C7">
<tr>
	<th>Hourly</th><th>Daily</th><th>Monthly</th>
</tr>
<tr>
	<td align="center">
		<table cellpadding=5>
			<tr>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "ht") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=ht&time_range=today&time_sep[0]=hour&time_sep[1]=on&time[0][0]=<?php echo $today_m ?>&time[0][1]=<?php echo $today_d ?>&time[0][2]=<?php echo $today_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Today</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "ht") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "ht") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=ht&time_range=today&time_sep[0]=hour&time_sep[1]=on&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $today_m ?>&time[0][3]=<?php echo $today_d ?>&time[0][4]=<?php echo $today_y ?>&time[1][0]=&time[1][1]=&time[1][2]=&submit=submit&time_cnt=1">Today</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "hy") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=hy&time_range=day&time_sep[0]=hour&time_sep[1]=on&time[0][0]=<?php echo $yesterday_m ?>&time[0][1]=<?php echo $yesterday_d ?>&time[0][2]=<?php echo $yesterday_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Yesterday</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "hy") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "hy") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=hy&time_range=day&time_sep[0]=hour&time_sep[1]=on&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $yesterday_m ?>&time[0][3]=<?php echo $yesterday_d ?>&time[0][4]=<?php echo $yesterday_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=1">Yesterday</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "hw") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=hw&time_range=week&time_sep[0]=hour&time_sep[1]=between&time[0][0]=<?php echo $week_m ?>&time[0][1]=<?php echo $week_d ?>&time[0][2]=<?php echo $week_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">This Week</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "hw") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "hw") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=hw&time_range=week&time_sep[0]=hour&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $week_m ?>&time[0][3]=<?php echo $week_d ?>&time[0][4]=<?php echo $week_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=1">This Week</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "hw2") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=hw2&time_range=weeks&time_sep[0]=hour&time_sep[1]=between&time%5B0%5D%5B0%5D=<?php echo $two_week_m; ?>&time%5B0%5D%5B1%5D=<?php echo $two_week_d ?>&time%5B0%5D%5B2%5D=<?php echo $two_week_y ?>&time%5B1%5D%5B0%5D=<?php echo $today_m ?>&time%5B1%5D%5B1%5D=<?php echo $today_d ?>&time%5B1%5D%5B2%5D=<?php echo $today_y ?>&submit=submit">Last two Weeks</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "hw2") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "hw2") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=hw2&time_range=weeks&time_sep[0]=hour&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $two_week_m; ?>&time[0][3]=<?php echo $two_week_d ?>&time[0][4]=<?php echo $two_week_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time[0][9]=AND&time_cnt=2">Last two Weeks</a></td>
			</tr>
		</table>
	</td>
	<td align="center">
		<table cellpadding=5>
			<tr>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "dw") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=dw&time_range=week&time_sep[0]=day&time_sep[1]=between&time[0][0]=<?php echo $week_m; ?>&time[0][1]=<?php echo $week_d ?>&time[0][2]=<?php echo $week_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">This Week</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "dw") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "dw") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=dw&time_range=week&time_sep[0]=day&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $week_m; ?>&time[0][3]=<?php echo $week_d ?>&time[0][4]=<?php echo $week_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=1">This Week</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "dw2") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=dw2&time_range=weeks&time_sep[0]=day&time_sep[1]=between&time[0][0]=<?php echo $two_week_m; ?>&time[0][1]=<?php echo $two_week_d ?>&time[0][2]=<?php echo $two_week_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Last two Weeks</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "dw2") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "dw2") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=dw2&time_range=weeks&time_sep[0]=day&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $two_week_m; ?>&time[0][3]=<?php echo $two_week_d ?>&time[0][4]=<?php echo $two_week_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time[0][9]=AND&time_cnt=2">Last two Weeks</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "dm") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=dm&time_range=month&time_sep[0]=day&time_sep[1]=between&time[0][0]=<?php echo $month_m; ?>&time[0][1]=<?php echo $month_d ?>&time[0][2]=<?php echo $month_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">This Month</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "dm") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "dm") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=dm&time_range=month&time_sep[0]=day&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $month_m; ?>&time[0][3]=<?php echo $month_d ?>&time[0][4]=<?php echo $month_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=1">This Month</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "dm2") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=dm2&time_range=months&time_sep[0]=day&time_sep[1]=between&time[0][0]=<?php echo $two_month_m; ?>&time[0][1]=<?php echo $two_month_d ?>&time[0][2]=<?php echo $two_month_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Last two Months</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "dm2") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "dm2") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=dm2&time_range=months&time_sep[0]=day&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $two_month_m; ?>&time[0][3]=<?php echo $two_month_d ?>&time[0][4]=<?php echo $two_month_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time[0][9]=AND&time_cnt=2">Last two Months</a></td>
			</tr>
		</table>
	</td>
	<td align="center" valign="top">
		<table cellpadding=5>
			<tr>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "my") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=my&time_range=year&time_sep[0]=month&time_sep[1]=between&time[0][0]=<?php echo $year_m; ?>&time[0][1]=<?php echo $year_d ?>&time[0][2]=<?php echo $year_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">This Year</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "my") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "my") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=my&time_range=year&time_sep[0]=month&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $year_m; ?>&time[0][3]=<?php echo $year_d ?>&time[0][4]=<?php echo $year_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=1">This Year</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "mly") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=mly&time_range=year&time_sep[0]=month&time_sep[1]=between&time[0][0]=<?php echo $lyear_m; ?>&time[0][1]=<?php echo $lyear_d ?>&time[0][2]=<?php echo $lyear_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Last Year</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "mly") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "mly") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=mly&time_range=year&time_sep[0]=month&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $lyear_m; ?>&time[0][3]=<?php echo $lyear_d ?>&time[0][4]=<?php echo $lyear_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time_cnt=2">Last Year</a></td>
				<!--<td bgcolor="<?php
echo ($_GET['time_option'] == "my2") ? "#28BC04" : "#EEEEEE" ?>"><A HREF="base_stat_time.php?time_option=my2&time_range=years&time_sep[0]=month&time_sep[1]=between&time[0][0]=<?php echo $two_year_m; ?>&time[0][1]=<?php echo $two_year_d ?>&time[0][2]=<?php echo $two_year_y ?>&time[1][0]=<?php echo $today_m ?>&time[1][1]=<?php echo $today_d ?>&time[1][2]=<?php echo $today_y ?>&submit=submit">Last two Years</a></td>-->
				<td bgcolor="<?php
echo ($_GET['time_option'] == "my2") ? "#28BC04" : "#EEEEEE" ?>"><A style="color: <?=($_GET['time_option'] == "my2") ? "white" : "black"?>;" HREF="base_stat_time.php?time_option=my2&time_range=years&time_sep[0]=month&time_sep[1]=between&time[0][0]=&time[0][1]=%3E%3D&time[0][2]=<?php echo $two_year_m; ?>&time[0][3]=<?php echo $two_year_d ?>&time[0][4]=<?php echo $two_year_y ?>&time[1][0]=&time[1][1]=%3C%3D&time[1][2]=<?php echo $today_m ?>&time[1][3]=<?php echo $today_d ?>&time[1][4]=<?php echo $today_y ?>&submit=submit&time[0][9]=AND&time_cnt=2">Last two Years</a></td>
			</tr>
		</table>
	</td>
</tr>
</table>
	

  <TABLE WIDTH="100%" class="query" style="border:1px solid #ABB7C7;margin-top:3px">
        <TR>
         <TD>

  <B><?php echo _BSTPROFILEBY ?> :</B> &nbsp;
        <INPUT NAME="time_sep[0]" TYPE="radio" VALUE="hour" <?php echo @chk_check($time_sep[0], "hour") ?> checked> <?php echo _HOUR ?>
        <INPUT NAME="time_sep[0]" TYPE="radio" VALUE="day" <?php echo @chk_check($time_sep[0], "day") ?>> <?php echo _DAY ?>
        <INPUT NAME="time_sep[0]" TYPE="radio" VALUE="month" <?php echo @chk_check($time_sep[0], "month") ?> onclick="document.getElementById('timesep1').value='between'"> <?php echo _MONTH ?>
        <BR>
	<input type="hidden" name="time[0][1]" value="<=">
<?php
echo '<SELECT NAME="time_sep[1]" id="timesep1">
         <OPTION VALUE=" "  ' . @chk_select($time_sep[1], " ") . '>' . _DISPTIME . '
         <OPTION VALUE="on" ' . @chk_select($time_sep[1], "on") . ' selected>' . _TIMEON . '
         <OPTION VALUE="between"' . @chk_select($time_sep[1], "between") . '>' . _TIMEBETWEEN . '
        </SELECT>';
echo '<input type="hidden" name="time[0][1]" value=">=">';
echo '<input type="hidden" name="time[1][1]" value="<=">';
// First initialize for select inputs
//print_r($_GET);
if ($_GET['time_option'] == "") $time = $_SESSION['time'];
for ($i = 0; $i < 2; $i++) {
    /*      echo '<SELECT NAME="time['.$i.'][0]">
    <OPTION VALUE=" "  '.chk_select($time[$i][0]," " ).'>'._DISPMONTH.'
    <OPTION VALUE="01" '.chk_select($time[$i][0],"01").'>'._JANUARY.'
    <OPTION VALUE="02" '.chk_select($time[$i][0],"02").'>'._FEBRUARY.'
    <OPTION VALUE="03" '.chk_select($time[$i][0],"03").'>'._MARCH.'
    <OPTION VALUE="04" '.chk_select($time[$i][0],"04").'>'._APRIL.'
    <OPTION VALUE="05" '.chk_select($time[$i][0],"05").'>'._MAY.'
    <OPTION VALUE="06" '.chk_select($time[$i][0],"06").'>'._JUNE.'
    <OPTION VALUE="07" '.chk_select($time[$i][0],"07").'>'._JULY.'
    <OPTION VALUE="08" '.chk_select($time[$i][0],"08").'>'._AUGUST.'
    <OPTION VALUE="09" '.chk_select($time[$i][0],"09").'>'._SEPTEMBER.'
    <OPTION VALUE="10" '.chk_select($time[$i][0],"10").'>'._OCTOBER.'
    <OPTION VALUE="11" '.chk_select($time[$i][0],"11").'>'._NOVEMBER.'
    <OPTION VALUE="12" '.chk_select($time[$i][0],"12").'>'._DECEMBER.'
    </SELECT>';*/
    echo '<SELECT NAME="time[' . $i . '][2]">
             <OPTION VALUE=" "  ' . chk_select($time[$i][2], " ") . '>' . _DISPMONTH . '
             <OPTION VALUE="01" ' . chk_select($time[$i][2], "01") . '>' . _JANUARY . '
             <OPTION VALUE="02" ' . chk_select($time[$i][2], "02") . '>' . _FEBRUARY . '
             <OPTION VALUE="03" ' . chk_select($time[$i][2], "03") . '>' . _MARCH . '
             <OPTION VALUE="04" ' . chk_select($time[$i][2], "04") . '>' . _APRIL . '
             <OPTION VALUE="05" ' . chk_select($time[$i][2], "05") . '>' . _MAY . '
             <OPTION VALUE="06" ' . chk_select($time[$i][2], "06") . '>' . _JUNE . '
             <OPTION VALUE="07" ' . chk_select($time[$i][2], "07") . '>' . _JULY . '
             <OPTION VALUE="08" ' . chk_select($time[$i][2], "08") . '>' . _AUGUST . '
             <OPTION VALUE="09" ' . chk_select($time[$i][2], "09") . '>' . _SEPTEMBER . '
             <OPTION VALUE="10" ' . chk_select($time[$i][2], "10") . '>' . _OCTOBER . '
             <OPTION VALUE="11" ' . chk_select($time[$i][2], "11") . '>' . _NOVEMBER . '
             <OPTION VALUE="12" ' . chk_select($time[$i][2], "12") . '>' . _DECEMBER . '
            </SELECT>';
    //echo '<INPUT TYPE="text" NAME="time['.$i.'][1]" SIZE=2 VALUE="'.$time[$i][1].'"> &nbsp;'."\n";
    echo '<INPUT TYPE="text" NAME="time[' . $i . '][3]" SIZE=2 VALUE="' . $time[$i][3] . '"> &nbsp;' . "\n";
    /*
    echo '<SELECT NAME="time['.$i.'][2]">'.
    dispYearOptions($time[$i][2])
    .'</SELECT>';
    */
    echo '<SELECT NAME="time[' . $i . '][4]">' . dispYearOptions($time[$i][4]) . '</SELECT>';
    if ($i == 0) echo '&nbsp; -- &nbsp;&nbsp;';
}
echo '<INPUT TYPE="submit" class="button" NAME="submit" VALUE="' . _PROFILEALERT . '">
        </TD></TR></TABLE>
        </FORM>

        <P><HR>';
// Conversion criteria -> profile (Granada 22/05/09)
$day_convert = $time[0][3];
$month_convert = $time[0][2];
$year_convert = $time[0][4];
$time[0][0] = $month_convert;
$time[0][1] = $day_convert;
$time[0][2] = $year_convert;
$day_convert = $time[1][3];
$month_convert = $time[1][2];
$year_convert = $time[1][4];
$time[1][0] = $month_convert;
$time[1][1] = $day_convert;
$time[1][2] = $year_convert;
if ($submit != "" && @$time_sep[0] == "") echo _BSTERRPROFILECRIT;
else if ($submit != "" && $time_sep[1] == " ") echo _BSTERRTIMETYPE;
else if ($submit != "" && $time_sep[0] != "" && $time_sep[1] == "on" && $time[0][2] == " ") echo _BSTERRNOYEAR;
else if ($submit != "" && $time_sep[0] != "" && $time_sep[1] == "between" && ($time[1][2] == " " || $time[0][2] == " ")) echo _BSTERRNOYEAR;
else if ($submit != "" && $time_sep[0] != "" && $time_sep[1] == "between" && ($time[1][0] == " " || $time[0][0] == " ")) echo _BSTERRNOMONTH;
else if ($submit != "" && ($time_sep[0] != "") && $time_sep[1] == "between" && ($time[1][1] == "" || $time[0][1] == "")) echo _BSTERRNODAY;
else if ($submit != "") {
    /* Dump the results of the above specified query */
    $year_start = $year_end = NULL;
    $month_start = $month_end = NULL;
    $day_start = $day_end = NULL;
    $hour_start = $hour_end = NULL;
    if ($time_sep[1] == "between") {
        if ($time_sep[0] == "hour") {
            $year_start = $time[0][2];
            $year_end = $time[1][2];
            $month_start = $time[0][0];
            $month_end = $time[1][0];
            $day_start = $time[0][1];
            $day_end = $time[1][1];
            $hour_start = 0;
            $hour_end = 23;
        } else if ($time_sep[0] == "day") {
            $year_start = $time[0][2];
            $year_end = $time[1][2];
            $month_start = $time[0][0];
            $month_end = $time[1][0];
            $day_start = $time[0][1];
            $day_end = $time[1][1];
            $hour_start = - 1;
        } else if ($time_sep[0] == "month") {
            $year_start = $time[0][2];
            $year_end = $time[1][2];
            $month_start = $time[0][0];
            $month_end = $time[1][0];
            $day_start = $time[0][1];
            $day_end = $time[1][1];
            $hour_start = - 1;
        }
    } else if ($time_sep[1] == "on") {
        if ($time_sep[0] == "hour") {
            $year_start = $time[0][2];
            $year_end = $time[0][2];
            if ($time[0][0] != " ") {
                $month_start = $time[0][0];
                $month_end = $time[0][0];
            } else {
                $month_start = 1;
                $month_end = 12;
            }
            if ($time[0][1] != "") {
                $day_start = $time[0][1];
                $day_end = $time[0][1];
            } else {
                $day_start = 1;
                $day_end = 31;
            }
            $hour_start = 0;
            $hour_end = 23;
        } else if ($time_sep[0] == "day") {
            $year_start = $time[0][2];
            $year_end = $time[0][2];
            if ($time[0][0] != " ") {
                $month_start = $time[0][0];
                $month_end = $time[0][0];
            } else {
                $month_start = 1;
                $month_end = 12;
            }
            if ($time[0][1] != "") {
                $day_start = $time[0][1];
                $day_end = $time[0][1];
            } else {
                $day_start = 1;
                $day_end = 31;
            }
            $hour_start = - 1;
        } else if ($time_sep[0] == "month") {
            $year_start = $time[0][2];
            $year_end = $time[0][2];
            if ($time[0][0] != " ") {
                $month_start = $time[0][0];
                $month_end = $time[0][0];
            } else {
                $month_start = 1;
                $month_end = 12;
            }
            $day_start = - 1;
            $hour_start = - 1;
        }
    }
    if ($debug_mode == 1) {
        echo '<TABLE BORDER=0>
            <TR>
              <TD>year_start<TD>year_end<TD>month_start<TD>month_end
              <TD>day_start<TD>day_end<TD>hour_start<TD>hour_end
            <TR>
              <TD>' . $year_start . '<TD>' . $year_end . '<TD>' . $month_start . '<TD>' . $month_end . '<TD>' . $day_start . '<TD>' . $day_end . '<TD>' . $hour_start . '<TD>' . $hour_end . '</TABLE>';
    }
    $tmp_start = $year_start . "-" . $month_start . "-" . $day_start . " 00:00:00";
    $tmp_end = $year_end . "-" . $month_end . "-" . $day_end . " 00:00:00";
    $cnt = 0;
    //echo "START: $tmp_start, END: $tmp_end, SEP: ".print_r($time_sep);
    GetTimeProfile2($tmp_start, $tmp_end, $time_sep, $join_sql, "$where_sql $criteria_sql");
    /*
    for ( $i_year = $year_start; $i_year <= $year_end; $i_year++ )
    {
    // !!! AVN !!!
    // to_date() must used!
    $sql = "SELECT count(*) ".$from.$where." AND ".
    $db->baseSQL_YEAR("timestamp", "=", $i_year);
    
    if ( $month_start != -1 )
    {
    if ($i_year == $year_start)  $month_start2 = $month_start;  else  $month_start2 = 1;
    if ($i_year == $year_end)    $month_end2 = $month_end;      else  $month_end2 = 12;
    
    for ( $i_month = $month_start2; $i_month <= $month_end2; $i_month++ )
    {
    $sql = "SELECT count(*) ".$from.$where." AND ".
    $db->baseSQL_YEAR("timestamp", "=", $i_year)." AND ".
    $db->baseSQL_MONTH("timestamp", "=", $i_month);
    
    if ( $day_start != -1 )
    {
    if ($i_month == $month_start)  $day_start2 = $day_start;  else  $day_start2 = 1;
    if ($i_month == $month_end)    $day_end2 = $day_end;      else  $day_end2 = 31;
    
    for ( $i_day = $day_start2; $i_day <= $day_end2; $i_day++ )
    {
    if ( checkdate($i_month, $i_day, $i_year) )
    {
    $sql = "SELECT count(*) ".$from.$where." AND ".
    $db->baseSQL_YEAR("timestamp", "=", $i_year)." AND ".
    $db->baseSQL_MONTH("timestamp", "=",$i_month)." AND ".
    $db->baseSQL_DAY("timestamp", "=", $i_day);
    
    $i_hour = "";
    if ( $hour_start != -1 )
    {
    for ( $i_hour = $hour_start; $i_hour <= $hour_end; $i_hour++ )
    {
    $sql = "SELECT count(*) ".$from.$where." AND ".
    $db->baseSQL_YEAR("timestamp", "=", $i_year)." AND ".
    $db->baseSQL_MONTH("timestamp", "=", $i_month)." AND ".
    $db->baseSQL_DAY("timestamp", "=", $i_day)." AND ".
    $db->baseSQL_HOUR("timestamp", "=", $i_hour);
    
    StoreAlertNum($sql, $i_month."/".$i_day."/".$i_year." ".
    $i_hour.":00:00 - ".$i_hour.":59:59",
    $time_sep, $i_year, $i_month, $i_day, $i_hour);
    }  // end hour
    }
    else
    StoreAlertNum($sql, $i_month."/".$i_day."/".$i_year,
    $time_sep, $i_year, $i_month, $i_day, $i_hour);
    }
    }   // end day
    }
    else
    StoreAlertNum($sql, $i_month."/".$i_year, $time_sep, $i_year, $i_month, $i_day, $i_hour);
    }   // end month
    }
    else
    StoreAlertNum($sql, $i_year, $time_sep, $i_year, $i_month, $i_day, $i_hour);
    }   // end year
    */
    echo '</TABLE>';
    PrintTimeProfile($time);
}
PrintBASESubFooter();
echo "</body>\r\n</html>";
?>
