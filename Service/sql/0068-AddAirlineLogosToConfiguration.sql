ALTER TABLE configuration
MODIFY COLUMN value MEDIUMTEXT;

INSERT INTO configuration SELECT 'AIRLINE_LOGOS' AS type, 'public,modifiable' AS levels, c.`key` AS `key`, "" AS value FROM configuration c WHERE c.`type` = 'AIRLINES';