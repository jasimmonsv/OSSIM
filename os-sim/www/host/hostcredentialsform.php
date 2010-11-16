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
* 
* Classes list:
*/
require_once ('classes/Session.inc');
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$ip  = GET('ip');		

ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Ip"));

if (ossim_error()) 
	die(ossim_error());

$db = new ossim_db();
$conn = $db->connect();

$credential_type = Host::get_credentials_type($conn);


if ( isset($_SESSION['_credentials']) )
{
	$hostname  = $_SESSION['_credentials']['hostname'];  
	$ip        = $_SESSION['_credentials']['ip']; 
	$type      = $_SESSION['_credentials']['type']; 
	$user_ct   = $_SESSION['_credentials']['user_ct'];   
	$pass_ct   = $_SESSION['_credentials']['pass_ct']; 
	$pass_ct2  = $_SESSION['_credentials']['pass_ct2']; 
	$extra     = $_SESSION['_credentials']['extra'];  
	
	unset($_SESSION['_credentials']);
		
}
else
{
	if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
		$host = $host_list[0];
	
	if (!empty($host))
	{
		$hostname = $host->get_hostname();
		$ip = $host->get_ip();
		
		$credentials = Host::get_credentials_ip($conn, $ip);
		
		if (is_array($credentials) && !empty($credentials) )
		{
			
			
			$type     = $credentials[0]['type'];
			$user_ct  = $credentials[0]['username'];
			$pass_ct  = $pass_ct2 = $credentials[0]['password'];
			$extra    = $credentials[0]['extra'];
		}
		else
			list($type, $user_ct, $pass_ct, $pass_ct2, $extra) = array("", "", "", "", "");
			
	}
}



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript">
  
		$(document).ready(function() {
			$('#edit_c').bind('click', function() {
				$('#action').val('edit');
				$('#edit_c').attr("id", "send");
				submit_form();
				$('#send').val("Update");
				$('#send').attr("id", "edit_c");
				
			});
			
			$('#clean_c').bind('click', function() {
				$('#action').val('clean');
				
				$('#type').removeClass("req_field vfield");
				$('#user_ct').removeClass("req_field vfield");
				$('#pass_ct').removeClass("req_field vfield");
				$('#pass_ct2').removeClass("req_field vfield");
				
				$('#clean_c').attr("id", "send");
				
				submit_form();
				
				$('#send').val("Clean Credentials");
				$('#send').attr("id", "clean_c");
			});
			
			$('textarea').elastic();
					
			$('.vfield').bind('blur', function() {
				 validate_field($(this).attr("id"), "modifycredentials.php");
			});
			
		});
				
	</script>
</head>

<style type='text/css'>
	#table_form { min-width: 500px;}
	#table_form th {width: 150px;}
	input[type='text'], input[type='password'], select, textarea {width: 90%; height: 18px;}
	textarea { height: 45px;}
	label {border: none; cursor: default;}
	.bold {font-weight: bold;}
	div.bold {line-height: 18px;}
	a {cursor:pointer;}
	
</style>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); ?>


<div id='info_error' class='ossim_error' style='display: none;'></div>

<form method="POST" name='credential_form' id='credential_form' action="modifycredentials.php">

<input type="hidden" name="action" id='action'/>

<table id='table_form' align='center'>
		
	<tr>
		<th><label for='hostname'><?php echo gettext("Hostname"); ?></label></th>
		<td class="left">
			<input type="hidden" class='req_field vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>
			<div class='bold'><?php echo $hostname?></div>
		</td>
	</tr>	
	
	<tr>
		<th><label for='ip'><?php echo gettext("Ip"); ?></label></th>
		<td class="left">
			<input type="hidden" class='req_field vfield' name="ip" id="ip" value="<?php echo $ip;?>"/>
			<div class='bold'><?php echo $ip?></div>
		</td>
	</tr>	
		
	<tr>
		<th><label for='type'><?php echo gettext("Type"); ?></label></th>
		<td class="left">
			<select name='type' id='type' class='req_field vfield'>
			<?php
				foreach ($credential_type as $k => $v)
				{
					$selected = ($v['id'] == $type) ? "selected='selected'" : '';
					echo "<option value='".$v['id']."' $selected>".$v['name']."</option>";
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<th><label for='user_ct'><?php echo gettext("Username"); ?></label></th>
		<td class="left">
			<input type="text" class='req_field vfield' name="user_ct" id="user_ct" value="<?php echo $user_ct;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
		
	<tr>
		<th><label for='pass_ct'><?php echo gettext("Password"); ?></label></th>
		<td class="left">
			<input type="password" class='req_field vfield' name="pass_ct" id="pass_ct" value="<?php echo $pass_ct;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	<tr>
		<th><label for='pass_ct2'><?php echo gettext("Repeat Password"); ?></label></th>
		<td class="left">
			<input type="password" class='req_field vfield' name="pass_ct2" id="pass_ct2" value="<?php echo $pass_ct2;?>"/>
			<span style="padding-left: 3px;">*</span>
		</td>
	</tr>
	
	
	<tr>
		<th><label for='extra'><?php echo gettext("Extra"); ?></label></th>
		<td class="left noborder"><textarea name='extra' id='extra'><?php echo $extra;?></textarea></td>
	</tr>
	
	<tr>
		<td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
			<input type="button" class="button" name="edit_c"  id='edit_c' value="<?=_("Update")?>"/>
			<input type="button" class="button" name="clean_c" id='clean_c' value="<?=_("Clean Credentials")?>"/>
		</td>
	</tr>
	
</table>


<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>

</form>


</body>
</html>
<?php $db->close($conn); ?>
