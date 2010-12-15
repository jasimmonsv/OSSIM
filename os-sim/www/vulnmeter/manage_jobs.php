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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("Vulnmeter"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <meta http-equiv="refresh" content="120;url=manage_jobs.php?bypassexpirationupdate=1">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
  <script type="text/javascript" src="../js/greybox.js"></script>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/vulnmeter.js"></script>
  <script type="text/javascript" src="../js/jquery.sparkline.js"></script>
  <script type="text/javascript" src="../js/jquery.cookie.js"></script>
  <script type="text/javascript" src="../js/jquery.json-2.2.js"></script>
  <style type="text/css">
  img.downclick { cursor:pointer; }
  </style>
  <? include ("../host_report_menu.php") ?>
  <script>
    function postload() {
        refresh_state();
        $(".scriptinfo").simpletip({
            position: 'right',
            content: '',
            onBeforeShow: function() { 
                var txt = this.getParent().attr('txt');
                this.update(txt);
            }
        });
    }
    // 
    function cancelScan(id) {
    	$('#working').toggle();
        $.ajax({
            type: "GET",
            url: "manage_jobs.php",
            data: { disp: "kill", sid: id },
            success: function(msg) {
            	alert("<?=_("Cancelling job, please wait a few seconds. Server will stop current scan as soon as possible.")?>");
                document.location.reload();
            }
        });
    }
    function changeTaskStatus(id, command) {
    	$('#changing_task_status_'+id).toggle();
        $.ajax({
            type: "GET",
            url: "manage_jobs.php",
            data: { disp: command, job_id: id },
            success: function(msg) {
                if(command=='pause_task') {
                    alert("<?=_("Pausing job, please wait a few seconds.")?>");
                    document.location.reload();
                }
                else if(command=='play_task') {
                    alert("<?=_("Starting job, please wait a few seconds.")?>");
                    document.location.reload();
                }
                else if(command=='stop_task') {
                    alert("<?=_("Stopping job, please wait a few seconds.")?>");
                    setTimeout('document.location.href="manage_jobs.php?hmenu=Vulnerabilities&smenu=Jobs"',8000);
                }
                else if(command=='resume_task') {
                    alert("<?=_("Resuming job, please wait a few seconds.")?>");
                    document.location.reload();
                }
            }
        });
    }
    
    
    function deleteTask(id) {
        if (confirmDelete()) {
            $.ajax({
                type: "GET",
                url: "manage_jobs.php",
                data: { disp: 'delete_task', job_id: id },
                success: function(msg) {
                    document.location.reload();
                }
            });
            $.ajax({
                type: "GET",
                url: "sched.php",
                data: { disp: 'delete_scan', job_id: id }
            });
        }
    }
    
    
    var date5m = new Date();
    //date.setTime(date.getTime() + (3 * 24 * 60 * 60 * 1000));
    date5m.setTime(date5m.getTime() + (5 * 60 * 1000));
    var nessuspoints = [];
    if ($.cookie('nessuspoints')) nessuspoints = $.evalJSON($.cookie('nessuspoints'));
    var nmappoints = [];
    if ($.cookie('nmappoints')) nmappoints = $.evalJSON($.cookie('nmappoints'));
    var max_points = 30;
    var loading = '<img width="16" align="absmiddle" src="images/loading.gif">&nbsp;&nbsp;<?=_("Loading")?>...';
    var last_state = 0;
    function refresh_state() {
        var state = (last_state == 0) ? "?bypassexpirationupdate=1" : "";
        $.ajax({
            type: "GET",
            url: "get_state.php"+state,
            success: function(msg) {
                var data = msg.split("|");
                last_state = data[0];
                if(data[0]==1) {
                    <?php
                    if ($_SESSION["scanner"]!="omp") {?>
                        $('#nta').html(data[0]);
                    <?php
                    }
                    ?>
                    $('#nessus_threads').html('');
                    $('#td1').attr("width","100%");
                    $('#td2').attr("width", "1%");
                    $('#status_server').html('<font color=green>Launching scan</font>');
                    if ($('#nmta').html()=='') {
                    	$('#nmta').html($('#nmta').html()+'<b>Finished</b><br>')
                    }
                }
                else if(data[0]==0) {
                    <?php
                    if ($_SESSION["scanner"]!="omp") {?>
                        $('#nta').html(data[0]);
                    <?php
                    }
                    ?>
                    $('#nessus_threads').html('');
                    $('#td1').attr("width","100%");
                    $('#td2').attr("width", "1%");
                    $('#status_server').html('<font color=blue>Idle</font>');
                    <?php
                    if ($_SESSION["scanner"]!="omp") {?>
                        $('#nmta').html('');
                    <?php
                    }
                    else
                    {
                    ?>
                        $('#nmta').css("text-align","center");
                        $('#nmta').html('No Running Scans');
                    <?php
                    }
                    ?>
                }
                else{
                    <?php
                    if ($_SESSION["scanner"]!="omp") {?>
                        data[0] = data[0] -1;
                    <?php
                    }
                    else {
                    ?>
                        if(data[0]==-1) {
                            data[0] = 0;
                        }
                    <?php
                    }
                    ?>
                    $('#status_server').html('<font color=green>Scan in progress</font>');
                    nessuspoints.push(data[0]);
                    <?php
                    if ($_SESSION["scanner"]!="omp") {?>
                        $('#nta').html(data[0]);
                    <?php
                    }
                    ?>
                    if (nessuspoints.length > max_points)
                        nessuspoints.splice(0,1);
                    $('#nessus_threads').sparkline(nessuspoints, { width:nessuspoints.length*4 });
                    //
                    $.cookie('nessuspoints', $.toJSON(nessuspoints), { expires: date5m });
                    $('#messages').show();
                    $.sparkline_display_visible();
                    // 
                    $('#nmta').html('');
                    
                    <?php
                    if ($_SESSION["scanner"]=="omp") {
                    ?>
                    $('#nmta').html($('#nmta').html()+data[1]);
                    <?php
                    }
                    else {
                    ?>
                        for (var i=1;i<data.length;i++) {
                            $('#nmta').html($('#nmta').html()+data[i]+'<br>')
                        }
                    <?php
                    }
                    ?>
                    setTimeout (refresh_state,4000);
                }
            }
        });
    }
    $(document).ready(function(){
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
            dest = $(this).attr('href');
            GB_show("<?=_("Make this scan job visible for:")?>",dest,150,400);
            return false;
        });

    });
    function GB_onclose() {
        document.location.href='manage_jobs.php';
    }
  </script>
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = _("Manage Jobs");
require_once('config.php');
require_once('functions.inc');
require_once ('ossim_conf.inc');
require_once ('classes/OMP.inc');

//require_once('auth.php');
//require_once('header2.php');
//require_once('permissions.inc.php');

$myhostname="";

$getParams = array( 'disp', 'schedid', 'sortby', 'sortdir', 'viewall', 'setstatus', 'enabled', 'job_id');
$hosts = array();
$hosts = host_ip_name();

switch ($_SERVER['REQUEST_METHOD']) {
case "GET" :
   foreach($getParams as $gp) {
       if (isset($_GET[$gp])) { 
           $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES);
       } else { 
           $$gp="";
       }
    }
    $range_start="";
    $range_end="";
    break;
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

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


$query = "select count(*) from vuln_nessus_plugins";
$result = $dbconn->execute($query);
list($pluginscount) = $result->fields;

if ($pluginscount==0) {
    //include_once('header2.php');
    die ("<h2>"._("Please run updateplugins.pl script first before using web interface").".</h2>");
}


function delete_sched( $schedid ) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;

    $sql_require = "";
    if ( ! $uroles['admin'] ) { $sql_require = "AND username='$username'"; }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
    //echo "query=$query<br>";
    $result=$dbconn->Execute($query);
    list( $jid, $nname ) = $result->fields;

    if ( $jid > 0 ) {
       $query = "DELETE FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
       $result=$dbconn->Execute($query);

        $infolog = array($nname);
        Log_action::log(68, $infolog);
        
       //echo "Deleted Reoccuring Schedule <i>\"$nname\"</i><br><br>";
 //logAccess( "DELETED Reoccuring Schedule $nname" );
    } else {
       //echo "Not Authorized to Delete Reoccuring Schedule <i>\"$nname\"</i>";
 //logAccess( "UNAUTHORIZED ATTEMPT TO DELETED Reoccuring Schedule $nname" );
    }
    main_page ( $viewall, $sortby, $sortdir );
}

function set_status ( $schedid, $enabled ) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;

    $sql_require = "";
    if ( ! $uroles['admin'] ) { $sql_require = "AND username='$username'"; }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = '$schedid' $sql_require";
    //echo "query=$query<br>";
    $result=$dbconn->Execute($query);
    list( $jid, $nname ) = $result->fields;

    if ( $jid > 0 ) {
       $query = "UPDATE vuln_job_schedule SET enabled ='$enabled' WHERE id = '$schedid' $sql_require";
       $result=$dbconn->Execute($query);

       //echo "UPDATED Reoccuring Schedule <i>\"$nname\"</i><br><br>";
 //logAccess( "UPDATED Reoccuring Schedule $nname" );
    } else {
       echo _("Not Authorized to CHANGLE STATUS for Reoccuring Schedule")." <i>\"$nname\"</i>";
 //logAccess( "UNAUTHORIZED ATTEMPT TO EDIT Reoccuring Schedule $nname" );
    }
    main_page ( $viewall, $sortby, $sortdir );
}



function main_page ( $viewall, $sortby, $sortdir ) {
    global $uroles, $username, $dbconn, $hosts;
    global $arruser, $user;

    if ($sortby == "" ) { $sortby = "id"; }
    if ($sortdir == "" ) { $sortdir = "DESC"; }

/*    if ( $uroles['admin'] ) {
        if($viewall == 1) {
            echo "&nbsp;<a href='manage_jobs.php'>View My Schedules</a>&nbsp;|&nbsp;";
        } else {
            echo "&nbsp;<a href='manage_jobs.php?viewall=1'>View All Schedules</a>&nbsp;|&nbsp;";
        }
    } else {
        $viewall = "1";
    }*/
    //echo "<a href='sched.php?op=reoccuring'>New Schedule</a>&nbsp;|<br><br>";

    $sql_order="order by $sortby $sortdir";


//    if($viewall == 1) {
//       $url_sortby="<a href=\"manage_jobs.php?viewall=1&sortby=";
//    } else {
//       $url_sortby="<a href=\"manage_jobs.php?sortby=";
//    }
echo "<center>";
status($arruser, $user);
echo "<br>";
echo "<form>";
echo "<input type=\"button\" onclick=\"document.location.href='sched.php?smethod=schedule&hosts_alive=1&scan_locally=1'\" value=\""._("New Scan Job")."\" class=\"button\">";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<input type=\"button\" onclick=\"document.location.href='sched.php?smethod=inmediately&hosts_alive=1&scan_locally=1'\" value=\""._("Run Scan Now")."\" class=\"button\">";
echo "</form>";
echo "</center>";
echo "<br>";
$schedulejobs = _("Scheduled Jobs");
   echo <<<EOT
   <center>
   <table cellspacing="0" cellpadding="0" border="0" width="90%"><tr><td class="headerpr" style="border:0;">$schedulejobs</td></tr></table>
   <table cellspacing="2" width="90%" summary="Job Schedules" 
        border=0 cellspacing="0" cellpadding="0">
EOT;

   if($sortdir == "ASC") { $sortdir = "DESC"; } else { $sortdir = "ASC"; }
   $arr = array( _("Name"), _("Schedule Type"), _("Time") , _("Next Scan"), _("Status") );


// modified by hsh to return all scan schedules
if (in_array("admin", $arruser)){
    $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.time, t1.id, t1.name, t1.schedule_type, t1.meth_VSET, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK 
              FROM vuln_job_schedule t1, vuln_nessus_settings t2 WHERE t1.meth_VSET=t2.id ";
}
else {
    $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.time, t1.id, t1.name, t1.schedule_type, t1.meth_VSET, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK
              FROM vuln_job_schedule t1, vuln_nessus_settings t2 WHERE username in ('$user') and t1.meth_VSET=t2.id ";
}
//    if($viewall == 1) { // list all schedules
//    } else { // view only logged in users schedules
//       $query .= "where username='$username' ";
//    }

    $query .= $sql_order;
    $result=$dbconn->execute($query);

    if ($result->EOF){
        echo "<tr><td height='20' class='nobborder' style='text-align:center;'>"._("No Scheduled Jobs")."</td></tr>";
    }
    if (!$result->EOF) {
        echo "<tr>";
        foreach ( $arr as $value) {
        echo "<th><a href=\"manage_jobs.php?sortby=$value&sortdir=$sortdir\">$value</a></th>";
        }
        echo "<th>"._("Action")."</th></tr>";
    }
    while (!$result->EOF) {
       list ($profile, $targets, $time, $schedid, $schedname, $schedtype, $sid, $timeout, $user, $schedstatus, $nextscan )=$result->fields;

       switch ($schedtype) {
       case "N":
          $stt = _("Once (Now)");
          break;
       case "O":
          $stt = _("Once");
          break;
       case "D":
          $stt = _("Daily");
          break;
       case "W":
          $stt = _("Weekly");
          break;
       case "M":
          $stt = _("Monthly");
          break;
       case "Q":
          $stt = _("Quarterly");
          break;
       case "H":
          $stt = _("On Hold");
          break;
       case "NW":
          $stt = _("N<sup>th</sup> weekday of the month");
          break;
       default:
          $stt="&nbsp;";
          break;
       }

       switch ($schedstatus) {
       case "1":
          $itext=_("Disable Scheduled Job");
          $isrc="images/stop2.png";
          $ilink = "manage_jobs.php?disp=setstatus&schedid=$schedid&enabled=0";
          break;
       default:
          $itext=_("Enable Scheduled Job");
          $isrc="images/play.png";
          $ilink = "manage_jobs.php?disp=setstatus&schedid=$schedid&enabled=1";          
          break;
       }

       if ( $schedstatus ) { 
          $txt_enabled = "<td><a href=\"$ilink\"><font color=\"green\">"._("Enabled")."</font></a></td>"; 
       } else { 
          $txt_enabled = "<td><a href=\"$ilink\"><font color=\"red\">"._("Disabled")."</font></a></td>"; 
       }
       //$nextscan = $user_time = switchTime_TimeZone( $nextscan, "user", "TZdate" );
       $nextscan = date("Y-m-d H:i:s",strtotime($nextscan));
       
        if(preg_match('/\d+/', $user)) {
            list($entities_all, $num_entities) = Acl::get_entities($dbconn, $user);
            $user = $entities_all[$user]['name'];
        }
       echo <<<EOT
<tr>
EOT;
    echo "<td><a style=\"text-decoration:none;\" href=\"javascript:;\" txt=\"<b>"._("Owner").":</b> $user<br><b>"._("Scheduled Job ID").":</b> $schedid<br><b>"._("Profile").":</b> $profile<br><b>"._("Targets").":</b><br>".tooltip_hosts($targets,$hosts)."\" class=\"scriptinfo\">$schedname</a></td>";
       echo <<<EOT
    <td>$stt</td>
    <td>$time</td>
    <td>$nextscan</td>
    $txt_enabled
    <td style="padding-top:2px;"><a href="$ilink"><img alt="$itext" src="$isrc" border=0 title="$itext"></a>&nbsp;
    <a href="sched.php?disp=edit_sched&sched_id=$schedid&amp;hmenu=Vulnerabilities&amp;smenu=Jobs"><img src="images/pencil.png"></a>&nbsp;
    <a href="manage_jobs.php?disp=delete&amp;schedid=$schedid" onclick="return confirmDelete();"><img src="images/delete.gif"></a></td>
</tr>
EOT;

       $result->MoveNext();
    }
    echo <<<EOT
</table></center>
EOT;
echo "<br>";

if ($_GET['page']!=""){ $page = $_GET['page'];}
else $page = 1;

$pagesize = 10;

if($username=="admin") {$query = "SELECT count(id) as num FROM vuln_jobs";}
else {$query = "SELECT count(id) as num FROM vuln_jobs where username='$username'";}

$result = $dbconn->Execute($query);
$jobCount =$result->fields["num"];

$num_pages = ceil($jobCount/$pagesize);

//echo "num_pages:[".$num_pages."]";
//echo "jobCount:[".$jobCount."]";
//echo "page:[".$page."]";
$out = all_jobs(($page-1)*$pagesize,$pagesize);
?>
<table width="90%" align="center" class="transparent">
    <tr><td style="text-align:center;padding-top:5px;" class="nobborder">
        <a href="javascript:;" onclick="$('#legend').toggle();$('#message_show').toggle();$('#message_hide').toggle();" colspan="2"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0">
            <span id="message_show"><?=_("Show legend")?></span>
            <span id="message_hide" style="display:none"><?=_("Hide legend")?></span>
        </a>
        </td>
        <td class="nobborder" valign="top" style="padding-top:5px;">
        <?
        
        if ($out!=0 && $num_pages!=1){
            if ($page==1 && $page==$num_pages){ echo '<center><< '._("First").' <'._(" Previous").'&nbsp;&nbsp;&nbsp;['.$page.' '._("of").' '.$num_pages.']&nbsp;&nbsp;&nbsp;'._("Next").' >&nbsp;'._("Last").' >></center>'; } 
            elseif ($page==1){ echo '<center><< '._("First").' < '._("Previous").'&nbsp;&nbsp;&nbsp;['.$page.' '._("of").' '.$num_pages.']&nbsp;&nbsp;&nbsp;<a href="manage_jobs.php?page='.($page+1).'">'._("Next").' ></a>&nbsp;<a href="manage_jobs.php?page='.$num_pages.'">'._("Last").' >></a></center>';}
            elseif($page == $num_pages) {echo '<center><a href="manage_jobs.php?page=1"><< '._("First").'</a>&nbsp;<a href="manage_jobs.php?page='.($page-1).'">< '._("Previous").'</a>&nbsp;&nbsp;&nbsp;['.$page.' '._("of").' '.$num_pages.']&nbsp;&nbsp;&nbsp;'._("Next").'>&nbsp;'._("Last").' >></center>';}
            else {echo '<center><a href="manage_jobs.php?page=1"><< '._("First").'</a>&nbsp;<a href="manage_jobs.php?page='.($page-1).'">< '._("Previous").'</a>&nbsp;&nbsp;&nbsp;['.$page.' '._("of").' '.$num_pages.']&nbsp;&nbsp;&nbsp;<a href="manage_jobs.php?page='.($page+1).'">'._("Next").' ></a>&nbsp;<a href="manage_jobs.php?page='.$num_pages.'">'._("Last").' >></a></center>';}
            //echo "<br>";
            }
    ?>
        </td>
    </tr>
    <tr>
        <td width="110" class="nobborder">
            <table width="100%" cellpadding="3" cellspacing="3" id="legend" style="display:none;">
                <tr>       
                    <th colspan="2" style="padding-right: 3px;">
                        <div style="float: left; width: 60%; text-align: right;padding-top:3px;"><b><?=_("Legend")?></b></div>
                        <div style="float: right; width: 18%; padding-top: 2px; padding-bottom: 2px; text-align: right;"><a style="cursor: pointer; text-align: right;" onclick="$('#legend').toggle();$('#message_show').toggle();$('#message_hide').toggle();"><img src="../pixmaps/cross-circle-frame.png" alt="Close" title="Close" align="absmiddle" border="0"></a></div>
                    </th>
                </tr>
                <tr>
                    <td bgcolor="#EFFFF7" style="border:1px solid #999999" width="25%"></td><td class="nobborder"  width="75%" style="text-align:left;padding-left:7px;"><?=_("Completed")?></td>
                </tr>
                <tr>
                    <td bgcolor="#EFE1E0" style="border:1px solid #999999" width="25%"></td><td class="nobborder"  width="75%" style="text-align:left;padding-left:7px;"><?=_("Failed")?></td>
                </tr>
                <tr>
                    <td bgcolor="#D1E7EF" style="border:1px solid #999999" width="25%"></td><td class="nobborder"  width="75%" style="text-align:left;padding-left:7px;"><?=_("Running")?></td>
                </tr>
                <tr>
                    <td bgcolor="#DFF7FF" style="border:1px solid #999999" width="25%"></td><td class="nobborder"  width="75%" style="text-align:left;padding-left:7px;"><?=_("Sheduled")?></td>
                </tr>
                <tr>
                    <td bgcolor="#FFFFDF" style="border:1px solid #999999" width="25%"></td><td class="nobborder"  width="75%" style="text-align:left;padding-left:7px;"><?=_("Timeout")?></td>
                </tr> 
            </table>
        </td>
        <td class="nobborder">&nbsp;
        </td>
    </tr>
</table>
<?
}

switch($disp) {

    case "kill":
        $schedid = intval($schedid);
        if ($schedid>0) {
        	system("sudo /usr/share/ossim/scripts/vulnmeter/cancel_scan.pl $schedid");
        }
        break;
    case "play_task":
        $omp = new OMP();
        $omp->play_task($job_id);
        break;
        
    case "pause_task":
        $omp = new OMP();
        $omp->pause_task($job_id);
        break;
        
    case "stop_task":
        $omp = new OMP();
        $omp->stop_task($job_id);
        break;
        
    case "resume_task":
        $omp = new OMP();
        $omp->resume_task($job_id);
        break;
        
    case "delete_task":
        $omp = new OMP();
        $omp->delete_task($job_id);
        break;

    case "delete":
        delete_sched( $schedid );
        break;

    case "setstatus":
    	set_status ( $schedid, $enabled );
    	break;    
        
    default:
       main_page( 1, $sortby, $sortdir );
       break;
}

require_once("footer.php");

?>
