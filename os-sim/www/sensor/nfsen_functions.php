<?
function get_nfsen_sensors() {
	include("../nfsen/conf.php");
	
	$lines = file($nfsen_conf);
	$sensors = array();
	foreach ($lines as $line) {
		if (preg_match("/\s*^\#/",$line)) continue;
		if (preg_match("/'([^']+)'\s+\=\>\s*\{\s*'port'\s+\=\>\s+'(\d+)',\s+'col'\s+=>\s+'(\#......)',\s+'type'\s+=>\s+'([^']+)'/",$line,$found)) {
			$sensors[$found[1]]['port'] = $found[2];
			$sensors[$found[1]]['color'] = $found[3];
			$sensors[$found[1]]['type'] = $found[4];
		}
	}
	return $sensors;
}
function set_nfsen_sensors($sensors) {
	include("../nfsen/conf.php");
	
	$lines = file($nfsen_conf);
	$newlines = array();
	$insources = false;
	foreach ($lines as $line) {
		if (preg_match("/\s*^\#/",$line)) { $newlines[] = $line; continue; }
		if (!$insources && preg_match("/\%sources \= \(/",$line)) {
			$newlines[] = $line;
			$insources = true;
		} elseif ($insources && preg_match("/\)\;/",$line)) {
			$coma = "";
			foreach ($sensors as $sensor=>$data) {
				$newlines[] = "$coma    '".$sensor."'    => { 'port' => '".$data['port']."', 'col' => '".$data['color']."', 'type' => '".$data['type']."' }\n";
				$coma = ",";
			}
			$insources = false;
			$newlines[] = ");\n";
		} elseif (!$insources) {
			$newlines[] = $line;
		}
	}
	
	$f = fopen($nfsen_conf,"w");
	foreach ($newlines as $line) fputs($f,$line);
	fclose($f);
}
function is_running($name) {
	include("../nfsen/conf.php");

	$cmd = "sudo $nfsen_bin status";
	$fp = popen("$cmd 2>>/dev/null", "r");
	while (!feof($fp)) {
		$line = trim(fgets($fp));
		if (preg_match("/'$name'/",$line)) echo $line;
	}
	fclose($fp);
}
function nfsen_start() {
	include("../nfsen/conf.php");
	$cmd = "sudo $nfsen_bin stop";
	system($cmd);
	$cmd = "sudo $nfsen_bin start";
	$fp = popen("$cmd 2>>/dev/null", "r");
	while (!feof($fp)) {
		$line = trim(fgets($fp));
		echo $line;
	}
	fclose($fp);
}
function nfsen_reset() {
	include("../nfsen/conf.php");

        $cmd = "echo y | sudo $nfsen_bin reconfig > /tmp/nfsen.log 2>&1";
	system($cmd);
}
function get_nfsen_baseport($sensors) {
	$ports = array();
	$base_port = 12000;
	foreach ($sensors as $sensor=>$data) {
		$ports[$data['port']]++;
	}
	ksort($ports);
	foreach($ports as $port=>$val) {
		if ($port == $base_port) $base_port++;
	}
	return $base_port;
}
?>
