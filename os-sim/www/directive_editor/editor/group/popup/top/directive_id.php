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
require_once ('classes/Security.inc');
$list = GET('list');
ossim_valid($list, OSS_TEXT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("list"));
if (ossim_error()) {
    die(ossim_error());
}
if (!$dom = domxml_open_file('/etc/ossim/server/directives.xml', DOMXML_LOAD_SUBSTITUTE_ENTITIES)) {
    echo "Error while parsing the document\n";
    exit;
}
$table = array();
$table_dir = $dom->get_elements_by_tagname('directive');
foreach($table_dir as $dir) {
    $table[$dir->get_attribute('id') ] = $dir->get_attribute('name');
}
ksort($table);
?><h1><?php
echo gettext("List of directives"); ?></h1>
		<center>
			<table>
				<tr>
					<th width="70px">
						<a href="" onclick="onClickAll();return false"><?php echo _("Check All")?></a>
					</th>
					<th>
						<?php
echo gettext("Id"); ?>
					</th>
					<th>
						<?php
echo gettext("Name"); ?>
					</th>
				</tr>
<?php
$none_checked = 'true';
$default_checked = '';
foreach($table as $cle => $valeur) {
    if (in_array($cle, split(',', $list))) {
        $checked = ($default_checked == '') ? ' checked="checked"' : '';
    } else {
        $checked = $default_checked;
    }
    if ($checked != '') $none_checked = 'false';
?>
				<tr>
					<td>
						<input type="checkbox"
							name="chk"
							value="<?php
    echo $cle; ?>"
							<?php
    echo $checked; ?>
							onclick="onClickChk()"
						>
					</td>
					<td><?php
    echo $cle; ?></td>
					<td style="background: #eeeeee">
						<?php
    echo $valeur; ?>
					</td>
				</tr>

<?php
} ?>
			</table>
		</center>
		<script type="text/javascript" language="JavaScript">
			window.open(
				"../bottom.php?param=directive_id" +
				"&disabled=<?php
echo $none_checked; ?>",
				"bottom"
			);
		</script>
	</body>
</html>

<?php
dbClose();
?>
