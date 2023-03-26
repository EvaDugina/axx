update ax_assignment set task_id = -2 where id in (-7, -8);
update ax_assignment set task_id = -3 where id in (-9, -10);

update ax_assignment_student set student_user_id = -2 where id in (-2, -10, -12);

update ax_page_group set group_id = -1 where id in (-1, -2, -3);

-- (раскомментировать строчку, если колонки нет)
-- ALTER TABLE ax_student_page_info ADD COLUMN variant_num text;

--truncate public.ax_student_page_info;
delete from public.ax_student_page_info where id in (-1,-2);

INSERT INTO public.ax_student_page_info(id, student_user_id, page_id, total_count, passed_count, variant_num, variant_comment) VALUES 
(-1, -3, -8, 1, 0, '24', 'Длинное описание задания варианта 24'),
(-2, -4, -8, 1, 0, '17', 'А тут описние задания варианта 17'),
(-3, -3, -13, 10, 0, '3', 'Грамматика арифметических выражений над INT с использованием +,-,*,/,()'),
(-4, -4, -13, 10, 0, '15', E'Грамматика оператора switch c рекурсивными вложениями и с поддержкой printf и break. Пример:<i><br/>switch(2) {<br/>&nbsp;&nbsp;case 1:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(1);<br/>&nbsp;&nbsp;&nbsp;&nbsp;break;<br/>&nbsp;&nbsp;case 2:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(22);<br/>&nbsp;&nbsp;&nbsp;&nbsp;break;<br/>&nbsp;&nbsp;case 3:<br/>&nbsp;&nbsp;default:<br/>&nbsp;&nbsp;&nbsp;&nbsp;printf(100500);<br/>}</i>');
