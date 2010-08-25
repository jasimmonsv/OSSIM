use snort;
ALTER TABLE `extra_data`  ENGINE =  MYISAM;

use ossim;
INSERT IGNORE INTO log_config (code, log, descr, priority) VALUES
(90, 1, 'Cross Correlation - Rules: new rule added plugin id: %1%, plugin sid: %2%, reference id: %3%, reference sid: %4%', 1),
(91, 1, 'Cross Correlation - Rules: rule plugin id: %1%, plugin sid: %2%, reference id: %3%, reference sid: %4% deleted', 2);

INSERT INTO `ossim`.`config` (`conf` , `value`) VALUES ('server_remote_logger', 'no');
INSERT INTO `ossim`.`config` (`conf` , `value` ) VALUES ('server_remote_logger_user', '');
INSERT INTO `ossim`.`config` (`conf` , `value` ) VALUES ('server_remote_logger_pass', '');
INSERT INTO `ossim`.`config` (`conf` , `value` ) VALUES ('server_remote_logger_ossim_url', '');

ALTER TABLE `wireless_aps` ADD INDEX `aps_mac_full` (`mac`(18));
ALTER TABLE `wireless_aps` ADD INDEX `aps_sensor_full` (`sensor`);
ALTER TABLE `wireless_aps` ADD INDEX `aps_ssid` (`ssid`);
ALTER TABLE `wireless_aps` ADD INDEX `encryption` (`encryption`);
ALTER TABLE `wireless_aps` ADD INDEX `cloaked` (`cloaked`);
ALTER TABLE `wireless_aps` ADD INDEX `mac_sensor` (`mac`,`sensor`); 
ALTER TABLE `wireless_clients` CHANGE `mac` `mac` VARCHAR( 20 ) NOT NULL;
ALTER TABLE `wireless_clients` CHANGE `client_mac` `client_mac` VARCHAR( 20 ) NOT NULL;
ALTER TABLE `wireless_clients` ADD INDEX `clients_mac_full` (`mac`(18));
ALTER TABLE `wireless_clients` ADD INDEX `clients_sensor_full` (`sensor`);
ALTER TABLE `wireless_clients` ADD INDEX `clients_ssid` (`ssid`);
ALTER TABLE `wireless_clients` ADD INDEX `client_mac_sensor_ssid` (`client_mac`,`sensor`,`ssid`);

ALTER TABLE event ADD COLUMN filename TEXT AFTER snort_cid;
ALTER TABLE event ADD COLUMN username TEXT;
ALTER TABLE event ADD COLUMN password TEXT;
ALTER TABLE event ADD COLUMN userdata1 TEXT;
ALTER TABLE event ADD COLUMN userdata2 TEXT;
ALTER TABLE event ADD COLUMN userdata3 TEXT;
ALTER TABLE event ADD COLUMN userdata4 TEXT;
ALTER TABLE event ADD COLUMN userdata5 TEXT;
ALTER TABLE event ADD COLUMN userdata6 TEXT;
ALTER TABLE event ADD COLUMN userdata7 TEXT;
ALTER TABLE event ADD COLUMN userdata8 TEXT;
ALTER TABLE event ADD COLUMN userdata9 TEXT;


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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.2.2');
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
