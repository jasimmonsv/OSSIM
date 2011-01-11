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
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

include ("functions.php");

$new = ($_GET['new'] == "1") ? 1 : 0;

// Database Object
$db = new ossim_db();
$conn = $db->connect();

// Read config file with filters rules
$rules = get_rulesconfig ();
//echo "<br><br><br><br>";
//print_r($_SESSION['inventory_last_search']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
<link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>

<script src="../js/jquery-1.3.2.min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>

<script type="text/javascript">
// Parse ajax response
function parseJSON (data) {
	try {
		return eval ("(" + data + ")");
	} catch (e) {
		alert ("<?=_("ERROR")?> JSON "+e.message+" : "+data);
		return null;
	}
}

// Ajax sync flags
var syncflag = new Array;

// First level selects
var criterias = new Array; // (Events, Alarms, etc.)
var operator = "and";
var description = "";
// Second level selects
var subcriterias = new Array; // Events -> (HasEvent, HasIP, HasProtocol, etc.)
// Third level selects
var values = new Array; // Events -> HasIP -> (192.168)
var values2 = new Array;
var matches = new Array; // Events -> HasIP -> (eq,like)
var sayts = new Array;
var datepickers = new Array;

var criteria_count = 0;
var rules = new Array;
<? foreach ($rules as $criteria_type=>$arr) { ?>
	rules['<?=$criteria_type?>'] = new Array;
	<? foreach ($arr as $rule=>$prop) { ?>
		rules['<?=$criteria_type?>']['<?=$rule?>'] = new Array;
		rules['<?=$criteria_type?>']['<?=$rule?>']['match'] = "<?=$prop['match']?>";
		rules['<?=$criteria_type?>']['<?=$rule?>']['list'] = <?=($prop['list'] != "") ? "true" : "false"?>;
	<? } ?>
<? } ?>

// Profiles div
var show_profiles = false;
var current_profile = "";

var finish = false;

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
		criterias[i+1] = ""; // New criteria empty
		subcriterias[i+1] = "";
		values[i+1] = "";
		values2[i+1] = "";
		matches[i+1] = "";
		criteria_count++;
		syncflag[criteria_count] = false;
		save_values(); // Save text inputs with values
		setcriteria_type(i+1,"",0);
		setcriteria_subtype(i+1,"",0);
		setcriteria_match(i+1,"",0);
		reloadcriteria();
	}
	
	function removecriteria (ind) {
		save_values(); // Save text inputs with values
		for (i = ind; i < criteria_count; i++) {
			criterias[i] = criterias[i+1];
			subcriterias[i] = subcriterias[i+1];
			values[i] = values[i+1];
			values2[i] = values2[i+1];
			matches[i] = matches[i+1];
		}
		criterias[i] = ""; // Remove criteria data
		subcriterias[i] = "";
		values[i] = "";
		values2[i] = "";
		matches[i] = "";
		criteria_count--;
		setcriteria_type(i,"",0);
		setcriteria_subtype(i,"",0);
		setcriteria_match(i,"",0);
		reloadcriteria();
	}
	
	function reloadcriteria () {
		// loading
		document.getElementById('msg').innerHTML = "<?php echo _("Loading data..."); ?>";
		//
		var or_selected = ""; var and_selected = "";
		if (operator == "or") { or_selected = "selected"; and_selected = ""; }
		else { or_selected = ""; and_selected = "selected"; }
		//document.getElementById('criteria_form').innerHTML = "<tr><td class='nobborder'></td><td class='nobborder'><input type='radio' name='operator' id='operator' value='and' "+and_selected+" onchange='setcriteria_op(this.value)'><?=_('Match \"all\" of the following')?><input type='radio' name='operator' id='operator' value='or' "+or_selected+" onchange='setcriteria_op(this.value)'><?=_('Match \"any\" of the following')?></td></tr>";
		document.getElementById('criteria_form').innerHTML = "<tr><td class='nobborder'><b><?=_("Description")?></b>: <input type='text' name='description' id='description' onchange='description=this.value' value='"+description+"' style='width:300px'></td></tr>";
		document.getElementById('criteria_form').innerHTML += "<tr><td class='nobborder'><?=_('If')?> <select name='operator' id='operator' onchange='setcriteria_op(this.value)'><option value='and' "+and_selected+"><?=_("ALL")?><option value='or' "+or_selected+"><?=_("ANY")?></select> <?=_('of the following conditions are met')?>:</td></tr>";
		for (i = 1; i <= criteria_count; i++) {
			document.getElementById('criteria_form').innerHTML += criteria_html(i);
		}
		//document.getElementById('criteria_form').innerHTML += "<tr><td class='nobborder' style='text-align:right'><a href='' onclick='addcriteria("+criteria_count+");return false;'><img src='../pixmaps/plus-small.png' alt='Add Criteria' title='Add Criteria'></a></td><td class='nobborder'><i><?=_("Add another Criteria")?></i></td></tr>";
		//document.getElementById('criteria_form').innerHTML += "<tr><td class='nobborder'></td><td class='nobborder' style='text-align:right'><input type='button' value='+' class='lbutton' style='font-size:11px;font-weight:bold;width:20px' onclick='addcriteria("+criteria_count+")'></td></tr>";
		load_dates();
	}
	
	// Get the output in html for 'i' criteria (inputs and values)
	function criteria_html (i) {
		// Criteria
		var has_subtype = false;
		var has_filter = false;
		var criteria_select = "";
		datepickers[i] = false;
		criteria_select = "<select id='type_"+i+"' name='type_"+i+"' onchange='setcriteria_type("+i+",this.value,1)'><option value=''>- <?=_("Select Filter Type")?> -";
		
		
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
			criteria_select += "&nbsp;<select id='subtype_"+i+"' name='subtype_"+i+"' onchange='setcriteria_subtype("+i+",this.value,1)'><option value=''>- <?=_("Select Filter")?> -";
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
				var val = ""; var eq_selected = ""; var like_selected = "";
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
								var list = msg.split(",");
								var k = 0;
								for (elem in list) {
									var elem_fields = list[elem].split(";");
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
								var list = msg.split(",");
								var k = 0;
								for (elem in list) {
									var elem_fields = list[elem].split(";");
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
		$.ajax({
			type: "GET",
			url: "setvars.php?i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i],
			data: "",
			success: function(msg){
				
			}
		});
		if (r){
			reloadcriteria();
		}
	}
	function setcriteria_op (val) {
		operator = val;
		reloadcriteria();
	}
	function setcriteria_subtype (i,val,r) {
		subcriterias[i] = val;
		values[i] = "";
		values2[i] = "";
		matches[i] = "";
		$.ajax({
			type: "GET",
			url: "setvars.php?i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i],
			data: "",
			success: function(msg){
				syncflag[i] = true;
				checksync();
			}
		});
		if (r){
			reloadcriteria();
		}
	}
	function setcriteria_match (i,val,r) {
		matches[i] = val;
		$.ajax({
			type: "GET",
			url: "setvars.php?i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i],
			data: "",
			success: function(msg){
				
			}
		});
		if (r){
			reloadcriteria();
		}
	}
	function setcriteria (i,val_type,val_subtype,val_match) {
		criterias[i] = val_type;
		subcriterias[i] = val_subtype;
		matches[i] = val_match;
		$.ajax({
			type: "GET",
			url: "setvars.php",
			data: "i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i],
			success: function(msg){
				//alert("setvars.php?i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i]);
				syncflag[i] = true;
				checksync();
			}
		});
	}
	function asyncsetcriteria (i,val_type,val_subtype,val_match) {
		criterias[i] = val_type;
		subcriterias[i] = val_subtype;
		matches[i] = val_match;
		$.ajax({
			type: "GET",
			url: "setvars.php?i="+i+"&type="+criterias[i]+"&subtype="+subcriterias[i]+"&match="+matches[i],
			async: false,
			data: "",
			success: function(msg){
				return "ok";
			}
		});
	}
	function setcriteria_val (i,val) {
		values[i] = val;
		reloadcriteria();
	}
	
	function load_sayts (i) {
		if (sayts[i] != undefined && sayts[i] != "") {
			$("#value_"+i).focus().autocomplete(sayts[i].split(","), {
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

		$.ajax({
			type: "GET",
			url: "setvars.php"+params,
			data: "",
			success: function(msg){
				if (finish) window.location.href = "build_search.php?operator="+operator;
			}
		});
	}
	function load_values () {
		for (c in criterias) {
			if (values[c] != "") {
				document.getElementById(c).value = values[c];
			}
			else {
				if (document.getElementById(c) != null) document.getElementById(c).value = "";
			}
		}
	}
	
	function profile_save (filter_name) {
		if (filter_name == "- New Profile -" && current_profile != "") filter_name = current_profile;
		if (filter_name == "" || filter_name == "- New Profile -") alert("<?=_("Insert a name to export")?>");
		else {
			// save_values() code
			var params = "?op="+operator+"&n="+criteria_count+"&description="+description;
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
			$.ajax({
				type: "GET",
				url: "setvars.php"+params,
				data: "",
				success: function(msg){
			// end of save_values() code (second ajax call)
					$.ajax({
						type: "GET",
						url: "profiles.php",
						data: { name: filter_name, inv_do: 'export', op: operator, descr: description },
						success: function(msg) {
							reload_profiles();
							put_msg("<?=_("Profile successfully Saved")?>");
							$('#cur_name').val("");
						}
					});
				}
			});
		}
	}
	function profile_load (filter_name) {
		if (filter_name == "") alert("<?=_("Select a profile to import")?>");
		else {
			document.getElementById('msg').innerHTML = "<?=_("Loading profile...")?>";
			$('#search_btn').attr('disabled','');
			$('#search_btn').css('color','grey');
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'import' },
				success: function(msg) {
					//alert(msg);
					var ret = parseJSON(msg);
					var data = ret.dt;
					criteria_count = data.length;
					for (i = 0; i < data.length; i++) {
						setcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
						values[i+1] = data[i].value;
						values2[i+1] = data[i].value2;
						if (document.getElementById("value_"+(i+1)) != null) document.getElementById("value_"+(i+1)).value = data[i].value;
						if (document.getElementById("value2_"+(i+1)) != null){
							document.getElementById("value2_"+(i+1)).value = data[i].value2;
						}
					}
					operator = ret.op;
					description = ret.description;
					current_profile = filter_name;
					//save_values();
					reloadcriteria();
					put_msg("<?=_("Profile successfully Loaded")?>");
				}
			});
		}
	}
	function profile_exec (filter_name) {
		if (filter_name == "") alert("<?=_("Select a profile to import")?>");
		else {
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'import' },
				success: function(msg) {
					//alert(msg);
					var ret = parseJSON(msg);
					var data = ret.dt;
					for (i = 0; i < data.length; i++) {
						//var ret = asyncsetcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
						//alert(ret); // jQuery BUG?? ajax async: false does not work!!
						setcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
						values[i+1] = data[i].value;
					}
					criteria_count = data.length;
					operator = ret.op;
					description = ret.description;
					current_profile = filter_name;
					save_values();
					reloadcriteria();
					put_msg("<?=_("Profile successfully Loaded")?>");
					//build_request();
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
				var ret = parseJSON(msg);
				var data = ret.dt;
				for (i = 0; i < data.length; i++) {
					//setcriteria_type(i+1,data[i].type,0);
					//setcriteria_subtype(i+1,data[i].subtype,0);
					//setcriteria_match(i+1,data[i].match,0);
					setcriteria(i+1,data[i].type,data[i].subtype,data[i].match);
					values[i+1] = data[i].value;
				}
				criteria_count = data.length;
				operator = ret.op;
				save_values();
				reloadcriteria();
			}
		});
	}
	function profile_delete (filter_name) {
		if (filter_name == "") alert("<?=_("Select a profile to delete")?>");
		else {
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: filter_name, inv_do: 'delete' },
				success: function(msg) {
					document.location.href='inventory_search.php?new=1';
				}
			});
		}
	}
	function activate_rename () {
		if (document.getElementById('rename_button').disabled == true) document.getElementById('rename_button').disabled = false;
		if (document.getElementById('cur_rename').disabled == true) {
			document.getElementById('cur_rename').disabled = false;
		}
		document.getElementById('cur_rename').value = document.getElementById('profile').value;
	}
	function profile_rename (cur_name, new_name) {
		if (cur_name == new_name) alert("<?=_("Insert a different name to rename profile")?> '"+cur_name+"'");
		else if (cur_name == "" || new_name == "") alert ("<?=_("Select and type a correct name for profile")?>");
		else {
			$.ajax({
				type: "GET",
				url: "profiles.php",
				data: { name: cur_name, inv_do: 'rename', new_name: new_name },
				success: function(msg) {
					get_conf();
				}
			});
		}
	}
	
	function inic () {
		reload_profiles();
		<?
		if ($_SESSION['inventory_last_search'] != "" && !$new && $_GET['profile'] == "") {
		?>
		profile_last();
		<?
		} elseif($_GET['profile'] == "") {
		?>
		addcriteria(0);
		<?
		}
		?>
		<? if ($_GET['profile'] != "") { ?>
		profile_load("<?=$_GET['profile']?>");
		<? } ?>
	}
	function reload_profiles() {
		$.ajax({
			type: "GET",
			url: "profiles.php",
			data: { inv_do: 'getall' },
			success: function(msg) {
				var names = msg.split(",");
				var profiles = "<select id='profile' name='profile' multiple='true' size='6' style='width:250px' onclick='activate_rename(); profile_load(this.value)'>";
				for (n in names) {
					profiles += "<option value='"+names[n]+"'>"+names[n];
				}
				profiles += "</select>";
				document.getElementById('profiles').innerHTML = profiles;
			}
		});
	}
	/*
	function profiles_show () {
		if (!show_profiles) {
			document.getElementById('profiles_div').style.display = "block";
			document.getElementById('prof_link').value = "Hide <<";
			show_profiles = true;
		}
		else {
			document.getElementById('profiles_div').style.display = "none";
			document.getElementById('prof_link').value = "Profiles >>";
			show_profiles = false;
		}
	}
	*/
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
				document.location.href='inventory_search.php'
			}
		});
	}
	
	function put_msg (str) {
		document.getElementById('msg').innerHTML = str;
		setInterval("document.getElementById('msg').innerHTML=''",2000);
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
<? //print_r($rules); ?>
<body style="margin:0px">
<? include ("../hmenu.php") ?>
<table class="noborder" align="center" style="background-color:white">
	<tr>
		<td class="nobborder" valign="top">
			<table class="nobborder" align="center" style="background-color:white">
				<tr>
					<td class="nobborder" style="padding-bottom:10px">
						<table style="background:url(../pixmaps/fondo_hdr2.png) repeat-x" width="100%">
							<tr><td class="nobborder" style="font-weight:bold;text-align:center;font-size:13px"><?=_("Advanced Asset Search")?></td></tr>
						</table>
					</td>
				</tr>
			<form method=get>
				<tr>
					<td class="nobborder">
						<table id="criteria_form" cellpadding=5 align="center" width="100%" style="background:url(../pixmaps/background_green1.gif) repeat-x;border:1px solid #AAAAAA">
						</table>
					</td>
				</tr>
				<tr><td class="nobborder">
					<table class="noborder" width="100%" style="background-color:white">
						<tr>
							<td class="nobborder" width="100"><? if (Session::am_i_admin()) { ?><a href="" onclick="open_edit();return false;" target="_blank"><img src="../pixmaps/pencil.png" border="0" alt="<?=_("Edit rules.conf")?>" title="<?=_("Edit rules.conf")?>"><?=_("Rules")?></a><? } ?></td>
							<td class="nobborder" style="text-align:right">
								<input type="button" onclick="build_request()" id="search_btn" value="<?=_("Search")?>" class="button" style="font-size:12px;" disabled>
							</td>
							<td class="nobborder" style="text-align:left">
								<input type="button" onclick="clean_request()" value="<?=_("Clean")?>" class="button" style="font-size:12px">
							</td>
							<td class="nobborder" width="100" style="text-align:right">&nbsp;</td>
							<!--<td class="nobborder" width="100" style="text-align:right"><input type="button" class="lbutton" onclick="profiles_show()" id="prof_link" value="<?=_("Profiles")?> >>"></td>-->
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
							<tr><th><?=_("Predefined Searches")?></th></tr>
							<tr>
								<td class="nobborder" id="profiles"></td>
							</tr>
							<tr><td class="nobborder"><!--<input type="button" value="Load" onclick="profile_load(document.getElementById('profile').value)" class="lbutton">--><input type="button" value="<?=_("Delete")?>" onclick="profile_delete(document.getElementById('profile').value)" class="lbutton"></td></tr>
							<tr><td class="nobborder" style="padding-top:10px"><input type="text" id="cur_name" value="- <?=_("New Profile")?> -" onfocus="this.value=''"> <input type="button" value="<?=_("Save Current")?>" onclick="profile_save(document.getElementById('cur_name').value)" class="lbutton"></td></tr>
							<tr><td class="nobborder"><input type="text" id="cur_rename" value="" disabled> <input type="button" value="<?=_("Rename")?>" id="rename_button" onclick="profile_rename(document.getElementById('profile').value,document.getElementById('cur_rename').value)" class="lbutton" disabled></td></tr>
						</table>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr><td class="nobborder" style="text-align:center;color:green;font-weight:bold" id="msg"></td></tr>
</table>
</body>
</html>
