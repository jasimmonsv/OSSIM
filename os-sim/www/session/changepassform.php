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
require_once 'classes/Security.inc';
$user = GET('user');
ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
if (ossim_error()) {
    die(ossim_error());
}
?>

<form method="post" action="changepass.php">
<table align="center">
  <input type="hidden" name="update" value="update" />
  <input type="hidden" name="user" value="<?php
echo $user ?>" />
  <tr>
    <th> <?php
echo gettext("User name"); ?> </th>
    <td><?php
echo $user; ?></td>
  </tr>
<?php
if (Session::get_session_user() != $user && (!Session::am_i_admin())) {
?>
  <tr>
    <td> <?php
    echo gettext("Current password"); ?> </td>
    <td class="left"><input type="password" name="oldpass" /></td>
  </tr>
<?php
}
?>
  <tr>
    <td> <?php
echo gettext("Enter new password"); ?> </td>
    <td class="left"><input type="password" name="pass1" /></td>
  </tr>
  <tr>
    <td> <?php
echo gettext("Retype new password"); ?> </td>
    <td class="left"><input type="password" name="pass2" /></td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" class="btn" value="OK">
      <input type="reset" class="btn" value="<?php
echo gettext("reset"); ?>">
    </td>
  </tr>
</table>
</form>

</body>
</html>

