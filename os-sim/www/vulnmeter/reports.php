<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/

require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");

require_once 'classes/Host.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Incident.inc';

$value = GET('value');
$type = GET('type');
$report_name = POST('report_name');
$delete = GET('delete');
$assignto = (POST('transferred_user')!="")? POST('transferred_user'):POST('transferred_entity');

ossim_valid($value, OSS_TEXT, OSS_NULLABLE, 'illegal: value');  
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal: type');
ossim_valid($report_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Report name"));
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal: delete');
ossim_valid($assignto, OSS_DIGIT, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal: delete');

if($assignto=="") $assignto = Session::get_session_user();

if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$dbconn = $db->connect();

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

$net = "";
$hosts = array();

if ($type=="net" && preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$value)) $net = $value;

$error_importing = "";

//Delete imported reports

if($delete!=""){
    $result_delete = $dbconn->execute("SELECT username FROM vuln_nessus_reports WHERE report_id=".$delete);
    $user_delete = $result_delete->fields['username'];
    
    $result_delete = $dbconn->execute("SELECT distinct hostIP FROM vuln_nessus_results WHERE report_id=".$delete);
    while ( !$result_delete->EOF ) {
        $dbconn->execute("DELETE FROM vuln_nessus_latest_reports WHERE report_id = inet_aton('".$result_delete->fields['hostIP']."') AND username='".$user_delete."' AND sid=0");
        $dbconn->execute("DELETE FROM vuln_nessus_latest_results WHERE report_id = inet_aton('".$result_delete->fields['hostIP']."') AND username='".$user_delete."' AND sid=0");
        $result_delete->MoveNext();
    }
    
    $dbconn->execute("DELETE FROM vuln_nessus_results WHERE report_id = ".$delete);
    $dbconn->execute("DELETE FROM vuln_nessus_reports WHERE report_id = ".$delete);
}

//Imported nessus files 
if ($_FILES['nbe_file']['tmp_name']!="" && $_FILES['nbe_file']['size']>0) {
    $dest = $GLOBALS["CONF"]->db_conf["nessus_rpt_path"]."/tmp/import".md5($report_name).".nbe";
    if(!copy($_FILES['nbe_file']['tmp_name'], $dest)) {
        $error_importing =_("Error importing file");
    }
    else {
        $unresolved_host_names = array();
    
        $results_nbe = get_results_from_file ($dest);
        unlink($dest);
        
        $hostHash = pop_hosthash ($dbconn, $results_nbe);

        $scantime_import = date("YmdHis");
        $report_key = substr(preg_replace("/\D/", "", uniqid(md5(rand()), true)),0,15);
        
        $dbconn->execute("INSERT INTO vuln_nessus_reports ( username, name, sid, scantime, report_type, report_key ) VALUES (
        '".$assignto."', '".$report_name."', 0, '".$scantime_import."', 'I', '".$report_key."' )");
        
        $result_id_report = $dbconn->execute("SELECT report_id FROM vuln_nessus_reports WHERE scantime='$scantime_import' AND report_key='$report_key' ORDER BY scantime DESC LIMIT 1 ");
        $report_id_import = $result_id_report->fields["report_id"];
        
        if(POST('submit')==_("Import & asset insertion")) {
            $sensors = array();
            $sensor_list = Sensor::get_list($dbconn);
            foreach($sensor_list as $sensor)
                $sensors[] = $sensor->get_name();
        }
        
        foreach($hostHash as $ip => $data){
            if(preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/',$ip)){
                $hostname = Host::ip2hostname($dbconn, $ip);
            }
            else {
                $hostname = $ip;
                
                $ip = Host::hostname2ip($dbconn, $hostname, true);
                
                if($ip==""){
                    $unresolved_host_names[] = $hostname; 
                    continue;
                }
            }
            
            if(POST('submit')==_("Import & asset insertion") && Host::in_host($dbconn,$ip)=="") {
                Host::insert($dbconn, $ip, $hostname, 2, 60, 60, "", 0, 0, "", $sensors, ""); 
            
            }
            
            // latest results
            $report_keyl = substr(preg_replace("/\D/", "", uniqid(md5(rand()), true)),0,15);
            $dbconn->execute("DELETE FROM vuln_nessus_latest_reports WHERE report_id = inet_aton('".$ip."') AND username='".$assignto."' AND sid=0");
            $dbconn->execute("DELETE FROM vuln_nessus_latest_results WHERE report_id = inet_aton('".$ip."') AND username='".$assignto."' AND sid=0");
            $dbconn->execute("INSERT INTO vuln_nessus_latest_reports ( report_id, username, name, fk_name, sid, scantime, report_type, report_key, cred_used, note, failed )
                                VALUES (inet_aton('".$ip."'), '".$assignto."', '$ip', NULL, '0', '".$scantime_import."', 'I', '$report_keyl', NULL, '', '0' )");
            
            // load fps
            $host_fp = array();
            $result_fps=$dbconn->execute("SELECT scriptid,service FROM vuln_nessus_latest_results WHERE hostIP='$ip' and falsepositive='Y' UNION SELECT scriptid,service FROM vuln_nessus_results WHERE hostIP='$ip' and falsepositive='Y' ");
            while ( !$result_fps->EOF ) {
                $host_fp[$result_fps->fields['scriptid']][$result_fps->fields['service']] = 1;
                $result_fps->MoveNext();
            }
            
            foreach ($data["results"] as $id_result => $info){
                $fp = (intval($host_fp[$info["$scanid"]][$info["service"]]) == 1) ? 'Y' : 'N';
                // table vuln_nessus_results
                $sql_results = "INSERT INTO vuln_nessus_results ( report_id, scantime, hostip, hostname, record_type, service, port, protocol , app, scriptid,
                                risk, msg, falsepositive ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                
                $params = array(
                    $report_id_import, 
                    $scantime_import,
                    $ip,
                    $hostname,
                    $info["record"],
                    $info["service"],
                    $info["port"],
                    $info["proto"],
                    $info["app"],
                    $info["scanid"],
                    $info["risk"],
                    $info["desc"],
                    $fp
                );
                if ($dbconn->Execute($sql_results, $params) === false) {
                    print 'error inserting result: ' . $dbconn->ErrorMsg() . '<BR>';
                    exit;
                }
                // table vuln_nessus_latest_results
                $sql_results = "INSERT INTO vuln_nessus_latest_results ( report_id, username, sid, scantime, record_type, hostIP, hostname, service , port, protocol,
                                app, scriptid, risk, msg, falsepositive ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = array(
                    Host::ip2ulong($ip),
                    $assignto,
                    0,
                    $scantime_import,
                    $info["record"],
                    $ip,
                    $hostname,
                    $info["service"],
                    $info["port"],
                    $info["proto"],
                    $info["app"],
                    $info["scanid"],
                    $info["risk"],
                    $info["desc"],
                    $fp
                );
                if ($dbconn->Execute($sql_results, $params) === false) {
                    print 'error inserting result: ' . $dbconn->ErrorMsg() . '<BR>'; 
                    exit;
                }
                update_ossim_incidents ($dbconn, $conf->get_conf("vulnerability_incident_threshold"), $ip, $info["port"], $info["risk"], $info["desc"], $info["scanid"], Session::get_session_user(), $assignto);
            } 
            // update field results_sent in vuln_nessus_latest_reports
            $result_vuln_host=$dbconn->execute("SELECT count( * ) AS vulnerability FROM (SELECT DISTINCT hostip, port, protocol, app, scriptid, msg, risk
                        FROM vuln_nessus_latest_results WHERE report_id =inet_aton('$ip') AND falsepositive='N') AS t GROUP BY hostip");
            $vuln_host = $result_vuln_host->fields['vulnerability'];
            $dbconn->execute("UPDATE vuln_nessus_latest_reports SET results_sent=$vuln_host WHERE report_id=inet_aton('$ip') AND username='".$assignto."'");
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("Vulnmeter"); ?> </title>
  <meta http-equiv="refresh" content="60">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/> 
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <style type="text/css">
  img.downclick { cursor:pointer; }
  </style>
  <script>
    $(document).ready(function() {
        $('.downclick').bind("click",function(){
            var cls = $(this).attr('value');
            $('.'+cls).toggle();
            if ($(this).attr('src').match(/ltP_nesi/))
                $(this).attr('src','../pixmaps/theme/ltP_neso.gif')
            else
                $(this).attr('src','../pixmaps/theme/ltP_nesi.gif')
        });
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
            dest = $(this).attr('href');
            GB_show($(this).attr('gtitle'),dest,$(this).attr('gheight'),400);
            return false;
        });
    });
    function confirmDelete(key){
        var ans = confirm("Are you sure you want to delete this report?");
        if (ans) document.location.href='reports.php?delete='+key;
    }
    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }
    function GB_onclose() {
        document.location.href='reports.php';
    }
  </script>
<head>
<body>
<?php
if (GET('withoutmenu')!=1) include ("../hmenu.php");

require_once ('classes/Security.inc');
require_once ('config.php');
require_once ('ossim_conf.inc');
require_once ('functions.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host_vulnerability.inc');
require_once ('classes/Net_vulnerability.inc');
require_once ('classes/Net.inc');
require_once ('classes/Util.inc');


//require_once('auth.php');
//require_once('header2.php');
//require_once('permissions.inc.php');
//require_once('adodb/tohtml.inc.php');

// number of hosts to show per page with viewing all
$pageSize = 10;
$allres = 1;
$hosts = host_ip_name();

$isReportAdmin = ($uroles['admin'] || $uroles['reports']) ? TRUE : FALSE;

$getParams = array( "disp", "op", "output", "scantime", "scantype", "reporttype", "key", "offset",
   "sortby", "sortdir", "allres", "fp","nfp", "wh", "bg", "filterip", "critical", "increment","type","value");
$postParams = array( "disp", "op", "output", "scantime", "type", "value", "offset",
    "scantype", "fp","nfp", "filterip", "critical", "increment"
              );
$post = FALSE;

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES); 
      } else {
         $$gp = "";
      }
   }
	break;
case "POST" :
   $post = TRUE;
   foreach($postParams as $pp) {
      if (isset($_POST[$pp])) {
        $$pp=htmlspecialchars(mysql_real_escape_string(trim($_POST[$pp])), ENT_QUOTES);
      } else {
        $$pp = "";
      }
   }
	break;
}

if(isset($offset)) {
   if(!is_numeric($offset)) {
      $offset=0;
   }
} else {
   $offset=0;
}

$offset = intval($offset);

ossim_valid($sortby, "scantime", "jobname", "profile", OSS_NULLABLE, 'illegal:' . _("Sort By"));
if (ossim_error()) {
    die(_("Invalid Parameter sortby"));
}

ossim_valid($sortdir, "DESC", "ASC", OSS_NULLABLE, 'illegal:' . _("Sort Dir"));
if (ossim_error()) {
    die(_("Invalid Parameter sortdir"));
}

$arruser = array();

if(!preg_match("/pro/i",$version)){
    $user = Session::get_session_user();
    $arruser[] = $user;
}
else {
    $entities = array();
    $entities = Acl::get_user_entities();
    $entities[] = Session::get_session_user(); // add current user
    $arruser = $entities;
    $user = implode("', '",$entities);
}

function delete_results( $scantime, $scantype, $reporttype, $key, $sortby, $allres, $fp, $nfp, $op, $output, $wh, $bg ){
   global $enableDelProtect, $uroles, $username, $dbconn;

   #POTENTIALLY ALL ORG MEMBERS DELETE SHARED REPORTS IN THE FUTURE
   if ( ! $uroles[admin] ) { $sql_filter = " AND username='$username'";  }
   $query = "SELECT report_id FROM vuln_nessus_reports where scantime='$scantime' AND scantype='$scantype' AND report_key='$key' $sql_filter";
   $result=$dbconn->execute($query);
   list( $report_id ) = $result->fields;
   echo "report_id=$report_id<br>";
   if ( ! $report_id ) {
      //logAccess( "FAILED ATTEMPT TO DELETE REPORT [ $report_id ]" );
      die("Cannot access this page - unable to find report or possible error in the URL."); 
   } else {
      if ( $enableDelProtect ) {
         //logAccess( "MAKR DELETED - REPORT [ $report_id ]" );
         $query = "UPDATE vuln_nessus_reports SET deleted= '1' WHERE report_id='$report_id' LIMIT 1";
         $result=$dbconn->execute($query);
      } else {
         //logAccess( "DELETED - REPORT [ $report_id ]" );
         $query = "DELETE FROM vuln_nessus_results WHERE report_id='$report_id'";
         $result=$dbconn->execute($query);
         $query = "DELETE FROM vuln_nessus_report_stats WHERE report_id='$report_id'";
         $result=$dbconn->execute($query);
         $query = "DELETE FROM vuln_jobs WHERE report_id='$report_id'";
         $result=$dbconn->execute($query);
         #CLEAR MOST RECENT SCAN FLAGS (QUESTIONABLY SHORE REMAIN)
         #$query = "UPDATE I3_systems SET report_id='0' WHERE report_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "UPDATE I3_systems SET creport_id='0' WHERE creport_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "UPDATE vuln_subnets SET report_id='0' WHERE report_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "UPDATE vuln_subnets SET creport_id='0' WHERE creport_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "UPDATE hosts SET report_id='0' WHERE report_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "UPDATE hosts SET creport_id='0' WHERE creport_id='$report_id'";
         #$result=$dbconn->execute($query);
         #$query = "DELETE FROM host_stats WHERE report_id='$report_id'";
         #$result=$dbconn->execute($query);

         $query = "DELETE FROM vuln_nessus_reports WHERE report_id='$report_id'";
         $result=$dbconn->execute($query);

      }
      echo "<font face=\"Verdana\" color=\"#666666\" size=\"2\">Vulnerability test results have been deleted.<BR></font>";
      echo "<br>";
      scans( $scantime, $scantype, $reporttype, $key, $sortby, $allres, $fp, $nfp, $op, $output, $wh, $bg );
   }
}

function list_results ( $type, $value, $sortby, $sortdir ) {

   global $scanstate, $isReportAdmin , $allres, $offset, $pageSize, $username, $uroles, $dbconn, $hosts;
   global $user, $arruser;

   $filteredView = FALSE;

   $selRadio = array( "","","","");

   $query_onlyuser="";
   $url_filter="";

   //if (!$isReportAdmin || (!$allres)) { $query_onlyuser=" AND t1.username='$username' "; }   
   if(!in_array("admin", $arruser)) {$query_onlyuser = " AND t1.username in ('$user')";}

   if ($sortby == "" ) { $sortby = "scantime"; }
   if ($sortdir == "" ) { $sortdir = "DESC"; }

   $queryw="";
   $queryl="";

   //$querys="SELECT distinct t1.report_id, t1.name as jobname, t4.meth_target, t1.scantime,
   //   t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED,
   //   t5.vSerious, t5.vHigh, t5.vMed, t5.vLow, t5.vInfo
   //      FROM vuln_nessus_reports t1
   //   LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
   //   LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id
   //   LEFT JOIN vuln_nessus_report_stats t5 on t1.report_id = t5.report_id
   //      WHERE t1.deleted = '0' ";
   
  /*    $querys="SELECT distinct t1.report_id, t4.name as jobname, t4.scan_submit, t4.meth_target, t1.scantime,
     t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED,
     t5.vSerious, t5.vHigh, t5.vMed, t5.vLow, t5.vInfo
     FROM vuln_nessus_reports t1
     LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
     LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id
     LEFT JOIN vuln_nessus_report_stats t5 on t1.report_id = t5.report_id
     WHERE t1.deleted = '0' ";*/
      $querys="SELECT distinct t1.sid as sid, t1.report_id, t4.name as jobname, t4.scan_submit, t4.meth_target, t1.scantime,
     t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED, t1.name as report_name
     FROM vuln_nessus_reports t1
     LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
     LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id
     WHERE t1.deleted = '0' ";
  
   
   // set up the SQL query based on the search form input (if any)
   switch($type) {
   case "scantime":
      $selRadio[0] = "CHECKED";
      $q = $value;
      $queryw = " AND t1.scantime LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext =  "<b>"._("Search for Date/Time")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
      break;
   case "jobname":
      $selRadio[1] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext =  "<b>"._("Search for Job Name")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
      break;
   case "fk_name":
      $selRadio[2] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.fk_name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = _("Search for Subnet/CIDR")." = '*$q*'";
      $url_filter="&type=$type&value=$value";
      break;
   case "username":
      $selRadio[3] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.username LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = "<b>"._("Search for user")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
      break;
    case "net":
      $selRadio[4] = "CHECKED";
      if (!preg_match("/\//",$value)) {
        $q = $value;
      }
      else {
          $tokens = explode("/", $value);
          $bytes = explode(".",$tokens[0]);

          if($tokens[1]=="24")
                $q = $bytes[0].".".$bytes[1].".".$bytes[2].".";
          else if ($tokens[1]=="16")
                $q = $bytes[0].".".$bytes[1].".";
          else if ($tokens[1]=="8")
                $q = $bytes[0].".";
          else if ((int)$tokens[1]>24)
                $q = $bytes[0].".".$bytes[1].".".$bytes[2].".".$bytes[3];
      }
            
      $queryw = " AND t4.meth_TARGET LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize";
      if (!preg_match("/\//",$value)) {
        $stext =  "<b>"._("Search for Host")."</b> = '*$q*'";
      }
      else {
        $stext =  "<b>"._("Search for Subnet/CIDR")."</b> = '*$q*'";
      }
      $url_filter="&type=$type&value=$value";
      break;

   default:
      $selRadio[1] = "CHECKED";
      $viewAll = FALSE;
      $queryw = "$query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = "";
      break;
   }

   // put link to add new host

//   if ($isReportAdmin) {
//      $url_allres="&allres=";
//      if ($allres=="" || !is_numeric($allres) || (!$allres)) {
//         $allres=0;
//         echo "<a href='results.php?offset=0".$url_allres."1'>Show all results</a><br>";
//      } else {
//         $allres=1;
//         echo "<a href='results.php?offset=0".$url_allres."0'>Display only my Results</a><br>";
//      }
//      $url_allres .="$allres";
//   }

   // echo the search criteria used

   // set up the pager and search fields if viewing all hosts
   $reportCount = 0;
   if(!$filteredView) {
      $queryc = "SELECT count(report_id) FROM vuln_nessus_reports t1 WHERE t1.deleted = '0' ";
      $reportCount = $dbconn->GetOne($queryc.$queryw);
      $previous = $offset - $pageSize;
      if ($previous < 0) {
         $previous = 0;
      }
      $last = $reportCount - $pageSize;
      if ( $last < 0 ) { $last = 0; }
      $next = $offset + $pageSize;
      if ($next > $last) {
         $next = $last;
      }
      $pageEnd = $offset + $pageSize;

echo "<center><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\"><tr><td class=\"headerpr\" style=\"border:0;\">"._("Reports")."</td></tr></table></center>";
      //echo "<p>There are $reportCount scans defined in the system.";
      // output the search form
echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">";
echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";
      echo <<<EOT
<!--<form name="hostSearch" onSubmit="return OnSubmitForm();">-->
<center>
<form name="hostSearch" action="reports.php" method="GET">
<input type="text" length="25" name="value" value="$value">
EOT;
echo "
<input type=\"radio\" name=\"type\" value=\"scantime\" $selRadio[0]>"._("Date")."/"._("Time")."
<input type=\"radio\" name=\"type\" value=\"jobname\" $selRadio[1]>"._("Job Name")."
<!--<input type=\"radio\" name=\"type\" value=\"fk_name\" $selRadio[2]>Subnet Name-->
<input type=\"radio\" name=\"type\" value=\"net\" $selRadio[4]>"._("Host")."/"._("Net")."
<!--<input type=\"radio\" name=\"type\" value=\"username\" $selRadio[3]>Username-->
";
     echo <<<EOT
<input type="hidden" name="sortby" value="$sortby">
<input type="hidden" name="allres" value="$allres">
<input type="hidden" name="op" value="search">&nbsp;&nbsp;&nbsp;
EOT;
echo '<input type="hidden" name="withoutmenu" value="'.GET('withoutmenu').'">';
echo "<input type=\"submit\" name=\"submit\" value=\""._("Find")."\" class=\"btn\">";
     echo <<<EOT
</form>
</center>
</p>
EOT;
      // output the pager
      //echo "<p align=center><a href='reports.php?offset=0".$url_allres.$url_filter."' class='pager'>&lt&lt "._("First")."</a> | ";
      //if($offset != 0) {
      //   echo "<a href='reports.php?offset=$previous".$url_allres.$url_filter."' class='pager'>&lt "._("Previous")." </a> | ";
      //}
      //if($pageEnd >= $reportCount) { $pageEnd = $reportCount; }
      //echo "[ ".($offset+1)." - $pageEnd of $reportCount ] | ";
      //if($next < $last) {
      //   echo "<a href='reports.php?offset=$next".$url_allres.$url_filter."' class='pager'>| "._("Next")." &gt;</a> | ";
      //}
      //echo "<a href='reports.php?offset=$last".$url_allres.$url_filter."' class='pager'> "._("Last")." &gt;&gt;</a></p>";
   } else {
      // get the search result count
      $queryc = "SELECT count( report_id ) FROM vuln_nessus_reports WHERE t1.deleted = '0' ";
      $scount=$dbconn->GetOne($queryc.$queryw);
      echo "<p>$scount report";
      if($scount != 1) {
         echo "s";
      } else {
      }
      echo " "._("found matching search criteria")." | "; 
      echo " <a href='reports.php' alt='"._("View All Reports")."'>"._("View All Reports")."</a></p>";
   }

   echo "<p>";
   echo $stext;
   echo "</p>";
   echo "</td></tr></table>";
   // get the hosts to display
   $result=$dbconn->GetArray($querys.$queryw.$queryl);
   //echo "[$querys$queryw$queryl]";
   if($result === false) {
      $errMsg[] = _("Error getting results").": " . $dbconn->ErrorMsg();
      $error++;
      dispSQLError($errMsg,$error);
   } else {
      $tdata = array();
      foreach($result as $data) {
         $data['vSerious'] = 0;
         $data['vHigh'] = 0;
         $data['vMed'] = 0;
         $data['vLow'] = 0;
         $data['vInfo'] = 0;

         $query_risk = "SELECT risk FROM vuln_nessus_results WHERE report_id = ".$data['report_id'];
         $query_risk.= " AND falsepositive='N'";
         
         $result_risk = $dbconn->Execute($query_risk);
         while(!$result_risk->EOF) {
            if($result_risk->fields["risk"]==7)
                $data['vInfo']++;
            else if($result_risk->fields["risk"]==6)
                $data['vLow']++;
            else if($result_risk->fields["risk"]==3)
                $data['vMed']++;
            else if($result_risk->fields["risk"]==2)
                $data['vHigh']++;
            else if($result_risk->fields["risk"]==1)
                $data['vSerious']++;
            $result_risk->MoveNext();
         }
         $more = "&hmenu=Vulnerabilities&smenu=Vulnerabilities";
         $data['clink'] = "respdfc.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
         $data['plink'] = "respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
         $data['hlink'] = "reshtml.php?hmenu=Vulnerabilities&smenu=Reports&disp=html&amp;output=full&scantime=".$data['scantime']."&scantype=".$data['scantype'];
         $data['rerun'] = "sched.php?disp=rerun&job_id=".$data['jobid'].$more;
         $data['xlink'] = "rescsv.php?scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
         $data['xbase'] = "restextsummary.php?scantime=".$data['scantime']."&scantype=".$data['scantype'].$more
         ."&key=".$data['report_key'];
        /*
                                    $data['vSerious'] = "<a href=\"respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']
                                    ."&key=".$data['report_key']."&critical=1\">".$data['vSerious']."</a>";
                                    $data['vHigh'] = "<a href=\"respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']
                                    ."&key=".$data['report_key']."&critical=2\">".$data['vHigh']."</a>";
                                    $data['vMed'] = "<a href=\"respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']
                                    ."&key=".$data['report_key']."&critical=3\">".$data['vMed']."</a>";
                                    $data['vLow'] = "<a href=\"respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']
                                    ."&key=".$data['report_key']."&critical=6\">".$data['vLow']."</a>";	
                                    $data['vInfo'] = "<a href=\"respdf.php?scantime=".$data['scantime']."&scantype=".$data['scantype']
                                    ."&key=".$data['report_key']."&critical=7\">".$data['vInfo']."</a>";*/

        //$data['vSerious'] = $data['vSerious'];
        //$data['vHigh'] = $data['vHigh'];
        //$data['vMed'] = $data['vMed'];
        //$data['vLow'] = $data['vLow'];
        //$data['vInfo'] = $data['vInfo'];
        //$data['scan_submit'] = $data['scan_submit'];
            
        $list = explode("\n", trim($data['meth_target']));

        if(count($list)==1) {
            $list[0]=trim($list[0]);
            if($list[0]!=""){
                if($hosts[$list[0]]!="" && $hosts[$list[0]]!=$list[0]){
                    $data['target'] = $hosts[$list[0]]. " (".$list[0].")"; 
                }
                else {
                    $data['target'] = $list[0];
                }
            }
            else $data['target'] = "-";
        }
        elseif(count($list)==2) {
            $list[0] = trim($list[0]);
            if($hosts[$list[0]]!="" && $hosts[$list[0]]!=$list[0]){
                    $list[0] = $hosts[$list[0]]. " (".$list[0].")"; 
            }
            
            $list[1] = trim($list[1]);
            if($hosts[$list[1]]!="" && $hosts[$list[1]]!=$list[1]){
                    $list[1] = $hosts[$list[1]]. " (".$list[1].")";
            }
            
            $data['target'] = $list[0].' '.$list[1];
        }
        else {
            $list[0] = trim($list[0]);
            if($hosts[$list[0]]!="" && $hosts[$list[0]]!=$list[0]){
                    $list[0] = $hosts[$list[0]]. " (".$list[0].")"; 
            }
            
            $list[count($list)-1] = trim($list[count($list)-1]);
            if($hosts[$list[count($list)-1]]!="" && $hosts[$list[count($list)-1]]!=$list[count($list)-1]){
                    $list[count($list)-1] = $hosts[$list[count($list)-1]]. " (".$list[count($list)-1].")";
            }

            $data['target'] = $list[0]." ... ".$list[count($list)-1];
        }
        if ($data["report_type"]=="I") $data["jobname"] = $data["report_name"];
        $tdata[] = $data;
        

      }

      if($sortdir == "ASC") { $sortdir = "DESC"; } else { $sortdir = "ASC"; }
      $url = $_SERVER['SCRIPT_NAME'] . "?offset=$offset&sortby=%var%&sortdir=$sortdir".$url_allres.$url_filter;

      $fieldMapLinks = array();

         $fieldMapLinks = array(
            "HTML Results" => array(
                     'url' => '%param%',
                   'param' => 'hlink',
                   'target' => 'main',
                    'icon' => 'images/html.png'),
             "PDF Results" => array(
                     'url' => '%param%',
                   'param' => 'plink',
                  'target' => '_blank', 
                    'icon' => 'images/pdf.png'),
           "EXCEL Results" => array(
                     'url' => '%param%',
                   'param' => 'xlink',
                  'target' => '_blank',
                    'icon' => 'images/page_white_excel.png'),
//           "Baseline Results" => array(
//                     'url' => '%param%',
//                   'param' => 'xbase',
//                    'icon' => 'images/baseline.png'),                    
//            "Compliance Results" => array(
//                     'url' => '%param%',
//                   'param' => 'clink',
//                  'target' => '_blank',
//                    'icon' => 'images/blue_pdf.png'),
//            "Rerun Scan" => array(
//                    'url' => '%param%',
//                   'param' => 'rerun',
//                    'icon' => 'images/relaunch.gif')
);
      $fieldMap = array(
               "Date/Time" => array( 'var' => 'scantime', 'link' => $url ),
               "Job Name"  => array( 'var' => 'jobname', 'link' => $url ),
               "Targets" => array( 'var' => 'target', 'link' => $url ),
               "Profile" => array( 'var' => 'profile', 'link' => $url ),
               "Serious" => array( 'var' => 'vSerious', 'link' => $url ),
               "High" => array( 'var' => 'vHigh', 'link' => $url ),
               "Medium" => array( 'var' => 'vMed', 'link' => $url ),
               "Low" => array( 'var' => 'vLow', 'link' => $url ),
               "Info" => array( 'var' => 'vInfo', 'link' => $url ),
               "Links" => $fieldMapLinks);

      drawTable($fieldMap, $tdata, "Hosts");

   }

   // draw the pager again, if viewing all hosts
   if(!$filteredView && $last!=0) {
echo "<p align=center>
<a href=\"reports.php?offset=0".$url_allres.$url_filter."\" class=\"pager\">&lt&lt "._("First")."</a>
<a href=\"reports.php?offset=$previous".$url_allres.$url_filter."\" class=\"pager\">&lt "._("Previous")." </a>";
      echo "&nbsp;&nbsp;&nbsp;[ ".($offset+1)." - $pageEnd "._("of")." $reportCount ]&nbsp;&nbsp;&nbsp;";
echo "<a href=\"reports.php?offset=$next".$url_allres.$url_filter."\" class=\"pager\"> "._("Next")." &gt;</a>
<a href=\"reports.php?offset=$last".$url_allres.$url_filter."\" class=\"pager\"> "._("Last")." &gt;&gt;</a>
</p>";
   }
   else {
        echo "<br>";
   }
}    
echo "<center>";
if ($error_importing!="" || count($unresolved_host_names)>0) {
?>
    <table width="885"  cellspacing="0" cellpadding="0" class="transparent">
        <?if($error_importing!="") {?>
            <tr>
                <td class="nobborder" style="text-align:center;padding:10px 0px 10px 0px;"><span style="color:red"><b><?=$error_importing?></b></span></td>
            </tr>
        <?
        }
        if(count($unresolved_host_names)>0) {?>
            <tr>
                <td class="nobborder" style="text-align:center;padding:10px 0px 10px 0px;">
                    <?=_("Unresolved host names from imported scan:")?><br>
                    <b><?=implode(", ",$unresolved_host_names)?></b><br>
                    <?=_("Please insert them in Assets->Host")?></td>
            </tr>
        <?}?>
    </table>
<?
}
echo "<table cellspacing=\"8\" cellpadding=\"0\" class=\"noborder\" width=\"900\" style=\"background-color:transparent\">";
echo "<tr><td class=\"nobborder\">";
switch($disp) {
   case "delete":
      delete_results( $scantime, $scantype, $reporttype, $key, $sortby, $allres, $fp, $nfp, $op, $output, $wh, $bg );
      break;
      
   default:
      list_results( $type, $value, $sortby, $sortdir );
      break;
}
echo "</td></tr>";
echo "</table>";
?>
<form method="post" action="reports.php" enctype="multipart/form-data">
<table border="0" cellpadding="0" cellspacing="0" width="885"><tr><td class="headerpr" style="border: 0pt none;"><?=_("Import file results in nbe format")?></td></tr></table>
<table border="0" cellpadding="2" cellspacing="2" width="885">
    <tr>
        <th width="100"><?=_("Report Name")?></th> 
        <td width="785" class="nobborder" style="text-align:left;padding-left:5px;"><input name="report_name" type="text" style="width: 146px;"></td>
    </tr>
    <tr>
        <th width="100"><?=_("File")?></th>
        <td width="785" class="nobborder" style="text-align:left;padding-left:5px;"><input name="nbe_file" type="file" size="25"></td>
    </tr>
                <?
                $users = Session::get_list($dbconn);

                $conf = $GLOBALS["CONF"];
                $version = $conf->get_conf("ossim_server_version", FALSE);

                if(preg_match("/pro/i",$version)) {
                    $users_pro_login = array();
                    $users_pro = array();
                    $entities_pro = array();
                    
                    if(Session::am_i_admin()) { // admin in professional version
                        list($entities_all,$num_entities) = Acl::get_entities($dbconn);
                        $entities_types_aux = Acl::get_entities_types($dbconn);
                        $entities_types = array();

                        foreach ($entities_types_aux as $etype) { 
                            $entities_types[$etype['id']] = $etype;
                        }
                        
                        ?>
                        <tr>
                            <th><?php echo _("Assign To") ?></th>
                            <td style="text-align:left;padding-left:5px;" class="nobborder">
                                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                                    <tr>
                                        <td class="nobborder"><?php echo _("User:");?></td>
                                        <td class="nobborder">
                                          <select name="transferred_user" id="user" onchange="switch_user('user');return false;">
                                            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                                            <?php
                                            foreach($users as $u) if(Session::get_session_user()!=$u->get_login()){ ?>
                                                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                                            <?php
                                            } ?>
                                          </select>
                                        </td>
                                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                                        <td class="nobborder"><?php echo _("Entity:");?></td>
                                        <td class="nobborder">
                                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                                            <option value=""><? if (count($entities_all) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                                            <?php
                                                foreach ( $entities_all as $entity ) {
                                                ?>
                                                <option value="<?php echo $entity["id"]; ?>"><?php echo $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";?></option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr> 
                <?}
                    elseif(Acl::am_i_proadmin()) { // pro admin
                        //users
                        $users_admin = Acl::get_my_users($dbconn,Session::get_session_user()); 
                        foreach ($users_admin as $u){
                            if($u["login"]!=Session::get_session_user()){
                                $users_pro_login[] = $u["login"];
                            }
                        }
                        //if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
                        
                        //entities
                        list($entities_all,$num_entities) = Acl::get_entities($dbconn);
                        list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
                        $entities_list = array_keys($entities_admin);
                        
                        $entities_types_aux = Acl::get_entities_types($dbconn);
                        $entities_types = array();

                        foreach ($entities_types_aux as $etype) { 
                            $entities_types[$etype['id']] = $etype;
                        }
                        
                        //save entities for proadmin
                        foreach ( $entities_all as $entity ) if(in_array($entity["id"], $entities_list)) {
                            $entities_pro[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
                        }
                        
                        // filter users
                        foreach($users as $u) {
                            if (!in_array($u->get_login(),$users_pro_login)) continue;
                            $users_pro[$u->get_login()] = format_user($u, false);
                        }
                        ?>
                        <tr>
                            <th><?php echo _("Assign To") ?></th>
                            <td style="text-align:left;padding-left:5px;" class="nobborder">
                                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                                    <tr>
                                        <td class="nobborder"><?php echo _("User:");?></td>
                                        <td class="nobborder">
                                          <select name="transferred_user" id="user" onchange="switch_user('user');return false;">
                                            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                                            <?php
                                            foreach($users_pro as $loginu => $nameu) { ?>
                                                <option value="<?php echo $loginu; ?>"><?php echo $nameu; ?></option>
                                            <?php
                                            } ?>
                                          </select>
                                        </td>
                                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                                        <td class="nobborder"><?php echo _("Entity:");?></td>
                                        <td class="nobborder">
                                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                                            <option value=""><? if (count($entities_pro) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                                            <?php
                                                foreach ( $entities_pro as $entity_id => $entity_name ) {
                                                ?>
                                                <option value="<?php echo $entity_id; ?>"><?php echo $entity_name;?></option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr> 
                    <?
                    }
                    else { // normal user
                            $brothers = Acl::get_brothers($dbconn,Session::get_session_user());
                            foreach ($brothers as $brother){
                                $users_pro_login[] = $brother["login"];
                            }
                            //if(!in_array(Session::get_session_user(), $users_pro_login))   $users_pro_login[] = Session::get_session_user();
                            // filter users
                                foreach($users as $u) {
                                    if (!in_array($u->get_login(),$users_pro_login)) continue;
                                    $users_pro[$u->get_login()] = format_user($u, false);
                                }
                            ?>
                                <tr>
                                    <th><?php echo _("Assign To") ?></th>
                                    <td style="text-align:left;padding-left:5px;" class="nobborder">
                                      <select name="transferred_user">
                                        <option value=""><? if (count($users_pro) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                                        <?php
                            foreach($users_pro as $loginu => $nameu) { ?>
                                    <option value="<?php echo $loginu ?>"><?php echo $nameu ?></option>
                            <?php
                            } ?>
                                      </select>
                                    </td>
                                </tr>
                            <?
                    }
                }
                else {
                    ?>
                    <tr>
                        <th><?php echo _("Assign To") ?></th>
                        <td style="text-align:left;padding-left:5px;" class="nobborder">
                          <select name="transferred_user">
                            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                            <?php
                            foreach($users as $u) if ($u->get_login()!=Session::get_session_user()) { ?>
                                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                            <?php
                            } ?>
                          </select>
                        </td>
                    </tr> 
                <?}?>
    <tr>
        <td colspan="5" style="text-align:center;padding:15px 0px 5px 0px;" class="nobborder"> 
            <input class="btn" name="submit" type="submit" value="<?=_("Import")?>">&nbsp;&nbsp;
            <input class="btn" name="submit" type="submit" value="<?=_("Import & asset insertion")?>">
        </td>
    </tr>
</table>
</form>
<?
$db->close($dbconn);
echo "</center>";
require_once('footer.php');

function get_results_from_file ($outfile) {
    $issues = array();
    $compliance_plugins = array("21156", "21157", "24760");
    $lines = file($outfile);
    foreach ($lines as $line) {
        $host = $domain = $scan_id = $description = $service = $app = $port = $proto = $rec_type = $risk_type  = "";
        list( $rec_type, $domain, $host, $service, $scan_id, $risk_type, $description ) = explode("|", $line);
        if ($rec_type == "results") {
            if (preg_match("/^general/i",$service)) {
                $temp = array();
                $temp = explode("/", $service);
                $app = "general";
                $proto = $temp[1];
                $port = "0";
            } else {
                $temp = array();
                $temp = explode(" ", $service);
                $app = $temp[0];
                $temp[1] = str_replace("(","",$temp[1]);
                $temp[1] = str_replace(")","",$temp[1]);
                $temp2 = array();
                $temp2 = explode("/", $temp[1]);
                $port = $temp2[0];
                $proto = $temp2[1];
            }
            if ( $scan_id!="" && in_array($scan_id, $compliance_plugins) ) {
                //UPDATE SCANID FOR WIN CHECKS #21156
                if ( $scan_id =="21156" ) {
                    $test_name = $test_policy = "";
                    $temp = array();
                    $temp = explode("\\n",$description);
                    foreach ($temp as $li) {
                        $li = trim($li);
                        $li = preg_replace("/\#.*$/", "", $li);
                        if ($li== "") { continue; }
                        $li = str_replace("\"","",$li);
                        if (preg_match('/\[[EFP][AR][IRS][OLS][ER]D*\]/', $li)) {
                            $test_name = trim($li);
                            $test_name = preg_replace("/\[[EFP][AR][IRS][OLS][ER]D*\]/", "", $test_name);
                            $test_name = preg_replace("/:$/", "", $test_name);
                        }
                    }
                }
                $risk_value = "";
                if ( $description == "[PASSED]" ) {
                    $risk_value = "Risk factor : \n\nPassed\n";
                } else if ( $description == "[FAILED]" ) {
                    $risk_value = "Risk factor : \n\nFailed\n";
                } else {
                    $risk_value = "Risk factor : \n\nUnknown\n";
                }
                $description .= "$risk_value";
            }
            if ( $description!="" ) {   #ENSURE WE HAVE SOME DATA
                $description = preg_replace("/\\\/", "\\\\", $description);
                $description = preg_replace("/\\\\n/", "\\n", $description);
                $temp = array(
                    "Port"            => $port,
                    "Host"            => $host,
                    "Description"     => $description,
                    "Service"         => $app,
                    "Proto"           => $proto,
                    "ScanID"          => $scan_id
                );
                $issues [] = $temp;
            }
        }
    }
    return($issues);
}
function pop_hosthash($dbconn, $results) {
    
    $hostHash = array();
    $custom_risks = get_custom_risks($dbconn);
    
    foreach ($results as $result) {
        
        $scanid = $port = $desc = $service = $proto = $host = "";
        $scanid = preg_replace("/.*\.(\d+)$/","$1", $result["ScanID"]);

        $port = $result["Port"];
        $desc = $result["Description"];
        $service = $result["Service"];
        $proto = $result["Proto"];
        $host = $result["Host"];
        
        $app = $service;
        if($service != "") {
            if($proto != "") {
                $service = "$service ($port/$proto)";
            } else {
                $app = "general";
                $proto = $service;
                $port = "";
                $service = "general/$service";
            }
        }
        
        
        if($host == "") continue;
        
        $risk = "7";

        $alldesc = explode('\n',$desc);
        $strd = "";
        foreach ($alldesc as $desc) {
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Serious/s", $desc))          $risk = "1";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Critical/s", $desc))         $risk = "1";   
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*High/s", $desc))             $risk = "2";
          if (preg_match('/Risk [fF]actor\s*:\s*(..)*Medium/s', $desc))           $risk = "3";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Medium\/Low/s", $desc))      $risk = "4";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Low\/Medium/s", $desc))      $risk = "5";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Low/s", $desc))              $risk = "6";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Info/s", $desc))             $risk = "7";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*[nN]one/s", $desc))          $risk = "7";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Passed/s", $desc))           $risk = "6";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Unknown/s", $desc))          $risk = "3";
          if (preg_match("/Risk [fF]actor\s*:\s*(..)*Failed/s", $desc))           $risk = "2";
        
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Serious((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Critical((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*High((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Medium((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Medium\/Low((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Low\/Medium((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Low((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Info((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*[nN]one to High((..)(..)?|(\s)+| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*[nN]one((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Passed((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Unknown((..)(..)?| \/ |$)/", "", $desc);
          $desc = preg_replace("/Risk [fF]actor\s*:\s*.*Failed((..)(..)?| \/ |$)/", "", $desc);
        
          $strd .= $desc.'\n';
        }
        $desc = $strd;
        
        if ($custom_risks[$scanid]!="")     $risk = $custom_risks[$scanid];
        
        $service = trim($service);
        $desc = trim($desc);
        if($desc[0]=="\\" && $desc[1]=='n') $desc = substr($desc, 2);
        
        $desc = str_replace('\n','<br>',$desc);
        
        if(intval($scanid)>=60000)  $record_type = "C";
            else    $record_type = "N";
            

        
        $key = $port.$proto.$scanid;
        $hostHash[$host]['results'][$key] = array( 'scanid' => $scanid, 'port' => $port, 'app' => $app, 'service' => $service,
            'proto' => $proto, 'risk' => $risk, 'record' => $record_type, 'desc' => $desc );
        
        
    }
    return ($hostHash);
}
function get_custom_risks($dbconn) {
    $plugins = array();
    $result = $dbconn->Execute("SELECT id, custom_risk FROM vuln_nessus_plugins WHERE custom_risk IS NOT NULL");
    while ( !$result->EOF ) {
        if($result->fields["id"]!="")
            $plugins[$result->fields["id"]] = $result->fields["custom_risk"];
        $result->MoveNext();
    }
    return ($plugins);
}
function format_user($user, $html = true, $show_email = false) {
    if (is_a($user, 'Session')) {
        $login = $user->get_login();
        $name = $user->get_name();
        $depto = $user->get_department();
        $company = $user->get_company();
        $mail = $user->get_email();
    } elseif (is_array($user)) {
        $login = $user['login'];
        $name = $user['name'];
        $depto = $user['department'];
        $company = $user['company'];
        $mail = $user['email'];
    } else {
        return '';
    }
    $ret = $name;
    if ($depto && $company) $ret.= " / $depto / $company";
    if ($mail && $show_email) $ret = "$ret &lt;$mail&gt;";
    if ($login) $ret = "<label title=\"Login: $login\">$ret</label>";
    if ($mail) {
        $ret = '<a href="mailto:' . $mail . '">' . $ret . '</a>';
    } else {
        $ret = "$ret <font size=small color=red><i>(No email)</i></font>";
    }
    return $html ? $ret : strip_tags($ret);
}
function update_ossim_incidents ($dbconn, $vuln_incident_threshold, $hostip, $port, $risk, $desc, $scanid, $currentuser, $assignto) { 
    $id_pending = 65001;
    $id_false_positive = 6002;

    $risk = 8 - $risk;
    
    if($vuln_incident_threshold >= $risk) return;

    $sql_inc = $dbconn->execute("SELECT incident_id FROM incident_vulns WHERE ip = '$hostip' AND port = '$port' AND nessus_id = '$scanid'");
    $id_inc = $sql_inc->fields["incident_id"];
    
    if($id_inc!="") {
        $dbconn->execute("UPDATE incident SET last_update = now() WHERE id = '$id_inc'");
        $sql_inc = $dbconn->execute("SELECT priority FROM incident WHERE status='Closed' and id = '$id_inc'");
        $priority = $sql_inc->fields["priority"];
        
        if($priority!="") {
            $sql_inc = $dbconn->execute("SELECT incident_id FROM incident_tag WHERE incident_tag.incident_id = '$id_inc' AND incident_tag.tag_id = '$id_false_positive'");
            $hash_false_incident = $sql_inc->fields["incident_id"];
            if($hash_false_incident=="") {
                $dbconn->execute("UPDATE incident SET status = 'Open' WHERE id = '$id_inc'");
                $ticket_id = genID($dbconn,"incident_ticket_seq");
                $dbconn->execute("INSERT INTO incident_ticket (id, incident_id, date, status, priority, users, description) values ('$ticket_id', '$id_inc', now(), 'Open', '$priority', '$assignto','Automatic open of the incident')");
            }
        }
    }
    else {
        $sql_inc = $dbconn->execute("SELECT name,reliability,priority FROM plugin_sid where plugin_id = 3001 and sid = '$scanid'");
        $name_psid = $sql_inc->fields["name"];
        $reliability_psid = $sql_inc->fields["reliability"];
        $priority_psid = $sql_inc->fields["priority"]; 
        
        $vuln_name = ""; 
        if($name_psid != "") $vuln_name = $name_psid;
        else $vuln_name = "Vulnerability - Unknown detail";
        
        $priority = calc_priority($dbconn, $risk, $hostip, $scanid);
        $dbconn->execute("INSERT INTO incident(title, date, ref, type_id, priority, status, last_update, in_charge, submitter, event_start, event_end) VALUES('$vuln_name', now(), 'Vulnerability', 'Nessus Vulnerability', '$priority', 'Open', now(), '$assignto', '$currentuser', '0000-00-00 00:00:00', '0000-00-00 00:00:00')");
        
        $sql_inc = $dbconn->execute("SELECT MAX(id) id from incident");
        $incident_id = $sql_inc->fields["id"];
        
        #sanity check
        $desc = str_replace ("\"", "'", $desc);
        $desc = trim($desc);
        $incident_vulns_id = genID($dbconn, "incident_vulns_seq");
        $dbconn->execute("INSERT INTO incident_vulns(id, incident_id, ip, port, nessus_id, risk, description) VALUES('$incident_vulns_id', '$incident_id', '$hostip', '$port', '$scanid', '$risk', \"$desc\")");
        $dbconn->execute("INSERT INTO incident_tag(tag_id, incident_id) VALUES($id_pending, '$incident_id')");
        Incident::insert_subscription($dbconn, $incident_id, $assignto); 
    }
    
    
}
function genID($dbconn, $table) {
    
    $dbconn->execute("UPDATE $table SET id=LAST_INSERT_ID(id+1)");
    $last_id_query = $dbconn->execute("SELECT LAST_INSERT_ID() as lastid");

    return $last_id_query->fields["lastid"];
}
function calc_priority($dbconn, $risk, $hostip, $nessusid) {
    
    # If it's not set, set it to 1
    $risk_value = 1;
    
    if ($risk == "NOTE") {
        $risk_value = 0;
    }
    elseif ($risk == "INFO") {
        $risk_value = 1;
    }
    elseif ($risk == "Security Note") {
        $risk_value = 1;
    }
    elseif ($risk == "LOW") {
        $risk_value = 3;
    }
    elseif ($risk == "Security Warning") {
        $risk_value = 3;
    }
    elseif ($risk == "MEDIUM") {
        $risk_value = 5;
    }
    elseif ($risk == "HIGH") {
        $risk_value = 8;
    }
    elseif ($risk == "Security Hole") {
        $risk_value = 8;
    }
    elseif ($risk == "REPORT") {
        $risk_value = 10;
    }

    $sql_inc = $dbconn->execute("SELECT asset FROM host WHERE ip = '$hostip'");
    $asset = $sql_inc->fields["asset"];
    
    if ($asset == "") {
        $asset = 0;
    }
    
    $sql_inc = $dbconn->execute("SELECT reliability FROM plugin_sid WHERE sid = '$nessusid'");
    $reliability = $sql_inc->fields["reliability"];
    
    if ($reliability == "") {
        $reliability = 0;
    }
    
    # FIXME: check this formula once the values are clear. This is most definetivley wrong.
    $priority = intval( ($risk_value + $asset + $reliability) / 1.9 ); 
    return $priority;
}
?>