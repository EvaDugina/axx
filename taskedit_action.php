<?php

require_once("common.php");
require_once("dbqueries.php");

if (!isset($_GET['page'])) {
  // Совсем некорректный ввод
  header('Location: index.php');
  exit;
}

if (!isset($_POST['task-id'])) {
  // Просто некорректный ввод
  header('Location: preptasks.php?page='.$_GET['page']);
  exit;
}

if ($_POST['task-id'] != -1) { 
  echo "ОБНОВЛЕНИЕ ЗАДАНИЯ";
  echo "<br>";
  $query = update_ax_task($_POST['task-id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
  $result = pg_query($dbconnect, $query);
  $task_id = $_POST['task-id'];
} else {
  echo "СОЗДАНИЕ ЗАДАНИЯ";
  echo "<br>";
  $query = insert_ax_task($_GET['page'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
  $result = pg_query($dbconnect, $query);
  $task_id = pg_fetch_assoc($result)['id'];
}
echo "TASK_ID: ".$task_id;
echo "<br>";

if(isset($_POST['task-type']) ?? $_POST['task-type'] == 1) {
  $query = select_task_file(2, $task_id);
  $result = pg_query($dbconnect, $query);
  $file = pg_fetch_all($result);
  if(empty($file))
    $query = insert_file(2, $task_id, "test.cpp", $_POST['full_text_test']);
  else
    $query = update_file(2, $task_id, $_POST['full_text_test']);
  
  $result = pg_query($dbconnect, $query);
  
  $query = select_task_file(3, $task_id);
  $result = pg_query($dbconnect, $query);
  $file = pg_fetch_all($result);		
  if(empty($file))
    $query = insert_file(3, $task_id, "checktest.cpp", $_POST['full_text_test_of_test']);
  else
    $query = update_file(3, $task_id, $_POST['full_text_test_of_test']);
  
  $result = pg_query($dbconnect, $query);
}

$assignments_id = array();
// Изменение списка студентов, прикреплённых к заданию
if (isset($_POST['task-status-deligate']) && isset($_POST['checkboxStudents']) && !empty($_POST['checkboxStudents'])) {
  // Всё нормально, просто прикрепляем новых студентов
  $checked_elems = $_POST['checkboxStudents'];
  foreach($checked_elems as $id => $checked_elem) {
    if($checked_elem[0] == 's'){
      // Простой студент
      $student_id = (int) substr($checked_elem, 1, strlen($checked_elem)-1);
      echo "STUDENT_ID: ".$student_id;
      echo "<br>";
      $query = select_task_assignment_student_id($student_id, $task_id);
      $result = pg_query($dbconnect, $query);
      $task_assignment = pg_fetch_assoc($result);
      if($task_assignment){
        echo "STUDENT-ASSIGNMENT_ID: ".$task_assignment['id'];
        echo "<br>";
        array_push($assignments_id, $task_assignment['id']);
      } else {
        // Если к нему ещё не прикреплено задание - добавляем в бд 
        $query = insert_assignment($task_id);
        $result = pg_query($dbconnect, $query);
        $assignment_id = pg_fetch_assoc($result)['id'];
        echo "ДОБАВЛЕНИЕ НОВОГО ASSIGNMENT, ASSIGNMENT_ID: ".$assignment_id;
        echo "<br>";
        array_push($assignments_id, $assignment_id);

        $query = insert_assignment_student($assignment_id, $student_id);
        $result = pg_query($dbconnect, $query);
        
      }
    } else if ($checked_elem[0] == 'g') {
        // Группа, которую нужно пропустить
        $group_id = (int) substr($checked_elem, 1, strlen($checked_elem)-1);
        echo "GROUP_ID: ".$student_id;
        echo "<br>";
        $query = select_ax_page_group($student_id, $task_id);
        $result = pg_query($dbconnect, $query);
        $group = pg_fetch_assoc($result);
        if ($group) {
          // Группе уже ткрыт доступ к предмету, ещё раз предоставлять не надо
        } else {
          
        }


    }
  }
}

if (isset($_POST['finish-limit']) && $_POST['finish-limit'] != ""){
  // Должны быть выбраны студенты!
  echo "FINISH_LIMIT: ".$_POST['finish-limit'];
  echo "<br>";
  foreach ($assignments_id as $assignment_id) {
    $query = update_ax_assignment_finish_limit($assignment_id, $_POST['finish-limit']);
    $result = pg_query($dbconnect, $query);
  }
} 

/*if (isset($_POST['user_files'])) {
  echo "ADD_FILES: ".$_POST['user_files'];
  echo "<br>";
  foreach ($_POST['user_files'] as $file) {*/

    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    /*if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $file_path)) {
      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        $query = "INSERT into ax_message_attachment (message_id, file_name, download_url) values ($message_id, '$file_name', '$file_path')";
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      }

      // Если файлы такого расширения надо хранить в ДБ, добавляем в БД полный текст файла
      else {
        $file_name_without_prefix = delete_prefix($file_name);
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
        $query = "INSERT into ax_message_attachment (message_id, file_name, full_text) values ($message_id, '$file_name_without_prefix', '$file_full_text')";
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        unlink($file_path);
      }
    }
    else {
      exit("Ошибка загрузки файла");
    }*/
    
    //$query = insert_file_by_link(0/*Добавить галочку, если файл - шаблон проекта и тд.*/, $task_id, $file['name']);
    /*$result = pg_query($dbconnect, $query);
  }
}*/

header('Location: preptasks.php?page='.$_GET['page']);
?>