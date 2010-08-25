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
Session::logcheck("MenuConfiguration", "ConfigurationHostScan");
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
echo gettext("Host scan configuration"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$order = GET('order');
ossim_valid($order, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($order)) $order = "inet_aton(host_ip)";
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
?>

<p align="center">
* <?php
echo gettext("Use policy->hosts or networks to define nessus scans or else you'll get unexpected results"); ?> .
</p>

  <table align="center">
    <tr>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("host_ip", $order);
?>">
	<?php
echo gettext("Host"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("plugin_id", $order);
?>">
	<?php
echo gettext("Plugin id"); ?> </a></th>
      <th><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php
echo ossim_db::get_order("plugin_sid", $order);
?>">
	<?php
echo gettext("Plugin sid"); ?> </a></th>
      <th> <?php
echo gettext("Action"); ?> </th>
    </tr>
    
<?php
require_once ('classes/Host_scan.inc');
require_once ('classes/Plugin.inc');
if ($host_list = Host_scan::get_list($conn, "ORDER BY $order")) {
    foreach($host_list as $host) {
        $ip = $host->get_host_ip();
        $id = $host->get_plugin_id();
        if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) {
            $plugin_name = $plugin_list[0]->get_name();
        }
?>
    <tr>
      <td><?php
        echo $ip ?></td>
      <td><?php
        echo $plugin_name . " (" . $id . ")"; ?></td>
      <td>
          <?php
        if ($sid = $host->get_plugin_sid()) echo $sid;
        else echo "ANY"
?>
      </td>
      <td>
          <a href="deletehostscan.php?host_ip=<?php
        echo $ip
?>&plugin_id=<?php
        echo $id ?>">
	  <?php
        echo gettext("Delete"); ?> </a>
      </td>
    </tr>
<?php
    }
}
$db->close($conn);
?>
    <tr>
      <td colspan="4"><a href="newhostscanform.php"> <?php
echo gettext("New"); ?> </a></td>
    </tr>
  </table>
  
</body>
</html>

