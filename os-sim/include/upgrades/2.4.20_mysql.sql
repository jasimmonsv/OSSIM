use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `custom_report_types` SET `inputs` = 'Top SIEM Events List:top:text:OSS_DIGIT:25:250;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:;Source:source:select:OSS_ALPHA:EVENTSOURCE:' WHERE `id`=128;

REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('OS', 'OS is', 'fixed', 'SELECT DISTINCT os_value, os_text FROM (SELECT value as os_value, value as os_text FROM host_properties WHERE property_ref=3 UNION SELECT os as os_value, os as os_text FROM host_os WHERE os != "") h ORDER BY os_value', '(select distinct ip as ip from host_properties where property_ref=3 and value like ?) UNION (select distinct inet_ntoa(h.ip) as ip from host_os h where h.os like ? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os not like ? and h1.anom=0 and h1.date>h.date)) UNION (select distinct inet_ntoa(ip) from host_os where os like ? and anom=1 and ip not in (select distinct ip from host_os where anom=0))', 1),
('OS', 'OS is Not', 'fixed', 'SELECT DISTINCT os_value, os_text FROM (SELECT value as os_value, value as os_text FROM host_properties WHERE property_ref=3 UNION SELECT os as os_value, os as os_text FROM host_os WHERE os != "") h ORDER BY os_value', 'select distinct ip as ip from host_os where ip not in (select distinct inet_ntoa(ip) as ip from host_properties where property_ref=3 and value like ? UNION select h.ip from host_os h where h.os=? and h.anom=0 and h.ip not in (select h1.ip from host_os h1 where h1.os<>? and h1.anom=0 and h1.date>h.date)) UNION (select ip from host_os where os=? and anom=1 and ip not in (select distinct ip from host_os where anom=0))', 1);

REPLACE INTO config (conf, value) VALUES ('scanner_type', 'openvas3');
REPLACE INTO config (conf, value) VALUES ('vulnerability_incident_threshold', '2');

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'sensor' AND COLUMN_NAME = 'tzone')
  THEN
      ALTER TABLE `sensor` ADD `tzone` FLOAT NOT NULL DEFAULT '0';
  END IF;       
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-04');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.20');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
