DROP VIEW _expense_summary;
CREATE VIEW _expense_summary AS
  SELECT id,
    trip_id,
    type,
    GET_EXPENSE_DESCRIPTION_WITH_SUBSCRIPTION(description, subscription_id) AS description,
    value,
    currency,
    exchange_rate,
    GET_MAIN_CURRENCY_VALUE_WITH_SUBSCRIPTION(value, exchange_rate, subscription_id) AS main_currency_value
  FROM expense
  ORDER BY timestamp,
    id;
    
DROP TABLE expense_summary;
CREATE TABLE expense_summary AS
  SELECT *
  FROM _expense_summary;

ALTER TABLE cache_exchange_rate
CHANGE COLUMN last_update expiration BIGINT(20) UNSIGNED NOT NULL;

UPDATE pruner
SET query = 'DELETE FROM cache_exchange_rate WHERE expiration < UNIX_TIMESTAMP()'
WHERE name = 'PRUNE_EXCHANGE_RATES';

INSERT INTO configuration (type, levels, `key`, value) VALUES ('EXCHANGE_RATE_API_KEY', 'private', NULL, 'API_KEY');