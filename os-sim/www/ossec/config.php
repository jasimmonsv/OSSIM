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

if ( !Session::menu_perms("MenuEvents", "EventsHidsConfig") )
	Session::unallowed_section(null,'noback', "MenuEvents", "EventsHidsConfig");

$error        = false;
$cnf_message  = null;
$file_xml     = null;
$test_conf    = null;


if ( file_exists( $ossec_conf) )
{
	$file_xml = @file_get_contents ($ossec_conf, false);
					  	  		
	if ($file_xml === false)
	{
		$error    = true;
		$file_xml = null;
	}
}
else
{
	$error    = true;
	$file_xml = null;
}

if ( $error == false )
{
	$result = test_conf(); 	
	$error  = ( $result !== true ) ? true : false;	
}

$cnf_message = "<div id='cnf_load'><img src='images/loading.gif' border='0' align='absmiddle' alt='Loading'/><span>".('Loading data ...')."</span></div>";

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
	<script type='text/javascript' src='../js/codemirror/codemirror.js' ></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
		
	<!-- Multiselect: -->
	
    <script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/ui.multiselect.css"/>
	
	<script type='text/javascript'>
		var messages = new Array();
			messages[0]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Loading data ... ")?></span>';
			messages[1]  = '<img src="images/loading.gif" border="0" align="absmiddle" alt="Loading"/><span style="padding-left: 5px;"><?php echo _("Saving data ... ")?></span>';
			messages[2]  = '<span style="padding-left: 5px;"><?php echo _("Illegal action")?></span>';
			messages[3]  = '<?php echo _("Configuration error in file")." ".basename($ossec_conf)." "._("and/or")." ".$editable_files[0]?>';
			messages[4]  = '<?php echo _("View errors")?>';
			messages[5]  = "<?php echo _("Are you sure to delete this row")?>?";
			messages[6]  = '<?php echo _("Re-loading in")?>';
			messages[7]  = '<?php echo _("second(s)")?>';
			
		var editor = null;
	</script>
	
	<!-- Own libraries: -->
	<script type="text/javascript" src="js/config.js"></script>
	<script type='text/javascript' src='../js/utils.js'></script>	
	
	<script type='text/javascript'>
		
		$(document).ready(function() {

			/* Tabs */
						
			var error = '<?php echo $error;?>';
								
			if (error == false)
			{			
				//On Click Event
				
				$("ul.oss_tabs li:first").addClass("active");
				
				$("ul.oss_tabs li").click(function(event) { 
					event.preventDefault(); 
					show_tab_content(this); 
					load_config_tab($(this).find("a").attr("href"));
				});
				
				load_config_tab("#tab1");
			}
			else
			{
				$("ul.oss_tabs #litem_tab3").addClass("active");
				$('#link_tab1,#link_tab2').addClass("dis_tab");
				
				$("#litem_tab3").click(function(event) { 
					event.preventDefault(); 
					var tab = $("#litem_tab3");
					show_tab_content(tab);
					load_config_tab("#tab3");
				});
								
				load_config_tab("#tab3");
				
				var tab = $("#litem_tab3");
				show_tab_content(tab);
				
			}
							
			$('#send').bind('click', function()  { save_config_tab(); });	
											
		});
	
	</script>
	
	<style type='text/css'>
	
	.multiselect {
		width: 70%;
		height: 350px;
	}
		
		
	input.button {float: none; margin:auto; padding: 0px 2px;}
			
	.buttons_box {	
		float: right; 
		width: 20%;
		padding-right: 47px;
		padding-bottom: 10px;
	}
	
	#msg_init{ margin: 150px auto 270px auto; }
			
	.cont_sys {width: 85%; margin:auto; padding: 10px 0px;}
	
	.sys_dir {width: 350px;}
	.sys_ignores {width: 350px; }
	.sys_actions { width: 60px !important; padding: 2px 0px; }
	
	
	#cont_tsp { 
		padding: 10px 0px; 
		text-align: left;
	}
	
	#table_sys_parameters {width: 100%; text-align: left !important;}
	#frequency { height: 18px; width: 210px;}
	.sys_parameter {width: 150px; padding: 2px 0px;}
	.sys_value { width: 200px; padding: 2px 10px; text-align: left;}
	
	textarea { border: solid 1px #888;}
	#code { border:none;}
	.cont_savet2{ padding: 20px 0px 20px 0px; text-align: right; margin-right: 2px;}
	
	ul.oss_tabs li.active a:hover {cursor: pointer !important;} 
	
	.time { width: 22px;}
	
	.select_yn {width: 210px;}
	
	.select_wd {width: 210px;}
	
	.wentry, .sreg_ignore {width: 100%; height: 18px;}

	</style>
</head>

<body>

<?php include ("../hmenu.php"); ?>

	<div id='container_center'>
	
		<table id='tab_menu'>
			<tr>
				<td id='oss_mcontainer'>
					<ul class='oss_tabs'>
						<li id='litem_tab1'><a href="#tab1" id='link_tab1'><?php echo _("Rules")?></a></li>
						<li id='litem_tab2'><a href="#tab2" id='link_tab2'><?php echo _("Syscheck")?></a></li>
						<li id='litem_tab3'><a href="#tab3" id='link_tab3'><?php echo ucfirst(basename($ossec_conf))?></a></li>
					</ul>
				</td>
			</tr>
		</table>
		
		<table id='tab_container'>
		    <tr>
				<td id='oss_cnf_container'>
					<div id='cnf_message'></div>
					
					<div id="tab1" class="tab_content"><?php echo $cnf_message;?></div>
					
					<div id="tab2" class="tab_content" style='display:none;'><?php echo $cnf_message;?></div>
					
					<div id="tab3" class="tab_content" style='display:none;'>
						<div id='container_code'><textarea id="code"></textarea></div>
						<div class='buttons_box'>
							<div><input type='button' class='save' id='send' value='<?php echo _("Update")?>'/></div>				
						</div>
						<div class='notice'><div><span>(*)<?php echo _("You must restart Ossec for the changes to take effect")?></span></div></div>
					</div>
				</td>
			</tr>
		</table>
		
	</div>
	

</body>

</html>

