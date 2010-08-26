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
/* connection to the OSSIM database */
require_once ('classes/Security.inc');
require_once ("../../../../include/utils.php");
dbConnect();

$order = GET('order');
if (empty($order)) $order = 'id';
$plugin_id = GET('plugin_id');
ossim_valid($order, OSS_ALPHA, 'illegal:' . _("order"));
ossim_valid($plugin_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("plugin_id"));
if (ossim_error()) {
    die(ossim_error());
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
	<head>
		<link type="text/css" href="../../../../style/directives.css" rel="stylesheet">

		<script type="text/javascript" language="javascript" src="javascript/top.js"></script>
	</head>

	<body>
<center>
		<table>
				<tr>
					<th>&nbsp;</th>
				<th>
					<?php
echo '<a href="' . ossim_db::get_order('id', $order) . '">' . gettext("Name") . '</a'; ?>
				</th>

<?php
print '  <th>';
print '   <a href="plugin_id?order=';
print ossim_db::get_order('name', $order) . '"';
print '   >' . gettext("Name") . '</a>';
print '  </th>';
print '  <th>';
print '   <a href="plugin_id?order=';
print ossim_db::get_order('type', $order) . '"';
print '   >' . gettext("Type") . '</a>';
print '  </th>';
print '  <th>';
print '   <a href="plugin_id?order=';
print ossim_db::get_order('type', $order) . '"';
print '   >' . gettext("Description") . '</a>';
print '  </th>';
print ' </tr>';
$none_checked = 'true';
$plugin_list = getPluginList('ORDER BY ' . $order);
foreach($plugin_list as $plugin) {
    $plugin_type = $plugin->get_type();
    if ($plugin_type == '1') $type_name = 'Detector (1)';
    elseif ($plugin_type == '2') $type_name = 'Monitor (2)';
    else $type_name = 'Other (' . $plugin_type . ')';
    if ($plugin_id == $plugin->get_id()) $checked = ' checked';
    else $checked = '';
    if ($checked != '') $none_checked = 'false';
    print '<tr>';
    print ' <td>';
    print '  <input type="radio" name="chk"' . $checked;
    print '   value="' . $plugin->get_id() . '" onclick="onClickChk()"';
    print '  >';
    print ' </td>';
    print ' <td>' . $plugin->get_id() . '</td>';
    print ' <td bgcolor="#eeeee">';
    print '  <b>' . $plugin->get_name() . '</b>';
    print ' </td>';
    print ' <td>' . $type_name . '</td>';
    print ' <td>' . $plugin->get_description() . '</td>';
    print '</tr>';
}
print '</table></center>'; ?>

	<script language="javascript">
		window.open(
			"../bottom.php?param=plugin_id" +
			"&disabled=<?php
echo $none_checked; ?>",
			"bottom"
		);
	</script><?php
dbClose();
?></body>

</html>
