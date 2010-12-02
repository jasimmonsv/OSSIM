<?php 
/* This function accepts a (sid,cid) and purges it
* from the database
*
* - (sid,cid) : sensor, event id pair to delete
* - db        : database handle
*
* RETURNS: 0 or 1 depending on whether the alert was deleted
*/
function PurgeAlert($sid, $cid, $db, $deltmp, $j, $interval, $f, $acid_event_input) {
    $del_table_list = array(
        "iphdr",
        "tcphdr",
        "udphdr",
        "icmphdr",
        "opt",
        "extra_data",
        "acid_ag_alert",
        "acid_event"
    );
    if ($acid_event_input!="") $del_table_list[]=$acid_event_input;
    $del_cnt = 0;
    $del_str = "";
    if (($GLOBALS['use_referential_integrity'] == 1) && ($GLOBALS['DBtype'] != "mysql")) $del_table_list = array(
        "event"
    );
    fputs($f, "SET AUTOCOMMIT=0;\n");
    for ($k = 0; $k < count($del_table_list); $k++) {
        /* If trying to add to an BASE table append ag_ to the fields */
        if (strstr($del_table_list[$k], "acid_ag") == "") $sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE sid='" . $sid . "' AND cid='" . $cid . "'";
        else $sql2 = "DELETE FROM " . $del_table_list[$k] . " WHERE ag_sid='" . $sid . "' AND ag_cid='" . $cid . "'";
        //$db->baseExecute($sql2);
        if ($sid != "" && $cid != "") fputs($f, "$sql2;\n");
        //if ($db->baseErrorMessage() != "") ErrorMessage(_ERRDELALERT . " " . $del_table_list[$k]);
        if ($db->baseErrorMessage() != "") echo "Errorrrrrrrrrr!!!!!!!!!!";
        else if ($k == 0) $del_cnt = 1;
    }
    fputs($f, PurgeAlert_ac($sid, $cid, $db));
    fputs($f, "COMMIT;\n");
    $perc = round($j * $interval, 0); if ($perc>100) $perc=99;
    $rnd = explode("_", $deltmp);
    fputs($f, "UPDATE deletetmp SET perc=$perc WHERE id=" . $rnd[1] . ";\n");
    //
    return $del_cnt;
}
/* This function accepts a (sid,cid) and purges it
* from the database acumulate tables
*
* - (sid,cid) : sensor, event id pair to delete
* - db        : database handle
*
* RETURNS: sql delete string
*/
function PurgeAlert_ac($sid, $cid, $db) {
    $delsql = "";
    $res = $db->baseExecute("select * from acid_event where sid=$sid and cid=$cid");
    if ($myrow = $res->baseFetchRow()) {
        $day = date("Y-m-d", strtotime($myrow['timestamp']));
        $plugin_id = $myrow['plugin_id'];
        $plugin_sid = $myrow['plugin_sid'];
        $ip_src = $myrow['ip_src'];
        $ip_dst = $myrow['ip_dst'];
        $layer4_sport = $myrow['layer4_sport'];
        $layer4_dport = $myrow['layer4_dport'];
        $ip_proto = $myrow['ip_proto'];
        // test to not delete if does not exist
        if ($plugin_id != "" && $plugin_sid != "" && $ip_src != "" && $ip_dst != "") {
            // AC_SENSOR
            $delsql.= "update ignore ac_sensor_sid set cid=cid-1 WHERE sid=$sid and day='$day' and cid>0;\n";
            $delsql.= "delete from ac_sensor_signature WHERE sid=$sid and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid;\n";
            $delsql.= "delete from ac_sensor_ipsrc WHERE sid=$sid and day='$day' and ip_src=$ip_src;\n";
            $delsql.= "delete from ac_sensor_ipdst WHERE sid=$sid and day='$day' and ip_dst=$ip_dst;\n";
            // AC_ALERTS
            $delsql.= "update ignore ac_alerts_signature set sig_cnt=sig_cnt-1 WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and sig_cnt>0;\n";
            $delsql.= "delete from ac_alerts_sid WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and sid=$sid;\n";
            $delsql.= "delete from ac_alerts_ipsrc WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and ip_src=$ip_src;\n";
            $delsql.= "delete from ac_alerts_ipdst WHERE plugin_id=$plugin_id and plugin_sid=$plugin_sid and day='$day' and ip_dst=$ip_dst;\n";
            // AC_ALERTSCLAS
            //$delsql.= "update ignore ac_alertsclas_classid set cid=cid-1 WHERE sig_class_id=$sig_class_id and day='$day' and cid>0;\n";
            //$delsql.= "delete from ac_alertsclas_sid WHERE sig_class_id=$sig_class_id and day='$day' and sid=$sid;\n";
            //$delsql.= "delete from ac_alertsclas_signature WHERE sig_class_id=$sig_class_id and day='$day' and signature=$signature;\n";
            //$delsql.= "delete from ac_alertsclas_ipsrc WHERE sig_class_id=$sig_class_id and day='$day' and ip_src=$ip_src;\n";
            //$delsql.= "delete from ac_alertsclas_ipdst WHERE sig_class_id=$sig_class_id and day='$day' and ip_dst=$ip_dst;\n";
            // AC_SRCADDR
            $delsql.= "update ignore ac_srcaddr_ipsrc set cid=cid-1 WHERE ip_src=$ip_src and day='$day' and cid>0;\n";
            $delsql.= "delete from ac_srcaddr_sid WHERE ip_src=$ip_src and day='$day' and sid=$sid;\n";
            //$delsql.= "delete from ac_srcaddr_signature WHERE ip_src=$ip_src and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid;\n";
            $delsql.= "delete from ac_srcaddr_ipdst WHERE ip_src=$ip_src and day='$day' and ip_dst=$ip_dst;\n";
            // AC_DSTADDR
            $delsql.= "update ignore ac_dstaddr_ipdst set cid=cid-1 WHERE ip_dst=$ip_dst and day='$day' and cid>0;\n";
            $delsql.= "delete from ac_dstaddr_sid WHERE ip_dst=$ip_dst and day='$day' and sid=$sid;\n";
            //$delsql.= "delete from ac_dstaddr_signature WHERE ip_dst=$ip_dst and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid;\n";
            $delsql.= "delete from ac_dstaddr_ipsrc WHERE ip_dst=$ip_dst and day='$day' and ip_src=$ip_src;\n";
            // AC_LAYER4_SRC
            $delsql.= "update ignore ac_layer4_sport set cid=cid-1 WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and cid>0;\n";
            $delsql.= "delete from ac_layer4_sport_sid WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and sid=$sid;\n";
            $delsql.= "delete from ac_layer4_sport_signature WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid;\n";
            $delsql.= "delete from ac_layer4_sport_ipsrc WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and ip_src=$ip_src;\n";
            $delsql.= "delete from ac_layer4_sport_ipdst WHERE layer4_sport=$layer4_sport and ip_proto=$ip_proto and day='$day' and ip_dst=$ip_dst;\n";
            // AC_LAYER4_DST
            $delsql.= "update ignore ac_layer4_dport set cid=cid-1 WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and cid>0;\n";
            $delsql.= "delete from ac_layer4_dport_sid WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and sid=$sid;\n";
            $delsql.= "delete from ac_layer4_dport_signature WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and plugin_id=$plugin_id and plugin_sid=$plugin_sid;\n";
            $delsql.= "delete from ac_layer4_dport_ipsrc WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and ip_src=$ip_src;\n";
            $delsql.= "delete from ac_layer4_dport_ipdst WHERE layer4_dport=$layer4_dport and ip_proto=$ip_proto and day='$day' and ip_dst=$ip_dst;\n";
        }
    }
    $res->baseFreeRows();
    return $delsql;
}

// ******************************************Background Purge Script**********************************************
include ("base_conf.php");
include ("includes/base_db.inc.php");
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

$acid_event_input = "";
$res1 = $db->baseExecute("SELECT table_name FROM INFORMATION_SCHEMA.tables WHERE table_name='acid_event_input'");
if ($myrow = $res1->baseFetchRow()) {
	$acid_event_input = $myrow["table_name"];
}
$res1->baseFreeRows();

$deltmp = $argv[1];
$listtmp = $argv[2];
$interval = $argv[3];

$rndaux = explode("_", $deltmp);
$rnd = $rndaux[1];

if (!preg_match("/^\/var\/tmp\//",$deltmp) && !preg_match("/^\/tmp\//",$deltmp)) {
	echo "Error: 'file' parameter must be a valid /tmp file\n";
	exit;
}
if (!file_exists($listtmp)) {
	echo "Error: '$listtmp' file does not exist\n";
	exit;
}

$action_cnt = 0;
$dup_cnt = 0;
$j = 0;

$fsidcids = fopen($listtmp,"r");
$f = fopen($deltmp, "w+");
fputs($f, "/* ****************Background Purge Execution*************** */\n");
fputs($f, "CREATE TABLE IF NOT EXISTS `deletetmp` (`id` int(11) NOT NULL,`perc` int(11) NOT NULL, PRIMARY KEY (`id`));\n");
fputs($f, "INSERT INTO deletetmp (id,perc) VALUES ($rnd,1) ON DUPLICATE KEY UPDATE perc=1;\n");
while(!feof($fsidcids)){
	$sidcid = fgets($fsidcids,4096);
	$aux = explode("-",trim($sidcid));
	$sid = $aux[0]; $cid = $aux[1];
	if ($sid != "" && $cid != "") {
		$tmp = PurgeAlert($sid, $cid, $db, $deltmp, $j, $interval, $f, $acid_event_input);
		if ($tmp == 0) {
			++$dup_cnt;
		} else if ($tmp == 1) {
			++$action_cnt;
		}
		$j++;
	}
}
fputs($f, "UPDATE deletetmp SET perc=100 WHERE id=$rnd;\n");
fclose($f);
fclose($fsidcids);
unlink($listtmp);

// POST ACTION
shell_exec("nohup cat $deltmp | /usr/bin/ossim-db snort > /tmp/latest_siem_events_purge.sql.log 2>&1 &");

?>