use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE acl_entities DROP FOREIGN KEY fk_ac_entities_ac_entities_types1;
ALTER TABLE acl_entities DROP INDEX fk_ac_entities_ac_entities_types1;
ALTER TABLE acl_entities DROP INDEX fk_acl_entities_acl_users1;

use ossim;
UPDATE config SET value="2011-02-01" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.15');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
