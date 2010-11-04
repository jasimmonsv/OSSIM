use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `incident_custom_types` ADD `ord` INT NOT NULL;
ALTER TABLE `incident_custom` CHANGE `content` `content` BLOB NOT NULL;
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES (515, 'NetFlows - Trafic Graphs', 'Network', 'Network/TraficGraphs.php', 'Source:SOURCE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT;TCP:tcp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;UDP:udp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;ICMP:icmp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;ANY:any:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1);

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-04" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.5');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
