use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs = 'Logo:logo:FILE:OSS_NULLABLE::;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::35;Address:address:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::35;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT.OSS_NULLABLE::' WHERE id=440;
REPLACE INTO config (conf, value) VALUES ('server_logger_if_priority', '0');
REPLACE INTO config (conf, value) VALUES ('backup_events', '4000000');
REPLACE INTO config (conf, value) VALUES ('backup_store', '1');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-04-14');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.24');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
