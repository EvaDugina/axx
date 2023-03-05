<?php 
require_once("./settings.php");

class User {

  public $id; 
  public $first_name, $middle_name, $last_name;
  public $login, $role;
  public $email, $notify_status;

  public $Group = null;

  
  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];

      $query = queryGetUserInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $user = pg_fetch_assoc($result);

      $this->first_name = $user['first_name'];
      $this->middle_name = $user['middle_name'];
      $this->last_name = $user['last_name'];

      $this->login = $user['login'];
      $this->role = $user['role'];

      $this->email = $user['email'];
      $this->notify_status = $user['notification_type'];

      $this->Group = new Group($user['group_id']);
    }

    // Раскомментировать, когда понадобится создавать студента в БД
    // else if ($count_args == 7) { 
    //   $this->first_name = $args[0];
    //   $this->middle_name = $args[1];
    //   $this->last_name = $args[2];

    //   $this->login = $args[3];
    //   $this->role = $args[4];

    //   $this->email = $args[5];
    //   $this->notify_status = $args[6];

    //   $this->pushNewToDB();
    // }

    else {
      die('Неверное число аргументов, или неверный id');
    }

  }

  public function pushStudentChangesToDB() {
    global $dbconnect;

    $query = "UPDATE students SET first_name = '$this->first_name', middle_name = '$this->middle_name', last_name = '$this->last_name', 
              login = '$this->login', role = $this->role  
              WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  public function pushSettingChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_settings SET email = '$this->email', notification_type = '$this->notify_status'
              WHERE user_id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // public function pushGroupChangesToDB() {

  // }

  // public function deleteFromDB() {
  //   global $dbconnect;

  //   $query = "DELETE FROM students_to_groups WHERE student_id = $this->id;";
  //   $query .= "DELETE FROM ax_assignment_student WHERE student_user_id = $this->id;";
  //   $query .= "DELETE FROM students WHERE id = $this->id;";
    
  //   pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  // }
 
}


function getGroupByStudent($student_id) {
  global $dbconnect;

  $query = queryGetGroupByStudent($student_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $group_id = pg_fetch_all($result)['group_id'];

  return new Group($group_id);
}




// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryGetUserInfo($id){
  return "SELECT first_name, middle_name, last_name, login, role, students_to_groups.group_id as group_id,
          ax_settings.email, ax_settings.notification_type, ax_settings.monaco_dark
          FROM students
          LEFT JOIN students_to_groups ON students_to_groups.student_id = students.id
          LEFT JOIN ax_settings ON ax_settings.user_id = students.id
          WHERE students.id = $id;
  ";
}

function queryGetGroupByStudent($student_id) {
  return "SELECT group_id FROM students_to_groups WHERE student_id = $student_id;";
}




function querySetGroupId($user_id, $group_id) {
  return "UPDATE students_to_groups SET group_id = $group_id 
          WHERE student_id = $user_id; 
          SELECT name FROM groups WHERE id = $group_id;";
}

function querySetNotifyStatus($id, $notify_type) {
  return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
          VALUES ($id, null, $notify_type, 'TRUE')
          ON CONFLICT (user_id) DO UPDATE 
          SET notification_type = $notify_type;
  ";
}

function querySetEmail($id, $email) {
  return "INSERT INTO ax_settings (user_id, email, notification_type, monaco_dark) 
      VALUES ('$id', '$email', null, 'TRUE')
      ON CONFLICT (user_id) DO UPDATE
      SET email = '$email';
  ";
}




// получение уведомлений, отсортированных по message_id для студента по невыполненным заданиям
function queryGetNotifiesForStudentHeader($student_id){
  return "SELECT DISTINCT ON (ax_assignment.id) ax_assignment.id as aid, ax_task.id as task_id, ax_page.id as page_id, ax_page.short_name, ax_task.title, ax_assignment.status_code, 
            teachers.first_name || ' ' || teachers.last_name as teacher_io, ax_message.id as message_id, ax_message.full_text FROM ax_task
          INNER JOIN ax_page ON ax_page.id = ax_task.page_id
          INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id
          INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
          INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
          INNER JOIN ax_message ON ax_message.assignment_id = ax_assignment.id
          INNER JOIN students teachers ON teachers.id = ax_message.sender_user_id
          WHERE ax_assignment_student.student_user_id = $student_id AND ax_page.status = 1 AND ax_message.sender_user_type != 3 
          AND ax_message.status = 0 AND (ax_message.visibility = 3 OR ax_message.visibility = 0);
  ";
}

// получение уведомлений для преподавателя по непроверенным заданиям
function queryGetNotifiesForTeacherHeader($teacher_id){
  return "SELECT DISTINCT ON (ax_assignment.id) ax_assignment.id as aid, ax_task.id as task_id, ax_task.page_id, ax_page.short_name, ax_task.title, 
              ax_assignment.id as assignment_id, ax_assignment.status_code, ax_assignment_student.student_user_id,
              s1.middle_name, s1.first_name FROM ax_task
          INNER JOIN ax_page ON ax_page.id = ax_task.page_id
          INNER JOIN ax_assignment ON ax_assignment.task_id = ax_task.id
          INNER JOIN ax_page_prep ON ax_page_prep.page_id = ax_page.id
          INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id 
          INNER JOIN students s1 ON s1.id = ax_assignment_student.student_user_id 
          LEFT JOIN ax_message ON ax_message.assignment_id = ax_assignment.id
          LEFT JOIN students s2 ON s2.id = ax_message.sender_user_id
          WHERE ax_page_prep.prep_user_id = $teacher_id AND ax_message.sender_user_type != 2 
          AND ax_message.status = 0 AND (ax_message.visibility = 2 OR ax_message.visibility = 0);
  ";
}

function queryGetCountUnreadedMessagesBytaskForTeacher($assignment_id){
  return "SELECT COUNT(*) FROM ax_message
      INNER JOIN ax_assignment ON ax_assignment.id = ax_message.assignment_id
      INNER JOIN ax_task ON ax_task.id = ax_assignment.task_id
      INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id
      WHERE ax_message.status = 0 AND ax_assignment_student.student_user_id = $assignment_id
      AND ax_message.sender_user_type != 2 AND ax_message.type != 3;
  ";
}

function queryGetCountUnreadedMessagesBytaskForStudent($assignment_id){
  return "SELECT COUNT(*) FROM ax_message
      INNER JOIN ax_assignment ON ax_assignment.id = ax_message.assignment_id
      INNER JOIN ax_task ON ax_task.id = ax_assignment.task_id
      INNER JOIN ax_assignment_student ON ax_assignment_student.assignment_id = ax_assignment.id
      WHERE ax_message.status = 0 AND ax_assignment.id = $assignment_id
      AND ax_message.sender_user_type != 3;
  ";
}

?>