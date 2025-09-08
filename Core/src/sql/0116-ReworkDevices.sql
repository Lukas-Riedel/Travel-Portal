DROP TABLE device;

CREATE TABLE device (
    id text NOT NULL,
    name text NOT NULL,
    type text NOT NULL,
    data text NOT NULL,
    user_id bigint(20) unsigned NOT NULL,
    last_seen bigint(20) NOT NULL
);

ALTER TABLE device
ADD CONSTRAINT device_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;