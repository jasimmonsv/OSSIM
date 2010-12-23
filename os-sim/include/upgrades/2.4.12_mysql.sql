use ossim;
SET AUTOCOMMIT=0;
BEGIN;

CREATE TABLE IF NOT EXISTS `sessions` (
 `id` varchar(64) collate latin1_general_ci NOT NULL,
 `login` varchar(64) collate latin1_general_ci NOT NULL,
 `ip` varchar(15) collate latin1_general_ci NOT NULL,
 `agent` varchar(255) collate latin1_general_ci NOT NULL,
 `logon_date` timestamp NOT NULL default '0000-00-00 00:00:00',
 `activity` timestamp NOT NULL default '0000-00-00 00:00:00',
 PRIMARY KEY  (`id`,`login`)
);      
REPLACE INTO log_config (code, log, descr, priority) VALUES (093, 1, 'Account %1% locked: Too many failed login attempts', 1);
ALTER TABLE `incident_ticket` ADD INDEX `users` ( `incident_id` , `users` , `in_charge` , `transferred` ) ;
ALTER TABLE `incident` ADD INDEX ( `in_charge` );

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-23" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.12');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
