-- optener antispam
-- plugin_id: 1564

DELETE FROM plugin WHERE id = "1564";
DELETE FROM plugin_sid where plugin_id = "1564";

INSERT INTO plugin (id, type, name, description) VALUES(1564, 1, "nepenthes", "Nepenthes Honeypot");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 1, null, null, 1, 1, "nepenthes: Incoming Connection");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 2, null, null, 1, 1, "nepenthes: Shellcode Detected");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 3, null, null, 1, 1, "nepenthes: Transfer Attempt");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 4, null, null, 1, 1, "nepenthes: Handler download attempt");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 5, null, null, 1, 1, "nepenthes: Download failed");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 6, null, null, 1, 1, "nepenthes: Download done");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 7, null, null, 1, 1, "nepenthes: File submission");
insert into plugin_sid (plugin_id, sid, category_id, class_id, reliability, priority, name) values(1564, 8, null, null, 1, 1, "nepenthes: Malware on download file");
