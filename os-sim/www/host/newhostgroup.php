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

Session::logcheck("MenuPolicy", "PolicyHosts");
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
echo gettext("New host group"); ?> </h1>

<?php
require_once 'classes/Security.inc';
$descr = POST('descr');
$host_group_name = POST('name');
$threshold_a = POST('threshold_a');
$threshold_c = POST('threshold_c');
$rrd_profile = POST('rrd_profile');
$hhosts = POST('hhosts');
$nsens = POST('nsens');
ossim_valid($host_group_name, OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SPACE, 'illegal:' . _("Host name"));
ossim_valid($threshold_a, OSS_DIGIT, 'illegal:' . _("threshold_a"));
ossim_valid($threshold_c, OSS_DIGIT, 'illegal:' . _("threshold_c"));
ossim_valid($hhosts, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("hhosts"));
ossim_valid($rrd_profile, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Host name"));
ossim_valid($descr, OSS_ALPHA, OSS_NULLABLE, OSS_SPACE, OSS_PUNC, OSS_AT, 'illegal:' . _("Description"));
ossim_valid($nsens, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("nsens"));
if (ossim_error()) {
	echo ossim_error();
	Util::make_form($_POST,"newhostgroupform.php");
	die();
    //die(ossim_error());
}
if (POST('insert')) {
    $num_sens = 0;
    for ($i = 1; $i <= $nsens; $i++) {
        $name = "sboxs" . $i;
        if (POST("$name")) {
            $num_sens++;
            ossim_valid(POST("$name") , OSS_ALPHA, OSS_SCORE, OSS_PUNC, OSS_AT);
            if (ossim_error()) {
				echo ossim_error();
				Util::make_form($_POST,"newhostgroupform.php");
				die();
				//die(ossim_error());
            }
            $sensors[] = POST("$name");
        }
    }
    if (!isset($sensors)) {
        Util::print_error("You Need to select at least one sensor");
        Util::make_form($_POST,"newhostgroupform.php");
        die();
    }
    $hosts = array();
    $ips = POST('ips');
	
	if (!is_array($ips) || empty($ips)) {
        Util::print_error("You Need to select at least one Host");
        Util::make_form($_POST,"newhostgroupform.php");
        die();
    }	
	
	foreach($ips as $name) {
		ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, OSS_SPACE, 'illegal:' . _("$name"));
		if (ossim_error()) {
				echo ossim_error();
				Util::make_form($_POST,"newhostgroupform.php");
				die();
				//die(ossim_error());
		}
		if (!empty($name) && !in_array($name, $hosts)) 
		  $hosts[] = $name;
	}
	
	
	  
	require_once 'ossim_db.inc';
    require_once 'classes/Host.inc';
    require_once 'classes/Host_group.inc';
    require_once 'classes/Host_group_scan.inc';
    $db = new ossim_db();
    $conn = $db->connect();
    Host_group::insert($conn, $host_group_name, $threshold_c, $threshold_a, $rrd_profile, $sensors, $hosts, $descr);
    if (POST('nessus')) {
        Host_group_scan::insert($conn, $host_group_name, 3001, 0);
    } else Host_group_scan::delete($conn, $host_group_name, 3001, 0);
    if (POST('nagios')) {
        if (!Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) Host_group_scan::insert($conn, $host_group_name, 2007, 0, $hosts, $sensors);
    } else {
        if (Host_group_scan::in_host_group_scan($conn, $host_group_name, 2007)) Host_group_scan::delete($conn, $host_group_name, 2007);
    }
    $db->close($conn);
    Util::clean_json_cache_files("(policy|vulnmeter|hostgroup)");
}
?>
    <p> <?php
echo gettext("Host group succesfully inserted"); ?> </p>
    <? if ($_SESSION["menu_sopc"]=="Host groups") { ?><script>document.location.href="hostgroup.php"</script><? } ?>

</body>
</html>

