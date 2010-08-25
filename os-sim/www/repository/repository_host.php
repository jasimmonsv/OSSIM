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
require_once ("classes/Repository.inc");

// DB Connection
//require_once ("ossim_db.inc");
//$db = new ossim_db();
//$conn = $db->connect();
$repository_list = Repository::get_repository_linked($conn, $keyname, $type);
if (count($repository_list) > 0) { ?>
<table width="300">
	<tr><td class="nobborder"><h1 style="margin-bottom: 5px">Linked Documents from Repository</h1></td></tr>
	<?php
    foreach($repository_list as $repository) { ?>
	<tr><td height="2"></td></tr>
	
	<tr>
		<th><a target="topmenu" href="../top.php?option=8&soption=5&url=repository/repository_document.php?id_document=<?php echo $repository['id'] ?>"><?php echo $repository['title'] ?></a></th>
	</tr>
	<tr>
		<td class="nobborder" colspan="2" height="100">
			<div style="overflow:auto;width:300px;height:100px">
			<?php echo $repository['text'] ?>
			</div>
		</td>
	</tr>
	<tr>
		<td class="nobborder">
			<table class="noborder" width="100%" cellspacing=0>
				<tr>
					<td class="left" bgcolor="#CBCBCB"><b><?php echo $repository['user'] ?></b> - <i><?php echo $repository['date'] ?></i></td>
					<?php
        if ($repository['num_atch'] > 0) { ?>
					<td class="nobborder" bgcolor="#CBCBCB" width="10">(<?php echo $repository['num_atch'] ?>)</td>
					<td class="nobborder" bgcolor="#CBCBCB"><a target="topmenu" href="../top.php?option=8&soption=5&url=repository/repository_document.php?id_document=<?php echo $repository['id'] ?>"><img src="../pixmaps/attachment_icon_small.gif" alt="" border="0"></a></td>
					<?php
        } ?>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	<?php
    } ?>
</table>
<?php
} ?>
