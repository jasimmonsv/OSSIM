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
Session::logcheck("MenuConfiguration", "MonitorsSensors");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title> <?php
echo gettext("OSSIM Framework"); ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="../style/style.css"/>
<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
<script type="text/javascript">
function contenido(id) {
	$("#"+id).toggle();
	id_icono = id.substr (4);
	if ($("#icono"+id_icono).attr('src') == "../pixmaps/server--plus.png") { $("#icono"+id_icono).attr('src','../pixmaps/server--minus.png');  }
	else { $("#icono"+id_icono).attr('src','../pixmaps/server--plus.png'); }
}
function load_lead(pid) {
    $.ajax({
        type: "GET",
        url: "get_sensor_leads.php?pid="+pid,
        data: "",
        success: function(msg) {
              $('#plugin_'+pid).html(msg);
              $('#plugin_'+pid).show();
              $('#selector_'+pid).show();
              mark(pid);
        }
    });
}
function mark(id) {
	var y = $('#yellow_'+id).val()*3600;
	var r = $('#red_'+id).val()*3600; // need seconds
	var now = new Date; // Generic JS date object
	var unixtime_ms = now.getTime(); // Returns milliseconds since the epoch
	var unixtime = parseInt(unixtime_ms / 1000);
	$('#plugin_'+id+' .trc').each(function(){
		var eventdate = parseInt($(this).attr('txt'));
		var img = "";
		var bgcolor = "";
		if (unixtime - eventdate >= r) {
			img = "../pixmaps/flag_red.png";
			bgcolor = "#FFDFDF";
		} else if (unixtime - eventdate >= y) {
			img = "../pixmaps/flag_yellow.png";
			bgcolor = "#FFFBCF";
		} else {
			img = "../pixmaps/flag_green.png";
			bgcolor = "#CFFFD1";
		}
		$(this).css("background-color",bgcolor);
		$('td img',this).attr("src",img);
	});
}
</script>
<? include ("../host_report_menu.php") ?>
</head>
<body>                             
<?php
include ("../hmenu.php");

// Sensors perm check
if (!Session::menu_perms("MenuConfiguration", "PolicySensors")) {
	die("<br>"._("You need permissions of section '")."<b>"._("Configuration -> SIEM Components -> Sensors")."</b>"._("' to see this page. Contact with the administrator."));
}

require_once 'ossim_conf.inc';
require_once 'ossim_db.inc';
require_once 'classes/Sensor.inc';
require_once 'classes/Plugin.inc';
require_once 'get_sensors.php';
require_once 'get_sensor_plugins.php';
require_once 'classes/Security.inc';
$ip_get = GET('sensor');
$cmd = GET('cmd');
$id = GET('id');
ossim_valid($ip_get, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Sensor"));
ossim_valid($cmd, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Cmd"));
ossim_valid($id, OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Id"));
if (ossim_error()) {
    die(ossim_error());
}
/* connect to db */
$db = new ossim_db();
$conn = $db->connect();
$conn_snort = $db->snort_connect();
$conf = $GLOBALS["CONF"];
$acid_link = $conf->get_conf("acid_link");
$acid_prefix = $conf->get_conf("event_viewer");
$acid_main_link = str_replace("//", "/", $conf->get_conf("acid_link") . "/" . $acid_prefix . "_qry_main.php?clear_allcriteria=1&search=1&bsf=Query+DB&ossim_risk_a=+");
#
$db_sensor_list = array();
$list_no_active = array();
$tmp_list = Sensor::get_all($conn);
if (is_array($tmp_list)) {
    foreach($tmp_list as $tmp) {
        $db_sensor_list[] = $tmp->get_ip();
        $db_sensor_rel[$tmp->get_ip() ] = $tmp->get_name();
        $list_no_active[$tmp->get_ip() ] = $tmp->get_name();
    }
}
list($sensor_list, $err) = server_get_sensors($conn);
if ($err != "") echo $err;
if (!$sensor_list && empty($ip_get)) echo "<p> " . gettext("There aren't any sensors connected to OSSIM server") . " </p>";

$ossim_conf = $GLOBALS["CONF"];
$use_munin = $ossim_conf->get_conf("use_munin");
$capa = 0;

foreach($sensor_list as $sensor) {

    $ip = $sensor["sensor"];
    unset($list_no_active[$ip]); // borramos de la lista de no activos los que están activos
    if (isset($db_sensor_rel[$ip])) $name = $db_sensor_rel[$ip];
    $state = $sensor["state"];
    //if ((isset($ip_get)) && ($ip_get != $ip)) continue;

    if ((!empty($cmd)) && (!empty($id))) {
    
        /*
        *  Send message to server
        *    sensor-plugin-CMD sensor="" plugin_id=""
        *  where CMD can be (start|stop|enable|disable)
        */
        require_once ('ossim_conf.inc');
        $ossim_conf = $GLOBALS["CONF"];
        /* get the port and IP address of the server */
        $address = $ossim_conf->get_conf("server_address");
        $port = $ossim_conf->get_conf("server_port");
        /* create socket */
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0) {
            echo _("socket_create() failed: reason: ") . socket_strerror($socket) . "\n";
            exit();
        }
        /* connect */
        $result = socket_connect($socket, $address, $port);
        if ($result < 0) {
            echo _("socket_connect() failed.\nReason:")." ($result) " . socket_strerror($result) . "\n\n";
            exit();
        }
        /* first send a connect message to server */
        $in = 'connect id="1" type="web"' . "\n";
        $out = '';
        socket_write($socket, $in, strlen($in));
        $out = socket_read($socket, 2048, PHP_NORMAL_READ);
        if (strncmp($out, "ok id=", 4)) {
            echo "<p><b>" . gettext("Bad response from server") . "</b></p>";
            break;
        }
        /* send command */
        $msg = "sensor-plugin-$cmd sensor=\"$ip\" plugin_id=\"$id\"\n";
        socket_write($socket, $msg, strlen($msg));
        socket_close($socket);
        /* wait for
        *   framework => server -> agent -> server => framework
        * messages */
        //sleep(5);
        
    }

    /* get plugin list for each sensor */
    $sensor_plugins_list = server_get_sensor_plugins();
    /*
    *  show sensor ip (and sensor name if available)
    *  at the top of the table
    */
    $up_enabled = 0;
    $down_disabled = 0;
    $totales = 0;
    if ($sensor_plugins_list) {
        foreach($sensor_plugins_list as $sensor_plugin) {
            if ($sensor_plugin["sensor"] == $ip) {
                $state = $sensor_plugin["state"];
                $enabled = $sensor_plugin["enabled"];
                if ($state == 'start' || $enabled == 'true') {
                    $up_enabled++;
                }
                if ($state == 'stop' || $enabled != 'true') {
                    $down_disabled++;
                }
                $totales++;
            }
        }
    }
    $id_estado = "icono" . $capa;
    
    echo "<table class=\"noborder\" border=0 cellpadding=0 cellspacing=0 width=\"100%\" align=\"center\">";
    echo "<tr>";
    echo "<td class=\"noborder\"><a href=\"\" onclick=\"contenido('capa$capa');return false;\"><img id='$id_estado' align=\"bottom\" src=".(($ip_get==$ip) ? "\"../pixmaps/server--minus.png\"" : "\"../pixmaps/server--plus.png\"")." border=\"0\"></a></td>";
    echo "<td class=\"noborder\" style=\"text-align: left;padding-left:5px;\" height=\"25\" bgcolor=\"#DCDCDC\" nowrap>";
    echo "<table class=\"noborder\" border=0 cellpadding=0 cellspacing=0 style=\"background-color:transparent;\" nowrap>";
    echo "<tr><td class=\"noborder\" style=\"padding-right:2px;\">";
    echo "</td><td class=\"noborder\" style=\"text-align: left;padding-right:4px;\">";
    echo " <a href='' onclick=\"contenido('capa$capa');return false;\" class='HostReportMenu' id='$ip;".((isset($name)) ? $name : $ip)."'>$ip</a>";
    if (isset($name)) echo " [ $name ] ";
    /*
    * Show munin link for every sensor
    *
    */
    echo "</td><td class=\"noborder\" style=\"padding-right:4px;\">";
    if ($use_munin == 1) {
        $munin_link = $ossim_conf->get_conf("munin_link");
        if ($munin_link=="") $munin_link = "/munin/";
        $server_ip=trim(`grep framework_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
        $https=trim(`grep framework_https /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`);
        if ($ip == $server_ip) {
        	$munin_url='http'.(($https=="yes") ? "s" : "").'://'.$_SERVER["SERVER_NAME"].$munin_link;
        	$munin_url=str_replace("localhost",$ip,$munin_url);
        	$testmunin = "http://" . $ip . "/munin/";
        } else {
        	$munin_url='http://'.$ip.$munin_link;
        	$testmunin = $munin_url;
		}	
		// check valid munin url
		error_reporting(0);
		$testlink = get_headers($testmunin);
		error_reporting(E_ALL ^ E_NOTICE);
		
		if (preg_match("/200 OK/",$testlink[0])) {
    ?>
    	    <a href="<?php echo $munin_url; ?>"><img align="bottom" src="../pixmaps/chart_bar.png" border="0"></a>
	<?php
	    } else {
	    	echo "<img align=\"bottom\" src=\"../pixmaps/chart_bar_off.png\" border=\"0\">";
	    }
    }
    echo "</td><td class=\"noborder\" style=\"text-align: left;\">";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;\">[ "._("UP or ENABLED").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#089313;font-weight:bold;\">$up_enabled</span> ";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;\">/ "._("DOWN or DISABLED").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#E00E01;font-weight:bold;\">$down_disabled</span> ";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;\">/ "._("Totals").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#000000;font-weight:bold;\">$totales</span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;\"> ]</span>";
    echo "</table>";
    echo "</td></tr>";
    echo "<tr><td></td><td height=\"1\" bgcolor=\"#FFFFFF\"></td></tr>";
    if (is_array($db_sensor_list)) {
        if (!in_array($ip, $db_sensor_list)) {
            echo "<p><b>"._("Warning")."</b></font>:
                "._("The sensor is being reported as enabled by
                the server but isn't configured.")."<br/>
                "._("Click")." <a href=\"newsensorform.php?ip=$ip\">"._("here")."</a>
                "._("to configure the sensor").".</p>";
        }
    }
?>
<tr><td class="noborder"></td>
<td class="noborder">
<div id="<?php echo "capa" . $capa ?>" style="display:<?=($ip_get==$ip) ? "block" : "none"?>">
  <table class="noborder" width="100%" height="100%"><tr height="100%">
  <td class="nobborder" width="36" height="100%">
	  <table border=0 cellpadding=0 cellspacing=0 width="36" height="100%" class="noborder">
	  <tr><td class="nobborder" height="29"><img src="../pixmaps/bktop.gif" border=0></td></tr>
	  <tr><td class="nobborder" style="background:url(../pixmaps/bkbg.gif) repeat-y">&nbsp;</td></tr>
	  <tr><td class="nobborder" height="51"><img src="../pixmaps/bkcenter.gif" border=0></td></tr>
	  <tr><td class="nobborder" style="background:url(../pixmaps/bkbg.gif) repeat-y">&nbsp;</td></tr>
	  <tr><td class="nobborder" height="29"><img src="../pixmaps/bkdown.gif" border=0></td></tr>
	  </table>
  </td><td class="nobborder" style="background:#E0EFC2;padding:5px">
  <table align="left" width="100%">
    <tr>
      <th></th>
      <th> <?php
    echo gettext("Plugin"); ?> </th>
      <th> <?php
    echo gettext("Process Status"); ?> </th>
      <th> <?php
    echo gettext("Action"); ?> </th>
      <th> <?php
    echo gettext("Plugin status"); ?> </th>
      <th> <?php
    echo gettext("Action"); ?> </th>
      <th> <?php
    echo gettext("Last SIEM Event"); ?> </th>
    </tr>
<?php
    if ($sensor_plugins_list) {	
        foreach($sensor_plugins_list as $sensor_plugin) {
            if ($sensor_plugin["sensor"] == $ip) {
                $id = $sensor_plugin["plugin_id"];
                $state = $sensor_plugin["state"];
                $enabled = $sensor_plugin["enabled"];
                if ($plugin_list = Plugin::get_list($conn, "WHERE id = $id")) {
                    $plugin_name = $plugin_list[0]->get_name();
                } else {
                    $plugin_name = $id;
                }
                $event = Plugin::get_latest_SIM_Event($conn_snort,$id,$plugin_name);
?>
    <tr>
      <td width="16"><a href="javascript:;" onclick="load_lead('<?=$id?>')"><img src="../pixmaps/plus-small.png" border="0" align="absmiddle"></a></td>
      <td><?php
                echo $plugin_name ?></td>
<?php
                if ($state == 'start') {
?>
      <td><font color="GREEN"><b> <?php
                    echo gettext("UP"); ?> </b></font></td>
      <td><a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] . "?sensor=$ip&ip=$ip&cmd=stop&id=$id" ?>">
	    <?php
                    echo gettext("stop"); ?> </a></td>
<?php
                } elseif ($state == 'stop') {
?>
      <td><font color="RED"><b> <?php
                    echo gettext("DOWN"); ?> </b></font></td>
      <td><a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] . "?sensor=$ip&ip=$ip&cmd=start&id=$id" ?>">
	    <?php
                    echo gettext("start"); ?> </a></td>
      
<?php
                } else {
                    echo "
                          <td>"._("Unknown")."</td>
                          <td>-</td>
                        ";
                }
                if ($enabled == 'true') {
?>
      <td><font color="GREEN"><b> <?php
                    echo gettext("ENABLED"); ?> </b></font></td>
      <td><a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] . "?sensor=$ip&ip=$ip&cmd=disable&id=$id" ?>">
	    <?php
                    echo gettext("disable"); ?> </a></td>
<?php
                } else {
?>
      <td><font color="RED"><b> <?php
                    echo gettext("DISABLED"); ?> </b></font></td>
      <td><a href="<?php
                    echo $_SERVER["SCRIPT_NAME"] . "?sensor=$ip&ip=$ip&cmd=enable&id=$id" ?>">
	    <?php
                    echo gettext("enable"); ?> </a></td>
<?php
                }
?>
      <td>
			<table class="noborder"><tr>
			<td class="small nobborder" nowrap><i><?=$event["timestamp"]?></i>&nbsp;</td>
			<td class="small nobborder"><a href="<?=$acid_main_link."&plugin=".urlencode($event["plugin_id"])?>"><b><?=$event["sig_name"]?></b></a></td>
			</tr></table>
	  </td>
    </tr>
    <tr>
    	<td colspan="2" id="selector_<?=$id?>" style="display:none;padding-left:10px;border-bottom:none">
    		<form style="margin:0px"><table class="noborder center">
    		<tr>
    			<td class="noborder"><img src="../pixmaps/flag_yellow.png" border="0"></td>
    			<td class="noborder"><input type="text" size="4" id="yellow_<?=$id?>" value="12"> <?=_("hours")?></td>
    		</tr>
    		<tr>
    			<td class="noborder"><img src="../pixmaps/flag_red.png" border="0"></td>
    			<td class="noborder"><input type="text" size="4" id="red_<?=$id?>" value="48"> <?=_("hours")?></td>
    		</tr>
    		<tr>
    			<td colspan="2" class="noborder" align="center"><input type="button" class="button" onclick="mark('<?=$id?>')" value="<?=_("Mark")?>"></td>
    		</tr>
    		</table></form>
    	</td>    
    	<td colspan="5" id="plugin_<?=$id?>" style="display:none;padding-left:0px;border-bottom:none"></td>
    </tr>
<?php
            } // if
            
        } // foreach
        
?>
    <tr>
      <td colspan="7">
        <a href="<?php
        echo $_SERVER["SCRIPT_NAME"] . "?sensor=$ip" ?>"> <?=_("Refresh")?> </a>
      </td>
    </tr>
<?php
    } // if
    
?>
 <!--        </table>
     </td>
    </tr>-->
  </table>
  </td></tr></table>
</div>
</td></tr>


<?php
    $capa++;
}
foreach($list_no_active as $key => $value) {
    echo "<tr><td class=\"noborder\"><img align=\"bottom\" src=\"../pixmaps/server.png\" border=\"0\"></td>";
    echo "<td class=\"noborder\" style=\"text-align: left;padding-left:5px;\" height=\"25\" bgcolor=\"#EDEDED\" nowrap>";
    echo "<table class=\"noborder\" border=0 cellpadding=0 cellspacing=0 style=\"background-color:transparent;\" nowrap>";
    echo "<tr><td class=\"noborder\" style=\"padding-right:2px;\">";
    echo "</td><td class=\"noborder\" style=\"text-align: left;color:#696563;padding-right:4px;\">";
    echo "$key [ $value ] ";
    echo "</td><td class=\"noborder\" style=\"padding-right:4px;\">";
    echo "<img align=\"bottom\" src=\"../pixmaps/chart_bar_off.png\" border=\"0\">";
    echo "</td><td class=\"noborder\" style=\"text-align: left;\">";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#696563;\"> [ "._("UP or ENABLED").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#089313;font-weight:bold;\"> - </span> ";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#696563;\">/ "._("DOWN or DISABLED").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#E00E01;font-weight:bold;\"> - </span> ";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#696563;\">/ "._("Totals").": </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#000000;font-weight:bold;\"> - </span>";
    echo "<span style=\"font-family:tahoma; font-size:11px;font-weight:normal;color:#696563;\">]</span>";
    echo "</td></tr>";
    echo "</table>";
    echo "</td></tr>";
    echo "<tr><td></td><td height=\"1\" bgcolor=\"#FFFFFF\"></td></tr>";
}
echo "</table>";
$db->close($conn);
$db->close($conn_snort);
?>
  <br/>
  <style type="text/css"> html,body { height : auto !important; height:100%; min-height:100%; } </style>
</body>
</html>

