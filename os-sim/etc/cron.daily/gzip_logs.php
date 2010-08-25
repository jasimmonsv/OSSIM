#!/usr/bin/php
<?php
ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
require_once ('ossim_conf.inc');
require_once ('classes/Acl.inc');
$conf = $GLOBALS["CONF"];
$days = $conf->get_conf("backup_day", FALSE);
if ($days < 1 || $days > 999) exit;
$y = strftime("%Y", time() - ((24 * 60 * 60) * $days));
$m = strftime("%m", time() - ((24 * 60 * 60) * $days));
$d = strftime("%d", time() - ((24 * 60 * 60) * $days));
$start = "$y-$m-$d";
$end = date("Y-m-d");
$from_date = "$y$m$d";
$to_date = date("Ymd");

$a = $start;
$b = $end;
$result = "";

$break = 0;
for($i=0;$i<strlen($a) && !$break;$i++){
	if($a[$i] == $b[$i]){
    $result .= $a[$i];
		continue;
	} else {
		$break = 1;
	}
}

if($a[$i-1] == "-"){
	$result = substr($result, 0, $i-1);
}

$result = substr($result, 0, 10);
$date = str_replace("-","/",$result);

/*
$cmd = "perl return_sub_dates_locate.pl \"$start\" \"$end\"";
$return = explode("\n",`$cmd`);
$date = $return[0];
*/

$cmd = "locate.findutils -d /var/ossim/logs/locate.index $date | grep \".log\$\"";
$return = explode("\n",`$cmd`);
$files = array();
foreach ($return as $line) if (trim($line) != "") {
	$fields = explode("/",$line);
	$date = $fields[4].$fields[5].$fields[6];
	if ($date >= $from_date && $date <= $to_date)
		$files[$line]++;
}
foreach ($files as $file=>$val) {
	$cmd = "gzip '$file'";
	//echo $cmd."\n";
	system($cmd);
}
?>
