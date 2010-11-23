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
require_once ('classes/Host.inc');
require_once ('ossim_db.inc');
require_once ('classes/RRD_config.inc');
require_once ('classes/Security.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

$error = false;

$action    = REQUEST('action');

$id        = REQUEST('id');
$ip        = REQUEST('ip');
$hostname  = POST('hostname');
$type      = POST('type');
$user_ct   = POST('user_ct');
$pass_ct   = POST('pass_ct');
$pass_ct2  = POST('pass_ct2');
$extra     = POST('extra');

$validate = array (
    "hostname"  => array("validation"=>"OSS_ALPHA, OSS_SPACE, OSS_PUNC", "e_message" => 'illegal:' . _("Hostname")),
    "ip"        => array("validation"=>"OSS_IP_ADDR", "e_message" => 'illegal:' . _("Ip address")),
    "type"      => array("validation"=>"OSS_DIGIT", "e_message" => 'illegal:' . _("Type")),
    "user_ct"   => array("validation"=>"OSS_ALPHA, OSS_USER", "e_message" => 'illegal:' . _("Username")),
    "pass_ct"   => array("validation"=>"OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT", "e_message" => 'illegal:' . _("Password")),
    "pass_ct2"  => array("validation"=>"OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT", "e_message" => 'illegal:' . _("Repeat Password")),
    "extra"     => array("validation"=>"OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NL", "e_message" => 'illegal:' . _("Extra")));


if ($action ==  "edit")
{
    $txt_start = "Update Host Credentials";
    $txt_end = "Host Credentials succesfully updated";
}
else if ($action == "delete")
{
    
    $txt_start = "Delete Host Credential";
    $txt_end = "Host Credential succesfully deleted";
    
    $validate = array();
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
    ossim_valid($id, OSS_NULLABLE, OSS_DIGIT, 'illegal:'. _("Id"));
    
    if ( ossim_error() ) {
        $validation_errors[] = ossim_set_error(_("Invalid credential id") . "<br/>Entered id: '<b>$id</b>'");
    }
    
    if ( ( $validation_errors == 1 ) ||  (is_array($validation_errors) && !empty($validation_errors)) || $pass_ct != $pass_ct2 )
    {
        $error = true;
                
        $message_error = array();
        
        if(  $pass_ct != $pass_ct2 )
            $message_error [] = _("Password fields are different");
        
        
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
    $_SESSION['_credentials']['hostname'] = $hostname;
    $_SESSION['_credentials']['ip']       = $ip;
    $_SESSION['_credentials']['type']     = $type;
    $_SESSION['_credentials']['user_ct']  = $user_ct;
    $_SESSION['_credentials']['pass_ct']  = $pass_ct;
    $_SESSION['_credentials']['pass_ct2'] = $pass_ct2;
    $_SESSION['_credentials']['extra']    = $extra;
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

if (GET('withoutmenu') != "1") 
    include ("../hmenu.php"); 

echo "<h1>".gettext($txt_start)."</h1>";

if ( $error == true)
{
    $txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode( "<br/>", $message_error)."</div>";			
    Util::print_error($txt_error);	

    if (!empty($ip) )
        Util::make_form("POST", "hostcredentialsform.php?ip=".$ip);
    else 
        Util::make_form("POST", "host.php");
    die();
}

if ( $action == 'edit' || $action == 'delete' )
{
    $db = new ossim_db();
    $conn = $db->connect();

    if ($action == 'edit')
        Host::modify_credentials($conn, $ip, $type, $user_ct, $pass_ct, $extra);
    else if ($action == 'delete' && !empty($id))
        Host::delete_credential($conn, $id);
        
    $db->close($conn);

    if ( isset($_SESSION['_credentials']) )
        unset($_SESSION['_credentials']);

}


    echo "<p>"._($txt_end)."</p>";

    echo "<script type='text/javascript'>document.location.href=\"hostcredentialsform.php?ip=$ip\"</script>";



?>
</body>
</html>

