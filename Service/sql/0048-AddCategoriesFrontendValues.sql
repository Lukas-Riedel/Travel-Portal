ALTER TABLE category_identifier
ADD color text;

ALTER TABLE category_identifier
ADD unicode text;

ALTER TABLE category_identifier
ADD public_holidays_calendar text;

UPDATE category_identifier ci
SET color = (SELECT SUBSTRING(value, LOCATE('"color":"', value) + 9, LOCATE('"', value, LOCATE('"color":"', value) + 9) - (LOCATE('"color":"', value) + 9)) FROM configuration WHERE type = 'COUNTRIES' AND `key` = ci.name)
WHERE category = 'COUNTRY';

UPDATE category_identifier ci
SET unicode = (SELECT SUBSTRING(value, LOCATE('"unicode":"', value) + 11, LOCATE('"', value, LOCATE('"unicode":"', value) + 11) - (LOCATE('"unicode":"', value) + 11)) FROM configuration WHERE type = 'COUNTRIES' AND `key` = ci.name)
WHERE category = 'COUNTRY';

UPDATE category_identifier ci
SET public_holidays_calendar = (SELECT SUBSTRING(value, LOCATE('"publicHolidaysCalendar":"', value) + 26, LOCATE('"', value, LOCATE('"publicHolidaysCalendar":"', value) + 26) - (LOCATE('"publicHolidaysCalendar":"', value) + 26)) FROM configuration WHERE type = 'COUNTRIES' AND `key` = ci.name)
WHERE category = 'COUNTRY';