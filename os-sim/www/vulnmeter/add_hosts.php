<?
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

require_once('classes/Session.inc');
require_once('classes/Host.inc');
require_once('classes/Sensor.inc');

Session::logcheck("MenuEvents", "EventsVulnerabilities");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
echo gettext("Vulnmeter"); ?> </title>
<!--  <meta http-equiv="refresh" content="3"> -->
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>
<?

$action = POST('action');
$report_id = GET('report_id');

ossim_valid($report_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Report id"));
ossim_valid($action, OSS_NULLABLE, "insert", 'illegal:' . _("action"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

if($action=="insert") {
    $sensors = array();
    $sensor_list = Sensor::get_list($dbconn);
    foreach($sensor_list as $sensor)
    $sensors[] = $sensor->get_name();

    foreach ($_POST as $key => $value) {
        if(preg_match("/^ip(.+)/",$key,$found)) {
            ossim_valid(POST("$key"), OSS_IP_ADDR, 'illegal:' . _("ip"));
            $num = $found[1];
            if(POST("name$num")=="")    $hostname = POST("$key");
            else {
                $hostname = POST("name$num");
                ossim_valid($hostname, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("hostname"));
            }
            if (ossim_error()) {
                die(ossim_error());
            }
            Host::insert($dbconn, POST("$key"), $hostname, 2, 60, 60, "", 0, 0, "", $sensors, ""); 
        }
    }
    
    ?>
    <script type="text/javascript">
        parent.GB_onclose();
    </script>
    <?php
}

$ips = hosts_to_insert($dbconn, $report_id);

?>
<form action="add_hosts.php" method="post">
    <input type="hidden" name="action" value="insert">
    <center>
    <table class="transparent" width="85%" align="center">
        <tr>
            <th><?php echo _("IP")?></th>
            <th><?php echo _("Hostname")?></th>
        </tr>
        <?php 
        $i=1;
        foreach($ips as $ip){ ?>
            <tr>
                <td width="33%" style="text-align:left;" class="nobborder">
                    <input checked="checked" name="ip<?php echo $i;?>" value="<?php echo $ip;?>" type="checkbox"/><?php echo $ip;?>        
                </td>
                <td width="67%" style="text-align:center;" class="nobborder">
                   <input name="name<?php echo $i;?>" value="" style="width: 146px;" type="text"/>
                </td>
            </tr>
            <?php
            $i++;
        } ?>
        <tr>
            <td colspan="2" class="nobborder" style="text-align:center;padding-top:10px;">
                <input type="submit" class="button" value="<?php echo _("Save")?>">
            </td>
        </tr>
    </table>
    </center>
</form>
</center>
<?

$dbconn->disconnect();
?>
</body>
</html>
<?php
function hosts_to_insert($dbconn, $report_id) {
    $in_assets = array();
    $ips = array();

    $result = $dbconn->Execute("SELECT distinct v.hostIP FROM vuln_nessus_results v,host h WHERE v.report_id='$report_id' AND v.hostIP NOT IN (SELECT ip FROM host)");
    while ( !$result->EOF ) {
        if(Session::hostAllowed($dbconn,$result->fields["hostIP"])) {
            $ips[] = $result->fields["hostIP"];
        }
        $result->MoveNext();
    }
    return $ips;
}
?>