CREATE TABLE IF NOT EXISTS `risk_indicators` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `map` int(11) NOT NULL default '0',
  `url` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `type` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `type_name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `icon` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `x` int(11) NOT NULL default '0',
  `y` int(11) NOT NULL default '0',
  `w` int(11) NOT NULL default '0',
  `h` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);

ALTER TABLE `server_role` ADD `sign` INT ( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `server_role` ADD `sem` TINYINT ( 1 ) NOT NULL DEFAULT '1';
ALTER TABLE `server_role` ADD `sim` TINYINT ( 1 ) NOT NULL DEFAULT '1';

ALTER TABLE `policy_role_reference` ADD `sign` INT ( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `policy_role_reference` ADD `sem` TINYINT ( 1 ) NOT NULL DEFAULT '1';
ALTER TABLE `policy_role_reference` ADD `sim` TINYINT ( 1 ) NOT NULL DEFAULT '1';

ALTER TABLE plugin_sid ADD aro DECIMAL (11,4) NOT NULL DEFAULT 0;
ALTER TABLE bp_process ADD valuation DECIMAL (11,2) NOT NULL DEFAULT 0;
ALTER TABLE alarm ADD efr INTEGER (11) NOT NULL DEFAULT 0;

alter table users change column language language varchar(12) after department;

ALTER TABLE `policy` ADD `active` INT(11) NOT NULL AFTER `priority`;
ALTER TABLE `policy` ADD `group` INT(11) NOT NULL AFTER `active`;
ALTER TABLE `policy` ADD `order` INT(11) NOT NULL DEFAULT '0' AFTER `group`;
CREATE INDEX `group` ON `policy` (`group`);
CREATE INDEX `order` ON `policy` (`order`);

ALTER TABLE `alarm` ADD INDEX `timestamp` (`timestamp`);
ALTER TABLE `alarm` ADD INDEX `src_ip` (`src_ip`);
ALTER TABLE `alarm` ADD INDEX `dst_ip` (`dst_ip`);
 
REPLACE INTO config (conf, value) VALUES ('acid_link', '/ossim/forensics/');
REPLACE INTO config (conf, value) VALUES ('acid_path', '/usr/share/ossim/www/forensics/');
REPLACE INTO config (conf, value) VALUES ('scanner_type', 'openvas2');

INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_enable','');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_use_proxy','no');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_url','');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_user','');
INSERT IGNORE INTO config (conf, value) VALUES ('proxy_password','');
INSERT IGNORE INTO config (conf, value) VALUES ('last_update','');
INSERT IGNORE INTO config (conf, value) VALUES ('update_checks_source','http://data.alienvault.com/updates/update_log.txt');

CREATE TABLE IF NOT EXISTS `repository` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(256) NOT NULL,
  `text` text NOT NULL,
  `date` date NOT NULL,
  `user` varchar(64) NOT NULL,
  `keywords` varchar(256) NOT NULL COMMENT 'Comma separated',
  PRIMARY KEY  (`id`),
  KEY `title` (`title`),
  KEY `keywords` (`keywords`),
  FULLTEXT KEY `text` (`text`)
);

CREATE TABLE IF NOT EXISTS `repository_attachments` (
  `id` int(11) NOT NULL auto_increment,
  `id_document` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `type` varchar(4) NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `repository_relationships` (
  `id` int(11) NOT NULL auto_increment,
  `id_document` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `type` varchar(16) NOT NULL,
  `keyname` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `policy_group` (
  `group_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY  (`group_id`)
);

CREATE TABLE IF NOT EXISTS policy_host_group_reference (    
policy_id int(11) NOT NULL,    
host_group_name varchar(128) NOT NULL,    
direction enum('source','dest') NOT NULL,    
PRIMARY KEY (policy_id,host_group_name,direction)
);

CREATE TABLE IF NOT EXISTS policy_net_group_reference (
    policy_id int(11) NOT NULL,
    net_group_name varchar(128) NOT NULL,
    direction enum('source','dest') NOT NULL,
    PRIMARY KEY (policy_id,net_group_name,direction)
);

CREATE TABLE IF NOT EXISTS `host_mac_vendors` (
  `mac` varchar(8) NOT NULL,
  `vendor` varchar(255) NOT NULL,
  PRIMARY KEY (`mac`)
);

INSERT IGNORE INTO config (conf, value) VALUES ('repository_upload_dir', "/usr/share/ossim/www/uploads");

INSERT IGNORE INTO config (conf, value) VALUES ('bi_type','jasperserver');
INSERT IGNORE INTO config (conf, value) VALUES ('bi_host','localhost');
INSERT IGNORE INTO config (conf, value) VALUES ('bi_port','8080');
INSERT IGNORE INTO config (conf, value) VALUES ('bi_link','/jasperserver/flow.html?_flowId=listReportsFlow&curlnk=2&j_username=USER&j_password=PASSWORD');
INSERT IGNORE INTO config (conf, value) VALUES ('bi_user','jasperadmin');
INSERT IGNORE INTO config (conf, value) VALUES ('bi_pass','');

ALTER TABLE incident_type ADD keywords varchar(255) NOT NULL AFTER `descr`;

CREATE TABLE IF NOT EXISTS `sensor_agent_info` (
  `ip` varchar(64) NOT NULL,
        `version` varchar(64) NOT NULL,
        PRIMARY KEY  (`ip`)
);

CREATE TABLE IF NOT EXISTS alarm_group (
        id              BIGINT(20) NOT NULL auto_increment,
        status          ENUM("open","closed") DEFAULT "open",
        timestamp       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
        owner           VARCHAR(64) DEFAULT NULL,
        descr           VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS alarm_group_members (
        group_id        BIGINT(20) NOT NULL,
        backlog_id      BIGINT(20) NOT NULL,
        event_id        BIGINT(20) NOT NULL,
        PRIMARY KEY (backlog_id, event_id)
);


INSERT INTO config (conf, value) VALUES ('frameworkd_alarmgroup', '1');
INSERT INTO config (conf, value) VALUES ('first_login', '1');
REPLACE INTO config (conf, value) VALUES ('ossim_link', '/ossim/');

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '1.0.0rc2');

