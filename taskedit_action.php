<?php

require_once("common.php");
require_once("dbqueries.php");

if(isset($_POST['task-id'])){

  if($_POST['task-id'] != -1){
    $query = select_task_assignment_student_id($_SESSION['hash'], $_POST['task-id']);
    $result = pg_query($dbconnect, $query);
    $assignment_id = pg_fetch_all($result)['id'];
  }

  if(isset($_POST['task-type'])){
    $query = select_task_file(2, $_POST['task-id']);
    $result = pg_query($dbconnect, $query);
    $file = pg_fetch_all($result);
    if(empty($file))
      $query = insert_file(2, $_POST['task-id'], "test.cpp", $_POST['full_text_test']);
    else
      $query = update_file(2, $_POST['task-id'], $_POST['full_text_test']);
    
    $result = pg_query($dbconnect, $query);
    
    $query = select_task_file(3, $_POST['task-id']);
    $result = pg_query($dbconnect, $query);
    $file = pg_fetch_all($result);		
    if(empty($file))
      $query = insert_file(3, $_POST['task-id'], "checktest.cpp", $_POST['full_text_test_of_test']);
    else
      $query = update_file(3, $_POST['task-id'], $_POST['full_text_test_of_test']);
    
    $result = pg_query($dbconnect, $query);
  }

  if ($_POST['task-id'] != -1) { 
    print_r("ОБНОВЛЕНИЕ ЗАДАНИЯ");
    $query = update_ax_task($_POST['task-id'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
    $result = pg_query($dbconnect, $query);
  } else {
    print_r("СОЗДАНИЕ ЗАДАНИЯ");
    $query = insert_ax_task($_GET['page'], $_POST['task-type'], $_POST['task-title'], $_POST['task-description']);
    $result = pg_query($dbconnect, $query);
  }

  if (isset($_POST['finish-limit']) && $_POST['finish-limit'] && $_POST['task-id'] != -1){
    $query = update_ax_assignment_finish_limit($assignment_id, $_POST['finish-limit']);
    $result = pg_query($dbconnect, $query);
  } 

  //print_r($_POST['checkboxStudents']);
  //print_r("AAAAAAAAAAAAAAAAA");

  if (isset($_POST['checkboxStudents']) && !empty($_POST['checkboxStudents']) && isset($_POST['task-id']) && $_POST['task-id'] != -1) {
    $array_students_for_deligation = array();
    foreach($_POST['checkboxStudents'] as $id => $student_elem) {
        print_r($_POST['checkboxStudents']);
        if($student_elem[$id]=='g'){
          $query = select_students_id_by_group($student_elem[$id]);
          $result = pg_query($dbconnect, $query);
          $array_students = pg_fetch_all($result);
        } else {
          
        }
    }
  }
}


header('Location: preptasks.php?page='.$_GET['page']);
?>