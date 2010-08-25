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
require_once ("../../../../include/utils.php");
require_once ("../../../../include/groups.php");
require_once 'classes/Security.inc';
dbConnect();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../../../../style/directives.css">

		<script type="text/javascript" language="javascript" src="javascript/top.js"></script>
	</head>

	<body>

		<input type="hidden" id="is_mod" value="false" />

<?php
$iddir = GET('iddir');
$list = GET('list');
ossim_valid($iddir, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal: iddir');
ossim_valid($list, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal: list');
if (ossim_error()) {
    die(ossim_error());
}
if (!$dom = domxml_open_file('/etc/ossim/server/directives.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
    echo _("Error while parsing the document")."\n";
    exit;
}
$table = array();
$table_dir = $dom->get_elements_by_tagname('directive');
foreach($table_dir as $dir) {
    $table[$dir->get_attribute('id') ] = $dir->get_attribute('name');
}
ksort($table);
$groups = unserialize($_SESSION['groups']);
?>
<center>
		<table class="transparent" width="100%"><tr><th style="padding:5px;font-size:12px"><?php
echo gettext("List of groups"); ?></th></tr></table><br>
			<table>
				<tr>
					<th width="70px">
						<button class="th" id="all" onclick="onClickAll()">+</button>/
						<button class="th" id="inv" onclick="onClickInv()">-</button>
					</th>
					<th>
						<?php
echo gettext("Name"); ?>
					</th>
					<th>
						<?php
echo gettext("Directives"); ?>
					</th>
				</tr>

<?php
$default_checked = '';
foreach($groups as $group) {
    if (in_array($group->name, split(',', $list))) {
        $checked = ($default_checked == '') ? ' checked="checked"' : '';
    } else {
        $checked = $default_checked;
    }
    $list_dir = "";
    foreach($group->list as $dir) {
        if ($list_dir != "") $list_dir.= "<br>";
        $list_dir.= $dir . " : " . $table[$dir];
    }
?>

				<tr>
					<td>
						<input type="checkbox"
							name="chk"
							value="<?php
    echo $group->name; ?>"
							<?php
    echo $checked; ?>
						>
					</td>
					<td style="background: #eeeeee">
						<?php
    echo $group->name; ?>
					</td>
					<td style="text-align:left; background: #eeeeee">
						<?php
    echo $list_dir; ?>
					</td>
				</tr>

<?php
} ?>

			</table>
		</center>

		<script type="text/javascript" language="JavaScript">
			window.open(
				"../bottom.php?param=groups",
				"bottom"
			);
		</script>

	</body>
</html>

<?php
dbClose();
?>
