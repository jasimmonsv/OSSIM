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
echo gettext("Insert new host scan configuration"); ?> </h1>

<?php
require_once ('ossim_db.inc');
require_once ('classes/Plugin.inc');
$db = new ossim_db();
$conn = $db->connect();
$plugin_list = Plugin::get_list($conn, "WHERE id >= 3000 AND id < 4000");
?>

  <table align="center">
  <form method="post" action="newhostscan.php">

    <input type="hidden" name="insert" value="insert">
    <tr>
      <th><?php
echo gettext("host IP"); ?></th>
      <td class="left"><input type="text" name="host_ip"></td>
    </tr>
    <tr>
      <th><?php
echo gettext("Plugin id"); ?></th>
      <td class="left">
        <select name="plugin_id">
<?php
if ($plugin_list) {
    foreach($plugin_list as $plugin) {
        echo "<option value=\"" . $plugin->get_id() . "\">" . $plugin->get_name() . "</option>";
    }
}
?>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value="OK"/></td>
    </tr>
  
  </form>
  </table>
  
<?php
$db->close($conn);
?>

</body>
</html>

