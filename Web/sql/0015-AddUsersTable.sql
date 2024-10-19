CREATE TABLE users (
  username text NOT NULL,
  password text NOT NULL,
  api_key text,
  roles set('USER','ADMIN') NOT NULL
);

INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('BEARER_TOKEN', 'private', 'validity', '3600');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('BEARER_TOKEN', 'private', 'privateKey', 'PRIVATE_KEY');
INSERT INTO configuration (`type`, `levels`, `key`, `value`) VALUES ('BEARER_TOKEN', 'private', 'cipher', 'aes-256-cbc');