<?php
require_once("./settings.php");

class User
{

  public $id;
  public $first_name, $middle_name, $last_name;
  public $login, $role;
  public $email, $notify_status;
  public $github_url;
  public $password = null;

  public $group_id;
  public $subgroup = null;

  private $Image = null;

  // private $Group = null;


  function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1) {
      $this->id = (int)$args[0];

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

      $this->github_url = $user['github_url'];

      $this->group_id = $user['group_id'];
      $this->subgroup = $user['subgroup'];

      if ($user['image_file_id'])
        $this->Image = new File((int)$user['image_file_id']);

      // $this->Group = new Group((int)$user['group_id']);
    } else {
      die('Неверные аргументы в конструкторе User');
    }
  }


  // GETTERS

  public function getFI()
  {
    if (empty($this->first_name))
      return $this->middle_name;
    else
      return $this->first_name . " " . $this->middle_name;
  }
  public function getFIO()
  {
    if (empty($this->first_name) && empty($this->middle_name))
      return $this->last_name;
    if (empty($this->first_name))
      return $this->middle_name . " " . $this->last_name;
    if (empty($this->middle_name))
      return $this->first_name . " " . $this->last_name;
    return $this->middle_name . " " . $this->first_name . " " . $this->last_name;
  }
  public function getFIOspecial()
  {
    return $this->middle_name . " " . mb_substr($this->first_name, 0, 1, "UTF-8") . "." . mb_substr($this->last_name, 0, 1, "UTF-8") . ".";
  }

  public function getNotifications()
  {
    global $dbconnect;

    if ($this->isAdmin()) {
      $query = queryGetAllPages();
      return null;
    } else if ($this->isTeacher()) // Уведомления для преподавателя
      $query = queryGetPagesByTeacher($this->id);
    else if ($this->isStudent()) // Уведомления для студента
      $query = queryGetAllPagesByGroup($this->group_id);

    $result = pg_query($dbconnect, $query);
    $notifies = array();

    while ($page_id = pg_fetch_assoc($result)) {
      $Page = new Page((int)$page_id['id']);

      foreach ($Page->getTasks() as $Task) {
        foreach ($Task->getActiveAssignments() as $Assignment) {
          $unreadedMessages = $Assignment->getUnreadedMessage($this->id);
          if (count($unreadedMessages) > 0) {
            $notify = array(
              "task_id" => $Task->id,
              "taskTitle" => $Task->title,
              "countUnreaded" => count($unreadedMessages),
              "assignment_id" => $Assignment->id,
              "students" => $Assignment->getStudents(),
              "teachers" => $Page->getTeachers(),
              "page_name" => $Page->name,
              "needToCheck" => ($Assignment->isWaitingCheck()) ? true : false,
              "completed" => ($Assignment->isCompleted()) ? true : false
            );
            array_push($notifies, $notify);
          }
        }
      }

      // if ($this->isTeacher()) { // Уведомления для преподавателя 
      //   foreach ($Page->getTasks() as $Task) {
      //     foreach ($Task->getActiveAssignments() as $Assignment) {
      //       $unreadedMessages = $Assignment->getUnreadedMessagesForTeacher();
      //       if (count($unreadedMessages) > 0) {
      //         $notify = array(
      //           "task_id" => $Task->id,
      //           "taskTitle" => $Task->title,
      //           "countUnreaded" => count($unreadedMessages),
      //           "assignment_id" => $Assignment->id,
      //           "students" => $Assignment->getStudents(),
      //           "page_name" => $Page->name,
      //           "needToCheck" => ($Assignment->isWaitingCheck()) ? true : false
      //         );
      //         array_push($notifies, $notify);
      //       }
      //     }
      //   }
      // } else if ($this->isStudent()) { // Уведомления для студента
      //   foreach ($Page->getTasks() as $Task) {
      //     foreach ($Task->getVisibleAssignmemntsByStudent($this->id) as $Assignment) {
      //       $unreadedMessages = $Assignment->getUnreadedMessagesForStudent();
      //       if (count($unreadedMessages) > 0) {
      //         $notify = array(
      //           "task_id" => $Task->id,
      //           "taskTitle" => $Task->title,
      //           "countUnreaded" => count($unreadedMessages),
      //           "assignment_id" => $Assignment->id,
      //           "teachers" => $Page->getTeachers(),
      //           "page_name" => $Page->name,
      //           "completed" => ($Assignment->isCompleted()) ? true : false
      //         );
      //         array_push($notifies, $notify);
      //       }
      //     }
      //   }
      // }


    }

    return $notifies;
  }

  public function getImageFile()
  {
    return $this->Image;
  }


  public function isAdmin()
  {
    return isAdmin($this->role);
  }
  public function isTeacher()
  {
    return isTeacher($this->role);
  }
  public function isStudent()
  {
    return isStudent($this->role);
  }


  // -- END GETTERS


  // SETTERS
  public function setGithub($github_url)
  {
    global $dbconnect;

    $this->github_url = $github_url;

    $query = "UPDATE ax.ax_settings SET github_url = '$this->github_url'
              WHERE user_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setSubgroup($subgroup)
  {
    global $dbconnect;

    $this->subgroup = $subgroup;

    $query = "INSERT INTO ax.students_to_subgroups (student_id, subgroup) VALUES ($this->id, $this->subgroup) 
              ON CONFLICT(student_id) DO UPDATE SET subgroup = $this->subgroup";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setImage($image_file_id)
  {
    global $dbconnect;

    $this->Image = new File((int)$image_file_id);

    $query = "UPDATE ax.ax_settings SET image_file_id = '$image_file_id'
              WHERE user_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function addFile($file_id)
  {
    $this->setImage($file_id);
  }

  // -- END SETTERS


  public function pushStudentChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE students SET first_name = '$this->first_name', middle_name = '$this->middle_name', last_name = '$this->last_name', 
              login = '$this->login', role = $this->role  
              WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushSettingChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_settings SET email = '$this->email', notification_type = '$this->notify_status', github_url = '$this->github_url'
              WHERE user_id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
}

function isAdmin($role)
{
  if ($role == 1)
    return true;
  return false;
}
function isTeacher($role)
{
  if ($role == 2)
    return true;
  return false;
}
function isStudent($role)
{
  if ($role == 3)
    return true;
  return false;
}


function getGroupByStudent($student_id)
{
  global $dbconnect;

  $query = queryGetGroupByStudent($student_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $group_id = pg_fetch_all($result)['group_id'];

  return new Group((int)$group_id);
}

function getUserByLoginAndRole($login, $role)
{
  global $dbconnect;

  $query = queryUserByLoginAndRole($login, $role);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $user_id = pg_fetch_all($result)['id'];

  return $user_id;
}

function hasSecondRole($login)
{
  global $dbconnect;

  $query = pg_query($dbconnect, queryCountRoles($login)) or die('Ошибка запроса: ' . pg_last_error());
  $count_roles = pg_fetch_assoc($query)['count'];

  if ($count_roles > 1)
    return true;

  return false;
}






function queryUserByLoginAndRole($login, $role)
{
  return "SELECT id, login, role FROM students
          WHERE login = $login AND role = $role;
  ";
}




// ФУНКЦИИ ЗАПРОСОВ К БД 

function queryCountRoles($login)
{
  return "SELECT COUNT(*) FROM students
          WHERE login = '$login'";
}

function queryGetUserInfo($id)
{
  return "SELECT first_name, middle_name, last_name, login, role, students_to_groups.group_id as group_id,
          ax.students_to_subgroups.subgroup, ax.ax_settings.*
          FROM students
          LEFT JOIN students_to_groups ON students_to_groups.student_id = students.id
          LEFT JOIN ax.students_to_subgroups ON ax.students_to_subgroups.student_id = students.id
          LEFT JOIN ax.ax_settings ON ax.ax_settings.user_id = students.id
          WHERE students.id = $id;
  ";
}

function queryGetGroupByStudent($student_id)
{
  return "SELECT group_id FROM students_to_groups WHERE student_id = $student_id;";
}




function querySetGroupId($user_id, $group_id)
{
  return "UPDATE students_to_groups SET group_id = $group_id 
          WHERE student_id = $user_id; 
          SELECT name FROM groups WHERE id = $group_id;";
}

function querySetNotifyStatus($id, $notify_type)
{
  return "INSERT INTO ax.ax_settings (user_id, email, notification_type, monaco_dark) 
          VALUES ($id, null, $notify_type, 'TRUE')
          ON CONFLICT (user_id) DO UPDATE 
          SET notification_type = $notify_type;
  ";
}

function querySetEmail($id, $email)
{
  return "INSERT INTO ax.ax_settings (user_id, email, notification_type, monaco_dark) 
      VALUES ('$id', '$email', null, 'TRUE')
      ON CONFLICT (user_id) DO UPDATE
      SET email = '$email';
  ";
}




// получение уведомлений, отсортированных по message_id для студента по невыполненным заданиям
function queryGetNotifiesForStudentHeader($student_id)
{
  return "SELECT DISTINCT ON (ax_assignment.id) ax.ax_assignment.id as aid, ax.ax_task.id as task_id, ax.ax_page.id as page_id, ax.ax_page.short_name, ax.ax_task.title, ax.ax_assignment.status_code, ax.ax_assignment.status, 
            teachers.first_name || ' ' || teachers.last_name as teacher_io, ax.ax_message.id as message_id, ax.ax_message.full_text FROM ax.ax_task
          INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
          INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
          INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
          INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
          INNER JOIN ax.ax_message ON ax.ax_message.assignment_id = ax.ax_assignment.id
          INNER JOIN students teachers ON teachers.id = ax.ax_message.sender_user_id
          WHERE ax.ax_assignment_student.student_user_id = $student_id AND ax.ax_page.status = 1 AND ax.ax_message.sender_user_type != 3 
          AND ax.ax_message.status = 0 AND (ax_message.visibility = 3 OR ax.ax_message.visibility = 0);
  ";
}

// получение уведомлений для преподавателя по непроверенным заданиям
function queryGetNotifiesForTeacherHeader($teacher_id)
{
  return "SELECT DISTINCT ON (ax_assignment.id) ax.ax_assignment.id as aid, ax.ax_task.id as task_id, ax.ax_task.page_id, ax.ax_page.short_name, ax.ax_task.title, 
                ax.ax_assignment.id as assignment_id, ax.ax_assignment.status_code, ax.ax_assignment.status, ax.ax_assignment_student.student_user_id,
                s1.middle_name, s1.first_name FROM ax.ax_task
            INNER JOIN ax.ax_page ON ax.ax_page.id = ax.ax_task.page_id
            INNER JOIN ax.ax_assignment ON ax.ax_assignment.task_id = ax.ax_task.id
            INNER JOIN ax.ax_page_prep ON ax.ax_page_prep.page_id = ax.ax_page.id
            INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id 
            INNER JOIN students s1 ON s1.id = ax.ax_assignment_student.student_user_id 
            LEFT JOIN ax.ax_message ON ax.ax_message.assignment_id = ax.ax_assignment.id
            LEFT JOIN students s2 ON s2.id = ax.ax_message.sender_user_id
            WHERE ax.ax_page_prep.prep_user_id = $teacher_id AND ax.ax_message.sender_user_type != 2 
            AND ax.ax_message.status = 0 AND (ax_message.visibility = 2 OR ax.ax_message.visibility = 0);
  ";
}

function queryGetCountUnreadedMessagesByTaskForTeacher($teacher_id, $task_id)
{
  return "SELECT COUNT(*) FROM ax.ax_message
          INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
          INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
          INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id
          WHERE ax.ax_message.status = 0 AND ax.ax_assignment_student.student_user_id = $teacher_id AND ax.ax_task.id = $task_id
          AND ax.ax_message.sender_user_type != 2 AND ax.ax_message.type != 3;
  ";
}

function queryGetCountUnreadedMessagesByTaskForStudent($student_id, $task_id)
{
  return "SELECT COUNT(*) FROM ax.ax_message
          INNER JOIN ax.ax_assignment ON ax.ax_assignment.id = ax.ax_message.assignment_id
          INNER JOIN ax.ax_task ON ax.ax_task.id = ax.ax_assignment.task_id
          INNER JOIN ax.ax_assignment_student ON ax.ax_assignment_student.assignment_id = ax.ax_assignment.id
          WHERE ax.ax_message.status = 0 AND ax.ax_assignment_student.student_user_id = $student_id AND ax.ax_task.id = $task_id
          AND ax.ax_message.sender_user_type != 3;
  ";
}
