-- arpwatch
-- plugin_id: 1512
--
-- $Id: arpwatch.sql,v 1.3 2007/08/03 11:35:49 alberto_r Exp $
--
DELETE FROM plugin WHERE id = "1512";
DELETE FROM plugin_sid where plugin_id = "1512";


INSERT INTO plugin (id, type, name, description) VALUES (1512, 1, 'arpwatch', 'Ethernet/FDDI station monitor daemon');

-- INSERT INTO plugin (id, type, name, description) VALUES (2002, 2, 'arp_watch', 'Arpwatch'); -- FIXME: still in use?


INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 1, NULL, NULL, 'arpwatch: Mac address New');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 2, NULL, NULL, 'arpwatch: Mac address Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 3, NULL, NULL, 'arpwatch: Mac address Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 4, NULL, NULL, 'arpwatch: Mac address Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1512, 5, NULL, NULL, 'arpwatch: Mac address Event unknown');


