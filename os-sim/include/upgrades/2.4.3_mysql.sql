use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_backlog` CHAR(36) ASCII;

-- Assing uuid to tables and 
UPDATE event SET uuid=UPPER(UUID()) WHERE uuid IS NULL;
COMMIT;
UPDATE backlog SET uuid=UPPER(UUID()) WHERE uuid IS NULL;
COMMIT;
UPDATE backlog_event,backlog,event SET backlog_event.uuid=backlog.uuid,backlog_event.uuid_event=event.uuid WHERE backlog.id = backlog_event.backlog_id AND backlog_event.event_id = event.id;
COMMIT;
UPDATE alarm,backlog,event SET alarm.uuid_event = event.uuid,alarm.uuid_backlog = backlog.uuid WHERE alarm.event_id = event.id AND alarm.backlog_id = backlog.id;
COMMIT;
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
