-- courier
-- Courier Mail Server
-- type: detector
-- plugin_id: 1617
--
DELETE FROM plugin WHERE id = "1617";
DELETE FROM plugin_sid where plugin_id = "1617";


INSERT INTO plugin (id, type, name, description) VALUES (1617, 1, 'courier', 'Courier Mail Server');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 1, NULL, NULL, 'Courier: Login');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 2, NULL, NULL, 'Courier: Logout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 3, NULL, NULL, 'Courier: New connection');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 4, NULL, NULL, 'Courier: User disconnected');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 5, NULL, NULL, 'Courier: Timeout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 6, NULL, NULL, 'Courier: Login failed');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1617, 99, NULL, NULL, 'Courier: Generic Event');

