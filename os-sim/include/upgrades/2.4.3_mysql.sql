use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_backlog` CHAR(36) ASCII;

UPDATE event SET uuid=UPPER(UUID()) WHERE uuid IS NULL;
UPDATE backlog SET uuid=UPPER(UUID()) WHERE uuid IS NULL;


-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-13" WHERE conf="last_update";

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
