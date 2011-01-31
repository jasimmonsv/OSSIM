use ossim;
SET AUTOCOMMIT=0;
BEGIN;

DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =6;
DELETE FROM `acl_perm` WHERE `id` =6;
DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =52;
DELETE FROM `acl_perm` WHERE `id` =52;
DELETE FROM `acl_templates_perms` WHERE `ac_perm_id` =45;
DELETE FROM `acl_perm` WHERE `id` =45;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.00' WHERE `acl_perm`.`id` =60;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.01' WHERE `acl_perm`.`id` =70;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.02' WHERE `acl_perm`.`id` =19;
UPDATE `ossim`.`acl_perm` SET `ord` = '03.03' WHERE `acl_perm`.`id` =51;

UPDATE `ossim`.`acl_perm` SET `description` = 'Top Frame' WHERE `acl_perm`.`id` =68;
UPDATE `ossim`.`acl_perm` SET `description` = 'Analysis -> Vulnerabilities -> View' WHERE `acl_perm`.`id` =49;
UPDATE `ossim`.`acl_perm` SET `description` = 'Reports -> Custom Reports',`ord` = '04.02'  WHERE `acl_perm`.`id` =65;
UPDATE `ossim`.`acl_perm` SET `description` = 'Assets -> Assets Discovery',`ord` = '05.06',name='MenuPolicy' WHERE `acl_perm`.`id` =42;
UPDATE `ossim`.`acl_perm` SET `ord` = '08.06' WHERE `acl_perm`.`id` =36;
UPDATE `ossim`.`acl_perm` SET `ord` = '08.07' WHERE `acl_perm`.`id` =17;
UPDATE `ossim`.`acl_perm` SET `ord` = '08.08' WHERE `acl_perm`.`id` =57;
UPDATE `ossim`.`acl_perm` SET `ord` = '08.09' WHERE `acl_perm`.`id` =41;
UPDATE `ossim`.`acl_perm` SET `ord` = '08.10' WHERE `acl_perm`.`id` =44;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> SIEM Components -> Sensors',`ord` = '08.04',name='MenuConfiguration' WHERE `acl_perm`.`id` =12;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> SIEM Components -> Servers',`ord` = '08.05',name='MenuConfiguration' WHERE `acl_perm`.`id` =53;
UPDATE `ossim`.`acl_perm` SET `description` = 'Status -> Sensors',`ord` = '09.01',name='MenuStatus' WHERE `acl_perm`.`id` =29;
UPDATE `ossim`.`acl_perm` SET `description` = 'Status -> Sensors -> User Activity',`ord` = '09.02',name='MenuStatus' WHERE `acl_perm`.`id` =69;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> Collection -> Data Sources' WHERE `acl_perm`.`id` =36;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> Collection -> DS Groups' WHERE `acl_perm`.`id` =17;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> Collection -> Downloads',`ord` = '08.08',name='MenuConfiguration' WHERE `acl_perm`.`id` =57;
UPDATE `ossim`.`acl_perm` SET `description` = 'Configuration -> Backup',`ord` = '08.10',name='MenuConfiguration' WHERE `acl_perm`.`id` =44;

REPLACE INTO `ossim`.`acl_perm` (`id` ,`type` ,`name` ,`value` ,`description` ,`granularity_sensor` ,`granularity_net` ,`enabled` ,`ord`) VALUES (72 , 'MENU', 'MenuEvents', 'EventsVulnerabilitiesScan', 'Analysis -> Vulnerabilities -> Scan/Import', '1', '1', '1', '03.08');
REPLACE INTO `ossim`.`acl_perm` (`id` ,`type` ,`name` ,`value` ,`description` ,`granularity_sensor` ,`granularity_net` ,`enabled` ,`ord`) VALUES (73 , 'MENU', 'MenuEvents', 'EventsVulnerabilitiesDeleteScan', 'Analysis -> Vulnerabilities -> Delete Scan Report', '1', '1', '1', '03.09');
REPLACE INTO `ossim`.`acl_perm` (`id` , `type` , `name` , `value` , `description` , `granularity_sensor` , `granularity_net` , `enabled` , `ord`) VALUES (74 , 'MENU', 'MenuReports', 'ReportsCreateCustom', 'Reports -> Custom Reports -> Create Custom Report', '0', '0', '1', '04.06');
REPLACE INTO `ossim`.`acl_perm` (`id` , `type` , `name` , `value` , `description` , `granularity_sensor` , `granularity_net` , `enabled` , `ord`) VALUES (75 , 'MENU', 'MenuReports', 'ReportsScheduler', 'Reports -> Custom Reports -> Scheduler', '0', '0', '1', '04.07');
UPDATE `ossim`.`acl_perm` SET `ord` = '02.06' WHERE `acl_perm`.`id` =24;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.07' WHERE `acl_perm`.`id` =23;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.08' WHERE `acl_perm`.`id` =25;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.09' WHERE `acl_perm`.`id` =40;
UPDATE `ossim`.`acl_perm` SET `ord` = '02.10' WHERE `acl_perm`.`id` =55;
REPLACE INTO `ossim`.`acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES (76, 'MENU', 'MenuIncidents', 'IncidentsOpen', 'Incidents -> Tickets -> Open Tickets', '0', '0', '1', '02.04');
REPLACE INTO `ossim`.`acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES (77, 'MENU', 'MenuIncidents', 'IncidentsDelete', 'Incidents -> Tickets -> Delete', '0', '0', '1', '02.05');


ALTER TABLE host_property_reference ADD  `description` VARCHAR( 128 ) NOT NULL;
REPLACE INTO host_property_reference (`id`, `name`, `ord`, `description`) VALUES
(1, 'software', 3, 'Software'),
(2, 'cpu', 8, 'CPU'),
(3, 'operating-system', 1, 'Operating System'),
(4, 'services', 2, 'Services'),
(5, 'ram', 9, 'RAM'),
(6, 'department', 5, 'Department'),
(7, 'macAddress', 7, 'MAC Address'),
(8, 'workgroup', 6, 'Workgroup'),
(9, 'role', 4, 'Role');

CREATE TABLE host_properties_changes (
	   id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	   type        INT, 
	   ip           VARCHAR(15), 
	   sensor       VARCHAR(64), 
	   date         DATETIME, 
	   property_ref INT, 
	   source_id    INT, 
	   value        TEXT, 
	   extra        TEXT
);
       
use ossim;
UPDATE config SET value="2011-01-28" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.14');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
