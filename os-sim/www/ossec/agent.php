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
require_once ('utils.php');

$agents = array();
exec ( "sudo /var/ossec/bin/agent_control -ls", $agents, $ret);



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
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	
	<script type="text/javascript">
		
		function show_agent(id)
		{
			if ( $("#"+id).hasClass("visible") )
			{
				$("#"+id).show();
				$("#"+id).removeClass("visible");
				$("#"+id).addClass("no_visible");
			}
			else
			{
				$("#"+id).hide();
				$("#"+id).removeClass("no_visible");
				$("#"+id).addClass("visible");
			}
		}
		
		
		function add_agent()
		{
			var form_id = $('form[method="post"]').attr("id");
			
			$(".oss_load").html(messages[0]);
						
			$.ajax({
				type: "POST",
				url: "ajax/agent_actions.php",
				data: $('#'+form_id).serialize() + "&action=add_agent",
				success: function(html){
					var status = html.split("###");
					if ( status[0] == "error")
					{
						$(".oss_load").html('');
						
						if ( status[1].match("<br/>") == null )
							var style= '';
						else
						    var style = "class='error_left'";
						
						$(".info").html("<div class='oss_error'><div "+style+">"+status[1]+"</div></div>");
						$(".info").fadeIn(2000);
					}
					else
					{
						$(".oss_load").html('');
						
						if ( $('#agent_table tr').length == 1 )
							$('#agent_table').html(status[3]);	
						else
							$('#agent_table tr:last').after(status[3]);					
												
						$('#cont_agent_'+status[2]+' .agent_actions a').bind('click', function() {
							var id = $(this).attr("id");
							get_action(id);
						});
						
						$('#agent_'+status[2]+ ' .agent_id').bind('click', function() {
							var id   = $(this).text();
							var src  = $(this).find("img").attr("src");
							var src1 = "../pixmaps/minus-small.png";
							var src2 = "../pixmaps/plus-small.png";
							
							if (src == src1)
							{
								$("#minfo_"+id).css('display', 'none');
								$(this).find("img").attr("src", src2);
							}
							else
							{
								$("#minfo_"+id).css('display', '');
								$(this).find("img").attr("src", src1);
							}
										
						});
												
						$(".info").html("<div class='oss_success'>"+status[1]+"</div>");
						$(".info").fadeIn(4000);
					}
				}
			});
		}
		
		function get_action(id)
		{
			var action = null;
			if ( id.match("_key##") != null )	
				send_action(id, 'extract_key');
			else if ( id.match("_del##") != null )
				send_action(id, 'delete_agent');
			else if ( id.match("_check##") != null )
				send_action(id, 'check_agent');	
			else 
			{
				if ( id.match("_restart##") != null )
					send_action(id, 'restart_agent');
			}
		}
		
		function send_action(id, action)
		{
			var id = id.split("##")
			
			//Load img
			$(".oss_load").html(messages[1]);
			
			$.ajax({
				type: "POST",
				url: "ajax/agent_actions.php",
				data: "id="+ id[1] + "&action="+action,
				success: function(html){
					var status = html.split("###");
					
					if ( status[0] == "error")
					{
						$(".oss_load").css('display', 'none');
						$(".info").html("<div class='oss_error'>"+status[1]+"</div>");
						$(".info").fadeIn(4000);
					}
					else
					{
						$(".oss_load").html('');
						switch (action){
							case "extract_key":
								$(".info").html("<div class='oss_info'>"+status[1]+"</div>");
								$(".info").fadeIn(4000);
							break;
							
							case "delete_agent":
								$("#agent_"+id[1]).parent().remove();
								$(".info").html("<div class='oss_success'>"+status[1]+"</div>"); 
								$(".info").fadeIn(4000);
							break;
							
							case "check_agent":
								$(".info").html("<div class='oss_success'>"+status[1]+"</div>"); 
								$(".info").fadeIn(4000);
							break;
							
							case "restart_agent":
								$(".info").html("<div class='oss_success'>"+status[1]+"</div>");
								$(".info").fadeIn(4000);
							break;
						}
					}
				}
			});
		}
		
		$(document).ready(function() {
			
			//Tabs
			$("ul.oss_tabs li:first").addClass("active");
			
			$('#show_agent').bind('click', function() { show_agent("cont_add_agent") });
			$('#send').bind('click', function() { add_agent() });
			
			$("#agent_table tr[id^='cont_agent_']").each(function(index) {
				
				if (index % 2 == 0)
					$(this).css("background-color", "#EEEEEE");
			});
			
			$("#agent_table tr[id^='minfo_']").each(function(index) {
				
				if (index % 2 != 0)
					$(this).css("background-color", "#EEEEEE");
			});		
						
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "ajax/agent_actions.php");
			});
			
			$('#agent_table .agent_actions a').bind('click', function() {
				var id = $(this).attr("id");
				get_action(id);
			});
			
			$('#agent_table .agent_id').bind('click', function() {
				var id = $(this).text();
				var src  = $(this).find("img").attr("src");
				var src1 = "../pixmaps/minus-small.png";
				var src2 = "../pixmaps/plus-small.png";
				if (src == src1)
				{
					$("#minfo_"+id).css('display', 'none');
					$(this).find("img").attr("src", src2);
				}
				else
				{
					$("#minfo_"+id).css('display', '');
					$(this).find("img").attr("src", src1);
				}
							
			});
		});
				
		
	</script>
	
	<script type="text/javascript">
	var messages = new Array();
		messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Adding agent... ")?></span>';
		messages[1]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Processing action... ")?></span>';
	</script>
	
	<style type='text/css'>
		a {cursor:pointer; text-decoration: none !important;}			
		input[type='text'] {width: 90%; height: 20px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bborder_none { border-bottom: none !important; background-color: #FFFFFF !important;}
		
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		.lbutton, .lbutton:hover, input.lbutton:hover  {margin-right: 0px;}
		.right {text-align: right; padding: 3px 0px;}
		 div .button {float: none !important; margin-top: 0px;}
		.load { height: 25px; margin: auto;}
		td.center {text-align: center !important;}
		.notice { position:relative !important; margin: auto; padding-top: 3px;}
	</style>
</head>
<body>



<?php include ("../hmenu.php"); ?>

<div id='container_center'>

	<table id='tab_menu'>
		<tr>
			<td id='oss_mcontainer'>
				<ul class='oss_tabs'>
					<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?=_("Agent Control")?></a></li>
				</ul>
			</td>
		</tr>
	</table>
	
	<table id='tab_container'>
		<tr class='nobborder'><td><div class='cont_oss_load'><div class='oss_load'></div></div></td></tr>
		<tr>
			<td>
				<table id='agent_table'>
										
					<?php
											
						if ( !empty ($agents) )
						{
							?>
							<tr>
								<th style='width: 100px;'><?php echo _("ID")?></th>
								<th><?php echo _("Name")?></th>
								<th><?php echo _("IP")?></th>
								<th><?php echo _("Status")?></th>
								<th class='agent_actions'><?php echo _("Actions")?></th>
							</tr>
							
							<?php
											
							foreach ($agents as $k => $agent)
							{
								if ( empty($agent) )
									continue;
									
								$more_info = array();
								$ret       = null;
								
								$agent = explode(",", $agent);
								exec ( "sudo /var/ossec/bin/agent_control -i ".$agent[0]." -s", $more_info, $ret);
								
								$more_info = ( $ret !== 0 ) ? _("Information from agent not available") : explode(",",$more_info[0]);
								
								echo "<tr id='cont_agent_".$agent[0]."'>
										<td id='agent_".$agent[0]."'><a class='agent_id'><img src='../pixmaps/plus-small.png' alt='More info' align='absmiddle'/>".$agent[0]."</a></td>
										<td>".$agent[1]."</td>
										<td>".$agent[2]."</td>
										<td>".$agent[3]."</td>
										<td class='agent_actions center'>".get_actions($agent)."</td>
									</tr>
									<tr id='minfo_".$agent[0]."' style='display:none;'>
										<td colspan='5'>";
											
											if ( !is_array($more_info) )
											{
												echo "<div style='padding:5px; color: #D8000C; text-align:center;'>$more_info</div>";
											}
											else
											{
												echo "<div style='padding: 3px 3px 5px 5px; font-weight: bold;'>"._("Agent information").":</div>";
												
												echo "<div style='float:left; width: 170px; font-weight: bold; padding:0px 3px 5px 15px;'>
														<span>"._("Agent ID").":</span><br/> 
														<span>"._("Agent Name").":</span><br/>
														<span>"._("IP address").":</span><br/>
														<span>"._("Status").":</span><br/><br/>
														<span>"._("Operating system").":</span><br/>
														<span>"._("Client version").":</span><br/>
														<span>"._("Last keep alive").":</span><br/><br/>
														<span>"._("Syscheck last started at").":</span><br/>
														<span>"._("Rootcheck last started at").":</span><br/>
												</div>";
												
												echo "<div style='float:left; width: auto; padding:0px 3px 5px 15px;'>
														<span>".$more_info[0]."</span><br/>  
														<span>".$more_info[1]."</span><br/>
														<span>".$more_info[2]."</span><br/>
														<span>".$more_info[3]."</span><br/><br/>
														<span>".$more_info[4]."</span><br/>
														<span>".$more_info[5]."</span><br/>
														<span>".$more_info[6]."</span><br/><br/>
														<span>".$more_info[7]."</span><br/>
														<span>".$more_info[8]."</span><br/>
													 </div>
												</div>";
												
											}
								echo "</td>
									</tr>";
							}
						}
						else
						{
							if ($ret === 0)
							{
								$txt   = _("No agents available");
								$class = "oss_info";
							}
							else
							{
								$txt   = _("You don't have execute permissions");
								$class = "oss_error";
								$error = true;
							}
							echo "<tr><td colspan='5' class='no_agent bborder_none'><div class='$class info_agent'>$txt</div></td></tr>";
						}
						
					?>
				</table>
				
				<?php if ($error !== true) { ?> 
				<table id='agent_actions'>
					<tr>
						<td id='cont_commom_ac'>
							<div class='commom_ac'>
								<a id='show_agent'><img src='../pixmaps/user--plus.png' alt='Arrow' align='absmiddle'/><span><?php echo _("Add agent")?></span></a>
							</div>
						</td>
						<td class='info'></td>
					</tr>
					
					<tr>
						<td colspan='2'>
							<div id='cont_add_agent' class='visible'>
								<form method='POST' name='form_agent' id='form_agent'>
									<table>
										<tr>
											<th><label for='agent_name'><?php echo gettext("Agent Name"); ?></label></th>
											<td class="left">
												<input type='text' name='agent_name' id='agent_name' class='vfield req_field' value="<?php echo $agent_name?>"/>
												<span style="padding-left: 3px;">*</span>
											</td>
										</tr>
										
										<tr>
											<th><label for='ip'><?php echo gettext("IP Address"); ?></label></th>
											<td class="left">
												<input type='text' name='ip' id='ip' class='vfield req_field' value="<?php echo $ip?>"/>
												<span style="padding-left: 3px;">*</span>
											</td>
										</tr>
										<tr>
											<td class="cont_send" colspan='2'><input type="button" id='send' class="button" value="<?=_("Send")?>"/></td>
										</tr>
									</table>
								</form>
							</div>
						</td>
					</tr>
							
				</table>
				
				<?php } ?>
						
			</div>
		<td>
	</tr>	
</table>

<div class='notice'><span>(*)<?php echo _("You must restart Ossec for the changes to take effect")?></span></div>

</body>
</html>

