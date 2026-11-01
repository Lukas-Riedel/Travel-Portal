create table stay_watched_event (
  id text primary key,
  name text not null,
  trip_id uuid default null,
  address text default null,
  "start" bigint not null,
  "end" bigint not null
);

alter table stay_watched_event
add constraint fk_stay_watched_event_trip_id foreign key (trip_id) references trip_identifier (id);

create index idx_stay_watched_event_trip_id on stay_watched_event (trip_id);
create index idx_stay_watched_event_start_end on stay_watched_event ("start", "end");
