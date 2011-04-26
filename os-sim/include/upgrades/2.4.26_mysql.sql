use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES (81, 'MENU', 'MenuMonitors', 'MonitorsInventory', 'Situational Awareness -> Inventory', 0, 0, 1, '07.04');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-20');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.26');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
