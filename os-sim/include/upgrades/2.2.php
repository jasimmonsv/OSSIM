<?php
require_once 'classes/Upgrade_base.inc';

class upgrade_22 extends upgrade_base
{

    function start_upgrade(){
       print "<br/>";       print "<br/>";
       print "<br/>";
       print _("Due to this upgrade being quite big, your browser might not show the 'end of 
upgrade' message. If you see the browser has stopped loading the page, reload and your system
 should be upgraded.");
       print "<br/>";
       print "<br/>";
       print "<br/>";    
    }

    function end_upgrade()
    {
		require_once ('ossim_db.inc');
		$dbsock = new ossim_db();
		$db = $dbsock->connect();
		$configxml = "/etc/ossim/server/config.xml";
		$name = "Not found";
		
		// Check server name
		if (file_exists($configxml)) {
			$lines = file($configxml);
			foreach ($lines as $line) {
				if (preg_match("/\<server.*name=\"([^\"]+)\"/",$line,$found)) $name = $found[1];
			}
		}
		
		// Search in DB for name
		$sql = "SELECT * FROM server_role WHERE name=\"$name\"";
		if (!$rs = $db->Execute($sql)) {
            print $db->ErrorMsg();
        } elseif (!$rs->EOF) { // Found -> Update
			$correlate = ($rs->fields['correlate']) ? "yes" : "no";
			$cross_correlate = ($rs->fields['cross_correlate']) ? "yes" : "no";
			$store = ($rs->fields['store']) ? "yes" : "no";
			$qualify = ($rs->fields['qualify']) ? "yes" : "no";
			$resend_alarm = ($rs->fields['resend_alarm']) ? "yes" : "no";
			$resend_event = ($rs->fields['resend_event']) ? "yes" : "no";
			$sign = ($rs->fields['sign']) ? "yes" : "no";
			$sem = ($rs->fields['sem']) ? "yes" : "no";
			$sim = ($rs->fields['sim']) ? "yes" : "no";
			$alarms_to_syslog = ($rs->fields['alarms_to_syslog']) ? "yes" : "no";
			require_once 'classes/Config.inc';
			$conf = new Config();
			$conf->update("server_correlate",$correlate);
			$conf->update("server_cross_correlate",$cross_correlate);
			$conf->update("server_store",$store);
			$conf->update("server_qualify",$qualify);
			$conf->update("server_forward_alarm",$resend_alarm);
			$conf->update("server_forward_event",$resend_event);
			$conf->update("server_sign",$sign);
			$conf->update("server_sem",$sem);
			$conf->update("server_sim",$sim);
			$conf->update("server_alarms_to_syslog",$alarms_to_syslog);
		}
	exec("sudo /etc/init.d/ossim-server restart");
		//
        // Reload ACLS
        //
        $this->reload_acls();
        return true;
    }
}
?>
