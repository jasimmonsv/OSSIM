-- pads
-- plugin_id: 1516
--
-- $Id: pads.sql,v 1.3 2007/03/26 18:36:15 juanmals Exp $
--
DELETE FROM plugin WHERE id = "1516";
DELETE FROM plugin_sid where plugin_id = "1516";


INSERT INTO plugin (id, type, name, description) VALUES (1516, 1, 'pads', 'Passive Asset Detection System');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 1, NULL, NULL, 'pads: New service detected');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 2, NULL, NULL, 'pads: Service Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 3, NULL, NULL, 'pads: Service Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 4, NULL, NULL, 'pads: Service Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 5, NULL, NULL, 'pads: Service Event unknown');


