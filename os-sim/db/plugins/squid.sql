-- squid
-- plugin_id: 1553
--
-- Plugin sids from apache plugin

DELETE FROM plugin WHERE id = "1553";
DELETE FROM plugin_sid where plugin_id = "1553";

INSERT INTO plugin (id, type, name, description) VALUES (1553, 1, 'squid', 'Squid');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1553, 200, NULL, NULL, 'squid: OK', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 201, NULL, NULL, 'squid: Created');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 202, NULL, NULL, 'squid: Accepted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 203, NULL, NULL, 'squid: Non-Authorative Information');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 204, NULL, NULL, 'squid: No Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 205, NULL, NULL, 'squid: Reset Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 206, NULL, NULL, 'squid: Partial Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 300, NULL, NULL, 'squid: Multiple Choices');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 301, NULL, NULL, 'squid: Moved Permanently');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 302, NULL, NULL, 'squid: Moved Temporarily');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 303, NULL, NULL, 'squid: See Other');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 304, NULL, NULL, 'squid: Not Modified');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 305, NULL, NULL, 'squid: Use Proxy');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 307, NULL, NULL, 'squid: Temporary Redirect');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 400, NULL, NULL, 'squid: Bad Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1553, 401, NULL, NULL, 'squid: Authorization Required', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 402, NULL, NULL, 'squid: Payment Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1553, 403, NULL, NULL, 'squid: Forbidden', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 404, NULL, NULL, 'squid: Not Found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 405, NULL, NULL, 'squid: Method Not Allowed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 406, NULL, NULL, 'squid: Not Acceptable (encoding)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 407, NULL, NULL, 'squid: Proxy Authentication Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 408, NULL, NULL, 'squid: Request Timed Out');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 409, NULL, NULL, 'squid: Conflicting Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 410, NULL, NULL, 'squid: Gone');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 411, NULL, NULL, 'squid: Content Length Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 412, NULL, NULL, 'squid: Precondition Failed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 413, NULL, NULL, 'squid: Request Entity Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 414, NULL, NULL, 'squid: Request URI Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 415, NULL, NULL, 'squid: Unsupported Media Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 500, NULL, NULL, 'squid: Internal Server Error');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 501, NULL, NULL, 'squid: Not implemented');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 502, NULL, NULL, 'squid: Bad Gateway');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 503, NULL, NULL, 'squid: Service Unavailable');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 504, NULL, NULL, 'squid: Gateway Timeout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1553, 505, NULL, NULL, 'squid: HTTP Version Not Supported');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1553, 1200, NULL, NULL, 'squid: cgi-tunnel', 3, 2);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1553, 1201, NULL, NULL, 'squid: possible-tunnel', 3, 1);


