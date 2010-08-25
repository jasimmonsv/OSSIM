-- fw1 ng r60
-- Firewall-1 Checkpoint NG R60
-- plugin_id: 1503
--
-- $Id $
--

DELETE FROM plugin WHERE id = "1504";
DELETE FROM plugin_sid WHERE plugin_id = "1504";

INSERT INTO plugin (id, type, name, description) VALUES (1504, 1, 'fw1ngr60', 'Firewall-1 NG R60 Checkpoint');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, name)  VALUES (1504,1,'NULL','NULL',1,1,'fw1ngr60: Accept');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, name)  VALUES (1504,2,'NULL','NULL',1,1,'fw1ngr60: Reject');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, name)  VALUES (1504,3,'NULL','NULL',1,1,'fw1ngr60: Drop');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, name)  VALUES (1504,6,'NULL','NULL',1,1,'fw1ngr60: Monitor');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, priority, reliability, name)  VALUES (1504,99,'NULL','NULL',1,1,'fw1ngr600: unknown event');

