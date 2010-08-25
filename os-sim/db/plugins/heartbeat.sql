-- heartbeat
-- plugin_id: 1523
--
-- $Log: heartbeat.sql,v $
-- Revision 1.2  2007/03/26 18:36:15  juanmals
-- delete previous sids before inserting new ones
--
-- Revision 1.1  2006/11/06 15:42:05  dvgil
-- migrated heartbeat plugin from old agent
--
--
DELETE FROM plugin WHERE id = "1523";
DELETE FROM plugin_sid where plugin_id = "1523";


INSERT INTO plugin (id, type, name, description) VALUES (1523, 1, 'heartbeat', 'Heartbeat without CRM');

insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 1, NULL, NULL, 'heartbeat: node up');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 2, NULL, NULL, 'heartbeat: node active');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 3, NULL, NULL, 'heartbeat: node dead');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 4, NULL, NULL, 'heartbeat: link up');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 5, NULL, NULL, 'heartbeat: link dead');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 6, NULL, NULL, 'heartbeat: resources being acquired');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 7, NULL, NULL, 'heartbeat: resources acquired');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 8, NULL, NULL, 'heartbeat: no resources to acquire');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 9, NULL, NULL, 'heartbeat: standby');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 10, NULL, NULL, 'heartbeat: standby completed');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 11, NULL, NULL, 'heartbeat: shutdown');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 12, NULL, NULL, 'heartbeat: shutdown completed');
insert into plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1523, 13, NULL, NULL, 'heartbeat: late heartbeat');


