-- pam_unix
-- type: detector
-- plugin_id: 4004
--
-- $Id: pam_unix.sql,v 1.3 2007/10/25 11:13:20 dkarg Exp $
--
DELETE FROM plugin WHERE id = "4004";
DELETE FROM plugin_sid where plugin_id = "4004";


INSERT INTO plugin (id, type, name, description) VALUES (4004, 1, 'pam_unix', 'Pam Unix authentication mechanism');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 1, NULL, NULL, 'pam_unix: authentication successful');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 2, NULL, NULL, 'pam_unix: authentication failure');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (4004, 3, NULL, NULL, 'pam_unix: 2 more authentication failures');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 4, NULL, NULL, 'adduser: User created' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 5, NULL, NULL, 'adduser: Group created' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 6, NULL, NULL, 'passwd: Password Changed' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 7, NULL, NULL, 'userdel: User deleted' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 8, NULL, NULL, 'userdel: Group deleted' ,3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (4004, 9, NULL, NULL, 'userdel: Check pass' ,3, 2);
