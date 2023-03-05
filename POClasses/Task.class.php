<?php 
require_once("./settings.php");
require_once("Assignment.class.php");
require_once("File.class.php");

class Task {

  public $id;
  public $type, $title, $description;
  public $max_mark, $status, $checks;

  public $Assignments = array(); 
  public $Files = array();

  function __construct() {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 1 && is_int($args[0])) {
      $this->id = $args[0];

      $query = queryGetTaskInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $task = pg_fetch_assoc($result);

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

    else if ($count_args == 7) {
      $page_id = $args[0];

      $this->type = $args[1];
      $this->title = $args[2];
      $this->description = $args[3];

      $this->max_mark = $args[4];
      $this->status = $args[5];
      $this->checks = $args[6];

      $this->pushNewToDB($page_id);
    }

    else {
      die('Неверные аргументы в конструкторе');
    }

  }

  public function pushNewToDB($page_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_task (page_id, type, title, description, max_mark, status) 
              VALUES ('$page_id', '$this->type', '$this->title', '$this->description', $this->max_mark, '$this->status')
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_task SET type = $this->type, title = '$this->title', description = '$this->description', 
              max_mark = '$this->max_mark', status = $this->status
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
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


// WORK WITH ASSIGNMENT

  public function addAssignment($assignment_id) {
    $Assignment = new Assignment($assignment_id);
    array_push($this->Assignments, $Assignment);
  }
  public function deleteAssignment($assignment_id) {
    $index = $this->findAssignmentById($assignment_id);
    if ($index != -1) {
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

// -- END WORK WITH ASSIGNMENT


// WORK WITH FILE

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
  public function pushFileToTaskDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_task_file (task_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFileFromTaskDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_task_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH FILE
  
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