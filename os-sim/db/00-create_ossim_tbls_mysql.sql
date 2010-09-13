
/* ======== config ======== */
DROP TABLE IF EXISTS config;
CREATE TABLE config (
    conf    varchar(255) NOT NULL,
    value   TEXT,
    PRIMARY KEY (conf)
);

DROP TABLE IF EXISTS user_config;
CREATE TABLE user_config (
    login VARCHAR(64)  NOT NULL REFERENCES users (login),
    category VARCHAR(64) NOT NULL DEFAULT 'main',
    name VARCHAR(64) NOT NULL,
    value MEDIUMTEXT,
    PRIMARY KEY (login, category, name)
);

/* ======== hosts & nets ======== */
DROP TABLE IF EXISTS host;
CREATE TABLE host (
  ip                varchar(15) UNIQUE NOT NULL,
  hostname          varchar(128) NOT NULL,
  asset             smallint(6) NOT NULL,
  threshold_c       int NOT NULL,
  threshold_a       int NOT NULL,
  alert             int NOT NULL,
  persistence       int NOT NULL,
  nat               varchar(15),
  rrd_profile       varchar(64),
  descr             varchar(255),
  lat                varchar(255) DEFAULT 0,
  lon                varchar(255) DEFAULT 0,
  PRIMARY KEY       (ip)
);

DROP TABLE IF EXISTS host_group;
CREATE TABLE host_group (
  name              varchar(128) UNIQUE NOT NULL,
  threshold_c       int NOT NULL,
  threshold_a       int NOT NULL,
  rrd_profile       varchar(64),
  descr             varchar(255),
  PRIMARY KEY       (name)
);

DROP TABLE IF EXISTS host_group_scan;
CREATE TABLE host_group_scan (
  host_group_name               varchar(128) NOT NULL,
  plugin_id       INTEGER NOT NULL,
  plugin_sid      INTEGER NOT NULL,
  PRIMARY KEY (host_group_name, plugin_id, plugin_sid)
);

DROP TABLE IF EXISTS host_group_reference;
CREATE TABLE host_group_reference (
  host_group_name        varchar(128) NOT NULL,
  host_ip                varchar(15) NOT NULL,
  PRIMARY KEY     (host_group_name, host_ip)
);


DROP TABLE IF EXISTS host_group_sensor_reference;
CREATE TABLE host_group_sensor_reference (
    group_name      varchar(128) NOT NULL,
    sensor_name     varchar(64) NOT NULL,
    PRIMARY KEY     (group_name, sensor_name)
);


DROP TABLE IF EXISTS host_apps;
CREATE TABLE IF NOT EXISTS host_apps (
  ip                INT( 10 ) UNSIGNED NOT NULL ,
  app               TEXT NOT NULL ,
  KEY `ip` (`ip`)
);

DROP TABLE IF EXISTS net;
CREATE TABLE net (
  name              varchar(128) UNIQUE NOT NULL,
  ips               TEXT NOT NULL,
  asset             int NOT NULL,
  threshold_c       int NOT NULL,
  threshold_a       int NOT NULL,
  alert             int NOT NULL,
  persistence       int NOT NULL,
  rrd_profile       varchar(64),
  descr             varchar(255),
  PRIMARY KEY       (name)
);


DROP TABLE IF EXISTS net_group;
CREATE TABLE net_group (
  name              varchar(128) UNIQUE NOT NULL,
  threshold_c       int NOT NULL,
  threshold_a       int NOT NULL,
  rrd_profile       varchar(64),
  descr             varchar(255),
  PRIMARY KEY       (name)
);

DROP TABLE IF EXISTS net_group_scan;
CREATE TABLE net_group_scan (
    net_group_name               varchar(128) NOT NULL,
      plugin_id       INTEGER NOT NULL,
      plugin_sid      INTEGER NOT NULL,
      PRIMARY KEY (net_group_name, plugin_id, plugin_sid)
);

DROP TABLE IF EXISTS net_group_reference;
CREATE TABLE net_group_reference (
    net_group_name        varchar(128) NOT NULL,
    net_name     varchar(128) NOT NULL,
    PRIMARY KEY     (net_group_name, net_name)
);

DROP TABLE IF EXISTS network_device;
CREATE TABLE network_device (
  ip bigint NOT NULL,
  community varchar(128) NOT NULL,
  descr varchar(255),
  PRIMARY KEY (ip)
);
                
DROP TABLE IF EXISTS `inventory_search`;
CREATE TABLE IF NOT EXISTS `inventory_search` (
  `type` varchar(32) NOT NULL,
  `subtype` varchar(32) NOT NULL,
  `match` enum('text','ip','fixed','boolean','date','number','concat') NOT NULL,
  `list` varchar(255) default NULL,
  `query` text NOT NULL,
  `ruleorder` int(11) NOT NULL default '999',
  PRIMARY KEY  (`type`,`subtype`)
);

/* ======== signatures ======== */
DROP TABLE IF EXISTS signature_group;
CREATE TABLE signature_group (
  name              varchar(64) NOT NULL,
  descr             varchar(255),
  PRIMARY KEY       (name)
);

DROP TABLE IF EXISTS signature;
CREATE TABLE signature (
  name              varchar(64) NOT NULL,
  PRIMARY KEY       (name)
);

DROP TABLE IF EXISTS signature_group_reference;
CREATE TABLE signature_group_reference (
    sig_group_name    varchar(64) NOT NULL,
    sig_name          varchar(64) NOT NULL,
    PRIMARY KEY      (sig_group_name, sig_name)
);

/* ======== ports ======== */
DROP TABLE IF EXISTS port_group;
CREATE TABLE port_group (
    name            varchar(64) NOT NULL,
    descr           varchar(255),
    PRIMARY KEY     (name)
);

DROP TABLE IF EXISTS port;
CREATE TABLE port (
  port_number       int NOT NULL,
  protocol_name     varchar(12) NOT NULL,
  service           varchar(64),
  descr             varchar(255),
  PRIMARY KEY       (port_number,protocol_name)
);


DROP TABLE IF EXISTS port_group_reference;
CREATE TABLE port_group_reference (
    port_group_name varchar(64) NOT NULL,
    port_number     int NOT NULL,
    protocol_name   varchar(12) NOT NULL,
    PRIMARY KEY     (port_group_name, port_number, protocol_name)
);


INSERT INTO port_group (name, descr) VALUES ('ANY', 'Any port');
INSERT INTO port_group_reference (port_group_name, port_number, protocol_name) VALUES ('ANY', 0, 'tcp');
INSERT INTO port_group_reference (port_group_name, port_number, protocol_name) VALUES ('ANY', 0, 'udp');
INSERT INTO port_group_reference (port_group_name, port_number, protocol_name) VALUES ('ANY', 0, 'icmp');


DROP TABLE IF EXISTS protocol;
CREATE TABLE protocol (
  id                int NOT NULL,
  name              varchar(24) NOT NULL,
  alias             varchar(24),
  descr             varchar(255) NOT NULL,
  PRIMARY KEY       (id)
);


/* ======== sensors ======== */
DROP TABLE IF EXISTS sensor;
CREATE TABLE sensor (
    name            varchar(64) NOT NULL,
    ip              varchar(15) NOT NULL,
    priority        smallint NOT NULL,
    port            int NOT NULL,
    connect         smallint NOT NULL,
/*    sig_group_id    int  NOT NULL, */
    descr           varchar(255) NOT NULL,
    PRIMARY KEY     (name)
);

/*This table is necessary to give a name to each interface in the sensor. i.e. used in ntop */
DROP TABLE IF EXISTS sensor_interfaces;
CREATE TABLE sensor_interfaces (
    sensor  varchar(64) NOT NULL,
    interface varchar(64) NOT NULL,
    name    varchar(255) NOT NULL,
    main    int NOT NULL,
    PRIMARY KEY (sensor, interface)
);


DROP TABLE IF EXISTS host_sensor_reference;
CREATE TABLE host_sensor_reference (
    host_ip         varchar(15) NOT NULL,
    sensor_name     varchar(64) NOT NULL,
    PRIMARY KEY     (host_ip, sensor_name)
);

DROP TABLE IF EXISTS net_sensor_reference;
CREATE TABLE net_sensor_reference (
    net_name        varchar(128) NOT NULL,
    sensor_name     varchar(64) NOT NULL,
    PRIMARY KEY     (net_name, sensor_name)
);

/*Used to store how much events arrive to the server in a specific time, from a
 * specific sensor*/
DROP TABLE IF EXISTS sensor_stats;
CREATE TABLE sensor_stats (
    name            varchar(64) NOT NULL,
    events          int NOT NULL DEFAULT 0,
    os_events       int NOT NULL DEFAULT 0,
    mac_events      int NOT NULL DEFAULT 0,
    service_events  int NOT NULL DEFAULT 0,
    ids_events      int NOT NULL DEFAULT 0,
    PRIMARY KEY     (name)
);

/* ======== policy ======== */
DROP TABLE IF EXISTS policy;
CREATE TABLE policy (
  `id` int(11) NOT NULL auto_increment,
  `priority` smallint(6) NOT NULL,
  `active` int(11) NOT NULL,
  `group` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `descr` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `group` (`group`),
  KEY `order` (`order`)
);

DROP TABLE IF EXISTS policy_group;
CREATE TABLE `policy_group` (
  `group_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `order` INT(11) NOT NULL,
  PRIMARY KEY  (`group_id`)
);

DROP TABLE IF EXISTS policy_seq;
CREATE TABLE policy_seq (
    id INT NOT NULL
);
INSERT INTO policy_seq (id) VALUES (0);

DROP TABLE IF EXISTS policy_port_reference;
CREATE TABLE policy_port_reference (
    policy_id       int NOT NULL,
    port_group_name varchar(64) NOT NULL,
    PRIMARY KEY     (policy_id, port_group_name)
);

DROP TABLE IF EXISTS policy_host_reference;
CREATE TABLE policy_host_reference (
    policy_id       int NOT NULL,
    host_ip         varchar(15) NOT NULL,
    direction       enum ('source', 'dest') NOT NULL,
    PRIMARY KEY (policy_id, host_ip, direction)
);

DROP TABLE IF EXISTS policy_net_reference;
CREATE TABLE policy_net_reference (
    policy_id       int NOT NULL,
    net_name        varchar(128) NOT NULL,
    direction       enum ('source', 'dest') NOT NULL,
    PRIMARY KEY (policy_id, net_name, direction)
);

DROP TABLE IF EXISTS policy_sensor_reference;
CREATE TABLE policy_sensor_reference (
    policy_id       int NOT NULL,
    sensor_name     varchar(64) NOT NULL,
    PRIMARY KEY     (policy_id, sensor_name)
);

DROP TABLE IF EXISTS policy_sig_reference;
CREATE TABLE policy_sig_reference (
    policy_id       int NOT NULL,
    sig_group_name  varchar(64) NOT NULL,
    PRIMARY KEY     (policy_id, sig_group_name)
);

DROP TABLE IF EXISTS policy_time;
CREATE TABLE policy_time (
    policy_id       int NOT NULL,
    begin_hour      smallint NOT NULL,
    end_hour        smallint NOT NULL,
    begin_day       smallint NOT NULL,
    end_day         smallint NOT NULL,
    PRIMARY KEY     (policy_id)
);

DROP TABLE IF EXISTS policy_plugin_reference;
CREATE TABLE policy_plugin_reference (
    policy_id       int NOT NULL,
    plugin_id        INTEGER NOT NULL,
    PRIMARY KEY     (policy_id, plugin_id)
);

DROP TABLE IF EXISTS policy_plugin_sid_reference;
CREATE TABLE policy_plugin_sid_reference (
    policy_id       int NOT NULL,
    plugin_sid      INTEGER NOT NULL,
    PRIMARY KEY     (policy_id, plugin_sid)
);

DROP TABLE IF EXISTS policy_plugin_group_reference;
CREATE TABLE policy_plugin_group_reference (
    policy_id       INTEGER NOT NULL REFERENCES policy(id),
    group_id        INTEGER NOT NULL REFERENCES plugin_group_descr(group_id),
    PRIMARY KEY (policy_id, group_id)
);

DROP TABLE IF EXISTS policy_role_reference;
CREATE TABLE policy_role_reference (
    policy_id       INTEGER NOT NULL REFERENCES policy(id),
    correlate       BOOLEAN    NOT NULL DEFAULT '1',
    cross_correlate BOOLEAN    NOT NULL DEFAULT '1',
    store           BOOLEAN    NOT NULL DEFAULT '1',
    qualify         BOOLEAN    NOT NULL DEFAULT '1',
    resend_alarm    BOOLEAN    NOT NULL DEFAULT '1',
    resend_event    BOOLEAN    NOT NULL DEFAULT '1',
    sign            INT(10) unsigned NOT NULL default '0',
    sem             TINYINT(1) NOT NULL default '1',
    sim             TINYINT(1) NOT NULL default '1',
    PRIMARY KEY (policy_id)
);

DROP TABLE IF EXISTS policy_target_reference;
CREATE TABLE policy_target_reference (
    policy_id       int NOT NULL,
    target_name     varchar(64),   /*this is the target to wich applies the policy, it can be server or sensor names*/
    PRIMARY KEY     (policy_id, target_name)
);

DROP TABLE IF EXISTS policy_host_group_reference;
CREATE TABLE IF NOT EXISTS policy_host_group_reference (
    policy_id int(11) NOT NULL,
    host_group_name varchar(128) NOT NULL,
    direction enum('source','dest') NOT NULL,
    PRIMARY KEY (policy_id,host_group_name,direction)
);

DROP TABLE IF EXISTS policy_net_group_reference;
CREATE TABLE IF NOT EXISTS policy_net_group_reference (
    policy_id int(11) NOT NULL,
    net_group_name varchar(128) NOT NULL,
    direction enum('source','dest') NOT NULL,
    PRIMARY KEY (policy_id,net_group_name,direction)
);

/* ======== servers ======== */

/* This table is needed only in multi-level architecture in the upper master
 * server. This is filled with
 * the information in server's config.xml, and from the children servers wich
 * connects into the master */

DROP TABLE IF EXISTS server_role;
CREATE TABLE server_role (
    name            varchar(64) NOT NULL,
    correlate       BOOLEAN    NOT NULL DEFAULT '1',
    cross_correlate BOOLEAN    NOT NULL DEFAULT '1',
    store           BOOLEAN    NOT NULL DEFAULT '1',
    qualify         BOOLEAN    NOT NULL DEFAULT '1',
    resend_alarm    BOOLEAN    NOT NULL DEFAULT '1',
    resend_event    BOOLEAN    NOT NULL DEFAULT '1',
    sign            INT(10) unsigned NOT NULL default '0',
    sim             TINYINT(1) NOT NULL default '1',
    sem             TINYINT(1) NOT NULL default '1',
    alarms_to_syslog BOOLEAN   NOT NULL DEFAULT '0',
    PRIMARY KEY     (name)
);

DROP TABLE IF EXISTS server;
CREATE TABLE server (
    name            varchar(64) NOT NULL,
    ip              varchar(15) NOT NULL,
    port            int NOT NULL,
    descr           varchar(255) NOT NULL,
    PRIMARY KEY     (name)
);


/* ======== actions ======== */
DROP TABLE IF EXISTS action;
CREATE TABLE action (
    id              int NOT NULL auto_increment,
    action_type     varchar(100) NOT NULL,
    cond            VARCHAR(255) NOT NULL,
    on_risk         TINYINT(1) NOT NULL,
    descr           varchar(255) NOT NULL,
    PRIMARY KEY     (id)
);

DROP TABLE IF EXISTS action_type;
CREATE TABLE action_type (
    _type            varchar (100) NOT NULL,
    descr           varchar (255) NOT NULL,
    PRIMARY KEY     (_type)
);

INSERT INTO action_type (_type, descr) VALUES ("email", "send an email message");
INSERT INTO action_type (_type, descr) VALUES ("exec", "execute an external program");


DROP TABLE IF EXISTS action_email;
CREATE TABLE action_email (
    action_id       int NOT NULL,
    _from           varchar(255) NOT NULL,
    _to             varchar(255) NOT NULL,
    subject         text,
    message         text,
    PRIMARY KEY     (action_id)
);


DROP TABLE IF EXISTS action_exec;
CREATE TABLE action_exec (
    action_id       int NOT NULL,
    command         TEXT NOT NULL,
    PRIMARY KEY     (action_id)
);

DROP TABLE IF EXISTS action_risk;
CREATE TABLE action_risk (
    action_id  int(11) NOT NULL,
    backlog_id int(11) NOT NULL,
    risk       int(11) NOT NULL,
   PRIMARY KEY (action_id,backlog_id)
);
        
/* ======== response ========== */
DROP TABLE IF EXISTS response;
CREATE TABLE response (
    id          int NOT NULL auto_increment,
    descr       varchar(255),
    PRIMARY KEY (id)
);


DROP TABLE IF EXISTS response_host;
CREATE TABLE response_host (
    response_id int NOT NULL,
    host        varchar(15),
    _type       ENUM ('source', 'dest', 'sensor') NOT NULL DEFAULT 'source',
    PRIMARY KEY (response_id, host, _type)
);

DROP TABLE IF EXISTS response_net;
CREATE TABLE response_net (
    response_id int NOT NULL,
    net         varchar(255),
    _type       ENUM ('source', 'dest') NOT NULL DEFAULT 'source',
    PRIMARY KEY (response_id, net, _type)
);

DROP TABLE IF EXISTS response_port;
CREATE TABLE response_port (
    response_id int NOT NULL,
    port        int NOT NULL,
    _type       ENUM ('source', 'dest') NOT NULL DEFAULT 'source',
    PRIMARY KEY (response_id, port, _type)
);

DROP TABLE IF EXISTS response_plugin;
CREATE TABLE response_plugin (
    response_id int NOT NULL,
    plugin_id   int NOT NULL,
    PRIMARY KEY (response_id, plugin_id)
);

DROP TABLE IF EXISTS response_action;
CREATE TABLE response_action (
    response_id int NOT NULL,
    action_id   int NOT NULL,
    PRIMARY KEY (response_id, action_id)
);


/* ======== qualification ======== */
DROP TABLE IF EXISTS host_qualification;
CREATE TABLE host_qualification (
    host_ip         varchar(15) NOT NULL,
    compromise      int NOT NULL DEFAULT 1,
    attack          int NOT NULL DEFAULT 1,
    PRIMARY KEY     (host_ip)
);

DROP TABLE IF EXISTS net_qualification;
CREATE TABLE net_qualification (
    net_name        varchar(128) NOT NULL,
    compromise      int NOT NULL DEFAULT 1,
    attack          int NOT NULL DEFAULT 1,
    PRIMARY KEY     (net_name)
);

DROP TABLE IF EXISTS host_vulnerability;
CREATE TABLE host_vulnerability (
    ip              varchar(15) NOT NULL,
    scan_date       datetime,
    vulnerability   int NOT NULL DEFAULT 1,
    PRIMARY KEY     (ip, scan_date)
);

DROP TABLE IF EXISTS net_vulnerability;
CREATE TABLE net_vulnerability (
    net             varchar(128) NOT NULL,
    scan_date       datetime,
    vulnerability   int NOT NULL DEFAULT 1,
    PRIMARY KEY     (net, scan_date)
);

DROP TABLE IF EXISTS control_panel;
CREATE TABLE control_panel (
    id              varchar(128) NOT NULL,
    rrd_type        varchar(6) NOT NULL DEFAULT 'host',
    time_range      varchar(5) NOT NULL DEFAULT 'day',
    max_c           int NOT NULL,
    max_a           int NOT NULL,
    max_c_date      datetime,
    max_a_date      datetime,
    c_sec_level     float,
    a_sec_level     float,
    PRIMARY KEY     (id, rrd_type, time_range)
);
CREATE INDEX type_time ON control_panel(rrd_type,time_range);

--
-- Table: Host Mac.
--
DROP TABLE IF EXISTS host_mac;
CREATE TABLE host_mac (
    ip        INTEGER UNSIGNED NOT NULL,
    mac            VARCHAR(255) NOT NULL,
    date            DATETIME NOT NULL,
    vendor        VARCHAR(255),
    sensor        INTEGER UNSIGNED NOT NULL,
    interface   VARCHAR(64) NOT NULL,
    anom        INT DEFAULT 1,
    PRIMARY KEY     (ip, date, sensor)
);

DROP TABLE IF EXISTS host_mac_vendors;
CREATE TABLE IF NOT EXISTS host_mac_vendors (
  mac varchar(8) NOT NULL,
  vendor varchar(255) NOT NULL,
  PRIMARY KEY (mac)
);

--
-- Table: Host OS.
--
DROP TABLE IF EXISTS host_os;
CREATE TABLE host_os (
    ip        INTEGER UNSIGNED NOT NULL,
    os        VARCHAR(255) NOT NULL,
    date        DATETIME NOT NULL,
    sensor        INTEGER UNSIGNED NOT NULL,
    interface VARCHAR(64) NOT NULL,
    anom        INT DEFAULT 1,
    PRIMARY KEY    (ip,date,sensor)
);

DROP TABLE IF EXISTS host_services;
CREATE TABLE host_services (
    ip        INTEGER UNSIGNED NOT NULL,
    port    int NOT NULL,
    protocol int NOT NULL,
    service varchar(128),
    service_type varchar(128),
    version varchar(255) NOT NULL DEFAULT "unknown",
    date        DATETIME NOT NULL,
    origin  int NOT NULL DEFAULT 0,
    sensor        INTEGER UNSIGNED NOT NULL,
    interface VARCHAR(64) NOT NULL,
    anom    INT DEFAULT 1,
    nagios tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (ip, port, protocol, version, date)
);

DROP TABLE IF EXISTS host_netbios;
CREATE TABLE host_netbios (
    ip      varchar(15) NOT NULL,
    name    varchar(128) NOT NULL,
    wgroup  varchar(128),
    PRIMARY KEY (ip)
);

DROP TABLE IF EXISTS rrd_config;
CREATE TABLE rrd_config (
    profile     VARCHAR(64) NOT NULL,
    rrd_attrib  VARCHAR(60) NOT NULL,
    threshold   INTEGER UNSIGNED NOT NULL,
    priority    INTEGER UNSIGNED NOT NULL,
    alpha       FLOAT UNSIGNED  NOT NULL,
    beta        FLOAT UNSIGNED NOT NULL,
    persistence INTEGER UNSIGNED NOT NULL,
    enable      TINYINT DEFAULT 1,
    description TEXT,
    PRIMARY KEY (profile, rrd_attrib)
);


DROP TABLE IF EXISTS rrd_anomalies;
CREATE TABLE rrd_anomalies (
    ip                      varchar(15) NOT NULL,
    what                    varchar(100) NOT NULL,
    count                   int NOT NULL,
    anomaly_time            varchar(40) NOT NULL,
    anomaly_range           VARCHAR(30) NOT NULL,
    over                    int NOT NULL,
    acked                   int DEFAULT 0
);


DROP TABLE IF EXISTS rrd_anomalies_global;
CREATE TABLE rrd_anomalies_global (
    what                    varchar(100) NOT NULL,
    count                   int NOT NULL,
    anomaly_time            varchar(40) NOT NULL,
    anomaly_range           VARCHAR(30) NOT NULL,
    over                    int NOT NULL,
    acked                   int DEFAULT 0
);

--
-- Table: Category / Subcategory
--
DROP TABLE IF EXISTS category;
CREATE TABLE category (
    id        INTEGER NOT NULL,
    name        VARCHAR (100) NOT NULL,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS subcategory;
CREATE TABLE `subcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `name` text,
  PRIMARY KEY (`id`)
);

--
-- Table: Classification
--
DROP TABLE IF EXISTS classification;
CREATE TABLE classification (
    id        INTEGER NOT NULL,
    name        VARCHAR (100) NOT NULL,
    description    TEXT,
    priority    INTEGER,
    PRIMARY KEY (id)
);

--
-- Table: Plugin
--
DROP TABLE IF EXISTS plugin;
CREATE TABLE plugin (
    id        INTEGER NOT NULL,
    type        SMALLINT NOT NULL,
    name        VARCHAR (100) NOT NULL,
    description    TEXT,
    source_type    text,
	vendor         text,
    PRIMARY KEY (id)
);

--
-- Table: Plugin Sid
--
DROP TABLE IF EXISTS plugin_sid;
CREATE TABLE plugin_sid (
    plugin_id    INTEGER NOT NULL,
    sid            INTEGER NOT NULL,
    category_id    INTEGER,
    class_id    INTEGER,
    reliability    INTEGER DEFAULT 1,
    priority    INTEGER DEFAULT 1,
    name        VARCHAR (255) NOT NULL,
    aro         DECIMAL (11,4) NOT NULL DEFAULT 0,
    subcategory_id INTEGER,
    KEY `search` ( `plugin_id` , `name` ),
    PRIMARY KEY (plugin_id, sid)
);

--
-- Tables for the Policy Groups
--

-- Table: Plugin Group Descr: store the name and description of the plugin group.
DROP TABLE IF EXISTS plugin_group_descr;
CREATE TABLE plugin_group_descr (
    group_id    INTEGER NOT NULL ,
    name        VARCHAR(125) NOT NULL,
    descr       VARCHAR(255) NOT NULL,
    PRIMARY KEY (group_id, name)
);

DROP TABLE IF EXISTS plugin_group_descr_seq;
CREATE TABLE plugin_group_descr_seq (
    id INT NOT NULL
);
INSERT INTO plugin_group_descr_seq VALUES (0);

-- Table: Plugin group: used to have a relationship between plugin's and it sids
DROP TABLE IF EXISTS plugin_group;
CREATE TABLE plugin_group (
    group_id    INTEGER NOT NULL REFERENCES plugin_group_descr(group_id),
    plugin_id   INTEGER NOT NULL REFERENCES plugin(id),
    plugin_sid  TEXT NOT NULL,
    PRIMARY KEY (group_id, plugin_id)
);

--
-- Table: Event
--

DROP TABLE IF EXISTS event;
CREATE TABLE event (
        id              BIGINT NOT NULL,
        timestamp       TIMESTAMP NOT NULL,
        sensor          TEXT NOT NULL,
        interface       TEXT NOT NULL,
        type            INTEGER NOT NULL,
        plugin_id       INTEGER NOT NULL,
        plugin_sid      INTEGER NOT NULL,
        protocol        INTEGER,
        src_ip          INTEGER UNSIGNED,
        dst_ip          INTEGER UNSIGNED,
        src_port        INTEGER,
        dst_port        INTEGER,
        event_condition       INTEGER,
        value           TEXT,
        time_interval   INTEGER,
        absolute        TINYINT,
        priority        INTEGER DEFAULT 1,
        reliability     INTEGER DEFAULT 1,
        asset_src       INTEGER DEFAULT 1,
        asset_dst       INTEGER DEFAULT 1,
        risk_a          INTEGER DEFAULT 1,
        risk_c          INTEGER DEFAULT 1,
        alarm           TINYINT DEFAULT 1,
        snort_sid       INTEGER UNSIGNED,
        snort_cid       INTEGER UNSIGNED,
        filename        TEXT,
        username        TEXT,
        password        TEXT,
        userdata1       TEXT,
        userdata2       TEXT,
        userdata3       TEXT,
        userdata4       TEXT,
        userdata5       TEXT,
        userdata6       TEXT,
        userdata7       TEXT,
        userdata8       TEXT,
        userdata9       TEXT,
	rulename	TEXT,
	uuid		CHAR(36) ASCII,
        PRIMARY KEY (id)
);
CREATE INDEX event_idx ON event (timestamp);

--
-- Table: Backlog
--
DROP TABLE IF EXISTS backlog;
CREATE TABLE backlog (
    id        BIGINT NOT NULL DEFAULT 0,
    directive_id    INTEGER NOT NULL,
    timestamp    TIMESTAMP NOT NULL,
    matched        TINYINT,
		uuid	CHAR(36) ASCII,
    PRIMARY KEY (id)
);

--
-- Table: Backlog Event
--
DROP TABLE IF EXISTS backlog_event;
CREATE TABLE backlog_event (
    backlog_id    BIGINT NOT NULL,
    event_id    BIGINT NOT NULL,
    time_out    INTEGER,
    occurrence    INTEGER,
    rule_level    INTEGER,
    matched        TINYINT,
		uuid				CHAR(36) ASCII,
		uuid_event	CHAR(36) ASCII,
    PRIMARY KEY (backlog_id, event_id)
);

CREATE INDEX event_idx ON backlog_event(event_id);


--
-- Table: Temporary Event Tables
--

DROP TABLE IF EXISTS event_tmp;
CREATE TABLE event_tmp (
        id              BIGINT NOT NULL,
        timestamp       TIMESTAMP NOT NULL,
        sensor          TEXT NOT NULL,
        interface       TEXT NOT NULL,
        type            INTEGER NOT NULL,
        plugin_id       INTEGER NOT NULL,
        plugin_sid      INTEGER NOT NULL,
        plugin_sid_name varchar(255),
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
        userdata6       TEXT,
        userdata7       TEXT,
        userdata8       TEXT,
        userdata9       TEXT,
        PRIMARY KEY (id)
);

DROP TABLE IF EXISTS event_tmp_filter;
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
        userdata6       TEXT,
        userdata7       TEXT,
        userdata8       TEXT,
        userdata9       TEXT,
        PRIMARY KEY (id, login)
);

--
-- Sequences
--

DROP TABLE IF EXISTS event_seq;
CREATE TABLE event_seq (
         id INTEGER UNSIGNED NOT NULL
);
INSERT INTO event_seq VALUES (0);

DROP TABLE IF EXISTS backlog_seq;
CREATE TABLE backlog_seq (
         id INTEGER UNSIGNED NOT NULL
);
INSERT INTO backlog_seq VALUES (0);

DROP TABLE IF EXISTS backlog_event_seq;
CREATE TABLE backlog_event_seq (
         id INTEGER UNSIGNED NOT NULL
);
INSERT INTO backlog_event_seq VALUES (0);

DROP TABLE IF EXISTS event_tmp_seq;
CREATE TABLE event_tmp_seq (
         id INTEGER UNSIGNED NOT NULL
);
INSERT INTO event_tmp_seq (id) VALUES (0);


--
-- Table: Alarm
--
DROP TABLE IF EXISTS alarm;
CREATE TABLE alarm (
        backlog_id      BIGINT NOT NULL,
        event_id        BIGINT NOT NULL,
        timestamp       TIMESTAMP NOT NULL,
        status          ENUM ("open", "closed") DEFAULT "open",
        plugin_id       INTEGER NOT NULL,
        plugin_sid      INTEGER NOT NULL,
        protocol        INTEGER,
        src_ip          INTEGER UNSIGNED,
        dst_ip          INTEGER UNSIGNED,
        src_port        INTEGER,
        dst_port        INTEGER,
        risk            INTEGER,
        snort_sid       INTEGER UNSIGNED,
        snort_cid       INTEGER UNSIGNED,
        efr             INTEGER (11) NOT NULL DEFAULT 0,
		uuid_event      CHAR(36) ASCII,
		uuid_backlog    CHAR(36) ASCII,
        PRIMARY KEY (backlog_id),
        KEY `timestamp` (`timestamp`),
        KEY `src_ip` (`src_ip`),
        KEY `dst_ip` (`dst_ip`),
        KEY `status` (`status`,`timestamp`)
);

--
-- Alarmgroups
--
DROP TABLE IF EXISTS alarm_group;
CREATE TABLE alarm_group (
        id              BIGINT(20) NOT NULL auto_increment,
        status          ENUM("open","closed") DEFAULT "open",
        timestamp       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP,
        owner           VARCHAR(64) DEFAULT NULL,
        descr           VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
);

DROP TABLE IF EXISTS alarm_group_members;
CREATE TABLE alarm_group_members (
        group_id        BIGINT(20) NOT NULL,
        backlog_id      BIGINT(20) NOT NULL,
        event_id        BIGINT(20) NOT NULL,
        PRIMARY KEY (backlog_id, event_id)
);

DROP TABLE IF EXISTS alarm_groups;
CREATE TABLE alarm_groups (
        group_id        varchar(255) NOT NULL,
        description     text NOT NULL,
        status          enum('open','closed') NOT NULL,
        timestamp       timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
        owner           varchar(64) NOT NULL,
        PRIMARY KEY  (group_id)
);            

--
-- Table: plugin_reference
--
DROP TABLE IF EXISTS plugin_reference;
CREATE TABLE plugin_reference (
    plugin_id    INTEGER NOT NULL,
    plugin_sid    INTEGER NOT NULL,
    reference_id    INTEGER NOT NULL,
    reference_sid    INTEGER NOT NULL,
    PRIMARY KEY (plugin_id, plugin_sid, reference_id, reference_sid)
);

--
-- Table: Host plugin sid
--
DROP TABLE IF EXISTS host_plugin_sid;
CREATE TABLE host_plugin_sid (
    host_ip         INTEGER UNSIGNED NOT NULL,
    plugin_id    INTEGER NOT NULL,
    plugin_sid    INTEGER NOT NULL,
    PRIMARY KEY (host_ip, plugin_id, plugin_sid)
);

--
-- Table: Host scan
--
DROP TABLE IF EXISTS host_scan;
CREATE TABLE host_scan (
    host_ip         INTEGER UNSIGNED NOT NULL,
    plugin_id    INTEGER NOT NULL,
    plugin_sid    INTEGER NOT NULL,
    PRIMARY KEY (host_ip, plugin_id, plugin_sid)
);

--
-- Table: Net scan
--
DROP TABLE IF EXISTS net_scan;
CREATE TABLE net_scan (
    net_name               varchar(128) NOT NULL,
      plugin_id       INTEGER NOT NULL,
      plugin_sid      INTEGER NOT NULL,
      PRIMARY KEY (net_name, plugin_id, plugin_sid)
);


--
-- Table: Users
--

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    login   varchar(64)  NOT NULL,
    name    varchar(128) NOT NULL,
    pass    varchar(41)  NOT NULL,
    allowed_nets    TEXT DEFAULT '' NOT NULL,
    allowed_sensors TEXT NOT NULL,
    email   varchar(64),
    company varchar(128),
    department varchar(128),
    language varchar(12) DEFAULT 'en_GB' NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT '1',
	first_login TINYINT(1) NOT NULL DEFAULT '1',
    entities varchar(64) DEFAULT '' NOT NULL,
    template_sensors int(11) DEFAULT 0 NOT NULL,
    template_assets int(11) DEFAULT 0 NOT NULL,
    template_menus int(11) DEFAULT 0 NOT NULL,
    template_policies int(11) DEFAULT 0 NOT NULL,
    inherit_sensors int(11) DEFAULT 0 NOT NULL,
    inherit_assets int(11) DEFAULT 0 NOT NULL,
    inherit_menus int(11) DEFAULT 0 NOT NULL,
    inherit_policies int(11) DEFAULT 0 NOT NULL,
	last_pass_change timestamp NOT NULL default CURRENT_TIMESTAMP,
    PRIMARY KEY (login)
);

--
-- Data: User
--
INSERT INTO users (login, name, pass) VALUES ('admin', 'OSSIM admin', '21232f297a57a5a743894a0e4a801fc3');


--
-- Table: incident
--
DROP TABLE IF EXISTS incident;
CREATE TABLE incident (
    id          INTEGER NOT NULL AUTO_INCREMENT,
    title       VARCHAR(128) NOT NULL,
    date        DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    ref         ENUM ('Alarm', 'Alert', 'Event', 'Metric', 'Anomaly', 'Vulnerability') NOT NULL DEFAULT 'Alarm',
    type_id     VARCHAR(64) NOT NULL DEFAULT "Generic",
    priority    INTEGER NOT NULL,
    status      ENUM ('Open', 'Closed') NOT NULL DEFAULT 'Open',
    last_update DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    in_charge     VARCHAR(64) NOT NULL,
    submitter   VARCHAR(64) NOT NULL,
    event_start DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    event_end   DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS incident_type;
CREATE TABLE incident_type (
    id          VARCHAR(64) NOT NULL,
    descr       VARCHAR(255) NOT NULL DEFAULT "",
    `keywords` varchar(255) NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO incident_type (id, descr, keywords) VALUES ("Generic", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Expansion Virus", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Corporative Nets Attack", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Policy Violation", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Security Weakness", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Net Performance", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Applications and Systems Failures", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ("Anomalies", "", "");
INSERT INTO incident_type (id, descr, keywords) VALUES ('Nessus Vulnerability',"", "");

--
-- Table: incident ticket
--
DROP TABLE IF EXISTS incident_ticket;
CREATE TABLE incident_ticket (
    id              INTEGER NOT NULL,
    incident_id     INTEGER NOT NULL,
    date            DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    status          ENUM ('Open', 'Closed') NOT NULL DEFAULT 'Open',
    priority        INTEGER NOT NULL,
    users           VARCHAR(64) NOT NULL,
    description     TEXT,
    action          TEXT,
    in_charge       VARCHAR(64),
    transferred     VARCHAR(64),
    PRIMARY KEY (id, incident_id)
);

DROP TABLE IF EXISTS incident_ticket_seq;
CREATE TABLE incident_ticket_seq (
    id INT NOT NULL
);
INSERT INTO incident_ticket_seq VALUES (0);

--
-- Table: incident alarm
--
DROP TABLE IF EXISTS incident_alarm;
CREATE TABLE incident_alarm (
    id              INTEGER NOT NULL AUTO_INCREMENT,
    incident_id     INTEGER NOT NULL,
    src_ips         VARCHAR(255) NOT NULL,
    src_ports       VARCHAR(255) NOT NULL,
    dst_ips         VARCHAR(255) NOT NULL,
    dst_ports       VARCHAR(255) NOT NULL,
    backlog_id      BIGINT(20) NOT NULL,
    event_id        BIGINT(20) NOT NULL,
    alarm_group_id  BIGINT(20),
    PRIMARY KEY (id, incident_id)
);

--
-- Table: incident event
--
DROP TABLE IF EXISTS incident_event;
CREATE TABLE incident_event (
    id              INTEGER NOT NULL AUTO_INCREMENT,
    incident_id     INTEGER NOT NULL,
    src_ips         VARCHAR(255) NOT NULL,
    src_ports       VARCHAR(255) NOT NULL,
    dst_ips         VARCHAR(255) NOT NULL,
    dst_ports       VARCHAR(255) NOT NULL,
    PRIMARY KEY (id, incident_id)
);

--
-- Table: incident metric
--
DROP TABLE IF EXISTS incident_metric;
CREATE TABLE incident_metric (
    id              INTEGER NOT NULL AUTO_INCREMENT,
    incident_id     INTEGER NOT NULL,
    target          VARCHAR(255) NOT NULL,
    metric_type     ENUM ('Compromise', 'Attack', 'Level') NOT NULL DEFAULT 'Compromise',
    metric_value    INTEGER NOT NULL,
    PRIMARY KEY (id, incident_id)
);

DROP TABLE IF EXISTS incident_file;
CREATE TABLE incident_file (
    id              INTEGER NOT NULL AUTO_INCREMENT,
    incident_id     INTEGER NOT NULL,
    incident_ticket INTEGER NOT NULL,
    name            VARCHAR(50),
    type            VARCHAR(50),
    content         mediumblob, /* 16Mb */
    PRIMARY KEY (id, incident_id, incident_ticket)
);

--
-- Table: incident anomaly
--
DROP TABLE IF EXISTS incident_anomaly;
CREATE TABLE incident_anomaly (
     id              INTEGER NOT NULL AUTO_INCREMENT,
     incident_id     INTEGER NOT NULL,
     anom_type       ENUM ('mac', 'service', 'os') NOT NULL  DEFAULT 'mac',
     ip              VARCHAR(255) NOT NULL,
     data_orig       VARCHAR(255) NOT NULL,
     data_new        VARCHAR(255) NOT NULL,
     PRIMARY KEY (id, incident_id)
);

--
-- Table: incident vulnerabilities
--

DROP TABLE IF EXISTS incident_vulns;
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

DROP TABLE IF EXISTS incident_vulns_seq;
CREATE TABLE incident_vulns_seq (
    id int(11) NOT NULL
);
INSERT INTO incident_vulns_seq VALUES(0);


--
-- Table: incident TAGs
--
DROP TABLE IF EXISTS incident_tag_descr;
CREATE TABLE incident_tag_descr (
        id INT(11) NOT NULL,
        name VARCHAR(64),
        descr TEXT,
        PRIMARY KEY(id)
);

DROP TABLE IF EXISTS incident_tag;
CREATE TABLE incident_tag (
        tag_id INT(11) NOT NULL REFERENCES incident_tags_descr(id),
        incident_id INT(11) NOT NULL REFERENCES incident(id),
        PRIMARY KEY (tag_id, incident_id)
);

DROP TABLE IF EXISTS incident_tag_descr_seq;
CREATE TABLE incident_tag_descr_seq (
        id INT NOT NULL
);
INSERT INTO incident_tag_descr_seq VALUES (0);
INSERT INTO incident_tag_descr VALUES(65001,'OSSIM_INTERNAL_PENDING','DONT DELETE');
INSERT INTO incident_tag_descr VALUES(65002,'OSSIM_INTERNAL_FALSE_POSITIVE','DONT DELETE');


--
-- Table: incident_subscrip
--
DROP TABLE IF EXISTS incident_subscrip;
CREATE TABLE incident_subscrip (
    login VARCHAR(64) NOT NULL REFERENCES users(login),
    incident_id INT(11) NOT NULL REFERENCES incident(id),
    PRIMARY KEY (login, incident_id)
);

--
-- Table: restoredb
--
DROP TABLE IF EXISTS restoredb_log;
CREATE TABLE restoredb_log (
    id        INTEGER NOT NULL AUTO_INCREMENT,
    date        TIMESTAMP,
    pid        INTEGER,
    users        VARCHAR(64),
    data        TEXT,
    status        SMALLINT,
    percent        SMALLINT,
    PRIMARY KEY (id)
);

--
-- HIDS (Osiris) Support
--

DROP TABLE IF EXISTS host_ids;
CREATE TABLE host_ids(
    ip              INTEGER UNSIGNED NOT NULL,
    date            DATETIME NOT NULL,
    hostname        VARCHAR(255) NOT NULL,
    sensor          VARCHAR(255) NOT NULL,
    plugin_sid             INTEGER UNSIGNED NOT NULL,
    event_type      VARCHAR(255) NOT NULL,
    what            VARCHAR(255) NOT NULL,
    target          VARCHAR(255) NOT NULL,
    extra_data      VARCHAR(255) NOT NULL,
    cid             INTEGER UNSIGNED NOT NULL,
    sid             INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY     (ip,target,plugin_sid,date)
);

--
-- User action logging
--

DROP TABLE IF EXISTS log_config;
CREATE TABLE log_config(
    code        INTEGER UNSIGNED NOT NULL,
    log         BOOL    DEFAULT "0",
    descr       VARCHAR(255) NOT NULL,
    priority    INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (code)
);

DROP TABLE IF EXISTS log_action;
CREATE TABLE log_action(
    login       VARCHAR(255) NOT NULL,
    ipfrom        VARCHAR(15) NOT NULL,
    date        TIMESTAMP,
    code        INTEGER UNSIGNED NOT NULL,
    info        VARCHAR(255) NOT NULL,
    PRIMARY KEY (date, code, info)
);

--
-- Business Processes
--

DROP TABLE IF EXISTS bp_process;
CREATE TABLE bp_process (
    id          INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    valuation   DECIMAL (11,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);

--
-- List of assets conforming a bussiness process
--
DROP TABLE IF EXISTS bp_asset;
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
DROP TABLE IF EXISTS bp_asset_member;
CREATE TABLE bp_asset_member (
    asset_id    INT NOT NULL REFERENCES bp_asset(id),
    member      TEXT NOT NULL,
    member_type VARCHAR(255) NOT NULL REFERENCES bp_asset_member_type(type_name)
);

--
-- List of supported member types
--
DROP TABLE IF EXISTS bp_asset_member_type;
CREATE TABLE bp_asset_member_type (
    type_name  VARCHAR(255) NOT NULL UNIQUE,
    PRIMARY KEY (type_name)
);
INSERT INTO bp_asset_member_type (type_name) VALUES ('host');
INSERT INTO bp_asset_member_type (type_name) VALUES ('net');
INSERT INTO bp_asset_member_type (type_name) VALUES ('file');
INSERT INTO bp_asset_member_type (type_name) VALUES ('host_group');
INSERT INTO bp_asset_member_type (type_name) VALUES ('net_group');

--
-- Which assets belongs to which business process (and its relevance)
-- Note: the same asset could belong to many processes
--
DROP TABLE IF EXISTS bp_process_asset_reference;
CREATE TABLE bp_process_asset_reference (
    process_id  INT NOT NULL REFERENCES bp_process(id),
    asset_id    INT NOT NULL REFERENCES bp_asset(id),
    severity    INT(2) NOT NULL, /* How important is that asset (0 - low, 1 - medium, 2 - high) */
    bpte        INT(3) NOT NULL DEFAULT '0',
    PRIMARY KEY (process_id, asset_id)
);

--
-- Lists persons responsible of that asset
--
DROP TABLE IF EXISTS bp_asset_responsible;
CREATE TABLE bp_asset_responsible (
    asset_id     INT NOT NULL REFERENCES bp_asset(id),
    login         VARCHAR(64) NOT NULL REFERENCES users(login),
    PRIMARY KEY (asset_id, login)
);

--
-- Status of the diferent members
-- (frameworkd fills that table)
--
-- col "measure_type" is for example: alarm, vulnerability, incident, metric
--
DROP TABLE IF EXISTS bp_member_status;
CREATE TABLE bp_member_status (
    member        TEXT NOT NULL REFERENCES bp_asset_member(member),
    status_date   DATETIME NOT NULL,
    measure_type  VARCHAR(255) NOT NULL,
    severity      INT(2) NOT NULL /* number between 0-10: 0 = ok, 2 = low, 5 = med, 7 = high */
);

--
-- Sequence used for the business process related tables
--
DROP TABLE IF EXISTS bp_seq;
CREATE TABLE bp_seq (
    id INT NOT NULL
);
INSERT INTO bp_seq (id) VALUES (0);


--
-- Plugin Scheduler
--
DROP TABLE IF EXISTS plugin_scheduler;
CREATE TABLE plugin_scheduler(
    id          INT NOT NULL,
    plugin VARCHAR(255) NOT NULL,
    plugin_minute VARCHAR(255) NOT NULL,
    plugin_hour VARCHAR(255) NOT NULL,
    plugin_day_month VARCHAR(255) NOT NULL,
    plugin_month VARCHAR(255) NOT NULL,
    plugin_day_week VARCHAR(255) NOT NULL,
    type_scan VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS plugin_scheduler_sensor_reference;
CREATE TABLE plugin_scheduler_sensor_reference (
    plugin_scheduler_id          INT NOT NULL,
    sensor_name VARCHAR(255) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, sensor_name)
);

DROP TABLE IF EXISTS plugin_scheduler_netgroup_reference;
CREATE TABLE plugin_scheduler_netgroup_reference (
    plugin_scheduler_id          INT NOT NULL,
    netgroup_name VARCHAR(255) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, netgroup_name)
);

DROP TABLE IF EXISTS plugin_scheduler_hostgroup_reference;
CREATE TABLE plugin_scheduler_hostgroup_reference (
    plugin_scheduler_id          INT NOT NULL,
    hostgroup_name VARCHAR(255) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, hostgroup_name)
);

DROP TABLE IF EXISTS plugin_scheduler_net_reference;
CREATE TABLE plugin_scheduler_net_reference (
    plugin_scheduler_id          INT NOT NULL,
    net_name VARCHAR(255) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, net_name)
);

DROP TABLE IF EXISTS plugin_scheduler_host_reference;
CREATE TABLE plugin_scheduler_host_reference (
    plugin_scheduler_id          INT NOT NULL,
    ip  varchar(15) NOT NULL,
    PRIMARY KEY     (plugin_scheduler_id, ip)
);

DROP TABLE IF EXISTS plugin_scheduler_seq;
CREATE TABLE plugin_scheduler_seq (
    id INT NOT NULL
);
INSERT INTO plugin_scheduler_seq (id) VALUES (0);

--
-- Maps
--
DROP TABLE IF EXISTS map;
CREATE TABLE map (
    id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    engine ENUM('openlayers_op', 'openlayers_ve', 'openlayers_yahoo', 'openlayers_image'),
    engine_data1 MEDIUMTEXT,
    engine_data2 TEXT,
    engine_data3 TEXT,
    engine_data4 TEXT,
    center_x VARCHAR(255),
    center_y VARCHAR(255),
    zoom INT,
    show_controls BOOL DEFAULT 1,
    PRIMARY KEY (id)
);
DROP TABLE IF EXISTS map_seq;
CREATE TABLE map_seq (
     id INTEGER UNSIGNED NOT NULL
);
INSERT INTO map_seq VALUES (0);

DROP TABLE IF EXISTS map_element;
CREATE TABLE map_element (
    id INT NOT NULL,
    type ENUM('host', 'sensor', 'network', 'server'),
    ossim_element_key VARCHAR(255),
    map_id INT NOT NULL REFERENCES map(id),
    x VARCHAR(255),
    y VARCHAR(255),
    PRIMARY KEY (id)
);
DROP TABLE IF EXISTS map_element_seq;
CREATE TABLE map_element_seq (
     id INTEGER UNSIGNED NOT NULL
);
INSERT INTO map_element_seq VALUES (0);

DROP TABLE IF EXISTS `risk_indicators`;
CREATE TABLE `risk_indicators` (
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
  `size` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `repository`;
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

DROP TABLE IF EXISTS `repository_attachments`;
CREATE TABLE IF NOT EXISTS `repository_attachments` (
  `id` int(11) NOT NULL auto_increment,
  `id_document` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `type` varchar(4) NOT NULL,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `repository_relationships`;
CREATE TABLE IF NOT EXISTS `repository_relationships` (
  `id` int(11) NOT NULL auto_increment,
  `id_document` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `type` varchar(16) NOT NULL,
  `keyname` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
); 

DROP TABLE IF EXISTS `sensor_properties`;
CREATE TABLE IF NOT EXISTS `sensor_properties` (
  `ip` varchar(64) NOT NULL,
  `version` varchar(64) NOT NULL,
  `has_nagios` tinyint(1) NOT NULL default '1',
  `has_ntop` tinyint(1) NOT NULL default '1',
  `has_vuln_scanner` tinyint(1) NOT NULL default '1',
  `has_kismet` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ip`)
);

DROP TABLE IF EXISTS `policy_actions`;
CREATE TABLE `policy_actions` (
  `policy_id` int(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  PRIMARY KEY  (`policy_id`,`action_id`)
);

DROP TABLE IF EXISTS `sem_stats`;
CREATE TABLE `sem_stats` (
  `day` int(11) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `type` varchar(25) NOT NULL,
  `value` varchar(25) NOT NULL,
  `counter` int(11) NOT NULL,
  PRIMARY KEY  (`day`,`sensor`,`type`,`value`)
);

DROP TABLE IF EXISTS `sem_stats_events`;
CREATE TABLE `sem_stats_events` (
  `day` int(11) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `counter` int(11) NOT NULL,
  PRIMARY KEY  (`day`,`sensor`)
);

-- 
-- Wireless (Kismet plugin)
--
DROP TABLE IF EXISTS `wireless_aps`;
CREATE TABLE IF NOT EXISTS `wireless_aps` (
  `mac` varchar(20) NOT NULL,
  `ssid` varchar(255) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `nettype` varchar(32) NOT NULL,
  `info` varchar(255) NOT NULL,
  `channel` int(11) NOT NULL,
  `cloaked` enum('Yes','No') NOT NULL,
  `encryption` varchar(64) NOT NULL,
  `decrypted` enum('Yes','No') NOT NULL,
  `maxrate` float NOT NULL,
  `maxseenrate` int(11) NOT NULL,
  `beacon` int(11) NOT NULL,
  `llc` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  `crypt` int(11) NOT NULL,
  `weak` int(11) NOT NULL,
  `dupeiv` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `carrier` varchar(32) NOT NULL,
  `encoding` varchar(32) NOT NULL,
  `firsttime` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  `bestquality` int(11) NOT NULL,
  `bestsignal` int(11) NOT NULL,
  `bestnoise` int(11) NOT NULL,
  `gpsminlat` float NOT NULL,
  `gpsminlon` float NOT NULL,
  `gpsminalt` float NOT NULL,
  `gpsminspd` float NOT NULL,
  `gpsmaxlat` float NOT NULL,
  `gpsmaxlon` float NOT NULL,
  `gpsmaxalt` float NOT NULL,
  `gpsmaxspd` float NOT NULL,
  `gpsbestlat` float NOT NULL,
  `gpsbestlon` float NOT NULL,
  `gpsbestalt` float NOT NULL,
  `datasize` int(11) NOT NULL,
  `iptype` varchar(32) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `notes` tinytext NOT NULL,
  PRIMARY KEY  (`mac`,`ssid`,`sensor`),
  KEY `aps_mac_full` (`mac`(18)),
  KEY `aps_sensor_full` (`sensor`),
  KEY `aps_ssid` (`ssid`),
  KEY `encryption` (`encryption`),
  KEY `cloaked` (`cloaked`),
  KEY `mac_sensor` (`mac`,`sensor`)
);

DROP TABLE IF EXISTS `wireless_clients`;
CREATE TABLE IF NOT EXISTS `wireless_clients` (
  `client_mac` varchar(20) NOT NULL,
  `mac` varchar(20) NOT NULL,
  `ssid` varchar(255) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `plugin_sid` int(11) NOT NULL,
  `channel` int(11) NOT NULL,
  `encryption` varchar(64) NOT NULL,
  `maxrate` float NOT NULL,
  `maxseenrate` int(11) NOT NULL,
  `llc` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  `crypt` int(11) NOT NULL,
  `weak` int(11) NOT NULL,
  `dupeiv` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `encoding` varchar(32) NOT NULL,
  `firsttime` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  `gpsminlat` float NOT NULL,
  `gpsminlon` float NOT NULL,
  `gpsminalt` float NOT NULL,
  `gpsminspd` float NOT NULL,
  `gpsmaxlat` float NOT NULL,
  `gpsmaxlon` float NOT NULL,
  `gpsmaxalt` float NOT NULL,
  `gpsmaxspd` float NOT NULL,
  `datasize` int(11) NOT NULL,
  `iptype` varchar(32) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `notes` tinytext NOT NULL,
  PRIMARY KEY  (`client_mac`,`mac`,`ssid`,`sensor`),
  KEY `clients_mac_full` (`mac`(18)),
  KEY `clients_sensor_full` (`sensor`),
  KEY `clients_ssid` (`ssid`),
  KEY `client_mac_sensor_ssid` (`client_mac`,`sensor`,`ssid`)
);

DROP TABLE IF EXISTS `wireless_locations`;
CREATE TABLE IF NOT EXISTS `wireless_locations` (
  `location` varchar(100) NOT NULL,
  `user` varchar(64) NOT NULL,
  `description` varchar(255) default NULL,
  PRIMARY KEY  (`location`,`user`)
);

DROP TABLE IF EXISTS `wireless_networks`;
CREATE TABLE IF NOT EXISTS `wireless_networks` (
  `ssid` varchar(255) NOT NULL,
  `sensor` varchar(15) NOT NULL,
  `aps` int(11) NOT NULL,
  `clients` int(11) NOT NULL,
  `encryption` varchar(255) NOT NULL,
  `cloaked` varchar(15) NOT NULL,
  `firsttime` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` enum('Un-Trusted','Trusted') NOT NULL,
  `notes` tinytext NOT NULL,
  `macs` tinytext NOT NULL,
  PRIMARY KEY  (`ssid`,`sensor`)
);

DROP TABLE IF EXISTS `wireless_sensors`;
CREATE TABLE IF NOT EXISTS `wireless_sensors` (
  `sensor` varchar(64) NOT NULL,
  `location` varchar(100) NOT NULL,
  `model` varchar(150) NOT NULL,
  `serial` varchar(150) NOT NULL,
  `mounting_location` varchar(255) NOT NULL,
  `last_scraped` datetime,
  `free_space` varchar(45) NOT NULL,
  `version` varchar(45) NOT NULL,
  `avg_signal` int(10) NOT NULL,
  PRIMARY KEY  (`sensor`,`location`)
);

/* Vulnerabilities */

DROP TABLE IF EXISTS `vuln_nessus_latest_reports`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_latest_reports` (
  `report_id` int(11) unsigned NOT NULL,
  `name` varchar(50) NOT NULL default '',
  `fk_name` varchar(50) default NULL,
  `scantime` varchar(14) NOT NULL default '',
  `report_type` char(1) NOT NULL default 'N',
  `username` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `sid` int(11) NOT NULL default '0',
  `scantype` char(1) NOT NULL default 'M',
  `server_ip` varchar(15) NOT NULL default '',
  `server_nversion` varchar(100) NOT NULL default '',
  `server_feedtype` varchar(32) NOT NULL default '',
  `server_feedversion` varchar(12) NOT NULL default '',
  `domain` varchar(255) NOT NULL default '',
  `report_key` varchar(16) NOT NULL default '',
  `report_path` varchar(255) default NULL,
  `cred_used` varchar(25) default NULL,
  `note` text,
  `failed` tinyint(1) NOT NULL default '0',
  `results_sent` int(11) NOT NULL default '0',
  `deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`report_id`,`username`,`sid`),
  KEY `subnet` (`fk_name`),
  KEY `scantime` (`scantime`)
);
DROP TABLE IF EXISTS `vuln_nessus_latest_results`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_latest_results` (
  `result_id` int(11) NOT NULL auto_increment,
  `report_id` int(11) unsigned NOT NULL default '0',
  `username` varchar(255) character set latin1 collate latin1_bin NOT NULL,
  `sid` int(11) NOT NULL default '0',
  `scantime` varchar(14) NOT NULL default '',
  `record_type` char(1) NOT NULL default 'N',
  `hostIP` varchar(40) NOT NULL default '',
  `hostname` varchar(100) default NULL,
  `service` varchar(40) NOT NULL default '',
  `port` int(11) default NULL,
  `protocol` varchar(5) default NULL,
  `app` varchar(20) default NULL,
  `scriptid` varchar(40) NOT NULL default '',
  `risk` enum('1','2','3','4','5','6','7') NOT NULL default '1',
  `msg` text,
  `falsepositive` char(1) default 'N',
  PRIMARY KEY  (`result_id`),
  KEY `report_id` (`report_id`),
  KEY `scantime` (`scantime`),
  KEY `scriptid` (`scriptid`),
  KEY `hostIP` (`hostIP`),
  KEY `risk` (`risk`)
);

DROP TABLE IF EXISTS `vuln_hosts`;
CREATE TABLE IF NOT EXISTS `vuln_hosts` (
  `id` int(11) NOT NULL auto_increment,
  `hostip` varchar(40) default NULL,
  `hostname` varchar(64) NOT NULL default '',
  `description` varchar(200) NOT NULL default '',
  `status` varchar(45) NOT NULL default '',
  `workgroup` varchar(25) default NULL,
  `os` varchar(100) NOT NULL default '',
  `site_code` varchar(25) NOT NULL,
  `ORG` varchar(25) default NULL,
  `contact` varchar(45) NOT NULL default '',
  `scanstate` varchar(25) default NULL,
  `report_id` int(11) NOT NULL default '0',
  `creport_id` int(11) NOT NULL default '0',
  `lastscandate` datetime default NULL,
  `createdate` datetime default NULL,
  `inactive` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hostname` (`hostname`),
  KEY `hostip` (`hostip`)
);


DROP TABLE IF EXISTS `vuln_Incidents`;
CREATE TABLE IF NOT EXISTS `vuln_Incidents` (
  `id` int(11) NOT NULL auto_increment,
  `host_id` int(11) NOT NULL default '0',
  `result_id` int(11) NOT NULL default '0',
  `scriptid` varchar(40) NOT NULL default '',
  `service` varchar(40) NOT NULL default '',
  `risk` enum('1','2','3','4','5','6','7') NOT NULL default '7',
  `msg` text NOT NULL,
  `notes` text NOT NULL,
  `isLocalCheck` enum('0','1','2') NOT NULL default '2',
  `isCompCheck` enum('0','1') NOT NULL default '0',
  `exception_id` varchar(25) default NULL,
  `exception_approved` enum('0','1') NOT NULL default '0',
  `date_open` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_lastseen` datetime default NULL,
  `datelastupdate` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
  `date_ack` datetime default NULL,
  `date_resolved` datetime default NULL,
  `status` varchar(8) NOT NULL default 'open',
  PRIMARY KEY  (`id`),
  KEY `host_id` (`host_id`,`scriptid`),
  KEY `risk` (`risk`)
);


DROP TABLE IF EXISTS `vuln_jobs`;
CREATE TABLE IF NOT EXISTS `vuln_jobs` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `username` varchar(25) NOT NULL default '',
  `fk_name` varchar(50) default NULL,
  `job_TYPE` char(1) NOT NULL default 'M',
  `meth_SCHED` char(1) NOT NULL default 'N',
  `meth_TARGET` text,
  `meth_CRED` int(11) default NULL,
  `meth_VSET` int(11) NOT NULL default '1',
  `meth_CUSTOM` enum('N','A','R') NOT NULL default 'N',
  `meth_CPLUGINS` text,
  `meth_Wcheck` text,
  `meth_Wfile` text,
  `meth_Ucheck` text,
  `meth_TIMEOUT` int(6) NOT NULL default '172800',
  `scan_ASSIGNED` varchar(25) default NULL,
  `scan_SERVER` int(11) NOT NULL default '0',
  `scan_START` datetime default NULL,
  `scan_END` datetime default NULL,
  `scan_SUBMIT` datetime default NULL,
  `scan_NEXT` varchar(14) default NULL,
  `scan_PID` int(11) NOT NULL default '0',
  `scan_PRIORITY` tinyint(1) NOT NULL default '3',
  `status` char(1) NOT NULL default 'S',
  `notify` varchar(255) NOT NULL default '',
  `report_id` int(11) NOT NULL default '0',
  `tracker_id` int(11) default NULL,
  `failed_attempts` tinyint(1) NOT NULL default '0',
  `authorized` tinyint(1) NOT NULL default '0',
  `author_uname` varchar(25) default NULL,
  PRIMARY KEY  (`id`,`name`),
  KEY `name` (`name`),
  KEY `scan_END` (`scan_END`),
  KEY `report_id` (`report_id`),
  KEY `subnet` (`fk_name`)
);


DROP TABLE IF EXISTS `vuln_job_schedule`;
CREATE TABLE IF NOT EXISTS `vuln_job_schedule` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `username` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `fk_name` varchar(50) character set utf8 collate utf8_unicode_ci default NULL,
  `job_TYPE` enum('C','M','R','S') character set utf8 collate utf8_unicode_ci NOT NULL default 'M' COMMENT 'CRON, MANUAL, REQ, SYSTEM',
  `schedule_type` enum('O','D', 'W', 'M', 'NW') character set utf8 collate utf8_unicode_ci NOT NULL default 'M',
  `day_of_week` enum('Su','Mo','Tu','We','Th','Fr','Sa') character set utf8 collate utf8_unicode_ci NOT NULL default 'Mo',
  `day_of_month` int(2) unsigned NOT NULL default '1',
  `time` time NOT NULL default '00:00:00',
  `email` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `meth_TARGET` text character set utf8 collate utf8_unicode_ci,
  `meth_CRED` int(11) default NULL,
  `meth_VSET` int(11) NOT NULL default '1',
  `meth_CUSTOM` enum('N','A','R') character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `meth_CPLUGINS` text character set utf8 collate utf8_unicode_ci,
  `meth_Wcheck` text character set utf8 collate utf8_unicode_ci,
  `meth_Wfile` text character set utf8 collate utf8_unicode_ci,
  `meth_Ucheck` text character set utf8 collate utf8_unicode_ci,
  `meth_TIMEOUT` int(11) default '172800',
  `scan_ASSIGNED` varchar(25) character set utf8 collate utf8_unicode_ci default NULL,
  `next_CHECK` varchar(14) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `createdate` datetime default NULL,
  `enabled` enum('0','1') character set utf8 collate utf8_unicode_ci NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
);


DROP TABLE IF EXISTS `vuln_nessus_category`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_category` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nname` (`name`)
);


DROP TABLE IF EXISTS `vuln_nessus_family`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_family` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nname` (`name`)
);


DROP TABLE IF EXISTS `vuln_nessus_plugins`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_plugins` (
  `id` int(11) NOT NULL default '0',
  `oid` varchar(50) NOT NULL default '',
  `name` varchar(255) default NULL,
  `copyright` varchar(255) default NULL,
  `summary` varchar(255) default NULL,
  `description` blob,
  `cve_id` varchar(255) default NULL,
  `bugtraq_id` varchar(255) default NULL,
  `xref` blob,
  `enabled` char(1) NOT NULL default '',
  `version` varchar(255) default NULL,
  `created` varchar(14) default NULL,
  `modified` varchar(14) default NULL,
  `deleted` varchar(14) default NULL,
  `category` int(11) NOT NULL default '0',
  `family` int(11) NOT NULL default '0',
  `risk` int(11) NOT NULL default '0',
  `custom_risk` int(1) default NULL,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `vuln_nessus_preferences`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_preferences` (
  `id` varchar(255) default NULL,
  `nessus_id` varchar(255) NOT NULL default '',
  `value` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `type` char(1) NOT NULL default '',
  PRIMARY KEY  (`nessus_id`)
);

DROP TABLE IF EXISTS `vuln_nessus_preferences_defaults`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_preferences_defaults` (
  `nessus_id` varchar(255) NOT NULL default '',
  `nessusgroup` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `field` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `flag` char(1) default NULL,
  PRIMARY KEY  (`nessus_id`)
);


DROP TABLE IF EXISTS `vuln_nessus_reports`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_reports` (
  `report_id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `fk_name` varchar(50) default NULL,
  `scantime` varchar(14) NOT NULL default '',
  `report_type` char(1) NOT NULL default 'N',
  `username` varchar(255) character set latin1 collate latin1_bin default NULL,
  `sid` int(11) default NULL,
  `scantype` char(1) NOT NULL default 'M',
  `server_ip` varchar(15) NOT NULL default '',
  `server_nversion` varchar(100) NOT NULL default '',
  `server_feedtype` varchar(32) NOT NULL default '',
  `server_feedversion` varchar(12) NOT NULL default '',
  `domain` varchar(255) NOT NULL default '',
  `report_key` varchar(16) NOT NULL default '',
  `report_path` varchar(255) default NULL,
  `cred_used` varchar(25) default NULL,
  `note` text,
  `failed` tinyint(1) NOT NULL default '0',
  `results_sent` tinyint(2) NOT NULL default '0',
  `deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`report_id`),
  KEY `subnet` (`fk_name`),
  KEY `scantime` (`scantime`)
);


DROP TABLE IF EXISTS `vuln_nessus_report_stats`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_report_stats` (
  `id` int(11) NOT NULL auto_increment,
  `report_id` int(11) NOT NULL default '0',
  `name` varchar(25) NOT NULL default '',
  `iHostCnt` int(4) NOT NULL default '0',
  `dtLastScanned` datetime NOT NULL default '0000-00-00 00:00:00',
  `iScantime` decimal(4,0) NOT NULL default '0',
  `vExceptions` int(6) NOT NULL default '0',
  `vSerious` int(6) NOT NULL default '0',
  `vHigh` int(6) NOT NULL default '0',
  `vMed` int(6) NOT NULL default '0',
  `vMedLow` int(6) NOT NULL default '0',
  `vLowMed` int(6) NOT NULL default '0',
  `vLow` int(6) NOT NULL default '0',
  `vInfo` int(6) NOT NULL default '0',
  `trend` int(4) NOT NULL default '0',
  `dtLastUpdated` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `report_id` (`report_id`),
  KEY `subnet` (`name`),
  KEY `dtLastScanned` (`dtLastScanned`)
);


DROP TABLE IF EXISTS `vuln_nessus_results`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_results` (
  `result_id` int(11) NOT NULL auto_increment,
  `report_id` int(11) NOT NULL default '0',
  `scantime` varchar(14) NOT NULL default '',
  `record_type` char(1) NOT NULL default 'N',
  `hostIP` varchar(40) NOT NULL default '',
  `hostname` varchar(100) default NULL,
  `service` varchar(40) NOT NULL default '',
  `port` int(11) default NULL,
  `protocol` varchar(5) default NULL,
  `app` varchar(20) default NULL,
  `scriptid` varchar(40) NOT NULL default '',
  `risk` enum('1','2','3','4','5','6','7') NOT NULL default '1',
  `msg` text,
  `falsepositive` char(1) default 'N',
  PRIMARY KEY  (`result_id`),
  KEY `report_id` (`report_id`),
  KEY `scantime` (`scantime`),
  KEY `scriptid` (`scriptid`),
  KEY `hostIP` (`hostIP`),
  KEY `risk` (`risk`)
);

DROP TABLE IF EXISTS `vuln_nessus_servers`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_servers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `hostname` varchar(255) NOT NULL default '',
  `port` int(11) NOT NULL default '1241',
  `user` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `PASSWORD` varchar(255) NOT NULL default '',
  `server_nversion` varchar(100) NOT NULL default '',
  `server_feedtype` varchar(32) NOT NULL default '',
  `server_feedversion` varchar(12) NOT NULL default '',
  `max_scans` int(11) NOT NULL default '10',
  `current_scans` int(11) NOT NULL default '0',
  `TYPE` char(1) NOT NULL default '',
  `site_code` varchar(25) NOT NULL default '',
  `owner` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `checkin_time` datetime default NULL,
  `status` char(1) default NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) AUTO_INCREMENT=2;

DROP TABLE IF EXISTS `vuln_nessus_settings`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) default NULL,
  `autoenable` char(1) NOT NULL default 'N',
  `type` char(1) NOT NULL default 'G',
  `owner` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `auto_cat_status` int(10) NOT NULL default '4',
  `auto_fam_status` int(10) NOT NULL default '4',
  `update_host_tracker` tinyint(1) NOT NULL default '0',
  `deleted` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
);

DROP TABLE IF EXISTS `vuln_nessus_settings_category`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_category` (
  `sid` int(11) NOT NULL default '0',
  `cid` int(11) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  PRIMARY KEY  (`sid`,`cid`)
);

DROP TABLE IF EXISTS `vuln_nessus_settings_family`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_family` (
  `sid` int(11) NOT NULL default '0',
  `fid` int(11) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  PRIMARY KEY  (`sid`,`fid`)
);

DROP TABLE IF EXISTS `vuln_nessus_settings_plugins`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_plugins` (
  `id` int(11) NOT NULL default '0',
  `sid` int(11) NOT NULL default '0',
  `enabled` char(1) NOT NULL default 'Y',
  `category` int(11) NOT NULL default '0',
  `family` int(11) NOT NULL default '0'
);

DROP TABLE IF EXISTS `vuln_nessus_settings_preferences`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_settings_preferences` (
  `sid` int(11) NOT NULL default '0',
  `id` varchar(255) default NULL,
  `nessus_id` varchar(255) NOT NULL default '',
  `value` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `type` char(1) NOT NULL default ''
);

DROP TABLE IF EXISTS `vuln_nessus_user_zones`;
CREATE TABLE IF NOT EXISTS `vuln_nessus_user_zones` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  `zid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) AUTO_INCREMENT=2;

DROP TABLE IF EXISTS `vuln_settings`;
CREATE TABLE IF NOT EXISTS `vuln_settings` (
  `settingID` int(11) NOT NULL auto_increment,
  `settingName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `settingValue` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `settingDescription` varchar(255) character set utf8 collate utf8_unicode_ci default NULL,
  `settingSection` varchar(50) character set utf8 collate utf8_unicode_ci default NULL,
  `developerNotes` text character set utf8 collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`settingID`)
) AUTO_INCREMENT=92;

DROP TABLE IF EXISTS `vuln_timezones`;
CREATE TABLE IF NOT EXISTS `vuln_timezones` (
  `id` int(11) NOT NULL auto_increment,
  `zone` char(1) NOT NULL,
  `military` varchar(25) NOT NULL,
  `civilian` varchar(255) default NULL,
  `code` varchar(50) NOT NULL,
  `abbrev` varchar(8) default NULL,
  `offset` varchar(8) NOT NULL,
  `cities` text,
  PRIMARY KEY  (`id`)
) AUTO_INCREMENT=26;

DROP TABLE IF EXISTS `vuln_users`;
CREATE TABLE IF NOT EXISTS `vuln_users` (
  `pn_uname` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `pn_email` varchar(60) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `pn_pass` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `component` varchar(25) character set utf8 collate utf8_unicode_ci default NULL,
  `pass_lastchanged` datetime NOT NULL,
  `expire` varchar(8) character set utf8 collate utf8_unicode_ci default NULL,
  `locked` int(11) NOT NULL default '0',
  `lockout_reset` datetime default NULL,
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `lastaccessed` datetime NOT NULL default '0000-00-00 00:00:00',
  `owniponly` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `defProfile` int(11) NOT NULL default '1',
  `user_TZ` varchar(25) character set utf8 collate utf8_unicode_ci default NULL,
  `sc_now` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `sc_once` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `sc_daily` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `sc_weekly` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  `sc_monthly` char(1) character set utf8 collate utf8_unicode_ci NOT NULL default 'N',
  PRIMARY KEY  (`pn_uname`)
);

DROP TABLE IF EXISTS `databases`;
CREATE TABLE `databases` (
  `name` varchar(64) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `port` int(11) NOT NULL default 3306,
  `user` varchar(64) NOT NULL,
  `pass` varchar(64) NOT NULL,
  `icon` mediumblob NOT NULL,
  PRIMARY KEY (`name`)
);

DROP TABLE IF EXISTS `acl_entities`;
CREATE TABLE IF NOT EXISTS `acl_entities` (
  `id` int(11) NOT NULL auto_increment,
  `type` int(11) NOT NULL,
  `admin_user` varchar(60) NOT NULL,
  `name` varchar(128) default NULL,
  `address` tinytext,
  `parent_id` int(11) default NULL,
  `inherit_sensors_from_parent` tinyint(4) default NULL,
  `inherit_assets_from_parent` tinyint(4) default NULL,
  `inherit_menus_from_parent` tinyint(4) default NULL,
  `inherit_policies_from_parent` tinyint(4) default NULL,
  `template_sensors` int(11) default NULL,
  `template_assets` int(11) default NULL,
  `template_menus` int(11) default NULL,
  `template_policies` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `fk_ac_entities_ac_entities_types1` (`type`),
  KEY `fk_acl_entities_acl_users1` (`admin_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `acl_entities_types`;
CREATE TABLE IF NOT EXISTS `acl_entities_types` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `inherit_sensors_from_parent` tinyint(4) default NULL,
  `inherit_assets_from_parent` tinyint(4) default NULL,
  `inherit_menus_from_parent` tinyint(4) default NULL,
  `inherit_policies_from_parent` tinyint(4) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

INSERT INTO `acl_entities_types` (`id`, `name`, `inherit_sensors_from_parent`, `inherit_assets_from_parent`, `inherit_menus_from_parent`, `inherit_policies_from_parent`) VALUES
(1, 'Company', 1, 1, 1, 1),
(3, 'Department', 1, 1, 1, 0),
(4, 'Group', 1, 0, 1, 1),
(5, 'Home User', 1, 1, 1, 0);

DROP TABLE IF EXISTS `acl_perm`;
CREATE TABLE IF NOT EXISTS `acl_perm` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('MENU') default NULL,
  `name` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `description` varchar(128) NOT NULL,
  `granularity_sensor` tinyint(1) NOT NULL,
  `granularity_net` tinyint(1) NOT NULL,
  `enabled` tinyint(4) default '1',
  `ord` varchar(5) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=70 ;

INSERT INTO `acl_perm` (`id`, `type`, `name`, `value`, `description`, `granularity_sensor`, `granularity_net`, `enabled`, `ord`) VALUES
(1, 'MENU', 'MenuControlPanel', 'ControlPanelExecutive', 'Dashboard -> Main', 1, 1, 1, '01.01'),
(2, 'MENU', 'MenuControlPanel', 'ControlPanelAlarms', '', 1, 0, 0, '0'),
(3, 'MENU', 'MenuControlPanel', 'ControlPanelExecutiveEdit', 'Dashboard -> Executive Panel Edit', 1, 1, 1, '01.02'),
(4, 'MENU', 'MenuControlPanel', 'ControlPanelMetrics', 'Dashboard -> Metrics', 1, 1, 1, '01.06'),
(5, 'MENU', 'MenuControlPanel', 'ControlPanelEvents', '', 0, 0, 0, '0'),
(6, 'MENU', 'MenuControlPanel', 'ControlPanelVulnerabilities', 'Dashboard -> Vulnerabilities', 1, 1, 1, '01.03'),
(7, 'MENU', 'MenuControlPanel', 'ControlPanelAnomalies', '', 0, 0, 0, '0'),
(8, 'MENU', 'MenuControlPanel', 'ControlPanelHids', '', 0, 0, 0, '0'),
(9, 'MENU', 'MenuIntelligence', 'PolicyPolicy', 'Intelligence -> Policy', 1, 1, 1, '06.01'),
(10, 'MENU', 'MenuPolicy', 'PolicyHosts', 'Assets -> Assets -> Hosts', 1, 0, 1, '05.02'),
(11, 'MENU', 'MenuPolicy', 'PolicyNetworks', 'Assets -> Assets -> Networks', 0, 1, 1, '05.03'),
(12, 'MENU', 'MenuPolicy', 'PolicySensors', 'Assets -> SIEM Components -> Sensors', 1, 0, 1, '05.06'),
(13, 'MENU', 'MenuPolicy', 'PolicySignatures', '', 0, 0, 0, '0'),
(14, 'MENU', 'MenuPolicy', 'PolicyPorts', 'Assets -> Assets -> Ports', 0, 0, 1, '05.04'),
(15, 'MENU', 'MenuIntelligence', 'PolicyActions', 'Intelligence -> Actions', 0, 0, 1, '06.02'),
(16, 'MENU', 'MenuPolicy', 'PolicyResponses', '', 0, 0, 0, '0'),
(17, 'MENU', 'MenuConfiguration', 'PluginGroups', 'Configuration -> Collection -> PluginGroups', 0, 0, 1, '08.05'),
(18, 'MENU', 'MenuReports', 'ReportsHostReport', 'Reports -> Asset Report', 1, 1, 1, '04.01'),
(19, 'MENU', 'MenuIncidents', 'ReportsAlarmReport', 'Incidents -> Alarms -> Reports', 1, 0, 1, '02.02'),
(20, 'MENU', 'MenuReports', 'ReportsSecurityReport', 'Incidents -> Alarms -> Reports', 1, 0, 0, '0'),
(21, 'MENU', 'MenuReports', 'ReportsPDFReport', '', 1, 0, 0, '0'),
(22, 'MENU', 'MenuIncidents', 'IncidentsIncidents', 'Incidents -> Tickets', 1, 0, 1, '02.03'),
(23, 'MENU', 'MenuIncidents', 'IncidentsTypes', 'Incidents -> Tickets -> Types', 0, 0, 1, '02.05'),
(24, 'MENU', 'MenuIncidents', 'IncidentsReport', 'Incidents -> Tickets -> Report', 1, 0, 1, '02.04'),
(25, 'MENU', 'MenuIncidents', 'IncidentsTags', 'Incidents -> Tickets -> Tags', 0, 0, 1, '02.06'),
(26, 'MENU', 'MenuMonitors', 'MonitorsSession', '', 0, 0, 0, '0'),
(27, 'MENU', 'MenuMonitors', 'MonitorsNetwork', 'Monitors -> Network -> Profiles', 1, 0, 1, '07.02'),
(28, 'MENU', 'MenuMonitors', 'MonitorsAvailability', 'Monitors -> Availability', 0, 0, 1, '07.03'),
(29, 'MENU', 'MenuMonitors', 'MonitorsSensors', 'Monitors -> System', 1, 0, 1, '07.04'),
(30, 'MENU', 'MenuControlPanel', 'MonitorsRiskmeter', 'Dashboard -> Metrics -> Riskmeter', 1, 1, 1, '01.07'),
(31, 'MENU', 'MenuIntelligence', 'CorrelationDirectives', 'Intelligence -> Correlation Directives', 0, 0, 1, '06.03'),
(32, 'MENU', 'MenuIntelligence', 'CorrelationCrossCorrelation', 'Intelligence -> Cross Correlation', 0, 0, 1, '06.06'),
(33, 'MENU', 'MenuIntelligence', 'CorrelationBacklog', 'Intelligence -> Correlation Directives -> Backlog', 1, 0, 1, '06.04'),
(34, 'MENU', 'MenuConfiguration', 'ConfigurationMain', 'Configuration -> Main', 0, 0, 1, '08.01'),
(35, 'MENU', 'MenuConfiguration', 'ConfigurationUsers', 'Configuration -> Users', 0, 0, 1, '08.02'),
(36, 'MENU', 'MenuConfiguration', 'ConfigurationPlugins', 'Configuration -> Collection', 0, 0, 1, '08.04'),
(37, 'MENU', 'MenuConfiguration', 'ConfigurationRRDConfig', '', 0, 0, 0, '0'),
(38, 'MENU', 'MenuConfiguration', 'ConfigurationHostScan', '', 0, 0, 0, '0'),
(39, 'MENU', 'MenuConfiguration', 'ConfigurationUserActionLog', 'Configuration -> Users -> User activity', 0, 0, 1, '08.03'),
(40, 'MENU', 'MenuIncidents', 'ConfigurationEmailTemplate', 'Incidents -> Tickets -> Incidents Email Template', 0, 0, 1, '02.07'),
(41, 'MENU', 'MenuConfiguration', 'ConfigurationUpgrade', 'Configuration -> Software Upgrade', 0, 0, 1, '08.06'),
(42, 'MENU', 'MenuTools', 'ToolsScan', 'Tools -> Net Discovery', 0, 1, 1, '09.03'),
(43, 'MENU', 'MenuTools', 'ToolsRuleViewer', '', 0, 0, 0, '0'),
(44, 'MENU', 'MenuTools', 'ToolsBackup', 'Tools -> Backup', 0, 0, 1, '09.01'),
(45, 'MENU', 'MenuReports', 'ToolsUserLog', 'Reports -> Reports -> User log', 0, 0, 1, '04.04'),
(46, 'MENU', 'MenuControlPanel', 'BusinessProcesses', 'Dashboard -> Risk Maps', 1, 1, 1, '01.04'),
(47, 'MENU', 'MenuControlPanel', 'BusinessProcessesEdit', 'Dashboard -> Risk Maps Edit', 1, 1, 1, '01.05'),
(48, 'MENU', 'MenuEvents', 'EventsForensics', 'Analysis -> SIEM Events', 1, 1, 1, '03.01'),
(49, 'MENU', 'MenuEvents', 'EventsVulnerabilities', 'Analysis -> Vulnerabilities', 1, 1, 1, '03.07'),
(50, 'MENU', 'MenuEvents', 'EventsAnomalies', 'Analysis -> SIEM Events -> Anomalies', 1, 1, 1, '03.05'),
(51, 'MENU', 'MenuEvents', 'EventsRT', 'Analysis -> SIEM Events -> Real Time', 1, 0, 1, '03.02'),
(52, 'MENU', 'MenuEvents', 'EventsViewer', 'Analysis -> SIEM Events -> Custom', 1, 0, 1, '03.03'),
(53, 'MENU', 'MenuPolicy', 'PolicyServers', 'Assets -> SIEM Components -> Servers', 0, 0, 1, '05.07'),
(54, 'MENU', 'MenuPolicy', 'ReportsOCSInventory', 'Assets -> Assets -> Inventory', 1, 1, 1, '05.05'),
(55, 'MENU', 'MenuIncidents', 'Osvdb', 'Incidents -> Knowledge DB', 0, 0, 1, '02.08'),
(56, 'MENU', 'MenuConfiguration', 'ConfigurationMaps', '', 0, 0, 0, '0'),
(57, 'MENU', 'MenuTools', 'ToolsDownloads', 'Tools -> Downloads', 0, 0, 1, '09.02'),
(58, 'MENU', 'MenuReports', 'ReportsGLPI', '', 0, 0, 0, '0'),
(59, 'MENU', 'MenuMonitors', 'MonitorsVServers', '', 0, 0, 0, '0'),
(60, 'MENU', 'MenuIncidents', 'ControlPanelAlarms', 'Incidents -> Alarms', 1, 0, 1, '02.01'),
(61, 'MENU', 'MenuEvents', 'ControlPanelSEM', 'Analysis -> Logger', 1, 0, 1, '03.06'),
(62, 'MENU', 'MenuEvents', 'ReportsWireless', 'Analysis -> SIEM Events -> Wireless', 1, 0, 1, '03.04'),
(63, 'MENU', 'MenuIntelligence', 'ComplianceMapping', 'Intelligence -> Compliance Mapping', 0, 0, 1, '06.05'),
(64, 'MENU', 'MenuPolicy', '5DSearch', 'Assets -> Asset Search', 0, 0, 1, '05.01'),
(65, 'MENU', 'MenuReports', 'ReportsReportServer', 'Reports -> Reports -> Advanced', 0, 0, 1, '04.05'),
(66, 'MENU', 'MenuMonitors', 'MonitorsNetflows', 'Monitors -> Network -> Traffic', 0, 0, 1, '07.01'),
(67, 'MENU', 'MenuReports', '5DSearch', 'Assets -> Asset Search', 0, 0, 0, '0'),
(68, 'MENU', 'MainMenu', 'Index', 'Top Frame Status', 1, 1, 1, '00.01'),
(69, 'MENU', 'MenuMonitors', 'ToolsUserLog', 'Monitors -> System -> User Activity', 0, 0, 1, '07.05');


DROP TABLE IF EXISTS `acl_templates`;
CREATE TABLE IF NOT EXISTS `acl_templates` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `allowed_sensors` TEXT,
  `allowed_nets` TEXT,
  `entity` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `acl_templates_perms`;
CREATE TABLE IF NOT EXISTS `acl_templates_perms` (
  `ac_templates_id` int(11) NOT NULL,
  `ac_perm_id` int(11) NOT NULL,
  PRIMARY KEY  (`ac_templates_id`,`ac_perm_id`),
  KEY `fk_acl_aco_ac_templates1` (`ac_templates_id`),
  KEY `fk_acl_aco_ac_perm1` (`ac_perm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `acl_entities` ADD CONSTRAINT `fk_ac_entities_ac_entities_types1` FOREIGN KEY (`type`) REFERENCES `acl_entities_types` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `acl_templates_perms` ADD CONSTRAINT `fk_acl_aco_ac_perm1` FOREIGN KEY (`ac_perm_id`) REFERENCES `acl_perm` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_acl_aco_ac_templates1` FOREIGN KEY (`ac_templates_id`) REFERENCES `acl_templates` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

DROP TABLE IF EXISTS `custom_report_profiles`;
CREATE TABLE IF NOT EXISTS `custom_report_profiles` (
`name` varchar(64) NOT NULL,
`header` varchar(64) NOT NULL,
`lfooter` varchar(64) NOT NULL,
`rfooter` varchar(64) NOT NULL,
`color1` varchar(64) NOT NULL,
`color2` varchar(64) NOT NULL,
`color3` varchar(64) NOT NULL,
`color4` varchar(64) NOT NULL,
PRIMARY KEY (`name`)
);

DROP TABLE IF EXISTS `custom_report_types`;
CREATE TABLE IF NOT EXISTS `custom_report_types` (
`id` int(11) NOT NULL,
`name` varchar(128) NOT NULL,
`type` varchar(128) NOT NULL,
`file` varchar(128) NOT NULL,
`inputs` text NOT NULL,
`sql` text NOT NULL,
`dr` int(11) NOT NULL default '1',
PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `custom_report_scheduler`;
CREATE TABLE IF NOT EXISTS `custom_report_scheduler` (
  `id` int(11) NOT NULL auto_increment,
  `schedule_type` varchar(20) NOT NULL,
  `schedule` text NOT NULL,
  `next_launch` datetime NOT NULL,
  `id_report` varchar(100) NOT NULL,
  `name_report` varchar(100) NOT NULL,
  `email` varchar(255) default NULL,
  `date_from` date default NULL,
  `date_to` date default NULL,
  `date_range` varchar(30) default NULL,
  `assets` tinytext,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `risk_maps`;
CREATE TABLE IF NOT EXISTS `risk_maps` (
  `map` varchar(64) NOT NULL,
  `perm` varchar(64) NOT NULL,
  PRIMARY KEY (`map`,`perm`)
);
