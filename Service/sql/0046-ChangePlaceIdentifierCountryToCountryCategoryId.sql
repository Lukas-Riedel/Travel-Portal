UPDATE place_identifier pi
SET country = (SELECT ci.id FROM category_identifier ci WHERE ci.name = pi.country AND ci.category = 'COUNTRY')
WHERE country IS NOT NULL;

ALTER TABLE place_identifier
CHANGE COLUMN country country_category_id BIGINT(20) UNSIGNED NOT NULL;

ALTER TABLE place_identifier
ADD CONSTRAINT place_identifier_ibfk_2 FOREIGN KEY (country_category_id) REFERENCES category_identifier (id);

DROP VIEW _place_event_summary;
CREATE VIEW _place_event_summary AS
  SELECT pe.id,
    pi.id AS place_id,
    pi.name,
    ci.name AS country,
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
    a.thumbnail_url AS album_thumbnail_url,    
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
    INNER JOIN category_identifier ci
      ON pi.country_category_id = ci.id
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
    ci.name AS country,
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
    a.thumbnail_url AS album_thumbnail_url,    
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
    INNER JOIN category_identifier ci
      ON pi.country_category_id = ci.id  
    INNER JOIN place_permanent pp
      ON pi.id = pp.place_id
    LEFT JOIN _category_summary cs
      ON pi.id = cs.place_id
    WHERE pp.place_id IS NOT NULL
    ORDER BY UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(SUBSTR(a.name, CHAR_LENGTH(pi.name) + 2), ' 12:00AM'), '%e.%c.%Y %h:%i%p'));

UPDATE pruner
SET query = 'UPDATE configuration SET levels = ''public'' WHERE type = ''COUNTRIES'' AND `key` NOT IN (SELECT ci.name FROM place_identifier pi INNER JOIN category_identifier ci ON pi.country_category_id = ci.id)'
WHERE name = 'LIMIT_COUNTRIES_CONFIGURATION_VISIBILITY';

UPDATE definition_problem
SET query = 'SELECT name, CONCAT(''{"placeId":'', id, '',"countryGeoJson":{"type":"FeatureCollection","features":['', (SELECT GROUP_CONCAT(REPLACE(json, ''"geometry":'', CONCAT(''"properties":{"name":"'', ci.name, ''"},"geometry":'')) SEPARATOR '','') FROM region_geographical rg INNER JOIN category_identifier ci ON rg.category_id = ci.id INNER JOIN category_identifier cic ON rg.country_category_id = ci.id WHERE cic.name = data.country), '']}}'') FROM (SELECT pi.name, ci.name AS country, pi.id FROM category_summary cs INNER JOIN place_identifier pi ON cs.place_id = pi.id INNER JOIN category_identifier ci ON pi.country_category_id = ci.id INNER JOIN place_event pe ON pi.id = pe.place_id WHERE pe.start < UNIX_TIMESTAMP() AND NOT EXISTS(SELECT * FROM country_specific_category_identifier csci WHERE FIND_IN_SET(csci.id, cs.category_ids))) data WHERE country IN (SELECT * FROM country_with_more_than_one_place)'
WHERE name = 'PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY';