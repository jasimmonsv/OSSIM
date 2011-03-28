use ossim;
SET AUTOCOMMIT=0;
BEGIN;

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-25');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.23');
REPLACE INTO config (conf, value) VALUES ('nessus_pre_scan_locally', '1');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
