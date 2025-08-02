CREATE TABLE trip_day_trip (
  trip_id bigint(20) unsigned NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  PRIMARY KEY (trip_id)
);

ALTER TABLE trip_day_trip
ADD CONSTRAINT trip_day_trip_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

INSERT INTO trip_day_trip (trip_id, start, end) SELECT trip_id, start, end FROM trip_event WHERE id IS NULL;

DELETE FROM trip_event WHERE id IS NULL;

ALTER TABLE trip_event
MODIFY id text NOT NULL;

ALTER TABLE place_event
DROP FOREIGN KEY place_event_ibfk_2;

ALTER TABLE place_event
MODIFY trip_id bigint(20) unsigned NOT NULL;

ALTER TABLE place_event
ADD CONSTRAINT place_event_ibfk_2 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE stay_event
DROP FOREIGN KEY stay_event_ibfk_1;

ALTER TABLE stay_event
MODIFY trip_id bigint(20) unsigned NOT NULL;

ALTER TABLE stay_event
ADD CONSTRAINT stay_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE flight_event
DROP FOREIGN KEY flight_event_ibfk_1;

ALTER TABLE flight_event
MODIFY trip_id bigint(20) unsigned NOT NULL;

ALTER TABLE flight_event
ADD CONSTRAINT flight_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE flight_watched_event
DROP FOREIGN KEY flight_watched_event_ibfk_1;

ALTER TABLE flight_watched_event
MODIFY trip_id bigint(20) unsigned NOT NULL;

ALTER TABLE flight_watched_event
ADD CONSTRAINT flight_watched_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

DROP VIEW _trip_summary;
CREATE VIEW _trip_summary AS
  SELECT te.id,
    te.trip_id,
    ti.name,
    ti.year,
    ti.main_highlight_id,
    te.start,
    te.end,
    IF(IS_DAY_TRIP(te.trip_id), (
      SELECT COUNT(DISTINCT CAST(FROM_UNIXTIME(p.start) as DATE)) 
      FROM place_event p
      WHERE p.trip_id = te.trip_id),
      GET_DAYS_COUNT(te.start, te.end, 0)) AS days,
    IF(IS_DAY_TRIP(te.trip_id) OR ti.year < YEAR(CURDATE()),
      NULL,
      GET_DAYS_COUNT(te.start, te.end, 1)) AS working_days,
    IFNULL((
      SELECT SUM(es.main_currency_value)
      FROM _expense_summary es
      WHERE es.trip_id = te.trip_id), 0) AS cost,
    IF(te.start < UNIX_TIMESTAMP(), 
      NULL, 
      IFNULL(GET_EXPECTED_TIME_OFF_TO_USE(te.start, te.end, IF(IS_DAY_TRIP(te.trip_id) OR ti.year < YEAR(CURDATE()),
        NULL,
        GET_DAYS_COUNT(te.start, te.end, 1)), te.trip_id) / (8 * GET_CONFIGURATION('CURRENT_FTE')), 0)) AS expected_vacation,
    IF(te.start < UNIX_TIMESTAMP(),
      NULL,
      IFNULL(GET_MAX_TIME_OFF_TO_USE(IF(IS_DAY_TRIP(te.trip_id) OR ti.year < YEAR(CURDATE()),
        NULL,
        GET_DAYS_COUNT(te.start, te.end, 1)), ti.year) / (8 * GET_CONFIGURATION('CURRENT_FTE')), 0)) AS max_vacation
  FROM (
    SELECT id, trip_id, start, end
    FROM trip_event
    UNION
    SELECT NULL, trip_id, start, end
    FROM trip_day_trip
  ) te
    INNER JOIN trip_identifier ti
      ON te.trip_id = ti.id
  ORDER BY te.end;

UPDATE pruner
SET query = 'DELETE FROM trip_identifier WHERE id NOT IN (SELECT trip_id FROM trip_event) AND id NOT IN (SELECT trip_id FROM place_candidate_event) AND id NOT IN (SELECT trip_id FROM trip_day_trip)'
WHERE name = 'PRUNE_TRIP_IDENTIFIERS';