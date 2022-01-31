<?php
  require_once("common.php");
  require_once("dbqueries.php");
  $file_name = 0;
  $assignment = 0;
  $responce = 0;


  if (array_key_exists('type', $_REQUEST))
    $type= urldecode($_REQUEST['type']);
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }
  if ($type == "open"){

    //-----------------------------------------------------------------OPEN-------------------------------------------------------

    if (array_key_exists('id', $_REQUEST))
      $id = urldecode($_REQUEST['id']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

    $result2 = pg_query($dbconnect, 'select assignment_id, full_text, id from ax_solution_file');
    $result = pg_fetch_all($result2);

    foreach($result as $item) {
     if($item['id'] == $id) {
      header('Content-Type: text/plain');
      $responce= $item['full_text'];
     } 
    }

  } else if ($type == "save"){

    //-----------------------------------------------------------------SAVE-------------------------------------------------------

    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    if (array_key_exists('id', $_REQUEST))
      $id = urldecode($_REQUEST['id']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    if (array_key_exists('file', $_REQUEST)){
      $file = urldecode($_REQUEST['file']);

      pg_query($dbconnect, "UPDATE ax_solution_file SET full_text='$file', file_name='$file_name' where id='$id'");
    }
    else {  
      pg_query($dbconnect, "UPDATE ax_solution_file SET file_name='$file_name' where id='$id'");
    }
    $responce = $_REQUEST['file'];

  }else if ($type == "new"){

    //-----------------------------------------------------------------NEW--------------------------------------------------------

  if (array_key_exists('assignment', $_REQUEST))
    $assignment = $_REQUEST['assignment'];
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }
    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    } 
    pg_query($dbconnect, "INSERT INTO ax_solution_file (assignment_id, file_name, type) VALUES ('$assignment', '$file_name', '11')");

    $result2 = pg_query($dbconnect, 'select assignment_id, file_name, id from ax_solution_file');
    $result = pg_fetch_all($result2);

    foreach($result as $item) {
     if($item['assignment_id'] == $assignment and $item['file_name'] == $file_name) {
      $responce= $item['id'];
     } 
    }

  }else if ($type == "del"){
    
    //-----------------------------------------------------------------DEL---------------------------------------------------------

    if (array_key_exists('id', $_REQUEST))
      $id = urldecode($_REQUEST['id']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    pg_query($dbconnect, "DELETE FROM ax_solution_file WHERE id='$id'");

  }else if ($type == "oncheck"){
    
    //-----------------------------------------------------------------DEL---------------------------------------------------------

    if (array_key_exists('assignment', $_REQUEST))
      $assignment = $_REQUEST['assignment'];
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

      pg_query($dbconnect, "UPDATE ax_assignment SET status_code='3' where id='$assignment'");
  }
?>
<?=$responce?>