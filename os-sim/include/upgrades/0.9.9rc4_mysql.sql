CREATE TABLE event_tmp (
        id              BIGINT NOT NULL,
        timestamp       TIMESTAMP NOT NULL,
        sensor          TEXT NOT NULL,
        interface       TEXT NOT NULL,
        type            INTEGER NOT NULL,
        plugin_id       INTEGER NOT NULL,
        plugin_sid      INTEGER NOT NULL,
        event_name      varchar(255),
        protocol        INTEGER,
        src_ip          INTEGER UNSIGNED,
        dst_ip          INTEGER UNSIGNED,
        src_port        INTEGER,
        dst_port        INTEGER,
        priority        INTEGER DEFAULT 1,
        reliability     INTEGER DEFAULT 1,
        asset_src       INTEGER DEFAULT 1,
        asset_dst       INTEGER DEFAULT 1,
        risk_a          INTEGER DEFAULT 1,
        risk_c          INTEGER DEFAULT 1,
        alarm           TINYINT DEFAULT 1,
        filename        varchar(255),
        username        varchar(255),
        password        varchar(255),
        userdata1       varchar(255),
        userdata2       varchar(255),
        userdata3       varchar(255),
        userdata4       varchar(255),
        userdata5       varchar(255),
        userdata6       varchar(255),
        userdata7       varchar(255),
        userdata8       varchar(255),
        userdata9       varchar(255),
        PRIMARY KEY (id)
);

CREATE TABLE event_tmp_filter (
        id              BIGINT NOT NULL,
        login           varchar(255),
		timestamp       TIMESTAMP NOT NULL,
        sensor          TEXT NOT NULL,
        interface       TEXT NOT NULL,
        type            INTEGER NOT NULL,
        plugin_id       INTEGER NOT NULL,
        plugin_sid      INTEGER NOT NULL,
        event_name      varchar(255),
        protocol        INTEGER,
        src_ip          INTEGER UNSIGNED,
        dst_ip          INTEGER UNSIGNED,
        src_port        INTEGER,
        dst_port        INTEGER,
        priority        INTEGER DEFAULT 1,
        reliability     INTEGER DEFAULT 1,
        asset_src       INTEGER DEFAULT 1,
        asset_dst       INTEGER DEFAULT 1,
        risk_a          INTEGER DEFAULT 1,
        risk_c          INTEGER DEFAULT 1,
        alarm           TINYINT DEFAULT 1,
        filename        varchar(255),
        username        varchar(255),
        password        varchar(255),
        userdata1       varchar(255),
        userdata2       varchar(255),
        userdata3       varchar(255),
        userdata4       varchar(255),
        userdata5       varchar(255),
        userdata6       varchar(255),
        userdata7       varchar(255),
        userdata8       varchar(255),
        userdata9       varchar(255),
        PRIMARY KEY (id, login)
);

ALTER TABLE event_tmp CHANGE event_name plugin_sid_name varchar(255);
ALTER TABLE event_tmp_filter CHANGE event_name plugin_sid_name varchar(255);

CREATE TABLE event_tmp_seq (
         id INTEGER UNSIGNED NOT NULL
				          );
INSERT INTO event_tmp_seq (id) VALUES (0);

REPLACE INTO config (conf, value) VALUES ('max_event_tmp', '10000');

-- heartbeat events
REPLACE INTO plugin (id, type, name, description) VALUES (1523, 1, 'heartbeat', 'Heartbeat without CRM');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 1, NULL, NULL, 'heartbeat: node up');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 2, NULL, NULL, 'heartbeat: node active');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 3, NULL, NULL, 'heartbeat: node dead');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 4, NULL, NULL, 'heartbeat: link up');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 5, NULL, NULL, 'heartbeat: link dead');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 6, NULL, NULL, 'heartbeat: resources being acquired');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 7, NULL, NULL, 'heartbeat: resources acquired');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 8, NULL, NULL, 'heartbeat: no resources to acquire');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 9, NULL, NULL, 'heartbeat: standby');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 10, NULL, NULL, 'heartbeat: standby completed');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 11, NULL, NULL, 'heartbeat: shutdown');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 12, NULL, NULL, 'heartbeat: shutdown completed');
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 13, NULL, NULL, 'heartbeat: late heartbeat');

---------------------------------------------------------------
---- Business Processes
---------------------------------------------------------------

CREATE TABLE bp_process (
	id          INT NOT NULL,
	name        VARCHAR(255) NOT NULL,
	description TEXT,
	PRIMARY KEY (id)
);

--
-- List of assets conforming a bussiness process
--
CREATE TABLE bp_asset (
	id          INT NOT NULL,
	name        VARCHAR(255) NOT NULL,
	description TEXT,
	PRIMARY KEY (id)
);

--
-- List of members that conform an asset
-- "member" is for example: 10.10.10.10, /etc/passwd
-- "member_type" is for example: host, file
--
CREATE TABLE bp_asset_member (
	asset_id	INT NOT NULL REFERENCES bp_asset(id),
	member      TEXT NOT NULL,
	member_type VARCHAR(255) NOT NULL REFERENCES bp_asset_member_type(type_name)
);

--
-- List of supported member types
--
CREATE TABLE bp_asset_member_type (
	type_name  VARCHAR(255) NOT NULL UNIQUE,
	PRIMARY KEY (type_name)
);

--
-- Which assets belongs to which business process (and its relevance)
-- Note: the same asset could belong to many processes
--
CREATE TABLE bp_process_asset_reference (
	process_id  INT NOT NULL REFERENCES bp_process(id),
	asset_id    INT NOT NULL REFERENCES bp_asset(id),
	severity    INT(2) NOT NULL, /* How important is that asset (0 - low, 1 - medium, 2 - high) */
	PRIMARY KEY (process_id, asset_id)
);

--
-- Lists persons responsible of that asset
--
CREATE TABLE bp_asset_responsible (
	asset_id 	INT NOT NULL REFERENCES bp_asset(id),
	login 		VARCHAR(64) NOT NULL REFERENCES users(login),
	PRIMARY KEY (asset_id, login)
);

--
-- Status of the diferent members
-- (frameworkd fills that table)
--
-- col "measure_type" is for example: alarm, vulnerability, incident, metric
--
CREATE TABLE bp_member_status (
	member        TEXT NOT NULL REFERENCES bp_asset_member(member),
	status_date   DATETIME NOT NULL,
	measure_type  VARCHAR(255) NOT NULL,
	severity      INT(2) NOT NULL /* number between 0-10: 0 = ok, 2 = low, 5 = med, 7 = high */
);

--
-- Sequence used for the business process related tables
--
CREATE TABLE bp_seq (
	id INT NOT NULL
);
INSERT INTO bp_seq (id) VALUES (0);

-----------------------------------------------
INSERT INTO bp_asset_member_type (type_name) VALUES ('host');


-- Nagios events
REPLACE INTO plugin (id, type, name, description) VALUES (1525, 1, 'nagios', 'Nagios: host, service and network monitor');

REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 1, 2, 3, 'nagios: host alert - hard down');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 2, 1, 3, 'nagios: host alert - hard up');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 3, 2, 3, 'nagios: host alert - hard unreachable');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 4, 1, 1, 'nagios: host alert - soft down');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 5, 0, 1, 'nagios: host alert - soft up');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 6, 1, 1, 'nagios: host alert - soft unreachable');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 7, 2, 3, 'nagios: service alert - hard critical');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 8, 1, 3, 'nagios: service alert - hard ok');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 9, 1, 2, 'nagios: service alert - hard unknown');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 10, 1, 2, 'nagios: service alert - hard warning');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 11, 1, 1, 'nagios: service alert - soft critical');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 12, 0, 1, 'nagios: service alert - soft ok');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 13, 1, 1, 'nagios: service alert - soft unknown');
REPLACE INTO plugin_sid (plugin_id, sid, priority, reliability, name) VALUES (1525, 14, 1, 1, 'nagios: service alert - soft warning');

--
-- Plugin Scheduler
--
CREATE TABLE plugin_scheduler(
    id          INT NOT NULL,
    plugin VARCHAR(255) NOT NULL,
    plugin_minute VARCHAR(255) NOT NULL,
    plugin_hour VARCHAR(255) NOT NULL,
    plugin_day_month VARCHAR(255) NOT NULL,
    plugin_month VARCHAR(255) NOT NULL,
    plugin_day_week VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE plugin_scheduler_sensor_reference(
    plugin_scheduler_id          INT NOT NULL,
    sensor_name VARCHAR(255) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, sensor_name)
);

CREATE TABLE plugin_scheduler_seq (
    id INT NOT NULL
);
INSERT INTO plugin_scheduler_seq (id) VALUES (0);

--
-- Policy
--
DELETE FROM config WHERE conf = 'server_correlate';
DELETE FROM config WHERE conf = 'server_cross_correlate';
DELETE FROM config WHERE conf = 'server_qualify';
DELETE FROM config WHERE conf = 'server_store';
DELETE FROM config WHERE conf = 'server_resend_alarm';
DELETE FROM config WHERE conf = 'server_resend_event';

CREATE TABLE policy_target_reference (
    policy_id       int NOT NULL,
    target_name     varchar(64),   /*this is the target to wich applies the policy, it can be server or sensor names*/
    PRIMARY KEY     (policy_id, target_name)
);

DELETE FROM config WHERE conf = 'server_resend_event';
ALTER TABLE policy DROP COLUMN store;
ALTER TABLE policy DROP COLUMN server_name;

--
-- Server role
--
CREATE TABLE server_role (
    name            varchar(64) NOT NULL,
    correlate       BOOLEAN NOT NULL DEFAULT '1',
    cross_correlate BOOLEAN NOT NULL DEFAULT '1',
    store           BOOLEAN NOT NULL DEFAULT '1',
    qualify         BOOLEAN NOT NULL DEFAULT '1',
    resend_alarm    BOOLEAN NOT NULL DEFAULT '1',
    resend_event    BOOLEAN NOT NULL DEFAULT '1',
    PRIMARY KEY     (name)
);

CREATE TABLE server (
    name            varchar(64) NOT NULL,
    ip              varchar(15) NOT NULL,
    port            int NOT NULL,
    descr           varchar(255) NOT NULL,
    PRIMARY KEY     (name)
);

-- Needed for nessus scheduler
REPLACE INTO config (conf, value) VALUES ('frameworkd_dir', '/usr/share/ossim-framework/ossimframework');

-- New snare sid
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1518, 10, NULL, NULL, 'Snare Agent for Windows: Document printed');

-- Incident vulnerabilities

ALTER TABLE incident CHANGE ref ref enum('Alarm','Alert','Event','Metric','Anomaly','Vulnerability') not null default 'Alarm';

CREATE TABLE incident_vulns (
    id int(11) NOT NULL,
    incident_id int(11) NOT NULL,
    ip varchar(255) NOT NULL,
    port varchar(255) NOT NULL,
    nessus_id varchar(255) NOT NULL,
    risk varchar(255) NOT NULL,
    description text default NULL,
    PRIMARY KEY (id,incident_id)
);

CREATE TABLE incident_vulns_seq (
    id int(11) NOT NULL
);
INSERT INTO incident_vulns_seq VALUES(0);

INSERT INTO incident_tag_descr VALUES(65001,'OSSIM_INTERNAL_PENDING','DONT DELETE');
INSERT INTO incident_tag_descr VALUES(65002,'OSSIM_INTERNAL_FALSE_POSITIVE','DONT DELETE');

INSERT INTO incident_type (id, descr) VALUES ('Nessus Vulnerability',"");


-- New plugins for cross correlation
INSERT INTO plugin (id, type, name, description) VALUES (5001, 4, "os", "Operating Systems");
INSERT INTO plugin (id, type, name, description) VALUES (5002, 4, "services", "Services / Ports");

--new plugin sid's (from OS).
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 1, NULL, NULL, 1, 1, "Windows");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 2, NULL, NULL, 1, 1, "Linux");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 3, NULL, NULL, 1, 1, "Cisco");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 4, NULL, NULL, 1, 1, "BSD");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 5, NULL, NULL, 1, 1, "FreeBSD");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 6, NULL, NULL, 1, 1, "NetBSD");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 7, NULL, NULL, 1, 1, "OpenBSD");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 8, NULL, NULL, 1, 1, "HP-UX");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 9, NULL, NULL, 1, 1, "Solaris");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 10, NULL, NULL, 1, 1, "Macos");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 11, NULL, NULL, 1, 1, "Plan9");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 12, NULL, NULL, 1, 1, "SCO");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 13, NULL, NULL, 1, 1, "AIX");
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (5001, 14, NULL, NULL, 1, 1, "UNIX");


REPLACE INTO plugin(id, type, name, description) VALUES(5003, 4, "ovsdb", "Open Source Vulnerability Database");


--New osvdb DB config info

INSERT INTO config (conf, value) VALUES ('osvdb_type', 'mysql');
INSERT INTO config (conf, value) VALUES ('osvdb_base', 'osvdb');
INSERT INTO config (conf, value) VALUES ('osvdb_user', 'root');
INSERT INTO config (conf, value) VALUES ('osvdb_pass', 'ossim');
INSERT INTO config (conf, value) VALUES ('osvdb_host', 'localhost');

REPLACE INTO config (conf, value) VALUES ('vulnerability_incident_threshold', '0');


--Rename ossim.net.priority
ALTER TABLE net CHANGE priority asset int NOT NULL;


-- New table for storing user oriented data
CREATE TABLE user_config (
	login VARCHAR(64)  NOT NULL REFERENCES users (login),
    category VARCHAR(64) NOT NULL DEFAULT 'main',
    name VARCHAR(64) NOT NULL,
    value TEXT,
    PRIMARY KEY (login, category, name)
);

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '0.9.9rc4');
