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
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

  <?php
include ("../hmenu.php"); ?>

  <h3> <?php
echo gettext("Hints"); ?> </h3>
  <ul>
  <li> <?php
echo gettext("Threshold: Absolute value above which is being alerted"); ?> .
  <li> <?php
echo gettext("Priority: Resulting impact if threshold is being exceeded"); ?> .
  <li> <?php
echo gettext("Alpha: Intercept adaption parameter"); ?> .
  <li> <?php
echo gettext("Beta: Slope adaption parameter"); ?> .
  <li> <?php
echo gettext("Persistence: How long has this event to last before we alert.") . " (20 " . gettext("mins") . ")"; ?> 
  </ul>


<?php
require_once 'classes/RRD_config.inc';
require_once 'classes/Host.inc';
require_once 'ossim_db.inc';
$order = GET('order');
$profile = REQUEST('profile');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
ossim_valid($profile, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Profile"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "rrd_attrib";
$db = new ossim_db();
$conn = $db->connect();
if ((!empty($profile)) && (POST('insert'))) {
    $rrd_list = RRD_Config::get_list($conn, "WHERE profile = '$profile'");
    if ($rrd_list) {
        foreach($rrd_list as $rrd) {
            $attrib = $rrd->get_rrd_attrib();
            if (POST("$attrib#enable") == "on") $enable = 1;
            else $enable = 0;
            if (POST("$attrib#rrd_attrib")) {
                ossim_valid(POST("$attrib#rrd_attrib") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#rrd_attrib"));
                ossim_valid(POST("$attrib#threshold") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#threshold"));
                ossim_valid(POST("$attrib#priority") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#priority"));
                ossim_valid(POST("$attrib#alpha") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#alpha"));
                ossim_valid(POST("$attrib#beta") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#beta"));
                ossim_valid(POST("$attrib#persistence") , OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("$attrib#persistence"));
                if (ossim_error()) {
                    die(ossim_error());
                }
                RRD_Config::update($conn, $profile, POST("$attrib#rrd_attrib") , POST("$attrib#threshold") , POST("$attrib#priority") , POST("$attrib#alpha") , POST("$attrib#beta") , POST("$attrib#persistence") , $enable);
            }
        }
    }
}
echo "<h2>$profile</h2>";
$rrd_list = RRD_Config::get_list($conn, "WHERE profile = '$profile' ORDER BY $order");
$db->close($conn);
?>

  <table align="center">
    <tr>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("rrd_attrib", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Attribute"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("threshold", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Threshold"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("priority", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Priority"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("alpha", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Alpha"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("beta", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Beta"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("persistence", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Persistence"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("enable", $order); ?>&profile=<?php
echo $profile
?>">
		<?php
echo gettext("Enable"); ?> </a></th>
      <td></td>
    </tr>
      <form method="post" action="<?php
echo $_SERVER["SCRIPT_NAME"] ?>">
        <input type="hidden" name="insert" value="1" />
        <input type="hidden" name="profile" value="<?php
echo $profile ?>"/>
<?php
if ($rrd_list) {
    foreach($rrd_list as $rrd) {
        $rrd_attrib = $rrd->get_rrd_attrib();
        $threshold = $rrd->get_threshold();
        $priority = $rrd->get_priority();
        $alpha = $rrd->get_alpha();
        $beta = $rrd->get_beta();
        $persistence = $rrd->get_persistence();
        $enable = $rrd->get_enable();
?>
    <tr>
        <td bgcolor="#eeeeee"><?php
        echo $rrd->get_rrd_attrib(); ?></td>
        <input type="hidden" name="<?php
        echo $rrd_attrib ?>#rrd_attrib" 
            value="<?php
        echo $rrd_attrib ?>"/>
        <td><input type="text" name="<?php
        echo $rrd_attrib ?>#threshold" 
            size="8" value="<?php
        echo $threshold ?>"/></td>
        <td><input type="text" name="<?php
        echo $rrd_attrib ?>#priority" 
            size="2" value="<?php
        echo $priority ?>"/></td>
        <td><input type="text" name="<?php
        echo $rrd_attrib ?>#alpha" 
            size="8" value="<?php
        echo $alpha ?>"/></td>
        <td><input type="text" name="<?php
        echo $rrd_attrib ?>#beta" 
            size="8" value="<?php
        echo $beta ?>"/></td>
        <td><input type="text" name="<?php
        echo $rrd_attrib ?>#persistence" 
            size="2" value="<?php
        echo $persistence ?>"/></td>
        <td><input type="checkbox" name="<?php
        echo $rrd_attrib ?>#enable" 
            <?php
        if ($enable) echo " CHECKED " ?> />
    </tr>
<?php
    }
}
?>
    <tr>
        <td colspan="7"><input class="btn" type="submit" value="<?php
echo gettext("Modify"); ?>"/></td>
    </tr>
    </form>
  </table>


</body>
</html>

