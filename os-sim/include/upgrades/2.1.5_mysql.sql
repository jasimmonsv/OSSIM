-- Add indexes
ALTER TABLE `alarm` ADD INDEX ( `status` );
ALTER TABLE `event` ADD INDEX ( `sensor`(255) );
RENAME TABLE `ossim`.`sensor_agent_info`  TO `ossim`.`sensor_properties` ;
ALTER TABLE `sensor_properties` ADD `has_nagios` TINYINT( 1 ) NOT NULL DEFAULT '1', ADD `has_ntop` TINYINT( 1 ) NOT NULL DEFAULT '1', ADD `has_vuln_scanner` TINYINT( 1 ) NOT NULL DEFAULT '1';
 
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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.1.5');
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
