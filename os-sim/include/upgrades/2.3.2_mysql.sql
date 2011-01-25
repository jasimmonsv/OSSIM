use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `vuln_job_schedule` CHANGE `schedule_type` `schedule_type` ENUM( 'O','D', 'W', 'M', 'NW' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'M';

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

-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-09-17" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.2');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
