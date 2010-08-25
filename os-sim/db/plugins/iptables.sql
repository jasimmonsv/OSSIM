-- iptables
-- plugin_id: 1503
--
-- $Log: iptables.sql,v $
-- Revision 1.4  2009/05/20 13:53:04  alberto_r
-- event without defined sid
--
-- Revision 1.3  2007/03/26 18:36:15  juanmals
-- delete previous sids before inserting new ones
--
-- Revision 1.2  2006/10/31 08:51:06  dvgil
-- use the real name of the plugin
--
-- Revision 1.1  2006/10/31 08:45:16  dvgil
-- first iptables plugin commit
--
--
DELETE FROM plugin WHERE id = "1503";
DELETE FROM plugin_sid where plugin_id = "1503";


INSERT INTO plugin (id, type, name, description) VALUES (1503, 1, 'iptables', 'Iptables');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1503, 1, 202, NULL, 'iptables: Accept', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 2, 203, NULL, 'iptables: Reject');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 3, 204, NULL, 'iptables: Drop');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 4, NULL, NULL, 'iptables: traffic inbound');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 5, NULL, NULL, 'iptables: traffic outbound');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1503, 6, NULL, NULL, 'iptables: Generic event');



