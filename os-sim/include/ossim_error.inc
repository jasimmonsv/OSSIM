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
* - OssimError extends OssimMainError
* - OssimWarn extends OssimMainError
* - OssimNotice extends OssimMainError
*/
class OssimMainError {
    var $errors = array();
    var $_border_color;
    var $_bg_color;
    var $style;
    var $action;
    function OssimMainError() {
        $this->errors = array(
            "DEFAULT" => array(
                "short_descr" => gettext("Error") ,
                "long_descr" => gettext("Error") ,
            )
        );
        $this->style = "border-style: dotted; " . "border-width: 3px;" . "margin-left: 50px;" . "margin-right: 50px;" . "padding: 5px;" . "text-align: center;" . "border-color: " . $this->_border_color . ";" . "background-color: " . $this->_bg_color . ";";
        $this->action = "/* no action */";
    }
    function display($error_code, $extrainfo = NULL) {
        print $this->get($error_code, $extrainfo);
        eval($this->action);
    }
    function get($error_code, $extrainfo = NULL) {
        if (!array_key_exists($error_code, $this->errors)) $error_code = "DEFAULT";
        $msg = "<div style=\"" . $this->style . "\">\n";
        $msg.= "<b>" . $this->errors["$error_code"]["short_descr"] . "</b><br/>";
        $msg.= $this->errors["$error_code"]["long_descr"];
        if (count($extrainfo)) {
            $n = 1;
            foreach($extrainfo as $info) {
                $msg = str_replace("%" . $n . "%", $info, $msg);
                $n = $n + 1;
            }
        }
        $msg.= "</div><br/>\n";
        return $msg;
    }
}
class OssimError extends OssimMainError {
    function OssimError() {
        /* error - red colors */
        $this->_border_color = "#900404";
        $this->_bg_color = "#df5f4e";
        /* call to main error class */
        $this->OssimMainError();
        /* action after display error */
        $this->action = 'echo "<br/><br/><a href=\"javascript:history.back();\">' . gettext("Back") . '</a>"; die();';
        /* specific errors */
        /* PHP-adodb */
        $this->errors["ADODB_PATH"] = array(
            "short_descr" => gettext("Unable to load ADODB library") ,
            "long_descr" => gettext("
                Make sure that adodb_path is pointing to the right path") ,
        );
        /* PHP-domxml extension */
        $this->errors["PHP_DOMXML"] = array(
            "short_descr" => gettext("PHP-domxml  not loaded") ,
            "long_descr" => gettext("
                Make sure that php-domxml is installed and loaded") ,
        );
        /* NMAP not found */
        $this->errors["NMAP_PATH"] = array(
            "short_descr" => gettext("NMAP not found") ,
            "long_descr" => gettext("
                Make sure that nmap is installed and nmap_path is pointing to
                the right path") ,
        );
        /* fpdf */
        $this->errors["FPDF_PATH"] = array(
            "short_descr" => gettext("Unable to load FPDF library") ,
            "long_descr" => gettext("
                Make sure that fpdf_path is pointing to the right path") ,
        );
        /* jpgraph */
        $this->errors["JPGRAPH_PATH"] = array(
            "short_descr" => gettext("Unable to load Jpgraph library") ,
            "long_descr" => gettext("
                Make sure that jpgraph_path is pointing to the right path") ,
        );
        /* Form fill error */
        $this->errors["FORM_MISSING_FIELDS"] = array(
            "short_descr" => gettext("There are missing fields") ,
            "long_descr" => gettext("Please, fill all the fields") ,
        );
        /* action_id required */
        $this->errors["ACTIONID_REQ"] = array(
            "short_descr" => gettext("There are missing parameters") ,
            "long_descr" => gettext("action_id is required") ,
        );
        /* action_id not found */
        $this->errors["ACTIONID_UNK"] = array(
            "short_descr" => gettext("Action id not found") ,
            "long_descr" => gettext("action_id %1% not found") ,
        );
        /* not numeric var */
        $this->errors["NOT_NUMERIC"] = array(
            "short_descr" => gettext("Error processing") ,
            "long_descr" => gettext("<b> %1% </b> must be numeric") ,
        );
        /* Plugin id not valid */
        $this->errors["NOT_PLUGINID"] = array(
            "short_descr" => gettext("Plugin id is not valid") ,
            "long_descr" => gettext("plugin_id %1%") ,
        );
        /* Not values for priority or reliability */
        $this->errors["NOT_PRIORREA"] = array(
            "short_descr" => gettext("Missing parameters") ,
            "long_descr" => gettext("Not values for priority or reliability") ,
        );
        /* Priority must be between 0 and 10 */
        $this->errors["NOT_VALIDPRIO"] = array(
            "short_descr" => gettext("Not valid priority") ,
            "long_descr" => gettext("Priority must be between 0 and 10") ,
        );
        /* Reliability myst be between 0 and 5 */
        $this->errors["NOT_VALIDREL"] = array(
            "short_descr" => gettext("Not valid reliability") ,
            "long_descr" => gettext("Reliability must be between 0 and 5") ,
        );
        /* Unknown action */
        $this->errors["UNK_ACTION"] = array(
            "short_descr" => gettext("Scan not started") ,
            "long_descr" => gettext("Unknown action") ,
        );
        /* Error creating socket */
        $this->errors["CRE_SOCKET"] = array(
            "short_descr" => gettext("Socket_create() failed: reason") ,
            "long_descr" => gettext("%1%") ,
        );
        /* Socket error: Framework running? */
        $this->errors["FRAMW_NOTRUN"] = array(
            "short_descr" => gettext("Socket error") ,
            "long_descr" => gettext("Is Ossim framework running at %1% ?") ,
        );
        /* Error creating socket */
        $this->errors["INVALID_SIG"] = array(
            "short_descr" => gettext("Invalid Signature") ,
            "long_descr" => gettext("Wrong signature name") ,
        );
        /* Socket error: Framework running? */
        $this->errors["FRAMW_NOTRUN"] = array(
            "short_descr" => gettext("Socket error") ,
            "long_descr" => gettext("Is Ossim framework running at %1% ?") ,
        );
        /* Wrong user */
        $this->errors["WRONG_USER"] = array(
            "short_descr" => gettext("Wrong user") ,
            "long_descr" => gettext("Please enter a valid username") ,
        );
        /* Old password not correct */
        $this->errors["BAD_OLD_PASSWORD"] = array(
            "short_descr" => gettext("Authentication failure") ,
            "long_descr" => gettext("Old password is not correct") ,
        );
        /* Password mismatch */
        $this->errors["PASSWORDS_MISMATCH"] = array(
            "short_descr" => gettext("Authentication failure") ,
            "long_descr" => gettext("Old password is not correct") ,
        );
        /* Only admin can do that */
        $this->errors["ONLY_ADMIN"] = array(
            "short_descr" => gettext("Permission error") ,
            "long_descr" => gettext("Only admin can do that") ,
        );
        /* A user can not remove himself */
        $this->errors["USER_CANT_REMOVE"] = array(
            "short_descr" => gettext("Permission error") ,
            "long_descr" => gettext("A user can not remove himself") ,
        );
        /* Wrong ip */
        $this->errors["WRONG_IP"] = array(
            "short_descr" => gettext("Wrong IP") ,
            "long_descr" => gettext("Please introduce a valid ip") ,
        );
        /* Wrong profile */
        $this->errors["WRONG_PROFILE"] = array(
            "short_descr" => gettext("Wrong profile") ,
            "long_descr" => gettext("Please introduce a valid profile") ,
        );
        /* Wrong policy id */
        $this->errors["WRONG_POLICY_ID"] = array(
            "short_descr" => gettext("Wrong policy id") ,
            "long_descr" => gettext("Please introduce a valid policy id") ,
        );
        /* Wrong incident id */
        $this->errors["WRONG_INCIDENT_ID"] = array(
            "short_descr" => gettext("Wrong incident id") ,
            "long_descr" => gettext("Please introduce a valid incident id") ,
        );
        /* Wrong incident type id */
        $this->errors["WRONG_INCIDENT_TYPE"] = array(
            "short_descr" => gettext("Wrong incident type id") ,
            "long_descr" => gettext("Please introduce a valid incident type id") ,
        );
        /* Wrong port name */
        $this->errors["WRONG_PROFILE"] = array(
            "short_descr" => gettext("Wrong port name") ,
            "long_descr" => gettext("Please introduce a valid port name") ,
        );
        /* Wrong sensor */
        $this->errors["WRONG_SENSOR"] = array(
            "short_descr" => gettext("Wrong sensor") ,
            "long_descr" => gettext("Please introduce a valid sensor") ,
        );
        /* Wrong net */
        $this->errors["WRONG_NET"] = array(
            "short_descr" => gettext("Wrong net") ,
            "long_descr" => gettext("Please introduce the net argument in nmap
            format example: ?net=192.168.1.1-255") ,
        );
        /* Wrong backlog id */
        $this->errors["WRONG_BACKLOG_ID"] = array(
            "short_descr" => gettext("Wrong backlog id") ,
            "long_descr" => gettext("Please introduce a valid backlog id") ,
        );
        /* Wrong rule name */
        $this->errors["WRONG_RULE_NAME"] = array(
            "short_descr" => gettext("Wrong rule name") ,
            "long_descr" => gettext("Please introduce a valid rule name") ,
        );
        /* Incorrect date range */
        $this->errors["INCORRECT_DATE_RANGE"] = array(
            "short_descr" => gettext("Wrong parameter") ,
            "long_descr" => gettext("Incorrect date range") ,
        );
        /* response id required*/
        $this->errors["RESPONSEID_REQUIRED"] = array(
            "short_descr" => gettext("Parameters missing") ,
            "long_descr" => gettext("Response id is required") ,
        );
        /* rules not found*/
        $this->errors["RULES_NOT_FOUND"] = array(
            "short_descr" => gettext("Rules not found") ,
            "long_descr" => gettext("Snort rules where not found at: %1%") ,
        );
    }
}
class OssimWarn extends OssimMainError {
    function OssimWarn() {
        /* warn - yellow colors */
        $this->_border_color = "#dd9e06";
        $this->_bg_color = "#eceaab";
        /* call to main error class */
        $this->OssimMainError();
        /* specific errors */
        $this->errors["STRING_NOT_VALID"] = array(
            "short_descr" => gettext("String is not valid!") ,
            "long_descr" => gettext("
                For security reasons, bad characters have been skipped") ,
            $this->errors["ERROR"] = array(
                "short_descr" => gettext("Error!") ,
                "long_descr" => "%1%"
            ) ,
        );
    }
}
class OssimNotice extends OssimMainError {
    function OssimNotice() {
        /* Notice - blue colors */
        $this->_border_color = "#2f4960";
        $this->_bg_color = "#9bb5cb";
        /* call to main error class */
        $this->OssimMainError();
        /* action after display error */
        $this->action = 'echo "<br/><br/><a href=\"javascript:history.back();\">' . gettext("Back") . '</a>";';
        /* empty required field */
        $this->errors["FORM_NOFILL"] = array(
            "short_descr" => gettext("Empty a required field") ,
            "long_descr" => gettext("Please fill in all required fields") ,
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
    $str = "<pre>" . htmlentities($str, ENT_COMPAT, "UTF-8") . "</pre>";
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
