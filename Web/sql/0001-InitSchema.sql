CREATE TABLE airport_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code text NOT NULL,
  latitude double NOT NULL,
  longitude double NOT NULL,
  country text NOT NULL,
  timezone text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE album (
  name text NOT NULL,
  id bigint(20) unsigned NOT NULL,
  main_photo_id bigint(20) unsigned DEFAULT NULL,
  main_image_url text NOT NULL,
  images_count int(11) NOT NULL,
  indoor_images_count int(11) NOT NULL,
  permalink text NOT NULL,
  PRIMARY KEY (id),
  KEY main_photo_id (main_photo_id),
  CONSTRAINT album_ibfk_1 FOREIGN KEY (id) REFERENCES album_identifier (id),
  CONSTRAINT album_ibfk_2 FOREIGN KEY (main_photo_id) REFERENCES photo_identifier (id) ON DELETE SET NULL
);

CREATE TABLE album_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  external_id text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE album_metadata (
  id bigint(20) unsigned NOT NULL,
  is_main_for_place tinyint(4) NOT NULL,
  is_main_for_country tinyint(4) NOT NULL,
  is_main_for_trip tinyint(4) NOT NULL,
  is_low_quality tinyint(4) NOT NULL,
  is_bad_weather tinyint(4) NOT NULL,
  PRIMARY KEY id (id),
  CONSTRAINT album_metadata_ibfk_1 FOREIGN KEY (id) REFERENCES album_identifier (id) ON DELETE CASCADE
);

CREATE TABLE category (
  category_id bigint(20) unsigned NOT NULL,
  place_id bigint(20) unsigned NOT NULL,
  KEY place_id (place_id),
  KEY category_id (category_id),
  CONSTRAINT category_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id) ON DELETE CASCADE,
  CONSTRAINT category_ibfk_2 FOREIGN KEY (category_id) REFERENCES category_identifier (id) ON DELETE CASCADE
);

CREATE TABLE category_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  category enum('CONTINENT','COUNTRY','ADMINISTRATIVE','OCEAN','SEA','BAY','VARIABLE','ISLAND','REGION') NOT NULL,
  main_highlight_id bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY main_highlight_id (main_highlight_id),
  CONSTRAINT category_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_category (highlight_id) ON DELETE SET NULL
);

CREATE TABLE configuration (
  type text NOT NULL,
  levels set('public','private','modifiable') NOT NULL,
  key text DEFAULT NULL,
  value text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
);

CREATE TABLE definition_problem (
  name text NOT NULL,
  helper_statements text DEFAULT NULL,
  query text NOT NULL
);

CREATE TABLE definition_statistics (
  name text NOT NULL,
  kind enum('FACT','STANDINGS') NOT NULL,
  query text NOT NULL,
  category enum('FLIGHT','PHOTO','PLACE','EXPENSE','CALENDAR','HOTEL','FITNESS') NOT NULL,
  types set('YEAR','TRIP','CATEGORY') NOT NULL,
  unit text NOT NULL
);

CREATE TABLE expense (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  trip_id bigint(20) unsigned NOT NULL,
  value double NOT NULL,
  currency text NOT NULL,
  exchange_rate double NOT NULL,
  type enum('FLIGHT','HOTEL','INTERCITY_TRANSPORT','ATTRACTION','AIRPORT_TRANSFER','PUBLIC_TRANSPORT','PARKING','OTHER','CITY_TAX','ORGANIZED_TOUR','CAR_RENTAL','FUEL','VISA') NOT NULL,
  description text DEFAULT NULL,
  timestamp text DEFAULT NULL,
  subscription_id bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY trip_id (trip_id),
  KEY subscription_id (subscription_id),
  CONSTRAINT expense_ibfk_2 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id) ON DELETE CASCADE,
  CONSTRAINT expense_ibfk_3 FOREIGN KEY (subscription_id) REFERENCES expense_subscription (id) ON DELETE SET NULL
);

CREATE TABLE expense_subscription (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  value double NOT NULL,
  currency text NOT NULL,
  exchange_rate double NOT NULL,
  description text NOT NULL,
  expiration text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE fitness (
  timestamp bigint(20) NOT NULL,
  last_update bigint(20) NOT NULL,
  steps bigint(20) NOT NULL,
  minutes bigint(20) NOT NULL,
  calories double NOT NULL,
  distance double NOT NULL,
  PRIMARY KEY (timestamp)
);

CREATE TABLE flight_event (
  id text NOT NULL,
  flight text NOT NULL,
  trip_id bigint(20) unsigned DEFAULT NULL,
  `from` text NOT NULL,
  to text NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  KEY trip_id (trip_id),
  CONSTRAINT flight_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE flight_log (
  flight text NOT NULL,
  registration text NOT NULL,
  aircraft text NOT NULL,
  from_airport_id bigint(20) unsigned NOT NULL,
  to_airport_id bigint(20) unsigned NOT NULL,
  scheduled_departure bigint(20) NOT NULL,
  actual_departure bigint(20) NOT NULL,
  scheduled_arrival bigint(20) NOT NULL,
  actual_arrival bigint(20) NOT NULL,
  PRIMARY KEY (actual_departure),
  KEY from_airport_id (from_airport_id),
  KEY to_airport_id (to_airport_id),
  CONSTRAINT flight_log_ibfk_1 FOREIGN KEY (from_airport_id) REFERENCES airport_identifier (id),
  CONSTRAINT flight_log_ibfk_2 FOREIGN KEY (to_airport_id) REFERENCES airport_identifier (id)
);

CREATE TABLE flight_watched_event (
  id text NOT NULL,
  flight text NOT NULL,
  trip_id bigint(20) unsigned DEFAULT NULL,
  `from` text NOT NULL,
  to text NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  KEY trip_id (trip_id),
  CONSTRAINT flight_watched_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE forecast_actual (
  place_id bigint(20) unsigned NOT NULL,
  timestamp bigint(20) NOT NULL,
  temperature double NOT NULL,
  clouds double NOT NULL,
  wind double NOT NULL,
  precipitation double NOT NULL,
  symbol text NOT NULL,
  last_update bigint(20) NOT NULL,
  expiration bigint(20) NOT NULL,
  KEY place_id (place_id),
  CONSTRAINT forecast_actual_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id) ON DELETE CASCADE
);

CREATE TABLE forecast_daylight (
  place_id bigint(20) unsigned NOT NULL,
  timestamp bigint(20) NOT NULL,
  sunrise bigint(20) NOT NULL,
  sunset bigint(20) NOT NULL,
  KEY place_id (place_id),
  CONSTRAINT forecast_daylight_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id) ON DELETE CASCADE
);

CREATE TABLE forecast_historical (
  place_id bigint(20) unsigned NOT NULL,
  timestamp bigint(20) NOT NULL,
  temperature double NOT NULL,
  wind double NOT NULL,
  precipitation double NOT NULL,
  KEY place_id (place_id),
  CONSTRAINT forecast_historical_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id) ON DELETE CASCADE
);

CREATE TABLE highlight_category (
  id bigint(20) unsigned NOT NULL,
  highlight_id bigint(20) unsigned NOT NULL,
  KEY category_id (id),
  KEY highlight_id (highlight_id),
  CONSTRAINT highlight_category_ibfk_1 FOREIGN KEY (id) REFERENCES category_identifier (id) ON DELETE CASCADE,
  CONSTRAINT highlight_category_ibfk_2 FOREIGN KEY (highlight_id) REFERENCES highlight_identifier (id) ON DELETE CASCADE
);

CREATE TABLE highlight_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  photo_id bigint(20) unsigned NOT NULL,
  thumbnail_url text DEFAULT NULL,
  full_url text DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY photo_id_2 (photo_id),
  CONSTRAINT highlight_identifier_ibfk_2 FOREIGN KEY (photo_id) REFERENCES photo_identifier (id) ON DELETE CASCADE
);

CREATE TABLE highlight_place (
  id bigint(20) unsigned NOT NULL,
  highlight_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (highlight_id),
  KEY place_id (id),
  CONSTRAINT highlight_place_ibfk_1 FOREIGN KEY (id) REFERENCES place_identifier (id) ON DELETE CASCADE,
  CONSTRAINT highlight_place_ibfk_2 FOREIGN KEY (highlight_id) REFERENCES highlight_identifier (id) ON DELETE CASCADE
);

CREATE TABLE highlight_trip (
  id bigint(20) unsigned NOT NULL,
  highlight_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (highlight_id),
  KEY trip_id (id),
  CONSTRAINT highlight_trip_ibfk_1 FOREIGN KEY (id) REFERENCES trip_identifier (id) ON DELETE CASCADE,
  CONSTRAINT highlight_trip_ibfk_2 FOREIGN KEY (highlight_id) REFERENCES highlight_identifier (id) ON DELETE CASCADE
);

CREATE TABLE highlight_year (
  id bigint(20) unsigned NOT NULL,
  highlight_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (highlight_id),
  CONSTRAINT highlight_year_ibfk_1 FOREIGN KEY (highlight_id) REFERENCES highlight_identifier (id) ON DELETE CASCADE
);

CREATE TABLE migration_script (
  name text NOT NULL,
  hash text NOT NULL,
  timestamp bigint(20) NOT NULL
);

CREATE TABLE note (
  id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  trip_id bigint(20) unsigned NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (id),
  KEY trip_id (trip_id),
  CONSTRAINT note_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id) ON DELETE CASCADE
);

CREATE TABLE photo (
  id bigint(20) unsigned NOT NULL,
  album_id bigint(20) unsigned NOT NULL,
  focal_length double DEFAULT NULL,
  aperture double DEFAULT NULL,
  shutter_speed double DEFAULT NULL,
  iso int(11) DEFAULT NULL,
  timestamp bigint(20) NOT NULL,
  PRIMARY KEY (id),
  KEY album_id (album_id),
  CONSTRAINT photo_ibfk_1 FOREIGN KEY (album_id) REFERENCES album_identifier (id) ON DELETE CASCADE,
  CONSTRAINT photo_ibfk_2 FOREIGN KEY (id) REFERENCES photo_identifier (id)
);

CREATE TABLE photo_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  external_id text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE photo_pending (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  album_id bigint(20) unsigned NOT NULL,
  file_name text NOT NULL,
  position int(11) NOT NULL,
  upload_token text NOT NULL,
  PRIMARY KEY (id),
  KEY album_id (album_id),
  CONSTRAINT photo_pending_ibfk_1 FOREIGN KEY (album_id) REFERENCES album_identifier (id) ON DELETE CASCADE
);

CREATE TABLE place_candidate (
  place_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (place_id),
  CONSTRAINT place_candidate_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id)
);

CREATE TABLE place_candidate_event (
  place_id bigint(20) unsigned NOT NULL,
  trip_id bigint(20) unsigned NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  KEY place_id (place_id),
  KEY trip_id (trip_id),
  CONSTRAINT place_candidate_event_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id),
  CONSTRAINT place_candidate_event_ibfk_2 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE place_event (
  id text NOT NULL,
  place_id bigint(20) unsigned NOT NULL,
  trip_id bigint(20) unsigned DEFAULT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  layover tinyint(4) NOT NULL,
  KEY place_id (place_id),
  KEY trip_id (trip_id),
  CONSTRAINT place_event_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id),
  CONSTRAINT place_event_ibfk_2 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE place_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  country text NOT NULL,
  timezone text NOT NULL,
  latitude double NOT NULL,
  longitude double NOT NULL,
  main_highlight_id bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY main_highlight_id (main_highlight_id),
  CONSTRAINT place_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_place (highlight_id) ON DELETE SET NULL
);

CREATE TABLE place_permanent (
  place_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (place_id),
  CONSTRAINT place_permanent_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id)
);

CREATE TABLE pruner (
  name text NOT NULL,
  query text NOT NULL
);

CREATE TABLE queue_job (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  processor text NOT NULL,
  args mediumtext DEFAULT NULL,
  priority int(11) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE region_area (
  category_id bigint(20) unsigned NOT NULL,
  area double NOT NULL,
  PRIMARY KEY (category_id),
  CONSTRAINT region_area_ibfk_1 FOREIGN KEY (category_id) REFERENCES category_identifier (id) ON DELETE CASCADE
);

CREATE TABLE region_composite (
  category_id bigint(20) unsigned NOT NULL,
  subject_category_id bigint(20) unsigned NOT NULL,
  type enum('INCLUDE','EXCLUDE') NOT NULL,
  KEY category_id (category_id),
  KEY subject_category_id (subject_category_id),
  CONSTRAINT region_composite_ibfk_1 FOREIGN KEY (category_id) REFERENCES category_identifier (id),
  CONSTRAINT region_composite_ibfk_2 FOREIGN KEY (subject_category_id) REFERENCES category_identifier (id)
);

CREATE TABLE region_geographical (
  category_id bigint(20) unsigned NOT NULL,
  country text DEFAULT NULL,
  json mediumtext NOT NULL,
  radius int(11) NOT NULL,
  KEY category_id (category_id),
  CONSTRAINT region_geographical_ibfk_1 FOREIGN KEY (category_id) REFERENCES category_identifier (id)
);

CREATE TABLE scheduler (
  name text NOT NULL,
  processor text NOT NULL,
  args_query text DEFAULT NULL,
  interval_query text NOT NULL,
  last_execution bigint(20) NOT NULL
);

CREATE TABLE stay_event (
  id text NOT NULL,
  name text NOT NULL,
  trip_id bigint(20) unsigned DEFAULT NULL,
  address text NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  KEY trip_id (trip_id),
  CONSTRAINT stay_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE tracking (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type enum('VACATION','SELFCARE','TENURE','OVERTIME') NOT NULL,
  hours double NOT NULL,
  description text NOT NULL,
  timestamp bigint(20) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE trip_event (
  id text DEFAULT NULL,
  trip_id bigint(20) unsigned NOT NULL,
  start bigint(20) NOT NULL,
  end bigint(20) NOT NULL,
  PRIMARY KEY (trip_id),
  CONSTRAINT trip_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id)
);

CREATE TABLE trip_identifier (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  year int(11) DEFAULT NULL,
  main_highlight_id bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY main_highlight_id (main_highlight_id),
  CONSTRAINT trip_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_trip (highlight_id) ON DELETE SET NULL
);

CREATE FUNCTION GET_CONFIGURATION(t TEXT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT value 
  FROM configuration
  WHERE type = t AND `key` IS NULL);

CREATE FUNCTION GET_CONFIGURATION_FOR_KEY(t TEXT, k TEXT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT value
  FROM configuration
  WHERE type = t AND `key` = k);

CREATE FUNCTION GET_AIRLINE_NAME_FROM_AIRLINE_CODE(code TEXT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN GET_CONFIGURATION_FOR_KEY('AIRLINES', code);

CREATE FUNCTION GET_AIRPORT_NAME_FROM_AIRPORT_ID(id BIGINT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT `from` 
  FROM flight_event f 
    INNER JOIN flight_log lf 
      ON f.flight = lf.flight AND f.start = lf.scheduled_departure AND from_airport_id = id
  UNION SELECT to
    FROM flight_event f 
      INNER JOIN flight_log lf
        ON f.flight = lf.flight AND f.start = lf.scheduled_departure AND to_airport_id = id);

CREATE FUNCTION GET_CATEGORY_NAME_FROM_CATEGORY_ID(category_id TEXT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT name 
  FROM category_identifier
  WHERE id = category_id);

CREATE FUNCTION IS_DAY_TRIP(trip_id BIGINT UNSIGNED) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
RETURN GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips') = (SELECT name FROM trip_identifier WHERE id = trip_id);

CREATE FUNCTION GET_PREVIOUS_TRIP_END(trip_id TEXT) RETURNS bigint(20)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT COALESCE(MAX(ts1.end), UNIX_TIMESTAMP()) 
  FROM trip_event ts1
  WHERE ts1.end < (SELECT ts2.end FROM trip_event ts2 WHERE ts2.trip_id = trip_id) AND NOT IS_DAY_TRIP(ts1.trip_id));

CREATE FUNCTION GET_DAYS_BEFORE_TRIP_COUNT(trip_id BIGINT, only_working_days TINYINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN GET_DAYS_COUNT(GREATEST(UNIX_TIMESTAMP(), GET_PREVIOUS_TRIP_END(trip_id) + IF(HOUR(FROM_UNIXTIME(GET_PREVIOUS_TRIP_END(trip_id))) = 0 AND MINUTE(FROM_UNIXTIME(GET_PREVIOUS_TRIP_END(trip_id))) = 0, 0, 86400)), (SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id) - IF(HOUR(FROM_UNIXTIME((SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id))) = 0 AND MINUTE(FROM_UNIXTIME((SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id))) = 0, 0, 86400), only_working_days);

CREATE FUNCTION IS_WORKING_DAY(timestamp BIGINT) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
RETURN ((DAYOFWEEK(FROM_UNIXTIME(timestamp)) <> 1) AND (DAYOFWEEK(FROM_UNIXTIME(timestamp)) <> 7) AND (FIND_IN_SET(DATE_FORMAT(FROM_UNIXTIME(timestamp), "%e.%c.%Y"), GET_CONFIGURATION("PUBLIC_HOLIDAYS")) = 0));

CREATE FUNCTION GET_DAYS_COUNT(start BIGINT, end BIGINT, only_working_days TINYINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT COUNT(*)
  FROM days_sequence 
  WHERE (seq >= UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(start)))) 
    AND (seq <= IF(HOUR(FROM_UNIXTIME(end)) = 0 AND MINUTE(FROM_UNIXTIME(end)) = 0, 0, 86400) + UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(end))))
    AND (NOT(only_working_days) OR IS_WORKING_DAY(seq)));

CREATE FUNCTION GET_DISTANCE(lat1 DOUBLE, lng1 DOUBLE, lat2 DOUBLE, lng2 DOUBLE) RETURNS double
    NO SQL
    DETERMINISTIC
BEGIN
    DECLARE R int(11);
    DECLARE dLat decimal(30,15);
    DECLARE dLng decimal(30,15);
    DECLARE a1 decimal(30,15);
    DECLARE a2 decimal(30,15);
    DECLARE a decimal(30,15);
    DECLARE c decimal(30,15);
    DECLARE d decimal(30,15);

    SET R = 6378;
    SET dLat = RADIANS( lat2 ) - RADIANS( lat1 );
    SET dLng = RADIANS( lng2 ) - RADIANS( lng1 );
    SET a1 = SIN( dLat / 2 ) * SIN( dLat / 2 );
    SET a2 = SIN( dLng / 2 ) * SIN( dLng / 2 ) * COS( RADIANS( lat1 )) * COS( RADIANS( lat2 ) );
    SET a = a1 + a2;
    SET c = 2 * ATAN2( SQRT( a ), SQRT( 1 - a ) );
    SET d = R * c;
    RETURN d;
END;

CREATE FUNCTION GET_DISTANCE_FROM_HOME(lat DOUBLE, lng DOUBLE) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN GET_DISTANCE(lat, lng, GET_CONFIGURATION_FOR_KEY('HOME_LOCATION', 'latitude'), GET_CONFIGURATION_FOR_KEY('HOME_LOCATION', 'longitude'));

CREATE FUNCTION IS_PUBLIC_WEEKDAY_HOLIDAY(timestamp BIGINT) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
RETURN ((DAYOFWEEK(FROM_UNIXTIME(timestamp)) <> 1) AND (DAYOFWEEK(FROM_UNIXTIME(timestamp)) <> 7) AND (FIND_IN_SET(DATE_FORMAT(FROM_UNIXTIME(timestamp), "%e.%c.%Y"), GET_CONFIGURATION("PUBLIC_HOLIDAYS")) <> 0));

CREATE FUNCTION GET_PUBLIC_WEEKDAY_HOLIDAYS_COUNT(start BIGINT, end BIGINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT COUNT(*)
  FROM days_sequence
  WHERE (seq >= UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(start))))
    AND (seq <= IF(HOUR(FROM_UNIXTIME(end)) = 0 AND MINUTE(FROM_UNIXTIME(end)) = 0, 0, 86400) + UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(end))))
    AND IS_PUBLIC_WEEKDAY_HOLIDAY(seq));

CREATE FUNCTION GET_PUBLIC_WEEKDAY_HOLIDAYS_BEFORE_TRIP_COUNT(trip_id BIGINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN GET_PUBLIC_WEEKDAY_HOLIDAYS_COUNT(GREATEST(UNIX_TIMESTAMP(), GET_PREVIOUS_TRIP_END(trip_id) + IF(HOUR(FROM_UNIXTIME(GET_PREVIOUS_TRIP_END(trip_id))) = 0 AND MINUTE(FROM_UNIXTIME(GET_PREVIOUS_TRIP_END(trip_id))) = 0, 0, 86400)), (SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id) - IF(HOUR(FROM_UNIXTIME((SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id))) = 0 AND MINUTE(FROM_UNIXTIME((SELECT start FROM trip_event ts WHERE ts.trip_id = trip_id))) = 0, 0, 86400));

CREATE FUNCTION GET_UPCOMING_TRIP_ID() RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT trip_id
  FROM trip_event
  WHERE start > UNIX_TIMESTAMP()
  ORDER BY start
  LIMIT 1);

CREATE FUNCTION IS_EARLY_WEEKDAY_ARRIVAL(trip_id BIGINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN COALESCE((
  SELECT
    -- An early weekday arrival means an arrival before XX:59:59.
    HOUR(FROM_UNIXTIME(end)) <= GET_CONFIGURATION("EARLY_WEEKDAY_ARRIVAL_MAX_HOUR")
    -- Exclude Sundays.
    AND DAYOFWEEK(FROM_UNIXTIME(end)) <> 1
    -- Exclude Saturdays.
    AND DAYOFWEEK(FROM_UNIXTIME(end)) <> 7
    -- Exclude public holidays.
    AND NOT FIND_IN_SET(FROM_UNIXTIME(end, '%e.%c.%Y'), GET_CONFIGURATION('PUBLIC_HOLIDAYS'))
    -- Consider only arrivals to the home airport.
    AND to = GET_CONFIGURATION('HOME_AIRPORT')
  FROM flight_event f
  WHERE f.trip_id = trip_id
  ORDER BY end DESC
  LIMIT 1), 0);
  
CREATE FUNCTION IS_LATE_WEEKDAY_DEPARTURE(trip_id BIGINT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN COALESCE((
  SELECT
    -- A late weekday departure means a departure after XX:59:59.
    HOUR(FROM_UNIXTIME(start)) >= GET_CONFIGURATION("LATE_WEEKDAY_DEPARTURE_MIN_HOUR")
    -- Exclude Sundays.
    AND DAYOFWEEK(FROM_UNIXTIME(start)) <> 1
    -- Exclude Saturdays.
    AND DAYOFWEEK(FROM_UNIXTIME(start)) <> 7
    -- Exclude public holidays.
    AND NOT FIND_IN_SET(FROM_UNIXTIME(start, '%e.%c.%Y'), GET_CONFIGURATION('PUBLIC_HOLIDAYS'))
    -- Consider only departures `from` the home airport.
    AND `from` = GET_CONFIGURATION('HOME_AIRPORT')
  FROM flight_event f
  WHERE f.trip_id = trip_id
  ORDER BY start ASC
  LIMIT 1), 0);

CREATE FUNCTION GET_EXPECTED_TIME_OFF_TO_USE(working_days INT, trip_id BIGINT) RETURNS double
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
      - IF(trip_id = GET_UPCOMING_TRIP_ID(), (SELECT SUM(hours) FROM tracking WHERE type = 'OVERTIME') / (8 * GET_CONFIGURATION('CURRENT_FTE')), 0)));

CREATE FUNCTION GET_EXPENSE_DESCRIPTION_WITH_SUBSCRIPTION(description TEXT, subscription_id INT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN CONCAT(description, IF(subscription_id IS NULL, '', (SELECT CONCAT(' (', s.description, ' do ', date_format(from_unixtime(s.expiration), '%e.%c.%Y'), ')') FROM expense_subscription s WHERE s.id = subscription_id)));

CREATE FUNCTION GET_FULLY_QUALIFIED_TRIP_NAME(name TEXT, year INT) RETURNS text
    NO SQL
    DETERMINISTIC
RETURN CONCAT(name, ' ', year);

CREATE FUNCTION GET_FULLY_QUALIFIED_TRIP_NAME_FROM_TRIP_ID(trip_id BIGINT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year)
  FROM trip_identifier
  WHERE id = trip_id);

CREATE FUNCTION GET_INDOOR_IMAGES_COUNT(album_id TEXT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT COUNT(*)
  FROM photo p
  WHERE p.album_id = album_id AND iso >= 640);

CREATE FUNCTION GET_SUBSCRIPTION_SHARE(subscription_id INT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT COUNT(*)
  FROM expense e
  WHERE e.subscription_id = subscription_id);

CREATE FUNCTION GET_MAIN_CURRENCY_VALUE_WITH_SUBSCRIPTION(value DOUBLE, exchange_rate DOUBLE, subscription_id INT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN value * exchange_rate + IF(subscription_id IS NULL, 0, (SELECT s.value * s.exchange_rate FROM expense_subscription s WHERE s.id = subscription_id) / GET_SUBSCRIPTION_SHARE(subscription_id));

CREATE FUNCTION GET_TOTAL_AVAILABLE_TIME_OFF_FOR_YEAR(year INT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN IF(YEAR(CURDATE()) <= year, (SELECT SUM(value) FROM configuration WHERE type = 'TIME_OFF_HOURS'), NULL);

CREATE FUNCTION GET_USED_TIME_OFF_FOR_YEAR(year INT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN IF(YEAR(CURDATE()) <> year, 0, (SELECT (-1) * SUM(hours) FROM tracking WHERE type <> 'OVERTIME' AND hours < 0 AND YEAR(FROM_UNIXTIME(timestamp)) = year));

CREATE FUNCTION GET_STILL_AVAILABLE_TIME_OFF_FOR_YEAR(year INT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN GET_TOTAL_AVAILABLE_TIME_OFF_FOR_YEAR(year) - GET_USED_TIME_OFF_FOR_YEAR(year);

CREATE FUNCTION GET_TIME_OFF_NEEDED_UNTIL_END_OF_YEAR(year INT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT 8 * GET_CONFIGURATION('CURRENT_FTE') * SUM(IF(IS_DAY_TRIP(t.trip_id) OR ti.year < YEAR(CURDATE()), NULL, GET_DAYS_COUNT(t.start, t.end, 1)))
  FROM trip_event t 
    INNER JOIN trip_identifier ti 
      ON t.trip_id = ti.id
  WHERE ti.year = year 
    AND t.start > UNIX_TIMESTAMP());

CREATE FUNCTION GET_MAX_TIME_OFF_TO_USE(needed_days INT, year INT) RETURNS double
    READS SQL DATA
    DETERMINISTIC
RETURN (8 * GET_CONFIGURATION('CURRENT_FTE') * needed_days) / GET_TIME_OFF_NEEDED_UNTIL_END_OF_YEAR(year) * GET_STILL_AVAILABLE_TIME_OFF_FOR_YEAR(year);

CREATE FUNCTION GET_TRIP_ID_FOR_INTERVAL(eventStart BIGINT, eventEnd BIGINT) RETURNS text
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT trip_id
  FROM trip_event
  WHERE (eventStart + eventEnd) / 2 >= start
    AND (eventStart + eventEnd) / 2 <= end
  ORDER BY trip_id NOT IN (SELECT id FROM trip_identifier WHERE name = GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips')) DESC
  LIMIT 1);

CREATE FUNCTION GET_VARIABLE_TIME_CATEGORY_OFFSET(category_id BIGINT) RETURNS bigint(20) unsigned
    READS SQL DATA
    DETERMINISTIC
RETURN (
  SELECT value FROM
  configuration c
    INNER JOIN category_identifier ci
      ON c.key = ci.name
  WHERE c.type = 'VARIABLE_TIME_CATEGORIES'
    AND ci.id = category_id);

CREATE FUNCTION IS_IN_CATEGORY(place_id BIGINT UNSIGNED, category_id BIGINT) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
RETURN (category_id = -1) OR EXISTS(SELECT * FROM category_summary cs WHERE FIND_IN_SET(category_id, cs.category_ids) AND cs.place_id = place_id);

CREATE VIEW days_sequence AS
  SELECT (
    SELECT FLOOR(MIN(start) / 86400) * 86400 
    FROM trip_event) + 86400 * seq AS seq 
  FROM seq_0_to_30000;

CREATE VIEW fitness_sequence AS
  SELECT (
    SELECT MIN(start)
    FROM trip_event) + GET_CONFIGURATION('FITNESS_RECORD_DURATION') * seq AS seq
  FROM seq_0_to_200000;

CREATE VIEW _category_summary AS
  SELECT c.place_id,
    COALESCE(GROUP_CONCAT(c.category_id ORDER BY ra.area DESC, (
      SELECT c.value 
      FROM category_identifier ci 
        INNER JOIN configuration c 
          ON ci.name = c.key 
      WHERE c.type = 'VARIABLE_TIME_CATEGORIES'
        AND ci.category = 'VARIABLE'
        AND ci.id = c.category_id) DESC SEPARATOR ','), '') AS category_ids 
  FROM ((
    SELECT category_id,
      place_id
    FROM category) 
    UNION (
      SELECT DISTINCT ci.id AS category_id,
        pe.place_id
      FROM category_identifier ci
        INNER JOIN configuration c
          ON ci.name = c.key
        JOIN place_event pe
        WHERE c.type = 'VARIABLE_TIME_CATEGORIES'
          AND ci.category = 'VARIABLE'
          AND pe.start < UNIX_TIMESTAMP()
          AND pe.start > UNIX_TIMESTAMP() - c.value)
    UNION (
      SELECT ci.id AS category_id,
        pp.place_id
      FROM category_identifier ci
        INNER JOIN configuration c
          ON ci.name = c.key
        JOIN place_permanent pp
      WHERE c.type = 'VARIABLE_TIME_CATEGORIES'
        AND ci.category = 'VARIABLE'
    )) c
    INNER JOIN region_area ra
      ON c.category_id = ra.category_id
  GROUP BY c.place_id;

CREATE TABLE category_summary AS
  SELECT * 
    FROM _category_summary;

CREATE VIEW _expense_summary AS
  SELECT id,
    trip_id,
    type,
    GET_EXPENSE_DESCRIPTION_WITH_SUBSCRIPTION(description, subscription_id) AS description,
    value,
    currency,
    GET_MAIN_CURRENCY_VALUE_WITH_SUBSCRIPTION(value, exchange_rate, subscription_id) AS main_currency_value
  FROM expense
  ORDER BY timestamp,
    id;
    
CREATE TABLE expense_summary AS
  SELECT *
    FROM _expense_summary;

CREATE VIEW _place_event_summary AS
  SELECT pe.id,
    pi.id AS place_id,
    pi.name,
    pi.country,
    pe.start,
    pe.end,
    pi.latitude,
    pi.longitude,
    pi.timezone,
    pi.main_highlight_id,
    pe.trip_id,
    pe.layover,
    COALESCE(cs.category_ids, '') AS category_ids,
    a.id AS album_id,
    a.main_photo_id AS album_main_photo_id,
    a.main_image_url AS album_main_image_url,    
    a.images_count AS album_images_count,
    a.indoor_images_count AS album_indoor_images_count,
    a.permalink AS album_permalink,
    am.is_main_for_place AS is_main_album_for_place,
    am.is_main_for_country AS is_main_album_for_country,
    am.is_main_for_trip AS is_main_album_for_trip,
    am.is_low_quality AS is_low_quality_album,
    am.is_bad_weather AS is_bad_weather_album,
    IFNULL(fa.temperature, fh.temperature) AS temperature,
    fa.clouds,
    IFNULL(fa.wind, fh.wind) AS wind,
    IFNULL(fa.precipitation, fh.precipitation) AS precipitation,
    fa.symbol,
    fd.sunrise,
    fd.sunset,
    IFNULL(fa.last_update, UNIX_TIMESTAMP()) AS last_update
  FROM place_event pe
    INNER JOIN place_identifier pi
      ON pe.place_id = pi.id
    LEFT JOIN album a
      ON CONCAT(pi.name, ' ', DATE_FORMAT(FROM_UNIXTIME(pe.start), '%e.%c.%Y')) = a.name
    LEFT JOIN album_metadata am
      ON a.id = am.id
    LEFT JOIN forecast_actual fa
      ON pe.place_id = fa.place_id
        AND pe.start = fa.timestamp
    LEFT JOIN forecast_historical fh
      ON pe.place_id = fh.place_id
        AND pe.start = fh.timestamp
    LEFT JOIN forecast_daylight fd
      ON pe.place_id = fd.place_id
        AND pe.start = fd.timestamp
    LEFT JOIN _category_summary cs
      ON pi.id = cs.place_id
  ORDER BY pe.start;

CREATE VIEW _place_permanent_summary AS
  SELECT NULL AS id,
    pi.id AS place_id,
    pi.name,
    pi.country,
    UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(SUBSTR(a.name, CHAR_LENGTH(pi.name) + 2), ' 12:00AM'), '%e.%c.%Y %h:%i%p')) AS start,
    UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(SUBSTR(a.name, CHAR_LENGTH(pi.name) + 2), ' 12:00AM'), '%e.%c.%Y %h:%i%p')) + 86400 AS end,
    pi.latitude,
    pi.longitude,
    pi.timezone,
    pi.main_highlight_id,
    NULL AS trip_id,
    0 AS layover,
    COALESCE(cs.category_ids, '') AS category_ids,
    a.id AS album_id,
    a.main_photo_id AS album_main_photo_id,
    a.main_image_url AS album_main_image_url,    
    a.images_count AS album_images_count,
    a.indoor_images_count AS album_indoor_images_count,
    a.permalink AS album_permalink,
    am.is_main_for_place AS is_main_album_for_place,
    am.is_main_for_country AS is_main_album_for_country,
    am.is_main_for_trip AS is_main_album_for_trip,
    am.is_low_quality AS is_low_quality_album,
    am.is_bad_weather AS is_bad_weather_album,
    NULL AS temperature,
    NULL AS clouds,
    NULL AS wind,
    NULL AS precipitation,
    NULL AS symbol,
    NULL AS sunrise,
    NULL AS sunset,
    UNIX_TIMESTAMP() AS last_update
  FROM album a
    LEFT JOIN album_metadata am
      ON a.id = am.id
    LEFT JOIN place_identifier pi
      ON LOCATE(CONCAT(pi.name,' '), CONCAT(a.name,' ')) = 1
    INNER JOIN place_permanent pp
      ON pi.id = pp.place_id
    LEFT JOIN _category_summary cs
      ON pi.id = cs.place_id
    WHERE pp.place_id IS NOT NULL
    ORDER BY UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(SUBSTR(a.name, CHAR_LENGTH(pi.name) + 2), ' 12:00AM'), '%e.%c.%Y %h:%i%p'));

CREATE VIEW _place_summary AS
  SELECT *
    FROM _place_event_summary
  UNION SELECT *
    FROM _place_permanent_summary
  ORDER BY START;

CREATE TABLE place_summary AS
  SELECT *
    FROM _place_summary;

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
      IFNULL(GET_EXPECTED_TIME_OFF_TO_USE(IF(IS_DAY_TRIP(te.trip_id) OR ti.year < YEAR(CURDATE()),
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

CREATE TABLE trip_summary AS
  SELECT *
    FROM _trip_summary;

INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('ALBUM_THUMBNAIL_IMAGE_SIZE', 'public', 'height', '233');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('ALBUM_THUMBNAIL_IMAGE_SIZE', 'public', 'width', '350');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('AUTO_PURGE_RETENTION_DAYS', 'public,modifiable', 'exchangeRates', '1');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('AUTO_PURGE_RETENTION_DAYS', 'public,modifiable', 'location', '365');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('AUTO_PURGE_RETENTION_DAYS', 'public,modifiable', 'log', '1');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('AUTO_PURGE_RETENTION_DAYS', 'public,modifiable', 'points', '365');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CACHE_PATH', 'private', 'albumThumbnail', 'cache/album');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CACHE_PATH', 'private', 'highlightFull', 'cache/highlight/full');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CACHE_PATH', 'private', 'highlightThumbnail', 'cache/highlight/thumbnail');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDARS', 'private', 'flights', 'https://calendar.google.com/calendar/ical/94ddfff3f21d7411e1b594fe9ad4eaaf580a6cab29cdfd8d34b8c8f8e561d3f6%40group.calendar.google.com/private-9b4538b819b05ff6dec58d794e0a8f43/basic.ics');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDARS', 'private', 'places', 'https://calendar.google.com/calendar/ical/de0d327f2160e260d3b8eba6226fb480402b30afc582e5d902c5b7c932d0de64%40group.calendar.google.com/private-09aa7c590a31a04eae12e61833664c4b/basic.ics');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDARS', 'private', 'stays', 'https://calendar.google.com/calendar/ical/0589abc15e446056f9ea7b94622a3737675ca0f031dbb9470ac8958ec0e04dbb%40group.calendar.google.com/private-b1b8868f160d631e7842b1e6922c06b2/basic.ics');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDARS', 'private', 'trips', 'https://calendar.google.com/calendar/ical/4a6a3dff4757b25d10736742eebed53105f239744484c2aed0aab513098978d9%40group.calendar.google.com/private-1852b1ccb73f45629396a474aad7e53a/basic.ics');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDARS', 'private', 'watchedFlights', 'https://calendar.google.com/calendar/ical/1158c71b500bad7e3697d52bbb9ea8d646da556136f2e36cba7f163e9e44a37a%40group.calendar.google.com/private-e685cdd2cf32bafe05340eb2a71805c2/basic.ics');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CALENDAR_ENTRY_MINIMUM_WIDTH', 'public', NULL, '85');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CHAT_REQUESTS', 'private', 'mapPoints', 'Vypiš seznam nejzajímavějších míst pro město %s (%s) ve formátu JSON. Top-level prvek ať je JSON pole. Vypsaný text nesmí obsahovat nic jiného než JSON, toto je důležité! Každé místo by mělo obsahovat název (name) v češtině. Dále by mělo obsahovat GPS souřadnice (latitude, longitude), popisek v češtině o rozsahu 3-5 vět (description) a barvu (color) v HEX formátu, kterou použiji v mapě k odlišení místa od ostatních. Každá barva tedy musí být unikátní a musí být od sebe snadno rozeznatelné. Seznam by měl zahrnovat úplně všechny turisticky významné body v daném místě. Pro velká města (turistické hotspoty) takových bodů může být klidně až 25, pro menší 5-10. Důležité je, aby seznam obsahoval jen to hlavní.');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CHAT_REQUESTS', 'private', 'suggestedExcerpt', 'Napiš mi článek o místě %s (%s) pro můj cestovní blog. Článek budu rovnou publikovat a nebudu jej upravovat. Spíše než popis jednotlivých atrakcí vytvoř obecný popis daného místa. V závislosti na turistické významnosti daného místa by článek měl obsahovat 200-500 slov. Celý článek ať je v jednom řádku.');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CONTACT_EMAIL', 'public', NULL, 'lukas.riedel24@gmail.com');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COOKIES', 'public,modifiable', 'DisplayDetailedExpensify', 'ba5e4gHECmEgzHq3ac358Y');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COOKIES', 'public,modifiable', 'DisplayFeaturedTrip', 'hp4e4LHECmCgz3q3TDQV89');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COOKIES', 'public,modifiable', 'DisplayFutureTrips', 'LRAFrJMmb2YRsMBN98CE54');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Afghánistán', '{"color":"#FFC0CB","unicode":"1f1e6-1f1eb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.af%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Alandské ostrovy', '{"color":"#FFC0CB","unicode":"1f1e6-1f1fd","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Albánie', '{"color":"#DA291C","unicode":"1f1e6-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.al%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Alžírsko', '{"color":"#006633","unicode":"1f1e9-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.dz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Americká Samoa', '{"color":"#FFC0CB","unicode":"1f1e6-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.as%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Americké Panenské ostrovy', '{"color":"#FFC0CB","unicode":"1f1fb-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.vi%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Americké odlehlé ostrovy', '{"color":"#FFC0CB","unicode":"1f1fa-1f1f2","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Andorra', '{"color":"#D50032","unicode":"1f1e6-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ad%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Angola', '{"color":"#FFC0CB","unicode":"1f1e6-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ao%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Anguilla', '{"color":"#FFC0CB","unicode":"1f1e6-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ai%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Antarktida', '{"color":"#FFC0CB","unicode":"1f1e6-1f1f6","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Antigua a Barbuda', '{"color":"#FFC0CB","unicode":"1f1e6-1f1ec","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Argentina', '{"color":"#FFC0CB","unicode":"1f1e6-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ar%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Arménie', '{"color":"#FF9E1B","unicode":"1f1e6-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.am%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Aruba', '{"color":"#FFC0CB","unicode":"1f1e6-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.aw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Austrálie', '{"color":"#FFC0CB","unicode":"1f1e6-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.australian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bahamy', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bs%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bahrajn', '{"color":"#CE1126","unicode":"1f1e7-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bh%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bangladéš', '{"color":"#FFC0CB","unicode":"1f1e7-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bd%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Barbados', '{"color":"#FFC0CB","unicode":"1f1e7-1f1e7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bb%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Belgie', '{"color":"#C8102F","unicode":"1f1e7-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.be%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Belize', '{"color":"#FFC0CB","unicode":"1f1e7-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Benin', '{"color":"#FFC0CB","unicode":"1f1e7-1f1ef","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bj%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bermudy', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bhútán', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bt%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bolívie', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bo%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bosna a Hercegovina', '{"color":"#002F6C","unicode":"1f1e7-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ba%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Botswana', '{"color":"#FFC0CB","unicode":"1f1e7-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bouvetův ostrov', '{"color":"#FFC0CB","unicode":"1f1e7-1f1fb","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Brazílie', '{"color":"#009739","unicode":"1f1e7-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.brazilian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Britské Panenské ostrovy', '{"color":"#FFC0CB","unicode":"1f1fb-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.vg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Britské indickooceánské území', '{"color":"#FFC0CB","unicode":"1f1ee-1f1f4","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Brunej', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f3","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bulharsko', '{"color":"#00966E","unicode":"1f1e7-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bulgarian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Burkina Faso', '{"color":"#FFC0CB","unicode":"1f1e7-1f1eb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bf%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Burundi', '{"color":"#FFC0CB","unicode":"1f1e7-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.bi%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Bělorusko', '{"color":"#FFC0CB","unicode":"1f1e7-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.by%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Chile', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cl%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Chorvatsko', '{"color":"#0093DD","unicode":"1f1ed-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.croatian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Cookovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ck%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Curaçao', '{"color":"#FFC0CB","unicode":"1f1e8-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Diego Garcia', '{"color":"#FFC0CB","unicode":"1f1e9-1f1ec","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Dominika', '{"color":"#FFC0CB","unicode":"1f1e9-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.dm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Dominikánská republika', '{"color":"#CE1126","unicode":"1f1e9-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.do%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Dánsko', '{"color":"#C8102E","unicode":"1f1e9-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.danish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Džibutsko', '{"color":"#FFC0CB","unicode":"1f1e9-1f1ef","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.dj%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Egypt', '{"color":"#000000","unicode":"1f1ea-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.eg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ekvádor', '{"color":"#FFC0CB","unicode":"1f1ea-1f1e8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ec%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'El Salvador', '{"color":"#FFC0CB","unicode":"1f1f8-1f1fb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sv%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Eritrea', '{"color":"#FFC0CB","unicode":"1f1ea-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.er%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Estonsko', '{"color":"#0072CE","unicode":"1f1ea-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ee%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Etiopie', '{"color":"#FFC0CB","unicode":"1f1ea-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.et%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Faerské ostrovy', '{"color":"#ED2939","unicode":"1f1eb-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.fo%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Falklandy', '{"color":"#FFC0CB","unicode":"1f1eb-1f1f0","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Fidži', '{"color":"#FFC0CB","unicode":"1f1eb-1f1ef","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.fj%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Filipíny', '{"color":"#FFC0CB","unicode":"1f1f5-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.philippines%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Finsko', '{"color":"#002F6C","unicode":"1f1eb-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.finnish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Francie', '{"color":"#0055A4","unicode":"1f1eb-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.french%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Francouzská Guyana', '{"color":"#FFC0CB","unicode":"1f1ec-1f1eb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gf%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Francouzská Polynésie', '{"color":"#FFC0CB","unicode":"1f1f5-1f1eb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pf%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Francouzská jižní území', '{"color":"#FFC0CB","unicode":"1f1f9-1f1eb","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Gabon', '{"color":"#FFC0CB","unicode":"1f1ec-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ga%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Gambie', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ghana', '{"color":"#FFC0CB","unicode":"1f1ec-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gh%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Gibraltar', '{"color":"#DA000C","unicode":"1f1ec-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gi%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Grenada', '{"color":"#FFC0CB","unicode":"1f1ec-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gd%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Gruzie', '{"color":"#DA291C","unicode":"1f1ec-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ge%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Grónsko', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gl%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guadeloupe', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f5","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guam', '{"color":"#FFC0CB","unicode":"1f1ec-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gu%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guatemala', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gt%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guernsey', '{"color":"#FFC0CB","unicode":"1f1ec-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guinea', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gn%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guinea-Bissau', '{"color":"#FFC0CB","unicode":"1f1ec-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Guyana', '{"color":"#FFC0CB","unicode":"1f1ec-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gy%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Haiti', '{"color":"#FFC0CB","unicode":"1f1ed-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ht%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Heardovy a McDonaldovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1ed-1f1f2","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Honduras', '{"color":"#FFC0CB","unicode":"1f1ed-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.hn%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Indie', '{"color":"#FF671F","unicode":"1f1ee-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.indian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Indonésie', '{"color":"#FFC0CB","unicode":"1f1ee-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Irsko', '{"color":"#009A44","unicode":"1f1ee-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.irish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Irák', '{"color":"#FFC0CB","unicode":"1f1ee-1f1f6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.iq%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Island', '{"color":"#003087","unicode":"1f1ee-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.is%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Itálie', '{"color":"#008C45","unicode":"1f1ee-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.italian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Izrael', '{"color":"#005EB8","unicode":"1f1ee-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.jewish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jamaica', '{"color":"#FFC0CB","unicode":"1f1ef-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.jm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Japonsko', '{"color":"#BC002D","unicode":"1f1ef-1f1f5","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.japanese%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jemen', '{"color":"#FFC0CB","unicode":"1f1fe-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ye%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jersey', '{"color":"#FFC0CB","unicode":"1f1ef-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.je%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jihoafrická republika', '{"color":"#FFC0CB","unicode":"1f1ff-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sa%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jižní Georgie a Jižní Sandwichovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jižní Korea', '{"color":"#FFC0CB","unicode":"1f1f0-1f1f7","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jižní Súdán', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ss%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Jordánsko', '{"color":"#CE1126","unicode":"1f1ef-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.jo%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kajmanské ostrovy', '{"color":"#FFC0CB","unicode":"1f1f0-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ky%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kambodža', '{"color":"#E00025","unicode":"1f1f0-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.kh%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kamerun', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kanada', '{"color":"#D80621","unicode":"1f1e8-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.canadian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kapverdy', '{"color":"#003DA5","unicode":"1f1e8-1f1fb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cv%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Karibské Nizozemsko', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f6","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Katar', '{"color":"#6C1D45","unicode":"1f1f6-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.qa%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kazachstán', '{"color":"#FFC0CB","unicode":"1f1f0-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.kz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Keňa', '{"color":"#FFC0CB","unicode":"1f1f0-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ke%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kiribati', '{"color":"#FFC0CB","unicode":"1f1f0-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ki%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kokosové (Keelingovy) ostrovy', '{"color":"#FFC0CB","unicode":"1f1e8-1f1e8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kolumbie', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.co%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Komory', '{"color":"#FFC0CB","unicode":"1f1f0-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.km%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Konžská demokratická republika', '{"color":"#FFC0CB","unicode":"1f1e8-1f1e9","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Konžská republika', '{"color":"#FFC0CB","unicode":"1f1e8-1f1ec","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kosovo', '{"color":"#244AA5","unicode":"1f1fd-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.xk%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kostarika', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kuba', '{"color":"#FFC0CB","unicode":"1f1e8-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cu%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kuvajt', '{"color":"#007A3D","unicode":"1f1f0-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.kw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kypr', '{"color":"#D57800","unicode":"1f1e8-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cy%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Kyrgyzstán', '{"color":"#FFC0CB","unicode":"1f1f0-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.kg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Laos', '{"color":"#FFC0CB","unicode":"1f1f1-1f1e6","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Lesotho', '{"color":"#FFC0CB","unicode":"1f1f1-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ls%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Libanon', '{"color":"#FFC0CB","unicode":"1f1f1-1f1e7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.lb%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Libye', '{"color":"#FFC0CB","unicode":"1f1f1-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ly%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Libérie', '{"color":"#FFC0CB","unicode":"1f1f1-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.lr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Lichtenštejnsko', '{"color":"#002B7F","unicode":"1f1f1-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.li%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Litva', '{"color":"#046A38","unicode":"1f1f1-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.lithuanian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Lotyšsko', '{"color":"#A4343A","unicode":"1f1f1-1f1fb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.latvian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Lucembursko', '{"color":"#51ADDA","unicode":"1f1f1-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.lu%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Macao SAR Čína', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f4","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Madagaskar', '{"color":"#FFC0CB","unicode":"1f1f2-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Malajsie', '{"color":"#CC0000","unicode":"1f1f2-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.malaysia%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Malawi', '{"color":"#FFC0CB","unicode":"1f1f2-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Maledivy', '{"color":"#FFC0CB","unicode":"1f1f2-1f1fb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mv%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mali', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ml%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Malta', '{"color":"#CF142B","unicode":"1f1f2-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mt%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Maroko', '{"color":"#C1272D","unicode":"1f1f2-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ma%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Marshallovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1f2-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mh%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Martinik', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mq%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mauricius', '{"color":"#00A551","unicode":"1f1f2-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mu%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mauritánie', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mayotte', '{"color":"#FFC0CB","unicode":"1f1fe-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.yt%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Maďarsko', '{"color":"#436F4D","unicode":"1f1ed-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.hungarian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mexiko', '{"color":"#006341","unicode":"1f1f2-1f1fd","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mexican%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mikronésie', '{"color":"#FFC0CB","unicode":"1f1eb-1f1f2","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Moldavsko', '{"color":"#C8102E","unicode":"1f1f2-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.md%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Monako', '{"color":"#CE1120","unicode":"1f1f2-1f1e8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mc%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mongolsko', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mn%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Montserrat', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ms%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Mosambik', '{"color":"#FFC0CB","unicode":"1f1f2-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Myanmar (Barma)', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f2","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Namibie', '{"color":"#FFC0CB","unicode":"1f1f3-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.na%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nauru', '{"color":"#FFC0CB","unicode":"1f1f3-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.nr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nepál', '{"color":"#FFC0CB","unicode":"1f1f3-1f1f5","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.np%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Neznámo', '{"color":"#800080","unicode":"2753","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Niger', '{"color":"#FFC0CB","unicode":"1f1f3-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ne%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nigérie', '{"color":"#FFC0CB","unicode":"1f1f3-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ng%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nikaragua', '{"color":"#FFC0CB","unicode":"1f1f3-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ni%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Niue', '{"color":"#FFC0CB","unicode":"1f1f3-1f1fa","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nizozemsko', '{"color":"#1E4785","unicode":"1f1f3-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.dutch%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Norsko', '{"color":"#C8102E","unicode":"1f1f3-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.norwegian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nová Kaledonie', '{"color":"#FFC0CB","unicode":"1f1f3-1f1e8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.nc%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Nový Zéland', '{"color":"#FFC0CB","unicode":"1f1f3-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.new_zealand%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Německo', '{"color":"#FFCC00","unicode":"1f1e9-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.german%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Omán', '{"color":"#C8102E","unicode":"1f1f4-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.om%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ostrov Ascension', '{"color":"#FFC0CB","unicode":"1f1e6-1f1e8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ostrov Clipperton', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f5","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ostrov Man', '{"color":"#FFC0CB","unicode":"1f1ee-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.im%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ostrov Norfolk', '{"color":"#FFC0CB","unicode":"1f1f3-1f1eb","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ostrovy Turks a Caicos', '{"color":"#FFC0CB","unicode":"1f1f9-1f1e8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Palau', '{"color":"#FFC0CB","unicode":"1f1f5-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Palestina', '{"color":"#149954","unicode":"1f1f5-1f1f8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Panama', '{"color":"#DA121A","unicode":"1f1f5-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pa%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Papua-Nová Guinea', '{"color":"#FFC0CB","unicode":"1f1f5-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Paraguay', '{"color":"#FFC0CB","unicode":"1f1f5-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.py%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Peru', '{"color":"#C8102E","unicode":"1f1f5-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pe%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Pitcairnovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1f5-1f1f3","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Pobřeží slonoviny', '{"color":"#FFC0CB","unicode":"1f1e8-1f1ee","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Polsko', '{"color":"#FFFFFF","unicode":"1f1f5-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.polish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Portoriko', '{"color":"#FFC0CB","unicode":"1f1f5-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Portugalsko', '{"color":"#046A38","unicode":"1f1f5-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.portuguese%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Pákistán', '{"color":"#FFC0CB","unicode":"1f1f5-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.pk%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Rakousko', '{"color":"#EF3340","unicode":"1f1e6-1f1f9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.austrian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Rovníková Guinea', '{"color":"#FFC0CB","unicode":"1f1ec-1f1f6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.gq%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Rumunsko', '{"color":"#002B7F","unicode":"1f1f7-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.romanian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Rusko', '{"color":"#FFC0CB","unicode":"1f1f7-1f1fa","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Rwanda', '{"color":"#FFC0CB","unicode":"1f1f7-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.rw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Réunion', '{"color":"#FFC0CB","unicode":"1f1f7-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.re%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Samoa', '{"color":"#FFC0CB","unicode":"1f1fc-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ws%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'San Marino', '{"color":"#62B5E5","unicode":"1f1f8-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Saúdská Arábie', '{"color":"#165d31","unicode":"1f1f8-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.saudiarabian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Senegal', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sn%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Severní Korea', '{"color":"#FFC0CB","unicode":"1f1f0-1f1f5","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Severní Makedonie', '{"color":"#F8E92E","unicode":"1f1f2-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mk%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Severní Mariany', '{"color":"#FFC0CB","unicode":"1f1f2-1f1f5","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.mp%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Seychely', '{"color":"#002F6C","unicode":"1f1f8-1f1e8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sc%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Sierra Leone', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sl%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Singapur', '{"color":"#EF3340","unicode":"1f1f8-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.singapore%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Slovensko', '{"color":"#0B4EA2","unicode":"1f1f8-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.slovak%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Slovinsko', '{"color":"#003DA5","unicode":"1f1f8-1f1ee","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.slovenian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Solomonovy ostrovy', '{"color":"#FFC0CB","unicode":"1f1f8-1f1e7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sb%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Somálsko', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.so%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Spojené arabské emiráty', '{"color":"#00732F","unicode":"1f1e6-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ae%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Spojené království', '{"color":"#012169","unicode":"1f1ec-1f1e7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.uk%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Spojené státy americké', '{"color":"#0A3161","unicode":"1f1fa-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Srbsko', '{"color":"#C6363C","unicode":"1f1f7-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.rs%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Srí Lanka', '{"color":"#8D153A","unicode":"1f1f1-1f1f0","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.lk%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Středoafrická republika', '{"color":"#FFC0CB","unicode":"1f1e8-1f1eb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.cf%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Surinam', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sr%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatá Helena', '{"color":"#FFC0CB","unicode":"1f1f8-1f1ed","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatá Lucie', '{"color":"#FFC0CB","unicode":"1f1f1-1f1e8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Bartoloměj', '{"color":"#FFC0CB","unicode":"1f1e7-1f1f1","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Kryštof a Nevis', '{"color":"#FFC0CB","unicode":"1f1f0-1f1f3","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Martin', '{"color":"#FFC0CB","unicode":"1f1f8-1f1fd","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Martin', '{"color":"#FFC0CB","unicode":"1f1f8-1f1fd","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Pierre a Miquelon', '{"color":"#FFC0CB","unicode":"1f1f5-1f1f2","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Tomáš a Princův ostrov', '{"color":"#FFC0CB","unicode":"1f1f8-1f1f9","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svatý Vincenc a Grenadiny', '{"color":"#FFC0CB","unicode":"1f1fb-1f1e8","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Svazijsko', '{"color":"#FFC0CB","unicode":"1f1f8-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Súdán', '{"color":"#FFC0CB","unicode":"1f1f8-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.sd%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Sýrie', '{"color":"#FFC0CB","unicode":"1f1f8-1f1fe","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tanzanie', '{"color":"#FFC0CB","unicode":"1f1f9-1f1ff","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tchaj-wan', '{"color":"#FFC0CB","unicode":"1f1f9-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.taiwan%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Thajsko', '{"color":"#00247D","unicode":"1f1f9-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.th%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Timor-Leste', '{"color":"#FFC0CB","unicode":"1f1f9-1f1f1","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tl%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Togo', '{"color":"#FFC0CB","unicode":"1f1f9-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tg%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tokelau', '{"color":"#FFC0CB","unicode":"1f1f9-1f1f0","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tonga', '{"color":"#FFC0CB","unicode":"1f1f9-1f1f4","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.to%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Trinidad a Tobago', '{"color":"#FFC0CB","unicode":"1f1f9-1f1f9","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tristan da Cunha', '{"color":"#FFC0CB","unicode":"1f1f9-1f1e6","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tunisko', '{"color":"#C8102E","unicode":"1f1f9-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tn%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Turecko', '{"color":"#C8102E","unicode":"1f1f9-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.turkish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Turkmenistán', '{"color":"#FFC0CB","unicode":"1f1f9-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tuvalu', '{"color":"#FFC0CB","unicode":"1f1f9-1f1fb","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tv%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Tádžikistán', '{"color":"#FFC0CB","unicode":"1f1f9-1f1ef","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.tj%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Uganda', '{"color":"#FFC0CB","unicode":"1f1fa-1f1ec","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ug%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ukrajina', '{"color":"#FFC0CB","unicode":"1f1fa-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ukrainian%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Uruguay', '{"color":"#FFC0CB","unicode":"1f1fa-1f1fe","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.uy%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Uzbekistán', '{"color":"#FFC0CB","unicode":"1f1fa-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.uz%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Vanuatu', '{"color":"#FFC0CB","unicode":"1f1fb-1f1fa","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.vu%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Vatikán', '{"color":"#FFE100","unicode":"1f1fb-1f1e6","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.va%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Venezuela', '{"color":"#FFC0CB","unicode":"1f1fb-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ve%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Vietnam', '{"color":"#FFC0CB","unicode":"1f1fb-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.vietnamese%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Vánoční ostrov', '{"color":"#FFC0CB","unicode":"1f1e8-1f1fd","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Wallis &amp; Futuna', '{"color":"#FFC0CB","unicode":"1f1fc-1f1eb","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Zambie', '{"color":"#FFC0CB","unicode":"1f1ff-1f1f2","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.zm%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Zimbabwe', '{"color":"#FFC0CB","unicode":"1f1ff-1f1fc","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.zw%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Západní Sahara', '{"color":"#FFC0CB","unicode":"1f1ea-1f1ed","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Ázerbajdžán', '{"color":"#0092BC","unicode":"1f1e6-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.az%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Írán', '{"color":"#DA0000","unicode":"1f1ee-1f1f7","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Čad', '{"color":"#FFC0CB","unicode":"1f1f9-1f1e9","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.td%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Černá Hora', '{"color":"#D4AF3A","unicode":"1f1f2-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.me%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Česko', '{"color":"#1E73BE","unicode":"1f1e8-1f1ff","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.czech.official%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Čína', '{"color":"#FFC0CB","unicode":"1f1e8-1f1f3","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.china%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Řecko', '{"color":"#0D5EAF","unicode":"1f1ec-1f1f7","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.greek%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Španělsko', '{"color":"#F1BF00","unicode":"1f1ea-1f1f8","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.spain%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Špicberky &amp; Jan Mayen', '{"color":"#FFC0CB","unicode":"1f1f8-1f1ef","publicHolidaysCalendar":null}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Švédsko', '{"color":"#006AA7","unicode":"1f1f8-1f1ea","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.swedish%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRIES', 'public', 'Švýcarsko', '{"color":"#DA291C","unicode":"1f1e8-1f1ed","publicHolidaysCalendar":"https://calendar.google.com/calendar/ical/en.ch%23holiday%40group.v.calendar.google.com/public/basic.ics"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Afghanistan', 'Afghánistán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Albania', 'Albánie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Algeria', 'Alžírsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'American Samoa', 'Americká Samoa');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Andorra', 'Andorra');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Angola', 'Angola');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Anguilla', 'Anguilla');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Antarctica', 'Antarktida');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Antigua & Barbuda', 'Antigua a Barbuda');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Argentina', 'Argentina');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Armenia', 'Arménie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Aruba', 'Aruba');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ascension Island', 'Ostrov Ascension');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Australia', 'Austrálie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Austria', 'Rakousko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Azerbaijan', 'Ázerbajdžán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bahamas', 'Bahamy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bahrain', 'Bahrajn');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bangladesh', 'Bangladéš');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Barbados', 'Barbados');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Belarus', 'Bělorusko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Belgium', 'Belgie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Belize', 'Belize');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Benin', 'Benin');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bermuda', 'Bermudy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bhutan', 'Bhútán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bolivia', 'Bolívie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bosnia and Herzegovina', 'Bosna a Hercegovina');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Botswana', 'Botswana');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bouvet Island', 'Bouvetův ostrov');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Brazil', 'Brazílie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'British Indian Ocean Territory', 'Britské indickooceánské území');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'British Virgin Islands', 'Britské Panenské ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Brunei', 'Brunej');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Bulgaria', 'Bulharsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Burkina Faso', 'Burkina Faso');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Burundi', 'Burundi');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cabo Verde', 'Kapverdy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cambodia', 'Kambodža');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cameroon', 'Kamerun');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Canada', 'Kanada');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Caribbean Netherlands', 'Karibské Nizozemsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cayman Islands', 'Kajmanské ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Central African Republic', 'Středoafrická republika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Chad', 'Čad');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Chile', 'Chile');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'China', 'Čína');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Christmas Island', 'Vánoční ostrov');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Clipperton Island', 'Ostrov Clipperton');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cocos (Keeling) Islands', 'Kokosové (Keelingovy) ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Colombia', 'Kolumbie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Comoros', 'Komory');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Congo - Brazzaville', 'Konžská republika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Congo - Kinshasa', 'Konžská demokratická republika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cook Islands', 'Cookovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Costa Rica', 'Kostarika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Croatia', 'Chorvatsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cuba', 'Kuba');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Curaçao', 'Curaçao');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Cyprus', 'Kypr');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Czechia', 'Česko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Côte d’Ivoire', 'Pobřeží slonoviny');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Denmark', 'Dánsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Diego Garcia', 'Diego Garcia');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Djibouti', 'Džibutsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Dominica', 'Dominika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Dominican Republic', 'Dominikánská republika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ecuador', 'Ekvádor');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Egypt', 'Egypt');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'El Salvador', 'El Salvador');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Equatorial Guinea', 'Rovníková Guinea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Eritrea', 'Eritrea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Estonia', 'Estonsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ethiopia', 'Etiopie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Falkland Islands', 'Falklandy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Faroe Islands', 'Faerské ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Fiji', 'Fidži');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Finland', 'Finsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'France', 'Francie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'French Guiana', 'Francouzská Guyana');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'French Polynesia', 'Francouzská Polynésie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'French Southern Territories', 'Francouzská jižní území');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Gabon', 'Gabon');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Gambia', 'Gambie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Georgia', 'Gruzie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Germany', 'Německo');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ghana', 'Ghana');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Gibraltar', 'Gibraltar');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Greece', 'Řecko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Greenland', 'Grónsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Grenada', 'Grenada');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guadeloupe', 'Guadeloupe');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guam', 'Guam');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guatemala', 'Guatemala');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guernsey', 'Guernsey');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guinea', 'Guinea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guinea-Bissau', 'Guinea-Bissau');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Guyana', 'Guyana');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Haiti', 'Haiti');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Heard & McDonald Islands', 'Heardovy a McDonaldovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Honduras', 'Honduras');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Hungary', 'Maďarsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Iceland', 'Island');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'India', 'Indie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Indonesia', 'Indonésie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Iran', 'Írán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Iraq', 'Irák');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ireland', 'Irsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Isle of Man', 'Ostrov Man');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Israel', 'Izrael');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Italy', 'Itálie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Jamaica', 'Jamaica');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Japan', 'Japonsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Jersey', 'Jersey');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Jordan', 'Jordánsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kazakhstan', 'Kazachstán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kenya', 'Keňa');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kiribati', 'Kiribati');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kosovo', 'Kosovo');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kuwait', 'Kuvajt');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Kyrgyzstan', 'Kyrgyzstán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Laos', 'Laos');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Latvia', 'Lotyšsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Lebanon', 'Libanon');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Lesotho', 'Lesotho');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Liberia', 'Libérie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Libya', 'Libye');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Liechtenstein', 'Lichtenštejnsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Lithuania', 'Litva');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Luxembourg', 'Lucembursko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Macau SAR China', 'Macao SAR Čína');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Madagascar', 'Madagaskar');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Malawi', 'Malawi');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Malaysia', 'Malajsie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Maldives', 'Maledivy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mali', 'Mali');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Malta', 'Malta');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Marshall Islands', 'Marshallovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Martinique', 'Martinik');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mauritania', 'Mauritánie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mauritius', 'Mauricius');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mayotte', 'Mayotte');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mexico', 'Mexiko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Micronesia', 'Mikronésie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Moldova', 'Moldavsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Monaco', 'Monako');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mongolia', 'Mongolsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Montenegro', 'Černá Hora');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Montserrat', 'Montserrat');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Morocco', 'Maroko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Mozambique', 'Mosambik');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Myanmar (Burma)', 'Myanmar (Barma)');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Namibia', 'Namibie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Nauru', 'Nauru');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Nepal', 'Nepál');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Netherlands', 'Nizozemsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'New Caledonia', 'Nová Kaledonie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'New Zealand', 'Nový Zéland');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Nicaragua', 'Nikaragua');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Niger', 'Niger');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Nigeria', 'Nigérie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Niue', 'Niue');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Norfolk Island', 'Ostrov Norfolk');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'North Korea', 'Severní Korea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'North Macedonia', 'Severní Makedonie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Northern Mariana Islands', 'Severní Mariany');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Norway', 'Norsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Oman', 'Omán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Pakistan', 'Pákistán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Palau', 'Palau');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Palestine', 'Palestina');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Panama', 'Panama');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Papua New Guinea', 'Papua-Nová Guinea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Paraguay', 'Paraguay');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Peru', 'Peru');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Philippines', 'Filipíny');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Pitcairn Islands', 'Pitcairnovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Poland', 'Polsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Portugal', 'Portugalsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Puerto Rico', 'Portoriko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Qatar', 'Katar');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Romania', 'Rumunsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Russia', 'Rusko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Rwanda', 'Rwanda');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Réunion', 'Réunion');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Samoa', 'Samoa');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'San Marino', 'San Marino');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Saudi Arabia', 'Saúdská Arábie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Senegal', 'Senegal');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Serbia', 'Srbsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Seychelles', 'Seychely');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Sierra Leone', 'Sierra Leone');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Singapore', 'Singapur');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Sint Maarten', 'Svatý Martin');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Slovakia', 'Slovensko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Slovenia', 'Slovinsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Solomon Islands', 'Solomonovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Somalia', 'Somálsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'South Africa', 'Jihoafrická republika');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'South Georgia & South Sandwich Islands', 'Jižní Georgie a Jižní Sandwichovy ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'South Korea', 'Jižní Korea');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'South Sudan', 'Jižní Súdán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Spain', 'Španělsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Sri Lanka', 'Srí Lanka');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Barthélemy', 'Svatý Bartoloměj');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Helena', 'Svatá Helena');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Kitts & Nevis', 'Svatý Kryštof a Nevis');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Lucia', 'Svatá Lucie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Martin', 'Svatý Martin');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Pierre & Miquelon', 'Svatý Pierre a Miquelon');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'St. Vincent & Grenadines', 'Svatý Vincenc a Grenadiny');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Sudan', 'Súdán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Suriname', 'Surinam');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Svalbard & Jan Mayen', 'Špicberky &amp; Jan Mayen');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Swaziland', 'Svazijsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Sweden', 'Švédsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Switzerland', 'Švýcarsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Syria', 'Sýrie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'São Tomé & Príncipe', 'Svatý Tomáš a Princův ostrov');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Taiwan', 'Tchaj-wan');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tajikistan', 'Tádžikistán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tanzania', 'Tanzanie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Thailand', 'Thajsko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Timor-Leste', 'Timor-Leste');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Togo', 'Togo');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tokelau', 'Tokelau');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tonga', 'Tonga');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Trinidad & Tobago', 'Trinidad a Tobago');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tristan da Cunha', 'Tristan da Cunha');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tunisia', 'Tunisko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Turkmenistan', 'Turkmenistán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Turks & Caicos Islands', 'Ostrovy Turks a Caicos');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Tuvalu', 'Tuvalu');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Türkiye', 'Turecko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'U.S. Outlying Islands', 'Americké odlehlé ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'U.S. Virgin Islands', 'Americké Panenské ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'UNKNOWN', 'Neznámo');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Uganda', 'Uganda');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Ukraine', 'Ukrajina');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'United Arab Emirates', 'Spojené arabské emiráty');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'United Kingdom', 'Spojené království');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'United States', 'Spojené státy americké');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Uruguay', 'Uruguay');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Uzbekistan', 'Uzbekistán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Vanuatu', 'Vanuatu');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Vatican City', 'Vatikán');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Venezuela', 'Venezuela');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Vietnam', 'Vietnam');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Wallis & Futuna', 'Wallis &amp; Futuna');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Western Sahara', 'Západní Sahara');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Yemen', 'Jemen');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Zambia', 'Zambie');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Zimbabwe', 'Zimbabwe');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('COUNTRY_NAMES', 'private', 'Åland Islands', 'Alandské ostrovy');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CURRENCIES', 'public', NULL, '["EUR","CZK","NOK","SEK","HUF","AED","GBP","USD","MAD","BGN","PLN","RON","HRK","DKK","QAR","ISK","BHD","TND","JOD","TRY","ILS","EGP","MUR","OMR","ALL","AFN","AMD","ANG","AOA","ARS","AUD","AWG","AZN","BAM","BBD","BDT","BIF","BMD","BND","BOB","BOV","BRL","BSD","BTN","BWP","BYN","BZD","CAD","CDF","CHE","CHF","CHW","CLF","CLP","CNY","COP","COU","CRC","CUC","CUP","CVE","DJF","DOP","DZD","ERN","ETB","FJD","FKP","GEL","GHS","GIP","GMD","GNF","GTQ","GYD","HKD","HNL","HTG","IDR","INR","IQD","IRR","JMD","JPY","KES","KGS","KHR","KMF","KPW","KRW","KWD","KYD","KZT","LAK","LBP","LKR","LRD","LSL","LYD","MDL","MGA","MKD","MMK","MNT","MOP","MRU","MVR","MWK","MXN","MXV","MYR","MZN","NAD","NGN","NIO","NPR","NZD","PAB","PEN","PGK","PHP","PKR","PYG","RSD","RUB","RWF","SAR","SBD","SCR","SDG","SGD","SHP","SLL","SOS","SRD","SSP","STN","SVC","SYP","SZL","THB","TJS","TMT","TOP","TTD","TWD","TZS","UAH","UGX","USN","UYI","UYU","UYW","UZS","VES","VND","VUV","WST","XAF","XAG","XAU","XBA","XBB","XBC","XBD","XCD","XDR","XOF","XPD","XPF","XPT","XSU","XTS","XUA","XXX","YER","ZAR","ZMW","ZWL"]');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('CURRENT_FTE', 'public,modifiable', NULL, '0.8');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('DAYS_BEFORE_APPEARING_IN_PLAN', 'public,modifiable', NULL, '730');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EARLY_WEEKDAY_ARRIVAL_MAX_HOUR', 'public,modifiable', NULL, '12');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPECTED_OVERTIME_HOURS_PER_DAY', 'public,modifiable', NULL, '1.6');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'ATTRACTION', '{"image":"img/attraction.png","name":"Atrakce"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'CAR_RENTAL', '{"image":"img/car_rental.png","name":"Půjčení auta"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'FLIGHT', '{"image":"img/flight.png","name":"Letenky"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'FUEL', '{"image":"img/fuel.png","name":"Palivo"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'HOTEL', '{"image":"img/hotel.png","name":"Ubytování"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'INTERCITY_TRANSPORT', '{"image":"img/intercity_transport.png","name":"Meziměstská doprava"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'ORGANIZED_TOUR', '{"image":"img/organized_tour.png","name":"Organizované zájezdy"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('EXPENSE_TYPES', 'public', 'PUBLIC_TRANSPORT', '{"image":"img/public_transport.png","name":"Městská doprava"}');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('FILE_SYSTEM_DIR', 'private', NULL, '/domains/lriedel.cz');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('FITNESS_RECORD_DURATION', 'private', NULL, '1800');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('FLAG_MARKER_PATH', 'public', NULL, 'M 21.7691 46.7696 H 15.923 V 0 h 5.8461 V 46.7696 z M 45.1542 11.6925 L 56.8465 0 H 24.6924 v 23.3848 h 32.1542 L 45.1542 11.6925 z');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('FORECAST_DAYS_TO_CACHE', 'public,modifiable', NULL, '9');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_API_CREDENTIALS', 'private', 'accessKey', 'ACCESS_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_API_CREDENTIALS', 'private', 'clientSecret', 'CLIENT_SECRET');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_API_CREDENTIALS', 'public', 'clientId', 'CLIENT_ID');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_CALENDAR_API_WATCH_UUID', 'public', NULL, '08df4da9-7e1f-11ef-b9b7-a2d51aedccc2');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_MAPS_API_KEYS', 'private', 'ipAddress', 'API_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('GOOGLE_MAPS_API_KEYS', 'private', 'website', 'API_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HIGHLIGHT_FULL_IMAGE_SIZE', 'public', 'height', '4000');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HIGHLIGHT_FULL_IMAGE_SIZE', 'public', 'width', '6000');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HIGHLIGHT_THUMBNAIL_IMAGE_SIZE', 'public', 'height', '233');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HIGHLIGHT_THUMBNAIL_IMAGE_SIZE', 'public', 'width', '350');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOME_AIRPORT', 'private', NULL, 'Praha');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOME_LOCATION', 'public', 'country', 'Česko');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOME_LOCATION', 'public', 'latitude', '50.5511909');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOME_LOCATION', 'public', 'longitude', '13.7842376');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOME_LOCATION', 'public', 'timezone', 'Europe/Prague');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('HOST_NAME', 'public', NULL, 'lriedel.cz');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('LATE_WEEKDAY_DEPARTURE_MIN_HOUR', 'public,modifiable', NULL, '17');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('MAIN_CURRENCY', 'public', NULL, 'CZK');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('MAP_STYLE', 'public', NULL, '[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#dde6e8"},{"visibility":"on"}]}]');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('MAXIMUM_NEXT_TRIP_CALENDAR_ENTRIES', 'public,modifiable', NULL, '4');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('MINIMUM_NEARBY_PLACES_COUNT', 'public,modifiable', NULL, '3');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('OPEN_AI_API_KEY', 'private', NULL, 'API_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PASSWORDS', 'private', 'PASSWORD', 'API_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'AddHighlight', '8');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'AddPhoto', '4');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'BackupDatabase', '10');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'GetMediaItems', '7');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'LogFlight', '2');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'PruneDatabase', '9');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'StartCalendarWatching', '1');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateActualForecast', '3');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateAlbum', '5');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateCalendar', '2');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateCategories', '6');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateDaylightForecast', '3');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateFitnessData', '6');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateHighlight', '4');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateHistoricalForecast', '3');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateRegionAreas', '9');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PROCESSORS_DEFAULT_PRIORITIES', 'private', 'UpdateStats', '8');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('PUBLIC_HOLIDAYS', 'private', NULL, '28.10.2024,24.12.2024,1.5.2025,28.10.2025,24.12.2025,25.12.2025,26.12.2025,1.1.2026,8.5.2026,17.11.2026,25.12.2026,1.1.2027,26.3.2027,29.3.2027,6.7.2027,28.10.2027,25.12.2027,14.4.2028,8.5.2028,28.9.2028,24.12.2028,1.1.2029,30.3.2029,28.9.2029,28.10.2029,25.12.2029,26.12.2029,17.11.2024,26.12.2024,1.1.2025,5.7.2025,6.7.2025,28.9.2025,17.11.2025,3.4.2026,1.5.2026,6.7.2026,17.11.2027,24.12.2027,26.12.2027,1.1.2028,17.4.2028,1.5.2028,17.11.2028,8.5.2029,6.7.2029,24.12.2029,25.12.2024,1.1.2025,18.4.2025,21.4.2025,8.5.2025,1.1.2026,6.4.2026,28.9.2026,28.10.2026,24.12.2026,26.12.2026,1.1.2027,1.5.2027,5.7.2027,1.1.2028,5.7.2028,6.7.2028,28.10.2028,25.12.2028,26.12.2028,2.4.2029,1.5.2029,17.11.2029,5.7.2026,8.5.2027,28.9.2027,1.1.2029,5.7.2029');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('SPECIAL_TRIP_NAMES', 'public', 'dayTrips', 'Výlety');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('STANDINGS_STATS_LIMIT', 'private', NULL, '5');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('TIME_OFF_HOURS', 'public,modifiable', 'selfcare', '19.5');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('TIME_OFF_HOURS', 'public,modifiable', 'tenure', '6.5');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('TIME_OFF_HOURS', 'public,modifiable', 'vacation', '221');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('VARIABLE_TIME_CATEGORIES', 'public', 'Poslední měsíc', '2592000');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('VARIABLE_TIME_CATEGORIES', 'public', 'Poslední rok', '31536000');

INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('ALBUMS_WITHOUT_PLACE_LINK', NULL, 'SELECT a.name, NULL FROM album a LEFT JOIN place_summary ps ON a.id = ps.album_id WHERE ps.name IS NULL');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('UNNAMED_AIRLINES', NULL, 'SELECT `key`, CONCAT(''{"code":"'', `key`, ''"}'') FROM configuration WHERE `key` = value AND type = ''AIRLINES''');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('COUNTRIES_WITHOUT_GEOGRAPHICAL_REGIONS', NULL, 'SELECT DISTINCT country, CONCAT(''{"country":"'', country, ''"}'') FROM place_summary WHERE country NOT IN (SELECT DISTINCT country FROM region_geographical WHERE country IS NOT NULL) AND start < UNIX_TIMESTAMP() GROUP BY country HAVING COUNT(DISTINCT name) > 1');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY', 'CREATE TEMPORARY TABLE country_specific_category_identifier AS SELECT category_id AS id FROM region_geographical WHERE country IS NOT NULL;CREATE TEMPORARY TABLE country_with_more_than_one_place AS SELECT country FROM place_summary WHERE start < UNIX_TIMESTAMP() GROUP BY country HAVING COUNT(DISTINCT name) > 1', 'SELECT name, CONCAT(''{"placeId":'', id, ''}'') FROM (SELECT pi.name, pi.country, pi.id FROM category_summary cs INNER JOIN place_identifier pi ON cs.place_id = pi.id INNER JOIN place_event pe ON pi.id = pe.place_id WHERE pe.start < UNIX_TIMESTAMP() AND NOT EXISTS(SELECT * FROM country_specific_category_identifier csci WHERE FIND_IN_SET(csci.id, cs.category_ids))) data WHERE country IN (SELECT * FROM country_with_more_than_one_place)');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('PLACES_WITHOUT_TIME', NULL, 'SELECT CONCAT(name, '' @ '', date_format(from_unixtime(start),''%e.%c.%Y'')), NULL FROM place_event pe INNER JOIN place_identifier pi ON pe.place_id = pi.id WHERE start < UNIX_TIMESTAMP() AND end - start = 86400');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT', NULL, 'SELECT CONCAT(fai.code, '' - '', tai.code, '' ('', l.flight, '')'', '' @ '', DATE_FORMAT(FROM_UNIXTIME(l.scheduled_departure), ''%d.%m.%Y'')), NULL FROM flight_log l LEFT JOIN airport_identifier fai ON l.from_airport_id = fai.id LEFT JOIN airport_identifier tai ON l.to_airport_id = tai.id LEFT JOIN flight_event f ON l.scheduled_departure = f.start WHERE f.id IS NULL');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('PLACE_EVENT_WITH_INCORRECT_TIME', NULL, 'SELECT CONCAT(name, '' @ '', date_format(from_unixtime(start),''%e.%c.%Y'')), NULL FROM place_event pe INNER JOIN place_identifier pi ON pe.place_id = pi.id WHERE MOD(start, 1800) <> 0');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('PLACE_EVENT_WITH_INCORRECT_DURATION', NULL, 'SELECT CONCAT(name, '' @ '', date_format(from_unixtime(start),''%e.%c.%Y'')), NULL FROM place_event pe INNER JOIN place_identifier pi ON pe.place_id = pi.id WHERE MOD(end - start, 1800) <> 0');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('DUPLICATED_PLACE_IDENTIFIERS', NULL, 'SELECT GROUP_CONCAT(name SEPARATOR '', ''), NULL FROM place_identifier GROUP BY latitude, longitude HAVING COUNT(*) > 1');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('NON_LOGGED_FLIGHTS', NULL, 'SELECT CONCAT(f.from, '' - '', f.to, '' ('', f.flight, '')'', '' @ '', DATE_FORMAT(FROM_UNIXTIME(f.start), ''%d.%m.%Y'')), CONCAT(''{"scheduledDeparture":'', f.start, '',"flight":"'', f.flight, ''"'', '',"from":"'', f.from, ''"'', '',"to":"'', f.to, ''"'', '',"tripId":'', f.trip_id, ''}'') FROM flight_event f LEFT JOIN flight_log lf ON f.flight = lf.flight AND f.start = lf.scheduled_departure WHERE lf.scheduled_departure IS NULL AND f.start < UNIX_TIMESTAMP()');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('GEOGRAPHICAL_REGIONS_WITH_SAME_NAME', NULL, 'SELECT ci.name, NULL FROM region_geographical rg INNER JOIN category_identifier ci ON rg.category_id = ci.id WHERE rg.json NOT LIKE ''%Point%'' GROUP BY ci.name HAVING COUNT(*) > 1');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('LOW_QUALITY_PHOTOS_WITHOUT_REPLACEMENT', NULL, 'SELECT DISTINCT name, NULL FROM place_summary WHERE is_low_quality_album = 1 AND (name, country) NOT IN (SELECT name, country FROM place_summary WHERE is_low_quality_album = 0) ORDER BY start');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('FUTURE_COUNTRIES_WITHOUT_PUBLIC_HOLIDAYS_CALENDAR', NULL, 'SELECT DISTINCT country, CONCAT(''{"country":"'', country, ''"}'') FROM place_summary ps INNER JOIN configuration c ON c.`key` = ps.country WHERE c.type = ''COUNTRIES'' AND start > UNIX_TIMESTAMP() AND c.value LIKE ''%"publicHolidaysCalendar":null%''');
INSERT INTO definition_problem (`name`, `helper_statements`, `query`) VALUES ('LOGGED_ERRORS', NULL, 'SELECT CONCAT(message, '' @ '', date_format(from_unixtime(timestamp),''%H:%i:%s''), ''<br><br>''), NULL FROM cache_log ORDER BY timestamp DESC');

INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_AIRBORNE_DISTANCE', 'FACT', 'SELECT ROUND(SUM(GET_DISTANCE(fai.latitude, fai.longitude, tai.latitude, tai.longitude))) FROM flight_log l INNER JOIN airport_identifier fai ON l.from_airport_id = fai.id INNER JOIN airport_identifier tai ON l.to_airport_id = tai.id WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}}', 'FLIGHT', 'YEAR,TRIP', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_FLIGHT_DURATION', 'FACT', 'SELECT (AVG(actual_arrival - actual_departure)) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}}', 'FLIGHT', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_AIRBORNE_TIME', 'FACT', 'SELECT (SUM(actual_arrival - actual_departure)) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}}', 'FLIGHT', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_PHOTOS_COUNT', 'FACT', 'SELECT SUM(album_images_count) FROM (SELECT * FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) GROUP BY album_id) t', 'PHOTO', 'YEAR,TRIP,CATEGORY', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_VISITED_COUNTRIES_COUNT', 'FACT', 'SELECT COUNT(DISTINCT country) FROM place_summary WHERE start >= {{start}} AND end <= {{end}}', 'PLACE', 'YEAR', 'COUNTRIES');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_VISITED_PLACES_COUNT', 'FACT', 'SELECT COUNT(DISTINCT name) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}})', 'PLACE', 'YEAR,TRIP,CATEGORY', 'PLACES');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_EXPENSES', 'FACT', 'SELECT ROUND(SUM(cost)) FROM trip_summary WHERE start >= {{start}} AND end <= {{end}}', 'EXPENSE', 'YEAR', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_EXPENSES_PER_DAY', 'FACT', 'SELECT ROUND(SUM(cost) / SUM(days)) FROM trip_summary WHERE start >= {{start}} AND end <= {{end}} AND NOT IS_DAY_TRIP(trip_id)', 'EXPENSE', 'YEAR,TRIP', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_TRAVEL_DAYS_COUNT', 'FACT', 'SELECT COUNT(*) FROM (SELECT * FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) GROUP BY FLOOR(start / 86400)) t', 'CALENDAR', 'YEAR,CATEGORY', 'DAYS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_HOTEL_NIGHTS_COUNT', 'FACT', 'SELECT SUM(ROUND((end - start) / 86400) - 1) FROM stay_event WHERE start >= {{start}} AND end <= {{end}}', 'HOTEL', 'YEAR', 'NIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_NIGHTS_PER_HOTEL', 'FACT', 'SELECT ROUND(AVG(ROUND((end - start) / 86400) - 1)) FROM stay_event WHERE start >= {{start}} AND end <= {{end}}', 'HOTEL', 'YEAR', 'NIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_FLIGHTS_COUNT', 'FACT', 'SELECT COUNT(*) FROM flight_event WHERE start >= {{start}} AND end <= {{end}}', 'FLIGHT', 'YEAR', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_PHOTOS_PER_ALBUM', 'FACT', 'SELECT ROUND(AVG(album_images_count)) FROM (SELECT DISTINCT album_id, album_images_count FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}})) t', 'PHOTO', 'YEAR,TRIP,CATEGORY', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_TRIP_LENGTH', 'FACT', 'SELECT ROUND(AVG(days)) FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}}', 'CALENDAR', 'YEAR', 'DAYS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_USED_AIRCRAFTS', 'STANDINGS', 'SELECT aircraft, COUNT(*) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} GROUP BY aircraft ORDER BY COUNT(*) DESC', 'FLIGHT', 'YEAR', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_USED_AIRLINES', 'STANDINGS', 'SELECT GET_AIRLINE_NAME_FROM_AIRLINE_CODE(SUBSTRING(FLIGHT, 1, 2)), COUNT(*) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} GROUP BY GET_AIRLINE_NAME_FROM_AIRLINE_CODE(SUBSTRING(FLIGHT, 1, 2)) ORDER BY COUNT(*) DESC', 'FLIGHT', 'YEAR', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('SHORTEST_FLIGHTS', 'STANDINGS', 'SELECT CONCAT(GET_AIRPORT_NAME_FROM_AIRPORT_ID(from_airport_id), '' - '', GET_AIRPORT_NAME_FROM_AIRPORT_ID(to_airport_id), '' @ '', FROM_UNIXTIME(actual_departure, ''%d.%m.%Y'')), (actual_arrival - actual_departure) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} ORDER BY (actual_arrival - actual_departure) ASC', 'FLIGHT', 'YEAR', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LONGEST_FLIGHTS', 'STANDINGS', 'SELECT CONCAT(GET_AIRPORT_NAME_FROM_AIRPORT_ID(from_airport_id), '' - '', GET_AIRPORT_NAME_FROM_AIRPORT_ID(to_airport_id), '' @ '', FROM_UNIXTIME(actual_departure, ''%d.%m.%Y'')), (actual_arrival - actual_departure) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} ORDER BY (actual_arrival - actual_departure) DESC', 'FLIGHT', 'YEAR', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_USED_AIRPORTS', 'STANDINGS', 'SELECT CONCAT(GET_AIRPORT_NAME_FROM_AIRPORT_ID(airport_id), '' ('', code, '')''), COUNT(*) FROM (SELECT scheduled_departure AS time, from_airport_id airport_id FROM flight_log UNION SELECT scheduled_arrival AS time, to_airport_id airport_id FROM flight_log) a LEFT JOIN airport_identifier ai ON a.airport_id = ai.id WHERE time >= {{start}} AND time <= {{end}} GROUP BY airport_id ORDER BY COUNT(*) DESC', 'FLIGHT', 'YEAR', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_PHOTOS_PER_DAY', 'STANDINGS', 'SELECT CONCAT(GROUP_CONCAT(DISTINCT name ORDER BY start SEPARATOR '', ''), '' @ '', FROM_UNIXTIME(start, ''%d.%m.%Y'')), COALESCE(SUM(album_images_count), 0) FROM (SELECT name, album_images_count, MIN(start) AS start, MAX(end) AS end FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY album_id) t WHERE album_images_count > 0 GROUP BY FLOOR(end / 86400) ORDER BY SUM(album_images_count) DESC', 'PHOTO', 'YEAR,TRIP', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_PHOTOS_PER_PLACE', 'STANDINGS', 'SELECT name, COALESCE(SUM(album_images_count), 0) FROM (SELECT name, album_images_count FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) GROUP BY album_id) t WHERE album_images_count > 0 GROUP BY name ORDER BY SUM(album_images_count) DESC', 'PHOTO', 'YEAR,TRIP,CATEGORY', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_PHOTOS_PER_COUNTRY', 'STANDINGS', 'SELECT country, COALESCE(SUM(album_images_count), 0) FROM (SELECT name, country, album_images_count FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY album_id) t GROUP BY country ORDER BY SUM(album_images_count) DESC', 'PHOTO', 'YEAR', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_PHOTOS_PER_CATEGORY', 'STANDINGS', 'SELECT GET_CATEGORY_NAME_FROM_CATEGORY_ID(c.category_id), COALESCE(SUM(ps.album_images_count), 0) FROM category c INNER JOIN place_summary ps ON c.place_id = ps.place_id WHERE ps.start >= {{start}} AND ps.end <= {{end}} GROUP BY c.category_id ORDER BY SUM(ps.album_images_count) DESC', 'PHOTO', 'YEAR', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_USED_FLIGHTS', 'STANDINGS', 'SELECT CONCAT(GET_AIRPORT_NAME_FROM_AIRPORT_ID(from_airport_id), '' - '', GET_AIRPORT_NAME_FROM_AIRPORT_ID(to_airport_id), '' ('', flight, '')''), COUNT(*) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} GROUP BY flight HAVING COUNT(*) > 1 ORDER BY COUNT(*) DESC', 'FLIGHT', '', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_USED_AIRCRAFT_REGISTRATIONS', 'STANDINGS', 'SELECT CONCAT(registration, '' @ '', GET_AIRLINE_NAME_FROM_AIRLINE_CODE(SUBSTRING(FLIGHT, 1, 2))), (COUNT(*)) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} GROUP BY registration ORDER BY COUNT(*) DESC', 'FLIGHT', '', 'FLIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('FURTHEST_PLACES', 'STANDINGS', 'SELECT DISTINCT name, ROUND(GET_DISTANCE_FROM_HOME(latitude, longitude)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} ORDER BY GET_DISTANCE_FROM_HOME(latitude, longitude) DESC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('FURTHEST_COUNTRIES', 'STANDINGS', 'SELECT country, ROUND(MIN(GET_DISTANCE_FROM_HOME(latitude, longitude))) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY country ORDER BY MIN(GET_DISTANCE_FROM_HOME(latitude, longitude)) DESC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('VISITED_PLACES_PER_COUNTRY', 'STANDINGS', 'SELECT country, COUNT(DISTINCT name) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY country ORDER BY COUNT(DISTINCT name) DESC', 'PLACE', 'YEAR', 'PLACES');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('VISITED_PLACES_PER_CATEGORY', 'STANDINGS', 'SELECT GET_CATEGORY_NAME_FROM_CATEGORY_ID(c.category_id), COUNT(DISTINCT ps.name) FROM category c INNER JOIN place_summary ps ON c.place_id = ps.place_id WHERE ps.start >= {{start}} AND ps.end <= {{end}} GROUP BY c.category_id ORDER BY COUNT(DISTINCT ps.name) DESC', 'PLACE', 'YEAR', 'PLACES');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LONGEST_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), days FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY days DESC', 'CALENDAR', 'YEAR', 'DAYS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('SHORTEST_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), days FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY days ASC', 'CALENDAR', 'YEAR', 'DAYS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_EXPENSIVE_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), ROUND(cost) FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY cost DESC', 'EXPENSE', 'YEAR', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_EXPENSIVE_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), ROUND(cost) FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY cost ASC', 'EXPENSE', 'YEAR', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_EXPENSIVE_TRIPS_PER_DAY', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), ROUND(cost / days) FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY cost / days DESC', 'EXPENSE', 'YEAR', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_EXPENSIVE_TRIPS_PER_DAY', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), ROUND(cost / days) FROM trip_summary WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY cost / days ASC', 'EXPENSE', 'YEAR', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LONGEST_HOTEL_STAYS', 'STANDINGS', 'SELECT name, ROUND((end - start) / 86400) - 1 FROM stay_event WHERE start >= {{start}} AND end <= {{end}} ORDER BY ROUND((start - end) / 86400)', 'HOTEL', 'YEAR', 'NIGHTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT', 'STANDINGS', 'SELECT s.name, FLOOR(es.main_currency_value / (ROUND((s.end - s.start) / 86400) - 1)) FROM stay_event s INNER JOIN expense_summary es ON es.description = s.name AND es.trip_id = s.trip_id WHERE es.type = ''HOTEL'' AND (s.start + s.end) / 2 >= {{start}} AND (s.start + s.end) / 2 <= {{end}} ORDER BY FLOOR(es.main_currency_value / (ROUND((s.end - s.start) / 86400) - 1)) DESC', 'EXPENSE', 'YEAR,TRIP', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT', 'STANDINGS', 'SELECT s.name, FLOOR(es.main_currency_value / (ROUND((s.end - s.start) / 86400) - 1)) FROM stay_event s INNER JOIN expense_summary es ON es.description = s.name AND es.trip_id = s.trip_id WHERE es.type = ''HOTEL'' AND (s.start + s.end) / 2 >= {{start}} AND (s.start + s.end) / 2 <= {{end}} ORDER BY FLOOR(es.main_currency_value / (ROUND((s.end - s.start) / 86400) - 1)) ASC', 'EXPENSE', 'YEAR,TRIP', 'MAIN_CURRENCY');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('DAYS_PER_COUNTRY', 'STANDINGS', 'SELECT country, COUNT(DISTINCT ROUND(start / 86400)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY country ORDER BY COUNT(DISTINCT FLOOR(start / 86400)) DESC', 'CALENDAR', 'YEAR', 'DAYS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_DELAYED_FLIGHTS', 'STANDINGS', 'SELECT CONCAT(GET_AIRPORT_NAME_FROM_AIRPORT_ID(from_airport_id), '' - '', GET_AIRPORT_NAME_FROM_AIRPORT_ID(to_airport_id), '' @ '', FROM_UNIXTIME(actual_departure, ''%d.%m.%Y'')), (actual_arrival - scheduled_arrival) FROM flight_log WHERE scheduled_departure >= {{start}} AND scheduled_arrival <= {{end}} AND actual_arrival > scheduled_arrival ORDER BY (actual_arrival - scheduled_arrival) DESC', 'FLIGHT', 'YEAR', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_STEPS_COUNT', 'FACT', 'SELECT SUM(steps) FROM fitness WHERE timestamp >= {{start}} AND timestamp <= {{end}}', 'FITNESS', 'YEAR,TRIP', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_STEPS_PER_DAY', 'FACT', 'SELECT ROUND(SUM(steps) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) FROM fitness WHERE timestamp >= {{start}} AND timestamp <= {{end}}', 'FITNESS', 'YEAR,TRIP', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_TIME_IN_MOTION', 'FACT', 'SELECT SUM(minutes) * 60 FROM fitness WHERE timestamp >= {{start}} AND timestamp <= {{end}}', 'FITNESS', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('AVERAGE_TIME_IN_MOTION_PER_DAY', 'FACT', 'SELECT FLOOR(SUM(minutes) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) * 60 FROM fitness WHERE timestamp >= {{start}} AND timestamp <= {{end}}', 'FITNESS', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_AVERAGE_STEPS_PER_DAY_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), (SELECT FLOOR(SUM(steps) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) FROM trip_summary t WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY (SELECT FLOOR(SUM(steps) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) DESC', 'FITNESS', 'YEAR', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_AVERAGE_STEPS_PER_DAY_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), (SELECT FLOOR(SUM(steps) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) FROM trip_summary t WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY (SELECT FLOOR(SUM(steps) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) ASC', 'FITNESS', 'YEAR', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), (SELECT (FLOOR(SUM(minutes) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) * 60) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) FROM trip_summary t WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY (SELECT FLOOR(SUM(minutes) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) * 60 FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) DESC', 'FITNESS', 'YEAR', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME(name, year), (SELECT (FLOOR(SUM(minutes) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) * 60) FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) FROM trip_summary t WHERE NOT IS_DAY_TRIP(trip_id) AND start >= {{start}} AND end <= {{end}} ORDER BY (SELECT FLOOR(SUM(minutes) / COUNT(DISTINCT (FLOOR(timestamp / 86400) * 86400))) * 60 FROM fitness f WHERE f.timestamp >= t.start AND f.timestamp <= t.end) ASC', 'FITNESS', 'YEAR', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_STEPS_PER_DAY', 'STANDINGS', 'SELECT CONCAT(GROUP_CONCAT(DISTINCT p.name SEPARATOR '', ''), '' @ '', FROM_UNIXTIME(f.timestamp, ''%d.%m.%Y'')), f.steps FROM place_summary p INNER JOIN (SELECT SUM(steps) AS steps, timestamp - (timestamp % 86400) AS timestamp FROM fitness GROUP BY timestamp - (timestamp % 86400)) f ON (p.start - (p.start % 86400)) = f.timestamp WHERE p.start >= {{start}} AND p.end <= {{end}} GROUP BY f.timestamp ORDER BY f.steps DESC', 'FITNESS', 'YEAR,TRIP', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_STEPS_PER_DAY', 'STANDINGS', 'SELECT CONCAT(GROUP_CONCAT(DISTINCT p.name SEPARATOR '', ''), '' @ '', FROM_UNIXTIME(f.timestamp, ''%d.%m.%Y'')), f.steps FROM place_summary p INNER JOIN (SELECT SUM(steps) AS steps, timestamp - (timestamp % 86400) AS timestamp FROM fitness GROUP BY timestamp - (timestamp % 86400)) f ON (p.start - (p.start % 86400)) = f.timestamp WHERE p.start >= {{start}} AND p.end <= {{end}} GROUP BY f.timestamp ORDER BY f.steps ASC', 'FITNESS', 'YEAR,TRIP', 'STEPS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_TIME_IN_MOTION_PER_DAY', 'STANDINGS', 'SELECT CONCAT(GROUP_CONCAT(DISTINCT p.name SEPARATOR '', ''), '' @ '', FROM_UNIXTIME(f.timestamp, ''%d.%m.%Y'')), (f.minutes * 60) FROM place_summary p INNER JOIN (SELECT SUM(minutes) AS minutes, timestamp - (timestamp % 86400) AS timestamp FROM fitness GROUP BY timestamp - (timestamp % 86400)) f ON (p.start - (p.start % 86400)) = f.timestamp WHERE p.start >= {{start}} AND p.end <= {{end}} GROUP BY f.timestamp ORDER BY f.minutes DESC', 'FITNESS', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_TIME_IN_MOTION_PER_DAY', 'STANDINGS', 'SELECT CONCAT(GROUP_CONCAT(DISTINCT p.name SEPARATOR '', ''), '' @ '', FROM_UNIXTIME(f.timestamp, ''%d.%m.%Y'')), (f.minutes * 60) FROM place_summary p INNER JOIN (SELECT SUM(minutes) AS minutes, timestamp - (timestamp % 86400) AS timestamp FROM fitness GROUP BY timestamp - (timestamp % 86400)) f ON (p.start - (p.start % 86400)) = f.timestamp WHERE p.start >= {{start}} AND p.end <= {{end}} GROUP BY f.timestamp ORDER BY f.minutes ASC', 'FITNESS', 'YEAR,TRIP', 'DURATION');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LAST_VISIT', 'FACT', 'SELECT MAX(end) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}})', 'CALENDAR', 'CATEGORY', 'BEFORE_DAYS_TIMESTAMP');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_VISITED_PLACES', 'STANDINGS', 'SELECT name, COUNT(DISTINCT trip_id) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) GROUP BY place_id HAVING COUNT(DISTINCT trip_id) > 1 ORDER BY COUNT(DISTINCT trip_id) DESC, GET_DISTANCE_FROM_HOME(latitude, longitude) DESC', 'CALENDAR', 'YEAR,CATEGORY', 'VISITS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('WESTERNMOST_PLACES', 'STANDINGS', 'SELECT DISTINCT name, FLOOR(GET_DISTANCE_FROM_HOME(latitude, longitude)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} ORDER BY longitude ASC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('EASTERNMOST_PLACES', 'STANDINGS', 'SELECT DISTINCT name, FLOOR(GET_DISTANCE_FROM_HOME(latitude, longitude)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} ORDER BY longitude DESC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('NORTHERNMOST_PLACES', 'STANDINGS', 'SELECT DISTINCT name, FLOOR(GET_DISTANCE_FROM_HOME(latitude, longitude)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} ORDER BY latitude DESC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('SOUTHERNMOST_PLACES', 'STANDINGS', 'SELECT DISTINCT name, FLOOR(GET_DISTANCE_FROM_HOME(latitude, longitude)) FROM place_summary WHERE start >= {{start}} AND end <= {{end}} ORDER BY latitude ASC', 'PLACE', 'YEAR', 'KILOMETERS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('TOTAL_VISITED_AIRPORTS_COUNT', 'FACT', 'SELECT COUNT(DISTINCT airport_id) FROM (SELECT scheduled_departure AS time, from_airport_id airport_id FROM flight_log UNION SELECT scheduled_arrival AS time, to_airport_id airport_id FROM flight_log) a WHERE time >= {{start}} AND time <= {{end}}', 'FLIGHT', 'YEAR', 'AIRPORTS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('MOST_PHOTOS_PER_TRIP', 'STANDINGS', 'SELECT GET_FULLY_QUALIFIED_TRIP_NAME_FROM_TRIP_ID(trip_id), COALESCE(SUM(album_images_count), 0) FROM (SELECT trip_id, album_images_count FROM place_summary WHERE start >= {{start}} AND end <= {{end}} GROUP BY album_id) t GROUP BY trip_id ORDER BY SUM(album_images_count) DESC', 'PHOTO', 'YEAR', 'PHOTOS');
INSERT INTO definition_statistics (`name`, `kind`, `query`, `category`, `types`, `unit`) VALUES ('LEAST_RECENTLY_VISITED_PLACES', 'STANDINGS', 'SELECT name, last_visit FROM (SELECT *, MAX(end) AS last_visit FROM place_summary WHERE id IS NOT NULL GROUP BY place_id) p WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) ORDER BY last_visit ASC', 'PLACE', 'CATEGORY', 'BEFORE_DAYS_TIMESTAMP');

INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_EXCHANGE_RATES', 'DELETE FROM cache_exchange_rate WHERE last_update + GET_CONFIGURATION_FOR_KEY(''AUTO_PURGE_RETENTION_DAYS'', ''exchangeRates'') * 86400 < UNIX_TIMESTAMP()');
INSERT INTO pruner (`name`, `query`) VALUES ('LIMIT_COUNTRIES_CONFIGURATION_VISIBILITY', 'UPDATE configuration SET levels = ''public'' WHERE type = ''COUNTRIES'' AND `key` NOT IN (SELECT country FROM place_identifier)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_LOGS', 'DELETE FROM cache_log WHERE timestamp < (UNIX_TIMESTAMP() - 86400 * GET_CONFIGURATION_FOR_KEY(''AUTO_PURGE_RETENTION_DAYS'', ''log''))');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_PLACE_IDENTIFIERS', 'DELETE FROM place_identifier WHERE id NOT IN (SELECT place_id FROM place_candidate) AND id NOT IN (SELECT place_id FROM place_event) AND id NOT IN (SELECT place_id FROM place_permanent) AND id NOT IN (SELECT place_id FROM place_candidate_event)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_TRIP_IDENTIFIERS', 'DELETE FROM trip_identifier WHERE id NOT IN (SELECT trip_id FROM trip_event) AND id NOT IN (SELECT trip_id FROM place_candidate_event)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_CATEGORY_IDENTIFIERS', 'DELETE FROM category_identifier WHERE name NOT IN (SELECT `key` FROM configuration WHERE type = ''VARIABLE_TIME_CATEGORIES'') AND id NOT IN (SELECT category_id FROM category) AND id NOT IN (SELECT category_id FROM region_geographical) AND id NOT IN (SELECT category_id FROM region_composite) AND id NOT IN (SELECT subject_category_id FROM region_composite)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_ACTUAL_FORECAST', 'DELETE FROM forecast_actual WHERE timestamp < UNIX_TIMESTAMP()');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_HISTORICAL_FORECAST', 'DELETE FROM forecast_historical WHERE timestamp < UNIX_TIMESTAMP() OR (place_id, timestamp) NOT IN (SELECT place_id, start FROM place_event)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_DAYLIGHT_FORECAST', 'DELETE FROM forecast_daylight WHERE timestamp < UNIX_TIMESTAMP() OR (place_id, timestamp) NOT IN (SELECT place_id, start FROM place_event)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_PLACE_CANDIDATES', 'DELETE FROM place_candidate WHERE place_id IN (SELECT place_id FROM place_event WHERE end < UNIX_TIMESTAMP())');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_FITNESS_DATA', 'DELETE f FROM fitness f LEFT JOIN (SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT * FROM trip_event WHERE trip_id NOT IN (SELECT id FROM trip_identifier WHERE name = GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips''))) t WHERE s.seq >= t.start AND s.seq <= t.end AND s.seq <= UNIX_TIMESTAMP() UNION SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT ps.* FROM place_summary ps INNER JOIN trip_identifier ti ON ps.trip_id = ti.id WHERE ti.name = GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips'') AND YEAR(FROM_UNIXTIME(ps.start)) = ti.year) p WHERE s.seq >= p.start - (p.start % 86400) AND s.seq <= 86400 + p.end - (p.end % 86400) AND s.seq <= UNIX_TIMESTAMP()) x ON x.start = f.timestamp WHERE x.start IS NULL');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_LOCATIONS', 'DELETE FROM cache_location WHERE last_access < (UNIX_TIMESTAMP() - 86400 * GET_CONFIGURATION_FOR_KEY(''AUTO_PURGE_RETENTION_DAYS'', ''location''))');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_YEAR_STATISTICS', 'DELETE FROM cache_statistics_year WHERE id NOT IN (SELECT year FROM trip_identifier)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_ALBUM_IDENTIFIERS', 'DELETE FROM album_identifier WHERE id NOT IN (SELECT id FROM album)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_AIRPORT_IDENTIFIERS', 'DELETE FROM airport_identifier WHERE id NOT IN (SELECT from_airport_id FROM flight_log) AND id NOT IN (SELECT to_airport_id FROM flight_log)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_PHOTO_IDENTIFIERS', 'DELETE FROM photo_identifier WHERE id NOT IN (SELECT id FROM photo)');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_MAP_POINTS', 'DELETE FROM cache_point WHERE timestamp + GET_CONFIGURATION_FOR_KEY(''AUTO_PURGE_RETENTION_DAYS'', ''points'') * 86400 < UNIX_TIMESTAMP()');
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_HIGHLIGHT_IDENTIFIERS', 'DELETE FROM highlight_identifier WHERE id NOT IN (SELECT highlight_id FROM highlight_place) AND id NOT IN (SELECT highlight_id FROM highlight_trip) AND id NOT IN (SELECT highlight_id FROM highlight_category) AND id NOT IN (SELECT highlight_id FROM highlight_year)');

INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_ALBUMS', 'UpdateAlbum', NULL, 'SELECT 21600', '1727611826');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('BACKUP_DATABASE', 'BackupDatabase', 'SELECT (SELECT GROUP_CONCAT(TABLE_NAME SEPARATOR '','') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE <> ''VIEW'' AND TABLE_NAME NOT LIKE ''cache_%'' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME NOT IN (SELECT SUBSTRING(TABLE_NAME, 2) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = ''VIEW'' AND TABLE_SCHEMA = DATABASE())) AS tables, (SELECT GROUP_CONCAT(TABLE_NAME SEPARATOR '','') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = ''VIEW'' AND TABLE_SCHEMA = DATABASE()) AS views, (SELECT GROUP_CONCAT(ROUTINE_NAME SEPARATOR '','') FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = ''FUNCTION'' AND ROUTINE_SCHEMA = DATABASE()) AS functions', 'SELECT 604800', '1727622219');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('START_CALENDAR_WATCHING', 'StartCalendarWatching', 'SELECT u.uuid AS uuid, `key` AS calendar FROM configuration c, (SELECT UUID() AS uuid) u WHERE type = ''CALENDARS''', 'SELECT 82800', '1727585722');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_FITNESS_DATA', 'UpdateFitnessData', 'SELECT x.start AS start FROM (SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT * FROM trip_event WHERE trip_id NOT IN (SELECT id FROM trip_identifier WHERE name = GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips''))) t WHERE s.seq >= t.start AND s.seq <= t.end AND s.seq <= UNIX_TIMESTAMP() UNION SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT ps.* FROM place_summary ps INNER JOIN trip_identifier ti ON ps.trip_id = ti.id WHERE ti.name = GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips'') AND YEAR(FROM_UNIXTIME(ps.start)) = ti.year) p WHERE s.seq >= p.start - (p.start % 86400) AND s.seq <= 86400 + p.end - (p.end % 86400) AND s.seq <= UNIX_TIMESTAMP()) x LEFT JOIN fitness f ON x.start = f.timestamp WHERE f.timestamp IS NULL OR f.timestamp + (7 * 86400) > f.last_update', 'SELECT 1800', '1727621713');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('LOG_FLIGHTS', 'LogFlight', 'SELECT f.start AS scheduledDeparture, f.flight AS flight, f.`from` AS `from`, f.`to` AS `to`, f.trip_id AS tripId FROM flight_event f LEFT JOIN flight_log lf ON f.flight = lf.flight AND f.start = lf.scheduled_departure WHERE lf.flight IS NULL AND end < UNIX_TIMESTAMP()', 'SELECT IF((SELECT end FROM flight_event fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure WHERE fl.actual_arrival IS NULL ORDER BY fe.end ASC LIMIT 1) < (SELECT last_execution FROM scheduler WHERE name = ''{{name}}''), 14400, (SELECT UNIX_TIMESTAMP() - (SELECT last_execution FROM scheduler WHERE name = ''{{name}}'') + end + (SELECT AVG(actual_arrival - scheduled_arrival) FROM flight_log WHERE scheduled_arrival > UNIX_TIMESTAMP() - 365 * 86400 AND actual_arrival - scheduled_arrival > 0) - UNIX_TIMESTAMP() FROM flight_event fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure WHERE fl.actual_arrival IS NULL ORDER BY fe.end ASC LIMIT 1))', '1727551825');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_ACTUAL_FORECAST', 'UpdateActualForecast', 'SELECT pi.id AS placeId, p.start FROM place_event p LEFT JOIN place_identifier pi ON p.place_id = pi.id LEFT JOIN forecast_actual fa ON p.place_id = fa.place_id AND p.start = fa.timestamp WHERE UNIX_TIMESTAMP() < p.start AND UNIX_TIMESTAMP() + GET_CONFIGURATION(''FORECAST_DAYS_TO_CACHE'') * 86400 > p.start AND (fa.expiration IS NULL OR fa.expiration < UNIX_TIMESTAMP())', 'SELECT 300', '1727622137');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_HISTORICAL_FORECAST', 'UpdateHistoricalForecast', 'SELECT pi.id AS placeId, p.start FROM place_event p LEFT JOIN place_identifier pi ON p.place_id = pi.id LEFT JOIN forecast_historical fh ON p.place_id = fh.place_id AND p.start = fh.timestamp WHERE fh.place_id IS NULL AND p.start > UNIX_TIMESTAMP()', 'SELECT 300', '1727622137');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_DAYLIGHT_FORECAST', 'UpdateDaylightForecast', 'SELECT pi.id AS placeId, p.start FROM place_event p LEFT JOIN place_identifier pi ON p.place_id = pi.id LEFT JOIN forecast_daylight fd ON p.place_id = fd.place_id AND p.start = fd.timestamp WHERE fd.place_id IS NULL AND p.start > UNIX_TIMESTAMP()', 'SELECT 300', '1727622137');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_ALL_STATS', 'UpdateStats', 'SELECT ''ALL'' AS type', 'SELECT 604800', '1727470975');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_CATEGORY_STATS', 'UpdateStats', 'SELECT ''CATEGORY'' AS type, id AS id FROM category_identifier', 'SELECT 604800', '1727470976');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_TRIP_STATS', 'UpdateStats', 'SELECT ''TRIP'' AS type, trip_id AS id FROM trip_summary WHERE start < UNIX_TIMESTAMP() AND name <> GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips'')', 'SELECT 604800', '1727470976');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_YEAR_STATS', 'UpdateStats', 'SELECT DISTINCT ''YEAR'' AS type, year AS id FROM trip_summary WHERE start < UNIX_TIMESTAMP() AND name <> GET_CONFIGURATION_FOR_KEY(''SPECIAL_TRIP_NAMES'', ''dayTrips'')', 'SELECT 604800', '1727470976');
INSERT INTO scheduler (`name`, `processor`, `args_query`, `interval_query`, `last_execution`) VALUES ('UPDATE_HIGHLIGHTS', 'UpdateHighlight', NULL, 'SELECT 21600', '1727611826');