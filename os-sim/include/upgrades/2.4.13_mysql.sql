use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `sensor` ADD `tzone` INT NOT NULL DEFAULT '0';

UPDATE `inventory_search` SET  `query` = '(select distinct inet_ntoa(h.ip) from host_os h where h.os=? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select distinct inet_ntoa(ip) from host_os where os=? and anom=1 and ip not in (select distinct ip from host_os where anom=0))' WHERE  `inventory_search`.`type` =  'OS' AND  `inventory_search`.`subtype` =  'OS is';
UPDATE `inventory_search` SET  `query` = 'select distinct inet_ntoa(ip) from host_os where ip not in (select h.ip from host_os h where h.os=? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select ip from host_os where os=? and anom=1 and ip not in (select distinct ip from host_os where anom=0))' WHERE  `inventory_search`.`type` =  'OS' AND  `inventory_search`.`subtype` =  'OS is Not';
UPDATE `inventory_search` SET `subtype` = 'IP as Src' WHERE `subtype` = 'IP Is Src' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'IP as Dst' WHERE `subtype` = 'IP Is Dst' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'IP as Src or Dst' WHERE `subtype` = 'IP Is Any' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Source Port' WHERE `subtype` = 'Port Is Src' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Destination Port' WHERE `subtype` = 'Port Is Dst' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Port as Src or Dst' WHERE `subtype` = 'Port Is Any' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Date Before' WHERE `subtype` = 'Date Is LessThan' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Date After' WHERE `subtype` = 'Date Is GreaterThan' AND `type`= 'META';
UPDATE `inventory_search` SET `subtype` = 'Has Src or Dst IP' WHERE `subtype` = 'Has IP' AND `type`= 'META';

CREATE TABLE IF NOT EXISTS `host_agentless` (
  `ip` varchar(15) NOT NULL,
  `hostname` varchar(128) NOT NULL,
  `user` varchar(128) default NULL,
  `pass` varchar(128) default NULL,
  `ppass` varchar(128) default NULL,
  `descr` varchar(255) default NULL,
  `status` int(2) NOT NULL default '1',
  PRIMARY KEY  (`ip`),
  KEY `search` (`hostname`,`user`)
);

CREATE TABLE IF NOT EXISTS `host_agentless_entries` (
  `id` int(11) NOT NULL auto_increment,
  `ip` varchar(15) collate latin1_general_ci NOT NULL,
  `type` varchar(64) collate latin1_general_ci NOT NULL,
  `frecuency` int(10) NOT NULL,
  `state` varchar(20) collate latin1_general_ci NOT NULL,
  `arguments` varchar(255) collate latin1_general_ci default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`,`type`)
);

use snort;
ALTER TABLE `sensor` CHANGE `sensor` `sensor` TEXT NULL DEFAULT '';

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2011-01-14" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.13');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
