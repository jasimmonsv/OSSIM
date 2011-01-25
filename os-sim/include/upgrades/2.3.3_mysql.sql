use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use snort;
DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS 
    (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'sensor' AND COLUMN_NAME = 'sensor')
  THEN
    ALTER TABLE sensor ADD sensor text NOT NULL AFTER last_cid;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

use ossim;

REPLACE INTO plugin_sid (plugin_id,sid,category_id,class_id,reliability,priority,name,aro,subcategory_id) VALUES (7006,18149,2,NULL,1,1,'ossec: Windows user logoff.','0.0000',27);


-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-23" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
