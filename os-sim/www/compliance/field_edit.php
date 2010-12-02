<?php
/*****************************************************************************
*
*    License:
*
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
Session::logcheck("MenuIntelligence", "ComplianceMapping");
require_once 'classes/Compliance.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
$db = new ossim_db();
$conn = $db->connect();

$table = GET('table');
$ref = GET('ref');
$field = GET('field');
$pci = GET('pci');
ossim_valid($field, OSS_ALPHA, 'illegal:' . _("Field value"));
ossim_valid($table, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("Table value"));
ossim_valid($ref, OSS_ALPHA, OSS_SCORE, OSS_DOT, '-', 'illegal:' . _("Ref value"));
ossim_valid($pci, OSS_DIGIT, 'illegal:' . _("PCI value"));
if (ossim_error()) {
	die(ossim_error());
}
$text = GET('text');
if (GET('save') == "1") {
	ossim_valid($text, OSS_ALPHA, OSS_SCORE, OSS_DOT, OSS_SPACE, OSS_NULLABLE, '-', 'illegal:' . _("Text"));
	if (ossim_error()) {
		die(ossim_error());
	}
	if ($pci) PCI::save_text($conn,$table,$ref,$text);
	else ISO27001::save_text($conn,$table,$ref,$text);
}
if ($pci) $text = PCI::get_text($conn,$table,$ref);
else $text = ISO27001::get_text($conn,$table,$ref);
?>

<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> - <?=_("Compliance")?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<table class="noborder" align="center" style="background-color:white">
	<form name="ffield" method="get">
	<input type="hidden" value="1" name="save">
	<input type="hidden" value="<?=$pci?>" name="pci">
	<input type="hidden" value="<?=$table?>" name="table">
	<input type="hidden" value="<?=$ref?>" name="ref">
	<input type="hidden" value="<?=$field?>" name="field">
	<tr><th><?=_("Insert the text for '$field'")?></th></tr>
	<tr>
		<td class="nobborder" style="text-align:center">
			<textarea name="text" cols="40" rows="6"><?=$text?></textarea>
		</td>
	</tr>
	<tr><td class="nobborder" style="text-align:center"><input type="submit" value="<?=_("Save")?>" class="button"></td></tr>
	</form>
</table>
</body>
</html>

