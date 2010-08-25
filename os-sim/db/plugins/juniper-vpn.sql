DELETE FROM plugin WHERE id = "1609";
DELETE FROM plugin_sid where plugin_id = "1609";



INSERT INTO plugin (id, type, name, description) VALUES (1609, 1, 'Juniper-VPN', 'Juniper VPN SSL');


INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 1, NULL, NULL, 'Juniper-VPN: WebRequest ok' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 2, NULL, NULL, 'Juniper-VPN: WebRequest completed' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 3, NULL, NULL, 'Juniper-VPN: Login Succeeded' ,2, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 4, NULL, NULL, 'Juniper-VPN: Policy Check Passed' ,1, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 5, NULL, NULL, 'Juniper-VPN: Policy Check Failed' ,2, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 6, NULL, NULL, 'Juniper-VPN: Session Logout' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 7, NULL, NULL, 'Juniper-VPN: Downloaded File' ,2, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 8, NULL, NULL, 'Juniper-VPN: Access denied to Windows directory' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 9, NULL, NULL, 'Juniper-VPN: Login Failed' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 10, NULL, NULL, 'Juniper-VPN: Authentication successful' ,2, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 11, NULL, NULL, 'Juniper-VPN: Session switch' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 12, NULL, NULL, 'Juniper-VPN: RDP Session opened' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 13, NULL, NULL, 'Juniper-VPN: RDP Session closed' ,1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 14, NULL, NULL, 'Juniper-VPN: Authentication failed' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 15, NULL, NULL, 'Juniper-VPN: Account Lockout' ,4, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 16, NULL, NULL, 'Juniper-VPN: Write Error' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 17, NULL, NULL, 'Juniper-VPN: Read Error' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 18, NULL, NULL, 'Juniper-VPN: Password Real Restriction Failed' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 19, NULL, NULL, 'Juniper-VPN: Network Connect' ,3, 3);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1609, 999, NULL, NULL, 'Juniper-VPN: Generic Message' ,1, 1);
