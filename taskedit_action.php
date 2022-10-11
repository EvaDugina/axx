<?php

require_once("common.php");
require_once("dbqueries.php");
require_once("utilities.php");

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

if (isset($_POST['action']) && $_POST['action'] == 'delete') {
  $query = delete_task($task_id);
  $result = pg_query($dbconnect, $query);
  echo "УДАЛЕНИЕ ФАЙЛА";
  //header('Location: preptasks.php?page='.$_GET['page']);
  //exit();
}

if(isset($_POST['task-type']) && $_POST['task-type'] == 1) {
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
  $flag_checked_group = false;
  $checked_group_id = -10;
  foreach($checked_elems as $id => $checked_elem) {
    $elem_id = (int) substr($checked_elem, 1, strlen($checked_elem)-1);
    if($checked_elem[0] == 's'){
      // Обработка выделенного checkbox-студента
      $student_id = $elem_id;
      echo "<br>STUDENT_ID: ".$student_id;
      echo "<br>";

      $query = select_group_id_by_student_id($student_id);
      $result = pg_query($dbconnect, $query);
      $group_id = pg_fetch_assoc($result)['group_id'];

      echo "STUDENT_GROUP_ID: ".$group_id;
      echo "<br>";

      // Проспускаем, если его группа уже добавлена целиком
      if ($flag_checked_group && $group_id == $checked_group_id){
        echo "CONTINUE";
        echo "<br>";
        continue;
      }

      array_push($assignments_id, add_assignment_to_students($student_id, $task_id));

      add_group_to_ax_page_group($_GET['page'], $group_id);

    } else if ($checked_elem[0] == 'g') {
        // Обработка выделенного checkbox-группы
        $group_id = $elem_id;
        echo "<br>GROUP_ID: ".$group_id;
        echo "<br>";

        add_group_to_ax_page_group($_GET['page'], $group_id);

        $query = select_students_id_by_group($group_id);
        $result = pg_query($dbconnect, $query);
        $students = pg_fetch_all($result);
        foreach($students as $student){
          array_push($assignments_id, add_assignment_to_students($student['student_id'], $task_id));
        }

        $flag_checked_group = true;
        $checked_group_id = $group_id;


    }
  }
}

if (isset($_POST['finish-limit']) && $_POST['finish-limit'] != ""){
  // Должны быть выбраны студенты!
  echo "<br>FINISH_LIMIT: ".$_POST['finish-limit'];
  echo "<br>";

  $timestamp = conver_calendar_to_timestamp($_POST['finish-limit']);
  echo "TIMESTAMP: ".$timestamp;
  echo "<br>";

  foreach ($assignments_id as $assignment_id) {
    $query = update_ax_assignment_finish_limit($assignment_id, $timestamp);
    $result = pg_query($dbconnect, $query);
  }
} else if (count($assignments_id) > 0){

}

if ($_FILES['task_files']['size'][0] > 0) {
  $files = $_FILES['task_files'];
  echo "<br>ADD_FILES: " . count($_FILES['task_files']['name']);
  echo "<br>";

  $store_in_db = []; 

  print_r($_FILES);
  echo "<br>";
  print_r($_FILES['task_files']);
  echo "<br>";

  for($i=0; $i < count($files['name']); $i++) {

    print_r($files['name'][$i]);

    $file_name = rand_prefix() . basename($files['name'][$i]);
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', $file_name));
    $file_dir = 'upload_files/';
    $file_path = $file_dir . $file_name;

    $file_tmp_name = $files['tmp_name'][$i];

    /*echo "Добавление файла в ax_solution_file: ".$file_name;
    echo "<br>";*/

    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    if (move_uploaded_file($file_tmp_name, $file_path)) {
      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        $query = insert_ax_task_file_with_url($task_id, 0, $file_name, $file_path);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      } else { // Если файлы такого расширения надо хранить в БД, добавляем в БД полный текст файла
        $file_name_without_prefix = delete_prefix($file_name);
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
        $query = insert_ax_task_file_with_full_file_text($assignment_id, $commit_id, $file_name_without_prefix, $file_full_text);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        unlink($file_path);
      }
      echo " - ПРИКРЕПЛЕНИЕ ФАЙЛОВ ПРОШЛО УСПЕШНО<br>";
    } else {
      exit("Ошибка загрузки файла");
    }
  }
}

header('Location: preptasks.php?page='.$_GET['page']);
?>



<?php // ФУНКЦИИ

// Прикрепление группы к странице предмета, если ещё не открыт доступ
function add_group_to_ax_page_group($page_id, $group_id){
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

function add_assignment_to_students($student_id, $task_id){
  global $dbconnect;
  $assignment_id = null;
  $query = select_task_assignment_student_id($student_id, $task_id);
  $result = pg_query($dbconnect, $query);
  $task_assignment = pg_fetch_assoc($result);
  if($task_assignment){
    echo "STUDENT-ASSIGNMENT_ID: ".$task_assignment['id'];
    echo "<br>";
    $assignment_id = $task_assignment['id'];
  } else {
    // Если к нему ещё не прикреплено задание - добавляем в бд 
    $query = insert_assignment($task_id);
    $result = pg_query($dbconnect, $query);
    $assignment_id = pg_fetch_assoc($result)['id'];
    echo "ДОБАВЛЕНИЕ НОВОГО ASSIGNMENT, ASSIGNMENT_ID: ".$assignment_id;
    echo "<br>";

    $query = insert_assignment_student($assignment_id, $student_id);
    $result = pg_query($dbconnect, $query);
  }
  return $assignment_id;
}


?>