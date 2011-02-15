use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('OS', 'OS is', 'fixed', 'SELECT DISTINCT os as os_value, os as os_text FROM host_os WHERE os != "" ORDER BY os', '(select distinct inet_ntoa(h.ip) as ip from host_os h where h.os=? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select distinct inet_ntoa(ip) from host_os where os=? and anom=1 and ip not in (select distinct ip from host_os where anom=0))', 1),
('OS', 'OS is Not', 'fixed', 'SELECT DISTINCT os as os_value, os as os_text FROM host_os WHERE os != "" ORDER BY os', 'select distinct inet_ntoa(ip) as ip from host_os where ip not in (select h.ip from host_os h where h.os=? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select ip from host_os where os=? and anom=1 and ip not in (select distinct ip from host_os where anom=0))', 1);

ALTER TABLE `inventory_search` MODIFY `match` ENUM( 'text', 'ip', 'fixed', 'boolean', 'date', 'number', 'concat', 'fixedText') NOT NULL;
UPDATE `inventory_search` SET `match` = 'fixedText' WHERE `subtype` = 'Contains' AND `type`= 'Property';

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'host_source_reference' AND COLUMN_NAME = 'relevance')
  THEN
      ALTER TABLE  `host_source_reference` CHANGE  `priority` `relevance` INT( 11 ) NULL DEFAULT NULL;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;
ALTER TABLE  `host_source_reference` CHANGE  `id`  `id` INT( 11 ) NOT NULL;

CREATE TABLE IF NOT EXISTS event_field_reference (
	plugin_id                  INTEGER NOT NULL,
	plugin_sid                 INTEGER NOT NULL,
	host_property_reference_id INTEGER NOT NULL,
	host_source_reference_id   INTEGER NOT NULL,
	which_userdata             INTEGER NOT NULL,
	PRIMARY KEY (plugin_id, plugin_sid, host_property_reference_id)
);

use snort;
DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'acid_event' AND COLUMN_NAME = 'ossim_correlation')
  THEN
      ALTER TABLE acid_event ADD `ossim_correlation` TINYINT(1) DEFAULT  '0';
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

use ossim;
UPDATE config SET conf='server_logger_if_priority' WHERE conf='logger_if_priority';
UPDATE config SET value="2011-02-08" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.17');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
