	<!-- #################### other ##################### -->
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
echo $right_table_width; ?>">
		<tr>
			<th colspan="2">
				<?php
echo gettext("Sticky"); ?>
			</th>
		</tr>
		<!-- ##### sticky ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Sticky"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $right_select_width; ?>"
					name="sticky"
					id="sticky"
				>
					<?php
$selected = selectIf($rule->sticky == "None");
echo "<option value=\"None\"$selected>None</option>";
$selected = selectIf($rule->sticky == "true" || $rule->sticky == "" || $rule->sticky == "Default");
echo "<option value=\"true\"$selected>true</option>";
$selected = selectIf($rule->sticky == "false");
echo "<option value=\"false\"$selected>false</option>";
?>
				</select>
			</td>
		</tr>
		<!-- sticky different -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Sticky Different"); ?>
			</td>
			<td style="width: <?php
echo $right_select_width; ?>; text-align: left; padding-left: 5px">
				<select style="width: <?php
echo $right_select_width; ?>"
					name="sticky_different"
					id="sticky_different">
					<?php
$selected = selectIf($rule->sticky_different == "");
echo "<option value=\"None\"$selected>None</option>";
$selected = selectIf($rule->sticky_different == "PLUGIN_SID");
echo "<option value=\"PLUGIN_SID\"$selected>PLUGIN_SID</option>";
$selected = selectIf($rule->sticky_different == "SRC_IP");
echo "<option value=\"SRC_IP\"$selected>SRC_IP</option>";
$selected = selectIf($rule->sticky_different == "DST_IP");
echo "<option value=\"DST_IP\"$selected>DST_IP</option>";
$selected = selectIf($rule->sticky_different == "SRC_PORT");
echo "<option value=\"SRC_PORT\"$selected>SRC_PORT</option>";
$selected = selectIf($rule->sticky_different == "DST_PORT");
echo "<option value=\"DST_PORT\"$selected>DST_PORT</option>";
$selected = selectIf($rule->sticky_different == "PROTOCOL");
echo "<option value=\"PROTOCOL\"$selected>PROTOCOL</option>";
$selected = selectIf($rule->sticky_different == "SENSOR");
echo "<option value=\"SENSOR\"$selected>SENSOR</option>";
?>
				</select>
			</td>
		</tr>
	</table>
	<!-- #################### END: other ##################### -->
