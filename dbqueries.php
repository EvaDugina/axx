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
    function select_active_pages(){
        return 'SELECT ax_page.disc_id, ax_page.year, ax_page.semester FROM ax_page WHERE status = 1;';
    }

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
        return 'select students.middle_name || \' \' || students.first_name fio, students.id id, ax_student_page_info.variant_num vnum, ax_student_page_info.variant_comment vtext, groups.name grp, groups.id gid'.
                ' from ax_page_group '.
	            ' inner join students_to_groups on ax_page_group.group_id = students_to_groups.group_id'.
	            ' inner join students on students_to_groups.student_id = students.id'.
	            ' inner join groups on groups.id = students_to_groups.group_id'.
	            ' left join ax_student_page_info on ax_student_page_info.student_user_id = students.id and ax_student_page_info.page_id=ax_page_group.page_id'.
                ' where ax_page_group.page_id = '. $page_id.
                ' order by grp, fio';
    }
    
    // группы для страницах
    function select_page_groups($page_id)
    {
        return 'select groups.id id, groups.name grp'.
                ' from ax_page_group '.
	            ' inner join groups on groups.id = ax_page_group.group_id'.
                ' where ax_page_group.page_id = '. $page_id.
                ' order by grp';
    }

    // - получение названия страницы дисциплины
    function select_page_name($page_id, $status) {
        // p.id as pid, d.id as did, d.name as dname, p.short_name as pname
        return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS name
                FROM ax_page p inner join discipline d ON d.id = p.disc_id
                WHERE p.id=" . $page_id . " AND p.status=" . $status;
    }

    function select_page_names($status) {
        // p.id as pid, d.id as did, d.name as dname, p.short_name as pname
        return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS names
                FROM ax_page p inner join discipline d ON d.id = p.disc_id 
                WHERE p.status = " . $status . "ORDER BY p.semester";
    }


    
    // ЗАДАНИЯ

    // - получение статуса и времени отправки ответа студента
    function select_task_assignment($task_id, $student_id) {
        return "SELECT ax_assignment.finish_limit, ax_assignment.status_code, ax_assignment.mark FROM ax_assignment 
        INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id 
        WHERE ax_assignment_student.student_user_id = ". $student_id ." AND ax_assignment.task_id = ". $task_id ." LIMIT 1;";
    }

    // - получение всех заданий по странице дисциплины
    function select_page_tasks($page_id, $status) {
        return "SELECT * FROM ax_task WHERE page_id = " . $page_id . ' and status = '. $status .' ORDER BY id';
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

    // получение уведомлений для студента по невыполненным заданиям
    function select_notify_undone_tasks($student_id){
        return "SELECT ax_page.short_name, ax_task.title, ax_assignment.status_code FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            WHERE ax_assignment_student.student_user_id =". $student_id . 
            " AND ax_page.status = 1 AND ax_assignment.status_code = 2;
        ";
    }
    
    // получение сообщений для таблицы посылок
    function select_page_messages($page_id) {
        return "SELECT s1.middle_name || ' ' || s1.first_name fio, groups.name grp, 
            ax_task.id tid, ax_assignment.id aid, m1.id mid, ax_assignment_student.student_user_id sid, 
            ax_message_attachment.id fid, 
            case when ax_assignment.mark is not null then ax_assignment.mark
            when ax_assignment.status_code in (0, 1) then 'X'
            when ax_assignment.status_code in (4) then '-'
            when m1.sender_user_type = 0 then '?' 
            when m1.sender_user_type = 1 then '!'
            else null end val, ax_task.title task, ax_task.max_mark max_mark, 
            ax_assignment.mark amark, ax_assignment.delay adelay, ax_assignment.status_code astatus, 
            ax_assignment.status_text astext, to_char(m1.date_time, 'DD-MM-YYYY HH24:MI:SS') mtime, 
            m1.full_text mtext, m1.sender_user_type mtype, m1.status mstatus, m2.full_text mreply, 
            s2.middle_name || ' ' || s2.first_name mfio, s2.login mlogin, ax_message_attachment.file_name as mfile, 
            ax_message_attachment.download_url as murl FROM ax_task 

            inner join ax_assignment on ax_task.id = ax_assignment.task_id and ax_assignment.status_code in (2,3,4)
            inner join ax_assignment_student on ax_assignment.id = ax_assignment_student.assignment_id
            inner join students s1 on s1.id = ax_assignment_student.student_user_id 
            left join ax_message m1 on ax_assignment.id = m1.assignment_id 
            and (m1.sender_user_id=ax_assignment_student.student_user_id or m1.sender_user_type=1) and m1.status in (0,1)
            left join ax_message m2 on m1.reply_to_id = m2.id
            left join students s2 on s2.id = m1.sender_user_id
            left join ax_message_attachment on m1.id = ax_message_attachment.message_id
            inner join students_to_groups on s1.id = students_to_groups.student_id
            inner join groups on groups.id = students_to_groups.group_id
            where ax_task.page_id = ". $page_id ." order by mid DESC
        ";
    }

    // отправка ответа на сообщение
    function insert_message($message_id, $message_text, $mark, $sender_id) {
        if ($message_text != null)
            $message_text = str_replace("'", "''", $message_text);
        if ($mark != null)
            $mark = str_replace("'", "''", $mark);
        if ($mark != null)
        {
            return "update ax_assignment set mark='$mark' where id in (select assignment_id from ax_message where id=$message_id);\n" .
                   "update ax_message set status=1 where id=$message_id and status=0;\n" .
                   "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
                   "(select assignment_id, 2, 1, $sender_id, now(), $message_id, '$message_text<br/>\nОценка: $mark', null, 0 from ax_message where id=$message_id);";
        } else {
            return "update ax_message set status=1 where id=$message_id and status=0;\n" .
                   "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
                   "(select assignment_id, 0, 1, $sender_id, now(), $message_id, '$message_text', null, 0 from ax_message where id=$message_id);";
        }
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




   //
    function select_task($task_id)
    {
    return 'select type, title, description from ax_task where id ='.$task_id;
    }

    // Название дисциплины
    function select_all_disciplines() {
        return 'SELECT * FROM discipline';
    }

    // Страница всех дисциплин
    function select_all_discipline_page() {
        return 'SELECT * FROM ax_page';
    }
    
    // Страница дисциплины
    function select_discipline_page($id) {
        return 'SELECT * FROM ax_page where id ='.$id;
    }

    // Страницы дисциплин для конкретного студента
    function select_discipline_page_by_semester($semester) {
        return 'SELECT * FROM ax_page where semester <='. $semester;
    }

    // Все года и семестры
    function select_discipline_timestamps()
    {
    return 'SELECT distinct year, semester from ax_page order by year desc';
    }

    // Все года
    function select_discipline_years(){
        return 'SELECT distinct year from ax_page order by year desc'; 
    }

    // Изменение страницы дисциплины
    function update_discipline($discipline) {
        $timestamp = convert_timestamp_from_string($discipline['timestamp']);
        $short_name = pg_escape_string($discipline['short_name']);
        $id = pg_escape_string($discipline['id']);
        $disc_id = pg_escape_string($discipline['disc_id']);
        $year = pg_escape_string($timestamp['year']);
        $semester = pg_escape_string($timestamp['semester']);

        return "UPDATE ax_page SET short_name ='$short_name', disc_id='$disc_id', year='$year', semester='$semester' where id ='$id'";
    }

    function insert_discipline($discipline) {
        $timestamp = convert_timestamp_from_string($discipline['timestamp']);
        $short_name = pg_escape_string($discipline['short_name']);
        $id = pg_escape_string($discipline['id']);
        $disc_id = pg_escape_string($discipline['disc_id']);
        $year = pg_escape_string($timestamp['year']);
        $semester = pg_escape_string($timestamp['semester']);

        return "INSERT INTO ax_page (disc_id, short_name, year, semester) VALUES ('$disc_id', '$short_name', '$year', '$semester') returning id";
    }

    function prep_ax_prep_page($id, $first_name, $middle_name)
    {
    $first_name = pg_escape_string($first_name);
    $middle_name = pg_escape_string($middle_name);
    return "INSERT INTO ax_page_prep
    (id, prep_user_id, page_id)
    values(default, (select id from students where first_name = '$first_name' and middle_name = '$middle_name'),'$id')";
    }

    function update_ax_page_group($id, $groups)
    {
    $groups = pg_escape_string($groups);

    return "INSERT INTO ax_page_group(group_id, page_id)
    values ((select id from groups where name = '$groups'), '$id')";
    }

    //все группы
    function select_groups()
    {
    return 'select * from groups';
    }

    // группы у конкретной дисциплины
    function select_discipline_groups($page_id)
    {
    return 'select name from groups inner join ax_page_group on groups.id = ax_page_group.group_id where page_id ='.$page_id;
    }
    // все преподователи
    function select_teacher_name()
    {
    return 'select student_id, first_name, middle_name, last_name from students_to_groups inner join students on students_to_groups.student_id = students.id where group_id = 29';
    }
    // преподователи у конкретной дисциплины
    function select_page_prep_name($page_id)
    {
    return 'select first_name, middle_name from ax_page_prep inner join students on students.id = ax_page_prep.prep_user_id where page_id ='.$page_id;
    }
    // удаление из таблицы дисциплины-преподователи
    function delete_page_prep($page_id)
    {
    return 'DELETE FROM ax_page_prep WHERE page_id ='.$page_id;
    }

    // удаление из таблицы дисциплины-группы
    function delete_page_group($page_id)
    {
    return 'DELETE FROM ax_page_group WHERE page_id ='.$page_id;
    }

    // получения файлов для задание
    function select_task_file($type, $task_id)
    {
    return 'select * from ax_task_file where type ='.$type.' and task_id ='.$task_id;
    }

    // обновление задания
    function update_task($id, $type, $title, $description)
    {
    return "UPDATE ax_task SET type = '$type', title = '$title', description = '$description' WHERE id = '$id'";
    }
    // обновление текста файла
    function update_file($type, $task_id, $full_text)
    {
    return "UPDATE ax_task_file SET full_text = '$full_text' where task_id = '$task_id' and type = '$type'";
    }
    // добавление файла
    function insert_file($type, $task_id, $file_name, $full_text)
    {
    return "INSERT INTO ax_task_file(type, task_id, file_name, full_text)
    values ('$type', '$task_id', '$file_name', '$full_text')";
    }
    
?>
