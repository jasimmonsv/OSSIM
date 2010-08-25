<?
require_once ('classes/Security.inc');
$id = GET('id');
ossim_valid($id, OSS_DIGIT, 'illegal:' . _("id"));
if (ossim_error()) {
    die(ossim_error());
}
include ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$qs = new QueryState();
$sql = "SELECT count(sig.plugin_sid) FROM reference r,reference_system s,sig_reference sig WHERE r.ref_system_id=s.ref_system_id AND r.ref_id=sig.ref_id AND r.ref_system_id=$id";
$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
if ($myrow = $result->baseFetchRow()) {
	echo $myrow[0];
} else echo "0";