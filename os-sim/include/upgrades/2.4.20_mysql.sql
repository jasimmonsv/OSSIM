use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `custom_report_types` SET `inputs` = 'Top SIEM Events List:top:text:OSS_DIGIT:25:250;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Source:source:select:OSS_ALPHA:EVENTSOURCE:' WHERE `id`=128;

REPLACE INTO config (conf, value) VALUES ('scanner_type', 'openvas3');
REPLACE INTO config (conf, value) VALUES ('vulnerability_incident_threshold', '2');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-02-24');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.20');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
