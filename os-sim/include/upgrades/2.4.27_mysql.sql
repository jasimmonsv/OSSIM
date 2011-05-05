use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> Detection -> Anomalies', 1, 1, 1, '03.14'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> Detection -> Wireless', 1, 0, 1, '03.13'),
(79, 'MENU', 'MenuEvents', 'EventsHids', 'Analysis -> Detection -> HIDS -> View', 1, 0, 1, '03.11'),
(82, 'MENU', 'MenuEvents', 'EventsHidsConfig', 'Analysis -> Detection -> HIDS -> Config', 1, 0, 1, '03.12');

UPDATE custom_report_types SET inputs=CONCAT(inputs,';Order by:orderby:select:OSS_ALPHA.OSS_NULLABLE:ORDERBY:') WHERE type="Unique Signatures by" AND inputs not like '%orderby%';

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(135, 'Top Promiscuous Host', 'SIEM Events', 'SIEM/PromiscuousHost.php', 'Top Promiscuous Host:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29),
(136, 'Top Hosts with Multiple Events', 'SIEM Events', 'SIEM/MultipleEventsHost.php', 'Top Hosts:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:', '', 29);

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.27');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
