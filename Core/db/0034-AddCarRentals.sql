create table car_rental_event (
  id text primary key,
  model text not null,
  trip_id uuid default null,
  "start" bigint not null,
  "end" bigint not null
);

alter table car_rental_event
add constraint fk_car_rental_event_trip_id foreign key (trip_id) references trip_identifier (id);

create index idx_car_rental_event_trip_id on car_rental_event (trip_id);
create index idx_car_rental_event_start_end on car_rental_event ("start", "end");
