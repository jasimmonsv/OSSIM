-- Pads fingerprintings SIDs--

DELETE FROM plugin_sid WHERE plugin_id=1516;
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 1, NULL, NULL, 'pads: New service detected');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 2, NULL, NULL, 'pads: Service Change');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 3, NULL, NULL, 'pads: Service Deleted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 4, NULL, NULL, 'pads: Service Same');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1516, 5, NULL, NULL, 'pads: Service Event unknown');

-- New fields in host_ids

ALTER TABLE host_ids CHANGE sid plugin_sid INTEGER NOT NULL;
ALTER TABLE host_ids ADD cid INTEGER UNSIGNED NOT NULL AFTER extra_data;
ALTER TABLE host_ids ADD sid INTEGER UNSIGNED NOT NULL AFTER cid;


-- Replacing draw_graph.pl with the new one

REPLACE INTO config (conf, value) VALUES ('graph_link', '../report/graphs/draw_rrd.php');

-- Performance tip
CREATE INDEX event_idx ON event (id);




-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '0.9.9rc2');
-- vim:ts=4 sts=4 tw=79 expandtab: 
