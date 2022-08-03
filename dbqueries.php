<?php
    // ОБЩИЕ
    function select_timestamp($shift) {
        if (trim($shift) == "")
            $shift = '0';
        return 'SELECT to_char(now() + \''.$shift.'\', \'YYYY-MM-DD HH24:MI:SS\') val';
    }

    function select_check_timestamp($datetime) {
        return 'SELECT to_timestamp(\''.$datetime.'\', \'YYYY-MM-DD HH24:MI:SS\')';
    }

    function get_user_name($id) {
        return "SELECT first_name || ' ' || middle_name fio FROM students WHERE id = $id;";
    }

    function update_user_email($id, $email) {
        return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
            VALUES ('$id', '$email', null, 'TRUE')
            ON CONFLICT (user_id) DO UPDATE
            SET email = '$email';
        ";
    }

    function update_user_notify_type($id, $notify_type){
        if($notify_type=="ON") $notify_type = 1;
        else $notify_type = 0;
        return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
            VALUES ('$id', null, '$notify_type', 'TRUE')
            ON CONFLICT (user_id) DO UPDATE 
            SET notification_type = $notify_type;
        ";
    }

    function get_user_info($id){
        return "SELECT first_name || ' ' || middle_name || ' ' || last_name as fio, 
            groups.name as group_name, ax_settings.email, ax_settings.notification_type, ax_settings.monaco_dark
            FROM students
            INNER JOIN students_to_groups ON students_to_groups.student_id =  students.id
            INNER JOIN groups ON groups.id = students_to_groups.group_id
            LEFT JOIN ax_settings ON ax_settings.user_id = students.id
            WHERE students.id = $id;
        ";
    }

    

// ДЕЙСТВИЯ СО СТРАНИЦАМИ ПРЕДМЕТОВ
    function select_active_pages(){
        return 'SELECT ax_page.disc_id, ax_page.year, ax_page.semester FROM ax_page WHERE status = 1;';
    }

    // - получение названия дисциплины + предмета
    function select_page_name($page_id, $status) {
        // p.id as pid, d.id as did, d.name as dname, p.short_name as pname
        return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS name
                FROM ax_page p INNER JOIN discipline d ON d.id = p.disc_id
                WHERE p.id=" . $page_id . " AND p.status=" . $status;
    }

    function select_page_names($status) {
        return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS names
                FROM ax_page p INNER JOIN discipline d ON d.id = p.disc_id 
                WHERE p.status = " . $status . "ORDER BY p.semester";
    }

    // Страница предмета
    function select_discipline_page($id) {
        return "SELECT * FROM ax_page WHERE id =". $id;
    }

    // Страницы дисциплин для конкретного студента
    function select_discipline_page_by_semester($semester) {
        return "SELECT * FROM ax_page WHERE semester <=". $semester;
    }

    // Все года и семестры
    function select_discipline_timestamps()
    {
    return 'SELECT distinct year, semester FROM ax_page ORDER BY year desc';
    }

    // Все года
    function select_discipline_years(){
        return 'SELECT distinct year FROM ax_page ORDER BY year desc'; 
    }

    // Изменение страницы дисциплины
    function update_discipline($discipline) {
        $timestamp = convert_timestamp_from_string($discipline['timestamp']);
        $short_name = pg_escape_string($discipline['short_name']);
        $id = pg_escape_string($discipline['id']);
        $disc_id = pg_escape_string($discipline['disc_id']);
        $year = pg_escape_string($timestamp['year']);
        $semester = pg_escape_string($timestamp['semester']);

        return "UPDATE ax_page SET short_name ='$short_name', disc_id='$disc_id', year='$year', semester='$semester' 
            WHERE id ='$id'";
    }

    function insert_discipline($discipline) {
        $timestamp = convert_timestamp_from_string($discipline['timestamp']);
        $short_name = pg_escape_string($discipline['short_name']);
        $id = pg_escape_string($discipline['id']);
        $disc_id = pg_escape_string($discipline['disc_id']);
        $year = pg_escape_string($timestamp['year']);
        $semester = pg_escape_string($timestamp['semester']);

        return "INSERT INTO ax_page (disc_id, short_name, year, semester) 
            VALUES ('$disc_id', '$short_name', '$year', '$semester') returning id";
    }



// ДЕЙСТВИЯ С УВЕДОМЛЕНИЯМИ

    // получение уведомлений для студента по невыполненным заданиям
    function select_notify_for_student($student_id){
        return "SELECT ax_task.id, ax_task.page_id, ax_page.short_name, ax_task.title, ax_assignment.status_code FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            WHERE ax_assignment_student.student_user_id = $student_id AND ax_page.status = 1 
            AND (ax_assignment.status_code = 2 OR ax_assignment.status_code = 3);
        ";
    }

    // получение уведомлений для преподавателя по непроверенным заданиям
    function select_notify_for_teacher($teacher_id){
        return "SELECT ax_task.id, ax_task.page_id, ax_page.short_name, ax_task.title, ax_assignment.status_code, 
            ax_assignment_student.student_user_id, students.middle_name, students.first_name FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            WHERE ax_page_prep.prep_user_id = $teacher_id AND ax_assignment.status_code = 5;
        ";
    }

    // получение количества уведомлений по каждой странице предмета для преподавательского дэшборда
    function select_notify_count_by_page($page_id){
        return "SELECT COUNT(*) FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            WHERE ax_page.id = $page_id AND ax_assignment.status_code = 5;
        ";
    }

    function select_notify_count_by_student($page_id, $student_id){
        return "SELECT COUNT(*) FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            WHERE ax_page.id = $page_id AND ax_assignment.status_code = 5 AND student.id='$student_id';
        ";
    }

    // получение уведомлений по каждой странице предмета для преподавательского дэшборда
    function select_notify_by_page($teacher_id, $page_id){
        return "SELECT ax_task.id, ax_task.page_id, ax_page.short_name, ax_task.title, ax_assignment.id as assignment_id, ax_assignment.status_code, 
            ax_assignment_student.student_user_id, students.middle_name, students.first_name FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            WHERE ax_page_prep.prep_user_id = $teacher_id AND ax_assignment.status_code = 5 AND ax_page.id = $page_id;
        ";
    }

    // получений уведомлений по каждому непрочитанному сообщению и непроверенному заданию
    function select_message_notify_by_page($teacher_id, $page_id){
        return "SELECT ax_task.id, ax_task.page_id, ax_task.title, ax_assignment.status_code, 
            ax_assignment_student.student_user_id FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            WHERE ax_page_prep.prep_user_id = $teacher_id AND ax_assignment.status_code = 5 AND ax_page.id = $page_id;
        ";
    }

    function select_message_count_by_page_for_teacher($page_id){
        return "SELECT ax_task.id as task_id, students.id as student_id, COUNT(*) FROM ax_task
            INNER JOIN ax_page ON ax_page.id = ax_task.page_id
            INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            INNER JOIN ax_message ON ax_message.assignment_id = ax_assignment.id
            INNER JOIN ax_message_delivery ON ax_message_delivery.message_id = ax_message.id  
            WHERE ax_page.id = $page_id AND ax_message_delivery.read = FALSE;
        ";
    }

    function select_count_unreaded_messages_by_task($student_id, $task_id){
        return "SELECT COUNT(*) FROM ax_message
            INNER JOIN ax_assignment ON ax_assignment.id = ax_message.assignment_id
            INNER JOIN ax_task ON ax_task.id = ax_assignment.task_id
            INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
            INNER JOIN students ON students.id = ax_assignment_student.student_user_id
            INNER JOIN ax_message_delivery ON ax_message_delivery.message_id = ax_message.id  
            WHERE ax_message_delivery.read = FALSE AND students.id = '$student_id' AND ax_task.id = '$task_id';
        ";
    }


    
    

// ДЕЙСТВИЯ С ЗАДАНИЯМИ

    function select_task($task_id) {
        return 'SELECT type, title, description FROM ax_task WHERE id ='.$task_id;
    }

    // - получение статуса и времени отправки ответа студента
    function select_task_assignment($task_id, $student_id) {
        return "SELECT ax_assignment.finish_limit, ax_assignment.status_code, ax_assignment.mark, ax_assignment.status_text FROM ax_assignment 
            INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id 
            WHERE ax_assignment_student.student_user_id = ". $student_id ." AND ax_assignment.task_id = ". $task_id ." LIMIT 1;";
    }

    // - получение всех заданий по странице дисциплины
    function select_page_tasks($page_id, $status) {
        return "SELECT * FROM ax_task WHERE page_id = '$page_id' AND status = '$status' ORDER BY id";
    }

    function select_page_tasks_with_assignment($page_id, $status, $student_id) {
        return "SELECT ax_task.id, ax_task.page_id, ax_task.title, ax_task.status, ax_assignment.id as assignment_id FROM ax_task 
        INNER JOIN ax_assignment ON ax_task.id = ax_assignment.task_id
        INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id 
        INNER JOIN students ON students.id = ax_assignment_student.student_user_id 
        WHERE page_id = '$page_id' AND status = '$status' AND students.id = '$student_id' ORDER BY id";
    }
    
    // - получение студентов, которым назначено задание
    function select_assigned_students($task_id)
    {
        return "SELECT students.middle_name || ' ' || students.first_name fio, ax_assignment.id aid, to_char(ax_assignment.finish_limit, \'DD-MM-YYYY HH24:MI:SS\') ts 
              FROM ax_task 
              INNER JOIN ax_assignment ON ax_task.id = ax_assignment.task_id 
              INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id 
              INNER JOIN students ON students.id = ax_assignment_student.student_user_id 
              WHERE ax_task.id = '$task_id'
              ORDER BY ax_assignment.id";
    }
    
    // получение файлов к заданию
    function select_task_files($task_id)
    {
        return 'SELECT ax_task_file.* '.
                ' FROM ax_task INNER JOIN ax_task_file ON ax_task.id = ax_task_file.task_id '.
                ' WHERE ax_task.id = '.$task_id.' AND ax_task_file.type = 0 '.
				' ORDER BY id';
    }

    // обновление задания
    function update_task($id, $type, $title, $description) {
        return "UPDATE ax_task SET type = '$type', title = '$title', description = '$description' WHERE id = '$id'";
    }



// ДЕЙСТВИЯ С СООБЩЕНИЯМИ

    // отправка ответа на сообщение
    function insert_message($message_id, $message_text, $mark, $sender_id) {
        if ($message_text != null)
            $message_text = str_replace("'", "''", $message_text);
        if ($mark != null)
            $mark = str_replace("'", "''", $mark);
        if ($mark != null)
        {
            return "UPDATE ax_assignment set mark='$mark' WHERE id in (SELECT assignment_id FROM ax_message WHERE id=$message_id);\n" .
                   "UPDATE ax_message set status=1 WHERE id=$message_id AND status=0;\n" .
                   "INSERT INTO ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
                   "(SELECT assignment_id, 2, 1, $sender_id, now(), $message_id, '$message_text<br/>\nОценка: $mark', null, 0 FROM ax_message WHERE id=$message_id);";
        } else {
            return "UPDATE ax_message set status=1 WHERE id=$message_id AND status=0;\n" .
                   "INSERT INTO ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
                   "(SELECT assignment_id, 0, 1, $sender_id, now(), $message_id, '$message_text', null, 0 FROM ax_message WHERE id=$message_id);";
        }
    }



// ДЕЙСТВИЯ С ДИСЦИПЛИНАМИ

    function select_all_disciplines() {
        return 'SELECT * FROM discipline';
    }

    // - gjkextybt
    function select_discipline_name($disc_id){
        return "SELECT name FROM discipline WHERE id =". $disc_id;
    }
    

    
// ДЕЙСТВИЯ С ГРУППАМИ
    function select_groups(){
        return 'SELECT * FROM groups';
    }

    // группы у конкретной дисциплины
    function select_discipline_groups($page_id) {
        return 'SELECT name FROM groups INNER JOIN ax_page_group ON groups.id = ax_page_group.group_id WHERE page_id ='.$page_id;
    }

    // удаление из таблицы дисциплины-группы
    function delete_page_group($page_id) {
        return 'DELETE FROM ax_page_group WHERE page_id ='.$page_id;
    }

    function update_ax_page_group($id, $groups) {
        $groups = pg_escape_string($groups);

        return "INSERT INTO ax_page_group(group_id, page_id)
            VALUES ((SELECT id FROM groups WHERE name = '$groups'), '$id')";
    }

    function select_page_groups($page_id) {
        return "SELECT groups.id id, groups.name grp
                FROM ax_page_group 
	            INNER JOIN groups ON groups.id = ax_page_group.group_id
                WHERE ax_page_group.page_id = '$page_id'
                ORDER BY grp";
    }

    function select_page_students($page_id) {
        return "SELECT students.middle_name || ' ' || students.first_name fio, students.id id
                FROM ax_page_group 
	            INNER JOIN students_to_groups ON ax_page_group.group_id = students_to_groups.group_id
	            INNER JOIN students ON students_to_groups.student_id = students.id
                WHERE ax_page_group.page_id = '$page_id'
                ORDER BY fio";
    }

    function select_page_students_grouped($page_id) {
        return "SELECT students.middle_name || ' ' || students.first_name fio, students.id id, ax_student_page_info.variant_num vnum, ax_student_page_info.variant_comment vtext, groups.name grp, groups.id gid
                FROM ax_page_group
	            INNER JOIN students_to_groups ON ax_page_group.group_id = students_to_groups.group_id
	            INNER JOIN students ON students_to_groups.student_id = students.id
	            INNER JOIN groups ON groups.id = students_to_groups.group_id
	            LEFT JOIN ax_student_page_info ON ax_student_page_info.student_user_id = students.id AND ax_student_page_info.page_id=ax_page_group.page_id
                WHERE ax_page_group.page_id = '$page_id'
                ORDER BY grp, fio";
    }



// ДЕЙСТВИЯ С ПРЕПОДАВАТЕЛЯМИ

    // все преподователи
    function select_teacher_name() {
        return 'SELECT student_id, first_name, middle_name, last_name FROM students_to_groups INNER JOIN students ON students_to_groups.student_id = students.id WHERE group_id = 29';
    }

    // преподователи у конкретной дисциплины
    function select_page_prep_name($page_id) {
        return 'SELECT first_name, middle_name FROM ax_page_prep INNER JOIN students ON students.id = ax_page_prep.prep_user_id WHERE page_id ='.$page_id;
    }

    // удаление из таблицы дисциплины-преподователи
    function delete_page_prep($page_id) {
        return 'DELETE FROM ax_page_prep WHERE page_id ='.$page_id;
    }

    function prep_ax_prep_page($id, $first_name, $middle_name) {
        $first_name = pg_escape_string($first_name);
        $middle_name = pg_escape_string($middle_name);

        return "INSERT INTO ax_page_prep(id, prep_user_id, page_id)
            VALUES(default, (SELECT id FROM students WHERE first_name = '$first_name' AND middle_name = '$middle_name'),'$id')";
    }



// ДЕЙСТВИЯ С ФАЙЛАМИ

    // получения файлов для задание
    function select_task_file($type, $task_id) {
        return 'SELECT * FROM ax_task_file WHERE type ='.$type.' AND task_id ='.$task_id;
    }

    // обновление текста файла
    function update_file($type, $task_id, $full_text) {
        return "UPDATE ax_task_file SET full_text = '$full_text' WHERE task_id = '$task_id' AND type = '$type'";
    }

    // добавление файла
    function insert_file($type, $task_id, $file_name, $full_text) {
        return "INSERT INTO ax_task_file(type, task_id, file_name, full_text)
                VALUES ('$type', '$task_id', '$file_name', '$full_text')";
    }



// ПРОЧЕЕ

    function pg_fetch_all_assoc($res) {
        if (PHP_VERSION_ID >= 70100) 
            return pg_fetch_all($res, PGSQL_ASSOC);
        $array_out = array();
        while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
            $array_out[] = $row;
        }
        return $array_out;
    }
    
    // получение сообщений для таблицы посылок
    function select_page_messages($page_id) {
        return "SELECT s1.middle_name || ' ' || s1.first_name fio, groups.name grp, 
            ax_task.id tid, ax_assignment.id aid, m1.id mid, ax_assignment_student.student_user_id sid, 
            ax_message_attachment.id fid, m1.type,
            case WHEN ax_assignment.mark is not null then ax_assignment.mark
            WHEN ax_assignment.status_code in (0, 1) then 'X'
            WHEN ax_assignment.status_code in (4) then '-'
            WHEN m1.sender_user_type = 0 then '?' 
            WHEN m1.sender_user_type = 1 then '!'
            else null end val, ax_task.title task, ax_task.max_mark max_mark, 
            ax_assignment.mark amark, ax_assignment.delay adelay, ax_assignment.status_code astatus, 
            ax_assignment.status_text astext, to_char(m1.date_time, 'DD-MM-YYYY HH24:MI:SS') mtime, 
            m1.full_text mtext, m1.sender_user_type mtype, m1.status mstatus, m2.full_text mreply, 
            s2.middle_name || ' ' || s2.first_name mfio, s2.login mlogin, ax_message_attachment.file_name as mfile, 
            ax_message_attachment.download_url as murl FROM ax_task 

            INNER JOIN ax_assignment ON ax_task.id = ax_assignment.task_id AND ax_assignment.status_code in (2,3,4)
            INNER JOIN ax_assignment_student ON ax_assignment.id = ax_assignment_student.assignment_id
            INNER JOIN students s1 ON s1.id = ax_assignment_student.student_user_id 
            LEFT JOIN ax_message m1 ON ax_assignment.id = m1.assignment_id 
            AND (m1.sender_user_id=ax_assignment_student.student_user_id or m1.sender_user_type=1) AND m1.status in (0,1)
            LEFT JOIN ax_message m2 ON m1.reply_to_id = m2.id
            LEFT JOIN students s2 ON s2.id = m1.sender_user_id
            LEFT JOIN ax_message_attachment ON m1.id = ax_message_attachment.message_id
            INNER JOIN students_to_groups ON s1.id = students_to_groups.student_id
            INNER JOIN groups ON groups.id = students_to_groups.group_id
            WHERE ax_task.page_id = '$page_id' ORDER BY mid DESC
        ";
    }

    
?>
