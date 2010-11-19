<?
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2007-2010 AlienVault
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
require_once('classes/Session.inc');
Session::logcheck("MenuEvents", "EventsForensics");
$url = GET('url');
$data = GET('data');
$type = GET('type');
$date_from = GET('date_from');
$date_to = GET('date_to');
ossim_valid($url, OSS_TEXT, OSS_PUNC_EXT, "Invalid: url");
ossim_valid($data, OSS_TEXT, OSS_PUNC_EXT, "Invalid: data");
ossim_valid($type, OSS_ALPHA, "Invalid: type");
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, "Invalid: date_from");
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, "Invalid: date_to");
if (ossim_error()) {
    die(ossim_error());
}
?>
<html>
<HEAD>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<META HTTP-EQUIV="pragma" CONTENT="no-cache">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<TITLE>Forensics Console: Report Launcher</TITLE>
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>	
	<link rel="stylesheet" type="text/css" href="../style/style.css"/> 
</HEAD>
<script>
	function launch_form() { 
		<? if ($type!="pdf") { ?>
		$('#<?=$data?>').attr('action','csv.php?rtype=<?=$type?>');
		<? } ?>

		$('#<?=$data?>').submit();

		
		$('#msg').html('<img src="../pixmaps/loading.gif" width="16" border="0" align="absmiddle"> <?=_("Launching report and refreshing...")?><br>');
		if (typeof parent.GB_hide == 'function') parent.GB_hide();
		
	}
	function generate() {
		url = '<?=$url?>&numevents='+$('#numevents option:selected').val();
		$('#forensics').attr('src',url);
		$('#msg').html('<img src="../pixmaps/loading.gif" border="0" width="16" align="absmiddle"> <?=_("Loading event list, please wait a few seconds.")?><br>');
	}
</script>
<body>
<form action="" onsubmit="return false">
<br><br>
<table align="center" class="noborder">
	<tr>
		<td class="nobborder">
			Events # <select name="numevents" id="numevents">
			<option value="50" selected>50</option>
			<option value="100">100</option>
			<option value="250">250</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
			<option value="2500">2500</option>
			<option value="5000">5000</option>
			<option value="99999">All</option>
			</select>
		</td>
		<td class="nobborder">	
			&nbsp;<input type="button" class="lbutton" onclick="generate()" value="<?=_(($type=="pdf" ? "PDF" : "CSV"))?>">
		</td>
	</tr>
</table>
</form>
<center><span align="center" id="msg"></span></center>
<iframe id="forensics" style="display:none"></iframe>
<form style="margin:0px;display:inline" id="Events_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Events">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Events">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniqueEvents_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Unique_Events">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Unique_Events">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="Sensors_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Sensors">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Sensors">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniqueAddress_Report1" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Unique_Address">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Unique_Address">
<input type="hidden" name="Type" id="UniqueAddress_Report_Type" value="1">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniqueAddress_Report2" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Unique_Address">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Unique_Address">
<input type="hidden" name="Type" id="UniqueAddress_Report_Type" value="2">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="SourcePort_Report0" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Source_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Source_Port">
<input type="hidden" name="Type" id="SourcePort_Report_Type" value="0">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="SourcePort_Report1" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Source_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Source_Port">
<input type="hidden" name="Type" id="SourcePort_Report_Type" value="1">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="SourcePort_Report2" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Source_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Source_Port">
<input type="hidden" name="Type" id="SourcePort_Report_Type" value="2">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="DestinationPort_Report0" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Destination_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Destination_Port">
<input type="hidden" name="Type" id="DestinationPort_Report_Type" value="0">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="DestinationPort_Report1" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Destination_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Destination_Port">
<input type="hidden" name="Type" id="DestinationPort_Report_Type" value="1">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="DestinationPort_Report2" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Destination_Port">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Destination_Port">
<input type="hidden" name="Type" id="DestinationPort_Report_Type" value="2">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniquePlugin_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Unique_Plugin">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Unique_Plugin">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniqueCountryEvents_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="Security_DB_Unique_Country_Events">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="Security_DB_Unique_Country_Events">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
<form style="margin:0px;display:inline" id="UniqueIPLinks_Report" method="POST" action="../report/jasper_export.php?format=pdf" target="SIEM_Events_Unique_IP_Links">
<input type="hidden" name="reportUser" value="<?=$_SESSION["_user"]?>">
<input type="hidden" name="reportUnit" value="SIEM_Events_Unique_IP_Links">
<input type="hidden" name="date_from" value="<?=$date_from?>">
<input type="hidden" name="date_to" value="<?=$date_to?>">
</form>
</body>
</html>
