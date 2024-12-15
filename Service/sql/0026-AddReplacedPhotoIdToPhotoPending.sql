ALTER TABLE photo_pending
ADD replaced_photo_id bigint(20) unsigned;

ALTER TABLE photo_pending
ADD CONSTRAINT photo_pending_ibfk_2 FOREIGN KEY (replaced_photo_id) REFERENCES photo_identifier (id) ON DELETE SET NULL;

ALTER TABLE photo_pending
MODIFY position int(11);