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
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';

Session::logcheck("MenuEvents", "EventsVulnerabilities");

require_once 'ossim_db.inc';
require_once 'classes/Incident.inc';
require_once 'classes/Incident_vulnerability.inc';
require_once '../incidents/incident_common.php';
$db   = new ossim_db();
$conn = $db->connect();

$ref       = !ossim_valid(GET('ref') , OSS_LETTER) ? die("Ref required") : GET('ref');
$title     = GET('title');
$priority  = GET('priority');
$type      = GET('type');
$ip        = GET('ip');
$port      = GET('port');
$nessus_id = GET('nessus_id');
$risk      = GET('risk');

// TODO: Check the validations below, narrow them down a bit
ossim_valid($title, OSS_PUNC_EXT, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Title"));
ossim_valid($priority, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Priority"));
ossim_valid($type, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Type"));
ossim_valid($ip, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Ip"));
ossim_valid($port, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Port"));
ossim_valid($nessus_id, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Nessus id"));
ossim_valid($risk, OSS_PUNC, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Risk"));

if (ossim_error()) {
    die(ossim_error());
}

$submitter = Session::get_session_user();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <meta http-equiv="Pragma" content="no-cache"/>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }
  </script>
</head>
<body>

<form method="POST" action="../incidents/manageincident.php" target="main">
	<input type="hidden" name="from_vuln" value="1" />
	<input type="hidden" name="action" value="newincident" />
	<input type="hidden" name="ref" value="<?php echo $ref ?>" />
	<input type="hidden" name="submitter" size="40" value="<?php echo $submitter ?>" />

	<br/>
	<table align="center">
	<?php

	$result = $conn->Execute("SELECT name FROM plugin_sid WHERE plugin_id=3001 AND sid=$nessus_id");
	$title  = ( $result->fields["name"]=="" ) ? _("New Vulnerability ticket") : $result->fields["name"];

	?>
		<tr>
			<th><?php echo _("Title") ?></th>
			<td class="left">
				<input type="text" name="title" size="40" value="<?php echo $title ?>"/>
			</td>
		</tr>
	  
	<?php

	$users    = Session::get_users_to_assign($conn);
	$entities = Session::get_entities_to_assign($conn);

	?>

		<tr>
			<th><?php echo _("Assign To")?></th>
			<td class='left'>
				<table width="400" cellspacing="0" cellpadding="0" class="transparent">
					<tr>
						<td class='nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>
						<td class="nobborder">
												
							<select name="transferred_user" id="user" onchange="switch_user('user');return false;">
								
								<?php
															
								$num_users = 0;
								foreach( $users as $k => $v )
								{
									$login = $v->get_login();
									if ( $login != Session::get_session_user() )
									{
										$options .= "<option value='".$login."' $selected>".format_user($v, false)."</option>\n";
										$num_users++;
									}
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
						<?php if ( !empty($entities) ) { ?>
						<td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>
						<td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
						<td class="nobborder">
							<select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;">
								<option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
								<?php
								foreach ( $entities as $k => $v ) 
								{
									$selected = ( $k == $entity ) ? "selected='selected'": "";
									echo "<option value='$k' $selected>$v</option>";
								}
								?>
							</select>
						</td>
						<?php } ?>
					</tr>
				</table>
			</td>
		</tr>

		<tr>
			<th><?php echo _("Priority") ?></th>
			<td class="left">
				<select name="priority">
					<?php
					for ($i = 1; $i <= 10; $i++) 
					{
						echo "<option value='$i'>$i</option>";
					}	
					?>
				</select> 
			</td>
		</tr>
	  
		<tr>
		<th><?php echo _("Type") ?></th>
			<?php Incident::print_td_incident_type($conn, $type);?>
		</tr>
		<tr>
			<th><?php echo _("IP") ?></th>
			<td class="left">
				<input type="text" name="ip" value="<?php echo $ip ?>"/>
			</td>
		</tr>
		
		<tr>
			<th><?php echo _("Port") ?></th>
			<td class="left">
				<input type="text" name="port" size="30" value="<?php echo $port ?>" />
			</td>
		</tr>
		
		<tr>
			<th><?php echo _("Nessus/OpenVas ID") ?></th>
			<td class="left">
				<input type="text" name="nessus_id" size="30" value="<?php echo $nessus_id ?>" />
			</td>
		</tr>
		
		<tr>
			<th><?php echo _("Risk") ?></th>
			<td class="left">
				<input type="text" name="risk" size="30" value="<?php echo $risk ?>" />
			</td>
				<?php $result = $conn->Execute("SELECT description FROM vuln_nessus_plugins WHERE id=$nessus_id"); ?>
		</tr>
		
		<tr>
			<th><?php echo _("Description") ?></th>
			<td style="border-width: 0px;" class="nobborder">
				<textarea name="description" rows="10" cols="80" wrap="hard"><?php echo strip_tags($result->fields["description"]) ?></textarea> 
			</td>
		</tr>
		
		<tr>
			<td colspan="2" class="nobborder" style="text-align:center;">
				<input type="submit" value="<?php echo _("OK")?>" class="button" />
			</td>
		</tr>
	</table>
</form>

<?php $db->close($conn); ?>
</body>
</html>
