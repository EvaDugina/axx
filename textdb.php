<?php
  require_once("common.php");
  require_once("dbqueries.php");
  $file_name = 0;
  $assignment = 0;
  $responce = 0;
  if (array_key_exists('assignment', $_REQUEST))
    $assignment = $_REQUEST['assignment'];
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }


  if (array_key_exists('type', $_REQUEST))
    $type= urldecode($_REQUEST['type']);
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }
  if ($type == "open"){

    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

    $result2 = pg_query($dbconnect, 'select assignment_id, full_text, file_name from ax_solution_file');
    $result = pg_fetch_all($result2);

    foreach($result as $item) {
     if($item['assignment_id'] == $assignment and $item['file_name'] == $file_name) {
      $responce= $item['full_text'];
     } 
    }


  } else if ($type == "save"){
    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    if (array_key_exists('file', $_REQUEST))
      $file = urldecode($_REQUEST['file']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    pg_query($dbconnect, "UPDATE ax_solution_file SET full_text='$file' where file_name='$file_name' and assignment_id=$assignment");
    $responce = $_REQUEST['file'];

  }else if ($type == "new"){
    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    } 
    pg_query($dbconnect, "INSERT INTO ax_solution_file (assignment_id, file_name, type) VALUES ('$assignment', '$file_name', '11')");

  }else if ($type == "del"){
    if (array_key_exists('file_name', $_REQUEST))
      $file_name = urldecode($_REQUEST['file_name']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    } 
    pg_query($dbconnect, "DELETE FROM ax_solution_file WHERE file_name='$file_name' and assignment_id=$assignment");

  }
?>
<?=$responce?>