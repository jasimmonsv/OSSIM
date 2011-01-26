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


require_once ('classes/Security.inc');

require_once ('config.php');
require_once ('functions.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');
require_once ('classes/Host_vulnerability.inc');
require_once ('classes/Net_vulnerability.inc');
require_once ('classes/Net.inc');
require_once ('classes/Host.inc');
require_once ('classes/Util.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/CIDR.inc');

$value = GET('value');
$type = GET('type');
$delete = GET('delete');
$scantime = GET('scantime');

$delete_selected = (intval(GET('deletesel'))=="1") ? true : false;

ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));
ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("scantime"));
ossim_valid($value, OSS_TEXT, OSS_NULLABLE, 'illegal: value');
ossim_valid($type, "hn", "freetext", "service", OSS_NULLABLE, 'illegal: type');

if (ossim_error()) {
    die(ossim_error());
}
$net = "";
$hosts = array();

if ($type=="net" && preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$value)) $net = $value;

//for autocomplete input

$autocnetworks = $autochosts = $autocsensors = "";
list($_sensors, $_hosts) = Host::get_ips_and_hostname($dbconn,true);
$_nets = Net::get_all($dbconn,true);
//echo "ok"; exit;

$sensor_list = Sensor::get_list($dbconn);

$allowedSensors = Session::allowedSensors();



foreach ($_hosts as $_ip => $_hostname) {
    if ($_hostname!=$_ip) $autochosts .= '{ txt:"'.$_hostname.' [Host:'.$_ip.']", id: "'.$_ip.'" },';
        else $autochosts .= '{ txt:"'.$_ip.'", id: "'.$_ip.'" },';
}
foreach ($_nets as $_net) $autocnetworks .= '{ txt:"'.$_net->get_name().' [Net:'.$_net->get_ips().']", id: "'.$_net->get_ips().'" },';
foreach($sensor_list as $sensor) if (in_array($sensor->get_ip(), explode(",",$allowedSensors)) || $allowedSensors=="") {
    $autocsensors .= '{ txt:"'.$sensor->get_name().' [Sensor:'.$sensor->get_ip().']", id: "'.$sensor->get_ip().'" },';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("Vulnmeter"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <script language="javascript" type="text/javascript" src="../js/excanvas.pack.js"></script>
  <script language="JavaScript" src="../js/jquery.flot.pie.js"></script>
  <script type="text/javascript" src="../js/vulnmeter.js"></script>
  <style type="text/css">
  img.downclick { cursor:pointer; }
  </style>
  <script>
    $(document).ready(function() {
        // Autocomplete assets
        var assets = [
            <?php echo preg_replace("/,$/","",$autochosts . $autocnetworks . $autocsensors); ?>
        ];
        $(".assets").autocomplete(assets, {
            minChars: 0,
            width: 300,
            max: 100,
            matchContains: true,
            autoFill: true,
            formatItem: function(row, i, max) {
                return row.txt;
            }
        }).result(function(event, item) {
            $('#assets').val(item.id);
        });
        
        $('.downclick').bind("click",function(){
            var cls = $(this).attr('value');
            $('.'+cls).toggle();
            if ($(this).attr('src').match(/ltP_nesi/))
                $(this).attr('src','../pixmaps/theme/ltP_neso.gif')
            else
                $(this).attr('src','../pixmaps/theme/ltP_nesi.gif')
        });
        //<? if ($net!="") { ?>
        //load_pie('<?=$net?>');
        //<? } ?>
        
        
        //$(".psinfo").simpletip({
        //  position: 'bottom',
        //  content: 'Loading info...',
        //  onBeforeShow: function() { 
        //      var id = this.getParent().attr('pid');
        //      this.load('ps.php?id=' + id);
        //  }
        //});
    });
    function deleteSelected(f) {
        if (confirm("<?=_("Do you want to delete current vulnerabilities for filtered hosts?")?>")) {
            location.href="index.php?deletesel=1";
        }
        else {
            return false;
        }
    }
  </script>
<head>
<body>
<?php
$conf = $GLOBALS["CONF"];
if (GET('withoutmenu')!=1) include ("../hmenu.php");

$db = new ossim_db();
$conn = $db->connect();


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
   "sortby", "sortdir", "allres", "fp","nfp", "wh", "bg", "filterip", "critical", "increment","type","value", "delete");
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

if ($delete_selected) {
    $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_id in (".$_SESSION["_dreport_ids"].")";
    $result=$dbconn->execute($query);
    
    $query = "DELETE FROM vuln_nessus_latest_results WHERE report_id in (".$_SESSION["_dreport_ids"].")";
    $result=$dbconn->execute($query);

    unset($_SESSION["_dreport_ids"]);
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

$arruser = array();

if(!preg_match("/pro|demo/i",$version)){
    $user = Session::get_session_user();
    $arruser[] = $user;
    if (Session::get_session_user() != ACL_DEFAULT_OSSIM_ADMIN && Session::am_i_admin())  $arruser[] = ACL_DEFAULT_OSSIM_ADMIN;
}
else {
    $entities = array();
    $entities = Acl::get_user_entities();
    $entities[] = Session::get_session_user(); // add current user
    if (Session::get_session_user() != ACL_DEFAULT_OSSIM_ADMIN && Session::am_i_admin())  $entities[] = ACL_DEFAULT_OSSIM_ADMIN;
    $arruser = $entities;
    $user = implode("', '",$entities);
}

if($delete!=""){
    $query = "SELECT report_id, sid, username FROM vuln_nessus_latest_reports WHERE report_key='$delete' and scantime='$scantime'";
    $result=$dbconn->execute($query);
    
    $dreport_id = $result->fields["report_id"];
    $dsid = $result->fields["sid"];
    $dusername = $result->fields["username"];
    
    $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_key='$delete' and scantime='$scantime'";
    $result=$dbconn->execute($query);
    
    $query = "DELETE FROM vuln_nessus_latest_results WHERE report_id=$dreport_id and username='$dusername' and sid='$dsid'";
    $result=$dbconn->execute($query);
}

function delete_results( $scantime, $scantype, $reporttype, $key, $sortby, $allres, $fp, $nfp, $op, $output, $wh, $bg ){
   global $enableDelProtect, $uroles, $username, $dbconn;

   #POTENTIALLY ALL ORG MEMBERS DELETE SHARED REPORTS IN THE FUTURE
   if ( ! $uroles[admin] ) { $sql_filter = " AND username='$username'";  }
   $query = "SELECT report_id FROM vuln_nessus_latest_reports where scantime='$scantime' AND scantype='$scantype' AND report_key='$key' $sql_filter";
   $result=$dbconn->execute($query);
   list( $report_id ) = $result->fields;
   echo "report_id=$report_id<br>";
   if ( ! $report_id ) {
      //logAccess( "FAILED ATTEMPT TO DELETE REPORT [ $report_id ]" );
      die("Cannot access this page - unable to find report or possible error in the URL."); 
   } else {
      if ( $enableDelProtect ) {
         //logAccess( "MAKR DELETED - REPORT [ $report_id ]" );
         $query = "UPDATE vuln_nessus_latest_reports SET deleted= '1' WHERE report_id='$report_id' LIMIT 1";
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

         $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_id='$report_id'";
         $result=$dbconn->execute($query);

      }
      echo "<font face=\"Verdana\" color=\"#666666\" size=\"2\">Vulnerability test results have been deleted.<BR></font>";
      echo "<br>";
      scans( $scantime, $scantype, $reporttype, $key, $sortby, $allres, $fp, $nfp, $op, $output, $wh, $bg );
   }
}

function list_results ( $type, $value, $sortby, $sortdir ) {

    global $scanstate, $isReportAdmin , $allres, $offset, $pageSize, $username, $uroles, $dbconn, $hosts;
    global $user, $arruser, $delete_selected;
   
   $filteredView = FALSE;

   $selRadio = array( "","","","");

   $query_onlyuser="";
   $url_filter="";

   //if (!$isReportAdmin || (!$allres)) { $query_onlyuser=" AND t1.username='$username' "; }   
   if(!in_array("admin", $arruser)) {$query_onlyuser = " AND t1.username in ('$user')";}
   //echo $query_onlyuser;

   //if ($sortby == "" ) { $sortby = "scantime"; }
   //if ($sortdir == "" ) { $sortdir = "DESC"; }
   
   $sortby = "t1.results_sent DESC, t1.name DESC";
   //$sortdir = "DESC";
   $sortdir = "";
   
   $queryw="";
   $queryl="";

   //$querys="SELECT distinct t1.report_id, t1.name as jobname, t4.meth_target, t1.scantime,
   //   t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED,
   //   t5.vSerious, t5.vHigh, t5.vMed, t5.vLow, t5.vInfo
   //      FROM vuln_nessus_latest_reports t1
   //   LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
   //   LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id
   //   LEFT JOIN vuln_nessus_report_stats t5 on t1.report_id = t5.report_id
   //      WHERE t1.deleted = '0' ";
   
    //  $querys="SELECT distinct t1.report_id, t4.name as jobname, t4.scan_submit, t4.meth_target, t1.scantime,
    // t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t3.name as profile, t4.id as jobid, t4.meth_SCHED,
    // t5.vSerious, t5.vHigh, t5.vMed, t5.vLow, t5.vInfo
    // FROM vuln_nessus_latest_reports t1
    // LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
    // LEFT JOIN vuln_jobs t4 on t1.report_id = t4.report_id
    // LEFT JOIN vuln_nessus_report_stats t5 on t1.report_id = t5.report_id
    // WHERE t1.deleted = '0' ";
     
     /*$querys="SELECT distinct t1.report_id, t1.scantime,
     t1.username, t1.scantype, t1.report_key, t1.report_type as report_type,
     t3.name as profile, '0' as vSerious, '0' as High, '0' as vMed, '0' as vLow, '0' as vInfo
     FROM vuln_nessus_latest_reports t1
     LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
     WHERE t1.deleted = '0' ";*/
     
     $querys = "SELECT distinct t1.report_id, t4.hostname as host_name, t1.scantime,
     t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t1.sid,
     t3.name as profile
     FROM vuln_nessus_latest_reports t1
     LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
     LEFT JOIN host t4 ON t4.ip=inet_ntoa(t1.report_id)
     LEFT JOIN vuln_nessus_latest_results t5 ON t1.report_id=t5.report_id 
     WHERE t1.deleted = '0' ";

     // set up the SQL query based on the search form input (if any)

    if($type=="scantime" && $value!="") {
        $selRadio[0] = "CHECKED";
        $q = $value;
        $queryw = " AND t1.scantime LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize"; 
        $stext =  "<b>"._("Search for Date/Time")."</b> = '*$q*'";
        $url_filter="&type=$type&value=$value";
    }
    else if($type=="service" && $value!="") {
        $selRadio[5] = "CHECKED";
        $q = $value;
        $queryw = " AND t5.service LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize"; 
        $stext =  "<b>"._("Search for Service")."</b> = '*".html_entity_decode($q)."*'";
        $url_filter="&type=$type&value=$value";
    }
    else if($type=="freetext" && $value!="") {
        $selRadio[6] = "CHECKED";
        $q = $value;
        $queryw = " AND t5.msg LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
        $queryl = " limit $offset,$pageSize"; 
        $stext =  "<b>"._("Search for Free Text")."</b> = '*".html_entity_decode($q)."*'";
        $url_filter="&type=$type&value=$value";
    }
   else if($type=="hostip" && $value!="") {
      $selRadio[1] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND (t4.hostname LIKE '%$q%' OR inet_ntoa(t1.report_id) LIKE '%$q%') $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext =  "<b>"._("Search for Host-IP")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
   else if($type=="fk_name" && $value!="") {
      $selRadio[2] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.fk_name LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = _("Search for Subnet/CIDR")." = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
   else if($type=="username" && $value!="") {
      $selRadio[3] = "CHECKED";
      $q = strtolower($value);
      $queryw = " AND t1.username LIKE '%$q%' $query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = "<b>"._("Search for user")."</b> = '*$q*'";
      $url_filter="&type=$type&value=$value";
    }
    else if($type=="hn" && $value!="") {
      $selRadio[4] = "CHECKED";
      if (preg_match("/\//",$value)) {
          /*$tokens = explode("/", $value);
          $bytes = explode(".",$tokens[0]);

          if($tokens[1]=="24")
                $q = $bytes[0].".".$bytes[1].".".$bytes[2].".";
          else if ($tokens[1]=="16")
                $q = $bytes[0].".".$bytes[1].".";
          else if ($tokens[1]=="8")
                $q = $bytes[0].".";
          else if ((int)$tokens[1]>24)
                $q = $bytes[0].".".$bytes[1].".".$bytes[2].".".$bytes[3];
          //
          */
          $ip_range = array();
          $ip_range = CIDR::expand_CIDR($value, "SHORT");
          $queryw = " AND (inet_aton(t1.name) >= '".$ip_range[0]."' AND inet_aton(t1.name) <='".$ip_range[1]."') $query_onlyuser order by $sortby $sortdir";
      }
      elseif (preg_match("/\,/",$value)) {
          $q = implode("','",explode(",",$value));
          $queryw = " AND t1.name in ('$q') $query_onlyuser order by $sortby $sortdir";
          $q = "Others";
      }
      else {
          $q = $value;
          $queryw = " AND t1.name LIKE '$q' $query_onlyuser order by $sortby $sortdir";
      }

      $queryl = " limit $offset,$pageSize";
      if (!preg_match("/\//",$value)) {
        $stext =  "<b>"._("Search for Host")."</b> = '".html_entity_decode($q)."'";
      }
      else {
        $stext =  "<b>"._("Search for Subnet/CIDR")."</b> = '$value'";
      }
      $url_filter="&type=$type&value=$value";
    }
   else {
      $selRadio[4] = "CHECKED";
      $viewAll = FALSE;
      $queryw = "$query_onlyuser order by $sortby $sortdir";
      $queryl = " limit $offset,$pageSize"; 
      $stext = "";
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
      //$queryc = "SELECT count(report_id) FROM vuln_nessus_latest_reports t1 WHERE 1=1 ";
      $queryc = "SELECT SQL_CALC_FOUND_ROWS distinct t1.report_id, t4.hostname as host_name, t1.scantime,
                t1.username, t1.scantype, t1.report_key, t1.report_type as report_type, t1.sid,
                t3.name as profile
                FROM vuln_nessus_latest_reports t1
                LEFT JOIN vuln_nessus_settings t3 ON t1.sid=t3.id
                LEFT JOIN host t4 ON t4.ip=inet_ntoa(t1.report_id)".
                (($type=="service"||$type=="freetext")? " LEFT JOIN vuln_nessus_latest_results t5 ON t1.report_id=t5.report_id ":" ")
                ."WHERE t1.deleted = '0' ";
      $dbconn->Execute($queryc.$queryw);
      
      $reportCount = $dbconn->GetOne("SELECT FOUND_ROWS() as total");

      $previous = $offset - $pageSize;
      if ($previous < 0) {
         $previous = 0;
      }

      $last = (intval($reportCount/$pageSize))*$pageSize;
      if ( $last < 0 ) { $last = 0; }
      $next = $offset + $pageSize;
      /*if ($next < $last) {
        $last = $next;
      }*/
      $pageEnd = $offset + $pageSize;
	$value=html_entity_decode($value);
echo "<center><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"900\"><tr><td class=\"headerpr\" style=\"border:0;\">"._("Current Vulnerablities")."</td></tr></table>";
      //echo "<p>There are $reportCount scans defined in the system.";
      // output the search form
echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"900\">";
echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";
      echo <<<EOT
<center>
<form name="hostSearch" id="hostSearch" action="index.php" method="GET">
<input type="text" length="25" name="value" class="assets" id="assets" value="$value">
EOT;
echo "
<!--<input type=\"radio\" name=\"type\" value=\"scantime\" $selRadio[0]>"._("Date")."/"._("Time")."-->
<!--<input type=\"radio\" name=\"type\" value=\"hostip\" $selRadio[1]>"._("Host - IP")."-->
<!--<input type=\"radio\" name=\"type\" value=\"fk_name\" $selRadio[2]>Subnet Name-->
<input type=\"radio\" name=\"type\" value=\"service\" $selRadio[5]>"._("Service")."
<input type=\"radio\" name=\"type\" value=\"freetext\" $selRadio[6]>"._("Free text")."
<input type=\"radio\" name=\"type\" value=\"hn\" $selRadio[4]>"._("Host/Net")."
<!--<input type=\"radio\" name=\"type\" value=\"username\" $selRadio[3]>Username-->
";
/*     echo <<<EOT
<input type="hidden" name="sortby" value="$sortby">
<input type="hidden" name="allres" value="$allres">
<input type="hidden" name="op" value="search">&nbsp;&nbsp;&nbsp;
EOT;*/
echo '<input type="hidden" name="withoutmenu" value="'.GET('withoutmenu').'">';
echo "<input type=\"submit\" name=\"submit\" value=\""._("Find")."\" class=\"button\" style=\"margin-left:15px;\">";
if(Session::am_i_admin() && (GET("submit")!="" || GET("type")!="") && GET("value")!="") echo "<input style=\"margin-left:5px;\" type=\"button\" value=\""._("Delete selection")."\" onclick=\"deleteSelected(this.form)\" class=\"button\">";
     echo <<<EOT
</form>
</center>
</p>
EOT;
      // output the pager
      //echo "<p align=center><a href='index.php?offset=0".$url_allres.$url_filter."' class='pager'>&lt&lt "._("First")."</a> | ";
      //if($offset != 0) {
      //   echo "<a href='index.php?offset=$previous".$url_allres.$url_filter."' class='pager'>&lt "._("Previous")." </a> | ";
      //}
      //if($pageEnd >= $reportCount) { $pageEnd = $reportCount; }
      //echo "[ ".($offset+1)." - $pageEnd of $reportCount ] | ";
      //if($next < $last) {
      //   echo "<a href='index.php?offset=$next".$url_allres.$url_filter."' class='pager'>| "._("Next")." &gt;</a> | ";
      //}
      //echo "<a href='index.php?offset=$last".$url_allres.$url_filter."' class='pager'> "._("Last")." &gt;&gt;</a></p>";
   } else {
      // get the search result count
      $queryc = "SELECT count( report_id ) FROM vuln_nessus_latest_reports WHERE t1.deleted = '0' ";
      $scount=$dbconn->GetOne($queryc.$queryw);
      echo "<p>$scount report";
      if($scount != 1) {
         echo "s";
      } else {
      }
      echo " "._("found matching search criteria")." | "; 
      echo " <a href='index.php' alt='"._("View All Reports")."'>"._("View All Reports")."</a></p>";
   }

   echo "<p>";
   echo $stext;
   echo "</p>";
   echo "</td></tr></table>";
   // get the hosts to display
   $result=$dbconn->GetArray($querys.$queryw.$queryl);

   $delete_ids = array();
   
    foreach ($result as $rpt) {
        $delete_ids[] = $dreport_id = $rpt["report_id"];
    }
    
    $_SESSION["_dreport_ids"]=implode(",", $delete_ids);
   
/*   if ($delete_selected!="") { // delete selected current vulns from latest tables defore display
        foreach ($result as $rpt) {
            $dreport_id = $rpt["report_id"];

            $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_id=$dreport_id";
            $result=$dbconn->execute($query);
            
            $query = "DELETE FROM vuln_nessus_latest_results WHERE report_id=$dreport_id";
            $result=$dbconn->execute($query);
        }
    ?>
    <script type="text/javascript">
    //    document.location.href='index.php';
    </script>
    <?php
   }
*/

   //echo "[$querys$queryw$queryl]"; 
   if($result === false) {
      $errMsg[] = _("Error getting results").": " . $dbconn->ErrorMsg();
      $error++;
      dispSQLError($errMsg,$error);
   } else {
       $data['vInfo'] = 0;
       $data['vLow'] = 0;
       $data['vMed'] = 0;
       $data['vHigh'] = 0;
       $data['vSerious'] = 0;
       
       $queryt = "SELECT count(*) AS total, risk, hostIP FROM (
                    SELECT DISTINCT port, protocol, app, scriptid, msg, risk, hostIP
                    FROM vuln_nessus_latest_results where falsepositive='N'".((in_array("admin", $arruser))? "": " and username in ('".$user."')").") AS t GROUP BY risk, hostIP";
       //echo "$queryt<br>";
       
       $resultt = $dbconn->Execute($queryt);
         while(list($riskcount, $risk, $hostIP)=$resultt->fields) {
            if($risk==7)
                $data['vInfo']+= $riskcount;
            else if($risk==6)
                $data['vLow']+=$riskcount;
            else if($risk==3)
                $data['vMed']+=$riskcount;
            else if($risk==2)
                $data['vHigh']+=$riskcount;
            else if($risk==1)
                $data['vSerious']+=$riskcount;
            $resultt->MoveNext();
      }

      if($data['vInfo']==0 && $data['vLow']==0 && $data['vMed']==0 && $data['vHigh']==0 && $data['vSerious']==0 )
            $tdata [] = array("report_id" =>"All","host_name" => "", "scantime" => "", "username" => "",
                            "scantype" => "", "report_key" => "", "report_type" => "", "sid" => "", "profile" => "",
                        "hlink" =>"", "plink" => "", "xlink" =>"",
                        "vSerious" => $data['vSerious'], "vHigh" => $data['vHigh'], "vMed" => $data['vMed'],
                        "vLow" => $data['vLow'], "vInfo" => $data['vInfo']);
                        
      else
      
            $tdata [] = array("report_id" =>"All","host_name" => "", "scantime" => "", "username" => "",
                            "scantype" => "", "report_key" => "", "report_type" => "", "sid" => "", "profile" => "",
                        "hlink" =>"reshtml.php?ipl=all&disp=html&output=full&scantype=M", "plink" => "respdf.php?ipl=all&scantype=M", "xlink" =>"rescsv.php?ipl=all&scantype=M",
                        "dlink" =>"",
                        "vSerious" => $data['vSerious'], "vHigh" => $data['vHigh'], "vMed" => $data['vMed'],
                        "vLow" => $data['vLow'], "vInfo" => $data['vInfo']);

      foreach($result as $data) {
         $data['vSerious'] = 0;
         $data['vHigh'] = 0;
         $data['vMed'] = 0;
         $data['vLow'] = 0;
         $data['vInfo'] = 0;
         // query for reports for each IP
         $query_risk = "SELECT distinct risk, port, protocol, app, scriptid, msg, hostIP FROM vuln_nessus_latest_results WHERE report_id = ".$data['report_id'];
         $query_risk.= " AND username = '".$data['username']."' AND sid =".$data['sid']." AND falsepositive='N'";
         //echo "[$query_risk]<br>";
         
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
         $data['plink'] = "respdf.php?treport=latest&scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
         $data['hlink'] = "reshtml.php?treport=latest&key=".$data['report_key']."&disp=html&output=full&scantime=".$data['scantime']."&scantype=".$data['scantype'].$more;
         $data['rerun'] = "sched.php?disp=rerun&job_id=".$data['jobid'].$more;
         $data['xlink'] = "rescsv.php?treport=latest&scantime=".$data['scantime']."&scantype=".$data['scantype']."&key=".$data['report_key'].$more;
         $data['xbase'] = "restextsummary.php?scantime=".$data['scantime']."&scantype=".$data['scantype'].$more
         ."&key=".$data['report_key'];
         if(Session::am_i_admin()) {
            $data['dlink'] = "index.php?delete=".$data['report_key']."&scantime=".$data['scantime'];
         }
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
                    'icon' => 'images/page_white_excel.png')
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
         if(Session::am_i_admin()) {
            $fieldMapLinks ["DELETE Results"] = array(
                     'url' => '%param%',
                   'param' => 'dlink',
                  'target' => 'main',
                    'icon' => 'images/delete.gif');
         }
         
      $fieldMap = array(
               "Host - IP"  => array( 'var' => 'hostip'),
               "Date/Time" => array( 'var' => 'scantime'),
               "Profile" => array( 'var' => 'profile'),
               "Serious" => array( 'var' => 'vSerious'),
               "High" => array( 'var' => 'vHigh'),
               "Medium" => array( 'var' => 'vMed'),
               "Low" => array( 'var' => 'vLow'),
               "Info" => array( 'var' => 'vInfo'),
               "Links" => $fieldMapLinks);
      if(count($tdata)>1)
        drawTableLatest($fieldMap, $tdata, "Hosts");
      else echo "<br><b>"._("No results found, try to change the search parameters")."<br><br></b>";

   }
   // draw the pager again, if viewing all hosts
   if(!$filteredView && $reportCount>10) {
echo "<p align=center>
<a href=\"index.php?offset=0".$url_allres.$url_filter."\" class=\"pager\">&lt&lt "._("First")."</a>
<a href=\"index.php?offset=$previous".$url_allres.$url_filter."\" class=\"pager\">&lt "._("Previous")." </a>";
echo "&nbsp;&nbsp;&nbsp;[ ".($offset+1)." - $pageEnd "._("of")." $reportCount ]&nbsp;&nbsp;&nbsp;";
if ($reportCount > $pageEnd)
    echo "<a href=\"index.php?offset=$next".$url_allres.$url_filter."\" class=\"pager\"> "._("Next")." &gt;</a>
    <a href=\"index.php?offset=$last".$url_allres.$url_filter."\" class=\"pager\"> "._("Last")." &gt;&gt;</a>";
echo "</p>";
   }
}    
echo "<center>";
echo "<table cellspacing=\"8\" cellpadding=\"0\" class=\"noborder\" width=\"900\" style=\"background-color:transparent\">";
echo "<tr><td class=\"nobborder\">";
    stats_severity_services($type, $value);
echo "</td></tr>";
echo "<tr><td class=\"nobborder\" style=\"padding-bottom:10px;\">";
//stats();
    stats_networks_hosts($type, $value);
echo "</td></tr>";
//echo "<tr><td class=\"nobborder\">";
//status();
//echo "</td></tr>";
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
echo "</center>";
require_once('footer.php');
?>