<div style="position:absolute;left:0px;top:0px;width:100%;background:#8E8E8E" class="noborder">
	<table width="100%" border='0' cellpadding='0' cellspacing='0' style="background:#8E8E8E" class="noborder">
	<tr>
		<td style="width:15px;vertical-align:bottom" class="noborder">&nbsp;</td>
		<td style="padding-top:7px" class="noborder">
			<table border='0' cellpadding='0' cellspacing='0' style="background:transparent;" class="noborder">
				<tr>
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
//if ($tabs) {
    $tabsmerge = $tabs;
	// add tabs urls
	/*
	if (is_array($tabs_urls)) {
		// no repeat key
		$last_key = array_pop(array_keys($tabsmerge));
		// end no repeat key
		foreach ($tabs_urls as $tab_values){
			$tabsmerge[++$last_key] = $tab_values;
		}
	}*/
	// end add tabs urls
	
	if (is_array($tabsavt)) {
		foreach ($tabsavt as $tab_id => $tab_values)
			$tabsmerge[$tab_id] = $tab_values;
	}

	if (!empty($tabsmerge) && is_array($tabsmerge))
	{
		$tabshow = array();
		foreach($tabsmerge as $tab_id => $tab_name) {
			// Check perms
			if ($tabsmerge[$tab_id]["tab_name"] == "Vulnerabilities" && !Session::menu_perms("MenuEvents", "EventsVulnerabilities")) {continue;}
			if ($tabsmerge[$tab_id]["tab_name"] == "Tickets" && !Session::menu_perms("MenuIncidents", "IncidentsIncidents")) {continue;}
			if ($tabsmerge[$tab_id]["tab_name"] == "Compliance" && !Session::menu_perms("MenuIntelligence", "ComplianceMapping")) {continue;}
			// Check disable
			if ($tabsmerge[$tab_id]['disable']) continue;
			
			$tabshow[$tab_id] = $tabsmerge[$tab_id];
		}
		ksort($tabshow);
		
		$ctabs = count($tabshow) - 1;
		$j = 0;

		//echo '------';
		//print_r($tabshow);
		//echo '-----';
		foreach($tabshow as $tab_id => $tab_name) {
			if (strlen($tabsmerge[$tab_id]["tab_icon_url"]) > 0) {
				$image_string = '<img align="absmiddle" border="0" src="' . $tabsmerge[$tab_id]["tab_icon_url"] . '" height="20">&nbsp;';
			} else {
				$image_string = "";
			}
			$on = ($panel_id == $tab_id) ? "on" : "";
			$url = "?panel_id=$tab_id";
			if(empty($tabsmerge[$tab_id]["tab_url"])){
				// normal tabs
				$url = "?panel_id=$tab_id";
			}else{
				// tabs urls
				$url = '?panel_id='.$tab_id.'&hmenu=dashboards&smenu=dashboards';
				//$txt = "<table cellpadding='0' cellspacing='0' border='0' class='transparent'><tr><td class='nobborder'>".$image_string ."</td><td class='nobborder'><a href='".$tabsmerge[$tab_id]["tab_url"]."' class='gristab$on' target='main'>".gettext($tabsmerge[$tab_id]["tab_name"])."</a></td></tr></table>";
			}
			$txt = "<table cellpadding='0' cellspacing='0' border='0' class='transparent'><tr><td class='nobborder'>".$image_string ."</td><td class='nobborder'><a href='$url' class='gristab$on'>".gettext($tabsmerge[$tab_id]["tab_name"])."</a></td></tr></table>";
			if ($panel_id == $tab_id) { ?>
					<td style="vertical-align:bottom" class="noborder">
						<table border='0' cellpadding='0' cellspacing='0' height="26" class="noborder">
							<tr>
								<td width="16" class="noborder"><img src="../pixmaps/menu/tsl<?php echo ($j > 0) ? "2" : "" ?>.gif" border='0'/></td>
								<td style="background:url(../pixmaps/menu/bgts.gif) repeat-x bottom left;padding:0px 15px 0px 15px" class="noborder" nowrap='nowrap'><?php echo $txt ?></td>
								<td width="16" class="noborder"><img src="../pixmaps/menu/tsr<?php echo ($j == $ctabs) ? "2" : "" ?>.gif" border='0'></td>
							</tr>
						</table>
					</td>
														
			<?php
				$selected = true;
			} else { ?>
					<td style="vertical-align:bottom" class="noborder">
						<table border='0' cellpadding='0' cellspacing='0' height="26" class="transparent">
							<tr>
							<?php if (!$selected) { ?><td width="16" class="noborder"><img src="../pixmaps/menu/tul<?php echo ($j == 0) ? "2" : "" ?>.gif" border='0'></td><?php } ?>
							<td height="26" style="background:url(../pixmaps/menu/bgtu.gif) repeat-x bottom left;padding:0px 10px 0px 10px" class="noborder" nowrap='nowrap'><?php echo $txt ?></td>
							<?php if ($j == $ctabs) { ?>
							<td width="16" class="noborder"><img src="../pixmaps/menu/tur.gif" border='0'></td>
							<?php } ?>
							</tr>
						</table>
					</td>
			<?php
				$selected = false;
			}
			$j++;
		}
//}
}
?>
						<td style="width:100%;vertical-align:bottom" class="noborder">&nbsp;</td>
					</tr>
				</table>
	    	</td>
											
			<td style="vertical-align:bottom;text-align:right;background-color:#8E8E8E" class="noborder" nowrap='nowrap'>
				<table cellpadding='0' cellspacing='0' border='0' width="100%" style="background:transparent;display: <?php
							$can_edit || $show_edit ? 'inline' : 'none' ?>; margin: 0px; padding: 0px;" class="noborder">
					<tr>
						<td align="<?($tabsavt[$_GET['panel_id']] == "") ? "left" : "right"?>" class="noborder" nowrap='nowrap' style="color:white;padding-right:5px;padding-bottom:2px">
							<?php
							//if ($tabsavt[$panel_id] == "" || 1==1)
							//{
								if ($can_edit) 
								{ ?>
									<small>
										<?php echo _("Panel config") ?>:
										<?php echo _("Geom") ?>: <input id="rows" type="text" size="2" style="height:10px;width:15px;font-size:10px" value="<?php echo $rows ?>"> x
										<input id="cols" type="text" size="2" style="height:10px;width:15px;font-size:10px" value="<?php echo $cols ?>">
										<a style="color:#FFFFFF" href="#" onClick="javascript:
											panel_save($('rows').value, $('cols').value);
											panel_load($('rows').value, $('cols').value);
											"><?php echo _("Apply") ?></a>	|
									</small>
								<?php
								}
								?>
						</td>
						
						<td align="right" class="noborder" nowrap='nowrap'>
							<small class="white">
								<?php if ($show_edit && !$can_edit) { ?>
									<a style="color:#FFFFFF" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?edit=1&panel_id=<?php echo $panel_id ?>"><?php
									echo gettext("Edit"); ?></a> 
									| 		
								<?php
								} else if ($show_edit && $can_edit) { ?>
									<a style="color:#FFFFFF" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?edit=0&panel_id=<?php echo $panel_id ?>"><?php
									echo gettext("No Edit"); ?></a>
									|
							<?php } ?>
								<? //}
								//else 
								//{ 
								//   echo "<small class='white'>"; 
								//} 
								?>
								<a style="color:#FFFFFF" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?edit_tabs=1&panel_id=<?php echo $panel_id ?>"><?php
								echo gettext("Edit Tabs"); ?></a>	|
								[<a style="color:#FFFFFF" href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?fullscreen=1&panel_id=<?php echo $panel_id ?>" target="popup" onClick="wopen('<?php echo $_SERVER['SCRIPT_NAME'] ?>?fullscreen=1&panel_id=<?php echo $panel_id ?>', 'popup', 800, 600); return false;"><?php echo _("Fullscreen") ?></a>]
							</small>
						</td>
						
						<td style="vertical-align:bottom;padding:0px;padding-left:15px" class="nobborder">
							<table class="noborder" border='0' cellpadding='0' cellspacing='0' align="right" height="26">
								<tr>
									<td width="16" class="nobborder"><img src="../pixmaps/menu/tsl.gif" border='0'></td>
									<td class="nobborder" style="background:url(../pixmaps/menu/bgts.gif) repeat-x bottom left;padding-right:4px" nowrap='nowrap'>
										<a href="javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboard:dashboard','DashboardHelp');" sltyle="color:black;text-decoration:none"><img align="absmiddle" src="../pixmaps/help_icon.gif" border="0" alt="<?=_("Help")?>"></a>
									</td>
								</tr>
							</table>
						</td>
						
					</tr>		
				</table>
			</td>	
				
				
			</td>
		</tr>
	</table>
</div>


<table width="100%" class="noborder" border='0' cellpadding='0' cellspacing='0' style="background:transparent;">
	<tr><td height="35" class="noborder">&nbsp;</td></tr>
</table>


