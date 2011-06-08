<?php
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
require_once ('classes/Session.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuIncidents", "ControlPanelAlarms");

//
$similar = GET('similar');
ossim_valid($similar, OSS_SHA1, 'illegal:' . _("similar"));
if (ossim_error()) {
    die(ossim_error());
}
//
require_once ('classes/Alarm.inc');
require_once 'ossim_db.inc';
$db = new ossim_db();
$conn = $db->connect();
$info = Alarm::get_similar_info($conn, $similar);
if (count($info) != 0) {
    $tz = Util::get_timezone();
    ?>
    <table class="transparent">
        <tr><td class="nobborder" width="55"><strong><?php echo _("Min date: ")?></strong></td>
            <td class="nobborder"><?php echo gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$info["min_date"])+3600*$tz); ?></td>
        </tr>
        <tr><td class="nobborder" width="55"><strong><?php echo _("Max date: ")?></strong></td>
            <td class="nobborder"><?php echo gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($conn,$info["max_date"])+3600*$tz);?></td>
        </tr>
    </table>
<?php
}
else echo "<strong>$similar</strong> not found in alarms";
$db->close($conn);
?>