<?php
require_once("./settings.php");
require_once("Assignment.class.php");
require_once("File.class.php");

class Task
{

  public $id;
  public $type; // 0 - обычное, 1 - программирование, 2 - общая беседа потока
  public $title, $description;
  public $mark_type, $max_mark;
  public $status; // 1 - активно, 0 - архив
  public $checks;

  private $Assignments = array();
  private $Files = array();

  public function __construct()
  {
    global $dbconnect;

    $count_args = func_num_args();
    $args = func_get_args();

    // Перегружаем конструктор по количеству подданых параметров

    if ($count_args == 0) {
      $this->id = null;

      $this->type = -1;
      $this->title = "";
      $this->description = "";

      $this->mark_type = "";
      $this->max_mark = "";
      $this->status = -1;
      $this->checks = "";

      $this->Assignments = [];
      $this->Files = [];
    } else if ($count_args == 1) {
      $this->id = (int)$args[0];

      $query = queryGetTaskInfo($this->id);
      $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      $task = pg_fetch_assoc($result);

      $this->type = $task['type'];
      $this->title = $task['title'];
      $this->description = $task['description'];

      $this->mark_type = $task['mark_type'];
      $this->max_mark = $task['max_mark'];
      $this->status = $task['status'];
      $this->checks = $task['checks'];

      $this->Assignments = getAssignmentsByTask($this->id);
      $this->Files = getFilesByTask($this->id);
      // $this->AutoTests = getAutoTestsByTask($this->id);
    } else if ($count_args == 3) {
      $page_id = $args[0];
      $this->type = $args[1];
      $this->status = $args[2];
      $this->mark_type = "";
      $this->max_mark = 5;

      $this->pushEmptyNewToDB($page_id);
    } else if ($count_args == 8) {
      $page_id = $args[0];

      $this->type = $args[1];
      $this->title = $args[2];
      $this->description = $args[3];

      $this->mark_type = $args[4];
      $this->max_mark = $args[5];
      $this->status = $args[6];
      $this->checks = $args[7];

      $this->pushNewToDB($page_id);
    } else {
      die('Неверные аргументы в конструкторе Task');
    }
  }


  // GETTERS:

  public function getAssignments()
  {
    return $this->Assignments;
  }
  public function getFiles()
  {
    return $this->Files;
  }


  public function getInitialCodeFiles()
  {
    return $this->getFilesByType(1);
  }

  public function getCodeTestFiles()
  {
    return $this->getFilesByType(2);
  }

  public function getCodeCheckTestFiles()
  {
    return $this->getFilesByType(3);
  }


  public function isCompleted($student_id)
  {
    foreach ($this->Assignments as $Assignment) {
      if ($Assignment->checkStudent($student_id) && $Assignment->status != 4)
        return false;
    }
    return true;
  }

  public function getCountCompletedAssignments($student_id)
  {
    $count_success = 0;
    if ($this->status == 1) {
      foreach ($this->getVisibleAssignmemntsByStudent($student_id) as $Assignment) {
        if ($Assignment->isMarked())
          $count_success++;
      }
    }
    return $count_success;
  }

  public function getMainInfoAsTextForDowload()
  {
    $this->title = addslashes($this->title);
    $this->description = addslashes($this->description);
    $query_task = queryInsertTaskWithDeclaredVariablePageId($this);

    $query_files = "";
    foreach ($this->getFiles() as $File) {
      $query_files .= "\n" . $File->getMainInfoAsTextForDowload();
      $query_files .= "\n" . queryInsertFileToTaskDBWithDeclaredVariableTaskId();
    }
    $query_task .= $query_files;

    return $query_task;
  }

  // -- END GETTERS



  // SETTERS:

  public function setStatus($status)
  {
    global $dbconnect;

    $this->status = $status;

    $query = "UPDATE ax.ax_task SET status = $this->status WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setTitle($title)
  {
    global $dbconnect;

    $this->title = $title;

    $query = "UPDATE ax.ax_task SET title = \$antihype1\$$this->title\$antihype1\$ WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setMarkType($mark_type)
  {
    global $dbconnect;

    $this->mark_type = $mark_type;

    $query = "UPDATE ax.ax_task SET mark_type = '$this->mark_type' WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function setMarkMax($max_mark)
  {
    global $dbconnect;

    $this->max_mark = $max_mark;

    $query = "UPDATE ax.ax_task SET max_mark = '$this->max_mark' WHERE id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END SETTERS



  // WORK WITH TASK

  public function pushNewToDB($page_id)
  {
    global $dbconnect;

    $query = queryInsertTask($page_id, $this);

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = $result['id'];
  }
  public function pushEmptyNewToDB($page_id)
  {
    global $dbconnect;

    $query = "INSERT INTO ax.ax_task (page_id, type, status, mark_type, max_mark) 
              VALUES ($page_id, $this->type, $this->status, '$this->mark_type', '$this->max_mark')
              RETURNING id;";

    $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    $result = pg_fetch_assoc($pg_query);

    $this->id = (int)$result['id'];
  }
  public function pushChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_task SET type = $this->type, title = \$antihype1\$$this->title\$antihype1\$, 
              description = \$antihype1\$$this->description\$antihype1\$, mark_type = '$this->mark_type', 
              max_mark = '$this->max_mark', status = $this->status
              WHERE id = $this->id;
    ";

    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function pushAllChangesToDB()
  {
    global $dbconnect;

    $query = "UPDATE ax.ax_task SET type = $this->type, title = \$antihype1\$$this->title\$antihype1\$, 
              description = \$antihype1\$$this->description\$antihype1\$, mark_type = '$this->mark_type', 
              max_mark = '$this->max_mark', status = $this->status, checks = \$antihype1\$$this->checks\$antihype1\$
              WHERE id = $this->id;
    ";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  public function deleteFromDB()
  {
    global $dbconnect;

    foreach ($this->Assignments as $Assignment) {
      $Assignment->deleteFromDB();
    }

    foreach ($this->Files as $File) {
      $File->deleteFromDB();
    }
    $query = "DELETE FROM ax.ax_task_file WHERE task_id = $this->id;";

    $query .= "DELETE FROM ax.ax_task WHERE id = $this->id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  public function copy($task_id)
  {
    $Task = new Task((int)$task_id);

    $this->type = $Task->type;
    $this->title = $Task->title;
    $this->description = $Task->description;
    $this->mark_type = $Task->mark_type;
    $this->max_mark = $Task->max_mark;
    $this->status = $Task->status;
    $this->checks = $Task->checks;
    $this->pushAllChangesToDB();

    $this->deleteFilesFromTaskDB();
    $this->addFiles($Task->getFiles());
  }


  public function isMarkNumber()
  {
    if ($this->mark_type == "оценка")
      return true;
    return false;
  }

  public function isDefault()
  {
    if ($this->type == 0)
      return true;
    return false;
  }
  public function isProgramming()
  {
    if ($this->type == 1)
      return true;
    return false;
  }
  public function isConversation()
  {
    if ($this->type == 2)
      return true;
    return false;
  }
  public function isActive()
  {
    if ($this->status == 1)
      return true;
    return false;
  }
  public function isArchived()
  {
    if ($this->status == 0)
      return true;
    return false;
  }

  // -- END WORK WITH TASK



  // WORK WITH ASSIGNMENT

  public function addAssignment($assignment_id)
  {
    $Assignment = new Assignment((int)$assignment_id);
    array_push($this->Assignments, $Assignment);
  }
  public function deleteAssignment($assignment_id)
  {
    $index = $this->findAssignmentById($assignment_id);
    if ($index != -1) {
      $this->Assignments[$index]->deleteFromDB();
      unset($this->Assignments[$index]);
      $this->Assignments = array_values($this->Assignments);
    }
  }
  private function findAssignmentById($assignment_id)
  {
    $index = 0;
    foreach ($this->Assignments as $Assignment) {
      if ($Assignment->id == $assignment_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getAssignmentById($assignment_id)
  {
    foreach ($this->Assignments as $Assignment) {
      if ($Assignment->id == $assignment_id)
        return $Assignment;
    }
    return null;
  }

  public function getActiveAssignments()
  {
    $active_Assignments = array();
    foreach ($this->Assignments as $Assignment) {
      if ($Assignment->isVisible())
        array_push($active_Assignments, $Assignment);
    }
    return $active_Assignments;
  }
  public function getVisibleAssignmemntsByStudent($student_id)
  {
    $student_Assignments = array();
    foreach ($this->Assignments as $Assignment) {
      if (($Assignment->isVisibleForStudent()) && $Assignment->checkStudent($student_id))
        array_push($student_Assignments, $Assignment);
    }
    return $student_Assignments;
  }
  public function hasUncheckedAssignments($student_id)
  {
    foreach ($this->Assignments as $Assignment) {
      if (($Assignment->isWaitingCheck()) && $Assignment->checkStudent($student_id))
        return true;
    }
    return false;
  }
  public function getUncheckedAssignmentsForStudent($student_id)
  {
    $uncheckedAssignments = array();
    foreach ($this->Assignments as $Assignment) {
      if (($Assignment->status == 1) && $Assignment->checkStudent($student_id))
        array_push($uncheckedAssignments, $Assignment);
    }
    return $uncheckedAssignments;
  }
  public function getAllUncheckedAssignments()
  {
    $uncheckedAssignments = array();
    foreach ($this->Assignments as $Assignment) {
      if (($Assignment->status == 1))
        array_push($uncheckedAssignments, $Assignment);
    }
    return $uncheckedAssignments;
  }
  public function getLastAssignmentByStudent($student_id)
  {
    $last_Assignment = null;
    foreach ($this->Assignments as $Assignment) {
      if ($Assignment->checkStudent($student_id))
        $last_Assignment = $Assignment;
    }
    return $last_Assignment;
  }

  public function createConversationAssignment($Students)
  {
    $conversationAssignment = new Assignment($this->id, 2, -1);
    $conversationAssignment->addStudents($Students);
    $this->addAssignment($conversationAssignment->id);
    return $conversationAssignment;
  }
  public function getConversationAssignment()
  {
    if (count($this->getAssignments()) > 0)
      return $this->getAssignments()[0];
    return null;
  }

  // -- END WORK WITH ASSIGNMENT



  // WORK WITH FILE

  public function addFile($file_id)
  {
    $File = new File((int)$file_id);
    $this->pushFileToTaskDB($file_id);
    array_push($this->Files, $File);
  }
  public function addFiles($Files)
  {
    $copyFiles = array();
    foreach ($Files as $File) {
      $copiedFile = new File($File->type, $File->name_without_prefix);
      $copiedFile->copy($File->id);
      array_push($copyFiles, $copiedFile);
    }
    $this->pushFilesToTaskDB($copyFiles);

    foreach ($copyFiles as $File) {
      array_push($this->Files, $File);
    }
  }
  public function deleteFile($file_id)
  {
    $index = $this->findFileById($file_id);
    if ($index != -1) {
      $this->deleteFileFromTaskDB($file_id);
      $this->Files[$index]->deleteFromDB();
      unset($this->Files[$index]);
      $this->Files = array_values($this->Files);
    }
  }
  private function findFileById($file_id)
  {
    $index = 0;
    foreach ($this->Files as $File) {
      if ($File->id == $file_id)
        return $index;
      $index++;
    }
    return -1;
  }
  public function getFileById($file_id)
  {
    foreach ($this->Files as $File) {
      if ($File->id == $file_id)
        return $File;
    }
    return null;
  }
  public function getFilesByType($type)
  {
    $Files = array();
    foreach ($this->Files as $File) {
      if ($File->type == $type)
        array_push($Files, $File);
    }
    return $Files;
  }
  public function getVisibleFiles()
  {
    $Files = array();
    foreach ($this->Files as $File) {
      if ($File->isVisible())
        array_push($Files, $File);
    }
    return $Files;
  }

  public function getStudentFilesToTaskchat()
  {
    $Files = array();
    foreach ($this->Files as $File) {
      if ($File->isVisible() && $File->isAttached())
        array_push($Files, $File);
    }
    return $Files;
  }

  public function getTeacherFilesToTaskchat()
  {
    $Files = array();
    foreach ($this->Files as $File) {
      if ($File->isAttached() || $File->isCodeTest() || $File->isCodeCheckTest())
        array_push($Files, $File);
    }
    return $Files;
  }

  private function pushFileToTaskDB($file_id)
  {
    global $dbconnect;

    $query = queryInsertFileToTaskDB($this->id, $file_id);
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function pushFilesToTaskDB($Files)
  {
    global $dbconnect;

    $query = "";
    foreach ($Files as $File)
      $query .= "INSERT INTO ax.ax_task_file (task_id, file_id) VALUES ($this->id, $File->id);";
    if (count($Files) > 0)
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function deleteFileFromTaskDB($file_id)
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_task_file WHERE file_id = $file_id;";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }
  private function synchFilesToTaskDB()
  {
    global $dbconnect;

    $this->deleteFilesFromTaskDB();

    if (!empty($this->Files)) {
      $query = "";
      foreach ($this->Files as $File) {
        $query .= "INSERT INTO ax.ax_task_file (task_id, file_id) VALUES ($this->id, $File->id);";
      }
      pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
    }
  }
  private function deleteFilesFromTaskDB()
  {
    global $dbconnect;

    $query = "DELETE FROM ax.ax_task_file WHERE task_id = $this->id";
    pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  }

  // -- END WORK WITH FILE

}


function getTaskByAssignment($assignment_id)
{
  global $dbconnect;

  $query = "SELECT task_id FROM ax.ax_assignment WHERE id = $assignment_id";
  $pg_query = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $task_id = pg_fetch_assoc($pg_query)['task_id'];

  return $task_id;
}


function getAssignmentsByTask($task_id)
{
  global $dbconnect;

  $assignments = array();

  $query = queryGetAssignmentsByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($row_assignment = pg_fetch_assoc($result)) {
    array_push($assignments, new Assignment((int)$row_assignment['id']));
  }

  return $assignments;
}

function getFilesByTask($task_id)
{
  global $dbconnect;

  $files = array();

  $query = queryGetFilesByTask($task_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  while ($file = pg_fetch_assoc($result)) {
    array_push($files, new File((int)$file['file_id']));
  }

  return $files;
}




function getSVGByTaskType($type)
{
  switch ($type) {
    case 0: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-text" viewBox="0 0 16 16">
        <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z" />
        <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z" />
      </svg>
    <?php break;
    case 1: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-slash" viewBox="0 0 16 16">
        <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z" />
      </svg>
    <?php break;
    case 2: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-square-text-fill" viewBox="0 0 16 16">
        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.5a1 1 0 0 0-.8.4l-1.9 2.533a1 1 0 0 1-1.6 0L5.3 12.4a1 1 0 0 0-.8-.4H2a2 2 0 0 1-2-2V2zm3.5 1a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z" />
      </svg>
<?php break;
  }
}




// ФУНКЦИИ ЗАПРОСОВ К БД

function queryGetTaskInfo($task_id)
{
  return "SELECT * FROM ax.ax_task WHERE ax.ax_task.id = $task_id 
          ORDER BY ax.ax_task.id;
  ";
}

function queryGetAssignmentsByTask($task_id)
{
  return "SELECT id FROM ax.ax_assignment WHERE task_id = $task_id ORDER BY id;
  ";
}

function queryGetFilesByTask($task_id)
{
  return "SELECT * FROM ax.ax_task_file WHERE task_id = $task_id
          ORDER BY id;
  ";
}

function queryInsertFileToTaskDB($task_id, $file_id)
{
  return "INSERT INTO ax.ax_task_file (task_id, file_id) VALUES ($task_id, $file_id);";
}

function queryInsertFileToTaskDBWithDeclaredVariableTaskId()
{
  return "INSERT INTO ax.ax_task_file (task_id, file_id) VALUES (current_task_id, current_file_id);";
}

function queryInsertTask($page_id, $Task)
{
  return "INSERT INTO ax.ax_task (page_id, type, title, description, mark_type, max_mark, status) 
              VALUES ($page_id, $Task->type, \$antihype1\$$Task->title\$antihype1\$, 
              \$antihype1\$$Task->description\$antihype1\$, \'$Task->mark_type\', $Task->max_mark, $Task->status)
              RETURNING id;";
}

function queryInsertTaskWithDeclaredVariablePageId($Task)
{
  return "INSERT INTO ax.ax_task (page_id, type, title, description, mark_type, max_mark, status) 
              VALUES (pageId, $Task->type, \$antihype1\$$Task->title\$antihype1\$, 
              \$antihype1\$$Task->description\$antihype1\$, \'$Task->mark_type\', $Task->max_mark, $Task->status)
              RETURNING id INTO current_task_id;";
}
?>