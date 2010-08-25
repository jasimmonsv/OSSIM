-- apache modsecurity
-- plugin_id: 1561
--
-- $Id: modsecurity.sql,v 1.1 2009/01/11 18:19:16 dvgil Exp $
--

DELETE FROM plugin WHERE id = "1561";
DELETE FROM plugin_sid where plugin_id = "1561";

INSERT INTO plugin (id, type, name, description) VALUES (1561, 1, 'modsecurity', 'ModSecurity');

-- INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1561, 200, NULL, NULL, 'modsecurity: OK', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 201, NULL, NULL, 'modsecurity: Created');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 202, NULL, NULL, 'modsecurity: Accepted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 203, NULL, NULL, 'modsecurity: Non-Authorative Information');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 204, NULL, NULL, 'modsecurity: No Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 205, NULL, NULL, 'modsecurity: Reset Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 206, NULL, NULL, 'modsecurity: Partial Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 300, NULL, NULL, 'modsecurity: Multiple Choices');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 301, NULL, NULL, 'modsecurity: Moved Permanently');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 302, NULL, NULL, 'modsecurity: Moved Temporarily');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 303, NULL, NULL, 'modsecurity: See Other');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 304, NULL, NULL, 'modsecurity: Not Modified');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 305, NULL, NULL, 'modsecurity: Use Proxy');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 307, NULL, NULL, 'modsecurity: Temporary Redirect');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 400, NULL, NULL, 'modsecurity: Bad Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1561, 401, NULL, NULL, 'modsecurity: Authorization Required', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 402, NULL, NULL, 'modsecurity: Payment Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1561, 403, NULL, NULL, 'modsecurity: Forbidden', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 404, NULL, NULL, 'modsecurity: Not Found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 405, NULL, NULL, 'modsecurity: Method Not Allowed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 406, NULL, NULL, 'modsecurity: Not Acceptable (encoding)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 407, NULL, NULL, 'modsecurity: Proxy Authentication Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 408, NULL, NULL, 'modsecurity: Request Timed Out');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 409, NULL, NULL, 'modsecurity: Conflicting Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 410, NULL, NULL, 'modsecurity: Gone');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 411, NULL, NULL, 'modsecurity: Content Length Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 412, NULL, NULL, 'modsecurity: Precondition Failed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 413, NULL, NULL, 'modsecurity: Request Entity Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 414, NULL, NULL, 'modsecurity: Request URI Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 415, NULL, NULL, 'modsecurity: Unsupported Media Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 500, NULL, NULL, 'modsecurity: Internal Server Error');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 501, NULL, NULL, 'modsecurity: Not implemented');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 502, NULL, NULL, 'modsecurity: Bad Gateway');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 503, NULL, NULL, 'modsecurity: Service Unavailable');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 504, NULL, NULL, 'modsecurity: Gateway Timeout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1561, 505, NULL, NULL, 'modsecurity: HTTP Version Not Supported');


