<?php
require_once ('classes/Security.inc');
require_once ("classes/Session.inc");

$job_id = GET("job_id");
ossim_valid($job_id, OSS_DIGIT, 'illegal:' . _("job id"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE); 

// check username

$user = "";
$user_name_filter = "";

if(!Session::am_i_admin()) {
    if(!preg_match("/pro/i",$version)){
        $user = Session::get_session_user();
    }
    else {
        $entities_and_users = array();
        $entities_and_users = Acl::get_user_entities();
        $entities_and_users[] = Session::get_session_user(); // add current user
        $users_pro_admin = Acl::get_my_users($dbconn, Session::get_session_user());
        foreach ($users_pro_admin as $us) {
            $entities_and_users[] = $us["login"];
        }
        $user = implode("', '",$entities_and_users); 
    }
}

if($user!="") $user_name_filter = "and username in ('$user')";

$result = $dbconn->Execute("select name, scan_PID from vuln_jobs where id=$job_id $user_name_filter");

$name = "";
$name = $result->fields["name"];

$scan_PID = "";
$scan_PID = $result->fields["scan_PID"];

if($name!="") {
 
    $dest = $GLOBALS["CONF"]->db_conf["nessus_rpt_path"]."/tmp/nessus_s".$scan_PID.".out";
    $file_name = "results_".$name;
    
    $file_name = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "_", $file_name);

    header("Content-type: application/unknown");
    header('Content-Disposition: attachment; filename='.$file_name.'.nbe');
   
    readfile($dest);
}
else {
    echo _("You don't have permission to see these results");
}

$dbconn->disconnect();
?>