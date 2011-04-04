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

$users    = Session::get_users_to_assign($dbconn);
$entities = Session::get_entities_to_assign($dbconn);


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