use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(138, 'Top Events of top Attacker host', 'SIEM/Logger Events', 'SIEM/TopAttackerHosts.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Top Events:num_events:text:OSS_DIGIT:5:15;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 1),
(139, 'Top Events of top Attacked host', 'SIEM/Logger Events', 'SIEM/TopAttackedHosts.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Top Events:num_events:text:OSS_DIGIT:5:15;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 1);

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(146, 'Last Attacker Hosts', 'Logger', 'Logger/LastAttackerHosts.php', 'Last Attacker Host:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(147, 'Last Attacked Hosts', 'Logger', 'Logger/LastAttackedHosts.php', 'Last Attacked Host:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(148, 'Last Used Ports', 'Logger', 'Logger/LastUsedPorts.php', 'Last Used Ports:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1);

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(166, 'Last Used Ports', 'Alarms', 'Alarms/LastUsedPorts.php', 'Last Used Ports:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 1),
(167, 'Last Alarms', 'Alarms', 'Alarms/LastAlarms.php', 'Last Alarms:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 1),
(168, 'Last Alarms by Risk', 'Alarms', 'Alarms/LastAlarmsByRisk.php', 'Last Alarms by Risk:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 1);

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(187, 'Last Attacker Host', 'SIEM Events', 'SIEM/LastAttackerHosts.php', 'Last Attacker Host:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(188, 'Last Attacked Host', 'SIEM Events', 'SIEM/LastAttackedHosts.php', 'Last Attacked Host:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(189, 'Last Used Ports', 'SIEM Events', 'SIEM/LastUsedPorts.php', 'Last Used Ports:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(190, 'Last Events by risk', 'SIEM Events', 'SIEM/LastEventsByRisk.php', 'Last Events by Risk:top:text:OSS_DIGIT:15:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30),
(191, 'Last Source/Destination Used Ports', 'SIEM Events', 'SIEM/LastSourceDestinationUsedPorts.php', 'Last Used Ports:top:text:OSS_DIGIT:20:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29),
(192, 'Last Source/Destination Events', 'SIEM Events', 'SIEM/LastSourceDestinationEvents.php', 'Last Events:top:text:OSS_DIGIT:20:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29),
(193, 'Last Promiscuous Host', 'SIEM Events', 'SIEM/LastPromiscuousHost.php', 'Last Promiscuous Host:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29),
(194, 'Last Hosts with Multiple Events', 'SIEM Events', 'SIEM/LastMultipleEventsHost.php', 'Last Hosts:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29);

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(440, 'Title Page', 'Title Page', 'Common/titlepage.php', 'Logo:logo:FILE:OSS_NULLABLE::;Main Title:maintitle:text:OSS_TEXT::64;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::35;Address:address:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::35;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::', '', 1);


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'custom_report_profiles' AND COLUMN_NAME = 'permissions')
  THEN
      ALTER TABLE `custom_report_profiles` ADD `permissions` INT(4) NOT NULL DEFAULT 0 AFTER `creator`;
   END IF;        
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-06-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.32');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
