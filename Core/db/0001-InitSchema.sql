create table airline_code (
  id uuid primary key default gen_random_uuid(),
  code text not null,
  airline_id uuid default null,
  constraint unique_airline_code_code unique (code)
);

create table airline_identifier (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  logo text default null,
  constraint unique_airline_identifier_name unique (name)
);

create table airport_identifier (
  id uuid primary key default gen_random_uuid(),
  code text not null,
  name text default null,
  latitude double precision not null,
  longitude double precision not null,
  country_category_id uuid not null,
  timezone text not null,
  constraint unique_airport_identifier_code unique (code)
);

create table album (
  id uuid primary key,
  name text not null,
  main_photo_id uuid default null,
  thumbnail_url text default null,
  images_count integer not null,
  indoor_images_count integer not null,
  permalink text not null
);

create table album_identifier (
  id uuid primary key default gen_random_uuid(),
  external_id text not null,
  constraint unique_album_identifier_external_id unique (external_id)
);

create table category (
  category_id uuid not null,
  place_id uuid not null,
  constraint unique_category_category_id_place_id unique (category_id, place_id)
);

create table category_identifier (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  category text not null,
  main_highlight_id uuid default null,
  color text default null,
  unicode text default null,
  public_holidays_calendar text default null,
  constraint unique_category_identifier_name unique (name)
);

create table configuration (
  key text primary key,
  private boolean not null,
  value jsonb not null
);

create table device (
  id text not null,
  name text not null,
  type text not null,
  data jsonb not null,
  user_id text not null,
  last_seen bigint not null
);

create table document (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  code text not null,
  issuer text not null,
  expiration bigint default null
);

create table expense (
  id uuid primary key default gen_random_uuid(),
  trip_id uuid not null,
  type text not null,
  description text default null,
  value double precision not null,
  currency text not null,
  exchange_rate double precision not null,
  timestamp bigint not null,
  subscription_id uuid default null
);

create table expense_subscription (
  id uuid primary key default gen_random_uuid(),
  value double precision not null,
  currency text not null,
  exchange_rate double precision not null,
  description text not null,
  expiration bigint not null
);

create table expense_voucher (
  id uuid primary key default gen_random_uuid(),
  code text not null,
  issuer text not null,
  value double precision not null,
  currency text not null,
  expiration bigint default null
);

create table fitness (
  timestamp bigint primary key,
  last_update bigint not null,
  steps bigint not null,
  seconds bigint not null,
  distance double precision not null
);

create table fitness_conflict (
  timestamp bigint primary key,
  steps bigint not null,
  seconds bigint not null,
  distance double precision not null
);

create table flight_event (
  id text primary key,
  flight text not null,
  trip_id uuid default null,
  "from" text not null,
  "to" text not null,
  "start" bigint not null,
  "end" bigint not null
);

create table flight_log (
  flight text not null,
  registration text not null,
  aircraft text not null,
  from_airport_id uuid not null,
  to_airport_id uuid not null,
  scheduled_departure bigint not null,
  actual_departure bigint not null,
  scheduled_arrival bigint not null,
  actual_arrival bigint not null,
  airline_code_id uuid not null,
  constraint unique_flight_log_flight_scheduled_departure unique (flight, scheduled_departure)
);

create table flight_watched_event (
  id text primary key,
  flight text not null,
  trip_id uuid default null,
  "from" text not null,
  "to" text not null,
  "start" bigint not null,
  "end" bigint not null
);

create table forecast_actual (
  place_id uuid not null,
  timestamp bigint not null,
  temperature double precision not null,
  clouds double precision not null,
  wind double precision not null,
  precipitation double precision not null,
  symbol text not null,
  last_update bigint not null,
  expiration bigint not null,
  constraint unique_forecast_actual_place_id_timestamp unique (place_id, timestamp)
);

create table forecast_daylight (
  place_id uuid not null,
  timestamp bigint not null,
  sunrise bigint not null,
  sunset bigint not null,
  start_sun_altitude double precision not null,
  end_sun_altitude double precision not null,
  start_sun_azimuth double precision not null,
  end_sun_azimuth double precision not null,
  constraint unique_forecast_daylight_place_id_timestamp unique (place_id, timestamp)
);

create table forecast_historical (
  place_id uuid not null,
  timestamp bigint not null,
  temperature double precision not null,
  wind double precision not null,
  precipitation double precision not null,
  constraint unique_forecast_historical_place_id_timestamp unique (place_id, timestamp)
);

create table highlight_category (
  id uuid not null,
  highlight_id uuid not null
);

create table highlight_identifier (
  id uuid primary key default gen_random_uuid(),
  photo_id uuid not null,
  thumbnail_url text default null,
  full_url text default null,
  composition integer default null,
  sky integer default null,
  shadows integer default null,
  circumstances integer default null,
  atmosphere integer default null,
  constraint unique_highlight_identifier_photo_id unique (photo_id)
);

create table highlight_place (
  id uuid not null,
  highlight_id uuid primary key
);

create table highlight_trip (
  id uuid not null,
  highlight_id uuid primary key
);

create table highlight_year (
  id bigint not null,
  highlight_id uuid not null
);

create table label (
  place_id uuid not null,
  label_id uuid not null,
  constraint unique_labeL_place_id_label_id unique (place_id, label_id)
);

create table label_identifier (
  id uuid primary key default gen_random_uuid(),
  name text not null
);

create table note_identifier (
  id uuid primary key default gen_random_uuid(),
  content text not null,
  timestamp bigint not null
);

create table note_place (
  note_id uuid not null,
  id uuid not null,
  constraint unique_note_place_note_id_id unique (note_id, id)
);

create table note_trip (
  note_id uuid not null,
  id uuid not null,
  constraint unique_note_trip_note_id_id unique (note_id, id)
);

create table photo (
  id uuid primary key,
  album_id uuid not null,
  focal_length double precision default null,
  aperture double precision default null,
  shutter_speed double precision default null,
  iso integer default null,
  timestamp bigint not null,
  permalink text not null,
  sun_altitude double precision not null,
  sun_azimuth double precision not null
);

create table photo_identifier (
  id uuid primary key default gen_random_uuid(),
  external_id text not null,
  replaced boolean not null,
  reviewed bigint default null,
  constraint unique_photo_identifier_external_id unique (external_id)
);

create table photo_pending (
  id uuid primary key default gen_random_uuid(),
  album_id uuid not null,
  file_name text not null,
  batch_position integer not null,
  upload_token text not null,
  replaced_photo_id uuid default null,
  expiration bigint not null,
  created bigint not null,
  batch_id text not null,
  expected_batch_size integer not null
);

create table place_candidate (
  place_id uuid primary key
);

create table place_candidate_event (
  place_id uuid not null,
  trip_id uuid not null,
  "start" bigint not null,
  "end" bigint not null
);

create table place_event (
  id text primary key,
  place_id uuid not null,
  trip_id uuid default null,
  "start" bigint not null,
  "end" bigint not null,
  layover boolean not null
);

create table place_identifier (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  latitude double precision not null,
  longitude double precision not null,
  country_category_id uuid not null,
  timezone text not null,
  main_highlight_id uuid default null,
  excerpt text default null,
  score double precision not null,
  quality double precision default null,
  constraint unique_place_identifier_name_country_category_id unique (name, country_category_id)
);

create table place_permanent (
  place_id uuid primary key
);

create table region_area (
  category_id uuid primary key,
  area double precision not null
);

create table region_composite (
  category_id uuid not null,
  subject_category_id uuid not null,
  included boolean not null
);

create table region_geographical (
  category_id uuid not null,
  country_category_id uuid default null,
  json jsonb not null,
  radius integer not null
);

create table scheduler (
  action text primary key,
  last_triggered bigint not null
);

create table stay_event (
  id text primary key,
  name text not null,
  trip_id uuid default null,
  address text default null,
  "start" bigint not null,
  "end" bigint not null
);

create table tracking (
  id uuid primary key default gen_random_uuid(),
  type text not null,
  hours numeric(5,2) not null,
  description text not null,
  timestamp bigint not null
);

create table trip_candidate (
  trip_id uuid primary key
);

create table trip_event (
  id text primary key,
  trip_id uuid not null,
  "start" bigint not null,
  "end" bigint not null,
  constraint unique_trip_event_trip_id unique (trip_id)
);

create table trip_identifier (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  year bigint default null,
  main_highlight_id uuid default null,
  constraint unique_trip_identifier_name_year unique (name, year)
);

create table year_identifier (
  id bigint primary key,
  main_highlight_id uuid default null
);

alter table airline_code
add constraint fk_airline_code_airline_id foreign key (airline_id) references airline_identifier (id);

alter table airport_identifier
add constraint fk_airport_identifier_country_category_id foreign key (country_category_id) references category_identifier (id);

alter table album
add constraint fk_album_id foreign key (id) references album_identifier (id),
add constraint fk_album_main_photo_id foreign key (main_photo_id) references photo_identifier (id) on delete set null;

alter table category
add constraint fk_category_place_id foreign key (place_id) references place_identifier (id) on delete cascade,
add constraint fk_category_category_id foreign key (category_id) references category_identifier (id) on delete cascade;

alter table category_identifier
add constraint fk_category_identifier_main_highlight_id foreign key (main_highlight_id) references highlight_place (highlight_id) on delete set null;

alter table expense
add constraint fk_expense_trip_id foreign key (trip_id) references trip_identifier (id) on delete cascade,
add constraint fk_expense_subscription_id foreign key (subscription_id) references expense_subscription (id) on delete set null;

alter table fitness_conflict
add constraint fk_fitness_conflict_timestamp foreign key (timestamp) references fitness (timestamp) on delete cascade;

alter table flight_event
add constraint fk_flight_event_trip_id foreign key (trip_id) references trip_identifier (id);

alter table flight_log
add constraint fk_flight_log_from_airport_id foreign key (from_airport_id) references airport_identifier (id),
add constraint fk_flight_log_to_airport_id foreign key (to_airport_id) references airport_identifier (id),
add constraint fk_flight_log_airline_code_id foreign key (airline_code_id) references airline_code (id);

alter table flight_watched_event
add constraint fk_flight_watched_event_trip_id foreign key (trip_id) references trip_identifier (id);

alter table forecast_actual
add constraint fk_forecast_actual_place_id foreign key (place_id) references place_identifier (id) on delete cascade;

alter table forecast_daylight
add constraint fk_forecast_daylight_place_id foreign key (place_id) references place_identifier (id) on delete cascade;

alter table forecast_historical
add constraint fk_forecast_historical_place_id foreign key (place_id) references place_identifier (id) on delete cascade;

alter table highlight_category
add constraint fk_highlight_category_id foreign key (id) references category_identifier (id) on delete cascade,
add constraint fk_highlight_category_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade;

alter table highlight_identifier
add constraint fk_highlight_identifier_photo_id foreign key (photo_id) references photo_identifier (id) on delete cascade;

alter table highlight_place
add constraint fk_highlight_place_id foreign key (id) references place_identifier (id) on delete cascade,
add constraint fk_highlight_place_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade;

alter table highlight_trip
add constraint fk_highlight_trip_id foreign key (id) references trip_identifier (id) on delete cascade,
add constraint fk_highlight_trip_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade;

alter table highlight_year
add constraint fk_highlight_year_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade,
add constraint fk_highlight_year_id foreign key (id) references year_identifier (id) on delete cascade;

alter table label
add constraint fk_label_place_id foreign key (place_id) references place_identifier (id) on delete cascade,
add constraint fk_label_label_id foreign key (label_id) references label_identifier (id);

alter table note_place
add constraint fk_note_place_note_id foreign key (note_id) references note_identifier (id) on delete cascade,
add constraint fk_note_place_id foreign key (id) references place_identifier (id) on delete cascade;

alter table note_trip
add constraint fk_note_trip_note_id foreign key (note_id) references note_identifier (id) on delete cascade,
add constraint fk_note_trip_id foreign key (id) references trip_identifier (id) on delete cascade;

alter table photo
add constraint fk_photo_album_id foreign key (album_id) references album_identifier (id) on delete cascade,
add constraint fk_photo_id foreign key (id) references photo_identifier (id);

alter table photo_pending
add constraint fk_photo_pending_album_id foreign key (album_id) references album_identifier (id) on delete cascade,
add constraint fk_photo_pending_replaced_photo_id foreign key (replaced_photo_id) references photo_identifier (id) on delete set null;

alter table place_candidate
add constraint fk_place_candidate_place_id foreign key (place_id) references place_identifier (id);

alter table place_candidate_event
add constraint fk_place_candidate_event_place_id foreign key (place_id) references place_identifier (id),
add constraint fk_place_candidate_event_trip_id foreign key (trip_id) references trip_candidate (trip_id);

alter table place_event
add constraint fk_place_event_trip_id foreign key (trip_id) references trip_identifier (id);

alter table place_identifier
add constraint fk_place_identifier_main_highlight_id foreign key (main_highlight_id) references highlight_place (highlight_id) on delete set null,
add constraint fk_place_identifier_country_category_id foreign key (country_category_id) references category_identifier (id);

alter table place_permanent
add constraint fk_place_permanent_place_id foreign key (place_id) references place_identifier (id);

alter table region_area
add constraint fk_region_area_category_id foreign key (category_id) references category_identifier (id) on delete cascade;

alter table region_composite
add constraint fk_region_composite_category_id foreign key (category_id) references category_identifier (id),
add constraint fk_region_composite_subject_category_id foreign key (subject_category_id) references category_identifier (id);

alter table region_geographical
add constraint fk_region_geographical_category_id foreign key (category_id) references category_identifier (id),
add constraint fk_region_geographical_country_category_id foreign key (country_category_id) references category_identifier (id);

alter table stay_event
add constraint fk_stay_event_trip_id foreign key (trip_id) references trip_identifier (id);

alter table trip_candidate
add constraint fk_trip_candidate_trip_id foreign key (trip_id) references trip_identifier (id);

alter table trip_event
add constraint fk_trip_event_trip_id foreign key (trip_id) references trip_identifier (id);

alter table trip_identifier
add constraint fk_trip_identifier_main_highlight_id foreign key (main_highlight_id) references highlight_trip (highlight_id) on delete set null,
add constraint fk_trip_identifier_year foreign key (year) references year_identifier (id) on delete cascade;

alter table year_identifier
add constraint fk_year_identifier_main_highlight_id foreign key (main_highlight_id) references highlight_trip (highlight_id) on delete set null;

create index idx_airline_code_airline_id on airline_code (airline_id);
create index idx_airport_identifier_country_category_id on airport_identifier (country_category_id);
create index idx_album_main_photo_id on album (main_photo_id);
create index idx_album_name on album (name);
create index idx_category_place_id on category (place_id);
create index idx_category_identifier_category on category_identifier (category);
create index idx_device_type on device (type);
create index idx_expense_trip_id on expense (trip_id);
create index idx_expense_timestamp on expense (timestamp);
create index idx_expense_subscription_id on expense (subscription_id);
create index idx_fitness_timestamp on fitness (timestamp);
create index idx_fitness_last_update on fitness (last_update);
create index idx_flight_event_trip_id on flight_event (trip_id);
create index idx_flight_event_start_end on flight_event ("start", "end");
create index idx_flight_log_from_airport_id on flight_log (from_airport_id);
create index idx_flight_log_to_airport_id on flight_log (to_airport_id);
create index idx_flight_watched_event_trip_id on flight_watched_event (trip_id);
create index idx_flight_watched_event_start_end on flight_watched_event ("start", "end");
create index idx_highlight_category_id on highlight_category (id);
create index idx_highlight_category_highlight_id on highlight_category (highlight_id);
create index idx_highlight_place_id on highlight_place (id);
create index idx_highlight_place_highlight_id on highlight_place (highlight_id);
create index idx_highlight_trip_id on highlight_trip (id);
create index idx_highlight_trip_highlight_id on highlight_trip (highlight_id);
create index idx_highlight_year_id on highlight_year (id);
create index idx_highlight_year_highlight_id on highlight_year (highlight_id);
create index idx_label_identifier_name on label_identifier (name);
create index idx_photo_album_id on photo (album_id);
create index idx_photo_pending_album_id on photo_pending (album_id);
create index idx_place_candidate_event_place_id on place_candidate_event (place_id);
create index idx_place_candidate_event_trip_id on place_candidate_event (trip_id);
create index idx_place_candidate_event_start_end on place_candidate_event ("start", "end");
create index idx_place_event_place_id on place_event (place_id);
create index idx_place_event_trip_id on place_event (trip_id);
create index idx_place_event_start_end on place_event ("start", "end");
create index idx_place_identifier_main_highlight_id on place_identifier (main_highlight_id);
create index idx_place_identifier_score on place_identifier (score);
create index idx_place_identifier_quality on place_identifier (quality);
create index idx_region_composite_category_id on region_composite (category_id);
create index idx_region_composite_subject_category_id on region_composite (subject_category_id);
create index idx_region_geographical_category_id on region_geographical (category_id);
create index idx_region_geographical_country_category_id on region_geographical (country_category_id);
create index idx_stay_event_trip_id on stay_event (trip_id);
create index idx_stay_event_start_end on stay_event ("start", "end");
create index idx_tracking_type on tracking (type);
create index idx_trip_event_start_end on trip_event ("start", "end");
create index idx_trip_identifier_main_highlight_id on trip_identifier (main_highlight_id);
create index idx_year_identifier_main_highlight_id on year_identifier (main_highlight_id);
