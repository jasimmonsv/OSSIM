use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE vuln_nessus_servers SET max_scans = '5';

use ossim;
UPDATE config SET value="2011-02-10" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.18');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
