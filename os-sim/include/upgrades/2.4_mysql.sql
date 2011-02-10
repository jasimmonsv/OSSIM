use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `vuln_job_schedule` CHANGE `schedule_type` `schedule_type` ENUM( 'O','D', 'W', 'M', 'NW' ) NOT NULL DEFAULT 'M';

-- From now on, always add the date of the new releases to the .sql files
-- UPDATE config SET value="2010-07-23" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
