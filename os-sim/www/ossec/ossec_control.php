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
* - match_os()
* Classes list:
*/

require_once ('classes/Session.inc');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="css/ossec.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		
	<script type="text/javascript">
	var messages = new Array();
		messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Processing action...")?> </span>';
	</script>
	
	<script type="text/javascript">
		
		function execute_action(action)
		{
			//Load img
			$("#ossc_result").parent().css('vertical-align', 'middle');
			$("#ossc_result").html(messages[0]);
									
			$.ajax({
				type: "POST",
				url: "ajax/ossec_control_actions.php",
				data: "action="+action,
				success: function(msg){
					
					if ( msg.match("Illegal action") != null )	
					{
						$("#ossc_result").html("<div class='oss_error'>"+msg+"</div>");
					}
					else
					{
						$("#ossc_result").html('');
						$("#ossc_result").parent().css('vertical-align', 'top');
						$("#ossc_result").html("<div class='div_pre'>"+msg+"</div>");
					}
				}
			});
		}
		
		
		$(document).ready(function() {
			
			//Tabs
			$("ul.oss_tabs li:first").addClass("active");
			
			$('.cont_ossc_opt input').bind('click', function() {
				var action = $(this).val();
				execute_action(action);
			});
		});
	
	</script>
	
	<style type='text/css'>
		#oss_control {width: 80%; margin: 20px auto;}
		#oss_control table{ width: 100%;}
		#oss_control td{ 
			-moz-border-radius:4px;
			-webkit-border-radius: 4px;
			-khtml-border-radius: 4px;
			border: solid 1px #D2D2D2;
		}
		
		#oss_control th { height: 20px;}
				
		#ossc_options { width: 60px;}
		div .button {float: none; margin:0px; width: 55px;}
		.cont_ossc_opt {vertical-align: middle; padding: 90px 0px;}
		.cont_ossc_opt div {padding: 5px 0px;}
		
		.div_pre {
			background-color: #FFFFFF;
			border: none;
			font-family: Courier New,Courier, monospace;
			font-size: 12px;
			padding: 10px;
			text-align: left;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		
		#ossc_result {margin: auto; width: 98%;}
		
	</style>
		
</head>
<body>



<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
	

exec ("sudo /var/ossec/bin/ossec-control status", $result);
$result = implode("<br/>", $result);
$result = str_replace("is running", "<span style='font-weight: bold; color:#15B103;'>is running</span>", $result);
$result = str_replace("not running", "<span style='font-weight: bold; color:#E54D4d;'>not running</span>", $result);

	
?>

	<div id='container_center'>

		<table id='tab_menu'>
			<tr>
				<td id='oss_mcontainer'>
					<ul class='oss_tabs'>
						<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?=_("Ossec Control")?></a></li>
					</ul>
				</td>
			</tr>
		</table>
		
		<table id='tab_container' class='oss_control'>
			<tr>
				<td>
					<div id='oss_control'>
						<table>
							<tr>
								<th><?php echo _("Status");?></th>
								<th id='ossc_options'><?php echo _("Actions");?></th>
							</tr>
							<tr>
							<td valign='top'><div id='ossc_result'><div class='div_pre'><?php echo $result;?></div></div></td>
							<td class='cont_ossc_opt'>
								<div><input type='button' class='button' value='<?php echo _("Start");?>'/></div>
								<div><input type='button' class='button' value='<?php echo _("Stop");?>'/></div>
								<div><input type='button' class='button' value='<?php echo _("Restart");?>'/></div>
								<div><input type='button' class='button' value='<?php echo _("Status");?>'/></div>
							</td>		
							</tr>
						</table>
					
					</div>
				</td>
			</tr>
		</table>	
		
	</div>
</body>
</html>

