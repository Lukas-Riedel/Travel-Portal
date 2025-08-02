DROP TRIGGER IF EXISTS highlight_year_insert_trigger_1$$
DROP TRIGGER IF EXISTS highlight_trip_delete_trigger_1$$
DROP TRIGGER IF EXISTS highlight_category_insert_trigger_1$$
DROP TRIGGER IF EXISTS highlight_place_delete_trigger_1$$

ALTER TABLE place_identifier
DROP CONSTRAINT place_identifier_ibfk_1$$
ALTER TABLE place_identifier
ADD CONSTRAINT place_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_place (highlight_id) ON DELETE RESTRICT$$

ALTER TABLE trip_identifier
DROP CONSTRAINT trip_identifier_ibfk_1$$
ALTER TABLE trip_identifier
ADD CONSTRAINT trip_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_trip (highlight_id) ON DELETE RESTRICT$$

ALTER TABLE category_identifier
DROP FOREIGN KEY category_identifier_ibfk_1$$
ALTER TABLE category_identifier
ADD CONSTRAINT category_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_place (highlight_id) ON DELETE RESTRICT$$

ALTER TABLE year_identifier
DROP FOREIGN KEY year_identifier_ibfk_1$$
ALTER TABLE year_identifier
ADD CONSTRAINT year_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_trip (highlight_id) ON DELETE RESTRICT$$

DELETE FROM highlight_category$$
DELETE FROM highlight_year$$

CREATE TRIGGER highlight_year_insert_trigger_1
BEFORE INSERT ON highlight_year
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT *
    FROM year_identifier
    WHERE main_highlight_id = NEW.highlight_id
    )
  THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot insert into highlight_year. The highlight_id is set in the main_highlight_id column in year_identifier.';
  END IF;
END$$

CREATE TRIGGER highlight_category_insert_trigger_1
BEFORE INSERT ON highlight_category
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT *
    FROM category_identifier
    WHERE main_highlight_id = NEW.highlight_id
    )
  THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot insert into highlight_category. The highlight_id is set in the main_highlight_id column in category_identifier.';
  END IF;
END$$

UPDATE pruner
SET query = 'DELETE FROM highlight_identifier WHERE id NOT IN (SELECT highlight_id FROM highlight_place) AND id NOT IN (SELECT highlight_id FROM highlight_trip)'
WHERE name = 'PRUNE_HIGHLIGHT_IDENTIFIERS'$$