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

$error == false;

if ( file_exists( $ossec_conf) )
{
	$file_xml = @file_get_contents ($ossec_conf, false);
					  	  		
	if ($file_xml == false)
	{
		$error    = true;
		$file_xml = '';
		$txt      = _("Directory")." <b>$ossec_conf</b> "._("doesn't exist or you don't have permission to access");
	}
}
else
{
	$error    = true;
	$file_xml = '';
	$txt      = "<b>$ossec_conf</b> "._("not found or you don't have have permission to access");
}


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
	<script type='text/javascript' src='codemirror/codemirror.js' ></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
		
	<!-- Own libraries: -->
	<script type='text/javascript' src='utils.js'></script>
	<script type='text/javascript' src='functions.js'></script>
	
	<script type='text/javascript'>
		var messages = new Array();
			messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Loading data ... ")?></span>';
			messages[1]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Saving data ... ")?></span>';
			messages[2]  = '<span style="padding-left: 5px;"><?php echo _("Illegal action")?></span>';
		
		var editor       = null;	
	</script>
	
	<!-- Multiselect: -->
	
    <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/ui.multiselect.css"/>
	
	<script type='text/javascript'>
		
		function load_config_tab(tab)
		{
			
			//Add Load img
			if ($('#cnf_load').length < 1)
			{
				$(tab+" div").css('display', 'none');
				var load ="<div id='cnf_load'>"+messages[0]+"</div>";
				$(tab).append(load);
			}
															
			//Remove error message
			
						
			if ($('#cnf_message').length >= 1)
			{
				$('#cnf_message').html('');
				$('#cnf_message').removeClass($('#cnf_message').attr('class'));
			}
						
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
						var error_message = "<div id='msg_init'><div class='oss_error'>"+status[1]+"</div></div>";
						$(tab).html(error_message);
						$(tab+" div").css('display', 'block');
						
					}
					else
					{
						$(tab).html(status[1]);	
													
						if (tab == "#tab1")
						{
							$(".multiselect").multiselect({
								searchDelay: 500,
								dividerLocation: 0.5
							});
							
							$(tab+" div").css('display', 'block');
						}
						else if (tab == "#tab2")
						{
							$(tab+" div").css('display', 'block');
							$('textarea').elastic();
							$('#table_sys_directories table').css('background', 'transparent');
							$('#table_sys_directories .dir_tr:odd').css('background', '#EFEFEF');
						}
						else
						{
							if (tab == "#tab3")
							{
								if (editor == null)
								{
									editor = new CodeMirror(CodeMirror.replace("code"), {
										parserfile: "parsexml.js",
										stylesheet: "css/xmlcolors.css",
										path: "codemirror/",
										continuousScanning: 500,
										content: msg,
										lineNumbers: true
									});
								}
								else
									editor.setCode(msg);
								
								$(tab+" div").css('display', 'block');
							}	
						}
					}
					
					
						
				}
			});
		}
		
		function save_config_tab()
		{
			
			var tab = $(".active a").attr("href");
						
			if ($('#cnf_message').length >= 1)
			{
				$('#cnf_message').html('');
				$('#cnf_message').removeClass($('#cnf_message').attr('class'));
			}
			
			
			if (tab == '')
			{
				$('#cnf_message').addClass("oss_error");
				$('#cnf_message').html(messages[2]);
				return;
			}
			
			//Add Load img
						
			$('#cnf_message').html("<div id='cnf_wait_save'></div>");
			$('#cnf_wait_save').html(messages[1]);
			
			var data= "tab="+tab;
			
			if (tab == "#tab1")	
				data += "&"+ $('form').serialize();
			else
			{
				if (tab == "#tab3")	
					data += "&"+"data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));
			}
				
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
						$('#cnf_message').addClass("oss_success");	
										
					$('#cnf_message').html(status[1]);
					
				}
			});
		}
		
		
		function add_dir(id)
		{
			
			$.ajax({
				type: "POST",
				url: "ajax/config_actions.php",
				data: "action=add_directory",
				success: function(msg){
					
					var status = msg.split("###");
															
					if (status[0] != "error")
					{
						$('#'+id).after(status[1]);
					}
				}
			});
			
		
		}
		
		function delete_dir(id)
		{
			if ( confirm ("<?php echo _("Are you sure to delete this row")?>?") )
			{
				
				if ( $('#'+id).length >= 1 )
				{
					$('#'+id).remove();
					if ($('#tbody_sd tr').length <= 2)
						add_dir();
				}
			}
		
		}
		
		
	
	</script>
	
	<script type='text/javascript'>
		
		$(document).ready(function() {

			/* Tabs */
			
			$("ul.oss_tabs li:first").addClass("active");
			
			<?php if ($error !== true) {?>			
			
				//On Click Event
				$("ul.oss_tabs li").click(function(event) { event.preventDefault(); show_tab_content(this); load_config_tab($(this).find("a").attr("href"))});
				
				load_config_tab("#tab1");
				
				<?php } else { ?>
					$("ul.oss_tabs li a").css("cursor", "text");	
				<?php } ?>
						
		});
	
	</script>
	
	<style type='text/css'>
	
	.multiselect {
		width: 70%;
		height: 350px;
	}
	
	
	
	.agent_actions { width: auto;}
	
	input.button {float: none; margin:auto; padding: 0px 2px;}
			
	.buttons_box {	
		float: right; 
		width: 20%;
		padding-right: 40px;
		padding-bottom: 10px;
	}
	
	
	#msg_init{ margin: 150px auto 270px auto; }
			
	#tab3 .button {background:none !important;}
	
	.cont_sys {width: 98%; margin:auto; padding: 10px 0px;}
	
	.sys_dir {width: 350px;}
	.sys_ignores {width: 450px;}
	.sys_actions { width: 60px !important; padding: 2px 0px; }
	
	
	#cont_tsp { padding: 10px 0px;}
	#table_sys_parameters {width: 80%; text-align: left;}
	#frequency { height: 18px; width: 300px;}
	
	.sys_frequency {width: 150px; padding: 2px 0px;}
	
	textarea { border: solid 1px #888}
	
	.cont_savet2{ padding: 20px 0px 20px 0px; text-align: right; margin-right: 10px;}

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
						<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?=_("Syscheck")?></a></li>
						<li id='litem_tab3'><a href="#tab3" id='link_tab3'><?=_("XML Source")?></a></li>
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
					
					<div id="tab1" class="tab_content">
						<?php if ( $error == true ) 
							echo "<div id='msg_init'><div class='oss_error'>$txt</div></div>";
						else
							echo "<div id='cnf_load'><img src='images/loading.gif' border='0' align='absmiddle' alt='Loading'/><span>".('Loading data ...')."</span></div>";
						?>
					</div>
					
					<div id="tab2" class="tab_content" style='display:none;'>
						<?php if ( $error == true ) 
							echo "<div id='msg_init'><div class='oss_error'>$txt</div></div>";
						else
							echo "<div id='cnf_load'><img src='images/loading.gif' border='0' align='absmiddle' alt='Loading'/><span>".('Loading data ...')."</span></div>";
						?>
					</div>
					
					<div id="tab3" class="tab_content" style='display:none;'>
						<div id='container_code'><textarea id="code"></textarea></div>
						<div class='buttons_box'>
							<?php if ( $error == false ) { ?>
								<div class='button'><input type='button' class='save' id='send' value='<?=_("save")?>' onclick="save_config_tab();"/></div>
							<?php } else{ ?>
								<div class='button'><input type='button' class='save' id='dis_send' disabled='disabled' value='<?=_("save")?>'/></div>
							<?php } ?>						
						</div>
						<div class='notice'><div><span>(*)<?php echo _("You must restart Ossec for the changes to take effect")?></span></div></div>
					</div>
				</td>
			</tr>
		</table>
		
	</div>
	

</body>

</html>

