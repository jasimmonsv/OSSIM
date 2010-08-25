-- Arpwatch fingerprintings SIDs--
DELETE FROM plugin_sid WHERE plugin_id=1512;
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 1, NULL, NULL, 'arpwatch: Mac address New');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 2, NULL, NULL, 'arpwatch: Mac address Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 3, NULL, NULL, 'arpwatch: Mac address Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 4, NULL, NULL, 'arpwatch: Mac address Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 5, NULL, NULL, 'arpwatch: Mac address Event unknown');

DELETE FROM plugin_sid WHERE plugin_id=2002;
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2002, 1, NULL, NULL, 'arp_watch: New Mac');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2002, 2, NULL, NULL, 'arp_watch: Mac Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2002, 3, NULL, NULL, 'arp_watch: Mac Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2002, 4, NULL, NULL, 'arp_watch: Mac Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2002, 5, NULL, NULL, 'arp_watch: Mac Event unknown');

-- Server role
INSERT INTO config (conf, value) VALUES ('server_store', '1');
INSERT INTO config (conf, value) VALUES ('server_correlate', '1');
INSERT INTO config (conf, value) VALUES ('server_cross_correlate', '1');
INSERT INTO config (conf, value) VALUES ('server_qualify', '1');
INSERT INTO config (conf, value) VALUES ('server_resend_alarm', '1');
INSERT INTO config (conf, value) VALUES ('server_resend_event', '1');

-- Event role.
CREATE TABLE policy_role_reference (
    policy_id       INTEGER NOT NULL REFERENCES policy(id),
    correlate       BOOLEAN NOT NULL DEFAULT '1',
    cross_correlate BOOLEAN NOT NULL DEFAULT '1',
    store           BOOLEAN NOT NULL DEFAULT '1',
    qualify         BOOLEAN NOT NULL DEFAULT '1',
    resend_alarm    BOOLEAN NOT NULL DEFAULT '1',
    resend_event    BOOLEAN NOT NULL DEFAULT '1',
    PRIMARY KEY (policy_id)
);

-- Event & backlog don't need autoincrement anymore
ALTER table event modify id BIGINT NOT NULL DEFAULT '0';
ALTER table backlog modify id BIGINT NOT NULL DEFAULT 0;

/*Sequences */
CREATE TABLE event_seq (
         id INTEGER UNSIGNED NOT NULL
				  );
INSERT INTO event_seq VALUES (0);

CREATE TABLE backlog_seq (
         id INTEGER UNSIGNED NOT NULL
				  );
INSERT INTO backlog_seq VALUES (0);

CREATE TABLE backlog_event_seq (
         id INTEGER UNSIGNED NOT NULL
 );
INSERT INTO backlog_event_seq VALUES (0);




-- New sshd event

REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 4, NULL, NULL, 'SSHd: Invalid user', 3, 2);

-- reduced reliability of SHELLCODE (from 3 to 1)
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 638, 133, 115, 'SHELLCODE SGI NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 639, 133, 115, 'SHELLCODE SGI NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 640, 133, 115, 'SHELLCODE AIX NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 641, 133, 115, 'SHELLCODE Digital UNIX NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 642, 133, 115, 'SHELLCODE HP-UX NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 643, 133, 115, 'SHELLCODE HP-UX NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 644, 133, 115, 'SHELLCODE sparc NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 645, 133, 115, 'SHELLCODE sparc NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 646, 133, 115, 'SHELLCODE sparc NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 648, 133, 115, 'SHELLCODE x86 NOOP', 5, 1);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1001, 649, 133, 119, 'SHELLCODE x86 setgid 0', 5, 1);

-- Service level
ALTER table control_panel modify a_sec_level float;
ALTER table control_panel modify c_sec_level float;

-- Modified sshd events
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 1, NULL, NULL, 'pam_unix: authentication failure', 2, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 2, NULL, NULL, 'PAM_unix: authentication failure', 2, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 5, NULL, NULL, 'pam_unix: session opened', 0, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 6, NULL, NULL, 'PAM_unix: session opened', 0, 2);
REPLACE INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4002, 7, NULL, NULL, 'SSHd: Accepted login', 0, 2);

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '0.9.9rc3');
