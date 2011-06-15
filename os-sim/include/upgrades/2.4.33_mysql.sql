use ossim;
SET AUTOCOMMIT=0;
BEGIN;

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-06-13');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.33');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
