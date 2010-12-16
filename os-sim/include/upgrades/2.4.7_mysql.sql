use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (`conf`, `value`) VALUES ('unlock_user_interval', '30');
REPLACE INTO config (`conf`, `value`) VALUES ('failed_retries', '5');

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(71, 'MENU', 'MenuEvents', 'EventsForensicsDelete', 'Analysis -> SIEM Events -> Delete Events', 0, 0, 1, '03.02'),
(70, 'MENU', 'MenuIncidents', 'ControlPanelAlarmsDelete', 'Incidents -> Alarms -> Delete Alarms', 0, 0, 1, '02.02');

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-25" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.7');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
