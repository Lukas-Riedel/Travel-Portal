CREATE TABLE device (
    token text NOT NULL,
    type ENUM('PORTAL','AGENT','BRIDGEX') NOT NULL,
    last_seen bigint(20) NOT NULL
);

INSERT INTO scheduler (action, last_triggered) VALUES ('UNREGISTER_INACTIVE_DEVICES', UNIX_TIMESTAMP());