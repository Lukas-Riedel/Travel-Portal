alter table label_identifier
add column keywords jsonb default null;

update label_identifier
set keywords = jsonb_build_array(description)
where description is not null;

alter table label_identifier
drop column description;
