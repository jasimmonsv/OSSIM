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
// $Id: webconfig.php,v 1.9 2010/04/16 17:34:54 jmalbarracin Exp $
//
//ini_set('memory_limit', '128M');
ob_implicit_flush(true);
require_once ('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsVulnerabilities");
$nohmenu = (POST('nohmenu') != "") ? 1 : (GET('nohmenu') != "") ? 1 : 0;
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
  <script type="text/javascript" src="../js/vulnmeter.js"></script>
  <? include ("../host_report_menu.php") ?>
  <script>
	function postload() {
		$(".scriptinfo").simpletip({
			position: 'right',
			content: '',
			onBeforeShow: function() { 
				var txt = this.getParent().attr('txt');
				this.update(txt);
			}
		});
	}
    function checking() {
        $('#loading_image').show();
        $('#loading_message').html('<?=_("Checking Scanner...")?>');
    }
  </script>
</head>

<body>
<?php
if (!$nohmenu) { include ("../hmenu.php"); }

$pageTitle = "Settings";

require_once('config.php');
require_once('functions.inc');
//require_once('auth.php');
//require_once('permissions.inc.php');
//require_once('header2.php');

$conf = $GLOBALS["CONF"];
$nessus_path = $conf->get_conf("nessus_path", FALSE);

$getParams = array( "section", "smethod" );
$postParams = array( "op", "submit" );

switch ($_SERVER['REQUEST_METHOD']) {
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES); 
      } else { 
         $$gp=""; 
      }
   }
   $op="";
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

ossim_valid($action, OSS_NULLABLE, 'migrate', 'update', 'illegal:' . _("action"));
ossim_valid($smethod, OSS_NULLABLE, 'rsync', 'wget', 'illegal:' . _("synchronization method"));

if (ossim_error()) {
    die(_("Invalid Parameter action"));
}

if (!$uroles['admin']) {
    echo _("Access denied");
} else {
   if ($op=="save") {

      //logAccess( "WEBCONFIG: MODIFIED" );

      $update="UPDATE vuln_settings
              SET settingValue = ?
              WHERE settingName = ?";
      $ustmt = $dbconn->Prepare($update);

      // get the data from the $_POST
      $query = "SELECT settingName
                FROM vuln_settings";
      $result = $dbconn->GetArray($query);
      foreach($result as $setting) {
	     if (isset($_POST[$setting['settingName']])) { 
            $val=htmlspecialchars(mysql_real_escape_string(trim($_POST[$setting['settingName']])), ENT_QUOTES); 
         } else { 
            $val=""; 
         }
         $updateres=$dbconn->execute($ustmt,array( $val,
                                                   $setting['settingName']));
         if($updateres === false) {
            $errMsg[] = _("Error updating vuln_settings").": " . $dbconn->ErrorMsg();
            dispSQLError($errMsg,1);
         }
      }

   } else {
      //logAccess( "Accessed Webconfig" );
   }
   
    if(($action=="migrate" || $action=="update") && Session::get_session_user()=="admin"){
        $result_check = CheckScanner();
        if ($result_check!="") {
            echo $result_check;
        }
        else {
            $data_dir = $GLOBALS["CONF"]->get_conf("data_dir");
            echo "<table width=\"900\" class=\"noborder\" style=\"background:transparent;\">";
            echo "<tr><td class=\"nobborder\" style=\"text-align:left;padding-left:9px;\">";
            echo _("Launching updateplugins.pl, please wait for a few seconds...be patient.")."&nbsp;&nbsp;";
            echo "<img width=\"16\" id=\"running_updateplugins\" align=\"absmiddle\" src=\"./images/loading.gif\" border=\"0\" alt=\""._("Running updateplugins.pl")."\" title=\""._("Running updateplugins.pl")."\">";
            echo "<br><span id=\"text_done\" style=\"display:none;\">"._("Done")."</span>";
            echo "</td></tr></table>";
            echo "<pre>";
            passthru("export HOME='/tmp';cd $data_dir/scripts/vulnmeter/;perl updateplugins.pl $action $smethod");
            echo "</pre>";
            ?>
            <script type="text/javascript">
                //<![CDATA[
                $('#running_updateplugins').hide();
                $('#text_done').show();
                //]]>
            </script>
            <?
        }
    }
    
   $dbconn->disconnect();
   $dbconn = $db->connect();
   
   $settingTabs="";
   $settingContent="";
   
   $sq = "SELECT distinct settingSection
          FROM vuln_settings
          ORDER BY settingSection";
   $result = $dbconn->GetArray($sq);
   $query="SELECT *
           FROM vuln_settings 
           WHERE settingSection = ?";
   $stmt = $dbconn->Prepare($query);
   if($result === false) {
      $errMsg[] = _("SQL Error getting settingSections").": " . $dbconn->ErrorMsg();
      dispSQLError($errMsg, 1);
   } else {
      echo "<form>";
      $i = 0;
      $numSections = count($result) - 1;
      foreach($result as $section) {
         $result2 = $dbconn->GetArray($stmt,array($section['settingSection']));
         if($result2 === false) {
            $errMsg[] = _("SQL Error getting data").": " . $dbconn->ErrorMsg();
            dispSQLError($errMsg, 1);
         } else {
            if( $settingTabs != "") { $settingTabs .= ""; }
            if ( $section['settingSection'] == "Subnets" && $enableSub == "0" ) {

            } 
            elseif($section['settingSection']!="Compliance" && $section['settingSection']!="Lists" && $section['settingSection']!="Mail"){
               $settingTabs .= "<input id=\"b$i\" type=\"button\" onClick=\"showDivSettings($i, 'section',$numSections);return false;\" value=\"".$section['settingSection']."\" class=\"".(($section['settingSection']=="Auth")?"buttonon":"button")."\">&nbsp;&nbsp;";
            }
            $settingContent .= createHiddenDiv($section['settingSection'], $i, $result2);
               $i++;
         }
      }
     echo "</form>";
   }
   echo "<div>";
   echo "<form method='post' action='" . $_SERVER['SCRIPT_NAME'] . "'>";
   echo "<input type='hidden' name='nohmenu' value='$nohmenu'>";
   echo "<input type='hidden' name='op' value='save'>";
   echo "<p>" . $settingTabs . "</p>\n";
   echo $settingContent;
   echo "<p><input type='submit' name='submit' value='"._("Update")."' class='button'></p>";
   if(Session::get_session_user()=="admin"){
        echo "<center>";
        ?>
        <table width="900" class="transparent">
            <tr>
            <?php
            $display = "";
            if (preg_match("/nessus\s*$/i", $nessus_path)) {
                $display = "style='display:none;'";
            }
            ?>
            <tr <?php echo $display;?>>
                <td class="nobborder" style="padding:12px 0px 10px 0px;text-align:center;"><b><?php echo _("Synchronization method") ?>:</b>
                    <input type="radio" name="smethod" value="rsync" checked="checked"/> <?php echo _("rsync - fastest");?>
                    <input type="radio" name="smethod" value="wget" /> <?php echo _("wget - if rsync is blocked");?>
                </td>
            </tr>
                <td class="nobborder" style="text-align:center;">
                <input type="button" class="button" onclick="checking();document.location.href='webconfig.php?action=migrate&smethod='+$('input[name=smethod]:checked').val()" value="<?=_("Recreate Scanner DB (can be used for Nessus < -- > OpenVAS migration)")?>">&nbsp;&nbsp;&nbsp;                
                <img style="display:none;" id="loading_image" width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?=_("Loading")?>" title="<?=_("Loading")?>">&nbsp;&nbsp;
                <span id="loading_message"><span>
                </td>
            </tr>
            <tr>
                <td style="padding-top:8px;text-align:center;" class="nobborder">
                    <input type="button" class="button" onclick="checking();document.location.href='webconfig.php?action=update&smethod='+$('input[name=smethod]:checked').val()" value="<?=_("Update Scanner DB")?>">
                </td>
            </tr>
        </table>
        <?
        echo "</center>";
   }
   echo "</form>";
   echo "</div>";
/*
   echo <<<EOT
<form method="post" action="$_SERVER['SCRIPT_NAME']">
<input type="hidden" name="op" value="save">
<table>
EOT;
      foreach($result as $setting) {
         echo "<tr><th>$setting[settingDescription]</th>";
         echo "<td><input type='text' name='$setting[settingName]'
                    value='$setting[settingValue]' size=50></input></td></tr>";
      }
      echo <<<EOT
</table>
<input type="submit" name="submit" value="Save">
</form>
EOT;
   }
*/
}
require_once('footer.php');

function createHiddenDiv($name, $num, $data) {
   $text = "";
   $style = "";
   if($num == 0) {
      $style = "style='display: block;'";
   }
   else {
      $style = "style='display: none;'";
   }
   $text = "<center><div id='section" . $num . "' name='$name' class='settings' $style>\n";
   $text .= "<table>\n";
   foreach($data as $element) {

      $devnote = $element['developerNotes'];
      $devnote = str_replace("\r\n", "<br>", $devnote );

      //$text .= "<tr><th align=left><a href=\"javascript:void(0);\" class=\"scriptinfo\" txt=\"" . $devnote 
      //      . "\">". $element['settingDescription'] . "</a></th>"
      $text .= "<tr><th align=left>". $element['settingDescription'] . "</th>"
            . "<td><input type='text' name='" . $element['settingName'] . "' value='" .
               $element['settingValue'] . "' size=50</input></td></tr>\n";
   }
   $text .= "</table>\n</div></center>\n";
   return $text;
}
function CheckScanner(){
    $result = "";
    $arr_out = array();
    
    if (preg_match("/omp\s*$/i", $GLOBALS["CONF"]->db_conf["nessus_path"])) { // OMP
        $command = "export HOME='/tmp';".$GLOBALS["CONF"]->db_conf["nessus_path"]." -h ".$GLOBALS["CONF"]->db_conf["nessus_host"]." -p ".$GLOBALS["CONF"]->db_conf["nessus_port"]." -u ".$GLOBALS["CONF"]->db_conf["nessus_user"]." -w ".$GLOBALS["CONF"]->db_conf["nessus_pass"]." -iX \"<help/>\" | grep CREATE_TASK 2>&1";
    }
    else { // OpenVAS and nessus
        $command = "export HOME='/tmp';".$GLOBALS["CONF"]->db_conf["nessus_path"]." -qxP ".$GLOBALS["CONF"]->db_conf["nessus_host"]." ".$GLOBALS["CONF"]->db_conf["nessus_port"]." ".$GLOBALS["CONF"]->db_conf["nessus_user"]." ".$GLOBALS["CONF"]->db_conf["nessus_pass"]." | grep max_hosts 2>&1";
    }
    //print_r($command);
    exec($command,$arr_out);
    $out = implode(" ",$arr_out);
    //print_r($out); 
    if (preg_match("/host not found|could not open a connection|login failed|could not connect/i",$out)) {
        return _("Scanner check failed").":<br>".implode("<br>",$arr_out);
    }
    else if (!preg_match("/max_hosts/i",$out) && !preg_match("/CREATE_TASK/i",$out)) {
        return _("Scanner check failed");
    }
    
    return $result;
}
?>
