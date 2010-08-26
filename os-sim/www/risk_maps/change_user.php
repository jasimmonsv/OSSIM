<?
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
require_once('classes/Session.inc');
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

if (!Session::am_i_admin() && (preg_match("/pro/i",$version) && !Acl::am_i_proadmin())) {
	echo "<br><br><center>"._("You don't have permission to see this page.")."</center>";
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script>
    function switch_user(select) {
        if(select=='entity' && $('#entity').val()!='none'){
            $('#user').val('none');
        }
        else if (select=='user' && $('#user').val()!='none'){
            $('#entity').val('none');
        }
    }
  </script>
</head>
<body>
<?

$id_map = $_GET["id_map"];
$entity = $_GET["entity"];
$user = $_GET["user"];
$delete_perm = $_GET["delete"];

ossim_valid($id_map, OSS_DIGIT, OSS_ALPHA, OSS_DOT, 'illegal:' . _("ID Map"));
ossim_valid($entity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));
ossim_valid($user, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("User"));
ossim_valid($delete_perm, OSS_SCORE, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("Delete Perm"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

if($entity!="" || $user!="") { // save data to DB
    if($user!="none" && $user != "") $newuser = $user;
    if($entity!="none" && $entity != "") $newuser = $entity;
    $query = "INSERT IGNORE INTO risk_maps (map,perm) VALUES ('$id_map','$newuser')";
    $result=$dbconn->execute($query);
}
if ($delete_perm != "") {
	$query = "DELETE FROM risk_maps WHERE map='$id_map' AND perm='$delete_perm'";
	$result=$dbconn->execute($query);
}

$perms = array();
$query = "SELECT perm FROM risk_maps where map='$id_map'";
$result = $dbconn->Execute($query);
while (!$result->EOF) {
	$perms[$result->fields['perm']]++;
    $result->MoveNext();
}

if (preg_match("/pro/i",$version)) {
	$entities_types_aux = Acl::get_entities_types($dbconn);
	$entities_types = array();
	foreach ($entities_types_aux as $etype) { 
	    $entities_types[$etype['id']] = $etype;
	}
	list($entities_all,$num_entities) = Acl::get_entities($dbconn);
    list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
    $entities_list = array_keys($entities_admin);
}
?>
<center>
<form action="change_user.php" method="get">
<input type="hidden" name="id_map" value="<?php echo $id_map ?>">
<table class="transparent" align="center">
	<?php foreach ($perms as $perm=>$val) { ?>
    <tr>
    	<td class="nobborder"><a href="change_user.php?id_map=<?php echo $id_map ?>&delete=<?php echo $perm ?>"><img src="../pixmaps/cross-circle-frame.png" border="0"></img></a></td>
    	<td class="nobborder"><?php echo (preg_match("/^\d+$/",$perm) && $entities_all[$perm] != "") ? $entities_all[$perm]['name']  : $perm ?></td>
    </tr>
    <?php } ?>
    <?php
if (!preg_match("/pro/i",$version)) {
    $users = Session::get_list($dbconn);
    ?>
    <tr>
    	<td class="nobborder"><?php echo _("User:")?></td>
    	<td class="nobborder">
	    	<select name="user">
	        <option value="none"> - </option>
	    	<? foreach ( $users as $user ) { if ($perms[$user]) continue; ?>
	            <option value="<?php echo $user->get_login() ?>" <?php echo (($user_name==$user->get_login()) ? " selected":"") ?>><?php echo $user->get_login() ?></option>
	        <?php } ?>
	    	</select>
    	</td>
    </tr>
<?
}
else {
    ?>
    <tr>
    	<td class="nobborder"><?php echo _("User:")?></td>
    	<td class="nobborder">
	    	<select name="user" id="user" onchange="switch_user('user');return false;">
	        <option value="none"> - </option>
    <?
      if(Session::am_i_admin()) {
            $users = Session::get_list($dbconn);
            foreach ($users as $user) { if ($perms[$user->get_login()]) continue; ?>
                <option value="<?=$user->get_login()?>" <?=(($user_name==$user->get_login()) ? " selected":"")?>><?=$user->get_login()?></option>
          <?}
      }
      else {
            $users = Acl::get_my_users($dbconn,Session::get_session_user());
            foreach ($users as $user){ if ($perms[$user->get_login()]) continue; ?>
                <option value="<?=$user["login"]?>" <?=(($user_name==$user["login"]) ? " selected":"")?>><?=$user["login"]?></option>
            <?}
      }
    ?>
		</select>
    
    	</td>
    </tr>
    <tr><td class="nobborder">&nbsp;</td><td class="nobborder"><?php echo _("OR")?></td></tr>
    <tr>
    	<td class="nobborder"><?php echo _("Entity:")?></td>
	    <td class="nobborder">
	    <select name="entity" id="entity" onchange="switch_user('entity');return false;">
	        <option value="none"> - </option>
	    <?
	        foreach ( $entities_all as $entity ) if(Session::am_i_admin() || (Acl::am_i_proadmin() && in_array($entity["id"], $entities_list))) {
	        	if ($perms[$entity['id']]) continue;
	            echo "<option value=\"".$entity["id"]."\"".(($user_name==$entity["id"]) ? " selected":"").">".$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]</option>";
	        }
	    ?>
	    </select>
	    </td>
    </tr>
    <?//var_dump($entities_all);
}
?>
	<tr>
		<td class="nobborder"></td>
		<td class="nobborder" style="text-align:left;padding-top:5px;">
			<input type="submit" class="button" value="<?php echo _("Save")?>">
		</td>
	</tr>
</table>
</form>
</center>
<?php
$dbconn->disconnect();
?>
</body>
</html>