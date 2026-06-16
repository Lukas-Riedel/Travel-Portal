create table task (
  id uuid primary key default gen_random_uuid(),
  trip_id uuid not null,
  description text not null,
  priority integer not null,
  deadline bigint default null,
  last_notification bigint default null
);

alter table task
add constraint fk_task_trip_id foreign key (trip_id) references trip_identifier (id) on delete cascade;