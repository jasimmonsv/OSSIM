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
$log_line = base64_decode($_GET["log"]);
$start = $_GET["start"];
$end = $_GET["end"];
$logfile = $_GET["logfile"];

ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($logfile, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SLASH, '[^\.]\.[^\.]', 'illegal:' . _("logfile"));
if (ossim_error()) {
    die(ossim_error());
}

if ($logfile != "") {
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
            //error_log("$found_str\n", 3, "/tmp/validate");
            $validate_file = $result;
            break;
        }
    }
}
$verified = 0;
if ($validate_file != "" && file_exists($validate_file)) {
    if (file_exists($validate_file . ".sig")) {
        $signature = file_get_contents($validate_file . ".sig");
        $sig_dec = base64_decode($signature);
        $f = fopen("/tmp/sig_decoded", "wb");
        fwrite($f, $sig_dec);
        fclose($f);
        //$pub_key = openssl_pkey_get_public($config["pubkey"]);
        //$verified = openssl_verify( $data, $sig_dec, $pub_key);
        $cmdv = "openssl dgst -sha1 -verify /var/ossim/keys/rsapub.pem -signature /tmp/sig_decoded '" . $validate_file . "'";
        //error_log("$cmdv\n", 3, "/tmp/validate");
        $status = exec($cmdv, $res);
        $verified = (preg_match("/Verified OK/i", $status)) ? 1 : 0;
    } else {
        print _("Signature file not found. If the event is less than one hour old it will not be generated yet.");
        exit;
    }
} else {
    print _("Logline not found in any logfiles");
    exit;
}
if ($verified == 1) {
    $verification_str = _("Verification")." <font color=\"green\">"._("OK")."</font><br/>";
} else if ($verified == 0) {
    $verification_str = _("Verification failed");
} else {
    $verification_str = _("Verification failed")." ";
    $verification_str.= openssl_error_string();
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
