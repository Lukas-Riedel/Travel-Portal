ALTER TABLE users
MODIFY password text;

ALTER TABLE users
MODIFY api_key text NOT NULL;