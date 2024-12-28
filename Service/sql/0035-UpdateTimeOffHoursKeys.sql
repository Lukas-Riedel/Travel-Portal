UPDATE configuration
SET `key` = 'VACATION'
WHERE type = 'TIME_OFF_HOURS' AND `key` = 'vacation';

UPDATE configuration
SET `key` = 'TENURE'
WHERE type = 'TIME_OFF_HOURS' AND `key` = 'tenure';

UPDATE configuration
SET `key` = 'SELFCARE'
WHERE type = 'TIME_OFF_HOURS' AND `key` = 'selfcare';