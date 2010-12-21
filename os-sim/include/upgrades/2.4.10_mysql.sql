use ossim;
SET AUTOCOMMIT=0;
BEGIN;

REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES ('Vulnerabilities', 'Vuln Contains', 'text', '', 'SELECT DISTINCT INET_NTOA(hp.host_ip) as ip FROM host_plugin_sid hp, plugin_sid p WHERE hp.plugin_id = 3001 AND p.plugin_id = 3001 AND hp.plugin_sid = p.sid AND p.name %op% ? UNION SELECT DISTINCT INET_NTOA(s.host_ip) as ip FROM vuln_nessus_plugins p,host_plugin_sid s WHERE s.plugin_id=3001 and s.plugin_sid=p.id AND p.name %op% ?', 4);
REPLACE INTO `inventory_search` (`type`, `subtype`, `match`, `list`, `query`, `ruleorder`) VALUES ('SIEM Events', 'Has Plugin Groups', 'fixed', 'SELECT group_id,name FROM plugin_group_descr', 'SELECT INET_NTOA(ip_src) as ip FROM snort.acid_event WHERE plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?) UNION SELECT INET_NTOA(ip_dst) as ip FROM snort.acid_event WHERE plugin_id in (SELECT plugin_id FROM ossim.plugin_group WHERE group_id=?)', 5);

UPDATE custom_report_types SET inputs=REPLACE(inputs,";Source:source:select:OSS_ALPHA:EVENTSOURCE:",";Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:;Source:source:select:OSS_ALPHA:EVENTSOURCE:") WHERE file='SIEM/List.php' AND name!="List" AND inputs not like '%PLUGINGROUPS%';
UPDATE custom_report_types SET inputs=CONCAT(inputs,";Plugin Groups:plugin_groups:select:OSS_DIGIT.OSS_NULLABLE:PLUGINGROUPS:") WHERE (id="160" OR id="161" OR id="162" OR id="163" OR id="164" OR id="120" OR id="121" OR id="122" OR id="123" OR id="124") AND inputs not like '%PLUGINGROUPS%';

REPLACE INTO log_config (code, log, descr, priority) VALUES (094, 1, 'User %1% failed logon', 1);


REPLACE INTO config (conf , value) VALUES ('unlock_user_interval', '5');
REPLACE INTO config (conf , value) VALUES ('pass_complex', 'no');
REPLACE INTO config (conf , value) VALUES ('pass_length_min', '7');
REPLACE INTO config (conf , value) VALUES ('pass_length_max', '32');
REPLACE INTO config (conf , value) VALUES ('pass_expire', '0');
REPLACE INTO config (conf , value) VALUES ('pass_expire_min', '0');
REPLACE INTO config (conf , value) VALUES ('pass_history', '0');

CREATE TABLE IF NOT EXISTS pass_history (
    id		INTEGER NOT NULL AUTO_INCREMENT,
    user    varchar(64) NOT NULL,
    hist_number int(11),
    pass    varchar(41)  NOT NULL,
    PRIMARY KEY (id)
);
DELETE FROM log_config WHERE code=92;

-- From now on, always add the date of the new releases to the .sql files
use ossim;
UPDATE config SET value="2010-11-30" WHERE conf="last_update";


-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.10');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
