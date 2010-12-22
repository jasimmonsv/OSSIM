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
require_once 'classes/Security.inc';
require_once ('classes/Session.inc');
Session::logcheck("MenuIntelligence", "PolicyPolicy");
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$opensource = (!preg_match("/pro|demo/i",$conf->get_conf("ossim_server_version", FALSE))) ? true : false;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?=_("OSSIM Framework")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv=""Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css" />
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css" />
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<link rel="stylesheet" type="text/css" href="../style/greybox.css" />
	<link rel="stylesheet" type="text/css" href="../style/ui.multiselect.css" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/ui.multiselect.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript">
		var tab_actual = 'tabs-1';
		//var loading = '<br><img src="../pixmaps/theme/ltWait.gif" border="0" align="absmiddle"> <?php echo _("Loading resource tree, please wait...") ?>';
		var reloading = '<img src="../pixmaps/theme/ltWait.gif" border="0" align="absmiddle"> <?php echo _("Re-loading data...") ?>';
		var layer = null;
		var nodetree = null;
		var suf = "c";
		var i=1;
	
		function load_tree(filter)
		{
			combo = (suf=="c") ? 'sources' : 'dests';
			if (nodetree!=null) {
				nodetree.removeChildren();
				$(layer).remove();
			}
			layer = '#srctree'+i;
			$('#container'+suf).append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layer).dynatree({
				initAjax: { url: "draw_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					if (dtnode.data.url.match(/CCLASS/)) {
						// add childrens if is a C class
						var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
						for (c=0;c<children.length; c++)
							addto(combo,children[c].data.url,children[c].data.url)
					} else {
						addto(combo,dtnode.data.url,dtnode.data.url);
					}
					drawpolicy();
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "draw_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1
		}
		
		var layerp = null;
		var nodetreep = null;
		
		function load_ports_tree()
		{
			if (nodetreep!=null) {
				nodetreep.removeChildren();
				$(layerp).remove();
			}
			layerp = '#srctree'+i;
			$('#containerp').append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layerp).dynatree({
				initAjax: { url: "draw_ports_tree.php" },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					if (dtnode.data.url!='noport') addto('ports',dtnode.data.url,dtnode.data.url);
					drawpolicy();
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "draw_ports_tree.php",
						data: {key: dtnode.data.key}
					});
				}
			});
			nodetreep = $(layerp).dynatree("getRoot");
			i=i+1
		}
		
		var layerse = null;
		var nodetreese = null;
		function load_sensors_tree()
		{
			if (nodetreese!=null) {
				nodetreese.removeChildren();
				$(layerse).remove();
			}
			layerse = '#srctree'+i;
			$('#containerse').append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layerse).dynatree({
				initAjax: { url: "draw_sensors_tree.php" },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					addto('sensors',dtnode.data.url,dtnode.data.url);
					drawpolicy();
				},
				onDeactivate: function(dtnode) {}
			});
			nodetreese = $(layerse).dynatree("getRoot");
			i=i+1
		}
		
		var actions_loaded = false;
		function load_multiselect()
		{
			if (!actions_loaded) {
				$("#loading_actions").hide();
				$("#actions").multiselect({
					dividerLocation: 0.5,
					searchable: false,
					nodeComparator: function (node1,node2){ return 1 }
				});
				actions_loaded = true;
			}
		}
			
		function GB_onclose()
		{
			if (tab_actual=='tabs-1' || tab_actual=='tabs-2') {
				suf = (tab_actual=='tabs-1') ? 'c' : 'd';
				load_tree($('filter'+suf).val());
			} else if (tab_actual=='tabs-3') {
				load_ports_tree();
			} else if (tab_actual=='tabs-4') {
				$('#plugins').html(reloading);
				$.ajax({
					type: "GET",
					url: "getpolicydata.php?tab=plugins<? if (preg_match("/^\d+$/",$_GET['id'])) echo "&id=".$_GET['id']?>",
					data: "",
					success: function(msg) {
						$('#plugins').html(msg);
						$("a.greybox").click(function(){
							var t = this.title || $(this).text() || this.href;
							GB_show(t,this.href,490,"92%");
							return false
						});
					}
				});
			} else if (tab_actual=='tabs-5') {
				load_sensors_tree();
			} else if (tab_actual=='tabs-6') {
				$('#targets').html(reloading);
				$.ajax({
					type: "GET",
					url: "getpolicydata.php?tab=targets",
					data: "",
					success: function(msg) {
						$('#targets').html(msg);
					}
				});
			} else if (tab_actual=='tabs-8') {
				$('#groups').html(reloading);
				$.ajax({
					type: "GET",
					url: "getpolicydata.php?tab=groups",
					data: "",
					success: function(msg) {
						$('#groups').html(msg);
					}
				});
			} else if (tab_actual=='tabs-10') {
				$("#actions").multiselect('destroy');
				$('#responses').html(reloading);
				$("#loading_actions").show();
				actions_loaded = false;
				$.ajax({
					type: "GET",
					url: "getpolicydata.php?tab=responses",
					data: "",
					success: function(msg) {
						$('#actions').html(msg);
						load_multiselect();
					}
				});
			}
		}
		
		$(document).ready(function()
		{
			// Textareas
			$('textarea').elastic();
			
			// Tabs
			$('#tabs').tabs({
				select: function(event, ui) { 
					tab_actual = ui.panel.id
					// default loading tree for source /dest
					if (tab_actual=='tabs-1' || tab_actual=='tabs-2') {
						suf = (tab_actual=='tabs-1') ? 'c' : 'd';
						load_tree($('filter'+suf).val());
					}
					// default load tree for ports
					if (tab_actual=='tabs-3') load_ports_tree();
					// default load tree for sensors
					if (tab_actual=='tabs-5') load_sensors_tree();
					// default load tree for sensors
					if (tab_actual=='tabs-10') setTimeout('load_multiselect()',500);
					drawpolicy();
				}
			});
			$('#tabs').tabs('disable', 8);
			// Tree
			load_tree("");
			// graybox
			$("a.greybox").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   GB_show(t,this.href,490,"92%");
			   return false;
			});
			drawpolicy();
		});
		
		function disen(element,text)
		{
			if (element.attr('disabled') == true) {
				element.attr('disabled', '');
				text.removeClass("thgray");
			} else {
				element.attr('disabled', 'disabled');
				text.addClass("thgray");
			}
		}
		
		function dis(element,text) {
			element.attr('disabled', 'disabled');
			text.addClass("thgray");
		}
		
		function en(element,text) {
			element.attr('disabled', '');
			text.removeClass("thgray");
		}
	
		// show/hide some options
		function tsim(val)
		{
			valsim = val;
			/*
			$('#correlate').toggle();
			$('#cross_correlate').toggle();
			$('#store').toggle();
			$('#qualify').toggle();
			*/
			disen($('input[name=correlate]'),$('#correlate_text'));
			disen($('input[name=cross_correlate]'),$('#cross_correlate_text'));
			disen($('input[name=store]'),$('#store_text'));
			disen($('input[name=qualify]'),$('#qualify_text'));
			/*if (valsim==0) {
				$('input[name=correlate]')[1].checked = true;
				$('input[name=cross_correlate]')[1].checked = true;
				$('input[name=store]')[1].checked = true;
				$('input[name=qualify]')[1].checked = true;
			}*/
			if (valsim==0 && valsem==0) {
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
				//$('#ralarms').hide();
				//$('#revents').hide();
				//$('#rtitle').hide();
				$('input[name=resend_alarms]')[1].checked = true;
				$('input[name=resend_events]')[1].checked = true;
				$('input[name=multi]')[1].checked = true;
			} else {
				<? if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) { ?>
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
				$('input[name=multi]')[0].checked = true;
				<? } ?>
				//$('#ralarms').show();
				//$('#revents').show();
				//$('#rtitle').show();
			}
		}
	
		function tsem(val)
		{
			valsem = val
			//$('#sign').toggle();
			disen($('input[name=sign]'),$('#sign_text'));
			/*if (valsem==0) {
				$('input[name=sign]')[1].checked = true;
			}*/
			if (valsim==0 && valsem==0) {
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
				//$('#ralarms').hide();
				//$('#revents').hide();
				//$('#rtitle').hide();
				$('input[name=resend_alarms]')[1].checked = true;
				$('input[name=resend_events]')[1].checked = true;
				$('input[name=multi]')[1].checked = true;
			} else {
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
				//$('#ralarms').show();
				//$('#revents').show();
				//$('#rtitle').show();
				$('input[name=multi]')[0].checked = true;
			}
		}
		
		function tmulti(val) 
		{
			if (val == 1) {
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
			} else {
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
			}
		}
		
		function submit_form(form)
		{
			if(!$('input[type="button"].sok').hasClass('buttonoff')){
				selectall('sources');
				selectall('dests');
				selectall('ports');
				selectall('sensors');
				//selectall('actions');
				form.submit();
			}
		}
		
		function putit(id,txt)
		{
			if (txt == '') {
				$(id).removeClass('bgred').removeClass('bggreen').addClass('bgred');
				$("#img"+id.substr(3)).attr("src","../pixmaps/tables/cross-small.png");
				$(id).html(txt);
			} else {
				$(id).removeClass('bgred').removeClass('bggreen').addClass('bggreen');
				$("#img"+id.substr(3)).attr("src","../pixmaps/tables/tick-small.png");
				$(id).html(txt);
			}
		}
		
		function iscomplete()
		{
			if ($("#imgsource").attr("src").match(/tick/) && $("#imgdest").attr("src").match(/tick/) &&
			$("#imgports").attr("src").match(/tick/) && $("#imgplugins").attr("src").match(/tick/) &&
			$("#imgtime").attr("src").match(/tick/) && $("#imgmore").attr("src").match(/tick/) &&
			$("#imgother").attr("src").match(/tick/))
				return true;
			return false;
		}
		
		function drawpolicy()
		{
			var elems = getcombotext('sources');
			for (var i=0,txt = ''; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			putit("#tdsource",txt);
			//
			var elems = getcombotext('dests');
			for (var i=0,txt=''; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			putit("#tddest",txt);
			//
			//var elems = getselectedcombotext('ports');
			var elems = getcombotext('ports');
			for (var i=0,txt=''; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			putit("#tdports",txt);
			//
			if ($('#plugin_ANY').is(":checked")) {
				$(':checkbox').each(function(i){ 
					if ($(this).attr('id').match(/^plugin/) && !$(this).attr('id').match(/^plugin_ANY/))
						$(this).attr("disabled", "disabled").attr('checked',false);
				});
			} else {
				$(':checkbox').each(function(i){ 
					if ($(this).attr('id').match(/^plugin/)) $(this).removeAttr("disabled");
				});
			}
			//
			txt = '';
			$(':checkbox:checked').each(function(i){ 
				if ($(this).attr('id').match(/^plugin/)) txt = txt + $(this).attr('id').substr(7) + "<br>";
			});
			putit("#tdplugins",txt);
			//var elems = getselectedcombotext('sensors');
			
			var elems = getcombotext('sensors');
			for (var i=0,txt=''; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			putit("#tdsensors",txt);
			//
			txt = '';
			$(':checkbox:checked').each(function(i){ 
				if ($(this).attr('id').match(/^target/)) txt = txt + $(this).attr('id').substr(7) + "<br>";
			});
			putit("#tdtargets",txt);
			//
			txt = "<?=_('Begin')?>: <b>" + document.fop.begin_day.options[document.fop.begin_day.selectedIndex].text + " - " + document.fop.begin_hour.options[document.fop.begin_hour.selectedIndex].text + "</b><br>";
			txt = txt + "<?=_('End')?>: <b>" + document.fop.end_day.options[document.fop.end_day.selectedIndex].text + " - " + document.fop.end_hour.options[document.fop.end_hour.selectedIndex].text + "</b><br>";
			putit("#tdtime",txt);
			//
			txt = "<?=_('Policy Group')?>: <b> " + document.fop.group.options[document.fop.group.selectedIndex].text + "</b><br>";
			txt = txt + "<?=_('Description')?>: <i> " + document.fop.descr.value + "</i><br>";
			txt = txt + "<?=_('Active')?>: <b> " + ($("input[name='active']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Sign')?>: <b> " + ($("input[name='sign']:checked").val()==1 ? "<?=_('Line')?>" : "<?=_('Block')?>") + "</b><br>";
			txt = txt + "<?=_('Logger')?>: <b> " + ($("input[name='sem']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('SIEM')?>: <b> " + ($("input[name='sim']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			putit("#tdmore",txt);
			//
			txt = "<?=_('Priority')?>: <b>" + document.fop.priority.options[document.fop.priority.selectedIndex].text + "</b><br>";
			txt = txt + "<?=_('Correlate')?>: <b> " + ($("input[name='correlate']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Cross Correlate')?>: <b> " + ($("input[name='cross_correlate']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Store')?>: <b> " + ($("input[name='store']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Qualify')?>: <b> " + ($("input[name='qualify']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Resend Alarms')?>: <b> " + ($("input[name='resend_alarms']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			txt = txt + "<?=_('Resend Events')?>: <b> " + ($("input[name='resend_events']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b><br>";
			putit("#tdother",txt);
			//
			if (iscomplete()) {
				//$('input[type="button"].sok').removeAttr("disabled");
				$('input[type="button"].sok').removeClass('buttonoff').addClass('button');
			} else {
				//$('input[type="button"].sok').attr("disabled", "disabled");
				$('input[type="button"].sok').removeClass('button').addClass('buttonoff');
			}
		}
	
		function manual_addto (what,val)
		{
			if (fnValidateIPAddress(val)) {
				if (confirm('<?=_("Do you want to add it to the Asset Database?")?>')) {
					document.getElementById('inventory_loading_'+what).innerHTML = "<img src='../pixmaps/loading.gif' width='20'>";
					$.ajax({
						type: "GET",
						url: "newhost_response.php?host="+val,
						data: "",
						success: function(msg) {
							document.getElementById('inventory_loading_'+what).innerHTML = "";
							alert(msg);
							addto(what,'HOST:'+val,'HOST:'+val);
						}
					});
				} else {
					addto(what,'HOST:'+val,'HOST:'+val);
				}
			} else {
				alert("<?=_("Type a correct IPv4 address")?>");
			}
		}
	
		function fnValidateIPAddress(ipaddr)
		{
			//Remember, this function will validate only Class C IP.
			//change to other IP Classes as you need
			ipaddr = ipaddr.replace( /\s/g, "") //remove spaces for checking

			var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/; //regex. check for digits and in
												  //all 4 quadrants of the IP
			if (re.test(ipaddr)) {
				//split into units with dots "."

				var parts = ipaddr.split(".");
				//if the first unit/quadrant of the IP is zero
				if (parseInt(parseFloat(parts[0])) == 0) {
					return false;
				}
				//if the fourth unit/quadrant of the IP is zero

				if (parseInt(parseFloat(parts[3])) == 0) {
					return false;
				}
				//if any part is greater than 255
				for (var i=0; i<parts.length; i++) {
					if (parseInt(parseFloat(parts[i])) > 255){

						return false;
					}
				}
				return true;
			} else {
				return false;
			}
		}
	
	</script>
	
	<style type='text/css'>
		.ptab   { font-weight:bold;font-size:12px;}
		.size10 {font-size:10px;}
		.tab_table {margin: auto;}
		.container_ptree {width:350px; padding-top:5px;}
		textarea { height: 45px; width:100%;}
		#p_conseq {width: 350px;}
		#p_conseq th {width: 130px;}
		.cont_elem{ width: 90%; float: left;}
	</style>
</head>
<body>
                                                                                
<?php
include ("../hmenu.php");
require_once ('classes/Policy.inc');
require_once ('classes/Policy_group.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Port_group.inc');
require_once ('classes/Plugingroup.inc');
require_once ('classes/Server.inc');
require_once ('classes/Action.inc');
require_once ('classes/Response.inc');
require_once ('ossim_db.inc');

$db      = new ossim_db();
$conn    = $db->connect();
$id      = GET('id');
$group   = GET('group');
$order   = GET('order');
$insert  = (GET('insertafter') != "") ? GET('insertafter') : GET('insertbefore');
ossim_valid($id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("id"));
ossim_valid($group, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("group"));
ossim_valid($order, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("order"));
if (ossim_error()) {
    die(ossim_error());
}
/*
$ossim_hosts = array();
$ossim_nets = array();
if ($host_list = Host::get_list($conn, "", "ORDER BY hostname")) foreach($host_list as $host) $ossim_hosts[$host->get_ip() ] = $host->get_hostname();
if ($net_list = Net::get_list($conn, "ORDER BY name")) {
    foreach($net_list as $net) {
        $net_name = $net->get_name();
        $net_ips = $net->get_ips();
        $hostin = array();
        foreach($ossim_hosts as $ip => $hname) if ($net->isIpInNet($ip, $net_ips)) $hostin[$ip] = $hname;
        $ossim_nets[$net_name] = $hostin;
    }
}*/
// default vars
$priority        = -1;
$correlate       = 1;
$cross_correlate = 1;
$store  		 = 1;
$qualify 		 = 1;
$active 		 = 1;
$order 			 = 0;
$resend_alarm 	 = 1;
$resend_event 	 = 1;
$sign 			 = 0;
$sem 			 = 0;
$sim 			 = 1;

if ($group == "") 
	$group = 1;
	
$desc = "";

$sources = $dests = $ports = $plugingroups = $sensors = $targets = $actions = array();
$timearr = array(1,0,7,23);
$actions_saved = array();

if ($id != "") {
    settype($id, "int");
    if ($policies = Policy::get_list($conn, "WHERE policy.order=$id")) {
        $policy = $policies[0];
        $id = $policy->get_id();
        $priority = $policy->get_priority();
        $active = $policy->get_active();
        $group = $policy->get_group();
        $order = $policy->get_order();
        if ($source_host_list = $policy->get_hosts($conn, 'source')) foreach($source_host_list as $source_host) {
            //$host = Host::ip2hostname($conn, $source_host->get_host_ip());
            $sources[] = ($host == "any") ? "ANY" : "HOST:" . $source_host->get_host_ip();
        }
        if ($source_net_list = $policy->get_nets($conn, 'source')) foreach($source_net_list as $source_net) {
            $sources[] = "NETWORK:" . $source_net->get_net_name();
        }
        if ($source_host_list = $policy->get_host_groups($conn, 'source')) foreach($source_host_list as $source_host_group) {
            $sources[] = "HOST_GROUP:" . $source_host_group->get_host_group_name();
        }
        if ($source_net_list = $policy->get_net_groups($conn, 'source')) foreach($source_net_list as $source_net_group) {
            $sources[] = "NETWORK_GROUP:" . $source_net_group->get_net_group_name();
        }
        //
        if ($dest_host_list = $policy->get_hosts($conn, 'dest')) foreach($dest_host_list as $dest_host) {
            //$host = Host::ip2hostname($conn, $dest_host->get_host_ip());
            $dests[] = ($host == "any") ? "ANY" : "HOST:" . $dest_host->get_host_ip();
        }
        if ($dest_net_list = $policy->get_nets($conn, 'dest')) foreach($dest_net_list as $dest_net) {
            $dests[] = "NETWORK:" . $dest_net->get_net_name();
        }
        if ($dest_host_list = $policy->get_host_groups($conn, 'dest')) foreach($dest_host_list as $dest_host_group) {
            $dests[] = "HOST_GROUP:" . $dest_host_group->get_host_group_name();
        }
        if ($dest_net_list = $policy->get_net_groups($conn, 'dest')) foreach($dest_net_list as $dest_net_group) {
            $dests[] = "NETWORK_GROUP:" . $dest_net_group->get_net_group_name();
        }
        //
        if ($port_list = $policy->get_ports($conn)) foreach($port_list as $port_group) {
            $ports[] = $port_group->get_port_group_name();
        }
        foreach($policy->get_plugingroups($conn, $policy->get_id()) as $pgroup) {
            $plugingroups[] = $pgroup['id'];
        }
        empty($sensor_exist);
        $sensor_exist=$policy->exist_sensors($conn);
        if ($sensor_list = $policy->get_sensors($conn)) foreach($sensor_list as $sensor) {
           if($sensor_exist[$sensor->get_sensor_name()]!='false'){
               $sensors[] = str_replace("any","ANY",$sensor->get_sensor_name());
            }
        }
        $policy_time = $policy->get_time($conn);
        $timearr[0] = $policy_time->get_begin_day();
        $timearr[1] = $policy_time->get_begin_hour();
        $timearr[2] = $policy_time->get_end_day();
        $timearr[3] = $policy_time->get_end_hour();
        if ($target_list = $policy->get_targets($conn)) foreach($target_list as $target) {
            $targets[] = $target->get_target_name();
        }
        $desc = html_entity_decode($policy->get_descr());
        $role_list = $policy->get_role($conn);
        foreach($role_list as $role) {
            $correlate = ($role->get_correlate()) ? 1 : 0;
            $cross_correlate = ($role->get_cross_correlate()) ? 1 : 0;
            $store = ($role->get_store()) ? 1 : 0;
            $qualify = ($role->get_qualify()) ? 1 : 0;
            $resend_alarm = ($role->get_resend_alarm()) ? 1 : 0;
            $resend_event = ($role->get_resend_event()) ? 1 : 0;
            $sign = ($role->get_sign()) ? 1 : 0;
            $sem = ($role->get_sem()) ? 1 : 0;
            $sim = ($role->get_sim()) ? 1 : 0;
            break;
        }
        // responses
        $actions_saved = array();
		if ($response_list = Response::get_list($conn, "WHERE descr='policy $id'")) {
            if ($action_list = $response_list[0]->get_actions($conn)) {
                foreach($action_list as $act) { $actions[] = $act->get_action_id(); $actions_saved[] = $act; }
            }
        }
    }
} else {
    $ports[] = "ANY";
    $targets[] = "ANY";
    $sensors[] = "ANY";
}
if ($insert != "") {
    settype($insert, "int");
    if ($policies = Policy::get_list($conn, "WHERE policy.order=$insert")) {
        $order = $policies[0]->get_order();
        $group = $policies[0]->get_group();
        if (GET('insertafter') != "") $order++; // insert after
        
    }
}
?>

<form method="POST" name="fop" action="<?php echo ($id != "") ? "modifypolicy.php?id=$id" : "newpolicy.php" ?>">
	<input type="hidden" name="clone" value="<?=GET('clone')?>"/>
	<input type="hidden" name="insert" value="insert"/>
	<input type="hidden" name="order" value="<?php echo $order ?>"/>

	<div id="elem_list" style="display:none"></div>
	<div id="port_list" style="display:none"></div>
	<div id="sensor_list" style="display:none"></div>

<div id="tabs">
	<ul>
		<li><a href="#tabs-1" class="ptab"><?php echo _("Source") . required() ?></a></li>
		<li><a href="#tabs-2" class="ptab"><?php echo _("Dest") . required() ?></a></li>
		<li><a href="#tabs-3" class="ptab"><?php echo _("Ports") . required() ?></a></li>
		<li><a href="#tabs-4" class="ptab"><?php echo _("Plugin Groups") . required() ?></a></li>
		<li><a href="#tabs-5" class="ptab"><?php echo _("Sensors") . required() ?></a></li>
		<li <?= ($opensource) ? "style='display:none'" : "" ?>><a href="#tabs-6" class="ptab"><?php echo _("Install in") . required() ?></a></li>
		<li><a href="#tabs-7" class="ptab"><?php echo _("Time Range") . required() ?></a></li>
		<li><a href="#tabs-8" class="ptab"><?php echo _("Policy group") . required() ?></a></li>
		<li><a href="#tabs-9" class="ptab"><img src="../pixmaps/arrow-join.png" border="0"></a></li>
		<li><a href="#tabs-10" class="ptab"><?php echo _("Policy Consequences") ?></a></li>
	</ul>

	<div id="tabs-1">
		<table class='tab_table'>
		<tr>
			<td class="nobborder" valign="top">
				<table align="center" class="noborder">
				<tr>
					<th style="background-position:top center"><?php echo _("Source") . required() ?><br/>
						<span class='size10'><a href="../net/newnetform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new net?") ?></a></span><br/>
						<span class='size10'><a href="../net/newnetgroupform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new net group?") ?></a></span><br/>
						<span class='size10'><a href="../host/newhostform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new host?") ?></a></span><br/>
						<span class='size10'><a href="../host/newhostgroupform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new host group?") ?></a></span><br/>
					</th>
					<td class="left nobborder">
						<select id="sources" name="sources[]" size="21" multiple="multiple" style="width:250px">
							<?php foreach($sources as $source) echo "<option value='$source'>$source"; ?>
						</select>
						<input type="button" class="lbutton" value=" [X] " onclick="deletefrom('sources');drawpolicy()"/>
					</td>
				</tr>
				</table>
			</td>
			<td valign="top" class="nobborder">
				<table class="noborder" align='center'>
				<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
				<tr>
					<td class="left nobborder">
						<?=_("Asset")?>: <input type="text" id="filterc" name="filterc" size='25'/>
						&nbsp;<input type="button" class="lbutton" value="<?=_("Filter")?>" onclick="load_tree(this.form.filterc.value)" /> 
							  <input type="button" class="lbutton" value="<?=_("Insert")?>" onclick="manual_addto('sources',this.form.filterc.value)" />
						<div id="containerc" class='container_ptree'></div>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		</table>
		
		<center style="padding-top:10px">
			<input type="button" class="button" value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',1);"/>
			<input type="button" class="button sok" value=" <?=_("OK")?> "  onclick="submit_form(this.form)"/>
		</center>
	</div>

	<div id="tabs-2">
		<table class='tab_table'>
		<tr>
			<td class="nobborder" valign="top">
				<table align="center" class="noborder">
				<tr>
					<th style="background-position:top center"><?php echo _("Dest") . required() ?><br/>
						<span class='size10'><a href="../net/newnetform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new net?") ?></a></span><br/>
						<span class='size10'><a href="../net/newnetgroupform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new net group?") ?></a></span><br/>
						<span class='size10'><a href="../host/newhostform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new host?") ?></a></span><br/>
						<span class='size10'><a href="../host/newhostgroupform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new host group?") ?></a></span><br/>
					</th>
					<td class="left nobborder" valign="top">
						<select id="dests" name="dests[]" size="21" multiple="multiple" style="width:250px">
							<?php foreach($dests as $dest) echo "<option value='$dest'>$dest"; ?>
						</select>
						<input type="button" value=" [X] " onclick="deletefrom('dests');drawpolicy()" class="lbutton"/>
					</td>
				</tr>
				</table>
			</td>
			
			<td valign="top" class="nobborder">
				<table class="noborder">
					<tr><td class="left nobborder" id="inventory_loading_dests"></td></tr>
					<tr>
						<td class="left nobborder" valign="top">
							<?=_("Asset")?>: <input type="text" id="filterd" name="filterd" size='20'/>&nbsp;
							<input type="button" class="lbutton" value="<?=_("Apply")?>" onclick="load_tree(this.form.filterd.value)" />
							<input type="button" class="lbutton" value="<?=_("Insert")?>" onclick="manual_addto('dests',this.form.filterd.value)"/>
							<div id="containerd" class='container_ptree'></div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
  
		<center style="padding-top:10px">
			<input type="button" class="button" value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',2);"/>
			<input type="button" class="button sok" value=" <?=_("OK")?> "  onclick="submit_form(this.form)"/>
		</center>
	</div>

	<div id="tabs-3">
		<table class='tab_table'>
		<tr>
			<th style="background-position:top center"><?php echo _("Ports") . required() ?><br/>
				<span class='size10'><a href="../port/newportform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new port group?") ?></a></span><br/>
				<span class='size10'><a href="../port/newsingleportform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new port?") ?></a></span><br/>
			</th>
			<td class="left nobborder" valign="top">
				<select id="ports" name="mboxp[]" size="20" multiple="multiple" class="multi" style="width:200px">
					<?php foreach($ports as $pgrp) echo "<option value='$pgrp'>$pgrp"; ?>
				</select>
				<input type="button" value=" [X] " class="lbutton" onclick="deletefrom('ports');drawpolicy()"/>
			</td>
			
			<td class="left nobborder" valign="top">
				<div id="containerp" class='container_ptree'></div>
			</td>
		</tr>
		</table>
		
		<center style="padding-top:10px">
			<input type="button" class="button" value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',3);"/>
			<input type="button" class="button sok" value=" <?=_("OK")?> "  onclick="submit_form(this.form)"/>
		</center>
	</div>

	<div id="tabs-4">
		<table class='tab_table'>
		<tr>
			<th style="background-position:top"> <?php echo _("Plugin Groups") . required() ?> <br/>
			<span class='size10'><a href="../policy/modifyplugingroupsform.php?action=new&withoutmenu=1" class="greybox"> <?php echo gettext("Insert new plugin group?"); ?> </a></span><br/>
			<span class='size10'><a href="../policy/plugingroups.php?withoutmenu=1" class="greybox"> <?php echo gettext("View all plugin groups"); ?></a></span><br/>
			</th>
			
			<td class="left nobborder" valign="top">
				<table class="left noborder" cellpadding="0" cellspacing="0">
					<tr>
						<td class="nobborder">
							<input type="checkbox" id="plugin_ANY" onclick="drawpolicy()" name="plugins[0]" <?php echo (in_array(0 , $plugingroups)) ? "checked='checked'" : "" ?>/> <?=_("ANY")?>
						</td>
					</tr>
					
					<tr>
						<td class="nobborder" id="plugins">
						<?php
						/* ===== plugin groups ==== */
						foreach(Plugingroup::get_list($conn) as $g) {
						?>
							<input type="checkbox" id="plugin_<?php echo $g->get_name() ?>" onclick="drawpolicy()" name="plugins[<?php echo $g->get_id() ?>]" <?php echo (in_array($g->get_id() , $plugingroups)) ? "checked='checked'" : "" ?>> <a href="../policy/modifyplugingroupsform.php?action=edit&id=<?php echo $g->get_id() ?>&withoutmenu=1" class="greybox" title="<?=_('View plugin group')?>"><?php echo $g->get_name() ?></a><br/>
						<?php
						} ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		
		<center style="padding-top:10px">
			<input type="button" class='button' value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',4);"/>
			<input type="button" class='button sok' value=" <?=_("OK")?> "  onclick="submit_form(this.form)"/>
		</center>
	</div>

	<div id="tabs-5">
		<?php if(GET('sensorNoExist')=='true'){ ?>
			<script type="text/javascript">
			  $(document).ready(function() {
				load_sensors_tree();
			  })
			</script>
		<?php } ?>
		<table class='tab_table'>
		<tr>
			<th style="background-position:top center"><?php echo _("Sensors") . required() ?><br/>
				<span class='size10'><a href="../sensor/newsensorform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new sensor?") ?></a></span><br/>
			</th>
			<td class="left nobborder" valign="top">
				<select id="sensors" name="mboxs[]" size="20" multiple="multiple" class="multi" style="width:200px">
					<?php foreach($sensors as $sensor) echo "<option value='$sensor'>$sensor"; ?>
				</select>
				<input type="button" value=" [X] " onclick="deletefrom('sensors');drawpolicy()" class="lbutton"/>
			</td>
			<td class="left nobborder" valign="top">
				<div id="containerse" style="width:350px"></div>
			</td>
		</tr>
		</table>
		<? $nexttab = ($opensource) ? 6 : 5; ?>
		
		<center style="padding-top:10px">
			<input type="button" class='button' value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',<?=$nexttab?>);" />
			<input type="button" class='button sok' value=" <?=_("OK")?> "  onclick="submit_form(this.form)" />
		</center>
	</div>

	<div id="tabs-6"<? if ($opensource) echo " style='display:none'" ?>>
		<table class='tab_table'>
		<tr>
			<th><?php echo _("Install in") . required() ?><br/>
				<span class='size10'><a href="../server/newserverform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new server?") ?></a></span><br/>
			</th>
			<td class="left nobborder" valign="top" id="targets">
				<?php
				/* ===== target sensors ====
				$i = 1;
				if ($sensor_list = Sensor::get_list($conn, "ORDER BY name")) {
				foreach($sensor_list as $sensor) {
				$sensor_name = $sensor->get_name();
				$sensor_ip =   $sensor->get_ip();
				if ($i == 1) {
				?>
				<input type="hidden" name="<?= "targetsensor"; ?>"
				value="<?= count($sensor_list); ?>">
				<?php
				}
				$name = "targboxsensor" . $i;
				?>
				<input type="checkbox"  id="target_<?= $sensor_ip . " (" . $sensor_name . ")"?>" name="<?= $name;?>"
				value="<?= $sensor_name; ?>" <?= (in_array($sensor_name,$sensors)) ? "checked='checked'" : "" ?>>
				<?= $sensor_ip . " (" . $sensor_name . ")<br>";?>
				</input>
				<?php
				$i++;
				}
				}*/
				?>
				<?php
				/* ===== target servers ==== */
				$i = 1;
				if ($server_list = Server::get_list($conn, "ORDER BY name"))
				{
					foreach($server_list as $server)
					{
						$server_name = $server->get_name();
						$server_ip = $server->get_ip();
						if ($i == 1) 
							echo "<input type='hidden' name='targetserver' value='".count($server_list)."'/>";
						
						$name = "targboxserver" . $i;
						?>
							<input type="checkbox" onclick="drawpolicy()" id="target_<?php echo $server_ip . " (" . $server_name . ")" ?>" name="<?php echo $name; ?>"
								value="<?php echo $server_name; ?>" <?php echo (in_array($server_name, $targets)) ? "checked='checked'" : "" ?>/>
								<?php echo $server_ip . " (" . $server_name . ")<br/>"; ?>
							
						<?php
							$i++;
						}
					}
				/* == ANY target == */
				?>
					<input type="checkbox" onclick="drawpolicy()" id="target_ANY" name="target_any" value="any" <?php echo (in_array("any", $targets) || in_array("ANY", $targets)) ? "checked='checked'" : "" ?>>&nbsp;<b><?php echo _("ANY");?></b><br></input>
			</td>
		</tr>
		</table>
		 
		<center style="padding-top:10px">
			<input type="button" class='button' value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',6);" />
			<input type="button" class='button sok' value=" <?=_("OK")?> "  onclick="submit_form(this.form)" />
		</center>
	</div>

	<div id="tabs-7">
		<table class='tab_table'>
		<tr>
			<th><?php echo _("Time Range") . required() ?></th>
			<td class="nobborder">
				<table>
					<tr>
						<td><?php echo _("Begin") ?></td><td></td><td><?php echo _("End") ?></td>
					</tr>
					<tr>
						<td class="nobborder">
							<select name="begin_day">
								<option <?php echo ($timearr[0] == 1) ? "selected='selected'" : "" ?> value="1"><?php echo _("Mon"); ?></option>
								<option <?php echo ($timearr[0] == 2) ? "selected='selected'" : "" ?> value="2"><?php echo _("Tue"); ?></option>
								<option <?php echo ($timearr[0] == 3) ? "selected='selected'" : "" ?> value="3"><?php echo _("Wed"); ?></option>
								<option <?php echo ($timearr[0] == 4) ? "selected='selected'" : "" ?> value="4"><?php echo _("Thu"); ?></option>
								<option <?php echo ($timearr[0] == 5) ? "selected='selected'" : "" ?> value="5"><?php echo _("Fri"); ?></option>
								<option <?php echo ($timearr[0] == 6) ? "selected='selected'" : "" ?> value="6"><?php echo _("Sat"); ?></option>
								<option <?php echo ($timearr[0] == 7) ? "selected='selected'" : "" ?> value="7"><?php echo _("Sun"); ?></option>
							</select>
							
							<select name="begin_hour">
								<?php
									for ($i=0; $i<24; $i++)
									{
										$selected = ( $timearr[1] == $i ) ? "selected='selected'" : '';
										echo "<option $selected value='$i'>".$i."h</option>";
									}
								?>
							</select>
						</td>
						<td class="nobborder">-</td>
						<td class="nobborder">
							<select name="end_day">
								<option <?php echo ($timearr[2] == 1) ? "selected='selected'" : "" ?> value="1"><?php echo _("Mon"); ?></option>
								<option <?php echo ($timearr[2] == 2) ? "selected='selected'" : "" ?> value="2"><?php echo _("Tue"); ?></option>
								<option <?php echo ($timearr[2] == 3) ? "selected='selected'" : "" ?> value="3"><?php echo _("Wed"); ?></option>
								<option <?php echo ($timearr[2] == 4) ? "selected='selected'" : "" ?> value="4"><?php echo _("Thu"); ?></option>
								<option <?php echo ($timearr[2] == 5) ? "selected='selected'" : "" ?> value="5"><?php echo _("Fri"); ?></option>
								<option <?php echo ($timearr[2] == 6) ? "selected='selected'" : "" ?> value="6"><?php echo _("Sat"); ?></option>
								<option <?php echo ($timearr[2] == 7) ? "selected='selected'" : "" ?> value="7"><?php echo _("Sun"); ?></option>
							</select>
							
							<select name="end_hour">
								<?php
									for ($i=0; $i<24; $i++)
									{
										$selected = ( $timearr[3] == $i ) ? "selected='selected'" : '';
										echo "<option $selected value='$i'>".$i."h</option>";
									}
								?>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		
		<center style="padding-top:10px">
			<input type="button" class="button" value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',7);"/></center>
	</div>

	<div id="tabs-8">
		<table class='tab_table'>
		<tr>
			<th style="background-position:top center"><?php echo _("Policy group") . required() ?><br/>
				<span class="size"><a href="newpolicygroupform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new policy group?") ?></a></span><br/>
			</th>
			<td class="left nobborder" valign="top">
				<select name="group" size="20" class="multi" style="width:200px" id="groups"id="groups">
					<?php
					$policygroups = Policy_group::get_list($conn, "ORDER BY name");
					foreach($policygroups as $policygrp)
					{
						$sel = ($policygrp->get_group_id() == $group) ? " selected" : "";
						echo "<option value='".$policygrp->get_group_id()."' $sel>".$policygrp->get_name()."</option>";
					} 
					?>
				</select>
			</td>
		</tr> 
		</table>
		<center style="padding-top:10px"><input type="button" class='button' value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',9);"/></center>
	</div>

	<div id="tabs-9">
		<center style="padding-top:10px">
			<input type="button" class="button" value=" <?=_("Next")?> >> " onclick="$('#tabs').tabs('select',9);"/>
			<input type="button" class="button sok" value=" <?=_("OK")?> "  onclick="submit_form(this.form)"/>
		</center>
	</div>

	<div id="tabs-10">
		<table class='tab_table noborder'>
		<tr>
			<td valign="top" class="nobborder">
				<table align="center">
					<tr>
						<th style="background-position:top center"><?php echo _("Actions") ?> &nbsp;
							<span class="size"><a href="../action/actionform.php?withoutmenu=1" class="greybox"><?php echo _("Insert new action?") ?></a></span><br/>
						</th>
					</tr>
					
					<tr>
						<td class="left nobborder" valign="top">
							<span id="loading_actions"><img src="../pixmaps/loading.gif" width="16px" align="absmiddle"><?=_("Loading actions, please wait a second...")?></span>
							<select id="actions" name="actions[]" class="multiselect" multiple="multiple" style="width:640px;height:348px;display:none">
								<?php
									if ($action_list2 = Action::get_list($conn))
									{
										$action_sel = array();
										foreach($actions_saved as $act) $action_sel[] = $act->get_action_id();
										foreach($action_list2 as $act) 
										{ 
											$sel = (in_array($act->get_id(),$action_sel)) ? '" selected="selected' : ''; 
											$desc1 = (strlen($act->get_descr())>48) ? substr($act->get_descr(),0,48)."..." : $act->get_descr();
											?>
											<option value="<?php echo $act->get_id().$sel ?>"><?php echo $desc1 ?></option>
											<?php
										}
									}
								?>
							</select>
						</td>
					</tr> 
				</table>
			</td>
			
			<td valign="top" class="nobborder" style="padding-left:15px;width:360px;">
				<table align="center" style="width:100%" id='p_conseq'>
					<tr>
						<th style="text-align:left; padding: 0px 10px"><?php echo _("Priority")?></th>
						<td class="left">
							<div class='cont_elem'>
								<select name="priority">
									<option <?php echo ($priority == - 1) ? "selected='selected'" : "" ?> value="-1"><?php echo _("Do not change"); ?></option>
									<option <?php echo ($priority == 0) ? "selected='selected'" : "" ?> value="0">0</option>
									<option <?php echo ($priority == 1) ? "selected='selected'" : "" ?> value="1">1</option>
									<option <?php echo ($priority == 2) ? "selected='selected'" : "" ?> value="2">2</option>
									<option <?php echo ($priority == 3) ? "selected='selected'" : "" ?> value="3">3</option>
									<option <?php echo ($priority == 4) ? "selected='selected'" : "" ?> value="4">4</option>
									<option <?php echo ($priority == 5) ? "selected='selected'" : "" ?> value="5">5</option>
								</select>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					<tr>
						<th style="text-decoration:underline;text-align:left; padding: 0px 10px"> <?php echo _("SIEM")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="sim" onchange="tsim(1)" value="1" <?php echo ($sim == 1) ? "checked='checked'" : "" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="sim" onchange="tsim(0)" value="0" <?php echo ($sim == 0) ? "checked='checked'" : "" ?>/> <?php echo _("No"); ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr id="qualify">
						<th style="text-align:left; padding-left:25px" id="qualify_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Qualify events")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="qualify" value="1" <?php echo ($qualify == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="qualify" value="0" <?php echo ($qualify == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr id="correlate">
						<th style="text-align:left; padding-left:25px" id="correlate_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Correlate events")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="correlate" value="1" <?php echo ($correlate == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="correlate" value="0" <?php echo ($correlate == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr id="cross_correlate">
						<th style="text-align:left; padding-left:25px" id="cross_correlate_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Cross Correlate events")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="cross_correlate" value="1" <?php echo ($cross_correlate == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="cross_correlate" value="0" <?php echo ($cross_correlate == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
				 
					<tr id="store">
						<th style="text-align:left; padding-left:25px" id="store_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Store events")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="store" value="1" <?php echo ($store == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="store" value="0" <?php echo ($store == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
				  
					<tr>
						<th style="text-decoration:underline;text-align:left; padding: 0px 10px"> <?php echo _("Logger")?> </th>
						<td class="left" <?= ($opensource) ? "style='color:gray'" : "" ?>>
							<div class='cont_elem'>
								<input type="radio" name="sem" onchange="tsem(1)" value="1" <?php echo ($sem == 1) ? "checked='checked'" : "" ?> <?= ($opensource) ? "disabled='disabled'" : "" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="sem" onchange="tsem(0)" value="0" <?php echo ($sem == 0) ? "checked='checked'" : "" ?> <?= ($opensource) ? "disabled='disabled'" : "" ?>/> <?php echo _("No"); ?>
								<?= ($opensource) ? "&nbsp;<a href='../sem' style='size:11px;color:gray'>\""._("Only available in Professional SIEM")."\"</a>" : "" ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>

					<tr id="sign">
						<th style="text-align:left; padding-left:25px" id="sign_text"<?php echo ($sem == 0) ? " class='thgray'" : "" ?>> <?php echo _("Sign")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="sign" value="1" <?php echo ($sign == 1) ? "checked='checked'" : "" ?><?php if ($sem == 0) echo " disabled" ?>/> <?php echo _("Line"); ?>
								<input type="radio" name="sign" value="0" <?php echo ($sign == 0) ? "checked='checked'" : "" ?><?php if ($sem == 0) echo " disabled" ?>/> <?php echo _("Block"); ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
				  
					<tr id="rtitle">
					<th style="text-decoration:underline;text-align:left; padding: 0px 10px"> <?=_("Multilevel")?></th>
						<td class="left">
							<input type="radio" name="multi" onchange="tmulti(1)" value="1" <?php echo ($sem == 1 || $sim == 1) ? "checked='checked'" : "" ?> <?= ($opensource) ? "disabled='disabled'" : "" ?>/> <?php echo _("Yes"); ?>
							<input type="radio" name="multi" onchange="tmulti(0)" value="0" <?php echo ($sem == 0 && $sim == 0) ? "checked='checked'" : "" ?> <?= ($opensource) ? "disabled='disabled'" : "" ?>/> <?php echo _("No"); ?>
						</td>
					</tr>
					
					<tr id="ralarms" <?php echo ($sim == 0 && $sem == 0) ? "class='thgray'" : "" ?>>
						<th style="text-align:left; padding-left:25px" id="ralarms_text"<?= ($opensource || ($sim == 0 && $sem == 0)) ? " class='thgray'" : "" ?>> <?php echo _("Forward alarms")?> </th>
						<td class="left" <?= ($opensource) ? "style='color:gray'" : "" ?>>
							<div class='cont_elem'>	
								<input type="radio" name="resend_alarms" value="1" <?php echo ($resend_alarm == 1) ? "checked='checked'" : "" ?> <?= ($opensource || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : "" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="resend_alarms" value="0" <?php echo ($resend_alarm == 0) ? "checked='checked'" : "" ?> <?= ($opensource || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : "" ?>/> <?php echo _("No"); ?>
								<?= ($opensource) ? "&nbsp;<a href='../sem' style='size:11px;color:gray'>\""._("Only available in Professional SIEM")."\"</a>" : "" ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr id="revents" <?php echo ($sim == 0 && $sem == 0) ? "class='thgray'" : "" ?>>
						<th style="text-align:left; padding-left:25px" id="revents_text"<?= ($opensource || ($sim == 0 && $sem == 0)) ? " class='thgray'" : "" ?>> <?php echo _("Forward events")?> </th>
						<td class="left" <?= ($opensource) ? "style='color:gray'" : "" ?>>
							<div class='cont_elem'>	
								<input type="radio" name="resend_events" value="1" <?php echo ($resend_event == 1) ? "checked='checked'" : "" ?> <?= ($opensource || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : "" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="resend_events" value="0" <?php echo ($resend_event == 0) ? "checked='checked'" : "" ?> <?= ($opensource || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : "" ?>/> <?php echo _("No"); ?>
								<?= ($opensource) ? "&nbsp;<a href='../sem' style='size:11px;color:gray'>\""._("Only available in Professional SIEM")."\"</a>" : "" ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr>
						<th><?php echo _("Description")?></th>
						<td class="left" class="nobborder">
							<div class='cont_elem'>	<textarea name="descr" id='descr'><?php echo $desc?></textarea></div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr>
						<th> <?php echo _("Active")?> </th>
						<td class="left">
							<div class='cont_elem'>
								<input type="radio" name="active" value="1" <?php echo ($active == 1) ? "checked='checked'" : "" ?>/> <?php echo _("Yes"); ?>
								<input type="radio" name="active" value="0" <?php echo ($active == 0) ? "checked='checked'" : "" ?>/> <?php echo _("No"); ?>
							</div>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					
					<tr>
						<script>
							var valsim = <?php echo $sim ?>;
							var valsem = <?php echo $sem ?>;
						</script>
						<td colspan="2" class="left noborder" style='padding:10px 5px;'>1) <?php echo _("Does not apply to targets without associated database.") ?> <?php echo _("Implicit value is always No for them."); ?></td>
					</tr>
					
					<?php $db->close($conn); ?>
				</table>
			</td>
		</tr>
		</table>

		<center style="padding-top:10px">
			<input type="button" class='button sok' value=" <?=_("OK")?> " class="sok" onclick="submit_form(this.form)"/>
			<input type="reset"  class='button' value="<?php echo gettext('Reset'); ?>" onclick="drawpolicy()"/>
		</center>
	</div>
		
</form>

<table width="100%">
	<tr>
		<th nowrap='nowrap'>
			<?php echo _("Source")?> <img src="../pixmaps/tables/cross-small.png" id="imgsource" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Dest")?> <img src="../pixmaps/tables/cross-small.png" id="imgdest" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Ports") ?> <img src="../pixmaps/tables/cross-small.png" id="imgports" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Plugin Groups")?> <img src="../pixmaps/tables/cross-small.png" id="imgplugins" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Sensors")?> <img src="../pixmaps/tables/cross-small.png" id="imgsensors" align="absmiddle"/>
		</th>
		<th <?=($opensource) ? " style='display:none'" : "nowrap='nowrap'"?>>
			<?php echo _("Install in") ?> <img src="../pixmaps/tables/cross-small.png" id="imgtargets" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Time Range")?> <img src="../pixmaps/tables/cross-small.png" id="imgtime" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Description")?> <img src="../pixmaps/tables/cross-small.png" id="imgmore" align="absmiddle"/>
		</th>
		<th nowrap='nowrap'>
			<?php echo _("Policy Consequences") ?> <img src="../pixmaps/tables/cross-small.png" id="imgother" align="absmiddle"/>
		</th>
	</tr>
	<tr>
		<td id="tdsource"  class="small"></td>
		<td id="tddest"    class="small"></td>
		<td id="tdports"   class="small"></td>
		<td id="tdplugins" class="small"></td>
		<td id="tdsensors" class="small"></td>
		<td id="tdtargets" class="small"<?=($opensource) ? " style='display:none'" : ""?>></td>
		<td id="tdtime"    class="small"></td>
		<td id="tdmore"    class="small"></td>
		<td id="tdother"   class="small" nowrap='nowrap'></td>
	</tr>
</table>

</body>
</html>

