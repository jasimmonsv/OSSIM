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
/* default values for "occurrence" */
$occurrence_list = array(
    1,
    2,
    3,
    4,
    5,
    10,
    15,
    50,
    75,
    200,
    300,
    1000,
    1500,
    10000,
    20000,
    50000,
    65535,
    100000
);
if ($rule->occurrence != "ANY" && !in_array($rule->occurrence, $occurrence_list)) {
    $occurrence_list[] = $rule->occurrence;
    sort($occurrence_list);
}
/* default values for "time_out" */
$timeout_list = array(
    5,
    10,
    20,
    30,
    60,
    180,
    300,
    600,
    1200,
    1800,
    3600,
    7200,
    43200,
    86400
);
if ($rule->time_out != "None" && !in_array($rule->time_out, $timeout_list)) {
    $timeout_list[] = $rule->time_out;
    sort($timeout_list);
}
?>

	<!-- #################### risk ##################### -->
	<table width="<?php
echo $right_table_width; ?>">
		<tr>
			<th colspan="3">
				<?php
echo gettext("Risk"); ?>
			</th>
		</tr>
		<!-- ##### occurrence ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Occurrence"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px"
				colspan="2"
			>
				<select class="editable" name="occurrence" id="occurrence">
					<?php
$selected = selectIf(isAny($rule->occurrence));
echo "<option value=\"ANY\"$selected>ANY</option>";
foreach($occurrence_list as $value) {
    $selected = selectIf($rule->occurrence != "ANY" && $value == $rule->occurrence);
    echo "<option value=\"$value\"$selected>$value</option>";
}
?>
				</select>
			</td>
		</tr>
		<!-- ##### timeout ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Timeout"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px"
				colspan="2"
			>
				<select style="width: <?php
echo $right_select_width; ?>"
					class="editable"
					name="time_out"
					id="time_out"
				>
					<?php
$selected = selectIf(isAny($rule->time_out));
echo "<option value=\"None\"$selected>None</option>";
foreach($timeout_list as $value) {
    $selected = selectIf($rule->time_out != "None" && $value == $rule->time_out);
    echo "<option value=\"$value\"$selected>$value</option>";
}
?>
				</select>
			</td>
		</tr>
		<!-- ##### reliability ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Reliability"); ?>
			</td>
			<td style="width: <?php
echo $reliability1_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $reliability1_width; ?>"
					name="reliability_op"
					id="reliability_op"
				>
					<?php
$first = $rule->reliability{0};
$selected = selectIf($first != "+");
echo "<option value=\"=\"$selected>=</option>";
if ($rule->level > 1) {
    $selected = selectIf($first == "+");
    echo "<option value=\"+\"$selected>+</option>";
}
?>
				</select>
			</td>
			<td style="width: <?php
echo $reliability2_width; ?>">
				<select style="width: <?php
echo $reliability2_width; ?>"
					name="reliability"
					id="reliability"
				>
					<?php
$value = intval(strtr($rule->reliability, '+', ''));
for ($i = 0; $i <= 10; $i++) {
    $selected = selectIf($value == $i);
    echo "<option value=\"$i\"$selected>$i</option>";
}
?>
				</select>
			</td>
		</tr>
	</table>
	<!-- #################### END: risk ##################### -->
