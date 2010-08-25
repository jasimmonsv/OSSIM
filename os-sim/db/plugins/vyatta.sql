DELETE FROM plugin WHERE id = "1610";
DELETE FROM plugin_sid where plugin_id = "1610";


INSERT INTO plugin (id, type, name, description) VALUES (1610, 1, 'vyatta', 'Vyatta');

INSERT INTO plugin_sid (plugin_id, sid, name) VALUES (1610, 1, 'vyatta: Accept');
INSERT INTO plugin_sid (plugin_id, sid, name) VALUES (1610, 2, 'vyatta: Drop');
INSERT INTO plugin_sid (plugin_id, sid, name) VALUES (1610, 3, 'vyatta: Reject');



