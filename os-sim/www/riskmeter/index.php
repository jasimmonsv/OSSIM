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
* - ordenar()
* Classes list:
*/
require_once ('classes/Session.inc');
Session::logcheck("MenuMonitors", "MonitorsRiskmeter");
/* get refresh page value */
$REFRESH_INTERVAL = 5;
require_once 'classes/Security.inc';
$refresh = GET('refresh');
$net_name = GET('net');
$expand = GET('expand');
ossim_valid($refresh, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Refresh interval"));
ossim_valid($net_name, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Net name"));
ossim_valid($expand, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("Net name"));
if (ossim_error()) {
    die(ossim_error());
}
if (empty($refresh)) $refresh = 10;
?>

<html>
<head>
  <title> <?php
echo gettext("Riskmeter"); ?> </title>
  <meta http-equiv="refresh" content="<?php
echo $refresh ?>">
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" href="../style/style.css"/>
</head>

<body>

<?php
require_once ('ossim_conf.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host_qualification.inc');
require_once ('classes/Net_qualification.inc');
require_once ('classes/Net.inc');
require_once ('classes/Net_group.inc');
require_once ('classes/Net_group_reference.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_os.inc');
require_once ('classes/Util.inc');
$db = new ossim_db();
$conn = $db->connect();
/* conf */
$conf = $GLOBALS["CONF"];
$THRESHOLD_DEFAULT = $conf->get_conf("threshold");
$BAR_LENGTH_LEFT = 300;
$BAR_LENGTH_RIGHT = 200;
$BAR_LENGTH = $BAR_LENGTH_LEFT + $BAR_LENGTH_RIGHT;
/*
* Networks
*/
$net_stats = Net_qualification::get_list($conn, "", "ORDER BY net_name");
$max_level = max(ossim_db::max_val($conn, "compromise", "net_qualification") , ossim_db::max_val($conn, "attack", "net_qualification"));
$net_groups = Net_group::get_list($conn);
$net_group_array = array();
if (is_array($net_stats)) {
    foreach($net_stats as $temp_net) {
        $net_name = $temp_net->get_net_name();
        foreach($net_groups as $net_group) {
            $ng_name = $net_group->get_name();
            $net_group_array[$ng_name]["name"] = $ng_name;
            $net_group_array[$ng_name]["threshold_c"] = Net_group::netthresh_c($conn, $ng_name);
            $net_group_array[$ng_name]["threshold_a"] = Net_group::netthresh_a($conn, $ng_name);
            if (Net_group::isNetInGroup($conn, $ng_name, $net_name)) {
                if (!isset($net_group_array[$ng_name]["compromise"])) {
                    $net_group_array[$ng_name]["compromise"] = 0;
                    $net_group_array[$ng_name]["attack"] = 0;
                }
                $net_group_array[$ng_name]["compromise"]+= $temp_net->get_compromise();
                $net_group_array[$ng_name]["attack"]+= $temp_net->get_attack();
            }
        }
    }
}
function ordenar($a, $b) {
    return (($a["max_c"] + $a["max_a"]) < ($b["max_c"] + $b["max_a"])) ? true : false;
}
?>
  <table align="center">

    <!-- configure refresh -->
    <tr>
      <td colspan="2" nowrap><?php
echo gettext("Page Refresh"); ?>: 

        <!-- decrease refresh -->
        <a href="?refresh=<?php
if ($refresh > $REFRESH_INTERVAL) echo $refresh - $REFRESH_INTERVAL;
else echo $refresh; ?>"><b>-</b></a>
        <!-- end decrease refresh -->

        <?php
echo $refresh ?>s

        <!-- increase refresh -->
        <a href="?refresh=<?php
echo $refresh + $REFRESH_INTERVAL ?>"><b>+</b></a>
        <!-- end increase refresh -->

      </td>
      <td></td></tr>
    <!-- end configure refresh -->

    <tr><td colspan="3"></td></tr>
    <tr><th align="center" colspan="3"><?php
echo gettext("Global"); ?></th></tr>
    <tr><td colspan="3"></td></tr>
    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="6" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->
    
    <tr>
      <td><a href="<?php
echo $_SERVER["SCRIPT_NAME"] ?>">
      <?php
echo gettext("Global"); ?> </a></td>
      <td align="center">
        <a href="<?php
echo "../control_panel/show_image.php?range=day" . "&ip=global_" . $_SESSION["_user"] . "&what=compromise&start=N-1D&type=global&zoom=1" ?>"
           target="main">&nbsp;<img src="../pixmaps/graph.gif" 
                                   border="0"/>&nbsp;</a>
      </td>
      <td class="left">
<?php
$compromise = Host_qualification::get_global_compromise($conn);
$attack = Host_qualification::get_global_attack($conn);
/* calculate proportional bar width */
$width_c = (($compromise / $THRESHOLD_DEFAULT) * $BAR_LENGTH_LEFT);
$width_a = (($attack / $THRESHOLD_DEFAULT) * $BAR_LENGTH_LEFT);
if ($compromise <= $THRESHOLD_DEFAULT) {
?>
        <img src="../pixmaps/solid-blue.jpg" height="12"
             width="<?php
    echo $width_c ?>" title="<?php
    echo $compromise ?>">
        C=<?php
    echo $compromise; ?>
<?php
} else {
    if ($width_c >= ($BAR_LENGTH)) {
        $width_c = $BAR_LENGTH;
        $icon = "../pixmaps/major-red.gif";
    } else {
        $icon = "../pixmaps/major-yellow.gif";
    }
?>
        <img src="../pixmaps/solid-blue.jpg" height="12" 
             width="<?php
    echo $BAR_LENGTH_LEFT ?>" 
             title="<?php
    echo $compromise ?>">
        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" 
             width="<?php
    echo $width_c - $BAR_LENGTH_LEFT ?>"
             title="<?php
    echo $compromise ?>">
        C=<?php
    echo $compromise; ?>
        <img src="<?php
    echo $icon ?>">
<?php
}
if ($attack <= $THRESHOLD_DEFAULT) {
?>
        <br/><img src="../pixmaps/solid-red.jpg" height="12"
             width="<?php
    echo $width_a ?>" title="<?php
    echo $attack ?>">
        A=<?php
    echo $attack;; ?>
<?php
} else {
    if ($width_a >= ($BAR_LENGTH)) {
        $width_a = $BAR_LENGTH;
        $icon = "../pixmaps/major-red.gif";
    } else {
        $icon = "../pixmaps/major-yellow.gif";
    }
?>
        <br/>
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
    echo $BAR_LENGTH_LEFT ?>" 
             title="<?php
    echo $attack ?>">
        <img src="../pixmaps/solid-red.jpg" border="0" height="12" 
             width="<?php
    echo $width_a - $BAR_LENGTH_LEFT ?>"
             title="<?php
    echo $attack ?>">
        A=<?php
    echo $attack; ?>
        <img src="<?php
    echo $icon ?>">
<?php
}
?>
      </td>
    </tr>
    
    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->

<?php
// Start group code
if (is_array($net_group_array)) {
    usort($net_group_array, "ordenar");
?>

    <tr><td colspan="3"><br/></td></tr>
    <tr><th align="center" colspan="3"> <?php
    echo gettext("Groups"); ?> </th></tr>
    <tr><td colspan="3"></td></tr>


    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
    echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
    echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->
<?php
    // Do real stuff
    $temporary = current($net_group_array);
    while ($temporary) {
        $group_name = $temporary["name"];
        /* calculate proportional bar width */
        $width_c = ((($compromise = $temporary["compromise"]) / $threshold_c = $temporary["threshold_c"]) * $BAR_LENGTH_LEFT);
        $width_a = ((($attack = $temporary["attack"]) / $threshold_a = $temporary["threshold_a"]) * $BAR_LENGTH_LEFT);
?>
    <!-- C & A levels for each group -->
    <tr>
      <td align="center">
            <?php
        if (!empty($expand) && $expand == $group_name) { ?>
            <a href="<?php
            echo $_SERVER["SCRIPT_NAME"] ?>"><?php
            echo Util::beautify($group_name); ?></a>
            <?php
        } else { ?>
            <a href="<?php
            echo $_SERVER["SCRIPT_NAME"] ?>?expand=<?php
            echo $group_name; ?>"><?php
            echo Util::beautify($group_name); ?></a>
            <?php
        } ?>
      </td>
      <td align="center">
        <a href="<?php
        echo "../control_panel/show_image.php?range=day&ip=" . strtolower($group_name) . "&what=compromise&start=N-1D&type=net&zoom=1"
?>" 
           target="main">&nbsp;<img src="../pixmaps/graph.gif" 
                                   border="0"/>&nbsp;</a>
      </td>

      <td class="left">
<?php
        if ($compromise <= $threshold_c) {
?>
        <img src="../pixmaps/solid-blue.jpg" height="12"
             width="<?php
            echo $width_c ?>" title="<?php
            echo $compromise ?>">
        C=<?php
            echo $compromise; ?>
<?php
        } else {
            if ($width_c >= ($BAR_LENGTH)) {
                $width_c = $BAR_LENGTH;
                $icon = "../pixmaps/major-red.gif";
            } else {
                $icon = "../pixmaps/major-yellow.gif";
            }
?>
        <img src="../pixmaps/solid-blue.jpg" height="12" 
             width="<?php
            echo $BAR_LENGTH_LEFT ?>" 
             title="<?php
            echo $compromise ?>">
        <!-- <img src="../pixmaps/solid-blue.jpg" height="10" width="5"> -->
        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" 
             width="<?php
            echo $width_c - $BAR_LENGTH_LEFT ?>"
             title="<?php
            echo $compromise ?>">
        C=<?php
            echo $compromise; ?>
        <img src="<?php
            echo $icon ?>">
<?php
        }
        if ($attack <= $threshold_a) {
?>
        <br/>
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
            echo $width_a ?>" title="<?php
            echo $attack ?>">
        A=<?php
            echo $attack; ?>
<?php
        } else {
            if ($width_a >= ($BAR_LENGTH)) {
                $width_a = ($BAR_LENGTH);
                $icon = "../pixmaps/major-red.gif";
            } else {
                $icon = "../pixmaps/major-yellow.gif";
            }
?>
        <br/><img src="../pixmaps/solid-red.jpg" height="12" 
                  width="<?php
            echo $BAR_LENGTH_LEFT ?>" 
                  title="<?php
            echo $attack ?>">
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
            echo $width_a - $BAR_LENGTH_LEFT ?>"
             title="<?php
            echo $attack ?>">
        A=<?php
            echo $attack; ?>
        <img src="<?php
            echo $icon ?>">
<?php
        }
?>
      </td>
    </tr>
    <!-- end C & A levels for each net -->

<?php
        $temporary = next($net_group_array);
    }
?>


<?php
}
// End group code

?>

    <tr><td colspan="3"><br/></td></tr>
    <tr><th align="center" colspan="3"> <?php
echo gettext("Networks"); ?> </th></tr>
    <tr><td colspan="3"></td></tr>


    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->


<?php
if ($net_stats) foreach($net_stats as $stat) {
    $net = $stat->get_net_name();
    if (!Net_group::isNetInGroup($conn, $expand, $net)) if (($stat->get_compromise() < Net::netthresh_c($conn, $net)) && ($stat->get_attack() < Net::netthresh_a($conn, $net)) && (Net_group::isNetInAnyGroup($conn, $net))) {
        continue;
    }
    /* get net threshold */
    if ($net_list = Net::get_list($conn, "name = '$net'")) {
        $threshold_c = $net_list[0]->get_threshold_c();
        $threshold_a = $net_list[0]->get_threshold_a();
    } else {
        $threshold_c = $threshold_a = $THRESHOLD_DEFAULT;
    }
    /* calculate proportional bar width */
    $width_c = ((($compromise = $stat->get_compromise()) / $threshold_c) * $BAR_LENGTH_LEFT);
    $width_a = ((($attack = $stat->get_attack()) / $threshold_a) * $BAR_LENGTH_LEFT);
?>

    <!-- C & A levels for each net -->
    <tr>
      <td align="center">
      <?php
    if (GET('expand')) { ?>

        <a href="<?php
        echo $_SERVER["SCRIPT_NAME"] . "?expand=" . $expand . "&net=$net" ?>"><?php
        echo Util::beautify($net) ?></a>
     <?php
    } else { ?>
        <a href="<?php
        echo $_SERVER["SCRIPT_NAME"] . "?net=$net" ?>"><?php
        echo Util::beautify($net) ?></a>

     <?php
    } ?>
      </td>
      <td align="center">
        <a href="<?php
    echo "../control_panel/show_image.php?range=day&ip=" . strtolower($net) . "&what=compromise&start=N-1D&type=net&zoom=1"
?>" 
           target="main">&nbsp;<img src="../pixmaps/graph.gif" 
                                   border="0"/>&nbsp;</a>
      </td>

      <td class="left">
<?php
    if ($compromise <= $threshold_c) {
?>
        <img src="../pixmaps/solid-blue.jpg" height="12"
             width="<?php
        echo $width_c ?>" title="<?php
        echo $compromise ?>">
        C=<?php
        echo $compromise; ?>
<?php
    } else {
        if ($width_c >= ($BAR_LENGTH)) {
            $width_c = $BAR_LENGTH;
            $icon = "../pixmaps/major-red.gif";
        } else {
            $icon = "../pixmaps/major-yellow.gif";
        }
?>
        <img src="../pixmaps/solid-blue.jpg" height="12" 
             width="<?php
        echo $BAR_LENGTH_LEFT ?>" 
             title="<?php
        echo $compromise ?>">
        <!-- <img src="../pixmaps/solid-blue.jpg" height="10" width="5"> -->
        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" 
             width="<?php
        echo $width_c - $BAR_LENGTH_LEFT ?>"
             title="<?php
        echo $compromise ?>">
        C=<?php
        echo $compromise; ?>
        <img src="<?php
        echo $icon ?>">
<?php
    }
    if ($attack <= $threshold_a) {
?>
        <br/>
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
        echo $width_a ?>" title="<?php
        echo $attack ?>">
        A=<?php
        echo $attack; ?>
<?php
    } else {
        if ($width_a >= ($BAR_LENGTH)) {
            $width_a = ($BAR_LENGTH);
            $icon = "../pixmaps/major-red.gif";
        } else {
            $icon = "../pixmaps/major-yellow.gif";
        }
?>
        <br/><img src="../pixmaps/solid-red.jpg" height="12" 
                  width="<?php
        echo $BAR_LENGTH_LEFT ?>" 
                  title="<?php
        echo $attack ?>">
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
        echo $width_a - $BAR_LENGTH_LEFT ?>"
             title="<?php
        echo $attack ?>">
        A=<?php
        echo $attack; ?>
        <img src="<?php
        echo $icon ?>">
<?php
    }
?>
      </td>
    </tr>
    <!-- end C & A levels for each net -->
    
<?php
}
?>

    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->

<?php
/*
* Hosts
*/
/*
* If click on a net, only show hosts of this net
*/
if (GET('net')) {
    if ($net_list = Net::get_list($conn, "name = '$net_name'")) {
        $ips = $net_list[0]->get_ips();
        print "<h1>$ips</h1>";
        if ($ip_list = Host_qualification::get_list($conn)) {
            foreach($ip_list as $host_qualification) {
                if (Net::isIpInNet($host_qualification->get_host_ip() , $ips)) {
                    $ip_stats[] = new Host_qualification($host_qualification->get_host_ip() , $host_qualification->get_compromise() , $host_qualification->get_attack());
                }
            }
        }
    }
} else {
    $ip_stats = Host_qualification::get_list($conn, "", "ORDER BY compromise + attack DESC");
}
//if (count($ip_stats) > 0) {
$max_level = max(ossim_db::max_val($conn, "compromise", "host_qualification") , ossim_db::max_val($conn, "attack", "host_qualification"));
?>


    <tr><td colspan="3"><br/></td></tr>
    <tr><th align="center" colspan="3">
    <A NAME="Hosts" HREF="<?php
echo $_SERVER["SCRIPT_NAME"] ?>?#Hosts" 
        title="Fix"><?php
echo gettext("Hosts") ?></A>
    </th></tr>
    <tr><td colspan="3"></td></tr>

    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->

<?php
if (isset($ip_stats)) {
    foreach($ip_stats as $stat) {
        $ip = $stat->get_host_ip();
        /* get host threshold */
        if ($host_list = Host::get_list($conn, "WHERE ip = '$ip'")) {
            $threshold_c = $host_list[0]->get_threshold_c();
            $threshold_a = $host_list[0]->get_threshold_a();
            $hostname = $host_list[0]->get_hostname();
        } else {
            $threshold_c = $threshold_a = $THRESHOLD_DEFAULT;
            $hostname = $ip;
        }
        /* calculate proportional bar width */
        $width_c = ((($compromise = $stat->get_compromise()) / $threshold_c) * $BAR_LENGTH_LEFT);
        $width_a = ((($attack = $stat->get_attack()) / $threshold_a) * $BAR_LENGTH_LEFT);
?>

    <!-- C & A levels for each IP -->
    <tr>
      <td align="center">
        <a href="../report/index.php?host=<?php
        echo $ip ?>&section=metrics" 
           title="<?php
        echo $ip ?>"><?php
        echo $hostname ?></a>
        <?php
        echo Host_os::get_os_pixmap($conn, $ip); ?>
      </td>
      <td align="center">
        <a href="<?php
        echo "../control_panel/show_image.php?range=day&ip=$ip&what=compromise&start=N-1D&type=host&zoom=1"
?>" target="main">
        &nbsp;<img src="../pixmaps/graph.gif" border="0"/>&nbsp;</a>
      </td>

      <td class="left">
<?php
        if ($compromise <= $threshold_c) {
?>
        <img src="../pixmaps/solid-blue.jpg" height="12" 
             width="<?php
            echo $width_c ?>" title="<?php
            echo $compromise ?>">
        C=<?php
            echo $compromise; ?>
<?php
        } else {
            if ($width_c >= ($BAR_LENGTH)) {
                $width_c = $BAR_LENGTH;
                $icon = "../pixmaps/major-red.gif";
            } else {
                $icon = "../pixmaps/major-yellow.gif";
            }
?>
        <img src="../pixmaps/solid-blue.jpg" height="12" 
             width="<?php
            echo $BAR_LENGTH_LEFT ?>"
             title="<?php
            echo $compromise ?>">
        <!-- <img src="../pixmaps/solid-blue.jpg" height="10" width="5"> -->
        <img src="../pixmaps/solid-blue.jpg" border="0" height="12" 
             width="<?php
            echo $width_c - $BAR_LENGTH_LEFT ?>"
             title="<?php
            echo $compromise ?>">
        C=<?php
            echo $compromise; ?>
        <img src="<?php
            echo $icon ?>">
<?php
        }
        if ($attack <= $threshold_a) {
?>
        <br/><img src="../pixmaps/solid-red.jpg" height="12" 
                  width="<?php
            echo $width_a ?>" 
                  title="<?php
            echo $attack ?>">
        A=<?php
            echo $attack; ?>
<?php
        } else {
            if ($width_a >= ($BAR_LENGTH)) {
                $width_a = $BAR_LENGTH;
                $icon = "../pixmaps/major-red.gif";
            } else {
                $icon = "../pixmaps/major-yellow.gif";
            }
?>
        <br/><img src="../pixmaps/solid-red.jpg" height="12" 
                  width="<?php
            echo $BAR_LENGTH_LEFT ?>"
             title="<?php
            echo $attack ?>">
        <img src="../pixmaps/solid-red.jpg" height="12" 
             width="<?php
            echo $width_a - $BAR_LENGTH_LEFT ?>" 
             title="<?php
            echo $attack ?>">
        A=<?php
            echo $attack; ?>
        <img src="<?php
            echo $icon ?>">
<?php
        } /* foreach */
    } /* if */
?>
      </td>
    </tr>
    <!-- end C & A levels for each IP -->
    
<?php
}
?>
    <!-- rule for threshold -->
    <tr>
      <td></td><td></td>
      <td class="left">
        <img src="../pixmaps/gauge-blue.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_LEFT; ?>">
        <img src="../pixmaps/gauge-red.jpg" height="5" 
             width="<?php
echo $BAR_LENGTH_RIGHT; ?>">
      </td>
    </tr>
    <!-- end rule for threshold -->


</table>
<br>
</body>
</html>

<?php
$db->close($conn);
?>
