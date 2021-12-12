<?php
    // ОБЩИЕ
    function select_timestamp($shift)
    {
        if (trim($shift) == "")
            $shift = '0';
        return 'select to_char(now() + \''.$shift.'\', \'YYYY-MM-DD HH24:MI:SS\') val';
    }

    function select_check_timestamp($datetime)
    {
        return 'select to_timestamp(\''.$datetime.'\', \'YYYY-MM-DD HH24:MI:SS\')';
    }

    function get_user_name($id)
    {
        return 'select first_name || \' \' || middle_name fio from students where id = '.$id;
    }
    
    // СТРАНИЦЫ
    function select_page_students($page_id)
    {
        return 'select students.middle_name || \' \' || students.first_name fio, students.id id '.
                ' from ax_page_group '.
	            ' inner join students_to_groups on ax_page_group.group_id = students_to_groups.group_id'.
	            ' inner join students on students_to_groups.student_id = students.id'.
                ' where page_id = '. $page_id.
                ' order by fio';
    }

    // ЗАДАНИЯ
    // - получение всех заданий по странице дисциплины
    function select_page_tasks($page_id, $status)
    {
        return "select * from ax_task where page_id = " . $page_id . ' and status = '. $status .' order by id';
    }
    // - получение студентов, которым назначено задание
    function select_assigned_students($task_id)
    {
        return 'select students.middle_name || \' \' || students.first_name fio, ax_assignment.id aid, to_char(ax_assignment.finish_limit, \'DD-MM-YYYY HH24:MI:SS\') ts '.
              ' from ax_task '.
              ' inner join ax_assignment on ax_task.id = ax_assignment.task_id '.
              ' inner join ax_assignment_student on ax_assignment.id = ax_assignment_student.assignment_id '.
              ' inner join students on students.id = ax_assignment_student.student_user_id '.
              ' where ax_task.id = '.$task_id.
              ' order by ax_assignment.id';
    }
    // получение файлов к заданию
    function select_task_files($task_id)
    {
        return 'select ax_task_file.* '.
                ' from ax_task inner join ax_task_file on ax_task.id = ax_task_file.task_id '.
                ' where ax_task.id = '.$task_id.' and ax_task_file.type = 0 '.
				' order by id';
    }
?>