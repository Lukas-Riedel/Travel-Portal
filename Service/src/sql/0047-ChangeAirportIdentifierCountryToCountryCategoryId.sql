UPDATE airport_identifier ai
SET country = (SELECT ci.id FROM category_identifier ci WHERE ci.name = ai.country AND ci.category = 'COUNTRY')
WHERE country IS NOT NULL;

ALTER TABLE airport_identifier
CHANGE COLUMN country country_category_id BIGINT(20) UNSIGNED NOT NULL;

ALTER TABLE airport_identifier
ADD CONSTRAINT airport_identifier_ibfk_1 FOREIGN KEY (country_category_id) REFERENCES category_identifier (id);