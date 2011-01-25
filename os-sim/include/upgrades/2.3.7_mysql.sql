use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO log_config (code, log, descr, priority) VALUES (093, 1, 'Account %1% locked: Too many failed login attempts', 1);
ALTER TABLE `incident_ticket` ADD INDEX `users` ( `incident_id` , `users` , `in_charge` , `transferred` );
ALTER TABLE `incident` ADD INDEX ( `in_charge` );
                                        
-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-12-23" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.7');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
