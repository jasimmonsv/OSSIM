use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE IGNORE vuln_nessus_servers SET max_scans = '5';

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-02-10');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.18');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
