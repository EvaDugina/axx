update ax_assignment set task_id = -2 where id in (-7, -8);
update ax_assignment set task_id = -3 where id in (-9, -10);

update ax_assignment_student set student_user_id = -2 where id in (-2, -10, -12);

update ax_page_group set group_id = -1 where id in (-1, -2, -3);