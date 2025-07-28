ALTER TABLE configuration
ADD private tinyint(4);

UPDATE configuration
SET private = 1
WHERE FIND_IN_SET('private', levels);

UPDATE configuration
SET private = 0
WHERE NOT FIND_IN_SET('private', levels);

ALTER TABLE configuration
CHANGE private private tinyint(4) NOT NULL;

ALTER TABLE configuration
DROP levels;

UPDATE configuration
SET type = 'GOOGLE_API_ACCESS_KEY', 
    `key` = NULL
WHERE type = 'GOOGLE_API_CREDENTIALS'
    AND `key` = 'accessKey';

UPDATE configuration
SET type = 'GOOGLE_CALENDAR_WATCH_TTL', 
    `key` = NULL
WHERE type = 'GOOGLE_CALENDAR_API'
    AND `key` = 'ttl';