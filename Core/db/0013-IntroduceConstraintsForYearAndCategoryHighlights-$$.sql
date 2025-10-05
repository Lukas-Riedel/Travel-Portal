CREATE TRIGGER highlight_year_insert_trigger_1
BEFORE INSERT ON highlight_year
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT *
    FROM highlight_trip ht
    INNER JOIN trip_identifier ti
      ON ht.id = ti.id
    WHERE ht.highlight_id = NEW.highlight_id
      AND ti.year = NEW.id)
  THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot insert into highlight_year. No appropriate entry in highlight_trip for the year was found.';
  END IF;
END$$

CREATE TRIGGER highlight_trip_delete_trigger_1
AFTER DELETE ON highlight_trip
FOR EACH ROW
BEGIN
  DELETE
  FROM highlight_year
  WHERE highlight_id = OLD.highlight_id;
END$$

CREATE TRIGGER highlight_category_insert_trigger_1
BEFORE INSERT ON highlight_category
FOR EACH ROW
BEGIN
  IF NOT EXISTS (
    SELECT *
    FROM highlight_place hp
    INNER JOIN category_summary cs
      ON hp.id = cs.place_id
    WHERE hp.highlight_id = NEW.highlight_id
      AND FIND_IN_SET(NEW.id, cs.category_ids))
  THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot insert into highlight_category. No appropriate entry in highlight_place for the category was found.';
  END IF;
END$$

CREATE TRIGGER highlight_place_delete_trigger_1
AFTER DELETE ON highlight_place
FOR EACH ROW
BEGIN
  DELETE
  FROM highlight_category
  WHERE highlight_id = OLD.highlight_id;
END$$