<?php
require_once("settings.php");
require_once("dbqueries.php");
require_once("utilities.php");
require_once("messageHandler.php");

if (!isset($_POST['assignment_id']) || !isset($_POST['user_id'])) {
  echo "ERROR EXIIIT!";
  exit;
}

// Находим sender_user_type (0 - студент, 1 - преподаватель)
$user_id = $_POST['user_id'];
$assignment_id = $_POST['assignment_id'];

$messageHandler = new messageHandler($assignment_id, $user_id);
$sender_user_type = $messageHandler->sender_user_type;

echo "USER_ID: ".$user_id;
echo "<br>";
echo "ASSIGNMENT_ID: ".$assignment_id;
echo "<br>";
echo "SENDER_USER_TYPE: ".$sender_user_type;
echo "<br>";
echo "SESSION_ROLE = ".$_SESSION['role'];
echo "<br>";
echo "SESSION_ID = ".$_SESSION['hash'];

$assignment = null;
$query = select_ax_assignment_by_id($assignment_id);
$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
$finish_limit = pg_fetch_assoc($result)['finish_limit'];


if (!isset($_POST['message_text'])){
//  echo "JUST UPDATE <br>";
//  echo "ISSET: " . $_POST['message_text'] . "<br>";
  update_chat($assignment_id, $sender_user_type, $user_id);
  exit;
}


$full_text = "";
$full_text = rtrim($_POST['message_text']);


/*echo "FULL_TEXT: " . $full_text;
echo "<br>";*/

if ($_POST['type'] == 1){
  /*echo "ПРИКРЕПЛЕНИЕ ОТВЕТА К ЗАДАНИЮ";
  echo "<br>";*/

  $query = insert_answer_commit($assignment_id, $user_id);
	$result = pg_query($dbconnect, $query) ;
	$commit_id = pg_fetch_assoc($result)['id'];

  /*echo "COMMIT_ID: ".$commit_id;
  echo "<br>";*/

  $message_id = $messageHandler->set_message(1, $full_text, $commit_id);

  $query = update_ax_assignment_status_code($assignment_id, 5);
  $result = pg_query($dbconnect, $query);

  $delay = -1;
  if ($finish_limit) {
    $date_db = convert_timestamp_to_date($finish_limit);
    $date_now = get_now_date("d-m-Y");  
    $delay = ($date_db >= $date_now) ? 0 : 1;
  }
  $query = update_ax_assignment_delay($assignment_id, $delay);
  $result = pg_query($dbconnect, $query);


} else if ($full_text != "" || isset($_FILES['message_files'])){
  /*echo "ОТПРАВКА ОБЫЧНОГО СООБЩЕНИЯ: " . $full_text;
  echo "<br>";*/

  if ($_POST['type'] == 2) {
    /*echo "ОЦЕНИВАНИЕ ЗАДАНИЯ";
    echo "<br>";*/
    $query = select_last_answer_message($assignment_id, 1);
    $result = pg_query($dbconnect, $query);
    $reply_to_id = pg_fetch_assoc($result)['reply_to_id'];
    $message_id = $messageHandler->set_message($_POST['type'], $full_text, null, $reply_to_id);
  } else if (isset($_POST['type']) && $_POST['type'] == 0) {
    $message_id = $messageHandler->set_message(0, $full_text);
  }

} else {
  //exit();
}


//echo "MESSAGE_ID: ".$message_id;
//echo "<br>";

$files = array();
// print_r($_FILES);
if (isset($_FILES['answer_files'])) {

  //echo "ПРИКРЕПЛЕНИЕ ФАЙЛА-ОТВЕТА НА ЗАДАНИЕ";
  // echo "<br>";

  for($i=0; $i < count($_FILES['answer_files']['tmp_name']); $i++) {
    if(!is_uploaded_file($_FILES['answer_files']['tmp_name'][$i])){
      continue;
    } else {
      array_push($files, ['name' => $_FILES['answer_files']['name'][$i], 'tmp_name' => $_FILES['answer_files']['tmp_name'][$i], 
              'size' => $_FILES['answer_files']['size'][$i]]);
    }
  }
  $messageHandler->add_files_to_message($commit_id, $message_id, $files, $_POST['type']);

} else if (isset($_FILES['message_files'])) {

  //echo "ПРИКРЕПЛЕНИЕ ФАЙЛА, ПРИЛОЖЕННОГО К СООБЩЕНИЮ";
  //echo "<br>";

  for($i=0; $i < count($_FILES['message_files']['tmp_name']); $i++) {
    if(!is_uploaded_file($_FILES['message_files']['tmp_name'][$i])){
      continue;
    } else {
      array_push($files, ['name' => $_FILES['message_files']['name'][$i], 'tmp_name' => $_FILES['message_files']['tmp_name'][$i], 
              'size' => $_FILES['message_files']['size'][$i]]);
      
    }
  }
  $messageHandler->add_files_to_message($commit_id, $message_id, $files, $_POST['type']);
}


if (isset($_POST['mark'])) {
  // Оценивание задания
  echo "ОЦЕНИВАНИЕ ЗАДАНИЯ";
  $query = update_ax_assignment_mark($assignment_id, $_POST['mark']);
  $result = pg_query($dbconnect, $query);
}

if (isset($_POST['flag_preptable']) && $_POST['flag_preptable']){
  exit;
}

/*echo "UPDATE AFTER ACTION";*/
update_chat($assignment_id, $sender_user_type, $user_id);




// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
// Возвращает id добавленного сообщения

function update_chat($assignment_id, $sender_user_type, $user_id){
  // Содержимое этого div'а JS вставляет в окно чата на taskchat.php 
  echo '<div id="content">';
  //echo "UPDATE_CHAT UPDATE_CHAT UPDATE_CHAT! <br>";
  $messages = get_messages($assignment_id, $sender_user_type, $user_id);
  //echo "SHOW_MESSAGES SHOW_MESSAGES SHOW_MESSAGES! <br>";
  //echo "MESSAGES_COUNT: " . count($messages) . "<br>";
  show_messages($messages);
  echo '</div>';
}

// Возвращает двумерный массив сообщений для текущей страницы по ax_assignment
function get_messages($assignment_id, $sender_user_type, $user_id) {
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
		if ((int) $row['status'] == 0){
      if ($sender_user_type == $row['sender_user_type']) {
        $unreaded = true;
      } else {
        $unreaded = false;
        // echo "sender_user_type: ".$sender_user_type;
        // echo "<br>";
        // echo "MESSAGE_SENDER_USER_TYPE: ".$row['sender_user_type'];
        // echo "<br>";
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
} ?>
