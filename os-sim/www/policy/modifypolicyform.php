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
Session::logcheck("MenuIntelligence", "PolicyPolicy");
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
include ("../hmenu.php"); ?>

<?php
require_once 'classes/Policy.inc';
require_once 'classes/Plugingroup.inc';
require_once 'classes/Host.inc';
require_once 'classes/Net.inc';
require_once 'classes/Port_group.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Server.inc';
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
$id = GET('id');
ossim_valid($id, OSS_ALPHA, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Policy id"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
settype($id, "int");
if ($policy_list = Policy::get_list($conn, "WHERE id = $id")) {
    $policy = $policy_list[0];
}
?>

</p>

<form method="post" action="modifypolicy.php?id=<?php echo $id
?>">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Source"); ?> <br/>
        <font size="-2">
          <a href="../net/newnetform.php"> <?php
echo gettext("Insert new net"); ?> ?</a>
        </font><br/>
        <font size="-2">
          <a href="../host/newhostform.php"> <?php
echo gettext("Insert new host"); ?> ?</a>
        </font><br/>
    </th>
    <td class="left">
<?php
/* ===== source nets =====*/
$j = 1;
if ($net_list = Net::get_list($conn, "", "ORDER BY name")) {
    foreach($net_list as $net) {
        $net_name = $net->get_name();
        if ($j == 1) {
?>
        <input type="hidden" name="<?php
            echo "sourcengrps"; ?>"
            value="<?php
            echo count($net_list); ?>">
<?php
        }
        $name = "sourcemboxg" . $j;
?>
        <input type="checkbox" 
<?php
        if (Policy_net_reference::in_policy_net_reference($conn, $id, $net_name, 'source')) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $net_name; ?>">
            <?php
        echo $net_name . "<br>"; ?>
        </input>
<?php
        $j++;
    }
}
?>

<hr noshade>

<?php
/* ===== source hosts ===== */
$i = 1;
if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) {
    foreach($host_list as $host) {
        $ip = $host->get_ip();
        $hostname = $host->get_hostname();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "sourcenips"; ?>"
            value="<?php
            echo count($host_list) + 1; ?>">
<?php
        }
        $name = "sourcemboxi" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_host_reference::in_policy_host_reference($conn, $id, $ip, "source")) {
            echo " CHECKED ";
        }
?>
               name="<?php
        echo $name; ?>"
               value="<?php
        echo $ip ?>">
            <?php
        echo $ip . ' (' . $hostname . ")<br>"; ?>
        </input>
<?php
        $i++;
    }
}
$name = "sourcemboxi" . $i;
?>
    <input type="checkbox" 
<?php
if (Policy_host_reference::in_policy_host_reference($conn, $id, 'any', 'source')) {
    echo " CHECKED ";
}
?>
           name="<?php
echo $name; ?>"
           value="any"><b> <?php
echo gettext("ANY"); ?> </b><br></input>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Dest"); ?> <br/>
        <font size="-2">
          <a href="../net/newnetform.php"> <?php
echo gettext("Insert new net"); ?> ?</a>
        </font><br/>
        <font size="-2">
          <a href="../host/newhostform.php"> <?php
echo gettext("Insert new host"); ?> ?</a>
        </font><br/>
    </th>
    <td class="left">
<?php
/* ===== dest nets =====*/
$j = 1;
if ($net_list = Net::get_list($conn, "", "ORDER BY name")) {
    foreach($net_list as $net) {
        $net_name = $net->get_name();
        if ($j == 1) {
?>
        <input type="hidden" name="<?php
            echo "destngrps"; ?>"
            value="<?php
            echo count($net_list); ?>">
<?php
        }
        $name = "destmboxg" . $j;
?>
        <input type="checkbox" 
<?php
        if (Policy_net_reference::in_policy_net_reference($conn, $id, $net_name, 'dest')) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $net_name; ?>">
            <?php
        echo $net_name . "<br>"; ?>
        </input>
<?php
        $j++;
    }
}
?>

<hr noshade>

<?php
/* ===== dest hosts ===== */
$i = 1;
if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) {
    foreach($host_list as $host) {
        $ip = $host->get_ip();
        $hostname = $host->get_hostname();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "destnips"; ?>"
            value="<?php
            echo count($host_list) + 1; ?>">
<?php
        }
        $name = "destmboxi" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_host_reference::in_policy_host_reference($conn, $id, $ip, "dest")) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $ip ?>">
            <?php
        echo $ip . ' (' . $hostname . ")<br>"; ?>
        </input>
<?php
        $i++;
    }
}
$name = "destmboxi" . $i;
?>
    <input type="checkbox" 
<?php
if (Policy_host_reference::in_policy_host_reference($conn, $id, 'any', 'dest')) {
    echo " CHECKED ";
}
?>
           name="<?php
echo $name; ?>"
           value="any"><b> <?php
echo gettext("ANY"); ?> </b><br></input>


    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Ports"); ?> <br/>
        <font size="-2">
          <a href="../port/newportform.php"> <?php
echo gettext("Insert new port group"); ?> ?</a>
        </font><br/>
    </th>
    <td class="left">
<?php
/* ===== ports ==== */
$i = 1;
if ($port_group_list = Port_group::get_list($conn, "ORDER BY name")) {
    foreach($port_group_list as $port_group) {
        $port_group_name = $port_group->get_name();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "nprts"; ?>"
            value="<?php
            echo count($port_group_list); ?>">
<?php
        }
        $name = "mboxp" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_port_reference::in_policy_port_reference($conn, $id, $port_group_name)) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $port_group_name; ?>">
            <?php
        echo $port_group_name . "<br>"; ?>
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
echo gettext("Priority"); ?> </th>
    <td class="left">
      <select name="priority">
        <option
        <?php
if ($policy->get_priority() == - 1) echo " SELECTED "; ?>
            value="-1"><?php echo _("Do not change"); ?></option>
        <option
        <?php
if ($policy->get_priority() == 0) echo " SELECTED "; ?>
            value="0">0</option>
        <option
        <?php
if ($policy->get_priority() == 1) echo " SELECTED "; ?>
            value="1">1</option>
        <option
        <?php
if ($policy->get_priority() == 2) echo " SELECTED "; ?>
            value="2">2</option>
        <option
        <?php
if ($policy->get_priority() == 3) echo " SELECTED "; ?>
            value="3">3</option>
        <option
        <?php
if ($policy->get_priority() == 4) echo " SELECTED "; ?>
            value="4">4</option>
        <option
        <?php
if ($policy->get_priority() == 5) echo " SELECTED "; ?>
            value="5">5</option>
      </select>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Plugin Groups"); ?> <br/>
        <font size="-2">
          <a href="../policy/modifyplugingroups.php">
	  <?php
echo gettext("Insert new plugin group"); ?>
	  ?</a>
        </font><br/>
    </th>
    <td class="left">
<?php
/* ===== plugin groups ==== */
$plug_groups = Plugingroup::get_list($conn);
$my_groups = $policy->get_plugingroups($conn, $id);
foreach($plug_groups as $group) {
    $group_id = $group->get_id();
    $group_name = $group->get_name();
    $checked = '';
    // Manual intersection between all plugin groups and the policy suscribed groups
    foreach($my_groups as $my_group) {
        if ($group_id == $my_group['id']) {
            $checked = 'checked';
        }
    }
?>
    <input type="checkbox" name="plugins[<?php echo $group_id
?>]" <?php echo $checked ?>> <?php echo $group_name ?><br/>
<?php
} ?>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Sensors"); ?> <br/>
        <font size="-2">
          <a href="../sensor/newsensorform.php">
	  <?php
echo gettext("Insert new sensor"); ?> ?</a>
        </font><br/>
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
        $name = "mboxs" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_sensor_reference::in_policy_sensor_reference($conn, $id, $sensor_name)) {
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
    <input type="checkbox" 
<?php
if (Policy_sensor_reference::in_policy_sensor_reference($conn, $id, 'any')) {
    echo " CHECKED ";
}
?>
           name="<?php
echo $name; ?>"
           value="any"><b> <?php
echo gettext("ANY"); ?> </b><br></input>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Time Range"); ?> 
    </th>
    <td>
      <table>
        <tr>
          <td> <?php
echo gettext("Begin"); ?> </td><td></td><td> <?php
echo gettext("End"); ?> </td>
        </tr>
        <tr>
          <td>
<?php
$policy_time = $policy->get_time($conn);
?>
            <select name="begin_day">
              <option 
              <?php
if ($policy_time->get_begin_day() == 1) echo " SELECTED " ?>
                value="1"><?=_('Mon')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 2) echo " SELECTED " ?>
                value="2"><?=_('Tue')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 3) echo " SELECTED " ?>
                value="3"><?=_('Wed')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 4) echo " SELECTED " ?>
                value="4"><?=_('Thu')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 5) echo " SELECTED " ?>
                value="5"><?=_('Fri')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 6) echo " SELECTED " ?>
                value="6"><?=_('Sat')?></option>
              <option 
              <?php
if ($policy_time->get_begin_day() == 7) echo " SELECTED " ?>
                value="7"><?=_('Sun')?></option>
            </select>
            <select name="begin_hour">
              <option 
              <?php
if ($policy_time->get_begin_hour() == 1) echo " SELECTED " ?>
                value="1"><?=_('1h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 2) echo " SELECTED " ?>
                value="2"><?=_('2h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 3) echo " SELECTED " ?>
                value="3"><?=_('3h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 4) echo " SELECTED " ?>
                value="4"><?=_('4h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 5) echo " SELECTED " ?>
                value="5"><?=_('5h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 6) echo " SELECTED " ?>
                value="6"><?=_('6h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 7) echo " SELECTED " ?>
                value="7"><?=_('7h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 8) echo " SELECTED " ?>
                value="8"><?=_('8h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 9) echo " SELECTED " ?>
                value="9"><?=_('9h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 10) echo " SELECTED " ?>
                value="10"><?=_('10h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 11) echo " SELECTED " ?>
                value="11"><?=_('11h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 12) echo " SELECTED " ?>
                value="12"><?=_('12h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 13) echo " SELECTED " ?>
                value="13"><?=_('13h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 14) echo " SELECTED " ?>
                value="14"><?=_('14h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 15) echo " SELECTED " ?>
                value="15"><?=_('15h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 16) echo " SELECTED " ?>
                value="16"><?=_('16h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 17) echo " SELECTED " ?>
                value="17"><?=_('17h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 18) echo " SELECTED " ?>
                value="18"><?=_('18h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 19) echo " SELECTED " ?>
                value="19"><?=_('19h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 20) echo " SELECTED " ?>
                value="20"><?=_('20h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 21) echo " SELECTED " ?>
                value="21"><?=_('21h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 22) echo " SELECTED " ?>
                value="22"><?=_('22h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 23) echo " SELECTED " ?>
                value="23"><?=_('23h')?></option>
              <option 
              <?php
if ($policy_time->get_begin_hour() == 0) echo " SELECTED " ?>
                value="0"><?=_('0h')?></option>
            </select>
          </td>
          <td>-</td>
          <td>
            <select name="end_day">
              <option 
              <?php
if ($policy_time->get_end_day() == 1) echo " SELECTED " ?>
                value="1"><?=_('Mon')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 2) echo " SELECTED " ?>
                value="2"><?=_('Tue')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 3) echo " SELECTED " ?>
                value="3"><?=_('Wed')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 4) echo " SELECTED " ?>
                value="4"><?=_('Thu')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 5) echo " SELECTED " ?>
                value="5"><?=_('Fri')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 6) echo " SELECTED " ?>
                value="6"><?=_('Sat')?></option>
              <option 
              <?php
if ($policy_time->get_end_day() == 7) echo " SELECTED " ?>
                value="7"><?=_('Sun')?></option>
            </select>
            <select name="end_hour">
              <option 
              <?php
if ($policy_time->get_end_hour() == 1) echo " SELECTED "; ?>
                value="1"><?=_('1h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 2) echo " SELECTED "; ?>
                value="2"><?=_('2h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 3) echo " SELECTED "; ?>
                value="3"><?=_('3h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 4) echo " SELECTED "; ?>
                value="4"><?=_('4h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 5) echo " SELECTED "; ?>
                value="5"><?=_('5h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 6) echo " SELECTED "; ?>
                value="6"><?=_('6h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 7) echo " SELECTED "; ?>
                value="7"><?=_('7h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 8) echo " SELECTED "; ?>
                value="8"><?=_('8h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 9) echo " SELECTED "; ?>
                value="9"><?=_('9h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 10) echo " SELECTED "; ?>
                value="10"><?=_('10h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 11) echo " SELECTED "; ?>
                value="11"><?=_('11h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 12) echo " SELECTED "; ?>
                value="12"><?=_('12h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 13) echo " SELECTED "; ?>
                value="13"><?=_('13h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 14) echo " SELECTED "; ?>
                value="14"><?=_('14h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 15) echo " SELECTED "; ?>
                value="15"><?=_('15h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 16) echo " SELECTED "; ?>
                value="16"><?=_('16h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 17) echo " SELECTED "; ?>
                value="17"><?=_('17h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 18) echo " SELECTED "; ?>
                value="18"><?=_('18h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 19) echo " SELECTED "; ?>
                value="19"><?=_('19h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 20) echo " SELECTED "; ?>
                value="20"><?=_('20h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 21) echo " SELECTED "; ?>
                value="21"><?=_('21h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 22) echo " SELECTED "; ?>
                value="22"><?=_('22h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 23) echo " SELECTED "; ?>
                value="23"><?=_('23h')?></option>
              <option 
              <?php
if ($policy_time->get_end_hour() == 0) echo " SELECTED "; ?>
                value="0"><?=_('0h')?></option>
            </select>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <th> <?php
echo gettext("Targets"); ?> <br/>
        <font size="-2">
          <a href="../sensor/newsensorform.php">
	  <?php
echo gettext("Insert new sensor"); ?> ?</a>
        </font><br/>
        <font size="-2">
          <a href="../server/newserverform.php">
	  <?php
echo gettext("Insert new server"); ?> ?</a>
        </font><br/>
    </th>
    <td class="left">
<?php
/* ===== target sensors ==== */
$i = 1;
if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
    foreach($sensor_list as $sensor) {
        $sensor_name = $sensor->get_name();
        $sensor_ip = $sensor->get_ip();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "targetsensor"; ?>"
            value="<?php
            echo count($sensor_list); ?>">
<?php
        }
        $name = "targboxsensor" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_target_reference::in_policy_target_reference($conn, $id, $sensor_name)) {
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

<?php
/* ===== target servers ==== */
$i = 1;
if ($server_list = Server::get_list($conn, "ORDER BY name")) {
    foreach($server_list as $server) {
        $server_name = $server->get_name();
        $server_ip = $server->get_ip();
        if ($i == 1) {
?>
        <input type="hidden" name="<?php
            echo "targetserver"; ?>"
            value="<?php
            echo count($server_list); ?>">
<?php
        }
        $name = "targboxserver" . $i;
?>
        <input type="checkbox" 
<?php
        if (Policy_target_reference::in_policy_target_reference($conn, $id, $server_name)) {
            echo " CHECKED ";
        }
?>
            name="<?php
        echo $name; ?>"
            value="<?php
        echo $server_name; ?>">
            <?php
        echo $server_ip . " (" . $server_name . ")<br>"; ?>
        </input>
<?php
        $i++;
    }
}
/* == ANY target == */
?>
    <input type="checkbox" 
<?php
if (Policy_target_reference::in_policy_target_reference($conn, $id, "any")) {
    echo " CHECKED ";
}
?>
name="target_any" value="any">&nbsp;<b><?php echo _("ANY") ?></b><br></input>

<?php
if ($role_list = $policy->get_role($conn)) {
    foreach($role_list as $role) {
?>
  <tr>
    <th> <?php
        echo gettext("Correlate events"); ?> </th>
    <td class="left">
    <input type="radio" name="correlate" value="1" <?php
        if ($role->get_correlate() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="correlate" value="0" <?php
        if ($role->get_correlate() == 0) echo " checked "; ?>> <?php echo _("No"); ?> <small>1)</small>
    </td>
  </tr>
  <tr>
    <th> <?php
        echo gettext("Cross Correlate events"); ?> </th>
    <td class="left">
    <input type="radio" name="cross_correlate" value="1" <?php
        if ($role->get_cross_correlate() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="cross_correlate" value="0" <?php
        if ($role->get_cross_correlate() == 0) echo " checked "; ?>> <?php echo _("No"); ?> <small>1)</small>
    </td>
  </tr>
  <tr>
    <th> <?php
        echo gettext("Store events"); ?> </th>
    <td class="left">
    <input type="radio" name="store" value="1" <?php
        if ($role->get_store() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="store" value="0" <?php
        if ($role->get_store() == 0) echo " checked "; ?>> <?php echo _("No"); ?> <small>1)</small>
    </td>
  </tr>
  <tr>
    <th> <?php
        echo gettext("Qualify events"); ?> </th>
    <td class="left">
    <input type="radio" name="qualify" value="1" <?php
        if ($role->get_qualify() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="qualify" value="0" <?php
        if ($role->get_qualify() == 0) echo " checked "; ?>> <?php echo _("No"); ?>
    </td>
  </tr>
  <tr>
    <th> <?php
        echo gettext("Resend alarms"); ?> </th>
    <td class="left">
    <input type="radio" name="resend_alarms" value="1" <?php
        if ($role->get_resend_alarm() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="resend_alarms" value="0" <?php
        if ($role->get_resend_alarm() == 0) echo " checked "; ?>> <?php echo _("No"); ?>
    </td>
  </tr>
  <tr>
    <th> <?php
        echo gettext("Resend events"); ?> </th>
    <td class="left">
    <input type="radio" name="resend_events" value="1" <?php
        if ($role->get_resend_event() == 1) echo " checked "; ?>> <?php echo _("Yes"); ?> 
    <input type="radio" name="resend_events" value="0" <?php
        if ($role->get_resend_event() == 0) echo " checked "; ?>> <?php echo _("No"); ?>
    </td>
  </tr>
<tr>
<td colspan="2" class="left">
1) <?php echo _("Does not apply to targets without associated database.") ?> <?php echo _("Implicit value is always No for them."); ?>
</td>
</tr>
	<?php
    }
}
?>
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="left">
        <textarea name="descr" rows="2" 
            cols="20"><?php
echo $policy->get_descr(); ?></textarea>
    </td>
  </tr>


<?php
$db->close($conn);
?>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" value="<?=_("OK")?>">
      <input type="reset" value="<?php
echo gettext('reset'); ?>">
    </td>
  </tr>
</table>
</form>

</body>
</html>

