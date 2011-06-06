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
* - check_writable_relative()
* Classes list:
*/
require_once 'classes/Session.inc';
Session::logcheck("MenuControlPanel", "BusinessProcesses");

if (!Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit") ) 
{
	echo ossim_error(_("You don't have permissions to edit risk indicators"), 'NOTICE');
	exit();
}
require_once 'classes/Security.inc';
$dir  = $_GET['dir'];
$mode = $_GET['mode'];

ossim_valid($dir, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._("Dir"));
ossim_valid($mode, OSS_NULLABLE, OSS_ALPHA, 'illegal:'._("Mode"));

if (ossim_error()) {
	die(ossim_error());
}

$standard_dir = "pixmaps/standard/";
if ($dir=="custom") $standard_dir = "pixmaps/uploaded/";
if ($dir=="flags") $standard_dir  = "pixmaps/flags/";
$icons = explode("\n",`ls -1 '$standard_dir'`);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="../style/style.css">
<?php if ($mode == "slider") { ?>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.easySlider.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){	
			$("#slider").easySlider();
		});
	</script>
	<style>
	/* Easy Slider */

		#slider ul, #slider li{
			margin:0;
			padding:0;
			list-style:none;
		}
		
		#slider, #slider li{ 
			/* 
				define width and height of container element and list item (slide)
				list items must be the same size as the slider area
			*/ 
			width:150px;
			height:100px;
			overflow:hidden; 
		}
		
		span#prevBtn{}
		span#nextBtn{}					
		
	/* // Easy Slider */
		
	</style>
<?php } ?>
</head>

<body style="text-align:center">
<?php if ($mode == "slider") { ?>

<table align="center" class="transparent">
	<tr>
		<td class="center nobborder">
			<div id="slider" style="text-align:center">
				<ul>				
				<?php 
				foreach($icons as $ico)
				{ 
					if(!$ico) continue; 
					if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico))
						continue 
					
					$ico2 = preg_replace("/\..*/","",$ico); 
					?>
					<li><a href="javascript:parent.choose_icon('<?php echo "$standard_dir/$ico" ?>')"><img src="<?php echo "$standard_dir/$ico" ?>" alt="Click to choose <?php echo $ico2 ?>"/></a></li>
					<?php 
				} 
				?>
				</ul>
			</div>
		</td>
	</tr>
</table>
<?php } else {
	
	foreach($icons as $ico)
	{
		if(!$ico) continue;
		
		if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico))
			continue;
		
		$ico2 = preg_replace("/\..*/","",$ico);
		
		print "<a href=\"javascript:parent.choose_icon('$standard_dir/$ico')\" title=\"Click to choose $ico2\"><img src=\"$standard_dir/$ico\" style='margin:10px' border='0'/></a>";
	}
}
?>
</body>
</html>
