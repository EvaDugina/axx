CREATE TABLE ax_page_subgroup(	-- соотнесение дисциплин с подгруппами
	id serial,
	page_id integer,
	subgroup_id integer, 
	CONSTRAINT ax_page_subgroup_pkey PRIMARY KEY (id)
);

