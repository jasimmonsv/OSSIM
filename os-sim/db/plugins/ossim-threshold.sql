-- ossim
-- plugin_id: 1509
--

DELETE FROM plugin WHERE id = "1509";
DELETE FROM plugin_sid where plugin_id = "1509";

INSERT INTO plugin (id, type, name, description) VALUES (1509, 1, 'threshold', 'Threshold exceeded');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1509, 1, NULL, NULL, 'Metric Threshold: C level exceeded', 0, 0);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1509, 2, NULL, NULL, 'Metric Threshold: A level exceeded', 0, 0);


