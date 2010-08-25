-- OPENLDAP
-- plugin_id: 9021

delete from plugin where id=1586;

delete from plugin_sid where plugin_id=1586;

INSERT INTO plugin (id, type, name, description) VALUES (1586, 1, 'openldap', 'OpenLDAP');	
	
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1586, 1, NULL, NULL, 1, 3, 'OPENLDAP: Authentication Failure'); 
INSERT INTO `plugin_sid` (`plugin_id`, `sid`, `category_id`, `class_id`, `reliability`, `priority`, `name`) VALUES (1586, 2, NULL, NULL, 1, 3, 'OPENLDAP: Authentication Success');
