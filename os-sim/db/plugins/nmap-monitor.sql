-- nmap
-- type: monitor
-- plugin_id: 2008
DELETE FROM plugin WHERE id = "2008";
DELETE FROM plugin_sid where plugin_id = "2008";


INSERT INTO plugin (id, type, name, description) VALUES (2008, 2, 'nmap', 'Nmap: network mapper');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 1, NULL, NULL, 'nmap-monitor: TCP Port opened');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 2, NULL, NULL, 'nmap-monitor: TCP Port closed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 3, NULL, NULL, 'nmap-monitor: UDP Port opened');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (2008, 4, NULL, NULL, 'nmap-monitor: UDP Port closed');
