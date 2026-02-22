drop index idx_region_area_category_id;
drop index idx_photo_identifier_external_id;

create index idx_region_area_category_area_desc on region_area (category_id, area desc);