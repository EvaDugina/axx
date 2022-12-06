<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");


// Проверка на корректный запрос
if ((!isset($_POST['page_id'])) && !isset($_POST['task_id'])) {
  header('Location: index.php');
  exit;
} else if (isset($_POST['page_id'])) {
  $page_id = $_POST['page_id'];
}


// Архивирование и разархивирование задания
if (isset($_POST['action']) && ($_POST['action'] == 'archive' || $_POST['action'] == 're-archive') && $_POST['task_id'] != -1) {
  if ($_POST['action'] == 'archive') {
    //echo "АРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 0;
  } else {
    //echo "РАЗАРХИВИРОВАНИЕ ЗАДАНИЯ";
    $new_status = 1;
  }
  $query = pg_query($dbconnect, update_ax_task_status($_POST['task_id'], $new_status));
  header('Location:'.$_SERVER['HTTP_REFERER']);
  exit();
}


if(isset($_POST['flag-deleteFile'])) {
  $task_file_id = $_POST['task_file_id'];
  $query = pg_query($dbconnect, delete_ax_task_file($task_file_id));
  header('Location: taskedit.php?task='.$_POST['task_id']);
  exit();
}

if(isset($_POST['flag-statusFile'])) {
  $task_file_id = $_POST['task_file_id'];
  $new_statusFile = $_POST['task-file-status'];
  $sdjl = update_ax_task_file_status($task_file_id, $new_statusFile);
  $query = pg_query($dbconnect, update_ax_task_file_status($task_file_id, $new_statusFile));
  header('Location: taskedit.php?task='.$_POST['task_id']);
  exit();
}

// Удаление задания
if (isset($_POST['action']) && $_POST['action'] == 'delete' && $_POST['task_id'] != -1) {
  //echo "УДАЛЕНИЕ ЗАДАНИЯ";
  $query = pg_query($dbconnect, select_page_by_task_id($_POST['task_id']));
  $page_id = pg_fetch_assoc($query)['page_id'];
  $query = delete_task($_POST['task_id']);
  $result = pg_query($dbconnect, $query);
  header('Location: preptasks.php?page=' . $page_id);
  exit();
}



if ($_POST['task_id'] != -1) {
  //echo "РЕДАКТИРОВАНИЕ СУЩЕСТВУЮЩЕГО ЗАДАНИЯ";
  if (isset($_POST['flag-editTaskInfo'])) {
    $query = update_ax_task($_POST['task_id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
    $result = pg_query($dbconnect, $query);
    header('Location:'.$_SERVER['HTTP_REFERER']);
    exit();
  }
  $task_id = $_POST['task_id'];
} else {
  //echo "СОЗДАНИЕ ЗАДАНИЯ";
  if (isset($_POST['flag-editTaskInfo'])) {
    $query = insert_ax_task($page_id, $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
  } else {
    $query = insert_ax_task($page_id, 1, "", "");
  }
  $result = pg_query($dbconnect, $query);
  $task_id = pg_fetch_assoc($result)['id'];
  header('Location: taskedit.php?task='.$task_id);
  exit();
}


// if (isset($_POST['task-type']) && $_POST['task-type'] == 1) {
//   $query = select_task_file(2, $task_id);
//   $result = pg_query($dbconnect, $query);
//   $file = pg_fetch_all($result);
//   if (empty($file))
//     $query = insert_file(2, $task_id, "test.cpp", $_POST['full_text_test']);
//   else
//     $query = update_file(2, $task_id, $_POST['full_text_test']);

//   $result = pg_query($dbconnect, $query);

//   $query = select_task_file(3, $task_id);
//   $result = pg_query($dbconnect, $query);
//   $file = pg_fetch_all($result);
//   if (empty($file))
//     $query = insert_file(3, $task_id, "checktest.cpp", $_POST['full_text_test_of_test']);
//   else
//     $query = update_file(3, $task_id, $_POST['full_text_test_of_test']);

//   $result = pg_query($dbconnect, $query);
// }



// Прикрепление файлов к заданию
if (isset($_FILES['add-files']) && isset($_POST['flag-addFiles'])) {
  $files = get_files('add-files');
  $store_in_db = getSpecialFileTypes();

  for ($i = 0; $i < count($files); $i++) {
    print_r($files[$i]['name']);

    $file_name = add_random_prefix_to_file_name($files[$i]['name']);
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', $file_name));
    $file_dir = getPathForUploadFiles();
    $file_path = $file_dir . $file_name;

    $file_tmp_name = $files[$i]['tmp_name'];

    /*echo "Добавление файла в ax_solution_file: ".$file_name;
    echo "<br>";*/

    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    if (move_uploaded_file($file_tmp_name, $file_path)) {
      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        $query = insert_ax_task_file_with_url($task_id, 0, $file_name, $file_path);
        $id_new_file = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла
        $file_name_without_prefix = delete_random_prefix_from_file_name($file_name);
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
        $query = insert_ax_task_file_with_full_file_text($task_id, 0, $file_name, $file_full_text);
        $id_new_file = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        unlink($file_path);
      }
      //      echo " - ПРИКРЕПЛЕНИЕ ФАЙЛОВ ПРОШЛО УСПЕШНО<br>";
    } else {
      exit("Ошибка загрузки файла");
    }
  }

  // http_response_code(400);
  // echo json_encode(array('id_new_element' => "AAAAAAAAAAAA"));
  //return array('id_new_element' => "$id_new_file");
  header('Location: taskedit.php?task='.$task_id);
  exit;
}

$assignments_id = array();
// Изменение списка студентов, прикреплённых к заданию
if (isset($_POST['task-status-deligate']) && isset($_POST['checkboxStudents']) && !empty($_POST['checkboxStudents'])) {
  // Всё нормально, просто прикрепляем новых студентов
  $checked_elems = $_POST['checkboxStudents'];
  $flag_checked_group = false;
  $checked_group_id = -10;
  foreach ($checked_elems as $id => $checked_elem) {
    $elem_id = (int) substr($checked_elem, 1, strlen($checked_elem) - 1);
    if ($checked_elem[0] == 's') {
      // Обработка выделенного checkbox-студента
      $student_id = $elem_id;
      //      echo "<br>STUDENT_ID: ".$student_id;
      //      echo "<br>";

      $query = select_group_id_by_student_id($student_id);
      $result = pg_query($dbconnect, $query);
      $group_id = pg_fetch_assoc($result)['group_id'];

      //      echo "STUDENT_GROUP_ID: ".$group_id;
      //      echo "<br>";

      // Проспускаем, если его группа уже добавлена целиком
      if ($flag_checked_group && $group_id == $checked_group_id) {
        echo "CONTINUE";
        echo "<br>";
        continue;
      }

      array_push($assignments_id, add_assignment_to_students($student_id, $task_id));

      add_group_to_ax_page_group($page_id, $group_id);
    } else if ($checked_elem[0] == 'g') {
      // Обработка выделенного checkbox-группы
      $group_id = $elem_id;
      //        echo "<br>GROUP_ID: ".$group_id;
      //        echo "<br>";

      add_group_to_ax_page_group($page_id, $group_id);

      $query = select_students_id_by_group($group_id);
      $result = pg_query($dbconnect, $query);
      $students = pg_fetch_all($result);
      foreach ($students as $student) {
        array_push($assignments_id, add_assignment_to_students($student['student_id'], $task_id));
      }

      $flag_checked_group = true;
      $checked_group_id = $group_id;
    }
  }
}

if (isset($_POST['finish-limit']) && $_POST['finish-limit'] != "") {
  // Должны быть выбраны студенты!
  //  echo "<br>FINISH_LIMIT: ".$_POST['finish-limit'];
  //  echo "<br>";

  $timestamp = conver_calendar_to_timestamp($_POST['finish-limit']);
  //  echo "TIMESTAMP: ".$timestamp;
  //  echo "<br>";

  foreach ($assignments_id as $assignment_id) {
    $query = update_ax_assignment_finish_limit($assignment_id, $timestamp);
    $result = pg_query($dbconnect, $query);
  }
} else if (count($assignments_id) > 0) {
}

header('Location: preptasks.php?page=' . $page_id);
?>



<?php // ФУНКЦИИ

function get_files($name_files)
{
  $files = array();
  for ($i = 0; $i < count($_FILES[$name_files]['tmp_name']); $i++) {
    if (!is_uploaded_file($_FILES[$name_files]['tmp_name'][$i])) {
      continue;
    } else {
      array_push($files, [
        'name' => $_FILES[$name_files]['name'][$i], 'tmp_name' => $_FILES[$name_files]['tmp_name'][$i],
        'size' => $_FILES[$name_files]['size'][$i]
      ]);
    }
  }
  return $files;
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


?>