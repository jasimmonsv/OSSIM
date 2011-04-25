<?php
/***************************************************************************
*
*   Copyright (c) 2007-2010 AlienVault
*   All rights reserved.
*
****************************************************************************/

require_once "customize_common.php";
require_once "classes/Security.inc";
require_once "classes/Session.inc";
require_once "ossim_db.inc";
require_once "ossim_conf.inc";
Session::useractive("../session/login.php");

$info_error    = null;
$error         = false;
$parameters    = array();

$conf          = $GLOBALS["CONF"];
$version       = $conf->get_conf("ossim_server_version", FALSE);

$opensource    = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo          = (preg_match("/.*demo.*/i",$version))  ? true : false;

$from_menu     = ( GET('smenu')== "Customize" || $_SESSION["menu_sopc"] == "Customize") ? true : false;

$current_user  = Session::get_session_user();

if ( $opensource || !Session::am_i_admin() ) 
{
	ossim_set_error(_("You don't have permissions to see this page"));
	ossim_error();
	exit();
}

$step               = ( !empty($_GET['step']) ) ? GET('step') : 1;
$customize_wizard   = (int)$conf->get_conf("customize_wizard", FALSE);

$display_class = "customize_hide";



ossim_valid($step, OSS_DIGIT, 'illegal:' . _("Step"));

if ( ossim_error() ) {
	header('Location: customize.php?step=1');
}

$db     = new ossim_db();
$dbconn = $db->connect();

// Check parameters

if ( isset($_POST['save']) && !empty($_POST['save']) )
{
	switch ($step)
	{
		case "1":
		
			$username     = $parameters['username']     = POST('username');
			$current_pass = $parameters['current_pass'] = base64_decode(POST('current_pass'));		
			$cw_pass1     = $parameters['cw_pass1']     = base64_decode(POST('cw_pass1'));		
			$cw_pass2     = $parameters['cw_pass2']     = base64_decode(POST('cw_pass2'));		
			$s_log        = $parameters['s_log']        = POST('s_log');		
			$email        = $parameters['email']        = POST('email');	
			
							
			$validate  = array (
					"username"      => array("validation" => "OSS_USER"                 ,"e_message" => 'illegal:' . _("User name")),
					"current_pass"  => array("validation" => "OSS_ALPHA, OSS_PUNC_EXT"  ,"e_message" => 'illegal:' . _("Current Password")),
					"cw_pass1"      => array("validation" => "OSS_ALPHA, OSS_PUNC_EXT"  ,"e_message" => 'illegal:' . _("Password")),
					"cw_pass2"      => array("validation" => "OSS_ALPHA, OSS_PUNC_EXT"  ,"e_message" => 'illegal:' . _("Rewrite Password")),
					"s_log"         => array("validation" => "OSS_IP_ADDR"              ,"e_message" => 'illegal:' . _("Send Logs")),
					"email"         => array("validation" => "OSS_MAIL_ADDR"            ,"e_message" => 'illegal:' . _("Email"))
			);
			
		break;

		case "2":
			
			$backgroundTitle      = $parameters['backgroundTitle']     = POST('backgroundTitle');
			$colorTitle           = $parameters['colorTitle']          = POST('colorTitle');	
			$backgroundSubtitle   = $parameters['backgroundSubtitle']  = POST('backgroundSubtitle');	
			$colorSubtitle        = $parameters['colorSubtitle']       = POST('colorSubtitle');	
			
			$validate  = array (
				"backgroundTitle"      => array("validation" => "OSS_ALPHA,'#'"  ,"e_message" => 'illegal:' . _("Report Layout - Title Background")),
				"colorTitle"           => array("validation" => "OSS_ALPHA,'#'"  ,"e_message" => 'illegal:' . _("Report Layout - Color Title")),
				"backgroundSubtitle"   => array("validation" => "OSS_ALPHA,'#'"  ,"e_message" => 'illegal:' . _("Report Layout - Subtitle Background")),
				"colorSubtitle"        => array("validation" => "OSS_ALPHA,'#'"  ,"e_message" => 'illegal:' . _("Report Layout - Color subtitle Background")),
			);

		break;
				
	}
				
	
	foreach ($parameters as $k => $v )
	{
		eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
	
		if ( ossim_error() )
		{
			$info_error[] = ossim_get_error();
			ossim_clean_error();
			$error         = true;
			$display_class = "customize_show";
			$status_class  = "ossim_error";
		}
	}
	
	
	//Extra check parameters
	
	if ( $step == 1 )
	{
		//Check Password
		
		$sql          = 'SELECT * FROM users WHERE login="'.$current_user.'" AND pass="'.md5($current_pass).'" AND enabled=1';
		$result       = $dbconn->Execute($sql);
		
		if($result->EOF)
		{
			$info_error[]  = _("Error in the 'Current password' field (Current password does not match)");
			$error         = true;
			$display_class = "customize_show";
			$status_class  = "ossim_error";
		}
			
		// check username
		if( $username != $current_user )
		{
			$sql = 'SELECT login FROM users WHERE login="'.$username.'"';
			$result = $dbconn->Execute($sql);
			if(!$result->EOF)
			{
				$info_error[]  = _("Error in the 'User name' field (User name already in use)");
				$error         = true;
				$display_class = "customize_show";
				$status_class  = "ossim_error";
			}
		}
		
		
		//Modify pass
		
		if ( $error == false)
		{
			if( $cw_pass1 == $cw_pass2 )
			{
				$res = checkpass($dbconn, $current_pass, $cw_pass1, $cw_pass2, $username);
												
				if ( $res !== true)
				{
					$error         = true;
					$display_class = "customize_show";
					$status_class  = "ossim_error";
					
					if ( is_array ($res) && !empty($res) )
						$info_error   = ( is_array($info_error) ) ? array_merge($info_error, $res) : $res;
					else
						$info_error[] = _("Unknown error to check passwords");
				}
				else
				{
					$result = Acl::changepass($dbconn, $username, $cw_pass1, $current_pass);
				
					if( $result<=0 )
					{
						$info_error[]  = _("Error in the 'Current password' field (Current password does not match)");
						$error         = true;
						$display_class = "customize_show";
						$status_class  = "ossim_error";
					}
				
				}
			}
			else
			{
				$info_error[]  = _("Error in 'New password' and 'Rewrite passwords' fields (Passwords mismatches)");
				$error         = true;
				$display_class = "customize_show";
				$status_class  = "ossim_error";
			}
		}
		
	}
		
	
	if ( $error == false )
	{
		
		switch ($step)
		{
			case "1":
				
				// Save in the session
				$_SESSION['customize']['step1']['ok']       = true;
				$_SESSION['customize']['username']          = $username;
				$_SESSION['customize']['s_log']             = $s_log;
				$_SESSION['customize']['email']             = $email;
				
				// Send logos
				$sql    = 'UPDATE config SET value="'.$s_log.'" WHERE conf="customize_send_logs"';
				$result = $dbconn->Execute($sql);
				
				// Email
				$sql    = 'UPDATE users SET email="'.$email.'",first_login=0 WHERE login="'.$current_user.'"';
				$result = $dbconn->Execute($sql);
							
			break;
			
			case "2":
			
				// Save in the session
				$_SESSION['customize']['step2']['ok']        = true;
				$_SESSION['customize']['backgroundTitle']    = $backgroundTitle;
				$_SESSION['customize']['colorTitle']         = $colorTitle;
				$_SESSION['customize']['backgroundSubtitle'] = $backgroundSubtitle;
				$_SESSION['customize']['colorSubtitle']      = $colorSubtitle;
				
				$sql    = 'UPDATE config SET value="'.$backgroundTitle.'" WHERE conf="customize_title_background_color"';
				$result = $dbconn->Execute($sql);
				
				$sql    = 'UPDATE config SET value="'.$colorTitle.'" WHERE conf="customize_title_foreground_color"';
				$result = $dbconn->Execute($sql);
				
				
				$sql    = 'UPDATE config SET value="'.$backgroundSubtitle.'" WHERE conf="customize_subtitle_background_color"';
				$result = $dbconn->Execute($sql);
									
				$sql    = 'UPDATE config SET value="'.$colorSubtitle.'" WHERE conf="customize_subtitle_foreground_color"';
				$result = $dbconn->Execute($sql);
			
			break;
			
			case "3":
			
				$sql    = 'UPDATE config SET value="0" WHERE conf="customize_wizard"';
				$result = $dbconn->Execute($sql);
				unset($_SESSION['customize']);
				
				if ( $from_menu == false )
					header("Location: ../index.php");
				else
					echo "<script type='text/javascript'>top.topmenu.location = '../top.php';</script>";
					exit();
			break;
			
			default:
				unset($_SESSION['customize']);
				header("Location: customize.php?step=1");
				
		
		}
		
		$step = $step + 1;
		
	}
	else
	{
		$errors_txt = implode("</div style='padding-top:3px;'><div>", $info_error);
	}
			
}
else
{
	if ( empty($_SESSION['customize']['username'])  )
	{
		$username                          = $current_user;
		$_SESSION['customize']['username'] = $username;
	}
	
	if ( empty($_SESSION['customize']['s_log'])  )
	{
		$s_log                          = $conf->get_conf("customize_send_logs", FALSE);
		$_SESSION['customize']['s_log'] = $s_log;
	}
	
	if ( empty($_SESSION['customize']['email'])  )
	{
		$me                             = Session::get_me($dbconn);
		$email                          = $me->get_email();
		$_SESSION['customize']['email'] = $email;
	}
	
	if ( empty($_SESSION['customize']['backgroundTitle'])  )
	{
		$backgroundTitle                          = $conf->get_conf("customize_title_background_color", FALSE);
		$_SESSION['customize']['backgroundTitle'] = $backgroundTitle;
	}
	
	if ( empty($_SESSION['customize']['colorTitle'])  )
	{
		$colorTitle                          = $conf->get_conf("customize_title_foreground_color", FALSE);
		$_SESSION['customize']['colorTitle'] = $colorTitle;
	}
	
	if ( empty($_SESSION['customize']['backgroundSubtitle'])  )
	{
		$backgroundSubtitle                          = $conf->get_conf("customize_subtitle_background_color", FALSE);
		$_SESSION['customize']['backgroundSubtitle'] = $backgroundSubtitle;
	}
	
	if ( empty($_SESSION['customize']['colorSubtitle'])  )
	{
		$colorSubtitle                          = $conf->get_conf("customize_subtitle_foreground_color", FALSE);
		$_SESSION['customize']['colorSubtitle'] = $colorSubtitle;
	}
	
}

$username           = $_SESSION['customize']['username'];
$s_log              = $_SESSION['customize']['s_log'];
$email              = $_SESSION['customize']['email'];
$backgroundTitle    = $_SESSION['customize']['backgroundTitle'];
$colorTitle         = $_SESSION['customize']['colorTitle'];
$backgroundSubtitle = $_SESSION['customize']['backgroundSubtitle'];
$colorSubtitle      = $_SESSION['customize']['colorSubtitle'];

$db->close($dbconn);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> AlienVault Unified SIEM. <?php echo gettext("Customization Wizard"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<?php
	// Javascript
	switch($step)
	{
		case 1:
	?>
    <script type="text/javascript" src="../js/greybox.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
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
	}
	?>
	<script type='text/javascript'>
		
		<?php 
			switch($step)
			{
			
				case 1:
		?>
					function GB_hide() { return; }
					
						function validate(ip) {
							GB_show("<?php echo _("Validate IP. Plugin Detection & Configuration")?>",'detect.php?ip='+ip,'80%','70%');
						}
						
						$(document).ready(function(){
							$('#cw_pass1').pstrength();
							
							$('#saveButton').bind('click', function() {
								
								var cw_pass1     = $('#cw_pass1').val();
								var cw_pass2     = $('#cw_pass2').val();
								var current_pass = $('#current_pass').val();
								
								if ( cw_pass1 !='' )
									$('#cw_pass1').val($.base64.encode(cw_pass1));
								
								if ( cw_pass2 != '')
									$('#cw_pass2').val($.base64.encode(cw_pass2));
								
								if ( current_pass != '' )
									$('#current_pass').val($.base64.encode(current_pass));
								
							});
							
							$(".scriptinfo").simpletip({
								position: 'right',
								fixed: true,
								boundryCheck: false,
								content: '<?php echo _("Write the Public ip adress or network, from which the system is authorized to receive logs, for example: 193.148.29.99 for a single IP, or 193.148.29.99/24")?>'
							});
							
						});
				<?php	
				break;
			
				case 2:
				?>
					function ajaxFileUpload(num) 
					{
						
						$('#downimage'+num).html("<img src='../pixmaps/loading.gif' width='16'>&nbsp;<?php echo _("Uploading file") ?>...");
						
						$.ajaxFileUpload (
							{
								url:'customize_logos.php?imgfile='+num,
								secureuri:false,
								fileElementId:'fileToUpload'+num,
								dataType: 'json',
								success: function (data, status) 
								{
									if(typeof(data.error) != 'undefined')
									{
										if(data.error != '') 
										{
											$('#downimage'+num).html("<div style='color:red'>"+data.error+"</div>");
										}
										else
										{
											if ( data.msg != '')
											{
												var rand = Math.floor(Math.random()*1001);
												var w    = (num == 3) ? " width='700'" : "";
												$('#downimage'+num).html("<img src='../tmp/headers/"+data.msg+"?d="+rand+"' alt='Logo uploaded'"+w+"/>");
											}
											else
												$('#downimage'+num).html("<div style='color:red'><?php echo _("File was not uploaded")?></div>");
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
			
					function restoreOriginalStyle()
					{
						var backgroundTitle    ='<?php echo $conf->get_conf("customize_title_background_color", FALSE);?>';
						var txtTitle           ='<?php echo $conf->get_conf("customize_title_foreground_color", FALSE);?>';
						var backgroundSubtitle ='<?php echo $conf->get_conf("customize_subtitle_background_color", FALSE);?>';
						var txtSubtitle        ='<?php echo $conf->get_conf("customize_subtitle_foreground_color", FALSE);?>';
						var txtContent         ='#000000';
						
						$('#backgroundTitle div input').attr('value',backgroundTitle);
						$('#backgroundTitle div').attr('style','background-color: '+ backgroundTitle);
						
						$('#colorTitle div input').attr('value',txtTitle);
						$('#colorTitle div').attr('style','background-color: '+ txtTitle);
						
						$('#backgroundSubtitle div input').attr('value',backgroundSubtitle);
						$('#backgroundSubtitle div').attr('style','background-color: '+ backgroundSubtitle);
						
						$('#colorSubtitle div input').attr('value',txtSubtitle);
						$('#colorSubtitle div').attr('style','background-color: '+ txtSubtitle);
					}
					
					function createColorPicker(id, color)
					{
						$('#'+id).ColorPicker({
							color: color,
							
							onShow: function (colpkr) {
								$(colpkr).fadeIn(500);
								return false;
							},
							onHide: function (colpkr) {
								$(colpkr).fadeOut(500);
								return false;
							},
							onChange: function (hsb, hex, rgb) {
								$('#'+id+' div').css('backgroundColor', '#' + hex);
								$('#'+id+' div input').val('#' + hex);
							}
						});
					}
										
					$(document).ready(function(){
						createColorPicker('backgroundTitle', '<?php echo $backgroundTitle; ?>');
						createColorPicker('colorTitle', '<?php echo $colorTitle; ?>');
						createColorPicker('backgroundSubtitle', '<?php echo $backgroundSubtitle; ?>');
						createColorPicker('colorSubtitle', '<?php echo $colorSubtitle; ?>');
					});
				
				
			<?php
				break;
			
			}
		
		?>				
	</script>
	
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/customize.css"/>
</head>

<body>
	<?php if ($from_menu) include("../hmenu.php"); ?>
	
	
	
	
	<div id='container_center'>
		
		<?php if (!$from_menu) { ?>
		<p align="center"><img src="../pixmaps/customization_logo.png" border="0"/></p>
		<p align="center" class="title"><?php echo _("Customization Wizard") ?></p>
		<? } ?>
		
		<div class='<?php echo $display_class?>'>
			<div class='<?php echo $status_class?>'>
				<div class='customize_errors'><div><?php echo $errors_txt;?></div></div>
			</div>
		</div>
		
		<form method="post" name="form" id="form" action="customize.php?step=<?php echo $step?>">
			
			<table id='tab_menu'>
				<tr>
					<td id='cw_mcontainer'>
						<ul class='cw_tabs'>
						
							<?php
								$active_1 = ( $step == 1 ) ? "class='active'" : "";
								$active_2 = ( $step == 2 ) ? "class='active'" : "";
								$active_3 = ( $step == 3 ) ? "class='active'" : "";
								
								$link2   = ( $_SESSION['customize']['step1']['ok'] || $customize_wizard === 0 ) ? "<a href='customize.php?step=2' id='link_tab2'>"._("Step 2: Customization Logos")."</a>" : "<span>"._("Step 2: Customization Logos")."</span>";
								$link3   = ( $_SESSION['customize']['step2']['ok'] ) ? "<a href='customize.php?step=3' id='link_tab3'>"._("Step 3")."</a>" : "<span>"._("Step 3")."</span>";
							?>
						
						
							<li id='litem_tab1' <?php echo $active_1;?>>
								<a href="customize.php?step=1" id='link_tab1'><?php echo _("Step 1: Basic Data"); ?></a>
							</li>
							<li id='litem_tab2' <?php echo $active_2;?>>
								<?php echo $link2;?>
							</li>
							<li id='litem_tab3' <?php echo $active_3;?>>
								<?php echo $link3;?>
							</li>
						</ul>
					</td>
				</tr>
			</table>
			
			
			<table id='tab_container' class='f'>
			
				<tr>
					<td>
					<?php if($step==1){ ?>
						<div id='tab1' class='generic_tab tab_content'>
							<div class='cont_customize'>
								<table width='100%'>
									<tr>
										<td class='c_label'><?php echo _('User name'); ?>:</td>
										<td><input type="text" name="username" id="username" value="<?php echo $username; ?>" tabindex='1'/></td>
										<td class='c_label'><?php echo _('New Password'); ?>:</td>
										<td>
											<input type="password" name="cw_pass1"  id="cw_pass1" value="" tabindex='3'/>
										</td>
									</tr>
									
									<tr>
										<td class='c_label'><?php echo _('Current Password'); ?>:</td>
										<td>
											<input type="password" name="current_pass" id="current_pass" value="" tabindex='2'/>
										</td>
										<td class='c_label'><?php echo _('Rewrite Password'); ?>:</td>
										<td>
											<input type="password" name="cw_pass2"  id="cw_pass2" value="" tabindex='4'/>
										</td>
									</tr>
									
									<tr>
										<td class='c_label'><?php echo _('Email'); ?>:</td>
										<td colspan='3' class='left'><input type="text" name="email" id="email" value="<?php echo $email;?>" tabindex='5'/></td>
									</tr>
										
									<tr>
										<td class="noborder" colspan="4"></td>
									</tr>
									
									<tr>
										<td class='c_label'><?php echo _('Authorized Collection Sources'); ?>:</td>
										<td colspan='3' class='left'>
											<input type="text" name="s_log" id="s_log" value="<?php echo $s_log; ?>" tabindex='6'/>
											<span style="color:#808080; margin: 0px 3px 0px 10px;">xxx.xxx.xxx.xxx</span>
											<a class='scriptinfo' style='text-decoration:none'><img src="../pixmaps/greenhelp.png" border='0' align='absmiddle'/></a>
											<div class="tooltip fixed" style="display: none;"></div>
											<!-- <input type="button" class="lbutton" value="<?php echo _("Plugin Detection & Configuration")?>" onclick="validate($('#s_log').val())"> -->
										</td>
									</tr>
								</table>
							</div>
						</div>
					<?php 
					}
					else if($step==2)
					{ 
					
						$rand  = rand(1, 1000); 
					?>
						<div id='tab2' class='generic_tab tab_content'>
							<div class='cont_customize'>
								<table class="transparent" width="100%">
									<tr>
										<th style="width: 310px"><?php echo _("Home login Logo") ?> [300x60px]</th>
										<td class="nobborder"></td>
										<th><?php echo _("Top header Logo") ?> [210x42px]</th>
									</tr>
									
									<tr>
										<td class="center border_c_img" style='width:310px;'>
											<input type="hidden" name="imgfile1" id="imgfile1" value=""/>
											<div id="downimage1" class='cw_cont_img'>
											<?php
												if (file_exists("../tmp/headers/_login_logo.png")) 
													$img = "<img src='../tmp/headers/_login_logo.png' border='0' width='300' height='60'/>";
												else
												{
													$path  = "../pixmaps/ossim";
													$path .= ( $demo == true ) ? "_siemdemo.png" : "_siem.png";
													$path .= "?d=$rand";
													$img   = "<img src='$path' style='border:1px solid #EEEEEE'/>";
												}
												
												echo $img;
											?>
											</div>
											
											<input type="file" name="fileToUpload1" id="fileToUpload1"/>
											<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(1);" value="<?php echo _('Upload');?>" /><br/><br/>
										</td>
										
										<td class="nobborder" style="width: 2px;"></td>
										
										<td class="center border_c_img" style='width:310px;'>
											<input type="hidden" name="imgfile2" id="imgfile2" value=""/>
											<div id="downimage2" class='cw_cont_img'>
												<?php 
													if (file_exists("../tmp/headers/_header_logo.png")) 
														echo "<img src='../tmp/headers/_header_logo.png?d=$rand' border='0' width='210' height='42'/>";
													else
														echo  "<img src='../pixmaps/top/logo_siem.png?d=$rand' style='border:1px solid #EEEEEE'/>";
												?>
											</div>
											<input type="file" name="fileToUpload2" id="fileToUpload2"/>
											<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(2);" value="<?php echo _('Upload');?>"/>
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
										<td class='border_c_img'>
											<table width="100%" class="noborder">
												<tr>
													<td class='c_label_2'><?php echo _("Background Color")?></td>
													<td class='c_label_2'><?php echo _("Foreground Color")?></td>
												</tr>
												
												<tr>
													<td class="nobborder">
														<div id="backgroundTitle" class="colorSelector" style="margin: 0 auto;">
															<div style="background-color: <?php echo $backgroundTitle;?>">
																<input type="hidden" name="backgroundTitle" value="<?php echo $backgroundTitle;?>"/>
															</div>
														</div>
															  
													</td>
													<td class="nobborder">
														<div id="colorTitle" class="colorSelector" style="margin: 0 auto;">
															<div style="background-color: <?php echo $colorTitle?>">
																<input type="hidden" name="colorTitle" value="<?php echo $colorTitle?>">
															</div>
														</div>
															
													</td>
												 </tr>
											</table>
										</td>
									
										<td class="nobborder"></td>
									
										<td class='border_c_img'>
											<table width="100%" class="noborder">
												<tr>
													<td class='c_label_2'><?php echo _("Background Color")?></td>
													<td class='c_label_2'><?php echo _("Foreground Color")?></td>
												</tr>
												
												<tr>
													<td class="nobborder">
														<div id="backgroundSubtitle" class="colorSelector" style="margin: 0 auto;">
															<div style="background-color: <?php echo $backgroundSubtitle?>;">
																<input type="hidden" name="backgroundSubtitle" value="<?php echo $backgroundSubtitle?>">
															</div>
														</div>
													</td>
													
													<td class="nobborder">
														<div id="colorSubtitle" class="colorSelector" style="margin: 0 auto;">
															<div style="background-color: <?php echo $colorSubtitle?>;">
															  <input type="hidden" name="colorSubtitle" value="<?php echo $colorSubtitle?>">
															</div>
														</div>
													</td>
												</tr>
											</table>
										</td>
									</tr>
									
									<tr>
										<td colspan="3" style="margin:0!important;padding:20px 0px 10px 0px!important;">
											<input id="btn_3" class="button" type="lbutton" value="<?php echo _('Restore Original')?>" onclick="javascript:restoreOriginalStyle();"/>
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
										<td colspan="3" class="center border_c_img">
											<input type="hidden" name="imgfile3" id="imgfile3" value=""/>
											<div id="downimage3" class="cw_cont_img">
												<?php echo "<img src='../tmp/headers/default.png?d=$rand' width='700' style='border:1px solid #EEEEEE'/>" ?>
											</div>
											<input type="file" name="fileToUpload3" id="fileToUpload3">
											<input type="button" class="lbutton" id="buttonUpload" onclick="return ajaxFileUpload(3);" value="<?php echo _('Upload');?>" />
											<br/><br/>
										</td>
									</tr>
									<tr>
										<td colspan="3" style="height: 10px"></td>
									</tr>
								</table>
							</div>
						</div>
					<?php 
					}
					else if( $step == 3)
					{ 
					?>
						<div id='tab3' class='generic_tab tab_content'>
							<div class='cont_customize'>
								<table width='100%'>
									<tr>
										<?php $url=getProtocolUrl(); ?>
										<td valign="top" style="padding:0px 10px 10px 0px">
											<p style="text-align:justify; padding:0px 25px;font-size:13px; margin:0px 10px 0px 10px;">
												<?php echo _('Thank you!').'<br>'._('Your system is ready, please go to the');?> 
												<a target="_blank"><strong><?php echo _('testing');?></strong></a> 
												<?php echo _('section to simulate events for an initial test, or to your Collection configuration documentation to send real logs from your network.');?>
												<br/><br/><?php echo _('Insert this HTML code in your home page to allow your customers to login to their multitenanted MSSP service, a login form like the one below will appear')?>:
											</p>
										</td>
										<td width="30" align="right">
											<pre>&lt;iframe src="<?php echo $url; ?>/ossim/session/login.php?embed=true" width="400" height="250" scrolling="auto" frameborder="0" style="background:transparent;"&gt;&lt;/iframe&gt;</pre>
										</td>
									</tr>
									<tr>
										<td colspan="2" style="padding-top:10px">
											<iframe src="login.php?embed=true" width="400" height="250" scrolling="auto" frameborder="0" style='background:transparent;'></iframe>
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
						<input type='submit' id='saveButton' class='button' name='save' value='<?php echo _("Save &amp; Next")?>'/>
					</td>
				</tr>
			
			</table>
		</form>
	</div>
</body>
</html>