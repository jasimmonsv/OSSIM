use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use ossim;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS 
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin')
  THEN
      ALTER TABLE  `users` ADD  `is_admin` BOOL NOT NULL DEFAULT  '0';
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

UPDATE host_group_reference, host SET host_group_reference.host_ip=host.ip WHERE host.hostname=host_group_reference.host_ip;
ALTER TABLE  `vuln_nessus_settings_preferences` CHANGE  `value`  `value` TEXT NULL DEFAULT NULL;

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-10-15" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
