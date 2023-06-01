<?php
  header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - (60 * 60)));

  require_once("common.php");
  require_once("dbqueries.php");
  require_once("POClasses/File.class.php");
  require_once("POClasses/Commit.class.php");
  require_once("POClasses/Message.class.php");

  $file_name = 0;
  $assignment = 0;
  $responce = 0;
  $commit_id = 0;
  $file_id = 0;


	$au = new auth_ssh();

  function get_prev_assignments($assignment)
  {
	global $dbconnect;	
	$aarray = array();

	$query = select_prev_students($assignment);
	$result = pg_query($dbconnect, $query);

	if ($result && pg_num_rows($result) > 0) {
	  $i=0;
	  $prev_assign = 0;
	  $studlist = "";
	  
	  while ($ass = pg_fetch_assoc($result)) {
		if ($ass['aid'] == $prev_assign) {
		  $studlist = $studlist.' '.$ass['fio'];
		} else {
		  if ($prev_assign != 0) 
			$aarray[$prev_assign] = $studlist;

  		  $prev_assign = $ass['aid'];
		  $studlist = $ass['fio'];
		}
	  }
	  if ($prev_assign != 0)
	    $aarray[$prev_assign] = $studlist;
	}

	return $aarray;
  }

  function get_prev_files($assignment)
  {
	global $dbconnect;

	$farray = array();
	$aarray = get_prev_assignments($assignment);

	$query = select_prev_files($assignment);
	$result = pg_query($dbconnect, $query);

	while ($file = pg_fetch_assoc($result)) {
	  $filename = $file['assignment_id'].' '.$aarray[$file['assignment_id']].' '.$file['file_name'];
	  $fulltext = $file['full_text'];
	  array_push($farray, array("name" => $filename, "text" => $fulltext));
	}

	return $farray;
  }

  if (array_key_exists('type', $_REQUEST))
    $type= urldecode($_REQUEST['type']);
  else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }

  if (array_key_exists('assignment', $_REQUEST)) {
    $assignment = $_REQUEST['assignment'];
	$Assignment = new Assignment((int)$assignment);
  } else {
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  }

  if (array_key_exists('commit', $_REQUEST))
    $commit_id= urldecode($_REQUEST['commit']);
  else {
	if($au->isStudent())
		$commit_id = $Assignment->getLastCommitForStudent()->id;
	else 
		$commit_id = $Assignment->getLastCommitForTeacher()->id;
  }
	// $result = pg_query($dbconnect, "select max(id) mid from ax_solution_commit where assignment_id = $assignment");
	// $result = pg_fetch_assoc($result);
	// if ($result === false)
	//   $commit_id = 0;
	// else
	//   $commit_id = $result['mid'];		  

  if (array_key_exists('id', $_REQUEST))
    $file_id = urldecode($_REQUEST['id']);

  if (array_key_exists('file_name', $_REQUEST))
    $file_name = urldecode($_REQUEST['file_name']);
  else if ($file_id != 0) {
	$result = pg_query($dbconnect, "SELECT file_name from ax_file where id = $file_id");
	$result = pg_fetch_assoc($result);
	$file_name = $result['file_name'];		  
  }
  else if ($type != 'oncheck' && $type != 'tools' && $type != 'console' && $type != 'commit'){
    echo "Некорректное обращение";
    http_response_code(400);
    exit;
  } 

  //-----------------------------------------------------------------OPEN-------------------------------------------------------
  if ($type == "open") {

	$responce = "Коммит $commit_id файла $file_name не найден";
	
	// выбираем файл по названию и номеру коммита
  // TODO: Проверить!
	$result = pg_query($dbconnect, "SELECT ax_file.id, ax_file.file_name, full_text from ax_file INNER JOIN ax_commit_file ON ax_commit_file.file_id = ax_file.id where ax_file.file_name = '$file_name' and ax_commit_file.commit_id = $commit_id");
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
    // TODO: Проверить!
    $result = pg_query($dbconnect, "SELECT ax_file.id from ax_file INNER JOIN ax_commit_file ON ax_commit_file.file_id = ax_file.id where file_name = '$file_name' and ax_commit_file.commit_id = $commit_id");
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
      // TODO: Проверить!
      pg_query($dbconnect, 'UPDATE ax_file SET full_text=$accelquotes$'.$file.'$accelquotes$, file_name=$accelquotes$'.$file_name.'$accelquotes$ where id='.$id);
    }
    else {  
      // TODO: Проверить!
      pg_query($dbconnect, 'UPDATE ax_file SET file_name=$accelquotes$'.$file_name.'$accelquotes$ where id='.$id);
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

    // TODO: Проверить!
    $File = new File(11, $file_name, null, null);    
	$File->setName(true, $file_name);
	$Commit = new Commit((int)$commit_id);
    $Commit->addFile($File->id);

	$return_values = array(
		"file_id" => $File->id,
		"download_url" => $File->getDownloadLink()
	  );

    $responce = json_encode($return_values);
  //   $result = pg_query($dbconnect, "INSERT INTO ax_solution_file (assignment_id, commit_id, file_name, type) VALUES ('$assignment', $commit_id, '$file_name', '11') returning id;");
  //   $result = pg_fetch_assoc($result);
	// if ($result === false) {
	// 	echo "Не удалось сохранить файл $file_name в коммит $commit_id";
	// 	http_response_code(400);
	// 	exit;
	// }
	// else
	// 	$responce = $result['id'];
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
  // TODO: Проверить!
  $result = pg_query($dbconnect, "SELECT ax_file.id from ax_file INNER JOIN ax_commit_file ON ax_commit_file.file_id = ax_file.id where file_name = '$file_name' and ax_commit_file.commit_id = $commit_id");
	$result = pg_fetch_assoc($result);
	if ($result === false) {
      echo "Не удалось найти удаляемый файл";
      http_response_code(400);
      exit;
    }
	else
    // TODO: ПРОВЕРИТЬ!
	  pg_query($dbconnect, "DELETE FROM ax_file WHERE id=".$result['id']);    
	  pg_query($dbconnect, "DELETE FROM ax_commit_file WHERE file_id=".$result['id']);    
  }
  
  //---------------------------------------------------------------COMMIT-------------------------------------------------------
  else if ($type == "commit") {
	$Assignment = new Assignment((int)$_REQUEST['assignment']);

	if($_REQUEST['commit']) {
		$lastCommit = new Commit((int)$_REQUEST['commit']);
	} else {
		if($au->isStudent())
			$lastCommit = $Assignment->getLastCommitForStudent();
		else 
			$lastCommit = $Assignment->getLastCommitForTeacher();
	}
	
	if(array_key_exists('commit_type', $_REQUEST)) {
		if($_REQUEST['commit_type']=="intermediate") {
			if($lastCommit) {
				$Commit = new Commit($Assignment->id, null, $au->getUserId(), 0, null);
				$Commit->copy($lastCommit->id);
				$Commit->setStudentUserId($au->getUserId());
				$Assignment->addCommit($Commit->id);
			} else {
				$Commit = new Commit($Assignment->id, null, $au->getUserId(), 0, null);
			}
			if($au->isStudent())
				$type = 0;
			else 
				$type = 2;
			$Commit->setType($type);
			header("Location:editor.php?assignment=" . $Assignment->id . "&commit=" . $Commit->id);
		} else {
			if($au->isStudent())
				$lastCommit->setType(1);
			else 
				$lastCommit->setType(3);
			header("Location:editor.php?assignment=" . $Assignment->id . "&commit=" . $lastCommit->id);
		}
	} else {
		echo "Отсутствует commit_type";
		http_response_code(400);
		exit;
	}

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
  // TODO: ПРОВЕРИТЬ!
	$result = pg_query($dbconnect, "SELECT count(*) cnt from ax_commit_file where commit_id = $commit_id");
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
			
    // TODO: Проверить!
    $pg_query = pg_query($dbconnect, "SELECT ax_file.* from ax_file INNER JOIN ax_commit_file ON ax_commit_file.file_id = ax_file.id where commit_id = $commit_id");
    while ($file = pg_fetch_assoc($pg_query)) {
      $File = new File((int)$file['type'], $file['file_name'], $file['download_url'], $file['full_text']);
      $Commit = new Commit((int)$new_id);
      $Commit->addFile($File->id);
    }

	  // $result = pg_query($dbconnect, "insert into ax_solution_file (assignment_id, commit_id, type, file_name, download_url, full_text) select assignment_id, $new_id, type, file_name, download_url, full_text from ax_solution_file where commit_id = $commit_id");
		
	  // $result = pg_query($dbconnect, "update ax_solution_commit set type = 1 where id = $commit_id");

    if ($user_role == 3)
      pg_query($dbconnect, "UPDATE ax_assignment SET status=1, status_code=2 where id=$assignment");
    else 
      pg_query($dbconnect, "UPDATE ax_assignment SET status=4, status_code=2 where id=$assignment");
	  
	  if ($user_role == 3) {
  	    $result2 = pg_query($dbconnect, "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)".
										"     values ($assignment, 1, $user_role, $user_id, now(), null, 'Отправлено на проверку', $new_id, 0) returning id");
        $result = pg_fetch_assoc($result2);
	    $msg_id = $result['id'];
	  
      // TODO: Проверить!
      $Message = new Message((int)$msg_id);
      $File = new File(null, 'проверить', "editor.php?assignment=$assignment&commit=$new_id", null);
	  $Message->addFile($File->id);
	    // pg_query($dbconnect, "insert into ax_message_attachment (message_id, file_name, download_url, full_text)".
			// 				 "     values ($msg_id, 'проверить', 'editor.php?assignment=$assignment&commit=$new_id', null)");
		pg_query($dbconnect, "update ax_assignment set status = 1, status_code = 2, status = 1 where id = $assignment");
	  }
	  else {
  	    $result2 = pg_query($dbconnect, "insert into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)".
										"     values ($assignment, 1, $user_role, $user_id, now(), null, 'Проверено', $new_id, 0) returning id");
        $result = pg_fetch_assoc($result2);
	    $msg_id = $result['id'];
	  
      // TODO: Проверить!
      $Message = new Message((int)$msg_id);
      $File = new File(null, 'проверенная версия', "editor.php?assignment=$assignment&commit=$new_id", null);
	  $Message->addFile($File->id);
	    // pg_query($dbconnect, "insert into ax_message_attachment (message_id, file_name, download_url, full_text)".
			// 				 "     values ($msg_id, 'проверенная версия', 'editor.php?assignment=$assignment&commit=$new_id', null)");
		pg_query($dbconnect, "update ax_assignment set status_code = 2, status = 4, where id = $assignment");
	  }		  
	}		
  }

  //---------------------------------------------------------------TOOLS-------------------------------------------------------
  else if ($type == "tools") {
	  
  	if ($commit_id == 0) {
      echo "Некорректное обращение";
      http_response_code(400);
      exit;
    }

	$result = pg_query($dbconnect,  "select ax_assignment.id aid, ax_task.id tid, ax_assignment.checks achecks, ax_task.checks tchecks ".
									" from ax_assignment inner join ax_task on ax_assignment.task_id = ax_task.id where ax_assignment.id = ".$assignment);
	$row = pg_fetch_assoc($result);
	$checks = $row['achecks'];
	if ($checks == null)
	  $checks = $row['tchecks'];
	if ($checks == null)
	  $checks = '{"tools": {"build":{"enabled":true,"show_to_student":false,"language":"C++","check":{"autoreject":true}},"valgrind": {"enabled": true,"show_to_student": false,"bin": "valgrind","arguments": "","compiler": "gcc","checks": [{"check": "errors","enabled": true,"limit": 3,"autoreject": true,"result": 6,"outcome": "pass"},{"check": "leaks","enabled": true,"limit": 0,"autoreject": true,"result": 10,"outcome": "reject"}],"output": ""},"cppcheck": {"enabled": true,"show_to_student": false,"bin": "cppcheck","arguments": "","checks": [{"check": "error","enabled": true,"limit": 1,"autoreject": false,"result": 1,"outcome": "fail"},{"check": "warning","enabled": true,"limit": 3,"autoreject": false,"result": 0,"outcome": "pass"},{"check": "style","enabled": true,"limit": 3,"autoreject": false,"result": 1,"outcome": "pass"},{"check": "performance","enabled": true,"limit": 2,"autoreject": false,"result": 0,"outcome": "pass"},{"check": "portability","enabled": true,"limit": 0,"autoreject": false,"result": 0,"outcome": "pass"},{"check": "information","enabled": true,"limit": 1,"autoreject": false,"result": 1,"outcome": "fail"},{"check": "unusedFunction","enabled": true,"limit": 0,"autoreject": false,"result": 0,"outcome": "pass"},{"check": "missingInclude","enabled": true,"limit": 0,"autoreject": false,"result": 0,"outcome": "pass"}],"output": ""},"clang-format": {"enabled": true,"show_to_student": false,"bin": "clang-format","arguments": "","check": {"name": "strict","file": ".clang-format","limit": 5,"autoreject": true,"result": 3,"outcome": "reject"},"output": ""},"copydetect": {"enabled": true,"show_to_student": false,"bin": "copydetect","arguments": "","check": {"type": "with_all","limit": 50,"autoreject": true,"reference_directory": "copydetect_input"}},  "autotests": {"enabled": true,"show_to_student": false,"test_path": "accel_autotest.cpp","check": {"limit": 0,"autoreject": true}}}}';
	
	$checks = json_decode($checks, true);
				
    if (array_key_exists('cppcheck', $_REQUEST)) {
      $checks['tools']['cppcheck']['enabled'] = str2bool(@$_REQUEST['cppcheck']);
	  $checks['tools']['cppcheck']['bin'] = "cppcheck";
	}
    if (array_key_exists('clang', $_REQUEST)) {
      $checks['tools']['clang-format']['enabled'] = str2bool(@$_REQUEST['clang']);
	  $checks['tools']['clang-format']['bin'] = "clang-format";
	  $checks['tools']['clang-format']['check']['file'] = ".clang-format";
	}
    if (array_key_exists('valgrind', $_REQUEST)) {
      $checks['tools']['valgrind']['enabled'] = str2bool(@$_REQUEST['valgrind']);
	  $checks['tools']['valgrind']['bin'] = "valgrind";
	}
    if (array_key_exists('copy', $_REQUEST)) {
      $checks['tools']['copydetect']['enabled'] = str2bool(@$_REQUEST['copy']);
	  $checks['tools']['copydetect']['bin'] = "copydetect";
	  $checks['tools']['copydetect']['check']['reference_directory'] = "copydetect_input";
	}
    if (array_key_exists('build', $_REQUEST)) {
	  //if (!array_key_exists('build', $checks['tools'])) 
		; //$checks['tools']['build'] = array();  
      //else
      
      $checks['tools']['build']['enabled'] = str2bool(@$_REQUEST['build']);
	}
    if (array_key_exists('test', $_REQUEST)) {
	  //if (!array_key_exists('autotests', $checks['tools'])) 
	  //	; //$checks['tools']['autotests'] = array();  
	  //else
      
	  $checks['tools']['autotests']['enabled'] = str2bool(@$_REQUEST['test']);
	  $checks['tools']['autotests']['test_path'] = "accel_autotest.cpp";
	}
	
	/*echo $checks; exit;*/

	$sid = session_id();
	$folder = "/var/app/share/".(($sid == false) ? "unknown" : $sid);
	if (!file_exists($folder)) 
      mkdir($folder, 0777, true);

	// получение файла проверки
	$result = pg_query($dbconnect,  "SELECT f.* from ax_file f INNER JOIN ax_task_file ON ax_task_file.file_id = f.id inner join ax_assignment a on ax_task_file.task_id = a.task_id where f.type = 2 and a.id = ".$assignment);
	$result = pg_fetch_assoc($result);
	if (!$result && array_key_exists('autotests', $checks['tools']))
	  $checks['tools']['autotests']['enabled'] = false;
	else if (array_key_exists('autotests', $checks['tools'])) {
	  $checks["tools"]["autotests"]["test_path"] = $result["file_name"];
	  @unlink($folder.'/'.$result['file_name']);
	  $myfile = fopen($folder.'/'.$result['file_name'], "w") or die("Unable to open file!");
	  fwrite($myfile, $result['full_text']);
	  fclose($myfile);
	}
	$checks = json_encode($checks);

	$myfile = fopen($folder.'/config.json', "w") or die("Unable to open file!");
	fwrite($myfile, $checks);
	fclose($myfile);

	// $result = pg_query($dbconnect,  "SELECT * from ax_solution_file where commit_id = ".$commit_id);
	$result = pg_query($dbconnect, "SELECT * FROM ax_file INNER JOIN ax_commit_file ON ax_commit_file.file_id = ax_file.id WHERE ax_commit_file.commit_id = $commit_id");
	$files = array();
	while ($row = pg_fetch_assoc($result)) {
	  $myfile = fopen($folder.'/'.$row['file_name'], "w") or die("Unable to open file!");
	  fwrite($myfile, $row['full_text']);
	  fclose($myfile);
	  if (strtoupper($row['file_name']) != 'MAKEFILE')
	    array_push($files, $row['file_name']);
	}

	if (count($files) < 1) {
	  echo "Не найдено файлов для коммита ".$commit_id;
      http_response_code(400);
      exit;
	}
	
	@unlink($folder.'/copydetect_input');
	mkdir($folder.'/copydetect_input', 0777, true);
	$prev_files = get_prev_files($assignment);
	foreach($prev_files as $pf) {
      $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п', 'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я', 'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П', 'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];
      $lat = ['a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p', 'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya', 'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P', 'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
        ];
      $transname = str_replace($cyr, $lat, $pf["name"]);	  
	  
	  $myfile = fopen($folder.'/copydetect_input/'.$transname, "w");
	  if (!$myfile) {
		echo "Ошибка создания файлов для проверки";
		http_response_code(500);
		exit;
	  }
	  fwrite($myfile, $pf["text"]);
	  fclose($myfile);
	}

	@unlink($folder.'/output.json');
	
	$output=null;
	$retval=null;
	//$responce = 'docker run -it --net=host --rm -v '.$folder.':/tmp nitori_sandbox codecheck -c config.json -i'.$commit_id.' '.implode(' ', $files);
	//exec('docker run -it --net=host --rm -v '.$folder.':/tmp -w=/tmp nitori_sandbox codecheck -c config.json -i '.$commit_id.' '.implode(' ', $files), $output, $retval);
	exec('docker run --net=host --rm -v '.$folder.':/tmp -v /var/app/utility:/stable -w=/tmp nitori_sandbox codecheck -c config.json '.implode(' ', $files).' 2>&1', $output, $retval);
	//echo 'docker run -it --net=host --rm -v '.$folder.':/tmp -w=/tmp nitori_sandbox codecheck -c config.json '.implode(' ', $files); exit;
	
/* Получение результатов проверки из БД
	$result = pg_query($dbconnect,  "select autotest_results from ax_solution_commit where id = ".$commit_id);
	if (!($row = pg_fetch_assoc($result))) {
	  echo "<pre>Ошибка при получении результатов проверок (".$retval."):\n";
	  echo $output;
	  echo "</pre>";
      http_response_code(400);
      exit;
	}
	$responce = $row['autotest_results'];
*/
/* Получение результатов проверки из файла */
	$myfile = fopen($folder.'/output.json', "r");
	if (!$myfile) {
      echo "Не удалось получить результаты проверки:<br>";
	  var_dump($output);
      http_response_code(500);
      exit;
	}	
	$responce = fread($myfile, filesize($folder.'/output.json'));
	fclose($myfile);
	
	pg_query($dbconnect, 'update ax_solution_commit set autotest_results = $accelquotes$'.$responce.'$accelquotes$ where id = '.$commit_id);
/**/

	header('Content-Type: application/json');	
  } 

  //---------------------------------------------------------------CONSOLE-------------------------------------------------------
  else if ($type == "console") {
	$sid = session_id();
	if (!array_key_exists('tool', $_REQUEST)) {
  	  http_response_code(400);
	  exit;
	}
	$tool =  $_REQUEST['tool'];

	$folder = "/var/app/share/".(($sid == false) ? "unknown" : $sid);
	if (!file_exists($folder)) {
	  echo "Перезапустите проверку!";
	  http_response_code(200);
	  exit;
	}	
	$ext = "txt";
	if ($tool =='cppcheck' || $tool =='format') 
	  $ext = "xml";

	$filename = $folder.'/output_'.$tool.'.'.$ext;
	$myfile = fopen($filename, "r");
	if (!$myfile) {
	  echo "Перезапустите проверку!";
	  http_response_code(200);
	  exit;
	}	

	$responce = htmlspecialchars(fread($myfile, filesize($filename)));
	fclose($myfile);

	header('Content-Type: text/plain');	
  }
  
  //-----------------------------------------------------------------------------------------------------------------------------
?>
<?=$responce?>