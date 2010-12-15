<?
require_once("classes/Session.inc");
Session::logcheck("MainMenu", "Index");
?>
<script>
function changedisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display=='none') ? '' : 'none'; }
function changevis(id)  { document.getElementById(id).style.visibility = (document.getElementById(id).style.visibility=='hidden') ? 'visible' : 'hidden'; }
function toggle_statusbar() {
    <?if ($check_updates == "" || $new_updates) { ?> changedisplay('updates');<?}?>
	changedisplay('statusbar')
	changevis('headertoggle')
	var rows = top.document.getElementById('ossimframeset').getAttribute("rows")
	top.document.getElementById('ossimframeset').setAttribute("rows", ((rows!="22,*") ? "22,*" : "66,*"))
}
function refresh_statusbar() {
	// ajax responder
	var ajaxObject = document.createElement('script');
	ajaxObject.src = 'statusbar/status_bar_responder.php?bypassexpirationupdate=1';
	ajaxObject.type = "text/javascript";
	ajaxObject.charset = "utf-8";
	document.getElementsByTagName('head').item(0).appendChild(ajaxObject);
}
onload=init
function init() {
	refresh_statusbar()
	setInterval("refresh_statusbar()",30000);
}
</script>
<style type="text/css">
.level11  {  background:url(pixmaps/statusbar/level11.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level10  {  background:url(pixmaps/statusbar/level10.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level9  {  background:url(pixmaps/statusbar/level9.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level8  {  background:url(pixmaps/statusbar/level8.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level7  {  background:url(pixmaps/statusbar/level7.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level6  {  background:url(pixmaps/statusbar/level6.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level5  {  background:url(pixmaps/statusbar/level5.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level4  {  background:url(pixmaps/statusbar/level4.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level3  {  background:url(pixmaps/statusbar/level3.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level2  {  background:url(pixmaps/statusbar/level2.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level1  {  background:url(pixmaps/statusbar/level1.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
.level0  {  background:url(pixmaps/statusbar/level0.gif) top left;background-repeat:no-repeat;width:86;height:29;padding-left:5px  }
</style>
<div id="headertoggle" style="visibility:hidden;position:absolute;top:0px;left:0px;width:100%;height:22px;z-index:999;cursor:pointer" onclick="toggle_statusbar();return false;">
<?
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$demo = (preg_match("/demo/i",$version)) ? true : false;
?>
<table cellpadding='0' cellspacing='0' border='0' height="22" width="100%" style="background:url('pixmaps/top/bg_header.gif') repeat-x bottom left;">
	<tr>
	<!--<table cellpadding='0' cellspacing='0' border='0' height="24" width="100%" style="background:#5B5B5B url(pixmaps/top/bg_darkgray.gif) bottom left repeat-x;"><tr>-->
	<!--<td width="12" align="left"><img src="pixmaps/statusbar/toggle.gif" border='0'></td><td style="font-size:6px">&nbsp;</td><td width="12" align="right"><img src="pixmaps/statusbar/toggle.gif" border='0'></td>-->
	<td style="padding-left:10px;" width="20"><img src="pixmaps/statusbar/logo_siem_small.png" border='0'></td><td style="color:black;text-align:left;font-size:11px;font-family:verdana;font-weight:bold"><?php echo gettext("AlienVault - ".($opensource ? "Open Source SIEM" : ($demo ? "Professional SIEM Demo" : "Professional SIEM"))); ?></td>
	<!--<td width="12" align="right" valign="top">
	<img src="pixmaps/statusbar/toggle.gif" border='0'>
	</td>-->
	</tr>
</table>
</div>

<div id="statusbar" style="position:absolute;top:0px;left:0px;width:100%;height:61px;z-index:100;">
<table cellpadding='0' cellspacing='0' border='0' height="64" width="734" align="right">
<tr>
<td class="canvas" width="722">
	<table cellpadding='0' cellspacing='0' border='0' height="62" width='100%'>
	<tr>
		<td height="3" colspan="11" bgcolor="#A1A1A1"></td>
	</tr>
	<tr>
		<td width="12" valign="top"><a href="javascript:;" onclick="toggle_statusbar();return false;"><img src="pixmaps/statusbar/btn_minimize.gif" border='0'></a></td>
		<td>
			<table cellpadding='0' cellspacing='0' border='0'>
				<tr>
					<td class="blackp" valign="bottom" style="padding-left:5px;padding-right:5px" nowrap='nowrap'>
						<table cellpadding='0' cellspacing='0' border='0' width="180">
							<tr>
								<td class="bartitle" width="125"><a href="top.php?option=1&soption=1" target="topmenu" class="blackp"><?=_("Tickets")?> <b><?=_("Opened")?></b></a></td>
								<td class="capsule" width="50"><a href="top.php?option=1&soption=1" target="topmenu" class="whitepn" id="statusbar_unresolved_incidents">-</a></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td class="vsep"></td></tr>
				<tr>
					<td class="blackp" valign="bottom" style="padding-left:5px;padding-right:5px" nowrap='nowrap'>
						<table cellpadding='0' cellspacing='0' border='0' width="180">
							<tr>
								<td class="bartitle" width="125"><a href="top.php?option=1&soption=0" target="topmenu" class="blackp"><?=_("Unresolved")?> <b><?=_("Alarms")?></b></a></td>
								<td class="capsule" width="50"><a href="top.php?option=1&soption=0" target="topmenu" class="whitepn" id="statusbar_unresolved_alarms">-</a></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td width="20" align="center"><img src="pixmaps/statusbar/bg_sep.gif" border='0'></td>
		<td>
			<table cellpadding='0' cellspacing='0' border='0'>
				<tr>
					<td valign="bottom" align="center" style="padding-left:5px;padding-right:5px">
						<table cellpadding='0' cellspacing='0' border='0' width="110">
							<tr><td class="blackp" style="font-size:9px;color:#A1A1A1;" align="center"><?=_("Last updated")?>:</td></tr>
							<tr><td class="blackp" style="font-size:8px;color:#A1A1A1;" align="center" nowrap id="statusbar_incident_date">__/__/__ --:--:--</td></tr>
						</table>
					</td>
				</tr>
				<tr><td class="vsep"></td></tr>
				<tr>
					<td valign="bottom" align="center" style="padding-left:5px;padding-right:5px">
						<table cellpadding='0' cellspacing='0' border='0' width="110">
							<tr><td class="blackp" style="font-size:9px;color:#A1A1A1;" align=center><?=_("Last updated")?>:</td></tr>
							<tr><td class="blackp" style="font-size:8px;color:#A1A1A1;" align="center" nowrap id="statusbar_alarm_date">__/__/__ --:--:--</td></tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td width="20" align="center"><img src="pixmaps/statusbar/bg_sep.gif" border='0'/></td>
		<td>
			<table cellpadding='0' cellspacing='0' border='0'>
			  <tr>
				<td class="blackp" valign="bottom" style="padding-left:5px;padding-right:5px" nowrap>
					<table cellpadding='0' cellspacing='0' border='0' width="125">
						<tr>
							<td class="bartitle" width="70"><a href="" target="topmenu" id="statusbar_incident_max_priority_txt" class="blackp"><?=_("Max")?> <b><?=_("priority")?></b></a></td>
							<td class="capsule" width="50"><a href="" target="topmenu" class="whitepn" id="statusbar_incident_max_priority">-</a></td>
						</tr>
					</table>
				</td>
			  </tr>
			  <tr><td class="vsep"></td></tr>
			  <tr>
				<td class="blackp" valign="bottom" style="padding-left:5px;padding-right:5px" nowrap>
					<table cellpadding='0' cellspacing='0' border='0' width="125">
						<tr>
							<td class="bartitle" width="70"><a href="" target="topmenu" class="blackp" id="statusbar_alarm_max_risk_txt"><?=_("Max")?> <b><?=_("risk")?></b></a></td>
							<td class="capsule" width="50"><a href="" target="topmenu" class="whitepn" id="statusbar_alarm_max_risk">-</a></td>
						</tr>
					</table>
				</td>
			  </tr>
			</table>
		</td>
		<td width="20" align="center"><img src="pixmaps/statusbar/bg_sep.gif" border='0'></td>
		<td width="80">
			<table cellpadding='0' cellspacing='0' border='0' align="center">
				<tr>
					<td align="center"><a href="top.php?option=0&soption=1&url=<?=urlencode("control_panel/global_score.php?hmenu=Risk&smenu=Metrics")?>" target="topmenu"><img id="semaphore" src="pixmaps/statusbar/sem_off.gif" border="0"></a></td>
					<td align="center" style="padding-left:4px"><a href="top.php?option=0&soption=1&url=<?=urlencode("control_panel/global_score.php?hmenu=Risk&smenu=Metrics")?>" target="topmenu" class="blackp" style="text-decoration:none"><b><?=_("Global")?></b><br><?=_("score")?></a></td>
				</tr>
			</table>
		</td>
		<td width="20" align="center"><img src="pixmaps/statusbar/bg_sep.gif" border='0'></td>
		<td width="86">
			<table cellpadding='0' cellspacing='0' border='0' width="91">
			  <tr>
			    <td>
					<table cellpadding='0' cellspacing='0' border='0'>
						<tr><td class="blackp" nowrap align="center"><b><?=_("Service")?></b><br><?=_("level")?></td></tr>
						<tr><td width='86px' height='30px' class="level11" nowrap align="center" id="service_level_gr"><a href="top.php?option=0&soption=1&url=<?=urlencode("control_panel/show_image.php?range=day&ip=level_admin&what=attack&start=N-1D&end=N&type=level&zoom=1&hmenu=Risk&smenu=Metrics")?>" target="topmenu" id="service_level" class="black" style="text-decoration:none">-</a></td></tr>
					</table>
				</td>
			  </tr>
			</table>
		</td>
		<td style="padding-right:5px"></td>
	</tr>
	</table>
</td>
<td width="10"></td>
</tr>
</table>
</div>
