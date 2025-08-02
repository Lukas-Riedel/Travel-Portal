CREATE FUNCTION GET_PREVIOUS_TRIP_EXPECTED_TIME_OFF_TO_USE(t BIGINT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT 8 * GET_CONFIGURATION('CURRENT_FTE') * IFNULL(expected_vacation, 0)
  FROM trip_summary
  WHERE start < t
  ORDER BY start DESC
  LIMIT 1);
  
DROP FUNCTION GET_EXPECTED_TIME_OFF_TO_USE;
CREATE FUNCTION GET_EXPECTED_TIME_OFF_TO_USE(start BIGINT, end BIGINT, working_days INT, trip_id BIGINT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN (8 * GET_CONFIGURATION('CURRENT_FTE') * (
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
      - GET_SUBMITTED_TIME_OFF(start, end)
      -- Subtract expected time off carried over from the previous trip.
      - IF(GET_PREVIOUS_TRIP_EXPECTED_TIME_OFF_TO_USE(start) < 0, (-1) * GET_PREVIOUS_TRIP_EXPECTED_TIME_OFF_TO_USE(start), 0));