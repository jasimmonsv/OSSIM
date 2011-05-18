use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(140, 'Top Attacker Host', 'Logger', 'Logger/AttackerHosts.php', 'Top Attacker Host:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(141, 'Top Attacked Host', 'Logger', 'Logger/AttackedHosts.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(142, 'Top Used Ports', 'Logger', 'Logger/UsedPorts.php', 'Top Used Ports:top:text:OSS_DIGIT:10:50;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(143, 'Data Sources', 'Logger', 'Logger/CollectionSources.php', 'Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(144, 'Events Trend', 'Logger', 'Logger/EventsTrend.php', 'Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1),
(145, 'Top Events', 'Logger', 'Logger/List.php', 'Top Logger Events List:top:text:OSS_DIGIT:25:250;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:', '', 1);

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(137, 'Top Attacks by Country', 'SIEM Events', 'SIEM/AttacksCountry.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Top Attacker Countries:num_countries:text:OSS_DIGIT:3:20;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 30);

ALTER TABLE  `vuln_jobs` CHANGE  `notify`  `notify` TEXT NOT NULL default '';
ALTER TABLE  `vuln_job_schedule` CHANGE  `email`  `email` TEXT NOT NULL default '';

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'custom_report_profiles' AND COLUMN_NAME = 'creator')
  THEN
		ALTER TABLE `custom_report_profiles` DROP PRIMARY KEY;
		ALTER TABLE `custom_report_profiles` ADD `id` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
		ALTER TABLE `custom_report_profiles` ADD `creator` VARCHAR( 64 ) NOT NULL AFTER `name`;
		ALTER TABLE `custom_report_profiles` ADD UNIQUE (`name`, `creator`) ;
		UPDATE custom_report_profiles SET creator='admin' WHERE creator='';
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-05-06');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.28');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
