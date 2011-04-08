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

//
// $Id: rescsv.php,v 1.9 2010/04/26 16:08:21 josedejoses Exp $
//

/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net/                       */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/
#error_reporting(E_ALL);
require_once('config.php');
require_once('functions.inc');
require_once('classes/Session.inc');
require_once('ossim_conf.inc');

ini_set('memory_limit', '1500M');
ini_set("max_execution_time","720");

//require_once('auth.php');
//include ('permissions.inc.php');

//php4 version of htmlspecialchars_decode which is only available in php5 upwards
if (!function_exists('htmlspecialchars_decode'))
{
     function htmlspecialchars_decode($str)
     {
          $str = preg_replace("/&gt;/",">",$str);
          $str = preg_replace("/&lt;/","<",$str);
          $str = preg_replace("/&quot;/","\"",$str);
          $str = preg_replace("/&#039;/","'",$str);
          $str = preg_replace("/&amp;/","&",$str);

          return $str;
     }
}

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
     if (isset($_GET['scantime'])) { 
          $scantime=htmlspecialchars(mysql_escape_string(trim($_GET['scantime'])), ENT_QUOTES); 
     } else { $scantime=""; }
     
     if (isset($_GET['scantype'])) { 
          $scantype=htmlspecialchars(mysql_escape_string(trim($_GET['scantype'])), ENT_QUOTES); 
     } else { $scantype=""; }
     
     if (isset($_GET['key'])) { 
          $report_key=htmlspecialchars(mysql_escape_string(trim($_GET['key'])), ENT_QUOTES); 
     } else { $report_key=""; }
     
     if (isset($_GET['critical'])) { 
          $critical=htmlspecialchars(mysql_escape_string(trim($_GET['critical'])), ENT_QUOTES); 
     } else { $critical="0"; }
     
     if (isset($_GET['filterip'])) { 
          $filterip=htmlspecialchars(mysql_escape_string(trim($_GET['filterip'])), ENT_QUOTES); 
     } else { $filterip=""; }
     
     if (isset($_GET['scansubmit'])) { 
          $scansubmit=htmlspecialchars(mysql_escape_string(trim($_GET['scansubmit'])), ENT_QUOTES); 
     } else { $scansubmit=""; }
     
     break;
}

if ( $critical ) {
     $query_critical = "AND risk <= '$critical'";
}

//online();

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

$arruser = array();
if(!preg_match("/pro|demo/i",$version)){
    $user = Session::get_session_user();
    $arruser[]= $user;
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

$ipl = $_GET['ipl'];
$treport = $_GET['treport'];
$key = $_GET['key'];

ossim_valid($ipl, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, 'illegal:' . _("IP latest"));
if (ossim_error()) {
    die(_("Invalid Parameter ipl"));
}
ossim_set_error(false);

ossim_valid($treport, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Latest Report"));
if (ossim_error()) {
    die(_("Invalid Parameter treport"));
}
ossim_set_error(false);

ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Scantime"));
if (ossim_error()) {
    die(_("Invalid Scantime"));
}
ossim_set_error(false);

ossim_valid($scantype, OSS_ALPHA, 'illegal:' . _("Scan Type"));
if (ossim_error()) {
    die(_("Invalid Scan Type"));
}
ossim_set_error(false);

ossim_valid($key, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Key"));
if (ossim_error()) {
    die(_("Invalid Key"));
}
if ($ipl!="") { 
    if($ipl!="all") $report_id=$ipl;
    else {
       $query = "SELECT distinct report_id FROM vuln_nessus_latest_reports".((in_array("admin", $arruser))? "":" where username in ('$user')");
       //print_r($query);
       $result=$dbconn->execute($query);
       while ( !$result->EOF ) {
       list( $report_id ) = $result->fields;
            $ids[] = $report_id;
            $result->MoveNext();
       }
       $report_id = implode(",",$ids);
    }
    $query = "SELECT distinct sid FROM vuln_nessus_latest_reports WHERE 1=1".(($ipl!="all")? " AND report_id=$report_id":"").((in_array("admin", $arruser))? "":" AND username in ('$user')");
    $result=$dbconn->execute($query);
    while ( !$result->EOF ) {
        list( $sid ) = $result->fields;
        $sids[] = $sid;
        $result->MoveNext();
    }
    $sid = implode(",",$sids);
    
    $query_scantime = "select max(scantime) as scantime from vuln_nessus_latest_reports where 1=1".((in_array("admin", $arruser))? "":" AND username in ('$user')").(($ipl!="all")?" and report_id=$ipl limit 1":"");
    $result_scantime=$dbconn->Execute($query_scantime);
    $scantime = $result_scantime->fields['scantime'];
}
else {

    if ($scansubmit!="") {
        $query = "SELECT r.report_id FROM vuln_nessus_reports r,vuln_jobs j 
                  WHERE r.report_id=j.report_id AND j.scan_SUBMIT='$scansubmit'
                  AND scantype='$scantype'".((in_array("admin", $arruser))? "":" AND r.username in ('$user')");
        $result=$dbconn->execute($query);
        while ( !$result->EOF ) {
            list( $report_id ) = $result->fields;
            $ids[] = $report_id;
            $result->MoveNext();
        }
           $report_id = implode(",",$ids);
    }
    else {
            $query = "SELECT report_id FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_reports" : "vuln_nessus_reports")." WHERE ".(($treport=="latest")? "report_key=$key":"scantime='$scantime'")."
                    AND scantype='$scantype' ".((in_array("admin", $arruser))? "":" AND username in ('$user')")." LIMIT 1";

            $result=$dbconn->execute($query);
            list ( $report_id ) = $result->fields;
        }
}


//Generated date
$gendate = date("Y-m-d H:i:s");

if ( ! $report_id ) {
    //logAccess( "ATTEMPT TO ACCESS INVALID NESSUS REPORT" );
    die(_("Report not found"));
}

$query = "select count(scantime) from ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
       where report_id in ($report_id)  ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user')":"")." and falsepositive='N'";
$result=$dbconn->execute($query);
list ( $numofresults ) = $result->fields;

if ($numofresults<1) {
    //logAccess( "NESSUS REPORT [ $report_id / NO RESULTS ] ACCESSED" );
    die(_("No vulnerabilities recorded"));
}

$scanyear = substr($scantime, 0, 4);
$scanmonth = substr($scantime, 4, 2);
$scanday = substr($scantime, 6, 2);
$scanhour = substr($scantime, 8, 2);
$scanmin = substr($scantime, 10, 2);
$scansec = substr($scantime, 12);

    if ($treport!="" || $ipl!="") {
        $query = "SELECT t1.username, t1.name, t2.name, t2.description, t4.hostname as host_name 
            FROM vuln_nessus_latest_reports t1
            LEFT JOIN vuln_nessus_settings t2 on t1.sid=t2.id
            LEFT JOIN host t4 ON t4.ip=inet_ntoa(t1.report_id)
            WHERE t1.report_id in ($report_id) ".((in_array("admin", $arruser))? "":" AND t1.username in ('$user')").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")."
            ".(($ipl!="")? " and t1.sid in ($sid)":"")." order by t1.scantime DESC";
    }
    else {
        $query = "select distinct t1.username, t3.name, t2.name, t2.description
            FROM vuln_nessus_reports t1
            LEFT JOIN vuln_nessus_settings t2 on t1.sid=t2.id
            LEFT JOIN vuln_jobs t3 on t3.report_id = t1.report_id
            where t1.report_id in ($report_id) $query_host  ".((in_array("admin", $arruser))? "":" AND t1.username in ('$user')")."
            order by scantime DESC";
    }
    $result = $dbconn->execute($query);
    
    //echo "[".$query."]";

    if ($ipl!=""){
        $lprofiles = array();
        while (list( $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) =$result->fields) {
            if($host_name!="" && $host_name!=long2ip($report_id)) { $phost_name = "$host_name (".long2ip($report_id).")"; }
            else { $phost_name = long2ip($report_id); }
            if ($profile_name=="") $profile_name = "-";
            $lprofiles[] = "$profile_name";
            $result->MoveNext();
        }
        $profiles = implode(",", $lprofiles);
        $profiles = preg_replace('/(.),(.)/', '$1, $2', $profiles);
   }
   if ($treport=="latest") {
        list( $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) = $result->fields;
        if($host_name!="" && $host_name!=long2ip($report_id)) { $phost_name = "$host_name (".long2ip($report_id).")"; }
        else { $phost_name = long2ip($report_id); }

   }
   else {
        list ($query_uid, $job_name, $profile_name, $profile_desc) = $result->fields;
        if($job_name=="") { // imported report
           $query_imported_report = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime' and report_key='$key'"; 
           $result_imported_report=$dbconn->execute($query_imported_report);
           $job_name = $result_imported_report->fields["name"];
        }
    }

// send the right content headers 


header("Cache-Control: public, must-revalidate");
header("Pragma: ");
header("Content-Type: application/vnd.ms-excel");
$output_name = "ScanResult_" . $scanyear . $scanmonth . $scanday . "_" . str_replace(" ","",$job_name) . ".xls";
$output_name = preg_replace( '/-_/', "", $output_name);
header("Content-disposition:  attachment; filename=$output_name");

// Set up the output table
//$dataHead = <<<EOT
echo <<<EOT
<style type="text/css">
    br {mso-data-placement:same-cell;}
</style>

<table border=\"1\" style=\"mso-data-placement:same-cell;\">
EOT;

if ($siteLogo!="") {
echo <<<EOT
<tr>
EOT;
echo "<th colspan=\"11\" height=\"46\" style=\"text-align:center;\">$siteBranding: "._("I.T Security Vulnerability Report")."</th>\n";
echo <<<EOT
</tr>
EOT;

} else {
echo <<<EOT
<tr>
EOT;
   echo "<th colspan=\"11\"> $siteBranding: "._("I.T Security Vulnerability Report")."</th>\n";
echo <<<EOT
</tr>
EOT;

}
echo <<<EOT
<tr>
EOT;
   echo "<th>"._("Scan time").":</th>";
echo <<<EOT
   <td colspan="10" style="text-align:left">$scanyear-$scanmonth-$scanday $scanhour:$scanmin:$scansec</td>
</tr>
<tr>
EOT;
   echo "<th>"._("Generated").":</th>";
echo <<<EOT
   <td colspan="10" style="text-align:left">$gendate</td>
</tr>
EOT;
if($ipl=="") {
    echo "<tr>";
    echo "<th>"._("Profile").":</th>";
    echo "<td colspan=\"10\" align=\"left\">$profile_name\n - $profile_desc</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>"._("Job Name").":</th>";
    echo "<td colspan=\"10\" align=\"left\">$job_name</td>";
    echo "</tr>";
}

echo <<<EOT
<tr>
EOT;

echo "<th>"._("HOSTNAME")."</th>";
echo "<th>"._("HOST IP")."</th>";
echo "<th>"._("SERVICE")."</th>";
echo "<th>"._("VULN ID")."</th>";
echo "<th>"._("RISK LEVEL")."</th>";
echo "<th>"._("VULNERABILITY")."</th>";
echo "<th>"._("OBSERVATION")."</th>";
echo "<th>"._("REMEDIATION")."</th>";
echo "<th>"._("CONSEQUENCES")."</th>";
echo "<th>"._("TEST OUTPUT")."</th>";
echo "<th>"._("OPERATING SYSTEM")."</th>";
echo "</tr>";

    $query = "select distinct hostip from ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." where report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user')":"")." $query_host and falsepositive='N' order by INET_ATON(hostip) ASC";
    
    $result = $dbconn->execute($query);

    while ( list($hostip) = $result->fields ) {
        if($ipl=="all") {
            $query1 = "select distinct t2.hostname, t1.service, t1.risk, t1.falsepositive, t1.scriptid, v.name, t1.msg FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE t1.report_id=inet_aton('$hostip')  ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user')":"")." and t1.hostip='$hostip' and t1.msg<>'' and t1.falsepositive<>'Y'
                 order by t1.risk ASC, t1.result_id ASC";
        }
        else {
            $query1 = "select distinct t2.hostname, t1.service, t1.risk, t1.falsepositive, t1.scriptid, v.name, t1.msg FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE 1=1 ".(($treport!="")? "AND t1.scantime=$scantime":"").((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') ":"")." AND t1.report_id in ($report_id) and t1.hostip='$hostip' and t1.msg<>'' and t1.falsepositive<>'Y'
                 order by t1.risk ASC, t1.result_id ASC";
        }

        $result1 = $dbconn->execute($query1);
        $arrResults="";

          while ( list($hostname, $service, $risk, $falsepositive, $scriptid, $pname, $msg) = $result1->fields ){

              if($hostname=="") $hostname = "unknown";
              $tmpport1=preg_split("/\(|\)/",$service);
              if (sizeof($tmpport1)==1) { $tmpport1[1]=$tmpport1[0]; }
              $tmpport2=preg_split("/\//",$tmpport1[1]);
              $service_num=$tmpport2[0];
              $service_proto=$tmpport2[1];
              $risk_txt = getrisk($risk);
              $msg = str_replace("\\r", "", $msg);
             echo "<tr valign=\"top\">";
             echo "<td style=\"text-align:center\">$hostname</td><td style=\"text-align:center\">$hostip</td><td style=\"text-align:center\">$service</td><br>";
             $msg = preg_replace("/(Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)\s*:/","<br><b>\\1:</b><br>",$msg);
             $msg .= "<b>to search</b>"; // added for latest items
             echo "    <td style=\"text-align:center\">$scriptid</td>";
             echo "    <td style=\"text-align:center\">$risk_txt</td>";
             echo "    <td style=\"text-align:left;vertical-align: top;\">";
             
             $plugin_info = $dbconn->execute("SELECT t2.name, t3.name, t1.copyright, t1.summary, t1.version 
                                    FROM vuln_nessus_plugins t1
                                    LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
                                    LEFT JOIN vuln_nessus_category t3 on t1.category=t3.id
                                    WHERE t1.id='$scriptid'"); 

             list($pfamily, $pcategory, $pcopyright, $psummary, $pversion) = $plugin_info->fields; 

             $pinfo = array();
             if ($pfamily!="")    { $pinfo[] = '<b>Family name:</b> '.trim(strip_tags($pfamily));} 
             if ($pcategory!="")  { $pinfo[] = '<b>Category:</b> '.trim(strip_tags($pcategory)); }
             if ($pcopyright!="") { $pinfo[] = '<b>Copyright:</b> '.trim(strip_tags($pcopyright)); }
             if ($psummary!="")   { $pinfo[] = '<b>Summary:</b> '.trim(strip_tags($psummary)); }
             if ($pversion!="")   { $pinfo[] = '<b>Version:</b> '.trim(strip_tags($pversion)); }
             
             if(count($pinfo)==0) {
                echo "n/a";
             }
             else {
                echo $pname." <br />".implode("<br /> ", $pinfo);
             }
             
             echo "</td>";
             
             $clean = str_replace("\n"," ",$msg);
             $clean_wbr = str_replace("\n","<br />",$msg);
             $clean_wbr = str_replace("<br>","<br />",$clean_wbr);
             $clean_wbr = preg_replace("/<br \/><br \/>/", "<br \/>", $clean_wbr);
             
             $data1 = "";
             $data2 = "";
             
             preg_match("/(Synopsis|Overview):(.*?)\<b\>/i", $clean, $data);
             $data1 = trim(strip_tags($data[2]));
             echo "<td>".(($data1=="") ? "n/a" : $data1)."</td>";
             
             preg_match("/(Solution|Fix):(.*?)\<b\>/i", $clean, $data);
             $data1 = trim(strip_tags($data[2]));
             
             echo "<td>".(($data1=="") ? "n/a" : $data1)."</td>";

             if(preg_match("/(Description|Vulnerability Insight|Impact|Impact Level|References)\s*:/i", $msg)) {
                 preg_match("/Description:(.*?)\<b\>/i", $clean, $data);
                 $data1 = trim(strip_tags($data[1]));
                 
                 preg_match("/Vulnerability Insight:(.*?)\<b\>/i", $clean, $data);
                 $data1 .= trim(strip_tags($data[1]));
                 
                 // add adiccional information to description
                 $impact_data = array();
                 
                 preg_match("/Impact:(.*?)\<b\>/i", $clean, $data);
                 if($data[1]!="") $impact_data[] = "<strong>"._("Impact").":</strong> " . trim(strip_tags($data[1]));
                 
                 preg_match("/Impact Level:(.*?)\<b\>/i", $clean, $data);
                 if($data[1]!="") $impact_data[] = "<strong>"._("Impact Level").":</strong> " . trim(strip_tags($data[1]));
                 
                 if(count($impact_data)!=0) { $data1 .= "<br />" . implode("<br />", $impact_data); }
                 
                 preg_match("/References:(.*?)\<b\>/i", $clean_wbr, $data);

                 if($data[1]!="") $data1 .= (($data1!="") ? "<br/>" : "")."<strong>"._("Refererences").":</strong>".trim($data[1]);
             }
             else {
                preg_match("/(.*?)\<b\>/i", $clean, $data);
                
                if($data[1]!="")
                    $data1 = $data[1];
                else
                    $data1 = str_replace("<b>to search</b>", "", $msg);
             }
             echo "<td>".((trim(strip_tags($data1))=="") ? "n/a" : $data1);
             
             preg_match("/See also:(.*?)\<b\>/i", $clean, $data);
             $data_see_also = trim(strip_tags($data[1]));
             if($data_see_also!="") {
                echo "<br />See also: " . $data_see_also;
             }
             echo "</td>";

             preg_match("/Plugin\soutput:(.*?)\<b\>/i", $clean, $data);
             $data1 = trim(strip_tags($data[1]));
             
             if(preg_match("/Information about this scan\s*:(.*?)\<b\>/i", $clean, $data))
                $data2 .= _("Information about this scan")."<br />".$data[1];

             echo "<td>".((trim($data1.$data2)=="") ? "n/a" : $data1. " " .$data2)."</td>";

             preg_match("/Affected\sSoftware\/OS:(.*?)\<b\>/i", $clean, $data);
             $data1 = trim(strip_tags($data[1]));
             
             echo "<td>".(($data1=="") ? "n/a" : $data1)."</td>";
             echo "</tr>";
             
             $result1->MoveNext();
          }
          $result->MoveNext();
    }


echo "</table>";

function get_msg($dbconn,$query_msg) {
    $result=$dbconn->execute($query_msg);
    return ($result->fields["msg"]);
}
?>
