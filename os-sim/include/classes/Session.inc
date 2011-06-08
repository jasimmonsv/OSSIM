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
* - Session()
* - get_login()
* - get_pass()
* - get_name()
* - get_enabled()
* - get_email()
* - get_language()
* - get_allowed_nets()
* - get_company()
* - get_department()
* - get_list()
* - get_me()
* - login()
* - login_mysql()
* - login_ldap()
* - logout()
* - menu_perms()
* - useractive()
* - logcheck()
* - logcheck_ext()
* - unallowed_section()
* - getValue()
* - allowedNets()
* - allowedSensors()
* - hostAllowed()
* - groupAllowed()
* - groupHostAllowed()
* - netAllowed()
* - get_session_user()
* - get_secure_id()
* - am_i_admin()
* - is_admin()
* - is_expert()
* - get_session_info()
* - insert()
* - update()
* - changepass()
* - changelang()
* - delete()
* - change_enabled()
* Classes list:
* - Session
*/
require_once 'classes/Host.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Net.inc';
require_once 'classes/Log_action.inc';
require_once 'ossim_db.inc';
require_once 'ossim_acl.inc';
/* global configuration */
require_once 'ossim_conf.inc';
session_start();

if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE)) && !$force_gacl) {
	include "Acl.inc";
}
else {
	//session_start();
	// Locale needs $_SESSION
	require_once 'classes/Locale.inc';
	$version_file = "/etc/ossim/.ossim_installer_version";

	class Session {
		var $login;
		var $name;
		var $pass;
		var $nets;
		var $company;
		var $department;
		var $language;
		var $enabled;
		var $allowed_sensors;
		var $is_admin;
		var $timezone;
		var $last_pass_change;
		function Session($login, $pass, $allowed_nets, $name = "", $email = "", $company = "", $department = "", $language = "", $enabled = 1, $is_admin = 0, $last_pass_change = "",$timezone = "") {
			$this->login = strtolower($login);
			$this->name = $name;
			$this->email = $email;
			$this->allowed_nets = $allowed_nets;
			$this->pass = $pass;
			$this->company = $company;
			$this->department = $department;
			$this->language = $language;
			$this->enabled = $enabled;
			$this->allowed_sensors = explode(",",$this->allowedSensors($this->login));
			$this->is_admin = $is_admin;
			$this->last_pass_change = $last_pass_change;
			$this->timezone = $timezone;
		}
		function get_login() {
			global $login;
			return $this->login;
		}
		function get_pass() {
			return $this->pass;
		}
		function get_name() {
			return $this->name;
		}
		function get_email() {
			return $this->email;
		}
		function get_enabled() {
			return $this->enabled;
		}
		function get_language() {
			return $this->language;
		}
		function get_allowed_nets() {
			return $this->allowed_nets;
		}
		function get_company() {
			return $this->company;
		}
		function get_department() {
			return $this->department;
		}
		function get_is_admin() {
			return $this->is_admin;
		}
	    function get_timezone($tz="") {
	    	if ($tz=='') return $this->timezone;
	    	if (is_numeric($tz)) return $tz; // old numeric stored format
	    	$this_tz = new DateTimeZone($tz);
			$offset = $this_tz->getOffset(new DateTime("now", $this_tz));
			if ($offset!=0) $offset /= 3600;
			return $offset;
	    }
		function get_last_pass_change() {
			return $this->last_pass_change;
		}
		
		function get_list($conn, $args = "") {
			$list = array();
			$query = OssimQuery("SELECT * FROM users $args");
			if (!$rs = & $conn->Execute($query)) {
				print $conn->ErrorMsg();
			} else {
				$list = array();
				while (!$rs->EOF) {
					$list[] = new Session($rs->fields["login"], $rs->fields["pass"], $rs->fields["allowed_nets"], $rs->fields["name"], $rs->fields["email"], $rs->fields["company"], $rs->fields["department"], $rs->fields["language"], $rs->fields["enabled"], $rs->fields["is_admin"], $rs->fields["last_pass_change"], $rs->fields["timezone"]);
					$rs->MoveNext();
				}
			}
			return $list;
		}
		
		// Check 'enabled', 'first_login', 'exists_last', 'last_logon_try', 'uuid' fields exists
		function check_enabled_field($conn) {
			$exists = false;
			$exists_flogin = false;
			$exists_last = false;
			$exists_last_logon = false;
			$exists_uuid = false;
			$query = OssimQuery("show columns from users");
			if (!$rs = & $conn->Execute($query)) {
				print $conn->ErrorMsg();
			} else {
				while (!$rs->EOF) {
					if ($rs->fields["Field"]=="enabled") $exists = true;
					if ($rs->fields["Field"]=="first_login") $exists_flogin = true;
					if ($rs->fields["Field"]=="last_pass_change") $exists_last = true;
					if ($rs->fields["Field"]=="last_logon_try") $exists_last_logon = true;
					if ($rs->fields["Field"]=="uuid") $exists_uuid = true;
					$rs->MoveNext();
				}
				if (!$exists) $conn->Execute("ALTER TABLE `users` ADD `enabled` BOOL NOT NULL DEFAULT '1'");
				if (!$exists_flogin) {
					$conn->Execute("ALTER TABLE `users` ADD `first_login` BOOL NOT NULL DEFAULT '1' AFTER `enabled`");
					$conn->Execute("UPDATE users SET first_login=0");
				}
				if (!$exists_last) {
					$conn->Execute("ALTER TABLE `users` ADD `last_pass_change` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `first_login`");
					$conn->Execute("UPDATE users SET last_pass_change=CURRENT_TIMESTAMP()");
				}
				if (!$exists_last_logon) {
					$conn->Execute("ALTER TABLE `users` ADD `last_logon_try` DATETIME NOT NULL AFTER `last_pass_change`");
					$conn->Execute("UPDATE users SET last_logon_try = NOW()");
				}	
				if (!$exists_uuid) {
					$conn->Execute("ALTER TABLE `users` ADD `uuid` VARCHAR(255) NOT NULL DEFAULT ''");				
				}
				$conn->Execute("UPDATE users SET uuid=SHA1(CONCAT(login,'#',pass)) WHERE uuid=''");
			}
			return $exists;
		}
		
		/*
		* Gets a Session object from the user currently logged
		*/
		function get_me($conn) {
			$user = Session::get_session_user();
			list($me) = Session::get_list($conn, "WHERE login='$user'");
			return $me;
		}
		
		function login() {
			$conf = $GLOBALS['CONF'];
			$ossim_link = $conf->get_conf("ossim_link", FALSE);
			$db = new ossim_db();
			$conn = $db->connect();
			$authenticated = false;
			$password = $this->pass;
			$login = $this->login;
			Session::update_logon_try($conn,$login);
			/*
			// 2007/04/05 DK: Currently it doesn't make sense not to use local users.
			// If a user doesn't exist locally he won't have access to anything.
			
			if($conf->get_conf("login_enforce_existing_user") == "yes"){
			$sql = "SELECT * FROM users WHERE login = ?";
			$params = array($login);
			
			if (($rs = &$conn->Execute($sql, $params)) && ($rs->EOF) ) {
			// return false if enforcing and no results match the given user
			$db->close($conn);
			return false;
			}
			}
			*/
			$mysql_login = Session::login_mysql($login, $password);
			if ($mysql_login == true) {
				session_regenerate_id(); // generate a new session identifier
				$_SESSION["_user"] = $login;
				Session_activity::insert($conn);
				$db->close($conn);
				return true;
			}
			elseif ($conf->get_conf("login_enable_ldap", FALSE) == "yes" && Session::login_ldap($login, $password)) {
				session_regenerate_id(); // generate a new session identifier
				$_SESSION["_user"] = $login;
				Session_activity::insert($conn);
				$db->close($conn);
				return true;
			}
			$db->close($conn);
			return false;
		}
		
		function update_logon_try($conn,$login) {
			$conn->Execute("UPDATE users SET last_logon_try=NOW() WHERE login=?",array($login));
		}
		
		/*
		Add additional login methods below these lines.
		Functions should be called "login_XXX" and there should be at least one configuration
		variable saying "login_enable_XXX" with yes/no values. This way we avoid unnecesary dependencies
		and errors.
		Return values should be "true" or "false".
		*/
		function login_mysql($login = "", $password = "") {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$pass = (preg_match('/^[A-Fa-f0-9]{32}$/',$password)) ? $password : md5($password);
			$sql1 = "SELECT * FROM users WHERE login = ?";
			$params1 = array(
				$login
			);
			if ($rs1 = & $conn->Execute($sql1, $params1) && (!$rs1->EOF)) {
				$_SESSION['_user_language'] = $rs1->fields['language'];
			   // ossim_set_lang($rs1->fields['language']);
			}
			unset($sql1, $rs1, $params1);
			$sql = "SELECT * FROM users WHERE login = ? AND pass = ? AND enabled=1";
			$params = array(
				$login,
				$pass
			);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				$db->close($conn);
				$_SESSION['_user_language'] = $rs->fields['language'];
				$_SESSION['_is_admin'] = $rs->fields['is_admin'];
				$_SESSION['_timezone'] = Session::get_timezone($rs->fields['timezone']);
				$_SESSION['_secureid'] = $rs->fields['uuid'];
				ossim_set_lang($rs->fields['language']);
				return true;
			}
			$db->close($conn);
			return false;
		}
		
		function is_disabled() {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$login = $this->login;
			$conf = $GLOBALS['CONF'];
			$lockout_duration = intval($conf->get_conf("unlock_user_interval", FALSE)) * 60;        
			$sql = "SELECT * FROM users WHERE login = ? AND enabled <= 0";
			$params = array(
				$login
			);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				if ($lockout_duration==0 || $rs->fields['enabled']==0) return true; // user must be unlocked by admin
				// auto-enable if account lockout duration expires
				if (time() - strtotime($rs->fields['last_logon_try']) >= $lockout_duration) {
					$conn->Execute("UPDATE users SET enabled=1 WHERE login=?",$params);
					$db->close($conn);
					return false;
				}		
				$db->close($conn);
				return true;
			}
			$db->close($conn);
			return false;
		}
		
		function login_exists() {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$login = $this->login;
			$sql = "SELECT * FROM users WHERE login = ?";
			$params = array(
				$login
			);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				$db->close($conn);
				return true;
			}
			$db->close($conn);
			return false;
		}
		
		function login_disable() {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$login = $this->login;
			$sql = "UPDATE users SET enabled=-1 WHERE login = ?";
			$params = array(
				$login
			);
			$conn->Execute($sql, $params);
			$db->close($conn);
			$infolog = array(
				$login
			);
			Log_action::log(93, $infolog);
		}
		
		function first_login() {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$pass = md5($this->pass);
			$login = $this->login;
			$sql = "SELECT * FROM users WHERE login = ? AND pass = ?";
			$params = array(
				$login,
				$pass
			);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				$db->close($conn);
				return $rs->fields['first_login'];
			}
			$db->close($conn);
			return 0;
		}
		
		function last_pass_change() {
			global $_SESSION;
			$db = new ossim_db();
			$conn = $db->connect();
			$pass = md5($this->pass);
			$login = $this->login;
			$sql = "SELECT * FROM users WHERE login = ? AND pass = ? and enabled = 1";
			$params = array(
				$login,
				$pass
			);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				$db->close($conn);
				return $rs->fields['last_pass_change'];
			}
			$db->close($conn);
			return date("Y-m-d H:i:s");
		}
		
		function login_ldap($login = "none", $password = "none") {
			if($login == "") $login = "none";
			if($password == "") $password = "none";
			$conf = $GLOBALS['CONF'];
			$ldap_server = $conf->get_conf("login_ldap_server", FALSE);
			$ldap_cn = $conf->get_conf("login_ldap_cn", FALSE);
			$ldap_o = $conf->get_conf("login_ldap_o", FALSE);
			$ldap_ou = $conf->get_conf("login_ldap_ou", FALSE);
			$ldaprdn = $ldap_cn . '=' . $login . ',' . $ldap_ou . ',' . $ldap_o; // ldap rdn or dn
			if (!function_exists('ldap_connect')) {
				echo ossim_error(_("LDAP authentication enabled in the configuration, but the php ldap extension is not available"));
				return false;
			}
			// connect to ldap server
			$ldapconn = @ldap_connect($ldap_server);
	 
				#-- handle LDAP v3 binds (thanks B.)
				  ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
	 
			$ret = false;
			if ($ldapconn === false) {
				echo ossim_error(_("Could not connect to ldap server") . ": $ldap_server");
			} else {
				// bind to ldap server
				$ldapbind = @ldap_bind($ldapconn, $ldaprdn, $password);
				// verify binding
				if ($ldapbind === false) {
					echo ossim_error(ldap_error($ldapconn));
				} else {
					$ret = true;
				}
				ldap_close($ldapconn);
			}
			return $ret;
		}
		
		function logout() {
			Session_activity::delete();
			Session_activity::delete_session();
					
			if (!$login_location) {
				$conf = $GLOBALS['CONF'];
				$ossim_link = $conf->get_conf("ossim_link", FALSE);
				$login_location = preg_replace("/(\/)+/","/",$ossim_link . '/session/login.php');
			}
			
			unset($_SESSION);
			header("Location: $login_location");
			exit;
		}
		
		function menu_perms($menu, $submenu) {
						
			$gacl = $GLOBALS['ACL'];
			if (!isset($_SESSION["_user"])) return true;
			if (Session::am_i_admin())      return true;
			
			if ( !is_array($menu) && !is_array($submenu) )
			{
				// Perms only for pro version => always return true
				if ($menu == 'MenuEvents'    && $submenu == 'EventsForensicsDelete')    return true;
				if ($menu == 'MenuIncidents' && $submenu == 'ControlPanelAlarmsDelete') return true;
				
				if (!$gacl->acl_check($menu, $submenu, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) 
				{
					if (!$gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) 
						return false;
				}
				
				return true;
			}
			else
			{
				$i = 0; 
				foreach ($menu as $v)
				{
					if ($menu[$i] == 'MenuEvents'    && $submenu[$i] == 'EventsForensicsDelete')    return true;
					if ($menu[$i] == 'MenuIncidents' && $submenu[$i] == 'ControlPanelAlarmsDelete') return true;
					
					if ($gacl->acl_check($menu[$i], $submenu[$i], ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) 
					{
						if ($gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) 
							return true;
					}
					
					$i++;
				}
				
				return false;
			}
		}
		
		function useractive($login_location = false) {
			$gacl = $GLOBALS['ACL'];
			if (array_key_exists('user', $_POST)) $user = $_POST["user"];
			if (array_key_exists('pass', $_POST)) $pass = $_POST["pass"];
			if (isset($user) && isset($pass)) {
				$sess = new Session($user, $pass, "");
				$sess->login();
			}
			if (!$login_location) {
				$conf = $GLOBALS['CONF'];
				$ossim_link = $conf->get_conf("ossim_link", FALSE);
				$login_location = $ossim_link . '/session/login.php';
			}
			if (!isset($_SESSION["_user"]) || $_SESSION["_user"] == "") {
				header("Location: $login_location");
				exit;
			}
		}
		
		function logcheck($menu, $submenu, $login_location = false) {
			$gacl = $GLOBALS['ACL'];
			if (array_key_exists('user', $_POST)) $user = $_POST["user"];
			if (array_key_exists('pass', $_POST)) $pass = $_POST["pass"];
			
			if ( isset($user) && isset($pass) ) 
			{
				$sess = new Session($user, $pass, "");
				$sess->login();
			}
			
			if (!$login_location) 
			{
				$conf           = $GLOBALS['CONF'];
				$ossim_link     = $conf->get_conf("ossim_link", FALSE);
				$login_location = $ossim_link . '/session/login.php';
			}
			
			if (!isset($_SESSION["_user"])) {
				header("Location: $login_location");
				exit;
			}
			
			if ( Session::menu_perms($menu, $submenu) == false )
				Session::unallowed_section();
		}
		
		function logcheck_ext($menu, $submenu, $axo_sec, $axo_val, $login_location = "../session/login.php") {
			$gacl = $GLOBALS['ACL'];
			$user = $_POST["user"];
			$pass = $_POST["pass"];
			if ($user && $pass) {
				$sess = new Session($user, $pass, "");
				$sess->login();
			}
			if (!isset($_SESSION["_user"])) {
				header("Location: $login_location");
				exit;
			}
			
			if ( Session::menu_perms($menu, $submenu) == false )
				Session::unallowed_section(false);
		}
		
		
		function unallowed_section($link = null, $action='noback')
		{
			require_once ("ossim_error.inc");
			
			if ( $link === false )
				$link = null;
			else
				$link = ( empty($link) ) ? "<a class='color: #00529B !important' href='/ossim/session/login.php?action=logout' title='"._("Logout")."'><span class='notice_link'>[ "._("Logout")." ]</span></a>" : $link;
						
			$error = new OssimNotice(null, $action);
			
			//CSS styles
			
			echo "<style type='text/css'>
					
					a {text-decoration:none !important; outline: none !important;}
					
					.notice_link, .notice_link:link, .notice_link:hover, .notice_link:active, .notice_link:visited  { color: #00529B !important; font-weight: bold; margin-left: 5px;}
															
					.button {
						border-width: 0px !important;
						#border-left-color:#C9C9C9 !important;
						#border-top-color:#C9C9C9 !important;
						#border-right-color:#B3B3B3 !important;
						#border-bottom-color:#B3B3B3 !important;
						color: #FFFFFF !important;
						height:24px !important;
						background-color: transparent !important;
						background: url(../pixmaps/theme/bg_button2.gif) 50% 50% repeat-x !important;
						padding:2px 5px !important;
						font-family: arial,verdana,helvetica,sans-serif !important;
						font-size: 12px !important;
						font-weight:bold !important;
					}
							
					input.button:hover
					{
						color:white !important;
						background: url(../pixmaps/theme/bg_button_on2.gif) 50% 50% repeat-x !important;
						padding-bottom:2px !important;
					}
					
					input {
						border: solid;
						border-style: solid;
						border-width: 1px;
						color: #222;
						border-color: #888;
						background: #fdfdfd;
						font-family: verdana,arial,helvetica,sans-serif;
						font-size:  8pt;
					}

					.button
					{
					   background: #cccccc url(../pixmaps/theme/bg_button.png) 50% 50% repeat-x; font-size: 11px; color: #222222; text-align: center;
					}
					
					input[type=\"button\"]:hover, .button:hover
					{
					   border:1px solid #02A705;
					   background: #4AC600 url(../pixmaps/theme/bg_button_on.png) 50% 50% repeat-x; color: #FFFFFF;
					}
					
					input{
						border-radius: 3px;
						-moz-border-radius: 3px;
						-webkit-border-radius: 3px;
					}
					
				</style>";
			
			echo "<div style='width: 95%; font-size: 11px; margin: auto;'>";
				$error->display(_("LOGOUT"), array($link), false);
			echo "</div>";
		}
				
		
		/*
		* Backwards compatibility with allowedNets function
		* (acid patches, old ossim-framework code, etc)
		*/
		function getValue($user = "") {
			return Session::allowedNets($user);
		}
		
		/* Wraps the acl_return_value() function */
		function allowedNets($user = "") {
			$gacl = $GLOBALS['ACL'];
			static $allowedNetsCache;
			if (!$user) $user = Session::get_session_user();
			if (isset($allowedNetsCache["$user"])) return $allowedNetsCache["$user"];
			$allowedNetsCache["$user"] = $gacl->acl_return_value(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_NETS, ACL_DEFAULT_USER_SECTION, $user);
			return $allowedNetsCache["$user"];
		}
		
		function allowedSensors($user = "") {
			$gacl = $GLOBALS['ACL'];
			static $allowedSensorsCache;
			if (!$user) $user = Session::get_session_user();
			if (isset($allowedSensorsCache["$user"])) return str_replace(",,",",",$allowedSensorsCache["$user"]);
			$allowedSensorsCache["$user"] = $gacl->acl_return_value(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_SENSORS, ACL_DEFAULT_USER_SECTION, $user);
			return str_replace(",,",",",$allowedSensorsCache["$user"]);
		}
		
		function sensorAllowed($sensor) {
			$gacl = $GLOBALS['ACL'];
			static $allowedSensorsCache;
			if (!$user) $user = Session::get_session_user();
			if ($user == ACL_DEFAULT_OSSIM_ADMIN) return 1;
			if (isset($allowedSensorsCache["$user"])) 
			$allowsensors = $allowedSensorsCache["$user"];
			else 
				$allowsensors = $gacl->acl_return_value(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_SENSORS, ACL_DEFAULT_USER_SECTION, $user);
			if ($allowsensors == "") return 1;
			$allowed_sensors = explode(",",str_replace(",,",",",$allowsensors));

			return (in_array($sensor,$allowed_sensors)) ? 1 : 0;
		}
		
		function hostAllowed($conn, $host = "ANY", $user = "") {
			static $hostAllowedCache;
			
			$host = strtoupper($host);
			
			if (!is_object($conn)) {
				echo "Missing database connection object.";
				exit();
			}
			if ($host == "ANY") return true;
			if (!$user) $user = Session::get_session_user();
			if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
			if (isset($hostAllowedCache["$user"]["$host"])) {
				return $hostAllowedCache["$user"]["$host"];
			}
			$allowed_nets = Session::allowedNets($user);
			$allowed_sensors = Session::allowedSensors($user);
			if ($allowed_nets) {
				if (!(Net::is_ip_in_cache_cidr($conn, $host, $allowed_nets))) {
					$hostAllowedCache["$user"]["$host"] = false;
					return false;
				}
			}
			if ($allowed_sensors) {
				$sensorarr_aux = array($host);
				$sensor_list = split(",", $allowed_sensors);
				if (!(array_intersect(Host::get_related_sensors($conn, $host) , $sensor_list)) && !(array_intersect($sensorarr_aux,$sensor_list))) {
					$hostAllowedCache["$user"]["$host"] = false;
					return false;
				}
			}
			$hostAllowedCache["$user"]["$host"] = true;
			return true;
		}
		
		function groupAllowed($conn, $group = "ANY", $user = "") {
			static $groupAllowedCache;
			if (!is_object($conn)) {
				echo "Missing database connection object.";
				exit();
			}
			if ($group == "ANY") return true;
			if (!$user) $user = Session::get_session_user();
			if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
			if (isset($groupAllowedCache["$user"]["$group"])) {
				return $groupAllowedCache["$user"]["$group"];
			}
			$networks = Net_group::get_networks($conn, $group);
			if (empty($networks)) return true;
			foreach($networks as $network) {
				if (!Session::netAllowed($conn, $network->net_name, $user)) {
					$groupAllowedCache["$user"]["$group"] = false;
					return false;
				}
			}
			$groupAllowedCache["$user"]["$group"] = true;
			return true;
		}
		
		function groupHostAllowed($conn, $group = "ANY", $user = "") {
			static $groupAllowedCache;
			if (!is_object($conn)) {
				echo "Missing database connection object.";
				exit();
			}
			if ($group == "ANY") return true;
			if (!$user) $user = Session::get_session_user();
			if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
			if (isset($groupAllowedCache["$user"]["$group"])) {
				return $groupAllowedCache["$user"]["$group"];
			}
			$hosts = Host_group::get_hosts($conn, $group);
			if (empty($hosts)) return true;
			foreach($hosts as $host) {
				/*
				* get info from host name $host
				* use Host::get_list(), as it perform host permission verification
				*  Host::get_list() -> Session::hostAllowed()
				* no need to call directly Session::hostAllowed()
				*/
				if (!Session::hostAllowed($conn, $host->host_ip)) {
					$groupAllowedCache["$user"]["$group"] = false;
					return false;
				}
			}
			$groupAllowedCache["$user"]["$group"] = true;
			return true;
		}
		
		function netAllowed($conn, $net = "ANY", $user = "") {
			static $netAllowedCache;
			if (!is_object($conn)) {
				echo "Missing database connection object.";
				exit();
			}
			if ($net == "ANY") return true;
			if (!$user) $user = Session::get_session_user();
			if ($user == ACL_DEFAULT_OSSIM_ADMIN) return true;
			if (preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$net)) {
				require_once('classes/Net.inc');
				$net = Net::get_name_by_ip($conn,$net);
			}
			if (isset($netAllowedCache["$user"]["$net"])) {
				return $netAllowedCache["$user"]["$net"];
			}
			$allowed_nets = Session::allowedNets($user);
			$allowed_sensors = Session::allowedSensors($user);
			if ($allowed_nets) {
				/*
				* get ips from network $net
				* don't use Net::get_list(), it's a recursive call:
				*  Net::get_list() -> Session::netAllowed() -> Net::get_list()
				*/
				$sql = "SELECT ips FROM net WHERE name = ?";
				$params = array(
					$net
				);
				if (!$rs = $conn->Execute($sql, $params)) {
					die($conn->ErrorMsg());
				}
				if (!$rs->EOF) {
					$cidr = explode(",",$rs->fields["ips"]);
					$allowed_nets_array = split(',', $allowed_nets);
			foreach ($cidr as $ips) {
				if (!in_array(trim($ips), $allowed_nets_array)) {
					$netAllowedCache["$user"]["$net"] = false;
								return false;
				}
					}
				}
			}
			if ($allowed_sensors) {
				$sensor_list = split(",", $allowed_sensors);
				if (!array_intersect(Net::get_related_sensors($conn, $net) , $sensor_list)) {
					$netAllowedCache["$user"]["$net"] = false;
					return false;
				}
			}
			$netAllowedCache["$user"]["$net"] = true;
			return true;
		}
		
		function get_session_user() {
			return @$_SESSION["_user"];
		}
		
		function get_secure_id() {
			return @$_SESSION["_secureid"];
		}
		
		/* return true if current user is admin */
		function am_i_admin() {
			return ($_SESSION["_user"] == ACL_DEFAULT_OSSIM_ADMIN || $_SESSION["_is_admin"]);
		}
		
		/* return true if the user is admin */
		function is_admin($conn, $user) {
			
			$user = Session::get_list($conn, "WHERE login='$user'");
			
			if ( is_array($user) && count($user)>= 1)
				return ( $user[0]->get_login() == ACL_DEFAULT_OSSIM_ADMIN || $user[0]->get_is_admin() );
			else
				return false;
		}
		
		function is_expert() {
			$db = new ossim_db();
			$conn = $db->connect();
			$sql = "SELECT value FROM phpgacl WHERE name = 'expert_mode'";
			if (!$rs = & $conn->Execute($sql)) {
				print $conn->ErrorMsg();
			} else {
				return $rs->fields["value"];
			}
		}
		
		function get_session_info() {
			if (isset($_SESSION["_user"])) {
				$db = new ossim_db();
				$conn = $db->connect();
				$sql = "SELECT * FROM users WHERE login = '" . $_SESSION["_user"] . "'";
				if (!$rs = & $conn->Execute($sql)) {
					print $conn->ErrorMsg();
				} else {
					return $rs->fields;
				}
				$db->close($conn);
			}
			return NULL;
		}
		
		function insert($conn, $login, $pass, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $first_login, $timezone) {
			global $ACL_MAIN_MENU;
			global $ACL_OPTIONS;
			$pass = md5($pass);
			/* bug with mrtg graphing? */
			$login = strtolower($login);
			/* insert into OSSIM database */
			$sql = "INSERT INTO users (login, name, pass, email, allowed_nets, company, department, language, first_login, timezone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$params = array(
				$login,
				$name,
				$pass,
				$email,
				$nets,
				//$sensors,
				$company,
				$department,
				$language,
				$first_login,
				$timezone
			);
			if ($conn->Execute($sql, $params) === false) {
				print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
			/* insert into GACL database */
			$gacl_api = new gacl_api($ACL_OPTIONS);
			$gacl_api->add_object(ACL_DEFAULT_USER_SECTION, /* section */
			$login, /* name  */
			$login, /* login */
			0, 0, 'ARO');
			$gacl_api->add_group_object($gacl_api->get_group_id(ACL_DEFAULT_USER_GROUP) , ACL_DEFAULT_USER_SECTION, $login, 'ARO');
			/* set perms */
			$aro_ids = array(
				ACL_DEFAULT_USER_SECTION => array(
					$login
				)
			);
			foreach($ACL_MAIN_MENU as $aco_section => $menus) {
				foreach($menus as $key => $menu) {
					$gacl_api->add_acl(array(
						$aco_section => array(
							$key
						)
					) , $aro_ids, NULL, NULL, NULL, $perms[$key], true);
				}
			}
			/* set allowed nets */
			$gacl_api->add_acl(array(
				ACL_DEFAULT_DOMAIN_SECTION => array(
					ACL_DEFAULT_DOMAIN_NETS
				)
			) , $aro_ids, NULL, NULL, NULL, true, true, $nets);
			/* set allowed sensors */
			$gacl_api->add_acl(array(
				ACL_DEFAULT_DOMAIN_SECTION => array(
					ACL_DEFAULT_DOMAIN_SENSORS
				)
			) , $aro_ids, NULL, NULL, NULL, true, true, $sensors);
			$infolog = array(
				$login
			);
			Log_action::log(4, $infolog);
		}
		
		function update($conn, $login, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $kdb_users, $first_login, $is_admin = 0, $timezone) {
			global $ACL_MAIN_MENU;
			global $ACL_OPTIONS;
			/* modify OSSIM database */
			$sql = "UPDATE users SET name = ?, allowed_nets = ?, email = ?, company = ?, department = ?, language = ?, first_login = ?, is_admin = ?, timezone = ? WHERE login = ?";
			$params = array(
				$name,
				$nets,
				$email,
				$company,
				$department,
				$language,
				$first_login,
				$is_admin,
				$timezone,
				$login
			);
			ossim_set_lang($language);
			if ($conn->Execute($sql, $params) === false) {
				print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
			/* update GACL database */
			$gacl_api = new gacl_api($ACL_OPTIONS);
			$aro_ids = array(
				ACL_DEFAULT_USER_SECTION => array(
					$login
				)
			);
			/* update perms */
			foreach($ACL_MAIN_MENU as $aco_section => $menus) {
				foreach($menus as $key => $menu) {
					/* get ACL id */
					if ($acl_ids = $gacl_api->search_acl($aco_section, $key, ACL_DEFAULT_USER_SECTION, $login, false, false, false, false, false)) {
						foreach($acl_ids as $acl_id) /* must be only one id */ {
							$gacl_api->edit_acl($acl_id, array(
								$aco_section => array(
									$key
								)
							) , $aro_ids, NULL, NULL, NULL, $perms[$key], true);
						}
					}
					/* there is no menu entry, insert it */
					else {
						$gacl_api->add_acl(array(
							$aco_section => array(
								$key
							)
						) , $aro_ids, NULL, NULL, NULL, $perms[$key], true);
					}
				}
			}
			/* update allowed nets */
			if ($acl_ids = $gacl_api->search_acl(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_NETS, ACL_DEFAULT_USER_SECTION, $login, false, false, false, false, false)) {
				foreach($acl_ids as $acl_id) /* must be only one id */ {
					$gacl_api->edit_acl($acl_id, array(
						ACL_DEFAULT_DOMAIN_SECTION => array(
							ACL_DEFAULT_DOMAIN_NETS
						)
					) , $aro_ids, NULL, NULL, NULL, true, true, $nets);
				}
			}
			/* update allowed sensors */
			if ($acl_ids = $gacl_api->search_acl(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_SENSORS, ACL_DEFAULT_USER_SECTION, $login, false, false, false, false, false)) {
				foreach($acl_ids as $acl_id) /* must be only one id */ {
					$gacl_api->edit_acl($acl_id, array(
						ACL_DEFAULT_DOMAIN_SECTION => array(
							ACL_DEFAULT_DOMAIN_SENSORS
						)
					) , $aro_ids, NULL, NULL, NULL, true, true, $sensors);
				}
			}
			
			// Update Knowledge DB access
			if ($kdb_users != "") {
				$user_perms = new User_config($conn);
				$user_perms->set($login,"user_docs",$kdb_users,'php',"knowledgedb");
			}
			
			$infolog = array(
				$login
			);
			Log_action::log(6, $infolog);
		}
		
		function update_noperms($conn, $login, $name, $email, $company, $department, $language, $first_login, $is_admin = 0, $timezone) {
			/* modify OSSIM database */
			$sql = "UPDATE users SET name = ?, email = ?, company = ?, department = ?, language = ?, first_login = ?, is_admin = ?, timezone = ? WHERE login = ?";
			$params = array(
				$name,
				$email,
				$company,
				$department,
				$language,
				$first_login,
				$is_admin,
				$timezone,
				$login
			);
			ossim_set_lang($language);
			if ($conn->Execute($sql, $params) === false) {
				print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
		}   
		
		function changepass($conn, $login, $pass, $current_pass=null) {
			$pass = md5($pass);
			
			$sql  = "UPDATE users SET pass = ?, last_pass_change=CURRENT_TIMESTAMP() WHERE login = ?";
			$sql .= ( !empty($current_pass) ) ? " AND pass='".md5($current_pass)."'" : "";
			
			$params = array(
				$pass,
				$login
			);
			
			if ($conn->Execute($sql, $params) === false) {
				print 'Error updating: ' . $conn->ErrorMsg() . '<br/>';
				exit;
			}
			$infolog = array(
				$login
			);
			Log_action::log(5, $infolog);
			
			return $conn->Affected_Rows();
		}
			
		function changefirst($conn, $login) {
			$pass = md5($pass);
			$sql = "UPDATE users SET first_login = 0 WHERE login = ?";
			$params = array(
				$login
			);
			if ($conn->Execute($sql, $params) === false) {
				print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
		}
		
		function changelang($conn, $login, $language) {
			$sql = "UPDATE users SET language = ? WHERE login = ?";
			$params = array(
				$language,
				$login
			);
			if ($conn->Execute($sql, $params) === false) {
				print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
			if ($login == $_SESSION["_user"]) {
				ossim_set_lang($language);
			}
			$infolog = array(
				$login
			);
			Log_action::log(5, $infolog);
		}
		
		function change_enabled($conn, $login) {
			$query = OssimQuery("UPDATE users SET enabled=NOT(enabled) WHERE login=?");
			$params = array ($login);
			if (!$rs = & $conn->Execute($query,$params)) {
				print $conn->ErrorMsg();
			}
		}
		
		function delete($conn, $login) {
			global $ACL_MAIN_MENU;
			global $ACL_OPTIONS;
			
			if ($login == ACL_DEFAULT_OSSIM_ADMIN) {
				print "<p>Can't remove default user</p>";
				exit;
			}
			
			$sql    = "DELETE FROM users WHERE login = ?";
			
			$params = array(
				$login
			);
			
			if ($conn->Execute($sql, $params) === false) {
				print 'Error deleting: ' . $conn->ErrorMsg() . '<BR>';
				exit;
			}
			
			$gacl_api = new gacl_api($ACL_OPTIONS);
			/* delete ACLs */
			if ($acl_ids = $gacl_api->search_acl(false, false, ACL_DEFAULT_USER_SECTION, $login, false, false, false, false, false)) {
				foreach($acl_ids as $id) {
					$gacl_api->del_acl($id);
				}
			}
			/* delete user object */
			$gacl_api->del_object($gacl_api->get_object_id(ACL_DEFAULT_USER_SECTION, $login, "ARO") , "ARO", true);
			$infolog = array(
				$login
			);
			Log_action::log(3, $infolog);
		}
		
		function get_uniqueid($user) {
			// get sha1 from unique md5 pass
			$db = new ossim_db();
			$conn = $db->connect();
			//$pass = md5($this->pass);
			$sql = "SELECT pass FROM users WHERE login = ?";
			$params = array( $user );
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				$db->close($conn);
				return sha1($rs->fields['pass']);
			}
			$db->close($conn);
			return sha1(md5($user));
		}
		
		function pass_check_complexity($pass) {
			require_once "ossim_conf.inc";
			$conf = $GLOBALS["CONF"];
			if ($conf->get_conf("pass_complex", FALSE) == "yes") {
				$counter = 0;
				if (preg_match("/[a-z]/",$pass)) { $counter++; }
				if (preg_match("/[A-Z]/",$pass)) { $counter++; }
				if (preg_match("/[0-9]/",$pass)) { $counter++; }
				if (preg_match("/[\!\"\·\$\%\&\/\(\)\|\#\~\€\¬\.\,\?\=\-\_\<\>]/",$pass)) { $counter++; }
				return ($counter < 3) ? 0 : 1;
			} else {
				return 1;
			}
		}
		
		function log_pass_history ($user,$pass) {
			$db = new ossim_db();
			$conn = $db->connect();
			
			$hist_number = 1;
			$query = "SELECT max(hist_number) as num FROM pass_history WHERE user=?";
			$params = array($user);
			if (!$rs = & $conn->Execute($query,$params)) {
				print $conn->ErrorMsg();
				return "";
			} else {
				if (!$rs->EOF) {
					$hist_number = $rs->fields['num'] + 1;
				}
			}
			
			$sql = "INSERT INTO pass_history (user,pass,hist_number) VALUES (?,?,?)";
			$params = array($user, $pass, $hist_number);
			if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
				print $conn->ErrorMsg();
				$db->close($conn);
				exit;
			}
			$db->close($conn);
		}
		
		//Return array with users that you can see
		function get_users_to_assign($conn, $user=null){
			
			$user = ( empty($user) ) ?  Session::get_session_user() : $user;
			
			if  ( Session::is_admin($conn, $user) )
			{
				$users_list = Session::get_list($conn, "ORDER BY login");
							
				if ( is_array($users_list) && !empty($users_list) )
				{
					foreach($users_list as $k => $v)
						$users[$v->get_login()] = $v->get_login();
					
					$where = "WHERE login in ('".implode("','",$users)."')";
				}
			}
			else
			{
				$where = "WHERE login in ('".$user."')";
			}	
			
			return Session::get_list($conn, $where." ORDER BY login ASC");
		}
		
		//Return array with entities that you can see
		function get_entities_to_assign($conn, $user=null){
			return array();
		}
	}
} // else include Acl




/**
* Class and Function List:
* Function list:
* - get_id()
* - get_login()
* - get_ip()
* - get_hostname()
* - get_agent()
* - get_login_date()
* - get_activity()
* - set_id()
* - set_login()
* - set_ip()
* - set_hostname()
* - set_agent()
* - set_login_date()
* - set_activity()
* - get_list()
* Classes list:
* - Session_activity
*/

class Session_activity {

/*Attributes*/

private $id;
private $login;
private $ip;
private $agent;
private $logon_date;
private $activity;
private static $style = 'font-family:Arial, Helvetica, sans-serif; 
								font-size:13px; border: 1px solid; 
								width: 90%; 
								margin: 10px auto; 
								padding:15px 10px 15px 50px;  
								background-repeat: no-repeat; 
								background-position: 10px center;
								color: #D8000C; 
								background-color: #FFBABA; 
								background-image: url("../pixmaps/ossim_error.png");';

/*Methods*/
	 
	public function __construct($id, $login, $ip, $agent, $logon_date, $activity){
		$this->id            = $id;
		$this->login         = $login;
		$this->ip            = $ip;
		$this->agent         = $agent;
		$this->logon_date    = $logon_date;
		$this->activity      = $activity;
	}
	
	
	public function get_id()    { return $this->id;}
	public function set_id($id) { $this->id = $id;}
	
	public function get_login()       { return $this->login;}
	public function set_login($login) { $this->login = $login;}
	
	public function get_ip()    { return $this->ip;}
	public function set_ip($ip) { $this->ip = $ip;}
	
	public function get_agent()       { return $this->agent;}
	public function set_agent($agent) { $this->agent = $agent;}
	
	
	
	public function get_logon_date()            { return $this->logon_date;}
	public function set_logon_date($logon_date) { $this->logon_date = $logon_date;}
	
	public function get_activity()          { return $this->activity;}
	public function set_activity($activity) { $this->activity= $activity;}	
	
	
	public function getRealIpAddr()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		else
		  $ip=$_SERVER['REMOTE_ADDR'];
		
		return $ip;
	}
	
	public function getExtIpAddr()
	{
		$ip = "";
		$output = file("http://cualesmiip.com");
		foreach ($output as $line) {
			if (preg_match("/Tu IP real es (\d+\.\d+\.\d+\.\d+)/",$line,$found)) {
				$ip = $found[1];
			}
		}
		return $ip;
	}
	
	private function get_ua()
	{
		require_once('classes/Browser.inc');
		
		$ua     = new Ua();
		$agent = null;
		
		$agent .= ( !empty($ua->name) )   ? ucfirst($ua->name) : "";
		$agent .= ( !empty($ua->ver) ) ? " ".ucfirst($ua->ver) : "";
		
		if (!empty ($ua->platform) || !empty ($ua->platform))
		{
			$agent .=  " / ";
			
			$agent .= ( !empty ($ua->platform) ) ? ucfirst($ua->platform)." " : "";
			$agent .= ( !empty ($ua->os) )       ? ucfirst($ua->os)       : "";
		}
		
		$agent  = ( empty($agent) ) ? _("Unknown") : $agent;
		$agent .= "###".$ua->str;
		return $agent;
		
	}

	public function get_list($dbconn, $args = "")
	{
        $list  = array();
		$query = OssimQuery("SELECT * FROM sessions $args");
        if (!$rs = & $dbconn->Execute($query))
            print $dbconn->ErrorMsg();
        else 
		{
            $list = array();
            while (!$rs->EOF) 
			{
                $list[] = new Session_activity($rs->fields["id"], $rs->fields["login"], $rs->fields["ip"], $rs->fields["agent"], $rs->fields["logon_date"], $rs->fields["activity"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
	
	public function expire_my_others_sessions($dbconn, $login)
	{
		$other_sessions = self::get_list($dbconn, "WHERE login='$login'");
		
		$my_session = session_id();
		session_commit();
		
		foreach ($other_sessions as $k => $v)
		{
			$id = $v->get_id();
			if ($my_session != $id)
			{
				self::delete($id);
				
				session_id($id);
				session_start();
			
				$_SESSION = array();
				session_destroy();
				session_commit();
			}
		}
		
		session_id($my_session);
		session_start();
		
		return true;
		
	}
			
	public function insert($dbconn)
	{
		
		$exceptions [] = "av report scheduler";
		$pattern_exp   = "/".implode("|", $exceptions)."/";
				
		$id            = session_id();
		$login         = Session::get_session_user();
		$ip            = self::getRealIpAddr();
		$agent         = self::get_ua();
		
		
		if ( preg_match($pattern_exp, $agent) == true )
			return true;
						
		$logon_date  = $activity = gmdate("Y-m-d H:i:s");
				
		$conf = $GLOBALS["CONF"];
		
		if (!$conf) {
			require_once 'ossim_db.inc';
			require_once 'ossim_conf.inc';
			$conf = new ossim_conf();
		}
		
		$expired_timeout = intval($conf->get_conf("session_timeout", FALSE)) * 60;
		
		if ( $expired_timeout != 0 )
		{
			$time = gmdate("U") - $expired_timeout;
			$date = gmdate("Y-m-d H:i:s", $time);
			$sql = "DELETE FROM sessions WHERE activity < '$date'";
        
			$params = array(
				$activity
			);
			
			if ($dbconn->Execute($sql, $params) === false) {
				echo "<div style='".Session_activity::$style."'>"._('Error deleting:').' '.$dbconn->ErrorMsg().'</div>';
				exit;
			}
		};
								
			
		$sql = "INSERT INTO sessions (id, login, ip, agent, logon_date, activity) VALUES (?, ?, ?, ?, ?, ?)";
        
		$params = array(
            $id,
			$login,
			$ip,
            $agent,
			$logon_date,
			$activity,
        );
        
		if ($dbconn->Execute($sql, $params) === false) {
            echo "<div style='".Session_activity::$style."'>"._('Error inserting:').' '.$dbconn->ErrorMsg().'</div>';
            exit;
        }
		
		return true;
			
    }
		
	public function update($time='')
	{
		require_once 'ossim_db.inc';
		
		$db       = new ossim_db();
		$dbconn   = $db->connect();
		
				
		$id       = session_id();
		$login    = Session::get_session_user();
		$activity = ( empty($time) ) ? gmdate("Y-m-d H:i:s") : gmdate("Y-m-d H:i:s", $time);
		
		$sql = "UPDATE sessions SET activity=? WHERE id=? AND login=?";
        
		$params = array(
            $activity,
			$id,
			$login
        );
        
		if ($dbconn->Execute($sql, $params) === false) {
            echo "<div style='".Session_activity::$style."'>"._('Error updating:').' '.$dbconn->ErrorMsg().'</div>';
            exit;
        }
		
		$db->close($dbconn);
		
		return true;
	}
	
	public function delete($id='')
	{
		require_once 'ossim_db.inc';
		$db     = new ossim_db();
		$dbconn = $db->connect();
		
		$id     = ( empty($id) ) ? session_id() : $id;
									
		$sql = "DELETE FROM sessions WHERE id=?";
		
		$params = array(
           	$id
		);
		
		if ($dbconn->Execute($sql, $params) === false) {
            echo "<div style='".Session_activity::$style."'>"._('Error deleting:').' '.$dbconn->ErrorMsg().'</div>';
            exit;
        }
		
		$db->close($dbconn);
		
		return true;
		
	}
	
	public function delete_session($id='')
	{
		$id         = ( empty($id) ) ? session_id() : $id;
		$my_session = session_id();
		
		if ($id == $my_session) {
			session_destroy();
			return true;
		}
		else
		{
			session_commit();
			session_id($id);
			session_start();
		
			$_SESSION = array();
			session_destroy();
			session_commit();
		
			session_id($my_session);
			session_start();
			
			return true;
		}
	}
	
	
	function is_expired($activity)
	{
		$conf     = $GLOBALS["CONF"];
		
		if (!$conf) {
			require_once 'ossim_db.inc';
			require_once 'ossim_conf.inc';
			$conf = new ossim_conf();
		}
			
		$expired_timeout = intval($conf->get_conf("session_timeout", FALSE)) * 60;
		
		if ($expired_timeout == 0)
			return false;
		
		$expired_date = $activity + $expired_timeout;
		$current_date = gmdate("U");
		
		if ( $expired_date < $current_date )
			return true;
		else
			return false;
	}
	

}

//
// DBA shared
// Allow shared vars between phps with db files (without use session vars)
//
class DBA_shared {
	
	private $dbfile;
	
	function __construct($dbfile="") {
		$this->dbfile = ($dbfile!="") ? $dbfile : tempnam("/var/tmp", "report_");
		if (!preg_match("/^\/var\/tmp/",$this->dbfile)) $this->dbfile = "/var/tmp/report_".$this->dbfile.".db";
	}
	
	/*function __destruct(){
		unlink($this->dbfile);
	}*/
	
	function truncate() {
		// create empty db
		if ($f = @dba_open($this->dbfile, "n", "db4")) {
			dba_insert("create",date("Y-m-d H:i:s"),$f);
			dba_close($f);
		}
	}
		
	function get($key) {
		$value = "";
		if ($f = @dba_open($this->dbfile, "rl", "db4")) {
		
		if (dba_exists($key,$f)) 
		{
			$value = dba_fetch($key,$f);
			$aux   = @unserialize($value);
			return ( $aux == false ) ? $value : unserialize($value);
		}
		dba_close($f);
			
		}
		return $value;
	}
	
	function put($key,$value) {
		if ($f = @dba_open($this->dbfile, "wl", "db4")) {
			if (is_array($value)) $value = serialize($value);
			dba_insert($key,$value,$f);
			dba_close($f);
		}
	}

	function dbfile() {
		return $this->dbfile;
	}

}

// Expire Session check. (15 minutes) PCI Compliance 
function expire_session_check() {
	$conf = $GLOBALS["CONF"];
	if (!$conf) {
		require_once 'ossim_db.inc';
		require_once 'ossim_conf.inc';
		$conf = new ossim_conf();
	}
	$expired_timeout = intval($conf->get_conf("session_timeout", FALSE)) * 60;
								
	if ($expired_timeout==0) 
		return;
	
	$time = gmdate("U");
	
	if ( isset($_SESSION["_expiration_time"]) && intval($_SESSION["_expiration_time"])+$expired_timeout < $time)  {
		header("Location: /ossim/session/login.php?action=logout");
	}
		
	// only update if not exists bypass => header ajax responses
	$_SESSION["_expiration_time"] = $time;
	Session_activity::update($_SESSION["_expiration_time"]);
}




if ( intval(GET('bypassexpirationupdate'))!=1 && intval(POST('bypassexpirationupdate'))!=1 ) {
	expire_session_check();
	
}

?>
