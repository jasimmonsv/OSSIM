use ossim;
SET AUTOCOMMIT=0;
BEGIN;

CREATE TABLE IF NOT EXISTS pass_history (
  id  INTEGER NOT NULL AUTO_INCREMENT,
  user  varchar(64) NOT NULL,
  hist_number int(11),
  pass varchar(41)  NOT NULL,
  PRIMARY KEY (id)
);

REPLACE INTO config (conf , value) VALUES ('unlock_user_interval', '5');
REPLACE INTO config (conf , value) VALUES ('pass_complex', 'no');
REPLACE INTO config (conf , value) VALUES ('pass_length_min', '7');
REPLACE INTO config (conf , value) VALUES ('pass_length_max', '32');
REPLACE INTO config (conf , value) VALUES ('pass_expire', '0');
REPLACE INTO config (conf , value) VALUES ('pass_expire_min', '0');
REPLACE INTO config (conf , value) VALUES ('pass_history', '0');

REPLACE INTO log_config (code, log, descr, priority) VALUES (093, 1, 'Account %1% locked: Too many failed login attempts', 1);
REPLACE INTO log_config (code, log, descr, priority) VALUES (094, 1, 'User %1% failed logon', 1);
DELETE FROM log_config WHERE code=92;
                                        
-- From now on, always add the date of the new releases to the .sql files
UPDATE config SET value="2010-12-20" WHERE conf="last_update";

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
REPLACE INTO config (conf, value) VALUES ('ossim_schema_version', '2.3.6');
COMMIT;
-- NOTHING BELOW THIS LINE / NADA DEBAJO DE ESTA LINEA
