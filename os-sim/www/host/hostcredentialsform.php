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
Session::logcheck("MenuPolicy", "PolicyHosts");
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');


$ip = GET('ip');
$error = false;

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("ip"));

if (ossim_error()) 
{
	$error = true;
	$$txt_error =  ossim_get_error_clean();
	ossim_clean_error();
}

$db = new ossim_db();
$conn = $db->connect();
?>

<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
  <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
  <script type="text/javascript">
  
	$(document).ready(function() {
		$('#edit_c').bind('click', function() {
		  edit_credentials();
		});
		
		$('#clean_c').bind('click', function() {
		  clean_credentials();
		});
	});
	
	String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); };
	
	function edit_credentials()
	{
	    $('#action').val('edit');
		check_form();
	}
	
	function clean_credentials()
	{
		$('#action').val('clean');
		$('#credential_form').submit();
	}

	function check_form()
	{
		var msg= '';
		var username  = $('#username').val().trim();
		var password  = $('#password').val().trim();;
		var password2 = $('#password2').val().trim();;
		
		if (username == '')
			msg += "Username is empty<br/>"
			
		if (password == '')
			msg += "Password is empty<br/>"
		else
		{
			if (password != password2)
				msg += "Password fields are different<br/>"
		}
		
		if ( msg != '' )
		{
			msg_error = "<div style='padding: 0px 10px'>Form contains validation errors:<div><div style='padding: 5px 0px 5px 20px;'>"+msg+"</div>";
			$("#info_error").html(msg_error);
			$("#info_error").css('display', 'block');
			window.scrollTo(0,0);
			return false;
		}
		else
		{
			$("#info_error").css('display', 'none');
			$("#info_error").html("");
			$('#credential_form').submit();
			return true;
		}
	
	}

  
  
  </script>
</head>
<style type='text/css'>
	#table_1, #table_2 { background-color: transparent; width:500px; margin: auto;}
	#table_1 {margin-top: 30px;}
	#table_1 th, #table_2 th {padding: 3px;}
	input[type='text'], input[type='password'], textarea, select { width: 100%;}
	input[type='text'],input[type='password'], select {height: 22px;}
	textarea {height: 45px;}
	.mandatory {width: 20px; text-align: left;}
</style>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); ?>

<?php
if ($error == false)
{
	if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) 
		$host = $host_list[0];
	
	
	if (empty($host))
	{
		$$txt_error = _("You don't have permission to modify this host");
		$error = true;
	}
	else
	{
		$credential_type = Host::get_credentials_type($conn);
		$ip = $host->get_ip();
		$hostname = $host->get_hostname();
		$credentials = Host::get_credentials_ip($conn, $ip);
			
		if (is_array($credentials) && !empty($credentials) )
		{
			$type     = $credentials[0]['type'];
			$username = $credentials[0]['username'];
			$password = $credentials[0]['password'];
			$extra    = $credentials[0]['extra'];
		}
		else
			list($type, $username, $password, $extra) = array("", "", "", "");
			
	}
}

if ($error == true)
{

?>
	<div id='info_error' class='ct_error' style='display: block;'>
		<div style='padding: 10px;'><?php echo $$txt_error?></div>
	</div>
	<div style='text-align: center; margin: auto;'>
		<form method='GET' action='host.php'>
			<input type='submit' value='Back' name='submit' class='button'/>
		</form>
	</div>
	<?php
    exit;
}

?>
<div id='info_error' class='ct_error'></div>
<form method="post" name='credential_form' id='credential_form' action="modifycredentials.php">
<input type="hidden" name="ip" value="<?php echo $ip; ?>"/>
<input type="hidden" name="action" id='action'/>

<table id='table_1'>
	<tr>
		<th> <?php echo gettext("Hostname"); ?></th>
		<td class="left noborder" colspan='2'>
			<span><?php echo $host->get_hostname(); ?><span/>
		</td>
	</tr>
	<tr>
		<th> <?php echo gettext("IP"); ?></th>
		<td class="left noborder" colspan='2'>
			<span style='font-weight: bold;'><?php echo $ip; ?></span>
			<input type="hidden" name="ip" value="<?php echo $ip ?>"/>
		</td>
	</tr>
	<tr>
		<th><?php echo gettext("Type");?></th>
		<td class="left noborder">
			<select name='type' id='type'>
			<?php
				foreach ($credential_type as $k => $v)
				{
					$selected = ($v['id'] == $type) ? "selected='selected'" : '';
					echo "<option value='".$v['id']."' $selected>".$v['name']."</option>";
				}
			?>
			</select>
		</td>
		<td class='noborder'></td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Username");?></th>
		<td class="left noborder"><input type='text' name='username' id='username' value='<?php echo $username;?>'/></td>
		<td class="noborder mandatory"><span style="padding-left: 3px;">*</span></td>
	</tr>
		
	<tr>
		<th> <?php echo gettext("Password");?></th>
		<td class="left noborder"><input type='password' name='password' id='password' value='<?php echo $password;?>'/></td>
		<td class="noborder mandatory"><span style="padding-left: 3px;">*</span></td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Repeat Password");?></th>
		<td class="left noborder"><input type='password' name='password2' id='password2' value='<?php echo $password;?>'/></td>
		<td class="noborder mandatory"><span style="padding-left: 3px;">*</span></td>
	</tr>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Extra");?></th>
		<td class="left noborder"><textarea name='extra' id='extra'><?php echo $extra;?></textarea></td>
		<td class="noborder"></td>
	</tr>
	<tr>
		<td colspan="3" class="noborder" style="text-align:center; padding: 5px;">
			<input type="button" name="edit_c" id='edit_c' value="<?=_("Update")?>" class="button" style="font-size:12px"/>
			<input type="button" name="clean_c" id='clean_c' value="<?=_("Clean Credentials")?>" class="button" style="font-size:12px"/>
		</td>
	</tr>
	
</table>


<table id='table_2' class='noborder'>
	<tr><td class="noborder"><p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p></td></tr>
</table>

</form>


</body>
</html>
<?php $db->close($conn); ?>
