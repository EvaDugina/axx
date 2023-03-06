<?php 
require_once("./settings.php");
require_once("Message.class.php");
require_once("Commit.class.php");
require_once("User.class.php");

class Assignment {

  public $id = null;
  public $variant_comment = null;
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
  
      $query = queryGetMessageInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $message = pg_fetch_assoc($result);

      $this->variant_comment = $message['variant_comment'];
      $this->start_limit = $message['start_limit'];
      $this->finish_limit = $message['finish_limit'];

      $this->status_code = $message['status_code'];
      $this->status_text = $message['status_text'];

      $this->delay = $message['delay'];
      $this->mark = $message['mark'];
      $this->checks = $message['checks'];

      $this->Students = getStudentsByAssignment($this->id);
      $this->Messages = getMessagesByAssignment($this->id);
      $this->Commits = getCommitsByAssignment($this->id);
    }

    else if ($count_args == 9) { // всё + task_id
      $task_id = $args[0];

      $this->variant_comment = $args[1];
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
      die('Неверные аргументы в конструкторе');
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



// WORK WITH ASSIGNMENT

  public function pushNewToDB($task_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_assignment(task_id, variant_comment, start_limit, finish_limit, 
              status_code, delay, status_text, mark, checks)
              VALUES ($task_id, '$this->variant_comment', '$this->start_limit', '$this->finish_limit', 
              $this->status_code, $this->delay, '$this->status_text', '$this->mark', '$this->checks') 
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB(){
    global $dbconnect;

    $query = "UPDATE ax_assignment SET variant_comment='$this->variant_comment', start_limit='$this->start_limit', finish_limit='$this->finish_limit', 
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
    $Student = new User($student_id);
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
    $Message = new Message($message_id);
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

// -- END WORK WITH MESSAGES 



// WORK WITH COMMITS

  public function addCommit($commit_id) {
    $Commit = new Commit($commit_id);
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

// -- END WORK WITH MESSAGES 
  

}




function getStudentsByAssignment($assignment_id) {
  global $dbconnect;

  $students = array();

  $query = queryGetStudentsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($student_row = pg_fetch_assoc($result)){
    array_push($students, new User($student_row['id']));
  }

  return $students;
}

function getMessagesByAssignment($assignment_id) {
  global $dbconnect;

  $messages = array();

  $query = queryGetMessagesByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($message_row = pg_fetch_assoc($result)){
    array_push($messages, new Message($message_row['id']));
  }

  return $messages;
}

function getCommitsByAssignment($assignment_id) {
  global $dbconnect;

  $commits = array();

  $query = queryGetCommitsByAssignment($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($commit_row = pg_fetch_assoc($result)){
    array_push($commits, new Commit($commit_row['id']));
  }

  return $commits;
}



function queryGetStudentsByAssignment($assignment_id){
  return "SELECT student_user_id as id FROM ax_assignment_student
          WHERE ax_assignment_student.assignment_id = $assignment_id;";
}

function queryGetMessagesByAssignment($assignment_id){
  return "SELECT id FROM ax_message
          WHERE assignment_id = $assignment_id;";
}

function queryGetCommitsByAssignment($assignment_id){
  return "SELECT id FROM ax_solution_commit
          WHERE assignment_id = $assignment_id;";
}

?>