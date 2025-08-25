CREATE TABLE fitness_conflict (
  timestamp bigint(20) NOT NULL,
  steps bigint(20) NOT NULL,
  seconds bigint(20) NOT NULL,
  calories double NOT NULL,
  distance double NOT NULL,
  PRIMARY KEY (timestamp)
);

ALTER TABLE fitness_conflict
ADD CONSTRAINT fitness_conflict_ibfk_1 FOREIGN KEY (timestamp) REFERENCES fitness (timestamp) ON DELETE CASCADE;