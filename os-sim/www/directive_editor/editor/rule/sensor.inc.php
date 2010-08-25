	<!-- #################### sensor ##################### -->
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
			<th colspan="3">
				<?php
echo gettext("Sensor"); ?>
			</th>
		</tr>
		<!-- ##### first line (the only one) ##### -->
		<tr>
			<td style="width: <?php
echo $left_select_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<select style="width: <?php
echo $left_select_width; ?>"
					name="sensor"
					id="sensor"
					onchange="onChangeIPSelectBox('sensor')"
				>
					<?php
$selected = selectIf(isAny($rule->sensor));
echo "<option value=\"ANY\"$selected>ANY</option>";
$selected = selectIf(isList($rule->sensor));
echo "<option value=\"LIST\"$selected>LIST</option>";
?>
				</select>
			</td>
			<td style="width: 100%; padding-right: 8px">
				<input type="text" style="width: 100%"
					name="sensor_list"
					id="sensor_list"
					value=""
					title=""
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeIPList('sensor_list')"
					onblur="onChangeIPList('sensor_list')"
					<?php
echo disableIf(!isList($rule->sensor)); ?>
				/>
			</td>
			<td style="vertical-align: top">
				<input type="button" style="width: 25px; cursor:pointer;"
					value="..."
					onclick="open_frame(
            'editor/rule/popup/index.php' +
						'?top=sensor' +
						'&sensor=' + getElt('sensor').value +
						'&sensor_list=' + getElt('sensor_list').value
					)"
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: sensor ##################### -->
