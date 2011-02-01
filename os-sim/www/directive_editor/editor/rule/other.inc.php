	<!-- #################### other ##################### -->
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
<table class="transparent">
	<tr>
		<th style="white-space: nowrap; padding: 5px;font-size:12px" colspan="2">
			<?php echo gettext("Other"); ?>
		</th>
	</tr>
	<!-- ##### interface ##### -->
	<tr>
		<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("interface"); ?>
		</td>
		<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
			<input type="text" style="width: 200px" name="iface" id="iface" value="<?php echo $rule->iface; ?>" title="<?php echo $rule->iface; ?>"/>
		</td>
	</tr>

	<!-- ##### filename ##### -->
	<tr>
		<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("filename"); ?>
		</td>
		<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
			<input type="text" style="width: 200px" name="filename" id="filename" value="<?php echo $rule->filename; ?>" title="<?php echo $rule->filename; ?>"/>
		</td>
	</tr>

	<!-- ##### username ##### -->
	<tr>
		<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("username"); ?>
		</td>
		<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
			<input type="text" style="width: 120px" name="username" id="username" autocomplete="off" value="<?php echo $rule->username; ?>" title="<?php echo $rule->username; ?>"/>
		</td>
	</tr>
	
	<!-- ##### password ##### -->
	<tr>
		<td class="nobborder" style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
			<?php echo gettext("password"); ?>
		</td>
		<td class="nobborder" style="width: 100%; text-align: left;padding-left: 5px; padding-right: 8px">
			<input type="password" style="width: 120px" name="password" id="password" autocomplete="off" value="<?php echo $rule->password; ?>" title="<?php echo $rule->password; ?>"/>
		</td>
	</tr>
	
	<tr><td class="center nobborder" colspan="2" style="padding-top:10px"><input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Next") ?>" onclick="wizard_next();"></td></tr>
</table>
<!-- #################### END: other ##################### -->
