<?
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
$directive_xml = preg_replace("/.*\//","",$_SESSION['XML_FILE']);
?>	
	<!-- #################### global properties ##################### -->
	<table width="<?php echo $left_table_width; ?>">
		<tr>
			<th colspan="5">
				<?php echo gettext("Global Properties"); ?>
			</th>
		</tr>
		<!-- ##### name ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php echo gettext("Name"); ?>
			</td>
			<td style="width: 100%; text-align: left; padding-left: 5px; padding-right: 8px" colspan="4">
				<input type="text" style="width: 100%" name="name" id="name" value="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" title="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" onkeypress="onKeyPressElt(this,event)" onchange="onChangeName()" onblur="onChangeName()" onfocus="onFocusName()">
			</td>
		</tr>
		<!-- ##### id ##### -->
		<tr>
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Id"); ?>
			</td>
			<td style="width: <?php
echo $select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<?
$categories = unserialize($_SESSION['categories']);
				// min IDs in range
				foreach($categories as $category) {
				?><input type="hidden" name="<?=$category->xml_file."_mini"?>" id="<?=$category->xml_file."_mini"?>" value="<?=$category->mini?>"><?
				}
				?>
				<input type="hidden" name="category_old" id="category_old" value="<?=$directive_xml?>">
				<input type="hidden" name="iddir_old" id="iddir_old" value="<?=$directive->id?>">
				<select style="width: <?php
echo $select_width; ?>"
					name="category"
					id="category"
					onchange="onChangeCategory(<?php
echo $directive->id; ?>)"
				>
					<?php
foreach($categories as $category) {
    //$selected = selectIf($category->mini <= $directive->id && $directive->id <= $category->maxi);
	$selected = ($category->xml_file == $directive_xml) ? " selected" : "";
    //echo '<option value="' . $category->id . '"' . $selected . '>' . $category->name . '</option>';
	// Now pass category filename, category id is not a good idea
	echo '<option value="' . $category->xml_file . '"' . $selected . '>' . $category->name . '</option>';
}
?>
				</select>
			</td>
			<td style="width: <?php
echo $id_width; ?>; text-align: left"
			>
				<input type="text" style="width: <?php
echo $id_width; ?>"
					name="iddir"
					id="iddir"
					value="<?php
echo $directive->id; ?>"
					title="<?php
echo $directive->id; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeId(<?php
echo $directive->id; ?>)"
					onblur="onChangeId(<?php
echo $directive->id; ?>)"
				/>
			</td>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Priority"); ?>
			</td>
			<td style="width: <?php
echo $priority_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $priority_width; ?>"
					name="priority"
					id="priority"
				>
					<?php
for ($i = 0; $i <= 5; $i++) {
    $selected = selectIf($directive->priority == $i);
    echo "<option value=\"$i\"$selected>$i</option>";
}
?>
				</select>
			</td>
		</tr>
		<!-- ##### list of groups ##### -->
		<tr>
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Groups"); ?>
			</td>
			<td style="width: 100%;
				text-align: left; padding-left: 5px"
				colspan="3"
			>
				<?php
$groups = unserialize($_SESSION['groups']);
$list = "";
foreach($groups as $group) {
    if (in_array($directive->id, $group->list)) {
        if ($list != "") $list.= ",";
        $list.= $group->name;
    }
    $list = trim($list);
}
?>
				<input type="text" style="width: 100%"
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
            'editor/directive/popup/index.php' +
						'?top=groups' +
						'&directive=' + getElt('iddir').value +
						'&list=' + getElt('list').value
					)"
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: global properties ##################### -->
