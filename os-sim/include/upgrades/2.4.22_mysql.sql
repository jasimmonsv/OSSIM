use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO repository values (27001, 'AV Possible Scada Modbus device scanning from SRC_IP detected by PIX Firewall', '<b>Description:</b><br><br>A scanning activity has been detected via firewall logs. This activity indicates that the process is scanning for the presence of Modbus enabled PLCs. An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br>', now(), 'admin', '');
REPLACE INTO repository values (27001, 'AV Possible Scada Modbus device scanning from  SRC_IP (Network Detection)', '<b>Description:</b><br><br>A scanning activity has been detected via network traffic (IDS) logs. This activity indicates that the process is scanning for the presence of Modbus enabled PLCs. An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br><br>', now(), 'admin', '');
REPLACE INTO repository values (27003, 'AV Possible Scada Modbus Scanning or Fingerprinting against DST_IP', '<b>Description:</b><br><br>A scanning pattern against Modbus devices has been detected with ModScan or a similar tool. ModScan is a new tool designed to map a SCADA MODBUS TCP based network. The tool is written in python for portability and can be used on virtually any system with few required libraries.<br><br>An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br><br>References:<br>	- http://code.google.com/p/modscan/<br>', now(), 'admin', '');

REPLACE INTO custom_report_types values (180, 'Thread Overview', 'B & C', 'BusinessAndComplianceISOPCI/ThreatOverview.php', '', '', 1);

ALTER TABLE `custom_report_scheduler` CHANGE `schedule_type` `schedule_type` VARCHAR(5) NOT NULL;
DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'custom_report_scheduler' AND COLUMN_NAME = 'schedule_name')
  THEN
      ALTER TABLE `custom_report_scheduler` ADD `schedule_name` VARCHAR(20) NOT NULL AFTER `schedule_type`;
      ALTER TABLE `custom_report_scheduler` ADD `user` VARCHAR( 64 ) NOT NULL AFTER `name_report`;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

REPLACE INTO config (conf, value) VALUES ('solera_enable', '0');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-25');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.22');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
