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
<div id="wizard_13" style="display:none">
<input type="hidden" name="sticky" id="sticky" value="<?php echo $rule->sticky ?>"></input>
	<table class="transparent">
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Sticky"); ?>
			</th>
		</tr>
		<tr><td class="center nobborder"><input type="button" value="None" onclick="document.getElementById('sticky').value = 'None';wizard_next();" style="width:80px<?php if ($rule->sticky == "None") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="true" onclick="document.getElementById('sticky').value = 'true';wizard_next();" style="width:80px<?php if ($rule->sticky == "true" || $rule->sticky == "" || $rule->sticky == "Default") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="false" onclick="document.getElementById('sticky').value = 'false';wizard_next();" style="width:80px<?php if ($rule->sticky == "false") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
	</table>
</div>

<div id="wizard_14" style="display:none">
<input type="hidden" name="sticky_different" id="sticky_different" value="<?php echo $rule->sticky_different ?>"></input>
	<table class="transparent">
		<!-- sticky different -->
		<tr>
			<th style="white-space: nowrap; padding: 5px;font-size:12px">
				<?php echo gettext("Sticky different"); ?>
			</th>
		</tr>
		<tr><td class="center nobborder"><input type="button" value="None" onclick="document.getElementById('sticky_different').value = 'None';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "None") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="PLUGIN_SID" onclick="document.getElementById('sticky_different').value = 'PLUGIN_SID';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "PLUGIN_SID") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="SRC_IP" onclick="document.getElementById('sticky_different').value = 'SRC_IP';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "SRC_IP") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="DST_IP" onclick="document.getElementById('sticky_different').value = 'DST_IP';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "DST_IP") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="SRC_PORT" onclick="document.getElementById('sticky_different').value = 'SRC_PORT';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "SRC_PORT") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="DST_PORT" onclick="document.getElementById('sticky_different').value = 'DST_PORT';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "DST_PORT") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="PROTOCOL" onclick="document.getElementById('sticky_different').value = 'PROTOCOL';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "PROTOCOL") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
		<tr><td class="center nobborder"><input type="button" value="SENSOR" onclick="document.getElementById('sticky_different').value = 'SENSOR';wizard_next();" style="width:80px<?php if ($rule->sticky_different == "SENSOR") { ?>;background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important<?php } ?>"></input></td></tr>
	</table>
</div>
<!-- #################### END: other ##################### -->
