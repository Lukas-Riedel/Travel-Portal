CREATE TABLE view_materialization (
  view_name text NOT NULL,
  last_materialization_duration bigint(11) NOT NULL,
  is_materialization_delayed tinyint(4) NOT NULL
);

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
  FROM trip_event te
    INNER JOIN trip_identifier ti
      ON te.trip_id = ti.id
  ORDER BY te.end;