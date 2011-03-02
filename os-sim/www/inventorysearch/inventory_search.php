<?php
/*****************************************************************************
*
*    License:
*
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
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Host.inc');
require_once ('classes/User_config.inc');
require_once ('classes/Util.inc');
require_once ('ossim_db.inc');
require_once ('ossim_conf.inc');

include ("functions.php");


// Database Object
$db   = new ossim_db();
$conn = $db->connect();

// Read config file with filters rules
$rules = get_rulesconfig ();

$config = new User_config($conn);
$user   = Session::get_session_user();
$data   = $config->get_all($user, "inv_search");

$new    = ( isset($_GET['new']) && !empty($_GET['new'])) ? 1 : 0;
$case   = 1;


if ( $new === 1 )
{
	unset($_SESSION['inventory_search']);
	unset($_SESSION['inventory_last_search']);
	unset($_SESSION['inventory_last_descr']);
	unset($_SESSION['profile']);
	$current_profile  = null;
}
else 
{
	$current_profile     = ( !empty($_GET['profile']) ) ? $_GET['profile'] : $_SESSION['profile'];
	$_SESSION['profile'] = $current_profile;
	
			
	if ( isset($_SESSION['inventory_last_search']) )
		$case = 2;
	else
	{
		$case = 3;
		
		if ( empty($current_profile) && $new === 0 && (is_array($data) || empty($data)) )
		{
			$name                  = (mb_detect_encoding($data[0]." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? $data[0] : mb_convert_encoding($data[0], 'UTF-8', 'ISO-8859-1');
			$_SESSION['profile']   = base64_encode($name);
		}
	}
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<meta http-equiv="Pragma" content="no-cache"/>
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
<link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
<style type="text/css">
	 a {cursor: pointer;}
	.active_filter{ font-weight: bold; }
	.msg_ok {text-align: center; color:green; font-weight:bold;}
	.msg_ko {text-align: center; color:red;   font-weight:bold;}
</style>
<script src="../js/jquery-1.3.2.min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
<script type="text/javascript" src="../js/utils.js"></script>

<script type="text/javascript">
// Parse ajax response
function parseJSON (data) {
	
	try {
		return eval ("(" + data + ")");
	} catch (e) {
		alert ("<?php echo _("ERROR")?> JSON "+e.message+" : "+data);
		return null;
	}
}

// Ajax sync flags
var syncflag = new Array;

// First level selects
var criterias    = new Array; // (Events, Alarms, etc.)
var operator     = "and";
var description  = "";
// Second level selects
var subcriterias = new Array; // Events -> (HasEvent, HasIP, HasProtocol, etc.)
// Third level selects
var values       = new Array; // Events -> HasIP -> (192.168)
var values2      = new Array;
var matches      = new Array; // Events -> HasIP -> (eq,like)
var sayts        = new Array;
var datepickers  = new Array;

var criteria_count = 0;
var rules = new Array;
<? foreach ($rules as $criteria_type=>$arr) { ?>
	rules['<?=$criteria_type?>'] = new Array;
	<? foreach ($arr as $rule=>$prop) { ?>
		rules['<?=$criteria_type?>']['<?=$rule?>'] = new Array;
		rules['<?=$criteria_type?>']['<?=$rule?>']['match'] = "<?php echo $prop['match']?>";
		rules['<?=$criteria_type?>']['<?=$rule?>']['list']  = <?=($prop['list'] != "") ? "true" : "false"?>;
	<? } ?>
<? } ?>

// Profiles div
var show_profiles = false;
var finish        = false;

	function addcriteria (i) {
		// Insert criteria in the middle
		/*
		if (ind < criteria_count) {
			for (i = criteria_count; i > ind; i--) {
				criterias[i+1] = criterias[i];
				operators[i+1] = operators[i];
				subcriterias[i+1] = subcriterias[i];
				values[i+1] = values[i];
				matches[i+1] = matches[i];
			}
			criterias[i+1] = ""; // New criteria empty
			operators[i+1] = "";
			subcriterias[i+1] = "";
			values[i+1] = "";
			matches[i+1] = "";
		}*/
		criterias[i+1]    = ""; // New criteria empty
		subcriterias[i+1] = "";
		values[i+1]       = "";
		values2[i+1]      = "";
		matches[i+1]      = "";
		criteria_count++;
		syncflag[criteria_count] = false;
		//save_values(); // Save text inputs with values
		setcriteria_type(i+1,"",0);
		setcriteria_subtype(i+1,"",0);
		setcriteria_match(i+1,"",0);
		reloadcriteria();
	}
	
	function removecriteria (ind)
	{
		//save_values(); // Save text inputs with values
		for (i = ind; i < criteria_count; i++) {
			criterias[i]    = criterias[i+1];
			subcriterias[i] = subcriterias[i+1];
			values[i]       = values[i+1];
			values2[i]      = values2[i+1];
			matches[i]      = matches[i+1];
		}
		
		criterias[i]    = ""; // Remove criteria data
		subcriterias[i] = "";
		values[i]       = "";
		values2[i]      = "";
		matches[i]      = "";
		criteria_count--;
		setcriteria_type(i,"",0);
		setcriteria_subtype(i,"",0);
		setcriteria_match(i,"",0);
		reloadcriteria();
	}
	
	function reloadcriteria () {
		// loading
		$('#msg').html("<?php echo _("Loading data...");?>");

		var or_selected = ""; var and_selected = "";
		if (operator == "or") 
		{
			or_selected = "selected"; 
			and_selected = ""; 
		}
		else 
		{
			or_selected = ""; 
			and_selected = "selected"; 
		}
		
		var html = "<tr><td class='nobborder'><strong><?php echo _("Description")?></strong>: <input type='text' name='description' id='description' onchange='description=this.value' value='"+description+"' style='width:300px'></td></tr>";
			html += "<tr><td class='nobborder'><?php echo _('If')?> <select name='operator' id='operator'><option value='and' "+and_selected+"><?php echo _("ALL")?><option value='or' "+or_selected+"><?=_("ANY")?></select> <?php echo _('of the following conditions are met')?>:</td></tr>";
		
		$('#criteria_form').html(html);
		
		for (i = 1; i <= criteria_count; i++) {
			document.getElementById('criteria_form').innerHTML += criteria_html(i);
		}
		load_dates();
	}
	
	// Get the output in html for 'i' criteria (inputs and values)
	function criteria_html (i) {
		// Criteria
		var has_subtype     = false;
		var has_filter      = false;
		var criteria_select = "";
		datepickers[i]      = false;
		criteria_select     = "<select id='type_"+i+"' name='type_"+i+"' onchange='setcriteria_type("+i+",this.value,1)'><option value=''>- <?php echo _("Select Condition")?> -";
		
		
		// FIRST LEVEL (type, and/or)
		for (ct in rules) {
			// Criteria type
			if (criterias[i] == ct) { var selected = "selected"; has_subtype = true; }
			else var selected = "";
			criteria_select += "<option value='"+ct+"' "+selected+">"+ct;
		}
		criteria_select += "</select>";
		
		// SECOND LEVEL (subtype)
		if (has_subtype) {
			criteria_select += "&nbsp;<select id='subtype_"+i+"' name='subtype_"+i+"' onchange='setcriteria_subtype("+i+",this.value,1)'><option value=''>- <?php echo _("Select Condition")?> -";
			for (cst in rules[criterias[i]]) {
				if (subcriterias[i] == cst) { var selected = "selected"; has_filter = true; }
				else var selected = "";
				criteria_select += "<option value='"+cst+"' "+selected+">"+cst;
			}
			criteria_select += "</select>";
		}
		
		// THIRD LEVEL (Filter is selected)
		if (has_filter) {
			// Text-Type Input
			
			if (rules[criterias[i]][subcriterias[i]]['match'] == "text" || rules[criterias[i]][subcriterias[i]]['match'] == "ip") {
				var val = ""; 
				var eq_selected = ""; 
				var like_selected = "";
				
				if (values[i] != "") val = values[i];
				if (matches[i] == "eq") eq_selected = "selected";
				else if (matches[i] == "LIKE") like_selected = "selected";
				criteria_select += "&nbsp;<select id='match_"+i+"' name='match_"+i+"' onchange='setcriteria_match("+i+",this.value,1)'><option value='LIKE' "+like_selected+"><?=_("Contains")?><option value='eq' "+eq_selected+"><?=_("Is equal")?></select>";
				criteria_select += "&nbsp;<input type='text' id='value_"+i+"' name='value_"+i+"' value='"+val+"'>";
				// AJAX! (if rule has 'list' field)
				if (rules[criterias[i]][subcriterias[i]]['list']) {
					$.ajax({
						type: "GET",
						url: "filter_response.php?type="+criterias[i]+"&subtype="+subcriterias[i],
						data: "",
						success: function(msg){
							sayts[i] = msg;
							load_sayts(i);
						}
					});
				}
			}
			// Combo-Type Input
			else if (rules[criterias[i]][subcriterias[i]]['match'] == "fixed" || rules[criterias[i]][subcriterias[i]]['match'] == "concat") {
				// AJAX! (if rule has 'list' field)
				if (rules[criterias[i]][subcriterias[i]]['list']) {
					criteria_select += "&nbsp;<select id='value_"+i+"' name='value_"+i+"' style='width:120px'>";
					
					$.ajax({
						type: "GET",
						url: "filter_response.php?type="+criterias[i]+"&subtype="+subcriterias[i],
						data: "",
						success: function(msg){
							if (msg != "\n") {
								var list = msg.split("###");
								var k = 0;
								for (elem in list) {
									var elem_fields = list[elem].split("_#_");
									var newOpt      = new Option(elem_fields[1], elem_fields[0]);
									document.getElementById('value_'+i).options[k] = newOpt;
									if (values[i] == elem_fields[0]) document.getElementById('value_'+i).options[k].selected = true;
									k++;
								}
							} else {
								var newOpt = new Option("<?=_("Empty list (check rules)")?>", "");
								document.getElementById('value_'+i).options[0] = newOpt;
							}
						}
					});
					criteria_select += "</select>";
				}
			}
			else if (rules[criterias[i]][subcriterias[i]]['match'] == "date") {
				var val = "";
				if (values[i] != "") val = values[i];
				criteria_select += "&nbsp;<input type='text' id='value_"+i+"' name='value_"+i+"' value='"+val+"'>";
				datepickers[i] = true;
			}
			else if (rules[criterias[i]][subcriterias[i]]['match'] == "number") {
				var val = "";
				if (values[i] != "") val = values[i];
				criteria_select += "&nbsp;<input type='text' style='width:30px' id='value_"+i+"' name='value_"+i+"' value='"+val+"'>";
			}
			// Combo-Type Input and Text
			else if (rules[criterias[i]][subcriterias[i]]['match'] == "fixedText") {
				// AJAX! (if rule has 'list' field)
				if (rules[criterias[i]][subcriterias[i]]['list']) {
					var val2 = "";
					if (values2[i] != ""){
						val2 = values2[i];
					}
					
					criteria_select += "&nbsp;<select id='value_"+i+"' name='value_"+i+"' style='width:120px'>";
					$.ajax({
						type: "GET",
						url: "filter_response.php?type="+criterias[i]+"&subtype="+subcriterias[i],
						data: "",
						success: function(msg){
							if (msg != "\n") {
								var list = msg.split("###");
								var k = 0;
								for (elem in list) {
									var elem_fields = list[elem].split("_#_");
									var newOpt = new Option(elem_fields[1], elem_fields[0]);
									document.getElementById('value_'+i).options[k] = newOpt;
									if (values[i] == elem_fields[0]) document.getElementById('value_'+i).options[k].selected = true;
									k++;
								}
							} else {
								var newOpt = new Option("<?=_("Empty list (check rules)")?>", "");
								document.getElementById('value_'+i).options[0] = newOpt;
							}
						}
					});
					criteria_select += "</select>&nbsp;<input type='text' id='value2_"+i+"' name='value2_"+i+"' value='"+val2+"'>";
				}
			}
		}
		
		//var remove = "<td class='nobborder'></td>";
		//if (criteria_count > 1) var remove = "<td class='nobborder'><a href='' onclick='removecriteria("+i+");return false;'><img src='../pixmaps/minus-small.png' alt='Remove Criteria' title='Remove Criteria'></a></td>";
		if (criteria_count > 1){
			var remove = "<td class='nobborder' width='15'><input type='button' value='-' class='lbutton' style='font-size:12px;font-weight:bold;width:20px' onclick='removecriteria("+i+")'></td>";
		}else{
			//var remove = "<td class='nobborder' width='15'><input type='button' value='-' class='lbutton' style='font-size:12px;font-weight:bold;width:20px' onclick='removecriteria("+i+")' disabled></td>";
			var remove = "<td class='nobborder' width='15'></td>";
		}
		var add = "<td class='nobborder' width='15' style='padding-right:6px'><input type='button' value='+' class='lbutton' style='font-size:12px;font-weight:bold;width:20px' onclick='addcriteria("+criteria_count+")'></td>";
		
		var debug = "<td class='nobborder'></td>";
		//debug = "<td>DEBUG: #"+i+" type: "+criterias[i]+", subtype: "+subcriterias[i]+", value: "+values[i]+"</td>";
		return "<tr><td class='nobborder'><table cellpadding=2 width='100%' style='background-color:#F2F2F2'><tr><td class='nobborder' nowrap style='padding:7px'>"+criteria_select+"</td>"+remove+add+"</tr></table></td>"+debug+"</tr>";
	}
	
	function setcriteria_type (i,val,r) {
		criterias[i] = val;
		subcriterias[i] = "";
		values[i] = "";
		values2[i] = "";
		matches[i] = "";
		if (r){
			reloadcriteria();
		}
	}
	
	function setcriteria_subtype (i,val,r) {
		subcriterias[i] = val;
		values[i] = "";
		values2[i] = "";
		matches[i] = "";
		if (r){
			reloadcriteria();
		}
	}
	function setcriteria_match (i,val,r) {
		matches[i] = val;
		if (r){
			reloadcriteria();
		}
	}
	function setcriteria (i,val_type,val_subtype,val_match) {
		criterias[i] = val_type;
		subcriterias[i] = val_subtype;
		matches[i] = val_match;
	}
	function setcriteria_val (i,val) {
		values[i] = val;
		reloadcriteria();
	}
	
	function load_sayts (i) {
		if (sayts[i] != undefined && sayts[i] != "") {
			$("#value_"+i).focus().autocomplete(sayts[i].split("###"), {
				minChars: 0,
				width: 150,
				matchContains: "word",
				autoFill: false
			});
		}
	}
	
	function load_dates () {
		for (i = 1; i <= criteria_count; i++) {
			if (datepickers[i]) $('#value_'+i).datepicker();
		}
		// loading ok
		document.getElementById('msg').innerHTML = "";
		//
	}
	
	function save_values () {
		var params = "?op="+operator+"&n="+criteria_count;

		for (i = 1; i <= criteria_count; i++) {
			if (criterias[i]) {
				if (document.getElementById("value_"+i) != null) values[i] = document.getElementById("value_"+i).value;
				if (document.getElementById("value2_"+i) != null){
					// For FixedText
					values2[i] = document.getElementById("value2_"+i).value;
				}
			}
			else {
				values[i] = "";
				values2[i] = "";
			}
			params += "&value"+i+"="+values[i];
			if(values2!=null){
				// For FixedText
				params += "&value_two"+i+"="+values2[i];
			}
		}
	}

	function get_params() {
		
		var operator = $("#operator").serialize();
		var params   = "?"+operator+"&num="+criteria_count;
		
		var valid_criteria = 0;
		
		
		for (i=1; i<=criteria_count; i++)
		{
			
			var type    = $("#type_"+i).serialize();
			var subtype = $("#subtype_"+i).serialize();
			
			if (type == '' || subtype == '' )
				continue;
			else
				valid_criteria++;
						
			params += "&"+type;
			params += "&"+subtype;
			
			if (match != null){
			    var match   = $("#match_"+i).serialize();
				params     += "&"+match;
			}
			
			if (document.getElementById("value_"+i) != null)
			{
				var value   = $("#value_"+i).serialize();
				params += "&"+value;
			}
			
			if (document.getElementById("value2_"+i) != null){
				// For FixedText
				var value2  = $("#value2_"+i).serialize();
				params     += "&"+value2;
			}
		}
		
		if (valid_criteria > 0)
		{
			params += "&profile="+$('#current_profile').val();
			params += "&"+$('#description').serialize();
		}
		else
			params = '';
		
		return params;
	}
	
	function launch_query () {
		var params = get_params();
		
		if (params != '')
			window.location.href = "build_search.php"+params;
		else
			alert ('<?php echo _("You must fill in all conditions") ?>')
	}
	
	function load_values () {
		for (c in criterias) 
		{
			if (values[c] != "") 
				document.getElementById(c).value = values[c];
			else if (document.getElementById(c) != null) 
				document.getElementById(c).value = "";
		}
	}
	
	function profile_save ()
	{
		var params      = get_params();
				
		if (params == '')
		{
			alert ('<?php echo _("You must fill in all conditions") ?>');
			return;
		}
		
		var filter_name     = $('#cur_name').val();
		var filter_name_s   = $('#cur_name').serialize();
				
		if ( (filter_name == '' ) ) 
		{
			alert("<?php echo _("Insert a name to export")?>");
			return;
		}
				
			
		$.ajax({
			type: "GET",
			url: "profiles.php"+params+"&"+filter_name_s+"&inv_do=export",
			success: function(msg){
				
				var status = msg.split("###");
				
				if ( status[0] == "error" )
				{
					$('#msg').removeClass("msg_ok");
					$('#msg').addClass("msg_ko");
					put_msg(status[1], "msg");
					
				}
				else
				{
					reload_profiles();
					put_msg("<?php echo _("Profile successfully Saved")?>", "msg");
					$('#current_profile').val(status[1]);
				}
			}
		});
		
	}
	
	function reset_active_filter(){
		$('.active_filter').removeClass('active_filter');
	}
	
	function profile_load (filter_name,id) {
		
		if (filter_name == "")
			alert("<?php echo _("Select a profile to import")?>");
		else 
		{
			if ( id != false )
			{
				var value = $('#'+id+ ' a').text();
			    $('#cur_name').val(value);
				$('#'+id).addClass('active_filter');
			}
			
			$('#current_profile').val(filter_name);
			reset_active_filter();
			
						
			$('#msg').html("<?php echo _("Loading profile...")?>");
			$('#search_btn').attr('disabled','');
			$('#search_btn').css('color','grey');
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'import' },
				success: function(msg) {
					var ret        = parseJSON(msg);
					var data       = ret.dt;
					criteria_count = data.length;
					for (i = 0; i < data.length; i++)
					{
						setcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
						values[i+1] = data[i].value;
						values2[i+1] = data[i].value2;
						if (document.getElementById("value_"+(i+1)) != null) 
							$("#value_"+(i+1)).value = data[i].value;
						if (document.getElementById("value2_"+(i+1)) != null)
							$("#value2_"+(i+1)).value = data[i].value2;
						
					}
					operator    = ret.op;
					description = ( ret.descr == undefined) ? '' : ret.descr;
					//save_values();
					reloadcriteria();
					put_msg("<?php echo _("Profile successfully Loaded")?>", "msg");
				}
			});
		}
	}
	
	
	
	function profile_last () {
		$.ajax({
			type: "GET",
			url: "profiles.php",
			data: { inv_do: 'last_search' },
			success: function(msg) {
				//alert(msg);
				var ret  = parseJSON(msg);
				var data = ret.dt;
				for (i = 0; i < data.length; i++) {
					setcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
					values[i+1]  = data[i].value;
					values2[i+1] = data[i].value2;
				}
				criteria_count = data.length;
				operator       = ret.op;
				description    = ret.descr;
				//save_values();
				reloadcriteria();
			}
		});
	}
	function profile_delete (filter_name) {
		if (filter_name == "") alert("<?php echo _("Select a profile to delete")?>");
		else {
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'delete' },
				success: function(msg) {
					document.location.href='inventory_search.php';
				}
			});
		}
	}
	
	
	function inic () {
				
		var status = '<?php echo $case;?>';
		
		if( status == 2 || status == 3 )
		{
			var profile  = '<?php echo $_SESSION['profile']?>';
			
			if ( status == 2 ) 
				profile_last();
			else if (profile != '')
				profile_load(profile, false);
				
		}
		
		addcriteria(0);
		$('#current_profile').val('');
		$('#cur_name').val('');
		
		
		reload_profiles();	
	}
	
	function reload_profiles() {
		
		$.ajax({
			type: "GET",
			url: "profiles.php",
			data: { inv_do: 'getall' },
			success: function(msg) {
				
				var profiles = "<table width='100%' class='noborder' border='0' cellpadding='0' cellspacing='0'>";
								
				if (msg != '')
				{
					var names           = msg.split(",");
					var current_profile = $('#current_profile').val();
					
					for (n in names)
					{
						var data  = names[n].split("###");
						var style = ( data[0] == current_profile ) ? 'class="active_filter"' : '';
						profiles += '<tr><td>';
						profiles += '<span id="profile_'+n+'" style="width:170px;display:block;float:left;" '+style+'>';
						profiles += '<a style="cursor:pointer" onclick="profile_load(\''+data[0]+'\',\'profile_'+n+'\')">'+data[1]+'</a></span>';
						profiles += '<a style="cursor:pointer" onclick="profile_delete(\''+data[0]+'\')"><img alt="Delete" src="../pixmaps/delete.gif" style="vertical-align: middle"/></a>';
						profiles += '</td></tr>';
					}
				}
				else
				{
					$('#current_profile').val('');
					profiles += "<tr><td><?php echo _("No profiles found") ?></td></tr>";
				}	
				
				
				profiles += "</table>";
				
				$('#profiles').html(profiles);				
			}
		});
	}
	
	function build_request () {
		finish = true;
		save_values();
	}
	
	function clean_request () {
		$.ajax({
			type: "GET",
			url: "profiles.php",
			data: { inv_do: 'clean' },
			success: function(msg) {
				document.location.href='inventory_search.php?new=1'
			}
		});
	}
	
	function put_msg (str, id) {
		$('#'+id).html(str);
		setTimeout ("reset_msg('#"+id+"');", 2000);
	}
	
	function reset_msg(id)
	{
		$(id).html('');
		
		if ( $(id).hasClass('msg_ko') )
		{
			$(id).removeClass("msg_ko");
			$(id).addClass("msg_ok");
		}
	}
	
	
	<? if (Session::am_i_admin()) { ?>
	function open_edit () {
		var edit_wnd = window.open('editrules.php','Edit rules.conf','scrollbars=yes,location=no,toolbar=no,status=no,directories=no,width=700,height=400');
		edit_wnd.focus()
	}
	
	function recarga () {
		window.location.reload();
	}
	<? } ?>
	
	// Ajax sync
	function checksync() {
		var isready = true;
		for (var s = 1; s <= criteria_count; s++) {
			if (!syncflag[s]) isready = false;
		}
		if (isready) {
			$('#search_btn').attr('disabled','');
			$('#search_btn').css('color','black');
		}
	}
	
		
	$(document).ready(function(){
		inic();
	});
</script>
</head>

<body style="margin:0px">
<? include ("../hmenu.php"); ?>

<table class="noborder" align="center" style="background-color:white">
	<tr>
		<td class="nobborder" valign="top">
			<table class="nobborder" align="center" style="background-color:white">
				<tr>
					<td class="nobborder" style="padding-bottom:2px">
						<table style="background:url(../pixmaps/fondo_hdr2.png) repeat-x" width="100%">
							<tr><td class="nobborder" style="font-weight:bold;text-align:center;font-size:13px;height:25px"><?php echo _("Asset Categories")?></td></tr>
						</table>
					</td>
				</tr>
			<form method='GET'>
				<tr>
					<td class="nobborder">
						<table id="criteria_form" cellpadding='5' align="center" width="100%" style="background:url(../pixmaps/background_green1.gif) repeat-x;border:1px solid #AAAAAA">
						</table>
					</td>
				</tr>
				<tr>
					<td class="nobborder">
						<table class="noborder" width="100%" style="background-color:white">
							<tr>
								<td class="nobborder" width="100"><? if (Session::am_i_admin()) { ?><a href="" onclick="open_edit();return false;" target="_blank"><img src="../pixmaps/pencil.png" border="0" alt="<?=_("Edit rules.conf")?>" title="<?=_("Edit rules.conf")?>"><?php echo _("Select Condition")?></a><? } ?></td>
								<td class="nobborder" style="text-align:right">
									<input type="button" onclick="launch_query()" id="search_btn" value="<?php echo _("Search")?>" class="button"/>
								</td>
								<td class="nobborder" style="text-align:left">
									<input type="button" onclick="clean_request()" value="<?php echo _("Clean")?>" class="button"/>
								</td>
								<td class="nobborder" width="100" style="text-align:right">&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td class="nobborder"><div id="debug"></div></td></tr>
			</form>
			</table>
		</td>
	
		<td class="nobborder" valign="top">
			<table class="nobborder">
				<tr>
					<td class="nobborder">
						<div id="profiles_div">
						<table width="250" align="center">
							<tr><th><?php echo _("Predefined Searches")?></th></tr>
							<tr>
								<td class="nobborder" id="profiles"></td>
							</tr>
							<tr>
								<td class="nobborder" style="padding-top:10px">
									<?php
										$cur_name = base64_decode($_SESSION['profile']);
										$cur_name = (mb_detect_encoding($cur_name." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? mb_convert_encoding($cur_name, 'ISO-8859-1', 'UTF-8') : $cur_name;
									?>
																		
									<input type="text"   id="cur_name" name="cur_name" value="<?php echo $cur_name;?>"/>
									<input type="hidden" id="current_profile" name="current_profile" value="<?php echo $_SESSION['profile']?>"/>
									<input type="button" id="save_current" value="<?php echo _("Save Current")?>" onclick="profile_save()" class="lbutton"/>
								</td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="nobborder msg_ok" id="msg"></td>
		<td class="nobborder">&nbsp;</td>
	</tr>
</table>
</body>
</html>
