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
require_once 'classes/Util.inc';
require_once 'classes/Security.inc';
require_once 'ossim_db.inc';
require_once 'classes/Host_group.inc';
require_once 'classes/Host_group_reference.inc';
require_once 'classes/Host_group_scan.inc';
require_once 'classes/NagiosConfigs.inc';
 
Session::logcheck("MenuPolicy", "PolicyHosts");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
</head>
<body>

<h1> <?php echo gettext("Update host group"); ?> </h1>

<?php

$host_group_name = POST('name');
$threshold_a = POST('threshold_a');
$threshold_c = POST('threshold_c');
$hhosts = POST('hhosts');
$rrd_profile = POST('rrd_profile');
$descr = POST('descr');
$nsens = POST('nsens');
ossim_valid($host_group_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Host name"));
ossim_valid($threshold_a, OSS_DIGIT, 'illegal:' . _("threshold_a"));
ossim_valid($threshold_c, OSS_DIGIT, 'illegal:' . _("threshold_c"));
ossim_valid($hhosts, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("hhosts"));
ossim_valid($rrd_profile, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Host name"));
ossim_valid($descr, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, 'illegal:' . _("Description"));
ossim_valid($nsens, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("nsens"));

if (ossim_error()) {
    die(ossim_error());
}
if (POST('insert')) {
    $sensors = array();
    $num_sens = 0;
    for ($i = 1; $i <= $nsens; $i++) {
        $name = "sboxs" . $i;
        if (POST("$name")) {
            $num_sens++;
            ossim_valid(POST("$name") , OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT);
            if (ossim_error()) {
                die(ossim_error());
            }
            $sensors[] = POST("$name");
        }
    }
    
	if (count($sensors) == 0) {
		Util::print_error(_("You Need to select at least one sensor")); 
		Util::make_form($_POST,"newhostgroupform.php");
        die();
    }
    $allhosts = array();
    /*$hosts = array();
    for ($i = 1; $i <= $hhosts; $i++) {
    $name = "mboxs" . $i;
    
    ossim_valid(POST("$name"), OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, OSS_SPACE, 'illegal:'._("$name"));
    
    if (ossim_error()) {
    die(ossim_error());
    }
    
    $name_aux = POST("$name");
    
    if (!empty($name_aux))
    $hosts[] = POST("$name");
    }*/
    //    echo implode($allhosts,",");
	
    $hosts = array();
    $ips = POST('ips');
	
	if (!is_array($ips) || empty($ips)) {
        Util::print_error(_("You Need to select at least one Host"));
        Util::make_form($_POST,"newhostgroupform.php");
        die();
    }
	
	foreach($ips as $name) {
		ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, OSS_SPACE, 'illegal:' . _("$name"));
		if (ossim_error()) {
			die(ossim_error());
		}
		if (!empty($name) && !in_array($name, $hosts)) 
		  $hosts[] = $name;
	}
		
   
    $db = new ossim_db();
    $conn = $db->connect();
    if (POST('nessus')) {
        Host_group_scan::delete($conn, $host_group_name, 3001, 0);
        Host_group_scan::insert($conn, $host_group_name, 3001, 0);
    } 
	else 
		Host_group_scan::delete($conn, $host_group_name, 3001, 0);
    
	if (Host_group_scan::in_host_group_scan($conn, $ip, 2007)) 
	{
        $hosts_list = Host_group_reference::get_list($conn, $host_group_name, "2007");
        
		foreach($hosts_list as $host) 
			$hostip[] = $host->get_host_ip();
        
		foreach($hostip as $host)
		{
            $flag = false;
            foreach($hosts as $h) if (strcmp($h, $host) == 0) {
                $flag = true;
                break;
            }
            if ($flag == false) {
                if (Host_group_scan::can_delete_host_from_nagios($conn, $host, $host_group_name)) {
                    require_once 'classes/NagiosConfigs.inc';
                    $q = new NagiosAdm();
                    $q->delHost(new NagiosHost($host, $host, ""));
                    $q->close();
                }
            }
        }
    }
    if (POST('nagios'))
	{
        if (Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) Host_group_scan::delete($conn, $host_group_name, 2007);
        Host_group_scan::insert($conn, $host_group_name, 2007);
       
        $q = new NagiosAdm();
        $q->addNagiosHostGroup(new NagiosHostGroup($host_group_name, $hosts, $sensors),$conn);
        $q->close();
    } 
	else
	{
        if (Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) Host_group_scan::delete($conn, $host_group_name, 2007);
    }
    
	Host_group::update($conn, $host_group_name, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr);
    $db->close($conn);
}
?>
    <p><?php echo gettext("Host group succesfully updated"); ?></p>
    <script>document.location.href="hostgroup.php"</script>

	</body>
</html>

