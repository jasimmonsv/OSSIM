-- wmi
-- type: monitor
-- plugin_id: 2012
DELETE FROM plugin WHERE id = "2012";
DELETE FROM plugin_sid where plugin_id = "2012";


INSERT INTO plugin (id, type, name, description) VALUES (2012, 2, 'wmi-monitor', 'wmi-monitor: Windows checks via wmi');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 1, NULL, NULL, 'wmi-monitor: User logged');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 2, NULL, NULL, 'wmi-monitor: Service up');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 3, NULL, NULL, 'wmi-monitor: Process up');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2012, 4, NULL, NULL, 'wmi-monitor: Clsid installed');
