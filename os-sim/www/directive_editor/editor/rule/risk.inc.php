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
<!-- ##### occurrence ##### -->
<div id="wizard_7" style="display:none">	
<input type="hidden" name="occurrence" id="occurrence" value="1"></input>
	<table class="transparent" width="100%">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Ocurrence"); ?>
			</th>
		</tr>
		<tr><td class="center nobborder"><input type="button" value="ANY" onclick="document.getElementById('occurrence').value = 'ANY';wizard_next();" style="width:80px<?php if ($rule->occurrence == "ANY") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<?php
		foreach($occurrence_list as $value) {
		    $selected = selectIf($rule->occurrence != "ANY" && $value == $rule->occurrence);
			?><tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('occurrence').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($selected) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr><?php
		}
		?>
		<tr><td class="center nobborder"><input type="text" style="width:80px" name="aux_occurrence" id="aux_occurrence" value="<?php echo _("Other...") ?>" onfocus="this.value='';document.getElementById('risk_oc_next').style.display=''"></input></td></tr>
		<tr><td class="center nobborder" id="risk_oc_next" style="display:none"><input type="button" value="OK" onclick="document.getElementById('occurrence').value = document.getElementById('aux_occurrence').value;wizard_next();" style="width:60px;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important"></input></td></tr>
	</table>
</div>

<!-- ##### timeout ##### -->
<div id="wizard_8" style="display:none">
<input type="hidden" name="time_out" id="time_out" value=""></input>
	<table class="transparent" width="100%">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Timeout")." ("._("seconds").")"; ?>
			</th>
		</tr>
		<!-- <tr><td class="center nobborder"><input type="button" value="None" onclick="document.getElementById('time_out').value = 'ANY';wizard_next();" style="width:80px<?php if (isAny($rule->time_out)) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr> -->
		<?php
		foreach($timeout_list as $value) {
		    $selected = selectIf($rule->time_out != "None" && $value == $rule->time_out);
			?><tr><td class="center nobborder"><input type="button" value="<?php echo $value ?>" onclick="document.getElementById('time_out').value = '<?php echo $value ?>';wizard_next();" style="width:80px<?php if ($selected) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr><?php
		}
		?>
		<tr><td class="center nobborder"><input type="text" style="width:80px" name="aux_time_out" id="aux_time_out" value="<?php echo _("Other...") ?>" onfocus="this.value='';document.getElementById('risk_timeout_next').style.display=''"></input></td></tr>
		<tr><td class="center nobborder" id="risk_timeout_next" style="display:none"><input type="button" value="OK" onclick="document.getElementById('time_out').value = document.getElementById('aux_time_out').value;wizard_next();" style="width:60px;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important"></input></td></tr>
	</table>
</div>

<!-- ##### reliability ##### -->
<div id="wizard_9" style="display:none">
<input type="hidden" name="reliability" id="reliability" value="<?php echo $rule->reliability ?>"></input>
<input type="hidden" name="reliability_op" id="reliability_op" value="<?php echo $rule->reliability_op ?>"></input>
	<table class="transparent" width="100%">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
				<?php echo gettext("Reliability"); ?>
			</th>
		</tr>
		<tr><td colspan="2" class="nobborder">&middot; <i>Risk = (priority * reliability * asset_value) / 25.</i></td></tr>
		<?php
		$value = ($rule->is_new()) ? 5 : intval(strtr($rule->reliability, '+', ''));
		$first = $rule->reliability{0};
		$selected_plus = selectIf($first != "+");
		for ($i = 0; $i <= 10; $i++) {
		    $selected2 = selectIf($value == $i && !$selected_plus);
		    $selected = selectIf($value == $i && $selected_plus);
		    ?>
		    <tr>
		    	<td class="center nobborder"><input type="button" value="= <?php echo $i ?>" onclick="document.getElementById('reliability').value = '<?php echo $i ?>';document.getElementById('reliability_op').value = '=';wizard_next();" style="width:50px<?php if ($selected) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td>
		    	<?php if ($rule->level > 1) { ?><td class="center nobborder"><input type="button" value="+ <?php echo $i ?>" onclick="document.getElementById('reliability').value = '<?php echo $i ?>';document.getElementById('reliability_op').value = '+';wizard_next();" style="width:50px<?php if ($selected2) { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td><?php } ?>
		    </tr>
		    <?php
		}
		?>
	</table>
</div>
	<!-- #################### END: risk ##################### -->
