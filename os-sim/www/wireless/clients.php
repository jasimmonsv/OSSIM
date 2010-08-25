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
Session::logcheck("MenuEvents", "ReportsWireless");
require_once 'classes/Security.inc';
require_once 'Wireless.inc';
//
$order = GET('order');
$si = intval(GET('index'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
$aps = GET('aps');
$mac = GET('mac');
$sensor = GET('sensor');
$hideold = intval(GET('hideold'));
$trusted = intval(GET('trusted'));
$knownmac = intval(GET('knownmac'));
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($aps, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, OSS_PUNC, 'illegal: aps');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, 'illegal: sensors');
ossim_valid($sensor, OSS_IP_ADDR, OSS_NULLABLE, 'illegal: sensor');
ossim_valid($mac, OSS_MAC, OSS_NULLABLE, 'illegal: mac');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$ossim = $db->connect();
$conn = $db->snort_connect();
if ($mac!="" && $sensor!="" && GET('action')=="delete") {
    if (!validate_sensor_perms($ossim,$sensor,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1")) {
        echo $_SESSION["_user"]." have not privileges for $sensor";
        $db->close($conn);
        exit;
    }
	Wireless::del_clients($ossim,$mac,$sensor);
}
if ($trusted>0) $_SESSION["trusted"]=$trusted;
if (!isset($_SESSION["trusted"])) $_SESSION["trusted"]=1;
if ($hideold>0) $_SESSION["hideold"]=$hideold;
if (!isset($_SESSION["hideold"])) $_SESSION["hideold"]=2;
if ($knownmac>0) $_SESSION["knownmac"]=$knownmac;
if (!isset($_SESSION["knownmac"])) $_SESSION["knownmac"]=2;
?>
<form style="margin-bottom:4px">
<input type="hidden" name="si" value="<?=$si?>">
<?=_("Show All")?> <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="1" <?=($_SESSION["trusted"]==1) ? "checked" : ""?>>
<?=_("Trusted")?> <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="2" <?=($_SESSION["trusted"]==2) ? "checked" : ""?>>
<?=_("Untrusted")?> <input type="radio" name="trusted" onclick="changeviewc(this.form.si.value,'trusted='+this.value)" value="3" <?=($_SESSION["trusted"]==3) ? "checked" : ""?>>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=_("Hide old ones")?> <input type="checkbox" onclick="changeviewc(this.form.si.value,'hideold='+(this.checked ? '1' : '2'))" name="hideold" <?=($_SESSION["hideold"]==1) ? "checked" : ""?>>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=_("Known mac vendors")?> <input type="checkbox" onclick="changeviewc(this.form.si.value,'knownmac='+(this.checked ? '1' : '2'))" name="knownmac" <?=($_SESSION["knownmac"]==1) ? "checked" : ""?>>
</form>
<table align="center" width="100%" id="results">
<thead>
	<th height='20'><?=_("Client Name")?></th>
	<th nowrap><?=_("MAC")?></th>
	<th nowrap><a href="javascript:;" onclick="load_data('clients.php?order=ip')"><?=_("IP Addr")?></a></th>
	<th><?=_("Type")?></th>
	<th><?=_("Encryption")?></th>
	<th><?=_("WEP")?></th>
	<th><?=_("1st Seen")?></th>
	<th><?=_("Last Seen")?></th>
	<th nowrap><?=_("Connected To")?></th>
	<th></th>
</thead>
<tbody>
<?
/*$sids = "";
if ($sensors!="") {
	$sensor_list = explode(",",$sensors);
	$sids = Wireless::get_sids($conn,$sensor_list);
}
$clients = Wireless::get_unique_clients($conn,$order,$sids,$aps);*/
$plugin_sids = Wireless::get_plugin_sids($ossim);
$clients = Wireless::get_wireless_clients($ossim,$order,$sensors,$aps);
$c=0;
foreach ($clients as $data) {
	$color = ($c++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
	$sids = array();
	foreach ($data['sids'] as $sid) if ($sid!=0 && $sid!=3 && $sid!=19) {
		$color = "bgcolor='#FFCA9F'";
		$plg = ($plugin_sids[$sid]!="") ? $plugin_sids[$sid] : $sid;
		$sids[] = $plg;
	}
	$sidsstr = implode("<br>",$sids);
	//
	$connected = "";
	$rest = "<b>APs</b><br>";
	if (count($data['connected'])>3) {
		$i=0; $max = 3;
		foreach ($data['connected'] as $mac) if (trim($mac)!="") {
			if ($i++ < $max) $connected .= trim($mac)."<br>";
			else $rest .= trim($mac)."<br>"; 
		}
		if (trim($sidsstr)!="") $rest .= "<b>Attacks</b><br>".trim($sidsstr);
		$connected .= "<a href='javascript:;' class='scriptinfo' txt='$rest'>[".($i-$max)." more]</a>";
	} else {
		$connected = implode("<br>",$data['connected']);
	}
	echo "<tr $color>
	<td>".$data['name']."</td>
	<td>".$data['mac']."<br><font style='font-size:10px'>".$data['vendor']."</font></td>
	<td><a target='main' class='HostReportMenu' id='".$data['ip'].";".$data['ip']."' href='../report/index.php?host=".$data['ip']."&hmenu=Host+Report&smenu=Host+Report'>".$data['ip']."</a></td>
	<td>".$data['type']."</td>
	<td>".$data['encryption']."</td>
	<td>".$data['encoding']."</td>
	<td><font color='".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</font></td>
	<td><font color='".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</font></td>
	<td style='padding:0px 5px 0px 5px;text-align:left' nowrap>$connected</td>
	<td><a href=\"javascript:load_data('clients.php?action=delete&mac=".urlencode($data['mac'])."&sensor=".urlencode($data['sensor'])."')\"><img src='../repository/images/delete_on.gif' border=0></a></td>
	</tr>";
}
?>
</tbody>
</table>

<?
$db->close($conn);
?>
