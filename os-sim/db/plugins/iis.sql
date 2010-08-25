-- iis
-- plugin_id: 1502
--
-- $Log: iis.sql,v $
-- Revision 1.2  2007/03/26 18:36:15  juanmals
-- delete previous sids before inserting new ones
--
-- Revision 1.1  2006/10/29 23:09:23  dvgil
-- first iis plugin commit
--
--
DELETE FROM plugin WHERE id = "1502";
DELETE FROM plugin_sid where plugin_id = "1502";


INSERT INTO plugin (id, type, name, description) VALUES (1502, 1, 'iis', 'IIS');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 200, NULL, NULL, 'IIS: OK', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 201, NULL, NULL, 'IIS: Created');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 202, NULL, NULL, 'IIS: Accepted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 203, NULL, NULL, 'IIS: Non-Authorative Information');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 204, NULL, NULL, 'IIS: No Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 205, NULL, NULL, 'IIS: Reset Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 206, NULL, NULL, 'IIS: Partial Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 300, NULL, NULL, 'IIS: Multiple Choices');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 301, NULL, NULL, 'IIS: Moved Permanently');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 302, NULL, NULL, 'IIS: Moved Temporarily');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 303, NULL, NULL, 'IIS: See Other');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 304, NULL, NULL, 'IIS: Not Modified');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 305, NULL, NULL, 'IIS: Use Proxy');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 400, NULL, NULL, 'IIS: Bad Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 401, NULL, NULL, 'IIS: Authorization Required', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 402, NULL, NULL, 'IIS: Payment Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1502, 403, NULL, NULL, 'IIS: Forbidden', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 404, NULL, NULL, 'IIS: Not Found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 405, NULL, NULL, 'IIS: Method Not Allowed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 406, NULL, NULL, 'IIS: Not Acceptable (encoding)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 407, NULL, NULL, 'IIS: Proxy Authentication Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 408, NULL, NULL, 'IIS: Request Timed Out');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 409, NULL, NULL, 'IIS: Conflicting Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 410, NULL, NULL, 'IIS: Gone');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 411, NULL, NULL, 'IIS: Content Length Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 412, NULL, NULL, 'IIS: Precondition Failed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 413, NULL, NULL, 'IIS: Request Entity Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 414, NULL, NULL, 'IIS: Request URI Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 415, NULL, NULL, 'IIS: Unsupported Media Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 500, NULL, NULL, 'IIS: Internal Server Error');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 501, NULL, NULL, 'IIS: Not implemented');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 502, NULL, NULL, 'IIS: Bad Gateway');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 503, NULL, NULL, 'IIS: Service Unavailable');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 504, NULL, NULL, 'IIS: Gateway Timeout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1502, 505, NULL, NULL, 'IIS: HTTP Version Not Supported');


