UPDATE definition_problem
SET query = 'SELECT `code`, CONCAT(''{"code":"'', `code`, ''"}'') FROM airline_code WHERE airline_id IS NULL'
WHERE name = 'UNNAMED_AIRLINES'