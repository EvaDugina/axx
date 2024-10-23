<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/Assignment.class.php");
require_once("POClasses/Task.class.php");
require_once("POClasses/File.class.php");


if (isset($_POST['flag-editFileVisibility']) && isset($_POST['file_id'])) {
  $File = new File((int)$_POST['file_id']);
  if (isset($_POST['new_visibility']))
    $File->setVisibility((int)$_POST['new_visibility']);

  echo getSVGByAssignmentVisibility($File->visibility * 2);
  exit();
}

// Проверка на корректный запрос
if ((isset($_POST['page_id']))) {
  $page_id = $_POST['page_id'];
  $Task = new Task($page_id, 0, 1);
} else if (isset($_POST['task_id']) && $_POST['task_id'] != -1) {
  $Task = new Task((int)$_POST['task_id']);
} else {
  header('Location: index.php');
  exit;
}

// Создание Task
if (isset($_POST['flag-createTask']) && $_POST['flag-createTask']) {
  $return_json = array("task_id" => $Task->id);
  echo json_encode($return_json);
  exit;
}


// Архивирование и разархивирование задания
if (isset($_POST['action']) && isset($_POST['task_id']) && ($_POST['action'] == 'archive' || $_POST['action'] == 'rearchive')) {
  if ($_POST['action'] == 'archive') {
    //echo "АРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 0;
  } else {
    //echo "РАЗАРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 1;
  }
  $Task->setStatus($new_status);
  header('Location:' . $_SERVER['HTTP_REFERER']);
  exit();
}


if (isset($_POST['flag-deleteFile']) && isset($_POST['task_id'])) {
  $file_id = $_POST['file_id'];
  $Task->deleteFile($file_id);
  // $query = pg_query($dbconnect, delete_ax_task_file($task_file_id));
  echo "";
  exit();
}

if (isset($_POST['flag-editFileType']) && isset($_POST['task_id'])) {
  $file_type = $_POST['new_type'];

  // В задании не может быть несколько файлов автоматической проверки
  if (($file_type == 2 || $file_type == 3)  && count($Task->getFilesByType($file_type)) > 0) {
    echo "ERROR: NO_MORE_FILES_CODE";
    exit();
  }

  $file_id = $_POST['file_id'];
  // $Task = new Task((int)$_POST['task_id']);
  $File = $Task->getFileById((int)$file_id);
  if ($File->isInUploadDir() && $file_type == 1) {
    echo "ERROR: EXT_FOR_CODE_PROJECT";
    exit();
  }

  $File->setType($file_type);

  // header('Location: taskedit.php?task=' . $_POST['task_id']);
  echo getSVGByFileType($File->type);
  exit();
}

// Удаление задания
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['task_id'])) {
  //echo "УДАЛЕНИЕ ЗАДАНИЯ";
  $query = pg_query($dbconnect, select_page_by_task_id($_POST['task_id']));
  $page_id = pg_fetch_assoc($query)['page_id'];

  // TODO: Придумать, как удалять auto_test_result!
  $Task->deleteFromDB();

  header('Location: preptasks.php?page=' . $page_id);
  exit();
}

if (isset($_POST['action']) && $_POST['action'] == "save") {
  if (isset($_POST['status'])) {
    $Task->status = $_POST['status'];
  }

  if (isset($_POST['title'])) {
    $Task->title = $_POST['title'];
  }

  if (isset($_POST['type'])) {
    $Task->type = $_POST['type'];
  }

  if (isset($_POST['markType'])) {
    $Task->mark_type = $_POST['markType'];
  }

  if (isset($_POST['markMax'])) {
    $Task->max_mark = $_POST['markMax'];
  }

  if (isset($_POST['description'])) {
    $Task->description = $_POST['description'];
  }
  $Task->pushChangesToDB();

  if (isset($_POST['codeTest'])) {
    $Files = $Task->getFilesByType(2);
    if ($_POST['codeTest'] != "") {
      if (empty($Files)) {
        $File = new File(2, "autotest.cpp", null, $_POST['codeTest']);
        $Task->addFile($File->id);
      } else {
        foreach ($Files as $File) {
          $File->setFullText($_POST['codeTest']);
          $File->pushChangesToDB();
        }
      }
    } else {
      foreach ($Files as $File) {
        $Task->deleteFile($File->id);
      }
    }
  }

  if (isset($_POST['extCodeTest'])) {
    $newExt = $_POST['extCodeTest'];
    $codeTestFile = $Task->getCodeTestFiles()[0];
    $codeTestFile->setName(true, $codeTestFile->getNameWithoutPrefixAndExt() . ".$newExt");
  }

  if (isset($_POST['codeCheck'])) {
    $Files = $Task->getFilesByType(3);
    if (empty($Files)) {
      $File = new File(3, "checktest.cpp", null, $_POST['codeCheck']);
      $Task->addFile($File->id);
    } else {
      foreach ($Files as $File) {
        $File->setFullText($_POST['codeCheck']);
        $File->pushChangesToDB();
      }
    }
  }

  echo showFiles($Task->getFiles(), true, $Task->id);

  exit();
}

// Сохранение изменений
// if (isset($_POST['action']) && $_POST['action'] == "save") {
//   $Task->type = $_POST['task-type'];
//   $Task->title = $_POST['task-title'];
//   $Task->description = $_POST['task-description'];
//   $Task->pushChangesToDB();
//   header('Location:' . $_SERVER['HTTP_REFERER']);
//   exit();
// }
if (isset($_POST['action']) && isset($_POST['assignment_id']) && ($_POST['action'] == 'reject') && ($_POST['assignment_id'] != 0)) {
  $Asssignment = new Assignment((int)$_POST['assignment_id']);
  // $query = pg_query($dbconnect, delete_assignment($_POST['assignment_id']));
  $Asssignment->deleteFromDB();
  header('Location:' . $_SERVER['HTTP_REFERER']);
  exit();
}

if (isset($_POST['task_id'])) {
  //echo "РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕГО ЗАДАНИЯ";
  $task_id = $_POST['task_id'];
  if (isset($_POST['flag-editTaskInfo'])) {
    $query = update_ax_task($_POST['task_id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
    $result = pg_query($dbconnect, $query);
    save_test_files($dbconnect, $task_id);
    header('Location:' . $_SERVER['HTTP_REFERER']);
    exit();
  }
} else {
  //echo "СОЗДАНИЕ ЗАДАНИЯ";
  if (isset($_POST['flag-editTaskInfo'])) {
    $query = insert_ax_task($page_id, $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
  } else {
    $query = insert_ax_task($page_id, 1, "", "");
  }
  $result = pg_query($dbconnect, $query);
  $task_id = pg_fetch_assoc($result)['id'];
  save_test_files($dbconnect, $task_id);
  header('Location: taskedit.php?task=' . $task_id);
  exit();
}

// Прикрепление файлов к заданию
if (isset($_POST['flag-addFiles'])) {
  if (!isset($_FILES['add-files'])) {
    echo showFiles($Task->getFiles(), true, $Task->id);
    exit;
  }
  $files = convertWebFilesToFiles('add-files');

  for ($i = 0; $i < count($files); $i++) {
    addFileToObject($Task, $files[$i]['name'], $files[$i]['tmp_name'], 0);
  }

  // header('Location: taskedit.php?task=' . $Task->id);
  echo showFiles($Task->getFiles(), true, $Task->id);
  exit;
}

// Изменение finish_limit всех Assignments прикреплённых к Task
if (isset($_POST['action']) && $_POST['action'] == "editFinishLimit") {
  if (isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
  } else {
    header('Location: index.php');
    exit;
  }

  foreach ($Task->getAssignments() as $Asssignment) {
    $Asssignment->setFinishLimit($_POST['finish_limit']);
  }

  exit();
}

// Изменение status всех Assignments прикреплённых к Task
if (isset($_POST['action']) && $_POST['action'] == "editVisibility") {
  if (isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
  } else {
    header('Location: index.php');
    exit;
  }

  $return_values = array();

  foreach ($Task->getAssignments() as $Assignment) {
    $Assignment->setVisibility((int)$_POST['visibility']);
    $return_value = array(
      "assignment_id" => $Assignment->id,
      "svg" => getSVGByAssignmentVisibilityAsText($Assignment->visibility),
      "next_visibility" => $Assignment->getNextAssignmentVisibility(),
      "visibility_to_text" => visibility_to_text($Assignment->getNextAssignmentVisibility())
    );

    array_push($return_values, $return_value);
  }

  // Не простое эхо, комментировать нельзя!
  // echo getSVGByAssignmentVisibility((int)$_POST['visibility']);
  echo json_encode($return_values);

  exit();
}

if (isset($_POST['action']) && $_POST['action'] == "editStatus") {
  if (isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
  } else {
    header('Location: index.php');
    exit;
  }

  foreach ($Task->getAssignments() as $Assignment) {
    if ($Assignment->mark != null)
      $Assignment->setStatus(2);
    else
      $Assignment->setStatus($_POST['status']);
  }

  // Не простое эхо, комментировать нельзя!
  echo getSVGByAssignmentStatus((int)$_POST['status']);

  exit();
}

header('Location: preptasks.php?page=' . $page_id);
?>



<?php // ФУНКЦИИ

// Прикрепление группы к странице предмета, если ещё не открыт доступ
function add_group_to_ax_page_group($page_id, $group_id)
{
  global $dbconnect;
  $query = select_ax_page_group($page_id, $group_id);
  $result = pg_query($dbconnect, $query);
  $group = pg_fetch_assoc($result);
  if (!$group) {
    // Если группе ещё не открыт доступ к предмету - открываем доступ к предмету
    $query = update_ax_page_group_by_group_id($page_id, $group_id);
    $result = pg_query($dbconnect, $query);
  }
}

function add_assignment_to_students($student_id, $task_id)
{
  global $dbconnect;
  $assignment_id = null;
  $query = select_task_assignment_student_id($student_id, $task_id);
  $result = pg_query($dbconnect, $query);
  $task_assignment = pg_fetch_assoc($result);
  if ($task_assignment) {
    //    echo "STUDENT-ASSIGNMENT_ID: ".$task_assignment['id'];
    //    echo "<br>";
    $assignment_id = $task_assignment['id'];
  } else {
    // Если к нему ещё не прикреплено задание - добавляем в бд 
    $query = insert_assignment($task_id);
    $result = pg_query($dbconnect, $query);
    $assignment_id = pg_fetch_assoc($result)['id'];
    //    echo "ДОБАВЛЕНИЕ НОВОГО ASSIGNMENT, ASSIGNMENT_ID: ".$assignment_id;
    //    echo "<br>";

    $query = insert_assignment_student($assignment_id, $student_id);
    $result = pg_query($dbconnect, $query);
  }
  return $assignment_id;
}

function save_test_files($dbconnect, $task_id)
{
  if (isset($_POST['task-type']) && $_POST['task-type'] == 1) {
    $query = select_task_file(2, $task_id);
    $result = pg_query($dbconnect, $query);
    $file = pg_fetch_all($result);
    if (empty($file))
      $query = insert_file(2, $task_id, "autotest.cpp", $_POST['full_text_test']);
    else
      $query = update_file(2, $task_id, $_POST['full_text_test']);

    $result = pg_query($dbconnect, $query);

    $query = select_task_file(3, $task_id);
    $result = pg_query($dbconnect, $query);
    $file = pg_fetch_all($result);
    if (empty($file))
      $query = insert_file(3, $task_id, "checktest.cpp", $_POST['full_text_test_of_test']);
    else
      $query = update_file(3, $task_id, $_POST['full_text_test_of_test']);

    $result = pg_query($dbconnect, $query);
  }
}
?>
