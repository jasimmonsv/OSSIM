use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs = 'Status:status:select:OSS_LETTER:Open,Closed,All' WHERE id in (320,321,322,323,324);
UPDATE custom_report_types SET inputs = 'Logo:logo:FILE:OSS_NULLABLE::;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT::35;Address:address:text:OSS_TEXT.OSS_PUNC_EXT::35;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT::' WHERE id=440;
REPLACE INTO config (conf, value) VALUES ('nessus_pre_scan_locally', '1');

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(48, 'MENU', 'MenuEvents', 'EventsForensics', 'Analysis -> SIEM Events', 1, 1, 1, '03.01'),
(71, 'MENU', 'MenuEvents', 'EventsForensicsDelete', 'Analysis -> SIEM Events -> Delete Events', 0, 0, 1, '03.02'),
(51, 'MENU', 'MenuEvents', 'EventsRT', 'Analysis -> SIEM Events -> Real Time', 1, 0, 1, '03.03'),
(61, 'MENU', 'MenuEvents', 'ControlPanelSEM', 'Analysis -> Logger', 1, 0, 1, '03.04'),
(49, 'MENU', 'MenuEvents', 'EventsVulnerabilities', 'Analysis -> Vulnerabilities -> View', 1, 1, 1, '03.07'),
(72, 'MENU', 'MenuEvents', 'EventsVulnerabilitiesScan', 'Analysis -> Vulnerabilities -> Scan/Import', 1, 1, 1, '03.08'),
(73, 'MENU', 'MenuEvents', 'EventsVulnerabilitiesDeleteScan', 'Analysis -> Vulnerabilities -> Delete Scan Report', 1, 1, 1, '03.09'),
(78, 'MENU', 'MenuEvents', 'EventsNids', 'Analysis -> Detection -> NIDS', 1, 1, 1, '03.10'),
(79, 'MENU', 'MenuEvents', 'EventsHids', 'Analysis -> Detection -> HIDS', 1, 1, 1, '03.11'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> Detection -> Wireless', 1, 0, 1, '03.12'),
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> Detection -> Anomalies', 1, 1, 1, '03.13');

INSERT INTO `ossim_acl`.`aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES ('86', 'MenuEvents', 'EventsNids', '0', 'EventsNids', '0'), ('87', 'MenuEvents', 'EventsHids', '0', 'EventsHids', '0');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-25');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.23');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
