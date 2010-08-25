ALTER TABLE action_email MODIFY COLUMN _from varchar(255);
ALTER TABLE action_email MODIFY COLUMN _to varchar(255);
ALTER TABLE action_email MODIFY COLUMN subject text;
ALTER TABLE action_email MODIFY COLUMN message text;

CREATE TABLE IF NOT EXISTS `policy_actions` (
  `policy_id` int(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  PRIMARY KEY  (`policy_id`,`action_id`)
);

-- Update policy/actions
INSERT IGNORE INTO policy_actions SELECT REPLACE(descr,"policy ","") AS policy_id, action_id FROM response, response_action WHERE response_action.response_id = response.id;

-- WARN! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.1.1');
