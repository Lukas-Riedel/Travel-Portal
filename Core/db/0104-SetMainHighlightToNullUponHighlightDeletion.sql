ALTER TABLE place_identifier
DROP CONSTRAINT place_identifier_ibfk_1;
ALTER TABLE place_identifier
ADD CONSTRAINT place_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_place (highlight_id) ON DELETE SET NULL;

ALTER TABLE trip_identifier
DROP CONSTRAINT trip_identifier_ibfk_1;
ALTER TABLE trip_identifier
ADD CONSTRAINT trip_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_trip (highlight_id) ON DELETE SET NULL;

ALTER TABLE category_identifier
DROP FOREIGN KEY category_identifier_ibfk_1;
ALTER TABLE category_identifier
ADD CONSTRAINT category_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_place (highlight_id) ON DELETE SET NULL;

ALTER TABLE year_identifier
DROP FOREIGN KEY year_identifier_ibfk_1;
ALTER TABLE year_identifier
ADD CONSTRAINT year_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_trip (highlight_id) ON DELETE SET NULL;