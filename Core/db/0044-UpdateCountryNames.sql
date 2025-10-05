UPDATE configuration
SET `key` = 'Myanmar'
WHERE type = 'COUNTRIES'
    AND `key` = 'Myanmar (Barma)';

UPDATE configuration
SET value = 'Myanmar'
WHERE type = 'COUNTRY_NAMES'
    AND value = 'Myanmar (Barma)';

UPDATE configuration
SET `key` = 'Macao'
WHERE type = 'COUNTRIES'
    AND `key` = 'Macao SAR Čína';

UPDATE configuration
SET value = 'Macao'
WHERE type = 'COUNTRY_NAMES'
    AND value = 'Macao SAR Čína';

UPDATE configuration
SET `key` = 'Východní Timor'
WHERE type = 'COUNTRIES'
    AND `key` = 'Timor-Leste';

UPDATE configuration
SET value = 'Východní Timor'
WHERE type = 'COUNTRY_NAMES'
    AND value = 'Timor-Leste';