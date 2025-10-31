CREATE TABLE document (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  document_id text NOT NULL,
  issuer text NOT NULL,
  expiration bigint(20),
  PRIMARY KEY (id)
);