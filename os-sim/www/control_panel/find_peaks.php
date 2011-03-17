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
* - RemoveExtension()
* Classes list:
*/
require_once 'classes/Util.inc';
require_once 'classes/Net.inc';
require_once 'classes/Security.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Session.inc';
Session::logcheck("MenuControlPanel", "ControlPanelMetrics");
if (Session::menu_perms("MenuControlPanel", "ControlPanelEvents")) {
    $event_perms = true;
} else {
    $event_perms = false;
}
$event_perms = true; // ControlPanelEvents temporarily disabled
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$type = GET('type');
$start = GET('start');
$end = GET('end');
$range = GET('range');
ossim_valid($range, "day", "week", "month", "year", OSS_NULLABLE, 'illegal:' . _("range"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("type"));
ossim_valid($start, OSS_DIGIT, 'illegal:' . _('Start param'));
ossim_valid($end, OSS_DIGIT, 'illegal:' . _('End param'));
$valid_range = array(
    'day',
    'week',
    'month',
    'year'
);
if (!$range) {
    $range = 'day';
} elseif (!in_array($range, $valid_range)) {
    die(ossim_error('Invalid range'));
}
if ($range == 'day') {
    $rrd_start = "N-1D";
} elseif ($range == 'week') {
    $rrd_start = "N-7D";
} elseif ($range == 'month') {
    $rrd_start = "N-1M";
} elseif ($range == 'year') {
    $rrd_start = "N-1Y";
}
// Get conf
$conf = $GLOBALS['CONF'];
$rrdtool_bin = $conf->get_conf('rrdtool_path') . "/rrdtool";
switch ($type) {
    case 'host':
        $rrdpath = $conf->get_conf('rrdpath_host');
        break;

    case 'net':
        $rrdpath = $conf->get_conf('rrdpath_net');
        break;

    case 'global':
        $rrdpath = $conf->get_conf('rrdpath_global');
        break;

    case 'level':
        $rrdpath = $conf->get_conf('rrdpath_level');
        break;
}
function RemoveExtension($strName, $strExt) {
    if (substr($strName, strlen($strName) - strlen($strExt)) == $strExt) return substr($strName, 0, strlen($strName) - strlen($strExt));
    else return $strName;
}
$start_acid = date("Y-m-d H:i:s", $start);
$end_acid = date("Y-m-d H:i:s", $end);
?>

<!-- <h2 align="center">
   HOSTS C&A
 </h2>
 <h3 align="center">-->
  <?php// echo $start_acid
?><!-- ---> <?php// echo $end_acid ?>
<!-- </h3>-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<center>
<table border="0" width="600">
<tr height="30"><th>
    <?=_("HOSTS C&A")?>
    </th>
</tr>
<tr><td class="nobborder" style="text-align:center;">
    <b><?=$start_acid?></b> - <b><?=$end_acid?></b>
    </td>
    </tr>
</table>
</center>

<?php
// Open dir and get files list
if (is_dir($rrdpath)) {
    if ($gestordir = opendir($rrdpath)) {
        $i = 0;
        $nrrds = 0;
        $rrds = array();
        while (($rrdfile = readdir($gestordir)) !== false) {
            if (strcmp($rrdfile, "..") == 0 || strcmp($rrdfile, ".") == 0) {
                continue;
            }
            $file_date = @filemtime($rrdpath . DIRECTORY_SEPARATOR . $rrdfile);
            // Get files list modified after start date
            if (isset($start) && ($file_date !== false) && ($file_date > $start)) {
                $i++;
                $command = "$rrdtool_bin fetch $rrdpath/$rrdfile MAX -s $start -e $end";
                $handle = popen($command, "r");
                if ($handle) {
                    while (!feof($handle)) {
                        $buffer = fgets($handle, 4096);
                        //9.9650777833e+01 or 9,9650777833e+01 or...
                        //echo "$buffer <br>";
                        if (preg_match("/(\d+):\s+(\d+[\.,]\d+e\+\d+)\s+(\d+[\.,]\d+e\+\d+)/", $buffer, $out)) {
                            if ($out[2] > 0) {
                                // echo "$rrdfile at " . date("Y-m-d H:i:s",$out[1]) . " -> C: " . intval(floatval($out[2])) . "<br>";
                                array_push($rrds, $rrdfile);
                                $nrrds++;
                                break;
                            }
                            if ($out[3] > 0) {
                                // echo "$rrdfile at " . date("Y-m-d H:i:s",$out[1]) . " -> A: " . intval(floatval($out[3])) . "<br>";
                                array_push($rrds, $rrdfile);
                                $nrrds++;
                                break;
                            }
                        }
                    }
                    pclose($handle);
                }
            }
        }
        //      echo "<br>$i files older than ". date("Y-m-d H:i:s",$start)."<br>" ;
        $db = new ossim_db();
        $conn = $db->connect();
        echo "<center><table border=\"0\" width=\"600\">";
        for ($i = 0; $i < $nrrds; $i++) {
            echo "<tr><td style=\"padding-bottom:10px;\">";
            $ip = RemoveExtension($rrds[$i], ".rrd");
            $what = "compromise";
?>
        <center>
         <!--<hr width="80%">-->
         <h4><i><?php echo Host::ip2hostname($conn, $ip) ?></i></h4>
         <?php
            if ($event_perms) { ?>
            <a target="main" href="<?php echo Util::get_acid_events_link($start_acid, $end_acid, "time_d", $ip, "ip_both"); ?>">
         <?php
            } ?>
          <img src="<?php
            echo "../report/graphs/draw_rrd.php?ip=$ip&what=$what&start=$start&end=$end&type=$type"; ?>" border=0>
         <?php
            if ($event_perms) { ?>
            </a>
         <?php
            } ?>
        </center>
        <?php
        echo "</td></tr>";
        }
        echo "</table></center>";
        $db->close($conn);
        closedir($gestordir);
    }
}
?>
</body>
</html>

