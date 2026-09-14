alter table place_identifier
add column tier smallint default null;

create index idx_place_identifier_tier on place_identifier (tier);
