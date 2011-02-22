use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `custom_report_types` SET `type` = 'SIEM/Logger Events' WHERE `custom_report_types`.`id` =1099;
UPDATE `custom_report_types` SET `type` = 'SIEM/Logger Events' WHERE `custom_report_types`.`id` =1100;

REPLACE INTO `tags_alarm` (`name`, `bgcolor`, `fgcolor`, `italic`, `bold`) VALUES
('False Positive', 'ffe3e3', 'cc0000', 0, 0),
('Analysis', '206cff', 'e0ecff', 0, 0);

use snort;
ALTER TABLE `acid_event` MODIFY `tzone` FLOAT NOT NULL DEFAULT '0' AFTER `plugin_sid`;

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-02-18');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.19');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
