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

$id = $_GET["id"];
$entity = $_GET["entity"];
$user = $_GET["user"];

ossim_valid($id, OSS_DIGIT, 'illegal:' . _("Job id"));
ossim_valid($entity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));
ossim_valid($user, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("User"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

if($id!="" && (($entity!="" && $entity!="none") || ($user!="" && $user!="none"))) {
    $ips = array();
    if($entity!="" && $entity!="none") $newuser = $entity;
    if($user!="" && $user!="none") $newuser = $user;
    
    $query = "select username, meth_VSET from vuln_jobs
                where report_id=$id";
    
    $result = $dbconn->execute($query);
    $olduser = $result->fields["username"];
    $sid = $result->fields["meth_VSET"];

    $query = "select distinct inet_aton(s.hostIP) as ip from
                vuln_jobs j,vuln_nessus_reports r, vuln_nessus_results s
                where j.report_id=r.report_id AND r.report_id=s.report_id and j.report_id=$id";
                
    $result = $dbconn->Execute($query);
    while (!$result->EOF) {
        $ips[] = $result->fields["ip"];
        $result->MoveNext(); 
    }
    
    // update to new user
    
    // check if exist duplicate
    foreach ($ips as $ip) {
        $query = "SELECT scantime FROM vuln_nessus_latest_reports WHERE report_id=$ip and username='$newuser' and sid='$sid'";
        $result=$dbconn->execute($query);
        $scantime_2 = $result->fields["scantime"];
              
        if($scantime_2=="") { // don't exist then update without duplicate key problem
            $query = "UPDATE vuln_nessus_latest_reports SET username='$newuser' WHERE report_id=$ip 
                      and username='$olduser' and sid='$sid'";
            $result=$dbconn->execute($query);
            
            $query = "UPDATE vuln_nessus_latest_results SET username='$newuser' WHERE report_id=$ip 
                      and username='$olduser' and sid='$sid'";
            $result=$dbconn->execute($query);
        }
        else { // duplicate exists, action depends scantime compartion using more recent
                $query = "SELECT scantime FROM vuln_nessus_latest_reports WHERE report_id=$ip and username='$olduser' and sid='$sid'";
                $result=$dbconn->execute($query);
                $scantime_1 = $result->fields["scantime"];
                
                if(intval($scantime_2)>intval($scantime_1)) {
                    $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_id=$ip and username='$olduser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                    $query = "DELETE FROM vuln_nessus_latest_results WHERE report_id=$ip and username='$olduser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                }
                else {
                    $query = "DELETE FROM vuln_nessus_latest_reports WHERE report_id=$ip and username='$newuser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                    $query = "DELETE FROM vuln_nessus_latest_results WHERE report_id=$ip and username='$newuser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                    
                    $query = "UPDATE vuln_nessus_latest_reports SET username='$newuser' WHERE report_id=$ip and username='$olduser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                    $query = "UPDATE vuln_nessus_latest_results SET username='$newuser' WHERE report_id=$ip and username='$olduser' and sid='$sid'";
                    $result=$dbconn->execute($query);
                }
        
        }
    }
    
    $query = "UPDATE vuln_jobs SET username='$newuser' WHERE report_id=$id";
    $result=$dbconn->execute($query);
    
    $query = "UPDATE vuln_nessus_reports SET username='$newuser' WHERE report_id=$id";
    $result=$dbconn->execute($query);
    
    ?>
    <script type="text/javascript">
        parent.GB_onclose();
    </script><?
}

if($entity=="" && $user=="") {
    $query = "SELECT username FROM vuln_jobs where report_id=$id";
    $result = $dbconn->Execute($query);
    $user_name = $result->fields['username'];
}

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
echo "<center>";
echo "<form action=\"change_user.php\" method=\"get\">";
echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
if(!preg_match("/pro/i",$version)){
    $users = Session::get_list($dbconn);
    echo "<table class=\"transparent\"><tr><td class=\"nobborder\">";
    echo _("User:")."</td>";
    echo "<td class=\"nobborder\">";
    ?>
    <select name="user">
        <option value="none"><?=_("Not assign")?></option>
    <?
        foreach ( $users as $user ) {
            echo "<option value=\"".$user->get_login()."\"".(($user_name==$user->get_login()) ? " selected":"").">".$user->get_login()."</option>";
        }
    ?>
    </select>
    <?
    echo "</td></tr></table>";
}
else {
    list($entities_all,$num_entities) = Acl::get_entities($dbconn);
    list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
    $entities_list = array_keys($entities_admin);
    
    echo "<table class=\"transparent\"><tr><td class=\"nobborder\">";
    echo _("User:")."</td>";
    echo "<td class=\"nobborder\">";
    ?>
    
    <select name="user" id="user" onchange="switch_user('user');return false;">
        <option value="none"><?=_("Not assign")?></option>
    <?
      if(Session::am_i_admin()) {
            $users = Session::get_list($dbconn);
            foreach ($users as $user) {?>
                <option value="<?=$user->get_login()?>" <?=(($user_name==$user->get_login()) ? " selected":"")?>><?=$user->get_login()?></option>
          <?}
      }
      else {
            $users = Acl::get_my_users($dbconn,Session::get_session_user());
            foreach ($users as $user){?>
                <option value="<?=$user["login"]?>" <?=(($user_name==$user["login"]) ? " selected":"")?>><?=$user["login"]?></option>
            <?}
      }
    ?>
    </select>
    <?
    echo "</td></tr>";
    echo "<tr><td class=\"nobborder\">&nbsp;</td><td class=\"nobborder\">"._("OR")."</td></tr>";
    echo "<tr><td class=\"nobborder\">"._("Entity:")."</td>";
    echo "<td class=\"nobborder\">";
    $entities_types_aux = Acl::get_entities_types($dbconn);
    $entities_types = array();

    foreach ($entities_types_aux as $etype) { 
        $entities_types[$etype['id']] = $etype;
    }
    ?>
    
    <select name="entity" id="entity" onchange="switch_user('entity');return false;">
        <option value="none"><?=_("Not assign")?></option>
    <?
        foreach ( $entities_all as $entity ) if(Session::am_i_admin() || (Acl::am_i_proadmin() && in_array($entity["id"], $entities_list))) {
                echo "<option value=\"".$entity["id"]."\"".(($user_name==$entity["id"]) ? " selected":"").">".$entity["name"]." [".$entities_types[$entity["type"]]["name"]."]</option>";
        }
    ?>
    </select>
    <?//var_dump($entities_all);
    echo "</td></tr></table>";
}

echo "<table class=\"transparent\" width=\"100%\" align=\"center\">";
echo "<tr><td class=\"nobborder\" style=\"text-align:center;padding-top:5px;\">";
echo "<input type=\"submit\" class=\"button\" value=\""._("Save")."\">";
echo "</td></tr>";
echo "</table>";
echo "</form>";
echo "</center>";

$dbconn->disconnect();
?>
</body>
</html>