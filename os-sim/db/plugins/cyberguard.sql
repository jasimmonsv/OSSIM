-- Cyberguard
-- plugin_id: 1575

delete from plugin where id=1575;
delete from plugin_sid where plugin_id=1575;
INSERT INTO plugin (id, type, name, description) values (1575, 1, 'cyberguard', 'Snort Rules');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 1, NULL, NULL, 1, 3, 'Firewall Cyberguard: DENY');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 2, NULL, NULL, 1, 3, 'Firewall Cyberguard: DROP');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 3, NULL, NULL, 1, 3, 'Firewall Cyberguard: REJECT');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 4, NULL, NULL, 1, 3, 'Firewall Cyberguard: ALLOW');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1575, 5, NULL, NULL, 1, 3, 'Firewall Cyberguard: ACCEPT');


