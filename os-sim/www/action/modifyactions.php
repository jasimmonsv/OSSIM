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
require_once ('ossim_db.inc');
require_once 'classes/Action.inc';
require_once ('classes/Security.inc');

Session::logcheck("MenuIntelligence", "PolicyActions");

$error = false;

$action        = POST('action');

$action_id     = POST('id');
$action_type   = REQUEST('action_type');
$cond          = POST('cond');
$on_risk       = (POST('on_risk') == "") ? "0" : "1";
$descr         = POST('descr');
$email_from    = POST('email_from');
$email_to      = POST('email_to');
$email_subject = POST('email_subject');
$email_message = POST('email_message');
$exec_command  = POST('exec_command');


$v_exec_command = $v_email = "";

if ($action_type=="email") {
    $v_exec_commad = ",OSS_NULLABLE";
}
else if ($action_type=="exec") {
    $v_email = ",OSS_NULLABLE";
}
else {
    $v_exec_commad = ",OSS_NULLABLE";
    $v_email = ",OSS_NULLABLE";
}

$validate = array (
    "action_id" => array("validation"=>"OSS_DIGIT, OSS_NULLABLE", "e_message" => 'illegal:' ._("Action id")),
    "action_type" => array("validation"=>"OSS_ALPHA", "e_message" => 'illegal:' ._("Action type")),
    "cond" => array("validation"=>"OSS_PUNC_EXT, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE", "e_message" => 'illegal:' ._("Condition")),
    "descr" => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, OSS_NULLABLE", "e_message" => 'illegal:' ._("Description")),
    "email_from" => array("validation"=>"OSS_MAIL_ADDR".$v_email, "e_message" => 'illegal:' ._("Email from")),
    "email_to" => array("validation"=>"OSS_MAIL_ADDR".$v_email, "e_message" => 'illegal:' ._("Email to")),
    "email_subject" => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, \"\>\<\"".$v_email, "e_message" => 'illegal:' ._("Email subject")),
    "email_message" => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, OSS_NL, \"\>\<\"".$v_email, "e_message" => 'illegal:' ._("Email message")),
    "exec_command" => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, \"\>\<\"".$v_exec_commad, "e_message" => 'illegal:' ._("Exec command"))
    );


if ($action == "edit" || $action == "new")
{
    $txt_start = "Update Action";
    $txt_end   = "Action succesfully updated";
}
else
{
    $error = true;
    $message_error [] = "Illegal action";
}
        
if ( GET('ajax_validation') == true )
{
    $validation_errors = validate_form_fields('GET', $validate);
    if ( $validation_errors == 1 )
        echo 1;
    else if ( empty($validation_errors) )
        echo 0;
    else
        echo $validation_errors[0];
    exit();
}
else
{
       
    $validation_errors = validate_form_fields('POST', $validate);
    
    if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) )
    {
        $error = true;

        $message_error = array();
        
        if ( is_array($validation_errors) && !empty($validation_errors) )
            $message_error = array_merge($message_error, $validation_errors);
        else
        {
            if ($validation_errors == 1)
                $message_error [] = _("Invalid send method");
        }
    }	
    
    if ( POST('ajax_validation_all') == true )
    {
        if ( is_array($message_error) && !empty($message_error) )
            echo implode( "<br/>", $message_error);
        else
            echo 0;
        
        exit();
    }
}

if ( $error == true )
{
    if($action_id!="")      $_SESSION['_actions']['action_id']     = $action_id;
    if($action_type!="")    $_SESSION['_actions']['action_type']   = $action_type;
    if($descr!="")          $_SESSION['_actions']['descr']         = $descr;
    if($cond!="")           $_SESSION['_actions']['cond']          = $cond;
    if($on_risk!="")        $_SESSION['_actions']['on_risk']       = $on_risk;
    if($email_from!="")     $_SESSION['_actions']['email_from']    = $email_from;
    if($email_to!="")       $_SESSION['_actions']['email_to']      = $email_to;
    if($email_subject!="")  $_SESSION['_actions']['email_subject'] = $email_subject;
    if($email_message!="")  $_SESSION['_actions']['email_message'] = $email_message;
    if($exec_command!="")   $_SESSION['_actions']['exec_command']  = $exec_command;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php

if (POST('withoutmenu') != "1") 
    include ("../hmenu.php"); 

echo "<h1>".gettext($txt_start)."</h1>";

if ( $error == true)
{
    $txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
    Util::print_error($txt_error);	

    if (!empty($action_id))
        Util::make_form("POST", "actionform.php");
    else 
        Util::make_form("POST", "action.php");
    die();
}

if ( $action == 'new' || $action == 'edit' )
{
    $db = new ossim_db();
    $conn = $db->connect();

    if ($action == 'new') { // new action
        if ($action_type == 'email')
            Action::insertEmail($conn, $action_type, $cond, $on_risk, $descr, $email_from, $email_to, $email_subject, $email_message);
        else if ($action_type == 'exec')
            Action::insertExec($conn, $action_type, $cond, $on_risk, $descr, $exec_command);
        else
            Action::insert($conn, $action_type, $cond, $on_risk, $descr);
    }
    else if($action == 'edit') { // update action 
        if ($action_type == 'email') 
            Action::updateEmail($conn, $action_id, $action_type, $cond, $on_risk, $descr, $email_from, $email_to, $email_subject, $email_message);
        else if ($action_type == 'exec')
            Action::updateExec($conn, $action_id, $action_type, $cond, $on_risk, $descr, $exec_command);
        else if ($action_type == 'ticket') 
            Action::update($conn, $action_id , $action_type, $cond, $on_risk, $descr);
    }

    $db->close($conn);

    if ( isset($_SESSION['_actions']) )
        unset($_SESSION['_actions']);

}
    echo "<p>"._($txt_end)."</p>";
    if (POST('withoutmenu')!=1) echo "<script type='text/javascript'>document.location.href=\"action.php\"</script>";

?>
</body>
</html>

