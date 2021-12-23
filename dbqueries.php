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
        return 'select students.middle_name || \' \' || students.first_name fio, students.id id'.
                ' from ax_page_group '.
	            ' inner join students_to_groups on ax_page_group.group_id = students_to_groups.group_id'.
	            ' inner join students on students_to_groups.student_id = students.id'.
                ' where ax_page_group.page_id = '. $page_id.
                ' order by fio';
    }

    function select_page_students_grouped($page_id)
    {
        return 'select students.middle_name || \' \' || students.first_name fio, students.id id, ax_student_page_info.variant_comment var, groups.name grp, groups.id gid'.
                ' from ax_page_group '.
	            ' inner join students_to_groups on ax_page_group.group_id = students_to_groups.group_id'.
	            ' inner join students on students_to_groups.student_id = students.id'.
	            ' inner join groups on groups.id = students_to_groups.group_id'.
	            ' left join ax_student_page_info on ax_student_page_info.student_user_id = students.id'.
                ' where ax_page_group.page_id = '. $page_id.
                ' order by grp, fio';
    }

    // группы для страницы
    function select_page_groups($page_id)
    {
        return 'select groups.id id, groups.name grp'.
                ' from ax_page_group '.
	            ' inner join groups on groups.id = ax_page_group.group_id'.
                ' where ax_page_group.page_id = '. $page_id.
                ' order by grp';
    }

    // - получение названия страницы дисциплины
    function select_page_name($page_id, $status) 
    {
        // p.id as pid, d.id as did, d.name as dname, p.short_name as pname
        return 'select d.name ||  \': \' || p.short_name || \' (\' || p.semester || \' семестр)\'' .
                ' from ax_page p inner join discipline d on d.id = p.disc_id' .
                ' where p.id=' . $page_id . ' and p.status=' . $status;
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
    
    // получение сообщений для таблицы посылок
    function select_page_messages_table($page_id)
    {
        return 'select distinct on (tid, sid) students.middle_name || \' \' || students.first_name fio, '.
                '       ax_task.id tid, ax_assignment.id aid, ax_message.id mid, ax_assignment_student.student_user_id sid,'.
                '       case when ax_assignment.mark is not null then ax_assignment.mark'.
                '            when ax_message.id is not null then \'?\' '.
                '            when ax_assignment.status_code in (0,1) then \'X\' '.
                '            when ax_assignment.status_code in (4) then \'-\' '.
                '       else null end val,'.
                '       ax_assignment.mark amark, ax_assignment.delay adelay, ax_assignment.status_code ascode, ax_assignment.status_text astext,'.
                '       to_char(ax_message.date_time, \'DD-MM-YYYY HH24:MI:SS\') mtime, ax_message.full_text mtext, ax_message_attachment.file_name as mfile, ax_message_attachment.download_url as murl'.
                ' from ax_task '.
                ' inner join ax_assignment on ax_task.id = ax_assignment.task_id and ax_assignment.status_code in (2,3,4)'.
                ' inner join ax_assignment_student on ax_assignment.id = ax_assignment_student.assignment_id'.
                ' inner join students on students.id = ax_assignment_student.student_user_id '.
                ' left join ax_message on ax_assignment.id = ax_message.assignment_id and (ax_message.sender_user_id=ax_assignment_student.student_user_id or ax_message.sender_user_type=1) and ax_message.status in (0,1)'.
                ' left join ax_message_attachment on ax_message.id = ax_message_attachment.message_id'.
                ' where ax_task.page_id = '.$page_id.
                ' order by sid asc, tid asc, ax_assignment.id desc, ax_message.date_time desc';
    }
    
  function pg_fetch_all_assoc($res) {
    if (PHP_VERSION_ID >= 70100) 
        return pg_fetch_all($res, PGSQL_ASSOC);
    $array_out = array();
    while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
        $array_out[] = $row;
    }
    return $array_out;
  }
    
?>