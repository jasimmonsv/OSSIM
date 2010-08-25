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
* - select_response_object()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "PolicyResponses");
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

  <h1><?php
echo gettext("New Response Action"); ?></h1>

<?php
define(ANY, "ANY");
/* list of response objects */
$RESPONSE_OBJECTS = array(
    "source_net",
    "source_host",
    "dest_net",
    "dest_host",
    "sensor",
    "source_port",
    "dest_port",
    "plugin",
    "action"
);
/* Add and delete objects from response arrays */
foreach($RESPONSE_OBJECTS as $object) {
    /*
    * clean response variables
    */
    if (!isset($_SESSION["_response_$object"]) or $_REQUEST[$object . "_clear"]) {
        $_SESSION["_response_$object"] = array();
    }
    /*
    * set and unset response variables
    */
    if ($_REQUEST["$object"] && $_REQUEST[$object . "_add"]) {
        if (!in_array($_REQUEST["$object"], $_SESSION["_response_" . $object])) {
            $_SESSION["_response_" . $object][] = $_REQUEST[$object];
        }
        /*
        * if ANY is in the list, remove others
        * if we are going to insert ANY, remove others
        */
        if (in_array(ANY, $_SESSION["_response_" . $object]) or $_REQUEST["$object"] == ANY) {
            $_SESSION["_response_$object"] = array(
                ANY
            );
        }
    }
}
if (!isset($_SESSION["_response_descr"]) or $_REQUEST["descr_clear"]) {
    $_SESSION["_response_descr"] = "";
}
if ($_REQUEST["descr"] && $_REQUEST["descr_add"]) {
    $_SESSION["_response_descr"] = $_REQUEST["descr"];
}
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
require_once ('classes/Response.inc');
if ($_REQUEST["insert_response"]) {
    /* insert response-action policy */
    Response::insert($conn, $_SESSION["_response_descr"], $_SESSION["_response_source_net"], $_SESSION["_response_source_host"], $_SESSION["_response_dest_net"], $_SESSION["_response_dest_host"], $_SESSION["_response_sensor"], $_SESSION["_response_source_port"], $_SESSION["_response_dest_port"], $_SESSION["_response_plugin"], $_SESSION["_response_action"]);
    /* clean session variables */
    foreach($RESPONSE_OBJECTS as $object) unset($_SESSION["_response_" . $object]);
    unset($_SESSION["_response_descr"]);
    echo '<p align="center">Response-Action policy inserted<br/>
          <a href="response.php">Back</a></p>';
    print '</body></html>';
    exit();
}
/* hosts */
require_once ('classes/Host.inc');
$host_list = Host::get_list($conn);
$hosts[] = array(
    "value" => ANY,
    "name" => "ANY"
);
foreach($host_list as $h) {
    $hosts[] = array(
        "value" => $h->get_ip() ,
        "name" => $h->get_hostname() . " (" . $h->get_ip() . ")"
    );
}
/* nets */
require_once ('classes/Net.inc');
$net_list = Net::get_list($conn);
$nets[] = array(
    "value" => ANY,
    "name" => "ANY"
);
foreach($net_list as $n) {
    $nets[] = array(
        "value" => $n->get_name() ,
        "name" => $n->get_name()
    );
}
/* sensors */
require_once ('classes/Sensor.inc');
$sensor_list = Sensor::get_list($conn);
$sensors[] = array(
    "value" => ANY,
    "name" => "ANY"
);
foreach($sensor_list as $s) {
    $sensors[] = array(
        "value" => $s->get_ip() ,
        "name" => $s->get_name() . " (" . $s->get_ip() . ")"
    );
}
/* ports */
require_once ('classes/Port_group.inc');
$port_list = Port_group::get_list($conn);
$ports[] = array(
    "value" => ANY,
    "name" => "ANY"
);
foreach($port_list as $p) {
    $ports[] = array(
        "value" => $p->get_name() ,
        "name" => $p->get_name() . " (" . $p->get_descr() . ")"
    );
}
/* plugins */
require_once ('classes/Plugin.inc');
$plugin_list = Plugin::get_list($conn);
$plugins[] = array(
    "value" => ANY,
    "name" => "ANY"
);
foreach($plugin_list as $p) {
    $plugins[] = array(
        "value" => $p->get_id() ,
        "name" => $p->get_name() . " (" . $p->get_id() . ")"
    );
}
/* actions */
require_once ('classes/Action.inc');
$action_list = Action::get_list($conn);
if (is_array($action_list)) {
    foreach($action_list as $a) {
        $actions[] = array(
            "value" => $a->get_id() ,
            "name" => $a->get_descr()
        );
    }
}
function select_response_object($title, $objects, $id) {
?>
  <!-- <?php
    echo $title ?> -->
  <tr>
    <th valign="top"><?php
    echo $title . ":"; ?></th>
    <td valign="top">
      <table class="noborder" width="100%" align="center">
        <tr>
          <td class="noborder" nowrap>
            <select name="<?php
    echo $id ?>" size="1">
<?php
    foreach($objects as $object) {
        echo '<option value="' . $object["value"] . '">' . $object["name"] . '</option>';
    }
?>
            </select>
            <br/>
            <input type="submit" name="<?php
    echo $id ?>_add"
                   value="<?php
    echo gettext("Add") ?>" />
            <input type="submit" name="<?php
    echo $id ?>_clear"
                   value="<?php
    echo gettext("Clear") ?>" />
          </td>
        </tr>
      </table>
    </td>
    <td>
      <table width="100%" align="center">
        <?php
    foreach($_SESSION["_response_$id"] as $object) {
        echo "<tr><td>$object</td></tr>";
    }
?>
      </table>
    </td>
  </tr>
  <!-- <?php
    echo "end " . $title ?> -->
<?php
}
$db->close($conn);
?>
  <table align="center">
  <form name="new_response" method="POST">
  
    <tr>
      <th><?php
echo gettext("Description"); ?></th>
      <td>
        <textarea name="descr"></textarea>
        <br/>
        <input type="submit" name="descr_add"
               value="<?php
echo gettext("Add") ?>" />
        <input type="submit" name="descr_clear"
               value="<?php
echo gettext("Clear") ?>" />
      </td>
      <td>
        <?php
if ($_SESSION["_response_descr"]) { ?>
          <table align="center" width="100%">
          <tr>
            <td><?php
    echo $_SESSION["_response_descr"] ?></td>
          </tr>
        </table>
         <?php
} ?>
      </td>
    </tr>

<?php
select_response_object(gettext("Source Nets") , $nets, "source_net");
select_response_object(gettext("Source Hosts") , $hosts, "source_host");
select_response_object(gettext("Dest Nets") , $nets, "dest_net");
select_response_object(gettext("Dest Hosts") , $hosts, "dest_host");
select_response_object(gettext("Sensors") , $sensors, "sensor");
select_response_object(gettext("Source Ports") , $ports, "source_port");
select_response_object(gettext("Dest Ports") , $ports, "dest_port");
select_response_object(gettext("Plugins") , $plugins, "plugin");
select_response_object(gettext("Actions") , $actions, "action");
if (($_SESSION["_response_descr"]) and ((count($_SESSION["_response_source_host"]) > 0) or (count($_SESSION["_response_source_net"]) > 0)) and ((count($_SESSION["_response_dest_host"]) > 0) or (count($_SESSION["_response_dest_net"]) > 0)) and (count($_SESSION["_response_plugin"]) > 0) and (count($_SESSION["_response_sensor"]) > 0) and (count($_SESSION["_response_action"]) > 0)) {
?>
    <tr>
      <td colspan="3">
        <input type="submit" name="insert_response" value="<?php
    echo gettext("Insert"); ?>" />
      </td>
    </tr>
        <?php
}
?>

  </form>
  </table>

</body>
</html>

