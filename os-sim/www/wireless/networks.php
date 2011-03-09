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
require_once 'classes/Util.inc';
//
$order = GET('order');
$sensor = GET('sensor');
$ssid = base64_decode(GET('ssid'));
$si = intval(GET('index'));
$hideold = intval(GET('hideold'));
$trusted = intval(GET('trusted'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($ssid, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC_EXT, '\<\|\>%"\{\}\`', 'illegal: ssid');
ossim_valid($sensor, OSS_IP_ADDR, OSS_NULLABLE, 'illegal: sensor');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, 'illegal: sensors');
if (ossim_error()) {
    die(ossim_error());
}
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
if (GET('action')=="delete") {
    # sensor list with perm
    if (!validate_sensor_perms($conn,$sensor,"s, sensor_properties p WHERE s.ip=p.ip AND p.has_kismet=1")) {
        echo $_SESSION["_user"]." have not privileges for $sensor";
        $db->close($conn);
        exit;
    }
    #
    Wireless::del_network($conn,$ssid,$sensor);
}
if ($trusted>0) $_SESSION["trusted"]=$trusted;
if (!isset($_SESSION["trusted"])) $_SESSION["trusted"]=1;
if ($hideold>0) $_SESSION["hideold"]=$hideold;
if (!isset($_SESSION["hideold"])) $_SESSION["hideold"]=2;
?>
<form style="margin-bottom:4px">
<input type="hidden" name="si" value="<?=$si?>">
<?=_("Show All")?> <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="1" <?=($_SESSION["trusted"]==1) ? "checked" : ""?>>
<?=_("Trusted")?> <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="2" <?=($_SESSION["trusted"]==2) ? "checked" : ""?>>
<?=_("Untrusted")?> <input type="radio" name="trusted" onclick="changeview(this.form.si.value,'trusted='+this.value)" value="3" <?=($_SESSION["trusted"]==3) ? "checked" : ""?>>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=_("Hide old ones")?> <input type="checkbox" onclick="changeview(this.form.si.value,'hideold='+(this.checked ? '1' : '2'))" name="hideold" <?=($_SESSION["hideold"]==1) ? "checked" : ""?>>
</form>
<table align="center" width="100%" id="results">
<thead>
	<th height='20'><a href="javascript:;" onclick="load_data('networks.php?order=ssid')"><?=_("Network SSID")?></a></th>
	<th nowrap><a href="javascript:;" onclick="load_data('networks.php?order=aps')"><?=_("# of APs")?></a></th>
	<th nowrap><a href="javascript:;" onclick="load_data('networks.php?order=clients')"><?=_("# Clients")?></a></th>
	<th><?=_("Type")?></th>
	<th><?=_("Encryption Type")?></th>
	<th><?=_("Cloaked")?></th>
	<th><?=_("1st Seen")?></th>
	<th><?=_("Last Seen")?></th>
	<th><?=_("Description")?></th>
	<th><?=_("Notes")?></th>
	<th></th>
</thead>
<tbody>
<?
/*
$sids = "";
if ($sensors!="") {
	$sensor_list = explode(",",$sensors);
	$sids = Wireless::get_sids($conn,$sensor_list);
}
$networks = Wireless::get_networks($conn,$order,$sids);*/
$networks = Wireless::get_wireless_networks($conn,$order,$sensors);
$i=0;
$nossid=array();
foreach ($networks as $data) {
    $color = ($i++ % 2 == 0) ? "bgcolor='#f2f2f2'" : "";
    $_SESSION["clients"][$data['ssid']] = $data['macs'];
    $enc = ($data['encryption']=="None") ? "None" : str_replace("None","<font color=red>None</font>",str_replace(","," ",$data['encryption']));
    echo "<tr $color>
    <td style='text-align:left;padding-left:5px'><a href=\"ap.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Access Points: ".Util::htmlentities($data['ssid'])."'>".Util::htmlentities(utf8_encode($data['ssid']))."</a></td>
    <td><a href=\"ap.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Access Points: ".Util::htmlentities($data['ssid'])."'>".$data['aps']."</a></td>
    <td><a href=\"clients_gb.php?index=$si&ssid=".urlencode(base64_encode($data['ssid']))."\" class='greybox' title='Clients: ".Util::htmlentities($data['ssid'])."'>".$data['clients']."</a></td>
    <td>".$data['type']."</td>
    <td>$enc</td>
    <td>".str_replace("Yes/No","Yes/<font color=red>No</font>",str_replace("No/Yes","Yes/No",$data['cloaked']))."</td>
    <td><font color='".Wireless::date_color($data['firsttime'],1)."'>".$data['firsttime']."</font></td>
    <td><font color='".Wireless::date_color($data['lasttime'],2)."'>".$data['lasttime']."</font></td>
    <td>".$data['description']."</td>
    <td style='text-align:left;padding-left:5px'>".nl2br($data['notes'])."</td>
    <td nowrap>
        <a href=\"network_edit.php?ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."\" class='greybox' title='Edit ".Util::htmlentities($data['ssid'])." description, type and notes'><img src='../repository/images/edit.gif' border=0></a>
        <a href=\"javascript:load_data('networks.php?order=$order&action=delete&ssid=".urlencode(base64_encode($data['ssid']))."&sensor=".urlencode($data['sensor'])."')\"><img src='../repository/images/delete_on.gif' border=0></a>
    </td>
    </tr>";
}
?>
</tbody>
</table>

<?
$db->close($conn);
?>
