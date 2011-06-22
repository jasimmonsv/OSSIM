<?php
ini_set("max_execution_time","300"); 

require_once ('classes/Scan.inc');
require_once ('classes/Session.inc');

$scan_name    = GET("scan_name");
$sensor_name  = GET("sensor_name");

ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _("Scan name"));
ossim_valid($sensor_name, OSS_NULLABLE,OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Sensor name"));

if (ossim_error()) {
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$scan_info = explode("_", $scan_name);
$users = Session::get_users_to_assign($dbconn);

$my_users = array();
foreach( $users as $k => $v ) {  $my_users[$v->get_login()]=1;  }

if($my_users[$scan_info[1]]!=1 && !Session::am_i_admin() )  return;

$scan = new TrafficScan();

$file = $scan->get_pcap_file($scan_name,$sesor_name);


if(file_exists($file)) {
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: no-cache'); // no-cache, public
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Content-Description: File Transfer');
    header('Content-Type: application/binary');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: inline; filename='.$scan_name);
    readfile($file);
}
// Clean temp files 
if (file_exists($file)) unlink($file);

$db->close($dbconn);

?>