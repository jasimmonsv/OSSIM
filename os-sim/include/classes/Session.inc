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
* - getValue()
* - allowedNets()
* - allowedSensors()
* - hostAllowed()
* - groupAllowed()
* - groupHostAllowed()
* - netAllowed()
* - get_session_user()
* - am_i_admin()
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

if ((preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE)) || preg_match("/demo/",$conf->get_conf("ossim_server_version", FALSE))) && !$force_gacl)
	include "Acl.inc";
else {

session_start();
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
    function Session($login, $pass, $allowed_nets, $name = "", $email = "", $company = "", $department = "", $language = "", $enabled = 1, $is_admin = 0) {
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
    function get_list($conn, $args = "") {
        $list = array();
        $query = OssimQuery("SELECT * FROM users $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Session($rs->fields["login"], $rs->fields["pass"], $rs->fields["allowed_nets"], $rs->fields["name"], $rs->fields["email"], $rs->fields["company"], $rs->fields["department"], $rs->fields["language"], $rs->fields["enabled"], $rs->fields["is_admin"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    // Check 'enabled', 'first_login', 'exists_last' fields exists
    function check_enabled_field($conn) {
        $exists = false;
		$exists_flogin = false;
		$exists_last = false;
        $query = OssimQuery("show columns from users");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                if ($rs->fields["Field"]=="enabled") $exists = true;
				if ($rs->fields["Field"]=="first_login") $exists_flogin = true;
				if ($rs->fields["Field"]=="last_pass_change") $exists_last = true;
                $rs->MoveNext();
            }
            if (!$exists) $conn->Execute("ALTER TABLE `users` ADD `enabled` BOOL NOT NULL DEFAULT '1'");
			if (!$exists_flogin) {
				$conn->Execute("ALTER TABLE `users` ADD `first_login` BOOL NOT NULL DEFAULT '1' AFTER `enabled`");
				$conn->Execute("UPDATE users SET first_login=0");
			}
			if (!$exists_last) {
				$conn->Execute("ALTER TABLE `users` ADD `last_pass_change` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
				$conn->Execute("UPDATE users SET last_pass_change=CURRENT_TIMESTAMP()");
			}
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
        $db->close($conn);
        // Always enabled
        $mysql_login = Session::login_mysql($login, $password);
		if ($mysql_login == false) return false;
		elseif ($mysql_login == true) {
			session_regenerate_id(); // generate a new session identifier
			$_SESSION["_user"] = $login;
            return true;
        }
        if ($conf->get_conf("login_enable_ldap", FALSE) == "yes" && Session::login_ldap($login, $password)) {
			session_regenerate_id(); // generate a new session identifier
			$_SESSION["_user"] = $login;
            return true;
        }
        return false;
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
        $pass = md5($password);
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
		$pass = md5($this->pass);
        $login = $this->login;
		$sql = "SELECT * FROM users WHERE login = ? AND enabled=0";
		$params = array(
            $login
        );
		if ($rs = & $conn->Execute($sql, $params) && (!$rs->EOF)) {
			$sql2 = "SELECT date as lastdate FROM log_action WHERE code=93 AND info = 'User $login disabled for security reasons' ORDER BY date DESC";
			if ($rs2 = & $conn->Execute($sql2) && (!$rs2->EOF)) {
				$lastdate = $rs2->fields['lastdate'];
				if (time() - strtotime($lastdate) >= 60*30) { // 30 minutes and enable
					$sql3 = "DELETE FROM log_action WHERE code=93 AND info = 'User $login disabled for security reasons'";
					$conn->Execute($sql3);
					$sql3 = "UPDATE users SET enabled=1 WHERE login=?";
					$conn->Execute($sql3,$params);
					$db->close($conn);
					return false;
				} else {
					$db->close($conn);
					return true;
				}
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
		$sql = "UPDATE users SET enabled=0 WHERE login = ?";
		$params = array(
            $login
        );
        $conn->Execute($sql, $params);
		$db->close($conn);
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
		$sql = "SELECT * FROM users WHERE login = ? AND pass = ?";
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
        session_destroy();
    }
    function menu_perms($menu, $submenu) {
        $gacl = $GLOBALS['ACL'];
        if (!isset($_SESSION["_user"])) return true;
        if (Session::am_i_admin()) return true;
        if (!$gacl->acl_check($menu, $submenu, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) {
            if (!$gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) {
                return false;
            }
        }
        return true;
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
        if (!isset($_SESSION["_user"])) {
            header("Location: $login_location");
            exit;
        }
    }
    function logcheck($menu, $submenu, $login_location = false) {
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
        if (!isset($_SESSION["_user"])) {
            header("Location: $login_location");
            exit;
        }
        if (!Session::am_i_admin()) {
	        if (!$gacl->acl_check($menu, $submenu, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) {
	            if (!$gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) {
	                echo "<p align=\"center\">
	                    <b>You don't have permission to see this page</b></p>";
	                echo "<p align=\"center\">[ User: " . $_SESSION["_user"] . "]</p>";
	                echo "<p align=\"center\">[ <a href=\"/ossim/session/login.php?action=logout\"
	                      title=\"Logout\"><font color=\"black\">Logout</font></a> ]</p>";
	                exit;
	            }
	        }
        }
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
        if (!$gacl->acl_check($menu, $submenu, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"], $axo_sec, $axo_val)) {
            if (!$gacl->acl_check(ACL_DEFAULT_DOMAIN_SECTION, ACL_DEFAULT_DOMAIN_ALL, ACL_DEFAULT_USER_SECTION, $_SESSION["_user"])) {
                echo "<p align=\"center\">
                    <b>You don't have permission to do this action</b></p>";
                echo "<p align=\"center\">[ User: " . $_SESSION["_user"] . "]";
                exit;
            }
        }
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
        if (!is_object($conn)) {
            echo "Missing database connection object.";
            exit();
        }
        if ($host == "ANY") return true;
        if (!$user) $user = Session::get_session_user();
        if (isset($hostAllowedCache["$user"]["$host"])) {
            return $hostAllowedCache["$user"]["$host"];
        }
        $allowed_nets = Session::allowedNets($user);
        $allowed_sensors = Session::allowedSensors($user);
		if ($allowed_nets) {
			if (!(Net::isIpInNet($host, $allowed_nets))) {
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
		if (preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$net)) {
			require_once('classes/Net.inc');
			$net = Net::get_name_by_ip($conn,$net);
		}
        if (!$user) $user = Session::get_session_user();
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
                $ips = $rs->fields["ips"];
                $allowed_nets_array = split(',', $allowed_nets);
				if (!in_array($ips, $allowed_nets_array)) {
					$netAllowedCache["$user"]["$net"] = false;
                    return false;
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
    /* return true if we are admin user */
    function am_i_admin() {
        return ($_SESSION["_user"] == ACL_DEFAULT_OSSIM_ADMIN || $_SESSION["_is_admin"]);
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
    function insert($conn, $login, $pass, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $first_login) {
        global $ACL_MAIN_MENU;
        global $ACL_OPTIONS;
        $pass = md5($pass);
        /* bug with mrtg graphing? */
        $login = strtolower($login);
        /* insert into OSSIM database */
        $sql = "INSERT INTO users (login, name, pass, email, allowed_nets, company, department, language, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
			$first_login
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
    function update($conn, $login, $name, $email, $perms, $nets, $sensors, $company, $department, $language, $kdb_users, $first_login, $is_admin = 0) {
        global $ACL_MAIN_MENU;
        global $ACL_OPTIONS;
        /* modify OSSIM database */
        $sql = "UPDATE users SET name = ?, allowed_nets = ?, email = ?, company = ?, department = ?, language = ?, first_login = ?, is_admin = ? WHERE login = ?";
        $params = array(
            $name,
            $nets,
            $email,
            $company,
            $department,
            $language,
            $first_login,
            $is_admin,
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
	function update_noperms($conn, $login, $name, $email, $company, $department, $language, $first_login, $is_admin = 0) {
        /* modify OSSIM database */
        $sql = "UPDATE users SET name = ?, email = ?, company = ?, department = ?, language = ?, first_login = ?, is_admin = ? WHERE login = ?";
        $params = array(
            $name,
            $email,
            $company,
            $department,
            $language,
			$first_login,
			$is_admin,
            $login
        );
        ossim_set_lang($language);
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function changepass($conn, $login, $pass) {
        $pass = md5($pass);
        $sql = "UPDATE users SET pass = ?, last_pass_change=CURRENT_TIMESTAMP() WHERE login = ?";
        $params = array(
            $pass,
            $login
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $login
        );
        Log_action::log(5, $infolog);
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
        $sql = "DELETE FROM users WHERE login = ?";
        $params = array(
            $login
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
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
}
} // else include Acl
?>
