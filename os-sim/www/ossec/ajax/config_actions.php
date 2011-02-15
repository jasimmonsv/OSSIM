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

require_once ('classes/Session.inc');
require_once ('classes/Security.inc');

$action = POST('action');

$directory_checks = array(
				"realtime"       => "Realtime",
				"report_changes" => "Report changes", 
				"check_all"      => "Chk all", 
				"check_sum"      => "Chk sum", 
				"check_sha1sum"  => "Chk aha1sum", 
				"check_size"     => "Chk size", 
				"check_owner"    => "Chk owner", 
				"check_group"    => "Chk group", 
				"check_perm"     => "Chk perm"				
			);

$error          = false;
$message_error  = array();

	switch ($action)
	{
		case "add_directory":
			
			$k = uniqid(md5(rand()), false); 
			
			echo "1###<tr class='dir_tr' id='dir_$k'>";
				echo "<td style='text-align: left;'><textarea name='".$k."_value_dir' id='".$k."_value_dir'></textarea></td>";
				echo "<td><table width='100%'>
					  <tr>";
				$i = 0;
				foreach ($directory_checks as $j => $value)
				{
					$i++;
					echo "<td style='width: 50px;'><input type='checkbox' id='".$j."_".$k."_".$i."' name='".$j."_".$k."_".$i."'/></td>";
				}
				echo "</tr>
					  </table></td>";
				echo "<td>
						<a onclick='delete_row(\"dir_$k\", \"delete_directory\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
						<a onclick='add_row(\"dir_$k\", \"$action\");'><img src='images/add.png' align='absmiddle'/></a>
				     </td>";
			echo "</tr>";
		
		break;
		
		case "add_ignore":
			
			$k = uniqid(md5(rand()), false); 
			
			echo "1###<tr class='ign_tr' id='ign_$k'>";
				echo "<td style='text-align: left;'><textarea name='".$k."_value_ign' id='".$k."_value_ign'></textarea></td>";
				echo "<td><input type='checkbox' name='".$k."_type' id='".$k."_type'/></td>";
				echo "<td>
					<a onclick='delete_row(\"ign_$k\", \"delete_ignore\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
					<a onclick='add_row(\"ign_$k\", \"$action\");'><img src='images/add.png' align='absmiddle'/></a>
				</td>";
			echo "</tr>";

		break;
		
		case "add_wentry":
			
			$k = uniqid(md5(rand()), false); 
			
			echo "1###<tr class='went_tr' id='went_$k'>";
				echo "<td style='text-align: left;'><input class='wentry' name='".$k."_value_went' id='".$k."_value_went'/></td>";
				echo "<td>
						<a onclick='delete_row(\"went_$k\", \"delete_wentry\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
						<a onclick='add_row(\"went_$k\", \"$action\");'><img src='images/add.png' align='absmiddle'/></a>	
					 </td>";
			echo "</tr>";

		break;
		
		case "add_reg_ignore":
			
			$k = uniqid(md5(rand()), false); 
			
			echo "1###<tr class='regi_tr' id='regi_$k'>";
				echo "<td style='text-align: left;'><input class='sreg_ignore' name='".$k."_value_regi' id='".$k."_value_regi'/></td>";
				echo "<td>
						<a onclick='delete_row(\"regi_$k\", \"delete_reg_ignore\");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
						<a onclick='add_row(\"regi_$k\", \"$action\");'><img src='images/add.png' align='absmiddle'/></a>	
					 </td>";
			echo "</tr>";
			

		break;
		
		default:
			echo "error###"._("Illegal action");
	}


?>


