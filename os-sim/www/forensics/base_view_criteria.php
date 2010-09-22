<?php
/*****************************************************************************
*
*License:
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
include ("base_conf.php");
include ("vars_session.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$cs = new CriteriaState("base_qry_main.php", "&amp;new=1&amp;submit=" . _QUERYDBP);
$cs->ReadState();

/* Generate the Criteria entered into a human readable form */
$criteria_arr = array();

$tmp_len = strlen($save_criteria);
//$save_criteria .= $cs->criteria['sensor']->Description();
//$save_criteria .= $cs->criteria['sig']->Description();
//$save_criteria .= $cs->criteria['sig_class']->Description();
//$save_criteria .= $cs->criteria['sig_priority']->Description();
//$save_criteria .= $cs->criteria['ag']->Description();
//$save_criteria .= $cs->criteria['time']->Description();
//$criteria_arr['meta'] = preg_replace ("/\[\d+\,\d+.*\]\s*/","",$cs->criteria['sensor']->Description());
$criteria_arr['meta'] = $cs->criteria['sensor']->Description();
$criteria_arr['meta'].= $cs->criteria['plugin']->Description();
$criteria_arr['meta'].= $cs->criteria['plugingroup']->Description();
$criteria_arr['meta'].= $cs->criteria['userdata']->Description();
$criteria_arr['meta'].= $cs->criteria['sourcetype']->Description();
$criteria_arr['meta'].= $cs->criteria['category']->Description();
$criteria_arr['meta'].= $cs->criteria['sig']->Description();
$criteria_arr['meta'].= $cs->criteria['sig_class']->Description();
$criteria_arr['meta'].= $cs->criteria['sig_priority']->Description();
$criteria_arr['meta'].= $cs->criteria['ag']->Description();
$criteria_arr['meta'].= $cs->criteria['time']->Description();
$criteria_arr['meta'].= $cs->criteria['ossim_risk_a']->Description();
$criteria_arr['meta'].= $cs->criteria['ossim_priority']->Description();
$criteria_arr['meta'].= $cs->criteria['ossim_reliability']->Description();
$criteria_arr['meta'].= $cs->criteria['ossim_asset_dst']->Description();
$criteria_arr['meta'].= $cs->criteria['ossim_type']->Description();
if ($criteria_arr['meta'] == "") {
$criteria_arr['meta'].= '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
$save_criteria.= '<TD>';
if (!$cs->criteria['ip_addr']->isEmpty() || !$cs->criteria['ip_field']->isEmpty()) {
$criteria_arr['ip'] = $cs->criteria['ip_addr']->Description();
$criteria_arr['ip'].= $cs->criteria['ip_field']->Description();
$save_criteria.= $cs->criteria['ip_addr']->Description();
$save_criteria.= $cs->criteria['ip_field']->Description();
} else {
$save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
$criteria_arr['ip'] = '<I> ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
$save_criteria.= '<TD CLASS="layer4title">';
$save_criteria.= $cs->criteria['layer4']->Description();
$save_criteria.= '</TD><TD>';
if ($cs->criteria['layer4']->Get() == "TCP") {
if (!$cs->criteria['tcp_port']->isEmpty() || !$cs->criteria['tcp_flags']->isEmpty() || !$cs->criteria['tcp_field']->isEmpty()) {
$criteria_arr['layer4'] = $cs->criteria['tcp_port']->Description();
$criteria_arr['layer4'].= $cs->criteria['tcp_flags']->Description();
$criteria_arr['layer4'].= $cs->criteria['tcp_field']->Description();
$save_criteria.= $cs->criteria['tcp_port']->Description();
$save_criteria.= $cs->criteria['tcp_flags']->Description();
$save_criteria.= $cs->criteria['tcp_field']->Description();
} else {
$criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
} else if ($cs->criteria['layer4']->Get() == "UDP") {
if (!$cs->criteria['udp_port']->isEmpty() || !$cs->criteria['udp_field']->isEmpty()) {
$criteria_arr['layer4'] = $cs->criteria['udp_port']->Description();
$criteria_arr['layer4'].= $cs->criteria['udp_field']->Description();
$save_criteria.= $cs->criteria['udp_port']->Description();
$save_criteria.= $cs->criteria['udp_field']->Description();
} else {
$criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
} else if ($cs->criteria['layer4']->Get() == "ICMP") {
if (!$cs->criteria['icmp_field']->isEmpty()) {
$criteria_arr['layer4'] = $cs->criteria['icmp_field']->Description();
$save_criteria.= $cs->criteria['icmp_field']->Description();
} else {
$criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
} else if ($cs->criteria['layer4']->Get() == "RawIP") {
if (!$cs->criteria['rawip_field']->isEmpty()) {
$criteria_arr['layer4'] = $cs->criteria['rawip_field']->Description();
$save_criteria.= $cs->criteria['rawip_field']->Description();
} else {
$criteria_arr['layer4'] = '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> &nbsp&nbsp ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
} else {
$criteria_arr['layer4'] = '<I> ' . _NONE . ' </I>';
$save_criteria.= '<I> &nbsp;&nbsp; ' . _NONE . ' </I></TD>';
}
/* Payload ************** */
$save_criteria.= '
<TD>';
if (!$cs->criteria['data']->isEmpty()) {
$criteria_arr['payload'] = $cs->criteria['data']->Description();
$save_criteria.= $cs->criteria['data']->Description();
} else {
$criteria_arr['payload'] = '<I> ' . _ANY . ' </I>';
$save_criteria.= '<I> &nbsp;&nbsp; ' . _ANY . ' </I>';
}
$save_criteria.= '&nbsp;&nbsp;</TD>';
if (!setlocale(LC_TIME, _LOCALESTR1)) if (!setlocale(LC_TIME, _LOCALESTR2)) setlocale(LC_TIME, _LOCALESTR3);

// Report Data
$report_data = array();
$r_meta = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;|,\s+$/i","",preg_replace("/\<br\>/i",", ",$criteria_arr['meta']));
$r_payload = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['payload']);
$r_ip = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['ip']);
$r_l4 = preg_replace("/\<a (.*?)\<\/a\>|\&nbsp;/i","",$criteria_arr['layer4']);
$report_data[] = array (_("META"),strip_tags($r_meta),"","","","","","","","","",0,0,0);
$report_data[] = array (_("PAYLOAD"),strip_tags($r_payload),"","","","","","","","","",0,0,0);
$report_data[] = array (_("IP"),strip_tags($r_ip),"","","","","","","","","",0,0,0);
$report_data[] = array (_("LAYER 4"),strip_tags($r_l4),"","","","","","","","","",0,0,0);

?>
<html>
<HEAD><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><META HTTP-EQUIV="pragma" CONTENT="no-cache"><meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /><META HTTP-EQUIV="REFRESH" CONTENT="180"><TITLE>Forensics Console : Query Results</TITLE><LINK rel="stylesheet" type="text/css" HREF="/ossim/forensics/styles/ossim_style.css"> 
</HEAD>
<body>
<table width="100%">
	<tr>
		<td>
			<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
				<TR>
					<TD height="27" align="center" style="background:url('../pixmaps/fondo_col.gif') repeat-x;border:1px solid #CACACA;color:#333333;font-size:14px;font-weight:bold">META</TD>
				</TR>
			</TABLE>
		</td>
	</tr>
	<tr>
		<td>
			<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
				<TR>
					<TD height="27" align="center" style="background:url('../pixmaps/fondo_col.gif') repeat-x;border:1px solid #CACACA;color:#333333;font-size:14px;font-weight:bold">PAYLOAD</TD>
				</TR>
			</TABLE>
		</td>
	</tr>
	<tr>
		<td>
			<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
				<TR>
					<TD height="27" align="center" style="background:url('../pixmaps/fondo_col.gif') repeat-x;border:1px solid #CACACA;color:#333333;font-size:14px;font-weight:bold">IP</TD>
				</TR>
			</TABLE>
		</td>
	</tr>
	<tr>
		<td>
			<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=0 WIDTH="100%">
				<TR>
					<TD height="27" align="center" style="background:url('../pixmaps/fondo_col.gif') repeat-x;border:1px solid #CACACA;color:#333333;font-size:14px;font-weight:bold">LAYER 4</TD>
				</TR>
			</TABLE>
		</td>
	</tr>
</table>
<?php print_r($criteria_arr) ?>
</body>
</html>
