use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (conf, value) VALUES ('def_asset', '2');
DELETE FROM user_config WHERE category='policy' AND name='sensors_layout';

-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-05-20');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.29');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
