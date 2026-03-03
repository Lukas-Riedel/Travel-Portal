update highlight_identifier
set composition = 1
where composition is null;

update highlight_identifier
set sky = 1
where sky is null;

update highlight_identifier
set shadows = 1
where shadows is null;

update highlight_identifier
set circumstances = 1
where circumstances is null;

update highlight_identifier
set atmosphere = 1
where atmosphere is null;

alter table highlight_identifier
alter column composition set not null,
alter column sky set not null,
alter column shadows set not null,
alter column circumstances set not null,
alter column atmosphere set not null;