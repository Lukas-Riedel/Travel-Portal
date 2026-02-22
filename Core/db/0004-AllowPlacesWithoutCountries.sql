alter table place_identifier
alter column country_category_id drop not null;

alter table airport_identifier
alter column country_category_id drop not null;