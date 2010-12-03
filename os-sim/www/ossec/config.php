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
require_once ('classes/Xml_parser.inc');
require_once ('conf/_conf.php');
require_once ('utils.php');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="css/ossec.css" />
	
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		
	<!-- Elastic textarea: -->
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	
	<!-- Own libraries: -->
	<script type='text/javascript' src='utils.js'></script>
	<script type='text/javascript' src='functions.js'></script>
	
	<script type='text/javascript'>
		var messages = new Array();
			messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Loading data ... ")?></span>';
			messages[1]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Saving data ... ")?></span>';
	</script>
	
	<!-- Multiselect: -->
	
    <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/ui.multiselect.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	
	<script type='text/javascript'>
		
		function load_config_tab(tab)
		{
			//Add Load img
			if ($('#cnf_load').length <= 1)
			{
				var load ="<div id='cnf_load'>"+messages[0]+"</div>";
				$("#oss_cnf_container").html(load);
			}
			
			//Remove error message
			if ($('#cnf_message').length >= 1)
				$('#cnf_message').html('');
						
			$.ajax({
				type: "POST",
				url: "ajax/load_config_tab.php",
				data: "tab="+tab,
				success: function(msg){
															
					//Remove load img
					
					if ( $('#cnf_load').length >= 1 )
						$('#cnf_load').remove();
						
					var status = msg.split("###");
															
					if (status[0] == "error")
					{
						$('#cnf_message').addClass("oss_error");
						$('#cnf_message').html(status[1]);
					}
					else
					{
						$("#oss_cnf_container").html(status[1]);	
						
						$('#send').bind('click', function() { save_config_tab(tab); });						
						
						if (tab == "tab1")
						{
							$(".multiselect").multiselect({
								searchDelay: 500,
								dividerLocation: 0.5,
								nodeComparator: function (node1,node2){ return 1; }
							});
						}
						
						
					}

					
						
				}
			});
		}
		
		function save_config_tab(tab)
		{
			//Add Load img
						
			if ($('#cnf_wait_save').length >= 1)
				$('#cnf_wait_save').html('');
			else
				$('#cnf_message').html("<div id='cnf_wait_save'></div>");
			
			$('#cnf_wait_save').html(messages[1]);
			
			var data= "tab="+tab;
			
			if (tab == "tab1")	
				data += "&"+ $('form').serialize();
			
			$.ajax({
				type: "POST",
				url: "ajax/save_config_tab.php",
				data: data,
				success: function(msg){
															
					//Remove load img
					
					if ( $('#cnf_wait_save').length >= 1 )
						$('#cnf_wait_save').remove();
						
					var status = msg.split("###");
															
					if (status[0] == "error")
						$('#cnf_message').addClass("oss_error");
					else
						$('#cnf_message').addClass("oss_info");	
					
					
					$('#cnf_message').html(status[1]);
					
				}
			});
		}
		
		
	
	</script>
	
	<script type='text/javascript'>
		
		$(document).ready(function() {

			/* Tabs */
			
			$("ul.oss_tabs li:first").addClass("active");
								
			//On Click Event
			$("ul.oss_tabs li").click(function(event) { event.preventDefault();});
			
			load_config_tab("tab1");
						
		});
	
	</script>
	
	<style type='text/css'>
	
	.multiselect {
		width: 70%;
		height: 350px;
	}
	
	.actions { width: auto;}
	
	.button {float: none; margin:0px auto 10px auto;}
	
	</style>
	
	

</head>

<body>

<?php include ("../hmenu.php"); ?>

	<div id='container_center'>
	
		<table id='tab_menu'>
			<tr>
				<td id='oss_mcontainer'>
					<ul class='oss_tabs'>
						<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?=_("Rules")?></a></li>
					</ul>
				</td>
			</tr>
		</table>
		
		<table id='tab_container'>
		    <tr>
				<td><div id='cnf_message'></div></td>
			</tr>
			<tr>
				<td id='oss_cnf_container'>
					<div id="cnf_load"><img src="images/loading.gif" border="0" align="absmiddle" alt='Loading'/><span><?php echo _("Loading data ... ")?></span></div>
				</td>
			</tr>	
		</table>
		
	</div>

</body>

</html>

