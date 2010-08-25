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
Session::logcheck("MenuPolicy", "PolicyResponses");
require_once ('ossim_db.inc');
require_once ('classes/Action.inc');
require_once ('classes/Response.inc');
require_once ('classes/Host.inc');
$db = new ossim_db();
$conn = $db->connect();
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
echo gettext("Responses"); ?></h1>

  <table align="center" width="100%">
    <tr>
      <th nowrap><?php
echo gettext("Description"); ?></th>
      <th nowrap><?php
echo gettext("Source"); ?></th>
      <th nowrap><?php
echo gettext("Dest"); ?></th>
      <th nowrap><?php
echo gettext("Source Ports"); ?></th>
      <th nowrap><?php
echo gettext("Dest Ports"); ?></th>
      <th nowrap><?php
echo gettext("Sensors"); ?></th>
      <th nowrap><?php
echo gettext("Plugins"); ?></th>
      <th nowrap><?php
echo gettext("Actions"); ?></th>
      <th nowrap>#</th>
      <td></td>
    </tr>

<?php
if (is_array($response_list = Response::get_list($conn))) {
    foreach($response_list as $response) {
?>
    <tr>
      <!-- description -->
      <td><?php
        echo $response->get_descr(); ?>&nbsp;</td>
      <!-- end description -->

      <td>
        <table class="noborder" width="100%">
          <tr>

            <!-- source nets -->
            <td class="noborder">
        <?php
        if (is_array($source_net_list = $response->get_source_nets($conn))) {
            foreach($source_net_list as $net) {
                echo gettext("Net") . ' ' . $net->get_net() . "<br/>";
            }
        }
?>
            </td>
            <!-- end source nets -->

            <!-- source hosts -->
            <td class="noborder">
        <?php
        if (is_array($source_host_list = $response->get_source_hosts($conn))) {
            foreach($source_host_list as $host) {
                echo gettext("Host") . ' ' . Host::ip2hostname($conn, $host->get_host()) . "<br/>";
            }
        }
?>
            </td>
            <!-- end source hosts -->

          </tr>
        </table>
      </td>

      <td>
        <table class="noborder" width="100%">
          <tr>

            <!-- dest nets -->
            <td class="noborder">
        <?php
        if (is_array($dest_net_list = $response->get_dest_nets($conn))) {
            foreach($dest_net_list as $net) {
                echo gettext("Net") . ' ' . $net->get_net() . "<br/>";
            }
        }
?>
            </td>
            <!-- end dest nets -->

            <!-- dest hosts -->
            <td class="noborder">
        <?php
        if (is_array($dest_host_list = $response->get_dest_hosts($conn))) {
            foreach($dest_host_list as $host) {
                echo gettext("Host") . ' ' . Host::ip2hostname($conn, $host->get_host()) . "<br/>";
            }
        }
?>
            </td>
            <!-- end dest hosts -->
          </tr>
        </table>
      </td>

      <!-- source ports -->
      <td>
        <?php
        if (is_array($source_ports_list = $response->get_source_ports($conn))) {
            foreach($source_ports_list as $port) {
                if ($port->get_port() == 0) echo "ANY";
                else echo $port->get_port() . "<br/>";
            }
        }
?>
      </td>
      <!-- end source ports -->

      <!-- dest ports -->
      <td>
        <?php
        if (is_array($dest_ports_list = $response->get_dest_ports($conn))) {
            foreach($dest_ports_list as $port) {
                if ($port->get_port() == 0) echo "ANY";
                else echo $port->get_port() . "<br/>";
            }
        }
?>
      </td>
      <!-- end dest ports -->

      <!-- sensors -->
      <td>
        <?php
        if (is_array($sensor_list = $response->get_sensors($conn))) {
            foreach($sensor_list as $sensor) {
                echo Host::ip2hostname($conn, $sensor->get_host()) . "<br/>";
            }
        }
?>
      </td>
      <!-- end sensors -->

      <!-- plugins -->
      <td>
        <?php
        if (is_array($plugin_list = $response->get_plugins($conn))) {
            foreach($plugin_list as $plugin) {
                if ($plugin->get_plugin_id() == 0) echo "ANY";
                else echo $plugin->get_plugin_id() . "<br/>";
            }
        }
?>
      </td>
      <!-- end plugins -->

      <!-- actions -->
      <td>
        <?php
        if (is_array($action_list = $response->get_actions($conn))) {
            foreach($action_list as $action) {
                $a = Action::get_action_by_id($conn, $action->get_action_id());
                echo $a->get_descr() . "<br/>";
            }
        }
?>
      </td>
      <!-- end actions -->

      <td>
        <a href="deleteresponse.php?id=<?php
        echo $response->get_id() ?>"><?php echo _("Delete") ?></a>
      </td>

    </tr>
<?php
    }
}
?>
    <tr>
      <td colspan="9">
        <a href="newresponseform.php"><?php
echo gettext("Insert new response"); ?></a>
      </td>
    </tr>


<?php
/*
print '<a href="newresponseform.php">New Response</a>';

print "<pre>";
print "Response:<br/>";
print_r(Response::get_list($conn));
print "Response Source Hosts:<br/>";
print_r(Response::get_source_hosts($conn));
print "Response Source Nets:<br/>";
print_r(Response::get_source_nets($conn));
print "Response Dest Hosts:<br/>";
print_r(Response::get_dest_hosts($conn));
print "Response Dest Nets:<br/>";
print_r(Response::get_dest_nets($conn));
print "Response Source Ports:<br/>";
print_r(Response::get_source_ports($conn));
print "Response Dest Ports:<br/>";
print_r(Response::get_dest_ports($conn));
print "Response Sensors:<br/>";
print_r(Response::get_sensors($conn));
print "Response Plugins:<br/>";
print_r(Response::get_plugins($conn));
print "Response Actions:<br/>";
print_r(Response::get_actions($conn));
print "</pre>";
*/
?>


<?php
$db->close($conn);
?>

</body>
</html>

