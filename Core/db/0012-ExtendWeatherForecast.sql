delete from forecast_actual;

alter table forecast_actual
rename column clouds to clouds_total;

alter table forecast_actual
add column clouds_low double precision,
add column clouds_medium double precision,
add column clouds_high double precision,
add column humidity double precision not null;