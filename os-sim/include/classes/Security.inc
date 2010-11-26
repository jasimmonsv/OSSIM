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
* - html_vul_scan()
* - ossim_valid()
* - ossim_set_error()
* - ossim_error()
* - valid_ip()
* - valid_cidr()
* - htm()
* - strip()
* - required()
* - GET()
* - POST()
* - REQUEST()
* Classes list:
*/
require_once 'ossim_error.inc';
require_once 'IDS/Init.php';

function ids(){
	try {
		$request = array(
			'GET' => $_GET,
			'POST' => $_POST,
			//'REQUEST' => $_REQUEST,
			'COOKIE' => $_COOKIE,
			'FILES' => $_FILES
			//'URI' => rawurlencode($_SERVER["REQUEST_URI"])
		);
		$init = IDS_Init::init('/usr/share/ossim/include/php-ids.ini');
		$init->config['General']['scan_keys'] = true;
		$ids = new IDS_Monitor($request, $init);
		$result = $ids->run();
		if (!$result->isEmpty()) {
//			require_once 'IDS/Log/Database.php';
			require_once 'IDS/Log/Composite.php';
			require_once 'IDS/Log/File.php';

			$compositeLog = new IDS_Log_Composite();
	//		$compositeLog->addLogger(IDS_Log_Database::getInstance($init));
			$compositeLog->addLogger(IDS_Log_File::getInstance($init));
			$compositeLog->execute($result);
			die(ossim_error("Sorry, operation was not completed due to security reasons. An attack attempt has been logged to the system.<br/>".$result, "ossim_alert"));
		}
	} catch (Exception $e) {
		printf('An error occured: %s',$e->getMessage());
	}
}

function ids_valid($data){
	try {
		$request = array(
			'INPUT' => $data
		);
		$init = IDS_Init::init('/usr/share/ossim/include/php-ids.ini');
		$init->config['General']['scan_keys'] = true;
		$ids = new IDS_Monitor($request, $init);
		$result = $ids->run();
		if (!$result->isEmpty()) {
			require_once 'IDS/Log/Composite.php';
			require_once 'IDS/Log/File.php';

			$compositeLog = new IDS_Log_Composite();
			$compositeLog->addLogger(IDS_Log_File::getInstance($init));
			$compositeLog->execute($result);
			die(ossim_error("Sorry, operation was not completed due to security reasons. An attack attempt has been logged to the system.<br/>".$result, "ossim_alert"));
		}
	} catch (Exception $e) {
		printf('An error occured: %s',$e->getMessage());
	}
}

function html_vul_scan($string, $is_get) {
    $scan_for = array(
        '/<\s*script[^>]*>/Ui'
    );
    if (is_array($string)) {
        foreach($string as $string_piece) {
            foreach($scan_for as $s) {
                if ($is_get) {
                    if (preg_match("/^(\<=|\>=|\>|\<|\<\>)$/",$string_piece)) {
                        continue;
                    }
                    if ($string_piece != strip_tags($string_piece)) {
                        die(ossim_error("HTTP GET " . _("param not allowed for security reasons"), "ossim_alert"));
                    }
                }
                if (preg_match($s, $string_piece)) {
                    die(ossim_error("HTTP POST " . _("param not allowed for security reasons"), "ossim_alert"));
                }
            }
        }
    } else {
        $string = html_entity_decode($string);
        foreach($scan_for as $s) {
            if ($is_get) {
                if (preg_match('/^<<./', $string) || preg_match("/^(\<=|\>=|\>|\<|\<\>)$/", $string)) {
                    continue;
                }
                if ($string !== strip_tags($string)) {
                    die(ossim_error("HTTP GET " . _("param not allowed for security reasons"), "ossim_alert"));
                }
            }
            if (preg_match($s, $string)) {
                die(ossim_error("HTTP POST " . _("param not allowed for security reasons"), "ossim_alert"));
            }
        }
    }
}
/*
* Scan all HTTP vars disallowing common exploits like XSS
*/
if (!isset($_html_vars_checked)) {
    $_is_get = true;
    foreach(array(
        $_GET,
        //$_REQUEST,
        $_POST
    ) as $globalvar) {
        foreach($globalvar as $key => $value) {
            if (is_array($value)) {
                foreach($value as $v) {
                    html_vul_scan($v, $_is_get);
                }
            } else {
                html_vul_scan($value, $_is_get);
            }
        }
        $_is_get = false;
    }
    $_html_vars_checked = true;
}
/*

Usage examples:

//--- simple validation  ---//

// Note that by default ossim_valid() doesn't permit empty vars.
// It implicitly adds the rule OSS_NOTNULL. If the var is optional
// or can be an empty value, add the rule OSS_NULLABLE

if (!ossim_valid($id, OSS_DIGIT)) {
echo ossim_error();
}

//--- complex validations ---//

// The special rule 'error:BLAHBLAH', means that in case
// the var does not validate against the other rules,
// an error will be registered (internally using ossim_set_error()).
// You can later check and retrieve the error by calling ossim_error()

// The special rule 'illegal:Title' would create automatically an
// error message in the form:
// "Error for the 'Title' field (<what caused the error>)"

// You can add your custom validation rules too, read the comments
// at the ossim_valid() function

$vals = array(
'id'   => array(OSS_DIGIT, 'error:Invalid ID, only numbers allowed'),
'name' => array(OSS_LETTER, OSS_SPACE, 'error:Only letters and spaces')
);

$name = isset($_GET['name']) ? $_GET['name'] : null;

ossim_valid($id, $vals['id']);
ossim_valid($name, $vals['name']);
if (ossim_error()) {
echo ossim_error();
}

*/
define('OSS_DIGIT', '0-9');
define('OSS_LETTER', 'A-Za-z');
define('OSS_SPACE', ' ');
define('OSS_SCORE', '_\-');
define('OSS_DOT', '\.');
define('OSS_MIDDLEDOT', '\Â·');
define('OSS_COLON', ':');
define('OSS_AT', '@');
define('OSS_MAC', 'a-fA-F0-9:');
define('OSS_PUNC_EXT', '\'"\.\!#\$%\^&\*_\-\=\+\:;,@\[\]\{\}\|\?\\\(\)\/ºª·' . OSS_SPACE);
// Temporary fix, need to check out each and every OSS_PUNC use throughout the code
define('OSS_PUNC', '\.,:@_\-\/\?&\=' . OSS_SPACE);
define('OSS_SLASH', '\/');
define('OSS_URL', '\.\/');
define('OSS_EALPHA_LOWER', 'áéíóúýàèìòùäëïöüÿâêîôûãñõ¨åæç½ðøþß');
define('OSS_EALPHA_UPPER', 'ÁÉÍÓÚÝÀÈÌÒÙÄËÏÖÜ¾ÂÊÎÔÛÃÑÕ¦ÅÆÇ¼ÐØÞ');
define('OSS_ALPHA', OSS_DIGIT . OSS_LETTER . OSS_EALPHA_LOWER . OSS_EALPHA_UPPER);
define('OSS_CRONTAB', OSS_DIGIT . OSS_LETTER . '\*\-,\/'); // Somewhat dangerous, if "/" is allowed don't allow spaces, tabs or anything similar.
define('OSS_NL', '\r\n');
define('OSS_INPUT', OSS_ALPHA . OSS_PUNC); // for most input text fields
define('OSS_TEXT', OSS_ALPHA . OSS_PUNC . OSS_NL . '\)\(\'"'); // for most textarea fields
define('OSS_USER', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT); // for validating logins/users
define('OSS_NET_NAME', OSS_LETTER . OSS_DIGIT . OSS_SCORE . OSS_DOT . OSS_COLON); // for network names
define('OSS_NOTNULL', 'rule:OSS_NOTNULL'); // default for all vars
define('OSS_NULLABLE', 'rule:OSS_NULLABLE'); // value can be null, false, missing
define('OSS_PORT', 'func:valid_port');  //	Valid port number or pair port-protocol. Port range: [0 - 65535]; Separators allowed : -, _, #; Protocols allowed : tcp, udp, icmp
define('OSS_PROTOCOL', 'tcp | udp | icmp');
define('OSS_IP_ADDR', 'func:valid_ip');
define('OSS_IP_CIDR', 'func:valid_cidr');
define('OSS_IP_ADDRCIDR', 'func:valid_ipcidr'); // is ip OR cidr format
define('OSS_FQDNS', 'func:valid_fqdns');
define('OSS_QUOTES', 'func:valid_quotes');
define('OSS_DAY', 'func:valid_day');
define('OSS_MAIL_ADDR', 'regex:[a-zA-Z0-9_\-\.+]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]+');
define('OSS_ERROR', _("unkown error")); // default error message
define('OSS_SQL', 'illegal:(select |union |update |delete |insert )');
// don't use
$GLOBALS['ossim_last_error'] = false; // track the latest error
/*
* Function for validating variables against the given rules
*
* @param1 The var to check
*
* @param2 Array with a list of validations
*         Ex: ossim_valid($foo, array(OSS_AT, OSS_SPACE))
* or
*
* @param2 to @paramN Checks
*         Ex: ossim_valid($foo, OSS_AT, OSS_SPACE)
*
* A check could be:
*      1) a string that would be converted into a regex. Ex. '0-9' -> '/[^0-9]/'
*      2) func:<func_name> Will call <func_name> passing var as the only arg.
*                          Should return true or false.
*                          Func can also be in the form <Class::method>
*      3) regex:<regex>   Will check var against the regex.
*                         Ex. '^[0-9]+' -> '/^[0-9]+/'
*      4) rule:<rules>    Not suitable for users
*/
function ossim_valid() {
	require_once("classes/Util.inc");
    static $cons = false;
    if (func_num_args() < 2) {
        die('Wrong number of params, usage: ' . __FUNCTION__ . '($var, $rules)');
    }
    if (!$cons) 
		$cons = get_defined_constants();
    
	$parms = func_get_args();
    $v_var = $parms[0]; // the var to be validated
    
	if ( is_object($v_var) ) {
        return ossim_set_error(_("Wrong datatype passed, expected string"));
    }
	else if ( !is_array($v_var) )
	{
		$subject[0] = $v_var;
	}
	else
		$subject = $v_var;
	
	array_shift($parms);
	
	
    // array of params instead of list of params
    // ej: ossim_valid($foo, array(OSS_DIGIT, OSS_FOO))
    if (func_num_args() == 2 && is_array($parms[0])) {
        $parms = $parms[0];
    }
    $error_msg = OSS_ERROR;
    $val_notnull = true; // by default do not allow empty vars
    $count = count($parms);
    for ($i = 0; $i < $count; $i++) {
        //
        // error:
        //
        if (preg_match('/^error:(.+)$/', $parms[$i], $m)) {
            $error_msg = $m[1];
            unset($parms[$i]);
            //
            // illegal:
            //
            
        } elseif (preg_match('/^illegal:(.+)$/', $parms[$i], $m)) {
            $error_msg = sprintf(_("Error in the '%s' field") , $m[1]);
            unset($parms[$i]);
            //
            // rule: (NULL permitted or not)
            //
            
        } elseif (preg_match('/^rule:(.+)$/', $parms[$i], $m)) {
            if ($m[1] == 'OSS_NOTNULL') {
                $val_notnull = true;
            } elseif ($m[1] == 'OSS_NULLABLE') {
                $val_notnull = false;
            }
            unset($parms[$i]);
        }
    }
	
	if ( empty($subject) )
		$subject[0] = array();
		
	
	foreach ($subject as $k => $v)
	{
		if (is_object($v) || is_array($v))
			return ossim_set_error("Wrong datatype passed, expected string $v");
					
		//
		// NULL check
		//
		if ($v === null || $v === '') {
			if ($val_notnull) {
				return ossim_set_error("$error_msg (" . _("missing required field") . ")");
			} else {
				return true;
			}
		}
		
		$v = ( Util::is_utf8($v) ) ? utf8_decode($v) : $v;
		
		$val_str = '';
		
		foreach($parms as $p) {
			//
			// func:
			//
			if (preg_match('/^func:(.+)$/', $p, $m)) {
				$func = $m[1];
				if (strpos($func, '::') !== false) { // static Class::method()
					$func = explode('::', $func);
				}
				if (!is_callable($func)) {
					die("The validation function '$func' doesn't exist");
				}
				if (!call_user_func($func, $v)) {
					return false;
				}
				continue;
			}
			//
			// regex:
			//
			if (preg_match('/^regex:(.+)$/', $p, $m)) {
				if (!preg_match('/^' . $m[1] . '$/', $v)) {
					$v= htmlentities($v);
					return ossim_set_error($error_msg . " (the string '<b>$v</b>' has not a valid format)");
				}
				continue;
			}
			//
			// OSS_* constants
			//
			$val_str.= $p;
		}
		
		
		
		if ($val_str && preg_match("/[^$val_str]/", $v, $m)) {
			$not = $m[0];
			if ($not == " ") $not = _("spaces");
			if ($not == "\r" || $not == "\n") $not = _("breaklines");
			$v   = htmlentities($v);
			$not = htmlentities($not);
			if (preg_match("/Password/i",$error_msg)) {
				return ossim_set_error($error_msg. " " . sprintf(_("(\"%s\" not allowed)") , $not)); 
			} else {
				return ossim_set_error($error_msg. " " . sprintf(_("(\"%s\" not allowed)") , $not) . "<br/>Introduced string: '<b>$v</b>'");
			}
		}
	}
	
    return true;
}
function ossim_set_error($error) {
    $GLOBALS['ossim_last_error'] = $error;
    return false;
}

function ossim_clean_error() {
    $GLOBALS['ossim_last_error'] = false;
    return true;
}

function ossim_get_error() {
    return $GLOBALS['ossim_last_error'];
}

function ossim_get_error_clean() {
    $error=$GLOBALS['ossim_last_error'];
	$normalize = explode("<br/>", $error);
	if (count($normalize) == 2 )
		$error = $normalize[0].".&nbsp;&nbsp;".$normalize[1];
		
	return $error;
		
}


function ossim_error($error_msg = null, $style=null) {
	include_once 'ossim_error.inc';
	
	$ossim_warn = new OssimWarn($style);
    
	$error = $error_msg ? $error_msg : $GLOBALS['ossim_last_error'];
    if (!$error) {
        return false;
    }
    
    return $ossim_warn->get('ERROR', array( $error ));
}

function validate_form_fields($type, $validate)
{
	$values            = array();
	$validation_errors = array();
	$validate_rules    = array();
	
		
	if ($type == 'POST')
	{
		$validate_rules = $validate;
		foreach ($_POST as $k => $v)
			$values[$k]= POST($k);
	}
	else if ($type == 'GET')
	{
		$k = $_GET['name'];
		
		if ( isset($validate[$k]) )
		{
			$validate_rules[$k] = $validate[$k];
			$values[$k]         = $_GET['value'];
		}
		
	}
	else
		return 1;
	
	foreach ($validate_rules as $k => $v)
	{
		if ( !isset($values[$k]) )
			$value = null;
		else
			$value = $values[$k];
			
		eval("ossim_valid(\$value, ".$validate_rules[$k]['validation'].", '".$validate_rules[$k]['e_message']."');");
		
		if ($GLOBALS['ossim_last_error'] !== false )
		{
			$validation_errors[] = $GLOBALS['ossim_last_error'];
			ossim_clean_error();
		}
		
	}
	
	return $validation_errors;

}

function valid_cidr($cidrs)
{
	$valid_sep = array (",", "\n", );
	$current_sep = '';
	
	foreach ( $valid_sep as $k => $v )
	{
		if ( preg_match("/$v/", $cidrs) )
		{
			$current_sep = $v;
			break;
		}
	}
	
	$array_cidr = ( $current_sep == '') ? array($cidrs) : explode($current_sep, $cidrs);
	
	foreach ($array_cidr as $k => $v)
	{
		if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)\/(\d+)$/', trim($v), $m) && $m[1] <= 255 && $m[2] <= 255 && $m[3] <= 255 && $m[4] <= 255 && $m[5] <= 32) 
			continue;
		else
		{
			ossim_set_error(_("Invalid IP/Mask address. Format allowed: nnn.nnn.nnn.nnn/nn") . "<br/>Entered CIDR: '<b>$v</b>'");
			return false;
		}
	}
	return true;
}
	
function valid_port($port)
{
	$valid_protocol = array ("tcp", "udp", "icmp");
	$valid_sep = array ("-", "_", "#");
	$current_sep = '';
	
	foreach ( $valid_sep as $k => $v )
	{
		if ( preg_match("/$v/", $port) )
		{
			$current_sep = $v;
			break;
		}
	}
	
	if ( empty($current_sep) )
	{
		$port = trim($port);
		if ( (preg_match('/^\d+$/', $port) && $port>=0 && $port<=65535) ) 
			return true;
		else
		{
			ossim_set_error(_("Port number isn't valid") . "<br/>Entered port: '<b>$port</b>'");
			return false;
		}
	
	}
	else
	{
		$pair_pp = explode($current_sep, $port);
		
		$port     = trim($pair_pp[0]);
		$protocol = trim(strtolower($pair_pp[1]));
		
		if ( (preg_match('/^\d+$/', $port) && $port>=0 && $port<=65535) && (in_array($protocol, $valid_protocol)) )
			return true;
		else
		{
			ossim_set_error(_("Pair port - protocol isn't valid") . "<br/>Entered pair: '<b>$port-$protocol</b>'");
			return false;
		}
	}
}

function valid_ip($ip) {
    if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $ip, $m) && $m[1] <= 255 && $m[2] <= 255 && $m[3] <= 255 && $m[4] <= 255) {
        return true;
    }
    ossim_set_error(_("Invalid IP address. Format allowed: nnn.nnn.nnn.nnn") . "<br/>Entered ip: '<b>$ip</b>'");
    return false;
}

function valid_ipcidr($ip) {
   	
	if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)\/(\d+)$/', $ip, $m) && $m[1] <= 255 && $m[2] <= 255 && $m[3] <= 255 && $m[4] <= 255 && $m[5] <= 32) {
        return true;
    }
    ossim_set_error(_("Invalid IP/CIDR address. Format allowed: nnn.nnn.nnn.nnn/nn?") . "<br/>Entered ip: '<b>$ip</b>'");
    return false;
}

function valid_fqdns($fqdns)
{
	$valid_sep = array (",", "\n", );
	$current_sep = '';
	
	foreach ( $valid_sep as $k => $v )
	{
		if ( preg_match("/$v/", $fqdns) )
		{
			$current_sep = $v;
			break;
		}
	}
	
	$array_fqdns = ( $current_sep == '') ? array($fqdns) : explode($current_sep, $fqdns);
	
	foreach ($array_fqdns as $k => $v)
	{
		if (ossim_valid(trim($v), OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, OSS_NL, 'illegal:' . _("FQDNS/Aliases")) == true) 
			continue;
		else
		{
			ossim_set_error(_("Invalid FQDNS/Aliases")."<br/>Entered FQDNS/Aliases: '<b>$v</b>'");
			return false;
		}
	}
	return true;
}


function valid_quotes($value) {
    if (!preg_match('/\'|\"/', $value)) {
        return true;
    }
    ossim_set_error(_("Invalid Quotes. Don't use quotes ' or \""));
    return false;
}
function valid_day($value) {
    if (preg_match('/(\d+)-(\d+)-(\d+)/', $value, $m)) {
        return checkdate($m[2],$m[3],$m[1]);
    }
    ossim_set_error(_("Invalid Date. Format allowed: YYYY-MM-DD"));
    return false;
}
/* Test perms in sensor_ip */
function validate_sensor_perms($conn,$sensor_ip,$sql="") {
    require_once 'classes/Sensor.inc';
    $ossim_sensors = Sensor::get_list($conn,$sql);
    $sensors_list = array();
    foreach ($ossim_sensors as $sensor) $sensors_list[] = $sensor->get_ip();
    return in_array($sensor_ip,$sensors_list);
}

/*
* Safe html escape. Always use that for printing vars comming
* from outside ($_GET, $_POST, databases, etc)
*/
function htm($var) {
    return nl2br(htmlentities($var));
}
function strip($var) {
    return get_magic_quotes_gpc() ? stripslashes($var) : $var;
}
function required() {
    return '&nbsp;<span class="required" title="' . _("Required field") . '">*</span>';
}
/*
* WARNING: if you use this function, it's a MUST to correct quote
* the values before inserting in the DB. AdoDB provides that, ex:
*
* $get1 = GET('get1');
* $get2 = GET('get2');
* $sql = "INSET INTO foo VALUES (?, ?)";
* $conn->Execute($sql, array($get1, $get2));
*
* $get1 will be automagically quoted against the DB quoting function
* (ex: mysql_real_escape_string()) and the result (ex: 'It\'s tea time')
* will replace the first "?". The same for $get2.
*/
function GET($param) {
    return isset($_GET[$param]) ? strip($_GET[$param]) : null;
}
/*
* @see GET()
*/
function POST($param) {
    if (isset($_POST[$param])) {
        if (!is_array($_POST[$param])) {
            return strip($_POST[$param]);
        } else {
            $ret = array();
            foreach($_POST[$param] as $k => $v) {
                $ret[$k] = strip($v);
            }
            return $ret;
        }
    }
    return null;
}
/*
* @see GET()
*/
function REQUEST($param) {
    return isset($_REQUEST[$param]) ? strip($_REQUEST[$param]) : null;
}
/* TESTS
function test($res, $expected)
{
static $test_num = 0;
echo '#' . ++$test_num;
if ($res != $expected) { echo " FAIL!\n"; return; }
echo " GOOD\n";
}
ini_set('include_path', '../');


test(ossim_valid('24', OSS_DIGIT), true); #1
test(ossim_valid('0.24', OSS_DIGIT), false); #2
test(ossim_valid('hello', OSS_ALPHA), true); #3
test(ossim_valid('hello dasd', OSS_ALPHA), false); #4
test(ossim_valid('', OSS_NOTNULL), false); #5
test(ossim_valid('', OSS_NULLABLE), true); #6
test(ossim_valid('3', OSS_LETTER), false); #7
test(ossim_valid('hello', OSS_LETTER), true); #8
test(ossim_valid('0.1.1.1', OSS_IP_ADDR), true); #9
test(ossim_valid('500.1.1.1', OSS_IP_ADDR), false); #10
test(ossim_valid('hello@hello.com', OSS_MAIL_ADDR), true); #11
test(ossim_valid('hello@hello', OSS_MAIL_ADDR), false); #12
test(ossim_valid('hello@he.llo.', OSS_MAIL_ADDR), false); #13
test(ossim_valid('hello@he.llo.9', OSS_MAIL_ADDR), false); #14
test(ossim_valid('hello@hello.com9', array(OSS_NOTNULL, OSS_MAIL_ADDR)), false); #15
test(ossim_valid('', array(OSS_NULLABLE, OSS_MAIL_ADDR)), true); #16
test(ossim_valid('', OSS_LETTER), false); #17
test(ossim_valid('ho la', OSS_LETTER, OSS_SCORE), false); #18
test(ossim_valid('', OSS_NULLABLE, OSS_IP_ADDR), true); #19
test(ossim_valid('', OSS_IP_ADDR, OSS_NULLABLE), true); #20
test(ossim_valid('', OSS_IP_ADDR), false); #21
test(ossim_valid('123.456.2.3', OSS_IP_ADDR), false); #22
test(ossim_valid('123.220.44.0', OSS_IP_ADDR), true); #23

test(ossim_valid('foo', 'func:no_exists'), true); #should die
//*/
function check_uniqueid($unique_id1,$unique_id2) {
	if ($unique_id1 != "" && $unique_id2 != "" && $unique_id1 == $unique_id2) {
		return 1;
	} else {
		echo _("Can't do this action for security reasons...");
		return 0;
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
	if ($expired_timeout==0) return;
	if (isset($_SESSION["_expiration_time"]) && intval($_SESSION["_expiration_time"])+$expired_timeout < time()) {
		header("Location /ossim/session/login.php?action=logout");
	}
	// only update if not exists bypass => header ajax responses
	$_SESSION["_expiration_time"] = time();
}
if ((session_id() != '') && intval(GET('bypassexpirationupdate'))!=1 && intval(POST('bypassexpirationupdate'))!=1) {
	expire_session_check();
}
// IDS Security
ids();
?>
