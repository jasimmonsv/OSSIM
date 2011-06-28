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
require_once 'classes/Session.inc';
if( !Session::am_i_admin() ) 
	Session::unallowed_section(false);

require_once 'ossim_db.inc';
require_once 'classes/Incident_type.inc';
Session::logcheck("MenuIncidents", "IncidentsTypes");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<META http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<style type='text/css'>
		.pad3 {padding: 3px;}
	</style>
</head>
<body>

<?php include ("../hmenu.php");

if (!empty($_GET['msg']))
{
	$var_tmp="";
	switch($_GET['msg'])
	{
		case 1:
			$var_tmp="New Custom Ticket Type succesfully created";
			break;
		case 2:
			$var_tmp="New Ticket Type succesfully created";
			break;
		case 3:
			$var_tmp="Action type deleted";
			break;
	}
	if ($var_tmp!="")
		Util::print_succesful( _($var_tmp) );
}	

$db   = new ossim_db();
$conn = $db->connect();
?>
    <!-- main table -->
    <table align="center">
<?php 
	$inctype_list = Incident_type::get_list($conn, "");
	
	
	if ( $inctype_list ) { ?>
		<tr>
			<th class='pad3'><?php echo gettext("Ticket type");?></th>
			<th class='pad3'><?php echo gettext("Description");?></th>
			<th class='pad3'><?php echo gettext("Custom");?></th>
			<th class='pad3'><?php echo gettext("Actions");?></th>
		</tr>    
    
		<?php
			foreach($inctype_list as $inctype)
			{
				$custom = (preg_match("/custom/",$inctype->get_keywords())) ? "tick.png" : "cross.png";
				$custom_fields = Incident_custom::get_custom_types($conn,$inctype->get_id());
				$alt = (preg_match("/custom/",$inctype->get_keywords())) ? implode(",",$custom_fields) : "";
		?>
				<tr>
					<td><?php echo $inctype->get_id(); ?></td>
					<td>
					<?php
						if ( "" == $inctype->get_descr() )
							echo " -- ";
						else 
							echo $inctype->get_descr();
						
					?>
					</td>
					<?php
					if (!("Generic" == $inctype->get_id()) && !("Nessus Vulnerability" == $inctype->get_id()))
					{
						echo "<td align='center'><img src='../pixmaps/$custom' title='$alt' border='0'></td>";
						echo "<td><a href=\"modifyincidenttypeform.php?id=" . urlencode($inctype->get_id()) . "\"> <img src='../vulnmeter/images/pencil.png' border='0' title='"._("Modify type")."'/> </a>
								  <a href=\"deleteincidenttype.php?msg='type deleted successfully'&confirm=1&inctype_id=" . urlencode($inctype->get_id()) . "\"> <img src='../vulnmeter/images/delete.gif' border='0' title='"._("Delete type")."'/> </a></td>";
					} 
					else
						echo "<td> -- </td><td> -- </td>";
					
		?>
				</tr>
				
				
				
		<?php
			}
			?>
			<tr>
				<td colspan="4" align="center" style="height:30px" class='noborder'>
					<a href="newincidenttypeform.php" class="buttonlink"><?php echo gettext("New custom Ticket Type"); ?></a>
				</td>
			</tr>
		</table>
		<?php		
			
			

	} 
	else 
	{
		$error = _("Error to connect to the database.  Please, try again.");
		ossim_set_error($error);
		echo "<div style='width:80%; margin:auto;'>".ossim_error()."</div>";
	}
	
?>
		
    
	

</body>
</html>
<?php $db->close($conn); ?> 
