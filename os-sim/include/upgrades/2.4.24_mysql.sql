use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (conf, value) VALUES ('server_logger_if_priority', '0');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-01');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.24');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
