<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");


// Находим user_type (0 - студент, 1 - преподаватель)
if (isset($_POST['user_id'])) {
  $user_id = $_POST['user_id'];
  /*echo "USER_ID: ".$user_id;
  echo "<br>";*/
	$query = select_student_role($_POST['user_id']);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	$user_type = $_POST['sender_user_type'];
  /*echo "SENDER_USER_TYPE: ".$user_type;
  echo "<br>";*/
} else {
  exit;
}

$assignment_id = -1;
$assignment = null;
if (isset($_POST['assignment_id'])) {
  $assignment_id = $_POST['assignment_id'];
  $query = select_ax_assignment_by_id($assignment_id);
  $result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
  $assignment = pg_fetch_assoc($result);
  /*echo "ASSIGNMENT_ID: ".$assignment_id;
  echo "<br>";*/
} else  {
  exit;
}

$full_text = "";
if (isset($_POST['message_text'])){
  $full_text = rtrim($_POST['message_text']);
} else {
  //exit;
}

/*echo "FULL_TEXT: " . $full_text;
echo "<br>";*/

$files = array();
if (isset($_POST['type']) && $_POST['type'] == 1 && isset($_FILES['answer-files'])){
  /*echo "ПРИКРЕПЛЕНИЕ ОТВЕТА К ЗАДАНИЮ";
  echo "<br>";*/

  $files = $_FILES['answer-files'];

  $query = insert_answer_commit($assignment_id, $user_id);
	$result = pg_query($dbconnect, $query) ;
	$commit_id = pg_fetch_assoc($result)['id'];

  /*echo "COMMIT_ID: ".$commit_id;
  echo "<br>";*/

  $message_id = set_message(1, $full_text, $commit_id);

  $query = update_ax_assignment_status_code($assignment_id, 5);
  $result = pg_query($dbconnect, $query);

  $delay = -1;
  if ($assignment && $assignment['finish_limit']) {
    $date_db = convert_timestamp_to_date($assignment['finish_limit']);
    $date_now = get_now_date("d-message-Y");  
    $delay = ($date_db >= $date_now) ? 0 : 1;
  }
  $query = update_ax_assignment_delay($assignment_id, $delay);
  $result = pg_query($dbconnect, $query);


} else if ($full_text != "" || isset($_FILES['message-files'])){
  /*echo "ОТПРАВКА ОБЫЧНОГО СООБЩЕНИЯ: " . $full_text;
  echo "<br>";*/

  if (isset($_POST['type']) && $_POST['type'] == 2) {
    $query = select_last_answer_message($assignment_id, 1);
    $result = pg_query($dbconnect, $query);
    $reply_to_id = pg_fetch_assoc($result)['reply_to_id'];
    $message_id = set_message($_POST['type'], $full_text, null, $reply_to_id);
  } else if (isset($_POST['type']) && $_POST['type'] == 0) {
    $message_id = set_message(0, $full_text);
  }

  if (isset($_FILES['message-files']))
    $files = $_FILES['message-files'];
} else {
  //exit;
}

/*echo "MESSAGE_ID: ".$message_id;
echo "<br>";*/

if ($files) {
  /*echo "ФАЙЛЫ ЕСТЬ. ПРИКРЕПЛЕНИЕ ФАЙЛОВ К СООБЩЕНИЮ";
  echo "<br>";*/
  add_files_to_message($message_id, $files);
}

if (isset($_POST['mark']) && $message_id) {
  // Оценивание задания
  $query = update_ax_assignment_mark($assignment_id, $_POST['mark']);
  $result = pg_query($dbconnect, $query);

}


update_chat($assignment_id, $user_type, $user_id);





// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
// Возвращает id добавленного сообщения

function update_chat($assignment_id, $user_type, $user_id){
  // Содержимое этого div'а JS вставляет в окно чата на taskchat.php 
  echo '<div id="content">';
  $messages = get_messages($assignment_id, $user_type, $user_id);
  show_messages($messages);
  echo '</div>';
}

function set_message($type, $full_text, $commit_id = null, $reply_id = null) {
	global $dbconnect, $assignment_id, $user_id, $user_type;

	$full_text = preg_replace('#\'#', '\'\'', $full_text);
	$query = insert_message($assignment_id, $type, $user_type, $user_id, $full_text, $commit_id, $reply_id);

	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	return $row['id'];
}

// Возвращает двумерный массив сообщений для текущей страницы по ax_assignment
function get_messages($assignment_id, $user_type, $user_id) {
	global $dbconnect;
  /*echo "ASSIGNMENT_ID: ".$assignment_id;
  echo "<br>";*/
	$query = select_messages_by_assignment_id($assignment_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

  /*echo $query;
  echo "<br>";*/
	
	$messages = [];
	$is_first_new = false; // false, пока for не обрабатывал новых сообщений от собеседника
	for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
		// Отмечаем сообщения собеседника прочитанными
		// Если у любого препода/студента прогрузилась страница с непрочитанными сообщениями от любого студента/препода, то сообщения отмечаются прочитанными в БД. 
		
    $unreaded = null; //$unreaded = false; // наши сообщения, которые не прочитал собеседник
		$first_new = false; // true, если это первое новое сообщение от собеседника
		if ($user_type != null && $row['status'] == 0){
      if ($user_type == $row['sender_user_type']) {
        $unreaded = true;
      } else {
        $unreaded = false;
        /*echo "USER_TYPE: ".$user_type;
        echo "<br>";
        echo "MESSAGE_SENDER_USER_TYPE: ".$row['sender_user_type'];
        echo "<br>";*/
        if (!$is_first_new) {
          $first_new = true;
          $is_first_new = true;
        }

        $query = update_ax_message_status($row['message_id']);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

        $query = insert_ax_message_delivery($row['message_id'], $user_id);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      }
    }

		$username = $row['first_name'] . ' ' . $row['middle_name'];
		$message_time = explode(" ", $row['date_time']);
		$date = explode("-", $message_time[0]);
		$time = explode(":", $message_time[1]);
		$date_time = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
		$attachments = get_message_attachments($row['message_id']);
		$messages[] = ['id' => $row['id'], 'username' => $username, 'full_text' => $row['full_text'], 'date_time' => $date_time, 
            'sender_user_id' => $row['sender_user_id'], 'attachments' => $attachments, 'unreaded' => $unreaded, 'first_new' => $first_new];
	}
	return $messages;
}

function add_files_to_message($message_id, $files){
  global $dbconnect;
  // Файлы с этими расширениями надо хранить в БД
  $store_in_db = ['cpp']; // TODO для Вани: Добавить сюда еще типы файлов
  for ($i = 0; $i < count($files['name']); ++$i) {					
    $file_name = rand_prefix() . basename($files['name'][$i]);
    $file_ext = strtolower(preg_replace('#.{0,}[.]#', '', $file_name));
    $file_dir = 'upload_files/';
    $file_path = $file_dir . $file_name;

    // Перемещаем файл пользователя из временной директории сервера в директорию $file_dir
    if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
      // Если файлы такого расширения надо хранить на сервере, добавляем в БД путь к файлу на сервере
      if (!in_array($file_ext, $store_in_db)) {
        $query = insert_ax_message_attachment_with_url($message_id, $file_name, $file_path);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
      } else { // Если файлы такого расширения надо хранить в ДБ, добавляем в БД полный текст файла
        $file_name_without_prefix = delete_prefix($file_name);
        $file_full_text = file_get_contents($file_path);
        $file_full_text = preg_replace('#\'#', '\'\'', $file_full_text);
        $query = insert_ax_message_attachment_with_full_file_text($message_id, $file_name_without_prefix, $file_full_text);
        pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
        unlink($file_path);
      }
    }
    else {
      exit("Ошибка загрузки файла");
    }
  }
}

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
			$row['download_url'] = 'download_file.php?file_path=' . $row['download_url'] . '&with_prefix=';
		}
		$messages[] = ['id' => $row['id'], 'file_name' => delete_prefix($row['file_name']), 'download_url' => $row['download_url']];
	}
	return $messages;
}

// Выводит сообщения на страницу
function show_messages($messages) {
	global $user_id;
	foreach ($messages as $message) {
		// Прижимаем сообщения текущего пользователя к правой части экрана
		$float_class = $message['sender_user_id'] == $user_id ? 'float-right' : ''; 
		// Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
		// пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
		$background_color_class = $message['unreaded'] ? 'background-color-blue' : '';
		if ($message['first_new']) {
			echo '<div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>';
		}
		?>
		<div id="message-<?=$message['id']?>" class="chat-box-message <?=$float_class?>">
			<div class="chat-box-message-wrapper pretty-text <?=$background_color_class?>"
				><b><?=$message['username']?></b>
				<?php 
				if ($message['full_text'] != '') {
					echo stripslashes(htmlspecialchars($message['full_text'])) . "<br>";
				}
				foreach ($message['attachments'] as $ma) {?>
					<a href="<?=$ma['download_url']?>" class="task-desc-wrapper-a" target="_blank"><i class="fa-solid fa-file"></i><?=$ma['file_name']?></a>
				<?php }?>
			</div>
			<div class="chat-box-message-date">
				<?=$message['date_time']?>
			</div>
		</div>
		<div class="clear"></div>
	<?php }
}


// Генерация префикса для уникальности названий файлов, которые хранятся на сервере
function rand_prefix() {
  return time() . mt_rand(0, 9999) . mt_rand(0, 9999) . '_';
}

function delete_prefix($str) {
  return preg_replace('#[0-9]{0,}_#', '', $str, 1);
}

function checkTask($mark) {
  global $dbconnect, $assignment_id;

  $query = update_ax_assignment_mark($assignment_id, $mark);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

}
?>
