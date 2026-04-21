alter table photo_pending 
add constraint idx_photo_pending_batch_id_position 
unique (batch_id, batch_position);