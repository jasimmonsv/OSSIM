	<!-- #################### network ##################### -->
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
			<th colspan="8">
				<?php
echo gettext("Network"); ?>
			</th>
		</tr>
		<!-- ##### from ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("From"); ?>
			</td>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="from"
					id="from"
					onchange="onChangeIPSelectBox('from')"
				>
					<?php
$selected = selectIf(isAny($rule->from));
echo "<option value=\"ANY\"$selected>ANY</option>";
for ($i = 1; $i <= $rule->level - 1; $i++) {
    $sublevel = $i . ":SRC_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":SRC_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = $i . ":DST_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":DST_IP";;
    echo "<option value=\"$sublevel\">$sublevel</option>";
}
$selected = selectIf(isList($rule->from));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: <?php
echo $left_text_width; ?>;
				text-align: left; padding-right: 8px"
			>
				<input type="text" style="width: 100%"
					name="from_list"
					id="from_list"
					value=""
					title=""
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeIPList('from_list')"
					onblur="onChangeIPList('from_list')"
					<?php
echo disableIf(!isList($rule->from)); ?>
				/>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					value="..."
					onclick="open_frame(
            'editor/rule/popup/index.php' +
						'?top=from' +
						'&from=' + getElt('from').value +
						'&from_list=' + getElt('from_list').value
					)"
				/>
			</td>
			<!-- ##### port from ##### -->
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				,&nbsp;<?php
echo gettext("Port"); ?>
			</td>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="port_from"
					id="port_from"
					onchange="onChangePortSelectBox('port_from')"
				>
					<?php
$selected = selectIf(isAny($rule->port_from));
echo "<option value=\"ANY\"$selected>ANY</option>";
for ($i = 1; $i <= $rule->level - 1; $i++) {
    $sublevel = $i . ":SRC_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":SRC_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = $i . ":DST_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":DST_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
}
$selected = selectIf(isList($rule->port_from));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: <?php
echo $left_text_width; ?>;
				text-align: left; padding-right: 8px"
			>
				<input type="text" style="width: 100%"
					name="port_from_list"
					id="port_from_list"
					value=""
					title=""
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangePortList('port_from_list')"
					onblur="onChangePortList('port_from_list')"
					<?php
echo disableIf(!isList($rule->port_from)); ?>
				/>
			</td>
		</tr>
		<!-- ##### to ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("To"); ?>
			</td>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="to"
					id="to"
					onchange="onChangeIPSelectBox('to')"
				>
					<?php
$selected = selectIf(isAny($rule->to));
echo "<option value=\"ANY\"$selected>ANY</option>";
for ($i = 1; $i <= $rule->level - 1; $i++) {
    $sublevel = $i . ":SRC_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":SRC_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = $i . ":DST_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":DST_IP";
    echo "<option value=\"$sublevel\">$sublevel</option>";
}
$selected = selectIf(isList($rule->to));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: <?php
echo $left_text_width; ?>;
				text-align: left; padding-right: 8px"
			>
				<input type="text" style="width: 100%"
					name="to_list"
					id="to_list"
					value=""
					title=""
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeIPList('to_list')"
					onblur="onChangeIPList('to_list')"
					<?php
echo disableIf(!isList($rule->to)); ?>
				/>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					value="..."
					onclick="open_frame(
            'editor/rule/popup/index.php' +
						'?top=to' +
						'&to=' + getElt('to').value +
						'&to_list=' + getElt('to_list').value
					)"
				/>
			</td>
			<!-- ##### port to ##### -->
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				,&nbsp;<?php
echo gettext("Port"); ?>
			</td>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="port_to"
					id="port_to"
					onchange="onChangePortSelectBox('port_to')"
				>
					<?php
$selected = selectIf(isAny($rule->port_to));
echo "<option value=\"ANY\"$selected>ANY</option>";
for ($i = 1; $i <= $rule->level - 1; $i++) {
    $sublevel = $i . ":SRC_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":SRC_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = $i . ":DST_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
    $sublevel = "!" . $i . ":DST_PORT";
    echo "<option value=\"$sublevel\">$sublevel</option>";
}
$selected = selectIf(isList($rule->port_to));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: <?php
echo $left_text_width; ?>;
				text-align: left; padding-right: 8px"
			>
				<?php
$value = isList($rule->port_to) ? $rule->port_to : "";
$disabled = disableIf(!isList($rule->port_to));
?>
				<input type="text" style="width: 100%"
					name="port_to_list"
					id="port_to_list"
					value="<?php
echo $value; ?>"
					title="<?php
echo $value; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangePortList('port_to_list')"
					onblur="onChangePortList('port_to_list')"
					<?php
echo $disabled; ?>
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: network ##################### -->
