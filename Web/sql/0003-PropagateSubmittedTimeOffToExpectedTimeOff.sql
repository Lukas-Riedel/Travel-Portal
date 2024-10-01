CREATE FUNCTION GET_SUBMITTED_TIME_OFF(s BIGINT, e BIGINT) RETURNS bigint
    READS SQL DATA
    DETERMINISTIC
RETURN (
    SELECT ABS(IFNULL(SUM(hours), 0))
    FROM tracking
    WHERE type <> 'OVERTIME'
        AND timestamp >= UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(s)))
        AND timestamp <= UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(e)))
);

DROP FUNCTION GET_EXPECTED_TIME_OFF_TO_USE;
CREATE FUNCTION GET_EXPECTED_TIME_OFF_TO_USE(start BIGINT, end BIGINT, working_days INT, trip_id BIGINT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN GREATEST(0, 8 * GET_CONFIGURATION('CURRENT_FTE') * (
      -- Use working_days as a base.
      working_days 
      -- Subtract a day if departing late (working on that day).
      - IS_LATE_WEEKDAY_DEPARTURE(trip_id)
      -- Subtract a day if arriving early (working on that day).
      - IS_EARLY_WEEKDAY_ARRIVAL(trip_id)
      -- Subtract all week-day holidays before the trip (working on those days).
      - GET_PUBLIC_WEEKDAY_HOLIDAYS_BEFORE_TRIP_COUNT(trip_id)
      -- Subtract all overtime hours expected to be gained before the trip.
      - GET_DAYS_BEFORE_TRIP_COUNT(trip_id, 1) * (GET_CONFIGURATION('EXPECTED_OVERTIME_HOURS_PER_DAY') / (8 * GET_CONFIGURATION('CURRENT_FTE')))
      -- Subtract already gained before the trip (if this is the upcoming trip).
      - IF(trip_id = GET_UPCOMING_TRIP_ID(), (SELECT SUM(hours) FROM tracking WHERE type = 'OVERTIME') / (8 * GET_CONFIGURATION('CURRENT_FTE')), 0))
      -- Subtract already submitted time off.
      - GET_SUBMITTED_TIME_OFF(start, end));

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
      FROM expense_summary es
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
  FROM trip_event te
    INNER JOIN trip_identifier ti
      ON te.trip_id = ti.id
  ORDER BY te.end;