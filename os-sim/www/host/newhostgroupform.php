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
require_once ('ossim_conf.inc');
require_once ('classes/Sensor.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_group.inc');
require_once ('classes/Host_group_scan.inc');
require_once ('classes/Host_sensor_reference.inc');
require_once ('classes/RRD_config.inc');
Session::logcheck("MenuPolicy", "PolicyHosts");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
	<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
	<link type="text/css" rel="stylesheet" href="../style/tree.css" />
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/urlencode.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/jquery.simpletip.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){

			$(".sensor_info").simpletip({
				position: 'top',
				offset: [-60, -10],
				content: '',
				baseClass: 'ytooltip',
				onBeforeShow: function() {
						var txt = this.getParent().attr('txt');
						this.update(txt);
				}
			});

		});
	</script>



	<script type="text/javascript">
		//var loading = '<br><img src="../pixmaps/theme/ltWait.gif" border="0" align="absmiddle"> Loading tree...';
		var layer = null;
		var nodetree = null;
		var i=1;
		var addnodes = false;
	
		function load_tree(filter) {
			combo = 'hosts';
			if (nodetree!=null) {
				nodetree.removeChildren();
				$(layer).remove();
			}
			layer = '#srctree'+i;
			$('#container').append('<div id="srctree'+i+'" style="width:100%"></div>');
			$(layer).dynatree({
				initAjax: { url: "draw_tree.php", data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) {
					if (!dtnode.hasChildren()) {
						if (!dtnode.data.url.match(/\:/)) {
							// add from a final node
							addto(combo,dtnode.data.url,dtnode.data.url)
						} else {
							// simulate expand and load
							addnodes = true;
							dtnode.toggleExpand();
						}
					} else {
						// add all children nodes
						var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
						for (c=0;c<children.length; c++)
							addto(combo,children[c].data.url,children[c].data.url)
					}
				},
				onDeactivate: function(dtnode) {;},
				onLazyRead: function(dtnode){
					// load nodes on-demand
					dtnode.appendAjax({
						url: "draw_tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page},
						success: function(options,selfnode) {
							if (addnodes) {
								addnodes = false;
								var children = dtnode.tree.getAllNodes(dtnode.data.key.replace('.','\\.')+'\\.');
								for (c=0;c<children.length; c++)
									addto(combo,children[c].data.url,children[c].data.url)
							}
						}
					});
				}
			});
			nodetree = $(layer).dynatree("getRoot");
			i=i+1

		}
	
		function submit_form(form) {
			selectall('hosts');
			form.submit();
		}
		
		$(function(){
			load_tree("");
		});
	</script>
	
	<style type='text/css'>
		.std_inp, .std_select, .std_txtarea {width: 90%; height: 18px;}
		.std_inp2 {width: 85%; height: 18px;}
		.std_txtarea { height: 45px;}
	</style>
	
</head>
<body>

<?php
if (GET('withoutmenu') != "1") 
	include ("../hmenu.php"); ?>

<?php

$db = new ossim_db();
$conn = $db->connect();
$conf = $GLOBALS["CONF"];
$threshold = $conf->get_conf("threshold");
$name = GET('name');
ossim_valid($name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE, OSS_SQL, 'illegal:' . _("name"));
if (ossim_error()) {
    die(ossim_error());
}
$all = array();
$hg_name = $hg_desc = $nessus = $nagios = "";
$hg_thra = $hg_thrc = $conf->get_conf("threshold");
$host_list = $hg_sensors = array();

if ($name != "")
{
    if ($host_group_list = Host_group::get_list($conn, "WHERE name = '$name'")) {
        $host_group = $host_group_list[0];
        $hg_name = $host_group->get_name();
        $hg_desc = $host_group->get_descr();
        $hg_thrc = $host_group->get_threshold_c();
        $hg_thra = $host_group->get_threshold_a();
        $host_list = $host_group->get_hosts($conn);
        $nessus = ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$name' AND plugin_id = 3001")) ? "checked='checked'" : "";
        $nagios = ($scan_list = Host_group_scan::get_list($conn, "WHERE host_group_name = '$name' AND plugin_id = 2007")) ? "checked='checked'" : "";
        $rrd_profile = $host_group->get_rrd_profile();
        if (!$rrd_profile) $rrd_profile = "None";
        $tmp_sensors = $host_group->get_sensors($conn);
        foreach($tmp_sensors as $sensor) $hg_sensors[] = $sensor->get_sensor_name();
    }
}
?>

<form method="post" action="<?php echo ($name != "") ? "modifyhostgroup.php" : "newhostgroup.php" ?>">

<table align="center" class='noborder' style='background-color: transparent;'>
	<tr>
		<td valign="top" class="nobborder">
		<table align="center">
			<input type="hidden" name="insert" value="insert"/>
			<tr>
				<th> <?php echo gettext("Name"); ?> </th>
				<td class="left">
					<? if ($name == "") { ?>
					<input type="text" name="name" size="30" class='std_inp' value="<?=((REQUEST('name')!="") ? REQUEST('name') : $hg_name )?>"/>
					<span style="padding-left: 3px;">*</span>
					<? } else { ?>
					<input type="hidden" name="name" value="<?=$name?>"/> <b><?=$name?></b>
					<? } ?>
				</td>
			</tr>

			<tr>
				<th><?php echo gettext("Hosts"); ?> <br/>
					<span><a href="newhostform.php"><?php echo gettext("Insert new host"); ?> ?</a><br/></span>
				</th>
				<td class="left nobborder">
					<select id="hosts" name="ips[]" size="20" multiple="multiple" style="width:250px">
					<?php
						if(count($host_list)==0 && count($_POST['ips'])!=0){
							$list = $_POST['ips'];
							foreach($list as $v) echo "<option value='$v'>$v\n";
						}
						else
						{
							foreach($host_list as $host) {
								$ip = $host->get_host_ip($conn);
								echo "<option value='$ip'>$ip\n";
							}
						} 
					?>
					</select>
					<span style="padding-left: 3px; vertical-align: top;">*</span>
					<input type="button" value=" [X] " onclick="deletefrom('hosts')" class="lbutton"/>
				</td>
			</tr>

			<tr>
				<th> <?php echo gettext("Description"); ?> </th>
				<td class="left">
					<textarea name="descr" class='std_txtarea'><?=((REQUEST('descr') != "") ? REQUEST('descr') : $hg_desc)?></textarea>
				</td>
			</tr>

			<tr>
				<th> 
					<?php echo gettext("Sensors"); ?> 
					<a style="cursor:pointer; text-decoration: none;" class="sensor_info" txt="<div style='width: 150px; white-space: normal; font-weight: normal;'>Define which sensors has visibility of this host</div>">
					<img src="../pixmaps/help.png" width="16" border="0" align="absmiddle"/>
					</a><br/>
					<span><a href="../sensor/newsensorform.php"><?php echo gettext("Insert new sensor"); ?> ?</a></span>
				</th>
				<td class="left">
					<?php
					/* ===== sensors ==== */
					$i = 1;
					
					if ($sensor_list = Sensor::get_all($conn, "ORDER BY name"))
					{
						foreach($sensor_list as $sensor) {
							$sensor_name = $sensor->get_name();
							$sensor_ip = $sensor->get_ip();
							if ($i == 1) {
								echo "<input type='hidden' name='nsens' value='".count($sensor_list)."'/>";
							}
							$sname = "sboxs" . $i;
							$checked = ( in_array($sensor_name, $hg_sensors) || REQUEST($sname)!="" )  ? "checked='checked'"  : '';
							
							echo "<input type='checkbox' name='$sname' value='$sensor_name' $checked/>";
							echo $sensor_ip . " (" . $sensor_name . ")<br>"; 
						  
							$i++;
						}
					}
					?>
				</td>
			</tr>

			<tr>
				<td style="text-align: left; border:none; padding-top:3px;">
				<a onclick="$('.advanced').toggle()" style="cursor:pointer;">
				<img border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/>Advanced</a></td>
			</tr>
	 
			<tr class="advanced" style="display:none;">
				<th> <?php echo gettext("Scan options"); ?> </th>
				<td class="left"><!--
					<input type="checkbox" name="nessus" value="1"  <?=((REQUEST('nessus') == 1) ? "checked='checked'" : $nessus)?>/>
					<?php echo gettext("Enable nessus scan"); ?> </input><br/>-->
					<input type="checkbox" name="nagios" value="1" <?=((REQUEST('nagios') == 1) ? "checked='checked'" : $nagios)?>/>
					<?php echo gettext("Enable nagios scan"); ?> 
				</td>
			</tr>

			<tr class="advanced" style="display:none;">
				<th> <?php echo gettext("RRD Profile"); ?> <br/>
					<span><a href="../rrd_conf/new_rrd_conf_form.php"><?php echo gettext("Insert new profile"); ?> ?</a></span>
				</th>
				<td class="left">
					<select name="rrd_profile" class='std_select'>
						<option value=""><?=gettext("None");?></option>
						<?php
						foreach(RRD_Config::get_profile_list($conn) as $profile) {
							if (strcmp($profile, "global")) {
								$selected = ( $rrd_profile == $profile || REQUEST('rrd_profile') ==$profile ) ? " selected='selected'" : '';
								echo "<option value=\"$profile\" $selected>$profile</option>\n";
							}
						}
						?>
					</select>
				</td>
			</tr>

			<tr class="advanced" style="display:none;">
				<th> <?php echo gettext("Threshold C"); ?> </th>
				<td class="left">
					<input type="text" name="threshold_c" class='std_inp' size="11" value="<?=((REQUEST('threshold_c')!="") ? REQUEST('threshold_c') : $hg_thrc )?>"/>
				</td>
			</tr>

			<tr class="advanced" style="display:none;">
				<th> <?php echo gettext("Threshold A"); ?> </th>
				<td class="left">
					<input type="text" name="threshold_a" class='std_inp' size="11" value="<?=((REQUEST('threshold_a')!="") ? REQUEST('threshold_a') : $hg_thra )?>"/>
				</td>
			</tr>
	  
			<tr>
				<td colspan="2" class="nobborder" style="text-align:center;padding:10px">
					<input type="button" value="<?php echo ($name != "") ? _("Modify") : _("OK") ?>" class="button" onclick="submit_form(this.form)">
					<?php
						if ($name == "") { ?><input type="reset" value="<?=_("Reset")?>" class="button"/>
					<?php } ?>
				</td>
			</tr>
		</table>
		</td>
	
		<td class="left nobborder" valign="top">
			<div style='float: left; width:280px; height:30px;'><?=_("Filter")?>: <input type="text" class='std_inp2' id="filter" name="filter"/></div>
			<div style='float: right; width:50px; height:30px;'><input type="button" value="<?=_("Apply")?>" onclick="load_tree(this.form.filter.value)" class="lbutton"/></div>
			<div id="container" style="width:350px; padding-top:10px; clear: both;"></div>
		</td>
	</tr>
	
	<tr>
		<td class='nobborder'>
			<p align="center" style="font-style: italic;"><?php echo gettext("Values marked with (*) are mandatory"); ?></p>
		</td>
		<td class='nobborder'></td>
	</tr>
</table>
</form>

<?php $db->close($conn); ?>

</body>
</html>

