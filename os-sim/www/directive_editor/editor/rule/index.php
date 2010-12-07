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

$add = GET('add');
ossim_valid($add, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("add"));
if (ossim_error()) {
    die(ossim_error());
}

$host_list = getHostList();
$net_list = getNetList();

$directive = GET("directive");
$level = GET("level");
$id = GET("id");
$xml_file = GET('xml_file');
ossim_valid($directive, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("directive"));
ossim_valid($level, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("level"));
ossim_valid($id, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("xml_file"));
if (ossim_error()) {
    die(ossim_error());
}
if ($rule->is_new() && $level > 1) $new_level = $level - 1;
else $new_level = $level;

if (empty($order)) $order = 'id';
$plugin_list = getPluginList('ORDER BY ' . $order);
$plugin_names = array();

$plugin_list_order = array();
foreach($plugin_list as $plugin) {
	$plugin_names[$plugin->get_id()] = $plugin->get_name();
	$plugin_list_order[strtolower($plugin->get_name())] = $plugin;
	if ($rule->plugin_id == $plugin->get_id()) $plugin_type = $plugin->get_type();
}
ksort($plugin_list_order);
$plugin_list = array(); // redefine to order
foreach ($plugin_list_order as $name => $plugin) {
	$plugin_list[] = $plugin;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<link type="text/css" rel="stylesheet" href="<?php echo $css_dir . '/directives.css'; ?>" />
		<link type="text/css" rel="stylesheet" href="../../../style/greybox.css" />
		<link rel="stylesheet" type="text/css" href="../../../style/tree.css" />
		<link type="text/css" rel="stylesheet" href="../../../style/jquery-ui-1.7.custom.css" />
    	<link type="text/css" rel="stylesheet" href="../../../style/ui.multiselect.css" rel="stylesheet" />
    	<style>
    	.multiselect_sids {
		    width: 97%;
		    height: 300px;
		}
		.multiselect_from {
		    width: 97%;
		    height: 150px;
		}
		.multiselect_from_port {
		    width: 97%;
		    height: 150px;
		}
		.multiselect_to {
		    width: 97%;
		    height: 150px;
		}
		.multiselect_to_port {
		    width: 97%;
		    height: 150px;
		}
		.multiselect_sensor {
		    width: 97%;
		    height: 300px;
		}
    	</style>
		<script type="text/javascript" src="../../../js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="../../../js/greybox.js"></script>
		<script type="text/javascript" src="../../../js/jquery-ui-1.7.custom.min.js"></script>
		<script type="text/javascript" src="../../../js/jquery.dynatree.js"></script>
		<script type="text/javascript" src="../../../js/jquery.tmpl.1.1.1.js"></script>
    	<script type="text/javascript" src="../../../js/ui.multiselect.js"></script>
    	<script type="text/javascript" src="../../../js/combos.js"></script>    
    	<script type="text/javascript" src="../../../js/split.js"></script>
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
    var wizard_current = <?php echo ($add) ? "0" : "1" ?>;

    var is_monitor = <?php echo ($plugin_type == "2") ? "true" : "false" ?>;
    var current_plugin_id = <?php echo ($rule->plugin_id != "") ? $rule->plugin_id : '""' ?>;
	function wizard_goto(num) {
		document.getElementById('wizard_'+wizard_current).style.display = "none";
		document.getElementById('link_'+wizard_current).className = "normal";
		wizard_current = num;
		wizard_refresh();
		if (num == 3) {
			init_sids(current_plugin_id,is_monitor);
		}
		if (num == 4) {
			init_network();
		}
		if (num == 6) {
			init_sensor();
		}
	}
	function wizard_refresh() {
		document.getElementById('wizard_'+(wizard_current)).style.display = "block";
		document.getElementById('link_'+wizard_current).className = "bold";
	}
    function wizard_next() {
    	document.getElementById('wizard_'+wizard_current).style.display = "none";
		if (wizard_current == 0)  document.getElementById('steps').style.display = "";
		else document.getElementById('link_'+wizard_current).className = "normal";
    	wizard_current++;
    	if (wizard_current >= 17) {
        	save_all();
    		document.getElementById('frule').submit();
    	} else {
			if (wizard_current == 10 && !is_monitor) { // Skip monitor options (detector selected)
				wizard_current = 13;
    		}
    		document.getElementById('wizard_'+(wizard_current)).style.display = "block";
    		if (wizard_current == 4) {
        		init_network();
    		}
    		if (wizard_current == 6) {
        		init_sensor();
    		}
    	}
		// Update steps
		if (wizard_current < 17) {
			document.getElementById('step_'+wizard_current).style.display = "";
			document.getElementById('link_'+wizard_current).className = "bold";
		}
    }
    var customDataParser = function(data) {
        if ( typeof data == 'string' ) {
            var pattern = /^(\s\n\r\t)*\+?$/;
            var selected, line, lines = data.split(/\n/);
            data = {};
            $('#msg').html('');
            for (var i in lines) {
                line = lines[i].split("=");
                if (!pattern.test(line[0])) {
                    if (i==0 && line[0]=='Total') {
                        $('#msg').html("<?=_("Total plugin sids found:")?> <b>"+line[1]+"</b>");
                    } else {
                        // make sure the key is not empty
                        selected = (line[0].lastIndexOf('+') == line.length - 1);
                        if (selected) line[0] = line.substr(0,line.length-1);
                        // if no value is specified, default to the key value
                        data[line[0]] = {
                            selected: false,
                            value: line[1] || line[0]
                        };
                    }
                }
            }
        } else {
            this._messages($.ui.multiselect.constante.MESSAGE_ERROR, $.ui.multiselect.locale.errorDataFormat);
            data = false;
        }
        return data;
    };
    function rm_sids() {
		var selectbox = document.getElementById('pluginsids');
    	var i;
    	for(i=selectbox.options.length-1;i>=0;i--) {
    		if(selectbox.options[i].selected) selectbox.remove(i);
    	}
    }
    function init_sids(id,m) {
		is_monitor = m;
    	$(".multiselect_sids").multiselect({
            searchDelay: 700,
            dividerLocation: 0.5,
            remoteUrl: 'popup/top/plugin_sid.php',
            remoteParams: { plugin_id: id },
            nodeComparator: function (node1,node2){ return 1 },
            dataParser: customDataParser,
        });
    }
	function save_sids() {
		var current_sid = document.getElementById('plugin_sid').value;
		if (!current_sid.match(/\d\:PLUGIN\_SID/)) {
			var plugin_sid_list = getselectedcombovalue('pluginsids');
			if (plugin_sid_list != "") {
				document.getElementById('plugin_sid').value = plugin_sid_list;
				document.getElementById('plugin_sid_list').value = plugin_sid_list;
			} else {
				document.getElementById('plugin_sid').value = "ANY";
				document.getElementById('plugin_sid_list').value = "";
			}
		}
	}
	function init_network() {
		load_tree();
	}
	var layer_i = null;
	var nodetree_i = null;
	var i=1;
	var layer_j = null;
	var nodetree_j = null;
	var j=1;
	function load_tree(filter)
	{
		var combo2 = "toselect";
		var suf2 = "to";
		if (nodetree_j!=null) {
			nodetree_j.removeChildren();
			$(layer_j).remove();
		}
		layer_j = '#srctree'+j;
		$('#container'+suf2).append('<div id="srctree'+j+'" style="width:100%"></div>');
		$(layer_j).dynatree({
			initAjax: { url: "draw_tree.php", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				if (dtnode.data.url.match(/CCLASS/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++)
						addto(combo2,children[c].data.url,children[c].data.url)
				} else {
					addto(combo2,dtnode.data.url,dtnode.data.url);
				}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
				dtnode.appendAjax({
					url: "draw_tree.php",
					data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
				});
			}
		});
		nodetree_j = $(layer_j).dynatree("getRoot");
		j=j+1;
		
		var combo1 = "fromselect";
		var suf1 = "from";
		if (nodetree_i!=null) {
			nodetree_i.removeChildren();
			$(layer_i).remove();
		}
		layer_i = '#srctree'+i;
		$('#container'+suf1).append('<div id="srctree'+i+'" style="width:100%"></div>');
		$(layer_i).dynatree({
			initAjax: { url: "draw_tree.php", data: {filter: filter} },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				if (dtnode.data.url.match(/CCLASS/)) {
					// add childrens if is a C class
					var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
					for (c=0;c<children.length; c++)
						addto(combo1,children[c].data.url,children[c].data.url)
				} else {
					addto(combo1,dtnode.data.url,dtnode.data.url);
				}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode){
				dtnode.appendAjax({
					url: "draw_tree.php",
					data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
				});
			}
		});
		nodetree_i = $(layer_i).dynatree("getRoot");
		i=i+1;

		
	}
	function save_network() {
		selectall('fromselect');
		selectall('toselect');
		var from_list = getselectedcombovalue('fromselect');
		var to_list = getselectedcombovalue('toselect');
		var port_from_list = document.getElementById('port_from_list').value;
		var port_to_list = document.getElementById('port_to_list').value;
		if (from_list != "") {
			document.getElementById('from').value = "LIST";
			document.getElementById('from_list').value = from_list;
		} else {
			document.getElementById('from').value = "ANY";
			document.getElementById('from_list').value = "";
		}
		if (to_list != "") {
			document.getElementById('to').value = "LIST";
			document.getElementById('to_list').value = to_list;
		} else {
			document.getElementById('to').value = "ANY";
			document.getElementById('to_list').value = "";
		}
		if (port_from_list != "" && port_from_list != "ANY") document.getElementById('port_from').value = "LIST";
		if (port_to_list != "" && port_to_list != "ANY") document.getElementById('port_to').value = "LIST"; 
	}
	function init_sensor() {
		$(".multiselect_sensor").multiselect({
            searchDelay: 700,
            dividerLocation: 0.5,
            remoteUrl: 'popup/top/sensor.php',
            nodeComparator: function (node1,node2){ return 1 },
            dataParser: customDataParser,
        });
	}
	function save_sensor() {
		var sensor_list = getselectedcombovalue('sensorselect');
		if (sensor_list != "") {
			document.getElementById('sensor').value = "LIST";
			document.getElementById('sensor_list').value = sensor_list;
		} else {
			document.getElementById('sensor').value = "ANY";
			document.getElementById('sensor_list').value = "";
		}
	}
	function save_all() {
		save_sids();
		save_network();
		save_sensor();
	}
	function search_plugin(q) {
		var str = "";
		var _regex = new RegExp( "^" + q, "i");
		$('.plugin_line').each(function() {
			val = $(this).attr("id");
			if (!val.match(_regex)) {
				str += val;
				document.getElementById(val).style.display='none';
			} else {
				document.getElementById(val).style.display='block';
			}
		});
		//alert(str);
	}
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
		var title = "";
		if (url.match(/top\=from/)) title = "<?php echo _("From")." ("._("Network").")" ?>";
		if (url.match(/top\=to/)) title = "<?php echo _("To")." ("._("Network").")" ?>";
		if (url.match(/top\=plugin\_sid/)) title = "<?php echo _("Plugin Sid") ?>";
		if (url.match(/top\=plugin\_id/)) title = "<?php echo _("Plugin Id") ?>";
		GB_show(title,'../../'+url,400,'90%');
	/*
	var iframe = window.parent.document.getElementById('fenetre');
    var fond = window.parent.document.getElementById('fond');
    iframe.childNodes[0].src = url;    
    taille();
    fond.style.display = 'block';
    iframe.style.display = 'block';
	*/
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
	<!-- #################### main container #################### -->
  <form method="POST" id="frule" name="frule" action="../../include/utils.php?query=save_rule">
	<table class="transparent" width="100%">
		<tr>
			<td class="nobborder" id="steps" style="border-bottom:1px solid #EEEEEE<?php if ($add) echo ";display:none" ?>">
				<table class="transparent">
					<?php
					$display = ($rule->plugin_id > 0) ? "" : ";display:none";
					?>
					<tr>
						<td class="nobborder"><img src="../../../pixmaps/wand.png" alt="wizard"></img></td>
						<td class="nobborder" style="font-size:11px" nowrap><?php echo _("Rule")?> <b>configuration</b>: </td>
						<td class="nobborder" style="font-size:11px" id="step_1" nowrap><a href='' onclick='wizard_goto(1);return false;' class="bold" id="link_1"><?php echo _("Rule name") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_2" nowrap> > <a href='' onclick='wizard_goto(2);return false;' class="normal" id="link_2"><?php echo _("Plugin") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_3" nowrap> > <a href='' onclick='wizard_goto(3);return false;' class="normal" id="link_3"><?php echo _("Plugin Sid") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_4" nowrap> > <a href='' onclick='wizard_goto(4);return false;' class="normal" id="link_4"><?php echo _("Network") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_5" nowrap> > <a href='' onclick='wizard_goto(5);return false;' class="normal" id="link_5"><?php echo _("Protocol") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_6" nowrap> > <a href='' onclick='wizard_goto(6);return false;' class="normal" id="link_6"><?php echo _("Sensor") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_7" nowrap> > <a href='' onclick='wizard_goto(7);return false;' class="normal" id="link_7"><?php echo _("Ocurrence") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_8" nowrap> > <a href='' onclick='wizard_goto(8);return false;' class="normal" id="link_8"><?php echo _("Timeout") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_9" nowrap> > <a href='' onclick='wizard_goto(9);return false;' class="normal" id="link_9"><?php echo _("Reliability") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo ($plugin_type == "2") ? $display : ";display:none" ?>" id="step_10" nowrap> > <a href='' onclick='wizard_goto(10);return false;' class="normal" id="link_10"><?php echo _("Monitor") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo ($plugin_type == "2") ? $display : ";display:none" ?>" id="step_11" nowrap> > <a href='' onclick='wizard_goto(11);return false;' class="normal" id="link_11"><?php echo _("Monitor intv") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo ($plugin_type == "2") ? $display : ";display:none" ?>" id="step_12" nowrap> > <a href='' onclick='wizard_goto(12);return false;' class="normal" id="link_12"><?php echo _("Monitor abs") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_13" nowrap> > <a href='' onclick='wizard_goto(13);return false;' class="normal" id="link_13"><?php echo _("Sticky") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_14" nowrap> > <a href='' onclick='wizard_goto(14);return false;' class="normal" id="link_14"><?php echo _("Sticky diff") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_15" nowrap> > <a href='' onclick='wizard_goto(15);return false;' class="normal" id="link_15"><?php echo _("Other") ?></a></td>
						<td class="nobborder" style="font-size:11px<?php echo $display ?>" id="step_16" nowrap> > <a href='' onclick='wizard_goto(16);return false;' class="normal" id="link_16"><?php echo _("User data") ?></a></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="center nobborder" style="padding-top:20px">
				<table class="transparent" style="border-width: 0px" align="center">
				<tr>
			
				<!-- #################### left container #################### -->
				<td class="container" style="vertical-align: top">
				<table class="transparent" width="100%">
				<tr><td class="nobborder">
				<div id="wizard_0"<?php if (!$add) echo " style='display:none'"?>>
				<table class="transparent">
					<tr>
						<th style="white-space: nowrap; padding: 5px;font-size:12px"><?php echo _("Do you want to define a new rule now") ?>?</th>
					</tr>
					<tr>
						<td class="center nobborder" style="padding-top:10px">
							<input type="button" value="Yes" onclick="wizard_next()" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important"></input>
							<input type="button" value="Later" onclick="onClickCancel(<?php echo $directive . ',' . $new_level; ?>)"></input>
						</td>
					</tr>
				</table>
				</div>
				
				<?php
			include ("global.inc.php"); ?>
				
				<div id="wizard_4" style="display:none">
				<table class="transparent">
				<tr><td class="container">
				<?php
			include ("network.inc.php"); ?>
				</td></tr>
				</table>
				</div>
			
				<div id="wizard_5" style="display:none">
				<table class="transparent">
				<tr><td class="container">
				<?php
			include ("protocol.inc.php"); ?>
				</td></tr>
				</table>
				</div>
			
				<div id="wizard_6" style="display:none">
				<table class="transparent">
				<tr><td class="container">
				<?php include ("sensor.inc.php"); ?>
				</td></tr>
				</table>
				</div>
			
				<?php include ("risk.inc.php"); ?>
			
				<?php include ("monitor.inc.php"); ?>
			
				<?php include ("sticky.inc.php"); ?>
			
				<div id="wizard_15" style="display:none">
				<?php include ("$base_dir/directive_editor/editor/rule/other.inc.php"); ?>
				</div>
				
				<div id="wizard_16" style="display:none">
				<?php include ("$base_dir/directive_editor/editor/rule/userdata.inc.php"); ?>
				</div>
				
				</td>
				</tr>
				</table>
				</td>
				<!-- #################### END: down container #################### -->
				
				</tr>
				<tr><td class="container" colspan="2" style="padding-top:20px">
					<input type="hidden" name="directive" value="<?php echo $directive; ?>" />
					<input type="hidden" name="level" value="<?php echo $level; ?>" />
					<input type="hidden" name="id" value="<?php echo $id; ?>" />
					<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
					<input type="hidden" name="type" id="type" value="<?php echo getPluginType($rule->plugin_id); ?>" />
					<input type="button" style="width: <?php echo ($rule->plugin_id) ? "80" : "130" ?>px; cursor:pointer;" value="<?php echo ($rule->plugin_id) ? _("Cancel") : gettext('Back to directives'); ?>" onclick="onClickCancel(<?php echo $directive . ',' . $new_level; ?>)"/>
					<?php if ($rule->plugin_id) { ?>
					&nbsp;<input type="button" style="background: url(../../../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important" value="<?php echo _("Save and finish") ?>" onclick="save_all();document.getElementById('frule').submit()">
					<?php } ?>
				</td></tr>
			
				</table>
			</td>
		</tr>
	</table>
	
	</form>
	
	<!-- #################### END: main container #################### -->
	<?php
dbClose();
?>
	</body>
</html>
