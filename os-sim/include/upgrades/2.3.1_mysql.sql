use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use datawarehouse;
CREATE TABLE IF NOT EXISTS `incidents_ssi_user` (
  `type` varchar(128) NOT NULL,
  `descr` varchar(128) NOT NULL,
  `priority` int(11) NOT NULL,
  `source` varchar(128) NOT NULL,
  `destination` varchar(128) NOT NULL,
  `details` varchar(128) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `hour` int(11) NOT NULL,
  `minute` int(11) NOT NULL,
  `volume` int(11) default NULL,
  `user` varchar(64) NOT NULL,
  PRIMARY KEY  (`type`,`descr`,`priority`,`source`,`destination`,`details`,`year`,`month`,`day`,`hour`,`minute`,`user`)
);
CREATE TABLE IF NOT EXISTS `ssi_user` (
  `sid` int(11) NOT NULL,
  `descr` varchar(128) NOT NULL,
  `priority` int(11) NOT NULL,
  `source` varchar(128) NOT NULL,
  `destination` varchar(128) NOT NULL,
  `details` varchar(128) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `hour` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL,
  `minute` int(11) NOT NULL default '0',
  `volume` int(11) default NULL,
  `user` varchar(64) NOT NULL,
  PRIMARY KEY  (`sid`,`descr`,`priority`,`source`,`destination`,`details`,`year`,`month`,`day`,`hour`,`minute`,`user`)
);


USE ossim
-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-07-23" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
