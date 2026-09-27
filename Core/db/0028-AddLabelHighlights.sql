alter table label_identifier
add column main_highlight_id uuid default null;

alter table label_identifier
add constraint fk_label_identifier_main_highlight_id foreign key (main_highlight_id) references highlight_place (highlight_id) on delete set null;

create table highlight_label (
  id uuid not null,
  highlight_id uuid not null
);

alter table highlight_label
add constraint fk_highlight_label_id foreign key (id) references label_identifier (id) on delete cascade,
add constraint fk_highlight_label_highlight_id foreign key (highlight_id) references highlight_identifier (id) on delete cascade;

create index idx_highlight_label_id on highlight_label (id);
create index idx_highlight_label_highlight_id on highlight_label (highlight_id);
