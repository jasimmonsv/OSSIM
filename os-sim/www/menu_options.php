<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
require_once ('ossim_conf.inc');

$conf = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$opensource = (!preg_match("/pro|demo/i",$version)) ? true : false;
$prodemo = (preg_match("/demo/i",$version)) ? true : false;
require_once 'classes/Upgrade.inc';
$upgrade = new Upgrade();
if (Session::am_i_admin() && $upgrade->needs_upgrade()) {
    $menu["Upgrade"][] = array(
        "name" => gettext("System Upgrade Needed") ,
        "id" => "Upgrade",
        "url" => "upgrade/index.php"
    );
    $hmenu["Upgrade"][] = array(
        "name" => gettext("Software Upgrade") ,
        "id" => "Upgrade",
        "url" => "upgrade/"
    );
    $hmenu["Upgrade"][] = array(
        "name" => gettext("Update Notification") ,
        "id" => "Updates",
        "url" => "updates/index.php"
    );
    $GLOBALS['ossim_last_error'] = false;
}
/* Dashboards */
$dashboards = 0;
if (Session::menu_perms("MenuControlPanel", "ControlPanelExecutive")) { $dashboards = 1; $menu["Dashboards"][] = array(
    "name" => gettext("Dashboards") ,
    "id" => "Executive Panel",
    "url" => "panel/"
);
}
if (Session::menu_perms("MenuControlPanel", "BusinessProcesses") || Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) { $dashboards = 1;
    $menu["Dashboards"][] = array(
      "name" => gettext("Risk") ,
      "id" => "Risk",
      "url" => "risk_maps/riskmaps.php?view=1"
    );
    if (Session::menu_perms("MenuControlPanel", "BusinessProcesses")) {
        $hmenu["Risk"][] = array(
          "name" => gettext("Risk Maps"),
          "id" => "Risk",
          "target" => "main",
          "url" => "risk_maps/riskmaps.php?view=1",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboards:risk:risk_maps','DashboardHelp');"
        );
        //$rmenu["Risk"][] = array(
        //  "name" => gettext("View Maps"),
        //  "target" => "main",
        //  "url" => "../risk_maps/riskmaps.php?view=1"
        //);
        $rmenu["Risk"][] = array(
          "name" => gettext("Set Indicators"),
          "target" => "main",
          "url" => "../risk_maps/riskmaps.php"
        );
        $rmenu["Risk"][] = array(
          "name" => gettext("Manage maps"),
          "target" => "main",
          "url" => "../risk_maps/riskmaps.php?view=2"
        );
    }
    if (Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")) {
        $hmenu["Risk"][] = array(
            "name" => gettext("Risk Metrics") ,
            "id" => "Metrics",
            "target" => "main",
            "url" => "control_panel/global_score.php?range=day",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:dashboards:risk:risk_metrics','DashboardHelp');"
        );
    }
}

/*
if (Session::menu_perms("MenuControlPanel", "BusinessProcesses")) if (!file_exists($version_file)) {
    $dashboards = 1;
    $menu["Dashboards"][] = array(
        "name" => gettext("Business Processes") ,
        "id" => "Business Processes",
        "url" => "business_processes/index.php"
    );
}
*/
/* if (Session::menu_perms("MenuControlPanel", "Help")) */
        /*if ($dashboards) $menu["Dashboards"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:control_panel','Dashboard Help');"
);*/
/* Incidents */
$incidents = 0;
if (Session::menu_perms("MenuIncidents", "ControlPanelAlarms")) { $incidents = 1;
    $menu["Incidents"][] = array(
        "name" => gettext("Alarms") ,
        "id" => "Alarms",
        "url" => "control_panel/alarm_console.php?&hide_closed=1"
        //"url" => "control_panel/alarm_group_console.php"
        
    );
    $hmenu["Alarms"][] = array(
        "name" => gettext("Alarms") ,
        "id" => "Alarms",
        "target" => "main",
        "url" => "control_panel/alarm_console.php?hide_closed=1",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:alarms:alarms','Help');"
    );
    if (Session::menu_perms("MenuIncidents", "ReportsAlarmReport")) $hmenu["Alarms"][] = array(
        "name" => gettext("Report") ,
        "id" => "Report",
        "url" => "report/sec_report.php?section=all&type=alarm",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:alarms:report','Help');"
    );
    $rmenu["Alarms"][] = array(
          "name" => gettext("Edit labels"),
          "target" => "main",
          "url" => "tags_edit.php"
        );
}
if (Session::menu_perms("MenuIncidents", "IncidentsIncidents")) { $incidents = 1;
    $menu["Incidents"][] = array(
        "name" => gettext("Tickets") ,
        "id" => "Tickets",
        "url" => "incidents/index.php?status=$status"
    );
    $hmenu["Tickets"][] = array(
        "name" => gettext("Tickets") ,
        "id" => "Tickets",
        "url" => "incidents/index.php?status=$status",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:tickets','Help');"
    );
}
if (Session::menu_perms("MenuIncidents", "IncidentsTypes")) { $incidents = 1; $rmenu["Tickets"][] = array(
    "name" => gettext("Types") ,
    "id" => "Types",
    "url" => "../incidents/incidenttype.php"
);
}
if (Session::menu_perms("MenuIncidents", "IncidentsTags")) { $incidents = 1; $rmenu["Tickets"][] = array(
    "name" => gettext("Tags") ,
    "id" => "Tags",
    "url" => "../incidents/incidenttag.php"
);
}
if (Session::menu_perms("MenuIncidents", "IncidentsReport")) { $incidents = 1; $hmenu["Tickets"][] = array(
    "name" => gettext("Report") ,
    "id" => "Report",
    "url" => "report/incidentreport.php",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:report','Help');"
);
}
if (Session::menu_perms("MenuIncidents", "ConfigurationEmailTemplate")) { $incidents = 1;
    $rmenu["Tickets"][] = array(
        "name" => gettext("Email Template") ,
        "id" => "Incidents Email Template",
        "url" => "../conf/emailtemplate.php"
    );
}

if (Session::menu_perms("MenuIncidents", "Osvdb")) { // if (file_exists($version_file)) {
    $incidents = 1;
	$menu["Incidents"][] = array(
        "name" => gettext("Knowledge DB") ,
        "id" => "Repository",
        "url" => "repository/index.php"
    );
    $hmenu["Repository"][] = array(
        "name" => gettext("Knowledge DB") ,
        "id" => "Repository",
        "url" => "repository/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents:knowledge_db','Help');"
    );
}
/* if ($incidents) $menu["Incidents"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:incidents','Help');"
); */
/* Events */
$events = 0;
if (Session::menu_perms("MenuEvents", "EventsForensics")) { $events = 1;
	$tmp_month = date("m");
	$tmp_day = date("d");
	$tmp_year = date("Y");
	$today = '&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B2%5D=' . $tmp_month . '&time%5B0%5D%5B3%5D=' . $tmp_day . '&time%5B0%5D%5B4%5D=' . $tmp_year . '&time%5B0%5D%5B5%5D=&time%5B0%5D%5B6%5D=&time%5B0%5D%5B7%5D=&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=+&time_range=today';
    $menu["Analysis"][] = array(
        "name" => gettext("SIEM") ,
        "id" => "Forensics",
        //"url" => $conf->get_conf("acid_link", FALSE) . "/" . $conf->get_conf("event_viewer", FALSE) . "_qry_main.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d"
        "url" => "forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d".$today
    );
    $hmenu["Forensics"][] = array(
        "name" => gettext("SIEM") ,
        "id" => "Forensics",
        //"url" => $conf->get_conf("acid_link", FALSE) . "/" . $conf->get_conf("event_viewer", FALSE) . "_qry_main.php?&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d"
        "url" => "forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:SIEM','EventHelp')"
    );
}
/*
if (Session::menu_perms("MenuEvents", "EventsViewer")) { $events = 1; $hmenu["Forensics"][] = array(
    "name" => gettext("Custom") ,
    "id" => "Events Viewer",
    "url" => "event_viewer/index.php",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:event_viewer','EventHelp')"
);
}*/
if (Session::menu_perms("MenuEvents", "ReportsWireless")) { $events = 1;
    $hmenu["Forensics"][] = array(
       "name" => gettext("Wireless") ,
       "id" => "Wireless",
       "url" => "wireless/",
       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:wireless','EventHelp')"
    );
    $rmenu["Wireless"][] = array(
       "name" => gettext("Setup"),
       "url" => "../wireless/setup.php"
    );
};

if (Session::menu_perms("MenuEvents", "EventsAnomalies")) { $events = 1;
    $hmenu["Forensics"][] = array(
        "name" => gettext("Anomalies") ,
        "id" => "Anomalies",
        "url" => "control_panel/anomalies.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:anomalies','EventHelp')"
    );
}
/*
if (Session::menu_perms("MenuEvents", "EventsRT")) { $events = 1;
    $hmenu["Forensics"][] = array(
        "name" => gettext("Real Time") ,
        "id" => "RT Events",
        "url" => "control_panel/event_panel.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:events','Event Help')"
    );
}
*/
if (Session::menu_perms("MenuEvents", "EventsForensics")) { $events = 1; $hmenu["Forensics"][] = array(
    "name" => gettext("Statistics") ,
    "id" => "Events Stats",
    "url" => "report/event_stats.php",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:statistics','EventHelp')"
);
}

	$rmenu["Forensics"][] = array(
	  "name" => gettext("Manage References"),
	  "target" => "main",
	  "url" => "../forensics/manage_references.php"
	);
	

if (Session::am_i_admin() ) { 
	$hmenu["Forensics"][] = array(
		"name" => gettext("Signed files") ,
		"id" => "Signed Files",
		"url" => "signed_files/index.php"
	);
}

if (is_dir("/var/ossim/")) {
    // Only show SEM menu if SEM is available
    if (Session::menu_perms("MenuEvents", "ControlPanelSEM")) { $events = 1;
        $menu["Analysis"][] = array(
            "name" => gettext("Logger") ,
            "id" => "SEM",
            "url" => ($conf->get_conf("server_remote_logger", FALSE)=="yes") ? "sem/remote_index.php" : "sem/index.php"
        );
        $hmenu["SEM"][] = array(
            "name" => gettext("Logs") ,
            "id" => "SEM",
            "url" => "sem/index.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:logger','EventHelp')"
        );
    }
}
if (Session::menu_perms("MenuEvents", "EventsVulnerabilities")) { $events = 1;
    $menu["Analysis"][] = array(
        "name" => gettext("Vulnerabilities") ,
        "id" => "Vulnerabilities",
        "url" => "vulnmeter/index.php"
    );
    $hmenu["Vulnerabilities"][] = array(
        "name" => gettext("Vulnerabilities") ,
        "id" => "Vulnerabilities",
        "url" => "vulnmeter/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:vulnerabilities','EventHelp')"
    );
    $hmenu["Vulnerabilities"][] = array(
        "name" => gettext("Reports") ,
        "id" => "Reports",
        "url" => "vulnmeter/reports.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:reports','EventHelp')"
    );
    $hmenu["Vulnerabilities"][] = array(
        "name" => gettext("Scan Jobs") ,
        "id" => "Jobs",
        "url" => "vulnmeter/manage_jobs.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:jobs','EventHelp')"
    );
    $rmenu["Vulnerabilities"][] = array(
        "name" => gettext("Profiles") ,
        "id" => "ScanProfiles",
        "url" => "../vulnmeter/settings.php"
    );
    $rmenu["Reports"][] = array(
        "name" => gettext("Profiles") ,
        "id" => "ScanProfiles",
        "url" => "../vulnmeter/settings.php"
    );
    $rmenu["Jobs"][] = array(
        "name" => gettext("Profiles") ,
        "id" => "ScanProfiles",
        "url" => "../vulnmeter/settings.php"
    );
    $rmenu["Database"][] = array(
        "name" => gettext("Profiles") ,
        "id" => "ScanProfiles",
        "url" => "../vulnmeter/settings.php"
    );
    $hmenu["Vulnerabilities"][] = array(
        "name" => gettext("Threats Database") ,
        "id" => "Database",
        "url" => "vulnmeter/threats-db.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:analysis:vulnerabilities:threats_database','EventHelp')"
    );
    if($_SESSION["_user"]=="admin") {
        $rmenu["Vulnerabilities"][] = array(
           "name" => gettext("Settings") ,
           "id" => "Settings",
           "url" => "../vulnmeter/webconfig.php"
        );
        $rmenu["Reports"][] = array(
           "name" => gettext("Settings") ,
           "id" => "Settings",
           "url" => "../vulnmeter/webconfig.php"
        );
        $rmenu["Jobs"][] = array(
           "name" => gettext("Settings") ,
           "id" => "Settings",
           "url" => "../vulnmeter/webconfig.php"
        );
        $rmenu["Database"][] = array(
           "name" => gettext("Settings") ,
           "id" => "Settings",
           "url" => "../vulnmeter/webconfig.php"
        );
    }
}
/* Reports */
$reports = 0;
/*
if (Session::menu_perms("MenuReports", "ReportsSecurityReport")) { $reports = 1;
    $menu["Reports"][] = array(
        "name" => gettext("Security Report") ,
        "id" => "Security Report",
        "url" => "report/sec_report.php?section=all"
    );
    $hmenu["Security Report"][] = array(
        "name" => gettext("Security Report") ,
        "id" => "Security Report",
        "url" => "report/sec_report.php?section=all"
    );
}*/
if (Session::menu_perms("MenuReports", "ReportsGLPI") && $conf->get_conf("glpi_link", FALSE) != "") { $reports = 1; $menu["Reports"][] = array(
    "name" => gettext("GLPI") ,
    "id" => "GLPI",
    "url" => "$glpi_link"
);
}
/*
if (Session::menu_perms("MenuReports", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "") { $reports = 1; $menu["Reports"][] = array(
    "name" => gettext("OCS Inventory") ,
    "id" => "OCS Inventory",
    "url" => "$ocs_link"
);
}
if (Session::menu_perms("MenuReports", "ReportsPDFReport")) { $reports = 1;
    /*$menu["Reports"][] = array(
        "name" => gettext("PDF Report") ,
        "id" => "PDF Report",
        "url" => "report/pdfreportform.php"
        
    );
    $hmenu["PDF Report"][] = array(
        "name" => gettext("PDF Report") ,
        "id" => "PDF Report",
        "url" => "report/pdfreportform.php"
        //      "url" => "ocs/index.php"
        
    );
}*/
// Report Manager
if ($opensource) {
	if (Session::menu_perms("MenuReports", "ReportsReportServer")
		/*|| Session::menu_perms("MenuReports", "ReportsHostReport")
		|| Session::menu_perms("MenuEvents", "EventsForensics")
		|| Session::menu_perms("MenuEvents", "ControlPanelSEM")
		|| Session::menu_perms("MenuIncidents", "ControlPanelAlarms")
		|| Session::menu_perms("MenuEvents", "EventsVulnerabilities")
		|| Session::menu_perms("MenuEvents", "EventsAnomalies")
		|| Session::menu_perms("MenuIncidents", "IncidentsIncidents")
		|| Session::menu_perms("MenuControlPanel", "ControlPanelMetrics")*/
		) { $reports = 1;
	    $menu["Reports"][] = array(
	       "name" => gettext("Reports") ,
	       "id" => "Reporting Server",
	       "target" => "main",
	       "url" => "report/jasper.php?mode=simple"
	    );
	    $hmenu["Reporting Server"][] = array(
	       "name" => gettext("Reports") ,
	       "id" => "Reporting Server",
	       "target" => "main",
	       "url" => "report/jasper.php?mode=simple",
	       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:reports','Help');"
	    );
	    //if (Session::menu_perms("MenuReports", "ReportsReportServer")) {
        $rmenu["Reporting Server"][] = array(
           "name" => gettext("Manager"),
           "target" => "main",
           "url" => "../report/jasper.php?mode=advanced"
        );
        // "url" => "../report/jasper.php?mode=advanced&link=".urlencode($reporting_link)
        $hmenu["Reporting Server"][] = array(
           "name" => gettext("Customize") ,
           "id" => "Parameters",
           "target" => "main",
           "url" => "report/jasper.php?mode=config",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:customize','Help');"
        );
        $rmenu["Parameters"][] = array(
           "name" => gettext("Manager"),
           "target" => "main",
           "url" => "../report/jasper.php?mode=advanced"
        );
	    //}
	}
} else {
	// pro-version
	if (Session::menu_perms("MenuReports", "ReportsReportServer")) { $reports = 1;
	    $menu["Reports"][] = array(
	       "name" => gettext("Reports") ,
	       "id" => "Reporting Server",
	       "target" => "main",
	       "url" => "report/wizard_custom_reports.php?hmenu=Reporting+Server&smenu=Reporting+Server"
	    );
	    $hmenu["Reporting Server"][] = array(
	       "name" => gettext("Reports") ,
	       "id" => "Reporting Server",
	       "target" => "main",
	       "url" => "report/wizard_custom_reports.php",
	       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:custom+reports','Help');"
	    );;
        /*$hmenu["Reporting Server"][] = array(
           "name" => gettext("Wizard") ,
           "id" => "Wizard",
           "target" => "main",
           "url" => "report/wizard.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:wizard','Help');"
        );*/
        if (Session::am_i_admin()) $hmenu["Reporting Server"][] = array(
           "name" => gettext("Modules") ,
           "id" => "Subreports",
           "target" => "main",
           "url" => "report/wizard_subreports.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:subreports','Help');"
        );
        $hmenu["Reporting Server"][] = array(
           "name" => gettext("Layouts"),
           "id" => "Parameters",
           "target" => "main",
           "url" => "report/wizard_profiles.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:layouts','Help');"
        );
        $hmenu["Reporting Server"][] = array(
            "name" => gettext("Scheduler"),
            "id" => "Scheduler",
            "target" => "main",
            "url" => "report/wizard_scheduler.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:scheduler','Help');"
        );        
        $hmenu["Reporting Server"][] = array(
           "name" => gettext("FOSS Reports"),
           "id" => "OSReports",
           "target" => "main",
           "ghost" => true,
           "url" => "report/jasper.php?mode=simple",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports:reports','Help');"
        );
        // "url" => "../report/jasper.php?mode=advanced&link=".urlencode($reporting_link)
        $rmenu["Reporting Server"][] = array(
           "name" => gettext("FOSS Reports"),
           "target" => "main",
           "url" => "../report/jasper.php?mode=simple"
        );        
        $rmenu["OSReports"][] = array(
           "name" => gettext("Customize"),
           "target" => "main",
           "url" => "../report/jasper.php?mode=config"
        );
        $rmenu["OSReports"][] = array(
           "name" => gettext("Manager"),
           "target" => "main",
           "url" => "../report/jasper.php?mode=advanced"
        );
	}
}
/*
if (Session::menu_perms("MenuReports", "ToolsUserLog")) { $reports = 1;
    $menu["Reports"][] = array(
        "name" => gettext("User log") ,
        "id" => "User log",
        "url" => "userlog/user_action_log.php"
    );
    $hmenu["User log"][] = array(
        "name" => gettext("User log") ,
        "id" => "User log",
        "url" => "userlog/user_action_log.php"
    );
}*/
/*
if (Session::menu_perms("MenuReports", "ReportsWireless")) { $reports = 1;
    $menu["Reports"][] = array(
       "name" => gettext("Wireless") ,
       "id" => "Wireless",
       "url" => "wireless/"
    );
    $hmenu["Wireless"][] = array(
       "name" => gettext("Networks") ,
       "id" => "Wireless",
       "url" => "wireless/"
    );
    $hmenu["Wireless"][] = array(
       "name" => gettext("Setup") ,
       "id" => "Setup",
       "url" => "wireless/setup.php"
    );
}
*/
/*if ($reports) {
    $menu["Reports"][] = array(
       "name" => gettext("Help") ,
       "id" => "Help",
       "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:reports','Help');"
    );
}*/
/* Policy => Assets */
$assets = 0;

if (!$opensource && Session::menu_perms("MenuPolicy", "PolicyHosts") && Session::menu_perms("MenuPolicy", "PolicyNetworks")) { $assets = 1;
    $menu["Assets"][] = array(
      "name" => gettext("Assets") ,
      "id" => "Assets",
      "url" => "policy/entities.php"
    );
    $hmenu["Assets"][] = array(
      "name" => gettext("Structure"),
      "id" => "Assets",
      "url" => "policy/entities.php",
      "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:structure','Help');"
    );
    if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
	   $rmenu["Assets"][] = array(
		"name" => gettext("OCS Inventory") ,
		"target" => "main",
		"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
		);
}

if (Session::menu_perms("MenuPolicy", "PolicyHosts") || Session::menu_perms("MenuPolicy", "PolicyNetworks")
|| Session::menu_perms("MenuPolicy", "PolicyPorts")) { 
    if (Session::menu_perms("MenuPolicy", "PolicyHosts") && !$assets) {
        $menu["Assets"][] = array(
          "name" => gettext("Assets") ,
          "id" => "Assets",
          "url" => "host/host.php"
        );
    } elseif (Session::menu_perms("MenuPolicy", "PolicyNetworks") && !$assets) {
        $menu["Assets"][] = array(
          "name" => gettext("Assets") ,
          "id" => "Assets",
          "url" => "net/net.php"
        );
    } elseif (Session::menu_perms("MenuPolicy", "PolicyPorts") && !$assets) {
        $menu["Assets"][] = array(
          "name" => gettext("Assets") ,
          "id" => "Assets",
          "url" => "port/port.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:ports','Help');"
        );
    }
    $assets = 1;
    if (Session::menu_perms("MenuPolicy", "PolicyHosts")) {
       $id_h = (!preg_match("/entities/",$menu["Assets"][0]["url"]) && (preg_match("/host/",$menu["Assets"][0]["url"]) || preg_match("/host/",$menu["Assets"][1]["url"]))) ? "Assets" : "Hosts";
       $hmenu["Assets"][] = array(
          "name" => gettext("Hosts"),
          "id" => $id_h,
          "url" => "host/host.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:host','Help');"
       );
       if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		   $rmenu[$id_h][] = array(
			"name" => gettext("OCS Inventory") ,
			"target" => "main",
			"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
       $hmenu["Assets"][] = array(
          "name" => gettext("Host groups") ,
          "id" => "Host groups",
          "url" => "host/hostgroup.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:host','Help');"
        );
       if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		   $rmenu["Host groups"][] = array(
			"name" => gettext("OCS Inventory") ,
			"target" => "main",
			"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
    }
    if (Session::menu_perms("MenuPolicy", "PolicyNetworks")) { $assets = 1;
        $id_n = (!preg_match("/entities/",$menu["Assets"][0]["url"]) && (preg_match("/net/",$menu["Assets"][0]["url"]) || preg_match("/net/",$menu["Assets"][1]["url"]))) ? "Assets" : "Networks";
        $hmenu["Assets"][] = array(
          "name" => gettext("Networks") ,
          "id" => $id_n,
          "url" => "net/net.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:networks','Help');"
        );
       if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		   $rmenu[$id_n][] = array(
			"name" => gettext("OCS Inventory") ,
			"target" => "main",
			"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
        $hmenu["Assets"][] = array(
          "name" => gettext("Network groups") ,
          "id" => "Network groups",
          "url" => "net/netgroup.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:networks','Help');"
        );
       if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		   $rmenu["Network groups"][] = array(
			"name" => gettext("OCS Inventory") ,
			"target" => "main",
			"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
    }
    if (Session::menu_perms("MenuPolicy", "PolicyPorts")) { $assets = 1;
    	$id_p = (!preg_match("/entities/",$menu["Assets"][0]["url"]) && (preg_match("/port/",$menu["Assets"][0]["url"]) || preg_match("/port/",$menu["Assets"][1]["url"]))) ? "Assets" : "Ports";
        $hmenu["Assets"][] = array(
            "name" => gettext("Ports"),
            "id" => $id_p,
            "url" => "port/port.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:ports','Help');"
        );
        if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "")
		   $rmenu[$id_p][] = array(
			"name" => gettext("OCS Inventory") ,
			"target" => "main",
			"url" => "../policy/ocs_index.php?hmenu=Assets&smenu=Inventory"
			);
    }
	if (Session::menu_perms("MenuPolicy", "ReportsOCSInventory") && $conf->get_conf("ocs_link", FALSE) != "") { $assets = 1;
		$hmenu["Assets"][] = array(
		"name" => gettext("OCS Inventory") ,
		"id" => "Inventory",
		"target" => "main",
		"ghost" => true,
		"url" => "policy/ocs_index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:assets:inventory','Help');"
		);
	}
}

if (Session::menu_perms("MenuPolicy", "5DSearch")) { $assets = 1;
    $menu["Assets"][] = array(
        "name" => gettext("Asset Search") ,
        "id" => "Asset Search",
        "url" => "inventorysearch/userfriendly.php"
    );
    $hmenu["Asset Search"][] = array(
        "id" => "Asset Search",
        "name" => gettext("Simple") ,
        "url" => "inventorysearch/userfriendly.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_search','Help');"
    );
	$hmenu["Asset Search"][] = array(
        "name" => gettext("Advanced") ,
        "id" => "Advanced",
        "url" => "inventorysearch/inventory_search.php?new=1",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_search','Help');"
    );

}


if(Session::am_i_admin()) {
    $menu["Assets"][] = array(
        "name" => gettext("Asset Discovery") ,
        "id" => "Asset Discovery",
        "url" => "netscan/index.php"
    );
	/* if (Session::menu_perms("MenuTools", "ToolsScan")) {
    $menu["Tools"][] = array(
        "name" => gettext("Net Discovery") ,
        "id" => "Net Scan",
        "url" => "netscan/index.php"
    ); */
    $hmenu["Asset Discovery"][] = array(
        "name" => gettext("Active Net Discovery") ,
        "id" => "Asset Discovery",
        "url" => "netscan/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:net_discovery','Help');"
    );    
    $hmenu["Asset Discovery"][] = array(
        "id" => "Passive Network Discovery",
        "name" => gettext("Passive Network Discovery") ,
        "url" => "net/assetdiscovery.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
    $hmenu["Asset Discovery"][] = array(
        "id" => "Nedi",
        "name" => gettext("Nedi") ,
        "url" => "net/nedi.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
    $hmenu["Asset Discovery"][] = array(
        "id" => "Active Directory",
        "name" => gettext("Active Directory") ,
        "url" => "net/activedirectory.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:asset_discovery','Help');"
    );
}


/*if ($assets) $menu["Assets"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:policy','Help');"
); */
/* Correlation => Intelligence */
$correlation = 0;
if (Session::menu_perms("MenuIntelligence", "PolicyPolicy") || Session::menu_perms("MenuIntelligence", "PolicyActions")) { $correlation = 1;
    if (Session::menu_perms("MenuIntelligence", "PolicyPolicy")) {
        $menu["Intelligence"][] = array(
          "name" => gettext("Policy & Actions") ,
          "id" => "Policy",
          "url" => "policy/policy.php"
        );
    } elseif (Session::menu_perms("MenuPolicy", "PolicyActions")) {
        $menu["Intelligence"][] = array(
          "name" => gettext("Policy / Action") ,
          "id" => "Policy",
          "url" => "action/action.php"
        );
    }
    if (Session::menu_perms("MenuIntelligence", "PolicyPolicy")) {
        $hmenu["Policy"][] = array(
           "name" => gettext("Policy") ,
           "id" => "Policy",
           "url" => "policy/policy.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:policy_actions:policy','Help');"
        );
        $rmenu["Policy"][] = array(
           "name" => gettext("Edit Policy groups") ,
           "url" => "../policy/policygroup.php"
        );
    }
    if (Session::menu_perms("MenuIntelligence", "PolicyActions")) {
        $hmenu["Policy"][] = array(
            "name" => gettext("Actions") ,
            "id" => "Actions",
            "url" => "action/action.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:policy_actions:actions','Help');"
        );
    }
}
if (Session::menu_perms("MenuIntelligence", "CorrelationDirectives")) { $correlation = 1;
    $menu["Intelligence"][] = array(
        "name" => gettext("Correlation Directives") ,
        "id" => "Directives",
        "url" => "directive_editor/main.php"
    );
    $hmenu["Directives"][] = array(
        "name" => gettext("Directives") ,
        "id" => "Directives",
        "target" => "main",
        "url" => "directive_editor/main.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:directives','Help');"
    );
    $rmenu["Directives"][] = array(
        "name" => gettext("Numbering and Groups"),
        "target" => "main",
        "url" => "numbering.php"
    );
    if (Session::menu_perms("MenuIntelligence", "ComplianceMapping")) {
        $hmenu["Directives"][] = array(
           "name" => gettext("Properties") ,
           "id" => "Compliance",
           "target" => "main",
           "url" => "compliance/general.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:properties','Help');"
        );
    }
    if (Session::menu_perms("MenuIntelligence", "CorrelationBacklog")) {
        $hmenu["Directives"][] = array(
            "name" => gettext("Backlog") ,
            "id" => "Backlog",
            "target" => "main",
            "url" => "control_panel/backlog.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:correlation_directives:backlog','Help');"
        );
    }
}

if (Session::menu_perms("MenuIntelligence", "ComplianceMapping")) { $correlation = 1;
    $menu["Intelligence"][] = array(
       "name" => gettext("Compliance Mapping") ,
       "id" => "Compliance",
       "url" => "compliance/iso27001.php"
    );
    $hmenu["Compliance"][] = array(
       "name" => gettext("ISO 27001") ,
       "id" => "Compliance",
       "url" => "compliance/iso27001.php",
       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:iso_27001','Help');"
    );
    $hmenu["Compliance"][] = array(
       "name" => gettext("PCI DSS") ,
       "id" => "PCIDSS",
       "url" => "compliance/pci-dss.php",
       "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:compliance_mapping:pci_dss','Help');"
    );
}

if (Session::menu_perms("MenuIntelligence", "CorrelationCrossCorrelation")) { $correlation = 1;
    $menu["Intelligence"][] = array(
        "name" => gettext("Cross Correlation") ,
        "id" => "Cross Correlation",
        "url" => "conf/pluginref2.php"
    );
    $hmenu["Cross Correlation"][] = array(
        "name" => gettext("Rules") ,
        "id" => "Cross Correlation",
        "url" => "conf/pluginref2.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:cross_correlation','Help');"
    );
	/*
    $hmenu["Cross Correlation"][] = array(
        "name" => gettext("Edit Rules") ,
        "id" => "Edit Rules",
        "url" => "conf/pluginref2.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:intelligence:cross_correlation','Help');"
    );*/
}

if (Session::am_i_admin()) { $correlation = 1;
    $menu["Intelligence"][] = array(
        "name" => gettext("HIDS") ,
        "id" => "HIDS",
        "url" => "ossec/index.php"
    );
    $hmenu["HIDS"][] = array(
        "name" => gettext("Ossec") ,
        "id" => "HIDS",
        "url" => "ossec/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:HIDS:ossec','Help');"
    );	
	
	$rmenu["HIDS"][] = array(
           "name" => gettext("Edit Rules") ,
           "url" => "index.php"
    );
	
	$rmenu["HIDS"][] = array(
           "name" => gettext("Config") ,
           "url" => "config.php"
    );
	
	$rmenu["HIDS"][] = array(
           "name" => gettext("Agents") ,
           "url" => "agent.php"
    );
	
	$rmenu["HIDS"][] = array(
           "name" => gettext("Agentless") ,
           "url" => "agentless.php"
    );
	
	$rmenu["HIDS"][] = array(
           "name" => gettext("Ossec Control") ,
           "url" => "ossec_control.php"
    );
	
	
}	

/* if (Session::menu_perms("MenuReports", "Help")) *//* if ($correlation) $menu["Intelligence"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:correlation','Help');"
);*/
/* Monitors */
$monitors = 0;
if (Session::menu_perms("MenuMonitors", "MonitorsNetflows")) { $monitors = 1;
	$menu["Monitors"][] = array(
        "name" => gettext("Network") ,
        "id" => "Network",
        "url" => "nfsen/index.php?tab=2",
		"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors','Help');"
    );
	$hmenu["Network"][] = array(
        "name" => gettext("Traffic") ,
        "id" => "Network",
        "target" => "main",
        "url" => "nfsen/index.php?tab=2",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:network:traffic','Help');"
    );
	/*
	if (Session::menu_perms("MenuMonitors", "MonitorsNetwork")) { $monitors = 1;
    $menu["Monitors"][] = array(
        "name" => gettext("Network") ,
        "id" => "Network",
        "url" => "ntop/index.php?opc=services&sensor=" . $sensor_ntop["host"]
    );*/
    /*
	$hmenu["Network"][] = array(
        "name" => gettext("Services") ,
        "id" => "Network",
        "target" => "main",
        "url" => "ntop/index.php?opc=services&sensor=" . $sensor_ntop["host"]
    );
    $hmenu["Network"][] = array(
        "name" => gettext("Global") ,
        "id" => "Global",
        "target" => "main",
        "url" => "ntop/index.php?sensor=" . $sensor_ntop["host"]
    );
    $hmenu["Network"][] = array(
        "name" => gettext("Throughput") ,
        "id" => "Throughput",
        "target" => "main",
        "url" => "ntop/index.php?opc=throughput&sensor=" . $sensor_ntop["host"]
    );
    $hmenu["Network"][] = array(
        "name" => gettext("Matrix") ,
        "id" => "Matrix",
        "target" => "main",
        "url" => "ntop/index.php?opc=matrix&sensor=" . $sensor_ntop["host"]
    );
	*/
	$hmenu["Network"][] = array(
        "name" => gettext("Profiles") ,
        "id" => "Profiles",
        "target" => "main",
        "url" => "ntop/index.php?opc=services&sensor=" . $sensor_ntop["host"],
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:network:profiles','Help');",
		"nFrame" => ""
    );
	$rmenu["Profiles"][] = array(
       "name" => gettext("Services"),
	   "target" => "main",
       "url" => "../ntop/index.php?opc=services&sensor=" . $sensor_ntop["host"]
    );
	$rmenu["Profiles"][] = array(
       "name" => gettext("Global"),
	   "target" => "main",
       "url" => "../ntop/index.php?sensor=" . $sensor_ntop["host"]
    );
	$rmenu["Profiles"][] = array(
       "name" => gettext("Throughput"),
	   "target" => "main",
       "url" => "../ntop/index.php?opc=throughput&sensor=" . $sensor_ntop["host"]
    );
	$rmenu["Profiles"][] = array(
       "name" => gettext("Matrix"),
	   "target" => "main",
       "url" => "../ntop/index.php?opc=matrix&sensor=" . $sensor_ntop["host"]
    );
	}

//}
/*
if (Session::menu_perms("MenuMonitors", "MonitorsNetflows")) { $monitors = 1;
    $menu["Monitors"][] = array(
        "name" => gettext("Netflows") ,
        "id" => "Netflows",
        "url" => "nfsen/index.php?tab=0"
    );
    $hmenu["Netflows"][] = array(
        "name" => gettext("Flow overview"),
        "id" => "Netflows",
        "url" => "nfsen/index.php?tab=0"
    );
    $hmenu["Netflows"][] = array(
        "name" => gettext("Flow graphs"),
        "id" => "Graphs",
        "url" => "nfsen/index.php?tab=1"
    );
    $hmenu["Netflows"][] = array(
        "name" => gettext("Flow details"),
        "id" => "Details",
        "url" => "nfsen/index.php?tab=2"
    );
}*/
/*
if (Session::menu_perms("MenuMonitors", "MonitorsSession")) $menu["Usage & Profiles"][] = array(
"name" => gettext("Session") ,
"id" => "Session",
"url" => "ntop/session.php?sensor=" . $sensor_ntop["host"]
);*/
if (Session::menu_perms("MenuMonitors", "MonitorsAvailability")) { $monitors = 1;
    $menu["Monitors"][] = array(
        "name" => gettext("Availability") ,
        "id" => "Availability",
        "url" => "nagios/index.php?sensor=" . $sensor_nagios["host"]
    );
    $hmenu["Availability"][] = array(
        "name" => gettext("Monitoring") ,
        "id" => "Availability",
        "target" => "main",
        "url" => "nagios/index.php?sensor=" . $sensor_nagios["host"],
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:availability','Help');"
    );
    $hmenu["Availability"][] = array(
        "name" => gettext("Reporting") ,
        "id" => "Reporting",
        "target" => "main",
        "url" => "nagios/index.php?opc=reporting&sensor=" . $sensor_nagios["host"],
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:availability','Help');"
    );
}
/*
if (Session::menu_perms("MenuMonitors", "MonitorsVServers") && $conf->get_conf("ovcp_link", FALSE) != "") $menu["Usage & Profiles"][] = array(
"name" => gettext("Virtual Servers") ,
"id" => "Virtual Servers",
"url" => "$ovcp_link"
);*/
if (Session::menu_perms("MenuMonitors", "MonitorsSensors")) { $monitors = 1;
    $menu["Monitors"][] = array(
        "name" => gettext("System") ,
        "id" => "Sensors",
        "url" => "sensor/sensor_plugins.php"
    );
    $hmenu["Sensors"][] = array(
        "name" => gettext("Sensors") ,
        "id" => "Sensors",
        "url" => "sensor/sensor_plugins.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:system:sensors','Help');"
    );
	if (Session::menu_perms("MenuMonitors", "ToolsUserLog")) {
		$hmenu["Sensors"][] = array(
			"name" => gettext("User Activity") ,
			"id" => "User Log",
			"url" => "userlog/user_action_log.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors:system:user_activity','Help');"
		);
	}
	
}
/*
if (Session::menu_perms("MenuMonitors", "MonitorsRiskmeter")) $menu["Usage & Profiles"][] = array(
"name" => gettext("Riskmeter") ,
"id" => "Riskmeter",
"url" => "riskmeter/index.php"
);*/

/* if (Session::menu_perms("MenuMonitors", "Help")) */
/* if ($monitors) $menu["Monitors"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:monitors','Help');"
);*/
/* Configuration */
$configuration = 0;
if (Session::menu_perms("MenuConfiguration", "ConfigurationMain")) { //if (file_exists($version_file)) {
    $configuration = 1;
	$menu["Configuration"][] = array(
        "name" => gettext("Main") ,
        "id" => "Main",
        "url" => "conf/main.php"
    );
    $hmenu["Main"][] = array(
        "name" => gettext("Simple") ,
        "id" => "Main",
        "url" => "conf/main.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:configuration','Help');"
    );
    $hmenu["Main"][] = array(
        "name" => gettext("Advanced") ,
        "id" => "Advanced",
        "url" => "conf/main.php?adv=1",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:configuration','Help');"
    );
} /*else {
    $configuration = 1;
	$menu["Configuration"][] = array(
        "name" => gettext("Main") ,
        "id" => "Main",
        "url" => "conf/index.php"
    );
    $hmenu["Main"][] = array(
        "name" => gettext("Simple") ,
        "id" => "Main",
        "url" => "conf/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration','Help');"
    );
    $hmenu["Main"][] = array(
        "name" => gettext("Advanced") ,
        "id" => "Advanced",
        "url" => "conf/index.php?adv=1",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration','Help');"
    );
}*/
if (Session::menu_perms("MenuConfiguration", "ConfigurationUsers")) { $configuration = 1;
    $users_path = ($opensource) ? "session/users.php" : "acl/users.php";
	$menu["Configuration"][] = array(
        "name" => gettext("Users") ,
        "id" => "Users",
        "url" => $users_path
    );
    $hmenu["Users"][] = array(
        "name" => gettext("Configuration") ,
        "id" => "Users",
        "url" => $users_path,
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:users:users','Help');"
    );
	if (!$opensource && (Session::am_i_admin() || Acl::am_i_proadmin())) {
	$rmenu["Users"][] = array(
          "name" => gettext("Entities"),
          "target" => "main",
          "url" => "../acl/entities.php"
        );
	$rmenu["Users"][] = array(
          "name" => gettext("Templates"),
          "target" => "main",
          "url" => "../acl/templates.php"
        );
	}
}
if (Session::menu_perms("MenuConfiguration", "ConfigurationUserActionLog")) { $configuration = 1; $hmenu["Users"][] = array(
    "name" => gettext("User activity") ,
    "id" => "User action logs",
    "url" => "conf/userlog.php",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:users:user_activity','Help');"
);
}
if (Session::menu_perms("MenuPolicy", "PolicyServers") || Session::menu_perms("MenuPolicy", "PolicySensors")) { $assets = 1;
    if (Session::menu_perms("MenuPolicy", "PolicySensors")) {
        $menu["Configuration"][] = array(
          "name" => gettext("SIEM Components") ,
          "id" => "SIEM Components",
          "url" => "sensor/sensor.php"
        );
    } elseif (Session::menu_perms("MenuPolicy", "PolicyServers")) {
        $menu["Configuration"][] = array(
          "name" => gettext("SIEM Components") ,
          "id" => "SIEM Components",
          "url" => "server/server.php"
        );
    }
    if (Session::menu_perms("MenuPolicy", "PolicySensors")) {
        $hmenu["SIEM Components"][] = array(
          "name" => gettext("Sensors") ,
          "id" => (preg_match("/sensor/",$menu["Configuration"][0]["url"]) || preg_match("/sensor/",$menu["Configuration"][1]["url"]) || preg_match("/sensor/",$menu["Configuration"][2]["url"]) || preg_match("/sensor/",$menu["Configuration"][3]["url"])) ? "SIEM Components" : "Sensors",
          "url" => "sensor/sensor.php",
          "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:sensors','Help');"
        );
    }
    if (Session::menu_perms("MenuPolicy", "PolicyServers") && !$opensource) {
        $hmenu["SIEM Components"][] = array(
            "name" => gettext("Servers") ,
            "id" => (preg_match("/server/",$menu["Configuration"][0]["url"]) || preg_match("/server/",$menu["Configuration"][1]["url"]) || preg_match("/server/",$menu["Configuration"][2]["url"]) || preg_match("/server/",$menu["Configuration"][3]["url"])) ? "SIEM Components" : "Servers",
            "url" => "server/server.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:servers','Help');"
        );
        $hmenu["SIEM Components"][] = array(
           "name" => gettext("Databases"),
           "id" => "DBs",
           "url" => "server/dbs.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:assets:siem_components:databases','Help');"
        );
    }
    /*if (Session::menu_perms("MenuPolicy", "PolicyPluginGroups")) {
        $hmenu["SIEM Components"][] = array(
            "name" => gettext("Plugin Groups") ,
            "id" => (preg_match("/plugingroups/",$menu["Assets"][1]["url"])) ? "SIEM Components" : "Plugin Groups",
            "url" => "policy/plugingroups.php"
        );
    }*/
}
if (Session::menu_perms("MenuConfiguration", "ConfigurationPlugins")) { $configuration = 1;
    $menu["Configuration"][] = array(
        "name" => gettext("Collection") ,
        "id" => "Plugins",
        "url" => "conf/plugin.php"
    );
    $hmenu["Plugins"][] = array(
        "name" => gettext("Plugins") ,
        "id" => "Plugins",
        "url" => "conf/plugin.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:plugins','Help');"
    );
    if (Session::menu_perms("MenuConfiguration", "PluginGroups")) {
		$hmenu["Plugins"][] = array(
			"name" => gettext("Plugin Groups") ,
			"id" => "Plugin Groups",
			"url" => "policy/plugingroups.php",
			"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:plugin_groups','Help');"
		);
	}
        $hmenu["Plugins"][] = array(
		"name" => gettext("Custom Collectors") ,
		"id" => "Custom Collectors",
		"url" => "policy/collectors.php",
		"help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:custom_collectors','Help');"
        );
		$hmenu["Plugins"][] = array(
           "name" => gettext("Taxonomy"),
           "id" => "Taxonomy",
           "url" => "conf/category.php",
           "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:collection:manage_taxonomy','Help');"
        );
        $hmenu["Plugins"][] = array(
            "name" => gettext("Downloads") ,
            "id" => "Downloads",
            "url" => "downloads/index.php",
            "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:downloads','Help');"
        );
}
/*
if (Session::menu_perms("MenuConfiguration", "ConfigurationRRDConfig")) {
$menu["Configuration"][] = array(
"name" => gettext("RRD Config") ,
"id" => "RRD Config",
"url" => "rrd_conf/rrd_conf.php"
);
$hmenu["RRD Config"][] = array(
"name" => gettext("RRD Config") ,
"id" => "RRD Config",
"url" => "rrd_conf/rrd_conf.php"
);
}
if (Session::menu_perms("MenuConfiguration", "ConfigurationHostScan")) $menu["Configuration"][] = array(
"name" => gettext("Host Scan") ,
"id" => "Host Scan",
"url" => "scan/hostscan.php"
);
if (Session::menu_perms("MenuConfiguration", "ConfigurationEmailTemplate")) {
$menu["Configuration"][] = array(
"name" => gettext("Incidents Email Template") ,
"id" => "Incidents Email Template",
"url" => "conf/emailtemplate.php"
);
$hmenu["Incidents Email Template"][] = array(
"name" => gettext("Incidents Email Template") ,
"id" => "Incidents Email Template",
"url" => "conf/emailtemplate.php"
);
}*/

if (Session::menu_perms("MenuConfiguration", "ConfigurationUpgrade") && Session::am_i_admin()) { $configuration = 1;
    $menu["Configuration"][] = array(
        "name" => gettext("Software Upgrade") ,
        "id" => "Update",
        "url" => "updates/"
    );
    /*$hmenu["Upgrade"][] = array(
        "name" => gettext("Software Upgrade") ,
        "id" => "Upgrade",
        "url" => "upgrade/",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:software_upgrade','Help');"
    );*/
    $hmenu["Update"][] = array(
        "name" => gettext("Update Notification") ,
        "id" => "Update",
        "url" => "updates/",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration:update_notification','Help');"
    );
}
if (Session::menu_perms("MenuTools", "ToolsBackup")) {
    $menu["Configuration"][] = array(
        "name" => gettext("Backup") ,
        "id" => "Backup",
        "url" => "backup/index.php"
    );
    $hmenu["Backup"][] = array(
        "name" => gettext("SIEM Backup") ,
        "id" => "Backup",
        "url" => "backup/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:backup','Help');"
    );
}

/*
if (Session::menu_perms("MenuConfiguration", "ConfigurationMaps")) $menu["Configuration"][] = array(
"name" => gettext("Maps") ,
"id" => "Maps",
"url" => "maps/"
);*/

/* if (Session::menu_perms("MenuConfiguration", "Help")) */
/* if ($configuration) $menu["Configuration"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:configuration','Help');"
);*/
/* Tools */
/*$tools = 0;
if (Session::menu_perms("MenuTools", "ToolsBackup")) { $tools = 1;
    $menu["Tools"][] = array(
        "name" => gettext("Backup") ,
        "id" => "Backup",
        "url" => "backup/index.php"
    );
    $hmenu["Backup"][] = array(
        "name" => gettext("Backup") ,
        "id" => "Backup",
        "url" => "backup/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:backup','Help');"
    );
}*/
/*
if (Session::menu_perms("MenuTools", "ToolsDownloads")) { $tools = 1; //if (file_exists($version_file)) { $tools = 1;
    $menu["Tools"][] = array(
        "name" => gettext("Downloads") ,
        "id" => "Downloads",
        "url" => "downloads/index.php"
    );
    $hmenu["Downloads"][] = array(
        "name" => gettext("Tool Downloads") ,
        "id" => "Downloads",
        "url" => "downloads/index.php",
        "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools:downloads','Help');"
    );
}*/

$hmenu["Sysinfo"][] = array(
    "name" => gettext("System Status") ,
    "id" => "Sysinfo",
    "url" => "sysinfo/sysinfo.php",
    "target" => "info", 
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:sysinfo','Help');"
);
    
/*
if (Session::menu_perms("MenuTools", "ToolsRuleViewer")) $menu["Tools"][] = array(
"name" => gettext("Rule Viewer") ,
"id" => "Rule Viewer",
"url" => "editor/editor.php"
);*/
// Right now only the installer uses this so it makes no sense in mainstream
/*
if (Session::menu_perms("MenuTools", "Updates")) $menu["Tools"][] = array(
"name" => gettext("Update Information") ,
"id" => "Updates",
"url" => "updates/index.php"
);*/
/* if (Session::menu_perms("MenuTools", "Help")) */ /*if ($tools) $menu["Tools"][] = array(
    "name" => gettext("Help") ,
    "id" => "Help",
    "url" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:tools','Help');"
);*/
/* Logout */
$menu["Logout"] = "session/login.php?action=logout"; // Plain url if no array entry

$hmenu["Sessions"][] = array(
    "name" => gettext("Opened Sessions") ,
    "id" => "Sessions",
    "url" => "userlog/opened_sessions.php",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:opened_sessions','Help');"
);

$hmenu["Userprofile"][] = array(
    "name" => gettext("My Profile") ,
    "id" => "Userprofile",
    "url" => ( $opensource ) ? "session/modifyuserform.php?user=".Session::get_session_user()."&frommenu=1" : "acl/users_edit.php?login=".Session::get_session_user()."&frommenu=1",
    "help" => "javascript:top.topmenu.new_wind('http://ossim.net/dokuwiki/doku.php?id=user_manual:my_profile','Help');"
);

?>
