-- Add indexes
ALTER TABLE `alarm` ADD INDEX ( `dst_port` );
ALTER TABLE `alarm` ADD INDEX ( `risk` );

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.1.4');
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
