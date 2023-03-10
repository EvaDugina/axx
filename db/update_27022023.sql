CREATE TABLE ax_file (		
	id serial,
  ax_attachment_id integer,
	type integer,		-- тип файла (из ax_task_file: 0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста | из ax_solution_file: 10 - просто файл с результатами, 11 - файл проекта)
	file_name text,		-- отображаемое имя файла
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


-- Перемещение файлов из таблицы ax_message_attachment
INSERT INTO ax_file (type, ax_attachment_id, file_name, download_url, full_text) 
SELECT 0, id, file_name, download_url, full_text FROM ax_message_attachment;

INSERT INTO ax_message_file (message_id, file_id)
SELECT message_id, ax_file.id FROM ax_message_attachment 
INNER JOIN ax_file ON ax_file.ax_attachment_id = ax_message_attachment.id;


-- Перемещение файлов из таблицы ax_task_file
INSERT INTO ax_file (type, ax_attachment_id, file_name, download_url, full_text) 
SELECT type, id, file_name, download_url, full_text FROM ax_task_file;

INSERT INTO ax_task_files (task_id, file_id)
SELECT task_id, ax_file.id FROM ax_task_file
INNER JOIN ax_file ON ax_file.ax_attachment_id = ax_task_file.id;


-- Перемещение файлов из таблицы ax_solution_file
INSERT INTO ax_file (type, ax_attachment_id, file_name, download_url, full_text) 
SELECT type, id, file_name, download_url, full_text FROM ax_solution_file;

INSERT INTO ax_commit_file (commit_id, file_id)
SELECT commit_id, ax_file.id FROM ax_solution_file
INNER JOIN ax_file ON ax_file.ax_attachment_id = ax_solution_file.id;

-- Нужно удалить лишнюю клолонку
ALTER TABLE ax_file DROP COLUMN ax_attachment_id;

-- Удаляем больше не нужные таблицы
DROP TABLE ax_message_attachment;
DROP TABLE ax_task_file;
DROP TABLE ax_solution_file;

ALTER TABLE ax_task_files RENAME TO ax_task_file;

