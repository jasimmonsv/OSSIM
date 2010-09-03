use ossim;
SET AUTOCOMMIT=0;
BEGIN;

ALTER TABLE `vuln_job_schedule` CHANGE `schedule_type` `schedule_type` ENUM( 'O','D', 'W', 'M', 'NW' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'M';
DELETE FROM `user_config` WHERE category='policy' AND name='sensors_layout';
INSERT INTO config (conf , value) VALUES ('tickets_max_days', '15');

INSERT INTO `custom_report_types` VALUES(500, 'Historical View', 'Network', 'Network/HistoricalView.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(501, 'Historical View', 'Network', 'Network/HistoricalView.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(502, 'Historical View', 'Network', 'Network/HistoricalView.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(503, 'Historical View', 'Network', 'Network/HistoricalView.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(504, 'Historical View', 'Network', 'Network/HistoricalView.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(505, 'Global TCP/UDP Protocol Distribution', 'Network', 'Network/GlobalTCPUDPProtocolDistribution.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(506, 'Global TCP/UDP Protocol Distribution', 'Network', 'Network/GlobalTCPUDPProtocolDistribution.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(507, 'Global TCP/UDP Protocol Distribution', 'Network', 'Network/GlobalTCPUDPProtocolDistribution.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(508, 'Global TCP/UDP Protocol Distribution', 'Network', 'Network/GlobalTCPUDPProtocolDistribution.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(509, 'Global TCP/UDP Protocol Distribution', 'Network', 'Network/GlobalTCPUDPProtocolDistribution.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(510, 'Throughput', 'Network', 'Network/Throughput.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(511, 'Throughput', 'Network', 'Network/Throughput.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(512, 'Throughput', 'Network', 'Network/Throughput.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(513, 'Throughput', 'Network', 'Network/Throughput.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);
INSERT INTO `custom_report_types` VALUES(514, 'Throughput', 'Network', 'Network/Throughput.php', 'Interface:INTERFACE:multiselect:OSS_ALPHA.OSS_COLON.OSS_SPACE.OSS_SCORE.OSS_DOT', '', 1);

INSERT INTO `custom_report_types` VALUES(145, 'Top Events', 'Logger', 'Logger/List.php', 'Top Logger Events List:top:text:OSS_DIGIT:25:250;Product Type:sourcetype:select:OSS_ALPHA.OSS_SLASH.OSS_SPACE.OSS_NULLABLE:SOURCETYPE:;Event Category:category:select:OSS_DIGIT.OSS_NULLABLE:CATEGORY:;Event SubCategory:subcategory:select:OSS_DIGIT.OSS_NULLABLE:SUBCATEGORY:', '', 1);

UPDATE custom_report_types SET inputs = 'Logo:logo:FILE:OSS_NULLABLE::' WHERE name='Title Page' and type='Title Page';

DROP TABLE IF EXISTS `custom_report_scheduler`;
CREATE TABLE IF NOT EXISTS `custom_report_scheduler` (
  `id` int(11) NOT NULL auto_increment,
  `schedule_type` varchar(20) NOT NULL,
  `schedule` text NOT NULL,
  `next_launch` datetime NOT NULL,
  `id_report` varchar(100) NOT NULL,
  `name_report` varchar(100) NOT NULL,
  `email` varchar(255) default NULL,
  `date_from` date default NULL,
  `date_to` date default NULL,
  `date_range` varchar(30) default NULL,
  `assets` tinytext,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `risk_maps`;
CREATE TABLE IF NOT EXISTS `risk_maps` (
  `map` varchar(64) NOT NULL,
  `perm` varchar(64) NOT NULL,
  PRIMARY KEY (`map`,`perm`)
);


-- From now on, always add the date of the new releases to the .sql files
-- UPDATE config SET value="2010-07-23" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
