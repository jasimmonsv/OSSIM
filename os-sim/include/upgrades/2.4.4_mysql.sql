use ossim;
SET AUTOCOMMIT=0;
BEGIN;


INSERT INTO plugin_sid (plugin_id,sid,category_id,class_id,reliability,priority,name,aro,subcategory_id) VALUES (7006,18149,2,NULL,1,1,'ossec: Windows user logoff.','0.0000',27);

DROP TABLE IF EXISTS `incident_custom`;
CREATE TABLE IF NOT EXISTS `incident_custom` (
  id int(11) NOT NULL auto_increment,
  incident_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (id,incident_id)
);

DROP TABLE IF EXISTS `incident_custom_types`;
CREATE TABLE IF NOT EXISTS `incident_custom_types` (
  id varchar(64) NOT NULL,
  name varchar(255) NOT NULL,
  PRIMARY KEY (id,name)
);
                    
                    
-- From now on, always add the date of the new releases to the .sql files
use ossim;
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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
