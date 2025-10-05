DELETE
FROM configuration
WHERE type = 'CHAT_REQUESTS'
  AND `key` = 'mapPoints';

DELETE
FROM configuration
WHERE type = 'AUTO_PURGE_RETENTION_DAYS'
  AND `key` = 'points';

DROP TABLE cache_point;