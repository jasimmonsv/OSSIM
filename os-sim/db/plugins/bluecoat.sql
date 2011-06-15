--
-- Bluecoat Sids
-- 1642

DELETE FROM plugin WHERE id = "1642";
DELETE FROM plugin_sid where plugin_id = "1642";


INSERT INTO plugin (id, type, name, description) VALUES (1642, 1, 'bluecoat', 'Bluecoat Proxy');

INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1642, 200, NULL, NULL, 'bluecoat: OK', 0, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 201, NULL, NULL, 'bluecoat: Created');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 202, NULL, NULL, 'bluecoat: Accepted');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 203, NULL, NULL, 'bluecoat: Non-Authorative Information');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 204, NULL, NULL, 'bluecoat: No Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 205, NULL, NULL, 'bluecoat: Reset Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 206, NULL, NULL, 'bluecoat: Partial Content');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 300, NULL, NULL, 'bluecoat: Multiple Choices');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 301, NULL, NULL, 'bluecoat: Moved Permanently');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 302, NULL, NULL, 'bluecoat: Moved Temporarily');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 303, NULL, NULL, 'bluecoat: See Other');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 304, NULL, NULL, 'bluecoat: Not Modified');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 305, NULL, NULL, 'bluecoat: Use Proxy');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 400, NULL, NULL, 'bluecoat: Bad Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1642, 401, NULL, NULL, 'bluecoat: Authorization Required', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 402, NULL, NULL, 'bluecoat: Payment Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1642, 403, NULL, NULL, 'bluecoat: Forbidden', 3, 1);
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 404, NULL, NULL, 'bluecoat: Not Found');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 405, NULL, NULL, 'bluecoat: Method Not Allowed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 406, NULL, NULL, 'bluecoat: Not Acceptable (encoding)');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 407, NULL, NULL, 'bluecoat: Proxy Authentication Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 408, NULL, NULL, 'bluecoat: Request Timed Out');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 409, NULL, NULL, 'bluecoat: Conflicting Request');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 410, NULL, NULL, 'bluecoat: Gone');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 411, NULL, NULL, 'bluecoat: Content Length Required');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 412, NULL, NULL, 'bluecoat: Precondition Failed');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 413, NULL, NULL, 'bluecoat: Request Entity Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 414, NULL, NULL, 'bluecoat: Request URI Too Long');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 415, NULL, NULL, 'bluecoat: Unsupported Media Type');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 500, NULL, NULL, 'bluecoat: Internal Server Error');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 501, NULL, NULL, 'bluecoat: Not implemented');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 502, NULL, NULL, 'bluecoat: Bad Gateway');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 503, NULL, NULL, 'bluecoat: Service Unavailable');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 504, NULL, NULL, 'bluecoat: Gateway Timeout');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 505, NULL, NULL, 'bluecoat: HTTP Version Not Supported');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 600, NULL, NULL, 'bluecoat: Proxy SG: NORMAL EVENT');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 601, NULL, NULL, 'bluecoat: Proxy SG: NORMAL EVENT');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 602, NULL, NULL, 'bluecoat: Proxy SG: NORMAL EVENT');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 603, NULL, NULL, 'bluecoat: Proxy SG: NORMAL EVENT');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 604, NULL, NULL, 'bluecoat: Proxy SG: SEVERE ERROR');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 605, NULL, NULL, 'bluecoat: Generic Traffic');
INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name) VALUES (1642, 999, NULL, NULL, 'bluecoat: Capture All');

