CREATE TABLE students (			-- все пользователи: и преподаватели, и студенты
	id serial,
	first_name text,	-- имя
	middle_name text,	-- фамилия
	last_name text,		-- отчество (не верь глазам своим блин)
	login text,		
	role integer,		-- 1 - администратор, 2 - преподаватель, 3 - студент (бывает, что в БД две записи для одного человека, как студент и как препод)
	CONSTRAINT students_pkey PRIMARY KEY (id)
); ALTER TABLE students OWNER TO postgres;

INSERT INTO students(id, first_name, middle_name, last_name, login, role) VALUES 
(-1, 'Сергей', 'Иванов', 'Петрович', 'sergeyiva', 3),
(-2, 'Сергей', 'Петров', 'Петрович', 'peter', 3),
(-3, 'Сергей', 'Семенов', 'Петрович', 'semen', 3),
(-4, 'Апполинарий', 'Сидоров', 'Бердымухамедович', 'sidorov', 3),
(-5, 'Сергей', 'Быков', 'Петрович', 'avz', 2),
(-6, 'Антон', 'Кузнецов', 'Сергеевич', 'admin', 1),
(-7, 'Иван', 'Иванов', 'Иванович', 'ivan', 3);



CREATE TABLE students_to_groups	(	-- соотнесение студентов с группами
	id serial,
	student_id integer,	-- --> students
	group_id integer, 	-- --> groups, 29 - преподаватели
	CONSTRAINT students_to_groups_pkey PRIMARY KEY (id)
); ALTER TABLE students_to_groups OWNER TO postgres;

INSERT INTO students_to_groups(id, student_id, group_id) VALUES 
(-1, -1, -1),
(-2, -2, -1),
(-3, -3, -2),
(-4, -4, -2),
(-5, -5, 29),
(-6, -6, 29),
(-7, -7, -5);



CREATE TABLE discipline	(		-- дисциплины УП
	id serial,
	name text,		-- полное название, я бы так не назвал, но увы, для совместимости с Шаназаровым надо
	CONSTRAINT discipline_pkey PRIMARY KEY (id)
); ALTER TABLE discipline OWNER TO postgres;

INSERT INTO discipline(id, name) VALUES 
(-1, 'Введение в разработку программного обеспечения'),
(-2, 'Программирование в задачах радиолокации'),
(-3, 'Методы и стандарты программирования'),
(-4, 'Эргономика программного обеспечения'),
(-5, 'Проектирование трансляторов');



CREATE TABLE groups (			-- группы
	id serial,
	name text,		-- название группы
	year integer,		-- год поступления группы
	CONSTRAINT groups_pkey PRIMARY KEY (id)
); ALTER TABLE groups OWNER TO postgres;

INSERT INTO groups(id, name, year) VALUES 
(-1, 'БЗМЖ-02-20', 2020),
(-2, 'КЗБЗ-02-21', 2021),
(-3, 'ДРЛО-02-18', 2018),
(-4, 'БЗМЖ-05-20', 2020),
(-5, 'КЗБЗ-05-21', 2021),
(29, 'Преподаватели', null);



-- 5 --
CREATE TABLE ax_page (			-- страница дисциплины
	id serial,
	disc_id integer,	-- --> discipline
	short_name text,	-- краткое название страницы
	year integer,		-- календаный год
	semester integer,	-- номер семестра в учебном году (1 - Осень / 2 - Весна)
	color_theme_id integer,	-- --> ax_color_theme
	creator_id integer,	-- --> students
	creation_date timestamp with time zone,
				-- дата создания страницы
	status integer,		-- состояние страницы (1 - активна, 0 - архив)
	CONSTRAINT ax_page_pkey PRIMARY KEY (id)
); ALTER TABLE ax_page OWNER TO postgres;

INSERT INTO ax_page(id, disc_id, short_name, year, semester, color_theme_id, creator_id, creation_date, status) VALUES 
(-14, -5, 'ПТ-21/22 ч2', 2022, 8, -1, -5, now(), 1),
(-13, -5, 'ПТ-21/22 ч1', 2021, 7, -1, -5, now(), 1),
(-12, -1, 'РПО-21 пг3', 2021, 1, -1, -5, now(), 1), 
(-11, -2, 'ПЗРЛ-21/22-ч2', 2022, 2, -1, -5, now() + '1 year', 1), 
(-10, -1, 'РПО-21 пг2', 2021, 1, -1, -5, now(), 1), 
(-9, -2, 'ПЗРЛ-21/22-ч1', 2021, 1, -1, -5, now(), 1), 
(-8, -1, 'РПО-21 пг1', 2021, 1, -1, -5, now(), 1), 
(-7, -2, 'ПЗРЛ-19-20-ч1', 2019, 1, -1, -5, now() + '-2 years', 1), 
(-6, -3, 'МИСП-21-ч2', 2021, 1, -1, -5, now(), 1), 
(-5, -2, 'ПЗРЛ-20/21-ч2', 2021, 2, -1, -5, now() + '-1 years', 0), 
(-4, -3, 'МИСП-21-ч1', 2021, 2, -1, -5, now() + '-1 year', 0), 
(-3, -3, 'МИСП-20-ч2', 2020, 1, -1, -5, now() + '-2 years', 0), 
(-2, -3, 'МИСП-20-ч1', 2020, 2, -1, -5, now() + '-2 years', 0), 
(-1, -4, 'Эргономика-19', 2019, 2, -1, -5, now() + '-3 years', 0);



CREATE TABLE ax_page_prep (		-- преподы, допущенные к странице
	id serial,		
	prep_user_id integer,	-- --> students
	page_id integer,	-- --> ax_page
	CONSTRAINT ax_page_prep_pkey PRIMARY KEY (id)
); ALTER TABLE ax_page_prep OWNER TO postgres;

INSERT INTO ax_page_prep(id, prep_user_id, page_id) VALUES 
(-1, -5, -1),
(-2, -5, -2),
(-3, -5, -3),
(-4, -5, -4),
(-5, -5, -5),
(-6, -5, -6),
(-7, -5, -7),
(-8, -5, -8),
(-9, -5, -9),
(-10, -5, -10),
(-11, -5, -11),
(-12, -5, -12),
(-13, -6, -11),
(-14, -6, -9),
(-15, -6, -7),
(-17, -6, -5),
(-18, -5, -13),
(-19, -5, -14);


CREATE TABLE ax_page_group (		-- группы, обучающихся по этой дисциплине (странице)
	id serial,
	page_id integer,	-- --> ax_page
	group_id integer,	-- --> groups
	CONSTRAINT ax_page_group_pkey PRIMARY KEY (id)
); ALTER TABLE ax_page_group OWNER TO postgres;

INSERT INTO ax_page_group(id, page_id, group_id) VALUES 
(-1, -1, -1),
(-2, -2, -1),
(-3, -3, -1),
(-4, -4, -1),
(-5, -5, -1),
(-6, -6, -1),
(-7, -7, -3),
(-8, -8, -2),
(-9, -9, -2),
(-10, -10, -5),
(-11, -11, -2),
(-12, -12, -5),
(-13, -5, -4),
(-14, -9, -5),
(-15, -11, -5),
(-16, -8, -1),
(-17, -13, -2),
(-18, -13, -5);


CREATE TABLE ax_color_theme (		-- цветовые схемы
	id serial,		
	disc_id integer,	--> discipline
	name text,		-- Название темы
	bg_color text,		-- Код соответсвующего ей цвета в палитре RGB
  src_url text,    -- ссылка на картинку
	CONSTRAINT ax_color_theme_pkey PRIMARY KEY (id)
); ALTER TABLE ax_color_theme OWNER TO postgres;

INSERT INTO ax_color_theme(id, font_color, bg_color, dark) VALUES 
(-1, 'ffffff', '660099', true),
(-2, 'ffffff', '000000', true),
(-3, 'ffffff', '3300cc', true),
(-4, 'ffffff', '336666', true),
(-5, '000000', 'ccff33', false),
(-6, '000000', 'ffccff', false),
(-7, '000000', '66ffff', false),
(-8, '000000', 'ffffff', false);



CREATE TABLE ax_task (			-- задания по дисциплинам
	id serial,
	page_id integer,	-- --> ax_page
	type integer,		-- (0 - обычное, 1 - программирование, 2 - общая беседа потока)
	title text,		-- название 
	description text,	-- постановка задачи
	max_mark text,		-- максимальный балл
	status integer,		-- состояние задания (1 - активно, 0 - архив)
	CONSTRAINT ax_task_pkey PRIMARY KEY (id)
); ALTER TABLE ax_task OWNER TO postgres;

INSERT INTO ax_task(id, page_id, type, title, description, max_mark, status) VALUES 
(-1, -1, 0, 'Анализ проблемной ситуации', 'Провести исследование пользователей и их деятельности для выявления проблем деятельности, интересов и потребностей пользователей. Результаты представить в виде карты эмпатии.', '5', 1),
(-2, -1, 0, 'Генерация идей', 'Разработать 15 идей, обеспечивающих решение заданной проблемы. Результаты представить в виде канваса ценностного предложения. Приоритезировать идеи и оценить сложность их реализации.', '5', 1),
(-3, -1, 0, 'Прототипирование интерфейса', 'Разработать макет пользовательского интерфейса программы, обеспечивающей решение выявленных проблем и реализацию наиболее приоритетных идей.', '5', 1),
(-4, -8, 0, 'Техническое задание', 'Разработать техническое задание на создание программы-калькулятора валют', '5', 1),
(-5, -7, 1, 'Перегрузка функций', 'Разработать класс с перегрузкой 3 функций с различными аргументами', '10', 1),
(-6, -8, 1, 'Реализация программы', 'Реализовать программу-калькулятор валют в соответствии с согласованным техническим заданием', '15', 1),
(-7, -13, 1, 'Задание 10. Анализатор на ANTLR', 'Разработать программу анализа с семантикой, используя инструментарий ANTLR', '5', 1),
(-8, -13, 1, 'Задание 9. Анализатор на YAXX+LEX', 'Разработать программу анализа с семантикой, используя инструменты YACC (BISON) и LEX (FLEX)', '5', 1),
(-9, -13, 0, 'Задание 8. LL(1)-распознаватель', 'Разработать программу рекурсивного нисходящего предиктивного распознавателя с классическим сканером и с семантикой', '5', 1),
(-10, -13, 0, 'Задание 7. SLR(1)-распознаватель', 'Разработать программу рекурсивного нисходящего предиктивного распознавателя с классическим сканером и с семантикой', '5', 1),
(-11, -13, 1, 'Задание 6. Рекурсивный распознаватель', 'Разработать программу рекурсивного нисходящего предиктивного распознавателя с классическим сканером и с семантикой', '5', 1),
(-12, -13, 0, 'Задание 5. Редукция основы', 'Провести две последовательных редукции основы', '5', 1),
(-13, -13, 0, 'Задание 4. Построение дерева и вывода', 'Построить синтаксическое дерево и вывод (последовательность непосредственных выводов) по произвольному предложению', '5', 1),
(-14, -13, 0, 'Задание 3. Классификация языка и грамматики', 'Определить класс разработанной грамматики и языка по Хомскому, определить однозначность грамматики и языка', '5', 1),
(-15, -13, 0, 'Задание 2. Разработка грамматики', 'Разработать грамматику по заданию 1', '5', 1),
(-16, -13, 0, 'Задание 1. Постановка задачи', 'Сформулировать задачу по разработке языка и грамматики согласно условиям своего варианта', '5', 1);



-- 10 --
CREATE TABLE ax_task_file (		-- файлы, прилагающиеся к заданиям
	id serial,
	type integer,		-- тип файла (0 - просто файл, 1 - шаблон проекта, 2 - код теста, 3 - код проверки теста)
	task_id integer,	-- --> ax_task
	file_name text,		-- отображаемое имя файла
	download_url text,	-- URL для скачивания, если файл лежит на диске
	full_text text,		-- полный текст файла, если он лежит в БД
	CONSTRAINT ax_task_file_pkey PRIMARY KEY (id)
); ALTER TABLE ax_task_file OWNER TO postgres;

INSERT INTO ax_task_file(id, type, task_id, file_name, download_url, full_text) VALUES 
(-1, 1, -5, 'main.c', null, E'#include <stdio.h>\nint main(void) {\n\n  return 0;\n}'),
(-2, 0, -6, 'instructions.md', null, E'# Лабораторная 2\n\n## ***Дедлайны***\n\n- 14.11 22.00 - первая итерация (***обязательно***)\n- 21.11 22.00 - исправление ошибок, дописывание недостающих функций, правки по замечаниям\n\n## Задача\n\nРеализовать собственные функции для работы со строками, повторяющие функции стандартной библиотеки (полное совпадение: возвращаемое значение, аргументы функции):\n- strlen\n- strcpy\n- strncpy\n- strcat\n- strncat\n- strstr\n- strchr\n- strcmp\n'),
(-3, 1, -6, 'main.cpp', null, E'#include <main.h>\nint main(void) {\n\n  return 0;\n}'),
(-4, 1, -6, 'main.h', null, E'#include <stdio.h>\n\n'),
(-5, 2, -6, 'test.cpp', null, '#include <googletest.h>'),
(-6, 3, -6, 'checktest.c', null, E'#include <main.h>\nint main(void) {\n\n  printf(\"Hello world!\");\n	return 0;\n}'),
(-7, 0, -1, 'rdv.pdf', '/dx/rdv.pdf', null);



CREATE TABLE ax_assignment (		-- задания, назначенные студентам (индивидуально ил группой)
	id serial,
	task_id integer,	-- --> ax_task
	variant_comment text,	-- номер варианта студента для данного задания 
	start_limit timestamp with time zone,
				-- ограничение на время доступности задания студенту "не ранее, чем"
	finish_limit timestamp with time zone,
				-- ограничение на время доступности здания студенту "не позднее, чем"
	status_code integer,	-- код статуса назначенного задания (0 - недоступно для просмотра, 
							-- 2 - доступно для просмотра, 4 - отменено)
	delay integer,		-- признак "сдано с задержкой" (0 - нет задержки, 1 - задержка срока выполнения)
	status_text text,	-- текстовый статус назначенного задания
	mark text,		-- полученная оценка
	CONSTRAINT ax_assignment_pkey PRIMARY KEY (id)
);
ALTER TABLE ax_assignment OWNER TO postgres;

INSERT INTO public.ax_assignment(id, task_id, variant_comment, start_limit, finish_limit, status_code, delay, status_text, mark) VALUES 
(-1, -1, '1', null, null, 2, 0, 'активно', null),
(-2, -1, '2', now()+'1 year', null, 0, 0, 'скрыто', null), -- в списке показываем, но просмотреть описание не даем
(-3, -5, '3', now(), null, 2, 0, 'активно', null),
(-4, -5, '4', now(), null, 2, 0, 'активно', null),
(-5, -6, '5', now(), null, 2, 0, 'активно', null),
(-6, -6, '6', now(), now()+'1 day', 2, 0, 'активно', null),
(-7, -2, '7', now(), now()+'1 year', 2, 0, 'активно', null),
(-8, -2, '1', null, null, 3, 0, 'выполнено', '4'),
(-9, -3, '2', now()+'-2 years', now()+'-1 year', 4, 1, 'отменено', null),
(-10, -3, '3', now()+'1 year', null, 1, 0, 'недоступно', null),
(-11, -16, '3', now()+'1 year', null, 3, 0, 'выполнено', '4'),
(-12, -15, '3', now()+'1 year', null, 3, 0, 'выполнено', '5'),
(-13, -14, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-14, -13, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-15, -12, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-16, -16, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-17, -15, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-18, -14, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-19, -13, '3', now()+'1 year', null, 2, 0, 'активно', null),
(-20, -16, '3', now(), now()+'1 year', 2, 0, 'активно', null),
(-21, -15, '3', now(), now()+'1 year', 2, 0, 'активно', null),
(-22, -14, '3', now(), now()+'1 year', 3, 0, 'выполнено', 5),
(-23, -13, '3', now(), now()+'1 year', 5, 0, 'ожидает проверки', null),
(-24, -12, '3', now(), now()+'1 year', 5, 0, 'ожидает проверки', null);



CREATE TABLE ax_assignment_student (	-- студенты, назначенные по конкретному заданию
	id serial,
	assignment_id integer,	-- --> ax_assignment
	student_user_id integer,-- --> students
	CONSTRAINT ax_assignment_student_pkey PRIMARY KEY (id)
); ALTER TABLE ax_assignment_student OWNER TO postgres;

INSERT INTO public.ax_assignment_student(id, assignment_id, student_user_id) VALUES 
(-1, -1, -1),
(-2, -2, -2),
(-3, -4, -1),
(-4, -4, -2),
(-5, -5, -3),
(-6, -5, -4),
(-7, -6, -1),
(-8, -6, -2),
(-9, -7, -1),
(-10, -8, -2),
(-11, -9, -1),
(-12, -10, -2),
(-13, -11, -3),
(-14, -12, -3),
(-15, -13, -3),
(-16, -14, -3),
(-17, -15, -3),
(-18, -16, -4),
(-19, -17, -4),
(-20, -18, -4),
(-21, -19, -4),
(-22, -20, -7),
(-23, -21, -7),
(-24, -22, -7),
(-25, -23, -7),
(-26, -24, -7);



CREATE TABLE ax_solution_commit	( 	-- посылка кода на проверку
	id serial,
	assignment_id integer,	-- --> ax_assignment
	session_id integer,	-- --> ax_assignment_session
	student_user_id integer,-- --> students
	type integer, 		-- тип посылки/коммита (0 - промежуточный (редактирует только отправляющий), 
	-- 											1 - отправлен на проверку (не редактирует никто),
	--											2 - проверяется (редактирует только препод),
	--											3 - проверенный (не редактирует никто))
	autotest_results text,
	CONSTRAINT ax_solution_commit_pkey PRIMARY KEY (id)
); ALTER TABLE ax_solution_commit OWNER TO postgres;

INSERT INTO public.ax_solution_commit(id, assignment_id, session_id, student_user_id, type, autotest_results) VALUES 
(-1, -4, -1, -2, 1, '<xml>результаты автотестов</xml>'),
(-2, -5, -2, -3, 0, null),
(-3, -5, -2, -4, 0, null),
(-4, -5, -3, -4, 1, '<xml>результаты автотестов</xml>');


CREATE TABLE ax_solution_file (		-- файл в составе посылки на проверку
	id serial,
	assignment_id integer,	-- --> ax_assignment
	commit_id integer,	-- --> ax_solution_commit
	type integer,		-- тип файла (10 - просто файл с результатами, 11 - файл проекта, нумерация совместима с ax_task_file)
	file_name text,		-- отображаемое имя файла
	download_url text,	-- URL для скачивания, если файл лежит на диске 
	full_text text,		-- полный текст файла, если он лежит в БД
	CONSTRAINT ax_solution_file_pkey PRIMARY KEY (id)
); ALTER TABLE ax_solution_file OWNER TO postgres;

INSERT INTO public.ax_solution_file(id, assignment_id, commit_id, type, file_name, download_url, full_text) VALUES 
(-1, -4, -1, 11, 'main.c', null, E'#include <stdio.h>\nint main(void) {\n\n  return 23423423;\n}'),
(-2, -5, -4, 11, 'main.cpp', null, E'#include <main.h>\nint main(void) {\n\n  //пока не сделал \n\nreturn 0;\n}'),
(-3, -5, -4, 11, 'main.h', null, E'#include <stdio.h>\n\n');



-- 15 --
CREATE TABLE ax_autotest_results (	-- результаты выполнения автотестов по определенной посылке
	id serial,
	commit_id integer,	-- --> ax_solution_commit
	order_num integer,	-- порядковый номер автотеста (1 .. N)
	test_name text,		-- название теста
	test_timing text,	-- время выполнения теста
	succeeded boolean,	-- признак успешного прохождения теста
	CONSTRAINT ax_autotest_results_pkey PRIMARY KEY (id)
); ALTER TABLE ax_autotest_results OWNER TO postgres;

INSERT INTO public.ax_autotest_results(id, commit_id, order_num, test_name, test_timing, succeeded) VALUES 
(-1, -1, 1, 'тест 1', '0,0005', false),
(-2, -1, 1, 'тест 2', '0,0002', false),
(-3, -4, 1, 'тест 1', '0,03', false),
(-4, -4, 1, 'тест 2', '0,003', false);



CREATE TABLE ax_message	(		-- сообщение в диалоге по заданию
	id serial,
	assignment_id integer,	-- --> ax_assignment
	type integer, 		-- тип сообщения (0 - обычное сообщение (в т. ч. с приложениями), 1 - коммит, 2 - оценка, 3 - ссылка)
  visibility integer, -- метка видимости сообщения (0 - видимо всем, 1 - видимо только админу, 2 - видимо только преподавателю, 3 - видимо только студенту)
	sender_user_type integer,-- тип отправителя (1 - админ, 2 - преподаватель, 3 - студент)
	sender_user_id integer, -- --> students
	date_time timestamp with time zone,
				-- дата и время отправки сообщения
	reply_to_id integer,	-- --> ax_message (ссылка на исходное сообщение, если это - ответное)
	resended_from_id integer, -- (ссылка на исходное сообщение, если это - пересланное сообщение)
	full_text text,		-- полный текст сообщения
	commit_id integer,	-- --> ax_solution_commit
	status integer,		-- состояние сообщения (0 - новое, 1 - прочитанное, 2 - удаленное), флаг прочтения получателем (одним из преподов или одним из исполнителей)
	CONSTRAINT ax_message_pkey PRIMARY KEY (id)
); ALTER TABLE ax_message OWNER TO postgres;

INSERT INTO public.ax_message(id, assignment_id, type, visibility, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status) VALUES 
(-1, -5, 0, 0,  3, -4, now() + '-10 days', null, 'А до какога нужно сдать?', null, 1),
(-2, -5, 0, 0,  2, -5, now() + '-8 days', null, 'До вчера', null, 1),
(-3, -5, 0, 0,  3, -4, now() + '-7 days', null, 'Я болел у меня справка', null, 2),
(-4, -5, 1, 0, 3, now() + '-5 days', null, 'Ппроверьте пожалуйсто очень надо', -4, 1),
(-5, -5, 2, 0,  2, -6, now() + '-4 days', null, 'Содержание выходного файла не соответствует заданию', null, 0),
(-6, -6, 1, 0,  3, -3, now() + '-3 days', null, '', -1, 1),
(-7, -6, 0, 0,  3, -3, now() + '-1 day', -6, '', null, 1),
(-8, -11, 0, 0,  3, -3, now() + '-10 day', null, 'Посмотрите задание, все верно? Разработать грамматику, реализующую оператор IF-ELSE языка С. <br/>Пример: if (true) printf(1); else if (false) printf(2); else printf(3);', null, 1),
(-9, -11, 2, 0,  2, -5, now() + '-9 day', -8, 'Принято, делай на его основе следующие', null, 1),
(-10, -12, 0, 0,  3, -3, now() + '-8 day', null, 'А такая грамматика правильная? <br/>S::=if(b) S|if(b) S else S|printf(i);', null, 1),
(-11, -12, 2, 0,  2, -5, now() + '-7 day', -10, 'Да, продолжай с ней остальные', null, 0),
(-12, -13, 0, 0,  3, -3, now() + '-6 day', null, 'Грамматика и язык класса 2 (КС), грамматика неоднозначная, язык существенно неознозначный. Верно?', null, 1),
(-13, -14, 0, 0,  3, -3, now() + '-5 day', null, 'Во вложении дерево и вывод', null, 0),
(-14, -16, 0, 0,  3, -4, now() + '-4 day', null, 'А я не понял как делать, объясните!', null, 0),
(-15, -17, 0, 0,  3, -4, now() + '-4 day', null, 'Тупой препод иди в жопу почему так долго отвечаешь?!', null, 2);



CREATE TABLE ax_message_attachment (	-- приложения к сообщениям
	id serial,
	message_id integer,	-- --> ax_message
	file_name text,		-- отображаемое имя файла
	download_url text,	-- URL для скачивания, если файл лежит на диске 
	full_text text,		-- полный текст файла, если он лежит в БД
	CONSTRAINT ax_message_attachment_pkey PRIMARY KEY (id)
); ALTER TABLE ax_message_attachment OWNER TO postgres;

INSERT INTO public.ax_message_attachment(id, message_id, file_name, download_url, full_text) VALUES 
(-1, -3, 'main.c', null, 'Малява на серого последняя.psd'),
(-2, -13, 'task3.pdf', null, 'тут видимо бинарный контент'),
(-3, -14, 'Google docs', 'https://docs.google.com/document/d/1TAVFhLyuuaVmR58zf1fZGY5XmZdxTendlMBjaPdhQvo/view', null);



CREATE TABLE ax_assignment_session (	-- сессии работы студентов по заданию
	id serial,
	assignment_id integer,	-- --> ax_assignment
	student_user_id integer,-- --> students
	start_time timestamp with time zone,
				-- время начала сессии
	end_time timestamp with time zone,
				-- время окончания сессии
	CONSTRAINT ax_assignment_session_pkey PRIMARY KEY (id)
); ALTER TABLE ax_assignment_session OWNER TO postgres;

INSERT INTO public.ax_assignment_session(id, assignment_id, student_user_id, start_time, end_time) VALUES 
(-1, -5, -3, now() + '-10 days', now() + '-9 days'),
(-2, -5, -3, now() + '-8 days', now() + '-7 days'),
(-3, -5, -4, now() + '-6 days', now() + '-5 days'),
(-4, -6, -4, now() + '-4 days', now() + '-3 days'),
(-5, -6, -4, now() + '-2 days', now() + '-1 days'),
(-6, -6, -3, now() + '-1 days', now());



CREATE TABLE ax_settings (		-- настройки пользователя (в профиле)
	user_id integer,	-- --> students
	email text,		-- email для отправки уведомлений
	notification_type integer,
				-- способ уведомления (0 - не отправлять, 1 - почта)
	monaco_dark boolean,	-- признак "темная тема в редакторе кода"
	CONSTRAINT ax_settings_pkey PRIMARY KEY (user_id)
); ALTER TABLE ax_settings OWNER TO postgres;

INSERT INTO ax_settings(user_id, email, notification_type, monaco_dark) VALUES 
(-1, 'ruslan.odegow@yandex.ru', 1, true),
(-2, 'arthurkov98@gmail.com', 0, true),
(-3, 'ruslan.odegow@yandex.ru', 0, true),
(-4, 'arthurkov98@gmail.com', 1, true),
(-5, 'a.zavjalov@gmail.com', 1, true),
(-6, 'zavjalov@mirea.ru', 1, true);



-- 20 --
CREATE TABLE ax_student_page_info (	-- информация о работе студента по дисциплине
	id serial,
	student_user_id integer,-- --> students
	page_id integer,	    -- --> ax_page
	total_count integer,	-- общее количество назначенных студенту заданий по дисциплине
	passed_count integer,	-- количество выполненных студентом заданий по дисциплине
	variant_num text,	    -- номер варианта студента для всех заданий по дисциплине (для гениальной таблицы)
	variant_comment text,	-- описание варианта студента для всех заданий по дисциплине (для гениальной таблицы)
	CONSTRAINT ax_student_page_info_pkey PRIMARY KEY (id)
); ALTER TABLE ax_student_page_info OWNER TO postgres;

INSERT INTO public.ax_student_page_info(id, student_user_id, page_id, total_count, passed_count, variant_num, variant_comment) VALUES 
(-1, -3, -8, 1, 0, '24', 'Длинное описание задания варианта 24'),
(-2, -4, -8, 1, 0, '17', 'А тут описние задания варианта 17'),
(-3, -3, -13, 10, 0, '3', 'Грамматика арифметических выражений над INT с использованием +,-,*,/,()'),
(-4, -4, -13, 10, 0, '15', E'Грамматика оператора switch c рекурсивными вложениями и с поддержкой printf и break. Пример:<i><br/>switch(2) {<br/>&nbsp;&nbsp;case 1:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(1);<br/>&nbsp;&nbsp;&nbsp;&nbsp;break;<br/>&nbsp;&nbsp;case 2:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(22);<br/>&nbsp;&nbsp;&nbsp;&nbsp;break;<br/>&nbsp;&nbsp;case 3:<br/>&nbsp;&nbsp;default:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(100500);<br/>}</i>');



CREATE TABLE ax_message_delivery (    -- признаки уведомлений о получении сообщений
  id serial,
  message_id integer,  -- --> ax_message
  recipient_user_id integer,  -- --> students
  read boolean, -- всегда true. Если сообщение не получено, то записи в этой таблице просто нет
  CONSTRAINT ax_message_delivery_pkey PRIMARY KEY (id)
); ALTER TABLE ax_message_delivery OWNER TO postgres;

INSERT INTO public.ax_message_delivery(id, message_id, recipient_user_id, read) VALUES 
(-1, -1, -4, True);