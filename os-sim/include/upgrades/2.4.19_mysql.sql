use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE `custom_report_types` SET `type` = 'SIEM/Logger Events' WHERE `custom_report_types`.`id` =1099;
UPDATE `custom_report_types` SET `type` = 'SIEM/Logger Events' WHERE `custom_report_types`.`id` =1100;

REPLACE INTO `tags_alarm` (`name`, `bgcolor`, `fgcolor`, `italic`, `bold`) VALUES
('False Positive', 'ffe3e3', 'cc0000', 0, 0),
('Analysis', '206cff', 'e0ecff', 0, 0);

REPLACE INTO host_property_reference (`id`, `name`, `ord`, `description`) VALUES
(1, 'software', 3, 'Software'),
(2, 'cpu', 8, 'CPU'),
(3, 'operating-system', 1, 'Operating System'),
(4, 'workgroup', 6, 'Workgroup'),
(5, 'memory', 9, 'Memory'),
(6, 'department', 5, 'Department'),
(7, 'macAddress', 7, 'MAC Address'),
(8, 'service', 2, 'Services'),
(9, 'acl', 10, 'ACL'),
(10, 'route', 11, 'Route'),
(11, 'storage', 12, 'Storage'),
(12, 'role', 4, 'Role');

REPLACE INTO host_source_reference(id, name, relevance) VALUES (8,'TELNET', 9);
REPLACE INTO host_source_reference(id, name, relevance) VALUES (9,'SNMP', 9);

DROP TABLE IF EXISTS host_properties_changes;
CREATE TABLE host_properties_changes (
   id INT NOT NULL AUTO_INCREMENT,
   type INT NOT NULL,
   ip VARCHAR(15) NOT NULL,
   sensor VARCHAR(64) NULL DEFAULT '',
   date DATETIME,
   property_ref INT,
   source_id INT,
   value TEXT,
   extra TEXT,
   anom TINYINT(1) NOT NULL DEFAULT '0',
   tzone FLOAT NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `date` (`date`),
   KEY `ip` (`ip`,`sensor`),
   KEY `property_ref` (`property_ref`,`value`(255))
);

use snort;
ALTER TABLE `acid_event` MODIFY `tzone` FLOAT NOT NULL DEFAULT '0' AFTER `plugin_sid`;

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-02-23');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.19');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
