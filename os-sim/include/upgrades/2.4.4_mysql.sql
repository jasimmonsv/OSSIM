use ossim;
SET AUTOCOMMIT=0;
BEGIN;

INSERT INTO plugin_sid (plugin_id,sid,category_id,class_id,reliability,priority,name,aro,subcategory_id) VALUES (7006,18149,2,NULL,1,1,'ossec: Windows user logoff.','0.0000',27);
INSERT INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES (202, 'Theats Database', 'Vulnerabilities', 'Vulnerabilities/TheatsDatabase.php', 'Keywords:keywords:text:OSS_NULLABLE::20;CVE:cve:text:OSS_NULLABLE::20;Risk Factor:riskFactor:select:OSS_ALPHA:ALL,Info,Low,Medium,High,Serious:;Detail:detail:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1);
INSERT INTO `config` (`conf`, `value`) VALUES ('backup_netflow', '45');

INSERT IGNORE INTO log_config (code, log, descr, priority) VALUES (092, 1, '%1%', 1);
INSERT IGNORE INTO log_config (code, log, descr, priority) VALUES (093, 1, 'User %1% disabled for security reasons', 1);

ALTER TABLE  `incident` CHANGE  `ref`  `ref` ENUM(  'Alarm',  'Alert',  'Event',  'Metric',  'Anomaly',  'Vulnerability',  'Custom' ) NOT NULL DEFAULT  'Alarm';

DROP TABLE IF EXISTS `incident_custom`;
CREATE TABLE IF NOT EXISTS `incident_custom` (
  id int(11) NOT NULL auto_increment,
  incident_id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (id,incident_id)
);

DROP TABLE IF EXISTS `incident_custom_types`;
CREATE TABLE IF NOT EXISTS `incident_custom_types` (
  id varchar(64) NOT NULL,
  name varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  options text NOT NULL,
  PRIMARY KEY (id,name)
);
                    
-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-10-15" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
