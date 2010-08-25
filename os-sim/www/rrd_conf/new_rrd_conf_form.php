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
Session::logcheck("MenuConfiguration", "ConfigurationRRDConfig");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>OSSIM Framework</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
include ("../hmenu.php"); ?>

<h3><?php
echo gettext("Hints"); ?></h3>
<ul>
<li> <?php
echo gettext("Threshold: Absolute value above which is being alerted"); ?>.
<li> <?php
echo gettext("Priority: Resulting impact if threshold is being exceeded"); ?>.
<li> <?php
echo gettext("Alpha: Intercept adaption parameter"); ?>.
<li> <?php
echo gettext("Beta: Slope adaption parameter"); ?>.
<li> <?php
echo gettext("Persistence: How long has this event to last before we alert.") . " (" . gettext("Hours") . ")"; ?>
</ul>

<?php
require_once 'classes/RRD_config.inc';
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
?>

    <form method="post" action="new_rrd_conf.php">

    <table align="center">
      <tr><th><?php
echo gettext("Enter a profile name"); ?></th></tr>
      <tr><td><input type="text" name="profile"></td></tr>
    </table>
    <br/>
    <table align="center">
      <tr>
        <th><?php
echo gettext("Attribute"); ?></th>
        <th><?php
echo gettext("Threshold"); ?></th>
        <th><?php
echo gettext("Priority"); ?></th>
        <th><?php
echo gettext("Alpha"); ?></th>
        <th><?php
echo gettext("Beta"); ?></th>
        <th><?php
echo gettext("Persistence"); ?></th>
        <th><?php
echo gettext("Enable"); ?></th>
      </tr>

<?php
if ($rrd_global_list = RRD_Config::get_list($conn, "WHERE profile = 'Default'")) {
    foreach($rrd_global_list as $global) {
        $attrib = $global->get_rrd_attrib();
        $threshold = $global->get_threshold();
        $priority = $global->get_priority();
        $alpha = $global->get_alpha();
        $beta = $global->get_beta();
        $persistence = $global->get_persistence();
?>
      <tr>
        <th><?php
        echo $attrib ?></th>
        <input type="hidden" name="<?php
        echo $attrib ?>#rrd_attrib"
            value="<?php
        echo $attrib ?>"/>
        <td><input type="text" name="<?php
        echo $attrib ?>#threshold"
            size="8" value="<?php
        echo $threshold ?>"></td>
        <td><input type="text" name="<?php
        echo $attrib ?>#priority"
            size="2" value="<?php
        echo $priority ?>"/></td>
        <td><input type="text" name="<?php
        echo $attrib ?>#alpha"
            size="8" value="<?php
        echo $alpha ?>"/></td>
        <td><input type="text" name="<?php
        echo $attrib ?>#beta"
            size="8" value="<?php
        echo $beta ?>"/></td>
        <td><input type="text" name="<?php
        echo $attrib ?>#persistence"
            size="2" value="<?php
        echo $persistence ?>"/></td>
        <td><input type="checkbox" name="<?php
        echo $attrib ?>#enable" checked/>
        </td>
      </tr>
<?php
    }
}
$db->close($conn);
?>

      <tr>
        <td colspan="7"><input type="submit" value="<?php
echo gettext("Insert"); ?>" class="btn"/></td>
      </tr>
    </table>
    </form>

</body>
</html>

