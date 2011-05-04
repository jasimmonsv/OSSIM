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
* - incidents_by_status_table()
* - incidents_by_type_table()
* - incidents_by_user_table()
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('ossim_db.inc');
require_once ('classes/Incident.inc');

Session::logcheck("MenuIncidents", "IncidentsReport");

$db   =  new ossim_db();
$conn =  $db->connect();

$user =  Session::get_session_user();


$tickets_by_status    = Incident::incidents_by_status($conn, null, $user);
$tickets_by_type      = Incident::incidents_by_type($conn, null, $user);
$tickets_by_user      = Incident::incidents_by_user($conn, true, null, $user); 

/*echo "<pre>";
	print_r($tickets_by_status);
echo "</pre>";*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<style type="text/css">
		body,html { height: 100%; }
		
		.container_table { 
			border: none !important;
			height: 100%;
			margin: auto;
			text-align: center;
			background-color: #FFFFFF; 
			width: 820px;
		}
		
		
		.td_container {
			width: 270px;
			height: 100px;
			background-color: #FAFAFA;
			border: 1px solid #BBBBBB;
			-moz-border-radius:8px;
			-webkit-border-radius: 8px;
			-khtml-border-radius: 8px;
		}
		
		table {
			border: none;
			background-color: transparent;
		}
		
	</style>
</head>
<body>
<?php include ("../hmenu.php"); ?>

<table class='container_table' cellpadding='0' cellspacing='0'>
	<tr>
		<td valign='top' class="headerpr"><?php echo gettext("Tickets by status");?></td>
		<td valign='top' width='15' class='nobborder'  height='100%' width='270px'>&nbsp;</td>
		<td class="headerpr"><?php echo gettext("Tickets by type");?></td>
		<td valign='top' width='15' class='nobborder'  height='100%'width='270px'>&nbsp;</td>
		<td class="headerpr"><?php echo gettext("Tickets by user in charge"); ?></td>
	</tr>
	<tr>
		<?php 
		
			$valign_ts = ( count($tickets_by_status) > 0 )   ? "top" : "middle";
			$valign_tt = ( count($tickets_by_type) > 0 )     ? "top" : "middle";
			$valign_tu = ( count($tickets_by_user) > 0 )     ? "top" : "middle";
		?>
		
		<td valign='<?php echo $valign_ts?>' class='td_container'><?php incidents_by_status_table($tickets_by_status);?></td>
		<td width='15' class='nobborder' height='100%' width='270px'>&nbsp;</td>
		<td valign='<?php echo $valign_tu?>' class='td_container'><?php incidents_by_user_table($tickets_by_user);?></td>
		<td width='15' class='nobborder' height='100%'width='270px'>&nbsp;</td>
		<td valign='<?php echo $valign_tt?>' class='td_container'><?php incidents_by_type_table($tickets_by_type);?></td>
	</tr>
	
	<tr><td  valign='top' class='noborder'>&nbsp;</td></tr>
</table>


<table class="container_table" cellpadding="0" cellspacing="0">
		
	<tr><td class="headerpr"><?php echo _("Closed Tickets By Month") ?></td></tr>	
	
	<tr>
		<td class="td_container"><iframe src="../panel/tickets.php?type=ticketsClosedByMonth" frameborder="0" style="width:99%;height:300px;"></iframe></td>
	</tr>

	<tr><td height="20" class="nobborder"></td></tr>
	
	<tr><td class="headerpr"><?php echo _("Ticket Resolution Time"); ?></td></tr>
	
	<tr>
		<td class="td_container"><iframe src="../panel/tickets.php?type=ticketResolutionTime" frameborder="0" style="width:99%;height:300px;"></iframe></td>
	</tr>
	
</table>

<br/><br/>

</body>
</html>

<?php


function incidents_by_status_table($tickets_by_status) 
{
    ?>		
		<table align="center" width="100%">
			<?php
			
			if ( count($tickets_by_status) > 0 ) 
			{
				?>
				<tr>
					<th><?php echo gettext("Ticket Status") ?></th>
					<th><?php echo gettext("Ocurrences") ?></th>
				</tr>
				
				<?php
				foreach($tickets_by_status as $status => $occurrences)
				{
					?>
					<tr>
						<td><?php Incident::colorize_status($status) ?></td>
						<td><?php echo $occurrences ?></td>
					</tr>
					<?php
				}
				?>	
				<tr>
					<td colspan="2" class="nobborder">
						<!--<iframe src="graphs/incidents_pie_graph.php?by=status" frameborder="0" style="width:99%;height:400px;"></iframe>-->
						<iframe src="../panel/tickets.php?type=ticketStatus&legend=s&height=470" frameborder="0" style="width:99%;height:470px;"></iframe>
				    </td>
				</tr>
			<?php	
			} 
			else 
			{
				echo "<tr><td style='border-bottom:none;' valign='middle'>"._("No Data Available")."</td></tr>";
			}
		?>
		</table>
<?php
}

function incidents_by_type_table($tickets_by_type) {
?>
    <table align="center" width="100%">
		
		<?php
			if ( count($tickets_by_type) > 0 ) 
			{
				?>
					<tr>
						<th><?php echo gettext("Ticket type") ?></th>
						<th><?php echo gettext("Ocurrences") ?></th>
					</tr>
					
				<?php
				foreach($tickets_by_type as $type => $occurrences)
				{
					?>
					<tr>
						<td style="text-align:left;"><?php echo $type ?></td>
						<td><?php echo $occurrences ?></td>
					</tr>
					<?php
				}
				
				?>
				
				<tr>
					<td colspan="2" class="nobborder">
						<!--<iframe src="graphs/incidents_pie_graph.php?by=type" frameborder="0" style="width:99%;height:400px;"></iframe>-->
						<iframe src="../panel/tickets.php?type=ticketTypes&legend=s&height=470" frameborder="0" style="width:99%;height:470px;"></iframe>
        			</td>
				</tr>
				
				<?php
			}
			else 
			{
				echo "<tr><td style='padding: 20px 0px; border-bottom:none;'>"._("No Data Available")."</td></tr>";
			}
			?>
    </table>
<?php
}


function incidents_by_user_table($tickets_by_user) {
?>
    <table align="center" width="100%">
		
		<?php
						
		if ( count($tickets_by_user) > 0 ) 
		{
			?>
				<tr>
					<th><?php echo gettext("User in charge") ?></th>
					<th><?php echo gettext("Ocurrences") ?></th>
				</tr>
				
			<?php
			foreach($tickets_by_user as $user => $occurrences) 
			{
				$user = ( strlen($user) > 28 ) ? substr($user, 0, 25)."[...]" : $user;
				?>
				<tr>
					<td><?php echo $user ?></td>
					<td><?php echo $occurrences ?></td>
				</tr>
				<?php
			}
			
			?>
			
			<tr>
				<td colspan="2" class="nobborder">
					<!--<iframe src="graphs/incidents_pie_graph.php?by=user" frameborder="0" style="width:99%;height:400px;"></iframe>-->
					<iframe src="../panel/tickets.php?type=openedTicketsByUser&legend=s&height=470" frameborder="0" style="width:99%;height:470px;"></iframe>
				</td>
			</tr>
			
		<?php
		}
		else 
		{
			echo "<tr><td style='padding: 20px 0px; border-bottom:none;'>"._("No Data Available")."</td></tr>";
		}
		?>
		
    </table>
    <?php
}
?>

