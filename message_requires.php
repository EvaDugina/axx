<?php
require_once("settings.php");

// Находим user_type (0 - студент, 1 - преподаватель)
if ($_POST['user_id']) {
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
    set_message(0, $full_text, 0);
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


// Возвращает двумерный массив сообщений для текущей страницы по ax_assignment
function get_messages() {
	global $dbconnect, $assignment_id, $user_type, $user_id;
	$query = select_messages($assignment_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	$ret = array();
	while ($row) {
		// Отмечаем сообщения собеседника прочитанными
		// Если у любого препода прогрузились непрочитанные сообщения от любого студента или наоборот, то сообщения отмечаются прочитанными
		$unreaded = false;
		if ($row['status'] == 0 && $user_type == $row['sender_user_type']) {
			$unreaded = true;
		}
		if ($row['status'] == 0 && $user_type != $row['sender_user_type']) {
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
		$ret[] = array('id' => $row['id'], 'username' => $username, 'full_text' => $row['full_text'], 'date_time' => $date_time, 
            'sender_user_id' => $row['sender_user_id'], 'attachments' => $attachments, 'unreaded' => $unreaded);
        
        	$row = pg_fetch_assoc($result);
	}
	return $ret;
}

// Делает запись сообщения и вложений в БД
// type: 0 - переговоры, 2 - оценка
function set_message($type, $full_text) {
	global $dbconnect, $assignment_id, $user_id, $user_type;

	$query = "INSERT into ax_message (assignment_id, type, sender_user_type, sender_user_id, date_time, reply_to_id, full_text, commit_id, status)
		values ($assignment_id, $type, $user_type, $user_id, now(), null, '$full_text', null, 0)";
	pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
}

// Возвращает двумерный массив вложений для сообщения по message_id
function get_message_attachments($message_id) {
	global $dbconnect;
	$query = select_message_attachment($message_id);
	$result = pg_query($dbconnect, $query) or die('Ошибка запроса: ' . pg_last_error());
	$row = pg_fetch_assoc($result);
	$ret = array();
	while ($row) {
		$ret[] = array('file_name' => $row['file_name'], 'download_url' => $row['download_url'], 'full_text' => $row['full_text']);
		$row = pg_fetch_assoc($result);
	}
	return $ret;
}

// Выводит сообщения на страницу
function show_messages($messages) {
	global $user_id;
	$i=0;
	foreach ($messages as $m) {
		// Прижимаем сообщения текущего пользователя к правой части экрана
		$float_class = $m['sender_user_id'] == $user_id ? 'float-right' : ''; 
		// Если студент написал сообщение, то у всех студентов сообщение подсвечивается синим, 
		// пока один из преподов его не прочитает(прочитать = прогрузить страницу с чатом). И наоборот
		$border_color_class = $m['unreaded'] ? 'border-color-blue' : ''; ?>
		<div id="message-<?=$i?>-<?=$m['id']?>" class="chat-box-message <?=$float_class?>">
			<div class="chat-box-message-wrapper <?=$border_color_class?>">
				<b><?=$m['username']?></b><br>
				<?=stripslashes(htmlspecialchars($m['full_text'])) ?>
				<br>

				<?php
				foreach ($m['attachments'] as $ma) {?>
					<a href="<?=$ma['download_url']?>" class="task-desc-wrapper-a" target="_blank">
                				<i class="fa-solid fa-file"></i><?=$ma['file_name']?></a><br>
				<?php }?>
			</div>
			<div class="chat-box-message-date">
				<?=$m['date_time']?>
			</div>
		</div>
		<div class="clear"></div>
	<?php
	$i++;
	}
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
	return "SELECT ax_message_attachment.file_name, ax_message_attachment.download_url, ax_message_attachment.full_text from ax_message_attachment
	inner join ax_message on ax_message.id = ax_message_attachment.message_id
	where ax_message_attachment.message_id = $message_id";
}
