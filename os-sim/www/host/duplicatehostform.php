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
* - match_os()
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
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
function check_host (ip) {
	$.ajax({
		type: "GET",
		url: "check_host_response.php?ip="+ip,
		data: "",
		success: function(msg){
			if (msg == "1") {
				if (confirm("Do you want to update host '"+ip+"'?"))
					document.duplicateform.submit();
			}
			else document.duplicateform.submit();
		}
	});
}
  </script>
</head>
<body>

<?php
if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<?php
require_once 'classes/Host.inc';
require_once 'classes/Host_scan.inc';
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/RRD_config.inc';
require_once 'classes/Security.inc';
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("ip"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) {
    $host = $host_list[0];
}
/* print SELECTED for html-select when os is matched */
function match_os($pattern, $os) {
    $pattern = "/$pattern/i";
    if (preg_match($pattern, $os)) echo " SELECTED ";
}
?>

<form method="post" action="newhost.php" name="duplicateform">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Hostname"); ?> (*)</th>
    <td class="left">
      <input type="text" name="hostname"
             value="<?php
echo $host->get_hostname(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("IP"); ?> (*)</th>
    <td class="left">
      <input type="text" name="ip" id="ip"
               value="<?php
echo $host->get_ip(); ?>">
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Asset value"); ?> (*)</th>
    <td class="left">
      <select name="asset">
        <option
        <?php
if ($host->get_asset() == 0) echo " SELECTED "; ?>
          value="0">
	  <?php
echo gettext("0"); ?> </option>
        <option
        <?php
if ($host->get_asset() == 1) echo " SELECTED "; ?>
          value="1">
	  <?php
echo gettext("1"); ?> </option>
        <option
        <?php
if ($host->get_asset() == 2) echo " SELECTED "; ?>
          value="2">
	  <?php
echo gettext("2"); ?> </option>
        <option
        <?php
if ($host->get_asset() == 3) echo " SELECTED "; ?>
          value="3">
	  <?php
echo gettext("3"); ?> </option>
        <option
        <?php
if ($host->get_asset() == 4) echo " SELECTED "; ?>
          value="4">
	  <?php
echo gettext("4"); ?> </option>
        <option
        <?php
if ($host->get_asset() == 5) echo " SELECTED "; ?>
          value="5">
	  <?php
echo gettext("5"); ?> </option>
      </select>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Threshold C"); ?> (*)</th>
    <td class="left">
      <input type="text" name="threshold_c" size="4"
             value="<?php
echo $host->get_threshold_c(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Threshold A"); ?> (*)</th>
    <td class="left">
      <input type="text" name="threshold_a" size="4"
             value="<?php
echo $host->get_threshold_a(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("RRD Profile"); ?> (*)<br/>
        <font size="-2">
          <a href="../rrd_conf/new_rrd_conf_form.php">
	  <?php
echo gettext("Insert new profile"); ?> ?</a>
        </font>
    </th>
    <td class="left">
      <select name="rrd_profile">
<?php
foreach(RRD_Config::get_profile_list($conn) as $profile) {
    $host_profile = $host->get_rrd_profile();
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
<!--
  <tr>
    <th>Alert</th>
    <td class="left">
      <select name="alert">
        <option <?php // if ($host->get_alert() == 1) echo " SELECTED ";
 ?>
            value="1">Yes</option>
        <option <?php // if ($host->get_alert() == 0) echo " SELECTED ";
 ?>
            value="0">No</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>Persistence</th>
    <td class="left">
      <input type="text" name="persistence" size="3"
             value="<?php //echo $host->get_persistence();
 ?>">min.
    </td>
  </tr>
-->
  <tr style="display:none;">
    <th> <?php
echo gettext("NAT"); ?> </th>
    <td class="left">
        <input type="text" name="nat"
               value="<?php
echo $host->get_nat(); ?>">
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
        if (Host_sensor_reference::in_host_sensor_reference($conn, $host->get_ip() , $sensor_name)) {
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
echo gettext("Scan options"); ?> </th>
    <td class="left">
        <input type="checkbox"
        <?php
if (Host_scan::in_host_scan($conn, $host->get_ip() , 3001)) {
    echo " CHECKED ";
}
?>
        name="nessus" value="1">
	<?php
echo gettext("Enable nessus scan"); ?> </input><br>
        <input type="checkbox"
        <?php
if (Host_scan::in_host_scan($conn, $host->get_ip() , 2007)) {
    echo " CHECKED ";
}
?>
        name="nagios" value="1">
	<?php
echo gettext("Enable nagios"); ?> </input>

    </td>
    </tr>
  <tr>
    <th> <?php
echo gettext("OS"); ?> </th>
    <td class="left">
      <select name="os">
        <option value="Unknown"> </option>
        <option value="Windows" <?php
match_os("Win", $host->get_os($conn)) ?>><?php echo _("Microsoft Windows"); ?> </option>
        <option value="Linux" <?php
match_os("Linux", $host->get_os($conn)) ?>><?php echo _("Linux"); ?> </option>
        <option value="FreeBSD" <?php
match_os("FreeBSD", $host->get_os($conn)) ?>><?php echo _("FreeBSD"); ?> </option>
        <option value="NetBSD" <?php
match_os("NetBSD", $host->get_os($conn)) ?>><?php echo _("NetBSD"); ?> </option>
        <option value="OpenBSD" <?php
match_os("OpenBSD", $host->get_os($conn)) ?>><?php echo _("OpenBSD"); ?> </option>
        <option value="MacOS" <?php
match_os("MacOS", $host->get_os($conn)) ?>><?php echo _("Apple MacOS"); ?> </option>
        <option value="Solaris" <?php
match_os("Solaris", $host->get_os($conn)) ?>><?php echo _("SUN Solaris"); ?> </option>
        <option value="Cisco" <?php
match_os("Cisco", $host->get_os($conn)) ?>><?php echo _("Cisco IOS"); ?> </option>
        <option value="AIX" <?php
match_os("AIX", $host->get_os($conn)) ?>><?php echo _("IBM AIX"); ?> </option>
        <option value="HP-UX" <?php
match_os("HP-UX", $host->get_os($conn)) ?>><?php echo _("HP-UX"); ?> </option>
        <option value="Tru64" <?php
match_os("Tru64", $host->get_os($conn)) ?>><?php echo _("Compaq Tru64"); ?> </option>
        <option value="IRIX" <?php
match_os("IRIX", $host->get_os($conn)) ?>><?php echo _("SGI IRIX"); ?> </option>
        <option value="BSD/OS" <?php
match_os("BSD\/OS", $host->get_os($conn)) ?>><?php echo _("BSD/OS"); ?> </option>
        <option value="SunOS" <?php
match_os("SunOS", $host->get_os($conn)) ?>><?php echo _("SunOS"); ?> </option>
        <option value="Plan9" <?php
match_os("Plan9", $host->get_os($conn)) ?>><?php echo _("Plan9"); ?> </option> <!-- gdiaz's tribute :) -->
        <option value="IPhone" <?php
match_os("IPhone", $host->get_os($conn)) ?>><?php echo _("IPhone"); ?> </option> 
      </select>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Mac Address"); ?> </th>
    <td class="left">
      <input type="text" name="mac" 
        value="<?php
echo $host->get_mac_address($conn); ?>" />
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Mac Vendor"); ?> </th>
    <td class="left">
      <input type="text" name="mac_vendor" 
        value="<?php
echo $host->get_mac_vendor($conn); ?>" />
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="left">
      <textarea name="descr"
        rows="2" cols="20"><?php
echo $host->get_descr(); ?></textarea>
    </td>
  </tr>
  <tr style="display:none">
    <th> <?php
      echo gettext("Latitude"); ?></th>
    <td class="left">
      <input type="text" name="latitude"
             value="<?php $coordinates = $host->get_coordinates();
        echo $coordinates['lat']; ?>"></td>
  </tr>
  <tr style="display:none">
    <th> <?php
      echo gettext("Longitude"); ?></th>
    <td class="left">
      <input type="text" name="longitude"
             value="<?php $coordinates = $host->get_coordinates();
       echo $coordinates['lon']; ?>"></td>
  </tr>
  <tr>
    <td colspan="2" style="padding: 10px;text-align:center;" class="nobborder">
      <input type="button" value="<?=_("Update")?>" onclick="check_host(document.getElementById('ip').value)" class="button" style="font-size:12px">
      <input type="reset" value="<?=_("Clear form")?>" class="button" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

<p align="center"><i><?php
echo gettext("Values marked with (*) are mandatory"); ?></b></i></p>

</body>
</html>
<?php
$db->close($conn);
?>
