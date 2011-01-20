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
	<script type="text/javascript" src="functions.js"></script>
			
	<script type="text/javascript">
	var messages = new Array();
		messages[0]  = '<div id="oss_load" style="height:20px; width: 100%; font-size:12px; text-align:center;"><img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Processing action...")?> </span></div>';
	</script>
	
	<script type="text/javascript">
				
		function load_tab1()
		{
			var extra = '';
			execute_action("status", "#ossc_result", extra);
		}
		
		function load_tab2()
		{
			var extra = $('#oss_num_line').val();
			execute_action("ossec_log", "#logs_result", extra);
		}
		
		function load_tab3()
		{
			var extra = $('#alert_num_line').val();
			extra = 5000;
			execute_action("alerts_log", "#alerts_result", extra);
		}
		
		
		function execute_action(action, div_load, extra)
		{
			//Load img
			
			var cont_height = $(div_load).css('height').replace("px","")
			var cont_height = ( isNaN(cont_height) ) ? parseInt($(div_load).height()) :  parseInt(cont_height);
			
			
			$(div_load).css('height', cont_height);
			
			$(div_load).html(messages[0]);
		
			var load_height = $("#oss_load").css('height').replace("px","");
			var load_height = ( isNaN(load_height) ) ? parseInt($("#oss_load").height()) :  parseInt(load_height);
						
			var middle_pos  =  Math.ceil((cont_height - load_height)/2);

			$("#oss_load").css('padding-top', middle_pos+"px");
			
			
			
			var data = "action="+action;
			
			if (extra !='')
				data += "&extra="+extra;
									
			$.ajax({
				type: "POST",
				url: "ajax/ossec_control_actions.php",
				data: data,
				success: function(msg){
					
					if ( msg.match("Illegal action") != null )	
						$(div_load).html("<div class='oss_error' style='width:90%'>"+msg+"</div>");
					else
					{
						if (div_load == "#ossc_result")
						{
							var status = msg.split("###");
							
							if (status[0] == "0")
								set_action(action);
													
							msg = status[1];
						}
						
						var width = parseInt($(div_load).css('width').replace("px",""))-50;
											
						$(div_load).html('');
						
						$(div_load).html("<div style='padding: 5px 10px 10px 10px; width:"+ width+"px;'>"+msg+"</div>");
					}
				}
			});
		}
		
		function set_action(action)
		{
			switch (action){
			
				case "enable_db":
					var id     = 'cont_db_action';
					var input  = "<span class='running'>Database <?php echo _("is running")?></span><br/><br/>";
						input += "<input type='button' id='db_disable' class='lbutton' value='<?php echo _("Disable") ?>'/>";
				break;
				
				case "disable_db":
					var id     = 'cont_db_action';
					var input  = "<span class='not_running'>Database <?php echo _("not running")?></span><br/><br/>";
						input += "<input type='button' id='db_enable' class='lbuttond' value='<?php echo _("Enable") ?>'/>";
				break;
				
				case "enable_cs":
					var id     = 'cont_cs_action';
					var input  = "<span class='running'>Client-syslog <?php echo _("is running")?></span><br/><br/>";
						input += "<input type='button' id='cs_disable' class='lbuttond' value='<?php echo _("Disable") ?>'/>";
				break;
				
				case "disable_cs":
					var id     = 'cont_cs_action';
					var input  = "<span class='not_running'>Client-syslog <?php echo _("not running")?></span><br/><br/>";
						input += "<input type='button' id='cs_enable' class='lbutton' value='<?php echo _("Enable") ?>'/>";
				break;
				
				case "enable_al":
					var id     = 'cont_al_action';
					var input  = "<span class='running'>Agentless <?php echo _("is running")?></span><br/><br/>";
						input += "<input type='button' id='al_disable' class='lbuttond' value='<?php echo _("Disable") ?>'/>";
				break;
				
				case "disable_al":
					var id     = 'cont_al_action';
					var input  = "<span class='not_running'>Agentless <?php echo _("not running")?></span><br/><br/>";
						input += "<input type='button' id='al_enable' class='lbutton' value='<?php echo _("Enable") ?>'/>";
				break;
				
				case "Stop":
					var id     = 'cont_system_action';
					var input  = "<span class='not_running'> <?php echo _("Ossec service is down")?></span><br/><br/>";
						input += "<input type='button' id='system_start' class='lbutton' value='<?php  echo _("Start")?>'/>";
						input += "<input type='button' id='system_restart' class='lbutton' value='<?php echo _("Restart")?>'/>";
				break;
				
				case "Restart":
					var id     = 'cont_system_action';
					var input  = "<span class='running'> <?php echo _("Ossec service is up")?></span><br/><br/>";
						input += "<input type='button' id='system_stop' class='lbuttond' value='<?php echo _("Stop")?>'/>";
						input += "<input type='button' id='system_restart' class='lbutton' value='<?php echo _("Restart")?>'/>";
				break;
				
				case "Start":
					var id     = 'cont_system_action';
					var input  = "<span class='running'> <?php echo _("Ossec service is up")?></span><br/><br/>";
						input += "<input type='button' id='system_stop' class='lbuttond' value='<?php echo _("Stop")?>'/>";
						input += "<input type='button' id='system_restart' class='lbutton' value='<?php echo _("Restart")?>'/>";
				break;
			}
			
			
			$('#'+ id).html(input);		
			
			if (action == "<?php echo _("Start")?>" || action == "<?php echo _("Stop")?>" || action == "<?php echo _("Restart")?>")
				bind_action ('#cont_system_action');
			else
			{
				bind_action ('#cont_db_action');
				bind_action ('#cont_cs_action');
				bind_action ('#cont_al_action');
			}
		}
		
		function bind_action (id)
		{
			switch (id){
				
				case "#cont_db_action":
					
					$(id+ ' input').bind('click', function() {
						var action = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable_db" : "disable_db";
						var extra  = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable database" : "disable database";
						execute_action(action, "#ossc_result", extra);
					});
				break;
				
				case "#cont_cs_action":
					$(id+ ' input').bind('click', function() {
						var action = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable_cs" : "disable_cs";
						var extra  = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable client-syslog" : "disable client-syslog";
						execute_action(action, "#ossc_result", extra);
					});
				break;
				
				case "#cont_al_action":
					$(id+ ' input').bind('click', function() {
						var action = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable_al" : "disable_al";
						var extra  = ( $(this).val() == "<?php echo _("Enable")?>" ) ? "enable agentless" : "disable agentless";
						execute_action(action, "#ossc_result", extra);
					});
				break;
				
				case "#cont_system_action":
					$(id+ ' input').bind('click', function() {
					
						var action = $(this).val();
						
						if ( action == "<?php echo _("Start")?>" )
							var action = "Start";
						else if ( action == "<?php echo _("Restart")?>" )
							var action = "Restart";
						else
						{
							if ( action == "<?php echo _("Restart")?>" )
								var action = "Stop";
						}
										
						load_tab1();
					});
				break;
			}
		}
		
		
		
		
		
		
		$(document).ready(function() {
			
			//Tabs
			$("ul.oss_tabs li:first").addClass("active");
			
			$('#oss_num_line').bind('change', function() {
				load_tab2();
			});
			
			$('#alert_num_line').bind('change', function() {
				load_tab3();
			});
			
			$("ul.oss_tabs li").click(function(event) { 
				event.preventDefault(); 
				show_tab_content(this); 
			});
			
			$("#link_tab1, #refresh").click(function(event) { 
				load_tab1();
			});
			
			$("#link_tab2").click(function(event) { 
				load_tab2();
			});
			
			$("#link_tab3").click(function(event) { 
				load_tab3();
			});
			
			$("#show_actions").click(function(event) { 
				event.preventDefault();
				
				if ( $("#show_actions").hasClass('hide') )
				{
					$("#show_actions").removeClass();
					$("#show_actions").addClass('show');
					$("#show_actions span").html('<?php echo _("Hide Actions")?>');
									  
					$('#ossc_actions').css('display', 'block');
					$('#ossc_actions').css('height', '100px');
				}
				else
				{
					$("#show_actions").removeClass();
					$("#show_actions").addClass('hide');
					$("#show_actions span").html('<?php echo _("Show Actions")?>');
					
					$('#ossc_actions').css('display', 'none');
					$('#ossc_actions').css('height', '1px');
								
				}
			});
			
			bind_action ('#cont_db_action');
			bind_action ('#cont_cs_action');
			bind_action ('#cont_al_action');
			bind_action ('#cont_system_action');
					
		});
	
	</script>
	
	<style type='text/css'>
		.generic_tab {width: 80%; margin: 20px auto;}
		.generic_tab table{ width: 100%;}
				
		.generic_tab th { height: 20px;}
				
		#ossc_options { width: 60px;}
		div .button {float: none; margin:0px; width: 55px;}
				
		.div_pre {
			background-color: #FFFFFF;
			border: none;
			font-family: Courier New,Courier, monospace;
			font-size: 12px;
			text-align: left;
			overflow:auto;
		}
		
		#ossc_result,#alerts_result,#logs_result {
			margin: auto; 
			width: 100%; 
			border: 1px solid #D3D3D3;
			-moz-border-radius:4px;
			-webkit-border-radius: 4px;
			-khtml-border-radius: 4px;
			border: solid 1px #D2D2D2;
		}
		
		
		.cont_num_line {border: none !important; padding: 0px 0px 10px 0px; text-align:left; font-size:11px;}
		.cont_num_line select {width: 100px; margin-left: 5px;}
		
		.log { height: 380px;}
		
		.bold {font-weight: bold;}
					
		html ul.oss_tabs li.active a:hover  {cursor:pointer !important;}
		
		#ossc_actions {display: none; padding-bottom: 20px;}
				
		.text_ossc_actions {padding: 10px 0px; text-align:left; border: none !important;}
		
		#table_ossc_actions { width: 100%;}
		#table_ossc_actions th {width: 120px;}
		
		.noborder {border: none !important;}
		
		.pad10 {padding-top: 10px;}
		
		.running {margin: 0px 5px 0px 5px; color:#15B103; font-weight: bold; font-size: 11px;}
		.not_running {margin: 0px 5px 0px 5px; color:#E54D4D; font-weight: bold; font-size: 11px;}
		
		.headerpr {margin-bottom: 5px;}
		
		#refresh img {float: right; margin-right: 3px;}
						
	</style>

		
</head>
<body>



<?php

include ("../hmenu.php"); 

//Ossec Status
exec ("sudo /var/ossec/bin/ossec-control status", $result_1);
$result_1 = implode("<br/>", $result_1);
$result_1 = str_replace("is running", "<span style='font-weight: bold; color:#15B103;'>is running</span>", $result_1);
$result_1 = str_replace("not running", "<span style='font-weight: bold; color:#E54D4D;'>not running</span>", $result_1);


//Ossec Control

exec ("ps ax | grep ossec | grep -v grep", $output_sys, $result_sys);

if ( count($output_sys) < 1)
{
	$system_action    = "<span class='not_running'>"._("Ossec service is down")."</span><br/><br/>
						<input type='button' id='system_start' class='lbutton' value='"._("Start")."'/>"; 
						

}
else
	$system_action   = "<span class='running'>"._("Ossec service is up")."</span><br/><br/>
						<input type='button' id='system_stop' class='lbuttond' value='"._("Stop")."'/>"; 
					

$system_action .= "<input type='button' id='system_restart' class='lbutton' value='"._("Restart")."'/>";
	
exec ("ps -ef | grep ossec-csyslogd | grep -v grep", $output_cs, $result_cs);

$syslog_action    = ( count($output_cs) < 1 ) ? "<span class='not_running'>Client-syslog "._("not running")."</span><br/><br/><input type='button' id='cs_enable' class='lbutton' value='"._("Enable")."'/>" : "<span class='running'>Client-syslog "._("is running")."</span><br/><br/><input type='button' id='cs_disable' class='lbuttond' value='"._("Disable")."'/>";

exec ("ps -ef | grep ossec-dbd | grep -v grep", $output_db, $result_db);
$database_action  = ( count($output_db) < 1 ) ? "<span class='not_running'>Database "._("not running")."</span><br/><br/><input type='button' id='db_enable' class='lbutton' value='"._("Enable")."'/>" : "<span class='running'>Database "._("is running")."</span><br/><br/><input type='button' id='db_disable' class='lbuttond' value='"._("Disable")."'/>";


exec ("ps -ef | grep ossec-agentlessd | grep -v grep", $output_al, $result_aj);
$agentless_action = ( count($output_al) < 1 ) ? "<span class='not_running'>Agentless "._("not running")."</span><br/><br/><input type='button' id='al_enable' class='lbutton' value='"._("Enable")."''/>" : "<span class='running'>Agentless "._("is running")."</span><br/><br/><input type='button' id='al_disable' class='lbuttond' value='"._("Disable")."'/>";

?>

	<div id='container_center'>

		<table id='tab_menu'>
			<tr>
				<td id='oss_mcontainer'>
					<ul class='oss_tabs'>
						<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?=_("Ossec Control")?></a></li>
						<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?=_("Ossec Log")?></a></li>
						<li id='litem_tab3'><a href="#tab3" id='link_tab3'><?=_("Alerts Log")?></a></li>
					</ul>
				</td>
			</tr>
		</table>
		
		<table id='tab_container' class='oss_control'>
			<tr>
				<td>
					<div id='tab1' class='generic_tab tab_content'>
						
						<div class='text_ossc_actions'>
							<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif">
							<a id='show_actions' class='hide'><span class='bold'><?php echo _("Show actions")?></span></a>
						</div>
						
						<div id='ossc_actions'>
								
							<table id='table_ossc_actions'>
								<tr>
									<th class='headerpr' colspan='4'><?php echo _("Actions")?></th>
								</tr>
								<tr>
									<td class='noborder center pad10' id='cont_db_action'><?php echo $database_action ?></td>
									<td class='noborder center pad10' id='cont_cs_action'><?php echo $syslog_action ?></td>
									<td class='noborder center pad10' id='cont_al_action'><?php echo $agentless_action ?></td>
									<td class='noborder center pad10' id='cont_system_action'><?php echo $system_action ?></td>
								</tr>
															
							</table>
						</div>
						
						<div class='headerpr'>
							<?php echo _("Ossec Output");?>
							<a id='refresh'><img border="0" src="../pixmaps/refresh.png"></a>
						</div>
						<div id='ossc_result' class='div_pre'>
							<div style='padding: 5px 10px 10px 10px;'><?php echo $result_1;?></div>
						</div>
					
					</div>
					
					<div id='tab2' class='generic_tab tab_content' style='display:none;'>
						
						<div class='cont_num_line'>
							<span class='bold'><?php echo _("View")?>:</span>
							<select name='oss_num_line' id='oss_num_line'>
								<option value='50'>50</option>
								<option value='100'>100</option>
								<option value='250'>250</option>
								<option value='500'>500</option>
								<option value='5000'>5000</option>
							</select>
						</div>
						<div class='headerpr'><?php echo _("Ossec Log");?></div>
						<div id='logs_result' class='log div_pre'></div>
					
					</div>
					
					<div id='tab3' class='generic_tab tab_content' style='display:none;'>
						
						<div class='cont_num_line'>
							<span class='bold'><?php echo _("View")?>:</span>
							<select name='alert_num_line' id='alert_num_line'>
								<option value='50'>50</option>
								<option value='100'>100</option>
								<option value='250'>250</option>
								<option value='500'>500</option>
								<option value='5000'>5000</option>
							</select>
						</div>
						<div class='headerpr'><?php echo _("Alerts log");?></div>
						<div id='alerts_result' class='log div_pre'></div>
						
					</div>
				</td>
			</tr>
		</table>	
		
	</div>
</body>
</html>

