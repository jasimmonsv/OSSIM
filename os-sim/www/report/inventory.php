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
Session::logcheck("MenuReports", "ReportsHostReport");
?>

<html>
<head>
  <title> <?php
echo gettext("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<?php
require_once 'classes/Security.inc';
$host = GET('host');
ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("ip"));
if (ossim_error()) {
    die(ossim_error());
}
?>

<!-- <h1><?php echo gettext("Inventory") . " - $host" ?></h1> -->

<?php
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';
require_once 'classes/Host.inc';
require_once 'classes/Host_os.inc';
require_once 'classes/Host_mac.inc';
require_once 'classes/Host_services.inc';
require_once 'classes/Host_netbios.inc';
require_once 'classes/Frameworkd_socket.inc';
require_once 'classes/Net.inc';
$db = new ossim_db();
$conn = $db->connect();
if (GET('edit') == "Update") {
    for ($i = 0;; $i++) {
        $nagi = "nagios" . $i;
        $nagp = "port" . $i;
        $serv = GET($nagi);
        $nport = GET($nagp);
        if (!isset($_GET[$nagi])) break;

        if (isset($_GET[$nagp]) && is_numeric($nport)) {
            Host_services::set_nagios($conn, $host, $nport, 1);
        } else {
            Host_services::set_nagios($conn, $host, $serv, 0);
        }
    }
    $s = new Frameworkd_socket();
    if ($s->status) {
        if (!$s->write('nagios action="reload" "')) echo "Frameworkd couldn't recieve a nagios command.<br>";
        $s->close();
    } else echo "Couldn't connect to frameworkd...<br>";
}
/* services update */
if (GET('origin') == 'active' && GET('update') == 'services') {
    $conf = $GLOBALS["CONF"];
    $nmap = $conf->get_conf("nmap_path");
    $services = shell_exec("$nmap -sV -P0 $host");
    $lines = split("[\n\r]", $services);
    foreach($lines as $line) {
        preg_match('/(\S+)\s+open\s+([\w\-\_\?]+)(\s+)?(.*)$/', $line, $regs);
        if (isset($regs[0])) {
            list($port, $protocol) = explode("/", $regs[1]);
            $protocol = getprotobyname($protocol);
            if ($protocol == - 1) {
                $protocol = 0;
            } else {
            }
            $service = $regs[2];
            $service_type = $regs[2];
            $version = $regs[4];
            $origin = 1;
            $date = strftime("%Y-%m-%d %H:%M:%S");
            Host_services::insert($conn, $host, $port, $date, $_SERVER["SERVER_ADDR"], $protocol, $service, $service_type, $version, $origin); // origin = 0 (pads), origin = 1 (nmap)
            
        }
    }
}
?>
    <table align="center">
      <tr><td colspan="2"></td></tr>
      <tr><th colspan="2"> <?php
echo gettext("Host Info"); ?> </th></tr>
<?php
$sensor_list = array();
if ($host_list = Host::get_list($conn, "WHERE ip = '$host'")) {
    $host_aux = $host_list[0];
    $sensor_list = $host_aux->get_sensors($conn);
?>
      <tr>
        <th> <?php
    echo gettext("Name"); ?> </th>
        <td><?php
    echo $host_aux->hostname ?></td>
      </tr>

<?php
}
?>
      <tr>
        <th>Ip</th>
        <td><b><?php
echo $host ?></b></td>
      </tr>
<?php
if ($os = Host_os::get_ip_data($conn, $host)) {
?>
      <tr>
        <th> <?php
    echo gettext("Operating System"); ?> </th>
        <td>
<?php
    echo $os["os"];
    echo Host_os::get_os_pixmap($conn, $host);
?>
        </td>
      </tr>
<?php
}
?>

<?php
if ($mac = Host_mac::get_ip_data($conn, $host)) {
?>
      <tr>
        <th>MAC</th>
        <td><?php
    echo $mac["mac"]; ?></td>
      </tr>
<?php
}
?>
    
      
<?php
if ($netbios_list = Host_netbios::get_list($conn, "WHERE ip = '$host'")) {
    $netbios = $netbios_list[0];
?>
      <tr>
        <th> <?php
    echo gettext("Netbios Name"); ?> </th>
        <td><?php
    echo $netbios->name ?></td>
      </tr>
      <tr>
        <th> <?php
    echo gettext("Netbios Work Group"); ?> </th>
        <td><?php
    echo $netbios->wgroup ?></td>
      </tr>
<?php
}
?>
      <tr><td colspan="2"></td></tr>
      <tr><td colspan="2"></td></tr>
      <tr><th colspan="2"><?php
echo gettext("Host belongs to:"); ?></td></tr>

<?php
if ($net_list = Net::get_list($conn)) {
    foreach($net_list as $net) {
        if (Net::is_ip_in_cache_cidr($conn, $host, $net->get_ips())) {
?>
      <tr>
        <th><?php
            echo gettext("Net"); ?></th>
        <td><?php
            echo $net->get_name() ?></td>
      </tr>
<?php
        }
    }
}
if ($sensor_list) {
    foreach($sensor_list as $sensor) {
?>
      <tr>
        <th>Sensor</th>
        <td><?php
        echo $sensor->get_sensor_name() ?></td>
      </tr>
<?php
    }
}
?>


      <tr><td colspan="2"></td></tr>
      <tr><td colspan="2"></td></tr>
      <tr><th colspan="2"> <?php
echo gettext("Port / Service information"); ?> 
      <?php
if (GET('origin') == 'active') {
    echo gettext("[Active view]") . "<BR>";
?>
      (<A HREF="<?php
    echo $_SERVER["SCRIPT_NAME"] ?>?host=<?php
    echo $host ?>&origin=passive">
      <?php
    echo gettext("Show passive view"); ?> </A>)
      [ <a href="<?php
    echo $_SERVER["SCRIPT_NAME"] ?>?host=<?php
    echo $host
?>&update=services&origin=active">
    <?php
    echo gettext("update"); ?> </a> ]
        </th></h2>
      </tr>
        <?php
} else {
    echo gettext("[Passive view]") . "<BR>";
?>
      (<A HREF="<?php
    echo $_SERVER["SCRIPT_NAME"] ?>?host=<?php
    echo $host ?>&origin=active">
      <?php
    echo gettext("Show active view"); ?> </A>)
        </th></h2>
        <?php
} ?>
      <tr>
      <td colspan="2">
      <form method="GET" action="<?php
echo $_SERVER['SCRIPT_NAME'] ?>">
      <table>
      <tr>
        <th> <?php
echo gettext("Service"); ?> </th>
        <th> <?php
echo gettext("Version"); ?> </th>
        <th> <?php
echo gettext("Date"); ?> </th>
        <th> <?php
echo gettext("Nagios"); ?> </th>
      </tr>
<?php
$servs = 0;
if (GET('origin') == 'active') {
    if ($services_list = Host_services::get_ip_data($conn, $host, '1')) {
        foreach($services_list as $services) {
?>
      <tr>
        <td><?php
            echo $services['service'] . " (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")" ?></td>
        <td><?php
            echo $services['version'] ?></td>
        <td><?php
            echo $services['date'] ?></td>
        <td><input type="checkbox" name="port<?php
            echo $servs; ?>" value="<?php
            echo $services['port'] ?>" <?php
            if ($services['nagios']) echo "CHECKED"; ?>>
            <input type="hidden" name="nagios<?php
            echo $servs++; ?>" value="<?php
            echo $services['port'] ?>"></td>
      </tr>
<?php
        }
    }
} elseif (GET('origin') == 'passive') {
    if ($services_list = Host_services::get_ip_data($conn, $host, '0')) {
        foreach($services_list as $services) {
?>
      <tr>
        <td><?php
            echo $services['service'] . " (" . $services['port'] . "/" . getprotobynumber($services['protocol']) . ")" ?></td>
        <td><?php
            echo $services['version'] ?></td>
        <td><?php
            echo $services['date'] ?></td>
        <td><input type="checkbox" name="port<?php
            echo $servs; ?>" value="<?php
            echo $services['port'] ?>" <?php
            if ($services['nagios']) echo "CHECKED"; ?>>
            <input type="hidden" name="nagios<?php
            echo $servs++; ?>" value="<?php
            echo $services['port'] ?>"></td>
      </tr>
<?php
        }
    }
}
if ($servs > 0) {
?>
        <tr><td colspan=3></td><td>
        <input type="submit" name="edit" value="Update" class="btn" style="font-size:12px">
        <input type="hidden" name="host" value="<?php
    echo $host
?>" >
        <input type="hidden" name="origin" value="<?php
    echo GET('origin') ?>" >
        </td></tr>
<?php
}
?>
      </table>
        </form>
      </td>
      </tr>
    </table>

<?php
$db->close($conn);
?>

</body>
</html>

