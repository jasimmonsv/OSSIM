<?
include ("classes/Security.inc");
include("nfsen_functions.php");
include("../nfsen/conf.php");
$name = GET('name');
$ip = GET('ip');
$port = GET('port');
$color = "#".GET('color');
$type = GET('type');
$delete = GET('delete');
$status = GET('status');
$restart = GET('restart');
ossim_valid($name, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, OSS_PUNC, OSS_SCORE, 'illegal:' . _("name"));
$name = str_replace(" ","_",$name);
$name = str_replace(".","_",$name);
if ($delete == "" && $status == "" && $restart == "") {
	ossim_valid($port, OSS_DIGIT, 'illegal:' . _("port"));
	ossim_valid($color, OSS_DIGIT, OSS_ALPHA, "#", 'illegal:' . _("color"));
	ossim_valid($type, OSS_ALPHA, 'illegal:' . _("type"));
}
ossim_valid($delete, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("delete"));
ossim_valid($status, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("status"));
ossim_valid($restart, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("restart"));
if (ossim_error()) {
	echo _("You must fill all inputs");
} else {
	$nfsen_sensors = get_nfsen_sensors();
	if ($delete) {
		if ($nfsen_sensors[$name] != "") {
			unset($nfsen_sensors[$name]);
			set_nfsen_sensors($nfsen_sensors);
			nfsen_reset($nfsen_dir);
			echo str_replace("IP",$ip,_("IP now is not configured as a Flow collector"));
		}
	} elseif ($status) {
		is_running($name);
	} elseif ($restart) {
		nfsen_start();
	} else {
		$nfsen_sensors[$name]['port'] = $port;
		$nfsen_sensors[$name]['color'] = $color;
		$nfsen_sensors[$name]['type'] = $type;
		set_nfsen_sensors($nfsen_sensors);
		nfsen_reset();
		echo str_replace("IP",$ip,str_replace("PORT",$port,_("You should now configure your Flows generator to send Flows to IP port PORT")));
	}
}
?>