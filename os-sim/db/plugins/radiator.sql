-- BIND (DNS)
-- plugin_id: 1589

delete from plugin where id=1589;
delete from plugin_sid where plugin_id=1589;

INSERT INTO plugin (id, type, name, description) VALUES (1589, 1, 'radiator', 'Radiator');	
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 1, NULL, NULL, 1, 3, 'Radiator: Request Ignored');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 2, NULL, NULL, 1, 3, 'Radiator: Handle Request');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 3, NULL, NULL, 1, 3, 'Radiator: Connection attempt');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 4, NULL, NULL, 1, 3, 'Radiator: LDAP bind attempt');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 5, NULL, NULL, 1, 3, 'Radiator: Unknown attribute');
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1589, 6, NULL, NULL, 1, 3, 'Radiator: Wrong attribute value');
