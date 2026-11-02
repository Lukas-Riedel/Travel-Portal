drop table label_highlight;

create table label_place (
  label_id uuid not null,
  place_id uuid not null,
  constraint unique_label_place_label_id_place_id unique (label_id, place_id)
);

alter table label_place
add constraint fk_label_place_label_id foreign key (label_id) references label_identifier (id) on delete cascade,
add constraint fk_label_place_place_id foreign key (place_id) references place_identifier (id) on delete cascade;

create index idx_label_place_label_id on label_place (label_id);
create index idx_label_place_place_id on label_place (place_id);
