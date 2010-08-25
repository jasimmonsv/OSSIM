-- netscreen-manager
-- plugin_id: 1520
--
-- $Id: netscreen-manager.sql,v 1.3 2007/08/03 11:35:49 alberto_r Exp $
--
DELETE FROM plugin WHERE id = "1520";
DELETE FROM plugin_sid where plugin_id = "1520";


INSERT INTO plugin (id, type, name, description) VALUES (1520, 1, 'netscreen-manager', 'Juniper Netscreen Security Manager');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1520, 1, NULL, NULL, 'Netscreen: Accepted ');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1520, 2, NULL, NULL, 'Netscreen: Packet Dropped');


