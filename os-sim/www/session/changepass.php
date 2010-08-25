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
Session::logcheck("MenuConfiguration", "ConfigurationUsers");
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

  <h1> <?php
echo gettext("Change password"); ?> </h1>

<?php
require_once ('ossim_db.inc');
require_once ('classes/Session.inc');
require_once ('ossim_acl.inc');
$user = POST('user');
$pass1 = POST('pass1');
$pass2 = POST('pass2');
$oldpass = POST('oldpass');
ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
/* check params */
if (!POST("user") || !POST("pass1") || !POST("pass2")) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("FORM_MISSING_FIELDS");
}
if (($_SESSION["_user"] != ACL_DEFAULT_OSSIM_ADMIN) && (($_SESSION["_user"] != $user) && !POST("oldpass"))) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("FORM_MISSING_FIELDS");
}
/* check for old password if not actual user or admin */
if ((($_SESSION["_user"] != $user) && $_SESSION["_user"] != ACL_DEFAULT_OSSIM_ADMIN) && !is_array($user_list = Session::get_list($conn, "WHERE login = '" . $user . "' and pass = '" . md5($oldpass) . "'"))) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("BAD_OLD_PASSWORD");
}
/* check passwords */
if (0 != strcmp($pass1, $pass2)) {
    require_once ("ossim_error.inc");
    $error = new OssimError();
    $error->display("PASSWORDS_MISMATCH");
}
/* only the user himself or the admin can change passwords */
if ((POST('user') != $_SESSION["_user"]) && ($_SESSION["_user"] != ACL_DEFAULT_OSSIM_ADMIN)) {
    die(ossim_error(_("To change the password for other user is not allowed")));
}
/* check OK, insert into DB */
if (POST('update')) {
    Session::changepass($conn, $user, $pass1);
?>
    <p> <?php
    echo gettext("User succesfully updated"); ?> </p>
<?php
    $location = "users.php";
    sleep(2);
    echo "<script>
///history.go(-1);
window.location='$location';
</script>
";
?>

<?php
}
$db->close($conn);
?>

</body>
</html>

