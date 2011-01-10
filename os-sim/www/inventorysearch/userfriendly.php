<?php
/*****************************************************************************
*
*    License:
*
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
require_once ('classes/Session.inc');
Session::logcheck("MenuPolicy", "5DSearch");
require_once ('classes/Host.inc');
require_once 'classes/User_config.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

include ("functions.php");

$new = (GET('new') == "1") ? 1 : 0;
$ip = GET('ip');
ossim_valid($ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("ip"));
if (ossim_error()) {
    die(ossim_error());
}

// Database Object
$db = new ossim_db();
$conn = $db->connect();

$net_search = Net::GetClosestNet($conn,$ip,1);

// Get Networks
list($_sensors, $_hosts) = Host::get_ips_and_hostname($conn,true);
$_nets = Net::get_all($conn,true);
$networks = $hosts = "";

foreach ($_nets as $_net) {
	$networks .= '{ txt:"'.$_net->get_name().' ['.$_net->get_ips().']", id: "'.$_net->get_ips().'" },';
}
foreach ($_hosts as $_ip => $_hostname) {
    if ($_hostname!=$_ip) $hosts .= '{ txt:"'.$_ip.' ['.$_hostname.']", id: "'.$_ip.'" },';
    else $hosts .= '{ txt:"'.$_ip.'", id: "'.$_ip.'" },';
}

// Get Services and OS
$inventory = "";
$query = "(SELECT DISTINCT os as element FROM host_os ORDER BY os) UNION (SELECT DISTINCT service as element FROM host_services ORDER BY service)";
if (!$rs = & $conn->Execute($query, $params)) {
	print $conn->ErrorMsg();
} else {
	while (!$rs->EOF) {
		if ($rs->fields['element'] != "") {
			if ($inventory != "") $inventory .= ",";
			$inventory .= $rs->fields["element"];
		}
		$rs->MoveNext();
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php echo $title ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
<link rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
<link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>

<script src="../js/jquery-1.3.2.min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.7.custom.min.js"></script>
<script src="../js/jquery.simpletip.js" type="text/javascript"></script>
<script src="../js/datepicker.js" type="text/javascript"></script>
<script type="text/javascript">
	var sayt1 = false;
	var sayt2 = false;
	function init_sayt (num) {
		if (num == 1 && !sayt1) {
			sayt1 = true;
			$("#value_1").val('');
			$("#value_1").css('color','black');
			var networks = [
			<?= preg_replace("/,$/","",$networks); ?>
			];
			$("#value_1").autocomplete(networks, {
				minChars: 0,
				width: 225,
				matchContains: "word",
				autoFill: true,
				formatItem: function(row, i, max) {
					return row.txt;
				}
			}).result(function(event, item) {
				$("#value_1").val(item.id);
			});
		} else if (num == 2 && !sayt2) {
			sayt2 = true;
			$("#value_2").val('');
			$("#value_2").css('color','black');
			var inventory = "<?=$inventory?>";
			$("#value_2").focus().autocomplete(inventory.split(","), {
				minChars: 0,
				width: 150,
				matchContains: "word",
				autoFill: false
			});
		}
	}
	
	function init_field (num) {
		$("#value_"+num).val('');
		$("#value_"+num).css('color','black');
	}

	$(document).ready(function(){
		$(".scriptinfo").simpletip({
		position: 'right',
		onBeforeShow: function() { 
			var data = base64_decode(this.getParent().attr('data'));
			this.update(data);
		}
		});
		// CALENDAR
		<?
		if ($date_from != "") {
			$aux = split("-",$date_from);
			$y = $aux[0]; $m = $aux[1]; $d = $aux[2];
		} else {
			$y = strftime("%Y", time() - (24 * 60 * 60));
			$m = strftime("%m", time() - (24 * 60 * 60 * 31));
			$d = strftime("%d", time() - (24 * 60 * 60));
		}
		if ($date_to != "") {
			$aux = split("-",$date_to);
			$y2 = $aux[0]; $m2 = $aux[1]; $d2 = $aux[2];
		} else {
			$y2 = date("Y"); $m2 = date("m"); $d2 = date("d");
		}
		?>
		var datefrom = new Date(<?php echo $y ?>,<?php echo $m-1 ?>,<?php echo $d ?>);
		var dateto = new Date(<?php echo $y2 ?>,<?php echo $m2-1 ?>,<?php echo $d2 ?>);
		$('#widgetCalendar').DatePicker({
			flat: true,
			format: 'Y-m-d',
			date: [new Date(datefrom), new Date(dateto)],
			calendars: 3,
			mode: 'range',
			starts: 1,
			onChange: function(formated) {
				if (formated[0]!=formated[1]) {
					var f1 = formated[0].split(/-/);
					var f2 = formated[1].split(/-/);
					document.getElementById('date_from').value = f1[0]+'-'+f1[1]+'-'+f1[2];
					document.getElementById('date_to').value = f2[0]+'-'+f2[1]+'-'+f2[2];
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
	});
	
	var criteria_count = 0;
	var operator = "and";
	var values = new Array;
	
	function save_values () {
		var params = "?op="+operator+"&date_from="+$('#date_from').val()+"&date_to="+$('#date_to').val();
		for (i = 1; i <= 5; i++) {
			if (document.getElementById("value_"+i) != null && document.getElementById("value_"+i).value != "Any" && document.getElementById("value_"+i).value != "") {
				params += "&value"+i+"="+document.getElementById("value_"+i).value;
				criteria_count++;
			}
		}
		$.ajax({
			type: "GET",
			url: "setvars.php"+params+"&basic=1&n="+criteria_count,
			data: "",
			success: function(msg){
				window.location.href = "build_search.php?operator="+operator+"&userfriendly=1";
			}
		});
	}
	
	function build_request () {
		save_values();
	}
	
	function handleEnter(field, event) {
		var keyCode = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;
		if (keyCode == 13) {
			build_request();
		}
	}
	
</script>
</head>
<body style="margin:0px">
<?php include ("../hmenu.php") ?>
<table class="noborder" align="center" style="background-color:white">
	<tr>
		<td class="nobborder" valign="top">
			<table class="nobborder" align="center" style="background-color:white">
			<form method=get>
			<input type="hidden" id="date_from" name="date_from" value="<?=_("Any date")?>">
			<input type="hidden" id="date_to" name="date_to" value="<?=_("Any date")?>">
				<tr>
					<td class="nobborder">
						<table id="criteria_form" class="transparent" cellpadding=5 align="center" width="100%">
							<tr>
								<td class="left nobborder" style="padding-left:10px">
									<table class="transparent" width="100%">
										<tr>
											<!--
											<td width="50%" class="nobborder" style="text-align:center;font-size:14px">
												<?=_("From:")?> <input type="text" id="date_from" name="date_from" onchange="this.style.color='black'" value="<?=_("Any date")?>" style="width:80px;font-size:13px;color:#BBBBBB;text-align:center">
											</td>
											<td width="50%" class="nobborder" style="text-align:center;font-size:14px">
												<?=_("To:")?> <input type="text" id="date_to" name="date_to" onchange="this.style.color='black'" value="<?=_("Any date")?>" style="width:80px;font-size:13px;color:#BBBBBB;text-align:center">
											</td>
											-->
											<td style="text-align: left; border-width: 0px" nowrap width="130">
												<b><?php echo _('Date frame selection') ?></b>:
											</td>
											<td class="nobborder" width="20">
												<div id="widget">
													<a href="javascript:;"><img src="../pixmaps/calendar.png" id='imgcalendar' border="0"></a>
													<div id="widgetCalendar"></div>
												</div>
											</td>
											<td class="right nobborder">
												<?php include('predefined_search.php'); ?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="nobborder" width="500">
									<table style="background:url(../pixmaps/background_gray1.gif) repeat-x;border:1px solid #AAAAAA" cellpadding="4" width="100%">
										<tr>
											<td width="50" nowrap class="nobborder" style="padding-left:40px;font-size:16px;font-weight:bold;color:#333333;text-align:left"><?=_("Network")?>:</td>
											<td class="nobborder" style="padding-top:15px;padding-bottom:15px"><input type="text" name="value_1" id="value_1" onkeypress="handleEnter(this, event)" value="<? if (preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$net_search)) echo $net_search; else echo "Any"; ?>" onfocus="init_sayt(1)" style="width:100%;color:<? if (preg_match("/\d+\.\d+\.\d+\.\d+\/\d+/",$net_search)) echo "black"; else echo "#BBBBBB"; ?>;font-size:14px"></td>
											<td class="nobborder" style="padding-right:40px" width="30"><div class="scriptinfo" data="<?=base64_encode("<i>"._("Type the")." <b>"._("Network Name")."</b> "._("or")." <b>"._("IP range")."</b></i>")?>"><font style="font-weight:bold;font-size:15px;color:#666666">?</font></div></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<table style="background:url(../pixmaps/background_gray2.gif) repeat-x;border:1px solid #999999" cellpadding="4" width="100%">
										<tr>
											<td width="50" nowrap class="noborder" style="padding-left:40px;font-size:16px;font-weight:bold;color:#333333;text-align:left"><?=_("Inventory")?>:</td>
											<td class="noborder" style="padding-top:15px;padding-bottom:15px"><input type="text" name="value_2" id="value_2" onkeypress="handleEnter(this, event)" value="Any" onfocus="init_sayt(2)" style="width:100%;color:#BBBBBB;font-size:14px"></td>
											<td class="nobborder" style="padding-right:40px" width="30"><div class="scriptinfo" data="<?=base64_encode("<i>"._("Type here the")." <b>"._("Service")."</b> "._("or the")._(" OS")."</i>")?>"><font style="font-weight:bold;font-size:15px;color:#666666">?</font></div></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<table style="background:url(../pixmaps/background_gray3.gif) repeat-x;border:1px solid #888888" cellpadding="4" width="100%">
										<tr>
											<td width="50" nowrap class="noborder" style="padding-left:40px;font-size:16px;font-weight:bold;color:#333333;text-align:left"><?=_("Vulnerability")?>:</td>
											<td class="noborder" style="padding-top:15px;padding-bottom:15px"><input type="text" name="value_3" id="value_3" onkeypress="handleEnter(this, event)" value="Any" onfocus="init_field(3)" style="width:100%;color:#BBBBBB;font-size:14px"></td>
											<td class="nobborder" style="padding-right:40px" width="30"><div class="scriptinfo" data="<?=base64_encode("<i>"._("Type here the")." <b>"._("Vuln name")."</b> "._("or")." <b>"._("CVE")."</i>")?>"><font style="font-weight:bold;font-size:15px;color:#666666">?</font></div></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<table style="background:url(../pixmaps/background_gray4.gif) repeat-x;border:1px solid #777777" cellpadding="4" width="100%">
										<tr>
											<td width="50" nowrap class="noborder" style="padding-left:40px;font-size:16px;font-weight:bold;color:#333333;text-align:left"><?=_("Tickets")?>:</td>
											<td class="noborder" style="padding-top:15px;padding-bottom:15px"><input type="text" name="value_4" id="value_4" onkeypress="handleEnter(this, event)" value="Any" onfocus="init_field(4)" style="width:100%;color:#BBBBBB;font-size:14px"></td>
											<td class="nobborder" style="padding-right:40px" width="30"><div class="scriptinfo" data="<?=base64_encode("<i>"._("Type here the")." <b>"._("ticket, alarm")."</b> "._("or")." <b>"._("KDB document")."</i>")?>"><font style="font-weight:bold;font-size:15px;color:#666666">?</font></div></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<table style="background:url(../pixmaps/background_gray5.gif) repeat-x;border:1px solid #666666" cellpadding="4" width="100%">
										<tr>
											<td width="50" nowrap class="noborder" style="padding-left:40px;font-size:16px;font-weight:bold;color:#333333;text-align:left"><?php echo _("Events")?>:</td>
											<td class="noborder" style="padding-top:15px;padding-bottom:15px"><input type="text" name="value_5" id="value_5" onkeypress="handleEnter(this, event)" value="Any" onfocus="init_field(5)" style="width:100%;color:#BBBBBB;font-size:14px"></td>
											<td class="nobborder" style="padding-right:40px" width="30"><div class="scriptinfo" data="<?=base64_encode("<i>"._("Type here the")." <b>"._("SIM event")."</b></i>")?>"><font style="font-weight:bold;font-size:15px;color:#666666">?</font></div></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="nobborder" style="text-align:center"><input type="button" onclick="build_request()" id="search_btn" value="<?=_("Search")?>" class="button" style="font-size:15px;font-weight:bold"></td>
				</tr>
			</form>
			</table>
		</td>
	</tr>
	<tr><td class="nobborder" style="text-align:center;color:green;font-weight:bold" id="msg"></td></tr>
</table>
</body>
</html>
