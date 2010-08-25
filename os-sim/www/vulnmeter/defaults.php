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
// $Id: defaults.php,v 1.5 2010/01/12 11:03:43 jmalbarracin Exp $
//

/***********************************************************/
/*		    Inprotect			    	   */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect.com			   */
/*							   */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.				    	   */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.					   */
/*							   */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA			   */
/*							   */
/* Contact Information:					   */
/* Inprotect (inprotect-devel@lists.sourceforge.net	   */
/* http://inprotect.sourceforge.net			   */
/***********************************************************/
/* See the README.txt and/or help files for more	   */
/* information on how to use & config.			   */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.		   */
/*							   */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items	   */
/* discovered with this program's use.			   */
/***********************************************************/

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
  
  <? include ("../host_report_menu.php") ?>
  <script>
  $(document).ready(function() {
    $('#loading').toggle();
    $('#pluginsdefault').toggle();
    });
  </script>
  
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = "Nessus Default Settings";
require_once('config.php');
require_once('functions.inc');
//require_once('auth.php');
//require_once('header2.php');
//include ('permissions.inc.php');

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
        if (isset($_GET['prefs'])) { $prefs=htmlspecialchars(mysql_escape_string(trim($_GET['prefs'])), ENT_QUOTES); }
	else { $prefs=""; }
# Not used in GET but needs to be defined to avoid PHP errors
	$submit="";
	$AllPlugins="";
	$NonDOS="";
	$DisableAll="";
        break;
case "POST" :
        if (isset($_POST['prefs'])) { $prefs=htmlspecialchars(mysql_escape_string(trim($_POST['prefs'])), ENT_QUOTES); }
	else { $prefs=""; }
        if (isset($_POST['submit'])) { $submit=htmlspecialchars(mysql_escape_string(trim($_POST['submit'])), ENT_QUOTES); }
	else { $submit=""; }
	if (isset($_POST['AllPlugins'])) { $AllPlugins=htmlspecialchars(mysql_escape_string(trim($_POST['AllPlugins'])), ENT_QUOTES); }
	else { $AllPlugins=""; }
	if (isset($_POST['NonDOS'])) { $NonDOS=htmlspecialchars(mysql_escape_string(trim($_POST['NonDOS'])), ENT_QUOTES); }
	else { $NonDOS=""; }
	if (isset($_POST['DisableAll'])) { $DisableAll=htmlspecialchars(mysql_escape_string(trim($_POST['DisableAll'])), ENT_QUOTES); }
	else { $DisableAll=""; }
	break;
}

//if (!$uroles['profile']) {
//    echo "Access Denied!!!";
//    logAccess( " Access denied" );
    //require_once('footer.php');
//    die();
//}
//else {
    echo "<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\" class=\"noborder\">";
    echo "<tr class=\"noborder\" style=\"background-color:white\"><td class=\"headerpr\">";
    echo "    <table width=\"100%\" class=\"noborder\" style=\"background-color:transparent\">";
    echo "        <tr class=\"noborder\" style=\"background-color:transparent\"><td width=\"20\" class=\"noborder\">";
    echo "        <a href=\"settings.php\"><img src=\"./images/back.png\" border=\"0\" alt=\""._("Back")."\" title=\""._("Back")."\"></a>";
    echo "        </td><td width=\"780\">";
    echo "        </font>";
    echo "        "._("Nessus Scanner Settings")."</td></tr>";
    echo "    </table>";
    echo "</td></tr>";
    echo "</table></center>";
    if ($prefs=="1" or $prefs=="") {
        echo "<center>";
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\">";
        echo "<tr><td style=\"padding-top:5px;\">";
        echo "<form>";
        echo "<center>";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=1'\" value=\""._("Preferences")."\" class=\"".(($prefs==1||$prefs=="")? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=3'\" value=\""._("Plugins")."\" class=\"".(($prefs==3)? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=4'\" value=\""._("View Configuration File")."\" class=\"".(($prefs==4)? "menuon":"menu")."\"><br><br>";
        echo "</center>";
        echo "</form>";
//        echo "<A href=\"defaults.php?prefs=1\">Preferences</A> | ";
//        echo "<A href=\"defaults.php?prefs=3\">Plugins</A> | ";
//        echo "<A href=\"defaults.php?prefs=4\">View Configuration File</a><BR><BR>";

        $sql = "SELECT t.nessusgroup, t.nessus_id, t.field, t.type, t.value, n.value, t.category
               FROM vuln_nessus_preferences_defaults t
               LEFT JOIN vuln_nessus_preferences n
               ON t.nessus_id = n.nessus_id
               order by category desc, nessusgroup, nessus_id";

        $result=$dbconn->execute($sql);
        $counter = 0;

        if ($submit=="save") {
	    logAccess( "Save default profile" );
            while(!$result->EOF) {
                $counter++;
                $vname = "form".$counter;
		if (isset($_POST[$vname]))
		{
			$$vname=htmlspecialchars(mysql_escape_string(trim($_POST[$vname])), ENT_QUOTES);
		}
		elseif (isset($_GET[$vname]))
		{
			logAccess( "GET instead of POST method used - failed to save" );
			die("Please use the default.php form to submit your changes.");
		}
		else { $$vname=""; }
                list ($nessusgroup, $nessus_id, $field, $type, $default, $value, $category) = $result->fields;
                updatedb($nessus_id, $$vname, $dbconn, $type, $category );
                $result->MoveNext();
            }

			# find all records in the vuln_nessus_preferences table that 
			# have no matching value in vuln_nessus_preferences_defaults
			# and delete them from vuln_nessus_preferences

            $sql = "select n.nessus_id from vuln_nessus_preferences n
                   left join vuln_nessus_preferences_defaults t
                   on n.nessus_id = t.nessus_id
                   where t.nessus_id is null";
            $result=$dbconn->execute($sql);

            while(!$result->EOF) {
                list ($pleasedeleteme) = $result->fields;

                $sql2 = "delete from vuln_nessus_preferences
                        where nessus_id = \"$pleasedeleteme\"";
                $result2=$dbconn->execute($sql2);
		logAccess( "Deleted obselete config item $pleasedeleteme" );
                $result->MoveNext();
            }

            echo "<BR><BR><BR><CENTER><B>"._("Nessus settings saved, please proceed to the")." <A href=\"settings.php\">"._("Profile Selection")."</A> "._("page").".</B></CENTER><BR>";
        }
        else {
      //logAccess( "Display default preferences table" );
            $lastvalue = "";
        print "<center>";
        print "<form method=\"post\" action=\"defaults.php\"><input type=\"hidden\" name=\"prefs\" value=\"1\"><input type=\"submit\" name=\"submit\" value=\""._("save")."\" class=\"btn\"><BR><BR>";
        print "<table border=\"0\">";
		while(!$result->EOF) {
                $counter++;
                list ($nessusgroup, $nessus_id, $field, $type, $default, $value, $category) = $result->fields;
                if ($nessusgroup != $lastvalue) {
                    print "<tr><th><b>$nessusgroup</b></th><th></th></tr>";
                    $lastvalue = $nessusgroup;
                }
                $vname = "form".$counter;
                print formprint($field, $vname, $type, $default, $value);
                $result->MoveNext();
            }

            echo "</table><BR><BR><INPUT type=\"submit\" name=\"submit\" value=\""._("save")."\" class=\"btn\"><BR><BR>
            </form>
            </center></td></tr></table></center>";
        }
    }
    elseif ($prefs=="4") {
  //logAccess( "View detault nessus configuration" );
        echo "<center>";
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\">";
        echo "<tr><td class=\"nobborder\" style=\"padding-top:5px;padding-bottom:10px;\">";
        echo "<form>";
        echo "<center>";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=1'\" value=\""._("Preferences")."\" class=\"".(($prefs==1)? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=3'\" value=\""._("Plugins")."\" class=\"".(($prefs==3)? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=4'\" value=\""._("View Configuration File")."\" class=\"".(($prefs==4)? "menuon":"menu")."\"><br><br>";
        echo "</center>";
        echo "</form>";
//        echo "<A href=\"defaults.php?prefs=1\">Preferences</A> | <A href=\"defaults.php?prefs=3\">Plugins</A> | <A href=\"defaults.php?prefs=4\">View Configuration File</a><BR><BR>";
        echo "<CENTER><TEXTAREA rows=15 cols=80 >\n# This file was automagically created\n\n";

        $query="SELECT t1.id, t1.enabled FROM vuln_nessus_plugins t1
        	LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id WHERE t2.name='scanner' order by t1.id";
        $result=$dbconn->Execute($query);
        echo "begin(SCANNER_SET)\n";
        while (!$result->EOF) {
            list ($id, $enabled) = $result->fields;
            $enabled1="yes";
            if ($enabled=="N") $enabled1="no";
            echo " $id = $enabled1\n";

            $result->MoveNext();
        }
        echo "end(SCANNER_SET)\n\n";

        $query="Select nessus_id, value from vuln_nessus_preferences where category='SERVER_PREFS'";
        $result=$dbconn->Execute($query);

        echo "begin(SERVER_PREFS)\n";

        while (!$result->EOF) {
            list( $nessus_id, $value)=$result->fields;

            echo " $nessus_id = $value\n";

            $result->MoveNext();
        }

        echo "end(SERVER_PREFS)\n\n";


        $query="Select nessus_id, value from vuln_nessus_preferences where category='PLUGINS_PREFS'";
        $result=$dbconn->Execute($query);

        echo "begin(PLUGINS_PREFS)\n";

        while (!$result->EOF) {
            list( $nessus_id, $value)=$result->fields;

            echo " $nessus_id = $value\n";

            $result->MoveNext();
        }

        echo "end(PLUGINS_PREFS)\n\n";
        $cat_id = getScnCATID();
        $query="SELECT t1.id, t1.enabled FROM vuln_nessus_plugins t1
        	LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id WHERE t2.name <> 'scanner' order by t1.id";
        $result=$dbconn->Execute($query);
        echo "begin(PLUGIN_SET)\n";
        while (!$result->EOF) {
            list ($id, $enabled) = $result->fields;
            $enabled1="yes";
            if ($enabled=="N") $enabled1="no";
            echo " $id = $enabled1\n";

            $result->MoveNext();
        }

        echo "end(PLUGIN_SET)\n\n";
        echo "</TEXTAREA></CENTER>";
        echo "</td></tr>";
        echo "</table></center>";
    }

    elseif ($prefs=="3") {
        echo "<center>";
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\">";
        echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";
        echo "<form>";
        echo "<center>";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=1'\" value=\""._("Preferences")."\" class=\"".(($prefs==1)? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=3'\" value=\""._("Plugins")."\" class=\"".(($prefs==3)? "menuon":"menu")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=\"button\" onclick=\"document.location.href='defaults.php?prefs=4'\" value=\""._("View Configuration File")."\" class=\"".(($prefs==4)? "menuon":"menu")."\"><br><br>";
        echo "</center>";
        echo "</form>";
//        echo "<A href=\"defaults.php?prefs=1\">Preferences</A> |
//        <A href=\"defaults.php?prefs=3\">Plugins</A> | <A href=\"defaults.php?prefs=4\">View Configuration File</a>";

        if ($submit=="submit") {
      //logAccess( "Save default plugins list" );
            reset ($_POST);   // if form method="post"

            $result=$dbconn->Execute("Update vuln_nessus_plugins set enabled='N'");

            while (list($key, $value) = each ($_POST)) {
		$key=htmlspecialchars(mysql_escape_string(trim($key)), ENT_QUOTES);
                if (substr($key,0,3)=="PID") {
                    $key=substr($key, 3);
                    if(is_numeric($key)) {
			$results=$dbconn->Execute("Update vuln_nessus_plugins set enabled='Y' where ID=$key");
		    }
                }
            }
        }
        if ($AllPlugins=="Enable All") {
      //logAccess( "Selected enable all plugins" );
            $result=$dbconn->Execute("Update vuln_nessus_plugins set enabled='Y'"); }

        if ($NonDOS=="Enable Non DOS") {
      //logAccess( "Selected enable all non dos plugins" );
            $result=$dbconn->Execute("Update vuln_nessus_plugins set enabled='N'");

            $result=$dbconn->Execute("SELECT t1.id FROM vuln_nessus_plugins t1
        	LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id WHERE t2.name <> 'denial' AND t2.name <> 'destructive_attack'");
            while (list($enableid)=$result->fields) {
                $result1=$dbconn->Execute("Update vuln_nessus_plugins set enabled='Y' where id=$enableid");
                $result->MoveNext();
            }
        }
	if ($DisableAll=="Disable All") {
	    $query="update vuln_nessus_plugins set enabled='N'";
	    $result=$dbconn->execute($query);
	}
        echo "<center>";
        echo "<div id=\"loading\"><img width=\"16\" align=\"absmiddle\" src=\"images/loading.gif\">&nbsp;&nbsp;"._("Please, wait a few seconds")." ...</div>";
        echo "</center>";
        echo "<br>";
        echo "<div id=\"pluginsdefault\" style=\"display:none;\">";
        echo "<CENTER><form method=\"post\" action=\"defaults.php\"><input type=\"hidden\" name=\"prefs\" value=\"3\">";
        echo "<table width=\"600\">
        <tr><th colspan=2><B>"._("Plugins")."</B></th></tr>";

        //$result=$dbconn->Execute("Select count(id) plugincount from vuln_plugins");
        //list($pcount)=$result->fields;
        $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_plugins where enabled='Y'");
        list($penabled)=$result->fields;

        echo "<tr><td colspan=2>$penabled "._("Nessus plugins")."</td></tr>\n";
        echo "<tr><td colspan=2><input type=\"submit\" name=\"AllPlugins\" value=\""._("Enable All")."\" class=\"btn\">&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"NonDOS\" value=\""._("Enable Non DOS")."\" class=\"btn\">&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"DisableAll\" value=\""._("Disable All")."\" class=\"btn\"><FONT SIZE=\"1\"><BR><BR></FONT><input type=\"submit\" name=\"submit\" value=\""._("save")."\" class=\"btn\"></td></tr>";


        $result=$dbconn->Execute("SELECT distinct name FROM vuln_nessus_family where name<>'' order by name");
        while (list($family)=$result->fields) {
            echo "<tr><td colspan=2><b>$family</b><hr></td></tr>\n";

            $result1=$dbconn->Execute("SELECT t1.id, t1.name, t2.name as family, t1.enabled from vuln_nessus_plugins t1
            	LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
          		WHERE t2.name='$family' ORDER BY t1.name");
            while(list($pid, $pname, $pcategory, $penabled)=$result1->fields) {
                echo "<tr><td align=\"right\" width=\"100\"><INPUT type=checkbox name=\"PID$pid\" ";
                if ($penabled=="Y") {
                    echo "checked ";
                }
                echo "id=$pid></td><td width=\"500\" style=\"text-align:left;\"><a target=\"_new\" href=\"lookup.php?id=$pid\">$pname</a> | $pcategory</td></tr>\n";
                $result1->MoveNext();
            }
            $result->MoveNext();
        }

        echo "</table>";
        echo "<BR><BR><input type=\"submit\" name=\"submit\" value=\""._("save")."\" class=\"btn\"><BR><BR></form></CENTER></div>";
		echo "</td></tr>";
        echo "</table>";
        echo "</center>";
        $result->Close();
    }
//}

include("footer.php");


function updatedb($nessus_id, $fieldvalue, $dbconn, $type, $category ) {
    if ($type=="C" and $fieldvalue=="") {
        $fieldvalue="no";
    }
    else
    {
	$fieldvalue=htmlspecialchars(mysql_escape_string(trim($fieldvalue)), ENT_QUOTES);
    }

    $sql = "select count(*) from vuln_nessus_preferences where nessus_id = \"$nessus_id\"";
    $result=$dbconn->execute($sql);

    list($existing)=$result->fields;
    if ($existing == 0) {
	# Do an insert statement
	logAccess( "New default preference added - $nessus_id" );
        $sql = "insert vuln_nessus_preferences set nessus_id = \"$nessus_id\", value=\"$fieldvalue\", type=\"$type\", category=\"$category\"";
    }
    else {
        $sql = "update vuln_nessus_preferences set value=\"$fieldvalue\", type=\"$type\", category=\"$category\" where nessus_id = \"$nessus_id\"";
    }
    $result=$dbconn->execute($sql);
}


function formprint($field, $vname, $type, $default, $value) {
	# The pseudocode below will load a default value for an undefined field
	# to help make it easier for new fields to be added into the structure
	#
    $retstr = "";
    if ( is_null($value)) {
        if ($type == "R") {
            $value = explode(";", $default);
            $value = $value[0];
        }
        else {
            $value = $default;
        }
    }

    if ($type == "C") {
		# Checkbox code here
        $retstr="<tr><td style=\"text-align:left;\">$field</td><td><INPUT type=\"checkbox\" name=\"$vname\" value=\"yes\"";
        if ($value=="yes") {
            $retstr.=" checked";
        }
        $retstr.="></td></tr>";
    }
    elseif ($type == "R") {
		# Radio button code here
        $retstr="<tr><td style=\"text-align:left;\"><B>$field<B></td><td></td></tr>";
        $array = explode(";", $default);
        foreach($array as $myoption) {
            $retstr.="<tr><td style=\"text-align:left;\"><INPUT type=\"radio\" name=\"$vname\" value=\"$myoption\"";
            if ($value == $myoption) {
                $retstr.=" checked";
            }
            $retstr.="> $myoption</td></tr>";
        }
    }
    elseif ($type == "P") {
		# Password code here
        $retstr="<tr><td style=\"text-align:left;\">$field</td><td><INPUT type=\"password\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    else {
		# Assume it is a text box
        $retstr="<tr><td style=\"text-align:left;\">$field</td><td><INPUT type=\"text\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    //$retstr .= "\n";
    return $retstr;
}
?>
