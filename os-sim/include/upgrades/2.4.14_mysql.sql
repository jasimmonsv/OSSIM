use ossim;
SET AUTOCOMMIT=0;
BEGIN;

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
