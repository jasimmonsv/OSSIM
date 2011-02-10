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
require_once ('classes/Session.inc');
require_once 'languages.inc';

Session::logcheck("MenuConfiguration", "ConfigurationUsers");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

	<?php
include ("../hmenu.php"); ?>

<?php
require_once ('ossim_db.inc');
require_once ('classes/Session.inc');
require_once ('ossim_acl.inc');
require_once ('classes/Security.inc');
$db = new ossim_db();
$conn = $db->connect();

require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$complex = ($conf->get_conf("pass_complex", FALSE)) ? $conf->get_conf("pass_complex", FALSE) : "lun";

$order = GET('order');
$change_enabled = GET('change_enabled');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($change_enabled, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("change_enabled"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "login";

if ($change_enabled != "") {
	Session::change_enabled($conn,$change_enabled);
}
?>

  <table align="center">
    <tr>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("login", $order);
?>">
	  <?php
echo gettext("Login"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("name", $order);
?>"> 
	  <?php
echo gettext("Name"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("email", $order);
?>">
	  <?php
echo gettext("Email"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("company", $order);
?>">
      <?php
echo gettext("Company"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("department", $order);
?>">
	  <?php
echo gettext("Department"); ?> </a></th>
      <th> <?php
echo gettext("Actions"); ?> </th>
	 <th> <?php
echo gettext("Language"); ?> </th>
    </tr>

<?php
if (isset($_POST['user_id'])) {
	$user_id = POST('user_id');
	$language = POST('language');
	ossim_valid($user_id,  OSS_USER, 'illegal:' . _("user_id"));
	ossim_valid($language, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _("Language"));
	if (ossim_error()) {
	    die(ossim_error());
	}
	$_SESSION['_user_language'] = $language;
    Session::changelang($conn, $user_id, $language);
    if ($user_id == Session::get_session_user()) { ?><script type="text/javascript">top.topmenu.location = '../top.php?option=7&soption=1';</script><?php }
}
if ($session_list = Session::get_list($conn, "ORDER BY $order")) {
    foreach($session_list as $session) {
        $login = $session->get_login();
        if (!Session::am_i_admin() && $login != Session::get_session_user()) continue;
		$name = $session->get_name();
        $email = $session->get_email();
		$enabled = $session->get_enabled();
        $pass = "...";
        $company = $session->get_company();
        $department = $session->get_department();
        $language = $session->get_language();
        $is_admin = $session->get_is_admin();
        $color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "" ;
?>
    <tr <?=$color?>>
      <td style="padding:2px"><?php if ($is_admin || $login == ACL_DEFAULT_OSSIM_ADMIN) { ?><img src="../pixmaps/user-business.png" align="absmiddle" alt="admin"> <?php } ?><b><?php echo $login; ?></b></td>
      <td><?php
        echo $name; ?>&nbsp;</td>
      <td><?php
        echo $email;
        if ($email) { ?>
            <a href="mailto:<?php echo $email
?>">
                <img border="0" src="../pixmaps/email_icon.gif"></a>
      <?php
        } ?>
      &nbsp;
      </td>
      <td><?php
        echo $company; ?>&nbsp;</td>
      <td><?php
        echo $department; ?>&nbsp;</td>
       <td>
<?php
        if (Session::am_i_admin()) { ?>
<?            if ($login != ACL_DEFAULT_OSSIM_ADMIN) {
?><a href="users.php?change_enabled=<?=$login?>"><img src="../pixmaps/<?=($enabled>0) ? "tick.png" : "cross.png"?>" border="0" alt="<?=($enabled>0) ? _("Click to disable") : _("Click to enable")?>" title="<?=($enabled>0) ? _("Click to disable") : _("Click to enable")?>"></a>&nbsp;
      <a href="duplicateuserform.php?user=<?php
                echo $login ?>"> 
      <img src="../pixmaps/tables/table_duplicate.png" alt="<?=_("Duplicate")?>" title="<?=_("Duplicate")?>" border="0"></a>
	  <a href="modifyuserform.php?user=<?php
                echo $login ?>"> 
      <img src="../pixmaps/tables/table_edit.png" alt="<?=_("Update")?>" title="<?=_("Update")?>" border="0"></a>
      <a href="deleteuser.php?user=<?php
                echo $login ?>"> 
      <img src="../pixmaps/tables/table_row_delete.png" alt="<?=_("Delete")?>" title="<?=_("Delete")?>" border="0"></a>
<?php
            } elseif ($login == ACL_DEFAULT_OSSIM_ADMIN) {
?>
      <a href="modifyuserform.php?user=<?php
                echo $login ?>"> 
      <img src="../pixmaps/tables/table_edit.png" alt="<?=_("Update")?>" title="<?=_("Update")?>" border="0"></a>
	  </td>
<?php		}
        } else {
		?>
		<a href="modifyuserform.php?user=<?php echo $login ?>"><img src="../pixmaps/tables/table_edit.png" alt="<?=_("Update")?>" title="<?=_("Update")?>" border="0"></a>
		<?
		}
        if ($login == $_SESSION['_user'] || Session::am_i_admin()) {
            echo "
     <form name=\"langform_" . $login . "\" action=\"users.php\" method=\"post\">
	<td>";
            $lform = "<select name=\"language\" onChange='document.langform_" . $login . ".submit()'>";
            foreach($languages['type'] as $option_value => $option_text) {
                $lform.= "<option ";
                if ($language == $option_value) $lform.= " SELECTED ";
                $lform.= "value=\"$option_value\">$option_text</option>";
            }
            $lform.= "</select>";
            $lform.= "<input type='hidden' name='user_id' value='" . $login . "'>";
            echo $lform . "</td></form>";
        } else {
            echo "<td>&nbsp; </td>";
        }
?>
    </tr>

<?php
    }
}
if (Session::am_i_admin()) {
?>
    <tr>
      <td colspan="8"><a href="newuserform.php"> <b><?php echo gettext("Insert new user"); ?></b> </a></td>
    </tr>
<? } ?>
    <tr>
      <td colspan="8"><a href="../setup/ossim_acl.php"> <b><?php echo gettext("Reload ACLS"); ?></b> </a></td>
    </tr>
  </table>

<?php
$db->close($conn);
?>

</body>
</html>

