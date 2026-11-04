alter table label_identifier
drop column keywords;

delete from highlight_label;

alter table highlight_label
drop constraint fk_highlight_label_highlight_id,
add constraint fk_highlight_label_highlight_id foreign key (highlight_id) references highlight_place (highlight_id) on delete cascade;

update label_identifier
set main_highlight_id = null;
