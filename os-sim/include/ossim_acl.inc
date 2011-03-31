<?php
/*****************************************************************************
*
*    License:
*
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
define("ACL_DEFAULT_USER_SECTION", "users");
define("ACL_DEFAULT_USER_GROUP", "users");
define("ACL_DEFAULT_OSSIM_ADMIN", "admin");
define("ACL_DEFAULT_DOMAIN_SECTION", "DomainAccess");
define("ACL_DEFAULT_DOMAIN_ALL", "All");
define("ACL_DEFAULT_DOMAIN_LOGIN", "Login");
define("ACL_DEFAULT_DOMAIN_NETS", "Nets");
define("ACL_DEFAULT_DOMAIN_SENSORS", "Sensors");
/* get phpgacl config */
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
if (!$conf) {
	require_once 'ossim_db.inc';
	require_once 'ossim_conf.inc';
	$conf = new ossim_conf();
}
/* include default $gacl_options */
//require_once ("$phpgacl_path/admin/gacl_admin.inc.php");
$ACL_OPTIONS = array(
    /*
    'debug'                     => $gacl_options['debug'],
    'items_per_page'            => $gacl_options['items_per_page'],
    'max_select_box_items'      => $gacl_options['max_select_box_items'],
    'max_search_return_items'   => $gacl_options['max_search_return_items'],
    */
    'db_type' => $conf->get_conf("phpgacl_type") ,
    'db_host' => $conf->get_conf("phpgacl_host") ,
    'db_user' => $conf->get_conf("phpgacl_user") ,
    'db_password' => $conf->get_conf("phpgacl_pass") ,
    'db_name' => $conf->get_conf("phpgacl_base") ,
    /*
    'db_table_prefix'           => $gacl_options['db_table_prefix'],
    'caching'                   => $gacl_options['caching'],
    'force_cache_expire'        => $gacl_options['force_cache_expire'],
    'cache_dir'                 => $gacl_options['cache_dir'],
    'cache_expire_time'         => $gacl_options['cache_expire_time']
    */
);
$phpgacl_path = $conf->get_conf("phpgacl_path");
/*
* phpgacl on "new gacl()" does a lot of heavy operations like:
* parsing files, including code and opens a new persistant
* connection to the BD. So better make $gacl global
*/
$phpgacl = $GLOBALS["CONF"]->get_conf("phpgacl_path");
require_once $phpgacl . DIRECTORY_SEPARATOR . 'gacl.class.php';
require_once $phpgacl . DIRECTORY_SEPARATOR . 'gacl_api.class.php';

$GLOBALS['ACL'] = new gacl($ACL_OPTIONS);
$ACL_MAIN_MENU = array(
    "MainMenu" => array(
        "Index" => array(
            "name" => "Top Frame",
            "default_perm" => true
        )
    ) ,
    "MenuControlPanel" => array(
        "ControlPanelExecutive" => array(
            "name" => "Dashboard -> Main",
            "default_perm" => true
        ) ,
        "ControlPanelExecutiveEdit" => array(
            "name" => "Dashboard -> Executive Panel Edit",
            "default_perm" => false
        ) ,
        "BusinessProcesses" => array(
            "name" => "Dashboard -> Risk Maps",
            "default_perm" => true
        ) ,/*
		"ConfigurationMaps" => array(
            "name" => "Configuration -> Maps",
            "default_perm" => true
        ) */
        "BusinessProcessesEdit" => array(
            "name" => "Dashboard -> Risk Maps Edit",
            "default_perm" => false
        ) ,
        "ControlPanelMetrics" => array(
            "name" => "Dashboard -> Metrics",
            "default_perm" => true
        ) ,
		"MonitorsRiskmeter" => array(
            "name" => "Dashboard -> Metrics -> Riskmeter",
            "default_perm" => true
        ) //,
        /*
		"ControlPanelEvents" => array(
            "name" => "Control Panel -> Events",
            "default_perm" => true
        ) ,*/
        /*
        "ControlPanelVulnerabilities" => array(
            "name" => "Dashboard -> Vulnerabilities",
            "default_perm" => true
        ) ,
		
        "ControlPanelAnomalies" => array(
            "name" => "Control Panel -> Anomalies",
            "default_perm" => true
        ) ,
        "ControlPanelSEM" => array(
            "name" => "Control Panel -> SEM",
            "default_perm" => false
        ) ,
        "ControlPanelHids" => array(
            "name" => "Control Panel -> Hids",
            "default_perm" => true
        )
		*/
    ) ,
	"MenuIncidents" => array(
    	"ControlPanelAlarms" => array(
            "name" => "Incidents -> Alarms",
            "default_perm" => true
        ) ,
		"ReportsAlarmReport" => array(
            "name" => "Incidents -> Alarms -> Reports",
            "default_perm" => true
        ) ,
        "IncidentsIncidents" => array(
            "name" => "Incidents -> Tickets",
            "default_perm" => true
        ) ,
        "IncidentsOpen" => array(
            "name" => "Incidents -> Tickets -> Open Tickets",
            "default_perm" => true
        ) ,
        "IncidentsDelete" => array(
            "name" => "Incidents -> Tickets -> Delete",
            "default_perm" => true
        ) ,
        "IncidentsReport" => array(
            "name" => "Incidents -> Tickets -> Report",
            "default_perm" => true
        ) ,
		"IncidentsTypes" => array(
            "name" => "Incidents -> Tickets -> Types",
            "default_perm" => false
        ) ,
        "IncidentsTags" => array(
            "name" => "Incidents -> Tickets -> Tags",
            "default_perm" => false
        ) ,
		"ConfigurationEmailTemplate" => array(
            "name" => "Incidents -> Tickets -> Incidents Email Template",
            "default_perm" => false
        ) ,
        "Osvdb" => array(
            "name" => "Incidents -> Knowledge DB",
            "default_perm" => true
        ) ,
    ) ,
    "MenuEvents" => array(
        "EventsForensics" => array(
            "name" => "Analysis -> SIEM Events",
            "default_perm" => true
        ) ,
        "EventsForensicsDelete" => array(
            "name" => "Analysis -> SIEM Events -> Delete Events",
            "default_perm" => true
        ) ,
        "EventsRT" => array(
            "name" => "Analysis -> SIEM Events -> Real Time",
            "default_perm" => true
        ) ,
		"ControlPanelSEM" => array(
            "name" => "Analysis -> Logger",
            "default_perm" => false
        ) ,
        "EventsVulnerabilities" => array(
            "name" => "Analysis -> Vulnerabilities -> View",
            "default_perm" => false
        ) ,
        "EventsNids" => array(
            "name" => "Analysis -> Detection -> Nids",
            "default_perm" => false
        ) ,
        "EventsHids" => array(
            "name" => "Analysis -> Detection -> Hids",
            "default_perm" => false
        ) ,
        "ReportsWireless" => array(
            "name" => "Analysis -> Detection -> Wireless",
            "default_perm" => false
        ) ,
        "EventsAnomalies" => array(
            "name" => "Analysis -> Detection -> Anomalies",
            "default_perm" => false
        )
    ) ,
	"MenuReports" => array(
        /*
		"ReportsReportServer" => array(
            "name" => "Reports -> ReportServer / BI",
            "default_perm" => true
        ) ,*/
		/*
		"ReportsPDFReport" => array(
            "name" => "Reports -> PDFReport",
            "default_perm" => true
        ) ,*/
    	"ReportsHostReport" => array(
        	"name" => "Reports -> Asset Report",
        	"default_perm" => true
        ) ,
		"ReportsReportServer" => array(
            "name" => "Reports -> Reports -> Advanced",
            "default_perm" => true
        )
        
        /*
        "ReportsGLPI" => array(
            "name" => "Reports -> GLPI",
            "default_perm" => true
        ) ,*//*
		"ToolsUserLog" => array(
            "name" => "Reports -> Reports -> User log",
            "default_perm" => true
        ) ,*//*
        "ReportsOCSInventory" => array(
            "name" => "Reports -> OCS Inventory",
            "default_perm" => true
        ) ,*//*
        "ReportsWireless" => array(
            "name" => "Reports -> Wireless",
            "default_perm" => true
        ) ,*/
    ) ,
    "MenuPolicy" => array(
        "5DSearch" => array(
            "name" => "Assets -> Asset Search",
            "default_perm" => true
        ) ,
		"PolicyHosts" => array(
            "name" => "Assets -> Assets -> Hosts",
            "default_perm" => false
        ) ,
        "PolicyNetworks" => array(
            "name" => "Assets -> Assets -> Networks",
            "default_perm" => false
        ) ,
		"PolicyPorts" => array(
            "name" => "Assets -> Assets -> Ports",
            "default_perm" => false
        ) ,
		"ReportsOCSInventory" => array(
            "name" => "Assets -> Assets -> Inventory",
            "default_perm" => true
        ) ,
        "ToolsScan" => array(
            "name" => "Assets -> Assets Discovery",
            "default_perm" => true
        )
        /*
        "PolicyResponses" => array(
            "name" => "Policy -> Responses",
            "default_perm" => false
        ) ,
        "PolicyPluginGroups" => array(
            "name" => "Assets -> SIEM -> Plugin Groups",
            "default_perm" => false
        )*/
	) ,
	"MenuIntelligence" => array(
		"PolicyPolicy" => array(
            "name" => "Intelligence -> Policy",
            "default_perm" => false
        ) ,
		"PolicyActions" => array(
            "name" => "Intelligence -> Actions",
            "default_perm" => false
        ) ,
        "CorrelationDirectives" => array(
            "name" => "Intelligence -> Correlation Directives",
            "default_perm" => false
        ) ,
		"CorrelationBacklog" => array(
            "name" => "Intelligence -> Correlation Directives -> Backlog",
            "default_perm" => true
        ) ,
		"ComplianceMapping" => array(
            "name" => "Intelligence -> Compliance Mapping",
            "default_perm" => false
        ) ,
		"CorrelationCrossCorrelation" => array(
            "name" => "Intelligence -> Cross Correlation",
            "default_perm" => false
        )/* ,
        "CorrelationBacklog" => array(
            "name" => "Correlation -> Backlog",
            "default_perm" => true
        )*/
    ) ,
    "MenuMonitors" => array(
        /*
		"MonitorsSession" => array(
            "name" => "Monitors -> Session",
            "default_perm" => true
        ) ,*/
    	"MonitorsNetflows" => array(
            "name" => "Situational Awareness -> Network -> Traffic",
            "default_perm" => true
        ) ,
        "MonitorsNetwork" => array(
            "name" => "Situational Awareness -> Network -> Profiles",
            "default_perm" => true
        ) ,
        "MonitorsAvailability" => array(
            "name" => "Situational Awareness -> Availability",
            "default_perm" => true
        ) /*
        "MonitorsVServers" => array(
            "name" => "Monitors -> Virtual Servers",
            "default_perm" => true
        ) ,*/
         /*,
        "MonitorsRiskmeter" => array(
            "name" => "Monitors -> Riskmeter",
            "default_perm" => true
        )*/
    ) ,
    "MenuConfiguration" => array(
        "ConfigurationMain" => array(
            "name" => "Configuration -> Main",
            "default_perm" => false
        ) ,
        "ConfigurationUsers" => array(
            "name" => "Configuration -> Users",
            "default_perm" => false
        ) ,
		"ConfigurationUserActionLog" => array(
            "name" => "Configuration -> Users -> User activity",
            "default_perm" => false
        ) ,
        "PolicySensors" => array(
            "name" => "Configuration -> SIEM Components -> Sensors",
            "default_perm" => false
        ) ,
        "PolicyServers" => array(
            "name" => "Configuration -> SIEM Components -> Servers",
            "default_perm" => false
        ) ,
        "ConfigurationPlugins" => array(
            "name" => "Configuration -> Collection -> Data Sources",
            "default_perm" => false
        ),
		"PluginGroups" => array(
            "name" => "Configuration -> Collection -> DS Groups",
            "default_perm" => false
        ),
        "ToolsDownloads" => array(
            "name" => "Configuration -> Collection -> Downloads",
            "default_perm" => true
        ),
        "MonitorsSensors" => array(
            "name" => "Configuration -> Collection -> Sensors",
            "default_perm" => false
        ),
        "ToolsUserLog" => array(
            "name" => "Configuration -> Collection -> User Activity",
            "default_perm" => false
        ),
        "ToolsBackup" => array(
            "name" => "Configuration -> Backup",
            "default_perm" => false
        )/*
        "ConfigurationRRDConfig" => array(
            "name" => "Configuration -> RRD Config",
            "default_perm" => false
        ) ,
        "ConfigurationHostScan" => array(
            "name" => "Configuration -> Host Scan",
            "default_perm" => false
        ) ,*/
         /*, ONLY ADMIN:
        "ConfigurationUpgrade" => array(
            "name" => "Configuration -> Software Upgrade",
            "default_perm" => false
        )*/
    )/* ,
    "MenuTools" => array(
        "ToolsScan" => array(
            "name" => "Tools -> Net Scan",
            "default_perm" => true
        ) ,
        "ToolsRuleViewer" => array(
            "name" => "Tools -> RuleViewer",
            "default_perm" => false
        ) ,
        "ToolsBackup" => array(
            "name" => "Tools -> Backup",
            "default_perm" => false
        ) ,
        "ToolsUserLog" => array(
            "name" => "Tools -> User log",
            "default_perm" => true
        ) ,
        "ToolsDownloads" => array(
            "name" => "Tools -> Downloads",
            "default_perm" => true
        )*/
    //)
);
?>