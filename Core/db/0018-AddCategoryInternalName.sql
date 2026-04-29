alter table category_identifier
add column internal_name text;

update category_identifier
set internal_name = name;

alter table category_identifier
alter column internal_name set not null;

alter table category_identifier
add constraint unique_category_identifier_internal_name unique (internal_name);