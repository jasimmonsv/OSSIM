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
require_once 'classes/Security.inc';
require_once 'classes/Session.inc';
require_once 'classes/Collectors.inc';
require_once 'ossim_db.inc';

Session::logcheck("MenuConfiguration", "ConfigurationPlugins");
$db = new ossim_db();
$conn = $db->connect();

$section = GET('section');
$download = intval(GET('download'));
$idc = GET("idc");

ossim_valid($section, OSS_LETTER, 'illegal:' . _("section"));
ossim_valid($download, OSS_DIGIT, 'illegal:' . _("section"));
ossim_valid($idc, OSS_DIGIT, "illegal:" . _("idc"));

if (ossim_error()) {
   die(ossim_error());
}

$out = array();

// CFG
if ($section=="cfg") {
	$collectors = Collectors::get_list($conn,"WHERE id=$idc");
	$coll = $collectors[0];
	$rules = CollectorRule::get_list($conn,"WHERE idc=$idc ORDER BY name");	
	// DEFAULT CONFIG
	$out[] = ";; ".$coll->get_name()."\n";
	$out[] = ";; plugin_id: ".$coll->get_plugin_id()."\n";
	$out[] = ";; type: ".(($coll->get_type()==1) ? "detector" : ($coll->get_type==2 ? "monitor" : ($coll->get_type==3 ? "scanner" : "data")))."\n";
	$out[] = ";; description: ".str_replace("\n"," ",$coll->get_description())."\n";
	$out[] = ";;\n;;\n[DEFAULT]\nplugin_id=".$coll->get_plugin_id()."\n\n";
	$out[] = "[config]\n";
	$out[] = "type=".(($coll->get_type()==1) ? "detector" : ($coll->get_type==2 ? "monitor" : ($coll->get_type==3 ? "scanner" : "data")))."\n";
	$out[] = "enable=".($coll->get_enable()==1 ? "yes" : "no")."\n";
	$out[] = "source=".$coll->get_source()."\n\n";
	$out[] = "location=".$coll->get_location()."\n\n";
	$out[] = "process=".$coll->get_process()."\n";
	$out[] = "start=".($coll->get_start()==1 ? "yes" : "no")."\n";
	$out[] = "stop=".($coll->get_stop()==1 ? "yes" : "no")."\n";
	$out[] = "startup=".$coll->get_startup_command()."\n";
	$out[] = "shutdown=".$coll->get_stop_command()."\n";
	$out[] = "\n\n ## rules\n\n";
	// RULES
	foreach ($rules as $rule) {
		$out[] = "[".$rule->get_name()."]\n";
		$out[] = "# ".$rule->get_description()."\n";
		$out[] = "event_type=".$rule->get_type()."\n";
		$out[] = "regexp=\"".$rule->get_expression()."\"\n";
		#$prio = $rule->get_prio();
		#$rel = $rule->get_rel();
		#$plugin_sid = $rule->get_plugin_sid();
		if ($rule->get_date()!="") $out[] = "date=".$rule->get_date()."\n";
		if ($rule->get_sensor()!="") $out[] = "sensor=".$rule->get_sensor()."\n";
		if ($rule->get_interface()!="") $out[] = "interface=".$rule->get_interface()."\n";
		if ($rule->get_protocol()!="") $out[] = "protocol=".$rule->get_protocol()."\n";
		if ($rule->get_src_ip()!="") $out[] = "src_ip=".$rule->get_src_ip()."\n";
		if ($rule->get_src_port()!="") $out[] = "src_port=".$rule->get_src_port()."\n";
		if ($rule->get_dst_ip()!="") $out[] = "dst_ip=".$rule->get_dst_ip()."\n";
		if ($rule->get_dst_port()!="") $out[] = "dst_port=".$rule->get_dst_port()."\n";
		if ($rule->get_username()!="") $out[] = "username=".$rule->get_username()."\n";
		if ($rule->get_password()!="") $out[] = "password=".$rule->get_password()."\n";
		if ($rule->get_filename()!="") $out[] = "filename=".$rule->get_filename()."\n";
		if ($rule->get_userdata1()!="") $out[] = "userdata1=".$rule->get_userdata1()."\n";
		if ($rule->get_userdata2()!="") $out[] = "userdata2=".$rule->get_userdata2()."\n";
		if ($rule->get_userdata3()!="") $out[] = "userdata3=".$rule->get_userdata3()."\n";
		if ($rule->get_userdata4()!="") $out[] = "userdata4=".$rule->get_userdata4()."\n";
		if ($rule->get_userdata5()!="") $out[] = "userdata5=".$rule->get_userdata5()."\n";
		if ($rule->get_userdata6()!="") $out[] = "userdata6=".$rule->get_userdata6()."\n";
		if ($rule->get_userdata7()!="") $out[] = "userdata7=".$rule->get_userdata7()."\n";
		if ($rule->get_userdata8()!="") $out[] = "userdata8=".$rule->get_userdata8()."\n";
		if ($rule->get_userdata9()!="") $out[] = "userdata9=".$rule->get_userdata9()."\n";
		$out[] = "\n\n";
	}
}

// SQL
if ($section=="sql") {
	$collectors = Collectors::get_list($conn,"WHERE id=$idc");
	$coll = $collectors[0];
	$rules = CollectorRule::get_list($conn,"WHERE idc=$idc ORDER BY name");	
	// PLUGIN_ID
	$out[] = "-- ".$coll->get_name()."\n";
	$out[] = "-- plugin_id: ".$coll->get_plugin_id()."\n";
	$out[] = "DELETE FROM plugin WHERE id = '".$coll->get_plugin_id()."';\n";
	$out[] = "DELETE FROM plugin_sid where plugin_id = '".$coll->get_plugin_id()."';\n";
	$out[] = "\n";
	$out[] = "INSERT INTO plugin (id, type, name, description) VALUES (".$coll->get_plugin_id().",".$coll->get_type().",'".strtolower($coll->get_name())."','".str_replace("'","\'",str_replace("\n"," ",$coll->get_description()))."');\n";
	$out[] = "\n";
	// PLUGIN_SID => RULES
	foreach ($rules as $rule) {
		$out[] = "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (".$coll->get_plugin_id().", ".$rule->get_plugin_sid().", NULL, NULL, '".$coll->get_name().": ".$rule->get_name()."' ,".$rule->get_prio().", ".$rule->get_rel().");\n";
	}
	$out[] = "\n";
}

$db->close($conn);
if ($download==0) { // view
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
	  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
	</head>
	<body style="height: auto; margin: 0 0 10px 0">
	<pre style='font-size:12px;font-family:courier'><?php
	foreach ($out as $line) echo htmlentities($line);
	?>
	</pre>
	</body>
	</html>
	<?
}
elseif ($download==1) { // download
    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Disposition: attachment; filename='.$coll->get_name().'.'.$section);
    foreach ($out as $line) echo $line;
}
elseif ($download==2) { // validate
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
	  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
	</head>
	<body style="height: auto; margin: 0 0 10px 0">
	<pre style='font-size:12px;font-family:courier'><?php
	$output = "/tmp/".$coll->get_name().".".$section;
	echo _("Generating file").": $output\n";
	file_put_contents($output,$out);
	$testfile = ($coll->get_sample_log()!="") ? $coll->get_sample_log() : "/dev/null";
	echo _("Testing .cfg file")."\n\n";
	passthru("python /usr/share/ossim/scripts/regexp.py '$testfile' '$output' q");
	?>
	</pre>
	</body>
	</html>
	<?
}
elseif ($download==3) { // insert sql
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	  <title> <?php echo gettext("OSSIM Framework"); ?> </title>
	  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	  <link rel="stylesheet" type="text/css" href="../style/style.css"/>
	</head>
	<body style="height: auto; margin: 0 0 10px 0">
	<pre style='font-size:12px;font-family:courier'><?php
	$output = "/tmp/".$coll->get_name().".".$section;
	echo _("Generating file").": $output\n";
	file_put_contents($output,$out);
	echo _("Inserting .sql file")."\n\n";
	passthru("/usr/bin/ossim-db < '$output'");
	echo "\n"._("Done.")."\n";
	?>
	</pre>
	</body>
	</html>
	<?
}
?>