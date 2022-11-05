<?php
  require_once("common.php");
  require_once("dbqueries.php");
  $file_name = 0;
  $assignment = 0;
  $responce = 0;
  $commit_id = 0;
  $file_id = 0;

  if (array_key_exists('type', $_REQUEST))
    $type= urldecode($_REQUEST['type']);
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }

  if (array_key_exists('assignment', $_REQUEST))
    $assignment = $_REQUEST['assignment'];
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }

  if (array_key_exists('commit', $_REQUEST))
    $commit_id= urldecode($_REQUEST['commit']);
  else {
	$result = pg_query($dbconnect, "select max(id) mid from ax_solution_commit where assignment_id = $assignment");
	$result = pg_fetch_assoc($result);
	if ($result === false)
	  $commit_id = 0;
	else
	  $commit_id = $result['mid'];		  
  }

  if (array_key_exists('id', $_REQUEST))
    $file_id = urldecode($_REQUEST['id']);

  if (array_key_exists('file_name', $_REQUEST))
    $file_name = urldecode($_REQUEST['file_name']);
  else if ($file_id != 0) {
	$result = pg_query($dbconnect, 'select file_name from ax_solution_file where id = '.$file_id);
	$result = pg_fetch_assoc($result);
	$commit_id = $result['mid'];		  
  }
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  } 

  //-----------------------------------------------------------------OPEN-------------------------------------------------------
  if ($type == "open") {

	$responce = "Коммит $commit_id файла $file_name не найден";
	
	// выбираем файл по названию и номеру коммита
	$result = pg_query($dbconnect, "select id, full_text from ax_solution_file where file_name = $file_name and commit_id = $commit_id");
	$result = pg_fetch_all($result);
    foreach($result as $item) {
      if($item['id'] == $file_id) {
        header('Content-Type: text/plain');
        $responce= $item['full_text'];
      } 
    }
  } 

  //-----------------------------------------------------------------SAVE-------------------------------------------------------
  else if ($type == "save") {
	  
    $id = 0;
    $result = pg_query($dbconnect, "select id from ax_solution_file where file_name = '$file_name' and commit_id = $commit_id");
    $result = pg_fetch_assoc($result);
	if (count($result) > 0)
	  $id = $result['id'];
    else if (array_key_exists('likeid', $_REQUEST))
      $id = urldecode($_REQUEST['likeid']);
    else if (array_key_exists('id', $_REQUEST))
      $id = $file_id;
    else {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
	
    if (array_key_exists('file', $_REQUEST)){
      $file = $_REQUEST['file'];
      pg_query($dbconnect, 'UPDATE ax_solution_file SET full_text=$accelquotes$'.$file.'$accelquotes$, file_name=$accelquotes$'.$file_name.'$accelquotes$ where id='.$id);
    }
    else {  
      pg_query($dbconnect, 'UPDATE ax_solution_file SET file_name=$accelquotes$'.$file_name.'$accelquotes$ where id='.$id);
    }
	
    $responce = $_REQUEST['file'];
  }

  //-----------------------------------------------------------------NEW--------------------------------------------------------
  else if ($type == "new") {

	if ($commit_id == 0) {
		// создать первый коммит, если его нет
		$result = pg_query($dbconnect, "select id from students where login='".$_SESSION['login']."'");
		$result = pg_fetch_all($result);
		$user_id = $result[0]['id'];	

		// --- сессий пока нет
		$result = pg_query($dbconnect, "insert into ax_solution_commit (assignment_id, session_id, student_user_id, type) values ($assignment, null, $user_id, 0) returning id;");
		$result = pg_fetch_all($result);
		$commit_id = $result[0]['id'];	
	}
	
    $result = pg_query($dbconnect, "INSERT INTO ax_solution_file (assignment_id, commit_id, file_name, type) VALUES ('$assignment', $commit_id, '$file_name', '11') returning id;");
    $result = pg_fetch_assoc($result);
	if ($result === false) {
		echo "Не удалось сохранить файл $file_name в коммит $commit_id";
		http_response_code(400);
		exit;
	}
	else
		$responce = $result['id'];
  }
    
  //-----------------------------------------------------------------DEL---------------------------------------------------------
  else if ($type == "del") {
	
	if ($commit_id == 0)
	{
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }
	
	// тут нужно монотонное возрастание id-шников файлов
	$result = pg_query($dbconnect, "select id from ax_solution_file where file_name = '$file_name' and commit_id = $commit_id");
	$result = pg_fetch_assoc($result);
	if ($result === false) {
      echo "Не удалось найти удаляемый файл";
      http_response_code(400);
      exit;
    }
	else 
	  pg_query($dbconnect, "DELETE FROM ax_solution_file WHERE id=".$result['id']);    
  }
    
  //---------------------------------------------------------------ONCHECK-------------------------------------------------------
  else if ($type == "oncheck") {
	  
	if ($commit_id == 0)
	{
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

	$filecount = 0;
	$result = pg_query($dbconnect, "select count(*) cnt from ax_solution_file where commit_id = $commit_id");
	$result = pg_fetch_all($result);
	$filecount = $result[0]['cnt'];	
	$new_id = 0;
		
	if ($filecount > 0) {
	  $result=pg_query($dbconnect, "select id, role from students where login='".$_SESSION['login']."'");
	  $result = pg_fetch_all($result);
	  $user_id = $result[0]['id'];
	  $user_role = $result[0]['role'];

	  // --- сессий пока нет
	  $result = pg_query($dbconnect, "insert into ax_solution_commit (assignment_id, session_id, student_user_id, type) select assignment_id, session_id, $user_id, ".
										(($user_role == 3) ? "1" : "0")." from ax_solution_commit where id = $commit_id RETURNING id");
	  $result = pg_fetch_all($result);
	  $new_id = $result[0]['id'];	
			
	  $result = pg_query($dbconnect, "insert into ax_solution_file (assignment_id, commit_id, type, file_name, download_url, full_text) select assignment_id, $new_id, type, file_name, download_url, full_text from ax_solution_file where commit_id = $commit_id");
		
	  // $result = pg_query($dbconnect, "update ax_solution_commit set type = 1 where id = $commit_id");

      pg_query($dbconnect, "UPDATE ax_assignment SET status_code=".(($user_role == 3) ? "5" : "2")." where id=$assignment");
	  
	  if ($user_role == 3) {
  	    $result2 = pg_query($dbconnect, "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)".
										"     values ($assignment, 1, 0, $user_id, now(), null, 'Отправлено на проверку', $new_id, 0) returning id");
        $result = pg_fetch_assoc($result2);
	    $msg_id = $result['id'];
	  
	    pg_query($dbconnect, "insert into ax_message_attachment (message_id, file_name, download_url, full_text)".
							 "     values ($msg_id, 'проверить', 'editor.php?assignment=$assignment&commit=$new_id', null)");
		pg_query($dbconnect, "update ax_assignment set status_code = 5, status_text = 'ожидает проверки' where id = $assignment");
	  }
	  else {
  	    $result2 = pg_query($dbconnect, "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)".
										"     values ($assignment, 1, 0, $user_id, now(), null, 'Проверено', $new_id, 0) returning id");
        $result = pg_fetch_assoc($result2);
	    $msg_id = $result['id'];
	  
	    pg_query($dbconnect, "insert into ax_message_attachment (message_id, file_name, download_url, full_text)".
							 "     values ($msg_id, 'проверенная версия', 'editor.php?assignment=$assignment&commit=$new_id', null)");
		pg_query($dbconnect, "update ax_assignment set status_code = 2, status_text = 'проверено' where id = $assignment");
	  }		  
	}		
  }
  
  //-----------------------------------------------------------------------------------------------------------------------------
?>
<?=$responce?>