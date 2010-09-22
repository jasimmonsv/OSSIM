use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs=CONCAT(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:") WHERE file='SIEM/List.php' AND name!="List" AND inputs not like '%EVENTSOURCE%';

INSERT IGNORE INTO  `action_type` (`_type` , `descr`) VALUES ('ticket',  'open new ticket if policy matches');

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-21" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
