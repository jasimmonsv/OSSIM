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
Session::logcheck("MenuPolicy", "PolicyHosts");
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

<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<?php
require_once 'classes/Host.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Host_group_scan.inc';
require_once 'ossim_db.inc';
require_once 'classes/Host_group_reference.inc';
require_once 'classes/RRD_config.inc';
require_once 'classes/Security.inc';
require_once 'classes/Sensor.inc';
$name = GET('name');
ossim_valid($name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SQL, 'illegal:' . _("name"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if ($host_group_list = Host_group::get_list($conn, "WHERE name = '$name'")) {
    $host_group = $host_group_list[0];
}
$all = array();
?>

<form method="post" action="modifyhostgroup.php">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Group Name"); ?> </th>
      <input type="hidden" name="name"
             value="<?php
echo $host_group->get_name(); ?>">
      <td class="left">
        <b><?php
echo $host_group->get_name(); ?></b>
      </td>
  </tr>

    <th> <?php
echo gettext("Hosts"); ?> <br/>
        <font size="-2">
          <a href="newhostform.php"> <?php
echo gettext("Insert new host"); ?> ?</a><br/>
          <a href="#" onClick="return selectAll('hosts');"><?php echo _("Select / Unselect all") ?></a>
        </font>
    </th>
    <td class="left">
<?php
/* ===== Hosts ==== */
$i = 1;
if ($host_list = Host::get_list($conn, $where = "", "ORDER by hostname")) {
    foreach($host_list as $host) {
        $all['hosts'][] = "host" . $i;
        $host_name = $host->get_hostname();
        $host_ips = $host->get_ip();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "hhosts"; ?>"
            value="<?php
            echo count($host_list); ?>">
<?php
        }
        $name = "mboxs" . $i;
?>
        <input type="checkbox"
<?php
        if (Host_group_reference::in_host_group_reference($conn, $host_group->get_name() , $host_ips)) {
            echo " CHECKED ";
        }
?>
            id="<?php echo "host" . $i ?>" name="<?php
        echo $name; ?>"
            value="<?php
        echo $host_ips; ?>">
            <?php
        echo $host_name . " (" . $host_ips . ")<br>"; ?>
        </input>
<?php
        $i++;
    }
}
?>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Threshold C"); ?> </th>
    <td class="left">
      <input type="text" name="threshold_c" size="4"
             value="<?php
echo $host_group->get_threshold_c(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Threshold A"); ?> </th>
    <td class="left">
      <input type="text" name="threshold_a" size="4"
             value="<?php
echo $host_group->get_threshold_a(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("RRD Profile"); ?> <br/>
        <font size="-2">
          <a href="../rrd_conf/new_rrd_conf_form.php"> <?php
echo gettext("Insert new profile"); ?> ?</a>
        </font>
    </th>
    <td class="left">
      <select name="rrd_profile">
<?php
foreach(RRD_Config::get_profile_list($conn) as $profile) {
    $host_profile = $host_group->get_rrd_profile();
    if (strcmp($profile, "global")) {
        $option = "<option value=\"$profile\"";
        if (0 == strcmp($host_profile, $profile)) $option.= " SELECTED ";
        $option.= ">$profile</option>\n";
        echo $option;
    }
}
?>
	<option value=""
            <?php
if (!$host_profile) echo " SELECTED " ?>>
	    <?php
echo gettext("None"); ?> </option>

      </select>
    </td>
  </tr>
  <tr>

  <tr>
    <th> <?php
echo gettext("Scan options"); ?> </th>
    <td class="left">
      <input type="checkbox"
      <?php
$name = $host_group->get_name();
if (Host_group_scan::in_host_group_scan($conn, $name, 3001)) {
    echo " CHECKED ";
}
?>
      name="nessus" value="1"> <?=_("Enable nessus scan")?> </input><br/>
      <input type="checkbox"
      <?php
$name = $host_group->get_name();
if (Host_group_scan::in_host_group_scan($conn, $name, 2007)) {
    echo " CHECKED ";
}
?>
      name="nagios" value="1"> <?=_("Enable nagios scan")?> </input>

    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Sensors"); ?> (*)<br/>
        <font size="-2">
          <a href="../sensor/newsensorform.php">
          <?php
echo gettext("Insert new sensor"); ?> ?</a>
        </font>
    </th>
    <td class="left">
<?php
/* ===== sensors ==== */
$i = 1;
if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
    foreach($sensor_list as $sensor) {
        $sensor_name = $sensor->get_name();
        $sensor_ip = $sensor->get_ip();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "nsens"; ?>"
            value="<?php
            echo count($sensor_list); ?>">
<?php
        }
        $name = "sboxs" . $i;
?>
        <input type="checkbox"
<?php
        if (Host_group_sensor_reference::in_host_group_sensor_reference($conn, $host_group->get_name() , $sensor_name)) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $sensor_name; ?>">
            <?php
        echo $sensor_ip . " (" . $sensor_name . ")<br>"; ?>
        </input>
<?php
        $i++;
    }
}
?>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="left">
      <textarea name="descr"
        rows="2" cols="20"><?php
echo $host_group->get_descr(); ?></textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" value="<?=_("OK")?>" class="btn" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>
<?php
$db->close($conn);
?>

<script>

var check_hosts = true; // if true next click on "Select/Unselect" puts all to checked

function selectAll(category)
{
    if (category == 'hosts') {
    <?php
foreach($all['hosts'] as $id) { ?>
        document.getElementById('<?php echo $id
?>').checked = check_hosts;
    <?php
} ?>
        check_hosts = check_hosts == false ? true : false;
    }
    return false;
}

</script>


</body>
</html>

