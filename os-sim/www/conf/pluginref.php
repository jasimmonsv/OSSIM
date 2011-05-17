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
Session::logcheck("MenuIntelligence", "CorrelationCrossCorrelation");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Plugin reference"); ?> </title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
include ("../hmenu.php");
require_once 'ossim_db.inc';
require_once 'classes/Security.inc';
$order = GET('order');
$inf = GET('inf');
$sup = GET('sup');
ossim_valid($order, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, 'illegal:' . _("order"));
ossim_valid($sup, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("sup"));
ossim_valid($inf, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("inf"));
if (ossim_error()) {
    die(ossim_error());
}
$db = new ossim_db();
$conn = $db->connect();
if (empty($order)) $order = "plugin_id";
require_once 'classes/Plugin_reference.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Plugin_sid.inc';
if (empty($inf)) $inf = 0;
if (empty($sup)) $sup = 25;
?>

    <table align="center" width="100%">
		<tr>
			<td colspan="4">
				<?php
				/*
				* prev and next buttons
				*/
				$inf_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup - 25) . "&inf=" . ($inf - 25);
				$sup_link = $_SERVER["SCRIPT_NAME"] . "?order=$order" . "&sup=" . ($sup + 25) . "&inf=" . ($inf + 25);

				$count = Plugin_reference::get_count($conn);
				if ($inf >= 25) {
					echo "<a href=\"$inf_link\">&lt;- ";
					printf(gettext("Prev %d") , 25);
					echo "</a>";
				}
				echo "&nbsp;&nbsp;(";
				printf(gettext("%d-%d of %d") , $inf, $sup, $count);
				echo ")&nbsp;&nbsp;";

				if ($sup < $count) 
				{
					echo "<a href=\"$sup_link\"> ";
					printf(gettext("Next %d") , 25);
					echo " -&gt;</a>";
				}
				?>
			</td>
		</tr>
		<tr>
			<th><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("plugin_id", $order) . "&inf=$inf&sup=$sup" ?>"><?php echo gettext("Plugin id"); ?> </a></th>
			<th><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("plugin_sid", $order) . "&inf=$inf&sup=$sup" ?>"><?php echo gettext("Plugin sid"); ?> </a></th>
			<th><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("reference_id", $order) . "&inf=$inf&sup=$sup" ?>"><?php echo gettext("Reference id"); ?> </a></th>
			<th><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?order=<?php echo ossim_db::get_order("reference_sid", $order) . "&inf=$inf&sup=$sup" ?>"><?php echo gettext("Reference sid"); ?> </a></th>
        </tr>

		<?php
		if ($pluginref_list = Plugin_reference::get_list($conn, "ORDER BY $order", $inf, $sup)) {
			foreach($pluginref_list as $plugin) 
			{
				$id = $plugin->get_plugin_id();
				$sid = $plugin->get_plugin_sid();
				$ref_id = $plugin->get_reference_id();
				$ref_sid = $plugin->get_reference_sid();
				// translate id
				if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) {
					$plugin_name = $plugin_list[0]->get_name();
				}
				// translate sid
				if ($plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $id AND sid = $sid")) {
					$plugin_sid_name = $plugin_sid_list[0]->get_name();
				}
				// translate ref id
				if ($plugin_list = Plugin::get_list($conn, "WHERE id = $ref_id")) {
					$plugin_ref_name = $plugin_list[0]->get_name();
				}
				// translate ref sid
				if ($plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $ref_id AND sid = $ref_sid")) {
					$plugin_ref_sid_name = $plugin_sid_list[0]->get_name();
				} else {
					$plugin_ref_sid_name = $ref_sid;
				}
				?>
				<tr>
					<td><?php echo $plugin_name; ?></td>
					<td><?php echo $plugin_sid_name; ?></td>
					<td><?php echo $plugin_ref_name; ?></td>
					<td><?php echo $plugin_ref_sid_name; ?></td>
				</tr>
				<?php
			}	
		}
	?>
    </table>

</body>

<?php $db->close($conn); ?>

</html>
