use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (conf, value) VALUES ('def_asset', '2');
DELETE FROM user_config WHERE category='policy' AND name='sensors_layout';

CREATE TABLE IF NOT EXISTS `plugin_sid_changes` (
  `plugin_id` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `reliability` int(11) DEFAULT '1',
  `priority` int(11) DEFAULT '1',
  `category_id` int(11) DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`plugin_id`,`sid`)
);

UPDATE custom_report_types SET inputs=CONCAT(inputs,";Group by:groupby:select:OSS_ALPHA.OSS_SCORE.OSS_PUNC.OSS_NULLABLE:GROUPBY:") WHERE file='SIEM/List.php' AND inputs not like '%GROUPBY%';


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'vuln_nessus_latest_results' AND INDEX_NAME='falsepositive')
   THEN
		ALTER TABLE  `vuln_nessus_latest_results` ADD INDEX `falsepositive` (  `falsepositive` ,  `hostIP` );
		ALTER TABLE  `vuln_nessus_latest_results` DROP INDEX  `report_id` , ADD INDEX  `report_id` (  `report_id` ,  `username` ,  `sid` );
   END IF; 
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'vuln_nessus_latest_reports' AND INDEX_NAME='deleted')
   THEN
		ALTER TABLE  `vuln_nessus_latest_reports` ADD INDEX `deleted` (  `deleted` ,  `results_sent` ,  `name` );
   END IF;    
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;


-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-05-30');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.29');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
