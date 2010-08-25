DELETE FROM plugin WHERE id = "1590";
DELETE FROM plugin_sid where plugin_id = "1590";

INSERT INTO plugin (id, type, name, description) VALUES (1590, 1, 'fw1', 'Checkpoint Fw1');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 1, NULL, NULL, 'fw1: drop');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 2, NULL, NULL, 'fw1: authorize');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 3, NULL, NULL, 'fw1: deauthorize');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 4, NULL, NULL, 'fw1: reject');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 5, NULL, NULL, 'fw1: ctl');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1590, 6, NULL, NULL, 'fw1: alert');
