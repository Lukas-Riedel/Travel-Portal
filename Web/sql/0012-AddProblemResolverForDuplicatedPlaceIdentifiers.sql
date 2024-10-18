UPDATE definition_problem
SET query = 'SELECT GROUP_CONCAT(name SEPARATOR '', ''), CONCAT(''{"places":['', GROUP_CONCAT(CONCAT(''{"id":'', id, '', "name":"'', name, ''"}'') SEPARATOR '', ''), '']}'') FROM place_identifier GROUP BY latitude, longitude HAVING COUNT(*) > 1'
WHERE name = 'DUPLICATED_PLACE_IDENTIFIERS';