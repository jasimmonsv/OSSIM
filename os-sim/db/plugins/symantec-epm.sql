-- SymantecEPM
-- plugin_id: 1619

DELETE FROM plugin WHERE id = "1619";
DELETE FROM plugin_sid where plugin_id = "1619";


INSERT INTO plugin (id, type, name, description) VALUES (1619, 1, 'SymantecEPM', 'SymantecEPM: Symantec AV Server');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 1, NULL, NULL, 'SymantecEPM: Received a new policy from Symantec Endpoint Protection Manager', 1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 2, NULL, NULL, 'SymantecEPM: Applied new policy', 1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 3, NULL, NULL, 'SymantecEPM: Location has been changed to Default', 1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 4, NULL, NULL, 'SymantecEPM: Failed to contact server for more than 10 times', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 5, NULL, NULL, 'SymantecEPM: Block running programs from removable media', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 6, NULL, NULL, 'SymantecEPM: Log writing to USB drives', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 7, NULL, NULL, 'SymantecEPM:TruScan Error - Heuristic Scan or Load Failure', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 8, NULL, NULL, 'SymantecEPM: Client services Registry Write', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 9, NULL, NULL, 'SymantecEPM: Symantec Endpoint Protection services shutdown was successful', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 10, NULL, NULL, 'SymantecEPM: Symantec Endpoint Protection services startup was successful', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 11, NULL, NULL, 'SymantecEPM: Virus Found', 2, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 12, NULL, NULL, 'SymantecEPM: Administrator log on failed', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 13, NULL, NULL, 'SymantecEPM: Administrator log on succeeded', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 14, NULL, NULL, 'SymantecEPM: Policy Edited', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 15, NULL, NULL, 'SymantecEPM: Computer has been moved', 1, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1619, 16, NULL, NULL, 'SymantecEPM: Failed to connect to the server', 1, 2);


