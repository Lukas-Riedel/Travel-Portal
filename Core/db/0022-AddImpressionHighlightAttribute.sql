alter table highlight_identifier
add column impression integer default null;

update highlight_identifier
set impression = 100
where impression is null;

alter table highlight_identifier
alter column impression set not null;