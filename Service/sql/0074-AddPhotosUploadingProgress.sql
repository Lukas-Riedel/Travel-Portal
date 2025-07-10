ALTER TABLE photo_pending
ADD created bigint(20) NOT NULL,
ADD batch_id text NOT NULL,
ADD expected_batch_size int(11) NOT NULL,
CHANGE position batch_position int(11) NOT NULL;