delete from highlight_year a
using highlight_year b
where a.ctid < b.ctid
and a.highlight_id = b.highlight_id;

alter table highlight_year
add constraint pk_highlight_year primary key (highlight_id);

delete from highlight_category a
using highlight_category b
where a.ctid < b.ctid
and a.highlight_id = b.highlight_id;

alter table highlight_category 
add constraint pk_highlight_category primary key (highlight_id);