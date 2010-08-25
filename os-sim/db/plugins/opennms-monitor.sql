-- opennms
-- type: monitor
-- plugin_id: 2004
DELETE FROM plugin WHERE id = "2004";
DELETE FROM plugin_sid where plugin_id = "2004";


INSERT INTO plugin (id, type, name, description) VALUES (2004, 2, 'opennms', 'OpenNMS');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2004, 1, NULL, NULL, 'opennms-monitor: Service Up');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2004, 2, NULL, NULL, 'opennms-monitor: Service Down');

-- FIXME: the three plugins below was commented. If you find any reason to do that, please comment it again.
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2004, 3, NULL, NULL, 'open_nms-monitor: Service Availability (%)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2004, 4, NULL, NULL, 'open_nms-monitor: Service Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2004, 5, NULL, NULL, 'open_nms-monitor: New Service Added');


