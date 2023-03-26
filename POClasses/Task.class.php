<?php 
require_once("./settings.php");
require_once("Assignment.class.php");
require_once("File.class.php");

class Task {

  public $id;
  public $type, $title, $description;
  public $max_mark, $status, $checks;

  private $Assignments = array(); 
  private $Files = array();

  public function __construct() {
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

    else if ($count_args == 3) {
      $page_id = $args[0];
      $this->type = $args[1];
      $this->status = $args[2];
      $this->max_mark = 5;

      $this->pushEmptyNewToDB($page_id);
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
      die('Неверные аргументы в конструкторе Task');
    }
  }


  public function getAssignments() {
    return $this->Assignments;
  }
  public function getFiles() {
    return $this->Files;
  }


// SETTERS:

  public function setStatus($status) {
    global $dbconnect;

    $this->status = $status;

    $query = "UPDATE ax_task SET status = $this->status WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setTitle($title) {
    global $dbconnect;

    $this->title = $title;

    $query = "UPDATE ax_task SET title = \$antihype1\$$this->title\$antihype1\$ WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END SETTERS



// WORK WITH TASK

  public function pushNewToDB($page_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_task (page_id, type, title, description, max_mark, status) 
              VALUES ($page_id, $this->type, \$antihype1\$$this->title\$antihype1\$, \$antihype1\$$this->description\$antihype1\$, $this->max_mark, $this->status)
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushEmptyNewToDB($page_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_task (page_id, type, status, max_mark) 
              VALUES ($page_id, $this->type, $this->status, '$this->max_mark')
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = (int)$result['id'];
  }
  public function pushChangesToDB() {
    global $dbconnect;

    $query = "UPDATE ax_task SET type = $this->type, title = \$antihype1\$$this->title\$antihype1\$, 
              description = \$antihype1\$$this->description\$antihype1\$, status = $this->status
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB() {
    global $dbconnect;

    foreach($this->Assignments as $Assignment) {
      $Assignment->deleteFromDB();
    }

    foreach($this->Files as $File) {
      $File->deleteFromDB();
    }
    $query = "DELETE FROM ax_task_file WHERE task_id = $this->id;";

    $query .= "DELETE FROM ax_task WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH TASK



// WORK WITH ASSIGNMENT

  public function addAssignment($assignment_id) {
    $Assignment = new Assignment((int)$assignment_id);
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
      if ($Assignment->id == $assignment_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getAssignmentById($assignment_id) {
    foreach($this->Assignments as $Assignment) {
      if ($Assignment->id == $assignment_id)
        return $Assignment;
    }
    return null;
  }

// -- END WORK WITH ASSIGNMENT



// WORK WITH FILE

  public function addFile($file_id) {
    $File = new File((int)$file_id);
    $this->pushFileToTaskDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files) {
    $this->pushFilesToTaskDB($Files);
    foreach ($Files as $File) {
      array_push($this->Files, $File);
    }
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
      if ($File->id == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getFileById($file_id) {
    foreach($this->Files as $File) {
      if ($File->id == $file_id)
        return $File;
    }
    return null;
  }
  public function getFilesByType($type) {
    $Files = array();
    foreach($this->Files as $File) {
      if ($File->type == $type)
        array_push($Files, $File);
    }
    return $Files;
  }

  private function pushFileToTaskDB($file_id) {
    global $dbconnect;

    $query = "INSERT INTO ax_task_file (task_id, file_id) VALUES ($this->id, $file_id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToTaskDB($Files) {
    global $dbconnect;

    $query = "";
    foreach ($Files as $File)
    $query .= "INSERT INTO ax_task_file (task_id, file_id) VALUES ($this->id, $File->id);";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFileFromTaskDB($file_id) {
    global $dbconnect;

    $query = "DELETE FROM ax_task_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchFilesToTaskDB() {
    global $dbconnect;

    $this->deleteFilesFromTaskDB();
  
    $query = "";
      if (!empty($this->Files)) {
        foreach($this->Files as $File) {
          $query .= "INSERT INTO ax_task_file (task_id, file_id) VALUES ($this->id, $File->id);";
        }
      }
      
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFilesFromTaskDB() {
    global $dbconnect;
  
    $query = "DELETE FROM ax_task_file WHERE task_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

// -- END WORK WITH FILE
  
}


function getAssignmentsByTask($task_id) {
  global $dbconnect;

  $assignments = array();

  $query = queryGetAssignmentsByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($row_assignment = pg_fetch_assoc($result)) {
    array_push($assignments, new Assignment((int)$row_assignment['id']));
  }

  return $assignments;
}

function getFilesByTask($task_id) {
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while($file = pg_fetch_assoc($result)) {
    array_push($files, new File((int)$file['file_id']));
  }

  return $files;
}

function getPageByTask($task_id) {
  global $dbconnect;

  $query = queryGetPageByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $page_id = pg_fetch_assoc($result)['page_id'];

  return $page_id;
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

function queryGetPageByTask($task_id) {
  return "SELECT page_id FROM ax_task WHERE id =$task_id;
  ";
}

?>