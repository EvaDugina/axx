ALTER TABLE ax_message ADD COLUMN resended_from_id integer;

CREATE TABLE students_to_subgroups	(	-- соотнесение студентов с подгруппами
	id serial,
	student_id integer,	-- --> students
	subgroup integer, 	-- --> 1 - первая подгруппа, 2 - вторая подгруппа, ...
	CONSTRAINT students_to_subgroups_pkey PRIMARY KEY (id)
); 
ALTER TABLE students_to_subgroups ADD UNIQUE (student_id);

-- Раскомментировать, если не на сервере
-- ALTER TABLE students_to_subgroups OWNER TO postgres;

INSERT INTO students_to_subgroups(student_id, subgroup) VALUES 
(-1, 1), (-2, 1), (-3, 2), (-4, 2);

ALTER TABLE ax_file ADD COLUMN status integer;
UPDATE ax_file SET status = 0;
 -- status:
 -- 0 - не удалённый файл
 -- 2 - удалённый файл