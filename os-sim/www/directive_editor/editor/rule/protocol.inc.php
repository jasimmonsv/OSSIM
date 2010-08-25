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
$protocols = split(',', $rule->protocol)
?>

	<!-- #################### protocol ##################### -->
	<table width="<?php
echo $left_table_width; ?>">
		<tr>
			<th>
				<?php
echo gettext("Protocol"); ?>
			</th>
		</tr>
		<!-- ##### first line ##### -->
		<tr>
			<td>
				<!-- ##### any ##### -->
				<?php
$checked = checkIf(in_array("ANY", $protocols) || (in_array("TCP", $protocols) && in_array("UDP", $protocols) && in_array("ICMP", $protocols)));
?>
				<input type="checkbox"
					name="protocol_any"
					id="protocol_any"
					onclick="onClickProtocolAny(<?php
echo $rule->level; ?>)"
					<?php
echo $checked; ?>
				/>&nbsp;ANY&nbsp;&nbsp;&nbsp;
				<!-- ##### tcp ##### -->
				<?php
$checked = checkIf(in_array("ANY", $protocols) || in_array("TCP", $protocols));
?>
				<input type="checkbox"
					name="protocol_tcp"
					id="protocol_tcp"
					onclick="onClickProtocol('protocol_tcp',<?php
echo $rule->level; ?>)"
					<?php
echo $checked; ?>
				/>&nbsp;TCP&nbsp;&nbsp;&nbsp;
				<!-- ##### udp ##### -->
				<?php
$checked = checkIf(in_array("ANY", $protocols) || in_array("UDP", $protocols));
?>
				<input type="checkbox"
					name="protocol_udp"
					id="protocol_udp"
					onclick="onClickProtocol('protocol_udp',<?php
echo $rule->level; ?>)"
					<?php
echo $checked; ?>
				/>&nbsp;UDP&nbsp;&nbsp;&nbsp;
				<!-- ##### icmp ##### -->
				<?php
$checked = checkIf(in_array("ANY", $protocols) || in_array("ICMP", $protocols));
?>
				<input type="checkbox"
					name="protocol_icmp"
					id="protocol_icmp"
					onclick="onClickProtocol('protocol_icmp',<?php
echo $rule->level; ?>)"
					<?php
echo $checked; ?>
				/>&nbsp;ICMP&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
		<!-- ##### second line ##### -->
		<?php
if ($rule->level > 1) {
?>
		<tr>
			<td>
				<!-- ##### :protocol ##### -->
				<?php
    for ($i = 1; $i <= $rule->level - 1; $i++) {
        $checked = checkIf($rule->protocol == 'ANY' || in_array($i . ':PROTOCOL', $protocols));
?>
					<input type="checkbox"
						name="protocol_<?php
        echo $i; ?>"
						id="protocol_<?php
        echo $i; ?>"
						onclick="onClickProtocol(
							'protocol_<?php
        echo $i; ?>',<?php
        echo $rule->level; ?>)"
						<?php
        echo $checked; ?>
					/>&nbsp;<?php
        echo $i . ":PROTOCOL"; ?>&nbsp;&nbsp;&nbsp;
				<?php
    } ?>
			</td>
		</tr>
		<?php
}
?>
	</table>
	<!-- #################### END: protocol ##################### -->
