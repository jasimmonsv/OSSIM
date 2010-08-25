-- Palo Alto Firewall
-- plugin_id: 1615

-- TODO:
-- It would be improved using the "subtype" field,
-- for example, type=TRAFFIC, *subtype=ALLOWED*
-- More documentation is needed

DELETE FROM plugin WHERE id = "1615";
DELETE FROM plugin_sid where plugin_id = "1615";


INSERT INTO plugin (id, type, name, description) VALUES (1615, 1, 'paloalto', 'PaloAlto Firewall');

INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 1, 'PaloAlto: CONFIG Event', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 2, 'PaloAlto: TRAFFIC Event', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 3, 'PaloAlto: SYSTEM Event', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 4, 'PaloAlto: THREAT Event', 1, 5);

