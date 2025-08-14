DROP TABLE forecast_daylight;
CREATE TABLE forecast_daylight (
  place_id bigint(20) unsigned NOT NULL,
  timestamp bigint(20) NOT NULL,
  sunrise bigint(20) NOT NULL,
  sunset bigint(20) NOT NULL,
  start_sun_altitude double NOT NULL,
  end_sun_altitude double NOT NULL,
  start_sun_azimuth double NOT NULL,
  end_sun_azimuth double NOT NULL,
  KEY place_id (place_id),
  CONSTRAINT forecast_daylight_ibfk_1 FOREIGN KEY (place_id) REFERENCES place_identifier (id) ON DELETE CASCADE
);

DROP VIEW _place_event_summary;
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
    pi.excerpt,
    pe.trip_id,
    pe.layover,
    COALESCE(cs.category_ids, '') AS category_ids,
    a.id AS album_id,
    a.main_photo_id AS album_main_photo_id,
    a.main_image_url AS album_main_image_url,    
    a.images_count AS album_images_count,
    a.indoor_images_count AS album_indoor_images_count,
    a.permalink AS album_permalink,
    IFNULL(fa.temperature, fh.temperature) AS temperature,
    fa.clouds,
    IFNULL(fa.wind, fh.wind) AS wind,
    IFNULL(fa.precipitation, fh.precipitation) AS precipitation,
    fa.symbol,
    fd.sunrise,
    fd.sunset,
    fd.start_sun_altitude,
    fd.end_sun_altitude,
    fd.start_sun_azimuth,
    fd.end_sun_azimuth,
    IFNULL(fa.last_update, UNIX_TIMESTAMP()) AS last_update
  FROM place_event pe
    INNER JOIN place_identifier pi
      ON pe.place_id = pi.id
    LEFT JOIN album a
      ON CONCAT(pi.name, ' ', DATE_FORMAT(FROM_UNIXTIME(pe.start), '%e.%c.%Y')) = a.name
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

DROP VIEW _place_permanent_summary;
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
    pi.excerpt,
    NULL AS trip_id,
    0 AS layover,
    COALESCE(cs.category_ids, '') AS category_ids,
    a.id AS album_id,
    a.main_photo_id AS album_main_photo_id,
    a.main_image_url AS album_main_image_url,    
    a.images_count AS album_images_count,
    a.indoor_images_count AS album_indoor_images_count,
    a.permalink AS album_permalink,
    NULL AS temperature,
    NULL AS clouds,
    NULL AS wind,
    NULL AS precipitation,
    NULL AS symbol,
    NULL AS sunrise,
    NULL AS sunset,
    NULL AS start_sun_altitude,
    NULL AS end_sun_altitude,
    NULL AS start_sun_azimuth,
    NULL AS end_sun_azimuth,
    UNIX_TIMESTAMP() AS last_update
  FROM album a
    LEFT JOIN place_identifier pi
      ON LOCATE(CONCAT(pi.name,' '), CONCAT(a.name,' ')) = 1
    INNER JOIN place_permanent pp
      ON pi.id = pp.place_id
    LEFT JOIN _category_summary cs
      ON pi.id = cs.place_id
    WHERE pp.place_id IS NOT NULL
    ORDER BY UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(SUBSTR(a.name, CHAR_LENGTH(pi.name) + 2), ' 12:00AM'), '%e.%c.%Y %h:%i%p'));

DROP VIEW _place_summary;
CREATE VIEW _place_summary AS
  SELECT *
    FROM _place_event_summary
  UNION SELECT *
    FROM _place_permanent_summary
  ORDER BY START;

DROP TABLE place_summary;
CREATE TABLE place_summary AS
  SELECT *
    FROM _place_summary;