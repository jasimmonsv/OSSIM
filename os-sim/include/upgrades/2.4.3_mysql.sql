use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs=CONCAT(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:") WHERE file='SIEM/List.php' AND name!="List" AND inputs not like '%EVENTSOURCE%';
INSERT IGNORE INTO  `action_type` (`_type` , `descr`) VALUES ('ticket',  'generate ticket');
REPLACE INTO `custom_report_types` (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`) VALUES
(515, 'NetFlows - Trafic Graphs', 'Network', 'Network/TraficGraphs.php', 'TCP:tcp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;UDP:udp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;ICMP:icmp:checkbox:OSS_NULLABLE.OSS_DIGIT:1;ANY:any:checkbox:OSS_NULLABLE.OSS_DIGIT:1', '', 1),
(516, 'NetFlows - Trafic Details', 'Network', 'Network/NetFlows.php', 'Source:SOURCE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT;Top Flows:top:text:OSS_DIGIT:20:500;Type List:Type_list:radiobuttons:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);

use snort;
DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'sensor' AND COLUMN_NAME = 'sensor')
  THEN
      ALTER TABLE sensor ADD sensor text NOT NULL AFTER last_cid;
  END IF;
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

use ossim;
-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-24" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.3');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
