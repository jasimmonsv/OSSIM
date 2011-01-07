-- Linux-DHCP
-- plugin_id: 1607
--

DELETE FROM plugin WHERE id = 1607;
DELETE FROM plugin_sid WHERE plugin_id=1607;

INSERT INTO plugin (id, type, name, description) VALUES (1607, 1, 'linuxdhcp', 'Linux DHCP Service Activity');

INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 1, 1, 1, 'DHCP Request');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 2, 1, 1, 'DHCP ACK');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 3, 1, 1, 'DHCP Offer');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 4, 1, 1, 'DHCP Inform');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 5, 1, 1, 'DHCP Release');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 6, 1, 1, 'DHCP Lease is duplicate');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 7, 1, 1, 'DHCP Relay Agent with Circuit');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 8, 2, 2, 'DHCP No free leases');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 9, 1, 1, 'DHCP Discover');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 10, 1, 1, 'DHCP NAK');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 11, 1, 1, 'DHCP Decline');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 12, 1, 1, 'DHCP Boot request');
INSERT INTO plugin_sid (plugin_id, sid, reliability, priority, name) VALUES (1607, 13, 1, 1, 'DHCP Added reverse map');


