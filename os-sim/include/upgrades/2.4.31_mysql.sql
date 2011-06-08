use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `ossim`.`custom_report_types` SET `inputs` = 'Top Logger Events List:top:text:OSS_DIGIT:10:50;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCELOGGER:' WHERE `custom_report_types`.`id` =145;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'alarm' AND COLUMN_NAME = 'similar')
  THEN
      ALTER TABLE  `alarm` ADD  `similar` VARCHAR( 40 ) NOT NULL DEFAULT  '0000000000000000000000000000000000000000';
   END IF;        
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

UPDATE alarm SET similar = SHA1(backlog_id);

DROP TRIGGER IF EXISTS set_similar_field;
DELIMITER '//'
CREATE TRIGGER set_similar_field BEFORE INSERT ON alarm
FOR EACH ROW BEGIN
  IF NEW.similar='0000000000000000000000000000000000000000' THEN
     SET NEW.similar = sha1(NEW.backlog_id);
  END IF;
END;
//
DELIMITER ";"

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-06-08');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.31');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
