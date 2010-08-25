<?
require_once ('classes/Session.inc');
require_once 'classes/Plugin_sid.inc';
Session::logcheck("MenuConfiguration", "PluginGroups");

$plugin_id = GET('plugin_id');
$q = addslashes(urldecode(GET('q')));

ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("ID"));
ossim_valid($q, OSS_TEXT, OSS_NULLABLE);

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$conn = $db->connect();
$more = "";
if ($q != "") $more = "AND name like '%$q%'";
$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$plugin_id $more LIMIT 150");
if ($plugin_list[0]->foundrows>150) echo "Total=".$plugin_list[0]->foundrows."\n";
foreach($plugin_list as $plugin) {
    $id = $plugin->get_sid();
    $name = trim($plugin->get_name());
    echo "$id= $id &nbsp; $name\n";
}
$db->close($conn);

?>