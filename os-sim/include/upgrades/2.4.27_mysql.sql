use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> Detection -> Anomalies', 1, 1, 1, '03.14'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> Detection -> Wireless', 1, 0, 1, '03.13'),
(79, 'MENU', 'MenuEvents', 'EventsHids', 'Analysis -> Detection -> HIDS -> View', 1, 0, 1, '03.11'),
(82, 'MENU', 'MenuEvents', 'EventsHidsConfig', 'Analysis -> Detection -> HIDS -> Config', 1, 0, 1, '03.12');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-26');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.27');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
