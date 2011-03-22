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
// $Id: threats-db.php,v 1.8 2010/04/07 16:14:41 josedejoses Exp $
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

require_once ('classes/Util.inc');
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
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="../js/datepicker.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <? include ("../host_report_menu.php") ?>
  <script type="text/javascript">
    function calendar()
	{
		// CALENDAR
		<?php
			if ($date_from != "") {
				$aux = split("-",$date_from);
				$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
			} else {
				$y = strftime("%Y", time() - ((24 * 60 * 60) * 30));
				$m = strftime("%m", time() - ((24 * 60 * 60) * 30));
				$d = strftime("%d", time() - ((24 * 60 * 60) * 30));
				$date_from = "$y-$m-$d";
			}
			if ($date_to != "") {
				$aux = split("-",$date_to);
				$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
			} else {
				$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
				$date_to = "$y2-$m2-$d2";
			}

		?>
		var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
		var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);

		$('#widgetCalendar').DatePicker({
			flat: true,
			format: 'Y-m-d',
			date: [new Date(datefrom), new Date(dateto)],
			calendars: 3,
			mode: 'range',
			starts: 1,
			onChange: function(formated) {
				if (formated[0]!=formated[1]) {
					var f1 = formated[0].split(/-/);
					var f2 = formated[1].split(/-/);
					document.getElementById('start_date').value = f1[0]+'-'+f1[1]+'-'+f1[2];
					document.getElementById('end_date').value = f2[0]+'-'+f2[1]+'-'+f2[2];
					$('#date_str').css('text-decoration', 'underline');
					$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
					$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
					state = !state;
				}
			}
		});
		
		var state = false;
		$('#widget>a').bind('click', function(){
			$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
			$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
			state = !state;
			return false;
		});
		
		$('#widgetCalendar div.datepicker').css('position', 'absolute');
	}
  function postload() {
	$(".scriptinfo").simpletip({
		position: 'right',
		onBeforeShow: function() { 
			var id = this.getParent().attr('lid');
			this.load('lookup.php?id=' + id);
		}
	});
	$('#loading').toggle();
    calendar(); 
  }
  </script>
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = _("Nessus Threats Database");
require_once('config.php');
require_once('functions.inc');
//require_once('auth.php');

//require_once('header2.php');
//require_once('permissions.inc.php');


$getParams = array(  'disp', 'increment', 'page', 'kw', 'family', 'risk', 'start_date', 'end_date', 'scve'
                   );


$postParams = array( 'disp', 'increment', 'page', 'kw', 'family', 'risk', 'start_date', 'end_date'
                   );


$schedOptions = array(      "N" => "Immediately",
                     "O" => "Run Once", 
                     "D" => "Daily", 
                     "W" => "Weekly", 
                     "M" => "Monthly" );          

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
   foreach ($postParams as $pp) {
      if (isset($_POST[$pp])) { 
         if(is_array($_POST[$pp])) {
            foreach($_POST[$pp] as $i=>$tmp) {
               ${$pp}[$i] = sanitize($tmp);
//echo $pp . "[" . $i . "] = " . $$pp[$i] . "<br>";
            }
         } else {
            $$pp = sanitize($_POST[$pp]);
//echo $pp . " = " . $$pp . "<br>";
         }
      } else { 
         $$pp=""; 
      }
//echo $pp . " = " . $$pp . "<Br>";
   }
   break;
}

if ( $increment == gettext("Previous") ) {
     $page = $page -1;
} elseif ($increment == gettext("Next") ) {
     $page = $page + 1;
}

if (!$page) { $page=1; }


function home() {
    global $dbconn, $start_date, $end_date, $kw, $scve, $risk;
    $resultcve=$dbconn->GetArray("select id, cve_id from vuln_nessus_plugins");
    foreach ($resultcve as $cve) {
        $c = explode(",",$cve['cve_id']);
        foreach ($c as $value) {
            $value = trim($value);
            if ($value!="") {
                $tmp = substr($value,0,8);
                $cves[$tmp] = $i;
                $i++;
            }
        }
    }
    if (is_array($cves))
        ksort($cves);


echo "<center><table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"804\"><tr><td class=\"headerpr\" style=\"border:0;\">"._("Threats")."</td></tr></table></center>";
	echo <<<EOT
      <form method="POST" action="threats-db.php">
        <input type="hidden" name="disp" value="search">
        <center>
        <table cellpadding="0" cellspacing="2" width="796">
        <tr><td class="nobborder">
        <table align="center" cellpadding="2" cellspacing="0" width="800" height="40">
        <tr>
        <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder">
EOT;
    echo "<div class=\"field\"><b>"._("Start Date")."</b></div>";
	echo <<<EOT
        <div id="widget" style="display:inline;">
            <a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0" align="absmiddle" style="padding:0px 0px 2px 0px" /></a>
            <div id="widgetCalendar" style="position:absolute;top:11;z-index:10"></div>
        </div>
     <input type="text" id="start_date" name="start_date" readonly="readonly" name="start" size="12" value="$start_date"/>
      </td>
      <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder">
EOT;
    echo "<div class=\"field\"><b>"._("End Date")."</b></div>";
	echo <<<EOT
     <input type="text" id="end_date" name="end_date" readonly="readonly" size="12" value="$end_date"/>
      </td>
          <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder">
EOT;
    echo "<div class=\"field\"><b>"._("Keywords")."</b></div>";
	echo <<<EOT
     <input type="text" name="kw" size="20" value="$kw"/>
      </td>
EOT;
echo "<td style=\"padding: 0 30px 0 30px;text-align:center;\" class=\"nobborder\" nowrap>";
echo "<div class=\"field\"><b>"._("CVE Id")."</b></div>";
echo "<select name=\"cve\" size=\"1\">";
echo "   <option value=\"\"></option>";

foreach ($cves as $key=>$value){
echo "   <option value='$key' ".(($key==$scve) ? "selected='selected'":"").">$key</option>";
}
echo "</select>";
echo "</td>";

	echo <<<EOT
      <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder" nowrap>
EOT;
    echo "<div class=\"field\"><b>"._("Risk Factor")."</b></div>";
	echo <<<EOT
     <select name="risk" size="1">
EOT;
    echo "<option value=\"\"></option>";
    echo "<option value=\"1\" ".(($risk==1) ? "selected='selected'":"").">"._("Info")."</option>";
    echo "<option value=\"2\" ".(($risk==2) ? "selected='selected'":"").">"._("Low")."</option>";
    echo "<option value=\"3\" ".(($risk==3) ? "selected='selected'":"").">"._("Medium")."</option>";
    echo "<option value=\"6\" ".(($risk==6) ? "selected='selected'":"").">"._("High")."</option>";
    echo "<option value=\"7\" ".(($risk==7) ? "selected='selected'":"").">"._("Serious")."</option>";
	echo <<<EOT
     </select>
      </td>

    </tr>
  </table><br>
EOT;
  echo "<center><input type=\"submit\" value=\""._("Search")."\" class=\"button\" /></center>";
	echo <<<EOT
</form>
<br>
<center>
<table id="family-table" class="tabular" width="800">
  <thead>
    <tr>
EOT;
    echo "<th sort:format=\"str\" style=\"text-align: left\">"._("Threat Family")."</th>";
    echo "<th sort:format=\"int\" class=\"risk1\">"._("Info")."-1</th>";
    echo "<th sort:format=\"int\" class=\"risk2\">"._("Low")."-2</th>";
    echo "<th sort:format=\"int\" class=\"risk3\">"._("Medium")."-3</th>";
    echo "<th sort:format=\"int\" class=\"risk6\">"._("High")."-6</th>";
    echo "<th sort:format=\"int\" class=\"risk7\">"._("Serious")."-7</th>";
    echo "<th sort:format=\"int\">"._("Total")."</th>";
	echo <<<EOT
    </tr>
  </thead>

EOT;

     $query = "SELECT t2.id, t2.name, count( t1.risk = '1'OR NULL ) AS Urgent, 
          count( t1.risk = '2' OR NULL ) AS Critical, count( t1.risk = '3' OR NULL ) AS High, 
          count( t1.risk = '6' OR NULL ) AS MEDIUM , count( t1.risk = '7'OR NULL ) AS Low, 
          count( t1.risk ) AS Total 
          FROM vuln_nessus_plugins t1
          LEFT JOIN vuln_nessus_family t2 ON t1.family = t2.id
          GROUP BY t1.family";
     $result = $dbconn->execute($query);

     $http_base = "threats-db.php?disp=search";
     $color = 0;
     while (!$result->EOF) {
          list( $fam_id, $fam_name, $fam_urg, $fam_ser, $fam_high, $fam_med, $fam_low, $fam_total )
          =  $result->fields;

          echo "<tr class=\"even\" bgcolor=".(($color % 2 ==0) ? "#F2F2F2" : "#FFFFFF")."><td style=\"text-align: left\">$fam_name</td>
                      <td align=\"center\">".(($fam_urg==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=1\" >".Util::number_format_locale((int)$fam_urg,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_ser==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=2\" >".Util::number_format_locale((int)$fam_ser,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_high==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=3\" >".Util::number_format_locale((int)$fam_high,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_med==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=6\" >".Util::number_format_locale((int)$fam_med,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_low==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=7\" >".Util::number_format_locale((int)$fam_low,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_total==0)? "0": "<a href=\"$http_base&family=$fam_id\" >".Util::number_format_locale((int)$fam_total,0)."</a>")."</td>
          </tr>";

          $result->MoveNext();
          $color++;
     }

     $query = "SELECT count( risk = '1' OR NULL ) AS Urgent, 
          count( risk = '2' OR NULL ) AS Critical, count( risk = '3' OR NULL ) AS High, 
          count( risk = '6' OR NULL ) AS MEDIUM , count( risk = '7'OR NULL ) AS Low, 
          count( risk ) AS Total 
          FROM vuln_nessus_plugins t1";
     $result = $dbconn->execute($query);

     list( $fam_urg, $fam_ser, $fam_high, $fam_med, $fam_low, $fam_total ) 
         =  $result->fields;

     echo "<tr><td colspan=7 height='20'></td></tr>
          <tr class=\"even\"><td class='noborder' style=\"text-align: left\">&nbsp;</td>
            <td class='noborder' align=\"center\">".(($fam_urg==0)? "0" : "<a href=\"$http_base&risk=1\" >".Util::number_format_locale((int)$fam_urg,0)."</a>")."</td>
          <td class='noborder' align=\"center\">".(($fam_ser==0)? "0" : "<a href=\"$http_base&risk=2\" >".Util::number_format_locale((int)$fam_ser,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_high==0)? "0" : "<a href=\"$http_base&risk=3\" >".Util::number_format_locale((int)$fam_high,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_med==0)? "0" : "<a href=\"$http_base&risk=6\" >".Util::number_format_locale((int)$fam_med,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_low==0)? "0" : "<a href=\"$http_base&risk=7\" >".Util::number_format_locale((int)$fam_low,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_total==0)? "0" : "<a href=\"$http_base&family=All&risk=All\" >".Util::number_format_locale((int)$fam_total,0)."</a>")."</td>
          </tr></table></td></tr></table></center></center>";

}

function search($page, $kw, $cve,$family, $risk, $start_date, $end_date) {
     global $dbconn;

     $Limit=20;
     
     $risks = array("1" => _("Info"), "2" => _("Low"), "3" => _("Medium"), "6" => _("High"), "7" => _("Serious"));
     
     $query = "SELECT name FROM vuln_nessus_family WHERE id=$family";
     $result = $dbconn->execute($query);

     list( $family_name ) = $result->fields;
     
     
     if ( $kw == "" ) { $txt_kw = "All"; } else { $txt_kw = $kw; }
     if ( $cve == "" ) { $txt_cve = "All"; } else { $txt_cve = $cve; }
     if ( $family_name == "" ) { $txt_family = "All"; } else { $txt_family = $family_name; }
     if ( $risk == "" ) { $txt_risk = "All"; } else { $txt_risk = $risks[$risk]; }
     if ( $start_date == "" ) { $txt_start_date = "All"; } else { $txt_start_date = $start_date; }
     if ( $end_date == "" ) { $txt_end_date = "All"; } else { $txt_end_date = $end_date; }

    echo "<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\" class=\"noborder\">";
    echo "<tr class=\"noborder\" style=\"background-color:white\"><td class=\"headerpr\">";
    echo "    <table width=\"100%\" class=\"noborder\" style=\"background-color:transparent\">";
    echo "        <tr class=\"noborder\" style=\"background-color:transparent\"><td width=\"20\" class=\"noborder\">";
    echo "        <a href=\"threats-db.php?start_date=$start_date&end_date=$end_date&kw=$kw&risk=$risk&scve=$cve\"><img src=\"./images/back.png\" border=\"0\" alt=\""._("Back")."\" title=\""._("Back")."\"></a>";
    echo "        </td><td width=\"780\">";
    echo "        </font>";
    echo "        "._("Search results for this criteria")."</td></tr>";
    echo "    </table>";
    echo "</td></tr>";
    echo "</table>";

     echo '
<table cellpadding="0" cellspacing="0" align="center" width="800">
     <tr><td height="50" class="nobborder">
     <table cellpadding="0" cellspacing="2" align="center" width="95%">
          <tr><th>'.gettext("Start Date").'</th><th>'.gettext("End Date").'</th><th>'.gettext("Keywords").'</th><th>'.gettext("CVE Id").'</th><th>'.gettext("Family").'</th><th>'.gettext("Risk Factor").'</th></tr>
          <tr>
          <td class="nobborder" style="text-align:center;">'.$txt_start_date.'</td>
          <td class="nobborder" style="text-align:center;">'.$txt_end_date.'</td>
          <td class="nobborder" style="text-align:center;">'.html_entity_decode($txt_kw).'</td>
          <td class="nobborder" style="text-align:center;">'.$txt_cve.'</td>
          <td class="nobborder" style="text-align:center;">'.$txt_family.'</td>
          <td class="nobborder" style="text-align:center;">'.$txt_risk.'</td>
          </tr>
     </table>
     </td></tr>
     <tr><td class="nobborder" style="text-align:center;padding-bottom:10px;">

';

     $query_filter = "";

     if ( $kw != "" ) { $query_filter .= "AND ( t1.summary LIKE '%$kw%' OR t1.cve_id LIKE '%$kw%' OR t2.name LIKE '%$kw%' OR CONCAT(t2.name, ' - ', t1.summary) LIKE '%$kw%' )"; }
     if ( $cve != "" ) { $query_filter .= "AND ( t1.cve_id LIKE '%$cve%' )"; }
     if ( $family != "" ) {  $query_filter .= "AND t1.family = '$family'"; }
     if ( $risk != "" ) {  $query_filter .= "AND t1.risk = '$risk'"; }
     if ( $start_date != "" ) {  $query_filter .= " AND CONVERT(t1.created,UNSIGNED) >= ".str_replace("-","",$start_date)."000000"; }
     if ( $end_date != "" ) {  $query_filter .= " AND CONVERT(t1.created,UNSIGNED) <= ".str_replace("-","",$end_date)."235959"; }

     $query_filter = ltrim($query_filter, "AND ");

     if ( $query_filter == "" ) { $query_filter = "1"; }

     $query_filter = "WHERE $query_filter";

     if (!preg_match("/t2/",$query_filter)) {
        $query = "SELECT count( t1.id ) FROM vuln_nessus_plugins t1 $query_filter";
     }
     else {
        $query = "SELECT count( t1.id ) FROM vuln_nessus_plugins t1 LEFT JOIN vuln_nessus_family t2 ON t1.family = t2.id $query_filter";
     }

     $result = $dbconn->execute($query);

     list ( $numrec ) =  $result->fields;

     if ($numrec > 0) {
          $numpages=intval($numrec/$Limit);
     } else {
          $numpages = 1;
     }

     if ($numrec%$Limit) { $numpages++; } // add one page if remainder 
        if ($page > 0) { $previous = $page -1; } else { $previous = -1; }
        if ($numpages > $page) { $next = $page +1; } else { $next = -1;     }
        

        $offset = (($page-1) * $Limit);  

     $query = "SELECT t1.cve_id, t1.id, t1.risk, t1.created, t2.name, t1.summary 
          FROM vuln_nessus_plugins t1 LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
          $query_filter LIMIT $offset,$Limit";     
     
     //echo "query=$query<br>";
     $result = $dbconn->execute($query);
     
if (!$result->EOF) {
         echo <<<EOT
        <form action="threats-db.php" method="post">
        <INPUT TYPE=HIDDEN NAME="disp" VALUE="search">
        <INPUT TYPE=HIDDEN NAME="page" VALUE="$page">
        <INPUT TYPE=HIDDEN NAME="kw" VALUE="$kw">
        <INPUT TYPE=HIDDEN NAME="family" VALUE="$family">
        <INPUT TYPE=HIDDEN NAME="risk" VALUE="$risk">
        <INPUT TYPE=HIDDEN NAME="start_date" VALUE="$start_date">
        <INPUT TYPE=HIDDEN NAME="end_date" VALUE="$end_date">
        <INPUT TYPE=HIDDEN NAME="cve" VALUE="$cve">

        <table id="results-table" class="tabular" cellpadding="2" cellspacing="2" width="95%" align="center">
EOT;
            echo "<thead><tr><th sort:format=\"int\" align=\"center\">".gettext("ID")."</th>";
            echo "<th sort:format=\"int\" align=\"center\">".gettext("Risk")."</th>";
            echo "<th sort:format=\"int\" align=\"center\">".gettext("Defined On")."</th>";
            echo "<th sort:format=\"str\" align=\"left\">".gettext("Threat Family &amp; Summary")."</th>";
            echo "<th>".gettext("CVE Id")."</th>";
            echo "</tr></thead>";

         while (!$result->EOF) {
              list( $cve_id, $pid, $prisk, $pcreated, $pfamily, $psummary )
              =  $result->fields;
                       //<a href=\"lookup.php?id=$pid\" atest=\"ids\">$pid</a>
              $dt_pcreated = gen_strtotime( $pcreated, "" );
              echo "<tr>
                   <td sort:by=\"18606\" align=\"center\" valign=\"top\">
                       <a href='javascript:;' lid='".$pid."' class='scriptinfo'>".$pid."</a>
                  </td>
                     <td sort:by=\"4\" align=\"center\" valign=\"top\">
                       <img src=\"./images/risk".$prisk.".gif\" style=\"width: 25px; height: 10px; border: 1px solid\" />
                     </td>
                     <td sort:by=\"1120546800\" align=\"center\" valign=\"top\">
                       $dt_pcreated
                     </td>
                     <td style=\"text-align:left;\" sort:by=\"Gentoo Local Checks\" valign=\"top\">
                         <strong>$pfamily</strong> - $psummary
                     </td>
                     <td>";
                if($cve_id=="") {
                    echo "-"; 
                }
                else {
                    $listcves = explode(",", $cve_id); 
                    foreach($listcves as $c){
                        $c = trim($c);
                        echo "<a href='http://www.cvedetails.com/cve/$c/' target='_blank'>$c</a><br>";
                    }  
                }
            echo "</td></tr>";
            $result->MoveNext();
         }
        if ($previous >0 || $next > 0){
            echo "<tr><td class=\"nobborder\" style=\"text-align:center;\" colSpan=\"12\" height=\"18\">"; 
            if ($previous > 0) {
                echo "<input type=\"submit\" name=\"increment\" value=\"".gettext("Previous")."\" class=\"button\">&nbsp;&nbsp;&nbsp;";
            }
            if ($next > 0) {
                echo "<input type=\"submit\" name=\"increment\" value=\"".gettext("Next")."\" class=\"button\">";
            }
         echo "</td></tr></table></form>";
        }
    }
    else {
        echo "<a href=\"threats-db.php?start_date=$start_date&end_date=$end_date&kw=$kw&risk=$risk&scve=$cve\"><b>"._("No results found, try to change the search parameters")."</b></a>";
    }
	echo "</td></tr></table></center>";
}

switch($disp) {

     case "search":
          search($page, $kw, $cve, $family, $risk, $start_date, $end_date);
          break;
     
     
    default:
        home( );
        break;
}
include_once('footer.php');
?>
