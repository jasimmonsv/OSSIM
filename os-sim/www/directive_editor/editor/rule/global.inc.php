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
			<th colspan="4">
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
				colspan="3"
			>
				<input type="text" style="width: 100%"
					name="name"
					id="name"
					value="<?php
echo str_replace("'", "", str_replace("\"", "", $rule->name)); ?>"
					title="<?php
echo str_replace("'", "", str_replace("\"", "", $rule->name)); ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeName()"
					onblur="onChangeName()"
					onfocus="onFocusName()"
				/>
			</td>
		</tr>
		<!-- ##### plugin id ##### -->
		<tr>
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Plugin Id"); ?>
			</td>
			<td style="width: <?php
echo $plugin_id_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<input type="text" style="width: <?php
echo $plugin_id_width; ?>"
					name="plugin_id"
					id="plugin_id"
					value="<?php
echo $rule->plugin_id; ?>"
					title="<?php
echo $rule->plugin_id; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangePluginId()"
					onblur="onChangePluginId()"
				/>
			</td>
			<td style="width: 100%;
				text-align: left; padding-left: 5px"
				id="plugin_name"
			>
				<?php
if ($rule->plugin_id != "") echo '<b>&nbsp;' . getPluginName($rule->plugin_id) . '&nbsp;&nbsp;(type: ' . getPluginType($rule->plugin_id) . ')</b>';
?>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					value="..." 
          onclick="open_frame('editor/rule/popup/index.php?top=plugin_id&plugin_id='+ getElt('plugin_id').value);"  
        />
					<!--
					open_frame('editor/rule/popup/index.php?top=plugin_id&plugin_id='+ getElt('plugin_id').value)
				/-->
			</td>
		</tr>
		<!-- ##### plugin sid ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Plugin Sid"); ?>
			</td>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="plugin_sid"
					id="plugin_sid"
					onchange="onChangePluginSid()"
				>
					<?php
$selected = selectIf(isAny($rule->plugin_sid));
echo "<option value=\"ANY\"$selected>ANY</option>";
for ($i = 1; $i <= $rule->level - 1; $i++) {
    $sublevel = $i . ":PLUGIN_SID";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":PLUGIN_SID";
    echo "<option value=\"$sublevel\">$sublevel</option>";
}
$selected = selectIf(isList($rule->plugin_sid));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: 100%; text-align: left; padding-right: 8px">
				<input type="text" style="width: 100%"
					name="plugin_sid_list"
					id="plugin_sid_list"
					value=""
					title=""
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangePluginSidList()"
					onblur="onChangePluginSidList()"
					<?php
echo disableIf(!isList($rule->plugin_sid)); ?>
				/>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					id="popup_plugin_sid"
					value="..."
					onclick="open_frame(
            'editor/rule/popup/index.php' +
						'?top=plugin_sid' +
						'&plugin_id=' + getElt('plugin_id').value +
						'&plugin_sid=' + getElt('plugin_sid').value +
						'&plugin_sid_list=' + getElt('plugin_sid_list').value
					)"
					<?php
echo disableIf($rule->plugin_id == ''); ?>
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: global properties ##################### -->
