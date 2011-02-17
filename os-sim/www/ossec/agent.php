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

if ( !file_exists($agent_conf) )
{
	$result = test_agents();

	if ( $result !== true )
		$error = true;
}
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
	<script type='text/javascript' src='../js/codemirror/codemirror.js'></script>
	<script type="text/javascript" src="js/agents.js"></script>
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
				$("ul.oss_tabs #litem_tab2").addClass("active");
				$('#link_tab1').addClass("dis_tab");
				
				$("#litem_tab2").click(function(event) { 
					event.preventDefault(); 
					var tab = $("#litem_tab2");
					show_tab_content(tab);
					load_agent_tab("#tab2");
				});
								
				load_agent_tab("#tab2");
				
				var tab = $("#litem_tab2");
				show_tab_content(tab);
			}
			
							
			$('#send').bind('click', function()  { save_agent_conf(); });	
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
					<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo ucfirst(basename($agent_conf))?></a></li>
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
					
				<div id="tab2" class="tab_content" style='display:none;'>
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

