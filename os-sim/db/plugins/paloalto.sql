-- Palo Alto Firewall
-- plugin_id: 1615

-- TODO:
-- More documentation is needed


DELETE FROM plugin WHERE id = "1615";
DELETE FROM plugin_sid where plugin_id = "1615";


INSERT INTO plugin (id, type, name, description) VALUES (1615, 1, 'paloalto', 'PaloAlto Firewall');

INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 1, 'PaloAlto: TRAFFIC start', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 2, 'PaloAlto: TRAFFIC end', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 3, 'PaloAlto: TRAFFIC drop', 2, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 4, 'PaloAlto: TRAFFIC deny', 2, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 11, 'PaloAlto: CONFIG event', 1, 5);
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 21, 'PaloAlto: SYSTEM general', 1, 5);

-- failsave event
INSERT INTO plugin_sid (plugin_id, sid, name, priority, reliability) VALUES (1615, 99, 'PaloAlto: Unknown event', 1, 5);

