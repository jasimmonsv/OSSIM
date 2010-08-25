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
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "CorrelationDirectives");
require_once ('ossim_conf.inc');
require_once ('classes/Security.inc');
/* directories */
$conf = $GLOBALS["CONF"];
$base_dir = $conf->get_conf("base_dir");
$css_dir = '../../style';
$js_dir = '../javascript';
$js_dir_rule = 'javascript';
/* connection to the OSSIM database */
require_once ('../../include/rule.php');
dbConnect();
/* get the rule */
$rule = unserialize($_SESSION['rule']);
/* width */
$left_table_width = "700px";
$right_table_width = "230px";
$middle_table_width = "930px";
$left_select_width = "120px";
$right_select_width = "110px";
$left_text_width = "160px";
$right_text_width = "102px";
$plugin_id_width = "112px";
$reliability1_width = "38px";
$reliability2_width = "68px";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<link type="text/css" rel="stylesheet"
			href="<?php
echo $css_dir . '/directives.css'; ?>" />

		<style>
			input.editable {width: <?php
echo $right_text_width; ?>}
			select.editable {width: <?php
echo $right_select_width; ?>}
		</style>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editor.js'; ?>"></script>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir . '/editableSelectBox.js'; ?>"></script>

		<script type="text/javascript" language="javascript"
			src="<?php
echo $js_dir_rule . '/rule.js'; ?>"></script>
			
    <script type="text/javascript" language="javascript">
    function taille()
    {
        if (document.body)
        {
        var larg = (window.parent.document.body.clientWidth);
        var haut = (window.parent.document.body.clientHeight);
        }
        else
        {
        var larg = (window.parent.window.innerWidth);
        var haut = (window.parent.window.innerHeight);
        }
        /* default size */
    	   var width = 890;
    	   var height = 550;
    
    	   /* center the popup to the screen */
    	   if (width < larg)
    	   {
    	     var left = (larg - width) / 2;
    	   }
    	   else
    	   {
            width = larg - 20;
            left = 10;
         }
         
         if (height < haut)
    	   {
           var top = (haut - height) / 2;
    	   }
    	   else
    	   {
            height = haut - 20;
            top = 10;
         }
         
         window.parent.document.getElementById('fenetre').style.top = top;
         window.parent.document.getElementById('fenetre').style.left = left;
         window.parent.document.getElementById('fenetre').style.width = width;
         window.parent.document.getElementById('fenetre').style.height = height;
         
    }
    
   function open_frame(url){
    var iframe = window.parent.document.getElementById('fenetre');
    var fond = window.parent.document.getElementById('fond');
    iframe.childNodes[0].src = url;    
    taille();
    fond.style.display = 'block';
    iframe.style.display = 'block';
   }

		function change_page(){
			var page1 = window.document.getElementById('page1');
			var page2 = window.document.getElementById('page2');

			if (page1.style.display == 'block'){
				page1.style.display = 'none';
				page2.style.display = 'block';
			}
			else{
				page1.style.display = 'block';
				page2.style.display = 'none';
			}
		}
   </script>
	</head>

	<body onload="onLoadRuleEditor(
		'<?php
echo isList($rule->plugin_sid) ? $rule->plugin_sid : ''; ?>',
		'<?php
echo isList($rule->from) ? $rule->from : ''; ?>',
		'<?php
echo isList($rule->to) ? $rule->to : ''; ?>',
		'<?php
echo isList($rule->port_from) ? $rule->port_from : ''; ?>',
		'<?php
echo isList($rule->port_to) ? $rule->port_to : ''; ?>',
		'<?php
echo isList($rule->sensor) ? $rule->sensor : ''; ?>'
	)">
  <div style="
      background-color:#17457c;
      width:100%;
      position:fixed;
      height:2px;
      left:0px; top:0px;"></div><br>
	<!-- #################### main container #################### -->
  <form method="POST" action="../../include/utils.php?query=save_rule">

	<!-- #################### page 1 #################### -->
	<div id="page1" style="display:block;">
	<table class="container" style="border-width: 0px" align="center">
	<tr>

	<!-- #################### left container #################### -->
	<td class="container" style="vertical-align: top">
	<table class="container">
	<tr><td class="container">
	<?php
include ("global.inc.php"); ?>
	</td></tr>

	<tr><td class="container">
	<?php
include ("network.inc.php"); ?>
	</td></tr>

	<tr><td class="container">
	<?php
include ("protocol.inc.php"); ?>
	</td></tr>

	<tr><td class="container">
	<?php
include ("sensor.inc.php");
$directive = GET("directive");
$level = GET("level");
$id = GET("id");
ossim_valid($directive, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("directive"));
ossim_valid($level, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("level"));
ossim_valid($id, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}
if ($rule->is_new() && $level > 1) $new_level = $level - 1;
else $new_level = $level;
?>
	</td></tr>

	</table>
	</td>
	<!-- #################### END: up container #################### -->
</tr>
<tr>
	<!-- #################### down container #################### -->
	<td style="vertical-align: top; border-width: 0px">
	<table class="container" style="border-width: 0px">

	<tr><td class="container" valign="top">
	<?php
include ("risk.inc.php"); ?>
	</td>

	<td class="container" valign="top">
	<?php
include ("monitor.inc.php"); ?>
	</td>

	<td class="container" valign="top">
	<?php
include ("sticky.inc.php"); ?>
	</td></tr>

	</table>
	</td>
	<!-- #################### END: down container #################### -->
	
	</tr>
<tr><td class="container" colspan="2">
		<input type="hidden" name="directive" value="<?php
echo $directive; ?>" />
		<input type="hidden" name="level" value="<?php
echo $level; ?>" />
		<input type="hidden" name="id" value="<?php
echo $id; ?>" />
		<input type="hidden" name="type" id="type"
			value="<?php
echo getPluginType($rule->plugin_id); ?>" />
		<input type="button" style="width: 100px; cursor:pointer;"
			value="<?php
echo gettext('Cancel'); ?>"
			onclick="onClickCancel(<?php
echo $directive . ',' . $new_level; ?>)"
		/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" style="width: 100px; cursor:pointer;"
			id="save"
			value="<?php
echo gettext('Save'); ?>"
			onclick="submit()"
			<?php
echo ($rule->plugin_id == '') ? ' disabled="disabled"' : ''; ?>
		/>
		<span style="position:absolute; right:2%;">
		<input type="button" style="width: 120px; cursor:pointer;"
			id="change"
			value="<?php
echo gettext('Other options'); ?> -->"
			onclick="change_page();"
		/>
		</span>
	</td></tr>

	</table>
		</div>
	<!-- #################### end page 1 ######################### -->
	<!-- #################### PAGE 2 ############################# -->
	<div id="page2" style="display:none;">
	<table class="container" style="border-width: 0px" align="center">
		<tr>
			<td class="container" style="vertical-align: top">
				<table class="container">
					<tr>
						<td class="container">
							<?php
include ("$base_dir/directive_editor/editor/rule/other.inc.php"); ?>
						</td>
					</tr>
					<tr>
						<td class="container">
							<?php
include ("$base_dir/directive_editor/editor/rule/userdata.inc.php"); ?>
						</td>
					</tr>
					<tr>
						<td class="container" colspan="2">
							
						</td>
					</tr>
					<tr><td colspan="2">
						<span style="position:absolute; left:3%;">
						<input type="button" style="width: 100px; cursor:pointer;"
							id="change"
							value="<-- <?php
echo gettext('Previous'); ?>"
							onclick="change_page();"
							/>
						</span>
						<input type="button" style="width: 100px; cursor:pointer;"
							value="<?php
echo gettext('Cancel'); ?>"
							onclick="onClickCancel(<?php
echo $directive . ',' . $new_level; ?>)"
						/>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" style="width: 100px; cursor:pointer;"
							id="save"
							value="<?php
echo gettext('Save'); ?>"
							onclick="submit()"
							<?php
echo ($rule->plugin_id == '') ? ' disabled="disabled"' : ''; ?>
						/>
					</td></tr>
				</table>
			</td>
		</tr>
	</table>
	</div>
	<!-- #################### END OF PAGE 2 ############################# -->
	</form>
	
	<!-- #################### END: main container #################### -->
	<?php
dbClose();
?>
	</body>
</html>
