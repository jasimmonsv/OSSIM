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
Session::logcheck("MenuPolicy", "PolicySensors");
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
require_once 'classes/Sensor.inc';
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
$name = GET('name');
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Sensor name"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if ($sensor_list = Sensor::get_list($conn, "WHERE name = '$name'")) {
    $sensor = $sensor_list[0];
}
$db->close($conn);
?>

<form method="post" action="modifysensor.php">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Hostname"); ?> </th>
      <input type="hidden" name="name"
             value="<?php
echo $sensor->get_name(); ?>">
    <td class="nobborder left">
      <b><?php
echo $sensor->get_name(); ?></b>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("IP"); ?> </th>
    <td class="nobborder left">
        <input type="text" name="ip" 
               value="<?php
echo $sensor->get_ip(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Priority"); ?> </th>
    <td class="nobborder left">
      <select name="priority">
        <option
        <?php
if ($sensor->get_priority() == 0) echo " SELECTED "; ?>
          value="0">0</option>
        <option
        <?php
if ($sensor->get_priority() == 1) echo " SELECTED "; ?>
          value="1">1</option>
        <option
        <?php
if ($sensor->get_priority() == 2) echo " SELECTED "; ?>
          value="2">2</option>
        <option
        <?php
if ($sensor->get_priority() == 3) echo " SELECTED "; ?>
          value="3">3</option>
        <option
        <?php
if ($sensor->get_priority() == 4) echo " SELECTED "; ?>
          value="4">4</option>
        <option
        <?php
if ($sensor->get_priority() == 5) echo " SELECTED "; ?>
          value="5">5</option>
        <option
        <?php
if ($sensor->get_priority() == 6) echo " SELECTED "; ?>
          value="6">6</option>
        <option
        <?php
if ($sensor->get_priority() == 7) echo " SELECTED "; ?>
          value="7">7</option>
        <option
        <?php
if ($sensor->get_priority() == 8) echo " SELECTED "; ?>
          value="8">8</option>
        <option
        <?php
if ($sensor->get_priority() == 9) echo " SELECTED "; ?>
          value="9">9</option>
        <option
        <?php
if ($sensor->get_priority() == 10) echo " SELECTED "; ?>
          value="10">10</option>
      </select>
    </td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Port"); ?> </th>
    <td class="nobborder left">
        <input type="text" name="port" 
               value="<?php
echo $sensor->get_port(); ?>"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="nobborder left">
      <textarea name="descr" 
        rows="2" cols="20"><?php
echo $sensor->get_descr(); ?></textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center" class="nobborder center">
      <input type="submit" value="<?=_("OK")?>" class="btn" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

</body>
</html>

