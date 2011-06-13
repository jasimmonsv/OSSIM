-- Airlock
-- plugin_id: 1641

DELETE FROM plugin WHERE id = "1641";
DELETE FROM plugin_sid where plugin_id = "1641";


INSERT INTO plugin (id, type, name, description) VALUES (1641, 1, 'airlock', 'Airlock Reverse Proxy');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 1, NULL, NULL, 'airlock: Web-Request', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 2, NULL, NULL, 'airlock: Access Denied', 2, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 3, NULL, NULL, 'airlock: Possible Attack', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 4, NULL, NULL, 'airlock: Possible Backend Problem', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 5, NULL, NULL, 'airlock: Terminated - Error', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 6, NULL, NULL, 'airlock: Malformed Packet', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1641, 999, NULL, NULL, 'airlock: Default', 1, 1);
