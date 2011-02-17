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
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  
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


require_once('config.php');
require_once("functions.inc");

$freport = GET("freport");
$sreport = GET("sreport");
$pag     = GET("pag");

ossim_valid($freport, OSS_DIGIT, 'illegal:' . _("First report id"));
ossim_valid($sreport, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Second report id"));
ossim_valid($pag, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("pag"));

if (ossim_error()) {
    die(ossim_error());
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
if ($pag=="" || $pag<1) $pag=1;

$maxpag = 2;

$db = new ossim_db();
$dbconn = $db->connect();


$query = "SELECT name, scantime FROM vuln_nessus_reports where report_id=".$freport;
$result=$dbconn->Execute($query);

$freport_name = preg_replace('/\d+\s-\s/', '', $result->fields["name"]);
$freport_scantime = preg_replace('/(\d\d\d\d)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)/i', '$1-$2-$3 $4:$5:$6', $result->fields["scantime"]);

$query = "SELECT name, scantime FROM vuln_nessus_reports where report_id=".$sreport;
$result=$dbconn->Execute($query);

$sreport_name = preg_replace('/\d+\s-\s/', '', $result->fields["name"]);
$sreport_scantime = preg_replace('/(\d\d\d\d)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)/i', '$1-$2-$3 $4:$5:$6', $result->fields["scantime"]);

?>
<br />
<table style="margin:auto;" width="75%" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td class="noborder" width="49%">
            <table style="margin:auto;border: 0pt none;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="headerpr"><?php echo $freport_name; ?><span style="font-size : 9px;"><?php echo " (".$freport_scantime.")";?></span></td>
            </tr>
            </table>
            <table style="margin:auto;background: transparent;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="noborder"><?php   echo vulnbreakdown($dbconn, $freport);  ?></td>
            </tr>
            </table>
        </td>
        <td class="nobborder" width="2%">
        &nbsp;
        </td>
        <td class="noborder" width="49%">
            <table style="margin:auto;border: 0pt none;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="headerpr"><?php echo $sreport_name; ?><span style="font-size : 9px;"><?php echo " (".$sreport_scantime.")";?></span></td>
            </tr>
            </table>
            <table style="margin:auto;background: transparent;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="noborder"><?php   echo vulnbreakdown($dbconn, $sreport);  ?></td>
            </tr>
            </table>
        </td>
    </tr>
</table>
<br />
<?

$vulns = get_vulns($dbconn, $freport, $sreport);

?>
<table style="margin:auto;border: 0pt none;" width="75%" cellspacing="0" cellpadding="0">
<tr>
    <td class="headerpr"><?php echo gettext("Summary of Scanned Hosts");?></span></td>
</tr>
</table>
<table style="margin:auto;" width="75%">
    <th><strong><?php echo _("Host")?></strong></th>
    <th><strong><?php echo _("Hostname")?></strong></th>
     <td width="128" style='background-color:#FFCDFF;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #C835ED;'>
        <?php echo _("Serious") ?>
     </td>
     <td width="128" style='background-color:#FFDBDB;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FF0000;'>
        <?php echo _("High") ?>
    </td>
    <td width="128" style='background-color:#FFF283;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFA500;'>
        <?php echo _("Medium") ?>
     </td>
    <td width="128" style='background-color:#FFFFC0;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFD700;'>
        <?php echo _("Low") ?>
    </td>
    <td width="128" style='background-color:#FFFFE3;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #F0E68C;'>
        <?php echo _("Info") ?>
    </td></tr>
    <?php
    
    
    $tp = intval(count($vulns)/$maxpag); $tp += (count($vulns) % $maxpag == 0) ? 0 : 1;
    
    $to = $pag*$maxpag;
    $from = $to - $maxpag;
    
    $ips_to_show = array();
   
    $i=1;
    
    foreach ($vulns as $key => $value) {

        if($i>$from && $i<=$to) {
            $naip = array();
            $naip = explode("|",$key);
            
            $ips_to_show[] = $key;
            ?>
            <tr>
                <td style="text-align:center"><?php echo $naip[0]?></td>
                <td style="text-align:center"><?php echo $naip[1]?></td>
                <?php
                $image = get_image($value[1]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[1])) ? $value[1] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[2]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[2])) ? $value[2] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[3]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[3])) ? $value[3] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[6]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[6])) ? $value[6] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[7]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[7])) ? $value[7] : "-"; echo $image; ?></td>
            </tr>
            <?
        }
        $i++;
    }

    if( $maxpag<count($vulns) && (($pag<$tp) || ($pag>1) ) ) {
        ?>
        <tr>
        <td colspan="7" class="nobborder" style="text-align:center">
            <?php
            if ($pag>1) {?>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=1" style="padding:0px 5px 0px 5px"><?php echo _("<< First");?></a>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo ($pag-1); ?>" style="padding:0px 5px 0px 5px"><?php echo _("< Previous");?></a>
            <?php
            }
            if ($pag<$tp) {?>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo ($pag+1) ?>" style="padding:0px 5px 0px 5px"><?php echo _("Next >");?></a>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo $tp; ?>" style="padding:0px 5px 0px 5px"><?php echo _("Last >");?></a>
            <?php
            }
            ?>
        </td>
        </tr>
    <?php
    }
    ?>
</table>
<br />

<?php

foreach($ips_to_show as $ip_name)
{
    $naip = array();
    $naip = explode("|",$ip_name);
    
    $ip   = $naip[0];
    $name = $naip[1];
    
    $report1_data = array();
    $query ="SELECT DISTINCT risk, hostIP, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$freport and hostIP='$ip' and falsepositive='N'";

    $result=$dbconn->Execute($query);
    
    while (list($risk, $hostIP, $hostname, $port, $protocol, $app, $scriptid, $msg)=$result->fields) {
        $report1_data[] = $scriptid;
        $result->MoveNext();
    }
    
    $report2_data = array();
    $query ="SELECT DISTINCT risk, hostIP, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$sreport and hostIP='$ip' and falsepositive='N'";

    $result=$dbconn->Execute($query);
    
    while (list($risk, $hostIP, $hostname, $port, $protocol, $app, $scriptid, $msg)=$result->fields) {
        $report2_data[] = $scriptid;
        $result->MoveNext();
    }
    ?>
    <table style="margin:auto;border: 0pt none;" width="75%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="headerpr"><?php echo $ip. " - ".$name;?></span></td>
    </tr>
    </table>
    <table style="margin:auto;" width="75%" cellspacing="0" cellpadding="0">
    <?php
    $j = 0;
    $max_results = max( count($report1_data), count($report2_data) );
    while($j<$max_results) {
        ?>
            <tr>
                <td width="50%" style="text-align:center;" <?php echo ($j+1==$max_results) ? "class='nobborder'" : ""; ?>><?php echo ($report1_data[$j]!="") ? $report1_data[$j] : "-";?></td>
                <td width="50%" style="border-left:1px solid #BBBBBB;text-align:center;" <?php echo ($j+1==$max_results) ? "class='nobborder'" : ""; ?>><?php echo $report2_data[$j]?></td>
            </tr>
        <?php
        $j++;
    }
    ?>
    </table>
    <br />
    <?php
}
$dbconn->disconnect();


// functions

function vulnbreakdown($dbconn, $report){   //GENERATE CHARTS
    $query = "SELECT count(risk) as count, risk
                     FROM (SELECT DISTINCT risk, hostIP, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$report and falsepositive='N') as t GROUP BY risk";
                     
   $result=$dbconn->Execute($query);

   
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
   if (intval($prevrisk)!=7) {
        for($i=$prevrisk+1;$i<=7;$i++) {
            $chartimg.="&amp;risk$i=0";
        }
   }
   // print out the pie chart
   if($prevrisk!=0)
        $htmlchart .= "<font size=\"1\"><br></font>
            <img alt=\"Chart\" src=\"$chartimg\"><br>";
   else
        $htmlchart = "<br><span style=\"color:red\">"._("No vulnerabilty data")."</span>";
        
       
   return $htmlchart;
}

function get_vulns($dbconn, $freport, $sreport) {
    
    // first report
    $vulns = array();
    $query = "SELECT count(risk) as count, risk, hostIP, hostname
                     FROM (SELECT DISTINCT risk, hostIP, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$freport and falsepositive='N') as t GROUP BY risk, hostIP";
    
    
    $result=$dbconn->Execute($query);

    while (!$result->EOF) {
        $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]] = $result->fields["count"]."/0";
        $result->MoveNext();
    }
    
    // second report
    $query = "SELECT count(risk) as count, risk, hostIP, hostname
                 FROM (SELECT DISTINCT risk, hostIP, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                 WHERE report_id=$sreport and falsepositive='N') as t GROUP BY risk, hostIP";

    $result=$dbconn->Execute($query);

    while (!$result->EOF) {
        if($vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]]!= "") {
            $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]] = $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]]."/".$result->fields["count"];
            $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]] = preg_replace('/(\d+)\/0\/(\d+)/i', '$1/$2', $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]]);
            }
        else {
            $vulns[$result->fields["hostIP"]."|".$result->fields["hostname"]][$result->fields["risk"]] = "0/".$result->fields["count"];
        }
        $result->MoveNext();
    }
    
    asort($vulns,SORT_NUMERIC);

    return $vulns;
}
function get_image($value) {

    $image = "";

    if(!is_null($value) && preg_match("/(\d+)\/(\d+)/",$value,$found)) {
        if($found[1]==$found[2]) {
            $image = "<img src='../pixmaps/equal.png' align='absmiddle' border='0' title='equal' alt='equal' />";
        }
        else if (intval($found[1]) > intval($found[2])) {
            $image = " <img src='../pixmaps/green-arrow.png' align='absmiddle' border='0' title='".(intval($found[2]) - intval($found[1]))."' alt='".(intval($found[2]) - intval($found[1]))."' />";
        }
        else {
            $image = " <img src='../pixmaps/red-arrow.png' align='absmiddle' border='0' title='+".(intval($found[2]) - intval($found[1]))."' alt='+".(intval($found[2]) - intval($found[1]))."' />";
        }
    }

    return $image;
}