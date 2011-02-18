<?
ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$days = $conf->get_conf("backup_day", FALSE);
if ($days < 1 || $days > 999) exit;
$y = strftime("%Y", time() - ((24 * 60 * 60) * $days));
$m = strftime("%m", time() - ((24 * 60 * 60) * $days));
$d = strftime("%d", time() - ((24 * 60 * 60) * $days));
$from_date = "$y$m$d";
$config = parse_ini_file("/usr/share/ossim/www/sem/everything.ini");

$cmd = "locate.findutils -d ".$config["locate_db"]." 20 | grep \".log\$\"";
$return = explode("\n",`$cmd`);
$files = array();
foreach ($return as $line) if (trim($line) != "") {
	$fields = explode("/",$line);
	$date = $fields[4].$fields[5].$fields[6];
	if ($date <= $from_date)
		$files[$line]++;
}
foreach ($files as $file=>$val) {
	if (file_exists($file) && !file_exists($file.".gz")) {
		$cmd = "gzip '$file'";
		echo "gzipping $file...\n";
		//system($cmd);
	} else {
		echo "skipping $file (.gz exists)\n";
	}
}
?>
