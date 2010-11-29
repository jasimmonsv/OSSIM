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
if ($argv[2] != "") {
	$path_class = '/usr/share/ossim/include/:/usr/share/ossim/www/sem';
	ini_set('include_path', $path_class);
}
require_once 'classes/Session.inc';
require_once 'classes/Security.inc';
Session::logcheck("MenuEvents", "ControlPanelSEM");
?>

<html>
<head>
<link rel="stylesheet" href="../style/style.css"/>
</head>
<body>
<?php
$config = parse_ini_file("everything.ini");
$cache_dir = $config["cache_dir"];
$locate_db = $config["locate_db"];

if ($argv[1] != "") {
	$signature = $argv[1];
	$log_line = $argv[2];
	$start = $argv[3];
	$end = $argv[4];
	$logfile = $argv[5];
	$server = "127.0.0.1";
} else {
	$signature = POST("signature");
	$log_line = base64_decode(POST("log"));
	$start = POST("start");
	$end = POST("end");
	$logfile = POST("logfile");
	$server = POST("server");
}
ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($logfile, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SLASH, '[^\.]\.[^\.]', 'illegal:' . _("logfile"));
ossim_valid($server, OSS_DIGIT, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _("server"));
if (ossim_error()) {
    die(ossim_error());
}

if ($server != "127.0.0.1") {
	$cmd = "sudo ./fetchremote_validate.pl \"$signature\" \"$log_line\" \"$start\" \"$end\" \"$logfile\" $server";
	echo $cmd;
	exit;
}

if ($logfile != "" && preg_match("/\//",$logfile)) {
    $found_str = _("Found in log file")." '$logfile'";
    $validate_file = $logfile;
} else {
    $status = exec("perl return_sub_dates_locate.pl '$start' '$end'", $results);
    $common_date = $results[0];
    $results = array();
    $cmd = "locate.findutils -d $locate_db $common_date | grep \".log\$\" | sort -r";
    //error_log("$cmd\n", 3, "/tmp/validate");
    $status = exec($cmd, $results);
    $log_line = escapeshellarg($log_line);
    $validate_file = "";
    $data = "";
    foreach($results as $result) {
        $res = array();
        //error_log("$result\n", 3, "/tmp/validate");
        $status = exec("grep -F -m 1 $log_line $result", $res);
        $data = $res[0];
        print $res[0];
        if (preg_match("/\s+(\d+)\s+(.*$)/", $res[0], $matches)) {
            $found_str = _("Found in")." $result "._("at line number")." " . $matches[1];
            $validate_file = $result;
            break;
        }
    }
}
$verified = 0;
//print_r($found_str.$validate_file);
if ($validate_file != "" && file_exists($validate_file)) {

    // signature in string
    if ($signature!="") {
    	$sig_dec = base64_decode($signature);
        $pub_key = openssl_get_publickey($config["pubkey"]);
        $verified = openssl_verify( $log_line, $sig_dec, $pub_key);
        //error_log("$log_line\n$signature\n", 3, "/tmp/validate");

	// signature en filename.sig
    } elseif (file_exists($validate_file . ".sig")) {
        $signature = file_get_contents($validate_file . ".sig");
        $sig_dec = base64_decode($signature);
        $f = fopen("/tmp/sig_decoded", "wb");
        fwrite($f, $sig_dec);
        fclose($f);
        //$pub_key = openssl_pkey_get_public($config["pubkey"]);
        //$verified = openssl_verify( $data, $sig_dec, $pub_key);
        $cmdv = "openssl dgst -sha1 -verify ".trim(str_replace("file://","",$config["pubkey"]))." -signature /tmp/sig_decoded '" . $validate_file . "'";
        //error_log("$cmdv\n", 3, "/tmp/validate");
        $status = exec($cmdv, $res);
        $verified = (preg_match("/Verified OK/i", $status)) ? 1 : 0;

    } else {
        print str_replace("SIGFILE","<b>$validate_file.sig</b>",_("Signature file SIGFILE not found.<br>If the event is less than one hour old it will not be generated yet."));
        exit;
    }
} else {
    print _("Logline not found in any logfiles");
    exit;
}
if ($verified == 1) {
    $verification_str = _("Verification")." <font color=\"green\"><b>"._("OK")."</b></font><br/>";
} else if ($verified == 0) {
    $verification_str = _("Verification")." <font color=\"red\"><b>"._("Failed")."</b></font>";
} else {
    $verification_str = _("Verification")." <font color=\"red\"><b>"._("Failed")."</b></font>"." ";
    $verification_str.= openssl_error_string();
}
?>
<center>
<table border="0">
<th colspan="2"><center> <?=_("Log verification results")?> </center></th>
<?php
print "<tr><td class='noborder'><b>"._("Logline")."</b>:</td><td class='noborder'> $log_line</td></tr>";
print "<tr><td class='noborder' colspan=\"2\"><hr></tr></td>";
print "<tr><td class='noborder' colspan=\"2\" nowrap>$found_str</td></tr>";
print "<tr><td class='noborder' colspan=\"2\">$verification_str</td></tr>";
?>
</table>
</center>
</body>
</html>
