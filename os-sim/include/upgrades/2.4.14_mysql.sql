use ossim;
SET AUTOCOMMIT=0;
BEGIN;

DROP TABLE IF EXISTS `perms1`;    
CREATE TABLE IF NOT EXISTS `perms1` (
  `ac_templates_id` int(11) NOT NULL,
  `ac_perm_id` int(11) NOT NULL,
  PRIMARY KEY  (`ac_templates_id`,`ac_perm_id`)
);
INSERT INTO perms1 SELECT * FROM acl_templates_perms;
DROP TABLE acl_templates_perms;
RENAME TABLE perms1 TO acl_templates_perms;
DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =6;
DELETE FROM `acl_perm` WHERE `id` =6;
DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =52;
DELETE FROM `acl_perm` WHERE `id` =52;
DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =45;
DELETE FROM `acl_perm` WHERE `id` =45;
REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(1, 'MENU', 'MenuControlPanel', 'ControlPanelExecutive', 'Dashboard -> Main', 1, 1, 1, '01.01'),
(2, 'MENU', 'MenuControlPanel', 'ControlPanelAlarms', '', 1, 0, 0, '0'),
(3, 'MENU', 'MenuControlPanel', 'ControlPanelExecutiveEdit', 'Dashboard -> Executive Panel Edit', 1, 1, 1, '01.02'),
(4, 'MENU', 'MenuControlPanel', 'ControlPanelMetrics', 'Dashboard -> Metrics', 1, 1, 1, '01.06'),
(5, 'MENU', 'MenuControlPanel', 'ControlPanelEvents', '', 0, 0, 0, '0'),
(7, 'MENU', 'MenuControlPanel', 'ControlPanelAnomalies', '', 0, 0, 0, '0'),
(8, 'MENU', 'MenuControlPanel', 'ControlPanelHids', '', 0, 0, 0, '0'),
(9, 'MENU', 'MenuIntelligence', 'PolicyPolicy', 'Intelligence -> Policy', 1, 1, 1, '06.01'),
(10, 'MENU', 'MenuPolicy', 'PolicyHosts', 'Assets -> Assets -> Hosts', 1, 0, 1, '05.02'),
(11, 'MENU', 'MenuPolicy', 'PolicyNetworks', 'Assets -> Assets -> Networks', 0, 1, 1, '05.03'),
(12, 'MENU', 'MenuConfiguration', 'PolicySensors', 'Configuration -> SIEM Components -> Sensors', 1, 0, 1, '08.04'),
(13, 'MENU', 'MenuPolicy', 'PolicySignatures', '', 0, 0, 0, '0'),
(14, 'MENU', 'MenuPolicy', 'PolicyPorts', 'Assets -> Assets -> Ports', 0, 0, 1, '05.04'),
(15, 'MENU', 'MenuIntelligence', 'PolicyActions', 'Intelligence -> Actions', 0, 0, 1, '06.02'),
(16, 'MENU', 'MenuPolicy', 'PolicyResponses', '', 0, 0, 0, '0'),
(17, 'MENU', 'MenuConfiguration', 'PluginGroups', 'Configuration -> Collection -> DS Groups', 0, 0, 1, '08.07'),
(18, 'MENU', 'MenuReports', 'ReportsHostReport', 'Reports -> Asset Report', 1, 1, 1, '04.01'),
(19, 'MENU', 'MenuIncidents', 'ReportsAlarmReport', 'Incidents -> Alarms -> Reports', 1, 0, 1, '02.02'),
(20, 'MENU', 'MenuReports', 'ReportsSecurityReport', 'Incidents -> Alarms -> Reports', 1, 0, 0, '0'),
(21, 'MENU', 'MenuReports', 'ReportsPDFReport', '', 1, 0, 0, '0'),
(22, 'MENU', 'MenuIncidents', 'IncidentsIncidents', 'Incidents -> Tickets', 1, 0, 1, '02.03'),
(23, 'MENU', 'MenuIncidents', 'IncidentsTypes', 'Incidents -> Tickets -> Types', 0, 0, 1, '02.07'),
(24, 'MENU', 'MenuIncidents', 'IncidentsReport', 'Incidents -> Tickets -> Report', 1, 0, 1, '02.06'),
(25, 'MENU', 'MenuIncidents', 'IncidentsTags', 'Incidents -> Tickets -> Tags', 0, 0, 1, '02.08'),
(26, 'MENU', 'MenuMonitors', 'MonitorsSession', '', 0, 0, 0, '0'),
(27, 'MENU', 'MenuMonitors', 'MonitorsNetwork', 'Monitors -> Network -> Profiles', 1, 0, 1, '07.02'),
(28, 'MENU', 'MenuMonitors', 'MonitorsAvailability', 'Monitors -> Availability', 0, 0, 1, '07.03'),
(29, 'MENU', 'MenuStatus', 'MonitorsSensors', 'Status -> Sensors', 1, 0, 1, '09.01'),
(30, 'MENU', 'MenuControlPanel', 'MonitorsRiskmeter', 'Dashboard -> Metrics -> Riskmeter', 1, 1, 1, '01.07'),
(31, 'MENU', 'MenuIntelligence', 'CorrelationDirectives', 'Intelligence -> Correlation Directives', 0, 0, 1, '06.03'),
(32, 'MENU', 'MenuIntelligence', 'CorrelationCrossCorrelation', 'Intelligence -> Cross Correlation', 0, 0, 1, '06.06'),
(33, 'MENU', 'MenuIntelligence', 'CorrelationBacklog', 'Intelligence -> Correlation Directives -> Backlog', 1, 0, 1, '06.04'),
(34, 'MENU', 'MenuConfiguration', 'ConfigurationMain', 'Configuration -> Main', 0, 0, 1, '08.01'),
(35, 'MENU', 'MenuConfiguration', 'ConfigurationUsers', 'Configuration -> Users', 0, 0, 1, '08.02'),
(36, 'MENU', 'MenuConfiguration', 'ConfigurationPlugins', 'Configuration -> Collection -> Data Sources', 0, 0, 1, '08.06'),
(37, 'MENU', 'MenuConfiguration', 'ConfigurationRRDConfig', '', 0, 0, 0, '0'),
(38, 'MENU', 'MenuConfiguration', 'ConfigurationHostScan', '', 0, 0, 0, '0'),
(39, 'MENU', 'MenuConfiguration', 'ConfigurationUserActionLog', 'Configuration -> Users -> User activity', 0, 0, 1, '08.03'),
(40, 'MENU', 'MenuIncidents', 'ConfigurationEmailTemplate', 'Incidents -> Tickets -> Incidents Email Template', 0, 0, 1, '02.09'),
(41, 'MENU', 'MenuConfiguration', 'ConfigurationUpgrade', 'Configuration -> Software Upgrade', 0, 0, 1, '08.09'),
(42, 'MENU', 'MenuPolicy', 'ToolsScan', 'Assets -> Assets Discovery', 0, 1, 1, '05.06'),
(43, 'MENU', 'MenuTools', 'ToolsRuleViewer', '', 0, 0, 0, '0'),
(44, 'MENU', 'MenuConfiguration', 'ToolsBackup', 'Configuration -> Backup', 0, 0, 1, '08.10'),
(46, 'MENU', 'MenuControlPanel', 'BusinessProcesses', 'Dashboard -> Risk Maps', 1, 1, 1, '01.04'),
(47, 'MENU', 'MenuControlPanel', 'BusinessProcessesEdit', 'Dashboard -> Risk Maps Edit', 1, 1, 1, '01.05'),
(48, 'MENU', 'MenuEvents', 'EventsForensics', 'Analysis -> SIEM Events', 1, 1, 1, '03.01'),
(49, 'MENU', 'MenuEvents', 'EventsVulnerabilities', 'Analysis -> Vulnerabilities -> View', 1, 1, 1, '03.07'),
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> SIEM Events -> Anomalies', 1, 1, 1, '03.05'),
(51, 'MENU', 'MenuEvents', 'EventsRT', 'Analysis -> SIEM Events -> Real Time', 1, 0, 1, '03.03'),
(53, 'MENU', 'MenuConfiguration', 'PolicyServers', 'Configuration -> SIEM Components -> Servers', 0, 0, 1, '08.05'),
(54, 'MENU', 'MenuPolicy', 'ReportsOCSInventory', 'Assets -> Assets -> Inventory', 1, 1, 1, '05.05'),
(55, 'MENU', 'MenuIncidents', 'Osvdb', 'Incidents -> Knowledge DB', 0, 0, 1, '02.10'),
(56, 'MENU', 'MenuConfiguration', 'ConfigurationMaps', '', 0, 0, 0, '0'),
(57, 'MENU', 'MenuConfiguration', 'ToolsDownloads', 'Configuration -> Collection -> Downloads', 0, 0, 1, '08.08'),
(58, 'MENU', 'MenuReports', 'ReportsGLPI', '', 0, 0, 0, '0'),
(59, 'MENU', 'MenuMonitors', 'MonitorsVServers', '', 0, 0, 0, '0'),
(60, 'MENU', 'MenuIncidents', 'ControlPanelAlarms', 'Incidents -> Alarms', 1, 0, 1, '02.00'),
(61, 'MENU', 'MenuEvents', 'ControlPanelSEM', 'Analysis -> Logger', 1, 0, 1, '03.06'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> SIEM Events -> Wireless', 1, 0, 1, '03.04'),
(63, 'MENU', 'MenuIntelligence', 'ComplianceMapping', 'Intelligence -> Compliance Mapping', 0, 0, 1, '06.05'),
(64, 'MENU', 'MenuPolicy', '5DSearch', 'Assets -> Asset Search', 0, 0, 1, '05.01'),
(65, 'MENU', 'MenuReports', 'ReportsReportServer', 'Reports -> Custom Reports', 0, 0, 1, '04.02'),
(66, 'MENU', 'MenuMonitors', 'MonitorsNetflows', 'Monitors -> Network -> Traffic', 0, 0, 1, '07.01'),
(67, 'MENU', 'MenuReports', '5DSearch', 'Assets -> Asset Search', 0, 0, 0, '0'),
(68, 'MENU', 'MainMenu', 'Index', 'Top Frame', 1, 1, 1, '00.01'),
(69, 'MENU', 'MenuStatus', 'ToolsUserLog', 'Status -> Sensors -> User Activity', 0, 0, 1, '09.02'),
(70, 'MENU', 'MenuIncidents', 'ControlPanelAlarmsDelete', 'Incidents -> Alarms -> Delete Alarms', 0, 0, 1, '02.01'),
(71, 'MENU', 'MenuEvents', 'EventsForensicsDelete', 'Analysis -> SIEM Events -> Delete Events', 0, 0, 1, '03.02'),
(72, 'MENU', 'MenuEvents', 'EventsVulnerabilitiesScan', 'Analysis -> Vulnerabilities -> Scan/Import', 1, 1, 1, '03.08'),
(73, 'MENU', 'MenuEvents', 'EventsVulnerabilitiesDeleteScan', 'Analysis -> Vulnerabilities -> Delete Scan Report', 1, 1, 1, '03.09'),
(74, 'MENU', 'MenuReports', 'ReportsCreateCustom', 'Reports -> Custom Reports -> Create Custom Report', 0, 0, 1, '04.06'),
(75, 'MENU', 'MenuReports', 'ReportsScheduler', 'Reports -> Custom Reports -> Scheduler', 0, 0, 1, '04.07'),
(76, 'MENU', 'MenuIncidents', 'IncidentsOpen', 'Incidents -> Tickets -> Open Tickets', 0, 0, 1, '02.04'),
(77, 'MENU', 'MenuIncidents', 'IncidentsDelete', 'Incidents -> Tickets -> Delete', 0, 0, 1, '02.05');

ALTER TABLE host_properties ADD `anom` TINYINT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE host_property_reference ADD  `description` VARCHAR( 128 ) NOT NULL;
REPLACE INTO host_property_reference (`id`, `name`, `ord`, `description`) VALUES
(1, 'software', 3, 'Software'),
(2, 'cpu', 8, 'CPU'),
(3, 'operating-system', 1, 'Operating System'),
(4, 'services', 2, 'Services'),
(5, 'ram', 9, 'RAM'),
(6, 'department', 5, 'Department'),
(7, 'macAddress', 7, 'MAC Address'),
(8, 'workgroup', 6, 'Workgroup'),
(9, 'role', 4, 'Role');

CREATE TABLE IF NOT EXISTS host_properties_changes (
	   id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	   type        INT, 
	   ip           VARCHAR(15), 
	   sensor       VARCHAR(64), 
	   date         DATETIME, 
	   property_ref INT, 
	   source_id    INT, 
	   value        TEXT, 
	   extra        TEXT
);

use ossim_acl;
REPLACE INTO `aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
(10, 'DomainAccess', 'All', 1, 'All', 0),
(11, 'DomainAccess', 'Login', 2, 'Login', 0),
(12, 'DomainAccess', 'Nets', 3, 'Nets', 0),
(13, 'DomainAccess', 'Sensors', 4, 'Sensors', 0),
(14, 'MainMenu', 'Index', 1, 'Index', 0),
(15, 'MenuControlPanel', 'ControlPanelExecutive', 1, 'ControlPanelExecutive', 0),
(16, 'MenuControlPanel', 'ControlPanelExecutiveEdit', 2, 'ControlPanelExecutiveEdit', 0),
(17, 'MenuControlPanel', 'ControlPanelMetrics', 3, 'ControlPanelMetrics', 0),
(18, 'MenuControlPanel', 'ControlPanelAlarms', 4, 'ControlPanelAlarms', 0),
(19, 'MenuControlPanel', 'ControlPanelEvents', 5, 'ControlPanelEvents', 0),
(20, 'MenuControlPanel', 'ControlPanelVulnerabilities', 6, 'ControlPanelVulnerabilities', 0),
(21, 'MenuControlPanel', 'ControlPanelAnomalies', 7, 'ControlPanelAnomalies', 0),
(22, 'MenuControlPanel', 'ControlPanelHids', 8, 'ControlPanelHids', 0),
(23, 'MenuIntelligence', 'PolicyPolicy', 1, 'PolicyPolicy', 0),
(24, 'MenuPolicy', 'PolicyHosts', 2, 'PolicyHosts', 0),
(25, 'MenuPolicy', 'PolicyNetworks', 3, 'PolicyNetworks', 0),
(26, 'MenuConfiguration', 'PolicySensors', 4, 'PolicySensors', 0),
(27, 'MenuPolicy', 'PolicySignatures', 5, 'PolicySignatures', 0),
(28, 'MenuPolicy', 'PolicyPorts', 6, 'PolicyPorts', 0),
(29, 'MenuIntelligence', 'PolicyActions', 7, 'PolicyActions', 0),
(30, 'MenuPolicy', 'PolicyResponses', 8, 'PolicyResponses', 0),
(31, 'MenuPolicy', 'PolicyPluginGroups', 9, 'PolicyPluginGroups', 0),
(32, 'MenuReports', 'ReportsHostReport', 1, 'ReportsHostReport', 0),
(33, 'MenuIncidents', 'ReportsAlarmReport', 2, 'ReportsAlarmReport', 0),
(34, 'MenuReports', 'ReportsSecurityReport', 3, 'ReportsSecurityReport', 0),
(35, 'MenuReports', 'ReportsPDFReport', 4, 'ReportsPDFReport', 0),
(36, 'MenuIncidents', 'IncidentsIncidents', 1, 'IncidentsIncidents', 0),
(37, 'MenuIncidents', 'IncidentsTypes', 2, 'IncidentsTypes', 0),
(38, 'MenuIncidents', 'IncidentsReport', 3, 'IncidentsReport', 0),
(39, 'MenuIncidents', 'IncidentsTags', 4, 'IncidentsTags', 0),
(40, 'MenuMonitors', 'MonitorsSession', 1, 'MonitorsSession', 0),
(41, 'MenuMonitors', 'MonitorsNetwork', 2, 'MonitorsNetwork', 0),
(42, 'MenuMonitors', 'MonitorsAvailability', 3, 'MonitorsAvailability', 0),
(43, 'MenuStatus', 'MonitorsSensors', 4, 'MonitorsSensors', 0),
(44, 'MenuControlPanel', 'MonitorsRiskmeter', 5, 'MonitorsRiskmeter', 0),
(45, 'MenuIntelligence', 'CorrelationDirectives', 1, 'CorrelationDirectives', 0),
(46, 'MenuIntelligence', 'CorrelationCrossCorrelation', 2, 'CorrelationCrossCorrelation', 0),
(47, 'MenuIntelligence', 'CorrelationBacklog', 3, 'CorrelationBacklog', 0),
(48, 'MenuConfiguration', 'ConfigurationMain', 1, 'ConfigurationMain', 0),
(49, 'MenuConfiguration', 'ConfigurationUsers', 2, 'ConfigurationUsers', 0),
(50, 'MenuConfiguration', 'ConfigurationPlugins', 3, 'ConfigurationPlugins', 0),
(51, 'MenuConfiguration', 'ConfigurationRRDConfig', 4, 'ConfigurationRRDConfig', 0),
(52, 'MenuConfiguration', 'ConfigurationHostScan', 5, 'ConfigurationHostScan', 0),
(53, 'MenuConfiguration', 'ConfigurationUserActionLog', 6, 'ConfigurationUserActionLog', 0),
(54, 'MenuIncidents', 'ConfigurationEmailTemplate', 6, 'ConfigurationEmailTemplate', 0),
(55, 'MenuConfiguration', 'ConfigurationUpgrade', 8, 'ConfigurationUpgrade', 0),
(56, 'MenuPolicy', 'ToolsScan', 1, 'ToolsScan', 0),
(57, 'MenuTools', 'ToolsRuleViewer', 2, 'ToolsRuleViewer', 0),
(58, 'MenuConfiguration', 'ToolsBackup', 3, 'ToolsBackup', 0),
(59, 'MenuStatus', 'ToolsUserLog', 4, 'ToolsUserLog', 0),
(60, 'MenuControlPanel', 'BusinessProcesses', 3, 'BusinessProcesses', 0),
(61, 'MenuControlPanel', 'BusinessProcessesEdit', 4, 'BusinessProcessesEdit', 0),
(62, 'MenuEvents', 'EventsForensics', 1, 'EventsForensics', 0),
(63, 'MenuEvents', 'EventsVulnerabilities', 2, 'EventsVulnerabilities', 0),
(64, 'MenuEvents', 'EventsAnomalies', 3, 'EventsAnomalies', 0),
(65, 'MenuEvents', 'EventsRT', 4, 'EventsRT', 0),
(66, 'MenuEvents', 'EventsViewer', 5, 'EventsViewer', 0),
(67, 'MenuConfiguration', 'PolicyServers', 5, 'PolicyServers', 0),
(68, 'MenuPolicy', 'ReportsOCSInventory', 5, 'ReportsOCSInventory', 0),
(69, 'MenuIncidents', 'Osvdb', 5, 'Osvdb', 0),
(70, 'MenuConfiguration', 'ConfigurationMaps', 9, 'ConfigurationMaps', 0),
(71, 'MenuConfiguration', 'ToolsDownloads', 5, 'ToolsDownloads', 0),
(72, 'MenuReports', 'ReportsGLPI', 5, 'ReportsGLPI', 0),
(73, 'MenuMonitors', 'MonitorsVServers', 4, 'MonitorsVServers', 0),
(74, 'MenuIncidents', 'ControlPanelAlarms', 1, 'ControlPanelAlarms', 0),
(75, 'MenuEvents', 'ControlPanelSEM', 1, 'ControlPanelSEM', 0),
(76, 'MenuEvents', 'ReportsWireless', 6, 'ReportsWireless', 0),
(77, 'MenuIntelligence', 'ComplianceMapping', 4, 'ComplianceMapping', 0),
(78, 'MenuPolicy', '5DSearch', 2, '5DSearch', 0),
(79, 'MenuReports', 'ReportsReportServer', 3, 'ReportsReportServer', 0),
(80, 'MenuMonitors', 'MonitorsNetflows', 2, 'MonitorsNetflows', 0),
(81, 'MenuReports', '5DSearch', 1, '5DSearch', 0),
(82, 'MenuConfiguration', 'PluginGroups', 0, 'PluginGroups', 0),
(83, 'MenuIncidents', 'IncidentsOpen', 0, 'IncidentsOpen', 0),
(84, 'MenuIncidents', 'IncidentsDelete', 0, 'IncidentsDelete', 0),
(85, 'MenuEvents', 'EventsForensicsDelete', 0, 'EventsForensicsDelete', 0);
   
use ossim;
UPDATE config SET value="2011-02-01" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.14');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
