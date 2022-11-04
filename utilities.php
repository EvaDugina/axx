<?php

// Работа с TIMESTAMP
date_default_timezone_set('Europe/Moscow');

function getNowTimestamp(){
  $timestamp = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
  return $timestamp;
}

function get_now_date($format = "d-m-Y"){
  return date($format);
}

// год и номер семестра по названию
function convert_timestamp_from_string($str){
  $pos = strpos($str, "/");
  $year = substr($str, 0, $pos);
  $sem = substr($str, $pos+1);
  $sem_number = 0;

  if($sem == 'Весна') 
    $sem_number = 2*((int)date('Y')-(int) $year + 1);
  else 
    $sem_number = 2*((int)date('Y')-(int) $year + 1)-1;
  echo $sem_number;

  return array('year' => $year, 'semester' => $sem_number);
}

function convert_timestamp_to_date($timestamp, $format = "d-m-Y") {
  return date($format, strtotime($timestamp));
}

function conver_calendar_to_timestamp($finish_limit) {
  $timestamp = strtotime($finish_limit);
  $timestamp = getdate($timestamp);
  $timestamp = date("Y-m-d H:i:s", mktime(23, 59, 59, $timestamp['mon'],$timestamp['mday'], $timestamp['year']));
  return $timestamp;
}


//семестр по цифре
function convert_sem_from_number($id){
  if($id == 1) return 'Весна';
  else return 'Осень';
}


// Выводит прикрепленные к странице с заданием файлы
function show_task_files($task_files) {?>
  <p style="line-height: 2.5em;">
  
	<?php $count_files = 0;
  foreach ($task_files as $f) {
    if ($f['type'] < 2) {
      $count_files++; ?>
      <a href="<?=$f['download_url']?>" target="_blank" class="btn btn-outline-primary">
        <i class="fa-solid fa-file"></i> 
        <?=$f['file_name']?>
      </a> 
	  <?php }
  }
	if ($count_files == 0) {
		echo 'Файлы временно не доступны<br>';
	}?>
  </p>
<?php }


// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function rand_prefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999) . '_';
}
function delete_prefix($str) {
  return preg_replace('#[0-9]{0,}_#', '', $str, 1);
}

function add_random_prefix_to_file_name($real_file_name) {
  //return rand_prefix() . basename($real_file_name);
  return $real_file_name;
}
function delete_random_prefix_from_file_name($db_file_name) {
  // Декодирование названия файла
  // $split_array = preg_split('/_/', $db_file_name);
  // $decodedFileName = "";
  // for ($i = 1; $i < count($split_array); $i++) {
  //   $decodedFileName .= $split_array[$i];
  // }
  // return $decodedFileName;
  return $db_file_name;
}




// Получение данных из БД


// Возвращает двумерный массив вложений для сообщения по message_id
function get_message_attachments($message_id) {
	global $dbconnect;
	$query = select_message_attachment($message_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	
	$messages = [];
	for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
		// Если текст файла лежит в БД
		if ($row['download_url'] == null) {
			$row['download_url'] = 'download_file.php?attachment_id=' . $row['id'];
		}
		// Если файл лежит на сервере
		else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
			if (strpos($row['download_url'], 'editor.php') === false)
				$row['download_url'] = 'download_file.php?file_path=' . $row['download_url'] . '&with_prefix=';
		}
		$messages[] = ['id' => $row['id'], 'file_name' => delete_prefix($row['file_name']), 'download_url' => $row['download_url']];
	}
	return $messages;
}


 function showAttachedFiles($message_id){
  $message_files = get_message_attachments($message_id);
  $message_text = "</br>";
  if (count($message_files) > 0) { 
    foreach ($message_files as $file) { 
      $message_text .= "
      <a target='_blank' download href='" . $file['download_url'] . "'>
        <i class='fa fa-paperclip' aria-hidden='true'></i> " . 
        $file['file_name']. "
      </a><br/>";
    }
  }
  return $message_text;
 }

// $task_files - массив прикрепленных к странице с заданием файлов из ax_task_file
function getTaskFiles($dbconnect, $task_id){
  $query = "SELECT id, type, file_name, download_url from ax_task_file where task_id = $task_id";
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $task_files = [];
  for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
    // Если текст файла лежит в БД
    if ($row['download_url'] == null) {
      $row['download_url'] = 'download_file.php?task_file_id=' . $row['id'];
    }
    // Если файл лежит на сервере
    else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
      $row['download_url'] = 'download_file.php?file_path=' . $row['download_url'];
    }
    $file_name = delete_random_prefix_from_file_name($row['file_name']);
    $task_files[] = ['type' => $row['type'], 'file_name' => $file_name, 'download_url' => $row['download_url']];
  }
  return $task_files;
}

function getSpecialFileTypes(){
  return array('cpp', 'c', 'h', 'txt');
}

function getPathForUploadFiles(){
  return 'upload_files/';
}

?>
