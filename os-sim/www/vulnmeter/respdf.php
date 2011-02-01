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
// $Id: respdf.php,v 1.13 2010/04/26 16:08:21 josedejoses Exp $
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

define('FPDF_FONTPATH','inc/font/');
require('inc/pdf.php');

require_once('config.php');
require_once('ossim_conf.inc');
require_once('functions.inc');
require_once ('classes/Session.inc');

#require_once('auth.php');
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

$critical = 0;
$arrResults = array();
$getParams = array( "ipl", "treport", "scantime", "scantype", "scansubmit", "key", "critical", "filterip", "summary" );

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

$query_byuser = ((in_array("admin", $arruser))? "" : "and username in ('$user')");

$post = FALSE;

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(trim($_GET[$gp]), ENT_QUOTES); 
      } else {
         $$gp = "";
      }
   }
	break;
}

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

#FOUND NEEDED THE SESSION CODE LOCAL TO BY PASS NEED FOR AUTH TO FIX CODE TO ALLOW ACCESS FOR 30
#DAYS PER REPORT KEY
session_cache_limiter('none');
session_start();

//if (isset($_SESSION['user'])) { 
//   $username=htmlspecialchars(mysql_real_escape_string(trim($_SESSION['user'])), ENT_QUOTES); 
//} else { 
//   $username=""; 
//}

if ($ipl!="") { 
    if($ipl!="all") $report_id=$ipl;
    $query = "SELECT distinct sid FROM vuln_nessus_latest_reports WHERE 1=1".(($ipl!="all")?" AND report_id=$report_id":"")." $query_byuser";
    $result=$dbconn->execute($query);
    $sids = array();
    while ( !$result->EOF ) {
        list( $sid ) = $result->fields;
        $sids[] = $sid;
        $result->MoveNext();
    }
    $sid = implode(",",$sids);
    
    $query_scantime = "select max(scantime) as scantime from vuln_nessus_latest_reports WHERE 1=1".(($ipl!="all")?" AND report_id=$ipl limit 1":"")." $query_byuser";
    $result_scantime=$dbconn->Execute($query_scantime);
    $scantime = $result_scantime->fields['scantime'];
}

//online();

//Seperates the parts of the date so it doesn't just display it as one big number
$scanyear = substr($scantime, 0, 4);
$scanmonth = substr($scantime, 4, 2);
$scanday = substr($scantime, 6, 2);
$scanhour = substr($scantime, 8, 2);
$scanmin = substr($scantime, 10, 2);
$scansec = substr($scantime, 12);

//Generated date
$gendate = date("Y-m-d H:i:s");

//here you can set the preference for the table colors
//$head_fill_color = array(100,149,237);
$head_fill_color = array(165, 165, 165);
$head_text_color = 255;
$fill_color = array(224,235,255);
$text_color = 0;
$line_color = array(0,0,0);
$boarder_type = 1; #doesn't work yet, there's always a full boarder for now

//row height for the table
$row_height = 5;

/*
if ( $client_uname == $client_ip ) {
    $query_byuser = "AND report_key='$key'"; # AND DATEDIFF ( $scantime, now ) <= 15 )";

    $curtime = gen_strtotime("now");
    $sqlScanTime = gen_strtotime($scantime);
    $days = datediff("d", $curtime, $sqlScanTime );
    if (  $days > 30 ) {
        die ("Email link access for requested report has expired: days [ $days ] ");
    }
}
*/

$query_critical="";
$query_host="";
if ( $critical ) {
    $query_critical = "AND t1.risk <= '$critical' ";
}
if ( $filterip ) {
    $query_host = "AND t1.hostIP='$filterip'";
}

ini_set("max_execution_time","360");

if ($scansubmit!="") {
   $query = "SELECT r.report_id FROM vuln_nessus_reports r,vuln_jobs j WHERE r.report_id=j.report_id AND j.scan_SUBMIT='$scansubmit'
   AND scantype='$scantype'".((in_array("admin", $arruser))? "" : " AND r.username in ('$user')");
   //print_r($query);
   $result=$dbconn->execute($query);
   $ids = array();
   while ( !$result->EOF ) {
   list( $report_id ) = $result->fields;
        $ids[] = $report_id;
        $result->MoveNext();
   }
   $report_id = implode(",",$ids);
}
else if($ipl=="all") {
   $query = "SELECT report_id FROM vuln_nessus_latest_reports where scantype='$scantype' $query_byuser";
   //print_r($query);
   $result=$dbconn->execute($query);
   $ids = array();
   while ( !$result->EOF ) {
   list( $report_id ) = $result->fields;
        $ids[] = $report_id;
        $result->MoveNext();
   }
   $report_id = implode(",",$ids);
}
 else if ($report_id=="") {
    $query = "SELECT report_id FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_reports" : "vuln_nessus_reports")." WHERE ".(($treport=="latest")? "report_key=$key":"scantime='$scantime'")."
    AND scantype='$scantype' $query_byuser LIMIT 1";
    
    $result=$dbconn->execute($query);
    list ( $report_id ) = $result->fields;
}

if ( ! $report_id ) {
    //logAccess( "ATTEMPT TO ACCESS INVALID PDF REPORT" );?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
      <title> <?php
    echo gettext("Vulnmeter"); ?> </title>
      <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
      <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
      <link rel="stylesheet" type="text/css" href="../style/style.css"/>
    </head>
    <body>
      <?php
        include ("../hmenu.php");
        echo "<table width=\"100%\" class=\"noborder transparent\" >";
        echo "<tr><td class=\"nobborder\" heigh=\"15\">&nbsp;</td></tr>";
        echo "<tr><td class=\"nobborder\" style=\"text-align:center;\">";
        echo _("Report not found");
        echo "</td></tr>";
        echo "</table>";
     ?>
    </body>
    <?php
    exit(0);
}

$query = "select count(scantime) from ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
       where report_id  in ($report_id) and falsepositive='N'".(($treport=="latest" || $ipl!="")? " $query_byuser":"");
$result=$dbconn->execute($query);
list ( $numofresults ) = $result->fields;

if ($numofresults<1) {
    //logAccess( "PDF REPORT [ $report_id / NO RESULTS ] ACCESSED" );
    die(_("No vulnerabilities recorded"));
}
    //logAccess( "PDF REPORT [ $report_id ] ACCESSED" );



	
	
//include pdf libraries

set_time_limit(300);
//start pdf file, add page, set font
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 13);
if ($siteLogo!="") $pdf->Image($siteLogo,10,11,40);
$pdf->Ln();
$pdf->Cell(0, 10, "    $siteBranding: I.T Security Vulnerability Report", 1, 1, 'C', 0);

$pdf->SetFont('Helvetica', '', 10);

$pdf->Cell(95,6,_("Scan time").": $scanyear-$scanmonth-$scanday $scanhour:$scanmin:$scansec",1,0,'L');
$pdf->Cell(95,6,_("Generated").": $gendate",1,1,'L');


if ($report_id) {

    if ($treport!="" || $ipl!="") {
        $query = "SELECT t1.username, t1.name, t2.name, t2.description, t4.hostname as host_name 
            FROM vuln_nessus_latest_reports t1
            LEFT JOIN vuln_nessus_settings t2 on t1.sid=t2.id
            LEFT JOIN host t4 ON t4.ip=inet_ntoa(t1.report_id)
            WHERE t1.report_id in ($report_id)".((in_array("admin", $arruser))? "" : " AND t1.username in ('$user') ").(($treport=="latest" && $ipl=="")? " AND t1.report_key=$key " : " ")."
            ".(($ipl!="")? "and t1.sid in ($sid)":"")." order by t1.scantime DESC";
    }
    else {
        $query ="SELECT t1.username, t1.name, t2.name, t2.description
            FROM vuln_jobs t1
            LEFT JOIN vuln_nessus_settings t2 on t1.meth_VSET=t2.id
            WHERE t1.report_id in ($report_id) ".((in_array("admin", $arruser))? "" : " AND t1.username in ('$user') ")." $query_host 
            order by t1.SCAN_END DESC";


    }

    $result = $dbconn->execute($query);
    
   if ($ipl!="" && $ipl!="all"){
        $lprofiles = array();
        while (list( $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) =$result->fields) {
            if($host_name!="" && $host_name!=long2ip($report_id)) { $phost_name = "$host_name (".long2ip($report_id).")"; }
            else { $phost_name = long2ip($report_id); }
            $lprofiles[] = "$profile_name";
            $result->MoveNext();
        }
        $profiles = implode(",", $lprofiles);
        $profiles = preg_replace('/(.),(.)/', '$1, $2', $profiles);
   }
   if($ipl!="all"){
       if ($treport=="latest") {
            list( $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) = $result->fields;
            if($host_name!="" && $host_name!=long2ip($report_id)) { $phost_name = "$host_name (".long2ip($report_id).")"; }
            else { $phost_name = long2ip($report_id); }

       }
       else {
            list ($query_uid, $job_name, $profile_name, $profile_desc) = $result->fields;
       }

        if ($ipl!="") {
            $pdf->Cell(95,6,((count($lprofiles)>1)? _("Profiles") :_("Profile") ).": $profiles",1,0,'L');
        }
        else {
            $pdf->Cell(95,6,_("Profile").": $profile_name - $profile_desc",1,0,'L');
        }
        //$pdf->Cell(70,6,"Owner: $query_uid",1,1,'L');
        
        if($job_name=="") { // imported report
           $query_imported_report = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime' and report_key='$key'"; 
           $result_imported_report=$dbconn->execute($query_imported_report);
           $job_name = $result_imported_report->fields["name"];
        }
        
        $pdf->Cell(0, 6, (($treport=="latest" || $ipl!="")? _("Host - IP") : _("Job Name")).": ".(($treport=="latest" || $ipl!="")? "$phost_name" : "$job_name"), 1, 1, 'L');    
    }

    //$pdf->Cell(0, 6, "Subnet Description: $sub_desc", 1, 0, 'L');
#    $pdf->Ln();

    $pdf->SetFont('Helvetica', '', 10);

    //get current possition so we can put the pie chart to the side
    $valX = $pdf->GetX();
    $valY = $pdf->GetY();
    $riskarray = array(); //array of risks for pie chart
    $colorarray=array(); //array of colors for pie chart

    //$query = "SELECT DISTINCT hostIP, hostname FROM vuln_nessus_results t1
    //    where report_id='$report_id' $query_host $query_critical order BY hostIP";

    $query= "SELECT DISTINCT t1.hostip as hostIP, t2.hostname as hostname
             FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
             LEFT JOIN host t2 on t1.hostip = t2.ip
             where report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "")." $query_host $query_critical and falsepositive='N'
             order BY hostIP";
    //var_dump($query);
    

    $result = $dbconn->execute($query);

    //initialise variable for number of hosts while loop
    $hosts = array();
    while ($row = $result->fields) {
       if ($row['hostname']=="") $row['hostname'] = "unknown";
       $hosts[$row['hostIP']]=$row['hostname'];
       $result->MoveNext();
    }

    //initialise variable for number of hosts while loop
    //$hostchecks = array();
    //while ($row = $result->fields) {
    //   $hostchecks[$row['hostIP']]=$row['localChecks'];
    //   $result->MoveNext();
    //}

   if ($ipl=="all") {
        $query = "select count(*) as count, risk from (select distinct hostIP, port, protocol, app, scriptid, risk, msg
        from vuln_nessus_latest_results where report_id in($report_id) and falsepositive='N' $query_byuser) as t group by risk";
   }
   else if ($ipl!="") {
        $query = "select count(*) as count, risk from (select distinct port, protocol, app, scriptid, risk, msg
        from vuln_nessus_latest_results where report_id=$report_id and falsepositive='N' $query_byuser) as t group by risk";
   }
   else {
        $query = "SELECT COUNT( risk ) AS count, risk FROM (SELECT DISTINCT t1.hostIP, t1.risk, t1.port, t1.protocol, t1.app, t1.scriptid, t1.msg
                FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                WHERE report_id in ($report_id)".((!in_array("admin", $arruser) &&($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")."$query_host AND t1.falsepositive<>'Y'
                ) as t GROUP BY risk";

    }
    
    $ecount = 0;
    $result = $dbconn->Execute($query);

    $index = 0;
    $pdf->Ln();

    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 6, _("Total number of vulnerabilities identified on ").sizeof($hosts). _(" system(s)"),0,1,'C');
    $pdf->Rect(10,43,190,54);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 10);

    $Eriskcount = 0;
    $riskArray = array();
    $colorarray = array();
    while ( list($riskcount, $risk) = $result->fields ) {
        if ( $ecount > 0 ) {
        	$Eriskcount += $ecount;
        	$riskcount -= $ecount;
        }
        $pdf->MultiCell(70, 6, "".getrisk($risk)." : $riskcount" ,0,0,'C');
        $riskArray [getrisk($risk)] = $riskcount;
        $colorarray[$index] = getriskcolor($risk);
        $index++;
        $result->MoveNext();
    }
    if ( $Eriskcount > 0 ) {
    	$risk = 8;
    	$pdf->MultiCell(70, 6, "".getrisk($risk)." : $Eriskcount" ,0,0,'C');
    	$riskArray [getrisk($risk)] = $Eriskcount;
		$colorarray[$index] = getriskcolor($risk);
    }

    $pdf->Ln();
    $pdf->Ln();

    //$pdf->SetXY(85, 53); #$valY);
    $pdf->SetXY(85, 45); #$valY);
    $pdf->PieChart(140, 40, $riskArray, '%l', $colorarray);
    $pdf->SetXY($valX, $valY + 33);

    //Host-Vulnerability Summary
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();

    if($ipl=="all") $pdf->Ln();
    
    $pdf->SetFont('Helvetica', 'B', 10);   
    $pdf->Cell(0, 10, _("Total number of vulnerabilities identified per system"),0,1,'C');    

    $pdf->SetFont('Helvetica', 'B', 10);
    $size= 12 + (6 * sizeof($hosts));
    $pdf->Rect(10,97,190,$size);

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetFillColor(238, 238, 238);
    $pdf->Cell(28, 6, _("HostIP"),1,0,'C',1);
    $pdf->Cell(52, 6, _("HostName"),1,0,'C',1);
    //$pdf->Cell(20, 6, "LocalChks",1,0,'C');
    $pdf->Cell(22, 6, _("Serious"),1,0,'C',1);
    $pdf->Cell(22, 6, _("High"),1,0,'C',1);
    $pdf->Cell(22, 6, _("Med"),1,0,'C',1);
    $pdf->Cell(22, 6, _("Low"),1,0,'C',1);
    $pdf->Cell(22, 6, _("Info"),1,0,'C',1);
    //$pdf->Cell(20, 6, "Exceptions",1,0,'C');
    $pdf->Ln();

    foreach ($hosts as $hostIP=>$hostname) {
       //$check_value = $hostchecks[$hostIP];
       //$check_text = "";
       //if ( $check_value == "1" ) {
       //   $check_text = "success";
       //}elseif ( $check_value == "0" ) {
       //   $check_text = "failed";
       //} else {
       //   $check_text = "omitted";
       //}
       
       ${"IP_".$hostIP}=$pdf->AddLink();
       $pdf->Cell(28, 6, $hostIP, 1, 0, 'C', 0, ${"IP_".$hostIP});
       $pdf->Cell(52, 6, $hostname, 1, 0, 'C', 0, ${"IP_".$hostIP});
       //$pdf->Cell(20, 6, $check_text,1,0,'C');
       $host_risk = array ( 0,0,0,0,0,0,0,0);

    if ($ipl=="all") {
        $query1 = "select count(*) as count,risk from (select distinct hostIP, port,protocol,app,scriptid,risk,msg
        from vuln_nessus_latest_results where report_id=INET_ATON('$hostIP') and falsepositive='N' $query_byuser) as t group by risk";
    }
    
    else if ($ipl!="") {
        $query1 = "select count(*) as count,risk from (select distinct port,protocol,app,scriptid,risk,msg
        from vuln_nessus_latest_results where report_id=$report_id and falsepositive='N' $query_byuser) as t group by risk";
    }
    else {
    $query1 = "SELECT COUNT( risk ) AS count, risk FROM (SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP
        FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
        WHERE report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : ""). " and hostIP='$hostIP'".(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_critical
        and falsepositive <> 'Y') as t1 group by hostIP, risk";
    }
       //echo "$query1<br><br>";
       $ecount = 0;
       $result1 = $dbconn->Execute($query1);

       $prevrisk=0;
       $Eriskcount=0;

       while(list($riskcount, $risk )=$result1->fields) {
       	  if ( $ecount > 0 ) {
             $Eriskcount += $ecount;
             $riskcount -= $ecount;
          }
          $host_risk[$risk] = $riskcount;

          $prevrisk=$risk;
          $result1->MoveNext();
       }
       $host_risk[8] = $Eriskcount;

       //$arrrisks = array( "1", "2", "3", "6", "7", "8" );
       $arrrisks = array( "1", "2", "3", "6", "7");
       
       foreach ( $arrrisks as $rvalue ) {
       	  $value = "--";
       	  $width = "22";
          if ( $host_risk[$rvalue] > 0  ) {
             $value = $host_risk[$rvalue];
          }
          if ( $rvalue == 8 ) { $width = "20"; }
          $pdf->Cell( $width, 6, $value ,1,0,'C');
       }
       $pdf->Ln();

   }

   $pdf->Ln();  

   if ( $query_critical == "" ) {

      $pdf->SetFont('Arial', '', 8);
      // get the open ports data for the hosts
      /*$query="SELECT hostip, service, port, protocol, app, risk FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
           WHERE report_id in ($report_id)".(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")."AND record_type='N' $query_host
                 AND msg='' 
                 AND risk='7' AND falsepositive='N'
           ORDER BY result_id ASC";*/
           
      $query="SELECT distinct hostip, service, port, protocol, app, risk, msg FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
           WHERE report_id in ($report_id)".(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_host ".(($treport=="latest" || $ipl!="")? " $query_byuser ":"")."
                 AND msg='' 
                 AND risk='7' AND falsepositive='N'
           ORDER BY result_id ASC";

      $result1=$dbconn->execute($query); 
      // put it into an array for reference later
      $portResults=array();
      while(list($hostIP, $service, $service_num, $service_proto, $app, $risk, $msg) = $result1->fields) {
         $portResults[$hostIP][]=array( 'service_num' => $service_num, 
                                 'service_proto' => $service_proto, 
                                 'service' => $service, 
                                 'risk' => getrisk($risk) );
         $result1->MoveNext();
      }
   }

   if ( $summary == "1" ) {
   	   //output the pdf, now we're done$pdf-
   	   header("Cache-Control: public, must-revalidate");
   	   header("Pragma: ");
       header('Content-Type: application/pdf');
       //header("Content-disposition:  attachment; filename=scanresults-$uid-$scantime.pdf");
       $pdf->Output("scanresults-$uid-$scantime.pdf","I");
       exit;
   }

   //build array of risks - join the hosts table to get the hostname
   //don't use ip to get hostname, if dhcp in use hostname can't be looked up by ip reliabley from the host table -duh
   //get hostname at the time of the scan, else may fall back to the host table for stuff that may not be resolved otherwise.
   //need some assurance it is not false, routers etc, should be flagged as static address on the devices added to the host table


   if($ipl!="all") {
       $query = "SELECT distinct t1.hostIP, t2.hostname, t1.service, t1.port, t1.protocol, t1.app, t1.risk, t1.scriptid, v.name, t1.msg
                 FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE t1.report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_host $query_critical and t1.falsepositive<>'Y'
                 ORDER BY INET_ATON(t1.hostIP) ASC, t1.risk ASC";
                 
        /*$query_msg = "SELECT t1.msg
                 FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE t1.report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_host $query_critical and t1.falsepositive<>'Y'
                 ORDER BY t1.scantime DESC LIMIT 0,1";*/
    }
    else {
       $query = "SELECT distinct t1.hostIP, t2.hostname, t1.service, t1.port, t1.protocol, t1.app, t1.risk, t1.scriptid, v.name, t1.msg
                 FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE t1.report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_host $query_critical and t1.falsepositive<>'Y'
                 ORDER BY INET_ATON(t1.hostIP) ASC, t1.risk ASC";
        /*$query_msg = "SELECT t1.msg
                 FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
                 LEFT JOIN host t2 on t1.hostip = t2.ip
                 LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                 WHERE t1.report_id in ($report_id) ".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "").(($treport=="latest" && $ipl=="")? " AND scantime=$scantime " : " ")." $query_host $query_critical and t1.falsepositive<>'Y'
                 ORDER BY t1.scantime DESC LIMIT 0,1";*/
    }
    //echo $query;
   $eid = "";
   $result=$dbconn->Execute($query);

   //$arrResults="";
   while( list($hostIP, $hostname, $service, $service_num, $service_proto, $app, $risk, $scriptid, $pname, $msg) = $result->fields) {
      //$msg = get_msg($dbconn,$query_msg);
      
      if($hostname=="") $hostname="unknown";
      $arrResults[$hostIP][]=array(
         'hostname' => $hostname, 
          'service' => $service,
             'port' => $service_num, 
         'protocol' => $service_proto, 
      'application' => $app,  
             'risk' => $risk,
         'scriptid' => $scriptid,
        'exception' => $eid, 
              'msg' => preg_replace('/(<br\\s*?\/??>)+/i', "\n", $msg),
              'pname'=> $pname);
      $result->MoveNext();
   }

 //Vulnerability table configs
   $vcols = array(_("Risk"), _("Details"));
   //widths for columns
   $vwidth_array=array(20, 170); // 196 total

   $count=0;
   $oldip="";
   // iterate through the IP is the results
   foreach ($arrResults as $hostIP=>$scanData) {
   
      $hostIP=htmlspecialchars_decode($hostIP);
      $hostname=htmlspecialchars_decode($hosts[$hostIP]);
      // new host record?
      if($oldip!=$hostIP) {
         $oldip=$hostIP;

         // don't print the table on the first host
         if($count==1) { 
             $pdf->PrintTable($vcols, $all_results, $vwidth_array, $head_fill_color, $head_text_color, $fill_color,
                $text_color, $line_color, $boarder_type, $row_height, ${"IP_".$hostIP});
            $pdf->Ln();
         }

         $pdf->SetLink(${"IP_".$hostIP},$pdf->GetY());
               
         //print out the host cell
         $pdf->SetFont('','B',10);
         $pdf->Cell(95, 6, $hostIP,1,0,'C',1);
         $pdf->Cell(95, 6, $hostname,1,0,'C',1);
         //$pdf->Cell(105, 6, "",1,0,'C');
         $pdf->SetFont('','');
         $pdf->Ln();
         #PORT STUFF
      }

      // now iterate through the scan results for this IP
      $all_results = array();
      foreach($scanData as $vuln) {
      	 $exception = ""; 
      	 $risk_value = $vuln['risk'];
      	 $actual_risk = getrisk($risk_value);

      	 if ( $vuln['exception'] != ""  ) { 
      	 	$exception = "\n"._("EXCEPTION").": $vuln[exception]\n";
      	    $risk_value = 8;
      	 }

      	 $risk = getrisk($risk_value);

         $info = "";
         
         if ($exception!="") {
            $info  .= "\n$exception"; 
         }
         $info .= "\n".$vuln["pname"];
         $info .= "\nRisk:". $actual_risk;
         $info .= "\nApplication:".$vuln["application"];
         $info .= "\nPort:".$vuln["port"];
         $info .= "\nProtocol:".$vuln["protocol"];
         $info .= "\nScriptID:".$vuln["scriptid"]."\n\n";

         #$info=htmlspecialchars_decode($info);
         $msg=trim($vuln['msg']);
         $msg=htmlspecialchars_decode($msg);
         $msg=preg_replace('/^\n+/','',$msg);
         $msg= str_replace("&#039;","'", $msg);
         $msg = str_replace("\\r", "", $msg);
         $info .= $msg;
         
         $plugin_info = $dbconn->execute("SELECT t2.name, t3.name, t1.copyright, t1.summary, t1.version 
                FROM vuln_nessus_plugins t1
                LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
                LEFT JOIN vuln_nessus_category t3 on t1.category=t3.id
                WHERE t1.id='".$vuln["scriptid"]."'");

         list($pfamily, $pcategory, $pcopyright, $psummary, $pversion) = $plugin_info->fields;
         $info .= "\n";
         if ($pfamily!="")    { $info .= "\nFamily name: ".$pfamily;} 
         if ($pcategory!="")  { $info .= "\nCategory: ".$pcategory; }
         if ($pcopyright!="") { $info .= "\nCopyright: ".$pcopyright; }
         if ($psummary!="")   { $info .= "\nSummary: ".$psummary; }
         if ($pversion!="")   { $info .= "\nVersion: ".$pversion; }

         // append it to the results array
         if ( ! $critical || $risk_value <= $critical ) {
           $all_results[] = array( $risk, $info);
         }
      }
      // increment host counter
      $count=1;

   }
   // print out the final table
   if($count!=0) {
        $pdf->PrintTable($vcols, $all_results, $vwidth_array, $head_fill_color, $head_text_color, $fill_color, $text_color,
        $line_color, $boarder_type, $row_height, ${"IP_".$hostIP});
   }

    $pdf->Ln();


} #$pdf->Image('/var/www/html/images/Picture2.png',50,90,0,0,$type='',$link='');


header("Cache-Control: public, must-revalidate");
header("Pragma: ");
//output the pdf, now we're done$pdf-
header('Content-Type: application/pdf');
$output_name = "ScanResult_" . $scanyear . $scanmonth . $scanday . "_" . str_replace(" ","",$job_name) . ".pdf";
//header("Content-disposition:  attachment; filename=$output_name");
$pdf->Output($output_name,"I");


// prints out the table of risks
function printTable() {
    //build table
    $pdf->PrintTable($cols, $all_results, $width_array, $head_fill_color, $head_text_color, $fill_color, $text_color, $line_color, $boarder_type, $row_height);

    $pdf->Ln();
}

//matches risks number with colors
function getriskcolor($risk)
{
    switch ($risk)
    {
    case 1:
        $risk=array(200, 53, 237);
        //$risk=array(255,0,0);
        break;
    case 2:
        $risk=array(255,0,0);
        break;
    case 3:
        $risk=array(255, 165, 0);
        //$risk=array(255,255,0);
        break;
    case 4:
        $risk=array(255,255,0);
        break;
    case 5:
        $risk=array(255,255,0);
        break;
    case 6:
        $risk = array(255, 215, 0);
        //$risk=array(0,255,0);
        //$risk=array(0,139,69);
        break;
    case 7:
        $risk =array(240, 230, 140);
        //$risk=array(238, 238, 238);
        //$risk=array(100,149,237);
        break;
    case 8:
        $risk=array(255,153,0);
        break;        
    }
    return $risk;
}

function get_msg($dbconn,$query_msg) {
    $result=$dbconn->execute($query_msg);
    return ($result->fields["msg"]);
}

?>

