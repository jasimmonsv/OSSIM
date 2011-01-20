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

DROP PROCEDURE IF EXISTS addsindex;
DELIMITER '//'
CREATE PROCEDURE addsindex() BEGIN
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'incident_ticket' AND INDEX_NAME='users')
   THEN
        ALTER TABLE `incident_ticket` ADD INDEX `users` ( `incident_id` , `users` , `in_charge` , `transferred` ) ;
   END IF;
END;
//
DELIMITER ';'
CALL addsindex();
DROP PROCEDURE addsindex;


DROP PROCEDURE IF EXISTS addsindex;
DELIMITER '//'
CREATE PROCEDURE addsindex() BEGIN
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'incident' AND INDEX_NAME='in_charge')
   THEN
        ALTER TABLE `incident` ADD INDEX ( `in_charge` );
   END IF;
END;
//
DELIMITER ';'
CALL addsindex();
DROP PROCEDURE addsindex;

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-23" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.12');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
