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
$timetz = $GLOBALS["timetz"];
$today_d = gmdate("d",$timetz);
$today_m = gmdate("m",$timetz);
$today_y = gmdate("Y",$timetz);
$today_h = gmdate("h",$timetz);
//$yesterday_d = date("d",mktime(0,0,0, $today_m, $today_d - 1, $today_y));
//$yesterday_m = date("m",mktime(0,0,0, $today_m, $today_d - 1, $today_y));
//$yesterday_y = date("Y",mktime(0,0,0, $today_m, $today_d - 1, $today_y));
$yesterday_d = gmdate("d", strtotime("-1 day",$timetz));
$yesterday_m = gmdate("m", strtotime("-1 day",$timetz));
$yesterday_y = gmdate("Y", strtotime("-1 day",$timetz));
//$week_d = date("d",mktime(0,0,0, $today_m, $today_d - (date("w") +1), $today_y));
//$week_m = date("m",mktime(0,0,0, $today_m, $today_d - (date("w") +1), $today_y));
//$week_y = date("Y",mktime(0,0,0, $today_m, $today_d - (date("w") +1), $today_y));
$week_d = gmdate("d", strtotime("-1 week",$timetz));
$week_m = gmdate("m", strtotime("-1 week",$timetz));
$week_y = gmdate("Y", strtotime("-1 week",$timetz));
//$two_week_d = date("d",mktime(0,0,0, $today_m, $today_d - 7 - (date("w") +1), $today_y));
//$two_week_m = date("m",mktime(0,0,0, $today_m, $today_d - 7 -  (date("w") +1), $today_y));
//$two_week_y = date("Y",mktime(0,0,0, $today_m, $today_d - 7 -  (date("w") +1), $today_y));
$two_week_d = gmdate("d", strtotime("-2 week",$timetz));
$two_week_m = gmdate("m", strtotime("-2 week",$timetz));
$two_week_y = gmdate("Y", strtotime("-2 week",$timetz));
//$month_d = date("d",mktime(0,0,0, $today_m, 1, $today_y));
//$month_m = date("m",mktime(0,0,0, $today_m, 1, $today_y));
//$month_y = date("Y",mktime(0,0,0, $today_m, 1, $today_y));
$month_d = gmdate("d", strtotime("-1 month",$timetz));
$month_m = gmdate("m", strtotime("-1 month",$timetz));
$month_y = gmdate("Y", strtotime("-1 month",$timetz));
//$two_month_d = date("d",mktime(0,0,0, $today_m - 1, 1, $today_y));
//$two_month_m = date("m",mktime(0,0,0, $today_m - 1, 1, $today_y));
//$two_month_y = date("Y",mktime(0,0,0, $today_m - 1, 1, $today_y));
$two_month_d = gmdate("d", strtotime("-2 month",$timetz));
$two_month_m = gmdate("m", strtotime("-2 month",$timetz));
$two_month_y = gmdate("Y", strtotime("-2 month",$timetz));
//$year_d = date("d",mktime(0,0,0, 1, 1, $today_y));
//$year_m = date("m",mktime(0,0,0, 1, 1, $today_y));
//$year_y = date("Y",mktime(0,0,0, 1, 1, $today_y));
$year_d = gmdate("d", strtotime("-11 month",$timetz));
$year_m = gmdate("m", strtotime("-11 month",$timetz));
$year_y = gmdate("Y", strtotime("-11 month",$timetz));
//$two_year_d = date("d",mktime(0,0,0, 1, 1, $today_y-1));
//$two_year_m = date("m",mktime(0,0,0, 1, 1, $today_y-1));
//$two_year_y = date("Y",mktime(0,0,0, 1, 1, $today_y-1));
$two_year_d = gmdate("d", strtotime("-2 year",$timetz));
$two_year_m = gmdate("m", strtotime("-2 year",$timetz));
$two_year_y = gmdate("Y", strtotime("-2 year",$timetz));

$sensor = ($_GET["sensor"] != "") ? $_GET["sensor"] : $_SESSION["sensor"];

$sterm = ($_GET['search_str'] != "") ? $_GET['search_str'] : ($_SESSION['search_str'] != "" ? $_SESSION['search_str'] : _("search term"));
$risk = ($_GET["ossim_risk_a"] != "") ? $_GET["ossim_risk_a"] : $_SESSION["ossim_risk_a"];
?>

<style type="text/css">

#views table, #taxonomy table, #mfilters table, #report table  {
    background:none repeat scroll 0 0 #FAFAFA;
    border:1px solid #BBBBBB;
    color:black;
    text-align:center;
   -moz-border-radius:8px 8px 8px 8px;
   padding: 2px;
}

#views table tr td, #taxonomy table tr td, #mfilters table tr td, #report table tr td{
    padding: 0;
}
#views table tr td input, #views table, 
#taxonomy table tr td input, #taxonomy table,
#taxonomy table tr td input, #report table,
#mfilters table tr td input, #mfilters table
{
    font-size: 0.9em;
    line-height: 0.5em;
}

#views table tr td ul{
    padding: 0px;
}
#views table tr td ul li{
    padding: 0px 0px 0px 12px;
    list-style-type: none;
    text-align: left;
    margin: 0px;
    clear:left;
    position: relative;
    height: 23px;
    line-height: 1em;
}
.par{
    background: #f2f2f2;
}
.impar{
    background: #fff;
}
#views table tr th, #taxonomy table tr th, #mfilters table tr th{
    background:url("../pixmaps/theme/ui-bg_highlight-soft_75_cccccc_1x300.png") repeat-x scroll 50% 50% #CCCCCC;
    border:1px solid #AAAAAA;
    color:#222222;
    font-size:11px;
    font-weight:bold;
    padding:0 10px;
    text-align:center;
    white-space:nowrap;
    -moz-border-radius:5px 5px 5px 5px;
}


#viewbox{
	font-size: 1.5em;
	margin: 0.5em;
}

#dhtmltooltip{
position: absolute;
width: 150px;
border: 2px solid black;
padding: 2px;
background-color: lightyellow;
visibility: hidden;
z-index: 100;
}

img{
	vertical-align:middle;
}
small {
	font:12px arial;
}

#maintable{
background-color: white;
}
#viewtable{
background-color: white;
}
.negrita { font-weight:bold; font-size:14px; }
.thickbox { color:gray; font-size:10px; }
.header{
line-height:28px; height: 28px; background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 0% 0%; color: rgb(51, 51, 51); font-size: 12px; font-weight: bold; text-align:center;
}
</style>

<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>

<form action="../conf/solera.php" method="post" id="solera_form">
<input type="hidden" name="from">
<input type="hidden" name="to">
<input type="hidden" name="src_ip">
<input type="hidden" name="dst_ip">
<input type="hidden" name="src_port">
<input type="hidden" name="dst_port">
<input type="hidden" name="proto">
</form>

<!-- MAIN HEADER TABLE -->
<table width='100%' cellspacing=0 border='0' align="center" class="headermenu" style="background-color:white;border:0px solid white">
<tr>
	<td valign="top" width="380" style="border-top:1px solid #CCCCCC;border-left:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;background:url('../pixmaps/fondo_hdr2.png') repeat-x">
	
<form name="QueryForm" id="frm" ACTION="base_qry_main.php" method="GET" style="margin:0 auto">
<input type='hidden' name="search" value="1" />
<input type="hidden" name="sensor" id="sensor" value="<?php echo $sensor?>" />
<input type="submit" name="bsf" id="bsf" value="Query DB" style="display:none">

<!--<input type='hidden' name="saved_get" value='<?php
//= serialize($_GET)
 ?>'>-->
<table width='100%' border='0' align="center">
<tr>
	<td>
		<table width='100%'>
			<tr>
				<td class='menuitem' nowrap>
				<a class='menuitem' href='<?php echo $BASE_urlpath ?>/base_qry_main.php?new=1'><font style="font-size:18px;color:#333333"><?php echo gettext("Search") ?></font></a>&nbsp;&nbsp;<font style="color:green;font-weight:bold;font-size:16px">|</font>&nbsp;&nbsp;
				<a class='menuitem' href='<?php echo $BASE_urlpath ?>/base_qry_main.php?time_range=all&clear_allcriteria=1&submit=Query+DB'><font style="font-size:18px;color:#333333"><?php echo gettext("Clear") ?></font></a>&nbsp;&nbsp;
				<?php
if ($Use_Auth_System == 1) {
?>
				|&nbsp;&nbsp;<a class='menuitem' href='<?php echo $BASE_urlpath
?>/base_user.php'><?php echo _USERPREF
?></a>
				&nbsp;&nbsp;|&nbsp;&nbsp;<a class='menuitem' href='<?php echo $BASE_urlpath
?>/base_logout.php'><?php echo _LOGOUT
?></a>
				<?php
}
?>
				</td>
				<td align="right">
					<table border='0' cellpadding='0' cellspacing='0'> 
					<tr>
						<td align="right">
							<table width="100%">
								<tr>			
									<td>
										<table border='0' cellpadding='0' cellspacing='0'>
										<tr><td align='right'>
											<a href="javascript:history.go(-1)" style="color:black;font-size:12px;font-weight:bold"><?php echo _("Back")?> <img src='./images/back.png' alt='<?php echo _("Back")?>' title='<?php echo _("Back")?>' border='0' align='absmiddle'></a> &nbsp;<font style="color:green;font-weight:bold;font-size:14px">|</font>&nbsp; <a href="javascript:document.location.reload()" style="color:black;font-size:12px;font-weight:bold"><?php echo _("Refresh")?> <img src='../pixmaps/refresh.png' alt='<?php echo _("Refresh")?>' title='<?php echo _("Refresh")?>' border='0' align='absmiddle'></a><!--<a href="javascript:history.go(-1)"><img src='./images/back.png' alt='Back' title='Back' border='0' align='absmiddle'></a>-->
											</td>
										<td align='right'> <?php echo str_replace (">Back","><span style='padding-left: 5px; color: rgb(51, 51, 51); font-weight: bold;font-size:14px;'>Back</span>",str_replace("|","<font style='color:green;font-size:14px;font-weight:bold'></font>",$back_link))
			?>  <!--<a style="color:black;font-size:12px;font-weight:bold" href="base_qry_main.php?submit=--><?php //echo _QUERYDB
			?><!--">Return to Main</a> &nbsp;<font style="color:green;font-size:14px;font-weight:bold">|</font>&nbsp; --><!--<a class='menuitem' href='--><?php //echo $BASE_urlpath
			?><!--/base_maintenance.php' style="color:black;font-size:12px;font-weight:bold">Administration</a> --></td></tr></table></td>
								</tr>
							</table>
						</td>
					</tr>			
					</table>						
				</td>
			
				<!--
				<TD class="menuitem"><FONT color="#8D4102"><B>Cached:&nbsp&nbsp</B></FONT>
			        <A class="menuitem" href="base_stat_alerts.html">Uniq</A> &nbsp&nbsp|&nbsp&nbsp
			        <A class="menuitem" href="base_stat_uaddr1.html">Src</A> &nbsp&nbsp|&nbsp&nbsp
			        <A class="menuitem" href="base_stat_uaddr2.html">Dst</A> &nbsp&nbsp|&nbsp&nbsp
			        <A class="menuitem" href="base_stat_ports2.html">Dst Port</A>
			    </td>-->
			</tr>
		</table>
	</td>
</tr>

<!--<tr><td style="padding-left:10px;padding-right:10px"><table width="100%" cellpadding=0 cellspacing=0 border=0><tr><td style="background:url('../pixmaps/points.gif') repeat-x"><img src="../pixmaps/points.gif"></td></tr></table></td></tr>-->

<tr>
	<td colspan='2'>
		<table border='0' cellpadding='0' cellspacing='0' width='100%'>

			<tr>
				<td align="left">
					<table width='100%'>
						<tr>
							<td><input type="text" name="search_str" id="search_str" style="width:180px;height:22px" class="gr" value="<?php echo $sterm ?>" onfocus="if(this.value=='search term') { this.value=''; this.className='ne'; }"></td>
							<td><img src="../pixmaps/search_icon.png" border=0 alt="You can use +,-,* modifiers" title="You can use +,-,* modifiers"></td>
							<td align="right">
								<table>
									<tr>
										<td>
										<input type="button" class="button" value="IP" onclick="$('#ipsearch').toggle()" style='width:30px'>&nbsp;
										<!-- SRC / DST IP combo -->
										<div style="position:relative; z-index:2; text-align:left">
											<div id="ipsearch" style="position:absolute;top:0;display:none;margin-top:1px">
												<input type="submit" class="button" value="<?=_("Src or Dst IP")?>" name="submit" style='width:85px;font-size:11px'>
												<br>
												<input type="submit" class="button" value="<?=_("Src IP")?>" name="submit" style='width:85px;font-size:11px'>
												<br>
												<input type="submit" class="button" value="<?=_("Dst IP")?>" name="submit" style='width:85px;font-size:11px'>
											</div>
										</div>	
										</td><td>
										<input type="submit" class="button" value="<?php echo gettext("Signature")?>" id="signature" name="submit">&nbsp;
										<input type="submit" class="button" value="Payload" name="submit">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td nowrap='nowrap' colspan='2'>
				<table cellpadding="0" cellspacing="2" width='100%'>
				<tr>
					<td><?=_("Sensor")?> </td>
					<td><?=_("Data Sources")?> </td>
					<td><?=_("Risk")?> </td>
				</tr>
				<tr>
					<td align="left">
						<input type="text" size="10" name="sip" id="sip" style="width:170px">
					</td>
					<td align="left"><select name="plugin" class="selectp" style="width:170px" onchange="$('input[name=sourcetype],#category,#subcategory').val('');this.form.bsf.click()"><option value=''></option> 
					<?php //mix_sensors(this.options[this.selectedIndex].value);
					$snortsensors = GetSensorSids($db);
					$sns = array();
					$sensor_keys = array();
					if (Session::allowedSensors() != "") {
						$user_sensors = explode(",",Session::allowedSensors());
						foreach ($user_sensors as $user_sensor)
							$sensor_keys[$user_sensor]++;
					}
					else $sensor_keys['all'] = 1;
					foreach($snortsensors as $ip => $sids) {
						//$ip = preg_replace ("/^\[.+\]\s*/","",$ip);
						$sid = implode(",", $sids);
						$sname = ($sensors[$ip] != "") ? $sensors[$ip] : (($hosts[$ip] != "") ? $hosts[$ip] : "");
						$sns[$sname] = array($ip,$sid);
					}
					// sort by sensor name
					ksort($sns);
					$str = $ipsel = "";
					foreach ($sns as $sname => $ip) {
						if ($sensor_keys['all'] || $sensor_keys[$ip[0]]) {
							$ip[0] = ($sname != "") ? "$sname [" . $ip[0] . "]" : $ip[0];
							$ip[0] = preg_replace ("/^\[(.+)\]\s*(.+)/","\\1 [\\2]",$ip[0]);
							if ($ipsel=="" && $ip[1] != "" && $sensor == $ip[1]) $ipsel = "$('#sip').val('".$ip[0]."');";
							$str .= '{ txt:"'.$ip[0].'", id: "'.$ip[1].'" },';
						}
					}
					//$snortsensors = GetSensorPluginSids($db,$sensor_keys);
					$snortsensors = GetPlugins($db);
					uksort($snortsensors, "strnatcasecmp");;
					/*foreach($snortsensors as $plugin => $sids) {
						$sid = implode(",", $sids);
						$sel = ($sid != "" && $sensor == $sid) ? "selected" : "";
						//$id_plugin = $plugins[$plugin];
						echo "<option value='$sid' $sel>$plugin</option>\n";
					}*/
					foreach($snortsensors as $plugin_name => $pids) {
						$pid = implode(",", $pids);
						$sel = ($pid != "" && ($_SESSION["plugin"] == $pid || in_array($_SESSION["plugin"],$pids))) ? "selected" : "";
						echo "<option value='$pid' $sel>$plugin_name</option>\n";
					}
					?>
					</select></td>
					<td align="left"><select name="ossim_risk_a" class="selectp" style="width:60px" onchange="this.form.bsf.click()"><option value=' '>
					<option value="low"<?php if ($risk == "low") echo " selected" ?>>Low</option>
					<option value="medium"<?php if ($risk == "medium") echo " selected" ?>>Medium</option>
					<option value="high"<?php if ($risk == "high") echo " selected" ?>>High</option>
					</select></td>
				</tr>
				
				<tr>
					<td colspan='3'>
						<table cellpadding='0' cellspacing='0' width='100%'>
							<tr>
								<td colspan='2' style="text-align:left;  padding: 5px 5px 10px 0px; width:50%">
									<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#mfilters').toggle();$('#taxonomy').hide();"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("More Filters")?></a>
									   <div style="position:relative; z-index:1">
											<div id="mfilters" style="position:absolute;left:0;top:0;display:none">
												<table cellpadding='0' cellspacing='0' align="center" style='width: 300px;'>
													<tr>
														<th style="padding-right:3px">
															<div style='float:left; width:60%; text-align: right;'><?php echo _("More Filters")?></div>
															<div style='float:right; width:18%; padding-top: 2px; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#mfilters').toggle(); $('#taxonomy').hide();"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
														</th>
													</tr>
													<tr class="noborder">
														<td>
															<table style="border:0px">
																<tr>
																	<td style="font-size:11px"><?php echo _("Extra Data")?>:</td>
																	<td style="padding-left:10px;text-align:left;">
																	<select name="userdata[0]">
																		<option value="userdata1"<?= ($_SESSION["userdata"][0]=="userdata1") ? " selected" : ""; ?>>userdata1</option>
																		<option value="userdata2"<?= ($_SESSION["userdata"][0]=="userdata2") ? " selected" : ""; ?>>userdata2</option>
																		<option value="userdata3"<?= ($_SESSION["userdata"][0]=="userdata3") ? " selected" : ""; ?>>userdata3</option>
																		<option value="userdata4"<?= ($_SESSION["userdata"][0]=="userdata4") ? " selected" : ""; ?>>userdata4</option>
																		<option value="userdata5"<?= ($_SESSION["userdata"][0]=="userdata5") ? " selected" : ""; ?>>userdata5</option>
																		<option value="userdata6"<?= ($_SESSION["userdata"][0]=="userdata6") ? " selected" : ""; ?>>userdata6</option>
																		<option value="userdata7"<?= ($_SESSION["userdata"][0]=="userdata7") ? " selected" : ""; ?>>userdata7</option>
																		<option value="userdata8"<?= ($_SESSION["userdata"][0]=="userdata8") ? " selected" : ""; ?>>userdata8</option>
																		<option value="userdata9"<?= ($_SESSION["userdata"][0]=="userdata9") ? " selected" : ""; ?>>userdata9</option>
																		<option value="filename"<?= ($_SESSION["userdata"][0]=="filename") ? " selected" : ""; ?>>filename</option>
																		<option value="username"<?= ($_SESSION["userdata"][0]=="username") ? " selected" : ""; ?>>username</option>
																		<option value="password"<?= ($_SESSION["userdata"][0]=="password") ? " selected" : ""; ?>>password</option>
																	</select>
																	<select name="userdata[1]">
																		<option value="="<?= ($_SESSION["userdata"][1]=="=") ? " selected" : ""; ?>>=</option>
																		<option value="<>"<?= ($_SESSION["userdata"][1]=="<>") ? " selected" : ""; ?>><></option>
																		<option value="<"<?= ($_SESSION["userdata"][1]=="<") ? " selected" : ""; ?>><</option>
																		<option value="<="<?= ($_SESSION["userdata"][1]=="<=") ? " selected" : ""; ?>><=</option>
																		<option value=">"<?= ($_SESSION["userdata"][1]==">") ? " selected" : ""; ?>>></option>
																		<option value=">="<?= ($_SESSION["userdata"][1]==">=") ? " selected" : ""; ?>>>=</option>
																		<option value="like"<?= ($_SESSION["userdata"][1]=="like") ? " selected" : ""; ?>>like</option>
																	</select>
																</tr>
																<tr>
																	<td></td>
																	<td style="padding-left:10px;text-align:left">
																	<input type="text" name="userdata[2]" style="width:158px" value="<?php echo $_SESSION["userdata"][2] ?>"></input>
																	<input type="button" class="button" value="<?php echo _("Apply")?>" onclick="this.form.bsf.click()" style="height:18px"></input></td>
																</tr>
															</table>
														</td>
													</tr>													
													<tr class="noborder">
														<td style='padding:5px;'>
															<div style='text-align: left; padding-bottom: 15px; clear: both;'>
																<div style='float: left; width:90px; font-size:11px'><?=_("DS Groups")?>:</div>
																<div style='float: left;'>
																	<select name="plugingroup" class="selectp" style="width:185px" onchange="this.form.bsf.click()"><option value=''></option> 
																	<?
																	// 
																	$pg = GetPluginGroups($db);
																	foreach ($pg as $idpg => $namepg) echo "<option value='$idpg'".(($_SESSION["plugingroup"]==$idpg) ? " selected" : "").">$namepg</option>\n";
																	?>
																	</select>
																</div>
															</div>
														</td>
													</tr>
													<tr class="noborder">
														<td style='padding:5px;'>
															<div style='text-align: left; padding-bottom: 15px; clear: both;'>
																<div style='float: left; width:90px; font-size:11px'><?=_("Network Groups")?>:</div>
																<div style='float: left;'>
																	<select name="networkgroup" class="selectp" style="width:185px" onchange="this.form.bsf.click()"><option value=''></option> 
																	<?
																	// 
																	$ng = GetOssimNetworkGroups();
																	foreach ($ng as $nameng) echo "<option value='$nameng'".(($_SESSION["networkgroup"]==$nameng) ? " selected" : "").">$nameng</option>\n";
																	?>
																	</select>
																</div>
															</div>
														</td>
													</tr>													
													<tr class="noborder">
														<td style='padding:5px;'>
															<div style="text-align:left;padding-right:10px;font-size:11px;float:left;"><?=_("Home networks")?> <img src="images/homelan.png" border=0 align="absmiddle"></div>
															<?
																$src_url = $actual_url."addhomeips=src";
																$dst_url = $actual_url."addhomeips=dst";
															?>
															<div style='float:left;'>
																<a style='font-weight: bold;' href="<?=$src_url?>"><?=_("By source")?></a>
																<span style="color:green;font-weight:bold">&nbsp;|&nbsp;</span>
																<a style='font-weight: bold;' href="<?=$dst_url?>"><?=_("By destination")?></a>
															</div>
														</td>
													</tr>
												</table>
											</div>
										</div>
									</td>
								
									<? if (!$opensource) { ?>
									
									<td style="text-align:right; padding: 5px 5px 10px 0px; width:50%">
									
									<?php //$display= (!empty($_SESSION["sourcetype"]) || $_SESSION["category"][0] !=0 ) ? "" : "display:none;"
									$display = "display:none"; ?>
																		
									<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#taxonomy').toggle(); $('#mfilters').hide();"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/><?php echo _("Taxonomy Filters")?></a>
									   <div style="position:relative; z-index:2; text-align:left;">
											<div id="taxonomy" style="position:absolute;left:-60;top:0;<?=$display?>">
												<table cellpadding='0' cellspacing='0' align="center" style='width: 270px;'>
													<tr>
														<th style="padding-right:3px">
															<div style='float:left; width:60%; text-align: right;'><?php echo _("Taxonomy")?></div>
															<div style='float:right; width:18%; padding-top: 2px; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#taxonomy').toggle(); $('#mfilters').hide();"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
														</th>
													</tr>
													<tr class="noborder">
														<td style='padding:5px;'>										    											
															<div style='text-align: left; padding-bottom: 15px; clear: both;'>
																<div style='float: left; width:90px;'><?=_("Product Type")?>:</div>
																<div style='float: left;'>
																	<select name="sourcetype" class="selectp" style="width:155px" onchange="$('input[name=plugin]').val('');this.form.bsf.click()"><option value=''></option> 
																	<?
																	// <select name="plugingroup" class="selectp" style="width:185px" onchange="this.form.bsf.click()"><option value=''></option>
																	//$pg = GetPluginGroups($db);
																	//foreach ($pg as $idpg => $namepg) echo "<option value='$idpg'".(($_SESSION["plugingroup"]==$idpg) ? " selected" : "").">$namepg</option>";
																	$srctypes = GetSourceTypes($db);
																	foreach ($srctypes as $srctype) echo "<option value=\"$srctype\"".(($_SESSION["sourcetype"]==$srctype) ? " selected" : "").">$srctype</option>\n";
																	?>
																	</select>
																</div>
															</div>
														
															<div style='text-align: left; padding-bottom: 15px; clear: both;'>
																<div style='float: left; width:90px;'><?=_("Event Category")?>:</div>
																<div style='float: left;'>
																	<select name="category[0]" id="category" class="selectp" style="width:155px" onchange="$('input[name=plugin]').val('');this.form.bsf.click()"><option value=''></option> 
																	<?
																	$categories = GetPluginCategories($db);
																	foreach ($categories as $idcat => $category) echo "<option value=\"$idcat\"".(($_SESSION["category"][0]!=0 && $_SESSION["category"][0]==$idcat) ? " selected" : "").">$category</option>\n";
																	?>
																	</select>
																</div>
															</div>
														
															<div style='text-align: left; padding-bottom: 15px; clear: both;'>
																<div style='float: left; width:90px;'><?=_("Sub-Category")?>:</div>
																<div style='float: left;'>
																	<?
																	$subcategories = GetPluginSubCategories($db,$categories);
																	?>
																	<select name="category[1]" id="subcategory" class="selectp" style="width:155px" onchange="$('input[name=plugin]').val('');this.form.bsf.click()"><option value=''></option> 
																	<?
																	if (is_array($subcategories[$_SESSION["category"][0]])) {
																		foreach ($subcategories[$_SESSION["category"][0]] as $idscat => $subcategory) 
																		   echo "<option value=\"$idscat\"".(($_SESSION["category"][1]!=0 && $_SESSION["category"][1]==$idscat) ? " selected" : "").">$subcategory</option>\n";
																	}
																	?>
																	</select>
																</div>
															</div>
														</td>
													</tr>
												</table>
											</div>
										</div>
									</td>
								<? } ?>
					
							</tr>
						</table>
					</td>
				</tr>
                </table>
			</td></tr>
		</table>
	</td>
</tr>

<!--<tr><td style="padding-top:5px"><table width="100%" cellpadding=0 cellspacing=0 border=0><tr><td style="background:url('../pixmaps/points.gif') repeat-x"><img src="../pixmaps/points.gif"></td></tr></table></td></tr>-->

<?php
$urltimecriteria = $_SERVER['SCRIPT_NAME'];
$params = "";
// Clicked from qry_alert or clicked from Time profile must return to main
if (preg_match("/base_qry_alert|base_stat_time/", $urltimecriteria)) {
    $urltimecriteria = "base_qry_main.php";
}
if ($_GET["addr_type"] != "") $params.= "&addr_type=" . $_GET["addr_type"];
if ($_GET["sort_order"] != "") $params.= "&sort_order=" . $_GET["sort_order"];

$txtzone = "<a href=\"javascript:;\" class=\"scriptinfoimg\" txt=\"<img src='../pixmaps/timezones/".rawurlencode(Util::timezone($GLOBALS["tz"])).".png' border=0>\">".Util::timezone($GLOBALS["tz"])."</a>";                         
?>

<tr>
	<td>
		<table>
			<tr>
				<td>
					<table width='100%'><tr>
					<td>
						<table cellpadding="0" cellspacing="0">
						<tr>
						<td><?=_("Time frame selection")." $txtzone"?>:&nbsp;</td>
						<td style='text-align:left;'>
							<div id="widget">
								<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0"></a>
								<div id="widgetCalendar"></div>
							</div>
						</td>
						</tr>
						</table>
					</td>
					<td align="right">
						<? if (preg_match("/base_qry_main|base_timeline/", $urltimecriteria)) { ?>
                        <table cellpadding="0" cellspacing="0">
						<tr>
						<td><?=_("Timeline analysis")?>:&nbsp;</td>
						<td style='text-align:left;'><a href="base_timeline.php"><img src="images/timeline.png" border="0"></a></td>
						</tr>
						</table>
						<? } ?>
					</td>
					</tr></table>
				</td>
				
			</tr>
			<tr>
				<td nowrap>
					<table>
					<tr>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "today") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "today") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=today&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $today_m ?>&time%5B0%5D%5B3%5D=<?php echo $today_d ?>&time%5B0%5D%5B4%5D=<?php echo $today_y ?>&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>"><?php echo _("Today")?></a>
					</td>
					<td><font style="color:green;font-weight:bold">|</font></td>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "day") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "day") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=day&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $yesterday_m ?>&time%5B0%5D%5B3%5D=<?php echo $yesterday_d ?>&time%5B0%5D%5B4%5D=<?php echo $yesterday_y ?>&time%5B0%5D%5B5%5D=<?php echo $today_h ?>&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>"><?php echo _("Last 24 Hours")?></a>
					</td>
					<td><font style="color:green;font-weight:bold">|</font></td>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "week") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "week") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=week&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $week_m ?>&time%5B0%5D%5B3%5D=<?php echo $week_d ?>&time%5B0%5D%5B4%5D=<?php echo $week_y ?>&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>"><?php echo _("Last Week")?></a>
					</td>
					<td><font style="color:green;font-weight:bold">|</font></td>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "weeks") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "weeks") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=weeks&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $two_week_m ?>&time%5B0%5D%5B3%5D=<?php echo $two_week_d ?>&time%5B0%5D%5B4%5D=<?php echo $two_week_y ?>&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>"><?php echo _("Last two Weeks")?></a>
					</td>
					<td><font style="color:green;font-weight:bold">|</font></td>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "month") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "month") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=month&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=<?php echo $month_m ?>&time%5B0%5D%5B3%5D=<?php echo $month_d ?>&time%5B0%5D%5B4%5D=<?php echo $month_y ?>&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&submit=Query+DB&num_result_rows=-1&time_cnt=1<?php echo $params ?>"><?php echo _("Last Month")?></a>
					</td>
					<td><font style="color:green;font-weight:bold">|</font></td>
					<td nowrap style="padding-left:4px;padding-right:4px" <? if ($_GET['time_range'] == "all") echo "bgcolor='#28BC04'" ?>><a <?php
if ($_GET['time_range'] == "all") echo "style='color:white;font-weight:bold'"; else echo "style='color:black;font-weight:bold'" ?> href="<?php echo $urltimecriteria ?>?time_range=all&clear_criteria=time&clear_criteria_element=&submit=Query+DB<?php echo $params ?>"><?php echo _("All")?></a>
					</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td id="task" style="display:none" nowrap>
					<div class="balloon">
						<a href="#"><img src="images/alarm-clock-blue.png" align="absmiddle" border=0> <i> <?php echo gettext("Background task in progress"); ?></i>
						<span class="tooltip">
								<span class="top"></span>
								<span class="middle" id="bgtask"><?php echo _("No pending tasks") ?>.</span>
								<span class="bottom"></span>
						</span>
						</a>
					</div> 
				</td>
			</tr>
		</table>
	</td>
</tr>
<!--
<tr>
	<td><?php
//PrintFramedBoxHeader(gettext("Summary Statistics"), "#669999", "#FFFFFF");
//PrintGeneralStats($db, 1, $show_summary_stats, "$join_sql ", "$where_sql $criteria_sql");

?></td>
</tr>
<tr>
	<td>
		<table width="100%">
			<tr>
				<td width="250" nowrap><B><?php echo _QUERIED
?></B><FONT> : <?php echo strftime(gettext("%a %B %d, %Y %H:%M:%S")) ?></FONT></td>
				<td width="130" nowrap><div id="forensics_time"></div></td>
			</tr>
		</table>
	</td>
</tr>
-->
</table>

</form>

</td><td valign="top" style="border-top:1px solid #CCCCCC;border-right:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;background:url('../pixmaps/fondo_hdr2.png') repeat-x">

<link href="../style/jquery.autocomplete.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="../style/greybox.css"/>
<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>

<script src="../js/greybox_post.js" type="text/javascript"></script>
<script src="../js/jquery.flot.pie.js" language="javascript" type="text/javascript"></script>
<script language="javascript" src="../js/jquery.bgiframe.min.js"></script>
<script language="javascript" src="../js/jquery.autocomplete.pack.js"></script>
<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
<script src="../js/datepicker.js" type="text/javascript"></script>
<? $ipsearch=1; include ("../host_report_menu.php") ?>
<script>
	var url = new Array(50)
	function showTooltip(x, y, contents, link) {
		link = link.replace(".","");
        link = link.replace(",","");
		$('<div id="tooltip" class="tooltipLabel" onclick="document.location.href=\'' + url[link] + '&submit=Query DB\'"><a href="' + url[link] + '&submit=Query DB" style="font-size:10px;">' + contents + '</a></div>').css( {
			position: 'absolute',
			display: 'none',
			top: y - 28,
			left: x - 10,
			border: '1px solid #ADDF53',
			padding: '1px 2px 1px 2px',
			'background-color': '#CFEF95',
			opacity: 0.80
		}).appendTo("body").fadeIn(200);
	}
	Array.prototype.in_array = function(p_val) {
		for(var i = 0, l = this.length; i < l; i++) {
			if(this[i] == p_val) {
				return true;
			}
		}
		return false;
	}
	function mix_sensors(val) {
		var sval = val.split(',');
		if ($("#sensor").val() != "") var aval = $("#sensor").val().split(',');
		else var aval = [];
		var mixed = [];
		var ind = 0;
		for(var i = 0, l = sval.length; i < l; i++) {
			if (aval.length>=0 || aval.in_array(sval[i])) // Before aval.length==0
				mixed[ind++] = sval[i];
		}
		var str = "";
		
		if (mixed.length > 0) {
			str = mixed[0];
			for(var i = 1, l = mixed.length; i < l; i++) {
				str = str + ',' + mixed[i];
			}
			//alert($("#sensor").val()+" + "+val+" = "+str);
		}
		// return intersection
		$("#sensor").val(str);
	}
	//
	<?=$ipsel?>
	
	function postload() {
		// CAPTURE ENTER KEY
		$("#search_str").bind("keydown", function(event) {
			// track enter key
			var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
			if (keycode == 13) { // keycode for enter key
				$('#signature').click();
				return false;
			} else  {
				return true;
			}
		});
		// TOOLTIPS
		$(".scriptinfo").simpletip({
			position: 'right',
			onBeforeShow: function() { 
				var ip = this.getParent().attr('ip');
				this.load('base_netlookup.php?ip=' + ip);
			}
		});
	    $(".scriptinfoimg").simpletip({
            position: 'right',
            baseClass: 'imgtip',
            onBeforeShow: function() {
                    this.update(this.getParent().attr('txt'));
            }
	    });		
	    $(".tztooltip").simpletip({
            position: 'right',
            onBeforeShow: function() {
                    this.update(this.getParent().attr('txt'));
            }
	    });		    
		// AUTOCOMPLETE SEARCH FACILITY FOR SENSOR
		var sensors = [
			<?= preg_replace("/,$/","",$str); ?>
		];
		$("#sip").autocomplete(sensors, {
			minChars: 0,
			width: 175,
			matchContains: "word",
			autoFill: true,
			formatItem: function(row, i, max) {
				return row.txt;
			}
		}).result(function(event, item) {
			//$("#sensor").val(item.id);
			mix_sensors(item.id);
			$("#bsf").click();
		});

		// CALENDAR
		<?
		if ($_SESSION["time_cnt"]==2) {
			$y1 = ($_SESSION["time"][0][4]!="") ? $_SESSION["time"][0][4] : date("Y",$timetz);
			$m1 = ($_SESSION["time"][0][2]!="") ? $_SESSION["time"][0][2]-1 : date("m",$timetz);
			$m11 = ($_SESSION["time"][0][2]!="") ? $_SESSION["time"][0][2] : date("m",$timetz);
			$d1 = ($_SESSION["time"][0][3]!="") ? $_SESSION["time"][0][3] : date("d",$timetz);
			$y2 = ($_SESSION["time"][1][4]!="") ? $_SESSION["time"][1][4] : date("Y",$timetz);
			$m2 = ($_SESSION["time"][1][2]!="") ? $_SESSION["time"][1][2]-1 : date("m",$timetz);
			$m21 = ($_SESSION["time"][1][2]!="") ? $_SESSION["time"][1][2] : date("m",$timetz);
			$d2 = ($_SESSION["time"][1][3]!="") ? $_SESSION["time"][1][3] : date("d",$timetz);
		?>
		var datefrom = new Date(<?=$y1?>,<?=$m1?>,<?=$d1?>);
		var dateto = new Date(<?=$y2?>,<?=$m2?>,<?=$d2?>);
		<?
		} elseif ($_SESSION["time_cnt"]==1) {
			$y1 = ($_SESSION["time"][0][4]!="") ? $_SESSION["time"][0][4] : date("Y",$timetz);
			$m1 = ($_SESSION["time"][0][2]!="") ? $_SESSION["time"][0][2]-1 : date("m",$timetz);
			$m11 = ($_SESSION["time"][0][2]!="") ? $_SESSION["time"][0][2] : date("m",$timetz);
			$d1 = ($_SESSION["time"][0][3]!="") ? $_SESSION["time"][0][3] : date("d",$timetz);
			$y2 = date("Y",$timetz);
			$m2 = $m21 = date("m",$timetz);
			$d2 = date("d",$timetz);
		?>
		var datefrom = new Date(<?=$y1?>,<?=$m1?>,<?=$d1?>);
		var dateto = new Date();
		<?
		} else {
		?>
		var datefrom = new Date();
		var dateto = new Date();
		<?
		}
		?>
		var clicks = 0;
		var dayswithevents = [ <?=GetDatesWithEvents($db)?> ];
		$('#widgetCalendar').DatePicker({
			flat: true,
			format: 'Y-m-d',
			date: [new Date(datefrom), new Date(dateto)],
			calendars: 3,
			mode: 'range',
			starts: 1,
			onChange: function(formated, dates) {
				if (formated[0]!="" && formated[1]!="" && clicks>0) {
					var url = "time_range=range&time_cnt=2&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=AND&time%5B1%5D%5B1%5D=%3C%3D"
					var f1 = formated[0].split(/-/);
					url = url + '&time%5B0%5D%5B2%5D=' + f1[1]; // month
					url = url + '&time%5B0%5D%5B3%5D=' + f1[2]; // day
					url = url + '&time%5B0%5D%5B4%5D=' + f1[0]; // year
					url = url + '&time%5B0%5D%5B5%5D=00&time%5B0%5D%5B6%5D=00&time%5B0%5D%5B7%5D=00';
					var f2 = formated[1].split(/-/);
					url = url + '&time%5B1%5D%5B2%5D=' + f2[1]; // month
					url = url + '&time%5B1%5D%5B3%5D=' + f2[2]; // day
					url = url + '&time%5B1%5D%5B4%5D=' + f2[0]; // year
					url = url + '&time%5B1%5D%5B5%5D=23&time%5B1%5D%5B6%5D=59&time%5B1%5D%5B7%5D=59';
					document.location.href = '<?=$actual_url?>'+url;
				} clicks++;
			},
			onRender: function(date) {
				return {
						//disabled: (date.getTime() < now.getTime()),
						className: dayswithevents.in_array(date.getTime()) ? 'datepickerSpecial' : false
				}
			}
		});
		var state = false;
		$('#widget>a').bind('click', function(){
			$('#widgetCalendar').stop().animate({height: state ? 0 : $('#widgetCalendar div.datepicker').get(0).offsetHeight}, 500);
			$('#imgcalendar').attr('src',state ? '../pixmaps/calendar.png' : '../pixmaps/tables/cross.png');
			state = !state;
			return false;
		});
		$('#widgetCalendar div.datepicker').css('position', 'absolute');
		$('.ndc').disableTextSelect();
		// timeline
		if (typeof load_tree == 'function') load_tree();
		// timeline
		if (typeof gen_timeline == 'function') gen_timeline();
		// report
		if (typeof parent.launch_form == 'function') parent.launch_form();
	}
	function bgtask() {
		$.ajax({
			type: "GET",
			url: "base_bgtask.php",
			data: "",
			success: function(msg) {
				if (msg.match(/No pending tasks/)) {
					$("#bgtask").html(msg);
					if ($("#task").is(":visible")) $("#task").toggle();
					setTimeout("bgtask()",5000);
				} else {
					if ($("#task").is(":hidden")) $("#task").toggle();
					$("#bgtask").html(msg);
					setTimeout("bgtask()",5000);
				}
			}
		});
	}
	<?
    function thousands_locale() {
        $locale = ( isset($_COOKIE['locale']) ? 
                        $_COOKIE['locale'] : 
                        $_SERVER['HTTP_ACCEPT_LANGUAGE']
                   );
        $languages = explode(",",$locale);
        switch($languages[0]) {
            case 'es-es':
            case 'de-de':
            case 'es-mx':
                $thousands = '.';
                break;
            default:
                $thousands = ',';
        }
        return $thousands;
    }
	?>
	function formatNmb(nNmb){
		var sRes = ""; 
		for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
			sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? "<?=thousands_locale()?>": "") + sRes;
		return sRes;
	}
	
	function change_view(view){
		var url = "base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&custom_view="+view;
		document.location.href=url;
	}
	function save_view(id_img){
		/*$('#customview_msg').html("<img width='20' src='../pixmaps/loading3.gif'>");
		$.ajax({
			type: "GET",
			url: "custom_view_save.php",
			data: "",
			success: function(msg) {
				$('#customview_msg').html("<img src='../pixmaps/tick.png'>");
				setTimeout("$('#customview_msg').html('')",1000);
			}
		});*/
		
		var img = $('#'+id_img).attr('src').split('/');
	    img = img[img.length-1];
	    var url = '../pixmaps/';
		
		var src1='loading3.gif';
		var src2='tick.png';
		
		$('#'+id_img).attr('src', url+src1);
						
		$.ajax({
			type: "GET",
			url: "custom_view_save.php",
			data: "",
			success: function(msg) {
				$('#'+id_img).attr('src', url+src2);
				setTimeout("($('#"+id_img+"').attr('src', '"+url+img+"'))",1000);
			}
		});
		
		
	}
	function delete_view(name){
		$.ajax({
			type: "GET",
			url: "custom_view_delete.php",
			data: "name="+name,
			success: function(msg) {
				if (msg != "") {
					alert(msg);
				} else {
					var url = "base_qry_main.php?num_result_rows=-1&submit=Query+DB";
					document.location.href=url;
				}
			}
		});
	}

	function report_launcher(data,type) {
		var url = '<?=urlencode($_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"])?>';
		var dates = '<?=($y1!="") ? "&date_from=$y1-$m11-$d1" : "&date_from="?><?=($y2!="") ? "&date_to=$y2-$m21-$d2" : "&date_to="?>';
		GB_show("<?=_("Report options")?>",'report_launcher.php?url='+url+'&data='+data+'&type='+type+dates,200,'40%');
		return false;
	}
	
    function GB_hide() { document.location.reload() }
    function fill_subcategories() {
    	var idcat = $('#category').val();
    	if (idcat!="") {
    		$('#subcategory').empty().append('<option value=""</option>');
    		$('#cat'+idcat).find('option').each(function(){
    			$(this).appendTo('#subcategory');
    		});
    		$('#subcategory').find('option:first').attr("selected","selected");
    	}
    }

    function solera_deepsee (from,to,src_ip,src_port,dst_ip,dst_port,proto) {
        $('#solera_form input[name=from]').val(from);
        $('#solera_form input[name=to]').val(to);
        $('#solera_form input[name=src_ip]').val(src_ip);
        $('#solera_form input[name=src_port]').val(src_port);
        $('#solera_form input[name=dst_ip]').val(dst_ip);
        $('#solera_form input[name=dst_port]').val(dst_port);
        $('#solera_form input[name=proto]').val(proto);
        GB_show_post('Solera DeepSee &trade;','#solera_form',300,600);
    }
        
	<?php
if ($_SESSION["deletetask"] != "") echo "bgtask();\n"; ?>
</script>
<div>
