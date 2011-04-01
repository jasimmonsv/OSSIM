<?
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

require_once('ossim_conf.inc');
require_once('classes/Session.inc');

Session::logcheck("MenuIncidents", "Osvdb");

//Return array with users that you can see

function get_my_users_vision($conn, $pro)
{
	
	require_once('classes/Session.inc');
	
	if  ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin()) )
	{
		if ( Session::am_i_admin() )
			$users_list = Session::get_list($conn, "ORDER BY login");
		else
			$users_list = Acl::get_my_users($conn,Session::get_session_user());
		
		
		if ( is_array($users_list) && !empty($users_list) )
		{
			foreach($users_list as $k => $v)
				$users[] = ( is_object($v) )? $v->get_login() : $v["login"];
			
			$where = "WHERE login in ('".implode("','",$users)."')";
		}
	}
	else
	{
	
		if ( !$pro )
			$where = "WHERE login in ('".Session::get_session_user()."')";
		else
		{
			$brothers = Acl::get_brothers($conn);
			
			foreach($brothers as $k => $v)
				$users[] = $v["login"];
			
			$users[] = Session::get_session_user();
		
			$where = "WHERE login in ('".implode("','",$users)."')";
		}	
	}	
		

	return Session::get_list($conn, $where." ORDER BY login ASC");
}

//Return array with entities that you can see

function get_my_entities_vision($dbconn, $pro)
{
	
	require_once('classes/Session.inc');
	$entities_types       = array();
	$entities_types_aux   = array();
	$entities             = array();
	
	if  ( Session::am_i_admin() )
	{
		list($entities_all,$num_entities) = Acl::get_entities($dbconn);
		$entities_types_aux               = Acl::get_entities_types($dbconn);
			

		foreach ($entities_types_aux as $etype) { 
			$entities_types[$etype['id']] = $etype;
		}
		
		foreach ( $entities_all as $entity ) 
			$entities[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
		
	}
	else if ($pro && Acl::am_i_proadmin())
	{
		list($entities_all,$num_entities) = Acl::get_entities($dbconn);
		list($entities_admin,$num)        = Acl::get_entities_admin($dbconn,Session::get_session_user());
		
		$entities_list      = array_keys($entities_admin);
		$entities_types_aux = Acl::get_entities_types($dbconn);
	   
		foreach ($entities_types_aux as $etype) { 
			$entities_types[$etype['id']] = $etype;
		}
		
		foreach ( $entities_all as $entity ) 
		{
			if(	in_array($entity["id"], $entities_list) ) 
				$entities[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
		
		}
	
	}

	return $entities;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript">
		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!=''){
				$('#entity').val('');
			}
		}
	</script>
	
	<style type='text/css'>
	
	table{
		margin: 10px auto;
		text-align:center;
		width: 330px;
	} 
	
	td { border: none; }
	
	#update { 
		padding: 10px 0px 0px 0px;
		border: none;
	}
	
	#user, #entity {width: 220px;}
		
	.format_user,.format_entity{
		margin-right: 3px;
		width: 50px;
		text-align: right;
	}
	
	.select_user,.select_entity{
		width: 260px;
	}
	
	
	.format_or{ 
		padding:5px;
		text-align:center; 
		border-bottom: none;
	}
		
	</style>
	
</head>
<body>
<?

$id_document = $_GET["id_document"];
$entity      = $_GET["entity"];
$user        = $_GET["user"];

ossim_valid($id_document, OSS_DIGIT, 'illegal:' . _("Document id"));
ossim_valid($entity, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _("Entity"));
ossim_valid($user, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, 'illegal:' . _("User"));

if (ossim_error()) {
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

if( $entity != "" || $user != "" ) 
{
    $newuser = ( $entity != "" ) ? $entity : $user;
    $query   = "UPDATE repository SET user='$newuser' WHERE id='$id_document'";
	$result  = $dbconn->execute($query);
	
    ?>
	<script type="text/javascript">parent.GB_onclose();</script>
	<?php
}

if( $entity == "" && $user == "") 
{
    $query       = "SELECT user FROM repository where id='$id_document'";
    $result      = $dbconn->Execute($query);
    $user_entity = $result->fields['username'];
}



$conf     = $GLOBALS["CONF"];
$version  = $conf->get_conf("ossim_server_version", FALSE);
$pro      = ( preg_match("/pro|demo/i",$version) ) ? true : false;

$users    = get_my_users_vision($dbconn, $pro);
$entities = ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin())  ) ? get_my_entities_vision($dbconn, $pro) : null;


?>

<form action='change_user.php' method='GET'>
	<input type='hidden' name='id_document' value='<?php echo $id_document?>'/>

		<table cellspacing="0" cellpadding="0" class="transparent">
			<tr>
				<td class='format_user'><?php echo _("User:");?></td>	
				<td class='select_user'>				
					<select name="user" id="user" onchange="switch_user('user');return false;">
						
						<?php
													
						$num_users = 0;
						foreach( $users as $k => $v )
						{
							$login = $v->get_login();
							
							$selected = ( $login == $user_entity ) ? "selected='selected'": "";
							$options .= "<option value='".$login."' $selected>$login</option>\n";
							$num_users++;
						}
						
						if ($num_users == 0)
							echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
						else
						{
							echo "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
							echo $options;
						}
												
						?>
					</select>
				</td>
			</tr>
			
			<tr>
			
			<?php if ( !empty($entities) ) { ?>
			<tr><td class="format_or" colspan='2'><?php echo _("OR");?></td></tr>
		
			<tr>
				<td class='format_entity'><?php echo _("Entity:");?></td>
				<td class='select_entity'>	
					<select name="entity" id="entity" onchange="switch_user('entity');return false;">
						<option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
						<?php
						foreach ( $entities as $k => $v ) 
						{
							$selected = ( $k == $user_entity ) ? "selected='selected'": "";
							echo "<option value='$k' $selected>$v</option>";
						}
						?>
					</select>
				</td>
				<?php } ?>
			</tr>
		
			<tr><td id='update' colspan='2'><input type='submit' class='button' value='<?php echo _("Update")?>'/></td></tr>
		
	</table>
</form>

<?php $dbconn->disconnect(); ?>
</body>
</html>