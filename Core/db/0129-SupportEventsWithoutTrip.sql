ALTER TABLE flight_event
DROP FOREIGN KEY flight_event_ibfk_1;

ALTER TABLE flight_event
MODIFY COLUMN trip_id bigint(20) unsigned;

ALTER TABLE flight_event
ADD CONSTRAINT flight_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE flight_watched_event
DROP FOREIGN KEY flight_watched_event_ibfk_1;

ALTER TABLE flight_watched_event
MODIFY COLUMN trip_id bigint(20) unsigned;

ALTER TABLE flight_watched_event
ADD CONSTRAINT flight_watched_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE place_event
DROP FOREIGN KEY place_event_ibfk_1;

ALTER TABLE place_event
MODIFY COLUMN trip_id bigint(20) unsigned;

ALTER TABLE place_event
ADD CONSTRAINT place_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE stay_event
DROP FOREIGN KEY stay_event_ibfk_1;

ALTER TABLE stay_event
MODIFY COLUMN trip_id bigint(20) unsigned;

ALTER TABLE stay_event
ADD CONSTRAINT stay_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

ALTER TABLE trip_event
DROP FOREIGN KEY trip_event_ibfk_1;

ALTER TABLE trip_event
MODIFY COLUMN trip_id bigint(20) unsigned;

ALTER TABLE trip_event
ADD CONSTRAINT trip_event_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);