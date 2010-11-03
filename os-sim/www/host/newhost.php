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
require_once ('classes/Security.inc');
require_once ('ossim_db.inc');
require_once ('classes/Host.inc');
require_once ('classes/Host_scan.inc');
require_once ('classes/Util.inc');

Session::logcheck("MenuPolicy", "PolicyHosts");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/style.css"/>
</head>

<body>
    <h1> <?php echo gettext("New Host"); ?> </h1>                                     
	

<?php
	
$insert = POST('insert');
$hostname = POST('hostname');
$fqdns = POST('fqdns');
$latitude = POST('latitude');
$longitude = POST('longitude');
$ip = POST('ip');
$id = POST('id');
$threshold_c = POST('threshold_c');
$threshold_a = POST('threshold_a');
$nsens = POST('nsens');
$asset = POST('asset');
$alert = POST('alert');
$persistence = POST('persistence');
$nat = POST('nat');
$descr = POST('descr');
$os = POST('os');
$mac = POST('mac');
$mac_vendor = POST('mac_vendor');
$nessus = POST('nessus');
$nagios = POST('nagios');
$sensor_name = POST('name');
$rrd_profile = POST('rrd_profile');
ossim_valid($insert, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("insert"));
ossim_valid($hostname, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("hostname"));

$fqdns_list = explode (",", $fqdns);

if ( !empty ($fqdns_list) && is_array ($fqdns_list))
foreach ($fqdns_list as $k => $fqdn)
	ossim_valid($fqdn, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("FQDN/Aliases"));

ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _("ip"));
ossim_valid($id, OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("id"));
ossim_valid($threshold_a, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("threshold_a"));
ossim_valid($threshold_c, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("threshold_c"));
ossim_valid($nsens, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("nsens"));
ossim_valid($asset, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("asset"));
ossim_valid($alert, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("alert"));
ossim_valid($persistence, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("persistence"));
ossim_valid($nat, OSS_NULLABLE, OSS_IP_ADDR, 'illegal:' . _("nat"));
ossim_valid($descr, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("descr"));
ossim_valid($rrd_profile, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("rrd_profile"));
ossim_valid($os, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, 'illegal:' . _("os"));
ossim_valid($mac_vendor, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_AT, "(", ")", 'illegal:' . _("mac_vendor"));
ossim_valid($mac, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("mac"));
ossim_valid($nessus, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("nesus"));
ossim_valid($nagios, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("nagios"));
ossim_valid($sensor_name, OSS_NULLABLE, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, 'illegal:' . _("Sensor name"));
ossim_valid($latitude, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC, 'illegal:' . _("latitude"));
ossim_valid($longitude, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, OSS_PUNC, 'illegal:' . _("longitude"));

if (!ossim_error()) {
	$sensors = array();
	$num_sens = 0;
	for ($i = 1; $i <= $nsens; $i++) {
		$name = "mboxs" . $i;
		if (POST("$name")) {
			$num_sens++;
			ossim_valid(POST("$name") , OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT);
			if (!ossim_error()) $sensors[] = POST("$name");
		}
	}
}
if (ossim_error() || count($sensors)==0) {
	echo ossim_error();
	if (count($sensors)==0 && !ossim_error()) 
		Util::print_error(_('You Need to select at least one sensor'));
?>
<form method="post" action="newhostform.php">
	<input type="hidden" name="insert" value="<?=$insert?>">
	<input type="hidden" name="hostname" value="<?=$hostname?>">
	<input type="hidden" name="fqdns" value="<?=$fqdns?>">
	<input type="hidden" name="latitude" value="<?=$latitude?>">
	<input type="hidden" name="longitude" value="<?=$longitude?>">
	<input type="hidden" name="ip" value="<?=$ip?>">
	<input type="hidden" name="groupname" value="<?=POST(groupname)?>">
	<input type="hidden" name="id" value="<?=$id?>">
	<input type="hidden" name="threshold_c" value="<?=$threshold_c?>">
	<input type="hidden" name="threshold_a" value="<?=$threshold_a?>">
	<input type="hidden" name="nsens" value="<?=$nsens?>">
	<? foreach($_POST as $k => $v) if (preg_match("/^mboxs/",$k)) { ?>
		<input type="hidden" name="<?=$k?>" value="<?=$v?>">
	<? } ?>
	<input type="hidden" name="asset" value="<?=$asset?>">
	<input type="hidden" name="alert" value="<?=$alert?>">
	<input type="hidden" name="persistence" value="<?=$persistence?>">
	<input type="hidden" name="nat" value="<?=$nat?>">
	<input type="hidden" name="descr" value="<?=$descr?>">
	<input type="hidden" name="os" value="<?=$os?>">
	<input type="hidden" name="mac" value="<?=$mac?>">
	<input type="hidden" name="mac_vendor" value="<?=$mac_vendor?>">
	<input type="hidden" name="nessus" value="<?=$nessus?>">
	<input type="hidden" name="nagios" value="<?=$nagios?>">
	<input type="hidden" name="sensor_name" value="<?=$sensor_name?>">
	<input type="hidden" name="rrd_profile" value="<?=$rrd_profile?>">
	<center><input type="submit" value="<?=_("Back")?>" class="button" style="font-size:12px"></center>
</form>
<?
    die();
}
if (!empty($insert)) {
//$num_sens = 0;
//$sensors = array();
//    for ($i = 1; $i <= $nsens; $i++) {
//        $name = "mboxs" . $i;
//        if (POST("$name")) {
//            $num_sens++;
//            ossim_valid(POST("$name") , OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT);
//            if (ossim_error()) {
//                die(ossim_error());
//            }
//            $sensors[] = POST("$name");
//        }
//    }
	
    if (!isset($sensors)) {
		Util::print_error(_('You Need to select at least one sensor'));
?>
        <p><a href="newhostform.php"><?php echo gettext("Back"); ?> </a></p> 
<?php
        die();
	}
   
    $db = new ossim_db();
    $conn = $db->connect();
	
	if (!Host::in_host($conn, $ip)) 
        Host::insert($conn, $ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude, $fqdns);
    else
		Host::update($conn, $ip, $hostname, $asset, $threshold_c, $threshold_a, $rrd_profile, $alert, $persistence, $nat, $sensors, $descr, $os, $mac, $mac_vendor, $latitude, $longitude, $fqdns);
		
	if (!empty($nessus)) 
		Host_scan::insert($conn, $ip, 3001, 0);
    else 
		Host_scan::delete($conn, $ip, 3001, 0);
    
	if (!empty($nagios))
        if (!Host_scan::in_host_scan($conn, $ip, 2007)) Host_scan::insert($conn, $ip, 2007, "", $hostname, $sensors, $sensors);
    else
        if (Host_scan::in_host_scan($conn, $ip, 2007)) Host_scan::delete($conn, $ip, 2007);
    
    $db->close($conn);
	
    Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)");
}
?>
    <p> <?php echo gettext("Host succesfully inserted"); ?> </p>
    <?if ($_SESSION["menu_sopc"]=="Hosts" || $_SESSION["menu_sopc"]=="Assets") { ?><script>document.location.href="host.php"</script><? } ?>
	
	<?php $OssimWebIndicator->update_display(); ?>

</body>
</html>

