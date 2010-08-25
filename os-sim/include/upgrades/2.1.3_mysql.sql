-- Change range to anomaly_range
ALTER TABLE `rrd_anomalies` CHANGE `range` `anomaly_range` VARCHAR( 30 );
ALTER TABLE `rrd_anomalies_global` CHANGE `range` `anomaly_range` VARCHAR( 30 );

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.1.3');
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
