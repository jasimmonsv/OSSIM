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

 */
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
require_once "classes/Security.inc";
require_once "classes/Session.inc";
require_once "ossim_conf.inc";
$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo = (preg_match("/.*demo.*/i",$version)) ? true : false;

if ($_GET['imgfile'] != "" && preg_match("/^\d+$/",$_GET['imgfile'])) {
	upload($_GET['imgfile']);
	exit;
}
if ($opensource || $_SESSION['_user'] != ACL_DEFAULT_OSSIM_ADMIN) {
	echo _("You're not allowed to see this page");
	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("AlienVault - ".($opensource ? "Open Source SIEM" : ($demo ? "Professional SIEM Demo" : "Professional SIEM"))); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<META http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/ajaxfileupload.js"></script>
	<link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico"></link>
<script type="text/javascript">
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
</script>
</head>
<body>
<form>
<br/><br/><br/><br/><br/>
<table align="center" style="padding:1px;background-color:#f2f2f2;border-color:#AAAAAA;" class='nobborder'/>
	<tr><th style="padding:5px"><?php echo _("Image Logos Customization") ?></th></tr>
	<tr>
		<td class="nobborder">
			<table align="center" class="noborder" style="background-color:white;">
				<tr>
					<td class="nobborder">
						<table class="transparent">
							<tr>
								<td style="padding:20px" class="nobborder">
									<table align="center">
									<tr>
										<th><?php echo _("Home login Logo") ?> [300 x 60]</th>
									</tr>
									<tr>
										<td class="center nobborder">
											<input type="hidden" name="imgfile1" id="imgfile1" value="">
											<div id="downimage1" class="ne12" align="center">
											<?php if (file_exists("../tmp/headers/_login_logo.png")) { ?>
											<img src="../tmp/headers/_login_logo.png" border='0' width="300" height="60"></img>
											<?php } else { ?>
											<img src="../pixmaps/ossim<?= (preg_match("/.*pro.*/i",$version)) ? "_siem" : ((preg_match("/.*demo.*/i",$version)) ? "_siemdemo" : "") ?>.png" style="border:1px solid #EEEEEE"></img>
											<?php } ?>
											</div>
											<input type="file" name="fileToUpload1" id="fileToUpload1">
										</td>
									</tr>
									<tr>
										<td class="center nobborder"><button id="buttonUpload" onclick="return ajaxFileUpload(1);">Upload</button></td>
									</tr>
									</table>
								</td>
							
								<td style="padding:20px" class="nobborder">
									<table align="center">
									<tr>
										<th><?php echo _("Top header Logo") ?> [210 x 42]</th>
									</tr>
									<tr>
										<td class="center nobborder">
											<input type="hidden" name="imgfile2" id="imgfile2" value="">
											<div id="downimage2" class="ne12" align="center">
											<?php if (file_exists("../tmp/headers/_header_logo.png")) { ?>
											<img src="../tmp/headers/_header_logo.png" border='0' width="210" height="42"></img>
											<?php } else { ?>
											<img src="../pixmaps/top/logo_siem.png" style="border:1px solid #EEEEEE"></img>
											<?php } ?>
											</div>
											<input type="file" name="fileToUpload2" id="fileToUpload2">
										</td>
									</tr>
									<tr>
										<td class="center nobborder"><button id="buttonUpload" onclick="return ajaxFileUpload(2);">Upload</button></td>
									</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td colspan="2" style="padding:20px" class="nobborder">
									<table align="center">
									<tr>
										<th><?php echo _("Report header Logo") ?> [1240 x 128]</th>
									</tr>
									<tr>
										<td class="center nobborder">
											<input type="hidden" name="imgfile3" id="imgfile3" value="">
											<div id="downimage3" class="ne12" align="center"><img src="../tmp/headers/default.png" width="700" style="border:1px solid #EEEEEE"></div>
											<input type="file" name="fileToUpload3" id="fileToUpload3">
										</td>
									</tr>
									<tr>
										<td class="center nobborder"><button id="buttonUpload" onclick="return ajaxFileUpload(3);">Upload</button></td>
									</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="nobborder right"><input type="button" value="<?php echo _("Done") ?>" onclick="document.location.href='../index.php'"></input></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
