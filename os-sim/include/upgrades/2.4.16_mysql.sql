use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `config` (`conf`, `value`) VALUES
('customize_send_logs', NULL),
('customize_title_background_color', '#8CC221'),
('customize_title_foreground_color', '#000000'),
('customize_subtitle_background_color', '#7A7A7A'),
('customize_subtitle_foreground_color', '#FFFFFF'),
('customize_wizard', '0');
REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> Detection -> Anomalies', 1, 1, 1, '03.06'),
(61, 'MENU', 'MenuEvents', 'ControlPanelSEM', 'Analysis -> Logger', 1, 0, 1, '03.04'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> Detection -> Wireless', 1, 0, 1, '03.05');

ALTER TABLE `host_agentless_entries` CHANGE `frecuency` `frequency` INT( 10 ) NOT NULL;

use ossim;
UPDATE config SET value="2011-02-04" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.16');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
