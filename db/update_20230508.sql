ALTER TABLE ax_message ADD COLUMN resended_from_id integer;

CREATE TABLE students_to_subgroups	(	-- соотнесение студентов с подгруппами
	id serial,
	student_id integer,	-- --> students
	subgroup integer, 	-- --> 1 - первая подгруппа, 2 - вторая подгруппа, ...
	CONSTRAINT students_to_groups_pkey PRIMARY KEY (id)
); 
ALTER TABLE students_to_subgroups ADD UNIQUE (student_id);

-- Раскомментировать, если не на сервере
-- ALTER TABLE students_to_groups OWNER TO postgres;

INSERT INTO students_to_subgroups(student_id, group_id) VALUES 
(-1, 1), (-2, 1), (-3, 2), (-4, 2)