	<!-- #################### network ##################### -->
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
?>
<input type="hidden" name="from" id="from" value=""></input>
<input type="hidden" name="from_list" id="from_list" value=""></input>
<input type="hidden" name="port_from" id="port_from" value=""></input>
<input type="hidden" name="port_from_list" id="port_from_list" value=""></input>
<table class="transparent">
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px">
			<?php echo gettext("Network"); ?>
		</th>
	</tr>
		<!-- ##### from ##### -->
	<tr>
		<th style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("From"); ?>
		</th>
	</tr>
	<tr>
		<td class="nobborder">
			<table class="transparent">
				<tr>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr><th><?php echo _("Host/Network") ?></th></tr>
							<tr>
								<td class="nobborder">
								<select id="fromselect" class="multiselect_from" multiple="multiple" name="fromselect[]" style="display:none;width:450px">
								<?php if (isList($rule->from) && $rule->from != "") { ?>
								
								<?php } ?>
								</select>
								</td>
							</tr>
							<?php
							for ($i = 1; $i <= $rule->level - 1; $i++) {
							    $sublevel = $i . ":SRC_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":SRC_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = $i . ":DST_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":DST_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							}
							?>
						</table>
					</td>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr><th><?php echo _("Port") ?></th></tr>
							<tr>
								<td class="nobborder">
								<select id="fromselect_port" class="multiselect_from_port" multiple="multiple" name="fromselect_port[]" style="display:none;width:450px">
								<?php if (isList($rule->port_from) && $rule->port_from != "") { ?>
								
								<?php } ?>
								</select>
								</td>
							</tr>
							<?php
							for ($i = 1; $i <= $rule->level - 1; $i++) {
							    $sublevel = $i . ":SRC_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":SRC_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = $i . ":DST_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":DST_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							}
							?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<!-- ##### to ##### -->
	<tr>
		<th style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("To"); ?>
		</th>
	</tr>
	<tr>
		<td class="nobborder">
			<table class="transparent">
				<tr>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr><th><?php echo _("Host/Network") ?></th></tr>
							<tr>
								<td class="nobborder">
								<select id="toselect" class="multiselect_to" multiple="multiple" name="toselect[]" style="display:none;width:450px">
								<?php if (isList($rule->to) && $rule->to != "") { ?>
								
								<?php } ?>
								</select>
								</td>
							</tr>
							<?php
							for ($i = 1; $i <= $rule->level - 1; $i++) {
							    $sublevel = $i . ":SRC_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":SRC_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = $i . ":DST_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":DST_IP";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							}
							?>
						</table>
					</td>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr><th><?php echo _("Port") ?></th></tr>
							<tr>
								<td class="nobborder">
								<select id="fromselect_to_port" class="multiselect_to_port" multiple="multiple" name="fromselect_to_port[]" style="display:none;width:450px">
								<?php if (isList($rule->port_to) && $rule->port_to != "") { ?>
								
								<?php } ?>
								</select>
								</td>
							</tr>
							<?php
							for ($i = 1; $i <= $rule->level - 1; $i++) {
							    $sublevel = $i . ":SRC_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":SRC_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = $i . ":DST_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							    $sublevel = "!" . $i . ":DST_PORT";
							    ?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick="document.getElementById('from').value='<?php echo $sublevel ?>'"></td></tr><?php
							}
							?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td class="center nobborder" style="padding-top:10px"><input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="wizard_next()"></td></tr>
</table>
<!-- #################### END: network ##################### -->
