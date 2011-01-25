use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO config (`conf`, `value`) VALUES ('unlock_user_interval', '30');
REPLACE INTO config (`conf`, `value`) VALUES ('failed_retries', '5');

REPLACE INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(71, 'MENU', 'MenuEvents', 'EventsForensicsDelete', 'Analysis -> SIEM Events -> Delete Events', 0, 0, 1, '03.02'),
(70, 'MENU', 'MenuIncidents', 'ControlPanelAlarmsDelete', 'Incidents -> Alarms -> Delete Alarms', 0, 0, 1, '02.02');

REPLACE INTO log_config (code, log, descr, priority) VALUES (092, 1, '%1%', 1);
REPLACE INTO log_config (code, log, descr, priority) VALUES (093, 1, 'User %1% disabled for security reasons', 1);

REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES ('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT INET_NTOA(hp.host_ip) as ip FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT INET_NTOA(s.host_ip) as ip FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4);

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-12-2" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.5');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
