alter table place_identifier
add column altitude integer;

update place_identifier
set altitude = 0;

alter table place_identifier
alter column altitude set not null;