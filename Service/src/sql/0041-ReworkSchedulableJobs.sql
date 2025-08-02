DELETE
FROM configuration
WHERE type = 'PROCESSORS_DEFAULT_PRIORITIES';

DROP TABLE scheduler;

CREATE TABLE scheduler (
    action text NOT NULL,
    last_triggered bigint(20) NOT NULL
);

INSERT INTO scheduler (action, last_triggered) VALUES ('WATCH_CALENDAR', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('RESET_OPENING_BALANCES', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_ACTUAL_WEATHER_FORECAST', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_HISTORICAL_WEATHER_FORECAST', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_DAYLIGHT_FORECAST', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_ALBUMS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_HIGHLIGHTS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('FETCH_FITNESS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('UPDATE_OVERALL_STATISTICS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('UPDATE_TRIP_STATISTICS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('UPDATE_YEAR_STATISTICS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('UPDATE_CATEGORY_STATISTICS', UNIX_TIMESTAMP());
INSERT INTO scheduler (action, last_triggered) VALUES ('LOG_FLIGHTS', UNIX_TIMESTAMP());

ALTER TABLE queue_job
CHANGE processor event text NOT NULL;

RENAME TABLE queue_job TO queue_event;