use ossim;
SET AUTOCOMMIT=0;
BEGIN;

DROP PROCEDURE IF EXISTS addcol;
DELIMITER '//'
CREATE PROCEDURE addcol() BEGIN
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'custom_report_scheduler' AND COLUMN_NAME = 'save_in_repository')
  THEN
      ALTER TABLE custom_report_scheduler ADD save_in_repository tinyint(1) NOT NULL DEFAULT '1';
  END IF;
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'host' AND COLUMN_NAME = 'fqdns')
  THEN
      ALTER TABLE `host` ADD `fqdns` VARCHAR( 255 ) NOT NULL AFTER `hostname` ;
  END IF;  
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin')
  THEN
      ALTER TABLE  `users` ADD  `is_admin` BOOL NOT NULL DEFAULT 0;
  END IF;  
  IF NOT EXISTS
      (SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'host' AND INDEX_NAME='search')
  THEN
      ALTER TABLE `host` ADD INDEX `search` ( `hostname` ,`fqdns` );
  END IF;  
END;
//
DELIMITER ';'
CALL addcol();
DROP PROCEDURE addcol;

DELETE FROM user_config WHERE category = 'policy' AND name = 'host_layout';

CREATE TABLE IF NOT EXISTS alarm_tags (
  id_alarm int(11) NOT NULL,
  id_tag int(11) NOT NULL,
  PRIMARY KEY (id_alarm)
);

CREATE TABLE IF NOT EXISTS `tags_alarm` (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(128) NOT NULL,
  bgcolor varchar(7) NOT NULL,
  fgcolor varchar(7) NOT NULL,
  italic int(1) NOT NULL DEFAULT '0',
  bold tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

DROP TRIGGER IF EXISTS auto_incidents;

DELIMITER "|"

CREATE TRIGGER auto_incidents AFTER INSERT ON alarm
FOR EACH ROW BEGIN
 IF EXISTS
 (SELECT value FROM config where conf = "alarms_generate_incidents" and value = "yes")
THEN
set @tmp_src_ip = NEW.src_ip;
set @tmp_dst_ip = NEW.src_ip;
set @tmp_risk = NEW.risk;
set @title = (SELECT TRIM(LEADING "directive_event:" FROM name) as name from plugin_sid where plugin_id = NEW.plugin_id and sid = NEW.plugin_sid);
set @title = REPLACE(@title,"DST_IP", inet_ntoa(NEW.dst_ip));
set @title = REPLACE(@title,"SRC_IP", inet_ntoa(NEW.src_ip));
set @title = REPLACE(@title,"PROTOCOL", NEW.protocol);
set @title = REPLACE(@title,"SRC_PORT", NEW.src_port);
set @title = REPLACE(@title,"DST_PORT", NEW.dst_port);
set @title = CONCAT(@title, " (", inet_ntoa(NEW.src_ip), ":", CAST(NEW.src_port AS CHAR), " -> ", inet_ntoa(NEW.dst_ip), ":", CAST(NEW.dst_port AS CHAR), ")");
insert into incident(title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values (@title, NEW.timestamp, "Alarm", "Generic", NEW.risk, "Open", NOW(), "admin", "admin", NEW.timestamp, NEW.timestamp);
set @last_id = (SELECT LAST_INSERT_ID() FROM incident limit 1);
insert into incident_alarm(incident_id, src_ips, dst_ips, src_ports, dst_ports, backlog_id, event_id, alarm_group_id) values (@last_id, inet_ntoa(NEW.src_ip), inet_ntoa(NEW.dst_ip), NEW.src_port, NEW.dst_port, NEW.backlog_id, NEW.event_id, 0);
CALL incident_ticket_populate(@last_id, @tmp_src_ip, @tmp_dst_ip, 1,@tmp_risk);
END IF;
END;
|

DELIMITER ";"

DROP PROCEDURE IF EXISTS incident_ticket_populate;
DELIMITER "|"

CREATE PROCEDURE incident_ticket_populate(incident_id INT, src_ip INT, dst_ip INT, i INT, prio INT)
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE count INT;
  DECLARE cnt_src, cnt_dst INT;
  DECLARE name, subname VARCHAR(255);
  DECLARE first_occ, last_occ TIMESTAMP;
  DECLARE source VARCHAR(15);
  DECLARE dest VARCHAR(15);

  DECLARE cur1 CURSOR FOR select count(*) as cnt,  inet_ntoa(snort.acid_event.ip_src) as src, inet_ntoa(snort.acid_event.ip_dst) as dst, ossim.plugin.name, ossim.plugin_sid.name, min(timestamp) as frst, max(timestamp) as last, count(distinct(acid_event.ip_src)) as cnt_src, count(distinct(acid_event.ip_dst)) as cnt_dst from snort.acid_event, ossim.plugin, ossim.plugin_sid where (ip_src = src_ip or ip_dst = src_ip or ip_src = dst_ip or ip_dst =dst_ip ) and timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND ossim.plugin.id = snort.acid_event.plugin_id and ossim.plugin_sid.sid = snort.acid_event.plugin_sid and ossim.plugin_sid.plugin_id = snort.acid_event.plugin_id group by snort.acid_event.plugin_id, snort.acid_event.plugin_sid ORDER by cnt DESC limit 50;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN cur1;

INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW()-1, "Open", prio, "admin", "The following tickets contain information about the top 50 event types the hosts have been generating during the last 7 days.");
SET i = i + 1;

  REPEAT
	FETCH cur1 INTO count, source, dest, name, subname, first_occ, last_occ, cnt_src, cnt_dst;
	IF NOT done THEN
		SET @desc = CONCAT( "Event Type: ",  name, "\nEvent Description: ", subname, "\nOcurrences: ",CAST(count AS CHAR), "\nFirst Ocurrence: ", CAST(first_occ AS CHAR(50)), "\nLast Ocurrence: ", CAST(last_occ AS CHAR(50)),"\nNumber of different sources: ", CAST(cnt_src AS CHAR), "\nNumber of different destinations: ", CAST(cnt_dst AS CHAR), "\nSource: ", source, "\nDest: ", dest);
		INSERT INTO incident_ticket(id,incident_id,date,status,priority,users,description) VALUES (i, incident_id, NOW(), "Open", prio, "admin", @desc);
		SET i = i + 1;
	END IF;
  UNTIL done END REPEAT;


  CLOSE cur1;
END
|

DELIMITER ";"

ALTER TABLE `event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid` CHAR(36) ASCII;
ALTER TABLE `backlog_event` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_event` CHAR(36) ASCII;
ALTER TABLE `alarm` MODIFY `uuid_backlog` CHAR(36) ASCII;

-- Assing uuid to tables and 
UPDATE event SET uuid=UPPER(UUID()) WHERE uuid IS NULL;
COMMIT;
UPDATE backlog SET uuid=UPPER(UUID()) WHERE uuid IS NULL;
COMMIT;
UPDATE backlog_event,backlog,event SET backlog_event.uuid=backlog.uuid,backlog_event.uuid_event=event.uuid WHERE backlog.id = backlog_event.backlog_id AND backlog_event.event_id = event.id;
COMMIT;
UPDATE alarm,backlog,event SET alarm.uuid_event = event.uuid,alarm.uuid_backlog = backlog.uuid WHERE alarm.event_id = event.id AND alarm.backlog_id = backlog.id;
COMMIT;

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-13" WHERE conf="last_update";

-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
