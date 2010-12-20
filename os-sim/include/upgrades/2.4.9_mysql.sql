use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `custom_collectors` ADD `source_type` VARCHAR(255) NOT NULL AFTER `plugin_id`;
ALTER TABLE `custom_collector_rules` ADD `category_id` INT(11) NOT NULL AFTER `plugin_sid` , ADD `subcategory_id` INT(11) NOT NULL AFTER `category_id`;

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-30" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.9');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
