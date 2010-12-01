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
<input type="hidden" name="to" id="to" value=""></input>
<input type="hidden" name="to_list" id="to_list" value=""></input>
<input type="hidden" name="port_to" id="port_to" value=""></input>
<table class="transparent">
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
			<?php echo gettext("Network"); ?>
		</th>
	</tr>
	<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY asset") ?></i></td></tr>
	<tr>
		<td class="nobborder" valign="top">
			<table class="transparent">
				<!-- ##### from ##### -->
				<tr>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr>
								<th><?php echo _("From Host/Network") ?></th>
							</tr>
							<tr>
								<td class="nobborder">
								<div id="from_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->from)) ? "hidden" : "visible" ?>">
								<select id="fromselect" class="multiselect_from" multiple="multiple" name="fromselect[]" style="display:none;width:450px">
								<?php if (isList($rule->from) && $rule->from != "" && !preg_match("/\:...\_IP/",$rule->from)) { ?>
								<?php
								$from_list = $rule->from;
								foreach ($host_list as $host) {
								    $hostname = $host->get_hostname();
								    $ip = $host->get_ip();
								    if (in_array($ip, split(',', $from_list))) {
								        echo "<option value='$ip' selected>$hostname</option>\n";
								    }
								}
								foreach ($net_list as $net) {
								    $netname = $net->get_name();
	   								$ips = $net->get_ips();
								    if (in_array($ips, split(',', $from_list))) {
								        echo "<option value='$ips' selected>$netname</option>\n";
								    }
								}
								}
								?>
								</select>
								</div>
								</td>
							</tr>
							<?php if ($rule->level > 1) { ?>
							<tr>
								<td class="center nobborder">
								From a parent rule: <select name="from" id="from" style="width:180px" onchange="onChangePortSelectBox('from',this.value)">
								<?php
								echo "<option value=\"LIST\"></option>";
								for ($i = 1; $i <= $rule->level - 1; $i++) {
								    $sublevel = $i . ":SRC_IP";
								    $selected = ($rule->from == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
								    $sublevel = "!" . $i . ":SRC_IP";
								    $selected = ($rule->from == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
								    $sublevel = $i . ":DST_IP";
								    $selected = ($rule->from == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
								    $sublevel = "!" . $i . ":DST_IP";
								    $selected = ($rule->from == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
								}
								?>
								</select>
								</td>
							</tr>
							<?php } else { ?>
							<input type="hidden" name="from" id="from" value="LIST"></input>
							<?php } ?>
						</table>
					</td>
				</tr>
				<tr><th><?php echo _("Port") ?></th></tr>
				<tr>
					<td class="center nobborder">
						<?php if ($rule->level > 1) { ?>
						From a parent rule: <select style="width:180px" name="port_from" id="port_from" onchange="onChangePortSelectBox('port_from',this.value)">
						<?php
						echo "<option value=\"LIST\"></option>";
						for ($i = 1; $i <= $rule->level - 1; $i++) {
						    $sublevel = $i . ":SRC_PORT";
						    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
						    $sublevel = "!" . $i . ":SRC_PORT";
						    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
						    $sublevel = $i . ":DST_PORT";
						    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
						    $sublevel = "!" . $i . ":DST_PORT";
						    $selected = ($rule->port_from == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
						}
						?>
						</select>&nbsp;
						<?php } else { ?>
						<input type="hidden" name="port_from" id="port_from" value="LIST"></input>
						<?php } ?>
						<div id="port_from_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_from)) ? "none" : "inline" ?>"><input type="text" name="port_from_list" id="port_from_list" value="<?php echo $rule->port_from ?>"></input></div>
					</td>
				</tr>
			</table>
		</td>
	
		<td class="nobborder" valign="top">
			<table class="transparent">
				<!-- ##### to ##### -->
				<tr>
					<td class="nobborder" valign="top">
						<table class="transparent">
							<tr>
								<th><?php echo _("To Host/Network") ?></th>
							</tr>
							<tr>
								<td class="nobborder">
								<div id="to_input" style="visibility:<?php echo (preg_match("/\:...\_IP/",$rule->to)) ? "hidden" : "visible" ?>">
								<select id="toselect" class="multiselect_to" multiple="multiple" name="toselect[]" style="display:none;width:450px">
								<?php if (isList($rule->to) && $rule->to != "" && !preg_match("/\:...\_IP/",$rule->to)) { ?>
								<?php
								$to_list = $rule->to;
								foreach ($host_list as $host) {
								    $hostname = $host->get_hostname();
								    $ip = $host->get_ip();
								    if (in_array($ip, split(',', $to_list))) {
								        echo "<option value='$ip' selected>$hostname</option>\n";
								    }
								}
								foreach ($net_list as $host) {
								    $netname = $net->get_name();
	   								$ips = $net->get_ips();
								    if (in_array($ips, split(',', $to_list))) {
								        echo "<option value='$ips' selected>$netname</option>\n";
								    }
								}
								} ?>
								</select>
								</div>
								</td>
							</tr>
							<?php if ($rule->level > 1) { ?>
							<tr>
								<td class="center nobborder">
								From a parent rule: <select name="to" id="to" style="width:180px" onchange="onChangePortSelectBox('to',this.value)">
								<?php
								echo "<option value=\"LIST\"></option>";
								for ($i = 1; $i <= $rule->level - 1; $i++) {
								    $sublevel = $i . ":SRC_IP";
								    $selected = ($rule->to == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>Source IP from level $i</option>";
								    $sublevel = "!" . $i . ":SRC_IP";
								    $selected = ($rule->to == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>!Source IP from level $i</option>";
								    $sublevel = $i . ":DST_IP";
								    $selected = ($rule->to == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>Destination IP from level $i</option>";
								    $sublevel = "!" . $i . ":DST_IP";
								    $selected = ($rule->to == $sublevel) ? " selected" : "";
								    echo "<option value=\"$sublevel\"$selected>!Destination IP from level $i</option>";
								}
								?>
								</select>
								</td>
							</tr>
							<?php } else { ?>
							<input type="hidden" name="to" id="to" value="LIST"></input>
							<?php } ?>
						</table>
					</td>
				</tr>
				<tr><th><?php echo _("Port") ?></th></tr>
				<tr>
					<td class="center nobborder">
						<?php if ($rule->level > 1) { ?>
						From a parent rule: <select style="width:180px" name="port_to" id="port_to" onchange="onChangePortSelectBox('port_to',this.value)">
						<?php
						echo "<option value=\"LIST\"></option>";
						for ($i = 1; $i <= $rule->level - 1; $i++) {
						    $sublevel = $i . ":SRC_PORT";
						    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>Source Port from level $i</option>";
						    $sublevel = "!" . $i . ":SRC_PORT";
						    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>!Source Port from level $i</option>";
						    $sublevel = $i . ":DST_PORT";
						    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>Destination Port from level $i</option>";
						    $sublevel = "!" . $i . ":DST_PORT";
						    $selected = ($rule->port_to == $sublevel) ? " selected" : "";
						    echo "<option value=\"$sublevel\"$selected>!Destination Port from level $i</option>";
						}
						?>
						</select>&nbsp;
						<?php } else { ?>
						<input type="hidden" name="port_to" id="port_to" value="LIST"></input>
						<?php } ?>
						<div id="port_to_input" style="display:<?php echo (preg_match("/\_PORT/",$rule->port_to)) ? "none" : "inline" ?>"><input type="text" name="port_to_list" id="port_to_list" value="<?php echo $rule->port_to ?>"></input></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td colspan="2" class="center nobborder" style="padding-top:10px"><input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="wizard_next()"></td></tr>
</table>
<!-- #################### END: network ##################### -->
