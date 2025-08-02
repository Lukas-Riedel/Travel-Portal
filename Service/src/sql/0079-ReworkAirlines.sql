ALTER TABLE airline_identifier
ADD id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY;

CREATE TABLE airline_code(
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code text NOT NULL,
  airline_id bigint(20) unsigned,
  PRIMARY KEY (id),
  KEY airline_id (airline_id)
);

ALTER TABLE airline_code
ADD CONSTRAINT airline_code_ibfk_1 FOREIGN KEY (airline_id) REFERENCES airline_identifier (id);

INSERT INTO airline_code (code, airline_id)
SELECT code, id FROM airline_identifier;

ALTER TABLE airline_identifier
DROP COLUMN code;

ALTER TABLE flight_log
ADD airline_code_id bigint(20) unsigned;

ALTER TABLE flight_log
ADD CONSTRAINT flight_log_ibfk_3 FOREIGN KEY (airline_code_id) REFERENCES airline_code (id);

UPDATE flight_log fl
SET fl.airline_code_id = (SELECT ac.id FROM airline_code ac WHERE ac.code = SUBSTRING(fl.flight, 1, 2));

ALTER TABLE flight_log
MODIFY COLUMN airline_code_id bigint(20) unsigned NOT NULL;