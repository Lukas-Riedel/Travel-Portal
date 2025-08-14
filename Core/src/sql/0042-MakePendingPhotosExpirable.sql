ALTER TABLE photo_pending
ADD expiration bigint(20) unsigned NOT NULL;

INSERT INTO pruner (`name`, `query`) VALUES ('PRUNE_EXPIRED_PENDING_PHOTOS', 'DELETE FROM photo_pending WHERE expiration <= UNIX_TIMESTAMP()');