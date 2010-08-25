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
* - message_ok()
* - email_form()
* - exec_form()
* - submit()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyActions");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script>
    <?
    $defaultcond = "RISK>=1";
    ?>
    function changecond() {
        $('#condition').hide();
        if ($('#only').attr('checked')==false) {
            $('#cond').val("True");
            $('#on_risk').attr('checked', false);
        } else {
            $('#cond').val("<?=$defaultcond?>");
            $('#on_risk').attr('checked', false);
        }
    }
    function activecond() {
        $('#condition').show();
        $('#only').attr('checked',false);
        $('#cond').val("True");
        $('#on_risk').attr('checked', false);
    }
  </script>
</head>
<body>

<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<?php
require_once ('classes/Action.inc');
require_once ('classes/Action_type.inc');
require_once ('ossim_db.inc');

$action_id = REQUEST('id');
$action_type = REQUEST('action_type');
$cond = REQUEST('cond');
$on_risk = (REQUEST('on_risk') == "") ? "0" : "1";
$descr = REQUEST('descr');
$email_from = REQUEST('email_from');
$email_to = REQUEST('email_to');
$email_subject = REQUEST('email_subject');
$email_message = REQUEST('email_message');
$exec_command = REQUEST('exec_command');
ossim_valid($action_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Action id"));
ossim_valid($action_type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Action type"));
ossim_valid($descr, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Description"));
ossim_valid($email_from, OSS_MAIL_ADDR, OSS_NULLABLE, 'illegal:' . _("Email from"));
ossim_valid($email_to, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Email to"));
foreach(split(',', $email_to) as $to_mail) {
    $to_mail = trim($to_mail);
    ossim_valid($to_mail, OSS_MAIL_ADDR, OSS_NULLABLE, 'illegal:' . _("Email to"));
}
ossim_valid($email_subject, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, "\>\<", OSS_NULLABLE, 'illegal:' . _("Email subject"));
ossim_valid($email_message, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, "\>\<", OSS_NULLABLE, OSS_NL, 'illegal:' . _("Email message"));
ossim_valid($exec_command, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, "\"\'", "\>\<", OSS_NULLABLE, 'illegal:' . _("Exec command"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if (REQUEST('insert_action')) {
    if ($action_type == "email") {
        if ((REQUEST('cond')) and (REQUEST('descr')) and (REQUEST('email_from')) and
			(REQUEST('email_to')) and (REQUEST('email_subject')))
		{
            Action::insertEmail($conn, $action_type, $cond, $on_risk, $descr, $email_from, $email_to, $email_subject, $email_message);
            message_ok();
            exit();
        } else {
            require_once ("ossim_error.inc");
            $error = new OssimNotice();
            $error->display("FORM_NOFILL");
        }
    } elseif ($action_type == "exec") {
        if (((REQUEST('cond')) and REQUEST('descr')) and (REQUEST('exec_command'))) {
            Action::insertExec($conn, $action_type, $cond, $on_risk, $descr, $exec_command);
            message_ok();
            exit();
        } else {
            require_once ("ossim_error.inc");
            $error = new OssimNotice();
            $error->display("FORM_NOFILL");
        }
    }
}
function message_ok() {
    echo '<p align="center">';
    echo gettext("Action inserted successfully");
    if ($_SESSION["menu_sopc"]=="Actions") { echo "<script>document.location.href=\"action.php\"</script>\n"; }
    //echo '<br/><a href="action.php">';
    //echo gettext("Back");
    //echo '</a></p>';
	echo '</p>';
}
function email_form() {
?>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
      <th> <?php
    echo gettext("From:"); ?> </th>
      <td><input name="email_from" type="text" size="60" /></td>
    </tr>
    <tr>
      <th> <?php
    echo gettext("To:"); ?> </th>
      <td><input name="email_to" type="text" size="60" /></td>
    </tr>
    <tr>
      <th> <?php
    echo gettext("Subject:"); ?> <h>
      <td><input name="email_subject" type="text" size="60" /></td>
    </tr>
    <tr>
      <th> <?php
    echo gettext("Message:"); ?> </th>
      <td><textarea name="email_message" type="text" rows="10" cols="80" WRAP=HARD></textarea></td>
    </tr>
<?php
}
function exec_form() {
?>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
      <th> <?php
    echo gettext("Command:"); ?> </th>
      <td><input name="exec_command" type="text" size="60" /></td>
    </tr>
<?php
}
function submit() {
?>
    <tr><td colspan="2">
      <input type="submit" name="insert_action" value="<?=_("OK")?>" class="btn" style="font-size:12px"></td>
    </tr>
<?php
}
?>

<table align="center" width="50%">
<tr>
<td colspan="2" style="text-align: left">
<?php
echo gettext("You can use the following keywords within any field which will be get substituted by it's matching value upon action execution") . ".";
?>
<table width="80%" align="center" style="border-width: 0px"><tr>
<td style="text-align: left" valign="top">
<ul>
<li> DATE
<li> PLUGIN_ID
<li> PLUGIN_SID
<li> RISK
<li> PRIORITY
<li> RELIABILITY
<li> SRC_IP_HOSTNAME
<li> DST_IP_HOSTNAME
<li> SRC_IP
<li> DST_IP
<li> SRC_PORT
<li> DST_PORT
<li> PROTOCOL
<li> SENSOR
<li> BACKLOG_ID
</ul>
<td style="text-align: left" valign="top">
<ul>
<li> EVENT_ID
<li> PLUGIN_NAME
<li> SID_NAME
<li> USERNAME
<li> PASSWORD
<li> FILENAME
<li> USERDATA1
<li> USERDATA2
<li> USERDATA3
<li> USERDATA4
<li> USERDATA5
<li> USERDATA6
<li> USERDATA7
<li> USERDATA8
<li> USERDATA9
</ul>
</td></tr></table>
</td></tr>
<form name="new_action" method="POST">
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td>
      <textarea name="descr" rows="4" cols="80" WRAP=HARD><?php
echo $descr ?></textarea>
    </td>
  </tr>
  <tr>
    <th><?=_("Type")?></th>
    <td>
      <select name="action_type"
        onChange="document.forms['new_action'].submit()">
        <option value=""> -- <?php
echo gettext("Select an action type"); ?> -- </option>
<?php
if (is_array($action_type_list = Action_type::get_list($conn))) {
    foreach($action_type_list as $action_type_aux) {
?>
        <option
            value="<?php
        echo $action_type_aux->get_type() ?>"
            <?php
        if ($action_type == $action_type_aux->get_type()) echo " SELECTED ";
?>>
            <?php
        echo $action_type_aux->get_descr(); ?>
        </option>
<?php
    }
}
?>
      </select>
    </td>
  </tr>

  <tr><td colspan="2" align="center">
    <input type="checkbox" id="only" name="only" onclick="changecond()" <?=($cond == $defaultcond) ? "checked" : ""?>> <?=_("Only if this is an alarm")?> <a href="javascript:;" onclick="activecond()" >[<?=_("Define logical condition")?>]</a>
  </td></tr>
  
  <tr id="condition" style="display:none">
	<th><?=_("Condition")?></th>
    <td style="text-align: center" class="noborder">
      <table class="noborder">
        <tr>
          <td class="noborder">
            &nbsp;<?php echo gettext('Python boolean expression') ?>:&nbsp;
          </td>
          <td class="noborder">
            <input type="text" id="cond" name="cond" size="55" value="<?php echo ($cond == '') ? 'True' : $cond ?>">
          </td>
        </tr>
        <tr>
          <td class="noborder">
            &nbsp;<?php echo gettext('Only on risk increase') ?>:&nbsp;
          </td>
          <td class="noborder" style="text-align: left">
            <input type="checkbox" id="on_risk" name="on_risk" <?php if ($on_risk == "1") echo "checked" ?>>
          </td>
        </tr>
      </table>
    </td>  
  </tr>

<?php
/* type of action */
if ($action_type == "email") {
    email_form();
    submit();
} elseif ($action_type == "exec") {
    exec_form();
    submit();
}
?>

  </form>
</table>

<?php
$db->close($conn);
?>

</body>
</html>

