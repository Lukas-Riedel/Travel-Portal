create table label_highlight (
  label_id uuid not null,
  highlight_id uuid not null,
  similarity double precision not null,
  constraint unique_label_highlight_label_id_highlight_id unique (label_id, highlight_id)
);

alter table label_highlight
add constraint fk_label_highlight_label_id foreign key (label_id) references label_identifier (id) on delete cascade,
add constraint fk_label_highlight_highlight_id foreign key (highlight_id) references highlight_place (highlight_id) on delete cascade;

create index idx_label_highlight_label_id on label_highlight (label_id);
create index idx_label_highlight_highlight_id on label_highlight (highlight_id);
