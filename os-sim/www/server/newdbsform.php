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
Session::logcheck("MenuPolicy", "PolicyServers");
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
                                                                                
<?php if (GET('withoutmenu') != "1") include ("../hmenu.php"); ?>

<form method="post" action="newdbs.php" enctype="multipart/form-data">
<table align="center">
  <input type="hidden" name="insert" value="insert">
  <tr>
    <th> <?php
echo gettext("Name").required(); ?> </th>
    <td class="left"><input type="text" name="name"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("IP").required(); ?> </th>
    <td class="left"><input type="text" name="ip"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("Port").required(); ?> </th>
    <td class="left"><input type="text" value="3306" name="port"></td>
  </tr>
  <tr>
    <th> <?php
echo gettext("User").required(); ?> </th>
    <td class="left"><input type="text" name="user"></td>
  </tr>
    <tr>
    <th> <?php
echo gettext("Password").required(); ?> </th>
    <td class="left"><input type="password" name="pass"></td>
  </tr>
    <tr>
    <th> <?php
echo gettext("Icon"); ?> </th>
    <td class="left"><input style="border:1px solid black" type="file" name="icon"> <font style="font-size:10px">(*) <?=_("only 32x32 pixels png icon supported")?></font></td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" value="<?=_("OK")?>" class="btn" style="font-size:12px">
      <input type="reset" value="<?=_("reset")?>" class="btn" style="font-size:12px">
    </td>
  </tr>
</table>
</form>

</body>
</html>

