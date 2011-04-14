<?php
/**
* Class and Function List:
* Function list:
* - DBLink()
* Classes list:
*/
/*
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 Kevin Johnson
** Copyright (C) 2000 Carnegie Mellon University
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
/*
* OSSIM Forensics Console
* based upon Forensics Console by Kevin  Johnson
* based upon Analysis Console for Incident Databases (ACID) by Roman  Danyliw
*
* See http://www.ossim.net and http://www.alienvault.com fore more
* information and documentation about OSSIM.
*
*/
$start = time();
require ("base_conf.php");
include_once ("$BASE_path/includes/base_auth.inc.php");
include_once ("$BASE_path/includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_output_html.inc.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/includes/base_cache.inc.php");
include_once ("$BASE_path/includes/base_state_criteria.inc.php");
include_once ("$BASE_path/includes/base_log_error.inc.php");
include_once ("$BASE_path/includes/base_log_timing.inc.php");
RegisterGlobalState();
/* Initialize the history */
/*OSSIM*/
/* Save OSSIM login data before intializing the history*/
/*if (isset($_SESSION["_user"])) {
    $user_ossim_tmp = $_SESSION["_user"];
}
if (isset($_SESSION["acid_sig_names"])) {
    $tmp_signatures = $_SESSION["acid_sig_names"];
}
if (isset($_SESSION["acid_sig_refs"])) {
    $tmp_sig_refs = $_SESSION["acid_sig_refs"];
}
$_SESSION = NULL;
if (isset($user_ossim_tmp)) {
    $_SESSION["_user"] = $user_ossim_tmp;
}
if (isset($tmp_signatures)) {
    $_SESSION["acid_sig_names"] = $tmp_signatures;
}
if (isset($tmp_sig_refs)) {
    $_SESSION["acid_sig_refs"] = $tmp_sig_refs;
}*/
InitArray($_SESSION['back_list'], 1, 3, "");
$_SESSION['back_list_cnt'] = 0;
PushHistory();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$BUser = new BaseUser();
//if (($Use_Auth_System == 1) && ($BUser->hasRole($roleneeded) == 0))
if ($Use_Auth_System == 1) {
    if ($BUser->hasRole($roleneeded) == 0) base_header("Location: $BASE_urlpath/index.php");
}
// Set cookie to use the correct db.
if (isset($_GET['archive'])) {
    "no" == $_GET['archive'] ? $value = 0 : $value = 1;
    setcookie('archive', $value);
    base_header("Location: $BASE_urlpath/base_main.php");
}
function DBLink() {
    // generate the link to select the other database....
    GLOBAL $archive_exists;
    if ((isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) || (isset($_GET['archive']) && $_GET['archive'] == 1)) {
        echo '<a href="base_main.php?archive=no">' . gettext("Use Event Database") . '</a>';
    } elseif ($archive_exists != 0) {
        echo ('<a href="base_main.php?archive=1">' . gettext("Use Archive Database") . '</a>');
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<!-- <?php
echo gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION; ?> -->
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php
echo gettext("iso-8859-1"); ?>">
  <meta http-equiv="pragma" content="no-cache">
<?php
PrintFreshPage($refresh_stat_page, $stat_page_refresh_time);
$archiveDisplay = (isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) ? "-- ARCHIVE" : "";
echo ('<title>' . gettext("Forensics Console " . $BASE_installID) . $BASE_VERSION . $archiveDisplay . '</title>
<link rel="stylesheet" type="text/css" href="styles/' . $base_style . '">');
?>
</head>
<body>
  <div class="header">&nbsp;<?php //class ="mainheadertitle"
echo gettext("Forensics Console " . $BASE_installID) . $archiveDisplay; ?></div>
<?php
if ($debug_mode == 1) {
    PrintPageHeader();
}
/* Check that PHP was built correctly */
$tmp_str = verify_php_build($DBtype);
if ($tmp_str != "") {
    echo $tmp_str;
    die();
}
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
/* Check that the DB schema is recent */
$tmp_str = verify_db($db, $alert_dbname, $alert_host);
if ($tmp_str != "") {
    echo $tmp_str;
    die();
}
?>
<table width="100%" style="border:0;padding:0">
  <tr>
    <td align="left" rowspan="2">
<?php
// Various things for the snapshot functiuonality on the first page.... Kevin
$tmp_month = date("m");
$tmp_day = date("d");
$tmp_year = date("Y");
$today = '&amp;time%5B0%5D%5B0%5D=+&amp;time%5B0%5D%5B1%5D=%3E%3D' . '&amp;time%5B0%5D%5B2%5D=' . $tmp_month . '&amp;time%5B0%5D%5B3%5D=' . $tmp_day . '&amp;time%5B0%5D%5B4%5D=' . $tmp_year . '&amp;time%5B0%5D%5B5%5D=&amp;time%5B0%5D%5B6%5D=&amp;time%5B0%5D%5B7%5D=' . '&amp;time%5B0%5D%5B8%5D=+&amp;time%5B0%5D%5B9%5D=+';
$yesterday_year = date("Y", time() - 86400);
$yesterday_month = date("m", time() - 86400);
$yesterday_day = date("d", time() - 86400);
$yesterday_hour = date("H", time() - 86400);
$yesterday = '&amp;time%5B0%5D%5B0%5D=+&amp;time%5B0%5D%5B1%5D=%3E%3D' . '&amp;time%5B0%5D%5B2%5D=' . $yesterday_month . '&amp;time%5B0%5D%5B3%5D=' . $yesterday_day . '&amp;time%5B0%5D%5B4%5D=' . $yesterday_year . '&amp;time%5B0%5D%5B5%5D=' . $yesterday_hour . '&amp;time%5B0%5D%5B6%5D=&amp;time%5B0%5D%5B7%5D=' . '&amp;time%5B0%5D%5B8%5D=+&amp;time%5B0%5D%5B9%5D=+';
$last72_year = date("Y", time() - 86400 * 3);
$last72_month = date("m", time() - 86400 * 3);
$last72_day = date("d", time() - 86400 * 3);
$last72_hour = date("H", time() - 86400 * 3);
$last72 = '&amp;time%5B0%5D%5B0%5D=+&amp;time%5B0%5D%5B1%5D=%3E%3D' . '&amp;time%5B0%5D%5B2%5D=' . $last72_month . '&amp;time%5B0%5D%5B3%5D=' . $last72_day . '&amp;time%5B0%5D%5B4%5D=' . $last72_year . '&amp;time%5B0%5D%5B5%5D=' . $last72_hour . '&amp;time%5B0%5D%5B6%5D=&amp;time%5B0%5D%5B7%5D=' . '&amp;time%5B0%5D%5B8%5D=+&amp;time%5B0%5D%5B9%5D=+';
$tmp_24hour = 'base_qry_main.php?new=1' . $yesterday . '&amp;submit=' . gettext("Query+DB") . '&amp;num_result_rows=-1&amp;time_cnt=1';
$tmp_24hour_unique = 'base_stat_alerts.php?time_cnt=1' . $yesterday;
$tmp_24hour_sip = 'base_stat_uaddr.php?addr_type=1&amp;sort_order=occur_d&amp;time_cnt=1' . $yesterday;
$tmp_24hour_dip = 'base_stat_uaddr.php?addr_type=2&amp;sort_order=occur_d&amp;time_cnt=1' . $yesterday;
$tmp_72hour = 'base_qry_main.php?new=1' . $last72 . '&amp;submit=' . gettext("Query+DB") . '&amp;num_result_rows=-1&amp;time_cnt=1';
$tmp_72hour_unique = 'base_stat_alerts.php?time_cnt=1' . $last72;
$tmp_72hour_sip = 'base_stat_uaddr.php?addr_type=1&amp;sort_order=occur_d&amp;time_cnt=1' . $last72;
$tmp_72hour_dip = 'base_stat_uaddr.php?addr_type=2&amp;sort_order=occur_d&amp;time_cnt=1' . $last72;
$tmp_today = 'base_qry_main.php?new=1' . $today . '&amp;submit=' . gettext("Query+DB") . '&amp;num_result_rows=-1&amp;time_cnt=1';
$tmp_today_unique = 'base_stat_alerts.php?time_cnt=1' . $today;
$tmp_sip = 'base_stat_uaddr.php?addr_type=1&amp;sort_order=occur_d&amp;time_cnt=1' . $today;
$tmp_dip = 'base_stat_uaddr.php?addr_type=2&amp;sort_order=occur_d&amp;time_cnt=1' . $today;
echo '
          <div class="header2">
            <table width="100%" class="systemstats">
              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Today's event: ") . '</td>
	            <td><a href="' . $tmp_today_unique . '">' . gettext("unique") . '</a></td>
	            <td><a href="' . $tmp_today . '">' . gettext("listing") . '</a></td>
	            <td><a href="' . $tmp_sip . '">' . gettext("Source IP") . '</a></td>
	            <td><a href="' . $tmp_dip . '">' . gettext("Destination IP") . '</a></td>
	          </tr>

              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Last 24 Hours events: ") . '</td>
	            <td><A href="' . $tmp_24hour_unique . '">' . gettext("unique") . '</a></td>
	            <td><A href="' . $tmp_24hour . '">' . gettext("listing") . '</a></td>
	            <td><A href="' . $tmp_24hour_sip . '">' . gettext("Source IP") . '</a></td>
	            <td><A href="' . $tmp_24hour_dip . '">' . gettext("Destination IP") . '</a></td>
	          </tr>

              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Last 72 Hours events: ") . '</td>
	            <td><a href="' . $tmp_72hour_unique . '">' . gettext("unique") . '</a></td>
	            <td><a href="' . $tmp_72hour . '">' . gettext("listing") . '</a></td>
	            <td><a href="' . $tmp_72hour_sip . '">' . gettext("Source IP") . '</a></td>
	            <td><a href="' . $tmp_72hour_dip . '">' . gettext("Destination IP") . '</a></td>
	          </tr>

	          <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Most recent ") . $last_num_alerts . gettext(" Events:") . '</td>
	            <td><a href="base_qry_main.php?new=1&amp;caller=last_any&amp;num_result_rows=-1&amp;submit=Last%20Any">' . gettext("any protocol") . '</a></td>
	            <td><a href="base_qry_main.php?new=1&amp;layer4=TCP&amp;caller=last_tcp&amp;num_result_rows=-1&amp;submit=Last%20TCP">TCP</a></td>
	            <td><a href="base_qry_main.php?new=1&amp;layer4=UDP&amp;caller=last_udp&amp;num_result_rows=-1&amp;submit=Last%20UDP">UDP</a></td>
	            <td><a href="base_qry_main.php?new=1&amp;layer4=ICMP&amp;caller=last_icmp&amp;num_result_rows=-1&amp;submit=Last%20ICMP">ICMP</a></td>
	          </tr>

              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Last Source Ports: ") . '</td>
	            <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=1&amp;proto=-1&amp;sort_order=last_d">' . gettext("any protocol") . '</a></td>
                <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=1&amp;proto=6&amp;sort_order=last_d">TCP</a></td>
                <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=1&amp;proto=17&amp;sort_order=last_d">UDP</a></td>
	          </tr>
      
              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Last Destination Ports: ") . '
                <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=2&amp;proto=-1&amp;sort_order=last_d">' . gettext("any protocol") . '</a></td>
                <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=2&amp;proto=6&amp;sort_order=last_d">TCP</a></td>
                <td><a href="base_stat_ports.php?caller=last_ports&amp;port_type=2&amp;proto=17&amp;sort_order=last_d">UDP</a></td>
              </tr>

              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Most Frequent Source Ports: ") . '</td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=1&amp;proto=-1&amp;sort_order=occur_d">' . gettext("any protocol") . '</a></td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=1&amp;proto=6&amp;sort_order=occur_d">TCP</a></td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=1&amp;proto=17&amp;sort_order=occur_d">UDP</a></td>
	          </tr>
      
              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Most Frequent Destination Ports: ") . '</td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=2&amp;proto=-1&amp;sort_order=occur_d">' . gettext("any protocol") . '</a></td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=2&amp;proto=6&amp;sort_order=occur_d">TCP</a></td>
	            <td><a href="base_stat_ports.php?caller=most_frequent&amp;port_type=2&amp;proto=17&amp;sort_order=occur_d">UDP</a></td>
	          </tr>

              <tr class="main_quick_surf">
	            <td style="text-align:left;">- ' . gettext("Most frequent ") . $freq_num_uaddr . " " . gettext(" Addresses") . ":" . '</td>
                <td><a href="base_stat_uaddr.php?caller=most_frequent&amp;addr_type=1&amp;sort_order=occur_d">' . gettext("Source") . '</a></td>
                <td><a href="base_stat_uaddr.php?caller=most_frequent&amp;addr_type=2&amp;sort_order=occur_d">' . gettext("Destination") . '</a></td>
	          </tr>

              <tr class="main_quick_surf">
	            <td colspan=2 style="text-align:left;">- <a href="base_stat_alerts.php?caller=last_alerts&amp;sort_order=last_d">' . gettext("Most recent ") . $last_num_ualerts . gettext("Unique Events") . '</a></td>
	          </tr>

	          <tr class="main_quick_surf">
	            <td colspan=2 style="text-align:left;">- <a href="base_stat_alerts.php?caller=most_frequent&amp;sort_order=occur_d">' . gettext("Most frequent ") . $freq_num_alerts . " " . gettext("Unique Events") . '</a></td>
	          </tr>
	        </table>
          </div>
    </td>
    <td align="right" valign="top">
      <div class="header2">'; // class="systemstats"
if ($event_cache_auto_update == 1) {
    UpdateAlertCache($db);
}
if (!setlocale(LC_TIME, gettext("eng_ENG.ISO8859-1"))) {
    if (!setlocale(LC_TIME, gettext("eng_ENG.utf-8"))) {
        setlocale(LC_TIME, gettext("english"));
    }
    printf("<strong>" . gettext("Queried on") . " </strong> : %s<br />", strftime(gettext("%a %B %d, %Y %H:%M:%S")));
    if (isset($_COOKIE['archive']) && $_COOKIE['archive'] == 1) {
        printf("<strong>" . gettext("Database:") . "</strong> %s &nbsp;&nbsp;&nbsp;(<strong>" . gettext("Schema Version:") . "</strong> %d) \n<br />\n", ($archive_dbname . '@' . $archive_host . ($archive_port != "" ? ':' . $archive_port : "")) , $db->baseGetDBversion());
    } else {
        printf("<strong>" . gettext("Database:") . "</strong> %s &nbsp;&nbsp;&nbsp;(<strong>" . gettext("Schema Version:") . "</strong> %d) \n<br />\n", ($alert_dbname . '@' . $alert_host . ($alert_port != "" ? ':' . $alert_port : "")) , $db->baseGetDBversion());
    }
    StartStopTime($start_time, $end_time, $db);
    if ($start_time != "") {
        printf("<strong>" . gettext("Time Window:") . "</strong> [%s] - [%s]\n", $start_time, $end_time);
    } else {
        printf("<strong>" . gettext("Time Window:") . "</strong> <em>" . gettext("no events detected") . "</em>\n");
    }
}
?>
      </div>
    </td>
  </tr>
  <tr>
    <td align="center" valign="top">
      <strong><a href="base_qry_main.php?new=1"><?php
echo gettext("Search"); ?></a></strong><br />
      <a href="base_stat_time.php"><?php
echo gettext("Graph Event Detection Time"); ?></a><br /><br />
<?php
DBLink(); ?>
    </td>
  </tr>
</table>

<hr />
<table style='border:0' width='100%'>
  <tr>
    <td width='30%' valign='top'>
<?php
/* mstone 20050309 avoid count(*) if requested */
PrintGeneralStats($db, 0, $main_page_detail, "", "", $avoid_counts != 1);
/* mstone 20050309 make show_stats even leaner! */
if ($main_page_detail == 1) {
    echo '
    </td>
    <td width="70%" valign="top">
    <strong>' . gettext("Traffic Profile by Protocol") . '</strong>';
    PrintProtocolProfileGraphs($db);
}
?>
    </td>
  </tr>
</table>

<p>
<hr />
<?php
include ("$BASE_path/base_footer.php");
if (strlen($base_custom_footer) != 0) {
    include ($base_custom_footer);
}
$stop = time();
if ($debug_time_mode > 0) {
    echo "<div class='systemdebug'>[Loaded in " . ($stop - $start) . " seconds]</div>";
}
?>
</body>
</html>
