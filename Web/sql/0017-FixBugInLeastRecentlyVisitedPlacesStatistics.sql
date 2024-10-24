UPDATE definition_statistics
SET query = 'SELECT name, last_visit FROM (SELECT *, MAX(end) AS last_visit FROM place_summary WHERE id IS NOT NULL AND end <= UNIX_TIMESTAMP() GROUP BY place_id) p WHERE start >= {{start}} AND end <= {{end}} AND IS_IN_CATEGORY(place_id, {{category}}) ORDER BY last_visit ASC'
WHERE name = 'LEAST_RECENTLY_VISITED_PLACES';