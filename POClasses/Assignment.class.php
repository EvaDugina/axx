<?php 
require_once("./settings.php");
require_once("Message.class.php");
require_once("Commit.class.php");
require_once("User.class.php");
require_once("Page.class.php");

class Assignment {

  public $id = null;
  public $variant_number = null;
  public $start_limit = null, $finish_limit = null;
  public $status_code = null, $status_text = null;
  public $delay = null; // не понятно, зачем нужно
  public $mark = null;
  public $checks = null;

  private $Students = array();
  private $Messages = array();
  private $Commits = array();

  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();
    
    // Перегружаем конструктор по количеству подданых параметров
    
    if ($count_args == 1 && is_int($args[0])) { 
      $this->id = $args[0];
  
      $query = queryGetAssignmentInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $assignment = pg_fetch_assoc($result);

      $this->variant_number = $assignment['variant_number'];
      $this->start_limit = convert_timestamp_to_date($assignment['start_limit'], "d-m-Y H:i:s");
      $this->finish_limit = convert_timestamp_to_date($assignment['finish_limit'], "d-m-Y H:i:s");

      $this->status_code = $assignment['status_code'];
      $this->status_text = $assignment['status_text'];

      $this->delay = $assignment['delay'];
      $this->mark = $assignment['mark'];
      $this->checks = $assignment['checks'];

      $this->Students = getStudentsByAssignment($this->id);
      $this->Messages = getMessagesByAssignment($this->id);
      $this->Commits = getCommitsByAssignment($this->id);
    }

    else if ($count_args == 2) {
      $task_id = $args[0];
      $this->status_code = $args[1];
      $this->status_text = status_code_to_text($this->status_code);

      $this->pushNewEmptyToDB($task_id);
    }

    else if ($count_args == 9) { // всё + task_id
      $task_id = $args[0];

      $this->variant_number = $args[1];
      $this->start_limit = $args[2];
      $this->finish_limit = $args[3];

      $this->status_code = $args[4];
      $this->status_text = $args[5];

      $this->delay = $args[6];
      $this->mark = $args[7];
      $this->checks = $args[8];

      $this->pushNewToDB($task_id);
    }

    else {
      die('Неверные аргументы в конструкторе Assignment');
    }

  }

  public function getStudents() {
    return $this->Students;
  }
  public function getMessages() {
    return $this->Messages;
  }
  public function getCommits() {
    return $this->Commits;
  }



// SETTERS

  public function setStatus($status_code) {
    global $dbconnect;

    $this->status_code = $status_code;
    $this->status_text = status_code_to_text($this->status_code);

    $query = "UPDATE ax_assignment SET status_code = $this->status_code, status_text = '$this->status_text' 
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setDelay($delay) {
    global $dbconnect;

    $this->delay = $delay;

    $query = "UPDATE ax_assignment SET delay = $this->delay WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setFinishLimit($finish_limit) {
    global $dbconnect;

    $this->finish_limit = $finish_limit;

    $query = "UPDATE ax_assignment SET finish_limit = to_timestamp('$this->finish_limit 23:59:59', 'YYYY-MM-DD HH24:MI:SS') WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setStartLimit($start_limit) {
    global $dbconnect;

    $this->start_limit = $start_limit;

    $query = "UPDATE ax_assignment SET start_limit = to_timestamp('$this->start_limit 00:00:00', 'YYYY-MM-DD HH24:MI:SS') WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END SETTERS


// WORK WITH ASSIGNMENT

  public function pushNewToDB($task_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_assignment(task_id, variant_number, start_limit, finish_limit, 
              status_code, delay, status_text, mark, checks)
              VALUES ($task_id, $this->variant_number, '$this->start_limit', '$this->finish_limit', 
              $this->status_code, $this->delay, '$this->status_text', '$this->mark', '$this->checks') 
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushNewEmptyToDB($task_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_assignment(task_id, status_code, status_text)
              VALUES ($task_id, $this->status_code, '$this->status_text') 
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB(){
    global $dbconnect;

    $query = "UPDATE ax_assignment SET variant_number=$this->variant_number, start_limit='$this->start_limit', finish_limit='$this->finish_limit', 
              status_code=$this->status_code, delay=$this->delay, status_text='$this->status_text', mark='$this->mark', checks='$this->checks' 
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;
  
    $this->deleteStudentsFromAssignmentDB();

    foreach($this->Messages as $Message) {
      $Message->deleteFromDB();
    }
    foreach($this->Commits as $Commit) {
      $Commit->deleteFromDB();
    }

    $query = "DELETE FROM ax_assignment WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH ASSIGNMENT



// WORK WITH STUDENTS 

  public function addStudent($student_id) {
    $Student = new User((int)$student_id);
    $this->pushStudentToAssignmentDB($student_id);
    array_push($this->Students, $Student);
  }
  public function addStudents($Students) {
    $this->pushStudentsToAssignmentDB($Students);
    foreach ($Students as $Student) {
      array_push($this->Students, $Student);
    }
  }
  public function deleteStudent($student_id) {
    $index = $this->findStudentById($student_id);
    if ($index != -1) {
      $this->deleteStudentFromAssignmentDB($student_id);
      $this->Students[$index]->deleteFromDB();
      unset($this->Students[$index]);
    }
  }
  private function findStudentById($student_id) {
    $index = 0;
    foreach($this->Students as $Student) {
      if ($Student->id == $student_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getStudentById($student_id) {
    foreach($this->Students as $Student) {
      if ($Student->id == $student_id)
        return $Student;
    }
    return null;
  }
  public function checkStudent($student_id) {
    foreach($this->Students as $Student) {
      if ($Student->id == $student_id)
        return true;
    }
    return false;
  }

  private function pushStudentToAssignmentDB($student_id){
    global $dbconnect;

    $query = "INSERT INTO ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $student_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushStudentsToAssignmentDB($Students) {
    global $dbconnect;

    $query = "";
    if (!empty($Students)) {
      foreach($Students as $Student) {
        $query .= "INSERT INTO ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $Student->id);";
      }
    }
    
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteStudentFromAssignmentDB($student_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_assignment_student WHERE student_user_id = $student_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchStudentsToAssignmentDB() {
    global $dbconnect;

    $this->deleteStudentsFromAssignmentDB();

    $query = "";
    if (!empty($this->Students)) {
      foreach($this->Students as $Student) {
        $query .= "INSERT INTO ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $Student->id);";
      }
    }
    
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteStudentsFromAssignmentDB() {
    global $dbconnect;

    // Удаляем предыдущие прикрепления студентов
    $query = "DELETE FROM ax_assignment_student WHERE assignment_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH STUDENTS 



// WORK WITH MESSAGES

  public function addMessage($message_id) {
    $Message = new Message((int)$message_id);
    // $this->pushNewToDeliveryDB($Message);
    array_push($this->Messages, $Message);
  }
  public function deleteMessage($message_id) {
    $index = $this->findMessageById($message_id);
    if ($index != -1) {
      $this->Messages[$index]->deleteFromDB();
      unset($this->Messages[$index]);
    }
  }
  private function findMessageById($message_id) {
    $index = 0;
    foreach($this->Messages as $Message) {
      if ($Message->id == $message_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getMessageById($message_id) {
    foreach($this->Messages as $Message) {
      if ($Message->id == $message_id)
        return $Message;
    }
    return null;
  }
  public function getFirstUnreadedMessage($user_id) {
    global $dbconnect; 

    $User = new User((int)$user_id);

    $query = "SELECT min(ax_message.id) as min_message_id FROM ax_message WHERE assignment_id = $this->id
              AND status = 0 AND ax_message.sender_user_type != $User->role LIMIT 1;";
    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    return pg_fetch_assoc($pg_query)['min_message_id'];
  }
  public function getLastAnswerMessage() {
    for ($i=count($this->Messages)-1; $i >= 0; $i--) { 
      if ($this->Messages[$i]->getCommit() != null && $this->Messages[$i]->type != 3)
        return $this->Messages[$i];
    }
  }


  function pushNewToDeliveryDB($Message) {
    global $dbconnect;

    $query = "";
    foreach($this->Students as $Student) {
      if ($Student->id != $Message->sender_user_id) {
        $query .= "INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
                  VALUES ($Message->id, $Student->id, 0)";
      }
    }

    $Teachers = getTeachersByAssignment($this->id);
    foreach($Teachers as $Teacher) {
      if ($Teacher->id != $Message->sender_user_id) {
        $query .= "INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
                  VALUES ($Message->id, $Teacher->id, 0)";
      }
    }

    $query = "INSERT INTO ax_message_delivery (message_id, recipient_user_id, status)
              VALUES ($Message->id, $Message->sender_user_id, 1)";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // function getNewMessagesByUser($user_id) {
  //   $new_messages = array();
  //   foreach ($this->Messages as $Message) {
  //     if ($Message->getDeliveryStatus($user_id) == 0)
  //       array_push($new_messages, $Message);
  //   }
  //   return $new_messages;
  // }

  function getNewMessagesByUser($user_id) {
    $new_messages = array();
    $User = new User($user_id);
    foreach ($this->Messages as $Message) {
      if ($Message->status == 0 && $User->role != $Message->sender_user_type)
        array_push($new_messages, $Message);
    }
    return $new_messages;
  }

  public function getCountUnreadedMessages($user_id) {
    $count_unreaded = 0;
    foreach ($this->Messages as $Message) {
      if ($Message->status == 0)
        $count_unreaded++;
    }
    return $count_unreaded;
  }

// -- END WORK WITH MESSAGES 



// WORK WITH COMMITS

  public function addCommit($commit_id) {
    $Commit = new Commit((int)$commit_id);
    array_push($this->Commits, $Commit);
  }
  public function deleteCommit($commit_id) {
    $index = $this->findCommitById($commit_id);
    if ($index != -1) {
      $this->Commits[$index]->deleteFromDB();
      unset($this->Commits[$index]);
    }
  }
  private function findCommitById($commit_id) {
    $index = 0;
    foreach($this->Commits as $Commit) {
      if ($Commit->id == $commit_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getCommitById($commit_id) {
    foreach($this->Commits as $Commit) {
      if ($Commit->id == $commit_id)
        return $Commit;
    }
    return null;
  }
  public function getLastCommit() {
    return end($this->Commits);
  }

// -- END WORK WITH COMMITS 
  

}

function getTaskByAssignment($assignment_id) {
  global $dbconnect;

  $query = "SELECT task_id FROM ax_assignment WHERE id = $assignment_id";
  $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $task_id = pg_fetch_assoc($pg_query)['task_id'];

  return $task_id;
}


function getTeachersByAssignment($assignment_id) {
  global $dbconnect;

  $query = queryGetPageByAssignment($assignment_id);
  $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $page_id = pg_fetch_assoc($pg_query)['page_id'];

  return getTeachersByPage($page_id);
}


function getStudentsByAssignment($assignment_id) {
  global $dbconnect;

  $students = array();

  $query = queryGetStudentsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($student_row = pg_fetch_assoc($result)){
    array_push($students, new User((int)$student_row['id']));
  }

  return $students;
}

function getMessagesByAssignment($assignment_id) {
  global $dbconnect;

  $messages = array();

  $query = queryGetMessagesByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($message_row = pg_fetch_assoc($result)){
    array_push($messages, new Message((int)$message_row['id']));
  }

  return $messages;
}

function getCommitsByAssignment($assignment_id) {
  global $dbconnect;

  $commits = array();

  $query = queryGetCommitsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($commit_row = pg_fetch_assoc($result)){
    array_push($commits, new Commit((int)$commit_row['id']));
  }

  return $commits;
}



function queryGetAssignmentInfo($assignment_id) {
  return "SELECT *, to_char(ax_assignment.start_limit, 'YYYY-MM-DD') as converted_start_limit,
          to_char(ax_assignment.finish_limit, 'YYYY-MM-DD') as converted_finish_limit
          FROM ax_assignment WHERE id = $assignment_id";
}

function queryGetStudentsByAssignment($assignment_id){
  return "SELECT student_user_id as id FROM ax_assignment_student
          WHERE ax_assignment_student.assignment_id = $assignment_id;";
}

function queryGetMessagesByAssignment($assignment_id){
  return "SELECT id FROM ax_message
          WHERE assignment_id = $assignment_id
          ORDER BY id;";
}

function queryGetCommitsByAssignment($assignment_id){
  return "SELECT id FROM ax_solution_commit
          WHERE assignment_id = $assignment_id;";
}

function queryGetPageByAssignment($assignment_id) {
  return "SELECT page_id FROM ax_task WHERE ax_task.id = (SELECT task_id FROM ax_assignment WHERE ax_assignment.id = $assignment_id)";
}







function status_code_to_text($status_code) {
    switch ($status_code) {
      case 0:
        return "недоступно для просмотра"; 
      case 1:
        return "недоступно для выполнения";
      case 2: 
        return "активно";
      case 3:
        return "выполнено";
      case 4:
        return "отменено";
      case 5:
        return "ожидает проверки";
      default:
        return "ERROR!";
    }
  }
?>