use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES (81, 'MENU', 'MenuMonitors', 'MonitorsInventory', 'Situational Awareness -> Inventory', 0, 0, 1, '07.04');

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'host' AND COLUMN_NAME = 'icon')
  THEN
      ALTER TABLE  `host` ADD  `icon` MEDIUMBLOB NULL;
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'net' AND COLUMN_NAME = 'icon')
  THEN
      ALTER TABLE  `net` ADD  `icon` MEDIUMBLOB NULL;
  END IF;  
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.26');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
