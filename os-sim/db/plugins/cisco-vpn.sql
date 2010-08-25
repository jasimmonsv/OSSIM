-- cisco-vpn
-- plugin_id: 1527
--
-- $Id: cisco-vpn.sql,v 1.1 2007/12/12 15:31:55 juanmals Exp $

DELETE FROM plugin WHERE id = "1527";
DELETE FROM plugin_sid where plugin_id = "1527";


INSERT INTO plugin (id, type, name, description) VALUES (1527, 1, 'cisco-vpn', 'Cisco VPN box');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 1, NULL, NULL, 'Cisco VPN Box: Connection denied ', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 2, NULL, NULL, 'Cisco VPN Box: Connection permmited', 1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 3, NULL, NULL, 'Cisco VPN Box: Interface changed status to down', 2, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 4, NULL, NULL, 'Cisco VPN Box: Interface changed status to up', 2, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 5, NULL, NULL, 'Cisco VPN Box: Login failed', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 6, NULL, NULL, 'Cisco VPN Box: Login success', 1, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1527, 7, NULL, NULL, 'Cisco VPN Box: Cisco VPN Box configured', 3, 1);
