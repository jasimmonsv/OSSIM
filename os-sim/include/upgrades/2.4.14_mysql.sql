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
   
use ossim;
UPDATE config SET value="2011-02-01" WHERE conf="last_update";

use snort;
ALTER TABLE acid_event ADD `ossim_correlation` TINYINT( 1 ) DEFAULT  '0';

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.14');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
