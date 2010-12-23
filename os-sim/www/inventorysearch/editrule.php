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
require_once ('classes/Security.inc');
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/InventorySearch.inc');
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$type = (GET('type') != "") ? GET('type') : POST('type');
$subtype = (GET('subtype') != "") ? GET('subtype') : POST('subtype');

$new = ($type == "" && $subtype == "") ? 1 : 0;

if (POST('save') == "1") {
	$match = POST('match');
	$list = POST('prelist');
	$query = POST('query');
	// Security OJO
	InventorySearch::insert($conn,$type,$subtype,$match,$list,$query);
}

$matches = InventorySearch::get_matches($conn);
if ($type != "" && $subtype != "") {
	// Security OJO
	$rule = InventorySearch::get_rule($conn,$type,$subtype);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<table width="100%">
	<? if (POST('save')) { ?>
	<tr><td class="nobborder" style="text-align:center;color:green" id="saved"><b>Succesfully Saved</b></td></tr>
	<? } ?>
	<form name="editrules" method="post">
	<input type="hidden" name="save" value="1">
	<? if ($new) { ?>
	<tr>
		<th><?=_("NEW RULE")?></th>
	</tr>
	<tr><td><?=_("Filter context")?>: <input type="text" name="type" value=""></td></tr>
	<tr><td><?=_("Filter")?>: <input type="text" name="subtype" value=""></td></tr>
	<? } else { ?>
	<input type="hidden" name="type" value="<?=$rule->get_type()?>">
	<input type="hidden" name="subtype" value="<?=$rule->get_subtype()?>">
	<tr>
		<th><?=_("RULE")?>: <b><?=$rule->get_type()?></b> -> <b><?=$rule->get_subtype()?></b></th>
	</tr>
	<? } ?>
	<tr>
		<td class="nobborder" style="text-align:center"><?=_("Match type")?>: 
			<select name="match<?=$i?>">
				<? foreach ($matches as $m) { ?>
				<option value="<?=$m?>" <? if (!$new && $m == $rule->get_match()) echo "selected"?>><?=$m?>
				<? } ?>
			</select>
		</td>
	</tr>
	<tr>
		<td class="nobborder" style="text-align:center"><?=_("QUERY")?><br><textarea rows="5" cols="80" name="query"><? if (!$new) echo $rule->get_query()?></textarea></td>
	</tr>
	<tr>
		<td class="nobborder" style="text-align:center"><?=_("Predefined list (optional)")?><br><textarea rows="5" cols="80" name="prelist"><? if (!$new) echo $rule->get_prelist()?></textarea></td>
	</tr>
	<tr><td class="nobborder" style="text-align:center"><input type="submit" class="lbutton" value="<?=_("Update")?>">&nbsp;<input type="button" class="lbutton" onclick="document.location.href='editrules.php'" value="<?=_("Back")?>"></td></tr>
	</form>
</table>
<? if (POST('save')) { ?>
<script type="text/javascript">
setInterval("document.getElementById('saved').innerHTML=''",1000);
</script>
<? } ?>
</body>
</html>
