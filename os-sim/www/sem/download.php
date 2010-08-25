<?php
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");

$user = $_SESSION["_user"];
$start = $_GET["start"];
$end = $_GET["end"];
$sort_order = $_GET["sort"];
$a = $_GET["query"];

ossim_valid($start, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("start date"));
ossim_valid($end, OSS_DIGIT, OSS_COLON, OSS_SCORE, OSS_SPACE, 'illegal:' . _("end date"));
ossim_valid($sort_order, OSS_LETTER, 'illegal:' . _("sort order"));
ossim_valid($a, OSS_SCORE, OSS_NULLABLE, OSS_SPACE, OSS_TEXT, ':', 'illegal:' . _("query"));

if (ossim_error()) {
    die(ossim_error());
}
if (preg_match("/(.*plugin_id!=)(\S+)(.*)/", $a, $matches) || preg_match("/(.*plugin_id=)(\S+)(.*)/", $a, $matches)) {
    $plugin_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
    $query = "select id from plugin where name like '" . $plugin_name . "%' order by id";
    if (!$rs = & $conn->Execute($query)) {
        print $conn->ErrorMsg();
        exit();
    }
    if ($plugin_id = $rs->fields["id"] != "") {
        $plugin_id = $rs->fields["id"];
    } else {
        $plugin_id = $matches[2];
    }
    $a = $matches[1] . $plugin_id . $matches[3];
}
if (preg_match("/(.*sensor!=)(\S+)(.*)/", $a, $matches) || preg_match("/(.*sensor=)(\S+)(.*)/", $a, $matches)) {
    $plugin_name = str_replace('\\\\','\\',str_replace('\\"','"',$matches[2]));
    $query = "select ip from sensor where name like '" . $plugin_name . "%'";
    if (!$rs = & $conn->Execute($query)) {
        print $conn->ErrorMsg();
        exit();
    }
    if ($plugin_id = $rs->fields["ip"] != "") {
        $plugin_id = $rs->fields["ip"];
    } else {
        $plugin_id = $matches[2];
    }
    $a = $matches[1] . $plugin_id . $matches[3];
}

$org = "/var/ossim/logs/searches/$user"."_"."$start"."_"."$end"."_"."$sort_order"."_"."$a/";
$dest = $user."_".$start."_".$end."_".$sort_order."_".$a.".zip";

$org = str_replace("'", "\'", $org);
$dest = str_replace("'", "\'", $dest);
$file = "/tmp/".$dest;

$cmd = "cd '$org';zip -r '$file' . > /dev/null";
//print_r($cmd);
system($cmd);

$dest = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "", $dest);

header("Content-type: application/zip");
header('Content-Disposition: attachment; filename='.$dest);

readfile($file);
unlink($file);

?>