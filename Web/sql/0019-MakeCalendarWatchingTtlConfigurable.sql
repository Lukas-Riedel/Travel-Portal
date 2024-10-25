UPDATE configuration
SET type = 'GOOGLE_CALENDAR_API', `key` = 'watchId'
WHERE type = 'GOOGLE_CALENDAR_API_WATCH_UUID';

INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_CALENDAR_API', 'public', 'ttl', '86400');

UPDATE scheduler
SET args_query = 'SELECT u.watchId AS watchId, `key` AS calendar FROM configuration c, (SELECT UUID() AS watchId) u WHERE type = ''CALENDARS'''
WHERE name = 'START_CALENDAR_WATCHING';