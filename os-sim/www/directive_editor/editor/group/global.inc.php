	<!-- #################### global properties ##################### -->
	<table width="<?php
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
echo $left_table_width; ?>">
		<tr>
			<th colspan="6">
				<?php
echo gettext("Global Properties"); ?>
			</th>
		</tr>
		<!-- ##### name ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Name"); ?>
			</td>
			<td style="width: 100%; text-align: left;
				padding-left: 5px; padding-right: 8px"
				colspan="6"
			>
				<?php
$name = "";
if ($group->name == "") $name = "New group";
else $name = str_replace("'", "", str_replace("\"", "", $group->name));
?>
				<input type="text" style="width: 100%"
					name="name"
					id="name"
					value="<?php
print $name; ?>"
					title="<?php
print $name; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeName('<?php
print $_GET['name']; ?>')"
					onblur="onChangeName('<?php
print $_GET['name']; ?>')"
					onfocus="onFocusName()"
				/>
			</td>
		</tr>
		<!-- ##### list of directives ##### -->
		<tr>
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("List of directives"); ?>
			</td>
			<td style="width: <?php
echo $list_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<?php
$list = "";
if ($group->list != null) {
    foreach($group->list as $dir) {
        if ($list != "") $list.= ",";
        $list.= $dir;
    }
    $list = trim($list);
}
?>
				<input type="text" style="width: <?php
echo $list_width; ?>"
					name="list"
					id="list"
					value="<?php
print $list; ?>"
					title="<?php
print $list; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangelist('<?php
print $list; ?>')"
					onblur="onChangelist('<?php
print $list; ?>')"
				/>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					id="popup_plugin_sid"
					value="..."
					onclick="open_frame(
            'editor/group/popup/index.php' +
						'?top=directive_id' +
						'&group_name=' + getElt('name').value +
						'&list=' + getElt('list').value
					)"
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: global properties ##################### -->
