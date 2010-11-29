use ossim;
SET AUTOCOMMIT=0;
BEGIN;

CREATE TABLE IF NOT EXISTS `custom_collectors` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `type` tinyint(4) NOT NULL,
  `plugin_id` int(11) NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `source` varchar(64) NOT NULL,
  `location` tinytext NOT NULL,
  `create` tinyint(1) NOT NULL,
  `process` varchar(255) NOT NULL,
  `start` tinyint(1) NOT NULL,
  `stop` tinyint(1) NOT NULL,
  `startup_command` varchar(255) NOT NULL,
  `stop_command` varchar(255) NOT NULL,
  `sample_log` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `custom_collector_rules` (
  `id` int(11) NOT NULL auto_increment,
  `idc` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` tinytext NOT NULL,
  `type` varchar(64) NOT NULL,
  `expression` tinytext NOT NULL,
  `prio` int(11) NOT NULL,
  `rel` int(11) NOT NULL,
  `plugin_sid` int(11) NOT NULL,
  `date` varchar(255) default NULL,
  `sensor` varchar(255) default NULL,
  `interface` varchar(255) default NULL,
  `protocol` varchar(255) default NULL,
  `src_ip` varchar(255) default NULL,
  `src_port` varchar(255) default NULL,
  `dst_ip` varchar(255) default NULL,
  `dst_port` varchar(255) default NULL,
  `username` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `filename` varchar(255) default NULL,
  `userdata1` varchar(255) default NULL,
  `userdata2` varchar(255) default NULL,
  `userdata3` varchar(255) default NULL,
  `userdata4` varchar(255) default NULL,
  `userdata5` varchar(255) default NULL,
  `userdata6` varchar(255) default NULL,
  `userdata7` varchar(255) default NULL,
  `userdata8` varchar(255) default NULL,
  `userdata9` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
);

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-30" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.8');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
