CREATE TABLE year_identifier (
  id bigint(20) unsigned NOT NULL,
  main_highlight_id bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY main_highlight_id (main_highlight_id),
  CONSTRAINT year_identifier_ibfk_1 FOREIGN KEY (main_highlight_id) REFERENCES highlight_year (highlight_id) ON DELETE SET NULL
);

INSERT INTO year_identifier (id)
  SELECT DISTINCT year
  FROM trip_identifier
  WHERE year IS NOT NULL
  ORDER BY year ASC;

ALTER TABLE highlight_year
ADD CONSTRAINT highlight_year_ibfk_2 FOREIGN KEY (id) REFERENCES year_identifier (id) ON DELETE CASCADE;

ALTER TABLE trip_identifier
MODIFY year bigint(20) unsigned;

ALTER TABLE trip_identifier
ADD CONSTRAINT trip_identifier_ibfk_2 FOREIGN KEY (year) REFERENCES year_identifier (id) ON DELETE CASCADE;
  
INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_YEAR_IDENTIFIERS', 'DELETE FROM year_identifier WHERE id NOT IN (SELECT year FROM trip_identifier)');

DELETE
FROM pruner
WHERE name = 'PRUNE_YEAR_STATISTICS';

ALTER TABLE cache_statistics_year
ADD CONSTRAINT cache_statistics_year_ibfk_1 FOREIGN KEY (id) REFERENCES year_identifier (id) ON DELETE CASCADE;