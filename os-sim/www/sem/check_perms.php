<?
session_start();
ini_set("include_path", ".:/usr/share/ossim/include:/usr/share/phpgacl");
include ("gacl.class.php");

require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];

/* include default $gacl_options */
//require_once ("$phpgacl_path/admin/gacl_admin.inc.php");
$ACL_OPTIONS = array(
    /*
    'debug'                     => $gacl_options['debug'],
    'items_per_page'            => $gacl_options['items_per_page'],
    'max_select_box_items'      => $gacl_options['max_select_box_items'],
    'max_search_return_items'   => $gacl_options['max_search_return_items'],
    */
    'db_type' => $conf->get_conf("ossim_type"),
    'db_host' => $conf->get_conf("ossim_host"),
    'db_port' => $conf->get_conf("ossim_port"),
    'db_user' => $conf->get_conf("ossim_user"),
    'db_password' => $conf->get_conf("ossim_pass"),
    'db_name' => "ossim_acl"
    /*
    'db_type' => 'mysql' ,
    'db_host' => 'localhost' ,
    'db_user' => 'root' ,
    'db_password' => 'ossim' ,
    'db_name' => 'ossim_acl' ,
    'db_table_prefix'           => $gacl_options['db_table_prefix'],
    'caching'                   => $gacl_options['caching'],
    'force_cache_expire'        => $gacl_options['force_cache_expire'],
    'cache_dir'                 => $gacl_options['cache_dir'],
    'cache_expire_time'         => $gacl_options['cache_expire_time']
    */
);

$user = $argv[1];
if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) {
	require_once ('classes/Session.inc');
	$allowedSensors = Session::allowedSensors($user);
} else {
	$gacl = new gacl($ACL_OPTIONS);
	$allowedSensors = $gacl->acl_return_value("DomainAccess", "Sensors", "users", $user);
}

$sensors = array();
if ($allowedSensors != "") {
	$sensors_aux = explode (",",str_replace(",,",",",$allowedSensors));
	foreach ($sensors_aux as $s) if ($s != "") {
		$sensors[$s]++;
	}
}

$f = fopen('php://stdin','r');
while (!feof($f)) {
	$line = fgets($f);
	if (count($sensors) > 0) {
		$campos = explode ("/",$line);
		if ($sensors[$campos[8]]>0)
			echo $line;
	}
	else echo $line;
}
fclose($f);
?>
