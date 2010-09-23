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
// $Id: reshtml.php,v 1.12 2010/04/26 16:08:21 josedejoses Exp $
//

/***********************************************************/
/*                 Inprotect                      */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                      */
/*                                          */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                              */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the         */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                 */
/*                                          */
/* You should have received a copy of the GNU General         */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                 */
/*                                          */
/* Contact Information:                              */
/* inprotect-devel@lists.sourceforge.net                */
/* http://inprotect.sourceforge.net/                    */
/***********************************************************/
/* See the README.txt and/or help files for more        */
/* information on how to use & config.                  */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                          */
/* This program is intended for use in an authorized          */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items      */
/* discovered with this program's use.                  */
/***********************************************************/

require_once('ossim_conf.inc');
require_once ('classes/Session.inc');

Session::logcheck("MenuEvents", "EventsVulnerabilities");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("Vulnmeter"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/greybox.js"></script>
  
  <? include ("../host_report_menu.php") ?>
  <style type="text/css">
  .tooltip {
   position: absolute;
   padding: 2px;
   z-index: 10;
   
   color: #303030;
   background-color: #f5f5b5;
   border: 1px solid #DECA7E;
   width:500px;
   
   font-family: arial;
   font-size: 11px;
   }
  </style>
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = "HTML Results";
require_once('config.php');
require_once("functions.inc");
//require_once('auth.php');
//require_once('permissions.inc.php');


$getParams = array( "key", "ipl, treport", "disp", "op", "output", "scantime", "scansubmit", "scantype", "reporttype", "key", "sortby", "allres", "fp","nfp", "wh", "bg", "filterip", "critical", "increment", "pag" );
$postParams = array( "treport", "disp", "op", "output", "scantime", "scansubmit", "scantype", "fp","nfp", "filterip", "critical", "increment" );

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
      if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES); 
      } else { 
         $$gp=""; 
      }
   }
   break;
case "POST" :
   foreach($postParams as $pp) {
      if (isset($_POST[$pp])) { 
         $$pp=htmlspecialchars(mysql_real_escape_string(trim($_POST[$pp])), ENT_QUOTES); 
      } else { 
         $$pp=""; 
      }
   }
   break;
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if ($pag=="" || $pag<1) $pag=1;

$arruser = array();

if(!preg_match("/pro|demo/i",$version)){
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

$query_byuser = ((in_array("admin", $arruser))? "" : "and username in ('$user')");

ossim_valid($treport, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Latest Report"));
if (ossim_error()) {
    die(_("Invalid Parameter treport"));
}
ossim_set_error(false);

ossim_valid($ipl, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("IP latest"));
if (ossim_error()) {
    die(_("Invalid Parameter ipl"));
}
ossim_set_error(false);

ossim_valid($key, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Key"));
if (ossim_error()) {
    die(_("Invalid Parameter Key"));
}
ossim_set_error(false);

ossim_valid($disp, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report Type"));
if (ossim_error()) {
    die(_("Invalid Report Type"));
}
ossim_set_error(false);

ossim_valid($output, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Output Type"));
if (ossim_error()) {
    die(_("Invalid Output Type"));
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

ossim_valid($chks, "t", "f", OSS_NULLABLE, 'illegal:' . _("Chks"));
if (ossim_error()) {
    die(_("Invalid Chks"));
}

$fp = base64_decode($fp);
//print_r("falso positivo:$fp");
ossim_valid($fp, OSS_NULLABLE, OSS_ALPHA, "\,\.\;\=\(\)\/ \_\-", 'illegal:' . _("False positive"));
if (ossim_error()) {
    die(ossim_error());
}

$nfp = base64_decode($nfp); 
//print_r("no falso positivo:$nfp");

ossim_valid($nfp, OSS_NULLABLE, OSS_ALPHA, "\,\.\;\=\(\)\/ \_\-", 'illegal:' . _("No False positive"));
if (ossim_error()) {
    die(ossim_error());
}

?>
  <script>
  // GrayBox
    $(document).ready(function(){
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,450,'90%');
            return false;
        });
        <? if (isset($chks)) {  // levels "Serious" => "1", "High" => "2", "Medium" => "3", "Low" => "6", "Info" => "7" 
        	  if (substr($chks,0,1)=="f") echo "$('#checkboxS').attr('checked',''); $('.risk1').hide();";
        	  if (substr($chks,1,1)=="f") echo "$('#checkboxH').attr('checked',''); $('.risk2').hide();";
        	  if (substr($chks,2,1)=="f") echo "$('#checkboxM').attr('checked',''); $('.risk3').hide();";
        	  if (substr($chks,3,1)=="f") echo "$('#checkboxL').attr('checked',''); $('.risk6').hide();";
        	  if (substr($chks,4,1)=="f") echo "$('#checkboxI').attr('checked',''); $('.risk7').hide();"; 
        } ?> 
        // show/hide hosts
        $('.hostip').map(function(idx, element){
    	var vall = false;
    	$('tr.trsk',element).each(function(){
            //$(this).log($(this).css('display'))
    		if ($(this).css('display')!='none') vall = true;
    	});
        if (!vall)
            $(element).hide();
        else
            $(element).show();
        });
    });
    function postload() {
    $(".scriptinfo").simpletip({
        position: 'right',
        baseClass: 'gtooltip',
        onBeforeShow: function() { 
            var id = this.getParent().attr('lid');
            this.load('lookup.php?id=' + id);
        }
    });
    $(".checkinfo").simpletip({
        position: 'top',
        baseClass: 'tooltip',
        onBeforeShow: function() { 
            this.update('<?=_("Click to enable/disable risk level view")?>');
        }
    });
  }


  function showFalsePositives() {
    if ($('#checkboxFP').attr('checked')){
        $('.fp').show();
    }
    else {
        $('.fp').hide();
    }
  }

    
  function toggle_vulns (type){
    if(type=="checkboxS"){
        if ($('#checkboxS').attr('checked')){
            $('.risk1').show();
        }
        else {
            $('.risk1').hide();
        }
    }
    else if(type=="checkboxH"){
        if ($('#checkboxH').attr('checked')){
            $('.risk2').show();
        }
        else {
            $('.risk2').hide();
        }
    }
    else if(type=="checkboxM"){
        if ($('#checkboxM').attr('checked')){
            $('.risk3').show();
        }
        else {
            $('.risk3').hide();
        }
    }
    else if(type=="checkboxL"){
        if ($('#checkboxL').attr('checked')){
            $('.risk6').show();
        }
        else {
            $('.risk6').hide();
        }
    }
    else if(type=="checkboxI"){
        if ($('#checkboxI').attr('checked')){
            $('.risk7').show();
        }
        else {
            $('.risk7').hide();
        }
    }
    
    // checking false positives
    if ($('#checkboxFP').attr('checked')){
        $('.fp').show();
    }
    else {
        $('.fp').hide(); 
    }
    
    // show/hide hosts
    $('.hostip').map(function(idx, element){
    	var vall = false;
    	$('tr.trsk',element).each(function(){
            //$(this).log($(this).css('display'))
    		if ($(this).css('display')!='none') vall = true;
    	});
        if (!vall)
            $(element).hide();
        else
            $(element).show();
    });
  }
  function jumptopage(url) {
  	var c1 = $('#checkboxS').is(':checked') ? "t" : "f";
  	var c2 = $('#checkboxH').is(':checked') ? "t" : "f";
  	var c3 = $('#checkboxM').is(':checked') ? "t" : "f";
  	var c4 = $('#checkboxL').is(':checked') ? "t" : "f";
  	var c5 = $('#checkboxI').is(':checked') ? "t" : "f";
  	document.location.href = url+'&chks='+c1+c2+c3+c4+c5
  }
  </script>
<?
//$isReportAdmin = ($uroles['admin'] || $uroles['reports']) ? TRUE : FALSE;

$tmp_fp = array();

if ($ipl!="") { 
    $report_id = $ipl;
    $query_scantime = "select max(scantime) as scantime from vuln_nessus_latest_reports ".(($ipl!="all")? "where report_id=$ipl ":"")." $query_byuser limit 1";
    $result_scantime=$dbconn->Execute($query_scantime);
    $scantime = $result_scantime->fields['scantime'];
}

if($nfp!="") {
    $tmp_fp = explode  (";", $nfp);
    if(count($tmp_fp)>1){
        $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='N' WHERE 1=1 ".
        ((!in_array("admin", $arruser))? " AND username in ('$user') ":"").
        (($tmp_fp[0]!="all") ? "AND report_id in (".$tmp_fp[0].")" : "")." 
         AND hostip='".$tmp_fp[1]."' AND service='".$tmp_fp[2]."' AND risk='".$tmp_fp[3]."' AND scriptid='".$tmp_fp[4]."'");

         $query = "select result_id from vuln_nessus_results t1 
                  LEFT JOIN vuln_nessus_reports t2 on t1.report_id=t2.report_id WHERE 1=1 ".(($tmp_fp[0]!="all") ? "AND t1.report_id in (".$tmp_fp[0].")" : ""). 
                  " AND t1.hostip='".$tmp_fp[1]."' AND t1.service='".$tmp_fp[2]."' AND t1.risk='".$tmp_fp[3]."' AND t1.scriptid='".$tmp_fp[4]."'".
                  ((!in_array("admin", $arruser))? " AND t2.username in ('$user') ":"");
        $result=$dbconn->execute($query);
        while ( !$result->EOF ) {
            list( $result_id ) = $result->fields;
            $dbconn->execute("UPDATE vuln_nessus_results SET falsepositive='N' WHERE result_id ='$result_id'");
            $result->MoveNext();
        }
        
    }
    else {
        $tmp_fp = explode (",", $nfp);
        foreach ($tmp_fp as $value) {
            $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='N' WHERE result_id ='$value'".
            ((!in_array("admin", $arruser))? " AND username in ('$user')":""));
            //echo "1-UPDATE vuln_nessus_latest_results SET falsepositive='N' WHERE result_id ='$nfp'".
            //((!in_array("admin", $arruser))? " AND username='$user'":"");
            
            $query = "select t2.username from vuln_nessus_results t1 
              LEFT JOIN vuln_nessus_reports t2 on t1.report_id=t2.report_id 
              WHERE t1.result_id='$value'";
            $username = $dbconn->GetOne($query);
            //echo "2-$query";
            if(in_array("admin", $arruser) || in_array($username, $arruser))
                $dbconn->execute("UPDATE vuln_nessus_results SET falsepositive='N' WHERE result_id ='$value'");
                //echo "3-UPDATE vuln_nessus_results SET falsepositive='N' WHERE result_id ='$nfp'";
        }
    }
}

if($fp!="") {
    $tmp_fp = explode  (";", $fp);
    if(count($tmp_fp)>1){
        $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='Y' WHERE 1=1 ".
        ((!in_array("admin", $arruser))? " AND username in ('$user') ":"").
                    (($tmp_fp[0]!="all") ? "AND report_id in (".$tmp_fp[0].")" : ""). 
                    " AND hostip='".$tmp_fp[1]."' AND service='".$tmp_fp[2]."' AND risk='".$tmp_fp[3]."' AND scriptid='".$tmp_fp[4]."'");

        $query = "select result_id from vuln_nessus_results t1 
                  LEFT JOIN vuln_nessus_reports t2 on t1.report_id=t2.report_id 
                  WHERE 1=1 ".(($tmp_fp[0]!="all") ? "AND t1.report_id in (".$tmp_fp[0].")" : "").
                  " AND t1.hostip='".$tmp_fp[1]."' AND t1.service='".$tmp_fp[2]."' AND t1.risk='".$tmp_fp[3]."' AND t1.scriptid='".$tmp_fp[4]."'".
                  ((!in_array("admin", $arruser))? " AND t2.username in ('$user') ":"");
        //print_r($query);
        $result=$dbconn->execute($query);
        while ( !$result->EOF ) {
            list( $result_id ) = $result->fields;
            $dbconn->execute("UPDATE vuln_nessus_results SET falsepositive='Y' WHERE result_id ='$result_id'");
            $result->MoveNext();
        }
        
    }
    else {
        $tmp_fp = explode (",", $fp);
        foreach ($tmp_fp as $value) {
            $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='Y' WHERE result_id ='$value'".
            ((!in_array("admin", $arruser))? " AND username in ('$user')":""));
            /*echo "--UPDATE vuln_nessus_latest_results SET falsepositive='Y' WHERE result_id ='$fp'".
            ((!in_array("admin", $arruser))? " AND username='$user'":"");*/
            
            $query = "select t2.username from vuln_nessus_results t1,vuln_nessus_reports t2 WHERE t1.report_id=t2.report_id
              AND t1.result_id='$value'";
            $username = $dbconn->GetOne($query);
            
            if(in_array("admin", $arruser) || in_array($username, $arruser)) { 
                $dbconn->execute("UPDATE vuln_nessus_results SET falsepositive='Y' WHERE result_id ='$value'");
               // echo "--UPDATE vuln_nessus_results SET falsepositive='Y' WHERE result_id ='$fp'";
            }
        }
    }
}


//if ( $output != "printable" ) {
//	require_once('header2.php');
//}


function navbar ( $output ) {
   global $disp, $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, 
   $filterip, $query_risk, $likefilter, $dbconn, $treport;
 
      $arr = array("full", "summary", "optimized", "printable", "min");
      if ( $output == "" ) { $output = "summary"; }

      echo <<<EOT
   <br><center><table cellspacing="2" cellpadding="0" width="500" border="0">
   <form action="reshtml.php" method="post">
   <INPUT TYPE=HIDDEN NAME="disp" VALUE="html">
   <INPUT TYPE=HIDDEN NAME="scantime" VALUE="$scantime">
   <INPUT TYPE=HIDDEN NAME="scantype" VALUE="$scantype">
   <INPUT TYPE=HIDDEN NAME="fp" VALUE="$fp">
   <INPUT TYPE=HIDDEN NAME="nfp" VALUE="$nfp">
EOT;

      if ( $filterip ) {
         echo "<INPUT TYPE=HIDDEN NAME=\"filterip\" VALUE=\"$filterip\">";
      }
      echo <<<EOT
   <tr height="20"><th align="Right" width="30%" class="noborder">REPORT FORMAT:</th>
   <td align=left class="noborder"><SELECT NAME="output">
EOT;

      foreach ( $arr as $value) {
         if ( $output == $value ) {
            echo "<OPTION VALUE=\"$value\" SELECTED>". strtoupper($value) . "</OPTION>";
         } else {
            echo "<OPTION VALUE=\"$value\">". strtoupper($value) . "</OPTION>";
         }
      }


   echo "</SELECT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"submit\" value=\""._("Reload Report")."\" class=\"btn\">";
      echo <<<EOT
   </td></tr>
</table>\n</form>
</center>
EOT;

}

function generate_results($output){
   
   global $user, $border, $report_id, $sid, $scantime, $scansubmit, $scantype, $fp, $nfp, $output, $filterip, 
   $query_risk, $dbconn, $treport, $ipl, $key, $query_byuser;

    if($report_id!="") {
        $query = "SELECT sid FROM vuln_nessus_latest_reports WHERE 1=1".(($report_id!="all")? " AND report_id=$report_id":"")." $query_byuser";
        //echo $query;
        $result=$dbconn->execute($query);
        while ( !$result->EOF ) {
            list( $sid ) = $result->fields;
            $sids[] = $sid;
            $result->MoveNext();
        }
        $sid = implode(",",$sids);
    }
    else {
       if ($scansubmit!="" && $treport!="latest") {
           $query = "SELECT r.report_id, r.sid FROM vuln_nessus_reports r,vuln_jobs j WHERE r.report_id=j.report_id AND j.scan_SUBMIT='$scansubmit'".((in_array("admin", $arruser))? "" : " AND r.username in ('$user') ");
           //print_r($query);
           $result=$dbconn->execute($query);
           while ( !$result->EOF ) {
                list( $report_id, $sid ) = $result->fields;
                $ids[] = $report_id;
                $result->MoveNext();
           }
           $report_id = implode(",",$ids);
       
       } else{   
           $query = "SELECT report_id, sid FROM ".(($treport=="latest")? "vuln_nessus_latest_reports" : "vuln_nessus_reports")." WHERE ".(($treport=="")? "scantime='$scantime'":"report_key=$key" )."
                 AND scantype='$scantype' $query_byuser LIMIT 1";
           //echo $query;
           $result=$dbconn->execute($query);
           list( $report_id, $sid ) = $result->fields;
        }
    }
   //echo $query;
   //echo "sid=$sid<br>";
   //echo "report_id=$report_id<br>";

   $ip = $_SERVER['REMOTE_ADDR'];

   logAccess( strtoupper($output) . " HTML REPORT [ $report_id ] ACCESSED" );
   
   echo "";
   //var_dump($output);
   switch($output) {
   
    

         case "full" :
	  //echo "navbar-".navbar($output)."\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
	  //echo "reportsummary-".reportsummary()."\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
      
      echo "".reportsummary();
      //echo "".navbar($output);
      echo "". vulnbreakdown();
      echo "". hostsummary();
      echo "". origdetails();
      
      break;

         case "detailed" :
      echo "". reportsummary();
      //navbar ( $output );
      #echo "". detailedresults();
      break;

         case "summary" :
      echo "". reportsummary();
      //navbar ( $output );
      echo "". vulnbreakdown();
      echo "". hostsummary();
      

      break;


         case "printable" :
      $border=0;
      echo "". reportsummary();
      //navbar ( $output );
      echo "". vulnbreakdown();
      #echo "". atrisksummary();
      echo "". hostsummary();
      echo "". vulndetails();
      break;

         case "min" :
      #$border=0;
      $query_risk = "AND risk <= '3' ";
      echo "". reportsummary();
      //navbar ( $output );
      echo "". vulnbreakdown();
      #echo "". atrisksummary();
      echo "". hostsummary();
      echo "". vulndetails();
      break;

         case "optimized" :
      echo "". reportsummary();
      //navbar ( $output );
      echo "". vulnbreakdown();
      echo "". hostsummary();
      echo "". vulndetails();
      
      break;

          default:
      echo "". reportsummary();
      //navbar ( $output );
      echo "". vulnbreakdown();
      echo "". hostsummary();
      echo "". origdetails();
      

      break;

    }
	echo "";
}

function reportsummary( ){   //GENERATE REPORT SUMMARY

   global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn, $pluginid;
   global $treport, $sid, $ipl;
   
   $htmlsummary = "";
   
   if($treport=="latest" || $ipl!="")
        $query = "SELECT t2.id, t1.username, t1.name, t2.name, t2.description, t4.hostname as host_name 
            FROM vuln_nessus_latest_reports t1
            LEFT JOIN vuln_nessus_settings t2 on t1.sid=t2.id
            LEFT JOIN host t4 ON t4.ip=inet_ntoa(t1.report_id)
            WHERE ".(($ipl!="all")?"t1.report_id in ($report_id) and ":"")."t1.sid in ($sid) AND t1.username in ('$user')
            order by t1.scantime DESC";
   else
        $query = "SELECT t2.id, t1.username, t1.name, t2.name, t2.description 
                    FROM vuln_jobs t1
                    LEFT JOIN vuln_nessus_settings t2 on t1.meth_VSET=t2.id
                    WHERE t1.report_id in ($report_id) AND t1.username in('$user')
                    order by t1.SCAN_END DESC";
   $result=$dbconn->execute($query);
   
   if ($treport=="latest" || $ipl!="") {
        //list( $id_profile, $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) =$result->fields;
        $lprofiles = array();
        $tmp_profiles = array();
        while (list( $id_profile, $query_uid, $job_name, $profile_name, $profile_desc, $host_name ) =$result->fields) {
            if($host_name!="" && $host_name!=long2ip($report_id)) { $phost_name = "$host_name (".long2ip($report_id).")"; }
            else { $phost_name = long2ip($report_id); }
            $lprofiles[] = "$profile_name - $profile_desc";
            $tmp_profiles[] = $id_profile;
            $result->MoveNext();
        }
        $profiles = implode("<br>", $lprofiles);
        $id_profile = implode(", ", $tmp_profiles);
   }
   else {
        list( $id_profile, $query_uid, $job_name, $profile_name, $profile_desc ) = $result->fields;
        if($job_name=="") { // imported report
           $query_imported_report = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime'";
           $result_imported_report=$dbconn->execute($query_imported_report);
           $job_name = $result_imported_report->fields["name"];
        }
   }

    $htmlsummary .= "<table border=\"5\" width=\"900\"><tr><th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
         
         <b>Scan time:</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">". gen_strtotime($scantime,"")."&nbsp;&nbsp;&nbsp;</td>";

    //Generated date
    $gendate = date("Y-m-d H:i:s");

    $htmlsummary .= "<th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
         <b>"._("Generated").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">$gendate</td></tr>";

if ($ipl!="all") {
        if ($treport=="latest" || $ipl!="") {
            $htmlsummary .= "<tr><th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>".((count($lprofiles)>1) ? _("Profiles") : _("Profile")).":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">";
            $htmlsummary .= "$profiles&nbsp;&nbsp;&nbsp;</td>
                <th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>".(($treport=="latest" || $ipl!="")? _("Host - IP") : _("Job Name")).":</b></th><td class=\"noborder\" valign=\"top\" style=\"text-align:left;padding-left:10px;\">".(($treport=="latest" || $ipl!="")? "$phost_name" : "$job_name")."</td></tr>";
        }
        else {
            $htmlsummary .= "<tr><th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>"._("Profile").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">";
            $htmlsummary .= "$profile_name - $profile_desc&nbsp;&nbsp;&nbsp;</td>
                <th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>"._("Job Name").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">$job_name</td></tr>";
        }
    }
    $htmlsummary.= "</table>";
    
/*
    if($pluginid!="") {
        if($fp!=""){
            $dbconn->execute("UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid in ($id_profile) and id='$pluginid'");
        }
        else {
            $dbconn->execute("UPDATE vuln_nessus_settings_plugins SET enabled='Y' WHERE sid in ($id_profile) and id='$pluginid'");
        }
    }
    */
    

   return "<center>".$htmlsummary."</center>";
}

function vulnbreakdown(){   //GENERATE CHART
   global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
   global $treport, $sid, $ipl;
   global $query_byuser, $arruser;

   $htmlchart = "";
   $query_host = "";
   if ( $filterip ) { $query_host = " AND hostip='$filterip'"; }

   if ($ipl=="all") {
        $query ="select count(*) as total,risk 
                    from (select distinct port,protocol,app,scriptid,risk,hostIP from vuln_nessus_latest_results where falsepositive='N' $query_byuser)
                    as t group by risk";
   }
   
   else if ($ipl!="") {
        $query = "select count(*) as total,risk from (select distinct port,protocol,app,scriptid,risk
        from vuln_nessus_latest_results where falsepositive='N'".(($ipl!="all")?" and report_id=$report_id":"")." $query_byuser) as t group by risk";
   }
   else {/*
        $query = "SELECT count(risk) as count, risk
            FROM `".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."`
            WHERE report_id in ($report_id) $query_host
            AND falsepositive<>'Y'
            AND scriptid <> 10180".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")."
            GROUP BY risk";*/
        $query = "SELECT count(risk) as count, risk
                FROM `".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."`
                WHERE report_id in ($report_id) $query_host".((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : "")."
                AND scriptid <> 10180".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")." AND falsepositive='N'".
                (($scantime!="")? " AND scantime=$scantime":"")." GROUP BY risk";
    }
    
   $result=$dbconn->Execute($query);

   //print_r($query);
   $prevrisk=0;
   $chartimg="./graph1.php?graph=1";

   while (list($riskcount, $risk)=$result->fields) {
         for ($i=0;$i<$risk-$prevrisk-1;$i++) {
      $missedrisk=$prevrisk+$i+1;
      $chartimg.="&amp;risk$missedrisk=0";
         }
         $prevrisk=$risk;
         $chartimg.="&amp;risk$risk=$riskcount";
         $result->MoveNext();
   }

   // print out the pie chart
   if($prevrisk!=0)
        $htmlchart .= "<font size=\"1\"><br></font>
            <img alt=\"Chart\" src=\"$chartimg\"><br>";
   else
        $htmlchart = "<br><span style=\"color:red\">"._("No vulnerabilty data")."</span>";

   return "<center>".$htmlchart."</center>";
}

function hostsummary( ){
   global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
   global $treport, $sid, $ipl, $query_byuser, $arruser, $ips_inrange, $pag;

   $htmldetails = "";
   $query_host = "";
   if ( $filterip ) { $query_host = " AND hostip='$filterip'"; }

   $htmldetails .= "<br><br><font color=\"red\">
         <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"900\"><tr><td class=\"headerpr\" style=\"border:0;\"><b>"._("Summary of Scanned Hosts")."</b></td></tr></table>
         <table summary=\""._("Summary of scanned hosts")."\" width=\"900\">";
   $htmldetails .= "<form>";

   $htmldetails .= "<tr><th width=\"128\"><b>"._("Host")."&nbsp;&nbsp;</b></th>
         <th width=\"128\"><b>"._("Hostname")."&nbsp;&nbsp;</b></th>
         <td width=\"128\" style='background-color:#FFCDFF;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #C835ED;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Serious")."&nbsp;&nbsp;</b>
                    </td>
                    <td class=\"checkinfo nobborder\" width=\"20%\">
                    <input id=\"checkboxS\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxS')\" checked>
                    </td>
                </tr>
            </table>
         </td>
         <td width=\"128\" style='background-color:#FFDBDB;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FF0000;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("High")."&nbsp;&nbsp;</b>
                    </td>
                    <td class=\"checkinfo nobborder\" width=\"20%\">
                    <input id=\"checkboxH\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxH')\" checked>
                    </td>
                </tr>
            </table>
        </td>
        <td width=\"128\" style='background-color:#FFF283;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFA500;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Medium")."&nbsp;&nbsp;</b>
                    </td>                    
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxM\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxM')\" checked>
                    </td>
                </tr>
            </table>
         </td>
        <td width=\"128\" style='background-color:#FFFFC0;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFD700;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Low")."&nbsp;&nbsp;</b>
                    </td>                    
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxL\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxL')\" checked></td>
                    </td>
                </tr>
            </table>
        </td>
        <td width=\"132\" style='background-color:#FFFFE3;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #F0E68C;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Info")."&nbsp;&nbsp;</b>
                    </td>
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxI\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxI')\" checked>
                    </td>
                </tr>
            </table>
        </td></tr>";
    $htmldetails .= "</form>";

    /*$query = "SELECT distinct hostip, hostname
          FROM vuln_nessus_results
         WHERE report_id = '$report_id' $query_host
         ORDER BY INET_ATON(hostip) ASC";*/
    if($ipl=="all"){
        $query = "SELECT distinct t1.hostip, t2.hostname
         FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
         LEFT JOIN host t2 on t1.hostip = t2.ip
         WHERE".(($treport=="latest" || $ipl!="")? " sid in ($sid)" : "")." AND falsepositive='N'".
         ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "")." ORDER BY INET_ATON(hostip) ASC";
    }
    else {
        $query = "SELECT distinct t1.hostip, t2.hostname
         FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
         LEFT JOIN host t2 on t1.hostip = t2.ip
         WHERE report_id in ($report_id) $query_host".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")." and falsepositive='N'".
         (($scantime!="")? "and scantime = $scantime":"").
         ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND t1.username in ('$user') " : "")." ORDER BY INET_ATON(hostip) ASC";
    }
   //echo $query;
   $result=$dbconn->execute($query);

   $maxpag = 20;
   $hasta = $pag*$maxpag;
   $desde = $hasta - $maxpag;
   $hi=0;
   while(list( $hostip, $hostname ) = $result->fields) {
      if($hostname=="") $hostname=$hostip;
      if ($desde <= $hi && $hi < $hasta) $ips_inrange[$hostip] = $hostname;
      $result->MoveNext();
      $hi++;
   }
      
   foreach ($ips_inrange as $hostip => $hostname) {
      if ( $output == "full" ) {
         $tmp_host = "<a href=\"#$hostip\" id=\"$hostip;$hostname\" class='HostReportMenu'>$hostip</a>";
      } else {
         $tmp_host = $hostip;
      }

      $htmldetails .= "<tr>
         <td>$tmp_host&nbsp;</td><td>$hostname&nbsp;</td>";
      $prevrisk=0;
      if ($ipl=="all"){
            $query2 = "select count(*) as total,risk from (select distinct port, hostIP, protocol,app,scriptid,risk 
                      from vuln_nessus_latest_results where report_id=inet_aton('$hostip') and falsepositive='N' $query_byuser) as t group by risk";
      }
      else if($ipl!="") {
        $query2 = "select count(*) as total,risk from (select distinct port,protocol,app,scriptid,risk 
                  from vuln_nessus_latest_results where report_id=$report_id and falsepositive='N' $query_byuser) as t group by risk";
      }
      else {
            /*$query2 = "SELECT count(risk) as count, risk
                        FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
                        WHERE report_id  in ($report_id) AND hostip='$hostip' 
                        AND falsepositive<>'Y' AND scriptid <>10180".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")."
                        GROUP BY risk";*/
            $query2 = "SELECT count(risk) as count, risk
                        FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
                        WHERE report_id  in ($report_id) AND hostip='$hostip' 
                        AND scriptid <>10180".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")." and falsepositive='N'".
                        (($scantime!="")? "and scantime = $scantime":"").
                        ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : "")." GROUP BY risk";
      }
      $drawtable=0;

      $result2=$dbconn->execute($query2);

      $arisk = array();
      while( list( $riskcount, $risk ) = $result2->fields ) {
           if ($risk == 4) $arisk[3] +=  $riskcount;
           else if ($risk == 5) $arisk[6] +=  $riskcount;
           else 
                $arisk [$risk] = $riskcount;
            $result2->MoveNext();
      }
      $lsrisk = array('1','2','3','6','7');

      foreach ($lsrisk as $lrisk) {
        if($arisk[$lrisk]!=""){
            $drawtable=1;
            $htmldetails .= "<td><a href=\"#".$hostip."_".$lrisk."\">$arisk[$lrisk]</a></td>";
        }
        else    $htmldetails .= "<td>-</td>";
      }

      if ($drawtable==0) {
         $htmldetails .= "<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>"; 
      }
      $htmldetails .= "</tr>";
   }
   
   if ($hi>=$maxpag) {
   	 // pagination
     $first = "<font color=\"#626262\"><< "._("First")."</font>";
     $previous = "<font color=\"#626262\">< "._("Previous")."  </font>"; 
   	 $url = preg_replace("/\&pag=\d+|\&chks=[tf]+/","",$_SERVER["QUERY_STRING"]);
     if ($pag>1) {
        $first = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=1')\" style='padding:0px 5px 0px 5px'>"._("<< First")."</a>";
        $previous = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".($pag-1)."')\" style='padding:0px 5px 0px 5px'>"._("< Previous")."</a>";
     }
   	 //$htmldetails .= "<tr><td colspan=11 class='nobborder' style='text-align:right'><b>"._("Page:")."</b> ";
     $htmldetails .= "<tr><td colspan=11 class='nobborder' style='text-align:right'>";
   	 $tp = intval($hi/$maxpag); $tp += ($hi % $maxpag == 0) ? 0 : 1;
     $htmldetails .= $first." ".$previous;
         $pbr = 1;
   	 for ($p=1;$p<=$tp;$p++) {
   	    $pg = ($p==$pag) ? "<b>$p</b>" : $p;
            $htmldetails .= "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=$p')\" style='padding:0px 5px 0px 5px'>$pg</a>";
            if ($pbr++ % 30 == 0) $htmldetails .= "<br>";
   	 }
     $next = "<font color=\"#626262\">  "._("Next")." ></font>";
     $last = "<font color=\"#626262\"> "._("Last")." >></font>";

     if ($pag<$tp) {
        $next = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".($pag+1)."')\" style='padding:0px 5px 0px 5px'>"._("Next >")."</a>";
        $last = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".$tp."')\" style='padding:0px 5px 0px 5px'>"._("Last >>")."</a>";
     }
     $htmldetails .= $next." ".$last;
   	 $htmldetails .= "</td></tr>";
   }
   $htmldetails .= "</table><br>";

   return "<center>".$htmldetails."</center>";

}

function vulndetails( ){
   global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
   global $treport, $ipl;

   $host_list = array();

   $htmldetails = "";
   $query_host = "";
   if ( $filterip ) { $query_host = " AND hostip='$filterip'"; }

   if ( $output == "original" ){
         $query_order = "group by hostip order by risk";
   } else {
         $query_order = "order by risk,scriptid,hostip";
   }

   $query = "SELECT risk, scriptid, service, msg, hostip
         FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
         WHERE ".(($ipl!="all")?"report_id='$report_id'":"").(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ").
         ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : " ").$query_risk.$query_host.$query_order;

   #echo "query=$query<br>";

   $result = $dbconn->execute($query);

   if ( $result->RecordCount() != 0 ) {
         $htmldetails .= "
      <br><hr><br><font color=\"red\">
      <b><big>"._("Summary of Vulnerabilities By Risk")."</big></b></font>
      <br><table width=\"100%\" summary=\"Summary of risks\" border=\"$border\"
      cellspacing=\"0\" cellpadding=\"0\" style=\"border-collapse: collapse\">";
   } else {
         $htmldetails .= "<br><hr><br><font color=\"red\">
      <b><big>"._("Serious Vulnerabilities Risks").":</big></b></font><br><br>
      <b>"._("NONE")."</b>";
         return $htmldetails;
   }

   $lastid=0;
   $scriptid=0;


   while( list( $risk, $scriptid, $service, $msg, $host ) = $result->fields ) {
      if ($scriptid != $lastid ) {

         if ( is_array($host_list)) {
            foreach ($host_list as $key => $value) {
                $htmldetails .= "<tr><td>$key</td></tr>";
            }
            reset($host_list);
         }

         $query2 = "select note from nessus_notes where pid='$scriptid'";
         $result_note = $dbconn->execute($query2);

         if (  $result_note->RecordCount() != 0 ) {
            $msg.="\n\n<FONT COLOR=\"#0044FF\"><B>"._("Custom Notes").":</B>";
            $note_num=1;
            while(list( $customnote ) = $result_note->fields) {
               $msg.="\n$note_num. $customnote";
               $note_num++;
               $result_note->MoveNext();
            }
            $msg.="</FONT>";
         }

         $msg=preg_replace("/^[ \t]*/","",$msg);
         $msg=wordwrap(preg_replace("/\n/","<br>",$msg),100,"<br>",1);
         $msg=activateHyperlink($msg);

         if ($lastid != 0) {
            $htmldetails .= "<tr>
               <td>&nbsp;</td></tr>
               <tr><td><hr></td></tr>
               <tr><td>&nbsp;</td></tr>";
         }

         $htmldetails .= "<tr><td><b>"._("RISK")."</b></td></tr>
            <tr><td>".getrisk($risk)."</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td><b>"._("PLUGIN")."</b></td></tr>
            <tr><td>$scriptid</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td><b>"._("SERVICE")."</b></td></tr>
            <tr><td>$service</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td><b>"._("DETAILS")."</b></td></tr>
            <tr><td>$msg</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td><b>"._("VULNERABLE HOSTS").":</b></td></tr>";
         $host_list[$host] = 1;
         $lastid=$scriptid;
      } else {
         if ( $scriptid != " ") {
            $host_list[$host] = 1;
         }
      }
      $result->MoveNext();
   }

   if ( !empty($host_list)) {
      foreach ($host_list as $key => $value) {
       $htmldetails .= "<tr><td>$key</td></tr>";
      }
      reset($host_list);
   }

    $htmldetails .= "</table><br>";
    return $htmldetails;
}

function origdetails( ) {
   global $uroles, $user, $sid, $query_risk, $border, $report_id, $scantime, $scantype, $fp, $nfp, $filterip,
   $enableFP, $enableNotes, $enableException, $output, $sortby, $dbconn, $arruser;
   global $treport, $ipl, $query_byuser, $ips_inrange;
   
   $enableException=0;

   $colors = array ("Serious" => "#FFCDFF", "High" => "#FFDBDB", "Medium" => "#FFF283", "Low" => "#FFFFC0", "Info" => "#FFFFE3");
   $images = array ("Serious" => "./images/risk7.gif", "High" => "./images/risk6.gif", "Medium" => "./images/risk3.gif", "Low" => "./images/risk2.gif", "Info" => "./images/risk1.gif");
   $levels = array("Serious" => "1", "High" => "2", "Medium" => "3", "Low" => "6", "Info" => "7");
   
   $query_host = "";
   if ( $filterip ) { $query_host = " AND hostip='$filterip'"; }   
   echo "<center>";
   echo "<form>";
   echo "<table width=\"900\" class=\"noborder\" style=\"background:transparent;\">";
   echo "<tr><td style=\"text-align:left;\" class=\"nobborder\">";
   echo "<input id=\"checkboxFP\" type=\"checkbox\" onclick=\"showFalsePositives()\"> <span style=\"color:black\">"._("View false positives")."</span>";
   echo "</td><td class=\"nobborder\" style=\"text-align:center;\">";
   // print the icon legend
   if ($enableFP) {
         echo "<img alt='True' src='images/true.gif' border=0 align='absmiddle'> - "._("True result")."&nbsp;&nbsp;";
         echo "<img alt='False' src='images/false.png' border=0 align='absmiddle'> - "._("False positive result")."&nbsp;&nbsp;";
   }
   if ($enableNotes) {
         echo "<img alt='Note' src='images/note.png' border=0 align='absmiddle'> - "._("Add a custom note")."&nbsp;&nbsp;";
   }
   echo "<img alt='Info' src='images/info.png' border=0 align='absmiddle'> - "._("Additional information is available");
   echo "</td></tr></table>";
   echo "</form>";
   echo "<br>";

   //$query ="select distinct hostip, hostname from vuln_nessus_results where report_id='$report_id' $query_host order by INET_ATON(hostip) ASC";
   if ($ipl=="all") {
       $query = "SELECT distinct t1.hostip, t2.hostname
         FROM vuln_nessus_latest_results t1
         LEFT JOIN host t2 on t1.hostip = t2.ip ".((in_array("admin", $arruser))? "":"WHERE username in ('$user') ")."ORDER BY hostip ASC";
   }
   else {
       $query = "SELECT distinct t1.hostip, t2.hostname
         FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." t1
         LEFT JOIN host t2 on t1.hostip = t2.ip
         WHERE report_id in ($report_id) ".(($treport=="latest" || $ip!="")? " and sid in ($sid)" : " ")." $query_host".
         ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : " ")."ORDER BY INET_ATON(hostip) ASC";
    }

   $resultp = $dbconn->execute( $query );

   $host_range = array_keys($ips_inrange);
   while( list($hostip, $hostname) = $resultp->fields) {
      if($hostname=="") $hostname="unknown";
      if (in_array($hostip,$host_range)) {

      if($output=="min") { 
         echo "<h3>"._("Details for Serious, High, Medium and Medium/Low severity risks only").".</h3>"; 
      }
      echo "<div class='hostip'>";
      echo <<<EOT
<br><font color="red"><b><a name="$hostip">$hostip - $hostname</a></b></font>
EOT;
      echo "<table summary=\"$hostip - "._("Reported Ports")."\">";
      echo "<tr><th colspan=2>"._("Reported Ports")."</th></tr>";


 // get the "open ports" this replaced an approroacj requiring risk 7 and an empty msg cell
   if($ipl=="all"){
        $query = "SELECT DISTINCT `port` , `protocol` FROM vuln_nessus_latest_results 
   		WHERE hostip='$hostip' $query_byuser AND port > '0' ORDER BY port ASC";
   }
   else {
   $query = "SELECT DISTINCT `port` , `protocol` FROM `".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."` 
   		WHERE report_id in ($report_id)".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ").
        (($scantime!="")? " AND scantime=$scantime":"").
        ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : " ")." AND hostip='$hostip' AND port > '0' ORDER BY  port ASC";
   }
   $result1=$dbconn->execute($query);

   //$arrResults="";
      $k=1;
      $pos = "";
      if (! $result1->fields)
      {
        print "<tr><td>"._("No reported ports found")."</td></tr>";
        } else {
              while( list( $port, $proto) = $result1->fields ) {
                    if($k % 2) {
                         echo "<tr><td>$port/$proto</td>";
                         $pos = "open";
                  } else {
                       echo "<td>$port/$proto</td></tr>";
                       $pos = "closed";
                    }
                    $k++;
                 $result1->MoveNext();
                  } // end while
                 // close up the table
                 if($pos!="closed") {
                 echo "<td>&nbsp;</td></tr>";
                    }
    }
      echo "</table><p></p>";

   echo <<<EOT
<table width="900" summary="$hostip - risks">
<tr>
EOT;

echo "<th>"._("Service")."</th>";
echo "<th>"._("Severity")."</th>";
echo "<th>"._("PluginID")."</th>";
echo "<th>"._("Description")."</th>";
echo "</tr>";

    if($ipl=="all"){
        $query = "select distinct 0, r.service, r.risk, r.falsepositive, r.scriptid, v.name from vuln_nessus_latest_results as r
                LEFT JOIN vuln_nessus_plugins as v ON v.id=r.scriptid
                WHERE hostip='$hostip' $query_byuser and msg<>''";
        $query_msg = "select r.msg from vuln_nessus_latest_results as r
                LEFT JOIN vuln_nessus_plugins as v ON v.id=r.scriptid
                WHERE hostip='$hostip' $query_byuser and msg<>'' ORDER BY r.scantime DESC LIMIT 0,1";
    }
    else if ($treport=="latest" || $ipl!=""){
     /* $query = "select distinct 0, service, risk, falsepositive, msg, scriptid, result_id from ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." 
                WHERE report_id in ($report_id)".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")." and hostip='$hostip' and msg<>''".
                (($scantime!="" && $ipl=="")? " AND scantime=$scantime":"").
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username='$user' " : "");*/
      $query = "select distinct 0, r.service, r.risk, r.falsepositive, r.scriptid, v.name from vuln_nessus_latest_results as r
                LEFT JOIN vuln_nessus_plugins as v ON v.id=r.scriptid
                WHERE report_id in ($report_id) and sid in ($sid) and hostip='$hostip' and msg<>''".
                (($scantime!="" && $ipl=="")? " AND scantime=$scantime":"").
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : "");
       $query_msg = "select r.msg from vuln_nessus_latest_results as r
                LEFT JOIN vuln_nessus_plugins as v ON v.id=r.scriptid
                WHERE report_id in ($report_id) and sid in ($sid) and hostip='$hostip' and msg<>''".
                (($scantime!="" && $ipl=="")? " AND scantime=$scantime":"").
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : " ")."ORDER BY r.scantime DESC LIMIT 0,1";
    }
    else {
              $query = "select distinct 0, t1.service, t1.risk, t1.falsepositive, t1.scriptid, v.name from vuln_nessus_results t1
                LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                WHERE report_id in ($report_id) and hostip='$hostip' and msg<>''".
                (($scantime!="" && $ipl=="")? " AND scantime=$scantime":"").
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : "");
                
              $query_msg = "select t1.msg from vuln_nessus_results t1
                LEFT JOIN vuln_nessus_plugins as v ON v.id=t1.scriptid
                WHERE report_id in ($report_id) and hostip='$hostip' and msg<>''".
                (($scantime!="" && $ipl=="")? " AND scantime=$scantime":"").
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : " ")."ORDER BY t1.scantime DESC LIMIT 0,1";
    }
      //echo $scantime;
      //echo "bucle:$query";
      // for minimized output, don't include risk=[5|6|7]
      if($output=="min") {
         $query.=" and risk NOT IN( '7', '6', '5')";
      }
      $query.=" order by risk";

      $result1=$dbconn->execute($query);

      $arrResults="";

      while(list( $result_id,
                  $service, 
                  $risk, 
                  $falsepositive, 
                  $scriptid,
                  $pname) = $result1->fields ) {
         $msg = get_msg($dbconn,$query_msg); // to avoid same messages
         $tmpport1=preg_split("/\(|\)/",$service);
         if (sizeof($tmpport1)==1) { $tmpport1[1]=$tmpport1[0]; }
   #echo "$tmpport1[0] $tmpport1[1]<BR>";
         $tmpport2=preg_split("/\//",$tmpport1[1]);
   #echo "$tmpport2[0] $tmpport2[1]<BR>";
         $service_num=$tmpport2[0];
         $service_proto=$tmpport2[1];
   
         $arrResults[]=array( $service_num, 
                              $service_proto, 
                              $service,
                              $risk, 
                              $falsepositive, 
                              $result_id, 
                              $msg, 
                              $scriptid,
                              $pname);
         $result1->MoveNext();
      }

      if(!empty($arrResults)) {
         //uasort ($arrResults, 'arrScanResultsCompare');
      } else { // empty, print out message
         echo "<td colspan=3>"._("No vulnerability results matching this reports 
               filtering criteria were found").".</td></tr>";
      }
    
      foreach ($arrResults as $key=>$value) {
         list( $service_num, 
               $service_proto, 
               $service, 
               $risk, 
               $falsepositive, 
               $resid, 
               $msg, 
               $scriptid,
               $pname) = $value;

// No need to do this anymore as the HTML entities are converted when
// importing the results
//            $msg=htmlspecialchars($msg);

         // Print Notes associated with this result (resid)
         // modified to remove username filter - will tag the note with the
         // username which we now get in the results
         if ($enableNotes) {
         	$query = "select note, username FROM nessus_notes WHERE pid=$scriptid and resid = $resid";
            $result_note=$dbconn->execute($query);
                                                //and username='$user'");
            //The next line breaks for upgrade installs
            //$notes=$result_note->GetArray();
            if ( !empty( $result_note) ) {
               $msg.='<p><FONT COLOR="#0044FF"><B>'._("Custom Notes").':</B>';
               foreach ($result_note as $note_num=>$customnote) {
                  //list($customnote)=$result_note->fields;
                  $note_num++; // do this as the index starts at 0
                  $msg.="\n$note_num. [$customnote[username]] - $customnote[note]";
                  //$note_num++;
                  //$result_note->MoveNext();
               }
               $msg.="</FONT></p>";
            }
         } // end Print Notes

         $msg=preg_replace("/^[ \t]*/","",$msg);
         $msg=preg_replace("/\n/","<br>",$msg);
//         $tr = array("\\n" => "<br>");
//         $msg=strtr($msg,$tr);
         //$msg=wordwrap(preg_replace("/\n/","<br>",$msg),100,"<br>",1);
         $msg=wordwrap($msg,100,"<br>",1);

         // Add Exceptions
         //if ($enableException && $risk <= 6) {
         if ($enableException) {
            $msg .= "<p><FONT COLOR='#0044FF'><b>"._("Exceptions").":</b><br>";
            if ($uroles['eview'] || $uroles['esubmit'] || $uroles['eapprove']) {
               $msg .= printException($hostip, $scriptid, $dbconn, FALSE,
                                      $hostname, $service_num, $sid );
            }
            //if ($esubmit) {
            //   $msg .= addException($hostip,$resid,$scriptid,$schedid,$hostname);
            //}
         } // end Exceptions

         $tmprisk = getrisk($risk);

         $msg = preg_replace("/^\<br\>/i","",str_replace("\\r", "", $msg));
         $msg = preg_replace("/(Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)\s*:/","<b>\\1:</b>",$msg);
 
         // output the table cells
         $ancla = $hostip."_".$levels[$tmprisk];

echo "<tr ".(($falsepositive=='Y')? "class=\"trsk risk$risk fp\"" : "class=\"trsk risk$risk\"")."style=\"background-color:".$colors[$tmprisk].(($falsepositive=='Y')? ";display:none;" : "")."\">";
//echo "<tr>";

echo "<td>$service</td>";

echo "<td>$tmprisk&nbsp;&nbsp;<img align=\"absmiddle\" src=\"".$images[$tmprisk]."\" style=\"border: 1px solid ; width: 25px; height: 10px;\"></td>";
         echo <<<EOT
<td>$scriptid</td>
<td width="70%" style="text-align:left;">
<A class="msg" NAME="$resid"></a><a name="$ancla"></a>
<p align="center" style="font-weight:bold">$pname</p>$msg
<font size="1">
<br><br>
</font>
EOT;
         // Add info from osvdb
         echo "&nbsp;&nbsp;<a title=\""._("Info from OSVDB for plugin id ")."$scriptid\" class=\"greybox\" href=\"osvdb_info.php?scriptid=$scriptid\"><img src=\"images/osvdb.png\" border=\"0\"></a>&nbsp;&nbsp;";
         // Add link to popup with Script Info
         echo <<<EOT
<a href="javascript:;" lid="$scriptid" class="scriptinfo"><img alt="Info" src="images/info.png" border=0></a>
EOT;
         // Add Custom Notes icon
         // don't filter on username - any user can add a note to any result
         //if ($sql_uid==$user) {
         if($enableNotes) {
            if ($output=="min") {
               echo <<<EOT
&nbsp;&nbsp;
<a href="notes.php?op=add&amp;pid=$scriptid&scantime=$scantime&scantype=$scantype&sortby=$sortby&resid=$resid&httpfrom=resmin" 
  onClick="popup('notes.php?op=add&amp;pid=$scriptid&scantime=$scantime&scantype=$scantype&sortby=$sortby&resid=$resid&httpfrom=resmin','Notes'); 
  return false;"><img alt="Note" src="images/note.png" title="Add note" border=0></a>
EOT;
            } else {
               echo <<<EOT
&nbsp;&nbsp;
<a href="notes.php?op=add&amp;pid=$scriptid&scantime=$scantime&scantype=$scantype&sortby=$sortby&resid=$resid&httpfrom=results" 
  onClick="popup('notes.php?op=add&amp;pid=$scriptid&scantime=$scantime&scantype=$scantype&sortby=$sortby&resid=$resid&httpfrom=results','Notes'); 
  return false;"><img alt="Note" src="images/note.png" title="Add note" border=0></a>
EOT;
            }
         }

         //}
         // Add False Positive Indicator/link
         if ($enableFP && ($sql_uid==$user || $uroles['admin'])) {
            /*if($ipl=="all"){
                $query = "select result_id from vuln_nessus_latest_results 
                WHERE hostip='$hostip' and service='$service' and risk=".$levels[$tmprisk]." and scriptid=$scriptid $query_byuser";
            }*/
            if($ipl==""){
                $list_result_ids = array();
                $query = "select result_id from ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")." 
                WHERE report_id in ($report_id)".(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ")." and hostip='$hostip'
                and service='$service' and risk=".$levels[$tmprisk]." and scriptid=$scriptid".
                ((!in_array("admin", $arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ('$user') " : "");
                $result=$dbconn->execute($query);

                while ( !$result->EOF ) {
                    $list_result_ids[] = $result->fields["result_id"];
                    $result->MoveNext();
                }
                $resid = base64_encode(implode(",",$list_result_ids));
            }
            else {
                $resid = base64_encode("$report_id;$hostip;$service;".$levels[$tmprisk].";$scriptid");
            }
         //print_r ($query);
         
         
         $tmpu = array();
         $url = "";
         foreach ($_GET as $kget => $vget) {
            if($kget!="pluginid" && $kget!="nfp" && $kget!="fp")
                $tmpu[] = "$kget=$vget";
         }
         $url = implode("&",$tmpu);
            if ($falsepositive=="Y") {
/*               echo <<<EOT
&nbsp;&nbsp;
<a href="reshtml.php?$url&nfp=$resid&pluginid=$scriptid">
EOT;*/
               echo <<<EOT
&nbsp;&nbsp;
<a href="reshtml.php?$url&nfp=$resid">
EOT;
echo "<img alt=\""._("Clear false positive")."\" src=\"images/false.png\" title=\""._("Clear false positive")."\" border=0></a>";

            } else {
/*               echo <<<EOT
&nbsp;&nbsp;
<a href="reshtml.php?$url&fp=$resid&pluginid=$scriptid">
EOT;*/
               echo <<<EOT
&nbsp;&nbsp;
<a href="reshtml.php?$url&fp=$resid">
EOT;
echo "<img alt=\""._("Mark as false positive")."\" src=\"images/true.gif\" title=\""._("Mark as false positive")."\" border=0></a>";

            }
         }
$pticket = "ref=Vulnerability&ip=$hostip&port=$service_num&nessus_id=$scriptid&risk=$tmprisk&type=Nessus Vulnerability"; 
echo "&nbsp;&nbsp;&nbsp;<a title=\""._("New ticket")."\" class=\"greybox\" href=\"new_vuln_ticket.php?$pticket\"><img style=\"padding-bottom:2px;\" src=\"../pixmaps/incident.png\" border=\"0\" alt=\"i\" width=\"12\"></a>&nbsp;&nbsp;";
         echo "</td></tr>";
         $result1->MoveNext();
      }
      echo "</table>";
      echo "</div>";
      }
      $resultp->MoveNext();
   }
echo "</center>";

}

$ips_inrange = array();

switch($disp) {
   case "html":
         generate_results($output);
         break;

   default:
         generate_results($output);
         break;

}
echo "<br>";

function get_msg($dbconn,$query_msg) {
    //echo "$query_msg<br>";
    $result=$dbconn->execute($query_msg);
    return ($result->fields["msg"]);
}
?>
