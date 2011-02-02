use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE acl_entities DROP FOREIGN KEY fk_ac_entities_ac_entities_types1;
ALTER TABLE acl_entities DROP INDEX fk_ac_entities_ac_entities_types1;
ALTER TABLE acl_entities DROP INDEX fk_acl_entities_acl_users1;
ALTER TABLE `event` ADD `tzone` FLOAT NOT NULL DEFAULT  '0' AFTER `timestamp`;

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
UPDATE config SET value="2011-02-03" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.15');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
