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
require_once ('classes/Security.inc');
require_once ('classes/Server.inc');
Session::logcheck("MenuPolicy", "PolicyServers");

$db = new ossim_db();
$conn = $db->connect();

$ip       = GET('ip');
$name     = GET('name');


ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Server IP"));
ossim_valid($name, OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _("Server Name"));

if (ossim_error())
    die(ossim_error());

$action = 'newserver.php';

if ( isset($_SESSION['_server']) )
{
	$name            =  $_SESSION['_server']['name'];
	$ip              =  $_SESSION['_server']['ip'];
	$descr           =  $_SESSION['_server']['descr'];
	$port            =  $_SESSION['_server']['port'];
	$correlate       =  $_SESSION['_server']['correlate'];
	$cross_correlate =  $_SESSION['_server']['cross_correlate'];
	$store           =  $_SESSION['_server']['store'];
	$qualify         =  $_SESSION['_server']['qualify'];
	$resend_events   =  $_SESSION['_server']['resend_events'];
	$resend_alarms   =  $_SESSION['_server']['resend_alarms'];
	$multi           =  $_SESSION['_server']['multi'];
	$sign            =  $_SESSION['_server']['sign'];
	$sem             =  $_SESSION['_server']['sem'];
	$sim             =  $_SESSION['_server']['sim'];
	
	unset($_SESSION['_server']);
	
}
else
{
	if ( !empty($name) )
	{
		$server_list = Server::get_list($conn, "WHERE name = '$name'");
		$role_list = Role::get_list($conn, $name);
		
		if ( !empty($server_list) && !empty($role_list) ) 
		{
			$server = $server_list[0];
			$role = $role_list[0];
			$name            =  $server->get_name();
			$ip              =  $server->get_ip();
			$port            =  $server->get_port();
			$descr           =  $server->get_descr();
			$correlate       =  $role->get_correlate();
			$cross_correlate =  $role->get_cross_correlate();
			$store           =  $role->get_store();
			$qualify         =  $role->get_qualify();
			$resend_events   =  $role->get_resend_event();
			$resend_alarms   =  $role->get_resend_alarm();
			$sign            =  $role->get_sign();
			$sem             =  $role->get_sem();
			$sim             =  $role->get_sim();
						
			$action = 'modifyserver.php';
			
		}
	}
	else
	{
		$correlate  = $cross_correlate = $store = $qualify = $resend_events = $resend_alarms = $sim = 1;
		$sign 		= $sem = 0;
	}
}


	$dis_sim                = ( $sim == 0 ) ? "disabled='disabled'" : '';
	$dis_resend         	= ( $opensource || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : '';
	$dis_opens              = ( $opensource ) ? "disabled='disabled'" : '';
	$dis_sign               = ( $sem == 0 ) ? "disabled='disabled'" : '';
	
	$class_sim        	 	= ( $sim == 0 ) ? "class='thgray'" : '';	
	$class_resend           = ( $sem == 0 && $sim == 0 ) ? "class='thgray'" : '';
	$class_sign        	 	= ( $sem == 0 ) ? "class='thgray'" : '';
	$class_opens       	 	= ( $opensource ) ? "class='thgray'" : '';
			
	$chk_correlate[0] 	    = ( $correlate == 0 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	$chk_correlate[1] 	    = ( $correlate == 1 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	
	$chk_cross_correlate[0] = ( $cross_correlate == 0 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	$chk_cross_correlate[1] = ( $cross_correlate == 1 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	
	$chk_qualify[0] 		= ( $qualify == 0 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	$chk_qualify[1] 		= ( $qualify == 1 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	
	$chk_store[0]         	= ( $store == 0 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	$chk_store[1] 			= ( $store == 1 ) ? "checked='checked' $dis_sim" : "$dis_sim";
	
	$chk_sem[0] 			= ( $sem == 0 ) ? "checked='checked' $dis_opens  " : "$dis_opens  ";
	$chk_sem[1] 			= ( $sem == 1 ) ? "checked='checked' $dis_opens  " : "$dis_opens  ";
	
	$chk_multi[0]			= ( $sem == 0 && $sim == 0 ) ? "checked='checked' $dis_opens   " : "$dis_opens  ";
	$chk_multi[1]			= ( $sem == 1 || $sim == 1 ) ? "checked='checked' $dis_opens   " : "$dis_opens  ";
	
	$chk_sim[0] 			= ( $sim == 0 ) ? "checked='checked'" : "";
	$chk_sim[1] 			= ( $sim == 1 ) ? "checked='checked'" : "";
	
	$chk_sign[0] 			= ( $sign == 0 ) ? "checked='checked' $dis_sign" : "$dis_sign";
	$chk_sign[1] 			= ( $sign == 1 ) ? "checked='checked' $dis_sign" : "$dis_sign";
			
	$chk_resend_events[0] 	= ( $resend_events == 0 ) ? "checked='checked' $dis_resend" : "$dis_resend";
	$chk_resend_events[1] 	= ( $resend_events == 1 ) ? "checked='checked' $dis_resend" : "$dis_resend";
		
	$chk_resend_alarms[0] 	= ( $resend_alarms == 0 ) ? "checked='checked' $dis_resend" : "$dis_resend";
	$chk_resend_alarms[1] 	= ( $resend_alarms == 1 ) ? "checked='checked' $dis_resend" : "$dis_resend";
	
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	
	<script type="text/javascript">
		$(document).ready(function(){
			$('textarea').elastic();
			
			$('.vfield').bind('blur', function() {
			     validate_field($(this).attr("id"), "newserver.php");
			});

		});
	</script>
	
	<script type="text/javascript">
		var valsim = <?php echo ( isset($sim) ) ? $sim : 0 ?>;
		var valsem = <?php echo ( isset($sem) ) ? $sem : 0 ?>;
	</script>
	
	<script>
	
</script>
	
	<style type='text/css'>
		<?php
		if ( GET('withoutmenu') == "1" )
		{
			echo "#table_form {background: transparent; width: 400px;}";
		    echo "#table_form th {width: 130px;}";
		}
		else
		{
			echo "#table_form {background: transparent; width: 450px;}";
		    echo "#table_form th {width: 150px;}";
		}
		?>
		
		input[type='text'], select, textarea {width: 90%; height: 18px;}
		.cont_radio{ width: 90%; float: left;}
		textarea { height: 45px;}
		label {border: none; cursor: default;}
		.bold {font-weight: bold;}
		div.bold {line-height: 18px;}
		.val_error { width: 270px;}
		th,.thgray { text-align: left; padding: 0px 10px; }
	</style>
	
	
	<script type="text/javascript">
		function disen(element,text)
		{
			if (element.attr('disabled') == true) {
				element.attr('disabled', '');
				text.removeClass("thgray");
			} 
			else 
			{
				element.attr('disabled', 'disabled');
				text.addClass("thgray");
			}
		}
		
		function dis(element,text)
		{
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
			
			/*if (valsim==0)
			{
				$('input[name=correlate]')[1].checked = true;
				$('input[name=cross_correlate]')[1].checked = true;
				$('input[name=store]')[1].checked = true;
				$('input[name=qualify]')[1].checked = true;
			}*/
			
			if (valsim==0 && valsem==0)
			{
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
				//$('#ralarms').hide();
				//$('#revents').hide();
				//$('#rtitle').hide();
				$('input[name=resend_alarms]')[1].checked = true;
				$('input[name=resend_events]')[1].checked = true;
				$('input[name=multi]')[1].checked = true;
			} 
			else
			{
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
				$('input[name=multi]')[0].checked = true;
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
			
			/*if (valsem==0)
				$('input[name=sign]')[1].checked = true;*/
			
			if (valsim==0 && valsem==0)
			{
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
				//$('#ralarms').hide();
				//$('#revents').hide();
				//$('#rtitle').hide();
				$('input[name=resend_alarms]')[1].checked = true;
				$('input[name=resend_events]')[1].checked = true;
				$('input[name=multi]')[1].checked = true;
			} 
			else
			{
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
			if (val == 1)
			{
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
			} 
			else
			{
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
			}
		}
</script>
</head>
<body>
                                                                                
<?php

if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); 
?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="post" name='form_server' id='form_server' action="<?php echo $action;?>">

<table align="center" id='table_form'>
	<input type="hidden" name="insert" value="insert"/>
	<input type="hidden" name="withoutmenu" id='withoutmenu' value="<?php echo GET('withoutmenu')?>"/>
	 
	<tr>
		<th><label for='name'><?php echo gettext("Name"); ?></label></th>
		<td class="left">
			<?php 
			if ( empty($name) ) 
			{
				echo "<input type='text' class='req_field vfield' name='name' id='name' value='$name'/>";
				echo "<span style='padding-left: 3px;'>*</span>";
			}
			else
			{
				echo "<input type='hidden' class='req_field vfield' name='name' id='name' value='$name'/>";
				echo "<div class='bold'>$name</div>";
			}
			?>
			
		</td>
	</tr>	
	
	<tr>
		<th><label for='ip'><?php echo gettext("IP"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
  
	<tr>
		<th><label for='port'><?php echo gettext("Port"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="port" id="port" value="<?php echo (!( empty($port) ) ) ? $port : 40001;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th style="text-decoration:underline"><label for='sim1'><?php echo _("SIEM")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="sim" class='req_field vfield' id='sim1' value="1" onchange="tsim(1)" <?php echo $chk_sim[1];?>/><?php echo _("Yes");?>
				<input type="radio" name='sim' id="sim2" value="0" onchange="tsim(0)" <?php echo $chk_sim[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th id="qualify_text" style="padding-left:25px" <?php echo $class_sim?>><label for='qualify1'><?php echo _("Qualify events")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="qualify" class='req_field vfield' id="qualify1" value="1" <?php echo $chk_qualify[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="qualify" id="qualify2" value="0" <?php echo $chk_qualify[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
  
	<tr>
		<th id="correlate_text" style="padding-left:25px" <?php echo $class_sim?>><label for='correlate1'><?php echo _("Correlate events")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="correlate" class='req_field vfield' id="correlate1" value="1" <?php echo $chk_correlate[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="correlate" id="correlate2" value="0" <?php echo $chk_correlate[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
  
	<tr>
		<th id="cross_correlate_text" style="padding-left:25px" <?php echo $class_sim?>><label for='cross_correlate1'><?php echo _("Cross Correlate events")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="cross_correlate" class='req_field vfield' id="cross_correlate1" value="1" <?php echo $chk_cross_correlate[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="cross_correlate" id="cross_correlate2" value="0" <?php echo $chk_cross_correlate[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
  
	<tr>
		<th id="store_text" style="padding-left:25px" <?php echo $class_sim?>><label for='store1'><?php echo _("Store events")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="store" class='req_field vfield' id="store1" value="1" <?php echo $chk_store[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="store" id="store2" value="0" <?php echo $chk_store[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
  
	<tr>
		<th style="text-decoration:underline" <?php echo $class_opens?>><label for='sem1'><?php echo _("Logger")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="sem" class='req_field vfield' id="sem1" value="1" <?php echo $chk_sem[1];?> onchange="tsem(1)"/><?php echo _("Yes");?>
				<input type="radio" name="sem" id="sem2" value="0" <?php echo $chk_sem[0];?> onchange="tsem(0)"/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th id="sign_text" style="padding-left:25px" <?php echo $class_sign?>><label for='sign1'> <?php echo _("Sign")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="sign" class='req_field vfield' id="sign1" value="1" <?php echo $chk_sign[1];?>/><?php echo _("Line");?>
				<input type="radio" name="sign" id="sign2" value="0" <?php echo $chk_sign[0];?>/><?php echo _("Block");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
  
	<tr id="rtitle">
		<th style="text-decoration:underline" <?php echo $class_opens?>><label for='multi1'> <?=_("Multilevel")?></label></th>
		<td class="left">
			<input type="radio" name="multi" id="multi1" value="1" onchange="tmulti(1)"<?php echo $chk_multi[1];?>/><?php echo _("Yes");?>
			<input type="radio" name="multi" id="multi2" value="0" onchange="tmulti(0)"<?php echo $chk_multi[0];?>/><?php echo _("No");?>
		</td>
	</tr>
  
	<tr>
		<th id="ralarms_text" style="padding-left:25px" <?php echo $class_resend?>><label for='resend_alarms1'> <?php echo _("Forward alarms")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="resend_alarms" class='req_field vfield' id="resend_alarms1" value="1" <?php echo $chk_resend_alarms[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="resend_alarms" id="resend_alarms2" value="0" <?php echo $chk_resend_alarms[0];?>/><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th id="revents_text" style="padding-left:25px" <?php echo $class_resend?>><label for='resend_events1'><?php echo _("Forward events")?></label></th>
		<td class="left">
			<div class='cont_radio'>
				<input type="radio" name="resend_events" class='req_field vfield' id="resend_events1" value="1" <?php echo $chk_resend_events[1];?>/><?php echo _("Yes");?>
				<input type="radio" name="resend_events" id="resend_events2" value="0" <?php echo $chk_resend_events[0];?> /><?php echo _("No");?>
			</div>
			<span style="padding-left: 8px;">*</span>
		</td>
	</tr>

	<tr>
		<th><label for='descr'><?php echo gettext("Description"); ?></label></th>
		<td class="left noborder"><textarea name="descr" id='descr' class='vfield'><?php echo $descr;?></textarea></td>
	</tr>
	    
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button" class="button" id='send' onclick="submit_form();" value="<?php echo _("Update")?>"/>
			<input type="reset"  class="button" value="<?php echo gettext("Clear form");?>"/> 
		</td>
	</tr>
 
</table>
</form>

</body>
</html>

