-- Linux-DHCP
-- plugin_id: 1607
--

DELETE FROM plugin WHERE id = 1607;
DELETE FROM plugin_sid WHERE plugin_id=1607;

INSERT INTO plugin (id, type, name, description) VALUES (1607, 1, 'linuxdhcp', 'Linux DHCP Service Activity');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 1, NULL, NULL, 1, 1, 'DHCP Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 2, NULL, NULL, 1, 1, 'DHCP ACK');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 3, NULL, NULL, 1, 1, 'DHCP Offer');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 4, NULL, NULL, 1, 1, 'DHCP Inform');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 5, NULL, NULL, 1, 1, 'DHCP Release');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 6, NULL, NULL, 1, 1, 'DHCP Lease is duplicate');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 7, NULL, NULL, 1, 1, 'DHCP Relay Agent with Circuit');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 8, NULL, NULL, 1, 1, 'DHCP No free leases');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES (1607, 9, NULL, NULL, 1, 1, 'DHCP Discover');

