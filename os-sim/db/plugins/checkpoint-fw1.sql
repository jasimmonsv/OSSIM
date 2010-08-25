-- checkpoint fw-1
-- plugin_id: 1504
--

DELETE FROM plugin WHERE id = "1504";
DELETE FROM plugin_sid where plugin_id = "1504";

INSERT INTO plugin (id, type, name, description) VALUES (1504, 1, 'fw1', 'FW1');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1504, 1, 202, NULL, 'fw1: Accept', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1504, 2, 204, NULL, 'fw1: Reject');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1504, 3, 203, NULL, 'fw1: Drop');


