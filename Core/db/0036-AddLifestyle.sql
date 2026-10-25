create table lifestyle (
  id uuid primary key default gen_random_uuid(),
  timestamp bigint not null,
  type text not null,
  content text not null,
  calories double precision default null
);

create unique index idx_lifestyle_timestamp_type on lifestyle (timestamp, type);

create index idx_lifestyle_timestamp on lifestyle (timestamp);
create index idx_lifestyle_type on lifestyle (type);