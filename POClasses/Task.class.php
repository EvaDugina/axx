<?php 
require_once("./settings.php");
require_once("Assignment.class.php");
require_once("File.class.php");

class Task {

  private $id;
  private $page_id, $type, $title, $description;
  private $max_mark, $status, $checks;

  private $Assignments = array(); 
  private $Files = array();

  function __construct($task_id) {
    global $dbconnect;

    $this->id = $task_id;

    $query = queryGetTaskInfo($this->id);
    $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $task = pg_fetch_assoc($result);

    $this->page_id = $task['page_id'];
    $this->type = $task['type'];
    $this->title = $task['title'];
    $this->description = $task['description'];

    $this->max_mark = $task['max_mark'];
    $this->status = $task['status'];
    $this->checks = $task['checks'];

    $this->Assignments = getAssignmentsByTask($this->id);
    $this->Files = getFilesByTask($this->id);
    // $this->AutoTests = getAutoTestsByTask($this->id);
  }


// GETTERS

  public function getId(){
    return $this->id;
  }

  public function getPageId(){
    return $this->page_id;
  }

  public function getType(){
    return $this->type;
  }

  public function getTitle(){
    return $this->title;
  }

  public function getDescription(){
    return $this->description;
  }

  public function getMaxMark(){
    return $this->max_mark;
  }

  public function getStatus(){
    return $this->status;
  }

  public function getChecks(){
    return $this->checks;
  }

  public function getAssignments(){
    return $this->Assignments;
  }

  public function getFiles(){
    return $this->Files;
  }

// -- END GETTERS


// SETTERS

  public function setPageId($page_id) {
    $this->page_id = $page_id;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setMaxMark($max_mark) {
    $this->max_mark = $max_mark;
  }

  public function setStatus($status) {
    $this->status = $status;
  }

  public function setChecks($checks) {
    $this->checks = $checks;
  }

  public function setAssignments($Assignments) {
    $this->Assignments = $Assignments;
  }

  public function setFiles($Files) {
    $this->Files = $Files;
  }

// -- END SETTERS


  public function addAssignment($assignment_id) {
    $Assignment = new Assignment($assignment_id);
    //$this->pushAssignmentToAssignmentDB($assignment_id);
    array_push($this->Assignments, $Assignment);
  }
  public function deleteAssignment($assignment_id) {
    $index = $this->findAssignmentById($assignment_id);
    if ($index != -1) {
      //$this->deleteStudentFromAssignmentDB($student_id);
      $this->Assignments[$index]->deleteFromDB();
      unset($this->Assignments[$index]);
    }
  }
  private function findAssignmentById($assignment_id) {
    $index = 0;
    foreach($this->Assignments as $Assignment) {
      if ($Assignment->getId() == $assignment_id)
        return $index;
      $index++;
    }
    return -1;
  }

  public function addFile($file_id) {
    $File = new File($file_id);
    $this->pushFileToTaskDB($file_id);
    array_push($this->Files, $File);
  }
  public function deleteFile($file_id) {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromTaskDB($file_id);
      $this->Files[$index]->deleteFromDB();
      unset($this->Files[$index]);
    }
  }
  private function findFileById($file_id) {
    $index = 0;
    foreach($this->Files as $File) {
      if ($File->getId() == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }


  public function deleteFromDB() {
    global $dbconnect;

    foreach($this->Assignments as $Assignment) {
      $Assignment->deleteFromDB();
    }
    $query = "DELETE FROM ax_assignment_student WHERE assignment_id = $this->id;";

    foreach($this->Files as $File) {
      $File->deleteFromDB();
    }
    $query = "DELETE FROM ax_task_file WHERE task_id = $this->id;";

    $query .= "DELETE FROM ax_task WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }


  public function pushFileToTaskDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_message_file (message_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFileFromTaskDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_message_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  
}





function getAssignmentsByTask($task_id) {
  global $dbconnect;

  $assignments = array();

  $query = queryGetAssignmentsByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $assignment_ids = pg_fetch_all($result);

  foreach($assignment_ids as $assignment_id) {
    array_push($assignments, new Assignment($assignment_id));
  }

  return $assignments;
}

function getFilesByTask($task_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $task_files = pg_fetch_all($result);

  foreach($task_files as $file) {
    array_push($files, new File($file['file_name'], $file['download_url'], $file['download_url']));
  }

  return $files;
}




// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetTaskInfo($task_id) {
  return "SELECT * FROM ax_task WHERE ax_task.id = $task_id 
          ORDER BY ax_task.id;
  ";
}

function queryGetAssignmentsByTask($task_id) {
  return "SELECT id FROM ax_assignment WHERE task_id = $task_id ORDER BY id;
  ";
}

function queryGetFilesByTask($task_id) {
  return "SELECT * FROM ax_task_file WHERE task_id = $task_id;
  ";
}

?>