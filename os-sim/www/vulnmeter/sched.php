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
// $Id: sched.php,v 1.17 2010/04/21 15:22:39 josedejoses Exp $
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

require_once ('classes/Session.inc');
require_once ('classes/Log_action.inc');
require_once ('ossim_conf.inc');

Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php echo gettext("Vulnmeter"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/tree.css" />
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
  <script type="text/javascript" src="../js/jquery.cookie.js"></script>
  <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
  <script type="text/javascript" src="../js/utils.js"></script>
  <script type="text/javascript" src="../js/vulnmeter.js"></script>
  <? include ("../host_report_menu.php") ?>
  <script>
	function postload() {
		var filter = "";
		$("#htree").dynatree({
			initAjax: { url: "draw_tree.php", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
                dtnode.data.url = html_entity_decode(dtnode.data.url);
				var ln = ($('#ip_list').val()!='') ? '\n' : '';
				var inside = 0;
				if (dtnode.data.url.match(/NODES/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++) {
						if (children[c].data.url != '') {
							var ln = ($('#ip_list').val()!='') ? '\n' : '';
							$('#ip_list').val($('#ip_list').val() + ln + children[c].data.url)
							inside = true;
						}
					}
					if (inside==0 && dtnode.data.key.match(/^hostgroup_/)) {
						dtnode.appendAjax({
					    	url: "draw_tree.php",
					    	data: {key: dtnode.data.key, page: dtnode.data.page},
			                success: function(msg) {
			                    dtnode.expand(true);
			                    var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++) {
									if (children[c].data.url != '') {
										var ln = ($('#ip_list').val()!='') ? '\n' : '';
										$('#ip_list').val($('#ip_list').val() + ln + children[c].data.url)
									}
								}
			                }
						});
					}
				} else {
					if (dtnode.data.url != '') $('#ip_list').val($('#ip_list').val() + ln + dtnode.data.url)
				}
			},
			onDeactivate: function(dtnode) {},
            onLazyRead: function(dtnode){
                dtnode.appendAjax({
                    url: "draw_tree.php",
                    data: {key: dtnode.data.key, page: dtnode.data.page}
                });
            }
		});
	}
    function switch_user(select) {
        if(select=='entity' && $('#entity').val()!='none'){
            $('#user').val('none');
        }
        else if (select=='user' && $('#user').val()!='none'){
            $('#entity').val('none');
        }
    }
    var loading = '<img width="16" align="absmiddle" src="images/loading.gif">';
    function simulation() {
        var targets = $('#ip_list').val();
        if (typeof(targets) != "undefined" && targets!="") {
            $('#ld').html(loading);
            //$('#sresult').toggle();
            $.ajax({
                type: "GET",
                url: "simulate.php",
                data: { 
                    hosts_alive: $('input[name=hosts_alive]').is(':checked') ? 1 : 0,
                    scan_locally: $('input[name=scan_locally]').is(':checked') ? 1 : 0,
                    scan_server: $('select[name=SVRid]').val(),
                    targets: targets
                },
                success: function(msg) {
                    $('#sresult').html(msg);
                    $('#ld').html('');
                    //$('#sresult').toggle();
                }
            });
        } else {
            alert("<?=_("At least one target needed!")?>");
        }
    }
  </script>
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = "Nessus Scan Schedule";
require_once ('config.php');
require_once('functions.inc');
//require_once('permissions.inc.php');


$myhostname="";

$getParams = array( 'disp', 'op', 'rid', 'sname', 'notify_email', 'tarSel', 'ip_list', 'ip_start',
                    'ip_end', 'named_list', 'subnets',  'schedule_type', 'cred_type', 'job_id', 'sched_id','hosts_alive','scan_locally'
                   );


$postParams = array( 'disp','op', 'rid', 'sname', 'notify_email', 'schedule_type', 'ROYEAR', 'ROMONTH', 'ROday',
                    'time_hour', 'time_min', 'dayofweek', 'dayofmonth', 'timeout', 'SVRid', 'sid', 'tarSel',
                     'ip_list', 'ip_start', 'ip_end', 'named_list', 'subnet', 'system', 'cred_type', 'credid', 'acc',
                     'domain', 'accpass', 'acctype', 'passtype', 'passstore', 'job_id','wpolicies', 'wfpolicies', 
                     'upolicies', 'cidr', 'custadd_type', 'cust_plugins', 'sched_id', 'is_enabled', 'submit', 'process',
                     'isvm', 'sen', 'hostlist', 'pluginlist','user','entity','hosts_alive','scan_locally','nthweekday', 'nthdayofweek');

 $daysMap = array ( 
     "0" => "NONE", 
    "Su" => "Sunday",
    "Mo" => "Monday",
    "Tu" => "Tuesday",
    "We" => "Wednesday",
    "Th" => "Thursday",
    "Fr" => "Friday", 
    "Sa" => "Saturday"
          );
 $wdaysMap = array ( 
    "Su" => "0",
    "Mo" => "1",
    "Tu" => "2",
    "We" => "3",
    "Th" => "4",
    "Fr" => "5", 
    "Sa" => "6"
          );        
          
$schedOptions = array( "N" => "Immediately",
                     "O" => "Run Once", 
                     "D" => "Daily", 
                     "W" => "Weekly", 
                     "M" => "Monthly" );

$pluginOptions = array( "N" => "No Additional Plugins",
                     "A" => "In Addition to ( selected Profile Plugins)", 
                     "R" => "In Replace of ( selected Profile Plugins)" );

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach ($getParams as $gp) {
      if (isset($_GET[$gp])) { 
         if(is_array($_GET[$gp])) {
            foreach ($_GET[$gp] as $i=>$tmp) {
               ${$gp}[$i] = sanitize($tmp);
            }
         } else {
            $$gp = sanitize($_GET[$gp]);
         }
      } else { 
         $$gp=""; 
      }
   }
   break;
case "POST" :
//   echo "<pre>"; print_r($_POST); echo "</pre>";
   foreach ($postParams as $pp) {
      if (isset($_POST[$pp])) { 
         if(is_array($_POST[$pp])) {
            foreach($_POST[$pp] as $i=>$tmp) {
               ${$pp}[$i] = sanitize($tmp);
//               echo $pp . "[" . $i . "] = " . ${$pp}[$i] . "<br>";
            }
         } else {
            $$pp = sanitize($_POST[$pp]);
//            echo $pp . " = " . $$pp . "<br>";
         }
//         echo "<pre>$pp = "; print_r($$pp); echo "</pre>";
      } else { 
         $$pp=""; 
      }
//      echo $pp . " = " . $$pp . "<Br>";
   }
//   echo "<pre>"; print_r($process); echo "</pre>";
   break;
}

if ($schedule_type=="NW") {
    $dayofweek = $nthdayofweek;
}

$error_message="";

if ($sname=="") {
    $error_message .= _("Invalid Job name")."<br>";
}
if ($ip_list=="") {
    $error_message .= _("Invalid Targets")."<br>";
}
if ($timeout=="") {
    $error_message .= _("Invalid Timeout")."<br>";
}

ossim_valid($sname, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Job name"));
if (ossim_error()) {
    $error_message .= _("Invalid Job name")."<br>";
}
ossim_set_error(false);
ossim_valid($entity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));
if (ossim_error()) {
    $error_message .= _("Invalid entity")."<br>";
}

ossim_set_error(false);
ossim_valid($user, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("User"));
if (ossim_error()) {
    $error_message .= _("Invalid user")."<br>";
}

ossim_set_error(false);
ossim_valid($timeout, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Timeout"));
if (ossim_error()) {
    $error_message .= _("Invalid timeout")."<br>";
}

$ip_targets = explode("\\r\\n", $ip_list);
foreach($ip_targets as $ip_target) {
    $ip_target = trim($ip_target);
    ossim_set_error(false);
    ossim_valid($ip_target, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, '\.\,\/', 'illegal:' . _("Target"));
    if (ossim_error()) {
        $error_message .= _("Invalid target").": $ip_target<br>";
    }
}
$hosts_alive = intval($hosts_alive);
$scan_locally = intval($scan_locally);


//echo "<pre>";
//print_r($hosts);
//echo $hosts;
//print_r($postParams);
//echo "</pre>";

global $dbconn, $username;

$query = "select count(*) from vuln_nessus_plugins";
$result=$dbconn->execute($query);
list($pluginscount)=$result->fields;

if ($pluginscount==0) {
   logAccess( "NO PLUGINS IN THE DB - USER NEED LAUNCH UPDATEPLUGINS" );
   //$logh->log("$username : " . $_SERVER['SCRIPT_NAME'] . " : You need to run updateplugins.pl", PEAR_LOG_CRIT);
   die ("<h2>Please run updateplugins.pl script first before using web interface.</h2>");
}

$component = getComponent( $username );  

function java_validation ( ) {
   global $emailDomains;
     echo <<< EOT

<script language="JavaScript">
function checkForm()
{
   var sname, notify_email, tarSel, ip_list, ip_start, ip_end, named_list, subnets,  schedule_type;
   with(window.document.msgform)
   {
      cname    = sname;
      cemail   = notify_email;
      csubject = schedule_type;
      ctSel    = tarSel;
      iplist   = ip_list;
      rstart   = ip_start;
      rend     = ip_end;
      named    = named_list;
      csubnets = subnets;
   }

   if (trim(cname.value) == '') {
      alert('Please enter job name');
      cname.focus();
      return false;
   } else if (trim(cemail.value) == '') {
      alert('Please enter your email');
      cemail.focus();
      return false;
   } else if ( ! checkEmails( trim(cemail.value) ) === true ) {
      cemail.focus();
      return false;
   } else if (trim(csubject.value) == '') {
      alert('Please setup schedule / scan frequency');
      csubject.focus();
      return false;
   } else if ( ctSel.value == '1' &&  trim(iplist.value) == '') {
      alert('Please setup Target Selection ( Host List)');
      ip_list.focus();
      return false;
   } else if ( ctSel.value == '2' && ( trim(rstart.value) == '' || trim(rend.value) == '' ) ) {
      alert('Please setup Target Selection ( Host Range)');
      rstart.focus();
      return false;
   } else if ( ctSel.value == '2' &&  trim(named.value) == '') {
      alert('Please setup Target Selection ( Host List)');
      named.focus();
      return false;
   } else if  (ctSel.value == '3' &&  trim(csubnets.value) == '') {
      alert('Please setup Target Selection (Subnet Block)');
      csubnets.focus();
      return false;
   } else {
      cname.value    = trim(cname.value);
      cemail.value   = trim(cemail.value);
      csubject.value = trim(csubject.value);
      return true;
   }
}

function checkEmails( emails ){
   //WATCH FOR OUTLOOK SEMI-COLON DELIMTERS AND FIX FOR PROPER PARSING
   emails = emails.replace(/;+/g, ',');
   var emailArray = emails.split(",");
   var invEmails = "";
   for(i = 0; i <= (emailArray.length - 1); i++){
      if( isEmail(trim( emailArray[i])) ){
         //Do what ever with the email.
      }else{
         alert('Email address: ' + emailArray[i] + ' is not valid');
	 invEmails += emailArray[i] + ",";
      }
   }
   if ( invEmails == '' ) {
      return true;
   }
}

function trim(str)
{
   return str.replace(/^\s+|\s+$/g,'');
}

function isEmail(str)
{
   var regex = /^[-_.a-z0-9]+@($emailDomains)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i;

return regex.test(str);
}
</script>


EOT;

}

function main_page( $job_id, $op ){
   global  $editdata, $scheduler, $defaultVSet, $credAudit, $enComplianceChecks, $profileid, $isvm, $sen, $hostlist, $pluginlist,
           $timeout, $uroles, $username, $useremail, $dbconn, $disp,
	   $enDetailedScanRequest, $enScanRequestImmediate, $enScanRequestRecur, $smethod;

     $query = "SELECT pn_email, defProfile 
               FROM vuln_users 
	       WHERE pn_uname='$username' LIMIT 1";
     $result=$dbconn->execute($query);
     list($useremail, $user_defsid )=$result->fields;

     $request = "";

     if ( $isvm != "" && $hostlist != "" ) {
     	$editdata['name'] = "ISVM SCAN - $isvm";
     	$editdata['meth_TARGET'] = str_replace( "&lt;br&gt;", "\n" , $hostlist );
     	$editdata['meth_CPLUGINS'] = str_replace( "&lt;br&gt;", "\n" , $pluginlist );
     }
     if ( $sen != "" && $hostlist != "" ) {
     	$editdata['name'] = "INVESTIGATE SCAN - $sen";
     	$editdata['meth_TARGET'] = str_replace( "&lt;br&gt;", "\n" , $hostlist );
     	$editdata['meth_CPLUGINS'] = str_replace( "&lt;br&gt;", "\n" , $pluginlist );
     }     
     
     
     if ( $op == "reoccuring" ) {
        $scheduler = "1";
        $title = "Create Recurring Job";
        $txt_submit = _("New Job");
     } elseif ( $op == "editreocurring" ) {
        $scheduler = "1";
        $title = "Edit Recurring Job";
        $txt_submit = _("Save Changes");
     } else {
     	 $scheduler = "0";
        if ( !($uroles['nessus']) ) {
	   #Users without nessus role can only submit scan request
           $request = " Request";   
        }
        /*if ( $op != "rerun" ) { #ADD SOME CONTROLS AROUND SETTING/SELECTING SOME IMPORTANT DEFAULTS
           if ( is_numeric($user_defsid) && $user_defsid > 0 ) {
           	   $editdata['meth_VSET'] = "$user_defsid";
           }
           if ( is_numeric($credAudit) && $credAudit > 0 ) {
              $editdata['meth_CRED'] = "$credAudit";
           }
        }*/
        if ($disp=="edit_sched")
            $title = _("Modify Scan Job$request");
        else
            $title = _("Create Scan Job$request");
        $txt_submit = _("New Job");
     }

     #java_validation ();

     $profileid = $defaultVSet;          #DEFAULT PROFILE

     #include ('navbar.php');
    if($timeout=="") {
        $timeout = "28800"; // 8 horas
    }

     
//<center><table cellspacing="0" cellpadding="0" border="0" width="80%"><tr><td class="headerpr" style="border:0;">$title</td></tr></table></center>
echo "<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"80%\" class=\"noborder\">";
echo "<tr class=\"noborder\" style=\"background-color:white\"><td class=\"headerpr\">";
echo "    <table width=\"100%\" class=\"noborder\" style=\"background-color:transparent\">";
echo "        <tr class=\"noborder\" style=\"background-color:transparent\"><td width=\"5%\" class=\"noborder\">";
echo "        <a href=\"manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs\"><img src=\"./images/back.png\" border=\"0\" alt=\"Back\" title=\"Back\"></a>";
echo "        </td><td width=\"95%\">";
echo "             $title</font>";
echo "        </td></tr>";
echo "    </table>";
echo "</td></tr></table></center>";
echo <<<EOT
<div>
     <form method="post" action="sched.php" name="msgform">
     <input type="hidden" name="disp" value="create">
EOT;
     if ( $op == "editrecurring" ) {
        $sched_id = $editdata['id'];
        echo <<<EOT
     <input type="hidden" name="op" value="editrecurring">
     <input type="hidden" name="sched_id" value="$sched_id">
EOT;
     }

     $tabs = array( "discovery" => "Target");
     if($uroles['nessus'] || $enDetailedScanRequest) {
        $tabs['settings'] = "Scan";
        $tabs['credentials'] = "Credentials";
        if ($enComplianceChecks ) {
           $tabs['compliance'] = "Compliance";
        }
     }
     // nothing here now, so no need to include the code
     //$tabs['reporting'] = "Reporting";

/*     $i = 0;
     $numTabs = count($tabs) - 1;
     foreach($tabs as $tkey=>$tname) {
        $func = "tab_" . $tkey;
        echo $func;
        if($schedTabs != "") { $schedTabs .= " &nbsp;&nbsp;&nbsp; "; }
        $schedTabs .= "<input type=\"button\" onClick=\"showDiv($i, 'section', $numTabs);return false;\" value=\"" . $tname . "\" class=\"button\">";
        $schedContent .= createHiddenDiv($tkey,$i,$func());
        
        $i++;
     }*/
	 echo "<center>".tab_discovery()."</center>";

//     foreach($tabs as $tkey=>$tname) {
//        $func = "tab_" . $tkey;
//        if($schedTabs != "") { $schedTabs .= " | "; }
//        $schedTabs .= "<a href=\"javascript:\" onClick=\"showDiv($i, 'section', $numTabs);return false;\">" . $tname . "</a>";
//        $schedContent .= createHiddenDiv($tkey,$i,$func());
//        $i++;
//     }

if ($disp=="edit_sched")
    echo "<br><center><input type=\"submit\" name=\"submit\" value=\""._("Update Job")."\" onClick=\"return checkForm();\" class=\"button\">";
else if($smethod=="inmediately")
    echo "<br><center><input type=\"submit\" name=\"submit\" value=\""._("Run Now")."\" onClick=\"return checkForm();\" class=\"button\">";
else
    echo "<br><center><input type=\"submit\" name=\"submit\" value=\"$txt_submit\" onClick=\"return checkForm();\" class=\"button\">";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript:;\" onclick=\"simulation();\"><img src=\"../pixmaps/arrow_green.gif\" align=\"absmiddle\" border=\"0\"> "._("Configuration Check")."</a>";
   // echo "&nbsp;&nbsp;<input type=\"button\" name=\"simulate\" value=\""._("Simulate")."\" onClick=\"simulation();\" class=\"button\">&nbsp;<span id='ld'></span>";
    echo "<br><br><span id='sresult'></span></center></form></div>";


   require_once("footer.php");

}

function tab_reporting () {
   $reporting = <<<EOT
<table><tr valign="top"><td>
<table class="noborder" >
        <tr><td>No Advanced / Customized Reporting exists (At this Time).</td>
        </tr>
     </table></td></tr></table>
EOT;
   return $reporting;
}

function tab_compliance () {
   global $editdata, $scheduler, $unixAuditDir, $winAuditDir, $winFileAudits, $enComplianceChecks, $profileid, $timeout, $username, $useremail, $dbconn;

$compliance = <<<EOT
<table>
  <tr valign="top">
    <td ><b>Compliance Checks</b>:<br><br>
   <input type="radio" name="comp_type" value="" onClick="showLayer('idComp', 1)" CHECKED>No Compliance Audit</input><br>
   <input type="radio" name="comp_type" value="N" onClick="showLayer('idComp', 2)" >Windows Checks</input><br>
   <input type="radio" name="comp_type" value="S" onClick="showLayer('idComp', 3)" >Win File Check</input><br>
   <input type="radio" name="comp_type" value="E" onClick="showLayer('idComp', 4)">Unix Checks</input>
    </td>
    <td>
      <div>
        <div id="idComp1" class="forminput">
        </div>
        <div id="idComp2" class="forminput">
        <SELECT MULTIPLE  name="wpolicies[]">
EOT;
   $directory = "$winAuditDir";

   $query = "SELECT name 
      FROM nessus_audits t1
      LEFT JOIN nessus_audit_users t2 ON t1.id = t2.cid
      WHERE t1.deleted='0' AND check_type = 'winAuditDir' AND
      ( t1.TYPE='G' 
         OR ( t1.TYPE='P' AND t1.owner = '$username' ) 
         OR ( t1.TYPE='P' AND t2.username = '$username' ) )";

   $result=$dbconn->execute($query);
   while (!$result->EOF) {
      list( $file)=$result->fields;
      if ( isset($editdata['meth_Wcheck']) && preg_match("/$file/i", $editdata['meth_Wcheck'] ) ) { $selected = "SELECTED"; }
      $fname = str_replace(".audit", "", $file );
      $compliance .= "<OPTION VALUE=\"$winAuditDir/$file\" $selected>$fname</option>";
      $result->MoveNext();
   }

    $compliance .= "
        </select><br><font color='red'> Credential is required</font>
    </div>
    <div id='idComp3' class='forminput'>
        <SELECT MULTIPLE  name='wfpolicies[]'>
";
   $directory = "$winFileAudits";
   $query = "SELECT name 
      FROM nessus_audits t1
      LEFT JOIN nessus_audit_users t2 ON t1.id = t2.cid
      WHERE t1.deleted='0' AND check_type = 'winFileAudits' AND
      ( t1.TYPE='G' 
         OR ( t1.TYPE='P' AND t1.owner = '$username' ) 
         OR ( t1.TYPE='P' AND t2.username = '$username' ) )";

   $result=$dbconn->execute($query);
   while (!$result->EOF) {
      list( $file)=$result->fields;
      if ( isset($editdata['meth_Wfile']) && preg_match("/$file/i", $editdata['meth_Wfile'] ) ) { $selected = "SELECTED"; }
      $fname = str_replace(".audit", "", $file );
      $compliance .= "<OPTION VALUE=\"$winFileAudits/$file\" $selected>$fname</option>";
      $result->MoveNext();
   }

    $compliance .= <<<EOT
        </select><br><font color="red">Credential is required</font>
    </div>
    <div id='idComp4' class="forminput"> 
        <SELECT MULTIPLE name='upolicies[]'>
EOT;
   $directory = "$unixAuditDir";

   $query = "SELECT name 
      FROM nessus_audits t1
      LEFT JOIN nessus_audit_users t2 ON t1.id = t2.cid
      WHERE t1.deleted='0' AND check_type = 'unixAuditDir' AND
      ( t1.TYPE='G' 
         OR ( t1.TYPE='P' AND t1.owner = '$username' ) 
         OR ( t1.TYPE='P' AND t2.username = '$username' ) )";

   $result=$dbconn->execute($query);
   while (!$result->EOF) {
      list( $file)=$result->fields;
      if ( isset($editdata['meth_Ucheck']) && preg_match("/$file/i", $editdata['meth_Ucheck'] ) ) { $selected = "SELECTED"; }
      $fname = str_replace(".audit", "", $file );
      $compliance .= "<OPTION VALUE=\"$unixAuditDir/$file\" $selected>$fname</option>";
      $result->MoveNext();
   }   

    $compliance .= <<<EOT
        </select><br><font color='red'>Credential is required</font>
        </div>
        <div id='idComp4' class="forminput">
        </div>
        <div id='idComp5' class="forminput">
        </div>
        <div id='idComp6' class="forminput">
        </div>
    </div>
      </td>
    </tr>
  </table>
EOT;
   return $compliance;
}

function tab_credentials () {
   global $editdata, $scheduler, $username, $dbconn;

   $sTYPE['N'] = "";
   $sTYPE['S'] = "";
   
   if ( isset($editdata['meth_CRED'] )) { $usedCred = $editdata['meth_CRED']; } else { $usedCred = ""; }
   if ( isset($editdata['meth_CRED'] )) {  $sTYPE['S'] = "CHECKED"; } ELSE { $sTYPE['N']  = "CHECKED"; }


   $credentials = <<<EOT
<center>
<table width="80%"><tr valign="top"><td>
<table class="noborder" width="100%">
  <tr>
    <td align="right" width="30%">Credentials:
    <table cellspacing="0" cellpadding="0" border="0" width="100%">
      <tr>
        <td style="text-align:left;" nowrap><input type="radio" name="cred_type" value="N" onClick="showLayer('idCred', 1)" $sTYPE[N]>Null Credentials</input></td>
      </tr> 
      <tr>
        <td style="text-align:left;" nowrap><input type="radio" name="cred_type" value="S" onClick="showLayer('idCred', 2)" $sTYPE[S]>Stored Credentials</input></td>
      </tr>
      <tr>
        <td style="text-align:left;" nowrap><input type="radio" name="cred_type" value="E" onClick="showLayer('idCred', 3)">Enter a Credential</input>
        <br><br><br><br><br><br><br><br><br></td>
      </tr>
    </table>
    </td>
    <td align="center" width="70%" valign="top">
      <div>
        <div id="idCred1" class="forminput">
        </div>
        <div id="idCred2" class="forminput">
          <table width="100%" class="noborder">
            <tr>
              <td><select name="credid">
                <option value="" >Select a credential to Use</option>
EOT;

   $query = "SELECT t1.id, t1.account, t1.domain, t1.ACC_TYPE, t1.STORE_TYPE
      FROM vuln_credentials t1
      LEFT JOIN vuln_orgs t2 ON t1.ORG = t2.org_code
      LEFT JOIN vuln_org_users t3 ON t2.id = t3.orgID
      WHERE expired='0' AND 
         ( t1.STORE_TYPE='G' 
         OR ( t1.STORE_TYPE='P' AND t1.pn_uname = '$username' ) 
         OR ( t1.STORE_TYPE='O' AND t3.pn_uname = '$username' ) )";

   $result=$dbconn->execute($query);

   while (!$result->EOF) {
      list($credid, $cname, $cdomain, $cacctype, $ctype )=$result->fields;
      $cAType = strtoupper($cacctype);
      if ( $cdomain ) {
      	$cAccount = "$cdomain\\$cname";
      } else {
      	$cAccount = "$cname";
      }
      $credentials .= "<option value=\"$credid\"";
      if ($usedCred==$credid) $credentials .= "selected";
      $credentials .= ">$cAType - $cAccount</option>";
      $result->MoveNext();
   }

   $credentials .=  <<<EOT
                </select>
              </td>
            </tr>
          </table>
        </div>
        <div id='idCred3'  class='forminput'> 
          <table width="100%">
            <tr>
              <td style='text-align:right;'>Account</td><td style='text-align:left;'><INPUT type='text' name='acc' value=''></td>
            </tr>
            <tr>
              <td style='text-align:right;'>Domain</td><td style='text-align:left;'><INPUT type='text' name='domain' value=''></td>
            </tr>
            <tr>
              <td style='text-align:right;'>Password</td><td style='text-align:left;'><INPUT type='password' name='accpass' value=''></td>
            </tr>
            <tr>
              <td style='text-align:right;'>Account Type</td><td style='text-align:left;'><select name="acctype">
               <option value="smb" SELECTED>SMB PASSWORD</option>
               <option value="ssh" >SSH PASSWORD</option>
               <option value="both" >USE FOR BOTH SMB/SSH</option>
              </select></td>
            </tr>
            <tr>
              <td style='text-align:right;'>Blowfish Encrypted Store</td>
                <td style='text-align:left;'>
                  <INPUT type='radio' name='passstore' value='C' checked>Until Job Start<BR>
                  <INPUT type='radio' name='passstore' value='P' >My Use Only<BR>
                  <INPUT type='radio' name='passstore' value='O' >ORG LEVEL SHARE<BR>
                  <INPUT type='radio' name='passstore' value='G' >Global Shared Password<BR>
                </td>
            </tr>
          </table>
        </div>
        <div id='idCred4' class='forminput'>
        </div>
        <div id='idCred5' class='forminput'>
        </div>
        <div id='idCred6' class='forminput'>
        </div>
      </div>
      </td>
    </tr>
  </table>
</td></tr></table></center>
EOT;
   return $credentials;
}

function tab_settings () {
   global $editdata, $pluginOptions, $scheduler, $enComplianceChecks, 
          $profileid, $timeout, 
          $uroles, $username, $useremail, $dbconn;

   if ( isset($editdata['meth_VSET']) ) { $profileid = $editdata['meth_VSET']; }
   if ( isset($editdata['meth_TIMEOUT']) ) { $timeout = $editdata['meth_TIMEOUT']; }

   $query = "SELECT id, name, hostname
     FROM vuln_nessus_servers
     WHERE enabled='1' AND status='A'";
   $result=$dbconn->execute($query);

   $settings = <<<EOT
<center>
<table>
  <tr>
    <td align="right">Select Server:</td>
    <td style="text-align:left;"><select name="SVRid">
      <option value="Null">First Available Server</option>
EOT;

   while (!$result->EOF) {
      list($SVRid, $sname, $shostIP)=$result->fields;
      if (Session::am_i_admin() || Session::sensorAllowed($shostIP)) { // $shostIP=="localhost" || 
	      $settings .= "<option value=\"$SVRid\" ";
	      if ($editdata['scan_ASSIGNED']!="" && $editdata['scan_ASSIGNED']==$SVRid) { $settings .= " SELECTED"; } 
	      $settings .= ">" . strtoupper($sname) . " [$shostIP] </option>";
	  }
      $result->MoveNext();
   }

   $settings .= <<<EOT
      </select>
    </td>
  </tr>
  <tr>
    <td align='right' width='25%'>Profile:</td>
    <td style="text-align:left;"><select name='sid'>
EOT;

   $query = "SELECT distinct(t1.id), t1.name, t1.description 
      FROM vuln_nessus_settings t1
      LEFT JOIN vuln_nessus_settings_users t2 ON t1.id = t2.sid 
      WHERE t1.type = 'G' OR t2.username='$username' 
      ORDER BY t1.name";
   $result=$dbconn->execute($query);

   while (!$result->EOF) {
      list($sid, $sname, $sdescription)=$result->fields;
      $settings .= "<option value=\"$sid\" ";
      if ($profileid==$sid) {$settings .= "selected";}
      $settings .= ">$sname-$sdescription</option>";
      $result->MoveNext();
    }

    $settings .= <<<EOT
    </select></td>
  </tr>  
EOT;

  if ( $uroles['plugoverride'] ) {
    $settings .= <<<EOT
  <tr>
    <td >Override Plugin Lists:</td>
    <td><select name='custadd_type'>
EOT;
   foreach( $pluginOptions as $custN => $custV ) {
      $settings .= "<option value=\"$custN\" ";
      if ( isset($editdata['meth_CUSTOM']) && ($editdata['meth_CUSTOM'] == $custN )) { $settings .= " SELECTED"; } 
      $settings .= ">$custV</option>";
   }

   $settings .= <<<EOT
      </select><br>
      <textarea name='cust_plugins' cols='48' rows='12'>$editdata[meth_CPLUGINS]</textarea><font color='red'>Enter Plugin ID's</font>
    </td>
  </tr>
EOT;
   } else {
   $settings .= <<<EOT
<tr>
    <td></td><td></td>
  </tr>
EOT;

   }
   $settings .= "</table></center>";
   return $settings;
}

function tab_discovery () {
    global $component, $uroles, $editdata, $scheduler, $username, $useremail, $dbconn, $disp,
          $enScanRequestImmediate, $enScanRequestRecur, $timeout, $smethod,$SVRid, $sid, $ip_list,
          $schedule_type, $ROYEAR, $ROday, $ROMONTH, $time_hour, $time_min, $dayofweek, $dayofmonth,
          $sname,$user,$entity,$hosts_alive,$scan_locally,$version,$nthweekday,$semail;
          
    global $pluginOptions, $enComplianceChecks, 
          $profileid;
          
     $user_selected = $user;
     $entity_selected = $entity;
          
     $SVRid_selected = $SVRid;
     
     $sid_selected = ($sid!="") ? $sid : $editdata['meth_VSET'];
     $timeout_selected = $timeout;
     $ip_list_selected = str_replace("\\r\\n", "\n", str_replace(";;", "\n", $ip_list));
     $ROYEAR_selected = $ROYEAR;
     $ROday_selected = $ROday;
     $ROMONTH_selected = $ROMONTH;
     $time_hour_selected = $time_hour;
     $time_min_selected = $time_min;
     $dayofweek_selected = $dayofweek;
     $dayofmonth_selected = $dayofmonth;
     $sname_selected = $sname;

	//print_r($editdata);

     if($schedule_type!=""){
        $editdata['schedule_type'] = $schedule_type;
     }

     $cquery_like = "";
     if ( $component != "" ) { $cquery_like = " AND component='$component'"; }      
     
     $today=date("Ymd");
     $tyear=substr($today,0,4);
     $nyear=$tyear+1;
     $tmonth = substr($today,4,2);
     $tday = substr($today,6,2);

     #SET VALUES UP IF EDIT SCHEDULER
     if ( isset($editdata['notify'] )) { $enotify = $editdata['notify']; } else { $enotify = "$useremail"; }
     if ( isset($editdata['time'] )) { list( $time_hour, $time_min, $time_sec) = split(':', $editdata['time'] ); }

     $arrTypes = array( "N", "O", "D", "W", "M" , "NW");
     foreach ( $arrTypes as $type ) {
         $sTYPE[$type] = "";
     }

     $arrJobTypes = array( "C", "M", "R", "S" );
     foreach ( $arrJobTypes as $type ) {
         $sjTYPE[$type] = "";
     }

     if ( isset($editdata['schedule_type'] )) {  
        $sTYPE[$editdata['schedule_type']] = "CHECKED"; 
        if ($editdata['schedule_type']=='D') $ni=2;
        elseif ($editdata['schedule_type']=='O') $ni=3;
        elseif ($editdata['schedule_type']=='W') $ni=4;
        elseif ($editdata['schedule_type']=='NW') $ni=6;
        else $ni=5;
        $show = "<br><script language=javascript>showLayer('idSched', $ni);</script>";
     } ELSE { 
        if($enScanRequestImmediate) {
           $sTYPE['N']  = "CHECKED";
           $show = "<br><script language=javascript>showLayer('idSched', 1);</script>";
        } else {
           $sTYPE['O'] = "checked";
           $show = "<br><script language=javascript>showLayer('idSched', 3);</script>";
        }
     }
     
     if($schedule_type!="" ){
        if ($schedule_type=="N") {
             $show .= "<br><script language=javascript>showLayer('idSched', 1);</script>";
            }
        if ($schedule_type=="O") {
             $show .= "<br><script language=javascript>showLayer('idSched', 3);</script>";
            }
        if ($schedule_type=="D") {
             $show .= "<br><script language=javascript>showLayer('idSched', 2);</script>";
            }
        if ($schedule_type=="W") {
             $show .= "<br><script language=javascript>showLayer('idSched', 4);</script>";
            }
        if ($schedule_type=="M") {
             $show .= "<br><script language=javascript>showLayer('idSched', 5);</script>";
            }
        if ($schedule_type=="NW") {
             $show .= "<br><script language=javascript>showLayer('idSched', 6);</script>";
            }
     }

     if ( isset($editdata['job_TYPE'] )) {
        $sjTYPE[$editdata['job_TYPE']] = "SELECTED";
     } ELSE { 
        $sjTYPE['M'] = "SELECTED";
     }

     if ( isset($editdata['day_of_month'] )) { $dayofmonth = $editdata['day_of_month']; }
     if ( isset($editdata['day_of_week'])) { $day[$editdata['day_of_week']] = "SELECTED"; }
     if ($dayofweek_selected!="") { $day[$dayofweek_selected] = "SELECTED";}
     if (!$uroles['nessus']) {
        $name = "sr-" . substr($username,0,6) . "-" . time();
        $name = ($editdata['name'] == "") ? $name : $editdata['name'];
	    $nameout = $name . "<input type=hidden style='width:200px' name='sname' value='$name'>";
     } else {
        $nameout = "<input type=text style='width:200px' name='sname' value='".(($sname_selected!="")? "$sname_selected":"$editdata[name]")."'>";
     }
	 
    $discovery = "<input type=\"hidden\" name=\"cred_type\" value=\"N\">";
    $discovery.= "<table width=\"80%\">";
    $discovery.= "<tr>";
    $discovery.= "<input type=\"hidden\" name=\"smethod\" value=\"$smethod\">";
    $discovery.= "<td align=\"Right\" width=\"30%\">"._("Job Name").":</td>";
    $discovery.= "<td style=\"text-align:left;\">$nameout</td>";
    $discovery.= "</tr>";

     $query = "SELECT id, name, hostname
     FROM vuln_nessus_servers
     WHERE enabled='1' AND status='A'";
   $result=$dbconn->execute($query);

    $discovery .= "<tr>";
    $discovery .= "<td align=\"right\">"._("Select Server").":</td>";
    $discovery .= "<td style=\"text-align:left;\"><select name=\"SVRid\">";
    //if($SVRid=="" || $SVRid_selected=="Null") {
        $discovery .= "<option value=\"Null\">"._("First Available Server-Distributed")."</option>";
    //}

   while (!$result->EOF) {
      list($SVRid, $sname, $shostIP)=$result->fields;
      if (Session::am_i_admin() || Session::sensorAllowed($shostIP)) { // $shostIP=="localhost" || 
	      $discovery .= "<option value=\"$SVRid\" ";
	      if ($editdata['scan_ASSIGNED']!="" && $editdata['scan_ASSIGNED']==$SVRid) { $discovery .= " SELECTED"; }
	      if ($SVRid_selected==$SVRid) $discovery .= " SELECTED";
	      $discovery .= ">" . strtoupper($sname) . " [$shostIP] </option>";
	  }
      $result->MoveNext();
   }

   $discovery .= <<<EOT
      </select>
    </td>
  </tr>
  <tr>
    <td align='right' width='25%'>Profile:</td>
    <td style="text-align:left;"><select name='sid'>
EOT;

   //$query = "SELECT distinct(t1.id), t1.name, t1.description 
   //  FROM vuln_nessus_settings t1
   //   LEFT JOIN vuln_nessus_settings_users t2 ON t1.id = t2.sid 
   //   WHERE t1.type = 'G' OR t2.username='$username' 
   //   ORDER BY t1.name";
   
   $query = "";

   if ($username == "admin" || Session::am_i_admin()) {
        $query = "SELECT distinct(t1.id), t1.name, t1.description 
                 FROM vuln_nessus_settings t1 WHERE deleted='0'
                 ORDER BY t1.name";
    }
    else if(preg_match("/pro|demo/i",$version)){
        if (Acl::am_i_proadmin()) {
            $pro_users = array();
            
            $entities_list = Acl::get_user_entities($current_user);   
            //list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
            //$entities_list = array_keys($entities_admin);
        
            $users = Acl::get_my_users($dbconn, Session::get_session_user());
            foreach ($users as $us) {
                $pro_users[] = $us["login"];
            }
            $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                      WHERE deleted = '0' and (name='Default' or owner in ('0','".implode("','", array_merge($entities_list,$pro_users))."')) ORDER BY t1.name";
        }
        else {
            $tmp = array();
            $entities = Acl::get_user_entities($username);
            foreach ($entities as $entity) {
                $tmp[] = "'".$entity."'";
            }
            if (count($tmp) > 0) $user_where = "owner in ('0','$username',".implode(", ", $tmp).")";
            else $user_where = "owner in ('0','$username')";
            
            $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                      WHERE deleted = '0' and (name='Default' or $user_where) ORDER BY t1.name"; 
        }
    } else {
        $query = "SELECT distinct(t1.id), t1.name, t1.description FROM vuln_nessus_settings t1
                     WHERE deleted = '0' and (name='Default' or owner in ('0','$username')) ORDER BY t1.name";
    }                          
    //var_dump($query); 
    
   $result=$dbconn->execute($query);

   while (!$result->EOF) {
      list($sid, $sname, $sdescription)=$result->fields;
      $discovery .= "<option value=\"$sid\" ";
      
      if (($sid_selected!="" && $sid_selected == $sid) || $profileid==$sid){
          if ($sdescription!="")
            $discovery .= "selected>$sname - $sdescription</option>";
          else
            $discovery .= "selected>$sname</option>";
      }
      else {
          if ($sdescription!="")
            $discovery .= (preg_match("/default/i", $sname) ? "selected": "").">$sname - $sdescription</option>";
          else
            $discovery .= (preg_match("/default/i", $sname) ? "selected": "").">$sname</option>";
      }
      $result->MoveNext();
    }

    $discovery .="</select>&nbsp;&nbsp;&nbsp[<a href=\"settings.php?hmenu=Vulnerabilities&amp;smenu=ScanProfiles\">"._("Edit Profiles")."</a>]</td>";
    $discovery .="</tr>";
    $discovery .="<tr>";
    $discovery .="<td align='right'>"._("Timeout")."</td>";
    $discovery .="<td style=\"text-align:left;\" nowrap><input type='text' style='width:80px' name='timeout' value='".(($timeout_selected=="")? "$timeout":"$timeout_selected")."'>";
    $discovery .="<font color='black'>&nbsp;&nbsp;&nbsp;"._("Max scan run time in seconds")."&nbsp;&nbsp;&nbsp;</font></td>";
    $discovery .="</tr>";
    if($smethod=="inmediately") {
	    $discovery .= "<tr>";
	    $discovery .= "<td style=\"text-align:center;\" nowrap>Schedule Method:</td>";
	    $discovery .= "<td style=\"text-align:left;\" nowrap>Inmediately<td>";
	    $discovery .= "</tr>";
	    $discovery .= "<tr style='display:none'>";
    }
    else $discovery .="<tr>";
    $discovery .="<td style=\"text-align:left;padding-left:35px;\">Schedule Method:<br>";


if( !$scheduler && $enScanRequestImmediate) {
     $discovery .= <<<EOT
        <input type="radio" name="schedule_type" value="N" onClick="showLayer('idSched', 1)" $sTYPE[N]>Immediately</input><br>
EOT;
}
if( !$scheduler ) {
     $discovery .= <<<EOT
        <input type="radio" name="schedule_type" value="O" onClick="showLayer('idSched', 3)"  $sTYPE[O]>Run Once</input><br>
EOT;
}

if ( $scheduler || $enScanRequestRecur ) {
     $discovery .= <<<EOT
        <input type="radio" name="schedule_type" value="D" onClick="showLayer('idSched', 2)" $sTYPE[D]>Daily</input><br>
        <input type="radio" name="schedule_type" value="W" onClick="showLayer('idSched', 4)" $sTYPE[W]>Day of the Week</input><br>
        <input type="radio" name="schedule_type" value="M" onClick="showLayer('idSched', 5)"  $sTYPE[M]>Day of the Month</input><br>
        <input type="radio" name="schedule_type" value="NW" onClick="showLayer('idSched', 6)"  $sTYPE[NW]>N<sup>th</sup> weekday of the month</input><br>
EOT;
}      
     $discovery .= <<<EOT
    </td>
    <td><div>
      <div id="idSched1" class="forminput">
      </div>
      <div id="idSched3" class="forminput">
        <table cellspacing="2" cellpadding="0" width="100%">
          <tr><td colspan="7" class="noborder">Year&nbsp;<select name="ROYEAR">
EOT;
            $discovery .="<option value=\"$tyear\" ".(($ROYEAR_selected==""||$ROYEAR_selected==$tyear)? "selected" : "").">$tyear</option>";
            $discovery .="<option value=\"$nyear\" ".(($ROYEAR_selected==$nyear)? "selected" : "").">$nyear</option>";
     $discovery .= <<<EOT
            </select>&nbsp;&nbsp;&nbsp;Month&nbsp;<select name="ROMONTH">";
EOT;

/*     $discovery .= <<<EOT
    </td>
    <td><div>
      <div id="idSched1" class="forminput">
      </div>
      <div id="idSched3" class="forminput">
        <table cellspacing="2" cellpadding="0" width="100%">
          <tr><td colspan="7" class="noborder">Year&nbsp;<select name="ROYEAR">
            <option value="$tyear" selected>$tyear</option>";
            <option value="$nyear">$nyear</option>";
            </select>&nbsp;&nbsp;&nbsp;Month&nbsp;<select name="ROMONTH">";
EOT;*/
   for ($i=1;$i<=12;$i++) {
      $discovery .= "<option value=\"$i\" ";
      if (($i==$tmonth && $ROMONTH_selected=="") || $ROMONTH_selected==$i) $discovery .= "selected";
      $discovery .= ">$i</option>";
   }
   $discovery .= "</select>&nbsp;&nbsp;&nbsp;Day&nbsp;<select name=\"ROday\">";
   for ($i=1;$i<=31;$i++) {
      $discovery .= "<option value=\"$i\" ";
      if (($i==$tday && $ROday_selected=="") || $ROday_selected==$i) $discovery .= "selected";
         $discovery .= ">$i</option>";
   }
            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
      <div id="idSched4" class="forminput" > 
        <table width="100%">
          <tr>
            <th align="right">Weekly</td><td colspan="2" class="noborder">
              <select name="dayofweek">
                <option value="0" SELECTED >Select week day to run</option>
                <option value="Su" $day[Su] >Sunday</option>
                <option value="Mo" $day[Mo] >Monday</option>
                <option value="Tu" $day[Tu] >Tuesday</option>
                <option value="We" $day[We] >Wednesday</option>
                <option value="Th" $day[Th] >Thursday</option>
                <option value="Fr" $day[Fr] >Friday</option>
                <option value="Sa" $day[Sa] >Saturday</option>
              </select>
            </td>
          </tr>
        </table>
      </div>
      <div id="idSched5" class="forminput">
        <table width="100%">
          <tr>
            <th align="right">Select Day</td>
            <td colspan="2" class="noborder"><select name="dayofmonth">"
EOT;
   for ($i=1;$i<=31;$i++) {
      $discovery .= "<option value=\"$i\"";
      if (($dayofmonth==$i && $dayofmonth_selected=="") || $dayofmonth_selected==$i) $discovery .= " selected";
      $discovery .= ">$i</option>";
   }

            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
      <div id="idSched6" class="forminput">
        <table width="100%">
          <tr>
            <th align="right">Day of week</th><td colspan="2" class="noborder">
              <select name="nthdayofweek">
                <option value="0" SELECTED >Select week day to run</option>
                <option value="Su" $day[Su] >Sunday</option>
                <option value="Mo" $day[Mo] >Monday</option>
                <option value="Tu" $day[Tu] >Tuesday</option>
                <option value="We" $day[We] >Wednesday</option>
                <option value="Th" $day[Th] >Thursday</option>
                <option value="Fr" $day[Fr] >Friday</option>
                <option value="Sa" $day[Sa] >Saturday</option>
              </select>
            </td>
          </tr>
        </table>
        <br>
        <table width="100%">
          <tr>
            <th align="right">N<sup>th</sup> weekday</th><td colspan="2" class="noborder">
              <select name="nthweekday">
EOT;
                $discovery .="<option value='0' SELECTED >Select nth weekday to run</option>";
                $discovery .="<option value='1'".(($dayofmonth==1) ? " selected":"").">First</option>";
                $discovery .="<option value='2'".(($dayofmonth==2) ? " selected":"").">Second</option>";
                $discovery .="<option value='3'".(($dayofmonth==3) ? " selected":"").">Third</option>";
                $discovery .="<option value='4'".(($dayofmonth==4) ? " selected":"").">Fourth</option>";
                $discovery .="<option value='5'".(($dayofmonth==5) ? " selected":"").">Fifth</option>"; 
            $discovery .= <<<EOT
              </select>
            </td>
          </tr>
        </table>
      </div>
      <div id="idSched2" class="forminput">
        <table width="100%">
          <tr>
            <th rowspan="2" align="right" width="30%">Time</td>
            <td align="right">Hour</td><td>Minutes</td>
          </tr>
          <tr>
            <td align="right" class="noborder"><select name="time_hour">
EOT;
   for ($i=0;$i<=23;$i++){
      $discovery .=  "<option align=\"right\" value=\"$i\"";
      if (($time_hour==$i && $time_hour_selected=="") || $time_hour_selected==$i) $discovery .= " selected";
      $discovery .= ">$i</option>";
   };
            $discovery .= <<<EOT
            </select></td>
            <td class="noborder"><select name="time_min">
EOT;
               for ($i=0;$i<60;$i=$i+15){
                    $discovery .= "<option value=\"$i\"";
                    if (($time_min == $i && $time_min_selected=="") || $time_min_selected==$i) $discovery .= " selected";
                    $discovery .= ">$i</option>";
               };
            $discovery .= <<<EOT
            </select></td>
          </tr>
        </table>
      </div>
    </tr>
    
EOT;
    $conf = $GLOBALS["CONF"];
    $version = $conf->get_conf("ossim_server_version", FALSE);
    if(Session::am_i_admin()) {
          $discovery .= "<tr><td>"._("Make this scan job visible for:")."</td>";

          $discovery .= "<td style=\"text-align:left;\">";
          $discovery .= "<table class=\"noborder\">";
          $discovery .= "<tr><td class=\"nobborder\">"._("User:")."&nbsp;";
          $users = Session::get_list($dbconn);
          $discovery .= "</td><td style=\"text-align:left;\" class=\"nobborder\">";
          $discovery .= "<select name=\"user\" id=\"user\" onchange=\"switch_user('user');return false;\">";
          $discovery .= "<option value=\"none\">"._("Not assign")."</option>";
          foreach ($users as $user) {
            $discovery .= "<option value=\"".$user->get_login()."\"".(($editdata["username"]==$user->get_login() || $user_selected==$user->get_login())? " selected":"").">".$user->get_login()."</option>";
          }
          $discovery .= "</select>";
          if(preg_match("/pro|demo/i",$version)){
              $discovery .= "<tr><td class=\"nobborder\">&nbsp;</td><td class=\"nobborder\">"._("OR")."</td></tr>";
              $discovery .= "<tr><td class=\"nobborder\">"._("Entity:")."</td><td class=\"nobborder\">";
              $entities_types_aux = Acl::get_entities_types($dbconn);
              $entities_types = array();

              foreach ($entities_types_aux as $etype) { 
                $entities_types[$etype['id']] = $etype;
              }
              list($entities_all,$num_entities) = Acl::get_entities($dbconn);
              $discovery .="<select name=\"entity\" id=\"entity\" onchange=\"switch_user('entity');return false;\">";
              $discovery .="<option value=\"none\">"._("Not assign")."</option>";
                foreach ($entities_all as $entity) {
                    $discovery .= "<option value=\"".$entity["id"]."\"".(($editdata["username"]==$entity["id"] || $entity_selected==$entity["id"])? " selected":"").">".$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]</option>";
                }
              $discovery .="</select>";
              $discovery .="</td></tr>";
          }
          $discovery .="</table>";
          $discovery .="</td></tr>";
      }
       else if(preg_match("/pro|demo/i",$version)) {
            if(Acl::am_i_proadmin()) {
                  $discovery .= "<tr><td>"._("Make this scan job visible for:")."</td>";

                  $discovery .= "<td style=\"text-align:left;\">";
                  $discovery .= "<table class=\"noborder\">";
                  $discovery .= "<tr><td class=\"nobborder\">"._("User:")."&nbsp;";
                  $users = Acl::get_my_users($dbconn,Session::get_session_user());
                  $discovery .= "</td><td style=\"text-align:left;\" class=\"nobborder\">";
                  $discovery .= "<select name=\"user\" id=\"user\" onchange=\"switch_user('user');return false;\">";
                  $discovery .= "<option value=\"none\">"._("Not assign")."</option>";
                  foreach ($users as $user) {
                    $discovery .= "<option value=\"".$user["login"]."\"".(($editdata["username"]==$user["login"] || $user_selected==$user["login"]) ? " selected":"").">".$user["login"]."</option>";
                  }
                  $discovery .= "</select>";
                  $discovery .= "<tr><td class=\"nobborder\">&nbsp;</td><td class=\"nobborder\">"._("OR")."</td></tr>";
                  $discovery .= "<tr><td class=\"nobborder\">"._("Entity:")."</td><td class=\"nobborder\">";
                  $entities_types_aux = Acl::get_entities_types($dbconn);
                  $entities_types = array();

                  foreach ($entities_types_aux as $etype) { 
                     $entities_types[$etype['id']] = $etype;
                  }
                  list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
                  $entities_list = array_keys($entities_admin);
                  list($entities_all,$num_entities) = Acl::get_entities($dbconn);
                  
                  $discovery .="<select name=\"entity\" id=\"entity\" onchange=\"switch_user('entity');return false;\">";
                  $discovery .="<option value=\"none\">"._("Not assign")."</option>";
                  foreach ($entities_all as $entity)  if(Session::am_i_admin() || (Acl::am_i_proadmin() && in_array($entity["id"], $entities_list))) {
                      $discovery .= "<option value=\"".$entity["id"]."\"".(($editdata["username"]==$entity["id"] || $entity_selected==$entity["id"])? " selected":"").">".$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]</option>";
                  }
                  $discovery .="</select>";
                  $discovery .="</td></tr>";
                  
                  $discovery .="</table>";
                  $discovery .="</td></tr>";
            }
       }
      $discovery .= "<tr><td>"._("Send an email notification when finished:");
      $discovery .= "</td>";
      $discovery .= "<td style=\"text-align:left;\">";
      $discovery .= "<input type=\"radio\" name=\"semail\" value=\"0\"".(((count($editdata)<=1 && intval($semail)==0) || intval($editdata['meth_Wfile'])==0)? " checked":"")."/>"._("No");
      $discovery .= "<input type=\"radio\" name=\"semail\" value=\"1\"".(((count($editdata)<=1 && intval($semail)==1) || intval($editdata['meth_Wfile'])==1)? " checked":"")."/>"._("Yes");
      $discovery .= "</td></tr>";

      $targets_message = _("Targets")."<br>"._("(Hosts/Networks)")."<br>";
      
      $discovery .= "<tr><td valign=\"top\" align=\"Right\" width=\"20%\" class=\"noborder\"><br>";
      $discovery .= "<input type=\"checkbox\" name=\"hosts_alive\" value=\"1\"".(((count($editdata)<=1 && intval($hosts_alive)==1) || intval($editdata['meth_CRED'])==1)? " checked":"").">"._("Only scan hosts that are alive")."<br>("._("greatly speeds up the scanning process").")<br><br>";
      if (Session::am_i_admin())
        $discovery .= "<input type=\"checkbox\" name=\"scan_locally\" value=\"1\"".(((count($editdata)<=1 && intval($scan_locally)==1) || intval($editdata['authorized'])==1)? " checked":"").">"._("Pre-Scan locally")."<br>("._("do not pre-scan from scanning sensor").")";
      else
        $discovery .= "<input type=\"hidden\" name=\"scan_locally\" value=\"0\">";
$discovery .= <<<EOT
        <select name="tarSel" style="display:none;" onClick="if (this.options[this.selectedIndex].value != 'null') {
          showLayer('idTarget', this.options[this.selectedIndex].value ) }">
          <option name="schedule" value="1" $sjTYPE[M] selected>IP List</option>
          <option name="schedule" value="2">IP Range</option>
          <option name="schedule" value="3" >Named Target List</option>
          <option name="schedule" value="4">CIDR</option>
          <option name="schedule" value="5" $sjTYPE[C] >Subnet</option>
          <option name="schedule" value="6" $sjTYPE[S] >Asset List/System</option>
        </select><br><br><br><br><br><br><br><br><br></td>
        <td class="noborder" style="text-align:left" valign="top">
        <div align="left">
          <div id="idTarget1">
			<table class="noborder"><tr>
            <td style="text-align:center;padding-bottom:3px;" class="nobborder">$targets_message</td>
            </tr>
            <tr>
			<td valign="top" class="noborder">
EOT;
            $discovery .="<textarea name=\"ip_list\" id=\"ip_list\" cols=\"32\" rows=\"8\">".(($ip_list_selected=="") ? "$editdata[meth_TARGET]":"$ip_list_selected")."</textarea>";
            $discovery .= <<<EOT
			</td>
			<td valign="top" style="text-align:left" class="noborder">
				<div id="htree" style="width:300px"></div>
			</td>
			</tr></table>
          </div>
          <div id="idTarget2" class="forminput">
            <table width="100%" style="border:0;">
              <tr>
                <td align="Right" width="30%"  >Range Start</td>
                <td><input type="text" name="ip_start" value=""></td>
              </tr>
              <tr>
                <td align="Right" width="30%" >Range End</td>
                <td><input type="text" name="ip_end" value=""></td>
              </tr>
            </table>
          </div>
          <div id="idTarget3" class="forminput">
            <textarea name="named_list" cols="32" rows="8"></textarea>
          </div>
          <div id="idTarget4" class="forminput">
            <input type="text" name="cidr" value="">
          </div>
          <div id="idTarget5" class="forminput">
            <table width="100%" style="border:0;">
              <tr>
                <td align="Right" width="30%" ></td>
                <td><select name="subnet">
                  <option value="" >Select A Subnet to Scan</option>
EOT;

if ( $uroles['admin'] || $uroles['auditAll'] ) {
	 $discovery .= "<option value='ALL' >Audit All Subnets - (SINGLE JOB)!!!</option>";
     $query_filter = "AND t1.tiScanApproval='1'";
} else {
     $query_filter = "AND t4.pn_uname = '$username'";
}
     #$query = "SELECT distinct t1.id, t1.site_code, t1.CIDR
     #          FROM vuln_subnets t1
     #          LEFT JOIN vuln_sites t2 ON t1.site_code = t2.site_code
     #          LEFT JOIN vuln_org_sites t3 ON t2.id = t3.siteID
     #          LEFT JOIN vuln_org_users t4 ON t3.orgID = t4.orgID
     #          WHERE t1.status != 'available' $query_filter
     #          ORDER BY t1.site_code, CIDR";

     //$result=$dbconn->execute($query);
     //while (!$result->EOF) {
     //     list($subid, $scode, $sname)=$result->fields;
     //     if ( $editdata['fk_name'] == $sname ) { $selected= "SELECTED"; } else { $selected=""; }
     //     $discovery .= "<option value=\"$sname\" $selected >[$scode] $sname</option>";
     //     $result->MoveNext();
     //}

                $discovery .= <<<EOT
                </select></td>
              </tr>
            </table>
          </div>
          <div id="idTarget6" class="forminput">
            <table width="100%" style="border:0;">
              <tr>
                <td align="Right" width="30%" ></td>
                <td><select name="system">
                  <option value="" >Select A System to Scan</option>
EOT;

if ( $uroles['admin'] || $uroles['auditAll'] ) {

} else {
     $query_filter = "AND t2.pn_uname = '$username'";
}
     #$query = "SELECT distinct t1.id, t1.acronym, t1.name
     #          FROM vuln_systems t1
     #          LEFT JOIN vuln_system_users t2 ON t2.sysID = t1.id
     #          WHERE t1.deleted='0' $cquery_like AND t1.status='assigned' $query_filter
     #          ORDER BY t1.site_code, acronym";

     #$result=$dbconn->execute($query);
     #while (!$result->EOF) {
     #     list($subid, $scode, $sname)=$result->fields;
     #     if ( $editdata['fk_name'] == $scode ) { $selected= "SELECTED"; } else { $selected=""; }
     #     $discovery .= "<option value=\"$scode\" $selected>[$scode] $sname</option>";
     #     $result->MoveNext();
     #}

                $discovery .= <<<EOT
                </select></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </td>
  </tr>
</table>
</tr></td></table>
EOT;
//if(!$scheduler && !$enScanRequestImmediate) {
//   $discovery .= "<script language=javascript>showLayer('idSched', 3);</script>";
//}
   $discovery .= $show;
   return $discovery;
}

function edit_schedule ( $sched_id ) {
    global $uroles, $editdata, $scheduler, $username, $useremail, $dbconn;

    logAccess( "USER $username CHOSE EDIT SCHEDULE $sched_id" );

    $sql_access = "";
    if ( ! $uroles['admin'] ) { $sql_access = "AND username='$username'"; }

    $query = "SELECT id, name, username, fk_name, job_TYPE, schedule_type, day_of_week, 
                     day_of_month, time, email, meth_TARGET, meth_CRED, 
                     meth_VSET, meth_Wcheck, meth_Wfile, meth_Ucheck, 
		     meth_TIMEOUT, scan_ASSIGNED
              FROM vuln_job_schedule 
	      WHERE id = '$sched_id' $sql_access";
    $result = $dbconn->execute($query);
    $editdata = $result->fields;
    $editdata['authorized'] = $editdata['meth_Ucheck'];

    if ( $editdata['id'] == $sched_id ) {
       main_page( $job_id, "editrecurring" );
    } else {
  //logAccess( "INVALID SCHEDULE $sched_id" );
    }
}

function rerun ( $job_id ) {
   global $uroles, $editdata, $scheduler, $username, $useremail, $dbconn;

    logAccess( "USER $username CHOSE TO RERUN SCAN $job_id" );

    $sql_access = "";
    if ( ! $uroles['admin'] ) { $sql_access = "AND username='$username'"; }    

    $query = "SELECT id, name, fk_name, notify, job_TYPE, meth_SCHED, meth_TARGET, 
                     meth_CRED, meth_VSET, meth_Wcheck, meth_Wfile, 
		     meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED, authorized
              FROM vuln_jobs WHERE id = '$job_id' $sql_access";
    $result = $dbconn->execute($query);
    #list( $sname, $notify_email, $job_type, $schedule_type, $timeout, $SVRid, $sid, $targetlist ) = $result->fields;
    $editdata = $result->fields;

    if ( $editdata['id'] == $job_id ) {
       main_page( $job_id, "rerun" );
    } else {
  //logAccess( "INVALID JOBID $job_id" );
       echo "<p><font color=red>INVALID JOB ID</font></p>";
    }

}

function getCredentialId ( $cred_type, $passstore, $credid, $acc, $domain, $accpass, $acctype, $passtype ) {
   global $scheduler, $allowscan, $uroles, $username, $schedOptions, $adminmail, $mailfrom, $dbk, $dbconn;

    if ( $cred_type == "E" ) {
      if ( $acc != "" && $accpass != "" && $acctype != "" && $passstore != "" ) {
         if ( $domain == "" ) { $sdomain = "Null"; } else { $sdomain = "'$domain'"; }
         $insert_time =  date("YmdHis");
         if ($accpass!="" && !strstr($accpass,'ENC{')) {  // not encrypted
            $cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
            mcrypt_generic_init($cipher, $dbk,substr($dbk,12, 8));
            $encrypted_val = mcrypt_generic($cipher,$accpass);
            $accpass = "ENC{" . base64_encode($encrypted_val) . "}";
            mcrypt_generic_deinit($cipher);
         }

         if ( $passstore == "O" ) {
            $query = "SELECT t1.org_code 
	              FROM vuln_orgs t1
                        LEFT JOIN vuln_org_users t2 ON t1.id = t2.orgID
                      WHERE t2.pn_uname = '$username'";
            $result = $dbconn->execute($query);
            list( $org ) = $result->fields;
         }
         $query = "INSERT INTO vuln_credentials ( pn_uname, account, password, domain, password_type, ACC_TYPE,
              STORE_TYPE, ORG, select_key ) VALUES ( '$username', '$acc', '$accpass', $sdomain, 'Password',
              '$acctype', '$passstore', '$org', '$insert_time' ) ";

         
         if ($dbconn->execute($query) === false) {
            echo "Error creating scan job: " .$dbconn->ErrorMsg();
       //logAccess( "Error saving credentials $auname:" . $dbconn->ErrorMsg() );
            $error = 1;
            exit;
         } else {
            $query2 = "SELECT id FROM vuln_credentials WHERE pn_uname='$username' AND select_key='$insert_time'";
            $result2 = $dbconn->execute($query2);
            list( $tmpID ) = $result2->fields;
            return "'$tmpID'";
         }

      }

   } 

   if ( $cred_type == "S" ) {
      if ( $credid != "" ) {
         return "'$credid'";
      }
   }

   return;

}

function submit_scan( $op, $sched_id, $sname, $notify_email, $schedule_type, $ROYEAR,$ROMONTH, $ROday,
     $time_hour, $time_min, $dayofweek, $dayofmonth, $timeout, $SVRid, $sid, $tarSel, $ip_list,
     $ip_start, $ip_end,  $named_list, $cidr, $subnet, $system, $cred_type, $credid, $acc, $domain,
     $accpass, $acctype, $passtype, $passstore, $wpolicies, $wfpolicies, $upolicies, $custadd_type, $cust_plugins,
     $is_enabled, $hosts_alive, $scan_locally, $nthweekday, $semail) {

     global $wdaysMap, $daysMap, $allowscan, $uroles, $username, $schedOptions, $adminmail, $mailfrom, $dbk, $dbconn;
     

     $notify_email = str_replace( ";", ",", $notify_email );
     $requested_run = "";
     $jobType="M";
     $recurring = False;
     $targets = array();
     $time_value = "";
     $profile_desc =  getProfileName( $sid );
     $target_list = "";
     $need_authorized = "";
     $request="";
     $plugs_list="NULL";
     $fk_name="NULL";
     $target_list="NULL";
     $tmp_target_list="";
     $jobs_names = array();
     $sjobs_names = array();

        
     //$I3crID = getCredentialId ( $cred_type, $passstore, $credid, $acc, $domain, $accpass, $acctype, $passtype );
     $I3crID = "";
     
	 if ( $hosts_alive == "1" ) { // option: Only scan hosts that are alive
        $I3crID = "1";
     }
     else
        $I3crID = "0";

     if ( $custadd_type == "" ) { $custadd_type = "N"; }
     if ( $custadd_type != "N" && $cust_plugins != "" ) {
     	  $plugs_list="";
          $vals=preg_split( "/\s+|\r\n|,|;/", $cust_plugins );
          foreach($vals as $v) {
               $v=trim($v);
               if ( strlen($v)>0 ) {
                    $plugs_list .= $v . "\n";
               }
          }
          $plugs_list = "'".$plugs_list."'";
     }

/*     echo <<<EOT
     <h3>Job Details:</h3>
     <center>
     <table>
     <tr><th align="right">Job Name</th><td>$sname</td></tr>
     <tr><th align="right">Notify</th><td>$notify_email</td></tr>
     <tr><th align="right">Timeout</th><td>$timeout</td></tr>
     <tr><th align="right">Profile</th><td>$profile_desc</td></tr>
     <tr><th></th><td>&nbsp;</td></tr>
     <tr><th align="right">Schedule Info</th><td>&nbsp;</td></tr>
EOT;*/

   $arrTime = localtime(time(), true);
   $year = 1900 + $arrTime["tm_year"];
   $mon = 1 + $arrTime["tm_mon"];
   $mday =  $arrTime["tm_mday"];
   $wday =  $arrTime["tm_wday"];
   $hour = ($arrTime["tm_hour"]<10) ? "0".$arrTime["tm_hour"] : $arrTime["tm_hour"];
   $min = ($arrTime["tm_min"]<10) ? "0".$arrTime["tm_min"] : $arrTime["tm_min"];
   $sec = ($arrTime["tm_sec"]<10) ? "0".$arrTime["tm_sec"] : $arrTime["tm_sec"];
      	
   $timenow = $hour.$min.$sec;
   
   if ( $time_hour ) { $hour = $time_hour; }
   if ( $time_min ) { $min = $time_min; }
   
   #echo "hour=$hour<br>";
   #$hour = $hour - $tz_offset;
   #echo "offset=$tz_offset<br>hour=$hour<br>";
   #if ( $hour < "0" ) { echo "change 1<br>"; $hour = $hour + 24; }
   #if ( $hour >= "24" ) { echo "change 2<br>"; $hour = $hour - 24; }
   #echo "hour_changed=$hour<br>";
   
   $run_wday = $wdaysMap[$dayofweek];
   #echo "run_day=$run_wday<br>dayofweek=$dayofweek<br>";
   $run_time = sprintf("%02d%02d%02d",  $time_hour, $time_min, "00" );
   $run_mday = $dayofmonth;     
   $time_value = "$time_hour:$time_min:00";  
   //echo "schedule_type: ".$schedule_type;
   //echo "$run_time : $timenow\n"; exit();
   $ndays = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
   
   switch($schedule_type) {
   case "N":

          $requested_run = sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec );
          $sched_message = "No reccurring Jobs Necessary";

      break;
   case "O":
   
          $requested_run = sprintf("%04d%02d%02d%06d", $ROYEAR, $ROMONTH, $ROday, $run_time );
          $sched_message = "No reccurring Jobs Necessary";
          //var_dump($schedule_type);
          $recurring = True;
          $reccur_type = "Run Once";

      break;
   case "D":
   
          if ( $run_time > $timenow ) {
	          $next_day = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("y")));
          } else {
	          $next_day = date("Ymd", mktime(0, 0, 0, date("m"), date("d")+1, date("y")));
          }
          $requested_run = sprintf("%08d%06d", $next_day, $run_time );
          $recurring = True;
          $sched_message = "Schedule Reccurring";
          $reccur_type = "Daily";
          
      break;
   case "W":
            if ($run_wday == $wday && $run_time > $timenow) {
                $next_day = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("y")));
            }
            else {
                $next_day = date("Ymd", strtotime("next ".$ndays[$run_wday]));
            }
          /*if ( $run_wday > $wday || ( $run_wday == $wday && $run_time > $timenow )) {
	          $next_day = date("Ymd", mktime(0, 0, 0, date("m"), date("d")+($run_wday-$wday), date("y")));
          } else {
	          $next_day = date("Ymd", mktime(0, 0, 0, date("m"), date("d")+7, date("y")));
          }*/
          $requested_run = sprintf("%08d%06d", $next_day, $run_time );
          $recurring = True;
          $sched_message = "Schedule Reccurring";
          $reccur_type = "Weekly";
          
      break;
   case "M":

          if ( $run_mday > $mday || ( $run_mday == $mday && $run_time > $timenow )) {
              $next_day = date("Ymd", mktime(0, 0, 0, date("m"), $run_mday, date("y")));
              #echo "date selected is in the future<br>";
          } else {
              $next_day = date("Ymd", mktime(0, 0, 0, date("m")+1, $run_mday, date("y")));
               #echo "date selected is in the past<br>";
          }
          
          #echo "run_mday=$run_mday mday=$mday rtime=$run_time now=$timenow next_day=$next_day<br>";
          
          $requested_run = sprintf("%08d%06d", $next_day, $run_time );
          $recurring = True;
          $sched_message = "Schedule Reccurring";
          $reccur_type = "Montly";
          
      break;
   case "NW":
        $dayweektonum = array(
            "Mo" => 1,
            "Tu" => 2,
            "We" => 3,
            "Th" => 4,
            "Fr" => 5,
            "Sa" => 6,
            "Su" => 7);
   
        $next_day = nthweekdaymonth(date("Y"), date("n"), 1, $dayweektonum[$dayofweek], $nthweekday); 
        
        
        $requested_run = sprintf("%08d%06d", $next_day, $run_time );
        
        $dayofmonth = $nthweekday;
        
        $recurring = True;
        $sched_message = "Schedule Reccurring";
        $reccur_type = "Nth weekday of the month";
          
      break;
   default:

      break;
   }

   	 //if ( $schedule_type != "N" ){ 
        //$requested_run  = switchTime_TimeZone( $requested_run, "server" );
   	 //}
   	 
/*     echo <<<EOT

     <tr><th align="right">Type</th><td>$schedOptions[$schedule_type]</td></tr>
     <tr><th align="right">First Occurrence</th><td>$requested_run</td></tr>
     <tr><th align="right">Recurring</th><td>$sched_message</td></tr>
     <tr><th align="right">&nbsp;</th><td></td></tr>
     <tr><th colspan="2">Target Selection</th></tr>
EOT;*/

   switch($tarSel) {
   case "1":     #SINGLE
          $vals=preg_split( "/\s+|\r\n|;/", $ip_list );
          foreach($vals as $v) {
               $v=trim($v);
               if ( strlen($v)>0 ) {
                    array_push($targets, $v );
               }
          }

      break;
   case "2":     #IP RANGE

          if ( $ip_start || $ip_end ) {
               if ( $ip_start && $ip_end ) {
                    $targets = range2List( $ip_start, $ip_end );
               } else {
               //     echo "<tr><td colspan=2>incomplete target list</td></tr>";
               }
          }

      break;
   case "3":     #NAMED TARGET
             $vals=preg_split( "/\s+|\n|,|;/", $named_list );
          foreach($vals as $v) {
               $v=trim($v);
               if ( strlen($v)>0 ) {
                    $ip = gethostbyname($v);
                    if ( strlen($ip)>0) {
                         array_push($targets, $ip );
                    } else {
                    //     echo "<tr><td colspan=2>$v&nbsp;&nbsp;Name could not be resolved</td></tr>";
                    }
               }
          }
      break;
   case "4":     #SUBNET
          array_push($targets, $cidr );

      break;
   case "5":
          if ( $uroles['auditAll'] && $subnet == "ALL" ) {
             array_push($targets, "all_live_subnets" );
          } else {
             array_push($targets, $subnet );
          }
          $fk_name = "'".$subnet."'";
      break;
   case "6":
          #$query = "SELECT isso_email, admin_sys, admin_dba, admin_network from vuln_systems WHERE acronym='$system'";
          #$result = $dbconn->Execute($query);
          #list( $isso_poc, $poc_sa, $poc_dba, $poc_network ) = $result->fields;

          $all_pocs = $isso_poc;
          if ( $all_pocs != "" && $poc_sa != "" ) { $all_pocs .= ", $poc_sa"; }
          if ( $all_pocs != "" && $poc_dba != "" ) { $all_pocs .= ", $poc_dba"; }
          if ( $all_pocs != "" && $poc_network != "" ) { $all_pocs .= ", $poc_network"; }
          $notify_email = $all_pocs;   
      
         $fk_name = "'".$system."'";

      break;
   default:          #INPUT FILE

      break;
   }

   if ( $tarSel < "4" ) {
       foreach ( $targets as $hostip ) {
           if ( !$allowscan && !inrange( $hostip, $dbconn ) ) {
               $need_authorized .= $hostip . "\n";
           }
           $tmp_target_list .= $hostip . "\n";
           //echo "<tr><td colspan=2>$hostip</td></tr>";
       }
       if ( $need_authorized != "" ) {
           //echo "<tr><th colspan=2><font color=red>NOT IN APPROVED ZONE</font></th></tr>";
           $html_needs_auth = str_replace( "\n", "<br>", $need_authorized );
           //echo "<tr><td colspan=2>$html_needs_auth</td></tr>";
       }
   } elseif ( $tarSel == "4") {
       $tmp_target_list=$cidr;
       //echo "<tr><td colspan=2>$cidr</td></tr>";
   } elseif ( $tarSel == "6") {
       $jobType="S";
       if ( $recurring == True ) {
          #$tmp_target_list="";
          #DO NOT PUT THE LIST OF IP'S IN UNTIL THE JOB STARTS FOR REOCCURING ( LIST MAY BE FREQUENT TO CHANGE )
       } else {
          /*$query = "SELECT hostip from vuln_systems t1
             LEFT JOIN vuln_system_hosts t2 on t2.sysID = t1.id
             WHERE t1.acronym='$system'";
          $result = $dbconn->Execute($query);

          while ( !$result->EOF ) {
             list($hostip) = $result->fields;
             if ( strlen($hostip)>0) {
                $tmp_target_list .= "$hostip\n";
                array_push($targets, $hostip );
             }
             $result->MoveNext();
          }*/
       }
	   
//       echo "<tr><td colspan=2>$system</td></tr>";


   } else {
       $jobType="C";
       $tmp_target_list=$subnet;
//       echo "<tr><td colspan=2>$subnet</td></tr>";
   }

   if ( !( $tarSel == "6" && $recurring == True ) && count( $targets ) == 0 ) {
//      echo "<p><center><font color=red>Missing Host Selection or BAD LIST:$targets[0]<br><br></font>"
//         ."[ <a href=\"javascript:history.go(-1)\">Go Back</a> ]</center></p>";
 //logAccess( "USER $username Fubared: Missing Host Selection or BAD LIST:$targets[0]" );
      require_once("footer.php");
      exit;
   } elseif ( ! $sname ) {
//      echo "<p><center><font color=red>Missing or BAD SNAME:[$sname]<br><br></font>"
//         ."[ <a href=\"javascript:history.go(-1)\">Go Back</a> ]</center></p>";
 //logAccess( "USER $username Fubared something on job name [$sname]" );
      require_once("footer.php");
      exit;
   }

   if ( $subnet == "" or $subnet== "0" ) { $subnet = "Null"; } else { $subnet = "'$subnet'"; }
   if ( $SVRid == "" or $SVRid == "Null" ) { $SVRid = "Null"; } else { $SVRid = "'$SVRid'"; }
   if ( $tmp_target_list != "" ) { $target_list = "'".$tmp_target_list."'"; }

   $arrChecks = array( "w" => $wpolicies, "f" => $wfpolicies, "u" => $upolicies );
   $arrAudits = array('w', 'f', 'u' );

   foreach ( $arrChecks as $check => $policydata ) {
      $i = 1;
      $audit_data = "";
      if ( $policydata ) {
         if ( $i <=5 ) {
            foreach( $policydata as $policy ) {
               $audit_data .= "$policy\n";
               $i++;
            }
         }
      }
      if ( $audit_data != "" ) {
         $arrAudits[$check] = "'$audit_data'"; 
      } else {
         $arrAudits[$check] = "NULL";
      }
   }
   $insert_time =  date("YmdHis");   

//   if ( $need_authorized != "" || !($uroles['nessus']) ) {
//      $jobType="R";  #REQUEST JOB
//      #DO not wrap $subnet / $SVRid with ticks '' as 'Null' is not Null
//      $query = "INSERT INTO vuln_jobs ( name, fk_name, username, job_TYPE, meth_SCHED, meth_TARGET, meth_CRED, 
//          meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wcheck, meth_Wfile, meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED, scan_SUBMIT, 
//          scan_next, scan_PRIORITY, status, notify ) VALUES ( '$sname', $fk_name, '$username', '$jobType', '$schedule_type', $target_list, $I3crID, 
//          '$sid', '$custadd_type', $plugs_list, $arrAudits[w], $arrAudits[f], $arrAudits[u], '$timeout', $SVRid, '$insert_time', 
//          '$requested_run', '3' , 'H', '$notify_email' )";
//      $request = "for Approval";

//      $subject = "Scan request [$sname]";
//      $message = "HELLO SOC TEAM, \tThe following User [ $username ] has requested a scan against:\n"
//         ." $target_list\n\nPlease Promptly Accept/Reject the request!"
//         ."Thank You\n\nThe SOC TEAM!\n";

     // mail($adminmail, $subject, $message, "From: $mailfrom\nX-Mailer: PHP/" . phpversion());

   //   echo "needs authorization<br>";
 //logAccess( "USER $username Submitted Scan Request [$sname]" );
  // } else {

    require_once("classes/Host_sensor_reference.inc");
    require_once("classes/Net_sensor_reference.inc");
    require_once("classes/Net.inc");
    require_once("classes/Scan.inc");
    require_once("classes/Sensor.inc");
      
    //Check Permissions
    $allowed = array();
    $notallowed = array();
    $ftargets = explode("\\r\\n", $target_list);
    foreach ($ftargets as $ftarget) {
        $ftarget = preg_replace("/\r|\n|\t|\s|\'/", "", $ftarget);
        if (preg_match("/\//", $ftarget) && Session::netAllowed($dbconn, Net::get_name_by_ip($dbconn,$ftarget))){ //, $username
            $allowed[] = $ftarget;
        }
        else if( Session::hostAllowed($dbconn, $ftarget) ) { // , $username
            $allowed[] = $ftarget;
        }
        else {
            $notallowed[] = $ftarget;
        }
    }
    if(count($allowed)>0) {
        $forced_server="";
        if ($SVRid!="Null") {
            $query = "SELECT hostname FROM vuln_nessus_servers WHERE id=$SVRid";
            $result = $dbconn->execute($query);
            list($forced_server) = $result->fields;
        }
        $all_sensors = array();
        $sensor_list = Sensor::get_all($dbconn);
        foreach ($sensor_list as $s) $all_sensors[$s->get_ip()] = $s->get_name();
        // remote nmap
        $rscan = new RemoteScan("","");
        if ($rscan->available_scan()) {
            $reports = $rscan->get_scans();
            $ids = (is_array($reports)) ? array_keys($reports) : array();
        } else {
            $ids = array();    
        }
        //if ($forced_server!="") $ids = array_merge(array($forced_server),$ids);
        //$tsjobs = explode("\\r\\n", $target_list);
        $sgr = array();
        $unables = array();
        $tsjobs = $allowed;

        foreach( $tsjobs as $tjobs ){
            $tjobs = preg_replace("/\r|\n|\t|\s|\'/", "", $tjobs);
            if (preg_match("/\//",$tjobs)) {
                $sensor = Net_sensor_reference::get_list_array($dbconn,$tjobs); 
            } else {
                $sensor = Host_sensor_reference::get_list_array($dbconn,$tjobs);
            }
            if ($forced_server!="") $sensor = array_merge(array($forced_server),$sensor);
            
            if(Session::am_i_admin() && count($sensor)==0 && $forced_server=="") {
                $local_ip = `grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; 
                $local_ip = trim($local_ip);
                $results = $dbconn->Execute("SELECT name FROM vuln_nessus_servers WHERE hostname like '$local_ip'");
                if($results->fields["name"]!="") { 
                    $sensor[] = $local_ip;  
                }
            }
            
            //var_dump($forced_server);
            // select best sensor with available nmap and vulnmeter
            $selected = "";
            foreach ($sensor as $sen) {
                $properties = Sensor::get_properties($dbconn, $sen);
                $withnmap = in_array($all_sensors[$sen],$ids) || !$hosts_alive;
                //echo "$sen:".$all_sensors[$sen].":$withnmap || $scan_locally:".$properties["has_vuln_scanner"]." || $SVRid:$forced_server<br>\n";
                if ($selected=="" && ($withnmap || $scan_locally) && ($properties["has_vuln_scanner"] || $SVRid!="Null")) {
                    $selected = ($SVRid!="Null" && $all_sensors[$sen]!="") ? $all_sensors[$sen] : $sen;
                    //echo "sel:$selected<br>\n";
                    break;
                }
            }
            if ($selected!="") $sgr[$selected][] = $tjobs;
            else $unables[] = $tjobs;
        }
        
        $query = array();

        if ( $op == "editrecurring" && $sched_id > 0 ) {
            //$query[] = "UPDATE vuln_job_schedule SET name='$sname', fk_name=$fk_name, job_TYPE='$jobType',
            //   schedule_type='$schedule_type', day_of_week='$dayofweek', day_of_month='$dayofmonth', time='$time_value',
            //   email='$notify_email', meth_TARGET=$target_list, meth_CRED=$I3crID, meth_VSET='$sid', meth_CUSTOM='$custadd_type',
            //   meth_CPLUGINS=$plugs_list, meth_Wcheck=$arrAudits[w], meth_Wfile=$arrAudits[f], meth_Ucheck=$arrAudits[u],
            //   meth_TIMEOUT='$timeout', next_CHECK='$requested_run' WHERE id='$sched_id' LIMIT 1";
         
            $query[] = "UPDATE vuln_job_schedule SET name='$sname', username='$username', fk_name='".Session::get_session_user()."', job_TYPE='$jobType',
                        schedule_type='$schedule_type', day_of_week='$dayofweek', day_of_month='$dayofmonth', time='$time_value',
                        meth_TARGET=$target_list, meth_CRED=$I3crID, meth_VSET='$sid', meth_CUSTOM='$custadd_type',
                        meth_CPLUGINS=$plugs_list, meth_Wcheck=$arrAudits[w], meth_Wfile=$semail, meth_Ucheck='$scan_locally',
                        meth_TIMEOUT='$timeout', next_CHECK='$requested_run' WHERE id='$sched_id' LIMIT 1";
                        
                        
          //logAccess( "USER $username Submitted $reccur_type JOB Schedule [$sname]" );
        }
        elseif ( $recurring ) {
            //if ($SVRid=="Null") {
                $i = 1;
                foreach ($sgr as $notify_sensor => $targets) {
                    $target_list = implode("\n",$targets);
                    $query[] = "INSERT INTO vuln_job_schedule ( name, username, fk_name, job_TYPE, schedule_type, day_of_week, day_of_month, 
                                time, email, meth_TARGET, meth_CRED, meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wcheck, meth_Wfile, 
                                meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED, next_CHECK, createdate, enabled  ) VALUES ( '$sname', '$username', '".Session::get_session_user()."', '$jobType',
                                '$schedule_type', '$dayofweek', '$dayofmonth', '$time_value', '$notify_sensor', '$target_list',
                                $I3crID, '$sid', '$custadd_type', $plugs_list, $arrAudits[w], $semail, '$scan_locally',
                                '$timeout', $SVRid, '$requested_run', '$insert_time', '1' ) ";
                    $sjobs_names [] = $sname.$i;
                    $i++;
                }
            //} 
            //else {
            //    $query[] = "INSERT INTO vuln_job_schedule ( name, username, fk_name, job_TYPE, schedule_type, day_of_week, day_of_month, 
            //                time, email, meth_TARGET, meth_CRED, meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wcheck, meth_Wfile, 
            //                meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED, next_CHECK, createdate, enabled  ) VALUES ( '$sname', '$username', $fk_name, '$jobType',
            //                '$schedule_type', '$dayofweek', '$dayofmonth', '$time_value', '', '$target_list',
            //                $I3crID, '$sid', '$custadd_type', $plugs_list, $arrAudits[w], $arrAudits[f], '$scan_locally', 
            //                '$timeout', $SVRid, '$requested_run', '$insert_time', '1' ) ";
            //    $sjobs_names [] = $sname;
            //}
          //logAccess( "USER $username Submitted $reccur_type JOB Schedule [$sname]" );
        } 
        else {
            //if ($SVRid=="Null") {
                $i = 1;
                foreach ($sgr as $notify_sensor => $targets) {
                    $target_list = implode("\n",$targets);
                    $query[] = "INSERT INTO vuln_jobs ( name, username, fk_name, job_TYPE, meth_SCHED, meth_TARGET,  meth_CRED,
                        meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wcheck, meth_Wfile, meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED,
                        scan_SUBMIT, scan_next, scan_PRIORITY, status, notify, authorized, author_uname ) VALUES ( '$sname',
                        '$username', '".Session::get_session_user()."', '$jobType', '$schedule_type', '$target_list', $I3crID, '$sid', '$custadd_type', $plugs_list,
                        $arrAudits[w], $semail, $arrAudits[u], '$timeout', $SVRid, '$insert_time', '$requested_run', '3',
                        'S', '$notify_sensor', '$scan_locally', 'ACL' ) "; 
                    $jobs_names [] = $sname.$i;
                    $i++;
                }
            //} 
            //else {
            //    $query[] = "INSERT INTO vuln_jobs ( name, username, fk_name, job_TYPE, meth_SCHED, meth_TARGET,  meth_CRED, 
            //        meth_VSET, meth_CUSTOM, meth_CPLUGINS, meth_Wcheck, meth_Wfile, meth_Ucheck, meth_TIMEOUT, scan_ASSIGNED,
            //        scan_SUBMIT, scan_next, scan_PRIORITY, status, notify, authorized, author_uname ) VALUES ( '$sname',
            //        '$username', $fk_name, '$jobType', '$schedule_type', '$target_list', $I3crID, '$sid', '$custadd_type', $plugs_list,
            //        $arrAudits[w], $arrAudits[f], $arrAudits[u], '$timeout', $SVRid, '$insert_time', '$requested_run', '3',
            //        'S', '', '1', 'ACL' ) ";
            //    $jobs_names [] = $sname;
            //}
    //logAccess( "USER $username Submitted a Run Once JOB Schedule [$sname]" );
        }
   //}
   
   //print_r($allowed); echo "<br>"; print_r($notallowed); echo "<br>"; print_r($query); echo "<br>"; print_r($unables);echo "<br>";print_r($sgr);echo "<br>"; print_r($SVRid); exit();
   //if ( $uroles['debug'] ) { echo "query=$query<br>"; }
   //$result = $dbconn->execute($query);
   
        $query_insert_time = gen_strtotime( $insert_time, "" );
        foreach ($query as $sql) {
			$sql = str_replace(", ',",", '',",str_replace("''","'",$sql));
            if ($dbconn->execute($sql) === false) {
                echo _("Error creating scan job").": " .$dbconn->ErrorMsg();
                $error = 1;
            }
            else {
                if ( $op == "editrecurring" && $sched_id > 0 ) {
                    $query2 = "SELECT id FROM vuln_job_schedule WHERE id='$sched_id' AND username='$username'";
                }
                elseif ( $recurring ) {
                    $query2 = "SELECT id FROM vuln_job_schedule WHERE createdate='$query_insert_time' AND username='$username'";
                }
                else {
                    $query2 = "SELECT id FROM vuln_jobs WHERE scan_SUBMIT='$query_insert_time' AND username='$username'";
                    $query2 = "SELECT id FROM vuln_jobs WHERE scan_SUBMIT='$query_insert_time' AND username='$username'";
                }
                $result2 = $dbconn->execute($query2);
                list( $jid ) = $result2->fields;

                if ( $op == "editrecurring" && $jid > 0 ) {
                    echo "<br><center>"._("Successfully Updated Recurring Job")."</center>";
                    if(count($notallowed)==0 && count($unables)==0){
                        ?><script type="text/javascript">
                        //<![CDATA[
                        document.location.href='manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs'; 
                        //]]>
                        </script><?
                    }
                    //logAccess( "Updated Recurring Job [ $jid ]" );
                }
                elseif ( $jid ) {
                    echo "<br><center>"._("Successfully Submitted Job")." $request</center>";
                    //logAccess( "Submitted Job [ $jid ] $request" );
                    
                    foreach ($jobs_names as $job_name){
                        $infolog = array($job_name);
                        Log_action::log(66, $infolog);
                    }
                    foreach ($sjobs_names as $job_name){
                        $infolog = array($job_name);
                        Log_action::log(67, $infolog);
                    }
                    
                    if(count($notallowed)==0 && count($unables)==0){
                        ?><script type="text/javascript">
                        //<![CDATA[
                        document.location.href='manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs';
                        //]]>
                        </script><?
                    }
                }
                else {
                    echo "<br><center>"._("Failed Job Creation")."</center>";
                    //logAccess( "Failed Job Creation" );
                    if(count($notallowed)==0 && count($unables)==0){
                        ?><script type="text/javascript">
                        //<![CDATA[
                        document.location.href='manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs';
                        //]]>
                        </script><?
                    }
                }
            }
        }
    
    } //end count($alowed)>0
    if(count($notallowed)>0 || count($unables)>0) {
        echo "<center>";
        echo "<table class=\"noborder\" width=\"400\" style=\"background-color:transparent;\">";
        echo "<tr><td class=\"nobborder\" style=\"text-align:left;\"><b>"._("Errors Found").":</b></td></tr>";
        if(count($notallowed)>0) {
            echo "<tr><td class=\"nobborder\" style=\"text-align:left;\">"._("User")." <b>$username</b> "._("is not allowed for the following targets").":</td></tr>";
            foreach ($notallowed as $target) {
                echo "<tr><td class=\"nobborder\" style=\"text-align:left;padding-left:5px;\">- <b>$target</b></tr>";
            }
            echo "<tr height=\"30\"><td class=\"nobborder\">&nbsp;</td></tr>";
        }
        if(count($unables)>0) {
            echo "<tr><td class=\"nobborder\" style=\"text-align:left;\">"._("No remote vulnerability scanners available for the following targets").":</td></tr>";
            foreach ($unables as $target) {
                echo "<tr><td class=\"nobborder\" style=\"text-align:left;padding-left:5px;\">- <b>$target</b></tr>";
            }
            echo "<tr height=\"30\"><td class=\"nobborder\">&nbsp;</td></tr>";
        }        
        echo "<tr><td class=\"nobborder\" style=\"text-align:center;\">";
        echo "<form action=\"sched.php\" method=\"post\">";
        ?>
              <input type="hidden" name="sname" value="<?=$sname?>"/>
              <? $SVRid = str_replace("'","",$SVRid); ?>
              <input type="hidden" name="SVRid" value="<?=$SVRid?>"/>
              <input type="hidden" name="sid" value="<?=$sid?>"/>
              <input type="hidden" name="timeout" value="<?=$timeout?>"/>
              <input type="hidden" name="schedule_type" value="<?=$schedule_type?>"/>
              <input type="hidden" name="ROYEAR" value="<?=$ROYEAR?>"/>
              <input type="hidden" name="ROMONTH" value="<?=$ROMONTH?>"/>
              <input type="hidden" name="ROday" value="<?=$ROday?>"/>
              <input type="hidden" name="time_hour" value="<?=$time_hour?>"/>
              <input type="hidden" name="time_min" value="<?=$time_min?>"/>
              <input type="hidden" name="dayofweek" value="<?=$dayofweek?>"/>
              <input type="hidden" name="nthweekday" value="<?=$nthweekday?>"/>
              <input type="hidden" name="dayofmonth" value="<?=$dayofmonth?>"/>
              <input type="hidden" name="ip_list" value="<?=str_replace("\\r\\n",";;",$ip_list)?>"/>
              <?if(is_numeric($username)) {?>
                <input type="hidden" name="entity" value="<?=$username?>"/>
              <?
              }
              else {?>
                <input type="hidden" name="user" value="<?=$username?>"/>
              <?
              }?>
              <input type="hidden" name="hosts_alive" value="<?=$hosts_alive?>"/>
              <input type="hidden" name="scan_locally" value="<?=$scan_locally?>"/> 
              <input type="hidden" name="semail" value="<?=$semail?>"/>
        <?
        echo "<input type=\"submit\" value=\""._("Back")."\" class=\"button\"/> &nbsp; ";
        echo "<input value=\""._("Continue")."\" class=\"button\" type=\"button\" onclick=\"document.location.href='manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs'\"></form>";
        echo "</td></tr>";
        echo "</table>";
        echo "</center>";
    
    }
   echo "</b></center>";

}

function auth_request($op, $submit, $rids) {
     global $dbconn;

     echo "<h2>Pending Scan Requests</h2>";

     //echo "<pre>$op = "; print_r($rids); echo "</pre>";
     if ($op != "" and !empty($rids)){
          switch($op) {
	       case "process":
	            process_requests($submit, $rids);
		    break;
               case "accept":
                    accept_request($rid);
                    break;
               case "reject":
                    reject_request($rid);
                    break;
	       default:
                    break;
          }
          echo "<br>";
     }

     $query="SELECT id, username, meth_VSET, meth_TARGET, scan_SUBMIT,
                    date_format(scan_NEXT,'%m/%d/%Y %T')
             FROM vuln_jobs 
	     WHERE job_TYPE= 'R' AND authorized='0'
             ORDER BY scan_NEXT";

     $result = $dbconn->execute($query);

echo <<<EOT
<form method="post" action="sched.php">
<input type=hidden name="op" value="process">
<input type=hidden name="disp" value="auth_request">
     <table summary="Request Details" width=100%>
          <tr>
          <th>Requested Scan Date</th>
          <th>Request Submit Date</th>
          <th>Requester</th>
          <th>Host IP(s)</th>
	  <th></th>
	  </tr>
EOT;


     while (!$result->EOF) {
          list ($rid, $rname, $rsid, $rhostip, $submit, $scantime) = $result->fields;
	  $rhostip = trim($rhostip); // get rid of any extra whitespace at the end

          echo "<tr>";
	  echo "<td>$scantime</td>";
	  echo "<td>$submit</td>";
	  
          //$requestor = getUserName($rname);
	  //echo "<td>$rname"; 
	  //if($requestor{'fname'} != "") { echo " - " . $requestor{'fname'};}
	  //if($requestor{'lname'} != "") { echo " " .  $requestor{'lname'}; }
	  //echo "</td>";
	  
	  //echo "<td>" . getProfileName( $rsid ) . "</td>";
	  $ips = explode("\n",$rhostip);
	  //echo "<pre>";
	  //print_r($ips);
	  //echo "</pre>";
	  $hosttext = array();
	  foreach ($ips as $ip) {
	     $hosttext[] = gethostbyaddr("$ip") . " ($ip)";
	  }
	  echo "<td>" . implode("<br>",$hosttext) . "</td>";
          echo "<td><input type=checkbox name='process[]' value='$rid'></td>";
          $result->MoveNext();
     }
     echo "</table>";
     echo "<input type=submit name=submit value='Reject Requests'>";
     echo "&nbsp;&nbsp;&nbsp;<input type=submit name=submit value='Approve Requests'>";
     echo "</form>";

}

function process_requests($submit, $rids) {
    global $uroles;

//    echo "<Pre>Processing Request\n";
//    echo "\$submit = $submit\n";
//    echo "\$rids = ";
//    print_r($rids);
    if( $uroles['admin'] || $uroles['scanRequest']) {
       if($submit == "Approve Requests") { 
//          echo "approving requests\n";
          $sub = "accept_request";
       } elseif ($submit == "Reject Requests") {
//          echo "rejecting requests\n";
          $sub = "reject_request";
       }
       foreach ($rids as $rid) {
//          echo "$sub($rid)\n";
          $sub($rid);
       }
    }
//    echo "</pre>";
}

function reject_request($rid) {
     global $siteBranding, $username, $useremail, $mailfrom, $dbconn;

     $admin_email = $useremail;

     $query = "SELECT username, notify, meth_TARGET, $name
               FROM vuln_jobs
               WHERE id='$rid' LIMIT 1";
     $result = $dbconn->execute($query);

     list($requestee, $req_email, $hostip, $name ) = $result->fields;

     echo "Denied Scan Request $name for: " . 
          str_replace("\n","<br>",$hostip);

     $curtime = date("Y-m-d H:i:s");
     $query = "Update vuln_jobs SET author_uname='$username', 
                                  authorized='-1', 
				  status='C', 
				  scan_NEXT = Null
               WHERE id = '$rid' LIMIT 1";

     $result = $dbconn->execute($query);

     $mailto = "$req_email";

     if ( $mailto != $admin_email ) { $mailto .= ", $admin_email"; }

     $subject = "$siteBranding scan request declined";
     $message = "HELLO, $requestee\n\n" .
          "Your scan request for $hostip has been declined at this time. ";

     mail($mailto, $subject, $message, "From: $mailfrom\nX-Mailer: PHP/" .
          phpversion());
//logAccess( "Rejected Scan Request for [ $requestee - $hostip ]" );

}

function accept_request($rid) {
     global $siteBranding, $username, $useremail, $mailfrom, $dbconn;

     $admin_email = $useremail;

     $query = "SELECT username, notify, meth_TARGET 
               FROM vuln_jobs
               WHERE id='$rid' LIMIT 1";
     $result = $dbconn->execute($query);

     list($requestee, $req_email, $hostip ) = $result->fields;

     echo "Accepted Scan Request for $hostip<br>";

     echo "<br>";

      $curtime = date("Y-m-d H:i:s");
     $query = "UPDATE vuln_jobs SET author_uname='$admin_name', 
                                  authorized='1', 
				  status='S'
               WHERE id='$rid' LIMIT 1";
     $result = $dbconn->execute($query);

     $mailto = "$req_email";

     if ( $mailto != $admin_email ) { $mailto .= ", $admin_email"; }

     $subject = "$siteBranding scan request declined";
     $message = "HELLO, $requestee\n\nAs per your request, a scan for ".
                "$hostip will begin shortly once a free scan slot is " .
		"available.\n\n";

     mail($mailto, $subject, $message, "From: $mailfrom\nX-Mailer: PHP/" .
          phpversion());
//logAccess( "Authorized Scan Request for [ $requestee - $hostip ]" );
}

function delete_scan( $job_id ) {
     global $uroles, $username, $useremail, $mailfrom, $dbconn;

     if ( $uroles['admin'] ) {
        $term_status = "Allowed";
        //echo "Scan Terminated";
        //echo "<br>";
        $query = "SELECT name, id, scan_SERVER, report_id, status FROM vuln_jobs WHERE id='$job_id' LIMIT 1";
        $result = $dbconn->execute($query);
        list($job_name, $kill_id, $nserver_id, $report_id, $status) = $result->fields;

        if($status=="R"){
            $query = "UPDATE vuln_nessus_servers SET current_scans=current_scans-5 WHERE id='$nserver_id' and current_scans>0 LIMIT 1";
            $result = $dbconn->execute($query);
        }
        //$query = "UPDATE vuln_jobs SET status='C' WHERE id='$kill_id' LIMIT 1";
        //$result = $dbconn->execute($query);
        
        $query = "DELETE FROM vuln_jobs WHERE id='$kill_id'";
        $result = $dbconn->execute($query);

        $query = "DELETE FROM vuln_nessus_reports WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);
        
        $query = "DELETE FROM vuln_nessus_report_stats WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);

        $query = "DELETE FROM vuln_nessus_results WHERE report_id='$report_id'";
        $result = $dbconn->execute($query);
        
        $infolog = array($job_name);
        Log_action::log(65, $infolog);
        
        ?><script type="text/javascript">
        //<![CDATA[
        document.location.href='manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs';
       //]]>
        </script><?
     } else {
        $term_status = "Denied";
     }

//logAccess( "TERMINATE SCAN: [ $term_status by $username ]" );

     //include("monitor.php");
}


switch($disp) {

   //case "auth_request":
   //   auth_request ( $op, $submit, $process );
   //break;

   case "create":
    if($error_message!=""){
        echo "<br><center><span style=\"color:red\"><b>$error_message</b></span></center><br>";
        main_page( $job_id, $op );
    }
    else {
        if($entity!="" && $entity!="none") $username = $entity;
        if($user!="" && $user!="none") $username = $user;
    
        submit_scan( $op, $sched_id, $sname, $notify_email, $schedule_type, $ROYEAR,$ROMONTH, $ROday,
        $time_hour, $time_min, $dayofweek, $dayofmonth, $timeout, $SVRid, $sid, $tarSel, $ip_list,
        $ip_start, $ip_end,  $named_list, $cidr, $subnet, $system, $cred_type, $credid, $acc, $domain,
        $accpass, $acctype, $passtype, $passstore, $wpolicies, $wfpolicies, $upolicies, $custadd_type, $cust_plugins,
        $is_enabled, $hosts_alive, $scan_locally, $nthweekday, $semail);
    }
   break;

   case "edit_sched":
      edit_schedule ( $sched_id );
   break;

   case "delete_scan":
      delete_scan ( $job_id );
   break;

   case "rerun":
      rerun ( $job_id );
   break;
   
   default:
      main_page( $job_id, $op );
      break;
}


function createHiddenDiv($name, $num, $data) {
   $text = "";
   $style = "";
   if($num == 0) {
      $style = "style='display: block;'";
   }
   else { $style = "style='display: none;'"; }
   $text = "<div id='section" . $num . "' name='$name' class='settings' $style>\n";
   $text .= $data;
   $text .= "</div>";
   return $text;
}

function nthweekdaymonth($year, $month, $day, $dayofweek, $nthweekday) { 

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $firstdaymonth = mktime(0, 0, 0, $month, $day, $year);
    $weekday = date("N", $firstdaymonth);
    
    if($weekday == $dayofweek) {
        $nextday = $day+(7*($nthweekday-1));
    }
    elseif($dayofweek > $weekday) {
        $nextday = (($dayofweek-$weekday)+$day)+(7*($nthweekday-1));
    }
    else {
        $nextday = ($day+(7-$weekday)+$dayofweek+(7*($nthweekday-1)));
    }
    if ($nextday > $days_in_month || ($nextday < date("d") && $month==date("n"))){
        $month = ($month==12)? 1: ++$month;
        $year = ($month==1)? $year++: $year;
        
        return nthweekdaymonth($year, $month, $day, $dayofweek, $nthweekday);
    }
    else
        return(date("Ymd", mktime(0, 0, 0, $month, $nextday, $year)));
}

?>
