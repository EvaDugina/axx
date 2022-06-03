<?php
require_once("settings.php");

// Находим user_type (0 - студент, 1 - преподаватель)
if (isset($_POST['user_id'])) {
	$query = "SELECT role from students where id = {$_POST['user_id']}";
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	$user_type = $row['role'] == 3 ? 0 : 1;
}

// Если юзер написал сообщение, то добавляем его в БД и отправляем обновленный лог чата
if (isset($_POST['message_text'], $_POST['assignment_id'], $_POST['user_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $user_id = $_POST['user_id'];
    $full_text = $_POST['message_text'];
    $message_id = set_message(0, $full_text);
	// TODO не отправляются некоторые файлы
	// TODO Всякие кавычки ломают код (на 29 строчке)
	if (isset($_FILES['files'])) {
		for ($i = 0; $i < count($_FILES['files']['name']); ++$i) {
			// Перемещаем файл пользователя из временной директории сервера в директорию 'upload_files'
			// Сохраняем файл в БД и удаляем из директории 'upload_files'
			$file_name = basename($_FILES['files']['name'][$i]);
			$files_dir = 'upload_files/';
			$file_path = $files_dir . $file_name;
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $file_path)) {
				$file_full_text = file_get_contents($file_path);
				$query = "INSERT into ax_message_attachment (message_id, file_name, full_text) values ($message_id, '$file_name', '$file_full_text')";
				pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
				unlink($file_path);
			}
		}
	}
	// Содержимое этого div'а JS вставляет в окно чата на taskchat.php 
    echo '<div id="content">';
    show_messages(get_messages());
    echo '</div>';
}

// Обновление лога чата
else if (isset($_POST['assignment_id'], $_POST['user_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $user_id = $_POST['user_id'];
    echo '<div id="content">';
    show_messages(get_messages());
    echo '</div>';
}

// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
// Возвращает id добавленного сообщения
function set_message($type, $full_text) {
	global $dbconnect, $assignment_id, $user_id, $user_type;

	$query = "INSERT into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)
		values ($assignment_id, $type, $user_type, $user_id, now(), null, '$full_text', null, 0);
		SELECT currval('ax_message_id_seq') as \"id\";";
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	return $row['id'];
}

// Возвращает двумерный массив сообщений для текущей страницы по ax_assignment
function get_messages() {
	global $dbconnect, $assignment_id, $user_type, $user_id;
	$query = select_messages($assignment_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	
	$ret = [];
	$is_first_new = false; // false, пока for не обрабатывал новых сообщений от собеседника
	for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
		// Отмечаем сообщения собеседника прочитанными
		// Если у любого препода/студента прогрузилась страница с непрочитанными сообщениями от любого студента/препода, то сообщения отмечаются прочитанными в БД. 
		
		$unreaded = false; // наши сообщения, которые не прочитал собеседник
		$first_new = false; // true, если это первое новое сообщение от собеседника
		if ($row['status'] == 0 && $user_type == $row['sender_user_type']) {
			$unreaded = true;
		}
		if ($row['status'] == 0 && $user_type != $row['sender_user_type']) {
			if (!$is_first_new) {
				$first_new = true;
				$is_first_new = true;
			}
			$query = "UPDATE ax_message set status = 1 where id = {$row['message_id']}";
			pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());

			$query = "INSERT into ax_message_delivery (message_id, recipient_user_id, read) values ({$row['message_id']}, $user_id, true)";
			pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
		}

		$username = $row['first_name'] . ' ' . $row['middle_name'];
		$message_time = explode(" ", $row['date_time']);
		$date = explode("-", $message_time[0]);
		$time = explode(":", $message_time[1]);
		$date_time = $date[2] . "." . $date[1] . "." . $date[0] . " " . $time[0] . ":" . $time[1];
		$attachments = get_message_attachments($row['message_id']);
		$ret[] = ['id' => $row['id'], 'username' => $username, 'full_text' => $row['full_text'], 'date_time' => $date_time, 
            'sender_user_id' => $row['sender_user_id'], 'attachments' => $attachments, 'unreaded' => $unreaded, 'first_new' => $first_new];
	}
	return $ret;
}

// Возвращает двумерный массив вложений для сообщения по message_id
function get_message_attachments($message_id) {
	global $dbconnect;
	$query = select_message_attachment($message_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	
	$ret = [];
	for ($row = pg_fetch_assoc($result); $row; $row = pg_fetch_assoc($result)) {
		// Если текст файла лежит в БД
		if ($row['download_url'] == null) {
			$row['download_url'] = 'download_file.php?attachment_id=' . $row['id'];
		}
		// Если файл лежит на сервере
		else if (!preg_match('#^http[s]{0,1}://#', $row['download_url'])) {
			$row['download_url'] = 'download_file.php?file_path=' . $row['download_url'];
		}
		$ret[] = ['id' => $row['id'], 'file_name' => $row['file_name'], 'download_url' => $row['download_url']];
	}
	return $ret;
}

// Выводит сообщения на страницу
function show_messages($messages) {
	global $user_id;
	// TODO это для скролла
	foreach ($messages as $m) {
		// Прижимаем сообщения текущего пользователя к правой части экрана
		$float_class = $m['sender_user_id'] == $user_id ? 'float-right' : ''; 
		// Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
		// пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
		$background_color_class = $m['unreaded'] ? 'background-color-blue' : '';
		if ($m['first_new']) {
			echo '<div id="new-messages" style="width: 100%; text-align: center">Новые сообщения</div>';
		}
		?>
		<div id="message-<?=$m['id']?>" class="chat-box-message <?=$float_class?>">
			<div class="chat-box-message-wrapper <?=$background_color_class?>">
				<b><?=$m['username']?></b><br>
				<?php
				if ($m['full_text'] != '') {
					echo stripslashes(htmlspecialchars($m['full_text'])) . "<br>";
				}
				foreach ($m['attachments'] as $ma) {?>
					<a href="<?=$ma['download_url']?>" class="task-desc-wrapper-a" target="_blank">
                		<i class="fa-solid fa-file"></i><?=$ma['file_name']?>
					</a><br>
				<?php }?>
			</div>
			<div class="chat-box-message-date">
				<?=$m['date_time']?>
			</div>
		</div>
		<div class="clear"></div>
	<?php }
}?>

<?php
// Копии функций с запросами в БД, когда перенесу свои запросы в dbquires.php, их удалю и сделаю require_once(dbquires.php)
function select_messages($assignment_id) {
    return "SELECT ax_message.id, students.first_name, students.middle_name, ax_message.type, ax_message.full_text, ax_message.date_time, 
        ax_message.sender_user_type, ax_message.sender_user_id, ax_message.id as \"message_id\", ax_message.status
        from ax_message
        inner join students on ax_message.sender_user_id = students.id
        where ax_message.assignment_id = $assignment_id order by date_time";
}

function select_message_attachment($message_id) {
	return "SELECT ax_message_attachment.id, ax_message_attachment.file_name, ax_message_attachment.download_url from ax_message_attachment
	inner join ax_message on ax_message.id = ax_message_attachment.message_id
	where ax_message_attachment.message_id = $message_id";
}
