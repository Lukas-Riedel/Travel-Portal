alter table airport_identifier
add column coordinates geography(Point, 4326)
generated always as (ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography) stored;

alter table place_identifier
add column coordinates geography(Point, 4326)
generated always as (ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography) stored;

create index idx_airport_identifier_coordinates on airport_identifier using gist(coordinates);
create index idx_place_identifier_coordinates on place_identifier using gist(coordinates);