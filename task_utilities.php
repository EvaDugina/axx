<?php

require_once("common.php");
require_once("dbqueries.php");


$Task = new Task((int)$_POST['task_id']);

if($_POST['type'] == 1){

  $Files = $Task->getFilesByType(2);
  
  if(empty($Files)) {
    $File = new File(2, "test.cpp", null, $_POST['full_text_test']);
    $Task->addFile($File->id);
  } else {
    foreach($Files as $File) {
      $File->full_text = $_POST['full_text_test'];
      $File->pushChangesToDB();
    }
  }

  // $query = select_task_file(2, $_POST['task_id']);
  // $result = pg_query($dbconnect, $query);
  // $file = pg_fetch_all($result);

  // if(empty($files))
  // 	$query = insert_file(2, $_POST['task_id'], "test.cpp", $_POST['full_text_test']);
  // else
  // 	$query = update_file(2, $_POST['task_id'], $_POST['full_text_test']);
  // $result = pg_query($dbconnect, $query);
	
  $Files = $Task->getFilesByType(3);
  if(empty($Files)) {
    $File = new File(3, "checktest.cpp", null, $_POST['full_text_test_of_test']);
    $Task->addFile($File->id);
  } else {
    foreach($Files as $File) {
      $File->full_text = $_POST['full_text_test_of_test'];
      $File->pushChangesToDB();
    }
  }

}

$Task->type = $_POST['type'];
$Task->title = $_POST['title'];
$Task->description = $_POST['description'];
$Task->pushChangesToDB();

// $query = update_task($_POST['task_id'], $_POST['type'], $_POST['title'], $_POST['description']);
// $result = pg_query($dbconnect, $query);
// $file = pg_fetch_all($result);

header('Location: index.php');
?>