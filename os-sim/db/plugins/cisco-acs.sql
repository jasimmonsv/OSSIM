-- Cisco ACS 
--
--
--
DELETE FROM plugin WHERE id = "1594";
DELETE FROM plugin_sid where plugin_id = "1594";
INSERT INTO plugin (id, type, name, description) VALUES (1594, 1, 'cisco-acs', 'Cisco-ACS ');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1594, 1, NULL, NULL, 'Cisco ACS: Passed Auth' , 1, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1594, 2, NULL, NULL, 'Cisco ACS: Failed Auth' , 1, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1594, 13, NULL, NULL, 'Cisco ACS: Administration event' , 1, 3);
