	<!-- #################### global properties ##################### -->
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
	<table width="100%">
		<tr>
			<th>
				<?php echo gettext("Global Properties"); ?>
			</th>
		</tr>
		<!-- ##### name ##### -->
		<tr><td style="text-align:left;border:0px;padding-bottom:10px;padding-left:5px"><?php echo _("Name of <b>group</b>:")?></td></tr>
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px;border:0px;text-align:left">
				<?php
				$name = "";
				if ($group->name == "") $name = "New group";
				else $name = str_replace("'", "", str_replace("\"", "", $group->name));
				?>
				<input type="text" style="width: 100%"
					name="name"
					id="name"
					value="<?php print $name; ?>"
					title="<?php print $name; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeName('<?php print $_GET['name']; ?>')"
					onblur="onChangeName('<?php print $_GET['name']; ?>')"
					onfocus="onFocusName()"/>
			</td>
		</tr>
		<!-- ##### list of directives ##### -->
		<tr><td style="border:0px">&nbsp;</td></tr>
		<tr>
			<th>
				<?php echo gettext("Group directives"); ?>
			</th>
		</tr>
		<tr>
			<td style="padding-top:10px">
				<div style="height:250px;overflow:auto">
					<table width="100%">
						<tr>
							<th width="70px">
								<a href="" onclick="onClickAll();return false"><?php echo _("Check All")?></a>
							</th>
							<th>
								<?php echo gettext("Id"); ?>
							</th>
							<th>
								<?php echo gettext("Name"); ?>
							</th>
						</tr>
						<?php
						$none_checked = 'true';
						$default_checked = '';
						foreach($table as $cle => $valeur) {
						    if (in_array($cle, split(',', $list))) {
						        $checked = ($default_checked == '') ? ' checked="checked"' : '';
						    } else {
						        $checked = $default_checked;
						    }
						    if ($checked != '') $none_checked = 'false';
						?>
						<tr>
							<td>
								<input type="checkbox"
									name="chk"
									value="<?php echo $cle; ?>"
									<?php echo $checked; ?> 
									onclick="check_directive(this.value,this.checked)">
							</td>
							<td><?php echo $cle; ?></td>
							<td style="background: #eeeeee">
								<?php echo $valeur; ?>
							</td>
						</tr>
						<?php
						} ?>
					</table>
				</div>
			</td>
		</tr>
	</table>
	<!-- #################### END: global properties ##################### -->
