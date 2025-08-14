CREATE TABLE airline (
  code text NOT NULL,
  name text NOT NULL,
  logo mediumtext
);

INSERT INTO airline SELECT c1.`key` AS code, c1.value AS name, c2.value AS logo FROM configuration c1 LEFT JOIN configuration c2 ON c1.`key` = c2.`key` WHERE c1.type = 'AIRLINES' AND c2.type = 'AIRLINE_LOGOS';

DELETE FROM configuration WHERE type = 'AIRLINES' OR type = 'AIRLINE_LOGOS';