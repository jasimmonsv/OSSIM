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
<div id="wizard_10" style="display:none">
<input type="hidden" name="condition" id="condition" value="<?php echo $rule->condition ?>"></input>
<input type="hidden" name="value" id="value" value="<?php echo $rule->value ?>"></input>
	<table class="transparent" width="100%">
		<!-- ##### condition AND value ##### -->
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Monitor value"); ?>
			</th>
		</tr>
		<tr>
			<td class="center nobborder">
				<table class="transparent">
					<tr>
						<td colspan="5" class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('condition').value = 'Default';document.getElementById('value').value = 'Default';wizard_next();" style="width:80px<?php if ($rule->value == "Default") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></input></td>
					</tr>
					<?php foreach ($value_list as $value) { ?>
					<tr>
						<td class="center nobborder"><input type="button" value="&#8800 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'ne';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->value != "Default" && $value == $rule->value && $rule->condition == "ne") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
						<td class="center nobborder"><input type="button" value="&#60 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'lt';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->value != "Default" && $value == $rule->value && $rule->condition == "lt") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
						<td class="center nobborder"><input type="button" value="&#62 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'gt';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->value != "Default" && $value == $rule->value && $rule->condition == "gt") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
						<td class="center nobborder"><input type="button" value="&#8804 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'le';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->value != "Default" && $value == $rule->value && $rule->condition == "le") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
						<td class="center nobborder"><input type="button" value="&#8805 <?php echo $value ?>" onclick="document.getElementById('condition').value = 'ge';document.getElementById('value').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->value != "Default" && $value == $rule->value && $rule->condition == "ge") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
	</table>
</div>

<div id="wizard_11" style="display:none">
<input type="hidden" name="interval" id="interval" value="<?php echo $rule->interval ?>"></input>
	<table class="transparent" width="100%">
		<!-- ##### interval ##### -->
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Monitor interval"); ?>
			</th>
		</tr>
		<tr><td class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('interval').value = 'Default';wizard_next();" style="width:80px<?php if ($rule->interval == "Default") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></input></td></tr>
		<?php foreach ($interval_list as $value) { ?>
		<tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('interval').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($rule->interval != "Default" && $value == $rule->interval) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></input></td></tr>
		<?php } ?>
	</table>
</div>

<div id="wizard_12" style="display:none">
<input type="hidden" name="absolute" id="absolute" value="<?php echo $rule->absolute ?>"></input>
	<table class="transparent" width="100%">
		<!-- ##### absolute ##### -->
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Monitor absolute"); ?>
			</th>
		</tr>
		<tr><td class="center nobborder"><input type="button" value="Default" onclick="document.getElementById('absolute').value = 'Default';wizard_next();" style="width:50px<?php if ($rule->absolute == "Default") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="true" onclick="document.getElementById('absolute').value = 'true';wizard_next();" style="width:50px<?php if ($rule->absolute == "true") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="false" onclick="document.getElementById('absolute').value = 'false';wizard_next();" style="width:50px<?php if ($rule->absolute == "false") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
	</table>
</div>
<!-- #################### END: monitor ##################### -->
