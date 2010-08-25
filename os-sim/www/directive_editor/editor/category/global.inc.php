	<!-- #################### global properties ##################### -->
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
			<th colspan="6">
				<?php
echo gettext("Global Properties"); ?>
			</th>
		</tr>
		<!-- ##### name ##### -->
		<tr>
			<td style="white-space: nowrap; padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Name"); ?>
			</td>
			<td style="width: 100%; text-align: left;
				padding-left: 5px; padding-right: 8px"
				colspan="6"
			>
				<input type="text" style="width: 100%"
					name="name"
					id="name"
					value="<?php
echo str_replace("'", "", str_replace("\"", "", $category->name)); ?>"
					title="<?php
echo str_replace("'", "", str_replace("\"", "", $category->name)); ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeName('<?php
echo $category->xml_file; ?>')"
					onblur="onChangeName('<?php
echo $category->xml_file; ?>')"
					onfocus="onFocusName()"
				/>
			</td>
		</tr>
		<!-- ##### xml file ##### -->
		<tr>
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("XML File"); ?>
			</td>
			<td style="width: <?php
echo $xml_file_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<input type="text" style="width: <?php
echo $xml_file_width; ?>"
					name="xml_file"
					id="xml_file"
					value="<?php
echo $category->xml_file; ?>"
					title="<?php
echo $category->xml_file; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeXmlFile('<?php
echo $category->xml_file; ?>')"
					onblur="onChangeXmlFile('<?php
echo $category->xml_file; ?>')"
					onfocus="onFocusXmlFile()"
				/>
			</td>
			<!-- ##### mini ##### -->
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Mini"); ?>
			</td>
			<td style="width: <?php
echo $mini_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<input type="text" style="width: <?php
echo $mini_width; ?>"
					name="mini"
					id="mini"
					value="<?php
echo $category->mini; ?>"
					title="<?php
echo $category->mini; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeMini('<?php
echo $category->id; ?>')"
					onblur="onChangeMini('<?php
echo $category->id; ?>')"
				/>
			</td>
			<!-- ##### maxi ##### -->
			<td style="white-space: nowrap;padding-left: 5px; padding-right: 5px">
				<?php
echo gettext("Maxi"); ?>
			</td>
			<td style="width: <?php
echo $maxi_width; ?>;
				text-align: left; padding-left: 5px"
			>
				<input type="text" style="width: <?php
echo $maxi_width; ?>"
					name="maxi"
					id="maxi"
					value="<?php
echo $category->maxi; ?>"
					title="<?php
echo $category->maxi; ?>"
					onkeypress="onKeyPressElt(this,event)"
					onchange="onChangeMaxi('<?php
echo $category->id; ?>')"
					onblur="onChangeMaxi('<?php
echo $category->id; ?>')"
				/>
			</td>
		</tr>
	</table>
	<!-- #################### END: global properties ##################### -->
