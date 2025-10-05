ALTER TABLE note
ADD timestamp bigint(20);

UPDATE note
SET timestamp = UNIX_TIMESTAMP();

ALTER TABLE note
MODIFY COLUMN timestamp bigint(20) unsigned NOT NULL;