use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(133, 'Top Source/Destination Used Ports', 'SIEM Events', 'SIEM/SourceDestinationUsedPorts.php', 'Top Used Ports:top:text:OSS_DIGIT:20:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29),
(134, 'Top Source/Destination Events', 'SIEM Events', 'SIEM/SourceDestinationEvents.php', 'Top Events:top:text:OSS_DIGIT:20:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29);
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(145, 'Top Events', 'Logger', 'Logger/List.php', 'Top Logger Events List:top:text:OSS_DIGIT:25:250;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 1);

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-22" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.11');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
