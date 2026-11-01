alter table label_identifier
add column description text default null;

update label_identifier
set description = name
where description is null;
