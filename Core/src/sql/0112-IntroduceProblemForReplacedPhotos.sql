ALTER TABLE photo_identifier
ADD replaced tinyint(4);

UPDATE photo_identifier
SET replaced = 0;

ALTER TABLE photo_identifier
CHANGE replaced replaced tinyint(4) NOT NULL;