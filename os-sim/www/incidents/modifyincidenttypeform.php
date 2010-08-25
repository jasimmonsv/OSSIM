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
Session::logcheck("MenuIncidents", "IncidentsTypes");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
require_once 'classes/Security.inc';
$inctype_id = GET('id');
ossim_valid($inctype_id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Incident type"));
if (ossim_error()) {
    die(ossim_error());
}
require_once ('ossim_db.inc');
require_once ("classes/Incident_type.inc");
$db = new ossim_db();
$conn = $db->connect();
if ($inctype_list = Incident_type::get_list($conn, "WHERE id = '$inctype_id'")) {
    $inctype = $inctype_list[0];
}
?>

<form method="post" action="modifyincidenttype.php">
<table align="center">
  <input type="hidden" name="modify" value="modify" />
  <input type="hidden" name="id" value="<?php
echo $inctype->get_id(); ?>" />
  <tr>
    <th> <?php
echo gettext("Ticket type"); ?> </th>
    <th class="left"><?php
echo $inctype->get_id(); ?></th>
  </tr>
  <tr>
    <th> <?php
echo gettext("Description"); ?> </th>
    <td class="nobborder">
      <textarea name="descr"><?php
echo $inctype->get_descr(); ?></textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="text-align:center;" class="nobborder">
      <input type="submit" value="<?=_("OK")?>" class="btn">
      <input type="reset" value="<?=_("reset")?>" class="btn">
    </td>
  </tr>
</table>
</form>

</body>
</html>

