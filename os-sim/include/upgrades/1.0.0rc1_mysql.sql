INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_eventstats', '1');
INSERT IGNORE INTO config (conf, value) VALUES ("rrdpath_stats","/var/lib/ossim/rrd/event_stats/");
INSERT IGNORE INTO config (conf, value) VALUES ('nagios_cfgs','/etc/nagios2/conf.d/ossim-configs/');
INSERT IGNORE INTO config (conf, value) VALUES ('nagios_reload_cmd','/etc/init.d/nagios2 reload || { /etc/init.d/nagios2 stop;/etc/init.d/nagios2 start; }');
INSERT IGNORE INTO config (conf, value) VALUES ('frameworkd_donagios','1');
INSERT IGNORE INTO config (conf, value) VALUES ('ocs_link','');
INSERT IGNORE INTO config (conf, value) VALUES ('ovcp_link','');
INSERT IGNORE INTO config (conf, value) VALUES ('glpi_link','');
INSERT IGNORE INTO config (conf, value) VALUES ('md5_salt','salty_dog');
INSERT IGNORE INTO config (conf, value) VALUES ('login_ldap_cn', "cn");

INSERT IGNORE INTO plugin (id, type, name, description) VALUES (2007, 2, 'nagios', 'Nagios'); 

ALTER TABLE `users` ADD `language` VARCHAR( 6 ) NOT NULL DEFAULT 'en_GB';

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '1.0.0rc1');

