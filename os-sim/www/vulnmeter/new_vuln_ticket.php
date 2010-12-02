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
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';

Session::logcheck("MenuEvents", "EventsVulnerabilities");

require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_vulnerability.inc';
$db = new ossim_db();
$conn = $db->connect();

$ref = !ossim_valid(GET('ref') , OSS_LETTER) ? die("Ref required") : GET('ref');
$title = GET('title');
$priority = GET('priority');
$type = GET('type');
$ip = GET('ip');
$port = GET('port');
$nessus_id = GET('nessus_id');
$risk = GET('risk');

// TODO: Check the validations below, narrow them down a bit
ossim_valid($title, OSS_PUNC_EXT, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("title"));
ossim_valid($priority, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("priority"));
ossim_valid($type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("type"));
ossim_valid($ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("ip"));
ossim_valid($port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("port"));
ossim_valid($nessus_id, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("nessus id"));
ossim_valid($risk, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("risk"));

if (ossim_error()) {
    die(ossim_error());
}

$submitter = Session::get_session_user();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript">
    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }
  </script>
</head>
<body>

<form method="POST" action="../incidents/manageincident.php" target="main">
<input type="hidden" name="from_vuln" value="1" />
<input type="hidden" name="action" value="newincident" />
<input type="hidden" name="ref" value="<?php echo $ref ?>" />
<input type="hidden" name="submitter" size="40" value="<?php echo $submitter ?>" />
<br>
<table align="center">
<?php

$result = $conn->Execute("SELECT name FROM plugin_sid WHERE plugin_id=3001 AND sid=$nessus_id");
if($result->fields["name"]=="")
    $title = _("New Vulnerability ticket");
else
    $title = $result->fields["name"];
?>
  <tr>
    <th><?php echo _("Title") ?></th>
    <td class="left">
      <input type="text" name="title" size="40" value="<?php echo $title ?>" />
    </td>
  </tr>
<?
$users = Session::get_list($conn);

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

if(preg_match("/pro|demo/i",$version)) {
    $users_pro_login = array();
    $users_pro = array();
    $entities_pro = array();
    
    if(Session::am_i_admin()) { // admin in professional version
        list($entities_all,$num_entities) = Acl::get_entities($conn);
        $entities_types_aux = Acl::get_entities_types($conn);
        $entities_types = array();

        foreach ($entities_types_aux as $etype) { 
            $entities_types[$etype['id']] = $etype;
        }
        
        ?>
        <tr>
            <th><?php echo _("Assign To") ?></th>
            <td style="text-align: left">
                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                    <tr>
                        <td class="nobborder"><?php echo _("User:");?></td>
                        <td class="nobborder">
                          <select name="transferred_user" id="user" onchange="switch_user('user');return false;">
                            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                            <?php
                            foreach($users as $u) if(Session::get_session_user()!=$u->get_login()){ ?>
                                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
                            <?php
                            } ?>
                          </select>
                        </td>
                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                        <td class="nobborder"><?php echo _("Entity:");?></td>
                        <td class="nobborder">
                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                            <option value=""><? if (count($entities_all) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                            <?php
                                foreach ( $entities_all as $entity ) {
                                ?>
                                <option value="<?php echo $entity["id"]; ?>"><?php echo $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
        </tr> 
<?}
    elseif(Acl::am_i_proadmin()) { // pro admin
        //users
        $users_admin = Acl::get_my_users($conn,Session::get_session_user()); 
        foreach ($users_admin as $u){
            if($u["login"]!=Session::get_session_user()){
                $users_pro_login[] = $u["login"];
            }
        }
        //if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
        
        //entities
        list($entities_all,$num_entities) = Acl::get_entities($conn);
        list($entities_admin,$num) = Acl::get_entities_admin($conn,Session::get_session_user());
        $entities_list = array_keys($entities_admin);
        
        $entities_types_aux = Acl::get_entities_types($conn);
        $entities_types = array();

        foreach ($entities_types_aux as $etype) { 
            $entities_types[$etype['id']] = $etype;
        }
        
        //save entities for proadmin
        foreach ( $entities_all as $entity ) if(in_array($entity["id"], $entities_list)) {
            $entities_pro[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
        }
        
        // filter users
        foreach($users as $u) {
            if (!in_array($u->get_login(),$users_pro_login)) continue;
            $users_pro[$u->get_login()] = format_user($u, false);
        }
        ?>
        <tr>
            <th><?php echo _("Assign To") ?></th>
            <td style="text-align: left;">
                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                    <tr>
                        <td class="nobborder"><?php echo _("User:");?></td>
                        <td class="nobborder">
                          <select name="transferred_user" id="user" onchange="switch_user('user');return false;">
                            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                            <?php
                            foreach($users_pro as $loginu => $nameu) { ?>
                                <option value="<?php echo $loginu; ?>"><?php echo $nameu; ?></option>
                            <?php
                            } ?>
                          </select>
                        </td>
                        <td style="padding:0px 5px 0px 5px;text-align:center;" class="nobborder"><?php echo _("OR");?></td>
                        <td class="nobborder"><?php echo _("Entity:");?></td>
                        <td class="nobborder">
                            <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
                            <option value=""><? if (count($entities_pro) < 1) { ?>- <?=_("No entities found")?> -<? } ?></option>
                            <?php
                                foreach ( $entities_pro as $entity_id => $entity_name ) {
                                ?>
                                <option value="<?php echo $entity_id; ?>"><?php echo $entity_name;?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
        </tr> 
    <?
    }
    else { // normal user
            $brothers = Acl::get_brothers($conn,Session::get_session_user());
            foreach ($brothers as $brother){
                $users_pro_login[] = $brother["login"];
            }
            //if(!in_array(Session::get_session_user(), $users_pro_login))   $users_pro_login[] = Session::get_session_user();
            // filter users
                foreach($users as $u) {
                    if (!in_array($u->get_login(),$users_pro_login)) continue;
                    $users_pro[$u->get_login()] = format_user($u, false);
                }
            ?>
                <tr>
                    <th><?php echo _("Assign To") ?></th>
                    <td style="text-align: left">
                      <select name="transferred_user">
                        <option value=""><? if (count($users_pro) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
                        <?php
            foreach($users_pro as $loginu => $nameu) { ?>
                    <option value="<?php echo $loginu ?>"><?php echo $nameu ?></option>
            <?php
            } ?>
                      </select>
                    </td>
                </tr>
            <?
    }
}
else {
    ?>
    <tr>
        <th><?php echo _("Assign To") ?></th>
        <td style="text-align: left">
          <select name="transferred_user">
            <option value=""><? if (count($users) < 1) { ?>- <?=_("No users found")?> -<? } ?></option>
            <?php
            foreach($users as $u) if ($u->get_login()!=Session::get_session_user()) { ?>
                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false) ?></option>
            <?php
            } ?>
          </select>
        </td>
    </tr> 
<?}?>
  <tr>
    <th><?php echo _("Priority") ?></th>
    <td class="left">
      <select name="priority">
<?php
$options = "";
for ($i = 1; $i <= 10; $i++) {
    $options.= "<option value=\"$i\"";
    if ($priority == $i) {
        $options.= " selected ";
    }
    $options.= ">$i</option>";
}
print $options;
?>
      </select>
    </td>
  </tr>
  <tr>
    <th><?php echo _("Type") ?></th>
<?php
Incident::print_td_incident_type($conn, $type);
?>
 </tr>
 <tr>
    <th><?php echo _("IP") ?></th>
    <td class="left">
      <input type="text" name="ip" value="<?php echo $ip ?>" />
    </td>
  </tr>
    <tr>
    <th><?php echo _("Port") ?></th>
    <td class="left">
      <input type="text" name="port" size="30" value="<?php echo $port ?>" />
    </td>
  </tr>
     <tr>
    <th><?php echo _("Nessus/OpenVas ID") ?></th>
    <td class="left">
      <input type="text" name="nessus_id" size="30" value="<?php echo $nessus_id ?>" />
    </td>
  </tr>
    <tr>
    <th><?php echo _("Risk") ?></th>
    <td class="left">
      <input type="text" name="risk" size="30" value="<?php echo $risk ?>" />
    </td>
<?php
$result = $conn->Execute("SELECT description FROM vuln_nessus_plugins WHERE id=$nessus_id");
?>
  </tr>
     <tr>
    <th><?php echo _("Description") ?></th>
    <td style="border-width: 0px;" class="nobborder">
        <textarea name="description" rows="10" cols="80" wrap="hard"><?php echo strip_tags($result->fields["description"]) ?></textarea> 
    </td>
  </tr>
<tr>
    <td colspan="2" class="nobborder" style="text-align:center;">
      <input type="submit" value="<?=_("OK")?>" class="button" />
    </td>
  </tr>
</table>
</form>

<?php
$db->close($conn);
?>
</body>
</html>
<?php
function format_user($user, $html = true, $show_email = false) {
    if (is_a($user, 'Session')) {
        $login = $user->get_login();
        $name = $user->get_name();
        $depto = $user->get_department();
        $company = $user->get_company();
        $mail = $user->get_email();
    } elseif (is_array($user)) {
        $login = $user['login'];
        $name = $user['name'];
        $depto = $user['department'];
        $company = $user['company'];
        $mail = $user['email'];
    } else {
        return '';
    }
    $ret = $name;
    if ($depto && $company) $ret.= " / $depto / $company";
    if ($mail && $show_email) $ret = "$ret &lt;$mail&gt;";
    if ($login) $ret = "<label title=\"Login: $login\">$ret</label>";
    if ($mail) {
        $ret = '<a href="mailto:' . $mail . '">' . $ret . '</a>';
    } else {
        $ret = "$ret <font size=small color=red><i>(No email)</i></font>";
    }
    return $html ? $ret : strip_tags($ret);
}
?>
