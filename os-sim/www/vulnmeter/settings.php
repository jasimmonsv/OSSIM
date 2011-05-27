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
// $Id: settings.php,v 1.12 2010/03/27 14:15:58 jmalbarracin Exp $
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
require_once ('ossim_conf.inc');
require_once ('classes/OMP.inc');
require_once ('classes/Util.inc');
require_once ('functions.inc');

$conf        = $GLOBALS["CONF"];
$version     = $conf->get_conf("ossim_server_version", FALSE);
$nessus_path = $conf->get_conf("nessus_path", FALSE);
$pro         = ( preg_match("/pro|demo/i",$version) ) ? true : false;

Session::logcheck("MenuEvents", "EventsVulnerabilities");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("Vulnmeter"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<script type="text/javascript" src="../js/vulnmeter.js"></script>
	<?php include ("../host_report_menu.php") ?>
	<script type="text/javascript">
		function postload() {
			$(".scriptinfo").simpletip({
				position: 'right',
				onBeforeShow: function() { 
					var id = this.getParent().attr('lid');
					this.load('lookup.php?id=' + id);
				}
			});
			
			$('#loading').toggle();
			
			$('.updateplugins').bind('click', function() {
				$('#div_updateplugins').show();
			});
			
			$('.updateautoenable').bind('click', function() {
				$('#div_updateautoenable').show();
			});
			
			$('.createprofile').bind('click', function() {
				$('#div_createprofile').show();
			});
			
			$('.saveprefs').bind('click', function() {
				$('#div_saveprefs').show();
			});
		}
  
		function showEnableBy(){
			$("#cat1").toggle();
			$("#fam1").toggle();
			$("#cat2").toggle();
			$("#fam2").toggle();  
		}
	  
		function showEnableByNew() {
			$("#cat1n").toggle();
			$("#fam1n").toggle();
			$("#cat2n").toggle();
			$("#fam2n").toggle();
		}

		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!='-1'){
				$('#user').val('-1');
			}
			else if (select=='user' && $('#user').val()!='-1'){
				$('#entity').val('-1');
			}

			if($('#entity').val()=='-1' && $('#user').val()=='-1') { 
				$('#user').val('0'); 
			}
		}
	</script>
</head>

<body>
<?php
include ("../hmenu.php");

$pageTitle = "Scanners";
require_once('config.php');
require_once('functions.inc');
//require_once('auth.php');
//require_once('header2.php');
//require_once('permissions.inc.php');

$getParams  = array( "disp", "item", "page", "delete", "prefs", "uid", "sid",
           "op", "confirm", "preenable", "bEnable" );

$postParams = array( "disp", "saveplugins", "page", "delete", "prefs", "uid", "sid",
           "op", "sname", "sdescription", "sautoenable", "item",
           "AllPlugins", "NonDoS", "DisableAll", "submit", "fam",
           "cloneid", "auto_cat_status", "auto_fam_status", "stype", "importplugins", "tracker", "preenable", "bEnable", "user", "entity" );


switch ($_SERVER['REQUEST_METHOD'])
{
	case "GET" :
	    foreach ($getParams as $gp) 
	    {
			if (isset($_GET[$gp])) 
				$$gp=Util::htmlentities(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES);
			else 
				$$gp="";
		  
	   }
	   
	   $submit      = "";
	   $AllPlugins  = "";
	   $NonDOS      = "";
	   $DisableAll  = "";
	   $saveplugins = "";
	break;

	case "POST" :
		foreach ($postParams as $pp) 
		{
			if (isset($_POST[$pp]))
				$$pp=Util::htmlentities(mysql_real_escape_string(trim($_POST[$pp])), ENT_QUOTES);
			else 
				$$pp="";
		  
	   }
	break;
}


ossim_valid($sid, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Sid"));

if (ossim_error()) {
    die(_("Invalid Parameter Sid"));
}


if(isset($_POST['authorized_users'])) 
{
	foreach($_POST['authorized_users'] as $user) {
		$users[] = Util::htmlentities(mysql_real_escape_string(trim($user)), ENT_QUOTES); 
	}
}

//if (!($uroles['profile'] || $uroles['admin'])) {
//   echo "Access Denied!!!<br>";
//   logAccess( $username . " : " . $_SERVER['SCRIPT_NAME'] . " : Unauthorized Access" );
//   //require_once('footer.php');
//   die();
//}

$db     = new ossim_db();
$dbconn = $db->connect();

$query              = "SELECT count(*) FROM vuln_nessus_plugins";
$result             = $dbconn->execute($query);
list($pluginscount) = $result->fields;

if ($pluginscount==0) {
   die ("<h2>"._("Please run updateplugins.pl script first before using web interface.")."</h2>");
}

function navbar( $sid ) {
	global $profilename, $dbconn;
	//<h3>Manage Nessus Scan Profiles</h3>

	echo "<center>";
	
	if ($sid) 
	{
		$query  = "SELECT name FROM vuln_nessus_settings WHERE id='$sid'";
		$result = $dbconn->execute($query);
		list($profilename) = $result->fields;

		echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\" class=\"noborder\">";
		echo "<tr class=\"noborder\" style=\"background-color:white\"><td class=\"headerpr\">";
		echo "    <table width=\"100%\" class=\"noborder\" style=\"background-color:transparent\">";
		echo "        <tr class=\"noborder\" style=\"background-color:transparent\"><td width=\"20\" class=\"noborder\">";
		echo "        <a href=\"settings.php\"><img src=\"./images/back.png\" border=\"0\" alt=\"Back\" title=\"Back\"></a>";
		echo "        </td><td width=\"780\">";
		echo "        <span style=\"font-weight:normal;\">"._("EDIT PROFILE").":</span> <font color=black><b>".html_entity_decode($profilename)."<b></font>";
		echo "        </td></tr>";
		echo "    </table>";
		echo "</td></tr>";
		echo "<tr><td class=\"nobborder\">";
		echo "       <table width=\"100%\"><tr><td class=\"nobborder\" style=\"text-align:center;padding-top:5px;padding-bottom:5px;\">";
		echo "<form>";
		echo "<input type=button onclick=\"document.location.href='settings.php?disp=editauto&amp;sid=$sid'\" class=\"".(($_GET['disp']=="editauto"||$_GET['disp']=='edit')? "buttonon":"button")."\" value=\""._("AUTOENABLE")."\">&nbsp;&nbsp;&nbsp;";
		echo "<input type=button onclick=\"document.location.href='settings.php?disp=editplugins&amp;sid=$sid'\" class=\"".(($_GET['disp']=='editplugins')? "buttonon":"button")."\" value=\""._("PLUGINS")."\">&nbsp;&nbsp;&nbsp;";
		echo "<input type=button onclick=\"document.location.href='settings.php?disp=linkplugins&amp;sid=$sid'\" class=\"".(($_GET['disp']=='linkplugins')? "buttonon":"button")."\" style=\"display:none;\" value=\""._("ImPLUGINS")."\">";
		echo "<input type=button onclick=\"document.location.href='settings.php?disp=editprefs&amp;sid=$sid'\" class=\"".(($_GET['disp']=='editprefs')? "buttonon":"button")."\" value=\""._("PREFS")."\">&nbsp;&nbsp;&nbsp;";

		//<input type=button onclick="document.location.href='settings.php?disp=editusers&amp;sid=$sid'" class="button" value="USERS">&nbsp;&nbsp;&nbsp;
		echo "<input type=button onclick=\"document.location.href='settings.php?disp=viewconfig&amp;sid=$sid'\" class=\"".(($_GET['disp']=='viewconfig')? "buttonon":"button")."\" value=\""._("VIEW CONFIG")."\">&nbsp;&nbsp;&nbsp;";
		echo "</form>";
		?>
		
		<div id="div_updateautoenable" style="display:none">
			<br/>
			<img width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?php echo _("Applying changes...")?>" title="<?php echo _("Applying changes...")?>">
			&nbsp;<?php echo _("Applying changes, please wait few seconds...") ?>
		</div>
		<?php
	}
	
	echo "</center><br>";
}

function new_profile() {
   global $dbconn,$username,$version;

    //navbar( $sid );
    echo "<center><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\" class=\"noborder\">";
    echo "<tr class=\"noborder\" style=\"background-color:white\"><td class=\"headerpr\">";
    echo "    <table width=\"100%\" class=\"noborder\" style=\"background-color:transparent\">";
    echo "        <tr class=\"noborder\" style=\"background-color:transparent\"><td width=\"20\" class=\"noborder\">";
    echo "        <a href=\"settings.php\"><img src=\"./images/back.png\" border=\"0\" alt=\""._("Back")."\" title=\""._("Back")."\"></a>";
    echo "        </td><td width=\"780\">";
    echo "        </font>";
    echo "        "._("New Profile")."</td></tr>";
    echo "    </table>";
    echo "</td></tr>";
    echo "</table></center>";

    echo "<center>";
    echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\">";
    echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";
    // build pulldown of existing scan policies/profiles in case user
    // wants to clone an existing policy instead of starting from scratch
    $query  = "SELECT id, name, description FROM vuln_nessus_settings";
    $result = $dbconn->GetArray($query);
    
	$allpolicies  = "<select name='cloneid'>\n";
    $allpolicies .= "<option value=''>"._("None")."</option>\n";

    if($result) 
	{
       foreach($result as $sp) {
          if($sp['description']!="") {
            $allpolicies .= "<option value='".$sp['id']."'>".$sp['name']." - ".$sp['description']."</option>\n";
          }
          else {
            $allpolicies .= "<option value='".$sp['id']."'>".$sp['name']."</option>\n";
          }
       }
    }
    
	$allpolicies .= "</select>";
    
	echo <<<EOT
<CENTER>
<form method="post" action="settings.php">
<input type="hidden" name="disp" value="create">
<table width="650">
<tr>
EOT;
?>
<div id="div_createprofile" style="display:none;padding-bottom:8px;">
	<br/>
	<img width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?php echo _("Applying changes...")?>" title="<?php echo _("Applying changes...")?>">
	&nbsp;<?php echo _("Creating the profile, please wait few seconds...") ?>
	<br/>
</div>

<?php
    echo "<td class='left'>"._("Name").":</td>";
    echo <<<EOT
<td class="left"><input type="text" name="sname" value=""/></td>
</tr>
<tr>
EOT;
    echo "<td class='left'>"._("Description").":</td>";
    echo <<<EOT
<td class="left"><input type="text" name="sdescription" value=""/></td>
</tr>
<tr>
EOT;
    echo "<td class='left'>"._("Clone existing scan policy").":</td><td class='left'>$allpolicies</td>";
    echo <<<EOT
</tr>
EOT;

$users    = Session::get_users_to_assign($dbconn);
$entities = Session::get_entities_to_assign($dbconn);

?>
	<tr>
        <td class='left'><?php echo _("Make this profile available for");?></td>
        <td class='left'>
			<table cellspacing="0" cellpadding="0" class="transparent">
				<tr>
					<td class='left nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>	
					<td class='nobborder'>				
						<select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >
							
							<?php
														
							$num_users    = 0;
							$current_user = Session::get_session_user();
							
							if ( ! Session::am_i_admin() )
								$user = (  $user == "" && $entity == "" ) ? $current_user : $user;
							
							foreach( $users as $k => $v )
							{
								$login = $v->get_login();
								
								$selected = ( $login == $user ) ? "selected='selected'": "";
								$options .= "<option value='".$login."' $selected>$login</option>\n";
								$num_users++;
							}
							
							if ($num_users == 0)
								echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
							else
							{
								echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
								if ( Session::am_i_admin() )
								{
									$default_selected = ( ( $user == "" || intval($user) == 0 ) && $entity == "" ) ? "selected='selected'" : "";
									echo "<option value='0' $default_selected>"._("ALL")."</option>\n";
								}
															
								echo $options;
							}
													
							?>
						</select>
					</td>
			
					<?php if ( !empty($entities) ) { ?>
					<td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>
									
					<td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
					<td class='nobborder'>	
						<select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
							<option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
							<?php
							foreach ( $entities as $k => $v ) 
							{
								$selected = ( $k == $user_entity ) ? "selected='selected'": "";
								echo "<option value='$k' $selected>$v</option>";
							}
							?>
						</select>
					</td>
						<?php } ?>
				</tr>
			</table>
		</td>
	</tr>

<?php

echo "<tr style='display:none'>";
echo "<td class='left'>"._("Link scans run by this profile in Network Hosts")."<br>"._("Purpose so that Network Hosts can be tracking full/perfered audits").".</td>";
echo "<td class='left'><input type='checkbox' name='tracker'/><font color='red'>"._("Update Host Tracker \"Network Hosts\" Status")."</font></input></td>";
echo "</tr>";
echo <<<EOT
<tr>
EOT;
echo "<td class='left'>"._("Autoenable plugins option").":</td>";
    echo <<<EOT
<td class='left'><select name="sautoenable"  onChange="showEnableByNew();return false;">
EOT;

echo "<option value=\"C\" selected>"._("Autoenable by category")."</option>";
echo "<option value=\"F\">"._("Autoenable by family")."</option>";
echo <<<EOT
</select></td>
</tr>
<tr id="cat1n">
EOT;
echo "<td class='nobborder left'>"._("Set all autoenabled categories to").":</td>";
echo <<<EOT
<td class='left nobborder'><select name="auto_cat_status">
EOT;
echo "<option value=\"1\">"._("Enable All")."</Option>";
echo "<option value=\"2\">"._("Enable New")."</Option>";
echo "<option value=\"3\">"._("Disable New")."</Option>";
echo "<option value=\"4\">"._("Disable All")."</Option>";
echo "<option value=\"5\">"._("Intelligent")."</Option>";
echo <<<EOT
</select></td></tr>
<tr id="fam1n" style="display:none;">
EOT;
echo "<td class='left'>"._("Set all autoenabled families to").":</td>";
echo <<<EOT
<td><select name="auto_fam_status">
EOT;
echo "<option value=\"1\">"._("Enable All")."</Option>";
echo "<option value=\"2\">"._("Enable New")."</Option>";
echo "<option value=\"3\">"._("Disable New")."</Option>";
echo "<option value=\"4\">"._("Disable All")."</Option>";
echo "<option value=\"5\">"._("Intelligent")."</Option>";
echo <<<EOT
</select></td></tr>
</table><BR>
EOT;

   $query="select * from vuln_nessus_category order by name";
   $result = $dbconn->execute($query);

   echo <<<EOT
   
<div id="cat2n">
EOT;
   echo "<B>"._("Autoenable plugins in categories").":</B><BR><BR>";
   echo <<<EOT
<table summary="Category Listing" border="0" cellspacing="2" cellpadding="0" width="650">
EOT;
echo "<tr><th><b>"._("Category")."</b></th>";
echo "<th><b>"._("Enable All")."</b></th>";
echo "<th><b>"._("Enable New")."</b></th>";
echo "<th><b>"._("Disable New")."</b></th>";
echo "<th><b>"._("Disable All")."</b></th>";
echo "<th><b>"._("Intelligent")."</b></th></tr>";


   while (!$result->EOF) {
      list($cid, $category)=$result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">".strtoupper($category)."</td>";
      echo <<<EOT
<td><input type="radio" name="c_$cid" value="1" checked></td>
<td><input type="radio" name="c_$cid" value="2"></td>
<td><input type="radio" name="c_$cid" value="3"></td>
<td><input type="radio" name="c_$cid" value="4"></td>
<td><input type="radio" name="c_$cid" value="5"></td>
</tr>
EOT;
      $result->MoveNext();
   }
   echo "</table></div>";

   $query="select * from vuln_nessus_family order by name";
   $result=$dbconn->execute($query);

   echo <<<EOT

<div id="fam2n" style="display:none;">
EOT;
    echo "<B>"._("Autoenable plugins in Families").":</B><BR><BR>";
   echo <<<EOT
<table summary="Family Listing" border="0" cellspacing="2" cellpadding="0" width="650">
EOT;
echo "<tr><th><b>"._("Family")."</b></th>";
echo "<th><b>"._("Enable All")."</b></th>";
echo "<th><b>"._("Enable New")."</b></th>";
echo "<th><b>"._("Disable New")."</b></th>";
echo "<th><b>"._("Disable All")."</b></th>";
echo "<th><b>"._("Intelligent")."</b></th></tr>";


   while (!$result->EOF) {
      list ($fid, $family)=$result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">$family</td>";
      echo <<<EOT
<td><input type="radio" name="f_$fid" value="1" checked></td>
<td><input type="radio" name="f_$fid" value="2"></td>
<td><input type="radio" name="f_$fid" value="3"></td>
<td><input type="radio" name="f_$fid" value="4"></td>
<td><input type="radio" name="f_$fid" value="5"></td>
</tr>
EOT;

      $result->MoveNext();
   }
   echo <<<EOT
</table></div>
<br>
EOT;
   echo "<input type=\"submit\" name=\"submit\" class=\"button createprofile\" value=\""._("Update")."\"><br><br>";
   echo <<<EOT
</form></CENTER>
EOT;
echo "</td></tr>";
echo "</table></center>";
}

function delete_profile($sid, $confirm){
   global $enableDelProtect, $username, $dbconn, $nessus_path;


      if ( $enableDelProtect ) {
         # PREVENT ACTUAL DELETION TO USE FOR PREVIOUSLY CREATED SCAN JOBS
         # FLAG AS DELETED ( Brilliant )
         $query = "UPDATE vuln_nessus_settings SET deleted = '1' WHERE id=$sid";
         $result=$dbconn->execute($query);
      } else {
         # ALLOW TO REALLY DELETE RECORD
         
        if (preg_match("/omp\s*$/i", $nessus_path)) {
             $omp = new OMP();
             $omp->delete_config($sid);
        }

         $query = "delete from vuln_nessus_settings where id=$sid";
         $result=$dbconn->execute($query);

         $query="delete from vuln_nessus_settings_preferences where sid=$sid";
         $result=$dbconn->execute($query);
         $query="delete from vuln_nessus_settings_plugins 
              where sid=$sid";
         $result=$dbconn->execute($query);
         $query = "delete from vuln_nessus_settings_family 
                where sid=$sid";
         $result=$dbconn->execute($query);
         $query = "delete from vuln_nessus_settings_category 
                where sid=$sid";
         $result=$dbconn->execute($query);
         
        }
      echo "Profile has been deleted<BR>";
      select_profile();
//logAccess( "User [ $username ] DELETED Profile $sid" );

}

function edit_autoenable($sid) {
   global $dbconn, $username, $version;

   navbar( $sid );

   $query = "select id, name, description, autoenable, type, owner, auto_cat_status, auto_fam_status, update_host_tracker
      FROM vuln_nessus_settings where id=$sid";
   $result=$dbconn->execute($query);

   echo <<<EOT
<form method="post" action="settings.php">
<input type="hidden" name="disp" value="update">
<input type="hidden" name="sid" value="$sid">
EOT;
   list ($sid, $sname, $sdescription, $sautoenable, $stype, $sowner, $auto_cat_status, 
   	$auto_fam_status, $tracker )=$result->fields;
    
   //if($stype=='G') { $stc = "checked"; }  else { $stc = ""; }
   if(is_numeric($sowner) && intval($sowner)!=0) $entity = $sowner;
   else $user = $sowner;
   
   if($tracker=='1') { $cktracker = "checked"; } else { $cktracker = ""; }
   echo <<<EOT
<center>
<table>
<tr>
EOT;
   echo "<th>"._("Name").":</th>";
   echo '
   <td><input type="text" name="sname" value="'.html_entity_decode($sname).'" size=50/>
</tr>
<tr>
';
   echo "<th>"._("Description").":</th>";
   echo '
   <td><input type="text" name="sdescription" value="'.html_entity_decode($sdescription).'" size=50/></td>
</tr>';

$users    = Session::get_users_to_assign($dbconn);
$entities = ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin())  ) ? Session::get_entities_to_assign($dbconn) : null;
?>
	<tr>
        <th><?php echo _("Make this profile available for");?>:</th>
        <td>
			<table cellspacing="0" cellpadding="0" align='center' class="transparent">
				<tr>
					<td class='nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>	
					<td class='nobborder'>				
						<select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >
							
							<?php
														
							$num_users    = 0;
							$current_user = Session::get_session_user();
							
							if ( ! Session::am_i_admin() )
								$user = (  $user == "" && $entity == "" ) ? $current_user : $user;
							
							foreach( $users as $k => $v )
							{
								$login = $v->get_login();
								
								$selected = ( $login == $user ) ? "selected='selected'": "";
								$options .= "<option value='".$login."' $selected>$login</option>\n";
								$num_users++;
							}
							
							if ($num_users == 0)
								echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
							else
							{
								echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
								if ( Session::am_i_admin() )
								{
									$default_selected = ( ( $user == "" || intval($user) == 0 ) && $entity == "" ) ? "selected='selected'" : "";
									echo "<option value='0' $default_selected>"._("ALL")."</option>\n";
								}
															
								echo $options;
							}
													
							?>
						</select>
					</td>
			
					<?php if ( !empty($entities) ) { ?>
					<td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>
									
					<td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
					<td class='nobborder'>	
						<select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
							<option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
							<?php
							foreach ( $entities as $k => $v ) 
							{
								$selected = ( $k == $user_entity ) ? "selected='selected'": "";
								echo "<option value='$k' $selected>$v</option>";
							}
							?>
						</select>
					</td>
						<?php } ?>
				</tr>
			</table>
		</td>
	</tr>

<?php

echo "<tr style='display:none'>";
echo "<th>"._("Link scans run by this profile in Network Hosts")."<br>"._("Purpose so that Network Hosts can be tracking full/perfered audits").".</th>";
echo "<td class='left'><input type='checkbox' name='tracker' $cktracker/><font color='red'>"._("Update Host Tracker \"Network Hosts\" Status")."</font></input></td>";
echo "</tr>";
echo "<tr>
<th valign='top' style='background-position:top center;'>"._("Autoenable options").":</th>
<td><SELECT name=\"sautoenable\" onChange=\"showEnableBy();return false;\">";
//echo "<option value=\"N\"";

//   if ($sautoenable=="N") { echo " selected";}
//   echo ">None";
   echo "<option value=\"C\"";
   if ($sautoenable=="C") { echo " selected";}
   echo ">"._("Autoenable by category")."<option value=\"F\"";
   if ($sautoenable=="F") { echo " selected";}
   echo ">"._("Autoenable by family")."</select>";

   echo "<div id=\"cat1\"".(($sautoenable=="C")? "":"style=\"display:none;\"").">";
   // now the auto-enable status pulldowns
   echo "<br>"._("Initial status for autoenabled Categories").": ";
   echo "<select name='auto_cat_status'>";
   echo "<option value='1'";
   if ($auto_cat_status == 1) { echo " selected";}
   echo ">"._("Enable All")."</option>";
   echo "<option value='2'";
   if ($auto_cat_status == 2) { echo " selected";}
   echo ">"._("Enable New")."</option>";
   echo "<option value='3'";
   if ($auto_cat_status == 3) { echo " selected";}
   echo ">"._("Disable New")."</option>";
   echo "<option value='4'";
   if ($auto_cat_status == 4) { echo " selected";}
   echo ">"._("Disable All")."</option>";
   echo "<option value='5'";
   if ($auto_cat_status == 5) { echo " selected";}
   echo ">"._("Intelligent")."</option><br>";
   echo "</select>";
   echo "<br><br><br></div>";
   
   echo "<div id=\"fam1\"".(($sautoenable=="F")? "":"style=\"display:none;\"").">";
   echo "<br>"._("Initial status for autoenabled Families").": ";
   echo "<select name='auto_fam_status'>";
   echo "<option value='1'";
   if ($auto_fam_status == 1) { echo " selected";}
   echo ">"._("Enable All")."</option>";
   echo "<option value='2'";
   if ($auto_fam_status == 2) { echo " selected";}
   echo ">"._("Enable New")."</option>";
   echo "<option value='3'";
   if ($auto_fam_status == 3) { echo " selected";}
   echo ">"._("Disable New")."</option>";
   echo "<option value='4'";
   if ($auto_fam_status == 4) { echo " selected";}
   echo ">"._("Disable All")."</option>";
   echo "<option value='5'";
   if ($auto_fam_status == 5) { echo " selected";}
   echo ">"._("Intelligent")."</option>";
   echo "</select>";
   echo "</div>";
   
   
   echo "<p></p>"; 
   echo "<div id=\"cat2\"".(($sautoenable=="C")? "":"style=\"display:none;\"").">";
   echo "<B>"._("Autoenable plugins in categories").":</B><BR><BR>";
   $query = "SELECT t1.cid, t2.name, t1.status FROM vuln_nessus_settings_category as t1, 
   vuln_nessus_category as t2 
     where t1.sid=$sid 
   and t1.cid=t2.id 
     order by t2.name";
    // var_dump($query);
   $result = $dbconn->execute($query);
   echo <<<EOT
<table bordercolor="#6797BF" border="0" cellspacing="2" cellpadding="0">
EOT;
echo "<tr><th>"._("Name")."</th>";
echo "<th>"._("Enable All")."</th>";
echo "<th>"._("Enable New")."</th>";
echo "<th>"._("Disable New")."</th>";
echo "<th>"._("Disable All")."</th>";
echo "<th>"._("Intelligent")."</th></tr>";

   while (!$result->EOF) {
      list ($cid, $name, $status) = $result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">".strtoupper($name)."</td>";
echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"1\" ";

      if ($status==1) {echo "checked";}
      echo "></td><td><input type=\"radio\" name=\"c_$cid\" value=\"2\" ";
      if ($status==2) {echo "checked";}
      echo "></td><td><input type=\"radio\" name=\"c_$cid\" value=\"3\" ";
      if ($status==3) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"4\" ";
      if ($status==4) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"5\" ";
      if ($status==5) {echo "checked";}
      echo "></td></tr>";
      $result->MoveNext();
   }
   echo "</table><BR>";
   echo "</div>";
   
   echo "<div id=\"fam2\"".(($sautoenable=="F")? "":"style=\"display:none;\"").">";
   $query = "select t1.fid, t2.name, t1.status 
     from vuln_nessus_settings_family as t1, 
   vuln_nessus_family as t2 
     where t1.sid=$sid 
   and t1.fid=t2.id 
     order by t2.name";
   $result = $dbconn->execute($query);

echo "<B><BR><BR>"._("Autoenable plugins in families").":<BR><BR></B>";
   echo <<<EOT
<table bordercolor="#6797BF" border="0" cellspacing="2" cellpadding="0">
EOT;
echo "<tr><th>"._("Name")."</th>";
echo "<th>"._("Enable All")."</th>";
echo "<th>"._("Enable New")."</th>";
echo "<th>"._("Disable New")."</th>";
echo "<th>"._("Disable All")."</th>";
echo "<th>"._("Intelligent")."</th></tr>";


   while (!$result->EOF) {
      list ($fid, $name, $status) = $result->fields;
      echo "<tr><td style=\"text-align:left;padding-left:3px;\">$name</td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"1\" ";
      if ($status==1) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"2\" ";
      if ($status==2) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"3\" ";
      if ($status==3) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"4\" ";
      if ($status==4) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"5\" ";
      if ($status==5) {echo "checked";}
      echo "></td></tr>";
      $result->MoveNext();
   }
    echo "</table></div></td></tr></table></center><br/>"; 
    echo "<input type=\"submit\" name=\"submit\" value=\""._("Update")."\" class=\"button updateautoenable\"><br/><br/></form>";
}

function edit_plugins($sid) {
   global $fam, $dbconn;

   navbar( $sid );
   //echo "<b>Plugins</b>";

   // get total number of plugins
   $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins 
      where sid=$sid");
   list($pcount)=$result->fields;
   // get count of enabled plugins
   $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins 
      where enabled='Y' and sid=$sid");
   list($penabled)=$result->fields;


echo "<span id=\"loading\"><font color=\"#018C15\"><img width=\"16\" align=\"absmiddle\" src=\"./images/loading.gif\" border=\"0\" alt=\""._("Loading")."\" title=\""._("Loading")."\">&nbsp;"._("Loading, please wait a few of seconds")."...</font></span>&nbsp;";
echo "<b>$pcount</b> "._("Nessus plugins available")." - <b>$penabled</b> - "._("enabled")."<br><br>";
   echo <<<EOT
<center>
<form method="post" action="settings.php" >
<input type="hidden" name="disp" value="saveplugins" >
<input type="hidden" name="sid" value="$sid" >
<input type="hidden" name="fam" value="$fam" >
EOT;

echo "<input type=\"submit\" name=\"AllPlugins\" value=\""._("Enable All")."\" class=\"button updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<input type=\"submit\" name=\"NonDOS\" value=\""._("Enable Non DOS")."\" class=\"button updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<input type=\"submit\" name=\"DisableAll\" value=\""._("Disable All")."\" class=\"button updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<br><br>";
?>
<div id="div_updateplugins" style="display:none">
<img width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?php echo _("Applying changes...")?>" title="<?php echo _("Applying changes...")?>">
&nbsp;<?php echo _("Applying changes, please wait few seconds...") ?>
</div>
<?php
   echo <<<EOT
</form>
</center>
EOT;
   //get all the plugins group by cve
    $cves = array();
    $i = 0;
    $resultcve=$dbconn->GetArray("select id, cve_id from vuln_nessus_plugins");
    $cveTabs = "";
    $cveContent = "";
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
    // get all the plugin families, ordered by family
    $result=$dbconn->GetArray("Select id, name from vuln_nessus_family order by name");
    $numFams = count($result) - 1;
    echo "<br>";
    echo "<center><table width='100%'><tr><td class=\"nobborder\" width=\"400\"><center>";
    echo "<table class=\"noborder\"><tr border='0' class=\"nobborder\">";
	echo "<th>"._("Family")."</th><td class='nobborder'>";

    $famSelect = "<select id='family' onChange=\"showPluginsByFamily(document.getElementById('family').options[document.getElementById('family').selectedIndex].value, $sid);return false;\">";
    //$famSelect = "<select id='family' onChange=\"showDivPlugins(document.getElementById('family').value, 'family', $numFams, 'cve', ".(count($cves) - 1).");return false;\">";
    $i = 0;
   
    //$famQuery = "Select t1.id, t1.name, t1.category, t2.enabled from vuln_nessus_plugins as t1, 
    //  vuln_nessus_settings_plugins as t2 
    //  where t2.family=? and t1.id=t2.id 
    //     and t2.sid=? order by t1.name";
   
    //$famQuery = "select t1.cve_id as cve, t1.id, t1.name, t3.name as pname, t2.enabled from vuln_nessus_plugins as t1
    //  left join vuln_nessus_category as t3 on t3.id=t1.category, vuln_nessus_settings_plugins as t2
    //  where t2.family=? and t1.id=t2.id 
    //  and t2.sid=? order by t1.name";
   
    //$stmt = $dbconn->Prepare($famQuery);
    $famTabs = "<option id=\"select_family\" selected>"._("Select Family")."</option>";
    $famContent = "";
    //ini_set('memory_limit', '256M');
    foreach ($result as $family) {
        //$chk = ($i==0) ? "selected" : "";
        $famTabs .= "<option value=\"".$family['id']."\">" . $family['name'] . "</option>\n";
        //$result1 = $dbconn->GetArray($stmt, array($family['id'], $sid));
        //$famContent .= createHiddenDiv($family['name'], $i, $result1, $family['id'],$sid);
        $i++;
    }
    echo $famSelect . $famTabs . "</select>";
    echo "</td><td class=\"nobborder\"><img id=\"tick1\" style=\"display:none;\" src=\"./images/tick.png\" border=\"0\" alt=\"Filtered by families\" title=\"Filtered by families\"></td></tr>";
    echo "</table></center>";
    echo "</td><td class=\"nobborder\"><center>";
    echo "<table class=\"noborder\"><tr class='nobborder'>";
    echo "<th>"._("CVE Id")."</th>";
    echo "<td class='nobborder'>";
    $cveTabs = "";
    $cveContent = "";
    ksort($cves);
    $j=0;
    $cveTabs .= "<option id=\"select_cve\">"._("Select CVE Id")."</option>";
    foreach ($cves as $key=>$value){
        //$cveQuery = "select t1.cve_id as cve, t1.id, t1.name, t3.name as pname, t2.enabled from vuln_nessus_plugins as t1
        //left join vuln_nessus_category as t3 on t3.id=t1.category, vuln_nessus_settings_plugins as t2 where t1.id=t2.id 
        //and t2.sid=? and t1.cve_id like '%$key%'";

        //$stmt = $dbconn->Prepare($cveQuery);
        $cveTabs .= "<option value='$j'>" . $key . "</option>";
        //$result2 = $dbconn->GetArray($stmt, array($sid));
        //$cveContent .= createHiddenDivCve($key, $j, $result2, $key ,$sid); 
        $j++;
   }
   //$cveSelect = "<select id='cve' onChange=\"showDivPlugins(document.getElementById('cve').value, 'cve', ".(count($cves) - 1).", 'family', ".$numFams.");return false;\">";
   $cveSelect = "<select id='cve' onChange=\"showPluginsByCVE(document.getElementById('cve').options[document.getElementById('cve').selectedIndex].text,$sid);return false;\">";
   echo $cveSelect . $cveTabs . "</select>";
   echo "</td><td class=\"nobborder\"><img id=\"tick2\" style=\"display:none;\" src=\"./images/tick.png\" border=\"0\" alt=\"Filtered by CVE\" title=\"Filtered by CVE\"></td>";
   echo "</tr></table></center>";
   echo "</td></tr></table></center>";
   echo "<br>";
   echo "<div id=\"dplugins\"></div>";
   //echo $famContent;
   //echo $cveContent;

   // end pref=3

}

function import_plugins ( $sid, $importplugins, $preenable, $bEnable ) {
   global $profilename, $dbconn;

   navbar( $sid );
   add_plugins ( $sid, $importplugins, $preenable, $bEnable  );
   
   echo "<p><b>Plugins</b></p>";

   // get total number of plugins
   $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins 
      where sid=$sid");
   list($pcount)=$result->fields;
   // get count of enabled plugins
   $result=$dbconn->Execute("Select count(id) plugincount from vuln_nessus_settings_plugins 
      where enabled='Y' and sid=$sid");
   list($penabled)=$result->fields;

   echo <<<EOT
<p>$pcount Nessus plugins available - $penabled - enabled</p>   
   
   <form method="post" action="settings.php">
   <input type="hidden" name="disp" value="linkplugins">
   <input type="hidden" name="sid" value="$sid">
   <center>
   <table summary="Plugin Select">
   <tr><td><b>SET Plugin Status per</b> [ <font color=black><b>$profilename</b></font> ]</td></tr>
   <tr><td>Preset Plugins Status: 
      <select name="preenable">
         <option value="" SELECTED>No Change</option>
         <option value="E">Enable All</option>
         <option value="D">DISABLE ALL</option>
       </select>
   </td></tr> 
   <tr><td><input type="checkbox" name="bEnable" value="1" checked ><b>Enable the following plugins</b></option></td></tr>  
   <tr><td valign="top"><textarea name="importplugins" style="WIDTH: 400px; HEIGHT: 300px"></textarea></td></tr>
   <tr><td align="right"><input type="submit" value="Import" class="button">
   </td></tr>
   </table>
   </center>
   <br>
</form>
EOT;

}
function edit_serverprefs($sid) {
   global $dbconn;

    navbar( $sid );
	
   // get the profile prefs for use later
     /* $sql = "SELECT t.nessusgroup, t.nessus_id, t.field, 
       t.type, d.value, n.value, t.category
       FROM vuln_nessus_preferences_defaults t
          LEFT JOIN vuln_nessus_preferences d
             ON t.nessus_id = d.nessus_id
          LEFT JOIN vuln_nessus_settings_preferences n
             ON t.nessus_id = n.nessus_id
                and n.sid = $sid
       order by category desc, nessusgroup, nessus_id";*/
	$uuid = Util::get_system_uuid();
	$sql  = "SELECT t.nessusgroup, t.nessus_id, t.field, t.type, t.value AS def_value, AES_DECRYPT(t.value,'$uuid') AS def_value_decrypt, n.value, AES_DECRYPT(n.value,'$uuid') AS value_decrypt, t.category
			FROM vuln_nessus_preferences_defaults t
			LEFT JOIN vuln_nessus_settings_preferences n
			ON t.nessus_id = n.nessus_id and n.sid = $sid
			ORDER BY category desc, nessusgroup, nessus_id";
			
	$result = $dbconn->execute($sql);
   
	if($result === false) 
	{ 
		// SQL error
		echo _("Error").": "._("There was an error with the DB lookup").": ".
		$dbconn->ErrorMsg() . "<br>";
	}
   
	$counter = 0;


    // display the settings form
    $lastvalue = "";

echo "<center><form method=\"post\" action=\"settings.php\">";
echo "<input type=\"hidden\" name=\"disp\" value=\"saveprefs\">";
echo "<input type=\"hidden\" name=\"sid\" value=\"$sid\">";

?>
<div id="div_saveprefs" style="display:none;padding-bottom:8px;">
	<img width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?php echo _("Applying changes...")?>" title="<?php echo _("Applying changes...")?>">
	&nbsp;<?php echo _("Applying changes, please wait few seconds...") ?>
</div>
<?php
print "<table>";
   
  while(!$result->EOF) 
  {
		$counter++;
		
		$nessusgroup = $result->fields['nessusgroup'];
		$nessus_id   = $result->fields['nessus_id'];
		$field       = $result->fields['field'];
		$type        = $result->fields['type'];
		$default     = ( $result->fields['type'] != 'P' || ( $result->fields['type'] == 'P' && empty($result->fields['def_value_decrypt']) ) ) ? $result->fields['def_value']  : $result->fields['def_value_decrypt'];
		$value       = ( $result->fields['type'] != 'P' || ( $result->fields['type'] == 'P' && empty($result->fields['value_decrypt']) ) ) ? $result->fields['value']  : $result->fields['value_decrypt'];
		$category    = $result->fields['category'];
		
		if ($nessusgroup != $lastvalue) 
		{
			print "<tr><th colspan='2'><strong>$nessusgroup</strong></th></tr>";
			$lastvalue = $nessusgroup;
		}
		
		$vname = "form".$counter;
		
		print formprint($nessus_id, $field, $vname, $type, $default, $value, $dbconn);
		
		$result->MoveNext();
   }
   
   echo "</table>";
       
   echo "<br/><input type=\"submit\" name=\"submit\" value=\""._("save")."\" class=\"button saveprefs\"></form></center><br/>";

}

function edit_profile($sid) {
   global $dbconn;

   navbar( $sid );

   $query  = "SELECT name, description from vuln_nessus_settings WHERE id=$sid";
   $result = $dbconn->execute($query);
   list($sname, $sdescription) = $result->fields;
//   echo <<<EOT
//Profile: $sname - $sdescription<br>
//<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
//EOT;

}

function manage_profile_users($sid) {
   global $dbconn;

   navbar( $sid );

   $query       = "SELECT name FROM vuln_nessus_settings WHERE id=$sid";
   $result      = $dbconn->execute($query);
   list($nname) = $result->fields;


     echo <<<EOT
<br>
<form action="settings.php" method="POST" onSubmit="selectAllOptions('authorized');">
<CENTER>
<TABLE width=60% border=0>

<TR>
     <TD colSpan=3>
     <h4> "$nname" - User Access:</h4>
     </TD>
</TR>
<TR>
<TD valign=top align='center'>Authorized Users<br>
      <input type="hidden" name="disp" value="updateusers">
      <input type="hidden" name="sid" value="$sid">
	  <select name="authorized_users[]" id="authorized" style="WIDTH: 187px; HEIGHT: 200px" multiple="multiple" size=20>
EOT;

   //$query = "SELECT t1.username FROM vuln_nessus_settings_users t1
   //   LEFT JOIN vuln_users t2 ON t1.username = t2.pn_uname
   //   WHERE t1.sid=$sid ORDER BY t1.username";
   //$result = $dbconn->execute($query);

   while( list($uname) = $result->fields ) {
      echo "<option value=\"$uname\">$uname</option>\n";
      $result->MoveNext();
   }


     echo <<<EOT

       </select>
	</td>
    <td>
	   <input type='button' value='<< Add' onclick="move2(this.form.unauthorized,this.form.authorized )" class="button"><br/><br>
       <input type='button' value='Remove >>' onclick="move2(this.form.authorized,this.form.unauthorized)" class="button"></td>
       <td valign=top align="left">
          <select name="unauth_users[]" id="unauthorized" style="WIDTH: 187px; HEIGHT: 200px" multiple="multiple" size=20>\n";
EOT;

   //$query = "SELECT t1.pn_uname FROM vuln_users t1
   //   LEFT JOIN vuln_nessus_settings_users t2 ON t1.pn_uname = t2.username
   //   AND t2.sid = '$sid'
   //   WHERE t2.username is Null ORDER BY t1.pn_uname";

   $result = $dbconn->execute($query);

   while( list($nname) = $result->fields ) {
      echo "<option value=\"$nname\" >$nname</option>\n";
      $result->MoveNext();

   }
     echo <<<EOT
</select>
</td></tr>
<tr><td colspan="3"><input type='submit' name='submit' value='Update Access' class='button'></input></td></tr>
</TABLE></CENTER></form>
EOT;

}

function add_plugins ( $sid, $importplugins, $preenable, $bEnable ) {
   global $username, $dbconn;
   if ( $sid && $preenable ) {
   		if ( $preenable == "E" ) {
   			$result=$dbconn->Execute("Update vuln_nessus_settings_plugins SET enabled='Y' WHERE sid=$sid");  
   		} elseif ( $preenable == "D" ) {
   			$result=$dbconn->Execute("Update vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid");
   		}
   }	
   
   if ( $sid && $importplugins ) {
      if ( strlen($importplugins) > 0 ) {	 
        $importplugins  = str_replace("\\r\\n", "<br>", $importplugins  );	
        $importplugins  = str_replace(", ", "<br>", $importplugins  );
        
        $row = explode( "<br>", $importplugins );
        $txtStatus = "N";
        if ( $bEnable ) {
        	$txtStatus = "Y";
        }
        foreach($row as $key) {
            if(is_numeric($key)){
               $results=$dbconn->Execute("Update vuln_nessus_settings_plugins SET enabled='Y' WHERE ID=$key AND sid=$sid");
            }
         }
      }
   }
}

function create_new_profile($sname, $sdescription, $sautoenable, $stype, $cloneid, $auto_cat_status, $auto_fam_status, $tracker ){
   global $dbconn, $nessus_path;
   $username = $stype; // Owner Profile
      if($cloneid <> '') {
         // get the data from the original profile
         $query = "SELECT autoenable, type
                   FROM vuln_nessus_settings
                   WHERE id = $cloneid";
         $result = $dbconn->GetArray($query);
         if($result === false) {
            $errMsg[] = "Error selecting profile data for id = $cloneid: " .
                        $dbconn->ErrorMsg();
            dispSQLError($errMsg,1);
            require_once('footer.php');
            die();
         } else {
            $orig = $result[0];
         }
         // create new entry in the vuln_nessus_settings table first and get
         // the new id
         $insert = "INSERT INTO vuln_nessus_settings
                      (name, description, autoenable, type, owner, update_host_tracker )
                    VALUES
                      ('$sname', '$sdescription', '$orig[autoenable]', 
                       '$orig[type]', '$username', '$tracker' )";
         $result = $dbconn->execute($insert);
         if($result === false) {
            $errMsg[] = "Error creating vuln_nessus_settings record: ".
                        $dbconn->ErrorMsg();
            $error++;
            dispSQLError($errMsg,$error);
            require_once('footer.php');
            die();
         } else {
            $newPID = $dbconn->Insert_ID();
         }
         /* now we need to copy all the data from the other tables with the
          * new sid = newPID
          * vuln_nessus_settings_users -> sid, username
          * vuln_nessus_settings_family
          * vuln_nessus_settings_category
          * vuln_nessus_settings_preferences
          * vuln_nessus_settings_plugins
          */

         //$query="insert into vuln_nessus_settings_users (sid, username) 
         //        values ($newPID, '$username')";
         //$result=$dbconn->execute($query);
         //if($result === false) {
         //   $errMsg[] = "Error creating vuln_nessus_settings_users record: ".
         //               $dbconn->ErrorMsg();
         //  $error++;
         //}
         $query="insert into vuln_nessus_settings_family
                   (select $newPID as sid, fid, status 
                    from vuln_nessus_settings_family
                    where sid=$cloneid)";
         $result=$dbconn->execute($query);
         if($result === false) {
            $errMsg[] = "Error copying vuln_nessus_settings_family records: ".
                        $dbconn->ErrorMsg();
            $error++;
         }
         $query="insert into vuln_nessus_settings_category
                   (select $newPID as sid, cid, status 
                    from vuln_nessus_settings_category
                    where sid=$cloneid)";
         $result=$dbconn->execute($query);
         if($result === false) {
            $errMsg[] = "Error copying vuln_nessus_settings_category records: ".
                        $dbconn->ErrorMsg();
            $error++;
         }
         $query="insert into vuln_nessus_settings_preferences 
                   (select $newPID as sid, id, nessus_id, value, 
                       category, type 
                    from vuln_nessus_settings_preferences
                    where sid=$cloneid)";
         $result=$dbconn->execute($query);
         if($result === false) {
            $errMsg[] = "Error copying vuln_nessus_settings_preferences records: ".
                        $dbconn->ErrorMsg();
            $error++;
         }
         $query = "insert into vuln_nessus_settings_plugins 
                    (select id, $newPID as sid, enabled, category, 
                        family from vuln_nessus_settings_plugins 
                     where sid = $cloneid)";
         $result=$dbconn->execute($query);
         if($result === false) {
            $errMsg[] = "Error copying vuln_nessus_settings_plugins records: ".
                        $dbconn->ErrorMsg();
            $error++;
         }
         $sid = $newPID; // necessary so that success links have the sid set
      } else {
         // create a new profile from scratch
         if( $sname<>"" and ($sautoenable=="N" or $sautoenable=="C" or $sautoenable=="F") ) {
      
            # see if this is duplicate name or not
            $query="SELECT count(name)
                    FROM vuln_nessus_settings
                    WHERE name='$sname'";
      
            $result=$dbconn->execute($query);
            list($count)=$result->fields;
            if ($count>0) {
               echo "Cannot create new profile. Duplicate profile name $sname exists.";
            } else {
               $type = ($stype =="true")? "G": " ";
               $query="INSERT into vuln_nessus_settings (name, description, autoenable, type, owner, auto_cat_status, auto_fam_status)
                       values ('$sname', '$sdescription', '$sautoenable', '$type', '$username', $auto_cat_status, $auto_fam_status)";
               $result=$dbconn->execute($query);
               if($result === false) {
                  $errMsg[] = "Error creating vuln_nessus_settings record: ".
                              $dbconn->ErrorMsg();
                  $error++;
               } else {
                  $sid = $dbconn->Insert_ID();
               }
   
               //$query="insert into vuln_nessus_settings_users ( sid, username ) values ($sid, '$username')";
               //$result=$dbconn->execute($query);
               //if($result === false) {
               //   $errMsg[] = "Error creating vuln_nessus_settings_users record: ".
               //               $dbconn->ErrorMsg();
               //   $error++;
               //}
   
               reset ($_POST);   // if form method="post"
   
               // improve logic here, only add these if this profile
               // is set to autoenable anything, otherwise skip this
               while (list($key, $value) = each ($_POST)) {
                  $value=Util::htmlentities(mysql_real_escape_string(trim($value)), ENT_QUOTES);
                  if (substr($key,0,2)=="f_") {
                     $type=substr($key,0,1);
                     $key=substr($key, 2);
                        $query="insert into vuln_nessus_settings_family values($sid, $key, $value)";
                        $results=$dbconn->Execute($query);
                        if($result === false) {
                           $errMsg[] = "Error creating vuln_nessus_settings_family records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                  } elseif (substr($key,0,2)=="c_") {
                     $type=substr($key,0,1);
                     $key=substr($key, 2);
                        $query="insert into vuln_nessus_settings_category values($sid, $key, $value)";
                        $results=$dbconn->Execute($query);
                        if($result === false) {
                           $errMsg[] = "Error creating vuln_nessus_settings_category records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                  }
               }

// not sure why we do this, there can't be any values in these tables
// with sid=$sid as $sid is a new ID
//               $query="select count(*) 
//                       from vuln_nessus_settings_preferences 
//                       where sid=$sid";
//               $result=$dbconn->execute($query);
//               list($count)=$result->fields;
//   
//               if (!$count>0) {
                  $query="insert into vuln_nessus_settings_preferences 
                          select $sid as sid, id, nessus_id, value, 
                                 category, type 
                          from vuln_nessus_preferences";
                  $result=$dbconn->execute($query);
                  if($result === false) {
                     $errMsg[] = "Error creating vuln_nessus_settings_preferences records: ".
                                 $dbconn->ErrorMsg();
                     $error++;
                  }
//               }
//   
//               $query = "select count(*) 
//                         from vuln_nessus_settings_plugins 
//                         where sid=$sid";
//               $result=$dbconn->execute($query);
//               list($count)=$result->fields;
//    
//               if (!$count>0) {
                  $query = "insert into vuln_nessus_settings_plugins 
                            select id, $sid as sid, enabled, category, 
                                   family from vuln_nessus_plugins 
                            where deleted is null";
                  $result=$dbconn->execute($query);
                  if($result === false) {
                     $errMsg[] = "Error creating vuln_nessus_settings_plugins records: ".
                                 $dbconn->ErrorMsg();
                     $error++;
                  }
//               }
    
               if ($sautoenable=="C") {
                  $query="select t1.cid, t1.status 
                          from vuln_nessus_settings_category as t1, 
                               vuln_nessus_category as t2 
                          where sid=$sid";
                  $result=$dbconn->execute($query);
    
                  while (!$result->EOF) {
                     list($cid, $catstatus)=$result->fields;
                     if ($catstatus==4) {
                        $query1="update vuln_nessus_settings_plugins 
                                 set enabled='N' 
                                 where category=$cid 
                                       and sid=$sid";
                        $result1=$dbconn->execute($query1);
                        if($result1 === false) {
                           $errMsg[] = "Error updating vuln_nessus_settings_plugins records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                     } elseif ($catstatus==1) {
                        $query1="update vuln_nessus_settings_plugins 
                                 set enabled='Y' 
                                 where category=$cid 
                                       and sid=$sid";
                        $result1=$dbconn->execute($query1);
                        if($result1 === false) {
                           $errMsg[] = "Error updating vuln_nessus_settings_plugins records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                     }
                     $result->MoveNext();
                  }
               } elseif($sautoenable=="F") {
                  $query="select t1.fid, t1.status 
                          from vuln_nessus_settings_family as t1, 
                               vuln_nessus_category as t2 
                          where sid=$sid";
                  $result=$dbconn->execute($query);
   
                  while (!$result->EOF) {
                     list($fid, $catstatus)=$result->fields;
                     if ($catstatus==4) {
                        $query1="update vuln_nessus_settings_plugins 
                                 set enabled='N' 
                                 where family=$fid 
                                       and sid=$sid";
                        $result1=$dbconn->execute($query1);
                        if($result1 === false) {
                           $errMsg[] = "Error updating vuln_nessus_settings_plugins records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                     } elseif ($catstatus==1) {
                        $query1="update vuln_nessus_settings_plugins 
                                 set enabled='Y' 
                                 where family=$fid 
                                       and sid=$sid";
                        $result1=$dbconn->execute($query1);
                        if($result1 === false) {
                           $errMsg[] = "Error updating vuln_nessus_settings_plugins records: ".
                                       $dbconn->ErrorMsg();
                           $error++;
                        }
                     }
                     $result->MoveNext();
                  }
               }
            }
         } else {
            echo "Please specify profile name";
         }
      }
      if(!$error) {
        if (preg_match("/omp\s*$/i", $nessus_path)) {
            $omp = new OMP();
            $omp->create_new_config($sid);
        }
   
   //logAccess( "Created Profile $sid - $sname" );

   //         echo <<<EOT
   //Step 1. Done - Profile $sname created!!!<BR>
   //Step 2. <a href="settings.php?item=editusers&amp;sid=$sid">Edit Profile Users</a></br>
   //Step 3. <A href="settings.php?item=edit&amp;sid=$sid&amp;page=1">Edit profile details</A>
   //EOT;
   ?><script type="text/javascript">
        //<![CDATA[
        document.location.href='settings.php?hmenu=Vulnerabilities&smenu=ScanProfiles';
       //]]>
     </script><?
      } else {
   //logAccess( "Created Profile Failed $errMsg[0]" );
         dispSQLError($errMsg, $error);
      }
}

function saveprefs( $sid ) {
  global $username, $uroles, $dbconn, $nessus_path;

   // get the profile prefs for use later
   $sql = "SELECT t.nessusgroup, t.nessus_id, t.field, 
       t.type, t.value, n.value, t.category
       FROM vuln_nessus_preferences_defaults t
          LEFT JOIN vuln_nessus_settings_preferences n
             ON t.nessus_id = n.nessus_id
                and n.sid = $sid
       order by category desc, nessusgroup, nessus_id";
   $result=$dbconn->execute($sql);
   if($result === false) { // SQL error
      echo "Error: There was an error with the DB lookup: ".
      $dbconn->ErrorMsg() . "<br>";
   }
   $counter = 0; 

   // user requested Save, update the DB with the values
   // Check to see if this is the owner doing the change
   $foo = $dbconn->execute("select owner from vuln_nessus_settings where id = $sid");
   list ($myowner)=$foo->fields;
//   if ($myowner <> $username && !$uroles[admin]) {
////logAccess( "$username : " . $_SERVER['SCRIPT_NAME'] . " : Access deined to profile" );
//      echo "Access denied: You do not own this profile and are not an admin - (owner = $myowner).";
//      //require_once('footer.php');
//      die();
//   }

	while(!$result->EOF) 
	{
		$counter++;
		$vname="form".$counter;
		
		if (isset($_POST[$vname])) 
		{
			$$vname=Util::htmlentities(mysql_real_escape_string(
			trim($_POST[$vname])), ENT_QUOTES);
		} 
		elseif (isset($_GET[$vname])) 
		{
			$logh->log("$username : " . $_SERVER['SCRIPT_NAME'] . " : GET instead of POST method used - failed to save", PEAR_LOG_NOTICE);
			echo "Please use the settings.php form to submit your changes.";
			require_once('footer.php');
			die();
		} 
		else 
		{
			$$vname="";
		}
		
		list ($nessusgroup, $nessus_id, $field, $type, $default, $value, $category) = $result->fields;
		
		/*    if (strstr($nessus_id, "[password]")) { // password field
				 if ($$vname!="" && !strstr($$vname,'ENC{')) {  // not encrypted
					$enc = new Crypt_CBC($dbk, $cipher);
					$encrypted_val = $enc->encrypt($$vname);
					$$vname = "ENC{" . base64_encode($encrypted_val) . "}";
				 }
			  }
		*/
		
		
		
		updatedb($nessus_id, $$vname, $dbconn, $type, $category, $sid);
		$result->MoveNext();
	} // end while loop

	
   /*
   * find all records in the vuln_nessus_settings_preferences table that
   * have no matching value in vuln_nessus_preferences_defaults
   * and delete them from vuln_nessus_preferences
   */

    $sql = "select n.nessus_id 
		   from vuln_nessus_settings_preferences n
		   left join vuln_nessus_preferences_defaults t
           on n.nessus_id = t.nessus_id
           where t.nessus_id is null";
   
    $result=$dbconn->execute($sql);

   while(!$result->EOF) 
   {
      list ($pleasedeleteme) = $result->fields;
      $sql2 = "delete from vuln_nessus_settings_preferences
          where nessus_id = \"$pleasedeleteme\"";
      $result2=$dbconn->execute($sql2);
      $result->MoveNext();
   }

//   echo <<<EOT
//Nessus settings saved<BR>
//EOT;
//   logAccess( "Edited Prefs for Profile $sid" );

    if (preg_match("/omp\s*$/i", $nessus_path)) {
        $omp = new OMP();
        $omp->set_preferences($sid);
    }

    edit_serverprefs($sid);
    //edit_profile($sid);
}

function saveplugins($sid, $fam, $cve, $saveplugins, $AllPlugins, $NonDOS, $DisableAll) {
   global $username, $dbconn, $nessus_path;
   //echo "Updating Plugins Status<br>";
    if ($saveplugins=="Update") {
      reset ($_POST);   // if form method="post"
      // edited to work on a per family basis so we can break
      // down the page to lighten up the HTML
        if ($fam!="") {
          $result=$dbconn->Execute("Update vuln_nessus_settings_plugins 
                   set enabled='N' 
                   where sid=$sid and family=$fam");
            while (list($key, $value) = each ($_POST)) {
                $key=Util::htmlentities(mysql_real_escape_string(trim($key)), ENT_QUOTES);
                if (substr($key,0,3)=="PID") {
                    $key=substr($key, 3);
                    if(is_numeric($key)){
                        $results=$dbconn->Execute("Update vuln_nessus_settings_plugins 
                        set enabled='Y' 
                        where ID=$key 
                        and sid=$sid");
                    }
                }
            }
        }
        else{echo "<br><br>";
            $result=$dbconn->Execute("SELECT id FROM vuln_nessus_plugins WHERE cve_id LIKE '%$cve%'");
            while (!$result->EOF) {
                $dbconn->Execute("Update vuln_nessus_settings_plugins 
                        set enabled='N' 
                        where id=".$result->fields['id']." and sid=$sid");
                $result->MoveNext();
            }
            while (list($key, $value) = each ($_POST)) {
                $key=Util::htmlentities(mysql_real_escape_string(trim($key)), ENT_QUOTES);
                if (substr($key,0,3)=="PID") {
                    $key=substr($key, 3);
                    if(is_numeric($key)){
                        $results=$dbconn->Execute("Update vuln_nessus_settings_plugins 
                        set enabled='Y' 
                        where ID=$key 
                        and sid=$sid");
                    }
                }
            }
       }
   }

   if ($AllPlugins=="Enable All") {
      $result=$dbconn->Execute("Update vuln_nessus_settings_plugins 
              set enabled='Y' 
              where sid=$sid");
   }
   if ($NonDOS=="Enable Non DOS") {
   	  $result=$dbconn->Execute("Update vuln_nessus_settings_plugins 
              set enabled='Y' where sid=$sid");
   	  //echo "query=$query<br>";
      $query="SELECT id FROM vuln_nessus_category WHERE name='denial'";
      $result=$dbconn->execute($query);
      list ($cid)=$result->fields;
      $query = "UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND category=$cid";
   	  //echo "query=$query<br>";      
      $result=$dbconn->execute($query);

      $query="SELECT id FROM vuln_nessus_category WHERE name='flood'";
      $result=$dbconn->execute($query);
      list ($cid)=$result->fields;
      $query = "UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND category=$cid";
   	  //echo "query=$query<br>";      
      $result=$dbconn->execute($query);
      
      $query="SELECT id FROM vuln_nessus_category WHERE name='destructive_attack'";
   	  //echo "query=$query<br>";      
      $result=$dbconn->execute($query);
      list ($cid)=$result->fields;
      $query = "UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND category=$cid";
   	  //echo "query=$query<br>";      
      $result=$dbconn->execute($query);

      $query="SELECT id FROM vuln_nessus_category WHERE name='kill_host'";
      $result=$dbconn->execute($query);
      list ($cid)=$result->fields;
      $query = "UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND category=$cid";
   	  //echo "query=$query<br>";      
      $result=$dbconn->execute($query);

   }
   if ($DisableAll=="Disable All") {
      $query="update vuln_nessus_settings_plugins 
              set enabled='N' 
              where sid=$sid";
      $result=$dbconn->execute($query);
   }

   //echo "ALL=$AllPlugins, NON=$NonDOS, DISABLE=$DisableAll";
   
   //echo "<br>";

    if (preg_match("/omp\s*$/i", $nessus_path)) {
        $omp = new OMP();
        $omp->set_plugins_by_family($sid);
    }
   
    logAccess( "Updated Plugins for Profile $sid" );
    edit_plugins($sid, $fam);
}

function select_profile(){
    global $sid, $username, $dbconn, $version, $nessus_path;
   
    $used_sids = array();
   
    if (preg_match("/omp\s*$/i", $nessus_path)) {
        $omp = new OMP();
        $used_sids = $omp->get_used_sids();
    }
   
   $entities_nt = array();
   
   $query = "SELECT ae.id as eid, ae.name as ename, aet.name as etype FROM acl_entities AS ae, acl_entities_types AS aet WHERE ae.type = aet.id";
   
   $result_entities = $dbconn->Execute($query);
   while ( !$result_entities->EOF ) {
       $entities_nt [$result_entities->fields['eid']] = $result_entities->fields['ename']." [".$result_entities->fields['etype']."]";
       $result_entities->MoveNext();
   }

    $query = "";
    $normal_user_pro = false;

    if($username == "admin"){
            $query="SELECT id, name, description, owner, type FROM vuln_nessus_settings 
                    WHERE deleted != '1' ORDER BY name";
        }
    else if(preg_match("/pro|demo/i",$version)){
        if (Acl::am_i_proadmin()) {
            $pro_users = array();
            $entities_list = array();

            //list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
            //$entities_list = array_keys($entities_admin);
            $entities_list = Acl::get_user_entities($current_user); 
        
            $users = Acl::get_my_users($dbconn, Session::get_session_user());
            foreach ($users as $us) {
                $pro_users[] = $us["login"];
            }
            $query = "SELECT id, name, description, owner, type FROM vuln_nessus_settings 
                      WHERE deleted != '1' and (name='Default' or owner in ('0','".implode("', '", array_merge($entities_list,$pro_users))."')) ORDER BY name";
        }
        else {
            $tmp = array();
            $entities = Acl::get_user_entities($username);
            foreach ($entities as $entity) {
                $tmp[] = "'".$entity."'";
            }
            if (count($tmp) > 0) $user_where = "owner in ('0','$username',".implode(", ", $tmp).")";
            else $user_where = "owner in ('0','$username')";
            
            $query = "SELECT id, name, description, owner, type FROM vuln_nessus_settings 
                          WHERE deleted != '1' and (name='Default' or $user_where) ORDER BY name";

            $normal_user_pro = true;
        }       
    } else {
        $query = "SELECT id, name, description, owner, type FROM vuln_nessus_settings 
                          WHERE deleted != '1' and (name='Default' or owner in ('0','$username')) ORDER BY name";
    }
    //var_dump($query); 

    $result=$dbconn->execute($query);

//echo $query;
echo "<CENTER>";
echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"800\"><tr><td class=\"headerpr\" style=\"border:0;\">"._("Vulnerability Scan Profiles")."</td></tr></table>";
echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"800\"><tr><td class=\"noborder\">";
echo "<p>";
echo _("Please select a profile to edit").":";
echo "</p>";
echo "<table align='center'>";
echo "<tr>";
if($username=="admin" || Session::am_i_admin()){
    echo "<th>"._("Available for")."</th>";
}
echo "   <th>"._("Profile")."</th>";
echo "   <th>"._("Description")."</th>";
echo "   <th>"._("Action")."</th>";
echo "</tr>";

   while (!$result->EOF) {
   //<td>$sowner</td>
   //<td>$stype</td>
      list($sid, $sname, $sdescription, $sowner, $stype)=$result->fields;
echo "<tr>";
if($username=="admin" || Session::am_i_admin()){
    if($sowner=="0"){
        echo "<td>"._("All")."</td>";
    }
    elseif(is_numeric($sowner)){
        echo "<td style='padding:0px 2px 0px 2px;'>".$entities_nt[$sowner]."</td>";
    }
    else
        echo "<td>".html_entity_decode($sowner)."</td>";
}
echo "<td>".html_entity_decode($sname)."</td>";
echo "<td>".html_entity_decode($sdescription)."</td>";
echo "<td>";
//var_dump($normal_user_pro);
//var_dump($sowner);
//var_dump($username);
//var_dump($used_sids); 

if($normal_user_pro && $sowner!=$username && $sname!="Default") {  
    echo "&nbsp";
}
elseif($username=="admin" || Session::am_i_admin()){
    if(!in_array($sid, $used_sids)) {
        echo "<a href=\"settings.php?disp=edit&amp;&amp;sid=$sid\"><img src=\"images/pencil.png\"></a>";
        echo "<a href=\"settings.php?disp=edit&amp;op=delete&amp;sid=$sid\" onclick=\"return confirmDelete();\"><img src=\"images/delete.gif\"></a>";
    }
    else {
        echo "<img src=\"images/pencil.png\" title=\""._("This profile is being used by a running job now")."\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\">";
        echo "<img src=\"images/delete.gif\" title=\""._("This profile is being used by a running job now")."\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\">";
    }
}
elseif($sname=="Default") {
    echo "["._("edit by admin")."]";
}
elseif($sname!="Default"){
    if(!in_array($sid, $used_sids)) { 
        echo "<a href=\"settings.php?disp=edit&amp;&amp;sid=$sid\"><img src=\"images/pencil.png\"></a>";
        echo "<a href=\"settings.php?disp=edit&amp;op=delete&amp;sid=$sid\" onclick=\"return confirmDelete();\"><img src=\"images/delete.gif\"></a>";
    }
    else {
        echo "<img title=\""._("This profile is being used by a running job now")."\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"images/pencil.png\">";
        echo "<img title=\""._("This profile is being used by a running job now")."\" style=\"filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;\" src=\"images/delete.gif\">";
    }
}
echo "</td>";
echo "</tr>";

      $result->MoveNext();
   }

echo "</table>";
echo "<center>";
echo "<p>";
echo "<form>";
echo "<input type=button onclick=\"document.location.href='settings.php?disp=new'\" value=\""._("Create New Profile")."\" class=\"button\">&nbsp;&nbsp;&nbsp;&nbsp;";
if($username=="admin" || Session::am_i_admin()){
    echo "<input type=button onclick=\"document.location.href='defaults.php'\" value=\""._("Edit default profile")."\" class=\"button\">";
}
echo "</form>";
echo "</p>";
echo "</center>";
echo "<br><br>";
echo "</td></tr></table></center>";
   // end else

   
}

function update_profile($sid, $sname, $sdescription, $stype, $sautoenable, $auto_cat_status, $auto_fam_status, $tracker ) { 
   global $uroles, $dbconn, $conf;
   $username = $stype; // Owner Profile

   $host_tracker = 0;
   
//      $result = $dbconn->execute("select owner 
//                                  from vuln_nessus_settings 
//                                  where id = $sid");
//      list ($myowner)=$result->fields;
//      if ($myowner <> $username && !$uroles[admin]) {
//         echo "Access denied: You do not own this profile and are not an admin 
//               - (owner = $myowner)\n";
//         //require_once('footer.php');
//         die ();
//      }
      // "G" is global, blank is a private scan profile
      if($stype == TRUE) { $stype = "G"; } else { $stype = ""; }

      if($tracker == "on") { $host_tracker = 1;}
      $query = "update vuln_nessus_settings 
                set name='$sname', description='$sdescription', 
                   type='$stype', autoenable='$sautoenable',
                   auto_cat_status = $auto_cat_status,
                   auto_fam_status = $auto_fam_status,
                   update_host_tracker='$host_tracker',
                   owner = '$username'
                where id=$sid";
      $result=$dbconn->execute($query);

      reset ($_POST);   // if form method="post"

      while (list($key, $value) = each ($_POST)) {
         $value=Util::htmlentities(mysql_real_escape_string(trim($value)), ENT_QUOTES);

         if (substr($key,0,2)=="f_") {
            $type=substr($key,0,1);
            $key=substr($key, 2);
            $query="update vuln_nessus_settings_family 
                    set status=$value 
                    where sid=$sid and fid=$key";
            $results=$dbconn->Execute($query);

         } elseif(substr($key,0,2)=="c_") {
            $type=substr($key,0,1);
            $key=substr($key, 2);
            $query="update vuln_nessus_settings_category set status=$value where sid=$sid and cid=$key";
            $results=$dbconn->Execute($query);
         }
      }

      if ($sautoenable=="C") {
         $query="select t1.cid, t1.status from vuln_nessus_settings_category as t1, vuln_nessus_category as t2 where sid=$sid";
         $result=$dbconn->execute($query);

         while (!$result->EOF) {
            list($cid, $catstatus)=$result->fields;
            if ($catstatus==4) {
               $query1="update vuln_nessus_settings_plugins set enabled='N' where category=$cid and sid=$sid";
               $result1=$dbconn->execute($query1);
            } elseif ($catstatus==1) {
               $query1="update vuln_nessus_settings_plugins set enabled='Y' where category=$cid and sid=$sid";
               $result1=$dbconn->execute($query1);
            }
            $result->MoveNext();
         }
      } elseif($sautoenable=="F") {
         $query="select t1.fid, t1.status from vuln_nessus_settings_family as t1, vuln_nessus_family as t2 where sid=$sid";
         $result=$dbconn->execute($query);

         while (!$result->EOF) {
            list($fid, $catstatus)=$result->fields;
            if ($catstatus==4) {
               $query1="update vuln_nessus_settings_plugins set enabled='N' where family=$fid and sid=$sid";
               $result1=$dbconn->execute($query1);
            } elseif ($catstatus==1) {
               $query1="update vuln_nessus_settings_plugins set enabled='Y' where family=$fid and sid=$sid";
               $result1=$dbconn->execute($query1);
            }
            $result->MoveNext();
         }
      }
   //echo "Profile Updated<BR>";
    ?><script type="text/javascript">
        //<![CDATA[
        document.location.href='settings.php?hmenu=Vulnerabilities&smenu=ScanProfiles';
       //]]>
      </script><?

   //logAccess( "Updated Autoenable Settings for Profile $sid" );

    if (preg_match("/omp\s*$/i", $nessus_path)) {
        $omp = new OMP();
        $omp->set_plugins_by_family($sid);
    }
   
   edit_profile($sid);

}

function update_users($sid, $users ){
   global $username, $dbconn;

   //if ( $sid ) {
   //   $query = "delete from vuln_nessus_settings_users where sid=$sid";
   //   $result = $dbconn->execute($query);
   //}

   if ( $sid && $users ) {

      $query = "SELECT name FROM vuln_nessus_settings WHERE id='$sid'";
      $result = $dbconn->execute($query);
      list($name) = $result->fields;
      echo _("Updated Users Access for Profile").": [$name]<br>";

  // foreach( $users as $uname ) {
       //  if ( $uname ) {
      //      $query = "INSERT INTO vuln_nessus_settings_users (sid, username) VALUES ('$sid', '$uname' );";
     //       $result = $dbconn->execute($query);
            #echo "sql=$query<br>";
            #echo "inserting server=$nserver   zid=$zid<br>";
    //     }
    //  }
   }

   echo "<br>";

   logAccess( "Updated Users Associated to Profile $sid" );


   manage_profile_users($sid);
}

function view_config($sid) {
   global $dbconn;

   navbar( $sid );

   echo "<CENTER><TEXTAREA rows=15 cols=80 ># "._("This file was automagically created")."\n\n";

   if($_SESSION["scanner"]=="nessus") {
       $query = "SELECT t1.id, t1.enabled FROM vuln_nessus_settings_plugins as t1
          LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
          WHERE t2.name ='scanner' and t1.sid=$sid order by id";
    }
    else {
        $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
                LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
                LEFT JOIN vuln_nessus_plugins t3 on t1.id=t3.id
                WHERE t2.name ='scanner' and t1.sid=$sid order by oid";
    }
   $result = $dbconn->execute($query);
   echo "begin(SCANNER_SET)\n";

   while (list ($id, $enabled) = $result->fields ) {
      $enabled1="yes";
      if ($enabled=="N") $enabled1="no";
      echo " $id = $enabled1\n";
      $result->MoveNext();
   }

   echo "end(SCANNER_SET)\n\n";

   $query = "Select nessus_id, value from vuln_nessus_settings_preferences 
      WHERE category='SERVER_PREFS' and sid=$sid";
   $result = $dbconn->execute($query);

   echo "begin(SERVER_PREFS)\n";

   while (list( $nessus_id, $value) = $result->fields) {
      echo " $nessus_id = $value\n";
      $result->MoveNext();
   }

   echo "end(SERVER_PREFS)\n\n";

   $query = "Select nessus_id, value from vuln_nessus_settings_preferences
      WHERE category='PLUGINS_PREFS' and sid=$sid";
   $result = $dbconn->execute($query);

   echo "begin(PLUGINS_PREFS)\n";

   while (list( $nessus_id, $value) = $result->fields ) {
      echo " $nessus_id = $value\n";
      $result->MoveNext();
   }

   echo "end(PLUGINS_PREFS)\n\n";

   if($_SESSION["scanner"]=="nessus") {
   $query = "SELECT t1.id, t1.enabled FROM vuln_nessus_settings_plugins as t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
      WHERE t2.name <>'scanner' and t1.sid=$sid order by id";
   }
   else {
      $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
      LEFT JOIN vuln_nessus_plugins t3 on t1.id=t3.id
      WHERE t2.name <>'scanner' and t1.sid=$sid order by oid";
   }
   $result = $dbconn->execute($query);
   echo "begin(PLUGIN_SET)\n";

   while (list ($id, $enabled) = $result->fields ) {
      $enabled1="yes";
      if ($enabled=="N") $enabled1="no";
      echo " $id = $enabled1\n";
      $result->MoveNext();
   }

   echo "end(PLUGIN_SET)\n\n";
   echo "</TEXTAREA></CENTER>"; 
   
}

function updatedbold($fieldname, $fieldvalue, $dbconn, $type, $sid) {
    if ($type=="C" and $fieldvalue=="") {
        $fieldvalue="no";
    }

    $query= "Update vuln_nessus_settings_preferences 
             set value='$fieldvalue', type='$type' 
             where id='$fieldname' and sid=$sid";
    $result=$dbconn->execute($query);
}

function updatedb($nessus_id, $fieldvalue, $dbconn, $type, $category, $sid) {
    
	if ($type=="C" and $fieldvalue=="") {
        $fieldvalue="no";
    }

    $sql = "SELECT count(*) FROM vuln_nessus_settings_preferences WHERE sid = $sid AND nessus_id = \"$nessus_id\"";
    $result=$dbconn->execute($sql);

    list($existing)=$result->fields;
    
	if ($existing == 0) 
	{
		# Do an insert statement
		$uuid            = Util::get_system_uuid();
		$sql_field_value = ( $type == "P" ) ? "AES_ENCRYPT('$fieldvalue','$uuid')" : "'$fieldvalue'";
        
		$sql = "INSERT vuln_nessus_settings_preferences SET nessus_id = '$nessus_id', value=$sql_field_value, type='$type', category='$category', sid=$sid";
    } 
	else 
	{
		if ($type == "P" && Util::is_fake_pass($fieldvalue) )
			$sql = "UPDATE vuln_nessus_settings_preferences SET type='$type', category='$category'  WHERE nessus_id = '$nessus_id' AND sid = $sid";
		else
		{
			$uuid            = Util::get_system_uuid();
			$sql_field_value = ( $type == "P" ) ? "AES_ENCRYPT('$fieldvalue','$uuid')" : "'$fieldvalue'";
			$sql  = "UPDATE vuln_nessus_settings_preferences SET value=$sql_field_value, type='$type', category='$category' WHERE nessus_id = '$nessus_id' AND sid = $sid";
		}
    }
    
	$result=$dbconn->execute($sql);
}

function fieldlink($fieldname, $dbconn) {

    $fieldquery = "select edgeos, label 
          from vuln_nessus_preferences 
          where id = '$fieldname' limit 1";
    $fieldresult = $dbconn->execute($fieldquery);
    list($edgeos,$label)=$fieldresult->fields;
    if ($edgeos > 0) {
        if ($label == "") {
            $returnvalue = "^$edgeos^";
        }
        else {
            $returnvalue = " <A href=\"http://www.edgeos.com/nessuskb/details.php?option_id=$edgeos\">$label</A> ";
        }
    }
    else {
        /* Weird cases here */
        $returnvalue = "^";
    }
    return $returnvalue;
}

function getKBID($dbconn, $nessus_id) {
    $kbid = null;
    $nessus_id = addslashes(rtrim($nessus_id, " :")); // sanitise string for db query
    $fieldquery = "SELECT nessus_nkb 
          FROM nessus_nkb 
          WHERE nessus_key='" . $nessus_id . "' LIMIT 1";
    $fieldresult = $dbconn->execute($fieldquery);
    list($kbid) = $fieldresult->fields;
    return $kbid;
}

function getKBURL($dbconn, $key, $nessus_id) {
    $kbid = getKBID($dbconn, $nessus_id);
    if( $kbid > 0 )
    {
        $url = "<a href=\"http://www.edgeos.com/nessuskb/details.php?option_id=" . $kbid . "\">" . $key . "</a>";
    }
    else
    {
        $url = $key;
    }
    return $url;
}

function formprint($nessus_id, $field, $vname, $type, $default, $value, $dbconn) {
    # Commenting this out as there is no nessus_nkb table in 0.22
    #$field = getKBURL($dbconn, $field, $nessus_id); 
   # The pseudocode below will load a default value for an undefined field
   # to help make it easier for new fields to be added into the structure
   #
		
    $retstr = "";
    if ( is_null($value) || $value=="") {
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
        $retstr="<tr><td style='text-align:left;width:65%'>$field</td><td><INPUT type=\"checkbox\" name=\"$vname\" value=\"yes\"";
        if ($value=="yes") {
            $retstr.=" checked";
        }
        $retstr.="></td></tr>";
    }
    elseif ($type == "R") {
      # Radio button code here
        $retstr="<tr><td style='text-align:left;width:65%'>$field</td><td>";
        $array = explode(";", $default);
        foreach($array as $myoption) {
            $retstr.="<INPUT type=\"radio\" name=\"$vname\" value=\"".trim($myoption)."\"";
            if ($value == $myoption) $retstr.=" checked";
            $retstr.="> $myoption </option>&nbsp;";
        }
        $retstr.="</td></tr>";
    }
    elseif ($type == "P") {
      # Password code here
        #$retstr="$nessus_id $field <INPUT type=\"password\" name=\"$vname\" value=\"$value\"><BR>";
		
		$value  =  Util::fake_pass($value);
        $retstr = "<tr><td style='text-align:left;width:65%'>$field</td><td><input type=\"password\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    else {
      # Assume it is a text box
        $sufix = (preg_match("/\[file\]/",$nessus_id)) ? "&nbsp;["._("full file path")."]" : "";
        $retstr="<tr><td style='text-align:left;width:65%'>$field $sufix</td><td><INPUT type=\"text\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    $retstr .= "\n";
    return $retstr;
}

function printProfileName($dbconn, $sid) {
   $query="select name, description 
           from vuln_nessus_settings 
           where id=$sid";
   $result=$dbconn->execute($query);
   echo "<p><b>"._("Profile").":</b> ".$result->fields[0] . " - " .
         $result->fields[1] . "</p>";
   echo "<p><a href='settings.php'>"._("Select another profile to edit")."</a></p>";
}

function printNavLinks($navLinks, $section) {
   global $sid;
   $nt = "";
   foreach ($navLinks as $navName=>$navURL) {
      if($navName == $section) { // don't print link
         $nt .= "$navName | ";
      } else {
         $navURL = str_replace("%sid%",$sid,$navURL);
         $nt .= "<a href=\"$navURL\">$navName</a> | ";
      }
   }
   // strip off the last "|"
   $nt = substr($nt,0,-3);
   echo "<p>$nt</p>";
}

function createHiddenDiv($name, $num, $data, $fam, $sid) {
   $style = $text = "";
   if($num == 0)  {
      $style = "style='display: block;'";
   }
   else {
      $style = "style='display: none;'";
   }
   $text = "<center><div id='family" . $num . "' name='$name' $style>\n";
   $text .= "<form method='post' action='settings.php' >";
   $text .= "<input type='hidden' name='disp' value='saveplugins'>";
   $text .= "<input type='hidden' name='sid' value='$sid'>";
   $text .= "<input type='hidden' name='fam' value='$fam'>";

   $text .= "<table width='800'>\n";
   $text .= "<tr>";
   $text .= "<th colspan=5>$name</td>";
   $text .= "</tr>\n";
   $text .= "<tr>";
   $text .= "<th>"._("Enabled")."</th>";
   $text .= "<th>"._("VulnID")."</th>";
   $text .= "<th>"._("Vuln Name")."</th>";
   $text .= "<th>"._("CVE Id")."</th>";
   $text .= "<th>"._("Plugin Category")."</th>";
   $text .= "</tr>\n";
   #$text .= "<span id=\"family" . $num . "\">";
   foreach($data as $element) {
      $text .= "<tr>";
      $checked = "";
      if($element['enabled'] == "Y") { $checked = " checked"; }
      $text .= "<td align='right'><INPUT type=checkbox name='PID" . $element['id'] . "' id='" .
      $element['id'] . "' $checked></input></td>";
      $text .= "<td>" . $element['id'] . "</td>";
      //$text .= "<td><a href='lookup.php?id=" . $element['id'] . "' target='_blank'>" . $element['name'] ."</a></td>";
      $text .= "<td style=\"text-align:left;\"><a href='javascript:;' lid='".$element['id']."' class='scriptinfo'>".$element['name']."</a></td>";
      $text .= "<td style='width:110px' nowrap>";
      if($element['cve']=="") {
        $text .= "-";
      }
      else {
        $listcves = explode(",", $element['cve']);
        foreach($listcves as $c){
            $c = trim($c);
            $text .= "<a href='http://www.cvedetails.com/cve/$c/' target='_blank'>$c</a><br>";
        }  
      }
      $text .= "</td>";
      $text .= "<td>" . strtoupper($element['pname']). "</td>";
      $text .= "</tr>\n";
   }
   #$text .= "</span>";
   $text .= "</table><br>\n";
   $text .= "<input type='button' name='cbAll' value='"._("Check All")."' onclick=\"CheckEm(this, 'family".$num."', true);\" class=\"button\"/>";
   $text .= "&nbsp;&nbsp;";
   $text .= "<input type='button' name='cbAll' value='"._("UnCheck All")."' onclick=\"CheckEm(this, 'family".$num."', false);\" class=\"button\"/>";
   $text .= "&nbsp;&nbsp;";
   $text .= "<input type=\"submit\" name=\"saveplugins\" value=\""._("Update")."\" class=\"button updateplugins\"></form>";
   $text .= "</div></center>\n";
   return $text;
}

function createHiddenDivCve($name, $num, $data, $cve, $sid) {
   $style = $text = "";
 //  if($num == 0)  {
 //     $style = "style='display: block;'";
 // }
 //  else {
      $style = "style='display: none;'";
 //  }
   $text = "<center><div id='cve" . $num . "' name='$name' $style>\n";
   $text .= "<form method='post' action='settings.php' >";
   $text .= "<input type='hidden' name='disp' value='saveplugins'>";
   $text .= "<input type='hidden' name='sid' value='$sid'>";
   $text .= "<input type='hidden' name='cve' value='$cve'>";

   $text .= "<table width='800'>\n";
   $text .= "<tr>";
   $text .= "<th colspan=5>$name</td>";
   $text .= "</tr>\n";
   $text .= "<tr>";
   $text .= "<th>"._("Enabled")."</th>";
   $text .= "<th>"._("VulnID")."</th>";
   $text .= "<th>"._("Vuln Name")."</th>";
   $text .= "<th>"._("CVE Id")."</th>";
   $text .= "<th>"._("Plugin Category")."</th>";
   $text .= "</tr>\n";
   #$text .= "<span id=\"family" . $num . "\">"; 
   foreach($data as $element) {
      $text .= "<tr>";
      $checked = "";
      if($element['enabled'] == "Y") { $checked = " checked"; }
      $text .= "<td align='right'><INPUT type=checkbox name='PID" . $element['id'] . "' id='" .
      $element['id'] . "' $checked></input></td>";
      $text .= "<td>" . $element['id'] . "</td>";
//      $text .= "<td><a href='lookup.php?id=" . $element['id'] . "' target='_blank'>" . $element['name'] ."</a></td>";
      $text .= "<td style=\"text-align:left;\"><a href='javascript:;' lid='".$element['id']."' class='scriptinfo' style='text-decoration:none;'>".$element['name']."</a></td>";
      $text .= "<td>";
      if($element['cve']=="") {
        $text .= "-";
      }
      else {
        $listcves = explode(",", $element['cve']);
        foreach($listcves as $c){
            $c = trim($c);
            $text .= "<a href='http://www.cvedetails.com/cve/$c/' target='_blank'>$c</a><br>";
        }  
      }
      $text .= "</td>";
      $text .= "<td>" . strtoupper($element['pname']). "</td>";
      $text .= "</tr>\n";
   }
   #$text .= "</span>";
   $text .= "</table><br>\n";
   $text .= "<input type='button' name='cbAll' value='"._("Check All")."' onclick=\"CheckEm(this, 'cve".$num."', true);\" class=\"button\"/>";
   $text .= "&nbsp;&nbsp;";
   $text .= "<input type='button' name='cbAll' value='"._("UnCheck All")."' onclick=\"CheckEm(this, 'cve".$num."', false);\" class=\"button\"/>";
   $text .= "&nbsp;&nbsp;";
   $text .= "<input type=\"submit\" name=\"saveplugins\" value=\""._("Update")."\" class=\"button updateplugins\"></form>";
   $text .= "</div></center>\n";
   return $text;
}

switch($disp) {

   case "edit":
   
       $used_sids = array();
   
        if (preg_match("/omp\s*$/i", $nessus_path)) {
            $omp = new OMP();
            $used_sids = $omp->get_used_sids();
        }
        
        if(in_array($sid, $used_sids)) {
            ?>
            <p style="text-align:center"><?php  echo _("This profile is being used by a running job now"); ?></p>
            <?
            select_profile();
        }
        else {
            $profiles_allowed = array(); // profiles alloded for pro admin and normal user
            $query = "";
            if(preg_match("/pro|demo/i",$version)){
                if (Acl::am_i_proadmin()) {
                    $pro_users = array();
                    $entities_list = array();

                    //list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
                    //$entities_list = array_keys($entities_admin);
                    $entities_list = Acl::get_user_entities($current_user);
                
                    $users = Acl::get_my_users($dbconn, Session::get_session_user());
                    foreach ($users as $us) {
                        $pro_users[] = $us["login"];
                    }
                    $query = "SELECT distinct(t1.id)FROM vuln_nessus_settings t1
                              WHERE deleted = '0' and (name='Default' or owner='0' or owner in ('".implode("', '", array_merge($entities_list,$pro_users))."')) ORDER BY t1.name";
                }
            }

            if($query=="")     $query = "SELECT distinct(t1.id)FROM vuln_nessus_settings t1
                                         WHERE deleted = '0' and (name='Default' or owner='0' or owner='$username') ORDER BY t1.name";
                              
            $result=$dbconn->Execute($query);
            
            while (!$result->EOF) {
                $profiles_allowed[] = $result->fields["id"];
                $result->MoveNext();
            }
            if (Session::am_i_admin() || in_array($sid, $profiles_allowed)){
                if ( $op == "delete") {
                    delete_profile($sid, $confirm);
                
                } else {
                     //edit_profile($sid);
                     edit_autoenable($sid);
                  }
            }
            else {
                ?>
                <p style="text-align:center"><?php  echo _("You don't have permission to edit or delete this profile"); ?></p>
                <?
                select_profile();
            }
        }

      break;

   case "editauto":
      edit_autoenable($sid);
      break;

   case "editplugins":
      edit_plugins($sid);
      break;

   case "editprefs":
      edit_serverprefs($sid);
      break;

   //case "editusers":
   //   manage_profile_users($sid);
   //   break;

   case "linkplugins":
      import_plugins ( $sid, $importplugins, $preenable, $bEnable );
      break;

   case "new":
      new_profile();
      break;

   case "create":
      $stype = ""; 
      
      if (intval($user)!=-1)
        $stype = $user;
      elseif (intval($entity)!=-1)
        $stype = $entity;
        
      if($stype=="")
        $stype = Session::get_session_user();
        create_new_profile($sname, $sdescription, $sautoenable, $stype, $cloneid, $auto_cat_status, $auto_fam_status, $tracker );
      break;

   case "saveplugins":
      saveplugins($sid, $fam, $cve, $saveplugins, $AllPlugins, $NonDOS, $DisableAll);
      break;

   case "saveprefs":
      saveprefs($sid);   
      break;

   case "update":
      $stype = "";
   
      if (intval($user)!=-1)
        $stype = $user;
      elseif (intval($entity)!=-1)
        $stype = $entity;
        
      if($stype=="")
        $stype = Session::get_session_user();
      update_profile($sid, $sname, $sdescription, $stype, $sautoenable, $auto_cat_status, $auto_fam_status, $tracker );
      break;

   case "updateusers":
      update_users( $sid, $users );
      break;

   case "viewconfig":
      view_config( $sid );
      break;

   default:
      select_profile();
      break;

}
echo "   </td></tr>";
echo "   </table>";
echo "</td></tr>";
echo "</table>";
$db->close($dbconn);
require_once('footer.php');

?>
