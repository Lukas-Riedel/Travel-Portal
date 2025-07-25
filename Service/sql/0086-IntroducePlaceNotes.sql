ALTER TABLE note RENAME TO note_identifier;

CREATE TABLE note_trip(
  note_id bigint(20) unsigned NOT NULL,
  id bigint(20) unsigned NOT NULL
);

ALTER TABLE note_trip
ADD CONSTRAINT note_trip_ibfk_1 FOREIGN KEY (note_id) REFERENCES note_identifier (id) ON DELETE CASCADE;

ALTER TABLE note_trip
ADD CONSTRAINT note_trip_ibfk_2 FOREIGN KEY (id) REFERENCES trip_identifier (id) ON DELETE CASCADE;

CREATE TABLE note_place(
  note_id bigint(20) unsigned NOT NULL,
  id bigint(20) unsigned NOT NULL
);

ALTER TABLE note_place
ADD CONSTRAINT note_place_ibfk_1 FOREIGN KEY (note_id) REFERENCES note_identifier (id) ON DELETE CASCADE;

ALTER TABLE note_place
ADD CONSTRAINT note_place_ibfk_2 FOREIGN KEY (id) REFERENCES place_identifier (id) ON DELETE CASCADE;

INSERT INTO note_trip(note_id, id)
SELECT id AS note_id, trip_id AS id FROM note_identifier;

ALTER TABLE note_identifier
DROP FOREIGN KEY note_identifier_ibfk_1;

ALTER TABLE note_identifier
DROP COLUMN trip_id;