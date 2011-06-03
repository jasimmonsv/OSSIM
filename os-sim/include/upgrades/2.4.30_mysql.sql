use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs=CONCAT(inputs,";Group by:groupby:select:OSS_ALPHA.OSS_SCORE.OSS_PUNC.OSS_NULLABLE:TRENDGROUPBY:") WHERE file='Logger/EventsTrend.php' AND inputs not like '%TRENDGROUPBY%';
UPDATE custom_report_types SET inputs=CONCAT(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:"),type='SIEM/Logger Events' WHERE file='SIEM/TopEvents.php' AND inputs not like '%EVENTSOURCE%';
UPDATE custom_report_types SET file="Logger/TopEvents.php" WHERE name='Top Events' AND type='Logger';

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-05-30');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.30');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
