update task
set title = description;

alter table task
alter column title set not null;

alter table task
alter column description drop not null;

update task
set description = null;
