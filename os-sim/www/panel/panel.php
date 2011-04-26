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
/*
* TODO:
* - Add options for Window contents update frecuency
* - Unify stuff used by both panel.php and window_panel.php
* - Browser interoperatibility tests (currently only tested under Firefox) -
* Better design
*/
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'ossim_db.inc';
require_once 'panel/Ajax_Panel.php';
require_once 'classes/Util.inc';
Session::logcheck("MenuControlPanel", "ControlPanelExecutive");

function gettabsavt($configs_dir,$cloud_instance=false) {
	$user = Session::get_session_user();
	$tabsavt = array();
	if (is_dir($configs_dir)) {
		if ($dh = opendir($configs_dir)) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match("/^$user.*\.avt/",$file)) {
					list($avt_id,$avt_values) = getavt($file,$configs_dir);
					if (!$cloud_instance || ($cloud_instance && $avt_id!=1004)) // if cloud disable Compliance Tab
						$tabsavt[$avt_id] = $avt_values;					
				}
			}
			closedir($dh);
		}
	}
	return $tabsavt;
}
function getavt($file,$configs_dir="") {
	if (file_exists($configs_dir."/".$file)) {
		$data = file($configs_dir."/".$file);
		if (preg_match("/([^\_]+)\_([^\_]+)\_([^\_]+)\_disabled\.avt/",$file,$found))
			return array($found[3],array("tab_name"=>base64_decode($found[2]),"tab_file"=>$file,"tab_data"=>$data,"tab_icon_url"=>"../pixmaps/alienvault_icon.gif","disable"=>1));
		elseif (preg_match("/([^\_]+)\_([^\_]+)\_([^\_]+)\.avt/",$file,$found))
			return array($found[3],array("tab_name"=>base64_decode($found[2]),"tab_file"=>$file,"tab_data"=>$data,"tab_icon_url"=>"../pixmaps/alienvault_icon.gif","disable"=>0));
	} else return array("",array());
}
function copyavt($filename,$data) {
	if (!$fd = fopen($filename, 'w')) {
		die(_("Could not save config in file, invalid perms?") . ": '$filename'");
	}
	foreach ($data as $line) {	
		if (!fwrite($fd, $line)) {
            die(_("Could not write to file, disk full?") . ": '$filename'");
        }
	}
	fclose($fd);
}
function swapavt($file1,$file2) {
	if (file_exists($file1) && file_exists($file2)) {
		rename($file1,$file1.".backup");
		rename($file2,$file1);
		rename($file1.".backup",$file2);
	}
}

$configs_dir = $conf->get_conf('panel_configs_dir');
$cloud_instance = ($conf->get_conf("cloud_instance", FALSE) == 1) ? true : false;
$tabsavt = gettabsavt($configs_dir,$cloud_instance);

$avt_icons = array(
	"1001" => "../pixmaps/panel/executive.png",
	"1002" => "../pixmaps/panel/network.png",
	"1003" => "../pixmaps/panel/tickets.png",
	"1004" => "../pixmaps/panel/compliance.gif",
	"1005" => "../pixmaps/panel/security.png",
	"1006" => "../pixmaps/panel/inventory.png",
	"1007" => "../pixmaps/panel/vulnerabilities.png",
);

require_once('classes/User_config.inc');
$login = Session::get_session_user();
$db = new ossim_db();
$conn = $db->connect();
$config_aux = new User_config($conn);
$tabdefault = $config_aux->get($login, 'panel_default', 'simple', "main");

$panel_id = GET('panel_id') ? intval(GET('panel_id')) : (($tabdefault > 0) ? $tabdefault : 1001);
$_GET['panel_id'] = $panel_id;

if (Session::menu_perms("MenuControlPanel", "ControlPanelExecutiveEdit")) {
    if (isset($_GET['edit'])) {
        $show_edit = true;
        $_SESSION['ex_panel_can_edit'] = $can_edit = (GET('edit') && $tabsavt[$panel_id] == "") ? true : false;
        $_SESSION['ex_panel_show_edit'] = true;
    } else if (isset($_SESSION['ex_panel_can_edit']) && isset($_SESSION['ex_panel_show_edit'])) {
        $can_edit = ($tabsavt[$panel_id] != "") ? false : $_SESSION['ex_panel_can_edit'];
        $show_edit = $_SESSION['ex_panel_show_edit'];
    } else {
        $can_edit = false;
        $show_edit = true;
    }
} else {
    $can_edit = $show_edit = false;
}

if (GET('edit_tabs') == 1 && $show_edit) {
    $tabs = Window_Panel_Ajax::getPanelTabs();
	
	// add tabs urls
	//$tabs_urls = Window_Panel_Ajax::getPanelUrls();
	/*
	print_r($tabs_urls);
	if (is_array($tabs_urls)) {
		// no repeat key
		$last_key = array_pop(array_keys($tabs));
		// end no repeat key
		foreach ($tabs_urls as $tab_values){
			$tabs[++$last_key] = $tab_values;
		}
	}*/
	// end add tabs urls
	
    //echo "<br><br><br>getPanelTabs<br>";
    //var_dump($tabs);
    if (GET('mode')) {
        $tab_id = intval(GET('tab_id'));
        if (!$tab_id) $tab_id=1;
        $tab_name = GET('tab_name');
		//
		$echars          = get_echars($tab_name);
		$exists_echars   = ( is_array($echars) && !empty($echars) ) ? true : false;
		if($exists_echars){
			$len_max=125;
		}else{
			$len_max=25;
		}
		//
    	if (strlen($tab_name) > $len_max) {
			$tab_name = substr($tab_name,0,$len_max);
			$truncmsg = _("Warning: Tab name too long, truncated to 15 characters.");
		}
        $tab_icon_url = str_replace("slash_special_char","/",GET('tab_icon_url'));
        if ($tab_icon_url == "") { $tab_icon_url = (GET('mode') == "clone" && $avt_icons[GET('clonefrom')] != "" && file_exists($avt_icons[GET('clonefrom')])) ? $avt_icons[GET('clonefrom')] : "../risk_maps/pixmaps/standard/Hacker.png"; }
		$tab_disable = ($tabs[$tab_id]['disable']) ? $tabs[$tab_id]['disable'] : 0;
		$avt = GET('clonefrom');
		/**/
		$tab_url = GET('tab_url');
		/**/
		$tab_order = GET('tab_order');
        ossim_valid($tab_id, OSS_DIGIT, 'error: tab_id.');
        ossim_valid($tab_name, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_NULLABLE, 'error: Invalid name, alphanumeric, score, underscore and spaces allowed.');
		ossim_valid($avt, OSS_DIGIT, OSS_NULLABLE, 'error: Invalid .avt file ID.');
		ossim_valid($tab_order, OSS_DIGIT, OSS_NULLABLE, 'error: Invalid tab order.');
        if (ossim_error()) {
            echo ossim_error();
			die();
        }
        if (is_array($tabs) && array_key_exists($tab_id, $tabs) && !preg_match("/move../",GET('mode'))) {
            unset($tabs[$tab_id]);
        }
        if (GET('mode') != "delete") {
            // Insert new from .avt file (copy data to new file)
			if (GET('mode') == "clone") {
				ossim_valid($tab_name, OSS_ALPHA, OSS_SCORE, OSS_SPACE, 'error: Invalid name, non empty, alphanumeric, score, underscore and spaces allowed.');
				if (ossim_error()) {
					echo ossim_error();
					die();
				}
				// Copy data from avt file to new file
				$data = $tabsavt[$avt]["tab_data"];
				$filename = Window_Panel_Ajax::getConfigFile($tab_id);
				copyavt($filename,$data);
			}
			if (GET('mode') == "new"||GET('mode') == "update") {
				ossim_valid($tab_name, OSS_ALPHA, OSS_SCORE, OSS_SPACE, 'error: Invalid name, non empty, alphanumeric, score, underscore and spaces allowed.');
				if (ossim_error()) {
					echo ossim_error();
					die();
				}
			}
			// Swap order
			$move = "";
			if (preg_match("/move(..)/",GET('mode'),$found)) {
				$move = $found[1];
				$prev = "";
				$prev_aux = "";
				$next = "";
				$flag_next = 0;
				foreach ($tabs as $t_id=>$t_data) {
					if ($flag_next) {
						$flag_next = 0;
						$next = $t_id;
					}
					if ($t_id == $tab_id) {
						$prev = $prev_aux;
						$flag_next = 1;
					}
					$prev_aux = $t_id;
				}
				// Cross IDs
				if ($move == "up" && $prev != "") {
					$tab_data_prev = array('tab_name' => $tabs[$prev]['tab_name'], 'tab_icon_url' => $tabs[$prev]['tab_icon_url'], 'disable' => $tabs[$prev]['disable']);
					$tab_data_curr = array('tab_name' => $tabs[$tab_id]['tab_name'], 'tab_icon_url' => $tabs[$tab_id]['tab_icon_url'], 'disable' => $tabs[$tab_id]['disable']);
					$tabs[$prev] = $tab_data_curr;
					$tabs[$tab_id] = $tab_data_prev;
					swapavt(Window_Panel_Ajax::getConfigFile($prev),Window_Panel_Ajax::getConfigFile($tab_id));
				} elseif ($move == "do" && $next != "") {
					$tab_data_next = array('tab_name' => $tabs[$next]['tab_name'], 'tab_icon_url' => $tabs[$next]['tab_icon_url'], 'disable' => $tabs[$next]['disable']);
					$tab_data_curr = array('tab_name' => $tabs[$tab_id]['tab_name'], 'tab_icon_url' => $tabs[$tab_id]['tab_icon_url'], 'disable' => $tabs[$tab_id]['disable']);
					$tabs[$next] = $tab_data_curr;
					$tabs[$tab_id] = $tab_data_next;
					swapavt(Window_Panel_Ajax::getConfigFile($next),Window_Panel_Ajax::getConfigFile($tab_id));
				}
			}
			// Insert new empty (or cloned)
			if (!is_array($tabs)) {
				$tabs = array();
			}
			if ($move == "") {
				$tabs[$tab_id] = array(
					'tab_name' => $tab_name,
					'tab_icon_url' => htmlentities($tab_icon_url, ENT_COMPAT, "UTF-8"),
					'disable' => $tab_disable
				);
				/**/
				if(!empty($tab_url)){
					$tabs[$tab_id]['tab_url']=$tab_url;
				}
				/**/
			}
        }
		if (GET('mode') == "change") {
			if ($tabs[$tab_id]['disable'] == 1) $tabs[$tab_id]['disable'] = 0;
			else $tabs[$tab_id]['disable'] = 1;
		}
                
		ksort($tabs);
        Window_Panel_Ajax::setPanelTabs($tabs);
    }
	if (GET('avtchange') != "") {
		$file = GET('avtchange');
		ossim_valid($file, OSS_ALPHA, OSS_PUNC, OSS_DIGIT, OSS_SPACE, 'error: Invalid file name.');
        if (ossim_error()) {
            echo ossim_error();
        }
		if (preg_match("/\_disabled/",$file)) {
			// Enable
			rename($configs_dir."/".$file, $configs_dir."/".str_replace("_disabled","",$file));
		} else {
			// Disable
			rename($configs_dir."/".$file, $configs_dir."/".str_replace(".avt","_disabled.avt",$file));
		}
		$tabsavt = gettabsavt($configs_dir,$cloud_instance);
	}
	if (GET('avtchangename') != "") {
		$file = GET('avtchangename');
		$newname = GET('newname');
		if (strlen($newname) > 25) {
			$newname = substr($newname,0,25);
			$truncmsg = _("Warning: Tab name too long, truncated to 15 characters.");
		}
		ossim_valid($file, OSS_ALPHA, OSS_PUNC, OSS_DIGIT, OSS_SPACE, 'error: Invalid file name.');
		ossim_valid($newname, OSS_ALPHA, OSS_PUNC, OSS_DIGIT, OSS_SPACE, 'error: Invalid file name.');
        if (ossim_error()) {
            echo ossim_error();
        }
		list ($file_id,$file_values) = getavt($file);
		$newfile = preg_replace("/([^\_]+)\_[^\_]+\_(\d+)/","\\1_".base64_encode($newname)."_\\2",$file);
		rename($configs_dir."/".$file, $configs_dir."/".$newfile);
		
		$tabsavt = gettabsavt($configs_dir,$cloud_instance);
	}
	if (GET('tabdefault') != "") {
		$newtabdefault = GET('tabdefault');
		ossim_valid($newtabdefault, OSS_DIGIT, 'error: Invalid tab id.');
        if (ossim_error()) {
            echo ossim_error();
        }
		require_once('classes/User_config.inc');
		$login = Session::get_session_user();
		$db = new ossim_db();
		$conn = $db->connect();
		$config = new User_config($conn);
		$_SESSION['views'][$name] = array(
			'cols' => $columns_arr
		);
		$config->set($login, 'panel_default', $newtabdefault, 'simple', 'main');
		$tabdefault = $newtabdefault;
		$panel_id = $tabdefault;
	}
	
	
	$standard_dir = "../risk_maps/pixmaps/standard/";
	if ($dir=="custom") $standard_dir = "pixmaps/uploaded/";
	if ($dir=="flags") $standard_dir = "pixmaps/flags/";
	$icons = explode("\n",`ls -1 '$standard_dir'`);
	$icons2 = explode("\n",`ls -1 '../risk_maps/pixmaps/uploaded/'`);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
	<script src="../js/prototype.js" type="text/javascript"></script>
	<script src="../js/jquery-1.3.1.js" type="text/javascript"></script>
	<script src="../js/greybox_noconflict.js" type="text/javascript"></script>
	<script type="text/javascript">
	function setdefault(tab_id) {
		document.location.href='panel.php?edit_tabs=1&tabdefault='+tab_id;
	}
	function choose_icon(frm,icon_url,icon_url_coded,tab_id) {
		frm.tab_icon_url.value = icon_url_coded;
		if (icon_url != "") document.getElementById('tab_icon_img_'+tab_id).innerHTML = "<a href='' onclick=\"show_icons("+tab_id+");return false\"><img src='"+icon_url+"' width='20' height='20'></a>";
		else document.getElementById('tab_icon_img_'+tab_id).innerHTML = '<input type="button" class="lbutton" onclick="show_icons('+tab_id+')" value="<?php echo _("Choose")?>">';
		document.getElementById('icons_'+tab_id).style.display = "none";
		document.getElementById('ftabs'+tab_id).mode.value='update';document.getElementById('ftabs'+tab_id).submit();
		
	}
	function show_icons(tab_id) {
		document.getElementById('icons_'+tab_id).style.display = "block";
	}
	</script>
</head>

<body>
<?php include ("tabs.php"); ?>
<div style="text-align: right; width: 100%;">[<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?panel_id=<?php echo $panel_id ?>"><?php echo _("Return to panel"); ?></a>]</div>
<br/>
<table align="center">
	<tr>
		<td colspan="3" class="nobborder">
			<form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" method="GET" name="fnew">
			<input type="hidden" name="mode" value="">
			<table class="transparent" align="center">
				<tr valign="middle">
					<td class="noborder">&nbsp;</td>
					<td class="noborder" style="padding-top:10px;padding-bottom:10px">
						<input type="text" size="30" name="tab_name" value="">
					</td>
					<td class="noborder" style="text-align: left" nowrap='nowrap'>
						<input type="hidden" name="tab_icon_url" value="">
						<input type="hidden" name="tab_id" value="">
						<input type="button" value="<?php echo _("Insert new") ?>" onclick="document.fnew.mode.value='new';document.fnew.submit()" class="lbutton" style="font-size:12px">
						<input type="hidden" name="edit_tabs" value="1">
						<input type="hidden" name="panel_id" value="<?php echo $panel_id ?>">
						<? if (count($tabsavt) > 0) { ?>
						or <input type="button" value="<?=_("Clone from")?>" onclick="document.fnew.mode.value='clone';document.fnew.submit()" class="lbutton">
							<select name="clonefrom">
							<? foreach ($tabsavt as $tab_id=>$tab_values) { ?>
								<option value="<?=$tab_id?>"><?=$tab_values['tab_name']?>
							<? } ?>
							</select>
						<? } ?>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php if ($truncmsg != "") { ?>
	<tr>
		<td colspan="5" style="color:red"><?php echo $truncmsg ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="nobborder" width="30"></td>
		<th nowrap='nowrap' width="40"><?php echo _("Icon") ?></th>
		<th width="130" nowrap='nowrap'><?php echo _("Tab Name") ?></th>
		<th width="40" nowrap='nowrap'><?php echo _("Default") ?></th>
		<td class="nobborder"></td>
	</tr>
	<?php
		$last_tab_id = - 1;
		// 1:
		// FROM DATABASE
		if ($tabs != false) {
			ksort($tabs);
			$index = 1;
			foreach($tabs as $tab_id => $tab_values) {
	?>
	
	<?php if ($tab_values['disable']) $back_color= "#EEEEEE;"; else $back_color='transparent;'?>
	<tr>
		<form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" name="ftabs<?=$tab_id?>" id="ftabs<?=$tab_id?>" method="GET">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="tab_icon_url" value="<?php echo str_replace("/","slash_special_char",$tabs[$tab_id]["tab_icon_url"]) ?>">
		
		<td>
			<?php if ($index > 1) { ?>
			<a href="" onclick="document.ftabs<?=$tab_id?>.mode.value='moveup';document.getElementById('ftabs<?=$tab_id?>').submit();return false;"><img src="../pixmaps/theme/arrow-skip-090.png" border="0" alt="<?php echo _("Move Up") ?>" title="<?php echo _("Move Up") ?>"></img></a>&nbsp;
			<?php } else { ?>
			<img src="../pixmaps/theme/arrow-skip-090.png" style="filter:alpha(opacity:50);opacity:0.5"></img>
			<?php } ?>
			<?php if ($index < count($tabs)) { ?>
			<a href="" onclick="document.ftabs<?=$tab_id?>.mode.value='movedo';document.getElementById('ftabs<?=$tab_id?>').submit();return false;"><img src="../pixmaps/theme/arrow-skip-270.png" border="0" alt="<?php echo _("Move Down") ?>" title="<?php echo _("Move Down") ?>"></img></a></td>
			<?php } else { ?>
			<img src="../pixmaps/theme/arrow-skip-270.png" style="filter:alpha(opacity:50);opacity:0.5"></img>
			<?php } ?>
		
		<td id="tab_icon_img_<?=$tab_id?>" style='background-color: <?=$back_color?>'>
			
		<?php 
		if ($tabs[$tab_id]["tab_icon_url"]) { ?>
			<a href="" onclick="show_icons('<?php echo $tab_id ?>');return false"><img src="<?php echo $tabs[$tab_id]["tab_icon_url"] ?>" height="20"></a>
		<?php 	
		} 
		else { ?>
			<input type="button" class="lbutton" onclick="show_icons(<?php echo $tab_id?>)" value="<?php echo _("Choose")?>">
		<?php  } ?>
		</td>
		
		<td style='background-color: <?=$back_color?>'>
			<input type="text" size="30" name="tab_name" style="color:<?=($tabs[$tab_id]['disable']) ? "gray" : "black"?>" value="<?php echo $tabs[$tab_id]["tab_name"]; ?>">
		</td>
		<td style="text-align:center; background-color: <?=$back_color?>"><input type="radio" style="border:0px;background:transparent" name="tabdefault" value="" onclick="setdefault(<?=$tab_id?>)" <? if ($tabdefault == $tab_id) echo "checked" ?> <?php if ($tabs[$tab_id]['disable']) echo "disabled" ?>></td>
		<td nowrap='nowrap' style='background-color: <?=$back_color?>'>
			<input type="button" class="lbutton" value="<?php echo _("Update") ?>" onclick="document.ftabs<?=$tab_id?>.mode.value='update';document.getElementById('ftabs<?=$tab_id?>').submit()">
			&nbsp;<a href="" onclick="document.ftabs<?=$tab_id?>.mode.value='delete';document.getElementById('ftabs<?=$tab_id?>').submit();return false;"><img src="../vulnmeter/images/delete.gif" alt="<?=_("Delete")?>" title="<?=_("Delete")?>" border="0"></a>
			&nbsp;<input type="button" onclick="document.ftabs<?=$tab_id?>.mode.value='change';document.getElementById('ftabs<?=$tab_id?>').submit();return false;" value="<?=($tabs[$tab_id]['disable']) ? _("Enable") : _("Disable")?>" class="<?=($tabs[$tab_id]['disable']) ? "lbutton" : "lbuttond" ?>" style="width:80px" <?php if (!$tabs[$tab_id]['disable'] && $tabdefault == $tab_id) echo "disabled"?>>
		</td>
		<?php if($tabs[$tab_id]["tab_url"]){ ?>
			<input type="hidden" name="tab_url" value="<?php echo $tabs[$tab_id]["tab_url"]; ?>">
		<?php } ?>
		<input type="hidden" name="edit_tabs" value="1">
		<input type="hidden" name="panel_id" value="<?php echo $panel_id ?>">
		<input type="hidden" name="tab_id" value="<?php echo $tab_id ?>">
	</form>
</tr>

<tr>
	<td colspan="5" class="nobborder">
		<div id="icons_<?php echo $tab_id ?>" style="width:600px;display:none">
			<?php
			$i = 0;
			foreach($icons as $ico){
			  if(!$ico)continue;
			  if(is_dir($standard_dir . "/" . $ico) || !getimagesize($standard_dir . "/" . $ico)){ continue;}
			  $ico2 = preg_replace("/\..*/","",$ico);
			  print "<a href=\"javascript:choose_icon(document.ftabs$tab_id,'$standard_dir/$ico','".str_replace("/","slash_special_char",$standard_dir."/".$ico)."',$tab_id)\" title=\"Click to choose $ico2\"><img src=\"$standard_dir/$ico\" style='margin:10px' border=0 width='20' height='20'></a>&nbsp;";
			}
			$custom_dir = "../risk_maps/pixmaps/uploaded/";
			foreach($icons2 as $ico){
			  if(!$ico)continue;
			  if(is_dir($custom_dir . "/" . $ico) || !getimagesize($custom_dir . "/" . $ico)){ continue;}
			  $ico2 = preg_replace("/\..*/","",$ico);
			  print "<a href=\"javascript:choose_icon(document.ftabs$tab_id,'$custom_dir/$ico','".str_replace("/","slash_special_char",$custom_dir."/".$ico)."',$tab_id)\" title=\"Click to choose $ico2\"><img src=\"$custom_dir/$ico\" style='margin:10px' border=0 width='20' height='20'></a>&nbsp;";
			}
			?>
			<br/>
			<center>
				<a href="javascript:choose_icon(document.ftabs<?php echo $tab_id?>,'','',<?php echo $tab_id?>)" style="font-size:14px;text-align:center"><b><?php echo _("None Selected")?></b>
				</a>
			</center>
			<br/><br/>
		</div>
	</td>
</tr>
<?php
            if ($last_tab_id < $tab_id) $last_tab_id = $tab_id;
            $index++;
        }
    }
?>
<script type="text/javascript">
document.fnew.tab_id.value = '<?=$last_tab_id + 1?>';
</script>
<?
	// 2:
	// FROM .avt FILES
	if (count($tabsavt) > 0 && is_array($tabsavt)) {
		ksort($tabsavt);
        echo "<tr height='20'><td class=nobborder></td></tr>";
		foreach($tabsavt as $tab_id => $tab_values) {
?>
<form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" method="GET">
<tr <? if ($tab_values['disable']) echo "bgcolor='#EEEEEE'";?>>
	<td></td>
	<td>
		<img src="<?php echo ($avt_icons[$tab_id] != "" && file_exists($avt_icons[$tab_id])) ? $avt_icons[$tab_id] : "../pixmaps/alienvault_icon.gif" ?>" height="20">
	</td>
<td>
<input type="text" size="30" name="tab_name" id="newname<?=$tab_id?>" style="color:<?=($tabsavt[$tab_id]['disable']) ? "gray" : "black"?>" value="<?php echo $tabsavt[$tab_id]["tab_name"]; ?>">
</td>
<td style="text-align:center"><input type="radio" style="border:0px;background:transparent" name="tabdefault" value="" onclick="setdefault(<?=$tab_id?>)" <? if ($tabdefault == $tab_id) echo "checked" ?> <?php if ($tabsavt[$tab_id]['disable']) echo "disabled" ?>></td>
<td nowrap='nowrap'>
<input type="button" class="lbutton" value="<?php echo _("Update") ?>" onclick="document.location.href='<?php echo $_SERVER['SCRIPT_NAME'] ?>?edit_tabs=1&avtchangename=<?=$tab_values['tab_file']?>&newname='+document.getElementById('newname<?=$tab_id?>').value">
&nbsp;<img style="filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity: 0.5;opacity: 0.5;" src="../vulnmeter/images/delete.gif" alt="<?=_("Delete")?>" title="<?=_("Delete")?>">
&nbsp;<input type="button" onclick="document.location.href='<?php echo $_SERVER['SCRIPT_NAME'] ?>?edit_tabs=1&avtchange=<?=$tab_values['tab_file']?>'" value="<?=($tabsavt[$tab_id]['disable']) ? _("Enable") : _("Disable")?>" class="<?=($tabsavt[$tab_id]['disable']) ? "lbutton" : "lbuttond" ?>" style="width:80px" <?php if (!$tabsavt[$tab_id]['disable'] && $tabdefault == $tab_id) echo "disabled"?>>
</td>
<input type="hidden" name="edit_tabs" value="1">
<input type="hidden" name="panel_id" value="<?php echo $panel_id ?>">
<input type="hidden" name="tab_id" value="<?php echo $tab_id ?>">
</form>
</tr>
<?php
            //if ($last_tab_id < $tab_id) $last_tab_id = $tab_id;
        }
	}
?>
<tr><td class="nobborder" colspan="5">* <i><?php echo _("You can choose only names, only icons or both") ?></i></td></tr>
</table>
</body></html>

<?php
    exit();
}
//
// Detect if that's an AJAX call
//

if (GET('interface') == 'ajax') {
    if (GET('ajax_method') == 'showWindowContents') {
        $ajax = & new Window_Panel_Ajax();
		$filename = (GET('panel_id') >= 1000) ? $configs_dir."/".$tabsavt[GET('panel_id')]['tab_file'] : null;
		$options = $ajax->loadConfig(GET('id'),$filename);
		$data['HELP_LABEL'] = _("help");
        if (count($options)) {
            // Add metric threshold indicator
            $indicator = "";
            if (isset($options['metric_opts']['enable_metrics']) && $options['metric_opts']['enable_metrics'] == 1 && isset($options['metric_opts']['metric_sql']) && strlen($options['metric_opts']['metric_sql']) > 0) {
                $sql = $options['metric_opts']['metric_sql'];
                if (!preg_match('/^\s*\(?\s*SELECT\s/i', $sql) || preg_match('/\sFOR\s+UPDATE/i', $sql) || preg_match('/\sINTO\s+OUTFILE/i', $sql) || preg_match('/\sLOCK\s+IN\s+SHARE\s+MODE/i', $sql)) {
                    die(_("SQL Query invalid due security reasons"));
                }
                $db = new ossim_db;
                $conn = $db->connect();
                if (!$rs = $conn->Execute($sql)) {
                    echo "Error was: " . $conn->ErrorMsg() . "\n\nQuery was: " . $sql;
                    exit();
                }
                $metric_value = $rs->fields[0];
                $db->close($conn);
                $low_threshold = $options['metric_opts']['low_threshold'];
                $high_threshold = $options['metric_opts']['high_threshold'];
                // We need 5 states for the metrics:
                /*
                * green
                -25 %
                * green-yellow
                - lower threshold
                * green-yellow
                +25 %
                * yellow
                -25 %
                * yellow-red
                - upper threshold
                * yellow-red
                +25 %
                * red
                */
                $first_comp = $low_threshold - ($low_threshold / 4);
                $second_comp = $low_threshold + ($low_threshold / 4);
                $third_comp = $high_threshold - ($high_threshold / 4);
                $fourth_comp = $high_threshold + ($high_threshold / 4);
                if ($metric_value <= $first_comp) {
                    $indicator = " <img src=\"../pixmaps/traffic_light1.gif\"/> ";
                } elseif ($metric_value > $first_comp && $metric_value <= $second_comp) {
                    $indicator = " <img src=\"../pixmaps/traffic_light2.gif\"/> ";
                } elseif ($metric_value > $second_comp && $metric_value <= $third_comp) {
                    $indicator = " <img src=\"../pixmaps/traffic_light3.gif\"/> ";
                } elseif ($metric_value > $third_comp && $metric_value <= $fourth_comp) {
                    $indicator = " <img src=\"../pixmaps/traffic_light4.gif\"/> ";
                } elseif ($metric_value > $fourth_comp) {
                    $indicator = " <img src=\"../pixmaps/traffic_light5.gif\"/> ";
                } else {
                    $indicator = " <img src=\"../pixmaps/traffic_light0.gif\"/> ";
                }
            }

            $data['CONTENTS'] = $ajax->showWindowContents($options);
            if (isset($options['window_opts']['title'])) $data['TITLE'] = $options['window_opts']['title'] . $indicator;
            else $data['TITLE'] = "";
            if (isset($options['window_opts']['help'])) $data['HELP_MSG'] = Util::string2js($options['window_opts']['help']);
            else $data['HELP_MSG'] = "";
        } else { // New window
            $data['CONTENTS'] = '';
            $data['TITLE'] = _("New window");
            $data['HELP_MSG'] = '';
        }
        if ($can_edit) {
            $data['CONFIG'] = '[<a href="window_panel.php?id=' . GET('id') . '&panel_id=' . $panel_id . '" title="config">config</a>]';
        } else {
            $data['CONFIG'] = '';
        }
        $data['ID'] = GET('id');
        if ($data['TITLE'] == "") echo $ajax->parseTemplate('./window_tpl_notitle.htm', $data);
        else echo $ajax->parseTemplate('./window_tpl.htm', $data);
    } elseif (GET('ajax_method') == 'savePanelConfig' && $can_edit && GET('panel_id') < 1000) {
        $ajax = & new Window_Panel_Ajax();
        $config['rows'] = GET('rows') ? GET('rows') : 3;
        $config['cols'] = GET('cols') ? GET('cols') : 2;
        $ajax->saveConfig('panel', $config);
    } elseif (GET('ajax_method') == 'moveWindow' && GET('panel_id') < 1000) {
        $ajax = & new Window_Panel_Ajax();
        $opts_from = $ajax->loadConfig(GET('from'));
        $opts_to = $ajax->loadConfig(GET('to'));
        echo $ajax->saveConfig(GET('to') , $opts_from);
        echo $ajax->saveConfig(GET('from') , $opts_to);
    } elseif (GET('panel_id') >= 1000) {
		echo _("Can not change configuration in .avt files");
	} else {
        echo _("Not recognized AJAX method: '") . GET('ajax_method') . "'";
        printr($_GET);
    }
    exit;
    //
    // Load Panel settings from config
    //
    
} else {
    $ajax = & new Window_Panel_Ajax();
    $filename = (GET('panel_id') >= 1000) ? $configs_dir."/".$tabsavt[GET('panel_id')]['tab_file'] : null;
	$options = $ajax->loadConfig('panel',$filename);
    $rows = isset($options['rows']) ? $options['rows'] : 3;
    $cols = isset($options['cols']) ? $options['cols'] : 2;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
<script src="../js/prototype.js" type="text/javascript"></script>
<script src="../js/jquery-1.3.1.js" type="text/javascript"></script>
<script src="../js/greybox_noconflict.js" type="text/javascript"></script>
<script src="../js/scriptaculous/scriptaculous.js" type="text/javascript"></script>
<script src="./panel.js" type="text/javascript"></script>
<script>
<!--
function doIframe2(){
	loadingIframe()
	o = document.getElementById('dashboardsIframe');
	if (o != null) {
		setHeight2(o);
		addEvent2(o,'load',doIframe2);
	}
}

function setHeight2(e){
	if(e.contentDocument){
		if (e.contentDocument.body.offsetHeight>500)
			e.height = e.contentDocument.body.offsetHeight + 85;
	} else {
	    if (e.contentWindow.document.body.scrollHeight>500)
	    	e.height = e.contentWindow.document.body.scrollHeight + 85;
	}
	//alert(e.height);
}

function addEvent2(obj, evType, fn){

	if(obj.addEventListener)
	{
	obj.addEventListener(evType, fn, false);
	return true;
	
	} 
	else if (obj.attachEvent){
		var r = obj.attachEvent("on"+evType, fn);
		return r;
	} 
	else {
		return false;
	}
}


if (document.getElementById && document.createTextNode){
	addEvent2(window,'load', doIframe2);
}

function wopen(url, name, w, h)
{
 // DK: Found this googling, thx :p
 // Fudge factors for window decoration space.
 // In my tests these work well on all platforms & browsers.
//w += 32; 
//h += 96; 
w = (screen.availWidth) ? screen.availWidth : w + 32;
h = (screen.availHeight) ? screen.availHeight : h + 96; 
 var win = window.open(url,
  name, 
  'width=' + w + ', height=' + h + ', ' +
  'location=no, menubar=no, status=no, toolbar=no, scrollbars=yes, resizable=yes');
 win.resizeTo(w, h);
 win.focus();
}

function loadingIframe(){
	o = document.getElementById('dashboardsIframeLoading');
	if (o != null) o.style.visibility='hidden';
}

// -->
</script>
<style type="text/css">
    body {
      background: white;
      color: black;
      font-family: tahoma,arial,verdana,helvetica,sans-serif;
      font-size:  8pt;
      margin: 1px;
      padding: 1px;
      margin-top: 1%;
      margin-left: 1%;
      margin-right: 2%;
      margin-bottom: 2%;
    }
  .panel-position {
    border: 0px solid #FFCFCF;
    margin: 1px;
    padding: 5px;
    /* background: #808080; */
    /* filter:alpha(opacity=50); -moz-opacity:.50; opacity:.50; */
    }
  .panel-active {
    background-color: #FFE59F;
    /* z-index: 1000; */
  }
  .placehere {
    position: relative;
    /* top: 5%; left: 5%; */
    border: 0px solid #bbb;
    margin: 0px; padding: 0px;
  }
  .loading {
      position: absolute;
      top: 1px;
      right: 1px;
      background-color: #AC0606;
      color: white;
  }
  .help {
      position: absolute;
      top: 5px;
      right: 5px;
      border: 1px;
      width: 300px;
      background-color: #F9F9F9;
      border: 1px dotted rgb(33,78,93);
      padding: 3px;
      z-index: 1001;
  }
  
.tag_cloud { padding: 3px; text-decoration: none; }
.tag_cloud:link  { color: #8DC41B; }
.tag_cloud:visited { color: #019c05; }
.tag_cloud:hover { color: #ffffff; background: #69da03; }
.tag_cloud:active { color: #ffffff; background: #ACFC65; }
.gristab {
	font-family:arial; color:#000000; font-weight:normal; font-size:12px;
	text-decoration:none;
}
.gristabon {
	font-family:arial;  color:#000000; font-weight:bold; font-size:12px;
	text-decoration:none;
}
a.gristab:hover, a.gristabon:hover {
	text-decoration:none;
}
small.white,small.white a { text-decoration:none; color:white }
.btn { background: #cccccc url(../pixmaps/theme/bg_button.png) 50% 50% repeat-x; font-size: 10px; color: #222222; text-align: center; }
input.btn:hover { border:1px solid #02A705; background: #4AC600 url(../pixmaps/theme/bg_button_on.png) 50% 50% repeat-x; color: #FFFFFF; }
.nobborder { border-bottom:0px none; }
.noborder { border:0px none; }

.hb{
	padding-top:0px;
	margin-bottom:0px;
	font-size:12px;
	font-family:arial,verdana,geneva,sans-serif;
	color:#606060;
	font-weight:bold;
}

.hb a,.hb a:visited{
	font-family:arial,verdana,geneva,sans-serif;
	text-decoration:underline;
	color:#3f3f3f;
}

.hb small {
	color:#3f3f3f; vertical-align:bottom;
}

.ymymd {
	background: #ffffff;
	border: 0px solid #a4a4a4;
}

.t1 { 
	background:#EEEEEE; 
	border-bottom:1px solid #CCCCCC; 
	border-top:1px solid #CCCCCC; 
	padding:2px 0px 2px 0px; 
}


div.hd:hover { cursor:-moz-grab; cursor:url(../pixmaps/theme/grab.cur),auto); }

#dashboardsIframe{
	border:0;
	width:100%;
	margin:0;
	padding:0;
}

#dashboardsIframeLoading{	
	text-align: center;
	background-image: url(../pixmaps/theme/overlay.png); 
	position: absolute;
	margin: auto;
	top: 0;
	left: 0;
	z-index: 100;
	width:  100%;
	height: 100%;
	overflow-x:hidden;
}

* html #dashboardsIframeLoading {
  background-color: #000;
  background-color: transparent;
  background-image: url(../pixmaps/theme/blank.gif);
  filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="../pixmaps/theme/overlay.png", sizingMethod="scale");
}

#dashboardsIframeLoading_window {
  top: 20px;
  left: 33%;
  position: absolute;
  background: #fff;
  border: 2px solid #787878;
  width: 300px;
  height: 80px;
  z-index: 150;
  overflow:hidden;
  text-align: center;
  padding-top: 50px;
	opacity: .75;
	-moz-opacity: .75;
	filter:alpha(opacity=75);
	border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
}

</style>
</head>
<body>
<!-- Tabs if present -->
<?php
if (GET('fullscreen') != 1) {
    $tabs = Window_Panel_Ajax::getPanelTabs();
	//$tabs_urls = Window_Panel_Ajax::getPanelUrls();
    $first = 1;
    include ("tabs.php");
?>
<!-- EDIT panel controls -->

<?php
    // if not fullscreen
    
} else {
    // if in fullscreen mode show a big tab name and icon
    $tabs = Window_Panel_Ajax::getPanelTabs();
    if (strlen($tabs[$panel_id]["tab_icon_url"]) > 0) {
        $image_string = "<img src=\"" . $tabs[$panel_id]["tab_icon_url"] . "\">";
    } else {
        $image_string = "";
    }
    print "<center><h2> [" . $tabs[$panel_id]["tab_name"] . $image_string . "] </h2></center>";

}
if ($tabs[$panel_id]['disable'] == 1) die(_("The panel you want to show is disabled.")." <a href='panel.php?edit_tabs=1&panel_id=".$panel_id."'>"._("Click here to Edit Tabs")."</a>");

// tab url
$menu_opc=GET('hmenu');
$menu_sopc=GET('smenu');

if (GET('edit') && $tabsavt[$panel_id] != "") {
	$last_tab_id = - 1;
	if (is_array($tabs))
		foreach($tabs as $tab_id => $tab_values) {
			if ($last_tab_id < $tab_id) $last_tab_id = $tab_id;
		}
	if ($last_tab_id < 1) $last_tab_id = 1;
	?>
<span align="center"><?php echo _("This tab can not be edited, ") ?><a href="panel.php?edit_tabs=1&panel_id=<?php echo $panel_id ?>&mode=clone&clonefrom=<?php echo $panel_id ?>&tab_name=<?php echo $tabsavt[$panel_id]['tab_name'] ?>Clone&tab_id=<?php echo $last_tab_id + 1 ?>"> <?php echo gettext("click here"); ?> </a><?php echo _(" to clone and edit a copy of this tab") ?>. <i>[<?php echo gettext("You will create a new tab called"); ?> '<b><?php echo $tabsavt[$panel_id]['tab_name'] ?>Clone</b>']</i></span>
<?php exit; }

if ($menu_opc == "dashboards" && $menu_sopc == "dashboards") {
?>
<div id="dashboardsIframeLoading">
	<div id="dashboardsIframeLoading_window">
		<img src="../pixmaps/loading3.gif" /> <?php echo _('Loading content'); ?>
	</div>
</div>
<iframe id="dashboardsIframe" src="<?php echo $tabs[$panel_id]['tab_url']; ?>" scrolling="auto" frameborder="0" height="535"></iframe>
<?php
}else{
// tab normal
?>
<!-- displays saveConfig errors -->
<div id="container" style="margin: 0px; padding: 0px"></div>
<div id="loading" class="loading"></div>
<div id="help" class="help"></div>
<script>Element.hide('help');</script>

<div id="placehere">
Ossim Panel Loading...
</div>

<!-- do nothing, Ajax.Updater in ajax_load() needs an element -->
<div id="null" style="display: none"></div>

<script>

var myResponders = {
    onCreate: function() {
        Element.show('loading');
        $('loading').innerHTML = '<?php
echo gettext("Loading"); ?>..';
    }
}
Ajax.Responders.register(myResponders);
var AjaxRequestCounter = 0;

function ajax_load(id)
{
    ajax_url = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?interface=ajax&panel_id=<?php echo $panel_id ?>&ajax_method=showWindowContents&id='+id;
    AjaxRequestCounter++;
    new Ajax.Updater (
        'null',  // Element to refresh
        ajax_url, // URL
        {          // options
            method: 'get',
            asynchronous: true,
            parameters: '<?php echo $can_edit ? 'edit=1' : '' ?>',
            onComplete: function(req) {
                $('loading').innerHTML = '<?php
echo gettext("Loading"); ?> ('+AjaxRequestCounter+' <?php
echo gettext("pending"); ?>)';
                AjaxRequestCounter--;
                if (AjaxRequestCounter == 0) {
                    Element.hide('loading');
                }
                
                Control.Panel.setWindow(id, req.responseText);
            }
        }
    );
    return false;
}

function panel_save(rows, cols)
{
    ajax_url = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?interface=ajax&panel_id=<?php echo $panel_id ?>&ajax_method=savePanelConfig';
    new Ajax.Updater (
        'container',  // Element to refresh
        ajax_url, // URL
        {          // options
            method: 'get',
            asynchronous: true,
            parameters: 'rows='+rows+'&cols='+cols+'<?php echo $can_edit ? '&edit=1' : '' ?>',
            onComplete: function(req) {
                $('container').innerHTML = req.responseText;
            }
        }
    );
    return false;
}

function on_move_window(fromEl, toEl)
{
    var fromPos = fromEl.id;
    var toPos   = toEl.id;
    ajax_url = '<?php echo $_SERVER['SCRIPT_NAME'] ?>?interface=ajax&panel_id=<?php echo $panel_id ?>&ajax_method=moveWindow';
    var myAjax = new Ajax.Updater (
        'container',  // Element to refresh
        ajax_url, // URL
        {          // options
            method: 'get',
            asynchronous: false,
            parameters: 'from='+fromPos+'&to='+toPos
        }
    );
    //
    // There is a bug in prototype when asynchronous = false, it doesn't
    // call  the "onComplete" function. This trick is a workarround.
    //
    $('container').innerHTML = myAjax.transport.responseText;
    ajax_load(fromPos);
    ajax_load(toPos);
    return false;
}

function panel_load(rows, cols)
{
    Control.Panel.setOptions(
        {
            rows: rows,
            cols: cols,
            posWidth: 520,
            posHeight: 300,
            posClass: 'panel-position',
            posHoverClass: 'panel-active',
            onWindowMove: on_move_window
        }
    );
    Control.Panel.drawGrid($('placehere'));
    for (i=1; i <= cols; i++) {
        for (j=1; j <= rows; j++) {
            var win_id = i+'x'+j;
            ajax_load(win_id);
        }
    }
}

panel_load(<?php echo $rows?>, <?php echo $cols ?>);
Control.Tip.use = 'help';
</script>
<?php } ?>
</body></html>
