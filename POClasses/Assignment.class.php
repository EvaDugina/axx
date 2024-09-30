<?php
require_once("./settings.php");
require_once("Message.class.php");
require_once("Commit.class.php");
require_once("User.class.php");
require_once("Page.class.php");

class Assignment
{

  public $id = null;
  public $variant_number = null;
  public $start_limit = null, $finish_limit = null;

  public $visibility = null, $visibility_text = null; // то же самое, что и "status_code" и "status_text" в БД
  // 0 - недоступно для просмотра, 2 - доступно для просмотра, 4 - отменено

  public $delay = null; // не понятно, зачем нужно
  public $mark = null;
  public $status = null; // -1 - недоступно для выполнения, 0 - ожидает выполнения, 1 - ожидает проверки, 2 / 4 - проверено
  public $checks = null;
  // public $new = false;

  // TODO: добавить колонку в БД
  // status_complete integer, где: 
  // 0 - не выполнено, 1 - ожидает проверки, 2 - выполнено

  private $Students = array();
  private $Messages = array();
  private $Commits = array();

  function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 0) {
      $this->id = null;

      $this->variant_number = null;

      $this->start_limit = null;
      $this->finish_limit = null;

      $this->visibility = 2;
      $this->visibility_text = visibility_to_text($this->visibility);
      $this->status = 0;

      $this->delay = null;
      $this->mark = null;
      $this->checks = null;
    } else if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetAssignmentInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $assignment = pg_fetch_assoc($result);

      if (isset($assignment['variant_number']))
        $this->variant_number = $assignment['variant_number'];
      else if (isset($assignment['variant_comment']))
        $this->variant_number = $assignment['variant_comment'];

      $this->start_limit = convertServerDateTimeToCurrent($assignment['start_limit'], "d-m-Y H:i:s");
      $this->finish_limit = convertServerDateTimeToCurrent($assignment['finish_limit'], "d-m-Y H:i:s");

      $this->visibility = $assignment['status_code'];
      $this->visibility_text = $assignment['status_text'];
      if (isset($assignment['status']))
        $this->status = $assignment['status'];

      $this->delay = $assignment['delay'];
      $this->mark = $assignment['mark'];
      $this->checks = $assignment['checks'];

      $this->Students = getStudentsByAssignment($this->id);
      $this->Messages = getMessagesByAssignment($this->id);
      $this->Commits = getCommitsByAssignment($this->id);
    } else if ($count_args == 2) {
      $task_id = (int)$args[0];
      $this->visibility = $args[1];
      $this->visibility_text = visibility_to_text($this->visibility);
      $this->status = 0;

      $this->pushNewEmptyToDB($task_id);
    } else if ($count_args == 3) {
      $task_id = (int)$args[0];
      $this->visibility = $args[1];
      $this->visibility_text = visibility_to_text($this->visibility);
      $this->status = $args[2];

      $this->pushNewEmptyToDB($task_id);
    }

    // else if ($count_args == 9) { // всё + task_id
    //   $task_id = $args[0];

    //   $this->variant_number = $args[1];
    //   $this->start_limit = $args[2];
    //   $this->finish_limit = $args[3];

    //   $this->visibility = $args[4];
    //   // $this->status_text = $args[5];

    //   $this->delay = $args[6];
    //   $this->mark = $args[7];
    //   $this->checks = $args[8];

    //   $this->pushNewToDB($task_id);
    // }

    else {
      die('Неверные аргументы в конструкторе Assignment');
    }
  }

  // function __destruct() {
  //   if ($this->new) {
  //     $this->deleteFromDB();
  //   }

  // }

  public function getStudents()
  {
    return $this->Students;
  }
  public function getMessages()
  {
    return $this->Messages;
  }
  public function getCommits()
  {
    return $this->Commits;
  }



  // SETTERS

  public function setStatus($status)
  {
    global $dbconnect;

    $this->status = $status;

    $query = "UPDATE ax.ax_assignment SET status = $this->status
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setDelay($delay)
  {
    global $dbconnect;

    $this->delay = $delay;

    $query = "UPDATE ax.ax_assignment SET delay = $this->delay WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setVisibility($visibility)
  {
    global $dbconnect;

    $this->visibility = $visibility;
    $this->visibility_text = visibility_to_text($this->visibility);

    $query = "UPDATE ax.ax_assignment SET status_code = $this->visibility, status_text = '$this->visibility_text'
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setFinishLimit($finish_limit)
  {
    global $dbconnect;

    $this->finish_limit = $finish_limit;

    $query = "UPDATE ax.ax_assignment SET finish_limit = to_timestamp('$this->finish_limit', 'YYYY-MM-DD HH24:MI:SS') WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setStartLimit($start_limit)
  {
    global $dbconnect;

    $this->start_limit = $start_limit;

    $query = "UPDATE ax.ax_assignment SET start_limit = to_timestamp('$this->start_limit', 'YYYY-MM-DD HH24:MI:SS') WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function getStartTime()
  {
    return convert_timestamp_to_date($this->start_limit, "H:i:s");
  }

  public function getEndTime()
  {
    return convert_timestamp_to_date($this->finish_limit, "H:i:s");
  }

  public function getEndDateTime()
  {
    return convert_timestamp_to_date($this->finish_limit, "m-d-Y H:i:s");
  }

  public function setVariantNumber($variant_number)
  {
    global $dbconnect;

    $this->variant_number = $variant_number;

    $query = "UPDATE ax.ax_assignment SET variant_number = $variant_number WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function setMark($mark)
  {
    global $dbconnect;

    if ($mark == "")
      $mark = null;

    $this->mark = $mark;

    if ($mark != null) {
      $status = 4;
      $query = "UPDATE ax.ax_assignment SET mark = '$mark', status = 4
              WHERE id = $this->id;";
    } else {
      $status = 0;
      $query = "UPDATE ax.ax_assignment SET mark = null, status = 0
              WHERE id = $this->id;";
    }
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

    $this->setStatus($status);
  }

  // -- END SETTERS


  // WORK WITH ASSIGNMENT

  public function pushNewToDB($task_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_assignment(task_id, variant_number, start_limit, finish_limit, 
              status_code, status_text, status, delay, mark, checks)
              VALUES ($task_id, $this->variant_number, '$this->start_limit', '$this->finish_limit', 
              $this->visibility, '$this->visibility_text', $this->status, $this->delay, '$this->mark', '$this->checks') 
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushNewEmptyToDB($task_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_assignment(task_id, status_code, status_text, status)
              VALUES ($task_id, $this->visibility, '$this->visibility_text', $this->status) 
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_assignment SET variant_number=$this->variant_number, start_limit='$this->start_limit', finish_limit='$this->finish_limit', 
              status_code=$this->visibility, status_text='$this->visibility_text', delay=$this->delay, mark='$this->mark', checks='$this->checks', 
              status=$this->status
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
    global $dbconnect;

    $this->deleteStudentsFromAssignmentDB();

    foreach ($this->Messages as $Message) {
      $Message->deleteFromDB();
    }
    foreach ($this->Commits as $Commit) {
      $Commit->deleteFromDB();
    }

    $query = "DELETE FROM ax.ax_assignment WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }



  public function isVisible()
  {
    if ($this->visibility == 2)
      return true;
    return false;
  }
  public function isVisibleForStudent()
  {
    $date_now = get_now_date("d-m-Y H:i:s");
    if ($this->isVisible() && !($this->start_limit != null && $date_now < $this->start_limit))
      return true;
    return false;
  }
  public function isCompleteable()
  {
    if ($this->status != -1 && $this->visibility != 4)
      return true;
    return false;
  }
  public function isCompleted()
  {
    if ($this->status == 2 || $this->status == 4)
      return true;
    return false;
  }
  public function isWaitingCheck()
  {
    if ($this->status == 1)
      return true;
    return false;
  }
  public function isMarked()
  {
    if ($this->mark != "" && $this->mark != null)
      return true;
    return false;
  }


  // TODO: на будущее - исправить
  public function getNextAssignmentVisibility()
  {
    if ($this->visibility == 0)
      return 2;
    else if ($this->visibility == 2)
      return 4;
    else if ($this->visibility == 4)
      return 0;
    else $this->visibility;
  }
  public function getNextAssignmentStatus()
  {
    if ($this->status == 0) {
      return -1;
    } else if ($this->status == -1) {
      return 0;
    }
    return $this->status;
  }

  // -- END WORK WITH ASSIGNMENT



  // WORK WITH STUDENTS 

  public function addStudent($student_id)
  {
    $Student = new User((int)$student_id);
    $this->pushStudentToAssignmentDB($student_id);
    array_push($this->Students, $Student);
  }
  public function addStudents($Students)
  {
    $this->pushStudentsToAssignmentDB($Students);
    foreach ($Students as $Student) {
      array_push($this->Students, $Student);
    }
  }
  public function deleteStudent($student_id)
  {
    $index = $this->findStudentById($student_id);
    if ($index != -1) {
      $this->deleteStudentFromAssignmentDB($student_id);
      $this->Students[$index]->deleteFromDB();
      unset($this->Students[$index]);
      $this->Students = array_values($this->Students);
    }
  }
  private function findStudentById($student_id)
  {
    $index = 0;
    foreach ($this->Students as $Student) {
      if ($Student->id == $student_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getStudentById($student_id)
  {
    foreach ($this->Students as $Student) {
      if ($Student->id == $student_id)
        return $Student;
    }
    return null;
  }
  public function checkStudent($student_id)
  {
    foreach ($this->Students as $Student) {
      if ((int)$Student->id == (int)$student_id)
        return true;
    }
    return false;
  }

  private function pushStudentToAssignmentDB($student_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $student_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushStudentsToAssignmentDB($Students)
  {
    global $dbconnect;

    if (!empty($Students)) {
      $query = "";
      foreach ($Students as $Student) {
        $query .= "INSERT INTO ax.ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $Student->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteStudentFromAssignmentDB($student_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_assignment_student WHERE student_user_id = $student_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchStudentsToAssignmentDB()
  {
    global $dbconnect;

    $this->deleteStudentsFromAssignmentDB();

    if (!empty($this->Students)) {
      $query = "";
      foreach ($this->Students as $Student) {
        $query .= "INSERT INTO ax.ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $Student->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteStudentsFromAssignmentDB()
  {
    global $dbconnect;

    // Удаляем предыдущие прикрепления студентов
    $query = "DELETE FROM ax.ax_assignment_student WHERE assignment_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH STUDENTS 



  // WORK WITH MESSAGES

  public function addMessage($message_id)
  {
    $Message = new Message((int)$message_id);
    $this->pushNewToDeliveryDB($Message);
    array_push($this->Messages, $Message);
  }
  public function deleteMessage($message_id)
  {
    $index = $this->findMessageById($message_id);
    if ($index != -1) {
      $this->Messages[$index]->deleteFromDB();
      $this->deleteMessageFromDelivery($message_id);
      unset($this->Messages[$index]);
      $this->Messages = array_values($this->Messages);
    }
  }
  private function findMessageById($message_id)
  {
    $index = 0;
    foreach ($this->Messages as $Message) {
      if ($Message->id == $message_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getMessageById($message_id)
  {
    foreach ($this->Messages as $Message) {
      if ($Message->id == $message_id)
        return $Message;
    }
    return null;
  }
  public function getFirstUnreadedMessage($user_id)
  {
    global $dbconnect;

    $query = "SELECT min(message_id) as min_message_id FROM ax.ax_message_delivery 
              INNER JOIN ax.ax_message ON ax.ax_message.id = ax.ax_message_delivery.message_id
              WHERE ax.ax_message.assignment_id = $this->id AND ax_message_delivery.recipient_user_id = $user_id
              AND ax.ax_message_delivery.status = 0 LIMIT 1;";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    return pg_fetch_assoc($pg_query)['min_message_id'];
  }
  public function getLastAnswerMessage()
  {
    for ($i = count($this->Messages) - 1; $i >= 0; $i--) {
      if ($this->Messages[$i]->type == 1)
        return $this->Messages[$i];
    }
  }


  public function getUnreadedMessage($user_id)
  {
    $unreadedMessages = array();
    foreach ($this->Messages as $Message) {
      if (!$Message->isReadedByUser($user_id))
        array_push($unreadedMessages, $Message);
    }
    return $unreadedMessages;
  }


  function pushNewToDeliveryDB($Message)
  {
    global $dbconnect;

    $query = "";
    foreach ($this->Students as $Student) {
      if ($Student->id != $Message->sender_user_id) {
        $query .= "INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
        VALUES ($Message->id, $Student->id, 0);";
      }
    }

    $Teachers = getTeachersByAssignment($this->id);
    foreach ($Teachers as $Teacher) {
      if ($Teacher->id != $Message->sender_user_id) {
        $query .= "INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
        VALUES ($Message->id, $Teacher->id, 0);";
      }
    }

    $query .= "INSERT INTO ax.ax_message_delivery (message_id, recipient_user_id, status)
    VALUES ($Message->id, $Message->sender_user_id, 1);";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  function deleteMessageFromDelivery($message_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_message_delivery WHERE message_id = $message_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  function getNewMessagesByUser($user_id)
  {
    $new_messages = array();
    $User = new User($user_id);
    foreach ($this->Messages as $Message) {
      if (!$Message->isReadedByUser($user_id))
        array_push($new_messages, $Message);
    }
    return $new_messages;
  }

  // TODO: Исправить на работу с таблицей ax.ax_message_delivery
  public function getCountUnreadedMessages($user_id)
  {
    $count_unreaded = 0;
    foreach ($this->Messages as $Message) {
      if (!$Message->isReadedByUser($user_id))
        $count_unreaded++;
    }
    return $count_unreaded;
  }

  // -- END WORK WITH MESSAGES 



  // WORK WITH COMMITS

  public function addCommit($commit_id)
  {
    $Commit = new Commit((int)$commit_id);
    array_push($this->Commits, $Commit);
  }
  public function deleteCommit($commit_id)
  {
    $index = $this->findCommitById($commit_id);
    if ($index != -1) {
      $this->Commits[$index]->deleteFromDB();
      unset($this->Commits[$index]);
      $this->Commits = array_values($this->Commits);
    }
  }
  private function findCommitById($commit_id)
  {
    $index = 0;
    foreach ($this->Commits as $Commit) {
      if ($Commit->id == $commit_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getCommitById($commit_id)
  {
    foreach ($this->Commits as $Commit) {
      if ($Commit->id == $commit_id)
        return $Commit;
    }
    return null;
  }
  public function getLastCommit()
  {
    return end($this->Commits);
  }
  public function getLastCommitForStudent()
  {
    $count = count($this->getCommitsForStudent());
    if ($count > 0)
      return $this->getCommitsForStudent()[$count - 1];
    else
      return null;
  }
  public function getLastCommitForTeacher()
  {
    $count = count($this->getCommitsForTeacher());
    if ($count > 0)
      return $this->getCommitsForTeacher()[$count - 1];
    else
      return null;
  }

  public function getCommitsForStudent()
  {
    $studentCommits = array();
    foreach ($this->Commits as $Commit) {
      if (!$Commit->isEditByTeacher()) {
        array_push($studentCommits, $Commit);
      }
    }
    return $studentCommits;
  }
  public function getCommitsForTeacher()
  {
    $teacherCommits = array();
    foreach ($this->Commits as $Commit) {
      if (!$Commit->isEditByStudent()) {
        array_push($teacherCommits, $Commit);
      }
    }
    return $teacherCommits;
  }

  // -- END WORK WITH COMMITS 


}

function getTeachersByAssignment($assignment_id)
{
  global $dbconnect;

  $query = queryGetPageByAssignment($assignment_id);
  $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $page_id = pg_fetch_assoc($pg_query)['page_id'];

  return getTeachersByPage($page_id);
}





function getStudentsByAssignment($assignment_id)
{
  global $dbconnect;

  $students = array();

  $query = queryGetStudentsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($student_row = pg_fetch_assoc($result)) {
    array_push($students, new User((int)$student_row['id']));
  }

  return $students;
}

function getMessagesByAssignment($assignment_id)
{
  global $dbconnect;

  $messages = array();

  $query = queryGetMessagesByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($message_row = pg_fetch_assoc($result)) {
    array_push($messages, new Message((int)$message_row['id']));
  }

  return $messages;
}

function getCommitsByAssignment($assignment_id)
{
  global $dbconnect;

  $commits = array();

  $query = queryGetCommitsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($commit_row = pg_fetch_assoc($result)) {
    array_push($commits, new Commit((int)$commit_row['id']));
  }

  return $commits;
}

function getAssignmentByCommit($commit_id)
{
  global $dbconnect;

  $query = "SELECT assignment_id FROM ax.ax_solution_commit WHERE id = $commit_id";
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $assignment_id = pg_fetch_assoc($result)['assignment_id'];

  return $assignment_id;
}



function queryGetAssignmentInfo($assignment_id)
{
  return "SELECT *, to_char(ax_assignment.start_limit, 'YYYY-MM-DD') as converted_start_limit,
          to_char(ax_assignment.finish_limit, 'YYYY-MM-DD') as converted_finish_limit
          FROM ax.ax_assignment WHERE id = $assignment_id";
}

function queryGetStudentsByAssignment($assignment_id)
{
  return "SELECT student_user_id as id FROM ax.ax_assignment_student
          WHERE ax.ax_assignment_student.assignment_id = $assignment_id;";
}

function queryGetMessagesByAssignment($assignment_id)
{
  return "SELECT id FROM ax.ax_message
          WHERE assignment_id = $assignment_id AND (status = 0 OR status = 1)
          ORDER BY id;";
}

function queryGetCommitsByAssignment($assignment_id)
{
  return "SELECT id FROM ax.ax_solution_commit
          WHERE assignment_id = $assignment_id
          ORDER BY id;";
}








function visibility_to_text($visibility)
{
  switch ($visibility) {
    case 0:
      return "Недоступно для просмотра";
    case 2:
      return "Доступно для просмотра";
    case 4:
      return "Отменено";
    default:
      return "";
  }
}

function status_to_text($status)
{
  switch ($status) {
    case -1:
      return "Недоступно для выполнения";
    case 0:
      return "Доступно для выполнения";
    case 1:
      return "Ожидает проверки";
    case 2:
      return "Проверено, не оценено";
    case 3:
      return "Ожидает повторного выполнения";
    case 4:
      return "Выполнено";
    default:
      return "";
  }
}
