CREATE TABLE expense_voucher (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code text NOT NULL,
  issuer text NOT NULL,
  value double NOT NULL,
  currency text NOT NULL,
  expiration bigint(20),
  PRIMARY KEY (id)
);

ALTER TABLE document
CHANGE document_id code text NOT NULL;