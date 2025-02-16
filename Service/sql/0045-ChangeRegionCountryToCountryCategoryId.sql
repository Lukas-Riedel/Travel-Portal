UPDATE region_geographical rg
SET country = (SELECT ci.id FROM category_identifier ci WHERE ci.name = rg.country AND ci.category = 'COUNTRY')
WHERE country IS NOT NULL;

ALTER TABLE region_geographical
CHANGE COLUMN country country_category_id BIGINT(20) UNSIGNED;

ALTER TABLE region_geographical
ADD CONSTRAINT region_geographical_ibfk_2 FOREIGN KEY (country_category_id) REFERENCES category_identifier (id);

UPDATE definition_problem 
SET query = 'SELECT DISTINCT country, CONCAT(''{"country":"'', country, ''"}'') FROM place_summary WHERE country NOT IN (SELECT DISTINCT ci.name FROM region_geographical rg INNER JOIN category_identifier ci ON rg.country_category_id = ci.id) AND start < UNIX_TIMESTAMP() GROUP BY country HAVING COUNT(DISTINCT name) > 1'
WHERE name = 'COUNTRIES_WITHOUT_GEOGRAPHICAL_REGIONS';

UPDATE definition_problem
SET helper_statements = 'CREATE TEMPORARY TABLE country_specific_category_identifier AS SELECT category_id AS id FROM region_geographical WHERE country_category_id IS NOT NULL;CREATE TEMPORARY TABLE country_with_more_than_one_place AS SELECT country FROM place_summary WHERE start < UNIX_TIMESTAMP() GROUP BY country HAVING COUNT(DISTINCT name) > 1'
WHERE name = 'PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY';

UPDATE definition_problem
SET query = 'SELECT name, CONCAT(''{"placeId":'', id, '',"countryGeoJson":{"type":"FeatureCollection","features":['', (SELECT GROUP_CONCAT(REPLACE(json, ''"geometry":'', CONCAT(''"properties":{"name":"'', ci.name, ''"},"geometry":'')) SEPARATOR '','') FROM region_geographical rg INNER JOIN category_identifier ci ON rg.category_id = ci.id INNER JOIN category_identifier cic ON rg.country_category_id = ci.id WHERE cic.name = data.country), '']}}'') FROM (SELECT pi.name, pi.country, pi.id FROM category_summary cs INNER JOIN place_identifier pi ON cs.place_id = pi.id INNER JOIN place_event pe ON pi.id = pe.place_id WHERE pe.start < UNIX_TIMESTAMP() AND NOT EXISTS(SELECT * FROM country_specific_category_identifier csci WHERE FIND_IN_SET(csci.id, cs.category_ids))) data WHERE country IN (SELECT * FROM country_with_more_than_one_place)'
WHERE name = 'PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY';