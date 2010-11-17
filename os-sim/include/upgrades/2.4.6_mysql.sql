use ossim;
SET AUTOCOMMIT=0;
BEGIN;

use snort;
ALTER TABLE `extra_data` ADD `context` INT(11) NOT NULL DEFAULT '0';

use ossim;
REPLACE INTO `config` (`conf`, `value`) VALUES ('session_timeout', '15');
DELETE FROM `user_config` WHERE category='conf' AND name='plugin_layout';
ALTER TABLE `ossim`.`plugin_sid` ADD INDEX ( `category_id` , `subcategory_id` );

ALTER TABLE `host_property_reference` ADD `ord` INT NOT NULL DEFAULT '0';
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(1, 'software', 3);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(2, 'cpu', 8);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(3, 'operating-system', 1);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(4, 'services', 2);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(5, 'ram', 9);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(6, 'department', 5);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(7, 'macAddress', 7);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(8, 'workgroup', 6);
REPLACE INTO `host_property_reference` (`id`, `name`, `ord`) VALUES(9, 'role', 4);
REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES
('Property', 'Has Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT * FROM host_properties WHERE property_ref = ?', 999),
('Property', 'Has not Property', 'fixed', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT * FROM host_properties WHERE property_ref != ?', 999),
('Property', 'Contains', 'fixedText', 'SELECT DISTINCT id as property_value, name as property_text  FROM host_property_reference ORDER BY name', 'SELECT DISTINCT * FROM host_properties WHERE property_ref = ? AND (value LIKE ''%$value2%'' OR extra LIKE ''%$value2%'')', 999);

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-05" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.6');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
