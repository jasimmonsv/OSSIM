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
require_once ('classes/Security.inc');
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
$order = GET('order');
if (empty($order)) $order = 'sid';
$plugin_id = GET('plugin_id');
$plugin_sid = GET('plugin_sid');
$plugin_sid_list = GET('plugin_sid_list');
ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("plugin_sid"));
ossim_valid($plugin_sid_list, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, 'illegal:' . _("plugin_sid_list"));
if (ossim_error()) {
    die(ossim_error());
}
$vars = '&amp;plugin_id=' . $plugin_id . '&amp;plugin_sid=' . $plugin_sid . '&amp;plugin_sid_list=' . $plugin_sid_list;
$plugins = Plugin::get_list($conn, 'WHERE id = ' . $plugin_id);
$title = $plugins[0]->get_name() . " ($plugin_id)";
?>
		<h2><?php
echo $title; ?></h2>

		<center>
			<table>
				<tr>
					<th width="70px">
						<a href="" id="all" onclick="onClickAll();return false"><?php echo _("Check All") ?></a>
					</th>
					<th>
						<button class="th"
							onclick="if (!isMod() || confirm('<?php
echo gettext('Changes will be lost!'); ?>') == '1')
							window.location.href='plugin_sid.php?order=<?php
echo ossim_db::get_order("sid", $order) . $vars; ?>'"
						><?php
echo gettext("Sid"); ?></button>
					</th>
					<th>
						<button class="th"
							onclick="if (!isMod() || confirm('<?php
echo gettext('Changes will be lost!'); ?>') == '1')
							window.location.href='plugin_sid.php?order=<?php
echo ossim_db::get_order("Name", $order) . $vars; ?>'"
						><?php
echo gettext("Name"); ?></button>
					</th>
				</tr>

<?php
$none_checked = 'true';
if (substr($plugin_sid_list, 0, 1) == '!') {
    $default_checked = ' checked="checked"';
    $plugin_sid_list = substr($plugin_sid_list, 1);
} else $default_checked = '';
if ($plugin_list = getPluginSidList($plugin_id, 'ORDER BY ' . $order)) {
    foreach($plugin_list as $plugin) {
        $sid = $plugin->get_sid();
        $name = $plugin->get_name();
        //if ($plugin_sid == 'ANY') {
        //	$checked = ' checked="checked"';
        //}
        /*else*/
        if (in_array($sid, split(',', $plugin_sid_list))) {
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
        echo $sid; ?>"
							<?php
        echo $checked; ?>
							onclick="onClickChk()"
						>
					</td>
					<td><?php
        echo $sid; ?></td>
					<td style="background: #eeeeee">
						<?php
        echo $name; ?>
					</td>
				</tr>

<?php
    }
} ?>

			</table>
		</center>

		<script type="text/javascript" language="JavaScript">
			window.open(
				"../bottom.php?param=plugin_sid" +
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
