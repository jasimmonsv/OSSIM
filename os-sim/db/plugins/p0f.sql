-- p0f
-- plugin_id: 1511
--
-- $Id: p0f.sql,v 1.3 2007/08/03 11:35:49 alberto_r Exp $
--
DELETE FROM plugin WHERE id = "1511";
DELETE FROM plugin_sid where plugin_id = "1511";


INSERT INTO plugin (id, type, name, description) VALUES (1511, 1, 'p0f', 'Passive OS fingerprinting tool');
-- INSERT INTO plugin (id, type, name, description) VALUES (2003, 2, 'p0f', 'P0f'); -- FIXME: still in use?


INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 1, NULL, NULL, 'p0f: New OS');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 2, NULL, NULL, 'p0f: OS Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 3, NULL, NULL, 'p0f: OS Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 4, NULL, NULL, 'p0f: OS Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1511, 5, NULL, NULL, 'p0f: OS Event unknown');


