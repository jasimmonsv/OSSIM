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
* - check_perms()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuConfiguration", "ConfigurationUsers");
require_once ('ossim_acl.inc');
require_once 'languages.inc';

// Get password length
$pass_length_min = ($conf->get_conf("pass_length_min", FALSE)) ? $conf->get_conf("pass_length_min", FALSE) : 7;
$pass_length_max = ($conf->get_conf("pass_length_max", FALSE)) ? $conf->get_conf("pass_length_max", FALSE) : 255;

$pass_length_max = ( $pass_length_max < $pass_length_min || $pass_length_max < 1 ) ? 255 : $pass_length_max;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css" />
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/jquery.checkboxes.js"></script>
	<script type="text/javascript" src="../js/jquery.pstrength.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
		
	<script type="text/javascript">
  
		function kdbperms (users) {
			document.fnewuser.knowledgedb_perms.value = users;
		}

		var checks = new Array;
		checks['nets']   = 0;
		checks['sensor'] = 0;
		checks['perms']  = 0;
		
		function checkall(col) {
			if (checks[col]) 
			{
				$("#fnewuser").unCheckCheckboxes("."+col, true)
				checks[col] = 0;
			} 
			else 
			{
				$("#fnewuser").checkCheckboxes("."+col, true)
				checks[col] = 1;
			}
		}
		
		function checkpasscomplex(pass) {
			<?php if ($conf->get_conf("pass_complex", FALSE) == "yes") { ?>
			var counter = 0;
			if (pass.match(/[a-z]/)) { counter++; }
			if (pass.match(/[A-Z]/)) { counter++; }
			if (pass.match(/[0-9]/)) { counter++; }
			if (pass.match(/[\!\"\·\$\%\&\/\(\)\|\#\~\€\¬\.\,\?\=\-\_\<\>]/)) { counter++; }
			return (counter < 3) ? 0 : 1;
			<?php } else { ?>
			return 1;
			<?php } ?>
		}
	
		function checkpasslength() {
			if ($('#pass1').val().length < <?php echo $pass_length_min ?>) {
				alert("<?php echo _("Minimum password size is ").$pass_length_min._(" characters")?>");
				return 0;
			} else if ($('#pass1').val().length > <?php echo $pass_length_max ?>) {
				alert("<?php echo _("Maximum password size is ").$pass_length_max._(" characters")?>");
				return 0;
			} else return 1;
		}

		function checkpass() {
			if (document.fnewuser.pass1.value != "" && !checkpasscomplex(document.fnewuser.pass1.value)) {
				alert("<?php echo _("Password is not strong enough. Check the password policy configuration for more details")?>");
				return 0;
			}
			else if (document.fnewuser.pass1.value != document.fnewuser.pass2.value) {
				alert("<?php echo _("Mismatches in passwords")?>");
				return 0;
			} else return 1;
		}

		function checklogin() {
			var str = document.getElementById('1').value;
			if (str.match(/ /)) {
				document.getElementById('msg_login').style.display = "inline";
				return 0;
			} else {
				document.getElementById('msg_login').style.display = "none";
				return 1;
			}
		}

		function checkemail() {
			var str = document.getElementById('3').value;
			if (str == "" || str.match(/.+\@.+\..+/)) {
				document.getElementById('msg_email').style.display = "none";
				return 1;
			} else {
				document.getElementById('msg_email').style.display = "inline";
				return 0;
			}
		}
	
		function formsubmit() {
			if (checkpasslength() && checkpass() && checklogin() && checkemail()) 
			{
				selectall('nets');
				document.fnewuser.submit();
			}
		}
		
		function load_tree(filter, entity) {
			combo = 'nets';

			$("#nets_tree").remove();
			$('#td_nets').append('<div id="nets_tree" style="width:100%"></div>');

			$("#nets_tree").dynatree({
				initAjax: { url: "../net/draw_nets.php", data: {filter: filter, entity: entity} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
						if (!dtnode.hasChildren()) {
							// add from a final node
							addto(combo,dtnode.data.title,dtnode.data.key)
						} else {
							// simulate expand and load
							addnodes = true;
							dtnode.toggleExpand();
						}
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "../net/draw_nets.php",
						data: {key: dtnode.data.key, filter:filter, entity: entity}
					});
				}
			});
		}
		
		$(document).ready(function(){
			
			GB_TYPE = 'w';
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,340,"70%");
				return false;
			});
			$('#pass1').pstrength();
			
			load_tree('','');
		});
		
	</script>
</head>
<body>

<?php
include ("../hmenu.php"); 

require_once ("classes/Security.inc");
$user     = GET('user');
$networks = GET('networks');
$sensors  = GET('sensors');
$perms    = GET('perms');
//$copy_panels = GET('copy_panels');
ossim_valid($user, OSS_USER, OSS_NULLABLE, 'illegal:' . _("User name"));
ossim_valid($user, OSS_USER, 'illegal:' . _("User name"));
ossim_valid($networks, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Nets"));
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Sensors"));
ossim_valid($perms, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Permissions"));

if (ossim_error()) {
    die(ossim_error());
}

function check_perms($user, $mainmenu, $submenu) {
    $gacl = $GLOBALS['ACL'];
    return $gacl->acl_check($mainmenu, $submenu, ACL_DEFAULT_USER_SECTION, $user);
}

require_once ('classes/Session.inc');
require_once ('classes/Net.inc');
require_once ('classes/Sensor.inc');
require_once ('ossim_db.inc');

$db   = new ossim_db();
$conn = $db->connect();

if ($user_list = Session::get_list($conn, "WHERE login = '$user'")) {
    $user = $user_list[0];
}


$net_list = Net::get_all($conn);
$sensor_list = Sensor::get_all($conn, "ORDER BY name ASC");
?>

<form name="fnewuser" id="fnewuser" method="post" action="duplicateuser.php">

<table align="center">
	<input type="hidden" name="insert" value="insert" />
	<tr>
		<th> <?php echo _("User login") . required();?></th>
		<td class="left">
			<input type="text" id="1" name="user" onkeyup="checklogin()" value="" size="30" />
			<div id="msg_login" style="display:none;border:2px solid red;padding-left:3px;padding-right:3px"><?php echo _("No spaces") ?></div>
		</td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("User name"). required(); ?> </th>
        <td class="left"><input type="text" id="2" name="name" value="<?php echo $user->get_name(); ?>" size="30" /></td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("User email"); ?> <img src="../pixmaps/email_icon.gif"/></th>
		<td class="left">
			<input type="text" id="3" name="email" onblur="checkemail()" value="<?php echo $user->get_email() ?>" size="30" />
			<div id="msg_email" style="display:none;border:2px solid red;padding-left:3px;padding-right:3px"><?php echo _("Incorrect email") ?></div>
		</td>
	</tr>
	
	
	
	<tr>
		<th> <?php echo gettext("User language"); ?></th>
		<td class="left">
			<?php
			$lform = "<select name=\"language\">";
			foreach($languages['type'] as $option_value => $option_text) 
			{
				$lform.= "<option ";
				if ($user->get_language() == $option_value) $lform.= " selected='selected' ";
				$lform.= "value=\"$option_value\">$option_text</option>";
			}
			$lform.= "</select>";
			echo $lform;
			?>
		</td>
	</tr>
	
	<tr>
		<th><?php echo _("Timezone:")?></th>
		<?php 
			$tzlist = timezone_identifiers_list();
			sort($tzlist); 
			$utz = $user->get_timezone();
			if ($utz=="0" || $utz=="") $utz="UTC";
		?>
		<td class="nobborder">
			<select name="tzone" id="tzone">
			<?  foreach($tzlist as $tz) if ($tz!="localtime")
					echo "<option value='$tz'".(($utz==$tz) ? " selected='selected'": "").">$tz</option>\n";
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Company"); ?> </th>
		<td class="left"><input type="text" name="company" value="<?php echo $user->get_company(); ?>" size="30"/></td>
	</tr>
	
	<tr>
		<th> <?php echo gettext("Department"); ?> </th>
		<td class="left"><input type="text" name="department" value="<?php echo $user->get_department(); ?>" size="30"/></td>
	</tr>
	
	<tr>
		<th><?php echo _("Ask to change password at first login") ?></th>
		<td align="center">
			<input type="radio" name="first_login" value="1"> <?php echo _("Yes"); ?>
			<input type="radio" name="first_login" value="0" checked> <?php echo _("No"); ?> 
		</td>
	</tr>
	
	<tr>
		<td> <?php echo gettext("Enter new password"). required(); ?> </td>
		<td class="left"><input type="password" name="pass1" id="pass1" size="30"/></td>
	</tr>
	
	<tr>
		<td> <?php echo gettext("Retype new password"). required(); ?> </td>
		<td class="left"><input type="password" name="pass2" id="pass2" size="30"/></td>
	</tr>
	
	<tr>
		<td colspan='2' class='nobborder center' style='padding: 5px 0px'>
			<input type="button" onclick="formsubmit()" class="lbutton" value="<?php echo _("Ok"); ?>"/>
			<input type="reset" class="lbutton" value="<?php echo gettext("Reset");?>"/>
		</td>
	</tr>
</table>

<br/>
<table align="center" cellspacing='8'>
	<tr>
		<th><?php echo _("Allowed nets");?></th>
		<th><?php echo _("Allowed sensors");?></th>
		<th colspan='2'><?php echo _("Allowed Sections");?></th>
    </tr>
	
	<tr>
		<td class="nobborder" valign="top" style="padding-top:8px">
			<table>
				 <tr>
					<td class="left nobborder">
						<select style="width:100%;height:90%" multiple="multiple" size="19" name="nets[]" id="nets">
						<?php
						/* ===== Networks ==== */
						foreach($net_list as $net) {
							$net_name = $net->get_name();
							$net_ips  = $net->get_ips();
							$cidrs = explode(",",$net_ips);
							foreach($cidrs as $cidr)
								if (false !== strpos(Session::allowedNets($user->get_login()) , $net_ips)) {
										echo "<option value='$net_name'>$net_name ($net_ips)</option>";
								}
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="nobborder" style="text-align:right">
					<input type="button" value=" [X] " onclick="deletefrom('nets')" class="lbutton"/>
					<input type="button" style="margin-right:0px;" value="<?php echo gettext("Clean All Nets");?>" onclick="deleteall('nets')" class="lbutton"/>
					</td>
				</tr>
				<tr>
					<td class="left nobborder">
						<i><?php echo gettext("NOTE: No selection allows ALL") . " " . gettext("nets"); ?></i>
					</td>
				</tr>
				<tr>
                    <td class="left nobborder" style="padding-top:10px;">
                        <div>
                            <div style="float:left">
                                <?php echo _("Filter")?>: <input type="text" id="filtern" name="filtern" style="height: 18px;width: 170px;" />
                            </div>
                            <div style="float:right">
                                <input type="button" style="margin-right:0px;" class="lbutton" value="<?php echo _("Apply")?>" onclick="load_tree(document.fnewuser.filtern.value,'<?php echo $current_entity ?>')" /> 
                            </div>
                        </div>
                    </td>
                </tr>
				<tr>
					<td class="nobborder" id="td_nets">
					</td>
				</tr>
			</table>
		</td>
	
		<td class="left" valign="top" style="padding-top:8px; border:none;">
			<a href="#" onclick="checkall('sensor');return false;"><?php echo gettext("Select / Unselect all"); ?></a>
			<hr noshade='noshade'>
			<?php
			$i = 0;
			foreach($sensor_list as $sensor) 
			{
				$sensor_name = $sensor->get_name();
				$sensor_ip = $sensor->get_ip();
				$input = "<input type=\"checkbox\" class='sensor' name=\"sensor$i\" value=\"" . $sensor_ip . "\"";
				if (false !== strpos(Session::allowedSensors($user->get_login()) , $sensor_ip)) 
					 $input.= " checked='checked' ";
							
				if ($sensors || ($user->get_login() == 'admin')) 
					$input.= " checked='checked' ";
							
				if ($user->get_login() == 'admin') 
					$input.= "disabled='disabled'";
							
				$input.= "/>$sensor_name<br/>";
				echo $input;
				$i++;
			}
			?>
			
			<input type="hidden" name="nsensors" value="<?php echo $i ?>" />
			<br/><br/><i><?php echo gettext("NOTE: No selection allows ALL") . " " . gettext("sensors"); ?></i>
		</td>
    
		<td class='nbborder center'>
			<table class="noborder">
				<tr>
					<td class="nobborder">
						<a href="#" onclick="checkall('perms');return false;"><?php echo gettext("Select / Unselect all"); ?></a>
					</td>
					
					<td class="nobborder" style="color:#777777;text-align:center" nowrap>
						<span style="color:black"><b><?php echo _("Granularity")?></b> <?php echo _("Net / Sensor")?> </span>
						<!--<br><img src="../pixmaps/tick.png"> <i>Checked is filtered</i>-->
					</td>
				</tr>
				
				<tr>
					<td colspan='2' class="nobborder"><hr noshade='noshade'></td>
				</tr>
				
				<input type="hidden" name="knowledgedb_perms" value=""/>
				
				<?php
				include ("granularity.php");
				include ("perms_sections.php");
				foreach($ACL_MAIN_MENU as $mainmenu => $menus) 
				{
					foreach($menus as $key => $menu) 
					{
						?>
						<tr>
							<td class="nobborder">
								<?php 
								if ($perms_sections[$key] != "") 
								{ 
									?>
									<a href="<?php echo $perms_sections[$key]?>?user=<?php echo $user->get_login()?>" title="<?php echo _("Permissions Submenu")?>" class="greybox"><img src="../pixmaps/plus.png" border='0'/></a>
									<?php 
								} 
								else 
									echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; ?>
								
								<?php
										$checked = 0;
										$checked_text = null;
										
										if ($user->get_login() == 'admin') 
											$checked_text = " disabled='disabled'";
										
										if ($perms) 
											$checked = 1;
										
										if (check_perms($user->get_login() , $mainmenu, $key)) $checked = 1;
										
										if ($checked) 
											 $checked_text = " checked='checked'";
								?>
								
								<input class="perms" type="checkbox" name="<?php echo $key ?>" <?php echo $checked_text?>/>
							
							<?php
								$sensor_tick = ($granularity[$mainmenu][$key]['sensor']) ? "<img src='../pixmaps/tick.png'/>" : "<img src='../pixmaps/tick_gray.png'/>";
								$net_tick    = ($granularity[$mainmenu][$key]['net']) ? "<img src='../pixmaps/tick.png'/>" : "<img src='../pixmaps/tick_gray.png'/>";
								echo $menu["name"] . "</td><td class='nobborder' style='text-align:center'>".$net_tick." ".$sensor_tick."</td></tr><tr>\n";
					}
					
					echo "<tr><td colspan='2' class='nobborder'><hr noshade></td></tr>";
				}
				?>
			</table>
		</td>
	</tr>
</table>

<br/>


<table align="center" class='transparent'>
	<tr>
		<td class="center nobborder" style="padding-top:10px;">
			<input type="button" onclick="formsubmit()" class="button" value="<?php echo _("Ok"); ?>"/>
			<input type="reset" class="button" value="<?php echo _("Reset"); ?>"/>
		</td>
	</tr>
</table>

</form>

</body>
</html>

