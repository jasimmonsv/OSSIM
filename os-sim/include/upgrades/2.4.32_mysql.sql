use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(138, 'Top Events of top Attacker host', 'SIEM/Logger Events', 'SIEM/TopAttackerHosts.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Top Events:num_events:text:OSS_DIGIT:5:15;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 1),
(139, 'Top Events of top Attacked host', 'SIEM/Logger Events', 'SIEM/TopAttackedHosts.php', 'Top Attacked Host:top:text:OSS_DIGIT:10:50;Top Events:num_events:text:OSS_DIGIT:5:15;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCE:', '', 1);

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-06-08');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.32');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
