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
Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
require_once ('ossim_conf.inc');
require_once ("ossim_db.inc");
require_once ('classes/Security.inc');
$conf = $GLOBALS["CONF"];
$range = GET('range');
$ip = GET('ip');
$what = GET('what');
$start = GET('start');
$type = GET('type');
$zoom = GET('zoom');
ossim_valid($range, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("range"));
ossim_valid($ip, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("ip"));
ossim_valid($what, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, 'illegal:' . _("what"));
ossim_valid($start, OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("start"));
ossim_valid($type, "host", "net", "global", "level", OSS_NULLABLE, 'illegal:' . _("type"));
ossim_valid($zoom, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("zoom"));
if (ossim_error()) {
    die(ossim_error());
}
?>

<html>
<head>
  <title> <?php echo "$ip " . gettext("graph"); ?> </title>
  <meta http-equiv="refresh" content="150">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
</head>

<body>
<? include("../hmenu.php"); ?>
<br>
<table align="center">
  <tr height="30">
    <td align="center" colspan="2">
      <a href="javascript:history.go(-1)"><?=_("Back")?></a> << &nbsp;&nbsp;
      <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?range=all&ip=<?php echo "$ip&what=$what&start=N-1D&type=$type&zoom=$zoom" ?>"><?= ($range=="all") ? "<b>" : "" ?> <?php echo _("All"); ?> </b></a> | 
      <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?range=day&ip=<?php echo "$ip&what=$what&start=N-1D&type=$type&zoom=$zoom" ?>"><?= ($range=="day") ? "<b>" : "" ?> <?php echo _("Last Day"); ?> </b></a> |
      <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?range=week&ip=<?php echo "$ip&what=$what&start=N-7D&type=$type&zoom=$zoom" ?>"><?= ($range=="week") ? "<b>" : "" ?> <?php echo _("Last Week"); ?> </b></a> |
      <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?range=month&ip=<?php echo "$ip&what=$what&start=N-1M&type=$type&zoom=$zoom" ?>"><?= ($range=="month") ? "<b>" : "" ?> <?php echo _("Last Month"); ?> </b></a> |
      <a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?range=year&ip=<?php echo "$ip&what=$what&start=N-1Y&type=$type&zoom=$zoom" ?>"><?= ($range=="year") ? "<b>" : "" ?> <?php echo _("Last Year"); ?> </b></a>
    </td>
  </tr>

<?php
/* range = day, week, month or year. Only display a single graph */
if ($range != "all") {
?>
  <tr>
    <td class="noborder" style="text-align:right">
      <img src="<?php
    echo "../report/graphs/draw_rrd.php?ip=$ip&what=$what&start=$start&end=N&type=$type"; ?>">
    </td>
    <td class="noborder" style="text-align:left">
       <?=_("file name")?>: <b><?php echo $ip ?>.rrd</b><br/>
       <?=_("date range")?>: <?php echo $range ?><br/>
       <?=_("rrd type")?>: <?php echo $type ?><br/>
    </td>
  </tr>
  
<?php
    /* range = all, display all graphs */
} else {
    $dates = array(
        "day" => "N-1D",
        "week" => "N-7D",
        "month" => "N-1M",
        "year" => "N-1Y"
    );
    foreach($dates as $date_legend => $date_rrd) {
?>
  <tr>
    <td class="noborder" style="text-align:right">
      <img src="<?php echo "../report/graphs/draw_rrd.php?ip=$ip&what=$what&start=$date_rrd&end=N&type=$type"; ?>">
    </td>
    <td class="noborder" style="text-align:left">
       <?=_("file name")?>: <b><?php echo $ip ?>.rrd</b><br/>
       <?=_("date range")?>: <?php echo $date_legend ?><br/>
       <?=_("rrd type")?>: <?php echo $type ?><br/>
    </td>
  </tr>
<?php
    } /* foreach */
} /* else */
?>
</table>
</HTML>
<BODY>
