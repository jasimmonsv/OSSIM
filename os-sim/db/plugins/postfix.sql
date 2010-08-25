-- postfix
-- type: detector
-- plugin_id: 1521
--
-- $Id: postfix.sql,v 1.4 2009/03/13 13:40:53 dvgil Exp $
--
DELETE FROM plugin WHERE id = "1521";
DELETE FROM plugin_sid where plugin_id = "1521";


INSERT INTO plugin (id, type, name, description) VALUES (1521, 1, 'postfix', 'Postfix mailer');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 1, NULL, NULL, 'Postfix: relaying denied');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 2, NULL, NULL, 'Postfix: sender domain not found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 3, NULL, NULL, 'Postfix: recipient user unknown');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 4, NULL, NULL, 'Postfix: blocked using spamhaus');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 5, NULL, NULL, 'Postfix: blocked using njabl');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 6, NULL, NULL, 'Postfix: suspicious access');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 10, NULL, NULL, 'Postfix: mail sent');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 11, NULL, NULL, 'Postfix: mail bounced');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1521, 5000, NULL, NULL, 'Postfix: blocked using a list');

