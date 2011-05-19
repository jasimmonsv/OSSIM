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

ob_implicit_flush();

require_once 'ossim_db.inc';
require_once 'classes/Session.inc';
require_once 'classes/User_config.inc';
require_once 'ossim_conf.inc';
include("riskmaps_functions.php");

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);

Session::logcheck("MenuControlPanel", "BusinessProcesses");

function mapAllowed($perms_arr,$version) {
	if (Session::am_i_admin()) return true;
	$ret = false;
	foreach ($perms_arr as $perm=>$val) {
		// ENTITY
		if (preg_match("/^\d+$/",$perm)) {
			if (preg_match("/pro|demo/i",$version) && $_SESSION['_user_vision']['entity'][$perm]) {
				$ret = true;
			}
		// USER
		} elseif (Session::get_session_user() == $perm) {
			$ret = true;
		}
	}
	return $ret;
}

$can_edit = false;

if (Session::menu_perms("MenuControlPanel", "BusinessProcessesEdit")) 
	$can_edit = true;



function check_writable_relative($dir){
$uid = posix_getuid();
$gid = posix_getgid();
$user_info = posix_getpwuid($uid);
$user = $user_info['name'];
$group_info = posix_getgrgid($gid);
$group = $group_info['name'];
$fix_cmd = '. '._("To fix that, execute following commands as root").':<br><br>'.
                   "cd " . getcwd() . "<br>".
                   "mkdir -p $dir<br>".
                   "chown $user:$group $dir<br>".
                   "chmod 0700 $dir";
if (!is_dir($dir)) {
     die(_("Required directory " . getcwd() . "$dir does not exist").$fix_cmd);
}
$fix_cmd .= $fix_extra;

if (!$stat = stat($dir)) {
        die(_("Could not stat configs dir").$fix_cmd);
}
        // 2 -> file perms (must be 0700)
        // 4 -> uid (must be the apache uid)
        // 5 -> gid (must be the apache gid)
if ($stat[2] != 16832 || $stat[4] !== $uid || $stat[5] !== $gid)
        {
            die(_("Invalid perms for configs dir").$fix_cmd);
        }
}

check_writable_relative("./maps");
check_writable_relative("./pixmaps/uploaded");

require_once 'classes/Security.inc';

$db          = new ossim_db();
$conn        = $db->connect();
$config      = new User_config($conn);
$login       = Session::get_session_user();
$default_map = $config->get($login, "riskmap", 'simple', 'main');

if ($default_map == "") $default_map = 1;

$map = ($_GET["map"]!="") ? $_GET["map"] : $default_map;
$_SESSION["riskmap"] = $map;

if ($_GET['default'] != "" && $map != "")
	$config->set($login, "riskmap", $map, 'simple', "main");

	
$hide_others=1;

ossim_valid($map, OSS_DIGIT, 'illegal:'._("type"));

if (ossim_error()) {
	die(ossim_error());
}

$perms = array();
$query = "SELECT map,perm FROM risk_maps";

if ($result = $conn->Execute($query)) 
{
	while (!$result->EOF) 
	{
		$perms[$result->fields['map']][$result->fields['perm']]++;
		$result->MoveNext();
	}
}

if ( is_array($perms[$map]) && !mapAllowed($perms[$map],$version) ) 
{
	echo ossim_error(_("You don't have permission to see Map $map."), "NOTICE");
	exit;
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo  _("Alarms") ?> - <?php echo _("View")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="./custom_style.css">
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript">
		function refresh_indicators() {
			$.ajax({
			   type: "GET",
			   url: "get_indicators.php?map=<?php echo $map ?>",
			   success: function(msg){
				   //Output format ID_1####DIV_CONTENT_1@@@@ID_2####DIV_CONTENT_2...
				   var indicators = msg.split("@@@@");
				   for (i = 0; i < indicators.length; i++) if (indicators[i].match(/\#\#\#\#/)) {
						var data = indicators[i].split("####");
						if (data[0] != null) {
							document.getElementById(data[0]).innerHTML = data[1];
						}
				   }
			   }
			});	
		}
		
		function initDiv () {
			
			$('#loading').hide();
			
			var x   = 0;
			var y   = 0;
			var obj = $('#map_img');
			do {
				x  += obj.offsetLeft;
				y  += obj.offsetTop;
				obj = obj.offsetParent;
			} while (obj);	
			
			var objs = document.getElementsByTagName("div");
			var txt = ''
			for (var i=0; i < objs.length; i++) 
			{
				if (objs[i].className == "itcanbemoved") 
				{
					xx = parseInt(objs[i].style.left.replace('px',''));
					objs[i].style.left = xx + x
					yy = parseInt(objs[i].style.top.replace('px',''));
					objs[i].style.top = yy + y;
					objs[i].style.visibility = "visible"
				}
			}
			refresh_indicators()
			setInterval(refresh_indicators,5000);
		}
		
		$(document).ready(function() {
			initDiv();
		});
				
	</script>
	
	<style type="text/css">
		.itcanbemoved { position:absolute; }
		
		#loading {
		position: absolute; 
		width: 99%; 
		height: 99%; 
		margin: auto; 
		text-align: center;
		background: #FFFFFF;
		z-index: 10000;
		}
		
		#loading div{
			position: relative;
			top: 40%;
			margin:auto;
		}
		
		#loading div span{
			margin-left: 5px;
			font-weight: bold;	
		}
		
		#tree { position: relative;}
		
	</style>
	
</head>

<body leftmargin='5' topmargin='5' class='ne1'>

<div id='loading'>
	<div><img src='../pixmaps/loading3.gif' alt='<?php echo _("Loading Maps")?>'/><span><?php echo _("Loading Maps")?>...</span></div>
</div>

<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td valign='top' id="map">
			<img id="map_img" src="maps/map<?php echo $map ?>.jpg" border="0"/>
		</td>
		
		<td valign='top' class='ne1' style="padding-left:5px">
			<?php
			
			if(!$hide_others)
			{
				?>
				<h2><?php echo  _("Maps") ?></h2>
				<?php
				if($can_edit){
					print "&nbsp;(<a href='riskmaps.php?hmenu=Risk+Maps&smenu=Edit+Risk+Maps' target='_parent'><b>" . _("Edit") . "</b></a>)";
				}
				 ?>
				<br/>
				<?php
				
				$maps = explode("\n",`ls -1 'maps' | grep -v CVS`);
								
				$i=0; $n=0; $txtmaps = ""; $linkmaps = "";
												
				foreach ($maps as $ico) 
				{
					if (trim($ico)!="") 
					{
						if(!getimagesize("maps/" . $ico)){ continue;}
						$n = str_replace("map","",str_replace(".jpg","",$ico));
						
						if (is_array($perms[$n]) && !mapAllowed($perms[$n],$version)) 
							continue;
						
						$txtmaps .= "<td><a href='$SCRIPT_NAME?map=$n'><img src='maps/$ico' border=".(($map==$n) ? "2" : "0")." width='100' height='100'></a></td>";
						$i++; 
						
						if ($i % 4 == 0) {
							$txtmaps .= "</tr><tr>";
						}
					}
				}
				?> 
				<table><tr><?php echo $txtmaps ?></tr></table><br/>
				<?php
			} // if(!$hide_others)
								
			$conn->close();
			?>
		</td>
	</tr>

	<tr>
		<td>
			<form name="fdefault" method="get" action="view.php">
				<input type="hidden" name="map" value="<?php echo $_GET['map']?>"/>
				<input type="hidden" name="default" value="1"/>
				<input type="submit" value="<?php echo _("Set as Default")?>" class="button"/>
			</form>
		</td>
	</tr>
</table>
<?php print_indicators($map);?>
</body>
</html>
