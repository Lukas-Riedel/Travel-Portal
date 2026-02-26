create extension if not exists vector;

alter table photo_identifier
add column embedding vector(768) default null;