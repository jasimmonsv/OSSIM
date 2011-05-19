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
* - valid_value()
* - submit()
*/
if ($_GET["section"] == "vulnerabilities") {
	header("Location:../vulnmeter/webconfig.php?nohmenu=1");
} elseif ($_GET["section"] == "hids") {
	header("Location:../ossec/config.php?nohmenu=1");
} elseif ($_GET["section"] == "wids") {
	header("Location:../wireless/setup.php?nohmenu=1");
} elseif ($_GET["section"] == "assetdiscovery") {
	header("Location:../net/assetdiscovery.php?nohmenu=1");
}
require_once 'classes/Session.inc';
Session::logcheck("MenuConfiguration", "ConfigurationMain");
require_once 'ossim_conf.inc';
require_once 'classes/Security.inc';
require_once 'languages.inc';

$ossim_conf = $GLOBALS["CONF"];
$config_languages = $GLOBALS["config_languages"];

$CONFIG = array(
    "Language" => array(
        "title" => gettext("Language") ,
        "desc" => gettext("Configure Internationalization") ,
        "advanced" => 0,
        "conf" => array(
            "language" => array(
                "type" => $config_languages,
                "help" => gettext("Obsolete, configure at Configuration -> Users") ,
                "desc" => gettext("Language") ,
                "advanced" => 0
            ) /*,
            "locale_dir" => array(
                "type" => "text",
                "help" => gettext("Location of the ossim.mo localization files. You shouldn't need to change this.") ,
                "desc" => gettext("Locale File Directory") ,
                "advanced" => 0
            )*/
        )
    ) ,
    "Ossim Server" => array(
        "title" => gettext("Ossim Server") ,
        "desc" => gettext("Configure the server's listening address") ,
        "advanced" => 1,
    	"section" => "siem,logger",
        "conf" => array(
            "server_address" => array(
                "type" => "text",
                "help" => gettext("Server IP") ,
                "desc" => gettext("Server Address (it's usually 127.0.0.1)") ,
                "advanced" => 1
            ) ,
            "server_port" => array(
                "type" => "text",
                "help" => gettext("Port number") ,
                "desc" => gettext("Server Port (default:40001)") ,
                "advanced" => 1
            ) ,
			"server_sim" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"onchange" => "tsim(this.value)" ,
                "help" => gettext("SIEM") ,
                "desc" => "<font style='text-decoration:underline'>".gettext("SIEM")."</font>" ,
                "advanced" => 1
            ) ,
			"server_qualify" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "qualify_select",
                "help" => gettext("Qualification") ,
                "desc" => gettext("Qualification") ,
                "advanced" => 1
            ) ,
			"server_correlate" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "correlate_select",
                "help" => gettext("Correlation") ,
                "desc" => gettext("Correlation") ,
                "advanced" => 1 ,
                "section" => "directives"
            ) ,
			"server_cross_correlate" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "cross_correlate_select",
                "help" => gettext("Cross-correlation") ,
                "desc" => gettext("Cross-correlation") ,
                "advanced" => 1 ,
                "section" => "directives"
            ) ,
			"server_store" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "store_select",
                "help" => gettext("SQL Storage") ,
                "desc" => gettext("SQL Storage") ,
                "advanced" => 1
            ) ,
			"server_sem" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"onchange" => "tsem(this.value)" ,
                "help" => gettext("Logger") ,
                "desc" => "<font style='text-decoration:underline'>".gettext("Logger")."</font>" ,
                "advanced" => 1,
                "section" => "logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_sign" => array(
                "type" => array(
                    "yes" => _("Line") ,
                    "no" => _("Block")
                ) ,
				"id" => "sign_select",
                "help" => gettext("Sign") ,
                "desc" => gettext("Sign") ,
                "advanced" => 1,
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_forward_alarm" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "forward_alarm_select",
                "help" => gettext("Alarm forwarding") ,
                "desc" => gettext("Alarm forwarding") ,
                "advanced" => 1,
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_forward_event" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "forward_event_select",
                "help" => gettext("Events forwarding") ,
                "desc" => gettext("Events forwarding") ,
                "advanced" => 1,
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_alarms_to_syslog" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
				"id" => "alarms_to_syslog_select",
                "help" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? gettext("Alarms to syslog") : _("Only Available when using Alienvault Unified SIEM"),
                "desc" => gettext("Alarms to syslog") ,
                "advanced" => 1,
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_remote_logger" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? gettext("Remote Logger") : _("Only Available when using Alienvault Unified SIEM"),
                "desc" => gettext("Remote Logger") ,
                "advanced" => 1,
                "section" => "logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_remote_logger_user" => array(
                "type" => "text",
                "help" => gettext("Remote OSSIM Logger user") ,
                "desc" => gettext("Remote Logger user") ,
                "advanced" => 1,
            	"section" => "logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_remote_logger_pass" => array(
                "type" => "password",
                "help" => gettext("Remote OSSIM Logger password") ,
                "desc" => gettext("Remote Logger password") ,
                "advanced" => 1,
            	"section" => "logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
			"server_remote_logger_ossim_url" => array(
                "type" => "text",
                "help" => gettext("Remote Logger Url") ,
                "desc" => gettext("Remote Logger OSSIM Url") ,
                "advanced" => 1,
            	"section" => "logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
            "server_logger_if_priority" => array(
                "type" => array(
                    "0" => 0,
                    "1" => 1,
            		"2" => 2,
            		"3" => 3,
            		"4" => 4,
            		"5" => 5
                ) ,
                "help" => gettext("Store in SIEM if priority >= this value")."<br>".gettext("Requires /etc/init.d/ossim-server restart") ,
                "desc" => gettext("SIEM process priority threshold") ,
                "advanced" => 1,
                "section" => "logger,siem",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            ) ,
            "databases_link" => array(
            	"type" => "link",
            	"help" => gettext("Define databases") ,
                "desc" => "<a target='".(($section != "") ? "_parent" : "main")."' href='../server/dbs.php?hmenu=SIEM+Components&smenu=DBs'>".gettext("Define SIEM databases")."</a>" ,
                "advanced" => 1,
                "section" => "siem,logger",
				"disabled" => (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) ? 0 : 1
            )
        )
    ) ,
    "Solera" => array(
        "title" => gettext("Solera") ,
        "desc" => gettext("Integration into the Solera DeepSee forensic suit") ,
        "advanced" => 1,
        "conf" => array(
            "solera_enable" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Solera integration") ,
                "advanced" => 1
            ),
            "solera_host" => array(
                "type" => "text",
                "help" => gettext("Solera API host. IP or FQDN") ,
                "desc" => gettext("Solera API host") ,
                "advanced" => 1,
            ),
            "solera_port" => array(
                "type" => "text",
                "help" => gettext("Solera API port") ,
                "desc" => gettext("Solera API port") ,
                "advanced" => 1,
            ),            
            "solera_user" => array(
                "type" => "text",
                "help" => gettext("Solera API user") ,
                "desc" => gettext("Solera API user") ,
                "advanced" => 1,
            ),
            "solera_pass" => array(
                "type" => "password",
                "help" => gettext("Solera API password") ,
                "desc" => gettext("Solera API password") ,
                "advanced" => 1,
            )
        )
    ),  
    "Ossim Framework" => array(
        "title" => gettext("Ossim Framework") ,
        "desc" => gettext("PHP Configuration (graphs, acls, database api) and links to other applications") ,
        "advanced" => 1,
    	"section" => "alarms",
        "conf" => array(
            /*"ossim_link" => array(
                "type" => "text",
                "help" => gettext("Ossim web link. Usually located under /ossim/") ,
                "desc" => gettext("Ossim Link") ,
                "advanced" => 1
            ) ,
            "adodb_path" => array(
                "type" => "text",
                "help" => gettext("ADODB Library path. PHP database extraction library.") ,
                "desc" => gettext("ADODB Path") ,
                "advanced" => 1
            ) ,
            "jpgraph_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("JPGraph Path") ,
                "advanced" => 1
            ) ,
            "fpdf_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("FreePDF Path") ,
                "advanced" => 1
            ) ,
            "xajax_php_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("XAJAX PHP Path") ,
                "advanced" => 1
            ) ,
            "xajax_js_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("XAJAX JS Path") ,
                "advanced" => 1
            ) ,
            "report_graph_type" => array(
                "type" => array(
                    "images" => gettext("Images (php jpgraph)") ,
                    "applets" => gettext("Applets (jfreechart)")
                ) ,
                "help" => "" ,
                "desc" => gettext("Graph Type") ,
                "advanced" => 1
            ) ,
            "use_svg_graphics" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes (Need SVG plugin)")
                ) ,
                "help" => "" ,
                "desc" => gettext("Use SVG Graphics") ,
                "advanced" => 1
            ) ,*/
            "use_resolv" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Resolve IPs") ,
                "section" => "alarms",
                "advanced" => 1
            ) ,
            "ntop_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Default Ntop Link") ,
                "advanced" => 1
            ) ,
            "nagios_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Default Nagios Link") ,
                "advanced" => 1
            ) ,
            "nagios_cfgs" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Nagios Configuration file Path") ,
                "advanced" => 1
            ) ,
            "nagios_reload_cmd" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Nagios reload command") ,
                "advanced" => 1
            ) ,
            /*"glpi_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("GLPI Link") ,
                "advanced" => 1
            ) ,*/
            "ocs_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OCS Link") ,
                "advanced" => 1
            ) ,
            /*"ovcp_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OVCP Link") ,
                "advanced" => 1
            ) ,*/
            "use_ntop_rewrite" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Apache-rewrite ntop") ,
                "advanced" => 1
            ) ,
            "use_munin" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Munin") ,
                "advanced" => 1
            ) ,
            /*"munin_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Munin Link") ,
                "advanced" => 1
            ) ,*/
            "md5_salt" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("MD5 salt for passwords") ,
                "advanced" => 1
            )
        )
    ) ,
    "Ossim FrameworkD" => array(
        "title" => gettext("Ossim Framework Daemon") ,
        "desc" => gettext("Configure the frameworkd's listening address") ,
        "advanced" => 1,
        "conf" => array(
            "frameworkd_address" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSSIM Frameworkd") ,
                "advanced" => 1
            ) ,
            "frameworkd_port" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Frameworkd Port") ,
                "advanced" => 1
            ) ,
            "frameworkd_dir" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Frameworkd Directory") ,
                "advanced" => 1
            ) ,
            "frameworkd_controlpanelrrd" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable ControlPanelRRD") ,
                "advanced" => 1
            ) ,/*
            "frameworkd_acidcache" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable AcidCache") ,
                "advanced" => 1
            ) ,*/
            "frameworkd_donagios" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable DoNagios") ,
                "advanced" => 1
            ) ,
            "frameworkd_alarmincidentgeneration" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable AlarmTicketGeneration") ,
                "advanced" => 1
            ) ,
            "frameworkd_optimizedb" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable DB Optimizations") ,
                "advanced" => 1
            ) ,
            "frameworkd_listener" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Listener") ,
                "advanced" => 1
            ) ,
            "frameworkd_scheduler" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Scheduler") ,
                "advanced" => 1
            ) ,/*
            "frameworkd_soc" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable SOC functionality") ,
                "advanced" => 1
            ) ,*/
            "frameworkd_businessprocesses" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable BusinesProcesses") ,
                "advanced" => 1
            ) ,
            "frameworkd_eventstats" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable EventStats") ,
                "advanced" => 1
            ) ,
            "frameworkd_backup" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Backups") ,
                "advanced" => 1
            ) ,
            "frameworkd_alarmgroup" => array(
                "type" => array(
                    "0" => gettext("Disabled") ,
                    "1" => gettext("Enabled")
                ) ,
                "help" => "" ,
                "desc" => gettext("Enable Alarm Grouping") ,
                "advanced" => 1
            )
        )
    ) ,
    "Snort" => array(
        "title" => gettext("Snort") ,
        "desc" => gettext("Snort database and path configuration") ,
        "advanced" => 1,
        "conf" => array(
            "snort_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort location") ,
                "advanced" => 1
            ) ,
            "snort_rules_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort rule location") ,
                "advanced" => 1
            ) ,
            "snort_type" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort DB Type") ,
                "advanced" => 1
            ) ,
            "snort_base" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort DB Name") ,
                "advanced" => 1
            ) ,
            "snort_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort DB User") ,
                "advanced" => 1
            ) ,
            "snort_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("Snort DB Password") ,
                "advanced" => 1
            ) ,
            "snort_host" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort DB Host") ,
                "advanced" => 1
            ) ,
            "snort_port" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Snort DB Port") ,
                "advanced" => 1
            )
        )
    ) ,
    "Osvdb" => array(
        "title" => gettext("OSVDB") ,
        "desc" => gettext("Open source vulnerability database configuration") ,
        "advanced" => 1,
        "conf" => array(
            "osvdb_type" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSVDB DB Type") ,
                "advanced" => 1
            ) ,
            "osvdb_base" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSVDB DB Name") ,
                "advanced" => 1
            ) ,
            "osvdb_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSVDB DB User") ,
                "advanced" => 1
            ) ,
            "osvdb_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("OSVDB DB Password") ,
                "advanced" => 1
            ) ,
            "osvdb_host" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSVDB DB Host") ,
                "advanced" => 1
            )
        )
    ) ,
    "Metrics" => array(
        "title" => gettext("Metrics") ,
        "desc" => gettext("Configure metric settings") ,
        "advanced" => 0,
    	"section" => "metrics",
        "conf" => array(
            "recovery" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Recovery Ratio") ,
                "advanced" => 0 ,
    			"section" => "metrics"
            ) ,
            "threshold" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Global Threshold") ,
                "advanced" => 0 ,
            	"section" => "metrics"
            )
        )
    ) ,/*
    "Reporting Server and BI" => array(
        "title" => gettext("Reporting Server / Business Intelligence") ,
        "desc" => gettext("Configure BI (JapserServer) settings") ,
        "advanced" => 0,
        "conf" => array(
            "bi_type" => array(
                "type" => array(
                    "jasperserver" => gettext("JasperServer")
                ) ,
                "help" => gettext("Right now only Jasperserver is supported as reporting backend.") ,
                "desc" => gettext("Reporting Server Type") ,
                "advanced" => 0
            ) ,
            "bi_host" => array(
                "type" => "text",
                "help" => gettext("Reporting server ip address, defaults to 'localhost'.") ,
                "desc" => gettext("BI Host") ,
                "advanced" => 1
            ) ,
            "bi_port" => array(
                "type" => "text",
                "help" => gettext("Reporting server port, defaults to 8080.") ,
                "desc" => gettext("BI Port") ,
                "advanced" => 1
            ) ,
            "bi_link" => array(
                "type" => "text",
                "help" => gettext("Reporting server link, defaults to /jasperserver/.") ,
                "desc" => gettext("BI Link") ,
                "advanced" => 1
            ) ,
            "bi_user" => array(
                "type" => "text",
                "help" => gettext("Reporting server user, defaults to 'jasperadmin'.") ,
                "desc" => gettext("BI User") ,
                "advanced" => 1
            ) ,
            "bi_pass" => array(
                "type" => "text",
                "help" => gettext("Reporting server password, default to the one inside /etc/ossim/ossim_setup.conf") ,
                "desc" => gettext("BI Pass") ,
                "advanced" => 1
            )
        )
    ) ,
    "Executive Panel" => array(
        "title" => gettext("Executive Panel") ,
        "desc" => gettext("Configure panel settings") ,
        "advanced" => 1,
    	"section" => "panel",
        "conf" => array(
            "panel_plugins_dir" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Executive Panel plugin Directory") ,
                "advanced" => 1 ,
    			"section" => "panel"
            ) ,
            "panel_configs_dir" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Executive Panel Config Directory") ,
                "advanced" => 1 ,
            	"section" => "panel"
            )
        )
    ) ,*/
    "ACLs" => array(
        "title" => gettext("ACL phpGACL configuration") ,
        "desc" => gettext("Access control list database configuration") ,
        "advanced" => 1,
        "conf" => array(
            "phpgacl_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("PHPGacl Path") ,
                "advanced" => 1
            ) ,
            "phpgacl_type" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("PHPGacl DB Type") ,
                "advanced" => 1
            ) ,
            "phpgacl_host" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("PHPGacl DB Host") ,
                "advanced" => 1
            ) ,
            "phpgacl_base" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("PHPGacl DB Name") ,
                "advanced" => 1
            ) ,
            "phpgacl_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("PHPGacl DB User") ,
                "advanced" => 1
            ) ,
            "phpgacl_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("PHPGacl DB Password") ,
                "advanced" => 1
            )
        )
    ) ,
    "RRD" => array(
        "title" => gettext("RRD") ,
        "desc" => gettext("RRD Configuration (graphing)") ,
        "advanced" => 1,
        "conf" => array(
            "graph_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("RRD Draw graph link") ,
                "advanced" => 1
            ) ,
            "rrdtool_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("RRDTool Path") ,
                "advanced" => 1
            ) ,
            "rrdtool_lib_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("RRDTool Lib Path") ,
                "advanced" => 1
            ) ,
            "mrtg_path" => array(
                "type" => "text",
                "help" => gettext("Unused.") ,
                "desc" => gettext("MRTG Path") ,
                "advanced" => 1
            ) ,
            "mrtg_rrd_files_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("MRTG RRD Files") ,
                "advanced" => 1
            ) ,
            "rrdpath_host" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Host Qualification RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_net" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Net Qualification RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_global" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Global Qualification RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_level" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Service level RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_incidents" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Ticket trend RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_bps" => array(
                "type" => "text",
                "help" => gettext("business processes rrd directory") ,
                "desc" => gettext("BPs RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_ntop" => array(
                "type" => "text",
                "help" => gettext("Defaults to /var/lib/ntop/rrd/") ,
                "desc" => gettext("Ntop RRD Path") ,
                "advanced" => 1
            ) ,
            "rrdpath_stats" => array(
                "type" => "text",
                "help" => gettext("Event Stats RRD directory") ,
                "desc" => gettext("EventStats RRD Path") ,
                "advanced" => 1
            ) ,
            "font_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("TTF Location") ,
                "advanced" => 1
            )
        )
    ) ,
    "Backup" => array(
        "title" => gettext("Backup") ,
        "desc" => gettext("Backup configuration: backup database, directory, interval") ,
        "advanced" => 0,
    	"section" => "siem",
        "conf" => array(
            "backup_type" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Backup DB Type") ,
                "advanced" => 1
            ) ,
            "backup_base" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Backup DB Name") ,
                "advanced" => 1
            ) ,
            "backup_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Backup DB User") ,
                "advanced" => 1
            ) ,
            "backup_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("Backup DB Password") ,
                "advanced" => 1
            ) ,
            "backup_host" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Backup DB Host") ,
                "advanced" => 1
            ) ,
            "backup_port" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Backup DB Port") ,
                "advanced" => 1
            ) ,
            "backup_store" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => gettext("Save SIEM Events backup in files to restore") ,
                "desc" => gettext("Store backups in files") ,
                "advanced" => 1
            ) ,
            "backup_dir" => array(
                "type" => "text",
                "help" => gettext("Defaults to /var/lib/ossim/backup/") ,
                "desc" => gettext("Backup File Directory") ,
                "advanced" => 1
            ) ,
            "backup_day" => array(
                "type" => "text",
                "help" => gettext("How many days in the past do you want to keep Events in forensics?") ,
                "desc" => gettext("Active Event Window (days)") ,
            	"section" => "siem",
                "advanced" => 0
            ) ,
            "backup_events" => array(
                "type" => "text",
                "help" => gettext("Maximum number of events stored in SQL Database") ,
                "desc" => gettext("Active Event Window (events)") ,
            	"section" => "siem",
                "advanced" => 0
            ) ,            
            "backup_netflow" => array(
                "type" => "text",
                "help" => gettext("How many days in the past do you want to keep Flows in Netflows?") ,
                "desc" => gettext("Active Netflow Window") ,
                "advanced" => 0
            )
        )
    ) ,
    "Vulnerability Scanner" => array(
        "title" => gettext("Vulnerability Scanner") ,
        "desc" => gettext("Vulnerability Scanner configuration") ,
        "advanced" => 0,
    	"section" => "vulnerabilities",
        "conf" => array(
            "scanner_type" => array(
                "type" => array(
                    "openvas3omp" => gettext("OpenVAS 3.x (OpenVAS Manager)") ,
                    "openvas3" => gettext("OpenVAS 3.x") ,
                    "openvas2" => gettext("OpenVAS 2.x") ,
                    "nessus2" => gettext("Nessus 2.x") ,
                    "nessus3" => gettext("Nessus 3.x") ,
                    "nessus4" => gettext("Nessus 4.x")
                ) ,
                "help" => gettext("Vulnerability scanner used. OpenVAS is used by default.") ,
                "desc" => gettext("Vulnerability Scanner") ,
                "advanced" => 1 ,
                "section" => "vulnerabilities"
            ) ,
            "nessus_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Scanner Login") ,
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("Scanner Password") , 
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_host" => array(
                "type" => "text",
                "help" => gettext("Only for non distributed scans") ,
                "desc" => gettext("Scanner host") ,
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_port" => array(
                "type" => "text",
                "help" => gettext("Defaults to port 1241 on Nessus, 9390 on OpenVAS") ,
                "desc" => gettext("Scanner port") ,
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Scanner Binary location") ,
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_updater_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Scanner Updater location") , 
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            "nessus_rpt_path" => array(
                "type" => "text",
                "help" => gettext("Where will scanning results be located") ,
                "desc" => gettext("Scan output path") ,
                "advanced" => 1 ,
            	"section" => "vulnerabilities"
            ) ,
            /*"nessusrc_path" => array(
                "type" => "text",
                "help" => gettext("Configuration (.rc) file") ,
                "desc" => gettext("Configuration file location") ,
                "advanced" => 0
            ) ,*/
            "nessus_distributed" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => gettext("Obsolete, distributed is very recommended even if you only got one sensor.") ,
                "desc" => gettext("Distributed Scanning") ,
                "advanced" => 1 ,
                "section" => "vulnerabilities"
            ) ,
            "nessus_pre_scan_locally" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => gettext("do not pre-scan from scanning sensor") ,
                "desc" => gettext("Enable Pre-Scan locally") ,
                "advanced" => 1 ,
                "section" => "vulnerabilities"
            ) ,
            "vulnerability_incident_threshold" => array(
                "type" => array(
                    "0" => "0",
                    "1" => "1",
                    "2" => "2",
                    "3" => "3",
                    "4" => "4",
                    "5" => "5",
                    "6" => "6",
                    "7" => "7",
                    "8" => "8",
                    "9" => "9",
                    "11" => _("Disabled")
                ) ,
                "help" => gettext("Any vulnerability with a higher risk level than this value will get inserted automatically into DB.") ,
                "desc" => gettext("Vulnerability Ticket Threshold") ,
                "advanced" => 0 ,
                "section" => "vulnerabilities"
            )
        )
    ) ,/*
    "Acid/Base" => array(
        "title" => gettext("ACID/BASE") ,
        "desc" => gettext("Acid and/or Base configuration") ,
        "advanced" => 1,
        "conf" => array(
            "event_viewer" => array(
                "type" => array(
                    "acid" => gettext("Acid") ,
                    "base" => gettext("Base")
                ) ,
                "help" => gettext("Choose your event viewer") ,
                "desc" => gettext("Event Viewer") ,
                "advanced" => 1
            ) ,
            "acid_link" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Event viewer link") ,
                "advanced" => 1
            ) ,
            "acid_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Event viewer php path") ,
                "advanced" => 1
            ) ,
            "acid_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Frontend login for event viewer") ,
                "advanced" => 1
            ) ,
            "acid_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("Frontend password for event viewer") ,
                "advanced" => 1
            ) ,
            "ossim_web_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("OSSIM Web user") ,
                "advanced" => 1
            ) ,
            "ossim_web_pass" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("OSSIM Web Password") ,
                "advanced" => 1
            )
        )
    ) ,*/
    "External Apps" => array(
        "title" => gettext("External applications") ,
        "desc" => gettext("Path to other applications") ,
        "advanced" => 1,
        "conf" => array(
            "nmap_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("NMap Binary Path") ,
                "advanced" => 1
            ) ,/*
            "p0f_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("P0f Binary Path") ,
                "advanced" => 1
            ) ,
            "arpwatch_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Arpwatch Binary Path") ,
                "advanced" => 1
            ) ,
            "mail_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Mail Binary Path") ,
                "advanced" => 1
            ) ,
            "touch_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("'touch' Binary Path") ,
                "advanced" => 1
            ) ,
            "wget_path" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Wget Binary Path") ,
                "advanced" => 1
            ) ,*/
            "have_scanmap3d" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Use Scanmap 3D") ,
                "advanced" => 0
            )
        )
    ) ,
    "User Log" => array(
        "title" => gettext("User activity") ,
        "desc" => gettext("User action logging") ,
        "advanced" => 0,
    	"section" => "userlog",
        "conf" => array(
            "session_timeout" => array(
                "type" => "text",
                "help" => gettext("Expired timeout for current session in minutes. (0=unlimited)") ,
                "desc" => gettext("Session Timeout") ,
                "advanced" => 1 ,
    			"section" => "userlog"
            ),
            "user_action_log" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "",
                "desc" => gettext("Enable User Log") ,
                "advanced" => 0 ,
                "section" => "userlog"
            ) ,
            "log_syslog" => array(
                "type" => array(
                    "0" => gettext("No") ,
                    "1" => gettext("Yes")
                ) ,
                "help" => "" ,
                "desc" => gettext("Log to syslog") ,
                "advanced" => 0 ,
                "section" => "userlog"
            )
        )
    ) ,
    /*
    "Event Viewer" => array(
        "title" => gettext("Real time event viewer") ,
        "desc" => gettext("Real time event viewer") ,
        "advanced" => 1,
        "conf" => array(
            "max_event_tmp" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Event limit for real time event viewer") ,
                "advanced" => 1
            )
        )
    ) ,*/
    "Login" => array(
        "title" => gettext("Login methods/options") ,
        "desc" => gettext("Setup main login methods/options") ,
        "advanced" => 0,
    	"section" => "users",
        "conf" => array(
            "first_login" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => "",
                "desc" => gettext("Show welcome message at next login") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "customize_wizard" => array(
                "type" => array(
                    "1" => _("Yes") ,
                    "0" => _("No")
                ) ,
                "help" => "",
                "desc" => gettext("Show Customization Wizard after admin login") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,            
            "login_enforce_existing_user" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => "",
                "desc" => gettext("Require a valid ossim user for login") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "login_enable_ldap" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => "",
                "desc" => gettext("Enable LDAP for login") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "login_ldap_server" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("Ldap server address") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "login_ldap_cn" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("LDAP CN") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "login_ldap_o" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("LDAP O") ,
                "advanced" => 0 ,
                "section" => "users"
            ) ,
            "login_ldap_ou" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("LDAP OU") ,
                "advanced" => 0 ,
                "section" => "users"
            )
        )
    ) ,
    "Passpolicy" => array(
        "title" => gettext("Password policy") ,
        "desc" => gettext("Setup login password policy options") ,
        "advanced" => 1,
        "section" => "users",
        "conf" => array(
			"pass_length_min" => array(
                "type" => "text",
                "help" => _("Number (default = 7)") ,
                "desc" => gettext("Minimum password lenght") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
            "pass_length_max" => array(
                "type" => "text",
                "help" => _("Number (default = 32)") ,
                "desc" => gettext("Maximum password lenght") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
            "pass_history" => array(
                "type" => "text",
                "help" => _("Number (default = 0) -> 0 disable") ,
                "desc" => gettext("Password history") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
            "pass_complex" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => _("3 of these group of characters -> lowercase, uppercase, numbers, special characters") ,
                "desc" => gettext("Complexity") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
        	"pass_expire_min" => array(
                "type" => "text",
                "help" => _("The minimum password lifetime prevents users from circumventing")."<br/>"._("the requirement to change passwords by doing five password changes<br> in a minute to return to the currently expiring password. (0 to disable) (default 0)") ,
                "desc" => gettext("Minimum password lifetime in minutes") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
        	"pass_expire" => array(
                "type" => "text",
                "help" => _("After these days the login ask for new password. (0 to disable) (default 0)") ,
                "desc" => gettext("Maximum password lifetime in days") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
			"failed_retries" => array(
                "type" => "text",
                "help" => _("Number of failed attempts prior to lockout") ,
                "desc" => gettext("Failed logon attempts") ,
                "advanced" => 1 ,
                "section" => "users"
            ),
			"unlock_user_interval" => array(
                "type" => "text",
                "help" => _("Account lockout duration in minutes (0 = never auto-unlock)") ,
                "desc" => gettext("Account lockout duration") ,
                "advanced" => 1 ,
                "section" => "users"
            )
        )
    ) ,
    "Updates" => array(
        "title" => gettext("Updates") ,
        "desc" => gettext("Configure updates") ,
        "advanced" => 0,
        "conf" => array(
            "update_checks_enable" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => gettext("The system will check once a day for updated packages, rules, directives, etc.")."<br/>"._("No system information will be sent, it just gets a file with dates and update messages using wget.") ,
                "desc" => gettext("Enable auto update-checking") ,
                "advanced" => 0
            ) ,
            "update_checks_use_proxy" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => "" ,
                "desc" => gettext("Use proxy for auto update-checking") ,
                "advanced" => 1
            ) ,
            "proxy_url" => array(
                "type" => "text",
                "help" => gettext("Enter the full path including a trailing slash, i.e., 'http://192.168.1.60:3128/'") ,
                "desc" => gettext("Proxy url") ,
                "advanced" => 1
            ) ,
            "proxy_user" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Proxy User") ,
                "advanced" => 1
            ) ,
            "proxy_password" => array(
                "type" => "password",
                "help" => "" ,
                "desc" => gettext("Proxy Password") ,
                "advanced" => 1
            ) ,
            "last_update" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Last update timestamp") ,
                "advanced" => 1
            ) ,
        )
    ) ,
    "IncidentGeneration" => array(
        "title" => gettext("Tickets") ,
        "desc" => gettext("Tickets parameters") ,
        "advanced" => 0,
    	"section" => "tickets,alarms",
        "conf" => array(
            "alarms_generate_incidents" => array(
                "type" => array(
                    "yes" => _("Yes") ,
                    "no" => _("No")
                ) ,
                "help" => gettext("Enabling this option will lead to automatic ticket generation upong arrival of alarms.") ,
                "desc" => gettext("Open Tickets for new alarms automatically?") ,
                "section" => "tickets,alarms",
                "advanced" => 0
            ) ,
            "tickets_max_days" => array(
                "type" => "text",
                "help" => "" ,
                "desc" => gettext("Maximum days for email notification") ,
                "advanced" => 0 ,
            	"section" => "tickets"
            ),
            "google_maps_key" => array(
                "type" => "textarea",
                "help" => gettext("http://code.google.com/apis/maps/signup.html") ,
                "desc" => gettext("Google Maps API Key") ,
            	"section" => "tickets",
                "advanced" => 0
            )            
        )
    ) ,

	"Action responses" => array(
        "title" => gettext("Action Responses") ,
        "desc" => gettext("Setup action responses") ,
        "advanced" => 1,
    	"section" => "actions",
        "conf" => array(
            "dc_ip" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("Domain Controller IP") ,
    			"section" => "actions" ,
                "advanced" => 1
            ) ,
            "dc_acc" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("Admin Account") ,
            	"section" => "actions" ,
                "advanced" => 1
            ) ,
            "dc_pass" => array(
                "type" => "password",
                "help" => "",
                "desc" => gettext("Password") ,
            	"section" => "actions" ,
                "advanced" => 1
            ) ,
            "snmp_comm" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("Network SNMP Community") ,
            	"section" => "actions" ,
                "advanced" => 1
            )
		)
	),
	"Policy" => array(
        "title" => gettext("Policy") ,
        "desc" => gettext("Policy settings") ,
        "advanced" => 1,
        "conf" => array()
	),
    "Mail Server Configuration" => array(
        "title" => gettext("Mail Server Configuration") ,
        "desc" => gettext("Mail Server Configuration settings") ,
        "advanced" => 1,
        "conf" => array(
            "from" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("From Address") ,
                "advanced" => 1
            ) ,
            "smtp_server_address" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("SMTP Server IP Address") ,
                "advanced" => 1
            ) ,
            "smtp_port" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("SMTP Server port") ,
                "advanced" => 1
            ) ,
            "smtp_user" => array(
                "type" => "text",
                "help" => "",
                "desc" => gettext("SMTP Username") ,
                "advanced" => 1
            ) ,
            "smtp_pass" => array(
                "type" => "password",
                "help" => "",
                "desc" => gettext("SMTP Password") ,
                "advanced" => 1
            ) ,
            "use_ssl" => array(
                "type" => array(
                    "yes" => _("Yes"),
                    "no" => _("No")
                ),
                "help" => "",
                "desc" => gettext("Use SSL Protocol") ,
                "advanced" => 1
            )
        )
    )
);

ksort($CONFIG);

function valid_value($key, $value, $numeric_values)
{
    if (in_array($key, $numeric_values)) {
        if (!is_numeric($value)) {
            require_once ("ossim_error.inc");
            $error = new OssimError();
            $error->display("NOT_NUMERIC", array(
                $key
            ));
        }
    }
    return true;
}

function submit()
{
	?>
		<!-- submit -->
		
		<input type="submit" name="update" class="button" value=" <?php echo gettext("Update configuration"); ?> "/>
		<br/><br/>
		<!-- end sumbit -->
	<?php
}
if (POST('update'))
{
    $numeric_values = array(
        "server_port",
        "use_resolv",
        "use_ntop_rewrite",
        "use_munin",
        "frameworkd_port",
        "frameworkd_controlpanelrrd",
        "frameworkd_donagios",
        "frameworkd_alarmincidentgeneration",
        "frameworkd_optimizedb",
        "frameworkd_listener",
        "frameworkd_scheduler",
        "frameworkd_businessprocesses",
        "frameworkd_eventstats",
        "frameworkd_backup",
        "frameworkd_alarmgroup",
        "snort_port",
        "recovery",
        "threshold",
        "backup_port",
        "backup_day",
        "nessus_port",
        "nessus_distributed",
        "vulnerability_incident_threshold",
        "have_scanmap3d",
        "user_action_log",
        "log_syslog",
        "pass_length_min",
        "pass_length_max",
        "pass_history",
        "pass_expire_min",
        "pass_expire",
        "failed_retries",
        "unlock_user_interval",
        "tickets_max_days",
        "smtp_port"
    );
        
    require_once 'classes/Config.inc';
    
	$config = new Config();
    
	for ($i = 0; $i < POST('nconfs'); $i++)
	{
        if(POST("conf_$i") == "pass_length_max")
		{
            $pass_length_max = POST("value_$i");
            continue;
        }
		if(POST("conf_$i") == "pass_expire")
		{
            $pass_expire_max = POST("value_$i");
        }
		if(POST("conf_$i") == "pass_expire_min")
		{
            $pass_expire_min = POST("value_$i");
        }
        
		if(in_array(POST("conf_$i"), $numeric_values) && (POST("value_$i")=="" || intval(POST("value_$i"))<0 ))
		{
            $_POST["value_$i"] = 0;
        }
        
        if(POST("conf_$i") == "pass_length_min")
		{
            if (POST("value_$i")<1) {
                $_POST["value_$i"] = 7;
            }
            $pass_length_min = POST("value_$i");
        }
		
        ossim_valid(POST("value_$i"), OSS_ALPHA, OSS_NULLABLE, OSS_SCORE, OSS_DOT, OSS_PUNC, "\{\}\|;", 'illegal:' . POST("conf_$i")); 
        
		if (ossim_error()) {
           die(ossim_error()); 
        }
        
		if (valid_value(POST("conf_$i") , POST("value_$i"), $numeric_values))
		{
            if (!$ossim_conf->is_in_file(POST("conf_$i"))) {
                $before_value = $ossim_conf->get_conf(POST("conf_$i"),false); 
                $config->update(POST("conf_$i") , POST("value_$i"));
                if (POST("value_$i") != $before_value) Log_action::log(7, array("variable: ".POST("conf_$i")));
                //echo POST("conf_$i")."---->";
                //echo POST("value_$i")."<br><br>";
                
            }
        }
    }
    
    // check valid pass lenght max
    if(intval($pass_length_max) < intval($pass_length_min) || intval($pass_length_max) < 1 || intval($pass_length_max) > 255 )
	{
        $config->update("pass_length_max" , 255);
    }
    else
	{
        $config->update("pass_length_max" , intval($pass_length_max));
    }
    // check valid expire min - max
    if ($pass_expire_max * 60 * 24 < $pass_expire_min) {
    	$config->update("pass_expire_min" , 0);
    }

    /*  $infolog = array(
        $_SESSION['_user']
    );
    Log_action::log(7, $infolog);*/
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "?adv=" . POST('adv') . "&word=" . POST('word') . "&section=" . POST('section'));
    exit;
}

if (REQUEST("reset"))
{
    if (!(GET('confirm'))) {
	?>
        <p align="center">
			<b><?php echo gettext("Are you sure ?") ?></b><br/>
			<a href="?reset=1&confirm=1"><?php echo gettext("Yes") ?></a>&nbsp;|&nbsp;
			<a href="main.php"><?php echo gettext("No") ?></a>
        </p>
<?php
        exit;
    }
	
    require_once 'classes/Config.inc';
    $config = new Config();
    $config->reset();
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "?adv=" . POST('adv') . "&word=" . POST('word') . "&section=" . POST('section'));
    exit;
}

$default_open = intval(GET('open'));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Advanced Configuration"); ?> </title>
	<META http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/style.css"/>
	<script src="../js/jquery-1.3.2.min.js" type="text/javascript" ></script>
	<script src="../js/accordian.js" type="text/javascript" ></script>
	<style type="text/css">
		#basic-accordian{
			padding:0px;
			align:center;
			width:450px;
		}

		.accordion_headings {
			height:24px; line-height:22px;
			cursor:pointer;
			padding-left:5px; padding-right:5px; margin-bottom:2px;
			font-family:arial; font-size:12px; color:#0E3C70; font-weight:bold; text-decoration:none
		}

		.accordion_headings:hover {
		}

		.accordion_child {
			padding-left:5px;
			padding-right:5px;
			padding-bottom:5px
		}
		.header_highlight {
		}
		.semiopaque { opacity:0.9; MozOpacity:0.9; KhtmlOpacity:0.9; filter:alpha(opacity=90); background-color:#B5C3CF }
		
		.m_nobborder { border: none; background: none; }
	</style>
	
	<script type='text/javascript'>
		var IE = document.all ? true : false
		if (!IE) document.captureEvents(Event.MOUSEMOVE)
		document.onmousemove = getMouseXY;
		var tempX = 0;
		var tempY = 0;

		var difX = 15;
		var difY = 0; 

		function getMouseXY(e)
		{
			if (IE) { // grab the x-y pos.s if browser is IE
					tempX = event.clientX + document.body.scrollLeft + difX
					tempY = event.clientY + document.body.scrollTop + difY 
			} else {  // grab the x-y pos.s if browser is MOZ
					tempX = e.pageX + difX
					tempY = e.pageY + difY
			}  
			if (tempX < 0){tempX = 0}
			if (tempY < 0){tempY = 0}
			
			var dh = document.body.clientHeight+ window.scrollY;
			if (document.getElementById("numeroDiv").offsetHeight+tempY > dh)
				tempY = tempY - (document.getElementById("numeroDiv").offsetHeight + tempY - dh)
			document.getElementById("numeroDiv").style.left = tempX+"px";
			document.getElementById("numeroDiv").style.top = tempY+"px"; 
			return true
		}
	
		function ticketon(name,desc)
		{ 
			
			if (document.getElementById) {
				var txt1 = '<table border=0 cellpadding=8 cellspacing=0 class="semiopaque"><tr><td class=nobborder style="line-height:18px;width:300px" nowrap><b>'+ name +'</b><br>'+ desc +'</td></tr></table>'
				document.getElementById("numeroDiv").innerHTML = txt1
				document.getElementById("numeroDiv").style.display = ''
				document.getElementById("numeroDiv").style.visibility = 'visible'
			}
		}

		function ticketoff()
		{
			if (document.getElementById) {
				document.getElementById("numeroDiv").style.visibility = 'hidden'
				document.getElementById("numeroDiv").style.display = 'none'
				document.getElementById("numeroDiv").innerHTML = ''
			}
		}
	
		// show/hide some options
		<?php
		if ($ossim_conf->get_conf("server_sem", FALSE) == "yes")
			echo "var valsem = 1;";
		else 
			echo "var valsem = 0;";

		if ($ossim_conf->get_conf("server_sim", FALSE) == "yes") 
			echo "var valsim = 1;";
		else 
			echo "var valsim = 0;";
		?>

		function enableall()
		{
			tsim("yes")
			tsem("yes")
		}
	
		$(document).ready(function(){	
			<?php if (GET('section') == "" && POST('section') == "") { ?>
			new Accordian('basic-accordian',5,'header_highlight');
			<?php } ?>
			// enable/disable by default
			$('input:hidden').each(function(){
				if ($(this).val()=='server_sim') {
					var idi = $(this).attr('name').substr(5);
					tsim($("select[name='value_"+idi+"']").val());
				}
				if ($(this).val()=='server_sem') {
					var idi = $(this).attr('name').substr(5);
					tsem($("select[name='value_"+idi+"']").val());
				}
			});
			
			$('.conf_items').each(function(index) {
				$(this).find("tr:last td").css('border', 'none');
			 });

			<?	if (intval(GET('passpolicy'))==1)  { ?>
			$('#test14-header').click(); 
			<?  }  ?>
			
			<?	if ($default_open>0)  { ?>
			$('#test<?=$default_open?>-header').click(); 
			<?  }  ?>
			
		});
		
		function tsim(val)
		{
			if (val == "yes") valsim = 1;
			else valsim = 0;
			document.getElementById('correlate_select').disabled = false;
			document.getElementById('cross_correlate_select').disabled = false;
			document.getElementById('store_select').disabled = false;
			document.getElementById('qualify_select').disabled = false;
			$('#correlate_select').css('color','black');
			$('#cross_correlate_select').css('color','black');
			$('#store_select').css('color','black');
			$('#qualify_select').css('color','black');
			
			if (valsim==0)
			{
				document.getElementById('correlate_select').disabled = true;
				document.getElementById('cross_correlate_select').disabled = true;
				document.getElementById('store_select').disabled = true;
				document.getElementById('qualify_select').disabled = true;
				$('#correlate_select').css('color','gray');
				$('#cross_correlate_select').css('color','gray');
				$('#store_select').css('color','gray');
				$('#qualify_select').css('color','gray');
				//document.getElementById('correlate_select').selectedIndex = 1;
				//document.getElementById('cross_correlate_select').selectedIndex = 1;
				//document.getElementById('store_select').selectedIndex = 1;
				//document.getElementById('qualify_select').selectedIndex = 1;
			}
			
			if (valsim==0 && valsem==0)
			{
				document.getElementById('forward_alarm_select').disabled = true;
				document.getElementById('forward_event_select').disabled = true;
				$('#forward_alarm_select').css('color','gray');
				$('#forward_event_select').css('color','gray');
				//document.getElementById('forward_alarm_select').selectedIndex = 1;
				//document.getElementById('forward_event_select').selectedIndex = 1;
			} 
			else
			{
				<? if (preg_match("/pro|demo/",$conf->get_conf("ossim_server_version", FALSE))) { ?>
				document.getElementById('forward_alarm_select').disabled = false;
				document.getElementById('forward_event_select').disabled = false;
				$('#forward_alarm_select').css('color','black');
				$('#forward_event_select').css('color','black');
				<? } ?>
			}
		}
	
	function tsem(val)
		{
			if (val == "yes") 
				valsem = 1;
			else 
				valsem = 0;
			
			document.getElementById('sign_select').disabled = false;
			$('#sign_select').css('color','black');
			
			if (valsem==0)
			{
				document.getElementById('sign_select').disabled = true;
				$('#sign_select').css('color','gray');
				//document.getElementById('sign_select').selectedIndex = 1;
			}
			if (valsim==0 && valsem==0)
			{
				document.getElementById('forward_alarm_select').disabled = true;
				document.getElementById('forward_event_select').disabled = true;
				$('#forward_alarm_select').css('color','gray');
				$('#forward_event_select').css('color','gray');
				//document.getElementById('forward_alarm_select').selectedIndex = 1;
				//document.getElementById('forward_event_select').selectedIndex = 1;
			} 
			else
			{
				document.getElementById('forward_alarm_select').disabled = false;
				document.getElementById('forward_event_select').disabled = false;
				$('#forward_alarm_select').css('color','black');
				$('#forward_event_select').css('color','black');
			}
		}

		function setvalue(id,val,checked)
		{
			var current = document.getElementById(id).value;
			current = current.replace(val,"");
			if (checked) current += val;
			document.getElementById(id).value = current;
		}
	</script>

</head>

<body>
	<div id="numeroDiv" style="position:absolute; z-index:999; left:0px; top:0px; height:80px; visibility:hidden; display:none"></div>
	<?php
	$advanced = (POST('adv') == "1") ? true : ((GET('adv') == "1") ? true : false);
	$section = (POST('section') != "") ? POST('section') : GET('section');
	//$links = ($advanced) ? "<a href='main.php' style='color:#cccccc'>simple</a> | <b>advanced</b>" : "<b>simple</b> | <a href='main.php?adv=1' style='color:#cccccc'>advanced</a>";
	//$title = ($advanced) ? "Advanced" : "Main";
	if ($section == "") {
		include ("../hmenu.php");
	}

	$onsubmit = ( GET('adv') == '1' ) ? "onsubmit='enableall();'" : "";
	?>
  
	<form method="POST" style="margin:0 auto" <?php echo $onsubmit;?> action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" />
  
	<table align='center'>
	
	<tr>
		<td class="noborder">
			<div id="basic-accordian" align="center">
				<?php
				$count  = 0;
				$div    = 0;
				$found  = 0;
				$arr    = array();
												
				foreach($CONFIG as $key => $val) 
					if ($advanced || ($section == "" && !$advanced && $val["advanced"] == 0) || ($section != "" && preg_match("/$section/",$val['section'])))
					{
						$s = (POST('word') != "") ? POST('word') : ((GET('word') != "") ? GET('word') : "");
						
						if ($s != "")
						{
							foreach($val["conf"] as $conf => $type) 
								if ($advanced || ($section == "" && !$advanced && $type["advanced"] == 0) || ($section != "" && preg_match("/$section/",$type['section'])))
								{
									if (preg_match("/$s/i", $conf))
									{
										$found = 1;
										array_push($arr, $conf);
									}
								}
						}
					?>
			
					<div id="test<?php
						if ($div > 0) echo $div ?>-header" class="accordion_headings <?php
						if ($found == 1) echo "header_highlight" ?>">

						<table width="100%" cellspacing="0" class='m_nobborder'>
							<tr>
								<th  <?php
										if ($found == 1) echo "style='background-color: #F28020; color: #FFFFFF'" ?>>
										<?php echo $val["title"] ?>
								</th>
							</tr>
						</table>
					</div>
  
					<div id="test<?php
						if ($div > 0) echo $div ?>-content">
						<div class="accordion_child">
							<table class='conf_items' cellpadding='3' align="center">
							<?php
								//print "<tr><th colspan=\"2\">" . $val["title"] . "</th></tr>";
								print "<tr><td colspan='3'>" . $val["desc"] . "</td></tr>";
								if ($advanced && $val["title"]=="RRD") {
							?>
							
								<tr>
									<td colspan="3" align="center">
										<input type="button" onclick="document.location.href='../rrd_conf/rrd_conf.php'" value="<?php echo _("RRD Profiles definition") ?>" class="button"/> 
									</td>
								</tr>
							
							<?php
								}
							
							if ($advanced && $val["title"]=="Policy")
							{
							?>
								<tr>
									<td colspan="3" align="center" class='nobborder'>
										<input type="button" onclick="document.location.href='../policy/reorderpolicies.php'" value="<?php echo _("Re-order Policies") ?>" class="button"/> 
									</td>
								</tr>
								<?php
							}
							foreach($val["conf"] as $conf => $type) 
							{
								if ($advanced || ($section == "" && !$advanced && $type["advanced"] == 0) || ($section != "" && preg_match("/$section/",$type['section'])))
								{
									//var_dump($type["type"]);
									$conf_value = $ossim_conf->get_conf($conf,false);
									$var = ($type["desc"] != "") ? $type["desc"] : $conf;
									
																										
									
							?>
								<tr <?php
									if (in_array($conf, $arr)) echo "bgcolor=#FE9B52" ?>>

									<input type="hidden" name="conf_<?php echo $count ?>" value="<?php echo $conf ?>" />
									
									<td><b><?php echo $var ?></b></td>
									
									<td class="left">
										<?php
											$input = "";
											
											$disabled = ($type["disabled"] == 1 || $ossim_conf->is_in_file($conf)) ? "class='disabled' style='color:gray' disabled='disabled'" : "";
											
											/* select */
											if (is_array($type["type"]))
											{
												// Multiple checkbox
												if ($type['checkboxlist'])
												{
													$input .= "<input type='hidden' name='value_$count' id='".$type['id']."' value='$conf_value'/>";
													foreach($type["type"] as $option_value => $option_text)
													{
														$input.= "<input type='checkbox' onclick=\"setvalue('".$type['id']."',this.value,this.checked);\"";
														if (preg_match("/$option_value/",$conf_value)) 
															$input.= " checked='checked' ";
														
														$input.= "value='$option_value'/>$option_text<br/>";
													}
												// Select combo
												} 
												else
												{
													$select_change = ($type['onchange'] != "") ? "onchange=\"".$type['onchange']."\"" : "";
													$select_id = ($type['id'] != "") ? "id=\"".$type['id']."\"" : "";
													$input.= "<select name='value_$count' $select_change $select_id $disabled>";
													
													if ($conf_value == "") 
														$input.= "<option value=''></option>";
													
													foreach($type["type"] as $option_value => $option_text)
													{
														$input.= "<option ";
														if ($conf_value == $option_value) 
															$input.= " selected='selected' ";
														
														$input.= "value='$option_value'>$option_text</option>";
													}
													
													$input.= "</select>";
												}
											}
											/* textarea */
											elseif ($type["type"]=="textarea")
											{
												$input.= "<textarea rows='2' cols='28' name=\"value_$count\" $disabled>$conf_value</textarea>";
											}
											/* link */
											elseif ($type["type"]=="link")
											{
												$input.= "";
											}
											/* input */
											else
											{
												$input.= "<input ";
												//if ($ossim_conf->is_in_file($conf)) {
												//   $input .= " class=\"disabled\" ";
												//    $input .= " DISABLED ";
												//}
												$input.= "type='" . $type["type"] . "' size='30' name='value_$count' value='$conf_value' $disabled/>";
											}
										
												echo $input;
											
										?>
										</td>
					
										<td align="left">
											<a href="javascript:;" onmouseover="ticketon('<?php echo str_replace("'", "\'", $var) ?>','<?php echo str_replace("\n"," ",str_replace("'", "\'", $type["help"])) ?>')"  onmouseout="ticketoff();">
												<img src="../pixmaps/help.png" width="16" border='0'/>
											</a>
										</td>

									</tr>
									
									<?php
									$count+= 1;
								}
							}
							?>
							</table>
				
							</div>
						</div>
						<?php
						$div++;
						$found = 0;
					}
					?>
				</div>
		  
			</td>
			
			<td valign='top' class="noborder">
				<?php submit(); ?> 
				
				<?php echo _("Find word:");?><input type="text" name="word" value="<?php echo $s ?>"/>
				<br/><br/>
				<input type='hidden' name="adv" value="<?php echo ($advanced) ? "1" : "" ?>"/>
				<input type='hidden' name="section" value="<?php echo $section ?>"/>
				<input type="submit" value="<?=_('search')?>" class="button"/>
				<input type="hidden" name="nconfs" value="<?php echo $count ?>"/>
			</td>
		</tr>
	</table>
</form>
<a name="end"></a>
</body>
</html>
