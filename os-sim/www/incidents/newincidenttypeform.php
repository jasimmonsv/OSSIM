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
require_once ('classes/Session.inc');
Session::logcheck("MenuIncidents", "IncidentsTypes");
require_once ("ossim_db.inc");
require_once ('classes/Incident_type.inc');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('textarea').elastic();
		});
	</script>
	
	<style type='text/css'>
		input[type='text'], textarea { width: 98%;}
		textarea {height: 40px;}
	</style>
</head>
<body>

<?php
include ("../hmenu.php"); ?>

<form method="post" action="newincidenttype.php">
	
	<input type="hidden" name="insert" value="insert"/>

	<table align="center">
		<tr>
			<th><?php echo gettext("Type id"); ?></th>
			<td class="left"><input type="text" id="type_id" name="id"  size="30"/></td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Description"); ?> </th>
			<td class="left">
				<textarea id="type_descr" name="descr"></textarea>
			</td>
		</tr>
		
		<tr>
			<th> <?php echo gettext("Custom"); ?> </th>
			<td class="left">
				<input type="checkbox" name="custom" value="1"/>
			</td>
		</tr>  
		
		<tr>
			<td colspan="2" align="center" valign="top" class='noborder'>
				<input type="submit" value="<?php echo _("OK")?>" class="button"/>
				<input type="reset" value="<?php echo _("Reset")?>" class="button"/>
			</td>
		</tr>
	</table>
	
</form>

</body>
</html>

