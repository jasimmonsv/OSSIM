<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2011 AlienVault
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
* - errorMsg
* - cleanError
* Classes list:
*/
// General Functions
function getProtocolUrl(){
        if(empty($_SERVER["HTTPS"])){
			return 'http://'.$_SERVER['SERVER_ADDR'];
        }else{
			return 'https://'.$_SERVER['SERVER_ADDR'];
        }
}

function errorMsg($msg_error,$step=1){
	unset($_SESSION['customize']['msg_error']);
	$_SESSION['customize']['msg_error']=$msg_error;
	header('Location: customize.php?step='.$step);
}

function printError($value){
	if(strstr($value, "<div style='font-family:Arial, Helvetica, sans-serif;")!==false){
		$html=$value;
	}else{
		$html="<div style='font-family:Arial, Helvetica, sans-serif; 
					font-size:13px; border: 1px solid; 
					width: 90%; 
					margin: 10px auto; 
					padding:15px 10px 15px 50px;  
					background-repeat: no-repeat; 
					background-position: 10px center; color: #D8000C; 
					background-color: #FFBABA; 
					background-image: url(\"../pixmaps/ossim_error.png\");'>
					<b>Error!</b><br/>".$value."</div><br/>";
	}
	
	return $html;
}

function cleanError(){
	unset($_SESSION['customize']['msg_error']);
}
// STEP 2 Functions
function check_size($num,$width,$height) {
	if ($num == 1) {
		return ($width < 310 && $height < 70 && $width > 290 && $height > 50) ? 1 : 0;
	}
	if ($num == 2) {
		return ($width < 220 && $height < 50 && $width > 200 && $height > 35) ? 1 : 0;
	}
	if ($num == 3) {
		return ($width < 1250 && $height < 135 && $width > 1230 && $height > 120) ? 1 : 0;
	}
	return 0;
}
function upload($num) {
	$error = "";
	$msg = "";
	$w = "";
	$fileElementName = 'fileToUpload'.$num;
	if(!empty($_FILES[$fileElementName]['error']))
	{
		switch($_FILES[$fileElementName]['error'])
		{
			case '1':
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case '2':
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case '3':
				$error = 'The uploaded file was only partially uploaded';
				break;
			case '4':
				$error = 'No file was uploaded.';
				break;
			case '6':
				$error = 'Missing a temporary folder';
				break;
			case '7':
				$error = 'Failed to write file to disk';
				break;
			case '8':
				$error = 'File upload stopped by extension';
				break;
			case '999':
			default:
				$error = 'No error code avaiable';
		}
	} elseif (empty($_FILES['fileToUpload'.$num]['tmp_name']) || $_FILES['fileToUpload'.$num]['tmp_name'] == 'none') {
		$error = 'No file was uploaded..';
	} elseif ($num == 3 && !preg_match("/\.(png)$/i",$_FILES['fileToUpload'.$num]['name'])) {
		$error = "The report header must be a valid <b>png</b> file";
	} elseif (!preg_match("/\.(jpg|jpeg|gif|png)$/i",$_FILES['fileToUpload'.$num]['name'])) {
		$error = "The logo must be a valid <b>jpeg</b>, <b>gif</b> or <b>png</b> file";
	} elseif (preg_match("/\.(php|phtml|html|js|shtml|pl|py)/",$_FILES['fileToUpload'.$num]['name'])) {
		$error = "The logo must be a valid <b>jpeg</b>, <b>gif</b> or <b>png</b> file";
	} else {
		list($width, $height, $type, $attr) = getimagesize($_FILES['fileToUpload'.$num]['tmp_name']);
		if (!check_size($num,$width,$height)) {
			$error = "The image size is not correct";
		} else {
			$filename = $_FILES['fileToUpload'.$num]['name'];
			$filesize = @filesize($_FILES['fileToUpload'.$num]['tmp_name']);
			if ($filename != "" && $filesize > 0 && ($type == 2 || $type == 1 || $type == 3) && check_size($num,$width,$height)) {
				if ($num == "1") {
					$tmpfname = "../tmp/headers/_login_logo.png";
				} elseif ($num == "2") {
					$tmpfname = "../tmp/headers/_header_logo.png";
				} elseif ($num == "3") {
					$tmpfname = "../tmp/headers/default.png";
					if (!file_exists("../tmp/headers/default_copy.png")) {
						@copy("../tmp/headers/default.png", "../tmp/headers/default_copy.png");
					}
				}
				@copy($_FILES['fileToUpload'.$num]['tmp_name'], $tmpfname);
				$msg = str_replace("../tmp/headers/","",$tmpfname);
			} else {
				$error = "Error in the image format file";
			}
		}
		//for security reason, we force to remove all uploaded file
		//@unlink($_FILES['fileToUpload']);
	}
	echo "{";
	echo				"error: '" . $error . "',\n";
	echo				"msg: '" . $msg . "'\n";
	echo "}";
}
//
require_once "classes/Security.inc";
require_once "classes/Session.inc";
Session::useractive("../session/login.php");
require_once "ossim_db.inc";
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;
if ($_GET['imgfile'] != "" && preg_match("/^\d+$/",$_GET['imgfile'])) {
	upload($_GET['imgfile']);
	exit;
}
//
$msg_error=array('');
if ($opensource || $_SESSION['_user'] != ACL_DEFAULT_OSSIM_ADMIN) {
	$msg_error[]="You're not allowed to see this page";
	errorMsg($msg_error);
}
// comprobar datos iniciales
$step = GET('step');
ossim_valid($step, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Step"));
$save = POST('save');
ossim_valid($save, 'true', OSS_NULLABLE, 'illegal:' . _("Save &amp; Next"));
$stepOld = POST('stepOld');
ossim_valid($stepOld, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Step Old"));
if (ossim_error()) {
	$msg_error[]=ossim_error();
	errorMsg($msg_error);
}
// inicializar variables 1
if(empty($_SESSION['customize'])){
	unset($_SESSION['customize']);
}
if(empty($step)){
	$step=1;
}
// Salvamos datos
if($save){
	// clean error
	cleanError();
	/* connect to db */
	$db   = new ossim_db();
	$conn = $db->connect();

	switch($stepOld){
		case 1:
			$username=POST('username');
			ossim_valid($username, OSS_USER, 'illegal:' . _("User name"));
			$current_pass=base64_decode(POST('current_pass'));
			ossim_valid($current_pass, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Current Password"));
			$pass1=base64_decode(POST('pass1'));
			ossim_valid($pass1, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Password"));
			$pass2=base64_decode(POST('pass2'));
			ossim_valid($pass2, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Rewrite Password"));
			$s_log=POST('s_log');
			ossim_valid($s_log, OSS_IP_ADDRCIDR, 'illegal:' . _("Send Logs"));
			$email=POST('email');
			ossim_valid($email, OSS_MAIL_ADDR, 'illegal:' . _("Email"));
			if (ossim_error()) {
				$msg_error[]=ossim_error();
				errorMsg($msg_error,$stepOld);
			}
			// check user and pass
			$sql = 'SELECT * FROM users WHERE login="'.Session::get_session_user().'" AND pass="'.md5($current_pass).'" AND enabled=1';
			$result = $conn->Execute($sql);
			if($result->EOF){
				// error password no correcto
				$msg_error[]='Current password does not match';
				errorMsg($msg_error,$stepOld);
			}else{
				// check username
				if($username!=Session::get_session_user()){
					$sql = 'SELECT login FROM users WHERE login="'.$username.'"';
					$result = $conn->Execute($sql);
					if(!$result->EOF){
						// error user exist
						$msg_error[]='User name in use';
						errorMsg($msg_error,$stepOld);
					}else{
						$_SESSION['customize']['step1']['username']=$username;
						// modificar usuario
						// crear metodo en ACL y Session, que se llame clonar, obtiene los datos del user y llama al metodo insert de esas clases
						//$sql = 'UPDATE users SET login="'.$username.'" WHERE login="'.Session::get_session_user().'"';
						//$result = $conn->Execute($sql);
					}
				}
				// modificar password
				if($pass1==$pass2){
					$result = Acl::changepass($conn, $username, $pass1, $current_pass);
					if($result<=0){
						$msg_error[] = 'Current password does not match';
						errorMsg($msg_error,$stepOld);
					}
					// save in the session
					$_SESSION['customize']['step1']['pass1']=$pass1;
					$_SESSION['customize']['step1']['pass2']=$pass2;
				}else{
					// error user exist
					$msg_error[]='Passwords mismatches';
					errorMsg($msg_error,$stepOld);
				}
				// send longs
				$_SESSION['customize']['step1']['s_log']=$s_log;
				$sql = 'UPDATE config SET value="'.$s_log.'" WHERE conf="customize_send_logs"';
				$result = $conn->Execute($sql);
				// email
				$sql = 'UPDATE users SET email="'.$email.'",first_login=0 WHERE login="'.Session::get_session_user().'"';
				$result = $conn->Execute($sql);
				$_SESSION['customize']['step1']['email']=$email;
				// OK
				$_SESSION['customize']['step1']['ok']=true;
			}
			break;
		case 2:
			$backgroundTitle=POST('backgroundTitle');
			ossim_valid($backgroundTitle, OSS_ALPHA,'#', 'illegal:' . _("Report Layout - Title Backg."));
			$colorTitle=POST('colorTitle');
			ossim_valid($colorTitle, OSS_ALPHA,'#', 'illegal:' . _("Report Layout - Title Backg."));
			$backgroundSubtitle=POST('backgroundSubtitle');
			ossim_valid($backgroundSubtitle, OSS_ALPHA,'#', 'illegal:' . _("Report Layout - Title Backg."));
			$colorSubtitle=POST('colorSubtitle');
			ossim_valid($colorSubtitle, OSS_ALPHA,'#', 'illegal:' . _("Report Layout - Title Backg."));
			if (ossim_error()) {
				$msg_error[]=ossim_error();
				errorMsg($msg_error,$stepOld);
			}
			//
			$_SESSION['customize']['step2']['backgroundTitle']=$backgroundTitle;
			$sql = 'UPDATE config SET value="'.$backgroundTitle.'" WHERE conf="customize_title_background_color"';
			$result = $conn->Execute($sql);
			//
			$_SESSION['customize']['step2']['colorTitle']=$colorTitle;
			$sql = 'UPDATE config SET value="'.$colorTitle.'" WHERE conf="customize_title_foreground_color"';
			$result = $conn->Execute($sql);
			//
			$_SESSION['customize']['step2']['backgroundSubtitle']=$backgroundSubtitle;
			$sql = 'UPDATE config SET value="'.$backgroundSubtitle.'" WHERE conf="customize_subtitle_background_color"';
			$result = $conn->Execute($sql);
			//
			$_SESSION['customize']['step2']['colorSubtitle']=$colorSubtitle;
			$sql = 'UPDATE config SET value="'.$colorSubtitle.'" WHERE conf="customize_subtitle_foreground_color"';
			$result = $conn->Execute($sql);
			//
			// OK
			$_SESSION['customize']['step2']['ok']=true;
			break;
		case 3:
			$sql = 'UPDATE config SET value="1" WHERE conf="customize_wizard"';
			$result = $conn->Execute($sql);
			unset($_SESSION['customize']);
			header("Location: ../index.php");
			break;
		default:
			$step=1;
			break;
		}
	$db->close($conn);
}

// incializamos variables 2
switch($step){
	case 1:
		if(empty($_SESSION['customize']['step1']['username'])){
			$username=Session::get_session_user();
		}else{
			$username=$_SESSION['customize']['step1']['username'];
		}
		//
		if(empty($_SESSION['customize']['step1']['pass1'])){
			$pass1='';
		}else{
			$pass1=$_SESSION['customize']['step1']['pass1'];
		}
		//
		if(empty($_SESSION['customize']['step1']['pass2'])){
			$pass2='';
		}else{
			$pass2=$_SESSION['customize']['step1']['pass2'];
		}
		//
		if(empty($_SESSION['customize']['step1']['s_log'])){
			$s_log='';
		}else{
			$s_log=$_SESSION['customize']['step1']['s_log'];
		}
		//
		if(empty($_SESSION['customize']['step1']['email'])){
			$email='';
		}else{
			$email=$_SESSION['customize']['step1']['email'];
		}
		break;
	case 2:
		if(empty($_SESSION['customize']['step2']['backgroundTitle'])){
			$backgroundTitle=$conf->get_conf("customize_title_background_color", FALSE);
		}else{
			$backgroundTitle=$_SESSION['customize']['step2']['backgroundTitle'];
		}
		//
		if(empty($_SESSION['customize']['step2']['colorTitle'])){
			$colorTitle=$conf->get_conf("customize_title_foreground_color", FALSE);
		}else{
			$colorTitle=$_SESSION['customize']['step2']['colorTitle'];
		}
		//
		if(empty($_SESSION['customize']['step2']['backgroundSubtitle'])){
			$backgroundSubtitle=$conf->get_conf("customize_subtitle_background_color", FALSE);
		}else{
			$backgroundSubtitle=$_SESSION['customize']['step2']['backgroundSubtitle'];
		}
		//
		if(empty($_SESSION['customize']['step2']['colorSubtitle'])){
			$colorSubtitle=$conf->get_conf("customize_subtitle_foreground_color", FALSE);
		}else{
			$colorSubtitle=$_SESSION['customize']['step2']['colorSubtitle'];
		}
		break;
	case 3:
		break;
	default:
		$step=1;
		break;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> AlienVault Unified SIEM. <?php echo gettext("Customize Wizard"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<?php
		// Javascript
		switch($step){
			case 1:
	?>
	<script type="text/javascript" src="../js/jquery.pstrength.js"></script>
	<script type="text/javascript" src="../js/jquery.base64.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>
	<?php
				break;
			case 2:
	?>
	<script type="text/javascript" src="../js/ajaxfileupload.js"></script>
	<link rel="stylesheet" href="../style/colorpicker.css" type="text/css" />
	<script type="text/javascript" src="../js/jquery.colorpicker.js"></script>
	<?php
				break;
			default;
				break;
		}
	?>
	<script type='text/javascript'>
		<?php
		// Javascript insert
		switch($step){
			case 1:
	?>
		$(document).ready(function() {
			$('#pass1').pstrength();
			$('#saveButton').bind('click', function() {
				var pass1 = $('#pass1').val();
				var pass2 = $('#pass2').val();
				var current_pass = $('#current_pass').val();
				
				if ( pass1!=''){
					$('#pass1').val($.base64.encode(pass1));
				}
				if ( pass2!=''){
					$('#pass2').val($.base64.encode(pass2));
				}
				if ( current_pass!=''){
					$('#current_pass').val($.base64.encode(current_pass));
				}
			});
			$(".scriptinfo").simpletip({
				position: 'right',
				fixed: true,
				boundryCheck: false,
				content: 'Write the Public ip adress or network, from which the system is authorized to receive logs, for example: 193.148.29.99 for a single IP, or 193.148.29.99/24'
			});
		});
	<?php
				break;
			case 2:
	?>
		function ajaxFileUpload(num) {
			$('#downimage'+num).html("<img src='../pixmaps/loading.gif' width='16'>&nbsp;<?php echo _("Uploading file") ?>...");
			$.ajaxFileUpload (
				{
					url:'customize_logos.php?imgfile='+num,
					secureuri:false,
					fileElementId:'fileToUpload'+num,
					dataType: 'json',
					success: function (data, status) {
						if(typeof(data.error) != 'undefined') {
							if(data.error != '') {
								$('#downimage'+num).html(data.error);
							} else {
								var rand = Math.floor(Math.random()*1001);
								var w = (num == 3) ? " width='700'" : "";
								$('#downimage'+num).html("<img src='../tmp/headers/"+data.msg+"?d='"+rand+" alt='Logo uploaded'"+w+">");
							}
						}
					},
					error: function (data, status, e) {
						$('#downimage'+num).html(e);
					}
				}
			)
			
			return false;
		}
		function restoreOriginalStyle(){
            var backgroundTitle='<?php echo $conf->get_conf("customize_title_background_color", FALSE);?>';
            var txtTitle='<?php echo $conf->get_conf("customize_title_foreground_color", FALSE);?>';
            var backgroundSubtitle='<?php echo $conf->get_conf("customize_subtitle_background_color", FALSE);?>';
            var txtSubtitle='<?php echo $conf->get_conf("customize_subtitle_foreground_color", FALSE);?>';
            var txtContent='#000000';
			
            $('#backgroundTitle div input').attr('value',backgroundTitle);
            $('#backgroundTitle div').attr('style','background-color: '+backgroundTitle);
            
            $('#colorTitle div input').attr('value',txtTitle);
            $('#colorTitle div').attr('style','background-color: '+txtTitle);
            
            $('#backgroundSubtitle div input').attr('value',backgroundSubtitle);
            $('#backgroundSubtitle div').attr('style','background-color: '+backgroundSubtitle);
            
            $('#colorSubtitle div input').attr('value',txtSubtitle);
            $('#colorSubtitle div').attr('style','background-color: '+txtSubtitle);
        }
	<?php
				break;
			default;
				break;
		}
	?>						
	</script>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/customize.css"/>
</head>
<body>
	<div id='container_center'>
		<p alig="center"><img src="../pixmaps/customization_logo.png" border="0"></p>
		<p alig="center" class="title"><?php echo _("Customize Wizard") ?></p>
		<form method="post" name="form" id="form" action="customize.php?step=<?php echo $step+1;?>">
			<table id='tab_menu'>
				<tr>
					<td id='oss_mcontainer'>
						<ul class='oss_tabs'>
							<li id='litem_tab1' <?php if($step==1){?>class='active'<?php } ?>><a href="customize.php?step=1" id='link_tab1'><?php echo _("Step 1: Basic Data"); ?></a></li>
							<li id='litem_tab2' <?php if($step==2){?>class='active'<?php } ?>><?php if($_SESSION['customize']['step1']['ok']){ ?><a href="customize.php?step=2" id='link_tab2'><?php }else{ ?><span><?php } ?><?php echo _("Step 2: Customization Logos"); ?><?php if($_SESSION['customize']['step1']['ok']){ ?></a><?php }else{ ?></span><?php } ?></li>
							<li id='litem_tab3' <?php if($step==3){?>class='active'<?php } ?>><?php if($_SESSION['customize']['step2']['ok']){ ?><a href="customize.php?step=3" id='link_tab3'><?php }else{ ?><span><?php } ?><?php echo _("Step 3"); ?><?php if($_SESSION['customize']['step2']['ok']){ ?></a><?php }else{ ?></span><?php } ?></li>
						</ul>
					</td>
				</tr>
			</table>
			<table id='tab_container' class='oss_control'>
			<?php
				if(!empty($_SESSION['customize']['msg_error'])){
					foreach($_SESSION['customize']['msg_error'] as $value){
						if(!empty($value)){
			?>
				<tr>
					<td><?php echo printError($value); ?></td>
				</tr>
			<?php
						}
					}
					//cleanError();
				}
				?>
				<tr>
					<td>
					<?php if($step==1){ ?>
						<div id='tab1' class='generic_tab tab_content'>
							<div id='ossc_result' class='div_pre'>
								<table>
									<tr class="tr_l">
										<td><strong><?php echo _('User name'); ?>:</strong></td>
										<td><input type="text" name="username" id="username" value="<?php echo $username; ?>" /></td>
									</tr>
									<tr class="tr_r">
										<td><strong><?php echo _('Current Password'); ?>:</strong></td>
										<td><input type="password" name="current_pass" id="current_pass"/></td>
									</tr>
									<tr class="tr_l">
										<td><strong><?php echo _('New Password'); ?>:</strong></td>
										<td><input type="password" name="pass1" id="pass1" value="<?php echo $pass1; ?>" /></td>
									</tr>
									<tr class="tr_r">
										<td><strong><?php echo _('Rewrite Password'); ?>:</strong></td>
										<td><input type="password" name="pass2" id="pass2" value="<?php echo $pass2; ?>" /></td>
									</tr>
									<tr class="tr_l">
										<td><strong><?php echo _('Email'); ?>:</strong></td>
										<td><input type="text" name="email" id="email" value="<?php echo $email; ?>" /></td>
									</tr>
									<tr class="tr_none">
										<td class="noborder" colspan="2"></td>
									</tr>
									<tr class="tr_none">
										<td><strong><?php echo _('Authorized Collection Sources'); ?>:</strong></td>
										<td>
											<input type="text" name="s_log" id="s_log" value="<?php echo $s_log; ?>" /> <span style="color:#808080">xxx.xxx.xxx.xxx/xx</span>
											<a class='scriptinfo' style='text-decoration:none' href="javascript:;">
												<img src="../pixmaps/greenhelp.png" border='0' align='absmiddle'/>
												<div class="tooltip fixed" style="display: none;"></div>
											</a>
										</td>
									</tr>
								</table>
							</div>
						</div>
					<?php }else if($step==2){ ?>
						<div id='tab2' class='generic_tab tab_content'>
							<div id='ossc_result' class='div_pre_2'>
								<table class="transparent" width="100%">
									<tr>
										<th style="width: 310px"><?php echo _("Home login Logo") ?> [300x60px]</th>
										<td class="nobborder"></td>
										<th><?php echo _("Top header Logo") ?> [210x42px]</th>
									</tr>
									<tr>
										<td class="center nobborder" style="width:300px;border: solid 1px #CCCCCC">
											<input type="hidden" name="imgfile1" id="imgfile1" value="">
											<div id="downimage1" class="ne12" align="center">
												<?php if (file_exists("../tmp/headers/_login_logo.png")) { ?>
												<img src="../tmp/headers/_login_logo.png" border='0' width="300" height="60"></img>
												<?php } else { ?>
												<img src="../pixmaps/ossim<?php echo (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" style="border:1px solid #EEEEEE"></img>
												<?php } ?>
											</div>
											<input type="file" name="fileToUpload1" id="fileToUpload1">
											<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(1);" value="<?php echo _('Upload');?>" /><br><br>
										</td>
										<td class="nobborder" style="width: 2px;"></td>
										<td class="center nobborder" style="border: solid 1px #CCCCCC">
											<input type="hidden" name="imgfile2" id="imgfile2" value="">
											<div id="downimage2" class="ne12" align="center">
												<?php if (file_exists("../tmp/headers/_header_logo.png")) { ?>
												<img src="../tmp/headers/_header_logo.png" border='0' width="210" height="42"></img>
												<?php } else { ?>
												<img src="../pixmaps/top/logo_siem.png" style="border:1px solid #EEEEEE"></img>
												<?php } ?>
											</div>
											<input type="file" name="fileToUpload2" id="fileToUpload2">
											<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(2);" value="<?php echo _('Upload');?>" />
										</td>
									</tr>

								<tr>
									<td colspan="3" style="height: 20px"></td>
								</tr>
								<tr>
									<th><?php echo _("Report Layout - Title")?></th>
									<td class="nobborder"></td>
									<th><?php echo _("Report Layout - Subtitle")?></th>
								</tr>
								<tr>
									<td style="text-align:center;margin:0!important;padding:0!important;border: solid 1px #CCCCCC">
									  <table width="100%" class="noborder">
										<tr>
											<td><strong><?php echo _("Background Color")?></strong></td>
											<td><strong><?php echo _("Foreground Color")?></strong></td>
										</tr>
										 <tr>
											<td class="nobborder" style="margin:0!important;padding:0!important;">
												<div id="backgroundTitle" class="colorSelector" style="margin: 0 auto;">
													  <div style="background-color: <?php echo $backgroundTitle;?>">
														  <input type="hidden" name="backgroundTitle" value="<?php echo $backgroundTitle;?>">
													  </div>
												  </div>
													  <script type='text/javascript'>
														//<![CDATA[
														$('#backgroundTitle').ColorPicker({
															color: '<?php echo $backgroundTitle; ?>',
															//color: '#00ff00',
															onShow: function (colpkr) {
																	$(colpkr).fadeIn(500);
																	return false;
															},
															onHide: function (colpkr) {
																	$(colpkr).fadeOut(500);
																	return false;
															},
															onChange: function (hsb, hex, rgb) {
																	$('#backgroundTitle div').css('backgroundColor', '#' + hex);
																	$('#backgroundTitle div input').attr('value','#' + hex);
															}
													});
														//]]>
													  </script>
											</td>
											<td style="margin:0!important;padding:0!important;" class="nobborder">
												<div id="colorTitle" class="colorSelector" style="margin: 0 auto;">
													  <div style="background-color: <?php echo $colorTitle?>">
														  <input type="hidden" name="colorTitle" value="<?php echo $colorTitle?>">
													  </div>
												  </div>
													  <script type='text/javascript'>
														//<![CDATA[
														$('#colorTitle').ColorPicker({
															color: '<?php echo $colorTitle; ?>',
															onShow: function (colpkr) {
																	$(colpkr).fadeIn(500);
																	return false;
															},
															onHide: function (colpkr) {
																	$(colpkr).fadeOut(500);
																	return false;
															},
															onChange: function (hsb, hex, rgb) {
																	$('#colorTitle div').css('backgroundColor', '#' + hex);
																	$('#colorTitle div input').attr('value','#' + hex);
															}
													});
														//]]>
													</script>
											</td>
										 </tr>
									  </table>
									</td>
									<td class="nobborder"></td>
									<td style="text-align:center;margin:0!important;padding:0!important;border: solid 1px #CCCCCC">
									  <table width="100%" class="noborder">
										<tr>
											<td><strong><?php echo _("Background Color")?></strong></td>
											<td><strong><?php echo _("Foreground Color")?></strong></td>
										</tr>
										 <tr>
											<td class="nobborder" style="margin:0!important;padding:0!important;">
												<div id="backgroundSubtitle" class="colorSelector" style="margin: 0 auto;">
													  <div style="background-color: <?php echo $backgroundSubtitle?>;">
														  <input type="hidden" name="backgroundSubtitle" value="<?php echo $backgroundSubtitle?>">
													  </div>
												  </div>
													  <script type='text/javascript'>
														//<![CDATA[
														$('#backgroundSubtitle').ColorPicker({
															color: '<?php echo $backgroundSubtitle; ?>',
															onShow: function (colpkr) {
																	$(colpkr).fadeIn(500);
																	return false;
															},
															onHide: function (colpkr) {
																	$(colpkr).fadeOut(500);
																	return false;
															},
															onChange: function (hsb, hex, rgb) {
																	$('#backgroundSubtitle" div').css('backgroundColor', '#' + hex);
																	$('#backgroundSubtitle" div input').attr('value','#' + hex);
															}
													});
														//]]>
													  </script>
											</td>
											<td class="nobborder" style="margin:0!important;padding:0!important;">
												<div id="colorSubtitle" class="colorSelector" style="margin: 0 auto;">
													  <div style="background-color: <?php echo $colorSubtitle?>;">
														  <input type="hidden" name="colorSubtitle" value="<?php echo $colorSubtitle?>">
													  </div>
												  </div>
													  <script type='text/javascript'>
														//<![CDATA[
														$('#colorSubtitle').ColorPicker({
															color: '<?php echo $colorSubtitle; ?>',
															onShow: function (colpkr) {
																	$(colpkr).fadeIn(500);
																	return false;
															},
															onHide: function (colpkr) {
																	$(colpkr).fadeOut(500);
																	return false;
															},
															onChange: function (hsb, hex, rgb) {
																	$('#colorSubtitle div').css('backgroundColor', '#' + hex);
																	$('#colorSubtitle div input').attr('value','#' + hex);
															}
													});
														//]]>
													  </script>
											</td>
										 </tr>
									  </table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="margin:0!important;padding:20px 0px 10px 0px!important;">
										<input id="btn_3" class="lbutton" type="button" onclick="javascript:restoreOriginalStyle();" value="<?php echo _('Restore Original')?>" />
									</td>
								</tr>
								<tr>
									<td colspan="3" style="height: 10px"></td>
								</tr>
								<tr>
									<th colspan="3" style="vertical-align:top;text-align: center">
										<?php echo _("Report header Logo") ?> [1240x128px]
									</th>
								</tr>
								<tr>
									<td colspan="3" class="center nobborder" style="border: solid 1px #CCCCCC">
										<input type="hidden" name="imgfile3" id="imgfile3" value="">
										<div id="downimage3" class="ne12" align="center"><img src="../tmp/headers/default.png" width="700" style="border:1px solid #EEEEEE"></div>
										<input type="file" name="fileToUpload3" id="fileToUpload3">
										<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(3);" value="<?php echo _('Upload');?>" /><br><br>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="height: 10px"></td>
								</tr>
							</table>
							</div>
						</div>
					<?php }else if($step==3){ ?>
						<div id='tab2' class='generic_tab tab_content'>
							<div id='ossc_result' class='div_pre_3'>
								<table>
									<tr>
										<?php
											$url=getProtocolUrl();
										?>
										<td valign="top" style="padding-top:10px">
											<p style="text-align:justify;padding:0px 5px 0px 5px;font-size:13px"><?php echo _('Thank you!').'<br>'._('Your system is ready, please go to the');?> <a href="#" target="_blank"><strong><?php echo _('testing');?></strong></a> <?php echo _('section to simulate events for an initial test, or to your Collection configuration documentation to send real logs from your network.');?><br><br><?php echo _('Insert this HTML code in your home page to allow your customers to login to their multitenanted MSSP service, a login form like the one below will appear')?>:</p>
										</td>
										<td width="30" align="right"><pre>&lt;iframe src="<?php echo $url; ?>/ossim/session/login.php?embed=true" width="300" height="250" scrolling="auto" frameborder="0" transparency&gt;&lt;/iframe&gt;</pre></td>
									</tr>
									<tr>
									<td colspan="2" style="padding-top:10px">
											<iframe src="login.php?embed=true" width="300" height="250" scrolling="auto" frameborder="0" transparency></iframe>
									</td>
									</tr>
								</table>
							</div>
						</div>
					<?php } ?>
					</td>
				</tr>
				<tr>
					<td class="tdButton">
						<input type="hidden" name="stepOld" value="<?php echo $step; ?>" />
						<input type="hidden" name="save" value="true" />
						<input type="submit" id="saveButton" class="button" value="<?php echo _('Save &amp; Next'); ?>" />
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>