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

	// тут нужно монотонное возрастание id-шников файлов
	$result = pg_query($dbconnect, 'select max(id) from ax_solution_file where file_name = '.$file_name);
	$result = pg_fetch_all($result);
	if (count($result) > 0)
		$id = $result['id'];

    $result2 = pg_query($dbconnect, 'select assignment_id, full_text, id from ax_solution_file where id = '.$id);
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
    if (array_key_exists('likeid', $_REQUEST))
      $id = urldecode($_REQUEST['likeid']);
    else if (array_key_exists('id', $_REQUEST))
      $id = urldecode($_REQUEST['id']);
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
    if (array_key_exists('file', $_REQUEST)){
      $file = $_REQUEST['file'];

		// тут нужно монотонное возрастание id-шников файлов
	  $result = pg_query($dbconnect, 'select max(id) from ax_solution_file where file_name = '.$file_name);
      $result = pg_fetch_all($result);
	  if (count($result) > 0)
		  $id = $result['id'];

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
	
	$commit_id = 0;
	$result = pg_query($dbconnect, "select max(id) mid from ax_solution_commit where assignment_id = $assignment");
	$result = pg_fetch_all($result);
	foreach($result as $item) 
		$commit_id = $item['mid'];	
		
	if ($commit_id == 0 || is_null($commit_id)) {
		// создать новый коммит
		
		$result = pg_query($dbconnect, "select id from students where login='".$_SESSION['login']."'");
		$result = pg_fetch_all($result);
		$user_id = $result[0]['id'];	

			// сессий пока нет
		$result = pg_query($dbconnect, "insert into ax_solution_commit (assignment_id, session_id, student_user_id, type) values ($assignment, null, $user_id, 0) returning id;");
		$result = pg_fetch_all($result);
		$commit_id = $result[0]['id'];	
	}
	
    pg_query($dbconnect, "INSERT INTO ax_solution_file (assignment_id, commit_id, file_name, type) VALUES ('$assignment', $commit_id, '$file_name', '11')");

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
	
	// тут нужно монотонное возрастание id-шников файлов
	$result = pg_query($dbconnect, 'select max(id) from ax_solution_file where file_name = '.$file_name);
	$result = pg_fetch_all($result);
	if (count($result) > 0)
		$id = $result['id'];
	
    pg_query($dbconnect, "DELETE FROM ax_solution_file WHERE id='$id'");

  }else if ($type == "oncheck"){
    
    //---------------------------------------------------------------ONCHECK-------------------------------------------------------

    if (array_key_exists('assignment', $_REQUEST))
      $assignment = $_REQUEST['assignment'];
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

	$commit_id = 0;
	$result = pg_query($dbconnect, "select max(id) mid from ax_solution_commit where assignment_id = $assignment");
	$result = pg_fetch_all($result);
	foreach($result as $item) 
		$commit_id = $item['mid'];	

	// if ($commit == 0 || is_null($commit_id)) {
		$filecount = 0;
		$result = pg_query($dbconnect, "select count(*) cnt from ax_solution_file where commit_id = $commit_id");
		$result = pg_fetch_all($result);
		$filecount = $result[0]['cnt'];	
		$new_id = 0;
		
		if ($filecount > 0) {
			$result=pg_query($dbconnect, "select id from students where login='".$_SESSION['login']."'");
			$result = pg_fetch_all($result);
			$user_id = $result[0]['id'];	

				// сессий пока нет
			$result = pg_query($dbconnect, "insert into ax_solution_commit (assignment_id, session_id, student_user_id, type) select assignment_id, session_id, $user_id, 0 from ax_solution_commit where id = $commit_id RETURNING id");
			$result = pg_fetch_all($result);
			$new_id = $result[0]['id'];	
			
			$result = pg_query($dbconnect, "insert into ax_solution_file (assignment_id, commit_id, type, file_name, download_url, full_text) select assignment_id, $new_id, type, file_name, download_url, full_text from ax_solution_file where commit_id = $commit_id");
		
			$result = pg_query($dbconnect, "update ax_solution_commit set type = 1 where id = $commit_id");
		}		
	// }

      pg_query($dbconnect, "UPDATE ax_assignment SET status_code='3' where id='$assignment'");
	  
	  $msgtext = "Отправлено на проверку"; //"<a href=\"editor.php?assignment=$assignment&commit=$commit_id\">Задание на проверку</a>";
  	  $result2 = pg_query($dbconnect, "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)".
						 "     values ($assignment, 1, 0, $user_id, now(), null, '$msgtext', $commit_id, 0) returning id");
      $result = pg_fetch_assoc($result2);
	  $responce = $result['id'];

  }
?>
<?=$responce?>