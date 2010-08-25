-- IRON_PORT
-- plugin_id: 1591
DELETE FROM plugin WHERE id = "1591";
DELETE FROM plugin_sid where plugin_id = "1591";


INSERT INTO plugin (id, type, name, description) VALUES (1591, 1, 'iron port', 'IRON PORT log');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 1, NULL, NULL, 'IRON_PORT: Virus detected' ,1, 5);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 2, NULL, NULL, 'IRON_PORT: msg dropped by filter' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 3, NULL, NULL, 'IRON_PORT: spam quarantine' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1591, 4, NULL, NULL, 'IRON_PORT: spam positive' ,1, 1);

