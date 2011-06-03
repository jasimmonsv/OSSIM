use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs=CONCAT(inputs,";Group by:groupby:select:OSS_ALPHA.OSS_SCORE.OSS_PUNC.OSS_NULLABLE:TRENDGROUPBY:") WHERE file='Logger/EventsTrend.php' AND inputs not like '%TRENDGROUPBY%';
UPDATE custom_report_types SET inputs=CONCAT(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:"),type='SIEM/Logger Events' WHERE file='SIEM/TopEvents.php' AND inputs not like '%EVENTSOURCE%';
UPDATE custom_report_types SET file="Logger/TopEvents.php" WHERE name='Top Events' AND type='Logger';


DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'vuln_jobs' AND COLUMN_NAME = 'resolve_names')
   THEN
		ALTER TABLE `vuln_jobs` ADD `resolve_names` TINYINT( 1 ) NOT NULL DEFAULT '1';
   END IF; 
   IF NOT EXISTS
        (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'vuln_job_schedule' AND COLUMN_NAME = 'resolve_names')
   THEN
		ALTER TABLE `vuln_job_schedule` ADD `resolve_names` TINYINT( 1 ) NOT NULL DEFAULT '1';
   END IF;    
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;


CREATE TABLE IF NOT EXISTS  category_changes (
    id        INTEGER NOT NULL,
    name      VARCHAR (100) NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS subcategory_changes (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `name` text,
  PRIMARY KEY (`id`)
);


-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-05-30');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.30');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
