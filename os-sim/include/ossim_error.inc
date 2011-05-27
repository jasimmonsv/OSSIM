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
* - OssimMainError()
* - display()
* - get()
* - OssimError()
* - OssimWarn()
* - OssimNotice()
* - printr()
* Classes list:
* - OssimMainError
* - OssimError  extends OssimMainError
* - OssimWarn   extends OssimMainError
* - OssimNotice extends OssimMainError
*/

require_once 'classes/Util.inc';

class OssimMainError {
    
	public $errors;
	
	public $error_type;
    
	public $current_style;
	
	public $action;
	
	public static $base_style = 'font-family:Arial, Helvetica, sans-serif; 
								 font-size:13px; border: 1px solid; 
								 margin: 10px auto; 
								 padding:15px 10px 15px 50px;  
								 background-repeat: no-repeat; 
								 background-position: 10px center;';
	
	
	public static $styles = array ( "ossim_error" => 'color: #D8000C; 
													  background-color: #FFBABA; 
													  background-image: url("../pixmaps/ossim_error.png");',
									"ossim_alert" => 'color: #9F6000;
													  background-color: #FEEFB3;
													  background-image: url("../pixmaps/ossim_alert.png");',
									"ossim_info"  => 'color: #00529B;
													  background-color: #BDE5F8;
													  background-image: url("../pixmaps/ossim_info.png");'
								    );
	
	public function __construct($error_msg, $style='ossim_error', $error_type = 'DEFAULT'){
        $this->errors = array(
            "DEFAULT" => array(
                "short_descr" => gettext("Error"),
                "long_descr"  => gettext("Error"),
            ),
		);
		
		$this->error_type     = $error_type;
		$this->current_style  = OssimMainError::$base_style;
		$this->current_style .= OssimMainError::$styles[$style];
		$this->action         = "/* no action */";
    }
			
    public function display($error_code = NULL, $extrainfo = NULL, $option = "noback") {
        print $this->get(_($error_code), $extrainfo);
		
		if ($option == "noback" || $option === false)
			$this->action = "die();";
		else if ( !empty($option) && $option !== false)
			$this->action = $option;
		
		if ($this->action) 
			eval($this->action);
			
	}
    
	function get($error_code = NULL, $extrainfo = NULL) {
        
		$error_code = ( empty($this->errors[$error_code]) ) ? _("DEFAULT") : $error_code;
		        
		$msg = "<div style='".$this->current_style."'>\n";
        $msg.= "<strong>" . $this->errors["$error_code"]["short_descr"] . "</strong><br/>";
        
		$msg.= $this->errors["$error_code"]["long_descr"];
        
		if ( $error_code == _("DEFAULT") && count($extrainfo) == 0 )
			$extrainfo = array("");
				
		if ( count($extrainfo) > 0 )
		{
            $n = 1;
            foreach($extrainfo as $info)
			{
                $msg = str_replace("%" . $n . "%", $info, $msg);
                $n = $n + 1;
            }
        }
				
        $msg.= "</div><br/>\n";
        return $msg;
    }
}
class OssimError extends OssimMainError {
     public function __construct($error_msg=null, $action="noback") {
        
		parent::__construct($error_msg=null, $style='ossim_error', $error_type = 'ERROR');
		
		/* Action after display error */
        
		if ( !empty($action) || ($action === false) )
			$this->action = $action;
		else
		{
			$style_div    = "margin:auto; width:90%; text-align:center;";
			$this->action = 'echo "<br/><div style=\"'.$style_div.'\"><input type=\"button\" class=\"button\" onclick=\"javascript:history.back();\" value=\"' . gettext("Back") . '\"/></div";';
		}
				
		/* specific errors */
        /* PHP-adodb */
        
		$this->errors[_("DEFAULT")] = array(
			"short_descr" => gettext("Error!") ,
			"long_descr"  => "%1%"
        );
		
		$this->errors[_("ADODB_PATH")] = array(
            "short_descr" => gettext("Unable to load ADODB library") ,
            "long_descr"  => gettext("Make sure that adodb_path is pointing to the right path") ,
        );
        /* PHP-domxml extension */
        $this->errors[_("PHP_DOMXML")] = array(
            "short_descr" => gettext("PHP-domxml  not loaded") ,
            "long_descr"  => gettext("Make sure that php-domxml is installed and loaded") ,
        );
        /* NMAP not found */
        $this->errors[_("NMAP_PATH")] = array(
            "short_descr" => gettext("NMAP not found") ,
            "long_descr"  => gettext("Make sure that nmap is installed and nmap_path is pointing to
                the right path") ,
        );
        /* fpdf */
        $this->errors[_("FPDF_PATH")] = array(
            "short_descr" => gettext("Unable to load FPDF library") ,
            "long_descr"  => gettext("Make sure that fpdf_path is pointing to the right path") ,
        );
        /* jpgraph */
        $this->errors[_("JPGRAPH_PATH")] = array(
            "short_descr" => gettext("Unable to load Jpgraph library") ,
            "long_descr"  => gettext("Make sure that jpgraph_path is pointing to the right path") ,
        );
        /* Form fill error */
        $this->errors[_("FORM_MISSING_FIELDS")] = array(
            "short_descr" => gettext("There are missing fields") ,
            "long_descr"  => gettext("Please, fill all the fields") ,
        );
        /* action_id required */
        $this->errors[_("ACTIONID_REQ")] = array(
            "short_descr" => gettext("There are missing parameters") ,
            "long_descr"  => gettext("action_id is required") ,
        );
        /* action_id not found */
        $this->errors[_("ACTIONID_UNK")] = array(
            "short_descr" => gettext("Action id not found") ,
            "long_descr"  => gettext("action_id %1% not found") ,
        );
        /* not numeric var */
        $this->errors[_("NOT_NUMERIC")] = array(
            "short_descr" => gettext("Error processing") ,
            "long_descr"  => gettext("<b> %1% </b> must be numeric") ,
        );
        /* Plugin id not valid */
        $this->errors[_("NOT_PLUGINID")] = array(
            "short_descr" => gettext("Plugin id is not valid") ,
            "long_descr"  => gettext("plugin_id %1%") ,
        );
        /* Not values for priority or reliability */
        $this->errors[_("NOT_PRIORREA")] = array(
            "short_descr" => gettext("Missing parameters") ,
            "long_descr"  => gettext("Not values for priority or reliability") ,
        );
        /* Priority must be between 0 and 10 */
        $this->errors[_("NOT_VALIDPRIO")] = array(
            "short_descr" => gettext("Not valid priority") ,
            "long_descr"  => gettext("Priority must be between 0 and 10") ,
        );
        /* Reliability myst be between 0 and 5 */
        $this->errors[_("NOT_VALIDREL")] = array(
            "short_descr" => gettext("Not valid reliability") ,
            "long_descr"  => gettext("Reliability must be between 0 and 5") ,
        );
        /* Unknown action */
        $this->errors[_("UNK_ACTION")] = array(
            "short_descr" => gettext("Scan not started") ,
            "long_descr"  => gettext("Unknown action") ,
        );
        /* Error creating socket */
        $this->errors[_("CRE_SOCKET")] = array(
            "short_descr" => gettext("Socket_create() failed: reason") ,
            "long_descr"  => gettext("%1%") ,
        );
        /* Socket error: Framework running? */
        $this->errors[_("FRAMW_NOTRUN")] = array(
            "short_descr" => gettext("Socket error") ,
            "long_descr"  => gettext("Is Ossim framework running at %1% ?") ,
        );
        /* Error creating socket */
        $this->errors[_("INVALID_SIG")] = array(
            "short_descr" => gettext("Invalid Signature") ,
            "long_descr"  => gettext("Wrong signature name") ,
        );
        
        /* Wrong user */
        $this->errors[_("WRONG_USER")] = array(
            "short_descr" => gettext("Wrong user") ,
            "long_descr"  => gettext("Please enter a valid username") ,
        );
        /* Old password not correct */
        $this->errors[_("BAD_OLD_PASSWORD")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Old password is not correct") ,
        );
        /* Admin password not correct */
        $this->errors[_("BAD_ADMIN_PASSWORD")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Admin password is not correct") ,
        );
        /* Password mismatch */
        $this->errors[_("PASSWORDS_MISMATCH")] = array(
            "short_descr" => gettext("Authentication failure") ,
            "long_descr"  => gettext("Passwords mismatch") ,
        );
        /* Password lenght */
        $this->errors[_("PASSWORD_SIZE")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Password is long enough. Check the password policy configuration for more details") ,
        );
        $this->errors[_("PASSWORD_SIZE_MAX")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Password is too long. Check the password policy configuration for more details") ,
        );
        /* Password lenght */
        $this->errors[_("PASSWORD_ALPHANUM")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Password is not strong enough. Check the password policy configuration for more details") ,
        );
        $this->errors[_("PASSWORD_EXPIRE_MIN")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("Password lifetime is too short to allow change. Wait a few minutes...") ,
        );
        /* Password recent */
        $this->errors[_("PASSWORD_RECENT")] = array(
            "short_descr" => gettext("Password saving failure") ,
            "long_descr"  => gettext("This password is recently used. Try another") ,
        );
        /* Only admin can do that */
        $this->errors[_("ONLY_ADMIN")] = array(
            "short_descr" => gettext("Permission error") ,
            "long_descr"  => gettext("Only admin can do that") ,
        );
        /* A user can not remove himself */
        $this->errors[_("USER_CANT_REMOVE")] = array(
            "short_descr" => gettext("Permission error") ,
            "long_descr"  => gettext("A user can not remove himself") ,
        );
        /* Wrong ip */
        $this->errors[_("WRONG_IP")] = array(
            "short_descr" => gettext("Wrong IP") ,
            "long_descr"  => gettext("Please introduce a valid ip") ,
        );
        /* Wrong profile */
        $this->errors[_("WRONG_PROFILE")] = array(
            "short_descr" => gettext("Wrong profile") ,
            "long_descr"  => gettext("Please introduce a valid profile") ,
        );
        /* Wrong policy id */
        $this->errors[_("WRONG_POLICY_ID")] = array(
            "short_descr" => gettext("Wrong policy id") ,
            "long_descr"  => gettext("Please introduce a valid policy id") ,
        );
        /* Wrong incident id */
        $this->errors[_("WRONG_INCIDENT_ID")] = array(
            "short_descr" => gettext("Wrong incident id") ,
            "long_descr"  => gettext("Please introduce a valid incident id") ,
        );
        /* Wrong incident type id */
        $this->errors[_("WRONG_INCIDENT_TYPE")] = array(
            "short_descr" => gettext("Wrong incident type id") ,
            "long_descr"  => gettext("Please introduce a valid incident type id") ,
        );
        /* Wrong port name */
        $this->errors[_("WRONG_PROFILE")] = array(
            "short_descr" => gettext("Wrong port name") ,
            "long_descr"  => gettext("Please introduce a valid port name") ,
        );
        /* Wrong sensor */
        $this->errors[_("WRONG_SENSOR")] = array(
            "short_descr" => gettext("Wrong sensor") ,
            "long_descr"  => gettext("Please introduce a valid sensor") ,
        );
        /* Wrong net */
        $this->errors[_("WRONG_NET")] = array(
            "short_descr" => gettext("Wrong net") ,
            "long_descr"  => gettext("Please introduce the net argument in nmap
            format example: ?net=192.168.1.1-255") ,
        );
        /* Wrong backlog id */
        $this->errors[_("WRONG_BACKLOG_ID")] = array(
            "short_descr" => gettext("Wrong backlog id") ,
            "long_descr"  => gettext("Please introduce a valid backlog id") ,
        );
        /* Wrong rule name */
        $this->errors[_("WRONG_RULE_NAME")] = array(
            "short_descr" => gettext("Wrong rule name") ,
            "long_descr"  => gettext("Please introduce a valid rule name") ,
        );
        /* Incorrect date range */
        $this->errors[_("INCORRECT_DATE_RANGE")] = array(
            "short_descr" => gettext("Wrong parameter") ,
            "long_descr"  => gettext("Incorrect date range") ,
        );
        /* response id required*/
        $this->errors[_("RESPONSEID_REQUIRED")] = array(
            "short_descr" => gettext("Parameters missing") ,
            "long_descr"  => gettext("Response id is required") ,
        );
        /* rules not found*/
        $this->errors[_("RULES_NOT_FOUND")] = array(
            "short_descr" => gettext("Rules not found") ,
            "long_descr"  => gettext("Snort rules where not found at: %1%") ,
        );
    }
}
class OssimWarn extends OssimMainError {
    public function __construct($error_msg=null, $action="noback") {
        /* warn - yellow colors */
       
        parent::__construct($error_msg, $style='ossim_alert', $error_type = 'WARNING');
					
		/* Action after display error */
        
		if ( !empty($action) || ($action === false) )
				$this->action = $action;
		else
		{
			$style_div    = "margin:auto; width:90%; text-align:center;";
			$this->action = 'echo "<br/><div style=\"'.$style_div.'\"><input type=\"button\" class=\"button\" onclick=\"javascript:history.back();\" value=\"' . gettext("Back") . '\"/></div";';
		}
		
        $this->errors[_("STRING_NOT_VALID")] = array(
            "short_descr" => gettext("String is not valid!") ,
            "long_descr" => gettext("For security reasons, bad characters have been skipped") ,
        );
		
		$this->errors[_("DEFAULT")] = array(
                "short_descr" => gettext("Warning!") ,
                "long_descr"  => "%1%"
        );
						
	}
}
class OssimNotice extends OssimMainError {
    public function __construct($error_msg=null, $action="noback") {
        /* Notice - blue colors */
        parent::__construct($error_msg, $style='ossim_info', $error_type = 'NOTICE');
        
		/* Action after display error */
        
		if ( !empty($action) || ($action === false) )
				$this->action = $action;
		else
		{
			$style_div    = "margin:auto; width:90%; text-align:center;";
			$this->action = 'echo "<br/><div style=\"'.$style_div.'\"><input type=\"button\" class=\"button\" onclick=\"javascript:history.back();\" value=\"' . gettext("Back") . '\"/></div";';
		}
		
       	$this->errors[_("FORM_NOFILL")] = array(
            "short_descr" => gettext("Empty a required field") ,
            "long_descr"  => gettext("Please fill in all required fields") ,
        ); 
		
		$this->errors[_("DEFAULT")] = array(
			"short_descr" => gettext("Notice!") ,
			"long_descr"  => "%1%"
		);
		
		$this->errors[_("LOGOUT")] = array(
			"short_descr" => gettext("Notice!") ,
			"long_descr"  => _("You don't have permission to see this page")."%1%"
		);
    }
}

/*
* Function for debuging vars (developers only)
*/
function printr($var, $die = false, $return = false) {
    if ($var === null) $str = 'null';
    elseif ($var === false) $str = 'false';
    elseif ($var === true) $str = 'true';
    elseif ($var === '') $str = "''";
    else $str = print_r($var, true);
    $str = "<pre>" . Util::htmlentities($str, ENT_COMPAT, "UTF-8") . "</pre>";
    if ($return) {
        return $str;
    }
    echo $str;
    if ($die) die();
}
/*
$error = new OssimError();
$error->display("ADODB_PATH");
*/
?>
