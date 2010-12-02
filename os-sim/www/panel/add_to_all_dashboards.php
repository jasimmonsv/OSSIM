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
require_once 'classes/Host.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Incident.inc';
require_once ('classes/Security.inc');
require_once ('../vulnmeter/config.php');
require_once ('ossim_conf.inc');
require_once ('../vulnmeter/functions.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host_vulnerability.inc');
require_once ('classes/Net_vulnerability.inc');
require_once ('classes/Net.inc');
require_once ('classes/Util.inc');

$users = Session::get_list($dbconn);

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

if(POST('action')=='save'){
	// save
	require_once 'panel/Ajax_Panel.php';

	$url = POST('url');
	if(empty($url)){
		die(_('Error no url for add'));
	}

	$name=POST('name');
	if(empty($name)){
		die(_('Error no name for add'));
	}
	
	$users=POST('users');
	if(empty($users)){
		die(_('Error no users for add'));
	}
	//
	require_once 'classes/User_config.inc';
	$login = Session::get_session_user();
	$db = new ossim_db();
	$conn = $db->connect();
	$config = new User_config($conn);
	// clean smenu && hmenu
	$url=base64_decode($url);
	// check exist ?
	if(strpos($url,'?')===false){
		$url.='?';
	}
	//
	$url=str_replace('hmenu', 'older-hmenu', $url);
	$url=str_replace('smenu', 'older-smenu', $url);
	$url.='&hmenu=dashboards&smenu=dashboards';
	foreach($users as $user){
		//
		//$panel_urls = Window_Panel_Ajax::getPanelUrls();
		$panel_urls = Window_Panel_Ajax::getPanelTabs($user);
		if(empty($panel_urls)){
			$panel_urls = array();
			$key_ini=1;
		}
		// check exist
		$flag=true;
		
		//
		foreach($panel_urls as $key => $value){
			$key_ini=$key;
			if(!empty($value['tab_url'])){
				if($value['tab_url']==$url){
					$flag=false;
					break;
				}
			}
		}
		//
		if($flag){
			$panel_urls[++$key_ini]=array(
					'tab_name'=>$name,
					'tab_icon_url'=>'',
					'disable'=>0,
					'tab_url'=>$url
					);
		}
		//

		//Window_Panel_Ajax::setPanelUrls($panel_urls);
		Window_Panel_Ajax::setPanelTabs($panel_urls,$user);
		
		// clean var
		unset($panel_urls);
		unset($flag);
		unset($key_ini);
	}
?>
<script type="text/javascript">
	parent.location.href='../panel/panel.php';
</script>
<?php
	return;
}else{
	$url = GET('url');
	if(empty($url)){
		die(_('Error no url for add'));
	}

	$name=GET('name');
	if(empty($name)){
		die(_('Error no name for add'));
	}
	//
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> OSSIM Framework</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
  <style type="text/css">
	body{
		text-align: center;
	}
	table{
		background: none;
		border: none;
		padding: 20px 0 0 0;
	}
	table tr th{
		padding: 0 0 10px 0 !important;
	}
  </style>
</head>
<body>
<form action="add_to_all_dashboards.php" method="post">
<?php
if(preg_match("/pro|demo/i",$version)) {
	$users_pro_login = array();
	$users_pro = array();
	$entities_pro = array();

	if(Session::am_i_admin()) { // admin in professional version
		list($entities_all,$num_entities) = Acl::get_entities($dbconn);
		// filter for entities
		$login=Session::get_session_user();
		$filter_entities=Acl::get_brothers($dbconn,$login);

		if($login!='admin'){
			foreach($users as $value){
				if($value->get_login()==$login){
					array_unshift($filter_entities,array(
						'login'=>$value->get_login(),
						'name'=>$value->get_name()
					));
					break;
				}
			}
		}else{
			// for user admin
			foreach($users as $value){
				$filter_entities[]=array(
					'login'=>$value->get_login(),
					'name'=>$value->get_name()
				);
			}
		}
		// end filter for entities
		$entities_types_aux = Acl::get_entities_types($dbconn);
		$entities_types = array();
	
		foreach ($entities_types_aux as $etype) { 
			$entities_types[$etype['id']] = $etype;
		}
		
?>
			<table cellspacing="0" cellpadding="0" align="center">
				<tr>
					<th class="nobborder"><strong><?php echo _("Select Users:");?></strong></th>
				</tr>
				<tr>
					<td class="nobborder">
					<?php
					foreach($filter_entities as $u){ ?>
						<input type="checkbox" name="users[]" value="<?php echo $u['login']; ?>" checked /> <?php echo format_user($u,false) ?><br />
					<?php
					}
					?>
					</td>
				</tr>
			</table>
			<input type="hidden" name="action" value="save" />
			<input type="hidden" name="name" value="<?php echo $name; ?>" />
			<input type="hidden" name="url" value="<?php echo $url; ?>" />
	<?php }
	elseif(Acl::am_i_proadmin()) { // pro admin
		//users
		$users_admin = Acl::get_my_users($dbconn,Session::get_session_user()); 
		foreach ($users_admin as $u){
		//	if($u["login"]!=Session::get_session_user()){
				$users_pro_login[] = $u["login"];
		//	}
		}
		//if(!in_array(Session::get_session_user(), $users_pro_login) && $incident_in_charge!=Session::get_session_user())   $users_pro_login[] = Session::get_session_user();
		
		//entities
		list($entities_all,$num_entities) = Acl::get_entities($dbconn);
		list($entities_admin,$num) = Acl::get_entities_admin($dbconn,Session::get_session_user());
		$entities_list = array_keys($entities_admin);
		
		$entities_types_aux = Acl::get_entities_types($dbconn);
		$entities_types = array();

		foreach ($entities_types_aux as $etype) { 
			$entities_types[$etype['id']] = $etype;
		}
		
		//save entities for proadmin
		foreach ( $entities_all as $entity ) if(in_array($entity["id"], $entities_list)) {
			$entities_pro[$entity["id"]] = $entity["name"]." [".$entities_types[$entity["type"]]["name"]."]";
		}
		
		// filter users
		$users_pro=array();
		foreach($users as $u) {
			if (!in_array($u->get_login(),$users_pro_login)){
				continue;
			}
			$users_pro[]=array(
				'login'=>$u->get_login(),
				'name'=>$u->get_name()
			);
		}
		?>
		<table cellspacing="0" cellpadding="0" align="center">
				<tr>
					<th class="nobborder"><strong><?php echo _("Select Users:");?></strong></th>
				</tr>
				<tr>
					<td class="nobborder">
					<?php
					foreach($users_pro as $u){ ?>
						<input type="checkbox" name="users[]" value="<?php echo $u['login']; ?>" checked /> <?php echo format_user($u,false) ?><br />
					<?php
					}
					?>
					</td>
				</tr>
			</table>
			<input type="hidden" name="action" value="save" />
			<input type="hidden" name="name" value="<?php echo $name; ?>" />
			<input type="hidden" name="url" value="<?php echo $url; ?>" />
	<?php
	}else { // normal user
		// no add to all ...
	}
	}else {
		if(Session::am_i_admin()) {
	?>
		<table cellspacing="0" cellpadding="0" align="center">
			<tr>
				<th class="nobborder"><strong><?php echo _("Select Users:");?></strong></th>
			</tr>
			<tr>
				<td class="nobborder">
				<?php
				foreach($users as $u){ ?>
					<input type="checkbox" name="users[]" value="<?php echo $u->get_login() ?>" checked /> <?php echo format_user($u, false) ?><br />
				<?php
				}
				?>
				</td>
			</tr>
		</table>
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="name" value="<?php echo $name; ?>" />
		<input type="hidden" name="url" value="<?php echo $url; ?>" />
	<?php 
		}else { // normal user
			// no add to all ...
		}
	}
	?>
	<div style="margin: 10px 0 0 0">
		<input type="submit" class="button" value="<?php echo _('Save'); ?>">
	</div>
</form>
</body>
<?php	
	function format_user($user, $html = true, $show_email = false) {
    if (is_a($user, 'Session')) {
        $login = $user->get_login();
        $name = $user->get_name();
        $depto = $user->get_department();
        $company = $user->get_company();
        $mail = $user->get_email();
    } elseif (is_array($user)) {
        $login = $user['login'];
        $name = $user['name'];
        $depto = $user['department'];
        $company = $user['company'];
        $mail = $user['email'];
    } else {
        return '';
    }
    $ret = $name;
	if ($login) $ret = "<label title=\"Login: $login\">$ret</label>";
    //if ($depto && $company) $ret.= " / $depto / $company";   

    return $html ? $ret : strip_tags($ret);
}
?>