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
//$directive_xml = preg_replace("/.*\//","",$_SESSION['XML_FILE']);
if ($directive_xml == "") {
	$directive_xml = GET('xml_file');
	ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
	if (ossim_error()) {
	    die(ossim_error());
	}
}
?>	
	<!-- #################### global properties ##################### -->
	<table width="<?php echo $left_table_width; ?>" class="transparent">
		<tr>
		<td class="nobborder">
		
		<!-- ##### name ##### -->
		<div id="wizard_1">
		<table class="transparent" width="400">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
				<?php echo gettext("Name for the directive"); ?>
			</th>
		</tr>
		<tr>
			<td style="width: 100%; text-align: left; padding-left: 5px; padding-right: 8px;border:0px">
				<input type="text" style="width: 100%;height:20px;font-size:13px" name="name" id="name" value="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" title="<?php echo str_replace("'", "", str_replace("\"", "", $directive->name)); ?>" onkeypress="onKeyPressElt(this,event)" onchange="onChangeName()" onblur="onChangeName()" onfocus="onFocusName()">
			</td>
			<td class="nobborder">
				<input type="button" value="<?php echo _("Next") ?>" onclick="wizard_next()"></input>
			</td>
		</tr>
		</table>
		</div>
		
		<!-- ##### id ##### -->
		<div id="wizard_2" style="display:none">
		<? $categories = unserialize($_SESSION['categories']);
		// min IDs in range
		foreach($categories as $category) {
		?><input type="hidden" name="<?=$category->xml_file."_mini"?>" id="<?=$category->xml_file."_mini"?>" value="<?=$category->mini?>"><?
		}
		?>
		<input type="hidden" name="category_old" id="category_old" value="<?=$directive_xml?>">
		<input type="hidden" name="category" id="category" value="">
		<input type="hidden" name="iddir_old" id="iddir_old" value="<?=$directive->id?>">
		<input type="hidden" style="width: <?php echo $id_width; ?>" name="iddir" id="iddir" value="<?php echo $directive->id; ?>" title="<?php echo $directive->id; ?>" onkeypress="onKeyPressElt(this,event)" onchange="onChangeId(<?php echo $directive->id; ?>)" onblur="onChangeId(<?php echo $directive->id; ?>)"/>
		<table class="transparent" width="200">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Category"); ?>
			</th>
		</tr>
		<?php foreach ($categories as $category) { ?>
		<tr><td class="center nobborder"><input type="button" style="width:100%<?php echo ($category->xml_file == $directive_xml) ? ";background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" : "";?>" value="<?php echo $category->name ?>" onclick="document.getElementById('category').value='<?php echo $category->xml_file ?>';onChangeCategory(<?php echo $directive->id; ?>);wizard_next();"></input></td></tr>
		<?php } ?>
		</table>
		</div>
		
		<div id="wizard_3" style="display:none">
		<input type="hidden" name="priority" id="priority" value="<?php echo $directive->priority ?>"></input>
		<table class="transparent" width="100">
			<tr>
				<th style="white-space: nowrap; padding: 5px;font-size:12px">
					<?php echo gettext("Priority"); ?>
				</th>
			</tr>
			<?php for ($i = 0; $i <= 5; $i++) { ?>
			<tr><td class="center nobborder"><input type="button" style="width:100%<?php if ($i == 3) echo ";background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" ?>" value="<?php echo $i ?>" onclick="document.getElementById('priority').value='<?php echo $i ?>';wizard_next();"></input></td></tr>
			<?php } ?>
		</table>
		</div>
		</td>
		</tr>
		
		<!-- ##### list of groups ##### -->
		<!--
		<tr><td colspan="5" style="border:0px">&nbsp;</td></tr>
		<tr><th colspan="5"><?php echo _("Groups")?></th></tr>
		<tr>
			<td colspan="5">
				<div style="height:300px;overflow:auto">
					<table width="100%">
						<tr>
							<th width="70px">
								<a href="" onclick="onClickAll();return false"><?php echo _("Check All")?></a>
							</th>
							<th>
								<?php echo gettext("Name"); ?>
							</th>
							<th>
								<?php echo gettext("Directives"); ?>
							</th>
						</tr>
						<?php
						$default_checked = '';
						foreach($groups as $group) {
						    if (in_array($group->name, split(',', $list))) {
						        $checked = ($default_checked == '') ? ' checked="checked"' : '';
						    } else {
						        $checked = $default_checked;
						    }
						    $list_dir = "";
						    foreach($group->list as $dir) {
						        if ($list_dir != "") $list_dir.= "<br>";
						        $list_dir.= $dir . " : " . $table[$dir];
						    }
						?>
						<tr>
							<td>
								<input type="checkbox"
									name="chk"
									onclick="check_group(this.value,this.checked)"
									value="<?php echo $group->name; ?>"
									<?php echo $checked; ?>>
							</td>
							<td style="background: #eeeeee">
								<?php echo $group->name; ?>
							</td>
							<td style="text-align:left; background: #eeeeee">
								<?php echo $list_dir; ?>
							</td>
						</tr>
						<?php
						} ?>
					</table>
				</div>
			</td>
		</tr>
		-->
	</table>
	<!-- #################### END: global properties ##################### -->
