	<!-- #################### global properties ##################### -->
<?
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
<div id="wizard_1"<?php if ($add) echo " style='display:none'"?>>
<table width="400" class="transparent">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
				<?php echo gettext("Name for the rule"); ?>
			</th>
		</tr>
		<!-- ##### name ##### -->
		<tr>
			<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
				<input type="text" style="font-size:12px;height:20px;width: 100%"
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
			<td class="nobborder">
				<input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="wizard_next()"></input>
			</td>
		</tr>
	</table>
</div>

<div id="wizard_2" style="display:none">
<?php
$none_checked = 'true';
if (empty($order)) $order = 'id';
$plugin_list = getPluginList('ORDER BY ' . $order);
$plugin_names = array();
foreach($plugin_list as $plugin) {
	$plugin_names[$plugin->get_id()] = $plugin->get_name();
}
?>
<input type="hidden" name="plugin_id" id="plugin_id" value="<?php echo $rule->plugin_id; ?>" onchange="onChangePluginId()"/>
<table width="500" class="transparent">
	<!-- ##### plugin id ##### -->
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px">
			<?php echo gettext("Select a Plugin"); ?>
		</th>
	</tr>
	<?php if ($rule->plugin_id != "") { ?>
	<tr>
		<td><?php echo _("Already selected")?>: <input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="Continue with <?php echo ($plugin_names[$rule->plugin_id] != "") ? $plugin_names[$rule->plugin_id] : $rule->plugin_id ?>" onclick="wizard_next();init_sids(<?php echo $rule->plugin_id ?>)"></input>&nbsp;<?php echo _("or select another one.") ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="nobborder">
			<table class="transparent">
				<tr>
					<th width="110"><a href="plugin_id?order=<?php echo ossim_db::get_order('name', $order) ?>"><?php echo _("Name") ?></a></th>
					<th width="70"><a href="plugin_id?order=<?php echo ossim_db::get_order('type', $order) ?>"><?php echo _("Type") ?></a></th>
					<th><a href="plugin_id?order=<?php echo ossim_db::get_order('type', $order) ?>"><?php echo _("Description") ?></a></th>
				</tr>
				<tr>
					<td class="nobborder" colspan="5">
					<div style="overflow:auto;height:300px;width:500px;border:1px solid #EEEEEE">
						<table class="transparent" width="100%">
						<?php
						$i = 0;
						foreach($plugin_list as $plugin) {
						    $color = ($i%2==0) ? "#FFFFFF" : "#F2F2F2";
							$i++;
						    $plugin_type = $plugin->get_type();
						    if ($plugin_type == '1') $type_name = 'Detector (1)';
						    elseif ($plugin_type == '2') $type_name = 'Monitor (2)';
						    else $type_name = 'Other (' . $plugin_type . ')';
						    if ($plugin_id == $plugin->get_id()) $checked = ' checked';
						    else $checked = '';
						    if ($checked != '') $none_checked = 'false';
						    ?>
						<tr>
						    <td width="110" class="nobborder" bgcolor="<?php echo $color ?>"><a href="" onclick="document.getElementById('plugin_id').value='<?php echo $plugin->get_id() ?>';wizard_next();init_sids(<?php echo $plugin->get_id() ?>,<?php echo ($plugin_type == '2') ? "true" : "false" ?>);return false;"><b><?php echo $plugin->get_name() ?></b></a></td>
						    <td width="70" class="nobborder" bgcolor="<?php echo $color ?>" nowrap><?php echo $type_name ?></td>
						    <td class="nobborder" bgcolor="<?php echo $color ?>"><?php echo $plugin->get_description() ?></td>
						</tr>
						<?php } ?>
						</table>
					</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>

<div id="wizard_3" style="display:none">
<input type="hidden" name="plugin_sid" id="plugin_sid" onchange="onChangePluginSid()" value="">
<input type="hidden" name="plugin_sid_list" id="plugin_sid_list" value="" onchange="onChangePluginSidList()" <?php echo disableIf(!isList($rule->plugin_sid)); ?>/>
<table class="transparent" width="500">
		<!-- ##### plugin sid ##### -->
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px">
			<?php echo gettext("Plugin Signatures"); ?>
		</th>
	</tr>
	<tr>
		<td class="nobborder">
			<table class="transparent">
				<tr>
					<td class="nobborder">
					<select id="pluginsids" class="multiselect_sids" multiple="multiple" name="sids[]" style="display:none;width:1000px">
				    <?
				    if (isList($rule->plugin_sid) && $rule->plugin_sid != "") {
				    	$sids = explode(",",$rule->plugin_sid);
				    	$range = "";
				    	$sin = array();
				    	foreach ($sids as $sid) {
				    		if (preg_match("/(\d+)-(\d+)/",$sid,$found)) {
				    			$range .= " OR (sid BETWEEN ".$found[1]." AND ".$found[2].")"; 
				    		} else { 
				    			$sin[] = $sid;
				    		}
				    	}
				    	if (count($sin)>0) $where = "sid in (".implode(",",$sin).") $range";
				    	else $where = preg_replace("/^ OR /","",$range);
				        $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$id AND ($where)");
				        foreach($plugin_list as $plugin) {
				            $id = $plugin->get_sid();
				            $name = "$id - ".trim($plugin->get_name());
				            if (strlen($name)>73) $name=substr($name,0,70)."...";
				            echo "<option value='$id' selected>$name</option>\n";
				        }
				    }
				    ?>
				    </select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY signature") ?></i></td></tr>
	<tr>
		<td class="center nobborder" style="padding-top:10px">
			<input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="save_sids();wizard_next();">
		</td>
	</tr>
	<?php for ($i = 1; $i <= $rule->level - 1; $i++) {
			$sublevel = $i . ":PLUGIN_SID";
			//echo "<option value=\"$sublevel\">$sublevel</option>";
			?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick=""></td></tr><?php
			$sublevel = "!" . $i . ":PLUGIN_SID";
			?><tr><td class="center nobborder"><input type="button" value="<?php echo $sublevel ?>" onclick=""></td></tr><?php
			//echo "<option value=\"$sublevel\">$sublevel</option>";?>
	<?php } ?>
	<!--
	<tr>
		<td style="width: <?php echo $left_select_width; ?>;text-align: left; padding-left: 5px">
			<select style="width: <?php echo $left_select_width; ?>" name="plugin_sid" id="plugin_sid" onchange="onChangePluginSid()">
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
	</tr>
	-->
</table>
</div>
	<!-- #################### END: global properties ##################### -->
