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
if (GET("engine") == 'openlayers_ve') {
    header("Location: openlayers.php?layer=ve");
    exit;
}
if (GET("engine") == 'openlayers_op') {
    header("Location: openlayers.php?layer=op");
    exit;
}
if (GET("engine") == 'openlayers_image') {
    header("Location: openlayers_image.php");
    exit;
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
<h3><?php echo _("Select map engine") ?></h3>

<form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" method="get">  
<table align="center" widht="60%">
<tr>
    <td style="text-align: left"><input type="radio" name="engine" value="openlayers_ve">Openlayers Virtual Earth</td>
</tr><tr>
    <td style="text-align: left"><input type="radio" name="engine" value="openlayers_op">Openlayers Native</td>
</tr><tr>
    <td style="text-align: left"><input type="radio" name="engine" value="openlayers_image">Openlayers Image</td>
</tr></table>
<br>
<center><input type="submit" name="submit" value="<?php echo _("Next") ?> &gt;"></center>
</form>
</body></html>