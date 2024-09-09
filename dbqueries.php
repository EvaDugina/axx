<?php
// ОБЩИЕ
function select_timestamp($shift)
{
  if (trim($shift) == "")
    $shift = '0';
  return 'SELECT to_char(now() + \'' . $shift . '\', \'YYYY-MM-DD HH24:MI:SS\') val';
}

function select_check_timestamp($datetime)
{
  return 'SELECT to_timestamp(\'' . $datetime . '\', \'YYYY-MM-DD HH24:MI:SS\')';
}

function get_user_name($id)
{
  return "SELECT first_name, middle_name FROM students WHERE id = $id;";
}


// - получение названия дисциплины + предмета
function select_page_name($page_id)
{
  // p.id as pid, d.id as did, d.name as dname, p.short_name as pname
  return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS name
            FROM ax.ax_page p INNER JOIN discipline d ON d.id = p.disc_id
            WHERE p.id='$page_id' ";
}

function select_ax_page_short_name($page_id)
{
  return "SELECT short_name FROM ax.ax_page
          WHERE id = $page_id;
  ";
}

function select_page_names($status)
{
  return "SELECT p.id, d.name ||  ': ' || p.short_name || ' (' || p.semester || ' семестр) ' AS names
            FROM ax.ax_page p INNER JOIN discipline d ON d.id = p.disc_id 
            WHERE p.status = " . $status . "ORDER BY p.semester";
}

function select_page_by_task_id($task_id)
{
  return "SELECT page_id FROM ax.ax_task WHERE id = $task_id";
}

function select_inside_semester_pages_for_teacher($teacher_id)
{
  return "SELECT p.*, get_semester(year, semester) sem, p.year y, p.semester s, ax.ax_color_theme.src_url
  FROM ax.ax_page p
  INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = p.id
  LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = p.color_theme_id
  WHERE p.year IS null AND p.semester IS null AND ax.ax_page_prep.prep_user_id = $teacher_id
";
}

function select_pages_for_teacher($teacher_id)
{
  return "SELECT p.*, get_semester(year, semester) sem, p.year y, p.semester s, ax.ax_color_theme.src_url
          FROM ax.ax_page p
          INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = p.id
          LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = p.color_theme_id
          WHERE p.year IS NOT null AND p.semester IS NOT null AND ax.ax_page_prep.prep_user_id = $teacher_id
          ORDER BY p.year DESC, p.semester DESC
  ";
}

function select_inside_semester_pages_for_admin()
{
  return "SELECT p.*, get_semester(year, semester) sem, p.year y, p.semester s, ax.ax_color_theme.src_url
          FROM ax.ax_page p
          LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = p.color_theme_id
          WHERE p.year IS null AND p.semester IS null
  ";
}

function select_pages_for_admin()
{
  return "SELECT p.*, get_semester(year, semester) sem, p.year y, p.semester s, ax.ax_color_theme.src_url
          FROM ax.ax_page p
          LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = p.color_theme_id
          WHERE p.year IS NOT null AND p.semester IS NOT null
          ORDER BY p.year DESC, p.semester DESC
  ";
}

function select_pages_for_student($group_id)
{
  return "SELECT p.*, get_semester(year, semester) sem, p.year y, p.semester, ax.ax_color_theme.src_url 
          FROM ax.ax_page p
          INNER JOIN ax.ax_page_group ON ax.ax_page_group.page_id = p.id
          LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = p.color_theme_id
          WHERE p.year IS NOT null AND p.semester IS NOT null AND ax.ax_page_group.group_id = $group_id AND p.status = 1
          ORDER BY p.year DESC, p.semester DESC;
  ";
}

// Страница предмета
function select_discipline_page($page_id)
{
  return "SELECT ax.ax_page.*, discipline.name AS disc_name, ax.ax_color_theme.id as color_theme_id, ax.ax_color_theme.bg_color FROM ax.ax_page 
            LEFT JOIN discipline ON discipline.id = ax.ax_page.disc_id 
            LEFT JOIN ax.ax_color_theme ON ax.ax_color_theme.id = ax.ax_page.color_theme_id
            WHERE ax.ax_page.id = '$page_id';
    ";
}

// Все года и семестры
function select_discipline_timestamps()
{
  return 'SELECT distinct year, semester FROM ax.ax_page ORDER BY year desc';
}

// Все года
function select_discipline_years()
{
  return 'SELECT max(year) as max, min(year) as min FROM ax.ax_page';
}

// Изменение страницы дисциплины
function update_discipline($discipline)
{
  $short_name = pg_escape_string($discipline['short_name']);
  $id = pg_escape_string($discipline['id']);

  $disc_id = pg_escape_string($discipline['disc_id']);
  $disc_sql = "";
  if ($disc_id == "null") {
    $disc_id = null;
    $disc_sql = "disc_id = null";
  } else {
    $disc_sql = "disc_id = $disc_id";
  }

  $color_theme_id = pg_escape_string($discipline['color_theme_id']);
  $page_description = pg_escape_string($discipline['page-description']);

  $timestamp = trim($discipline['timestamp']);
  if ($timestamp != "ВНЕ CЕМЕСТРА") {
    $type = 0;
    $timestamp = convert_timestamp_from_string($timestamp);
    $year = pg_escape_string($timestamp['year']);
    $semester = pg_escape_string($timestamp['semester']);
  } else {
    $type = 1;
  }

  if ($type == 0) {
    return "UPDATE ax.ax_page SET short_name ='$short_name', " . $disc_sql . ", year='$year', semester='$semester',
            color_theme_id='$color_theme_id', type = $type, status=1, description = \$antihype1\$$page_description\$antihype1\$
            WHERE id ='$id'";
  } else {
    return "UPDATE ax.ax_page SET short_name ='$short_name', " . $disc_sql . ",
            color_theme_id='$color_theme_id', year=null, semester=null,
            type = $type, status=1, description = \$antihype1\$$page_description\$antihype1\$
            WHERE id ='$id'";
  }
}

function insert_page($discipline)
{
  $short_name = pg_escape_string($discipline['short_name']);

  $disc_id = pg_escape_string($discipline['disc_id']);
  $disc_sql = "";
  if ($disc_id == "null") {
    $disc_id = null;
    $disc_sql = "null";
  } else {
    $disc_sql = "$disc_id";
  }

  $color_theme_id = pg_escape_string($discipline['color_theme_id']);
  $creator_id = pg_escape_string($discipline['creator_id']);
  $creation_date = getNowTimestamp();

  $page_description = pg_escape_string($discipline['page-description']);

  $timestamp = trim($discipline["timestamp"]);
  if ($timestamp != "ВНЕ CЕМЕСТРА") {
    $type = 0;
    $timestamp = convert_timestamp_from_string($timestamp);
    $year = pg_escape_string($timestamp['year']);
    $semester = pg_escape_string($timestamp['semester']);
  } else {
    $type = 1;
  }

  if ($type == 0) {
    return "INSERT INTO ax.ax_page (disc_id, short_name, year, semester, color_theme_id, creator_id, creation_date, type, status, description) 
        VALUES (" . $disc_sql . ", '$short_name', '$year', '$semester', '$color_theme_id', '$creator_id', '$creation_date', $type, 1, \$antihype1\$$page_description\$antihype1\$) returning id";
  } else {
    return "INSERT INTO ax.ax_page (disc_id, short_name, color_theme_id, creator_id, creation_date, type, status, description) 
        VALUES (" . $disc_sql . ", '$short_name', '$color_theme_id', '$creator_id', '$creation_date', $type, 1, \$antihype1\$$page_description\$antihype1\$) returning id";
  }
}



// ДЕЙСТВИЯ С УВЕДОМЛЕНИЯМИ

// получение уведомлений, отсортированных по message_id для студента по невыполненным заданиям
//[x]: убрать функцию, как только не будет использоваться
// function select_notify_for_student_header($student_id){
//     return "SELECT DISTINCT ON (ax_assignment.id) ax.ax_assignment.id as aid, ax.ax_task.id as task_id, ax.ax_page.id as page_id, ax.ax_page.short_name, ax.ax_task.title, ax.ax_assignment.status_code, ax.ax_assignment.status, 
//               teachers.first_name || ' ' || teachers.last_name as teacher_io, ax.ax_message.id as message_id, ax.ax_message.full_text FROM ax.ax_task
//             INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
//             INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
//             INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
//             INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
//             INNER JOIN ax.ax_message ON ax.ax_message.assignment_id = ax.ax_assignment.id
//             INNER JOIN students teachers ON teachers.id = ax.ax_message.sender_user_id
//             WHERE ax.ax_assignment_student.student_user_id = $student_id AND ax.ax_page.status = 1 AND ax.ax_message.sender_user_type != 3 
//             AND ax.ax_message.status = 0 AND (ax_message.visibility = 3 OR ax.ax_message.visibility = 0);
//     ";
// }

// получение уведомлений для преподавателя по непроверенным заданиям
//[x]: убрать функцию, как только не будет использоваться
// function select_notify_for_teacher_header($teacher_id){
//     return "SELECT DISTINCT ON (ax_assignment.id) ax.ax_assignment.id as aid, ax.ax_task.id as task_id, ax.ax_task.page_id, ax.ax_page.short_name, ax.ax_task.title, 
//                 ax.ax_assignment.id as assignment_id, ax.ax_assignment.status_code, ax.ax_assignment.status, ax.ax_assignment_student.student_user_id,
//                 s1.middle_name, s1.first_name FROM ax.ax_task
//             INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
//             INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
//             INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
//             INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
//             INNER JOIN students s1 ON s1.id = ax.ax_assignment_student.student_user_id 
//             LEFT JOIN ax.ax_message ON ax.ax_message.assignment_id = ax.ax_assignment.id
//             LEFT JOIN students s2 ON s2.id = ax.ax_message.sender_user_id
//             WHERE ax.ax_page_prep.prep_user_id = $teacher_id AND ax.ax_message.sender_user_type != 2 
//             AND ax.ax_message.status = 0 AND (ax_message.visibility = 2 OR ax.ax_message.visibility = 0);
//     ";
// }

// получение количества уведомлений по каждой странице предмета для преподавательского дэшборда
function select_notify_count_by_page_for_mainpage($page_id)
{
  return "SELECT COUNT(*) FROM ax.ax_task
        INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
        INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
        WHERE ax.ax_page.id = $page_id AND ax.ax_assignment.status = 1 AND ax.ax_task.status = 1;
    ";
}

// получение уведомлений по каждой странице предмета для преподавательского дэшборда
function select_notify_by_page_for_mainpage($teacher_id, $page_id)
{
  return "SELECT ax.ax_task.id, ax.ax_task.page_id, ax.ax_page.short_name, ax.ax_task.title, ax.ax_assignment.id as assignment_id, ax.ax_assignment.status_code, 
        ax.ax_assignment_student.student_user_id, students.middle_name, students.first_name FROM ax.ax_task
        INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
        INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
        INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
        INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
        INNER JOIN students ON students.id = ax.ax_assignment_student.student_user_id
        WHERE ax.ax_page_prep.prep_user_id = $teacher_id AND ax.ax_assignment.status = 1 AND ax.ax_page.id = $page_id;
    ";
}

function select_notify_by_page_for_mainpage_for_admin($teacher_id, $page_id)
{
  return "SELECT ax.ax_task.id, ax.ax_task.page_id, ax.ax_page.short_name, ax.ax_task.title, ax.ax_assignment.id as assignment_id, ax.ax_assignment.status_code, 
        ax.ax_assignment_student.student_user_id, students.middle_name, students.first_name FROM ax.ax_task
        INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
        INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
        INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
        INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
        INNER JOIN students ON students.id = ax.ax_assignment_student.student_user_id
        WHERE ax.ax_page_prep.prep_user_id = $teacher_id AND ax.ax_assignment.status = 1 AND ax.ax_page.id = $page_id;
    ";
}

// // получение уведомлений по каждой странице предмета для преподавательского дэшборда
// function select_unchecked_by_page($teacher_id, $page_id){
//     return "SELECT ax.ax_task.id, ax.ax_assignment.id as assignment_id, ax.ax_assignment.status_code, ax.ax_assignment.status, 
//         ax.ax_assignment_student.student_user_id FROM ax.ax_task
//         INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
//         INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
//         INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
//         INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
//         INNER JOIN students ON students.id = ax.ax_assignment_student.student_user_id
//         WHERE ax.ax_page_prep.prep_user_id = $teacher_id AND ax.ax_assignment.status = 1 AND ax.ax_page.id = $page_id;
//     ";
// }

//[x]: убрать
// function select_count_unreaded_messages_by_task_for_teacher($teacher_id, $task_id){
//     return "SELECT COUNT(*) FROM ax.ax_message
//         INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
//         INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
//         INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id
//         WHERE ax.ax_message.status = 0 AND ax.ax_assignment_student.student_user_id = $teacher_id AND ax.ax_task.id = $task_id
//         AND ax.ax_message.sender_user_type != 2 AND ax.ax_message.type != 3;
//     ";
// }


//[x]: убрать
// function select_count_unreaded_messages_by_task_for_student($student_id, $task_id){
//     return "SELECT COUNT(*) FROM ax.ax_message
//         INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
//         INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
//         INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id
//         WHERE ax.ax_message.status = 0 AND ax.ax_assignment_student.student_user_id = $student_id AND ax.ax_task.id = $task_id
//         AND ax.ax_message.sender_user_type != 3;
//     ";
// }






// ДЕЙСТВИЯ С ЗАДАНИЯМИ

function select_task($task_id)
{
  return "SELECT * FROM ax.ax_task WHERE id ='$task_id'";
}

function select_ax_assignment_by_id($assignment_id)
{
  return "SELECT * FROM ax.ax_assignment WHERE id = $assignment_id";
}

// - получение статуса и времени отправки ответа студента
function select_task_assignment_with_limit($task_id, $student_id)
{
  return "SELECT ax.ax_assignment.id, ax.ax_assignment.finish_limit, ax.ax_assignment.status_code, ax.ax_assignment.status, ax.ax_assignment.mark, ax.ax_assignment.status_text FROM ax.ax_assignment 
        INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id 
        WHERE ax.ax_assignment_student.student_user_id = " . $student_id . " AND ax.ax_assignment.task_id = " . $task_id . " LIMIT 1;";
}

function select_task_assignment_student_id($student_id, $task_id)
{
  return "SELECT ax.ax_assignment.id FROM ax.ax_assignment
            INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id
            WHERE ax.ax_assignment_student.student_user_id = $student_id AND ax.ax_assignment.task_id = $task_id;
    ";
}

// function select_assignment_with_task($student_id, $task_id){
//   return "SELECT ax.ax_assignment.id, ax.ax_task.title, ax.ax_task.status, ax.ax_task.id as tid, ax.ax_task.page_id as pid,
//     ax.ax_assignment.finish_limit, ax.ax_assignment.status_code, ax.ax_assignment.status as astatus, ax.ax_assignment.mark, ax.ax_task.max_mark, ax.ax_assignment.status_text FROM ax.ax_task 
//     INNER JOIN ax.ax_assignment ON ax.ax_task.id = ax.ax_assignment.task_id
//     INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id 
//     INNER JOIN students ON students.id = ax.ax_assignment_student.student_user_id 
//     WHERE ax.ax_task.id = '$task_id' AND students.id = '$student_id' ORDER BY id";
// }

// - получение всех заданий по странице дисциплины
function select_page_tasks($page_id, $status)
{
  return "SELECT * FROM ax.ax_task WHERE page_id = '$page_id' AND status = '$status' AND (type = 0 OR type = 1) ORDER BY id";
}

// - получение количества всех заданий по странице дисциплины
function select_count_page_tasks($page_id)
{
  return "SELECT COUNT(*) FROM ax.ax_task WHERE page_id = '$page_id'";
}

function select_ax_assignment_with_task_by_id($assignment_id)
{
  return "SELECT * FROM ax.ax_assignment 
          INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
          WHERE ax.ax_assignment.id = $assignment_id;        
  ";
}

// - получение студентов по коммитам аналогичного задания
function select_prev_students($assignment_id)
{
  return "SELECT students.id as sid, students.middle_name || ' ' || students.first_name fio, ax.ax_task.id as tid,
                  ax.ax_assignment.id aid, to_char(ax_assignment.finish_limit, 'DD-MM-YYYY HH24:MI:SS') ts 
        FROM ax.ax_task 
        INNER JOIN ax.ax_assignment ON ax.ax_task.id = ax.ax_assignment.task_id 
        INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id 
        INNER JOIN students ON students.id = ax.ax_assignment_student.student_user_id 
        WHERE ax.ax_assignment.id in (select a.id from ax.ax_assignment sa inner join ax.ax_assignment a on sa.task_id = a.task_id and sa.id != a.id where sa.id = $assignment_id)
        ORDER BY ax.ax_assignment.id";
}

// - получение файлов по коммитам аналогичного задания
function select_prev_files($assignment_id)
{
  return "SELECT ax.ax_file.id, ax.ax_solution_commit.assignment_id, ax.ax_solution_commit.id as commit_id, 
          ax.ax_file.file_name, ax.ax_file.full_text 
          from ax.ax_solution_commit 
          INNER JOIN ax.ax_commit_file ON ax.ax_commit_file.commit_id = ax.ax_solution_commit.id  
          INNER JOIN ax.ax_file ON ax.ax_file.id = ax.ax_commit_file.file_id
           where ax.ax_solution_commit.assignment_id in (select a.id from ax.ax_assignment sa inner join ax.ax_assignment a on sa.task_id = a.task_id and sa.id != a.id where sa.id = $assignment_id) 
             and commit_id in (select max(id) from ax.ax_solution_commit group by assignment_id)";
}

// получение файлов к заданию
// function select_task_files($task_id) {
//     return 'SELECT ax.ax_task_file.* '.
//             ' FROM ax.ax_task INNER JOIN ax.ax_task_file ON ax.ax_task.id = ax.ax_task_file.task_id '.
//             ' WHERE ax.ax_task.id = '.$task_id.' AND ax.ax_task_file.type = 0 '.
//     ' ORDER BY id';
// }

// обновление задания
function update_ax_task_status($id, $status)
{
  return "UPDATE ax.ax_task SET status = $status WHERE id = $id";
}

// обновление задания
function update_ax_task($id, $type, $title, $description)
{
  return "UPDATE ax.ax_task SET type = '$type', title = '$title', description = '$description' 
            WHERE id = '$id'";
}

// function update_ax_assignment_status_code($assignment_id, $status_code) {
//   $status_text = status_code_to_text($status_code);
//   return "UPDATE ax.ax_assignment SET status_code = $status_code, status_text = '$status_text' 
//           WHERE id = $assignment_id";
// }

function update_ax_assignment_mark($assignment_id, $mark)
{
  return "UPDATE ax.ax_assignment SET mark = '$mark', status = 4, status_text = 'выполнено' 
          WHERE id = $assignment_id;
  ";
}

function update_ax_assignment_delay($assignment_id, $delay)
{
  return "UPDATE ax.ax_assignment SET delay = $delay 
          WHERE id = $assignment_id;
  ";
}

function update_ax_assignment_finish_limit($assignment_id, $finish_limit)
{
  return "UPDATE ax.ax_assignment SET finish_limit = '$finish_limit' 
            WHERE id = $assignment_id;
  ";
}

// function update_ax_assignment_by_id ($assignment_id, $finish_limit=null, $variant=null, $status_code=-1, $delay=-1, $status_text=null, $mark=null){  
//   $query = "UPDATE ax.ax_assignment SET ";
//   if ($finish_limit)
//     $query .= "finish_limit = '$finish_limit'";
//   if ($variant)
//     $query .= ", variant_number = '$variant'";
//   if ($status_code != -1)
//     $query .= ", status_code = '$status_code'";
//   if ($delay != -1)
//     $query .= ", delay = '$delay'";
//   if ($status_text)
//     $query .= ", status_text = '$status_text'";
//   if ($mark)
//     $query .= ", mark='$mark'";
//   $query .= "WHERE id = '$assignment_id';";
//   return $query;
// }

function insert_ax_task($page_id, $type, $title, $description, $max_mark = 5, $status = 1)
{
  return "INSERT INTO ax.ax_task (page_id, type, title, description, max_mark, status) 
          VALUES ('$page_id', '$type', '$title', '$description', $max_mark, '$status')
          RETURNING id;
  ";
}

function insert_assignment($task_id)
{
  return "INSERT INTO ax.ax_assignment(task_id, variant_number, start_limit, finish_limit, status_code, status, delay, status_text, mark)
          VALUES ($task_id, null, now(), now()+'1 year', 2, null, null, null, null) RETURNING id;
  ";
}

function insert_assignment_student($assignment_id, $student_id)
{
  return "INSERT INTO ax.ax_assignment_student (assignment_id, student_user_id)
          VALUES ($assignment_id, $student_id);
  ";
}



// ДЕЙСТВИЯ С СООБЩЕНИЯМИ


// отправка ответа на сообщение
function insert_message_reply($message_id, $message_text, $sender_id, $mark = null)
{
  if ($message_text != null)
    $message_text = str_replace("'", "''", $message_text);
  if ($mark != null)
    $mark = str_replace("'", "''", $mark);
  if ($mark != null) {
    return "UPDATE ax.ax_assignment set mark='$mark' WHERE id in (SELECT assignment_id FROM ax.ax_message WHERE id=$message_id);\n" .
      "UPDATE ax.ax_message set status=1 WHERE id=$message_id AND status=0;\n" .
      "INSERT INTO ax.ax_message (assignment_id, type, visibility, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
      "(SELECT assignment_id, 2, 0, 3, $sender_id, now(), $message_id, '$message_text\nОценка: $mark', null, 0 FROM ax.ax_message WHERE id=$message_id);";
  } else {
    return "UPDATE ax.ax_message set status=1 WHERE id=$message_id AND status=0;\n" .
      "INSERT INTO ax.ax_message (assignment_id, type, visibility, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)\n" .
      "(SELECT assignment_id, 0, 0, 3, $sender_id, now(), $message_id, '$message_text', null, 0 FROM ax.ax_message WHERE id=$message_id);";
  }
}




// ДЕЙСТВИЯ С ДИСЦИПЛИНАМИ

function select_all_disciplines()
{
  return 'SELECT * FROM discipline';
}

// - gjkextybt
function select_discipline_name($disc_id)
{
  return "SELECT name FROM discipline WHERE id =" . $disc_id;
}

function select_discipline_name_by_page($page_id)
{
  return "SELECT discipline.name from discipline 
            INNER JOIN ax.ax_page ON ax.ax_page.disc_id = discipline.id
            WHERE ax.ax_page.id = '$page_id';
    ";
}






// ДЕЙСТВИЯ С ГРУППАМИ

function select_ax_page_group($page_id, $group_id)
{
  return "SELECT * FROM ax.ax_page_group 
            WHERE page_id = $page_id AND group_id = $group_id;
    ";
}

function select_group_id_by_student_id($student_id)
{
  return "SELECT group_id FROM students_to_groups
          WHERE students_to_groups.student_id = $student_id
  ";
}

// группы у конкретной дисциплины
function select_discipline_groups($page_id)
{
  return 'SELECT groups.id, name FROM groups INNER JOIN ax.ax_page_group ON groups.id = ax.ax_page_group.group_id WHERE page_id =' . $page_id;
}

// удаление из таблицы дисциплины-группы
function delete_page_group($page_id)
{
  return 'DELETE FROM ax.ax_page_group WHERE page_id =' . $page_id;
}

function update_ax_page_group($page_id, $groups)
{
  $groups = pg_escape_string($groups);

  return "INSERT INTO ax.ax_page_group(page_id, group_id)
        VALUES ($page_id, (SELECT id FROM groups WHERE name = '$groups'))";
}

function addGroupToPage($page_id, $group_id)
{
  return "INSERT INTO ax.ax_page_group(page_id, group_id)
          VALUES ($page_id, $group_id)";
}

function update_ax_page_group_by_group_id($page_id, $group_id)
{
  return "INSERT INTO ax.ax_page_group(page_id, group_id)
          VALUES ($page_id, $group_id);
  ";
}

function select_page_groups($page_id)
{
  return "SELECT groups.id id, groups.name grp
            FROM ax.ax_page_group 
          INNER JOIN groups ON groups.id = ax.ax_page_group.group_id
            WHERE ax.ax_page_group.page_id = '$page_id'
            ORDER BY grp";
}

function select_page_students($page_id)
{
  return "SELECT students.middle_name || ' ' || students.first_name fio, students.id id, ax.students_to_subgroups.subgroup
            FROM ax.ax_page_group 
            INNER JOIN students_to_groups ON ax.ax_page_group.group_id = students_to_groups.group_id
            INNER JOIN students ON students_to_groups.student_id = students.id
            LEFT JOIN ax.students_to_subgroups ON ax.students_to_subgroups.student_id = students.id
            WHERE ax.ax_page_group.page_id = '$page_id'
            ORDER BY fio";
}

function select_page_students_grouped($page_id)
{
  return "SELECT students.middle_name || ' ' || students.first_name fio, students.id id, ax.ax_student_page_info.variant_num vnum, ax.ax_student_page_info.variant_comment vtext, groups.name grp, groups.id gid
            FROM ax.ax_page_group
          INNER JOIN students_to_groups ON ax.ax_page_group.group_id = students_to_groups.group_id
          INNER JOIN students ON students_to_groups.student_id = students.id
          INNER JOIN groups ON groups.id = students_to_groups.group_id
          LEFT JOIN ax.ax_student_page_info ON ax.ax_student_page_info.student_user_id = students.id AND ax.ax_student_page_info.page_id=ax_page_group.page_id
            WHERE ax.ax_page_group.page_id = '$page_id'
            ORDER BY grp, fio;";
}



// ДЕЙСТВИЯ С ПРЕПОДАВАТЕЛЯМИ

// все преподователи
function select_all_teachers()
{
  return 'SELECT id, first_name, middle_name, last_name, role FROM students
            WHERE role = 2';
}

// преподователи у конкретной дисциплины
function select_page_prep_name($page_id)
{
  return 'SELECT students.id, first_name, middle_name FROM ax.ax_page_prep 
            INNER JOIN students ON students.id = ax.ax_page_prep.prep_user_id 
            WHERE page_id =' . $page_id;
}

function isPrepByAssignmentId($assignment_id, $user_id)
{
  return "SELECT DISTINCT prep_user_id FROM ax.ax_page_prep
          INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_page_prep.page_id
          INNER JOIN ax.ax_task ON ax.ax_task.page_id = ax.ax_page.id
          INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
          WHERE prep_user_id = $user_id AND ax.ax_assignment.id = $assignment_id";
}

// удаление из таблицы дисциплины-преподователи
function delete_page_prep($page_id)
{
  return 'DELETE FROM ax.ax_page_prep WHERE page_id =' . $page_id;
}

function prep_ax_prep_page($id, $first_name, $middle_name)
{
  $first_name = pg_escape_string($first_name);
  $middle_name = pg_escape_string($middle_name);

  return "INSERT INTO ax.ax_page_prep(id, prep_user_id, page_id)
        VALUES(default, (SELECT id FROM students WHERE first_name = '$first_name' AND middle_name = '$middle_name'),'$id')";
}

function addTeacherToPage($page_id, $teacher_id)
{
  return "INSERT INTO ax.ax_page_prep(prep_user_id, page_id)
          VALUES ($teacher_id, $page_id)";
}


// ДЕЙСТВИЯ СО СТУДЕНТАМИ

function select_students_by_group_by_page($page_id)
{
  return "SELECT s.id, s.middle_name || ' ' || s.first_name as fi, s.login, g.id as group_id, g.name as group_name 
          FROM groups g
          INNER JOIN ax.ax_page_group ON g.id = ax.ax_page_group.group_id 
          INNER JOIN students_to_groups ON g.id = students_to_groups.group_id
          INNER JOIN students s ON s.id = students_to_groups.student_id
          WHERE page_id ='$page_id' ORDER BY g.id, fi;
  ";
}

function select_students_by_group_by_page_by_task($page_id, $task_id)
{
  return "SELECT a.id, a.fi, a.login, a.group_id, a.group_name, max(a.task_id) task_id
          FROM( 
            SELECT DISTINCT s.id, s.middle_name || ' ' || s.first_name as fi, s.login, g.id as group_id, g.name as group_name, 
            CASE WHEN ax.ax_task.id = '$task_id' THEN ax.ax_task.id ELSE null END task_id
            FROM groups g
                
            INNER JOIN ax.ax_page_group ON g.id = ax.ax_page_group.group_id 
            INNER JOIN students_to_groups ON g.id = students_to_groups.group_id
            INNER JOIN students s ON s.id = students_to_groups.student_id
            LEFT JOIN ax.ax_assignment_student ON ax.ax_assignment_student.student_user_id = s.id
            LEFT JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id
            LEFT JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id

            ORDER BY g.id, fi, task_id, s.id) a
          GROUP BY a.id, a.fi, a.login, a.group_id, a.group_name ORDER BY a.group_id, a.fi;
  ";
}

function select_students_id_by_group($group_id)
{
  return "SELECT student_id FROM students_to_groups
          WHERE group_id = $group_id;
  ";
}

function select_group_students_count($group_id)
{
  return "SELECT COUNT(*) FROM students_to_groups WHERE group_id = $group_id";
}

function select_student_role($user_id)
{
  return "SELECT role FROM students 
          WHERE id = $user_id
  ";
}



// ДЕЙСТВИЯ С ФАЙЛАМИ

// получения файлов для задание
function select_task_file($type, $task_id)
{
  return 'SELECT * FROM ax.ax_task_file WHERE type = ' . $type . ' AND task_id = ' . $task_id;
}

// обновление текста файла
function update_file($type, $task_id, $full_text)
{
  return 'UPDATE ax.ax_task_file SET full_text = $accelquotes$' . $full_text . '$accelquotes$ WHERE task_id = ' . $task_id . ' AND type = ' . $type;
}

// добавление файла
function insert_file($type, $task_id, $file_name, $full_text)
{
  return 'INSERT INTO ax.ax_task_file(type, task_id, file_name, full_text) VALUES (' . $type .
    ', ' . $task_id . ', $accelquotes$' . $file_name . '$accelquotes$, $accelquotes$' . $full_text . '$accelquotes$)';
}



// ПРОЧЕЕ

// Добавление нового коммита
function insert_answer_commit($assignment_id, $student_id)
{
  return "INSERT INTO ax.ax_solution_commit(assignment_id, session_id, student_user_id, type) 
          VALUES ($assignment_id, null, $student_id, 1) 
          RETURNING id";
}


function select_color_theme($page_id)
{
  return "SELECT * FROM ax.ax_color_theme WHERE status = 1 OR page_id = $page_id ORDER BY id;";
}

function pg_fetch_all_assoc($res)
{
  if (PHP_VERSION_ID >= 70100)
    return pg_fetch_all($res, PGSQL_ASSOC);
  $array_out = array();
  while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
    $array_out[] = $row;
  }
  return $array_out;
}


function select_last_commit_id_by_assignment_id($assignment_id)
{
  return "SELECT MAX(id) as id FROM ax.ax_solution_commit 
          WHERE assignment_id = $assignment_id;
  ";
}


// TODO: ПРОВЕРИТЬ!
// получение сообщений для таблицы посылок
function select_preptable_messages($page_id)
{
  return "SELECT DISTINCT s1.middle_name || ' ' || s1.first_name fio, groups.name grp, 
      ax.ax_task.id tid, ax.ax_assignment.id aid, ax.ax_assignment.status_code, ax.ax_assignment.status, m1.id mid, s1.id sid, 
      m1.type, ax.ax_task.title task, ax.ax_task.max_mark max_mark, ax.ax_assignment.mark amark, 
      ax.ax_assignment.delay adelay, ax.ax_assignment.status_code astatus, 
      to_char(m1.date_time, 'DD-MM-YYYY HH24:MI:SS') mtime, 
      m1.full_text mtext, m1.sender_user_id msid, m1.sender_user_type mtype, 
      m1.status mstatus, m2.id mreply_id, m2.full_text mreply_text,
      s2.middle_name || ' ' || s2.first_name mfio, s2.login mlogin
    
      FROM (
        SELECT a.aid, a.type, max(a.mid) as mid
        FROM( 
          SELECT ax.ax_task.id tid, ax.ax_assignment.id aid, m1.id mid, m1.type FROM ax.ax_task 
          INNER JOIN ax.ax_assignment ON ax.ax_task.id = ax.ax_assignment.task_id AND ax.ax_assignment.status in (1)
          INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id
          INNER JOIN students s1 ON s1.id = ax.ax_assignment_student.student_user_id 
          LEFT JOIN ax.ax_message m1 ON ax.ax_assignment.id = m1.assignment_id 
            AND (m1.sender_user_id=ax_assignment_student.student_user_id OR m1.sender_user_type=2) AND m1.status in (0,1)
                  
          WHERE ax.ax_task.page_id = $page_id AND m1.type in (1, 2) AND ax.ax_task.status = 1 
          ORDER BY mid DESC
        ) a
        GROUP BY a.aid, a.type
      ) a
    
    INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = a.aid
    INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
    INNER JOIN ax.ax_message m1 ON m1.id = a.mid
    INNER JOIN students s1 ON s1.id = m1.sender_user_id
    
    INNER JOIN students_to_groups ON students_to_groups.student_id = s1.id
    INNER JOIN groups ON groups.id = students_to_groups.group_id
    
    LEFT JOIN ax.ax_message_file ON m1.id = ax.ax_message_file.message_id
    LEFT JOIN ax.ax_file ON ax.ax_file.id = ax.ax_message_file.file_id
    
    LEFT JOIN ax.ax_message m2 ON m2.id = m1.reply_to_id
    LEFT JOIN students s2 ON s2.id = m2.sender_user_id
    
    ORDER BY mid         
    ";
}

// TODO: ПРОВЕРИТЬ!
function select_message_with_all_relations($message_id)
{
  return "SELECT DISTINCT ON (ax_assignment.id) s1.middle_name || ' ' || s1.first_name fio, groups.name grp, 
          ax.ax_task.id tid, ax.ax_assignment.id aid, ax.ax_assignment.status_code, ax.ax_assignment.status, m1.id mid, ax.ax_assignment_student.student_user_id sid, 
          m1.type, ax.ax_task.title task, ax.ax_task.max_mark max_mark, 
          ax.ax_assignment.mark amark, ax.ax_assignment.delay adelay, ax.ax_assignment.status_code astatus, 
          to_char(m1.date_time, 'DD-MM-YYYY HH24:MI:SS') mtime, 
          m1.full_text mtext, m1.sender_user_id msid, m1.sender_user_type mtype, m1.status mstatus, m2.id mreply_id, m2.full_text mreply_text,
          s2.middle_name || ' ' || s2.first_name mfio, s2.login mlogin FROM ax.ax_task 

          INNER JOIN ax.ax_assignment ON ax.ax_task.id = ax.ax_assignment.task_id 
          INNER JOIN ax.ax_assignment_student ON ax.ax_assignment.id = ax.ax_assignment_student.assignment_id
          INNER JOIN students s1 ON s1.id = ax.ax_assignment_student.student_user_id 
          LEFT JOIN ax.ax_message m1 ON ax.ax_assignment.id = m1.assignment_id
          LEFT JOIN ax.ax_message m2 ON m1.reply_to_id = m2.id
          LEFT JOIN students s2 ON s2.id = m1.sender_user_id

          LEFT JOIN ax.ax_message_file ON m1.id = ax.ax_message_file.message_id
          LEFT JOIN ax.ax_file ON ax.ax_file.id = ax.ax_message_file.file_id

          INNER JOIN students_to_groups ON s1.id = students_to_groups.student_id
          INNER JOIN groups ON groups.id = students_to_groups.group_id
          WHERE m1.id = $message_id;
  ";
}
