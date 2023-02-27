CREATE TABLE ax_file (		
	id serial,
	type integer,		-- тип файла (из ax_task_file: 0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста | из ax_solution_file: 10 - просто файл с результатами, 11 - файл проекта)
	name text,		-- отображаемое имя файла
	download_url text,	-- URL для скачивания, если файл лежит на диске 
	full_text text,		-- полный текст файла, если он лежит в БД
	CONSTRAINT ax_file_pkey PRIMARY KEY (id)
); ALTER TABLE ax_file OWNER TO postgres;

-- TODO: ИСПРАВИТЬ НАЗВАНИЕ НА ax_task_file, удалить лишние таблицы
CREATE TABLE ax_task_files (	-- файлы, прикреплённые к конкретному task
	id serial,
	task_id integer, -- --> ax_task
	file_id integer, -- --> ax_file
	CONSTRAINT ax_task_files_pkey PRIMARY KEY (id)
); ALTER TABLE ax_task_files OWNER TO postgres;

CREATE TABLE ax_message_file (	-- файлы, прикреплённые к конкретному message
	id serial,
	message_id integer,	-- --> ax_message
	file_id integer, -- --> ax_file
	CONSTRAINT ax_message_file_pkey PRIMARY KEY (id)
); ALTER TABLE ax_message_file OWNER TO postgres;

CREATE TABLE ax_commit_file (	-- файлы, прикреплённые к конкретному commit
	id serial,
	commit_id integer,	-- --> ax_commit
	file_id integer, -- --> ax_file
	CONSTRAINT ax_commit_file_pkey PRIMARY KEY (id)
); ALTER TABLE ax_commit_file OWNER TO postgres;
