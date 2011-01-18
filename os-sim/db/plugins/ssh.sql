-- SSHd
-- plugin_id: 4003

DELETE FROM plugin WHERE id = "4003";
DELETE FROM plugin_sid where plugin_id = "4003";


INSERT INTO plugin (id, type, name, description) VALUES (4003, 1, 'sshd', 'SSHd: Secure Shell daemon');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 1, NULL, NULL, 'SSHd: Failed password', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 2, NULL, NULL, 'SSHd: Failed publickey', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 3, NULL, NULL, 'SSHd: Invalid user', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 4, NULL, NULL, 'SSHd: Illegal user', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 5, NULL, NULL, 'SSHd: Root login refused', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 6, NULL, NULL, 'SSHd: User not allowed because listed in DenyUsers', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 7, NULL, NULL, 'SSHd: Login sucessful, Accepted password', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 8, NULL, NULL, 'SSHd: Login sucessful, Accepted publickey', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 9, NULL, NULL, 'SSHd: Bad protocol version identification', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 10, NULL, NULL, 'SSHd: Did not receive identification string', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4003, 11, NULL, NULL, 'SSHd: Received disconnect', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 12, NULL, NULL, 'SSHd: Authentication refused: bad ownership or modes', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 13, NULL, NULL, 'SSHd: User not allowed becase account is locked', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 14, NULL, NULL, 'SSHd: PAM 2 more authentication failures', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 15, NULL, NULL, 'SSHd: Reverse mapped failed', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 16, NULL, NULL, 'SSHd: Address not mapped', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 17, NULL, NULL, 'SSHd: Server listening', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 18, NULL, NULL, 'SSHd: Server terminated', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 19, NULL, NULL, 'SSHd: Refused connect', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 20, NULL, NULL, 'SSHd: Denied connection', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 21, NULL, NULL, 'SSHd: Could not get shadow information', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority,reliability) VALUES (4003, 99, NULL, NULL, 'SSHd: Generic SSH Event', 1, 1);
