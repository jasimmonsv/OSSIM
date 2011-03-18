<?php
/***************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/

require_once "classes/Session.inc";
Session::useractive("../session/login.php");
require_once "ossim_db.inc";
require_once "ossim_conf.inc";

$conf          = $GLOBALS["CONF"];
$version       = $conf->get_conf("ossim_server_version", FALSE);
$opensource    = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo          = (preg_match("/.*demo.*/i",$version)) ? true : false;

$current_user  = Session::get_session_user();



if ($opensource || $current_user != ACL_DEFAULT_OSSIM_ADMIN) {
	exit;
}

require_once "customize_common.php";

//AJAX Upload
if ( $_GET['imgfile'] != "" && preg_match("/^\d+$/",$_GET['imgfile']) )
{
	upload($_GET['imgfile']);
	exit;
}
