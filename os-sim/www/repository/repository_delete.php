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
require_once ("classes/Repository.inc");
// menu authentication
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "Osvdb");
$user = $_SESSION["_user"];
$id_document = GET('id_document');
ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("id_document"));
if (ossim_error()) {
    die(ossim_error());
}
if ($id_document == "") exit;
// DB connect
require_once ("ossim_db.inc");
$db = new ossim_db();
$conn = $db->connect();
Repository::delete($conn, $id_document);
$db->close($conn);
?>
<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>

<body style="margin:0">
<table>
	<tr><th><?=_("Delete Repository document id")?>: <?php echo $id_document ?></th></tr>
	<tr><td class="nobborder"><?=_("Document successfully deleted")?></td></tr>
	<tr><td class="nobborder"><input class="btn" type="button" value="<?=_("OK")?>" onclick="parent.document.location.href='index.php'"></td></tr>
</table>
</body>
</html>
