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
  private $AutoTests = array();

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
  

  // TODO: getAssignmentById
  // TODO: getFileById
  // TODO: getAutoTestById
  // TODO: addTaskFile
  // by else...
  
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