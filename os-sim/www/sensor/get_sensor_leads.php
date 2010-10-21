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
require_once 'classes/Security.inc';
Session::logcheck("MenuMonitors", "MonitorsSensors");
require_once 'ossim_conf.inc';
require_once 'ossim_db.inc';
require_once 'classes/Plugin.inc';
require_once 'classes/Host.inc';
require_once 'classes/DateDiff.inc';
$plugin_id = GET('pid');
ossim_valid($plugin_id,  OSS_DIGIT, 'illegal:' . _("Plugin name"));
if (ossim_error()) {
    die(ossim_error());
}
/* connect to db */
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$acid_main_link = str_replace("//", "/", $conf->get_conf("acid_link") . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&search=1&bsf=Query+DB&ossim_risk_a=+");
$db = new ossim_db();
$conn = $db->connect();
$conn_snort = $db->snort_connect();
$events = Plugin::get_latest_SIM_Event_by_SID($conn_snort,$plugin_id);
?>
<table class="noborder" style="width:100%;border-color:#CBCBCB;border-width:1px">
<? if (count($events)==0) { ?>
<tr>
<th><?=_("No events found")?></th>
</tr>
<? } else { ?>
<tr>
<th>&nbsp;</th>
<th><?=_("Device")?></th>
<th><?=_("Date")?></th>
<th><?=_("Last SIEM Event")?></th>
</tr>
<? }
   foreach ($events as $event) { 
		$hostname = Host::ip2hostname($conn,$event["ip"]);
		if ($event["ip"]!=$hostname) $hostname = $event["ip"]." [$hostname]";
		$ago = TimeAgo(strtotime($event["event_date"]),time());
?>
<tr class="trc" txt="<?=strtotime($event["event_date"])?>">
<td class="small nobborder center" width="16px"><img src="" border="0"></td>
<td class="small nobborder"><b><?=$hostname?></b>&nbsp;</td>
<td class="small nobborder center"><?=$event["event_date"]?>&nbsp;&nbsp;(<?=$ago?>)</td>
<td class="small nobborder"><a href="<?=$acid_main_link."&plugin=".urlencode($plugin_id)?>"><b><?=$event["sig_name"]?></b></a></td>
</tr>
<? } ?>
</table>
<?
$db->close($conn);
$db->close($conn_snort);
?>
