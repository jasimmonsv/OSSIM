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
require_once 'classes/Session.inc';
Session::logcheck("MenuConfiguration", "ConfigurationMaps");
$db = new ossim_db();
$conn = $db->connect();
if (GET('delete')) {
    $id = GET('delete');
    $sql = "DELETE FROM map_element WHERE map_id = ?";
    if (!$conn->Execute($sql, array(
        $id
    ))) {
        die($conn->ErrorMsg());
    }
    $sql = "DELETE FROM map WHERE id = ?";
    if (!$conn->Execute($sql, array(
        $id
    ))) {
        die($conn->ErrorMsg());
    }
}
$sql = "SELECT
            m.id, m.name, m.engine, count(e.id) as num
        FROM
            map m
        LEFT JOIN map_element AS e ON m.id = e.map_id 
        GROUP BY m.id";
$rows = $conn->GetArray($sql);
if ($rows === false) {
    die(ossim_error($conn->ErrorMsg()));
}
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
<div style="width=100%; text-align: right">[<a href="./new.php"><?php echo _("New map") ?></a>]</div>
<h3><?php echo _("Configured maps") ?></h3>
<?php
if (!count($rows)) { ?>
    <center><i><?php echo _("No configured map found, please configure one using the 'New map' option") ?></i></center>
<?php
} else { ?>
<table align="center" width="80%">
    <tr>
        <th><?php echo _("Map name") ?></th>
        <th><?php echo _("Map type") ?></th>
        <th><?php echo _("#pos") ?></th>
        <th><?php echo _("Actions") ?></th>
    </tr>
    <?php
    foreach($rows as $r) { ?>
    <tr>
        <td><?php echo $r['name'] ?></td>
        <td><?php echo $r['engine'] ?></td>
        <td><?php echo $r['num'] ?></td>
        <td nowrap>
        [<a href="./positions.php?map_id=<?php echo $r['id'] ?>"><?php echo _("set positions") ?></a>]&nbsp;
        [<a href="./draw_openlayers.php?map_id=<?php echo $r['id'] ?>"><?php echo _("view map") ?></a>]&nbsp;
        [<a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?delete=<?php echo $r['id'] ?>"><?php echo _("delete") ?></a>]</td>
    </tr>
    <?php
    } ?>
</table>
<?php
} ?>
</body></html>