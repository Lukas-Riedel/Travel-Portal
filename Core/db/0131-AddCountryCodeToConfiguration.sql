UPDATE configuration
SET value = REPLACE(value, '"timezone":', '"countryCode":"CZ","timezone":')
WHERE `key` = 'homeLocation'