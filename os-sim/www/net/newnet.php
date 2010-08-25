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
Session::logcheck("MenuPolicy", "PolicyNetworks");
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
                                                                                
  <h1> <?php
echo gettext("New network"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$net_name = POST('name');
$threshold_a = POST('threshold_a');
$threshold_c = POST('threshold_c');
$asset = POST('asset');
$descr = POST('descr');
$nsens = POST('nsens');
$ips = POST('ips');
$alert = POST('alert');
$persistence = POST('persistence');
$rrd_profile = POST('rrd_profile');
ossim_valid($net_name, OSS_NET_NAME, 'illegal:' . _("Net name"));
$nets = explode(",", $ips);
foreach ($nets as $net)
    ossim_valid($net, OSS_IP_CIDR, 'illegal:' . _("Ips"));
ossim_valid($asset, OSS_DIGIT, 'illegal:' . _("Asset"));
ossim_valid($threshold_a, OSS_DIGIT, 'illegal:' . _("threshold_a"));
ossim_valid($threshold_c, OSS_DIGIT, 'illegal:' . _("threshold_c"));
ossim_valid($nsens, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("nnets"));
ossim_valid($alert, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Alert"));
ossim_valid($persistence, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Persistence"));
ossim_valid($rrd_profile, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Net name"));
ossim_valid($descr, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, 'illegal:' . _("Description"));
if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    $sensors = array();
    for ($i = 1; $i <= $nsens; $i++) {
        $name = "mboxs" . $i;
        ossim_valid(POST("$name") , OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, OSS_SPACE);
        if (ossim_error()) {
            die(ossim_error());
        }
        $aux_name = POST("$name");
        if (!empty($aux_name)) $sensors[] = POST("$name");
    }
    if (!count($sensors)) {
        die(ossim_error(_("At least one sensor is required")));
    }
    require_once 'ossim_db.inc';
    require_once 'classes/Net.inc';
    require_once 'classes/Net_scan.inc';
    require_once 'classes/Util.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    Net::insert($conn, $net_name, $ips, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $sensors, $descr);
    if (POST('nessus')) {
        Net_scan::insert($conn, $net_name, 3001, 0);
    }
    if (POST('nagios')) {
        Net_scan::insert($conn, $net_name, 2007, 0);
    }
    $db->close($conn);
    Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)");
}
?>
    <p> <?php echo gettext("Network succesfully inserted"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="Networks") { ?><script>document.location.href="net.php"</script><? } ?>

<?php
// update indicators on top frame
$OssimWebIndicator->update_display();
?>

</body>
</html>

