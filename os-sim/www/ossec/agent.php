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
require_once ('conf/_conf.php');
require_once ('utils.php');

$result = test_agents();

if ( $result !== true )
	$error = true;

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
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/agents.js"></script>
	<script type='text/javascript' src='codemirror/codemirror.js'></script>
	<script type="text/javascript">
		var messages = new Array();
			messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Adding agent... ")?></span>';
			messages[1]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Processing action... ")?></span>';
			messages[2]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Loading data ... ")?></span>';
			messages[3]  = '<?php echo _("Configuration error at")." ".$agent_conf?>';
			messages[4]  = '<?php echo _("View errors")?>';
			messages[5]  = "<?php echo _("Are you sure to delete this row")?>?";
			messages[6]  = '<?php echo _("Re-loading in")?>';
			messages[7]  = '<?php echo _("second(s)")?>';
			
		var editor = null;
	</script>
	<script type="text/javascript">
	
	
		function load_agent_tab(tab)
		{
			//Add Load img
			if ($('#cnf_load').length < 1)
			{
				$(tab+" div").css('display', 'none');
				var load ="<div id='cnf_load'>"+messages[2]+"</div>";
				$(tab).append(load);
			}
															
			//Remove error message
									
			if ($('#cnf_message').length >= 1)
			{
				$('#cnf_message').removeClass();
				$('#cnf_message').html('<div id="cont_cnf_message"></div>');
			}
		
				
			$.ajax({
				type: "POST",
				url: "ajax/load_agent_tab.php",
				data: "tab="+tab,
				success: function(msg){
															
					//Remove load img
					
					if ( $('#cnf_load').length >= 1 )
						$('#cnf_load').remove();
						
					var status = msg.split("###");
					var txt    = null;
					
					switch( status[0] )
					{
						case "1":
							if (tab == "#tab1")
							{
								
								$(tab).html(status[1]);
															
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
								
								$(tab).css('display', 'block');
													
							}
							else if (tab == "#tab2")
							{
								$(tab).html(status[1]);	
								
								$(tab+" div").css('display', 'block');
								$('textarea').elastic();
								$('#table_sys_directories table').css('background', 'transparent');
								$('#table_sys_directories .dir_tr:odd').css('background', '#EFEFEF');
								$('#table_sys_ignores table').css('background', 'transparent');
								$('#table_sys_ignores .dir_tr:odd').css('background', '#EFEFEF');
							}
							else if (tab == "#tab3")
							{
								if (editor == null)
								{
									editor = new CodeMirror(CodeMirror.replace("code"), {
										parserfile: "parsexml.js",
										stylesheet: "css/xmlcolors.css",
										path: "codemirror/",
										continuousScanning: 500,
										content: status[1],
										lineNumbers: true
									});
								}
								else
									editor.setCode(status[1]);
								
								$(tab+" div").css('display', 'block');
							}
							
						break;
						
						case "2":
							txt = "<div id='msg_init_error'><div class='oss_error'><div style='margin-left: 70px; text-align:center;'>"+status[1]+"</div></div></div>";
							$(tab).html(txt);
							$(tab+" div").css('display', 'block');
						break;
						
						case "3":
							
							$('#cont_cnf_message').hide();
							txt   = "<span style='font-weight: bold;'>"+messages[3]+"<a onclick=\"$('#msg_errors').toggle();\"> ["+messages[4]+"]</a><br/></span>";
							txt  += "<div id='msg_errors'>"+status[2]+"</div>";
								
							$('#cont_cnf_message').append("<div id='parse_errors'></div>");
							$('#parse_errors').addClass("oss_error");
							$('#parse_errors').html(txt);
							
												
							if (editor == null)
							{
								editor = new CodeMirror(CodeMirror.replace("code"), {
									parserfile: "parsexml.js",
									stylesheet: "css/xmlcolors.css",
									path: "codemirror/",
									continuousScanning: 500,
									content: status[1],
									lineNumbers: true
								});
							}
							else
								editor.setCode(status[1]);
							
							$(tab+" div").show();
							$('#cont_cnf_message').show();

							window.scroll(0,0);
							setTimeout('$("#cont_cnf_message").fadeOut(4000);', 25000);	
						
						break;
					}
				}
			});
		}
		
		$(document).ready(function() {
			
			var error = '<?php echo $error;?>';
								
			if (error == false)
			{			
				//On Click Event
				
				$("ul.oss_tabs li:first").addClass("active");
				
				$("ul.oss_tabs li").click(function(event) { 
					event.preventDefault(); 
					show_tab_content(this); 
					load_agent_tab($(this).find("a").attr("href"));
				});
				
				load_agent_tab("#tab1");
			}
			else
			{
				$("ul.oss_tabs #litem_tab3").addClass("active");
				$('#link_tab1,#link_tab2').addClass("dis_tab");
				
				$("#litem_tab3").click(function(event) { 
					event.preventDefault(); 
					var tab = $("#litem_tab3");
					show_tab_content(tab);
					load_agent_tab("#tab3");
				});
								
				load_agent_tab("#tab3");
				
				var tab = $("#litem_tab3");
				show_tab_content(tab);
			}
			
							
			$('#send').bind('click', function()  { save_agent_tab(); });	
		});
				
		
	</script>
		
	<style type='text/css'>
		a {cursor:pointer; text-decoration: none !important;}			
		input[type='text'] {width: 90%; height: 20px;}
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
		.akey { font-size:9px; }
		
		.buttons_box {	
			float: right; 
			width: 20%;
			padding-right: 40px;
			padding-bottom: 10px;
		}
		
		ul.oss_tabs li.active a:hover {cursor: pointer !important;} 
		
		textarea { border: solid 1px #888;}
		loc_txt { width: 200px;}
		
		.ag_name {width: 180px;}
		.ag_location, ag_logformat { padding: 1px; font-size: 10px; text-align:center; white-space:normal;}
		.ag_logformat {width: 120px;}
		.ag_actions { width: 60px !important; padding: 2px 0px; }
		
		.cont_savet2{ padding: 20px 0px 20px 0px; text-align: right; margin-right: 2px;}
	
	</style>
</head>
<body>

<?php include("../hmenu.php"); ?>

<div id='container_center'>

	<table id='tab_menu'>
		<tr>
			<td id='oss_mcontainer'>
				<ul class='oss_tabs'>
					<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?php echo _("Agent Control")?></a></li>
					<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo _("Config Agent")?></a></li>
					<li id='litem_tab3'><a href="#tab3" id='link_tab3'><?php echo _("XML Source")?></a></li>
				</ul>
			</td>
		</tr>
	</table>
	
	<table id='tab_container'>
		<tr class='nobborder'><td><div class='cont_oss_load'><div class='oss_load'></div></div></td></tr>
		
		<tr>
			<td>
				<div id='cnf_message'></div>
				
				<div id="tab1" class="tab_content"></div>
					
				<div id="tab2" class="tab_content" style='display:none;'></div>
	
				<div id="tab3" class="tab_content" style='display:none;'>
					<div id='container_code'><textarea id="code"></textarea></div>
					<div class='buttons_box'>
						<div><input type='button' class='save' id='send' value='<?php echo _("Update")?>'/></div>				
					</div>
				</div>
						
			</td>
		</tr>
	
	</table>

	<div class='notice'><span>(*)<?php echo _("You must restart Ossec for the changes to take effect")?></span></div>

</div>

</body>
</html>

