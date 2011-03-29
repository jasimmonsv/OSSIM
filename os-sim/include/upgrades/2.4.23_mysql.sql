use ossim;
SET AUTOCOMMIT=0;
BEGIN;

UPDATE custom_report_types SET inputs = 'Status:status:select:OSS_LETTER:Open,Closed,All' WHERE id in (320,321,322,323,324);
UPDATE custom_report_types SET inputs = 'Logo:logo:FILE:OSS_NULLABLE::;I.T. Security:it_security:text:OSS_TEXT.OSS_PUNC_EXT::35;Address:address:text:OSS_TEXT.OSS_PUNC_EXT::35;Tel:tlfn:text:OSS_TEXT.OSS_PUNC_EXT::;Date:date:text:OSS_TEXT.OSS_PUNC_EXT::' WHERE id=440;
REPLACE INTO config (conf, value) VALUES ('nessus_pre_scan_locally', '1');

-- WARNING! Keep this at the end of this file
-- ATENCION! Keep this at the end of this file
use ossim;
REPLACE INTO config (conf, value) VALUES ('last_update', '2011-03-25');
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.4.23');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
