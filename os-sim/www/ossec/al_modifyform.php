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
require_once ('classes/Ossec.inc');
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Util.inc');

$array_types = array ( 	"ssh_integrity_check_bsd"     => "Integrity Check BSD",
						"ssh_integrity_check_linux"   => "Integrity Check Linux",
						"ssh_generic_diff" 			  => "Generic Command Diff",
						"ssh_pixconfig_diff"  		  => "Cisco Config Check",
						"ssh_foundry_diff" 			  => "Foundry Config Check ",
						"ssh_asa-fwsmconfig_diff "    => "ASA FWSMconfig Check");
						
		
$info_error 	= null;
$error      	= false;
$display        = 'display:none;';

$ip 			= ( !empty($_GET['ip']) ) ? GET('ip') : POST('ip');
$al_data		= GET('al_data');


if ($al_data == 'me')
{
	$validate = array (
		"type"        => array("validation"=>"OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("Type")),
		"frecuency"   => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Frecuency")),
		"state"       => array("validation"=>"OSS_SCORE, OSS_LETTER", "e_message" => 'illegal:' . _("State")),
		"arguments"   => array("validation"=>"OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Arguments")));
}
else
{
	$validate = array (
		"hostname"    => array("validation"=>"OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT", "e_message" => 'illegal:' . _("Hostname")),
		"ip"          => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("IP")),
		"user"        => array("validation"=>"OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("User")),
		"descr"       => array("validation"=>"OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL, OSS_NULLABLE", "e_message" => 'illegal:' . _("Description")),
		"pass"        => array("validation"=>"OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Password")),
		"passc"       => array("validation"=>"OSS_ALPHA, OSS_PUNC_EXT", "e_message" => 'illegal:' . _("Pass confirm")),
		"ppass"       => array("validation"=>"OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Password")),
		"ppassc"      => array("validation"=>"OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE", "e_message" => 'illegal:' . _("Priv. Pass confirm")));
}

if ( GET('ajax_validation') == true )
{
	$validation_errors = validate_form_fields('GET', $validate);
	
	if ( $validation_errors == 1 )
		echo 1;
	else if ( empty($validation_errors) )
		echo 0;
	else
		echo $validation_errors[0];
		
	exit();
}

if ( POST('ajax_validation_all') == true || !empty($_POST['ip']) )
{
	$validation_errors = validate_form_fields('POST', $validate);
	$error_pass = $error_ppass = false;
	
	if (POST('pass') != POST('passc'))
		$error_pass = true;
		
	if ( !empty($_POST['ppass']) && (POST('ppass') != POST('ppassc')) )
		$error_ppass = true;
		
	if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $error_pass || $error_ppass)
	{
		$message_error = array();
			
		if( $error_pass )
			$message_error [] = _("Password fields are different");
		
		if( $error_ppass )
			$message_error [] = _("Privileged Password fields are different");
			
		if ( is_array($validation_errors) && !empty($validation_errors) )
			$message_error = array_merge($message_error, $validation_errors);
		else
		{
			if ($validation_errors == 1)
				$message_error [] = _("Invalid send method");
		}
				
	}

	if ( POST('ajax_validation_all') == true )
	{
		if ( is_array($message_error) && !empty($message_error) )
			echo implode( "<br/>", $message_error);
		else
			echo 0;
		
		exit();
	}
	else
	{
		if ( is_array($message_error) && !empty($message_error) )
			$info_error	= "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";
	}	
}


//Form actions	

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("Ip Address"));

if ( ossim_error() ) 
{
	$info_error = ossim_get_error();
	ossim_clean_error();
}
else
{
	$db 	   = new ossim_db();
	$conn      = $db->connect();
		
	$extra     = "WHERE ip = '$ip'";
	$agentless = array_shift(Agentless::get_list($conn, $extra));

	if ( !empty($agentless) )
	{
		$ip 	  = $agentless->get_ip();
		$hostname = $agentless->get_hostname();
		$user 	  = $agentless->get_user();
		$pass     = $agentless->get_pass();
		$passc    = $pass;
		$ppass    = $agentless->get_ppass();
		$ppassc   = $ppass;
		$descr 	  = $agentless->get_descr();
		
		
		$error_m_entries    = null;
		$monitoring_entries = Agentless::get_list_m_entries($conn, $extra);
		
		if ( !is_array($monitoring_entries) )
		{
			$error_m_entries    = $monitoring_entries;
			$monitoring_entries = array();
		}
	}
	else
		$info_error = _("No agentless host found");
		
	
	$db->close($conn);

}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript">
		messages[6]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Adding Monitoring entry... ")?></span>';
		messages[7]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Deleting Monitoring entry... ")?></span>';
		messages[8]  = '<img src="images/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;"><?php echo _("Updating Monitoring entry... ")?></span>';
	</script>
	
	<script type="text/javascript" src="../js/utils.js"></script>
	
	<script type="text/javascript">
	
		function add_monitoring()
		{
						
			var form_id = "al_modify_form_2";
									
			$("#al_load").html(messages[6]);
						
			$.ajax({
				type: "POST",
				url: "ajax/agentless_actions.php",
				data: $('#'+form_id).serialize() + "&ip="+$('#ip').val()+"&action=add_monitoring_entry",
				success: function(html){
					var status = html.split("###");
					$("#al_load").html('');
					$("#info").css('display', 'none');
					$("#info").html('');
					var style = "class='error_left'";
					
					if ( status[0] == "general_error")
					{
						$("#info").removeClass();
						$("#info").addClass("ossim_error");
						$("#info").html(status[1]);
						$("#info").fadeIn(2000);
					}
					else if ( status[0] == "error")
					{
						$("#al_load").html("<div class='cont_al_message'><div class='al_message'><div class='ossim_error'><div "+style+">"+status[1]+"</div></div></div></div>");
						$("#al_load").fadeIn(2000);
						$("#al_load").fadeOut(4000);
					}
					else
					{
						if ( $('#monitoring_table .al_no_added').length == 1 )
							$('.al_no_added').parent().remove();	
						
						$('#monitoring_table tr:last').after(status[1]);
						$('#monitoring_table tr:even').css('background-color', "#EEEEEE");		
											
					}                                                           
				}
			});
		}
	
	
		function delete_monitoring(id)
		{
			var form_id = "al_modify_form_2";
			
			$("#al_load").html(messages[7]);
						
			$.ajax({
				type: "POST",
				url: "ajax/agentless_actions.php",
				data: $('#'+form_id).serialize() + "&action=delete_monitoring_entry&id="+id,
				success: function(html){
					
					var status = html.split("###");
					$("#al_load").html('');
					$("#info").css('display', 'none');
					$("#info").html('');
					
					var style = "class='error_left'";
					
					if ( status[0] == "general_error")
					{
						
						$("#info").removeClass();
						$("#info").addClass("ossim_error");
						$("#info").html(status[1]);
						$("#info").fadeIn(2000);
					}
					else if ( status[0] == "error")
					{
						$("#al_load").html("<div class='cont_al_message'><div class='al_message'><div class='ossim_error'><div "+style+">"+status[1]+"</div></div></div></div>");
						$("#al_load").fadeIn(2000);
						$("#al_load").fadeOut(4000);
					}
					else
					{
						if ( $('#monitoring_table .al_no_added').length == 1 )
							$('.al_no_added').parent().remove();	
						
						$('#m_entry_'+id).remove();
																		
						if ( $('#monitoring_table tbody tr').length == 0 )
						{
							var msg="<tr><td class='al_no_added noborder' colspan='5'><div class='al_info_added'><?php echo _("No monitoring entries added")?></div></td></tr>";
							$('#monitoring_table tbody').html(msg);
						}
						else
							$('#monitoring_table tr:even').css('background-color', "#EEEEEE");		
						
					}                                                           
				}
			});
		}
		
		
		function add_values(id)
		{
			var type       = $("#al_type_"+id).text();
			var frecuency  = $("#al_frecuency_"+id).text();
			var state      = $("#al_state_"+id).text();
			var arguments  = $("#al_arguments_"+id).text();
									
			$('#type').val(type);
			$('#frecuency').val(frecuency);
			$('#state').val(state);
			change_type(type);
			$('#arguments').val(arguments);
			
			$('.add').unbind('click');
			$('.add').val(messages[5]);
			
			$('.add').bind('click', function() {
				modify_monitoring(id);
			});
		}
		
		
		function modify_monitoring(id)
		{
			
			var form_id = "al_modify_form_2";
									
			$("#al_load").html(messages[8]);
						
			$.ajax({
				type: "POST",
				url: "ajax/agentless_actions.php",
				data: $('#'+form_id).serialize() + "&ip="+$('#ip').val()+ "&action=modify_monitoring_entry&id="+id,
				success: function(html){
					
					var status = html.split("###");
					$("#info").css('display', 'none');
					$("#al_load").html('');
					$("#info").html('');
					var style = "class='error_left'";
					
					if ( status[0] == "general_error")
					{
						$("#info").removeClass();
						$("#info").addClass("ossim_error");
						$("#info").html(status[1]);
						$("#info").fadeIn(2000);
					}
					else if ( status[0] == "error")
					{
						$("#al_load").html("<div class='cont_al_message'><div class='al_message'><div class='ossim_error'><div "+style+">"+status[1]+"</div></div></div></div>");
						$("#al_load").fadeIn(2000);
						$("#al_load").fadeOut(4000);
					}
					else
					{
						
						$('#m_entry_'+id).html();
						
						$('#m_entry_'+id).html(status[1]);
						
						$('#monitoring_table tr:even').css('background-color', "#EEEEEE");	
						
						$('.add').unbind('click');
									
						$('.add').bind('click', function() {
							add_monitoring(id);
						});
					}  

					$('.add').val("<?php echo _("Add")?>");
				}
			});
		}
		
		function modify_host_data()
		{
			
			var form_id = "al_modify_form_1";
											
			$.ajax({
				type: "POST",
				url: "ajax/agentless_actions.php",
				data: $('#'+form_id).serialize() + "&action=modify_host_data",
				success: function(html){
					
					var status = html.split("###");
					$("#info").css('display', 'none');
					$("#al_load").html('');
					$("#info").html('');
					var style = "class='error_left'";
					$("#info").removeClass();
					
					if ( status[0] == "error")
					{
						$("#info").addClass("ossim_error");
						$("#info").html(status[1]);
						$("#info").fadeIn(2000);
					}
					else
					{
						$("#info").addClass("ossim_success");
						$("#info").html(status[1]);
						$("#info").fadeIn(2000);
					}  
					
				}
			});
		}
	
		function change_type(t_value)
		{
			if (t_value != '')
				var type = t_value;
			else
				var type = $('#type').val();
				
			if (type.match("_diff") != null)
			{
				$('#state_txt').text("Periodic_diff");
				$('#state').val("periodic_diff");
			}
			else
			{
				if (type.match("_integrity") != null)
				{
					$('#state_txt').html("Periodic");
					$('#state').val("periodic");
				}
			}
		}
		
		function change_arguments ()
		{
			var type = $('#type').val();
			
			if (type.match("_diff") != null)
				$('#arguments').text("");
			
			else
			{
				if (type.match("_integrity") != null)
					$('#arguments').text("/bin /etc /sbin");
			}
			
		}	
	
	</script>
	
	<script type="text/javascript">
		$(document).ready(function(){
			
			$('textarea').elastic();
				
			$('#monitoring_table tbody tr:even').css('background-color', "#EEEEEE");	
			
			$('#al_modify_form_1 .vfield').bind('blur', function() {
				validate_field($(this).attr("id"), $('#al_modify_form_1').attr("action"));
			});
			
			$('.update').bind('click', function() {
				modify_host_data();
			});
			
						
			$('.add').bind('click', function() {
				add_monitoring();
				$('.add').val("<?php echo _("Add")?>");
			});
			
			$('#type').bind('change', function() {
				change_type('');
				change_arguments();
			});
		});

	</script>
	
	
	<style type='text/css'>
		#table_form {background: transparent; border:none; width:75%;}
		input[type='text'],	input[type='password'], textarea {width: 90%; height: 18px;}
		select{width: 90%; height: 22px;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		a {cursor:pointer;}
		.subsection_1 {width: 45%; background: transparent; vertical-align: top; border:none;}
		#subsection_1 th {width: 190px;}
		.subsection_2 {width: 55%; background: transparent; vertical-align: top; border:none;}
		#subsection_2 th {width: 190px;}
		#state_txt, #ip_back {width: 90%; height: 18px; float:left;}
		.cont_update { border: none; padding: 15px 0px 10px 0px;}
		.al_type { width: 130px !important; padding: 3px 0px;}
		.al_frecuency { width: 80px !important; padding: 2px 0px;}
		.al_state { width: 80px !important; padding: 2px 0px;}
		.al_arguments{ padding: 2px 0px;}
		.al_actions { width: 50px !important; padding: 2px 0px; }
		.al_info_added {font-weight: bold; text-align: center; color: #D8000C; padding: 10px 0px;}
		.al_sep {height: 20px; border: none;}
		.cont_al_message {position: relative; width: 70%; margin:auto;}
		.cont_al_message .ossim_error {width: auto;}
		.al_message {position: absolute; width: 100%; top: -55px;}
		.ossim_error {padding: 10px 10px 10px 50px !important; }
		.al_advice {font-size: 10px; font-style: italic; padding: 3px 0px; width:90%;}
		#monitoring_table {width: 100%;}
		#monitoring_table td {font-size:11px;}
		#arguments {float: left; width:90%;}
		.balloon { width: 20px; float: left; margin-left: 5px;}
		.ct_mandatory {vertical-align: middle; text-align: left;}
		.ct_opt_format { width:200px; background: transparent; border: none;}
		.ct_title { padding: 0px 0px 3px 5px; font-size: 11px; text-align: left; }
	</style>
</head>

<body>

<?php include("../hmenu.php"); ?>

<h1><?php echo _("Modify Agentless Host")?></h1>


<?php 

if ( !empty($info_error) )
{
	Util::print_error($info_error);	
	Util::make_form("POST", "agentless.php");	
}
else
{

?>
	<div id='info' class='ossim_error' style='<?php echo $display;?>'><?php echo _($info)?></div>
	   
	<table align="center" id='table_form'>
		<tr>
			<td class='nobborder subsection_1'>
				<form method="POST" name="al_modify_form_1" id="al_modify_form_1" action="al_modifyform.php?al_data=hd">
				<table width='100%'>
				
					<tr>
						<td colspan='2' class='headerpr'><span><?php echo _("Host Data Configuration")?></span></td>
					</tr>
				
					<tr>
						<th><label for='hostname'><?php echo _("Hostname"); ?></label></th>
						<td class="left">
							<input type="text" class='req_field vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>	
					
					<tr>
						<th><label for='ip'><?php echo _("IP"); ?></label></th>
						<td class="left">
							<div id="ip_back" class='bold'><?php echo $ip;?></div>
							<input type="hidden" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
					  
					<tr>
						<th><label for='mac'><?php echo _("User"); ?></label></th>
						<td class="left">
							<input type="text" class='req_field vfield' name="user" id="user" value="<?php echo $user;?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>
					
					<tr>
						<th><label for='pass'><?php echo _("Password"); ?></label></th>
						<td class="left">
							<input type="password" class='req_field vfield' name="pass" id="pass" value="<?php echo $pass;?>"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>

					<tr>
						<th><label for='passc'><?php echo _("Password confirm"); ?></label></th>
						<td class="left">
							<input type="password" class='req_field vfield' name="passc" id="passc" value="<?php echo $passc;?>"/>
							<span style="padding-left: 3px;">*</span>
							<div class='al_advice'><?php echo _("(*) If you want to use public key authentication instead of passwords, you need to provide NOPASS as Normal Password ") ?></div>
						</td>
					</tr>
						
					<tr>
						<th><label for='ppass'><?php echo _("Privileged Password"); ?></label></th>
						<td class="left">
							<input type="password" class='vfield' name="ppass" id="ppass" value="<?php echo $ppass;?>"/>
						</td>
					</tr>
					
					<tr>
						<th><label for='user'><?php echo _("Privileged Password confirm"); ?></label></th>
						<td class="left">
							<input type="password" class='vfield' name="ppassc" id="ppassc" value="<?php echo $ppassc;?>"/>
							<div class='al_advice'><?php echo _("(*) If you want to add support for \"su\", you need to provide Privileged Password") ?></div>
						</td>
					</tr>

					<tr>
						<th><label for='descr'><?php echo _("Description"); ?></label></th>
						<td class="left nobborder">
							<textarea name="descr" id="descr" class='vfield'><?php echo $descr;?></textarea>
						</td>
					</tr>
					
					<tr>
						<td colspan="2" class="cont_update">
							<input type="button" class="button update" id='send' value="<?php echo _("Update")?>"/>
						</td>
					</tr>
				</table>
				</form>
			</td>
		
			<td class='nobborder subsection_2'>
				<form method="POST" name="al_modify_form_2" id="al_modify_form_2" action="al_modifyform.php?al_data=me">
				<table width='100%'>
					<tr>
						<td colspan='2' class='headerpr'><span><?php echo _("Monitoring Entries")?></span></td>
					</tr>
				
					<tr>
						<th><label for='type'><?php echo _("Type"); ?></label></th>
						<td class="left">
							<select name="type" id="type">
							<?php
								foreach ($array_types as $k => $v)
									echo "<option value='$k'>$v</option>";
							?>
							</select>
							<span style="padding-left: 5px;">*</span>
						</td>
					</tr>
		
					<tr>
						<th><label for='frecuency'><?php echo _("Frecuency"); ?></label></th>
						<td class="left">
							<input type="text" name="frecuency" id="frecuency" value="86400"/>
							<span style="padding-left: 3px;">*</span>
						</td>
					</tr>
			
					<tr>
						<th><label for='state'><?php echo _("State"); ?></label></th>
						<td class="left">
							<div id="state_txt" class='bold'>Periodic</div>
							<input type="hidden" class="state" id='state' name='state' value="periodic"/>
							<span style="padding-left: 8px;">*</span>
						</td>
					</tr>
		
					<tr>
						<th>
							<label for='arguments'><?php echo _("Arguments"); ?></label>
						</th>
						<td class="ct_mandatory nobborder left">
							<textarea name="arguments" id="arguments">/bin /etc /sbin</textarea>
							<div class="nobborder balloon">  
								<a style='cursor:pointer'><img src="../pixmaps/help-small.png" alt='Help'/></a> 
								<span class="tooltip">      
								<span class="top"></span>      
								<span class="middle ne11">          
									<table class='ct_opt_format' border='1'>
										<tbody>
										<tr><td class='ct_bold noborder center'><span class='ct_title'><?=_("Please Note:")?></span></td></tr>
										<tr>
											<td class='noborder'>
												<div class='ct_opt_subcont'>
													<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/>
													<span class='ct_bold'><?=_("If type value is Generic Command Diff")?>:</span>
													<div class='ct_pad5'>
														<span><?php echo _("Ex.: ls -la /etc; cat /etc/passwd")?></span>
													</div>
												</div>
												<br/>
												<div class='ct_opt_subcont'>
													<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/>
													<span class='ct_bold'><?php echo _("Other cases")?>:</span>
													<div class='ct_pad5'><span><?php echo _("Ex.: bin /etc /sbin")?></span>
													</div>
												</div>
											</td>
										</tr>
										
										</tbody>
									</table>
								</span>      
								<span class="bottom"></span>  
								</span>
							</div>
						</td>
					</tr>
					
					<tr>
						<td colspan='2' style='padding:15px 10px 5px 0px;' class='right nobborder'>
							<input type="button" class="button add" name='add' id='send' value="<?=_("Add")?>"/>
						</td>
					</tr>
					
					<tr><td class='al_sep' id='al_load' colspan='2'></td></tr>
					
					<tr>
						<td class='nobborder' colspan='2'>
							<table class='noborder' id='monitoring_table'>
								<thead class='center'>
									<tr><th colspan='5' class='headerpr center;' style='padding: 3px 0px;'><?php echo _("Monitoring entries added")?></th></tr>
									<tr>
										<th class='al_type'><?php echo _("Type")?></th>
										<th class='al_frecuency'><?php echo _("Frecuency")?></th>
										<th class='al_state'><?php echo _("State")?></th>
										<th class='al_arguments'><?php echo _("Arguments")?></th>
										<th class='al_actions'><?php echo _("Actions")?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<?php 
										if ( count($monitoring_entries) > 0 )
										{
											$path = '../pixmaps';
											
											foreach ($monitoring_entries as $k => $v)
											{
												echo "<tr id='m_entry_".$v['id']."'>
														<td class='nobborder center' id='al_type_$id'>". $v['type']."</td>
														<td class='nobborder center' id='al_frecuency_".$v['id']."'>".$v['frecuency']."</td>
														<td class='nobborder center' id='al_state_".$v['id']."'>".$v['state']."</td>
														<td class='nobborder left' id='al_arguments_".$v['id']."'>".$v['arguments']."</td>
														<td class='center nobborder'>
															<a onclick=\"add_values('".$v['id']."')\"><img src='$path/pencil.png' align='absmiddle' alt='"._("Modify monitoring entry")."' title='"._("Modify monitoring entry")."'/></a>
															<a onclick=\"delete_monitoring('".$v['id']."')\" style='margin-right:5px;'><img src='$path/delete.gif' align='absmiddle' alt='"._("Delete monitoring entry")."' title='"._("Delete monitoring entry")."'/></a>
														</td>
													</tr>"; 
											}
										}
										else
										{
											$info_entries = ( $error_m_entries != null) ? $error_m_entries : _("No monitoring entries added");
											echo "<td class='al_no_added noborder' colspan='5'><div class='al_info_added'>$info_entries</div></td>";
										}
										?>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
													
					
				</table>
				</form>
			</td>
		</tr>
	</table>
	
	<p align="center" style="font-style: italic;"><?php echo _("Values marked with (*) are mandatory"); ?></p>
	
<?php } ?>


</body>
</html>


