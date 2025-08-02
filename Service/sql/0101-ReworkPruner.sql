CREATE TABLE trip_candidate (
  trip_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY (trip_id)
);

ALTER TABLE trip_candidate
ADD CONSTRAINT trip_candidate_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trip_identifier (id);

INSERT INTO trip_candidate (trip_id)
SELECT DISTINCT trip_id FROM place_candidate_event;

ALTER TABLE place_candidate_event
DROP CONSTRAINT place_candidate_event_ibfk_2;

ALTER TABLE place_candidate_event
ADD CONSTRAINT place_candidate_event_ibfk_2 FOREIGN KEY (trip_id) REFERENCES trip_candidate (trip_id);

INSERT INTO scheduler (action, last_triggered) VALUES ('CONSOLIDATE_TIME_TRACKING_EVENTS', UNIX_TIMESTAMP());

DROP TABLE pruner;