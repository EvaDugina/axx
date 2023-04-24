<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("POClasses/Assignment.class.php");
require_once("POClasses/Task.class.php");
require_once("POClasses/File.class.php");


// Проверка на корректный запрос
if ((!isset($_POST['page_id'])) && !isset($_POST['task_id'])) {
  header('Location: index.php');
  exit;
} 

if (isset($_POST['page_id'])) {
  $page_id = $_POST['page_id'];
  $Page = new Page((int)$_POST['page_id']);
} 

if (isset($_POST['task_id']) && $_POST['task_id'] != -1) {
  $Task = new Task((int)$_POST['task_id']);
}


// Архивирование и разархивирование задания
if (isset($_POST['action']) && ($_POST['action'] == 'archive' || $_POST['action'] == 'rearchive') && $_POST['task_id'] != -1) {
  if ($_POST['action'] == 'archive') {
    //echo "АРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 0;
  } else {
    //echo "РАЗАРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 1;
  }
  $Task->setStatus($new_status);
  header('Location:'.$_SERVER['HTTP_REFERER']);
  exit();
}


if(isset($_POST['flag-deleteFile']) && isset($_POST['task_id'])) {
  $file_id = $_POST['file_id'];
  $Task->deleteFile($file_id);
  // $query = pg_query($dbconnect, delete_ax_task_file($task_file_id));
  header('Location: taskedit.php?task='.$_POST['task_id']);
  exit();
}

if(isset($_POST['flag-statusFile']) && $_POST['task_id']) {
  $file_type = $_POST['task-file-status'];

  $file_id = $_POST['file_id'];
  // $Task = new Task((int)$_POST['task_id']);
  $File = $Task->getFileById((int)$file_id);
  $File->setType($file_type);

  header('Location: taskedit.php?task='.$_POST['task_id']);
  exit();
}

// Удаление задания
if (isset($_POST['action']) && $_POST['action'] == 'delete' && $_POST['task_id'] != -1) {
  //echo "УДАЛЕНИЕ ЗАДАНИЯ";
  $query = pg_query($dbconnect, select_page_by_task_id($_POST['task_id']));
  $page_id = pg_fetch_assoc($query)['page_id'];

  // TODO: Придумать, как удалять auto_test_result!
  $Task->deleteFromDB();
  
  header('Location: preptasks.php?page=' . $page_id);
  exit();
}

if (isset($_POST['task_id']) && isset($_POST['action']) && $_POST['action'] == "save") {
  if (isset($_POST['title'])) {
    $Task->title = $_POST['title'];
  }

  if (isset($_POST['type'])) {
    $Task->type = $_POST['type'];
  }

  if (isset($_POST['description'])) {
    $Task->description = $_POST['description'];
  }
  $Task->pushChangesToDB();

  if (isset($_POST['codeTest'])) {
    $Files = $Task->getFilesByType(2);
    if(empty($Files)) {
      $File = new File(2, "test.cpp", null, $_POST['codeTest']);
      $Task->addFile($File->id);
    } else {
      foreach($Files as $File) {
        $File->full_text = $_POST['codeTest'];
        $File->pushChangesToDB();
      }
    }
  }
	
  if(isset($_POST['codeCheck'])) {
    $Files = $Task->getFilesByType(3);
    if(empty($Files)) {
      $File = new File(3, "checktest.cpp", null, $_POST['codeCheck']);
      $Task->addFile($File->id);
    } else {
      foreach($Files as $File) {
        $File->full_text = $_POST['codeCheck'];
        $File->pushChangesToDB();
      }
    }
  }

  echo showFiles($Task->getFiles(), true, $Task->id);

  exit();
}

// Сохранение изменений
if (isset($_POST['action']) && $_POST['action'] == "save") {
  $Task->type = $_POST['task-type'];
  $Task->title = $_POST['task-title'];
  $Task->description = $_POST['task-description'];
  $Task->pushChangesToDB();
  header('Location:'.$_SERVER['HTTP_REFERER']);
  exit();
} 

// if ($_POST['task_id'] != -1) {
//   //echo "РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕГО ЗАДАНИЯ";
//   if (isset($_POST['flag-editTaskInfo'])) {
//     $query = update_ax_task($_POST['task_id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
//     $result = pg_query($dbconnect, $query);
//     header('Location:'.$_SERVER['HTTP_REFERER']);
//     exit();
//   }
//   $task_id = $_POST['task_id'];
// } else {
//   //echo "СОЗДАНИЕ ЗАДАНИЯ";
//   if (isset($_POST['flag-editTaskInfo'])) {
//     $query = insert_ax_task($page_id, $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
//   } else {
//     $query = insert_ax_task($page_id, 1, "", "");
//   }
//   $result = pg_query($dbconnect, $query);
//   $task_id = pg_fetch_assoc($result)['id'];
//   header('Location: taskedit.php?task='.$task_id);
//   exit();
// }



// ОТМЕНА НАЗНАЧЕНИЯ СТУДЕНТА
/*
if (isset($_POST['flag-rejecAssignment']) && isset($_POST['task_id'], $_POST['student_id'])) {
  $query = pg_query($dbconnect, select_task_assignment_student_id($_POST['student_id'], $_POST['task_id']));
  $assignment_id = pg_fetch_assoc($query)['id'];
  $query = pg_query($dbconnect, delete_assignment($assignment_id));
  header('Location:'.$_SERVER['HTTP_REFERER']);
  exit();
}
*/
if (isset($_POST['action']) && isset($_POST['assignment_id']) && ($_POST['action'] == 'reject') && ($_POST['assignment_id'] != 0)) {
  $Asssignment = new Assignment((int)$_POST['assignment_id']);
  // $query = pg_query($dbconnect, delete_assignment($_POST['assignment_id']));
  $Asssignment->deleteFromDB();
  header('Location:'.$_SERVER['HTTP_REFERER']);
  exit();
}

if ($_POST['task_id'] != -1) {
  //echo "РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕГО ЗАДАНИЯ";
  $task_id = $_POST['task_id'];
  if (isset($_POST['flag-editTaskInfo'])) {
    $query = update_ax_task($_POST['task_id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
    $result = pg_query($dbconnect, $query);
    save_test_files($dbconnect, $task_id);
    header('Location:'.$_SERVER['HTTP_REFERER']);
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
  header('Location: taskedit.php?task='.$task_id);
  exit();
}

// Прикрепление файлов к заданию
if (isset($_FILES['add-files']) && isset($_POST['flag-addFiles'])) {
  $files = convertWebFilesToFiles('add-files');

  for ($i = 0; $i < count($files); $i++) {
    addFileToObject($Task, $files[$i]['name'], $files[$i]['tmp_name'], 0);
  }

  header('Location: taskedit.php?task='.$Task->id);
  exit;
}

// $assignments_id = array();
// // Изменение списка студентов, прикреплённых к заданию
// if (isset($_POST['task-status-deligate']) && isset($_POST['checkboxStudents']) && !empty($_POST['checkboxStudents'])) {
//   // Всё нормально, просто прикрепляем новых студентов
//   $checked_elems = $_POST['checkboxStudents'];
//   $flag_checked_group = false;
//   $checked_group_id = -10;
//   foreach ($checked_elems as $id => $checked_elem) {
//     $elem_id = (int) substr($checked_elem, 1, strlen($checked_elem) - 1);
//     if ($checked_elem[0] == 's') {
//       // Обработка выделенного checkbox-студента
//       $student_id = $elem_id;
//       //      echo "<br>STUDENT_ID: ".$student_id;
//       //      echo "<br>";

//       $query = select_group_id_by_student_id($student_id);
//       $result = pg_query($dbconnect, $query);
//       $group_id = pg_fetch_assoc($result)['group_id'];

//       //      echo "STUDENT_GROUP_ID: ".$group_id;
//       //      echo "<br>";

//       // Проспускаем, если его группа уже добавлена целиком
//       if ($flag_checked_group && $group_id == $checked_group_id) {
//         echo "CONTINUE";
//         echo "<br>";
//         continue;
//       }

//       array_push($assignments_id, add_assignment_to_students($student_id, $Task->id));

//       add_group_to_ax_page_group($page_id, $group_id);
//     } else if ($checked_elem[0] == 'g') {
//       // Обработка выделенного checkbox-группы
//       $group_id = $elem_id;
//       //        echo "<br>GROUP_ID: ".$group_id;
//       //        echo "<br>";

//       add_group_to_ax_page_group($page_id, $group_id);

//       $query = select_students_id_by_group($group_id);
//       $result = pg_query($dbconnect, $query);
//       $students = pg_fetch_all($result);
//       foreach ($students as $student) {
//         array_push($assignments_id, add_assignment_to_students($student['student_id'], $Task->id));
//       }

//       $flag_checked_group = true;
//       $checked_group_id = $group_id;
//     }
//   }
// }


// Изменение finish_limit всех Assignments прикреплённых к Task
if (isset($_POST['action']) && $_POST['action'] == "editFinishLimit") {
  if(isset($_POST['task_id'])) {
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
if(isset($_POST['action']) && $_POST['action'] == "editStatus") {
  if(isset($_POST['task_id'])) {
    $Task = new Task((int)$_POST['task_id']);
  } else {
    header('Location: index.php');
    exit;
  }

  foreach ($Task->getAssignments() as $Asssignment) {
    $Asssignment->setVisibility($_POST['status']);
  }

  // Не простое эхо, комментировать нельзя!
  echo getSVGByAssignmentVisibility((int)$_POST['status']);

  exit();
}

header('Location: preptasks.php?page=' . $page_id);
?>



<?php // ФУНКЦИИ

function convertWebFilesToFiles($name_files) {
  $files = array();
  for ($i = 0; $i < count($_FILES[$name_files]['tmp_name']); $i++) {
    if (!is_uploaded_file($_FILES[$name_files]['tmp_name'][$i])) {
      continue;
    } else {
      array_push($files, [
        'name' => $_FILES[$name_files]['name'][$i], 'tmp_name' => $_FILES[$name_files]['tmp_name'][$i]
      ]);
    }
  }
  return $files;
}


function addFileToTask($Task, $type, $file_name, $file_tmp_name) {

  // $store_in_db = getSpecialFileTypes();
  
  // $File = new File($type, $file_name);
  
  // $file_ext = $File->getFileExt();
  // $file_dir = getPathForUploadFiles();
  // $file_path = $file_dir . $File->name;

  // // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
  // if (move_uploaded_file($file_tmp_name, $file_path)) {

  //   // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
  //   if (!in_array($file_ext, $store_in_db)) {
      
  //     $File->setDownloadUrl($file_path);
  //     $Task->addFile($File->id);

  //   } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла
      
  //     $file_full_text = getFileContentByPath($file_path);
  //     $File->setFullText($file_full_text);
  //     $Task->addFile($File->id);
  //     unlink($file_path);

  //   }

  // } else {
  //   exit("Ошибка загрузки файла");
  // }
}





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
      $query = insert_file(2, $task_id, "accel_autotest.cpp", $_POST['full_text_test']);
    else
      $query = update_file(2, $task_id, $_POST['full_text_test']);
  
    $result = pg_query($dbconnect, $query);
  
    $query = select_task_file(3, $task_id);
    $result = pg_query($dbconnect, $query);
    $file = pg_fetch_all($result);
    if (empty($file))
      $query = insert_file(3, $task_id, "accel_checktest.cpp", $_POST['full_text_test_of_test']);
    else
      $query = update_file(3, $task_id, $_POST['full_text_test_of_test']);
  
    $result = pg_query($dbconnect, $query);
  }
}
?>
