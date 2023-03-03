<?php 
require_once("./settings.php");
require_once("Message.class.php");
require_once("Commit.class.php");
require_once("User.class.php");

class Assignment {

  private $id = null;
  private $variant_comment = null;
  private $start_limit = null, $finish_limit = null;
  private $status_code = null, $status_text = null;
  private $delay = null; // не понятно, зачем нужно
  private $mark = null;
  private $checks = null;

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

  }


  // GETTERS

    public function getId(){
      return $this->id;
    }

    public function getVariantComment(){
      return $this->variant_comment;
    }

    public function getStartLimit(){
      return $this->start_limit;
    }

    public function getFinishLimit(){
      return $this->finish_limit;
    }

    public function getStatusCode(){
      return $this->status_code;
    }

    public function getStatusText(){
      return $this->status_text;
    }

    public function getDelay(){
      return $this->delay;
    }

    public function getMark(){
      return $this->mark;
    }

    public function getChecks(){
      return $this->checks;
    }

    public function getStudents(){
      return $this->Students;
    }

    public function getMessages(){
      return $this->Messages;
    }

    public function getCommits(){
      return $this->Commits;
    }

  // -- END GETTERS


  // SETTERS

    public function setVariantComment($variant_comment){
      $this->variant_comment = $variant_comment;
    }

    public function setStartLimit($start_limit){
      $this->start_limit = $start_limit;
    }

    public function setFinishLimit($finish_limit){
      $this->finish_limit = $finish_limit;
    }

    public function setStatusCode($status_code){
      $this->status_code = $status_code;
    }

    public function setStatusText($status_text){
      $this->status_text = $status_text;
    }

    public function setDelay($delay){
      $this->delay = $delay;
    }

    public function setMark($mark){
      $this->mark = $mark;
    }

    public function setChecks($checks){
      $this->checks = $checks;
    }

    public function setStudents($Students){
      $this->Students = $Students;
    }

    public function setMessages($Messages){
      $this->Messages = $Messages;
    }

    public function setCommits($Commits){
      $this->Commits = $Commits;
    }

  // -- END SETTERS

  public function addStudent($student_id) {
    $Student = new User($student_id);
    $this->pushStudentToAssignmentDB($student_id);
    array_push($this->Students, $Student);
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
      if ($Student->getId() == $student_id)
        return $index;
      $index++;
    }
    return -1;
  }


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
      if ($Message->getId() == $message_id)
        return $index;
      $index++;
    }
    return -1;
  }


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
      if ($Commit->getId() == $commit_id)
        return $index;
      $index++;
    }
    return -1;
  }



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
  public function pushAssignmentChangesToDB(){
    global $dbconnect;

    $query = "UPDATE ax_assignment SET variant_comment='$this->variant_comment', start_limit='$this->start_limit', finish_limit='$this->finish_limit', 
              status_code=$this->status_code, delay=$this->delay, status_text='$this->status_text', mark='$this->mark', checks='$this->checks' 
              WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;
  
    foreach($this->Students as $Student) {
      $Student->deleteFromDB();
    }
    $query = "DELETE FROM ax_assignment_student WHERE assignment_id = $this->id;";

    foreach($this->Messages as $Message) {
      $Message->deleteFromDB();
    }
    foreach($this->Commits as $Commit) {
      $Commit->deleteFromDB();
    }

    $query .= "DELETE FROM ax_assignment WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function pushStudentToAssignmentDB($student_id){
    global $dbconnect;

    $query = "INSERT INTO ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $student_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteStudentFromAssignmentDB($student_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_assignment_student WHERE student_user_id = $student_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushStudentsToDB() {
    global $dbconnect;

    $this->deleteStudentsFromDB();

    $query = "";
    if (!empty($this->Students)) {
      foreach($this->Students as $Student) {
        $query .= "INSERT INTO ax_assignment_student (assignment_id, student_user_id) VALUES ($this->id, $Student->getId());";
      }
    }
    
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteStudentsFromDB() {
    global $dbconnect;

    // Удаляем предыдущие прикрепления студентов
    $query = "DELETE FROM ax_assignment_student WHERE assignment_id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  
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