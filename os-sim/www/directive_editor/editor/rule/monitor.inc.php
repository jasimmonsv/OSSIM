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
$disabled = disableIf(getPluginType($rule->plugin_id) != "monitor");
/* default values for "value" */
$value_list = array(
    10,
    15,
    20,
    30,
    50,
    100,
    200,
    300,
    400,
    500
);
if ($rule->value != 'Default' && !in_array(intval($rule->value) , $value_list)) {
    $value_list[] = intval($rule->value);
    sort($value_list);
}
/* default values for "monitor" */
$interval_list = array(
    10,
    15,
    20,
    30,
    50,
    100,
    200,
    300,
    400,
    500
);
if ($rule->value != 'Default' && !in_array(intval($rule->interval) , $interval_list)) {
    $interval_list[] = intval($rule->interval);
    sort($interval_list);
}
?>
	
	<!-- #################### monitor ##################### -->
	<table width="<?php
echo $right_table_width; ?>">
		<tr>
			<th colspan="2">
				<?php
echo gettext("Monitor"); ?>
			</th>
		</tr>
		<!-- ##### condition ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Condition"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $right_select_width; ?>"
					name="condition"
					id="condition"
					onchange="onChangeCondition()"
					<?php
echo $disabled; ?>
				>
					<?php
$selected = selectIf($rule->condition == "Default");
echo "<option value=\"Default\"$selected>Default</option>";
$selected = selectIf($rule->condition == "ne");
echo "<option value=\"ne\"$selected>&#8800</option>";
$selected = selectIf($rule->condition == "lt");
echo "<option value=\"lt\"$selected>&#60</option>";
$selected = selectIf($rule->condition == "gt");
echo "<option value=\"gt\"$selected>&#62</option>";
$selected = selectIf($rule->condition == "le");
echo "<option value=\"le\"$selected>&#8804</option>";
$selected = selectIf($rule->condition == "ge");
echo "<option value=\"ge\"$selected>&#8805</option>";
?>
				</select>
			</td>
		</tr>
		<!-- ##### value ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Value"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $right_select_width; ?>"
					class="editable"
					name="value"
					id="value"
					<?php
echo $disabled; ?>
				>
					<?php
$select = selectIf($rule->value == "Default");
echo "<option value=\"Default\"$selected>Default</option>";
foreach($value_list as $value) {
    $selected = selectIf($rule->value != "Default" && $value == $rule->value);
    echo "<option value=\"$value\"$selected>$value</option>";
}
?>
				</select>
			</td>
		</tr>
		<!-- ##### interval ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Interval"); ?>
			</td>
			<td style="width: <?php
echo $right_text_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $right_select_width; ?>"
					class="editable"
					name="interval"
					id="interval"
					<?php
echo $disabled; ?>
				>
					<?php
$select = selectIf($rule->interval == "Default");
echo "<option value=\"Default\"$selected>Default</option>";
foreach($interval_list as $value) {
    $selected = selectIf($rule->interval != "Default" && $value == $rule->interval);
    echo "<option value=\"$value\"$selected>$value</option>";
}
?>
				</select>
			</td>
		</tr>
		<!-- ##### absolute ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Absolute"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px">
				<select style="width: <?php
echo $right_select_width; ?>"
					name="absolute"
					id="absolute"
					onchange="onChangeAbsolute()"
					<?php
echo $disabled; ?>
				>
					<?php
$selected = selectIf($rule->absolute == "Default");
echo "<option value=\"Default\"$selected>Default</option>";
$selected = selectIf($rule->absolute == "true");
echo "<option value=\"true\"$selected>true</option>";
$selected = selectIf($rule->absolute == "false");
echo "<option value=\"false\"$selected>false</option>";
?>
				</select>
			</td>
		</tr>
	</table>
	<!-- #################### END: monitor ##################### -->
