UPDATE fitness
SET minutes = minutes * 60;

ALTER TABLE fitness
CHANGE COLUMN minutes seconds bigint(20) NOT NULL;