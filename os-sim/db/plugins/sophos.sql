DELETE FROM plugin WHERE id = "1558";
DELETE FROM plugin_sid where plugin_id = "1558";

INSERT INTO plugin (id, type, name, description) VALUES (1558, 1, 'sophos', 'Sophos Antivirus');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 1, NULL, NULL, 'Sophos: Trojan found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 2, NULL, NULL, 'Sophos: Forbidden software found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 4, NULL, NULL, 'Sophos: Malware found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 5, NULL, NULL, 'Sophos: Malware found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 6, NULL, NULL, 'Sophos: Forbidden software found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1558, 99, NULL, NULL, 'Sophos: Unknown event');
