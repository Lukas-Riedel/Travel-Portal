alter table task
add column title text default null;

alter table task
add column auto_delete boolean not null default false;
