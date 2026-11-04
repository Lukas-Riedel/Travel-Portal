drop table label_place;

create table label (
  place_id uuid not null,
  label_id uuid not null,
  constraint unique_label_place_id_label_id unique (place_id, label_id)
);

alter table label
add constraint fk_label_place_id foreign key (place_id) references place_identifier (id) on delete cascade,
add constraint fk_label_label_id foreign key (label_id) references label_identifier (id);

alter table highlight_label
drop constraint fk_highlight_label_highlight_id,
add constraint fk_highlight_label_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade;
