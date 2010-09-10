use ossim;
SET AUTOCOMMIT=0;
BEGIN;

CREATE TABLE IF NOT EXISTS alarm_tags (
  id_alarm int(11) NOT NULL,
  id_tag int(11) NOT NULL,
  PRIMARY KEY (id_alarm)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tags_alarm` (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(128) NOT NULL,
  bgcolor varchar(7) NOT NULL,
  fgcolor varchar(7) NOT NULL,
  italic int(1) NOT NULL DEFAULT '0',
  bold tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM;
       

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-09" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
