use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO repository values (27001, 'AV Possible Scada Modbus device scanning from SRC_IP detected by PIX Firewall', '<b>Description:</b><br><br>A scanning activity has been detected via firewall logs. This activity indicates that the process is scanning for the presence of Modbus enabled PLCs. An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br>', now(), 'admin', '');
REPLACE INTO repository values (27001, 'AV Possible Scada Modbus device scanning from  SRC_IP (Network Detection)', '<b>Description:</b><br><br>A scanning activity has been detected via network traffic (IDS) logs. This activity indicates that the process is scanning for the presence of Modbus enabled PLCs. An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br><br>', now(), 'admin', '');
REPLACE INTO repository values (27003, 'AV Possible Scada Modbus Scanning or Fingerprinting against DST_IP', '<b>Description:</b><br><br>A scanning pattern against Modbus devices has been detected with ModScan or a similar tool. ModScan is a new tool designed to map a SCADA MODBUS TCP based network. The tool is written in python for portability and can be used on virtually any system with few required libraries.<br><br>An attack to these devices can have a severe impact in the system, for example, a PLC may control the flow of cooling water through part of an industrial process.<br><br>References:<br>	- http://code.google.com/p/modscan/<br>', now(), 'admin', '');
REPLACE INTO config (conf, value) VALUES ('solera_enable', '0');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-11');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.22');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
